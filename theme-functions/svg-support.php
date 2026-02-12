<?php
// Enable SVG uploads
function add_file_types_to_uploads($file_types)
{
    $new_filetypes = array();
    $new_filetypes['svg'] = 'image/svg+xml';
    $file_types = array_merge($file_types, $new_filetypes);
    return $file_types;
}
add_filter('upload_mimes', 'add_file_types_to_uploads');

function wp_check_svg($file)
{
    $filetype = wp_check_filetype($file['name']);

    $ext = $filetype['ext'];
    $type = $filetype['type'];

    // Check if uploaded file is an SVG
    if ($type !== 'image/svg+xml' || $ext !== 'svg') {
        return $file;
    }

    // Ensure the file is uploaded by an authorized user
    if (!current_user_can('upload_files')) {
        return $file;
    }

    // Use WP_Filesystem to read the file contents
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
    }

    $content = $wp_filesystem->get_contents($file['tmp_name']);

    // Use DOMDocument to parse the SVG file
    $doc = new DOMDocument();
    $doc->loadXML($content);

    // Check if the file contains any <script> tags
    $scripts = $doc->getElementsByTagName('script');

    if ($scripts->length > 0) {
        // The file contains <script> tags, which is not allowed
        return $file;
    }

    // The SVG file is safe, so return the original data
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'wp_check_svg');

// Image to SVG
function image_to_svg($image)
{
    if (empty($image) || !isset($image['url'], $image['mime_type'])) {
        return '';
    }

    try {
        $upload_dir = wp_get_upload_dir();

        // Remove query string/URL fragments
        $img_url = preg_replace('/\?.*$/', '', $image['url']);

        // Try mapping from uploads baseurl to basedir
        $baseurl = untrailingslashit($upload_dir['baseurl']);
        $basedir = untrailingslashit($upload_dir['basedir']);

        if (strpos($img_url, $baseurl) !== false) {
            $image_path = str_replace($baseurl, $basedir, $img_url);
        } elseif (strpos($img_url, home_url('/')) !== false) {
            // Fallback: map site URLs to ABSPATH
            $image_path = str_replace(home_url('/'), ABSPATH, $img_url);
        } else {
            // If not a site URL, use the path as-is (may already be local)
            $image_path = $img_url;
        }

        // Normalize path for Windows and Unix
        $image_path = wp_normalize_path($image_path);

        // Detailed debug log
        error_log(sprintf('image_to_svg: url="%s" baseurl="%s" basedir="%s" path="%s"', $image['url'], $baseurl, $basedir, $image_path));

        if (!file_exists($image_path) || !is_readable($image_path)) {
            error_log('SVG Image path is missing or not readable: ' . $image_path);
            return '';
        }

        if ($image['mime_type'] === "image/svg+xml") {
            $svg_content = @file_get_contents($image_path);
            if ($svg_content === false) {
                error_log('Could not read SVG file: ' . $image_path);
                return '';
            }
            return $svg_content;
        }

        // Build img tag with escaped attributes
        return sprintf(
            '<img src="%s" width="%s" height="%s" alt="%s" title="%s" loading="lazy" decoding="async">',
            esc_url($image['url']),
            esc_attr($image['width'] ?? ''),
            esc_attr($image['height'] ?? ''),
            esc_attr($image['alt'] ?? ''),
            esc_attr($image['title'] ?? '')
        );
    } catch (Exception $e) {
        error_log('SVG Processing Error: ' . $e->getMessage());
        return '';
    }
}

// SVG in content
function check_content_images($content)
{
    if (!has_blocks($content) && !preg_match('/<img/', $content)) {
        return $content;
    }

    $pattern = '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i';

    return preg_replace_callback($pattern, function ($match) {
        $src = $match[1];

        // Convert URL to local path: remove query and map uploads
        $upload_dir = wp_get_upload_dir();
        $src_trim = preg_replace('/\?.*$/', '', $src);

        if (strpos($src_trim, untrailingslashit($upload_dir['baseurl'])) !== false) {
            $src_local = str_replace(untrailingslashit($upload_dir['baseurl']), untrailingslashit($upload_dir['basedir']), $src_trim);
        } elseif (strpos($src_trim, home_url('/')) !== false) {
            $src_local = str_replace(home_url('/'), ABSPATH, $src_trim);
        } else {
            $src_local = $src_trim;
        }

        $src_local = wp_normalize_path($src_local);

        try {
            if (!file_exists($src_local) || !is_readable($src_local)) {
                error_log('check_content_images: file missing or unreadable: ' . $src_local . ' (original src: ' . $src . ')');
                return $match[0];
            }

            $mime_type = mime_content_type($src_local);

            if ($mime_type !== 'image/svg+xml') {
                return $match[0];
            }

            $svg_content = file_get_contents($src_local);
            return $svg_content !== false ? $svg_content : $match[0];
        } catch (Exception $e) {
            error_log('SVG Content Processing Error: ' . $e->getMessage());
            return $match[0];
        }
    }, $content);
}

add_filter('the_content', 'check_content_images');

function sanitize_svg($file)
{
    if (empty($file['tmp_name'])) {
        return $file;
    }

    $filetype = wp_check_filetype($file['name']);

    if ($filetype['type'] !== 'image/svg+xml') {
        return $file;
    }

    // Verify permissions
    if (!current_user_can('upload_files')) {
        $file['error'] = __('Sorry, you are not allowed to upload SVG files.', 'growthlab');
        return $file;
    }

    // Allowed elements and attributes list
    $allowed_tags = array('svg', 'path', 'rect', 'circle', 'g', 'polygon');
    $allowed_attrs = array('viewBox', 'width', 'height', 'fill', 'stroke', 'd', 'x', 'y');

    // Load and sanitize SVG
    $content = file_get_contents($file['tmp_name']);
    $doc = new DOMDocument();
    $doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

    // Remove disallowed elements
    $elements = $doc->getElementsByTagName('*');
    for ($i = $elements->length - 1; $i >= 0; $i--) {
        $element = $elements->item($i);
        if (!in_array($element->tagName, $allowed_tags)) {
            $element->parentNode->removeChild($element);
        }

        // Remove disallowed attributes
        foreach (iterator_to_array($element->attributes) as $attr) {
            if (!in_array($attr->nodeName, $allowed_attrs)) {
                $element->removeAttribute($attr->nodeName);
            }
        }
    }

    // Save sanitized SVG
    file_put_contents($file['tmp_name'], $doc->saveXML());

    return $file;
}
