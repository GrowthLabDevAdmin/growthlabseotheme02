<?php

/**
 * Responsive Image Helper Functions
 * Generates <picture> elements with WebP support and multiple breakpoints.
 */

// Initialize global breakpoints for responsive image handling
// Maps breakpoint names to their minimum viewport widths
if (!isset($GLOBALS['breakpoints'])) {
    $GLOBALS['breakpoints'] = [
        'mobile' => '0px',   // Mobile devices (0px and up)
        'tablet' => '600px',  // Tablet devices (600px and up)
        'ldpi'   => '1024px', // Low DPI screens (1024px and up)
        'mdpi'   => '1200px', // Medium DPI screens (1200px and up)
        'hdpi'   => '1440px', // High DPI screens (1440px and up)
    ];
}

// Global map for storing preferred image sizes per context
if (!isset($GLOBALS['preferred_size_map'])) {
    $GLOBALS['preferred_size_map'] = [];
}

// Cache for storing image metadata to optimize performance
if (!isset($GLOBALS['img_metadata_cache'])) {
    $GLOBALS['img_metadata_cache'] = [];
}

/**
 * Get breakpoint ranges as integer values
 * 
 * Converts breakpoint values from string pixels to integers.
 * 
 * @return array Associative array of breakpoint names and their pixel values
 */
if (!function_exists('po_get_breakpoint_ranges')) {
    function po_get_breakpoint_ranges(): array
    {
        $ranges = [];
        foreach ($GLOBALS['breakpoints'] as $name => $value) {
            $ranges[$name] = (int) $value;
        }
        return $ranges;
    }
}

/**
 * Generate CSS media query for a specific breakpoint
 * 
 * @param string $breakpoint The breakpoint name (mobile, tablet, ldpi, mdpi, hdpi)
 * @return string|null The media query string or null if breakpoint doesn't exist
 */
if (!function_exists('po_get_media_query')) {
    function po_get_media_query(string $breakpoint): ?string
    {
        $ranges = po_get_breakpoint_ranges();
        if (!isset($ranges[$breakpoint]) || $ranges[$breakpoint] === 0) {
            return null;
        }
        return "(min-width: {$ranges[$breakpoint]}px)";
    }
}

/**
 * Initialize available image sizes
 * 
 * Collects all registered WordPress image sizes (thumbnails, medium, large, etc.)
 * and stores them in the global sizes array. Called on theme setup.
 * 
 * @return void
 */
if (!function_exists('po_init_sizes')) {
    function po_init_sizes(): void
    {
        $sizes = [];
        // Get standard WordPress image sizes
        if (function_exists('get_intermediate_image_sizes')) {
            $sizes = (array) get_intermediate_image_sizes();
        }
        // Add any custom registered image sizes
        global $_wp_additional_image_sizes;
        if (is_array($_wp_additional_image_sizes)) {
            foreach ($_wp_additional_image_sizes as $size_name => $size_data) {
                if (!in_array($size_name, $sizes, true)) {
                    $sizes[] = $size_name;
                }
            }
        }
        // Always include 'full' size for original images
        if (!in_array('full', $sizes, true)) {
            $sizes[] = 'full';
        }
        $GLOBALS['sizes'] = array_values($sizes);
    }
}

add_action('after_setup_theme', 'po_init_sizes', 999);

if (!function_exists('po_get_registered_width')) {
    function po_get_registered_width(string $size): int
    {
        global $_wp_additional_image_sizes;
        if (!empty($_wp_additional_image_sizes[$size]['width'])) {
            return (int) $_wp_additional_image_sizes[$size]['width'];
        }
        switch ($size) {
            case 'thumbnail':
                return (int) get_option('thumbnail_size_w');
            case 'medium':
                return (int) get_option('medium_size_w');
            case 'large':
                return (int) get_option('large_size_w');
        }
        return 0;
    }
}

/**
 * Get breakpoints sorted in descending order by width
 * 
 * Returns breakpoint names ordered from largest to smallest viewport width.
 * Useful for processing breakpoints from desktop down to mobile.
 * 
 * @return array Breakpoint names in descending width order
 */
if (!function_exists('po_get_breakpoint_order')) {
    function po_get_breakpoint_order(): array
    {
        $ranges = po_get_breakpoint_ranges();
        // Sort in descending order (largest screens first)
        arsort($ranges);
        return array_keys($ranges);
    }
}

/**
 * Get the minimum width of the next smaller breakpoint
 * 
 * Useful for determining the upper ceiling width constraint for a breakpoint.
 * Returns the minimum width of the breakpoint immediately smaller than the given one.
 * 
 * @param string $breakpoint The current breakpoint name
 * @return int|null The width ceiling for this breakpoint, or null if at smallest breakpoint
 */
if (!function_exists('po_get_next_breakpoint_min')) {
    function po_get_next_breakpoint_min(string $breakpoint): ?int
    {
        $ranges = po_get_breakpoint_ranges();
        $keys   = array_keys($ranges);
        $index  = array_search($breakpoint, $keys, true);
        // Return null if breakpoint not found or if it's the last (smallest) breakpoint
        if ($index === false || $index >= count($keys) - 1) {
            return null;
        }
        return $ranges[$keys[$index + 1]];
    }
}

/**
 * Select the best image size candidate for a specific breakpoint
 * 
 * Intelligently chooses the optimal image size from available options based on
 * breakpoint constraints, registered widths, and usage history.
 * 
 * @param string $breakpoint The breakpoint to select for
 * @param array $available Associative array of available sizes and their widths
 * @param string $max_size Maximum allowed image size
 * @param string $min_size Minimum allowed image size
 * @param array $already_used Sizes already used to avoid duplication
 * @return string|null The selected size name or null if no suitable candidate
 */
if (!function_exists('po_select_candidate')) {
    function po_select_candidate(
        string $breakpoint,
        array  $available,
        string $max_size,
        string $min_size,
        array  $already_used = []
    ): ?string {
        if ($max_size === $min_size) return null;

        $bp_min      = po_get_breakpoint_ranges()[$breakpoint] ?? 0;
        $ceiling     = po_get_next_breakpoint_min($breakpoint);
        $max_reg_w   = ($max_size !== 'full') ? po_get_registered_width($max_size) : 0;
        $min_reg_w   = ($min_size !== '')  ? po_get_registered_width($min_size) : 0;
        $prelast_bp  = po_get_breakpoint_order()[array_key_last(po_get_breakpoint_order()) - 1] ?? null;

        $available[$min_size] = isset($available[$min_size]) ?: null;

        $reference = $min_reg_w !== 0 ? $available[$min_size] : null;

        if ($reference !== null) {
            $available = array_filter($available, fn($v) => $v >= $reference);
        }

        asort($available);
        $available_keys = array_keys($available);

        if ($max_size !== 'full' && $max_reg_w > 0) {
            if (isset($available[$max_size]) && !in_array($max_size, $already_used, true)) {
                $index   = array_search($max_size, $available_keys, true);
                $prevKey = $available_keys[$index - 1] ?? null;
                if ($breakpoint !== $prelast_bp && $available[$max_size] < $bp_min) return null;
                if ($min_reg_w > 0 && $available[$prevKey] === $available[$min_size] && $breakpoint !== $prelast_bp) return null;
                return $max_size;
            }
            $candidates = [];
            foreach ($available as $size => $real_w) {
                if ($min_reg_w > 0 && $available[$size] <= $available[$min_size]) continue;
                if (in_array($size, $already_used, true)) continue;
                if ($min_reg_w > 0 && po_get_registered_width($size) < $min_reg_w && $size !== "full") continue;
                if ($size === "full" && !empty($already_used)) continue;
                $reg_w     = po_get_registered_width($size);
                $compare_w = ($reg_w > 0) ? $reg_w : $real_w;
                if ($size === "full") {
                    foreach ($available as $key => $value) {
                        if ($key !== "full" && po_get_registered_width($key) >= $real_w && $real_w >= $value) {
                            $compare_w = po_get_registered_width($key);
                        }
                    }
                }
                if ($breakpoint !== $prelast_bp && $compare_w < $bp_min) continue;
                if ($ceiling !== null && $compare_w > $ceiling) continue;
                if ($compare_w <= $max_reg_w) {
                    $candidates[$size] = $real_w;
                }
            }
            if (empty($candidates)) return null;
            arsort($candidates);
            return array_key_first($candidates);
        }

        $candidates = [];
        foreach ($available as $size => $real_w) {
            if ($min_reg_w > 0 && $available[$size] <= $available[$min_size]) continue;
            if (in_array($size, $already_used, true)) continue;
            if ($min_reg_w > 0 && po_get_registered_width($size) < $min_reg_w && $size !== "full") continue;
            if ($size === "full" && !empty($already_used)) continue;
            $reg_w   = po_get_registered_width($size);
            $compare = ($reg_w > 0) ? $reg_w : $real_w;
            if ($size === "full" && $breakpoint === $prelast_bp) {
                $s = [];
                foreach ($available as $key => $value) {
                    if ($key !== "full" && po_get_registered_width($key) >= $real_w && $real_w >= $value && po_get_registered_width($key) < $ceiling) {
                        $s[$key] = po_get_registered_width($key);
                    }
                }
                asort($s);
                $compare = array_last($s) + 1;
            }
            if ($breakpoint !== $prelast_bp && ($compare < $bp_min || $real_w < $bp_min)) continue;
            if ($breakpoint === $prelast_bp && $compare < $bp_min) continue;
            if ($ceiling !== null && $compare > $ceiling) continue;
            $candidates[$size] = $compare;
        }

        if (empty($candidates)) return null;
        arsort($candidates);
        return array_key_first($candidates);
    }
}

/**
 * Get all available sizes for a specific image attachment
 * 
 * Collects all registered image sizes that exist for the given attachment ID,
 * returning their actual widths.
 * 
 * @param array $img_meta Image metadata from WordPress
 * @param int $img_id The attachment post ID
 * @return array Associative array of available sizes and their widths
 */
if (!function_exists('img_get_available_sizes')) {
    function img_get_available_sizes(array $img_meta, int $img_id): array
    {
        $available = [];
        foreach ($GLOBALS['sizes'] as $size) {
            // Handle full-size image separately
            if ($size === 'full') {
                $available['full'] = (int) ($img_meta['width'] ?? 0);
                continue;
            }
            // Check if this size exists for the attachment
            $url = wp_get_attachment_image_url($img_id, $size);
            if (!$url) continue;
            // Store the actual width of this size
            $w = (int) ($img_meta['sizes'][$size]['width'] ?? 0);
            if ($w === 0) continue;
            $available[$size] = $w;
        }
        return $available;
    }
}

/**
 * Convert image URL to filesystem path
 * 
 * Converts a WordPress media URL to its corresponding server filesystem path.
 * Handles both uploaded files and theme/plugin resources.
 * 
 * @param string $url The image URL
 * @return string The filesystem path to the image
 */
if (!function_exists('img_url_to_path')) {
    function img_url_to_path(string $url): string
    {
        $upload   = wp_get_upload_dir();
        $baseurl  = untrailingslashit($upload['baseurl']);
        $basepath = untrailingslashit($upload['basedir']);
        // Check if URL is in uploads directory
        if (strpos($url, $baseurl) === 0) {
            return $basepath . substr($url, strlen($baseurl));
        }
        // Fallback: replace home URL with ABSPATH
        return str_replace(home_url('/'), ABSPATH, $url);
    }
}

/**
 * Check if a WebP version of an image exists
 * 
 * Determines whether a WebP alternative format exists for the given image.
 * Uses caching to avoid repeated filesystem checks.
 * 
 * @param string $url The image URL
 * @param string $mime_type The MIME type of the image
 * @return bool True if WebP version exists or image is already WebP
 */
if (!function_exists('img_has_webp')) {
    function img_has_webp(string $url, string $mime_type = ''): bool
    {
        static $cache = []; // Cache results to avoid repeated disk I/O
        $key = $url . '|' . $mime_type;
        if (isset($cache[$key])) return $cache[$key];
        // If already WebP, return true immediately
        if ($mime_type === 'image/webp' || str_ends_with(strtolower($url), '.webp')) {
            return $cache[$key] = true;
        }
        // Check if WebP file exists on disk
        $path  = img_url_to_path($url) . '.webp';
        $cache[$key] = file_exists($path);
        return $cache[$key];
    }
}

/**
 * Get the WebP URL for an image
 * 
 * Returns the WebP format URL for an image. If the image is already WebP,
 * returns the original URL unchanged.
 * 
 * @param string $url The original image URL
 * @param string $mime_type The MIME type of the image
 * @return string The WebP image URL
 */
if (!function_exists('img_get_webp_url')) {
    function img_get_webp_url(string $url, string $mime_type = ''): string
    {
        // If already WebP format, return as-is
        if ($mime_type === 'image/webp' || str_ends_with(strtolower($url), '.webp')) {
            return $url;
        }
        // Append .webp extension to URL
        return $url . '.webp';
    }
}

/**
 * Create an HTML source element for a picture tag
 * 
 * Generates a <source> element with lazy-loading support via data-srcset.
 * Used with media queries to serve appropriate image formats.
 * 
 * @param string $url The image URL
 * @param string $mime_type The MIME type (e.g., 'image/webp', 'image/jpeg')
 * @param string|null $media Optional CSS media query
 * @return string HTML source element string
 */
if (!function_exists('img_create_source')) {
    function img_create_source(string $url, string $mime_type, ?string $media = null): string
    {
        $srcset = "data-srcset='" . esc_url($url) . "'";
        $type   = "type='"       . esc_attr($mime_type) . "'";
        // Add media query attribute if provided
        $media  = $media ? " media='" . esc_attr($media) . "'" : '';
        return "<source {$srcset} {$type}{$media}>";
    }
}

/**
 * Add image sources with WebP support to sources array
 * 
 * Intelligently adds source elements, automatically including WebP alternatives
 * when available. Manages format ordering to prioritize WebP for modern browsers.
 * 
 * @param array $sources Reference to sources array (modified in place)
 * @param string $url The image URL
 * @param string $mime_type The MIME type of the original image
 * @param string|null $media Optional CSS media query
 * @return void
 */
if (!function_exists('img_push_source')) {
    function img_push_source(array &$sources, string $url, string $mime_type, ?string $media): void
    {
        // Check if this is already a WebP image
        $is_native_webp = (
            $mime_type === 'image/webp' ||
            str_ends_with(strtolower($url), '.webp')
        );
        if ($is_native_webp) {
            // WebP images need no conversion
            $sources[] = img_create_source($url, 'image/webp', $media);
            return;
        }
        // Try to add WebP alternative if it exists
        if (img_has_webp($url, $mime_type)) {
            $sources[] = img_create_source(img_get_webp_url($url, $mime_type), 'image/webp', $media);
        }
        // Always add fallback to original format
        $sources[] = img_create_source($url, $mime_type, $media);
    }
}

// ---------------------------------------------------------------------------
// img_create_img_tag()
//
// CHANGE: Width and height parameters replaced with bp_ratios array.
//
// $bp_ratios is an associative array containing actual image dimensions
// served at each breakpoint:
//   [
//     'mobile'  => ['w' => 480,  'h' => 320],
//     'tablet'  => ['w' => 800,  'h' => 533],
//     'ldpi'    => ['w' => 1024, 'h' => 683],
//     'hdpi'    => ['w' => 1920, 'h' => 1280],
//   ]
//
// For lazy-loaded images (is_priority = false):
//   - Dimensions are emitted as CSS custom properties in style attribute:
//     --ar-mobile-w, --ar-mobile-h, --ar-tablet-w, --ar-tablet-h, etc.
//   - Global stylesheet consumes these with media queries to assign
//     correct aspect-ratio per breakpoint without fixed dimensions.
//   - NO width/height attributes are emitted to avoid fixed sizing.
//
// For priority images (is_priority = true):
//   - Explicit width/height attributes from mobile breakpoint (previous behavior),
//     because browser needs them for LCP and fetchpriority.
//   - Custom properties are NOT emitted.
// ---------------------------------------------------------------------------

/**
 * Create an HTML img tag with responsive attributes
 * 
 * Generates an img element with lazy-loading support, responsive dimensions,
 * and aspect ratio preservation. Handles both priority and lazy-loaded images.
 * 
 * @param string $src The image source URL
 * @param array $bp_ratios Dimensions per breakpoint: ['breakpoint' => ['w' => int, 'h' => int]]
 * @param int $orig_width Original image width
 * @param int $orig_height Original image height
 * @param string $alt Alt text for accessibility
 * @param bool $is_priority Whether this is a priority image (eager load)
 * @param string $extra Additional HTML attributes to append
 * @return string HTML img tag string
 */
if (!function_exists('img_create_img_tag')) {
    function img_create_img_tag(
        string $src,
        array  $bp_ratios    = [],   // ['mobile'=>['w'=>int,'h'=>int], 'tablet'=>[...], ...]
        int    $orig_width   = 0,
        int    $orig_height  = 0,
        string $alt          = '',
        bool   $is_priority  = false,
        string $extra        = ''
    ): string {
        $loading       = $is_priority ? 'eager' : 'lazy';
        $fetchpriority = $is_priority ? " fetchpriority='high'" : '';

        if ($is_priority) {
            // For priority images: use eager loading
            $src_attr   = "src='" . esc_url($src) . "'";
            $class_attr = '';

            // For priority: emit width/height from mobile size
            $mob        = $bp_ratios['mobile'] ?? [];
            $width_attr  = !empty($mob['w']) ? "width='"  . (int) $mob['w'] . "'" : '';
            $height_attr = !empty($mob['h']) ? "height='" . (int) $mob['h'] . "'" : '';
            $style_attr  = '';
        } else {
            // For lazy-loaded images: use data-src
            $src_attr    = "data-src='" . esc_url($src) . "'";
            $class_attr  = "class='lazy-image'";
            $width_attr  = '';
            $height_attr = '';

            // Build CSS custom properties for each breakpoint
            // Results in style attribute like:
            //   --ar-mobile-w:480; --ar-mobile-h:320; --ar-tablet-w:800; ...
            $props = [];
            foreach ($bp_ratios as $bp => $dims) {
                if (!empty($dims['w']) && !empty($dims['h'])) {
                    $props[] = "--ar-{$bp}-w:{$dims['w']}";
                    $props[] = "--ar-{$bp}-h:{$dims['h']}";
                }
            }
            $style_attr = !empty($props) ? "style='" . implode(';', $props) . "'" : '';
        }

        $alt_attr      = "alt='"      . esc_attr($alt) . "'";
        $loading_attr  = "loading='"  . $loading . "'";
        $decoding_attr = "decoding='async'";

        // Store original aspect ratio for external use
        // Calculate reduced fraction using GCD (greatest common divisor)
        $aspect_ratio = '';
        if ($orig_width > 0 && $orig_height > 0) {
            $gcd_fn = null;
            // Recursive function to calculate GCD
            $gcd_fn = function (int $a, int $b) use (&$gcd_fn): int {
                return $b === 0 ? $a : $gcd_fn($b, $a % $b);
            };
            $gcd          = $gcd_fn($orig_width, $orig_height);
            $aspect_ratio = " data-aspect-ratio='" . ($orig_width / $gcd) . ':' . ($orig_height / $gcd) . "'";
        }

        $extra_attr = $extra ? ' ' . wp_kses_post($extra) : '';

        $parts = array_filter([
            $src_attr,
            $width_attr,
            $height_attr,
            $style_attr,
            $alt_attr,
            $loading_attr . $fetchpriority,
            $decoding_attr,
            $class_attr,
        ]);

        return '<img ' . implode(' ', $parts) . $aspect_ratio . $extra_attr . '>';
    }
}

/**
 * Wrap source elements and img tag in a picture element
 * 
 * Creates a complete picture element with fallback for no-script environments.
 * Automatically converts data-srcset to srcset for priority images.
 * 
 * @param array $sources Array of HTML source element strings
 * @param string $img_tag The HTML img tag string
 * @param string $classes CSS classes for the picture element
 * @param string $id HTML id for the picture element
 * @param bool $is_priority Whether this is a priority image (affects lazy-loading setup)
 * @return string Complete HTML picture element string
 */
if (!function_exists('img_wrap_picture')) {
    function img_wrap_picture(
        array  $sources,
        string $img_tag,
        string $classes     = '',
        string $id          = '',
        bool   $is_priority = false,
    ): string {
        $id_attr    = $id      ? " id='"    . esc_attr($id)      . "'" : '';
        $class_attr = $classes ? " class='" . esc_attr($classes) . "'" : '';

        if ($is_priority) {
            // For priority images: convert data-srcset to srcset for immediate loading
            $sources  = array_map(fn($s) => str_replace('data-srcset=', 'srcset=', $s), $sources);
            $noscript = '';
        } else {
            // For lazy-loaded images: provide fallback for noscript environments
            $fallback_sources = array_map(fn($s) => str_replace('data-srcset=', 'srcset=', $s), $sources);
            // Convert data-src to src and remove lazy-image class
            $fallback_img     = str_replace(['data-src=', "class='lazy-image'"], ['src=', ''], $img_tag);
            $noscript = '<noscript><picture' . $id_attr . $class_attr . '>'
                . implode('', $fallback_sources)
                . $fallback_img
                . '</picture></noscript>';
        }

        return '<picture' . $id_attr . $class_attr . '>'
            . implode('', $sources)
            . $img_tag
            . '</picture>'
            . $noscript;
    }
}

/**
 * Get empty/default image fields structure
 * 
 * Returns a template array with all required image field keys.
 * Used as fallback when image data is unavailable.
 * 
 * @return array Template array with empty image fields
 */
if (!function_exists('img_get_empty_fields')) {
    function img_get_empty_fields(): array
    {
        return [
            'id'        => 0,           // Attachment post ID
            'url'       => '',          // Full-size image URL
            'width'     => 0,           // Image width in pixels
            'height'    => 0,           // Image height in pixels
            'alt'       => '',          // Alt text
            'mime_type' => 'image/jpeg', // Image MIME type
            'urls'      => [],          // Array of URLs for each registered size
            'meta'      => [],          // WordPress attachment metadata
        ];
    }
}

/**
 * Parse and normalize image data into standardized fields
 * 
 * Handles both array format (ACF field data) and string format (image URL).
 * Extracts image metadata, dimensions, and available sizes.
 * 
 * @param array|string $img Image data as array or URL string
 * @return array Normalized image fields array
 */
if (!function_exists('img_parse_fields')) {
    function img_parse_fields(array|string $img): array
    {
        if (is_array($img)) {
            // Handle ACF image field data
            $acf_sizes = isset($img['sizes']) && is_array($img['sizes']) ? $img['sizes'] : [];
            $id        = (int) ($img['ID'] ?? $img['id'] ?? 0);
            // Get WordPress attachment metadata
            $meta      = $id ? wp_get_attachment_metadata($id) : [];
            // Build URL map for each registered size
            $urls      = ['full' => $img['url'] ?? ''];
            foreach ($GLOBALS['sizes'] as $size) {
                if ($size === 'full') continue;
                $urls[$size] = $acf_sizes[$size] ?? ($img['url'] ?? '');
            }
            return [
                'id'        => $id,
                'url'       => $img['url']      ?? '',
                'width'     => (int) ($img['width']  ?? 0),
                'height'    => (int) ($img['height'] ?? 0),
                'alt'       => $img['alt']       ?? '',
                'mime_type' => $img['mime_type'] ?? 'image/jpeg',
                'urls'      => $urls,
                'meta'      => $meta ?: [],
            ];
        }

        // Handle image URL string format
        $url = (string) $img;
        // Convert URL to attachment post ID
        $id  = attachment_url_to_postid($url);
        if (!$id) return img_get_empty_fields();
        // Get attachment metadata from WordPress
        $meta = wp_get_attachment_metadata($id);
        if (!$meta) return img_get_empty_fields();
        $mime_type = get_post_mime_type($id) ?: 'image/jpeg';
        // Build URL map for all registered sizes
        $urls = ['full' => wp_get_attachment_url($id)];
        foreach ($GLOBALS['sizes'] as $size) {
            if ($size === 'full') continue;
            $size_url    = wp_get_attachment_image_url($id, $size);
            // Fallback to full URL if size doesn't exist
            $urls[$size] = $size_url ?: $urls['full'];
        }
        return [
            'id'        => $id,
            'url'       => $urls['full'],
            'width'     => (int) ($meta['width']  ?? 0),
            'height'    => (int) ($meta['height'] ?? 0),
            'alt'       => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
            'mime_type' => $mime_type,
            'urls'      => $urls,
            'meta'      => $meta,
        ];
    }
}

/**
 * Get image fields with caching
 * 
 * Wrapper around img_parse_fields() that caches results to avoid repeated
 * metadata lookups for the same image.
 * 
 * @param array|string $img Image data as array or URL string
 * @return array Normalized image fields array
 */
if (!function_exists('img_get_fields')) {
    function img_get_fields(array|string $img): array
    {
        // Generate cache key from image data
        $cache_key = is_array($img) ? md5(serialize($img)) : md5((string) $img);
        // Return cached result if available
        if (isset($GLOBALS['img_metadata_cache'][$cache_key])) {
            return $GLOBALS['img_metadata_cache'][$cache_key];
        }
        // Parse fields and cache the result
        $result = img_parse_fields($img);
        $GLOBALS['img_metadata_cache'][$cache_key] = $result;
        return $result;
    }
}

// ---------------------------------------------------------------------------
// img_generate_standard_picture()
//
// CHANGE: Builds $bp_ratios array accumulating actual dimensions of the
// selected candidate for each breakpoint. This array is passed to
// img_create_img_tag() instead of previous $width/$height parameters.
//
// For mobile: uses mobile_img or selected mobile candidate from main image.
// For each non-mobile breakpoint: uses candidate resolved by po_select_candidate().
// If a breakpoint has no candidate, CSS cascades to inherit dimensions from
// the immediately lower breakpoint.
// ---------------------------------------------------------------------------

/**
 * Generate a responsive picture element for standard images
 * 
 * Creates a picture element with multiple source elements for different breakpoints.
 * Intelligently selects appropriate image sizes based on viewport and constraints.
 * Supports optional dedicated mobile and tablet images.
 * 
 * @param array $fields Primary image fields data
 * @param array|string $tablet_img Optional tablet-specific image
 * @param array|string $mobile_img Optional mobile-specific image
 * @param string $max_size Maximum allowed image size
 * @param string $min_size Minimum allowed image size
 * @param string $classes CSS classes for picture element
 * @param string $id HTML id for picture element
 * @param string $alt Alt text for accessibility
 * @param bool $is_priority Whether this is a priority image (eager load)
 * @param string $extra Additional HTML attributes
 * @return string HTML picture element string
 */
if (!function_exists('img_generate_standard_picture')) {
    function img_generate_standard_picture(
        array        $fields,
        array|string $tablet_img  = [],
        array|string $mobile_img  = [],
        string       $max_size    = 'full',
        string       $min_size    = '',
        string       $classes     = '',
        string       $id          = '',
        string       $alt         = '',
        bool         $is_priority = false,
        string       $extra       = ''
    ): string {

        // Initialize variables
        $sources      = [];        // Array to collect source elements
        $already_used = [];        // Track which sizes have been used
        $bp_order     = po_get_breakpoint_order(); // Breakpoints from largest to smallest
        $available    = img_get_available_sizes($fields['meta'], $fields['id']);
        $alt_text     = $alt ?: $fields['alt'];
        $mime_type    = $fields['mime_type'];

        // Check for optional dedicated tablet/mobile images
        $has_tablet    = !empty($tablet_img);
        $has_mobile    = !empty($mobile_img);
        $tablet_fields = $has_tablet ? img_get_fields($tablet_img) : null;
        $mobile_fields = $has_mobile ? img_get_fields($mobile_img) : null;

        // Accumulate actual dimensions for each breakpoint to use as CSS custom properties
        $bp_ratios = [];

        foreach ($bp_order as $bp) {

            if ($bp === 'mobile') {
                if ($has_mobile) {
                    $mobile_url  = $mobile_fields['url'];
                    $mobile_mime = $mobile_fields['mime_type'];
                    $mob_w       = $mobile_fields['width'];
                    $mob_h       = $mobile_fields['height'];
                } else {
                    $mobile_size = '';
                    if ($min_size !== '' && isset($available[$min_size])) {
                        $mobile_size = $min_size;
                    } else {
                        foreach (['cover-mobile', 'content', 'featured-small', 'medium', 'thumbnail'] as $s) {
                            if (isset($available[$s])) {
                                $mobile_size = $s;
                                break;
                            }
                        }
                    }
                    if (!$mobile_size) $mobile_size = 'full';

                    $mobile_url  = $fields['urls'][$mobile_size] ?? $fields['url'];
                    $mobile_mime = $mime_type;
                    $mob_w       = $fields['meta']['sizes'][$mobile_size]['width']  ?? $fields['width'];
                    $mob_h       = $fields['meta']['sizes'][$mobile_size]['height'] ?? $fields['height'];
                }

                // Store mobile dimensions for CSS custom properties
                $bp_ratios['mobile'] = ['w' => $mob_w, 'h' => $mob_h];

                // Add source without media query (mobile is default)
                img_push_source($sources, $mobile_url, $mobile_mime, null);
                continue;
            }

            // Handle tablet breakpoint
            if ($bp === 'tablet' && $has_tablet) {
                $url   = $tablet_fields['url'];
                $media = po_get_media_query('tablet');
                // Store tablet dimensions for CSS custom properties
                $bp_ratios['tablet'] = ['w' => $tablet_fields['width'], 'h' => $tablet_fields['height']];
                img_push_source($sources, $url, $tablet_fields['mime_type'], $media);
                continue;
            }

            // Select best image size for this breakpoint
            $candidate = po_select_candidate($bp, $available, $max_size, $min_size, $already_used);

            if ($candidate !== null) {
                // Track used size to avoid duplication
                $already_used[] = $candidate;
                $url   = $fields['urls'][$candidate] ?? $fields['url'];
                $media = po_get_media_query($bp);

                // Store actual dimensions of selected candidate for CSS custom properties
                $bp_ratios[$bp] = [
                    'w' => $fields['meta']['sizes'][$candidate]['width']  ?? $fields['width'],
                    'h' => $fields['meta']['sizes'][$candidate]['height'] ?? $fields['height'],
                ];

                // Add source element with media query
                img_push_source($sources, $url, $mime_type, $media);
            }
            // If no candidate found, CSS will cascade to lower breakpoint dimensions
        }

        $img_tag = img_create_img_tag(
            src: $mobile_url ?? $fields['url'],
            bp_ratios: $bp_ratios,
            orig_width: $fields['width'],
            orig_height: $fields['height'],
            alt: $alt_text,
            is_priority: $is_priority,
            extra: $extra
        );

        return img_wrap_picture($sources, $img_tag, $classes, $id, $is_priority);
    }
}

// ---------------------------------------------------------------------------
// img_generate_cover_picture()
//
// CHANGE: Like img_generate_standard_picture(), builds $bp_ratios with
// actual dimensions of each cover candidate selected per breakpoint,
// and passes it to img_create_img_tag() instead of individual width/height.
// ---------------------------------------------------------------------------

/**
 * Generate a responsive picture element optimized for cover/hero images
 * 
 * Creates a picture element with sources specifically for cover layouts.
 * Optimizes for full-width/height display with minimal file sizes per breakpoint.
 * Groups sources efficiently to reduce redundant elements.
 * 
 * @param array $img_fields Primary image fields data
 * @param array|string $tablet_img Optional tablet-specific image
 * @param array|string $mobile_img Optional mobile-specific image
 * @param string $classes CSS classes for picture element
 * @param string $id HTML id for picture element
 * @param string $alt Alt text for accessibility
 * @param bool $is_priority Whether this is a priority image (eager load)
 * @param string $extra Additional HTML attributes
 * @return string HTML picture element string
 */
if (!function_exists('img_generate_cover_picture')) {
    function img_generate_cover_picture(
        array        $img_fields,
        array|string $tablet_img  = [],
        array|string $mobile_img  = [],
        string       $classes     = '',
        string       $id          = '',
        string       $alt         = '',
        bool         $is_priority = false,
        string       $extra       = ''
    ): string {

        // Initialize
        $sources   = [];        // Collect source elements
        $bp_order  = po_get_breakpoint_order();
        $alt_text  = $alt ?: $img_fields['alt'];

        // Load optional dedicated images
        $has_tablet    = !empty($tablet_img);
        $has_mobile    = !empty($mobile_img);
        $tablet_fields = $has_tablet ? img_get_fields($tablet_img) : null;
        $mobile_fields = $has_mobile ? img_get_fields($mobile_img) : null;

        // Cover images use specific optimized sizes
        $cover_sizes = ['full', 'cover-desktop', 'cover-tablet', 'cover-mobile'];

        // Get available sizes, filtered to only cover sizes
        $img_available    = img_get_available_sizes($img_fields['meta'], $img_fields['id']);
        // Keep only cover-specific sizes
        $img_available    = array_diff_key(
            $img_available,
            array_flip(array_diff(array_keys($img_available), $cover_sizes))
        );
        // Get available sizes for optional tablet/mobile images
        $tablet_available = $tablet_fields ? img_get_available_sizes($tablet_fields['meta'], $tablet_fields['id']) : [];
        $mobile_available = $mobile_fields ? img_get_available_sizes($mobile_fields['meta'], $mobile_fields['id']) : [];

        // Helper function to select best cover size for a breakpoint
        $pick_cover = function (
            string $bp,
            array  $available,
            array  $fields,
            array  $already_used = []
        ) use ($cover_sizes): ?array {
            $bp_min     = po_get_breakpoint_ranges()[$bp] ?? 0;
            $ceiling    = po_get_next_breakpoint_min($bp);
            $prelast_bp = po_get_breakpoint_order()[array_key_last(po_get_breakpoint_order()) - 1] ?? null;

            $candidates = [];
            foreach ($cover_sizes as $size) {
                if (!isset($available[$size])) continue;
                if (in_array($size, $already_used, true)) continue;
                $real_w = $available[$size];
                if ($real_w <= $bp_min) continue;
                if ($ceiling !== null && $real_w > $ceiling) continue;
                $candidates[$size] = $real_w;
            }

            if ($bp === $prelast_bp && empty($candidates) && !empty($already_used)) {
                $best = array_last($already_used);
                return ['size' => $best, 'fields' => $fields];
            }

            if (empty($candidates)) return null;
            arsort($candidates);
            $best = array_key_first($candidates);
            return ['size' => $best, 'fields' => $fields];
        };

        $tablet_coverage_bp = null;
        if ($tablet_fields) {
            $tablet_full_w = $tablet_fields['width'];
            $ranges        = po_get_breakpoint_ranges();
            foreach (array_reverse(array_keys($ranges)) as $bp) {
                if ($bp === 'mobile') continue;
                if (isset($tablet_available['cover-tablet']) || $tablet_full_w >= $ranges[$bp]) {
                    $tablet_coverage_bp = $bp;
                    break;
                }
            }
        }

        $bp_resolution    = [];
        $img_already_used = [];

        foreach ($bp_order as $bp) {
            if ($bp === 'mobile') continue;
            $ranges = po_get_breakpoint_ranges();
            $bp_min = $ranges[$bp] ?? 0;

            if ($has_tablet && $tablet_coverage_bp !== null) {
                $tablet_bp_min = $ranges[$tablet_coverage_bp] ?? 0;
                if ($bp_min <= $tablet_bp_min) {
                    $result = $pick_cover($bp, $tablet_available, $tablet_fields);
                    if ($result) {
                        $bp_resolution[$bp] = $result + ['mime' => $tablet_fields['mime_type']];
                        continue;
                    }
                }
            }

            $result = $pick_cover($bp, $img_available, $img_fields, $img_already_used);
            if ($result) {
                $img_already_used[]  = $result['size'];
                $bp_resolution[$bp]  = $result + ['mime' => $img_fields['mime_type']];
            }
        }

        // Acumula dimensiones reales por breakpoint para las custom properties.
        $bp_ratios      = [];
        $non_mobile_bps = array_filter($bp_order, fn($bp) => $bp !== 'mobile');

        $pending_size   = null;
        $pending_fields = null;
        $pending_mime   = null;
        $pending_min_bp = null;

        foreach ($non_mobile_bps as $bp) {
            $res = $bp_resolution[$bp] ?? null;

            if ($res === null) {
                if ($pending_size !== null) {
                    $url   = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
                    $media = po_get_media_query($pending_min_bp);
                    img_push_source($sources, $url, $pending_mime, $media);
                    $pending_size = $pending_fields = $pending_mime = $pending_min_bp = null;
                }
                continue;
            }

            // Store actual dimensions of selected cover size for this breakpoint
            $res_size   = $res['size'];
            $res_fields = $res['fields'];
            $bp_ratios[$bp] = [
                'w' => $res_fields['meta']['sizes'][$res_size]['width']  ?? $res_fields['width'],
                'h' => $res_fields['meta']['sizes'][$res_size]['height'] ?? $res_fields['height'],
            ];

            if ($pending_size === null) {
                $pending_size   = $res['size'];
                $pending_fields = $res['fields'];
                $pending_mime   = $res['mime'];
                $pending_min_bp = $bp;
            } elseif ($res['size'] === $pending_size && $res['fields']['id'] === $pending_fields['id']) {
                $pending_min_bp = $bp;
            } else {
                $url   = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
                $media = po_get_media_query($pending_min_bp);
                img_push_source($sources, $url, $pending_mime, $media);
                $pending_size   = $res['size'];
                $pending_fields = $res['fields'];
                $pending_mime   = $res['mime'];
                $pending_min_bp = $bp;
            }
        }

        if ($pending_size !== null) {
            $url              = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
            $last_emitted_url = $url;

            if ($pending_min_bp === 'tablet') {
                $prev_emitted_url = $bp_resolution[$pending_min_bp]['fields']['urls'][array_last($img_already_used)];
                if ($pending_size === 'full' || $prev_emitted_url === $url) {
                    $media = po_get_media_query($pending_min_bp);
                    img_push_source($sources, $url, $pending_mime, $media);
                } else {
                    img_push_source($sources, $url, $pending_mime, null);
                }
            } else {
                $media = po_get_media_query($pending_min_bp);
                img_push_source($sources, $url, $pending_mime, $media);
                $last_emitted_url = null;
            }
        } else {
            $last_emitted_url = null;
        }

        $mobile_source_fields = $mobile_fields ?? $tablet_fields ?? $img_fields;
        $mobile_available_map = $mobile_fields ? $mobile_available
            : ($tablet_fields ? $tablet_available : $img_available);

        $mobile_size = null;
        foreach (['cover-mobile', 'cover-tablet', 'full'] as $s) {
            if (isset($mobile_available_map[$s])) {
                $mobile_size = $s;
                break;
            }
        }
        if (!$mobile_size) $mobile_size = 'full';

        $mobile_url  = $mobile_source_fields['urls'][$mobile_size] ?? $mobile_source_fields['url'];
        $mobile_mime = $mobile_source_fields['mime_type'];

        // Guardar dimensiones mobile.
        $bp_ratios['mobile'] = [
            'w' => $mobile_source_fields['meta']['sizes'][$mobile_size]['width']  ?? $mobile_source_fields['width'],
            'h' => $mobile_source_fields['meta']['sizes'][$mobile_size]['height'] ?? $mobile_source_fields['height'],
        ];

        if ($mobile_url !== $last_emitted_url) {
            img_push_source($sources, $mobile_url, $mobile_mime, null);
        }

        $img_tag = img_create_img_tag(
            src: $mobile_url,
            bp_ratios: $bp_ratios,
            orig_width: $img_fields['width'],
            orig_height: $img_fields['height'],
            alt: $alt_text,
            is_priority: $is_priority,
            extra: $extra
        );

        return img_wrap_picture($sources, $img_tag, $classes, $id, $is_priority);
    }
}

/**
 * Generate a responsive picture element (main entry point)
 * 
 * Routes to appropriate generator based on image type (standard vs cover).
 * Handles validation, SVG detection, and thumbnail special cases.
 * Main function for generating responsive picture tags.
 * 
 * @param array|string $img Primary image (array or URL string)
 * @param array|string $mobile_img Optional mobile-specific image
 * @param array|string $tablet_img Optional tablet-specific image
 * @param string $max_size Maximum allowed image size
 * @param string $min_size Minimum allowed image size
 * @param string $classes CSS classes for picture element
 * @param string $id HTML id for picture element
 * @param string $alt_text Alt text for accessibility
 * @param bool $is_cover Whether to generate cover-optimized picture
 * @param string $img_attr Additional HTML attributes
 * @param bool $is_priority Whether this is a priority image (eager load)
 * @return string HTML picture element string, or empty string if invalid
 */
if (!function_exists('img_generate_picture_tag')) {
    function img_generate_picture_tag(
        array|string $img,
        array|string $mobile_img  = [],
        array|string $tablet_img  = [],
        string       $max_size    = 'full',
        string       $min_size    = '',
        string       $classes     = '',
        string       $id          = '',
        string       $alt_text    = '',
        bool         $is_cover    = false,
        string       $img_attr    = '',
        bool         $is_priority = false
    ): string {
        // Validate input
        if (empty($img)) return '';
        // Initialize sizes if not already done
        if (empty($GLOBALS['sizes'])) po_init_sizes();

        // Validate and sanitize size parameters
        if ($max_size !== 'full' && !in_array($max_size, $GLOBALS['sizes'], true)) $max_size = 'full';
        if ($min_size !== '' && !in_array($min_size, $GLOBALS['sizes'], true)) $min_size = '';

        // Parse image data
        $fields = img_get_fields($img);
        if (empty($fields['url'])) return '';

        // Handle SVG images separately (delegate to SVG function if available)
        if (in_array($fields['mime_type'], ['image/svg+xml', 'image/svg'], true)) {
            if (function_exists('image_to_svg')) return image_to_svg($img, $classes);
            return '';
        }

        if ($is_cover) {
            return img_generate_cover_picture(
                img_fields: $fields,
                tablet_img: $tablet_img,
                mobile_img: $mobile_img,
                classes: $classes,
                id: $id,
                alt: $alt_text,
                is_priority: $is_priority,
                extra: $img_attr
            );
        }

        if ($max_size === 'thumbnail') {
            $sources   = [];
            $url       = $fields['urls']['thumbnail'] ?? $fields['url'];
            $mime      = $fields['mime_type'];
            $available = img_get_available_sizes($fields['meta'], $fields['id']);

            img_push_source($sources, $url, $mime, null);

            $thumb_w = $fields['meta']['sizes']['thumbnail']['width']  ?? 0;
            $thumb_h = $fields['meta']['sizes']['thumbnail']['height'] ?? 0;

            $img_tag = img_create_img_tag(
                src: $url,
                bp_ratios: ['mobile' => ['w' => $thumb_w, 'h' => $thumb_h]],
                orig_width: $fields['width'],
                orig_height: $fields['height'],
                alt: $alt_text ?: $fields['alt'],
                is_priority: $is_priority,
                extra: $img_attr
            );

            return img_wrap_picture($sources, $img_tag, $classes, $id, $is_priority);
        }

        return img_generate_standard_picture(
            fields: $fields,
            tablet_img: $tablet_img,
            mobile_img: $mobile_img,
            max_size: $max_size,
            min_size: $min_size,
            classes: $classes,
            id: $id,
            alt: $alt_text,
            is_priority: $is_priority,
            extra: $img_attr
        );
    }
}

/**
 * Echo a responsive picture element (wrapper for img_generate_picture_tag)
 * 
 * Convenient function to directly output a picture tag without storing to variable.
 * Passes all arguments directly to img_generate_picture_tag().
 * 
 * @param array|string $img Primary image (array or URL string)
 * @param array|string $mobile_img Optional mobile-specific image
 * @param array|string $tablet_img Optional tablet-specific image
 * @param string $max_size Maximum allowed image size
 * @param string $min_size Minimum allowed image size
 * @param string $classes CSS classes for picture element
 * @param string $id HTML id for picture element
 * @param string $alt_text Alt text for accessibility
 * @param bool $is_cover Whether to generate cover-optimized picture
 * @param string $img_attr Additional HTML attributes
 * @param bool $is_priority Whether this is a priority image (eager load)
 * @return void Outputs HTML directly
 */
if (!function_exists('img_print_picture_tag')) {
    function img_print_picture_tag(
        array|string $img,
        array|string $mobile_img  = [],
        array|string $tablet_img  = [],
        string       $max_size    = 'full',
        string       $min_size    = '',
        string       $classes     = '',
        string       $id          = '',
        string       $alt_text    = '',
        bool         $is_cover    = false,
        string       $img_attr    = '',
        bool         $is_priority = false
    ): void {
        echo img_generate_picture_tag(
            img: $img,
            mobile_img: $mobile_img,
            tablet_img: $tablet_img,
            max_size: $max_size,
            min_size: $min_size,
            classes: $classes,
            id: $id,
            alt_text: $alt_text,
            is_cover: $is_cover,
            img_attr: $img_attr,
            is_priority: $is_priority
        );
    }
}
