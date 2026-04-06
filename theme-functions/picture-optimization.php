<?php

/**
 * Responsive Image Helper Functions
 * Generates <picture> elements with WebP support and multiple breakpoints.
 */

// ---------------------------------------------------------------------------
// Breakpoint defaults (overridable from functions.php before this file loads)
// ---------------------------------------------------------------------------

if (!isset($GLOBALS['breakpoints'])) {
    $GLOBALS['breakpoints'] = [
        'mobile' => '0px',
        'tablet' => '600px',
        'ldpi'   => '1024px',
        'mdpi'   => '1200px',
        'hdpi'   => '1440px',
    ];
}

if (!isset($GLOBALS['preferred_size_map'])) {
    $GLOBALS['preferred_size_map'] = [];
}

if (!isset($GLOBALS['img_metadata_cache'])) {
    $GLOBALS['img_metadata_cache'] = [];
}

// ---------------------------------------------------------------------------
// 1. po_get_breakpoint_ranges()
//
// Reads $GLOBALS['breakpoints'] and returns each breakpoint's min value as int.
//
// Input:
//   $GLOBALS['breakpoints'] = ['mobile'=>'0px','tablet'=>'768px','ldpi'=>'1024px',...]
//
// Output:
//   ['mobile'=>0, 'tablet'=>768, 'ldpi'=>1024, 'mdpi'=>1280, 'hdpi'=>1920]
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// 2. po_get_media_query()
//
// Returns the min-width media query string for a breakpoint, or null for mobile.
//
// Examples:
//   po_get_media_query('mobile') → null
//   po_get_media_query('tablet') → "(min-width: 768px)"
//   po_get_media_query('hdpi')   → "(min-width: 1920px)"
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// 3. po_init_sizes()
//
// Reads all WP-registered image sizes (including custom ones from add_image_size)
// and stores them in $GLOBALS['sizes']. Hooked to after_setup_theme priority 999
// so custom sizes are already registered when this runs.
//
// Output in $GLOBALS['sizes']:
//   ['thumbnail','medium','large','cover-desktop','cover-tablet',
//    'cover-mobile','content','featured-small','full']
// ---------------------------------------------------------------------------

if (!function_exists('po_init_sizes')) {
    function po_init_sizes(): void
    {
        $sizes = [];

        if (function_exists('get_intermediate_image_sizes')) {
            $sizes = (array) get_intermediate_image_sizes();
        }

        global $_wp_additional_image_sizes;

        if (is_array($_wp_additional_image_sizes)) {
            foreach ($_wp_additional_image_sizes as $size_name => $size_data) {
                if (!in_array($size_name, $sizes, true)) {
                    $sizes[] = $size_name;
                }
            }
        }


        if (!in_array('full', $sizes, true)) {
            $sizes[] = 'full';
        }

        $GLOBALS['sizes'] = array_values($sizes);
    }
}

add_action('after_setup_theme', 'po_init_sizes', 999);

// ---------------------------------------------------------------------------
// 4. po_get_registered_width()
//
// Returns the registered width in px (int) for a WP size name.
// For custom sizes uses $_wp_additional_image_sizes.
// For WP built-ins reads database options.
// Returns 0 for 'full' or unknown sizes.
//
// Examples:
//   po_get_registered_width('cover-desktop') → 1920
//   po_get_registered_width('medium')        → 300
//   po_get_registered_width('full')          → 0
// ---------------------------------------------------------------------------

if (!function_exists('po_get_registered_width')) {
    function po_get_registered_width(string $size): int
    {
        global $_wp_additional_image_sizes;

        // Custom sizes registered via add_image_size().
        if (!empty($_wp_additional_image_sizes[$size]['width'])) {
            return (int) $_wp_additional_image_sizes[$size]['width'];
        }

        // WP built-in sizes.
        switch ($size) {
            case 'thumbnail':
                return (int) get_option('thumbnail_size_w');
            case 'medium':
                return (int) get_option('medium_size_w');
            case 'large':
                return (int) get_option('large_size_w');
        }

        // 'full' and unknown sizes have no fixed registered width.
        return 0;
    }
}

// ---------------------------------------------------------------------------
// 5. po_get_breakpoint_order()
//
// Returns breakpoint names sorted descending by min-width.
// Used to iterate when building <source> tags (largest first).
//
// Input $GLOBALS['breakpoints']:
//   ['mobile'=>'0px','tablet'=>'768px','ldpi'=>'1024px','mdpi'=>'1280px','hdpi'=>'1920px']
//
// Output:
//   ['hdpi','mdpi','ldpi','tablet','mobile']
// ---------------------------------------------------------------------------

if (!function_exists('po_get_breakpoint_order')) {
    function po_get_breakpoint_order(): array
    {
        $ranges = po_get_breakpoint_ranges();
        arsort($ranges);
        return array_keys($ranges);
    }
}

// ---------------------------------------------------------------------------
// 6. po_get_next_breakpoint_min()
//
// Returns the min-width (int) of the next breakpoint above the given one.
// Returns null if there is no higher breakpoint.
//
// Examples:
//   po_get_next_breakpoint_min('mobile') → 768
//   po_get_next_breakpoint_min('tablet') → 1024
//   po_get_next_breakpoint_min('hdpi')   → null
// ---------------------------------------------------------------------------

if (!function_exists('po_get_next_breakpoint_min')) {
    function po_get_next_breakpoint_min(string $breakpoint): ?int
    {
        $ranges = po_get_breakpoint_ranges();
        $keys   = array_keys($ranges);
        $index  = array_search($breakpoint, $keys, true);

        if ($index === false || $index >= count($keys) - 1) {
            return null;
        }

        return $ranges[$keys[$index + 1]];
    }
}

// ---------------------------------------------------------------------------
// 7. po_select_candidate()
//
// Selects the best WP size name for a given breakpoint from available sizes.
//
// Rules:
// - Without max_size: candidate real width must be >= breakpoint min AND
//   <= next breakpoint min (ceiling). Picks the closest to ceiling.
// - With max_size: forces max_size if it exists for this image; otherwise
//   picks the largest available whose registered width <= max_size registered width.
// - min_size: discards candidates whose registered width < min_size registered width.
// - Returns null if no valid candidate exists.
//
// @param string   $breakpoint      Breakpoint name (e.g. 'tablet')
// @param array    $available        Map of size_name => real_width for this image
//                                   e.g. ['medium'=>238,'cover-mobile'=>423,'full'=>660]
// @param string   $max_size         WP size name used as ceiling ('full' = no ceiling)
// @param string   $min_size         WP size name used as floor ('' = no floor)
// @param array    $already_used     Size names already assigned to higher breakpoints
//
// @return string|null
// ---------------------------------------------------------------------------

if (!function_exists('po_select_candidate')) {
    function po_select_candidate(
        string $breakpoint,
        array  $available,
        string $max_size,
        string $min_size,
        array  $already_used = []
    ): ?string {

        // If max_size and min_size are the same, no candidate can satisfy both constraints.
        if ($max_size === $min_size) return null;

        $bp_min      = po_get_breakpoint_ranges()[$breakpoint] ?? 0;
        $ceiling     = po_get_next_breakpoint_min($breakpoint); // null = no ceiling
        $max_reg_w   = ($max_size !== 'full') ? po_get_registered_width($max_size) : 0;
        $min_reg_w   = ($min_size !== '')  ? po_get_registered_width($min_size) : 0;
        $prelast_bp = po_get_breakpoint_order()[array_key_last(po_get_breakpoint_order()) - 1] ?? null;

        $available[$min_size] = isset($available[$min_size]) ?: null;

        $reference = $min_reg_w !== 0 ? $available[$min_size] : null;

        if ($reference !== null) {
            $available = array_filter($available, fn($v) => $v >= $reference);
        }

        asort($available);

        $available_keys = array_keys($available);

        // ── With max_size defined ─────────────────────────────────────────
        if ($max_size !== 'full' && $max_reg_w > 0) {
            // Force max_size if it was generated for this image.
            if (isset($available[$max_size]) && !in_array($max_size, $already_used, true)) {

                $index = array_search($max_size, $available_keys, true);
                $prevKey = $available_keys[$index - 1] ?? null;

                if ($breakpoint !== $prelast_bp && $available[$max_size] < $bp_min) return null;

                // If the previous size is the same real width as max_size, it means max_size is not actually larger than the min_size floor (or there is no floor). 
                //In this case, we skip emitting a source for this breakpoint to avoid redundancy, since the same image will be emitted for the next breakpoint anyway.
                if ($min_reg_w > 0 && $available[$prevKey] === $available[$min_size] && $breakpoint !== $prelast_bp) return null;

                return $max_size;
            }

            // Otherwise pick the largest available whose registered width <= max_reg_w.
            $candidates = [];
            foreach ($available as $size => $real_w) {
                if ($min_reg_w > 0 && $available[$size] <= $available[$min_size]) continue;
                if (in_array($size, $already_used, true)) continue;
                if ($min_reg_w > 0 && po_get_registered_width($size) < $min_reg_w && $size !== "full") continue;
                if ($size === "full" && !empty($already_used)) continue;

                $reg_w = po_get_registered_width($size);

                // For 'full', registered width is 0 — use real width as reference.
                $compare_w = ($reg_w > 0) ? $reg_w : $real_w;

                if ($size === "full") {
                    foreach ($available as $key => $value) {
                        if ($key !== "full" && po_get_registered_width($key) >= $real_w && $real_w >= $value) {
                            $compare_w = po_get_registered_width($key);
                        }
                    }
                }

                // Candidate must be >= bp min (always).
                if ($breakpoint !== $prelast_bp && $compare_w < $bp_min) continue;
                // Candidate must be <= ceiling (next bp min) when ceiling exists.
                if ($ceiling !== null && $compare_w > $ceiling) continue;

                if ($compare_w <= $max_reg_w) {
                    $candidates[$size] = $real_w;
                }
            }

            if (empty($candidates)) return null;

            arsort($candidates);

            return array_key_first($candidates);
        }

        // ── Without max_size ──────────────────────────────────────────────
        // Use registered width for comparisons; fall back to real width when
        // registered width is 0 (e.g. 'full' or unknown sizes).
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
                    if (
                        $key !== "full" &&
                        po_get_registered_width($key) >= $real_w &&
                        $real_w >= $value &&
                        po_get_registered_width($key) < $ceiling
                    ) {
                        $s[$key] = po_get_registered_width($key);
                    }
                }
                asort($s);
                $compare = array_last($s) + 1;
            }

            // Candidate must be >= bp min (always).
            if ($breakpoint !== $prelast_bp && ($compare < $bp_min || $real_w < $bp_min)) continue;

            if ($breakpoint === $prelast_bp && $compare < $bp_min) continue;

            // Candidate must be <= ceiling (next bp min) when ceiling exists.
            if ($ceiling !== null && $compare > $ceiling) continue;

            $candidates[$size] = $compare;
        }

        if (empty($candidates)) return null;

        // Pick the one closest to the ceiling (largest compare_w among candidates).
        arsort($candidates);
        return array_key_first($candidates);
    }
}

// ---------------------------------------------------------------------------
// 8. img_get_available_sizes()
//
// Returns a map of size_name => real_width (int) for all sizes WP actually
// generated for this image. Sizes not generated are excluded.
//
// @param array $img_meta   WP attachment metadata (from wp_get_attachment_metadata())
// @param int   $img_id     Attachment ID
//
// @return array  e.g. ['thumbnail'=>119,'medium'=>238,'cover-mobile'=>423,'full'=>660]
// ---------------------------------------------------------------------------

if (!function_exists('img_get_available_sizes')) {
    function img_get_available_sizes(array $img_meta, int $img_id): array
    {
        $available = [];

        foreach ($GLOBALS['sizes'] as $size) {
            if ($size === 'full') {
                // full is always available — use original dimensions.
                $available['full'] = (int) ($img_meta['width'] ?? 0);
                continue;
            }

            // Check if WP generated this size for this image.
            $url = wp_get_attachment_image_url($img_id, $size);

            // If WP returns the full URL for a size that wasn't generated,
            // we verify by checking if dimensions differ from full.
            if (!$url) continue;

            $w = (int) ($img_meta['sizes'][$size]['width'] ?? 0);

            // If width is 0, WP didn't generate this size — skip it.
            if ($w === 0) continue;

            $available[$size] = $w;
        }

        return $available;
    }
}

// ---------------------------------------------------------------------------
// 9. img_url_to_path()
//
// Converts an attachment URL to its absolute filesystem path.
// Uses wp_get_upload_dir() baseurl for CDN compatibility.
//
// Examples:
//   img_url_to_path('https://site.com/wp-content/uploads/2025/11/image.jpg')
//   → '/var/www/html/wp-content/uploads/2025/11/image.jpg'
// ---------------------------------------------------------------------------

if (!function_exists('img_url_to_path')) {
    function img_url_to_path(string $url): string
    {
        $upload   = wp_get_upload_dir();
        $baseurl  = untrailingslashit($upload['baseurl']);
        $basepath = untrailingslashit($upload['basedir']);

        if (strpos($url, $baseurl) === 0) {
            return $basepath . substr($url, strlen($baseurl));
        }

        // Fallback for non-upload URLs.
        return str_replace(home_url('/'), ABSPATH, $url);
    }
}

// ---------------------------------------------------------------------------
// 10. img_has_webp()
//
// Returns true if a WebP version is available for the given URL.
// Case 1: original is already WebP → true immediately.
// Case 2: a .webp sidecar exists on disk → true.
// Case 3: neither → false.
// Uses static cache to avoid repeated file_exists() calls.
//
// @param string $url        Attachment URL
// @param string $mime_type  MIME type of the original (e.g. 'image/jpeg')
//
// @return bool
// ---------------------------------------------------------------------------

if (!function_exists('img_has_webp')) {
    function img_has_webp(string $url, string $mime_type = ''): bool
    {
        static $cache = [];

        $key = $url . '|' . $mime_type;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        // Case 1: original is already WebP.
        if ($mime_type === 'image/webp' || str_ends_with(strtolower($url), '.webp')) {
            return $cache[$key] = true;
        }

        // Case 2: check for .webp sidecar on disk.
        $path  = img_url_to_path($url) . '.webp';
        $cache[$key] = file_exists($path);

        return $cache[$key];
    }
}

// ---------------------------------------------------------------------------
// 11. img_get_webp_url()
//
// Returns the WebP URL for a given attachment URL.
// If original is already WebP, returns the URL as-is.
// Otherwise appends .webp suffix.
//
// @param string $url        Attachment URL
// @param string $mime_type  MIME type of the original
//
// @return string
// ---------------------------------------------------------------------------

if (!function_exists('img_get_webp_url')) {
    function img_get_webp_url(string $url, string $mime_type = ''): string
    {
        if ($mime_type === 'image/webp' || str_ends_with(strtolower($url), '.webp')) {
            return $url;
        }

        return $url . '.webp';
    }
}

// ---------------------------------------------------------------------------
// 12. img_create_source()
//
// Creates a <source> HTML tag string.
//
// @param string      $url        Image URL
// @param string      $mime_type  MIME type (e.g. 'image/webp', 'image/jpeg')
// @param string|null $media      Media query string or null for no media attribute
//
// @return string  e.g. "<source srcset='...' type='image/webp' media='(min-width: 768px)'>"
// ---------------------------------------------------------------------------

if (!function_exists('img_create_source')) {
    function img_create_source(string $url, string $mime_type, ?string $media = null): string
    {
        $srcset = "data-srcset='" . esc_url($url) . "'";
        $type   = "type='"   . esc_attr($mime_type) . "'";
        $media  = $media ? " media='" . esc_attr($media) . "'" : '';

        return "<source {$srcset} {$type}{$media}>";
    }
}

// ---------------------------------------------------------------------------
// 13. img_push_source()
//
// Appends <source> tag(s) for a URL to the $sources array.
// Emits WebP source first if available, then original (unless original is WebP).
//
// @param array       &$sources    Array to append source tags to
// @param string       $url        Image URL
// @param string       $mime_type  MIME type of the original
// @param string|null  $media      Media query or null
// ---------------------------------------------------------------------------

if (!function_exists('img_push_source')) {
    function img_push_source(
        array   &$sources,
        string  $url,
        string  $mime_type,
        ?string $media
    ): void {
        $is_native_webp = (
            $mime_type === 'image/webp' ||
            str_ends_with(strtolower($url), '.webp')
        );

        if ($is_native_webp) {
            // Original is already WebP — emit once.
            $sources[] = img_create_source($url, 'image/webp', $media);
            return;
        }

        // Emit WebP sidecar first if available.
        if (img_has_webp($url, $mime_type)) {
            $sources[] = img_create_source(img_get_webp_url($url, $mime_type), 'image/webp', $media);
        }

        // Emit original.
        $sources[] = img_create_source($url, $mime_type, $media);
    }
}

// ---------------------------------------------------------------------------
// 14. img_create_img_tag()
//
// Creates the <img> fallback HTML tag.
// Includes data-aspect-ratio attribute as reduced fraction (e.g. '16:9', '165:208').
//
// @param string $src           Image URL
// @param int    $width         Display width
// @param int    $height        Display height
// @param int    $orig_width    Original image width (for aspect ratio calculation)
// @param int    $orig_height   Original image height
// @param string $alt           Alt text
// @param bool   $is_priority   When true: loading="eager" fetchpriority="high"
// @param string $extra         Extra raw HTML attributes string
//
// @return string
// ---------------------------------------------------------------------------

if (!function_exists('img_create_img_tag')) {
    function img_create_img_tag(
        string $src,
        int    $width       = 0,
        int    $height      = 0,
        int    $orig_width  = 0,
        int    $orig_height = 0,
        string $alt         = '',
        bool   $is_priority = false,
        string $extra       = ''
    ): string {
        $loading       = $is_priority ? 'eager' : 'lazy';
        $fetchpriority = $is_priority ? " fetchpriority='high'" : '';

        // For priority images, use src directly; for lazy, use data-src
        if ($is_priority) {
            $src_attr = "src='" . esc_url($src) . "'";
            $class_attr = '';
        } else {
            $src_attr = "data-src='" . esc_url($src) . "'";
            $class_attr = "class='lazy-image'";
        }

        $width_attr  = $width  ? "width='"  . (int) $width  . "'" : '';
        $height_attr = $height ? "height='" . (int) $height . "'" : '';
        $alt_attr    = "alt='"     . esc_attr($alt)      . "'";
        $loading_attr = "loading='" . $loading . "'";
        $decoding_attr = "decoding='async'";

        // Calculate reduced aspect ratio from original dimensions.
        $aspect_ratio = '';
        if ($orig_width > 0 && $orig_height > 0) {
            $gcd_fn = null;
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
            $alt_attr,
            $loading_attr . $fetchpriority,
            $decoding_attr,
            $class_attr,
        ]);

        return '<img ' . implode(' ', $parts) . $aspect_ratio . $extra_attr . '>';
    }
}

// ---------------------------------------------------------------------------
// 15. img_wrap_picture()
//
// Wraps <source> tags and <img> in a <picture> element.
//
// @param array  $sources  Array of <source> HTML strings
// @param string $img_tag  The <img> HTML string
// @param string $classes  CSS classes for the <picture> element
// @param string $id       HTML id for the <picture> element
//
// @return string
// ---------------------------------------------------------------------------

if (!function_exists('img_wrap_picture')) {
    function img_wrap_picture(
        array  $sources,
        string $img_tag,
        string $classes = '',
        string $id      = '',
        bool   $is_priority = false,
    ): string {
        $id_attr    = $id      ? " id='"    . esc_attr($id)      . "'" : '';
        $class_attr = $classes ? " class='" . esc_attr($classes) . "'" : '';

        // For priority images, use srcset directly instead of data-srcset, and skip noscript
        if ($is_priority) {
            $sources = array_map(function ($source) {
                return str_replace('data-srcset=', 'srcset=', $source);
            }, $sources);
            $noscript = '';
        } else {
            // Generate fallback sources and img for noscript (without data-)
            $fallback_sources = [];
            foreach ($sources as $source) {
                $fallback_sources[] = str_replace('data-srcset=', 'srcset=', $source);
            }
            $fallback_img = str_replace(['data-src=', "class='lazy-image'"], ['src=', ''], $img_tag);

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

// ---------------------------------------------------------------------------
// 16. img_parse_fields()
//
// Normalizes image data from either an ACF image array or a plain URL string
// into a consistent structure used by all generator functions.
//
// Accepts:
//   - ACF image array (with 'url', 'width', 'height', 'mime_type', 'sizes', 'alt')
//   - Plain attachment URL string
//
// Returns:
//   [
//     'id'        => int,             // attachment ID (0 if unknown)
//     'url'       => string,          // full-size URL
//     'width'     => int,             // original width
//     'height'    => int,             // original height
//     'alt'       => string,
//     'mime_type' => string,          // e.g. 'image/jpeg'
//     'urls'      => [                // URL per registered size
//                      'full'         => '...',
//                      'thumbnail'    => '...',
//                      'cover-mobile' => '...',
//                      ...
//                    ],
//     'meta'      => array,           // raw WP attachment metadata
//   ]
//
// Returns empty fields array on failure.
// ---------------------------------------------------------------------------

if (!function_exists('img_get_empty_fields')) {
    function img_get_empty_fields(): array
    {
        return [
            'id'        => 0,
            'url'       => '',
            'width'     => 0,
            'height'    => 0,
            'alt'       => '',
            'mime_type' => 'image/jpeg',
            'urls'      => [],
            'meta'      => [],
        ];
    }
}

if (!function_exists('img_parse_fields')) {
    function img_parse_fields(array|string $img): array
    {
        // ── ACF image array ───────────────────────────────────────────────
        if (is_array($img)) {
            $acf_sizes = isset($img['sizes']) && is_array($img['sizes']) ? $img['sizes'] : [];
            $id        = (int) ($img['ID'] ?? $img['id'] ?? 0);
            $meta      = $id ? wp_get_attachment_metadata($id) : [];

            $urls = ['full' => $img['url'] ?? ''];

            foreach ($GLOBALS['sizes'] as $size) {
                if ($size === 'full') continue;
                $urls[$size] = $acf_sizes[$size] ?? ($img['url'] ?? '');
            }

            return [
                'id'        => $id,
                'url'       => $img['url']       ?? '',
                'width'     => (int) ($img['width']  ?? 0),
                'height'    => (int) ($img['height'] ?? 0),
                'alt'       => $img['alt']        ?? '',
                'mime_type' => $img['mime_type']  ?? 'image/jpeg',
                'urls'      => $urls,
                'meta'      => $meta ?: [],
            ];
        }

        // ── Plain URL string ──────────────────────────────────────────────
        $url = (string) $img;
        $id  = attachment_url_to_postid($url);

        if (!$id) return img_get_empty_fields();

        $meta = wp_get_attachment_metadata($id);
        if (!$meta) return img_get_empty_fields();

        $mime_type = get_post_mime_type($id) ?: 'image/jpeg';

        $urls = ['full' => wp_get_attachment_url($id)];

        foreach ($GLOBALS['sizes'] as $size) {
            if ($size === 'full') continue;
            $size_url = wp_get_attachment_image_url($id, $size);
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

// ---------------------------------------------------------------------------
// 17. img_get_fields()
//
// Cached wrapper around img_parse_fields().
// Avoids parsing the same image multiple times per request.
//
// @param array|string $img  ACF image array or plain URL string
//
// @return array  Normalized image fields (see img_parse_fields)
// ---------------------------------------------------------------------------

if (!function_exists('img_get_fields')) {
    function img_get_fields(array|string $img): array
    {
        $cache_key = is_array($img)
            ? md5(serialize($img))
            : md5((string) $img);

        if (isset($GLOBALS['img_metadata_cache'][$cache_key])) {
            return $GLOBALS['img_metadata_cache'][$cache_key];
        }

        $result = img_parse_fields($img);

        $GLOBALS['img_metadata_cache'][$cache_key] = $result;

        return $result;
    }
}

// ---------------------------------------------------------------------------
// 18. img_generate_standard_picture()
//
// Generates a responsive <picture> element for standard (non-cover) images.
//
// Logic per breakpoint (hdpi → mobile):
// - Without max_size: candidate registered width must be >= bp min AND
//   <= next bp min. Picks closest to ceiling. Skips bp if no candidate qualifies.
// - With max_size: forces max_size if generated for this image, else largest
//   available whose registered width <= max_size registered width.
// - min_size: discards candidates with registered width < min_size registered width.
//   Also forces min_size as the mobile source and <img> fallback.
// - already_used: tracks sizes assigned to higher breakpoints to avoid reuse.
//
// @param array        $fields      Normalized image fields from img_get_fields()
// @param string       $max_size    WP size name ceiling ('full' = no ceiling)
// @param string       $min_size    WP size name floor ('' = no floor)
// @param string       $classes     CSS classes for <picture>
// @param string       $id          HTML id for <picture>
// @param string       $alt         Alt text override
// @param bool         $is_priority loading="eager" fetchpriority="high"
// @param string       $extra       Extra attributes for <img>
//
// @return string  HTML string
// ---------------------------------------------------------------------------

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

        $sources      = [];
        $already_used = [];
        $bp_order     = po_get_breakpoint_order(); // hdpi → mobile
        $available    = img_get_available_sizes($fields['meta'], $fields['id']);
        $alt_text     = $alt ?: $fields['alt'];
        $mime_type    = $fields['mime_type'];

        $has_tablet    = !empty($tablet_img);
        $has_mobile    = !empty($mobile_img);
        $tablet_fields = $has_tablet ? img_get_fields($tablet_img) : null;
        $mobile_fields = $has_mobile ? img_get_fields($mobile_img) : null;

        foreach ($bp_order as $bp) {

            if ($bp === 'mobile') {
                // Mobile: use mobile_img if provided, else min_size or hierarchy.
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

                img_push_source($sources, $mobile_url, $mobile_mime, null);

                $img_tag = img_create_img_tag(
                    src: $mobile_url,
                    width: $mob_w,
                    height: $mob_h,
                    orig_width: $fields['width'],
                    orig_height: $fields['height'],
                    alt: $alt_text,
                    is_priority: $is_priority,
                    extra: $extra
                );

                continue;
            }

            if ($bp === 'tablet' && $has_tablet) {
                // Use tablet_img full size for tablet breakpoint.
                $url   = $tablet_fields['url'];
                $media = po_get_media_query('tablet');
                img_push_source($sources, $url, $tablet_fields['mime_type'], $media);
                continue;
            }

            // Select candidate for this breakpoint from main image.
            $candidate = po_select_candidate($bp, $available, $max_size, $min_size, $already_used);

            if ($candidate === null) continue;

            $already_used[] = $candidate;

            $url   = $fields['urls'][$candidate] ?? $fields['url'];
            $media = po_get_media_query($bp);

            img_push_source($sources, $url, $mime_type, $media);
        }

        return img_wrap_picture($sources, $img_tag ?? '', $classes, $id, $is_priority);
    }
}

// ---------------------------------------------------------------------------
// 19. img_generate_cover_picture()
//
// Generates a responsive <picture> for cover/background images.
// Only uses cover-* and full sizes.
//
// Rules:
// - Iterates hdpi → mobile selecting best cover candidate per breakpoint.
// - When consecutive breakpoints resolve to the same candidate, emits ONE
//   <source> using the min-width of the LOWEST breakpoint that uses it.
// - mobile always emits its own <source> without media query.
// - Supports 4 input cases:
//   Case 1: $img only
//   Case 2: $img + $tablet_img
//   Case 3: $img + $mobile_img
//   Case 4: $img + $tablet_img + $mobile_img
//
// @param array        $img_fields     Normalized fields for main (desktop) image
// @param array|string $tablet_img     Optional tablet image (ACF array or URL)
// @param array|string $mobile_img     Optional mobile image (ACF array or URL)
// @param string       $classes        CSS classes for <picture>
// @param string       $id             HTML id for <picture>
// @param string       $alt            Alt text override
// @param bool         $is_priority    loading="eager" fetchpriority="high"
// @param string       $extra          Extra attributes for <img>
//
// @return string  HTML string
// ---------------------------------------------------------------------------

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

        $sources   = [];
        $bp_order  = po_get_breakpoint_order(); // hdpi → mobile
        $alt_text  = $alt ?: $img_fields['alt'];

        // ── Resolve image fields for each input ───────────────────────────
        $has_tablet    = !empty($tablet_img);
        $has_mobile    = !empty($mobile_img);
        $tablet_fields = $has_tablet ? img_get_fields($tablet_img) : null;
        $mobile_fields = $has_mobile ? img_get_fields($mobile_img) : null;

        // ── Available cover sizes per image ───────────────────────────────
        $cover_sizes = ['full', 'cover-desktop', 'cover-tablet', 'cover-mobile'];

        $img_available    = img_get_available_sizes($img_fields['meta'],    $img_fields['id']);
        $img_available = array_diff_key(
            $img_available,
            array_flip(array_diff(array_keys($img_available), $cover_sizes))
        );
        $tablet_available = $tablet_fields ? img_get_available_sizes($tablet_fields['meta'], $tablet_fields['id']) : [];
        $mobile_available = $mobile_fields ? img_get_available_sizes($mobile_fields['meta'], $mobile_fields['id']) : [];

        // Helper: pick best cover candidate for a breakpoint from available sizes.
        // Uses registered widths. Returns ['size'=>string, 'fields'=>array] or null.
        $pick_cover = function (
            string $bp,
            array  $available,
            array  $fields,
            array  $already_used = []
        ) use ($cover_sizes): ?array {
            $bp_min  = po_get_breakpoint_ranges()[$bp] ?? 0;
            $ceiling = po_get_next_breakpoint_min($bp);
            $prelast_bp = po_get_breakpoint_order()[array_key_last(po_get_breakpoint_order()) - 1] ?? null;

            $candidates = [];
            foreach ($cover_sizes as $size) {
                if (!isset($available[$size])) continue;
                if (in_array($size, $already_used, true)) continue;

                //$reg_w   = po_get_registered_width($size);
                $real_w  = $available[$size];
                //$compare = ($reg_w > 0) ? $reg_w : $real_w;

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

        // ── Determine tablet image coverage ceiling ───────────────────────
        // tablet_img covers from mobile up to the highest bp its full width supports.
        $tablet_coverage_bp = null;
        if ($tablet_fields) {
            $tablet_full_w = $tablet_fields['width'];
            $ranges        = po_get_breakpoint_ranges();
            foreach (array_reverse(array_keys($ranges)) as $bp) {
                if ($bp === 'mobile') continue;
                $reg_w = po_get_registered_width('cover-tablet');
                // tablet_img covers this bp if it has cover-tablet or its full >= bp min.
                if (isset($tablet_available['cover-tablet']) || $tablet_full_w >= $ranges[$bp]) {
                    $tablet_coverage_bp = $bp;
                    break;
                }
            }
        }

        // ── Build per-breakpoint resolution map ───────────────────────────
        // For each non-mobile bp, determine which image and size to use.
        $bp_resolution = []; // bp => ['size'=>, 'fields'=>, 'mime'=>]
        $img_already_used = [];

        foreach ($bp_order as $bp) {
            if ($bp === 'mobile') continue;

            $ranges  = po_get_breakpoint_ranges();
            $bp_min  = $ranges[$bp] ?? 0;

            // Determine source image for this bp.
            if ($has_tablet && $tablet_coverage_bp !== null) {
                $tablet_bp_min  = $ranges[$tablet_coverage_bp] ?? 0;
                if ($bp_min <= $tablet_bp_min) {
                    // This bp is within tablet coverage.
                    $result = $pick_cover($bp, $tablet_available, $tablet_fields);
                    if ($result) {
                        $bp_resolution[$bp] = $result + ['mime' => $tablet_fields['mime_type']];
                        continue;
                    }
                    // tablet_img couldn't cover this bp — fall through to img.
                }
            }

            // Use main img for this bp.
            $result = $pick_cover($bp, $img_available, $img_fields, $img_already_used);
            if ($result) {
                $img_already_used[] = $result['size'];
                $bp_resolution[$bp] = $result + ['mime' => $img_fields['mime_type']];
            }
        }

        // ── Emit <source> tags: merge consecutive same-candidate bps ─────
        // Iterate hdpi → tablet. When the candidate changes, emit the previous
        // group using the min-width of its lowest breakpoint.
        $non_mobile_bps = array_filter($bp_order, fn($bp) => $bp !== 'mobile');

        $pending_size   = null;
        $pending_fields = null;
        $pending_mime   = null;
        $pending_min_bp = null; // lowest bp in current group

        foreach ($non_mobile_bps as $bp) {
            $res = $bp_resolution[$bp] ?? null;

            if ($res === null) {
                // No candidate for this bp — flush pending if any.
                if ($pending_size !== null) {
                    $url   = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
                    $media = po_get_media_query($pending_min_bp);
                    img_push_source($sources, $url, $pending_mime, $media);
                    $pending_size = $pending_fields = $pending_mime = $pending_min_bp = null;
                }
                continue;
            }

            if ($pending_size === null) {
                // Start new group.
                $pending_size   = $res['size'];
                $pending_fields = $res['fields'];
                $pending_mime   = $res['mime'];
                $pending_min_bp = $bp;
            } elseif ($res['size'] === $pending_size && $res['fields']['id'] === $pending_fields['id']) {
                // Same candidate — extend group to this lower bp.
                $pending_min_bp = $bp;
            } else {
                // Different candidate — flush previous group.
                $url   = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
                $media = po_get_media_query($pending_min_bp);
                img_push_source($sources, $url, $pending_mime, $media);

                // Start new group.
                $pending_size   = $res['size'];
                $pending_fields = $res['fields'];
                $pending_mime   = $res['mime'];
                $pending_min_bp = $bp;
            }
        }

        // Flush last pending group.
        // If this last group's min_bp is 'tablet', it means mobile and tablet
        // would share the same URL — emit it without media query instead (covers both).
        if ($pending_size !== null) {
            $url              = $pending_fields['urls'][$pending_size] ?? $pending_fields['url'];
            $last_emitted_url = $url;

            if ($pending_min_bp === 'tablet') {
                $prev_emitted_url =  $bp_resolution[$pending_min_bp]['fields']['urls'][array_last($img_already_used)];
                if ($pending_size === 'full' || $prev_emitted_url === $url) {
                    // Same URL as previous (desktop) source — emit again with tablet media query to avoid mobile sharing it.
                    $media = po_get_media_query($pending_min_bp);
                    img_push_source($sources, $url, $pending_mime, $media);
                } else {
                    // Emit without media query — covers tablet and mobile together.
                    img_push_source($sources, $url, $pending_mime, null);
                }
            } else {
                $media = po_get_media_query($pending_min_bp);
                img_push_source($sources, $url, $pending_mime, $media);
                $last_emitted_url = null; // different from mobile, so mobile needs its own source
            }
        } else {
            $last_emitted_url  = null;
        }

        // ── Mobile <source> (no media query) ─────────────────────────────
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

        // Only emit mobile source if URL differs from last emitted source.
        if ($mobile_url !== $last_emitted_url) {
            img_push_source($sources, $mobile_url, $mobile_mime, null);
        }

        // ── Fallback <img> ────────────────────────────────────────────────
        $img_tag = img_create_img_tag(
            src: $mobile_url,
            width: $mobile_source_fields['meta']['sizes'][$mobile_size]['width']  ?? $mobile_source_fields['width'],
            height: $mobile_source_fields['meta']['sizes'][$mobile_size]['height'] ?? $mobile_source_fields['height'],
            orig_width: $img_fields['width'],
            orig_height: $img_fields['height'],
            alt: $alt_text,
            is_priority: $is_priority,
            extra: $extra
        );

        return img_wrap_picture($sources, $img_tag, $classes, $id, $is_priority);
    }
}

// ---------------------------------------------------------------------------
// 20. img_generate_picture_tag()
//
// Main orchestrator. Generates a responsive <picture> element.
//
// @param array|string $img         Main image (ACF array or URL string)
// @param array|string $mobile_img  Optional mobile image
// @param array|string $tablet_img  Optional tablet image
// @param string       $max_size    WP size name ceiling ('full' = no ceiling)
// @param string       $min_size    WP size name floor ('' = no floor)
// @param string       $classes     CSS classes for <picture>
// @param string       $id          HTML id for <picture>
// @param string       $alt_text    Alt text override
// @param bool         $is_cover    Use cover mode (only cover-* and full sizes)
// @param string       $img_attr    Extra raw HTML attributes for <img>
// @param bool         $is_priority loading="eager" fetchpriority="high"
//
// @return string  HTML string (does not echo)
// ---------------------------------------------------------------------------

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

        // Guard: empty input.
        if (empty($img)) return '';

        // Guard: sizes must be initialized.
        if (empty($GLOBALS['sizes'])) {
            po_init_sizes();
        }

        // Guard: validate max_size and min_size against registered sizes.
        if ($max_size !== 'full' && !in_array($max_size, $GLOBALS['sizes'], true)) {
            $max_size = 'full';
        }
        if ($min_size !== '' && !in_array($min_size, $GLOBALS['sizes'], true)) {
            $min_size = '';
        }

        // Parse main image fields.
        $fields = img_get_fields($img);

        if (empty($fields['url'])) return '';

        // SVG: delegate to theme helper if available.
        if (in_array($fields['mime_type'], ['image/svg+xml', 'image/svg'], true)) {
            if (function_exists('image_to_svg')) {
                return image_to_svg($img);
            }
            return '';
        }

        // ── Cover mode ────────────────────────────────────────────────────
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

        // ── Thumbnail shortcut ────────────────────────────────────────────
        // When max_size is 'thumbnail', emit a simple <picture> with no breakpoints.
        if ($max_size === 'thumbnail') {
            $sources  = [];
            $url      = $fields['urls']['thumbnail'] ?? $fields['url'];
            $mime     = $fields['mime_type'];
            $available = img_get_available_sizes($fields['meta'], $fields['id']);

            img_push_source($sources, $url, $mime, null);

            $img_tag = img_create_img_tag(
                src: $url,
                width: $fields['meta']['sizes']['thumbnail']['width']  ?? 0,
                height: $fields['meta']['sizes']['thumbnail']['height'] ?? 0,
                orig_width: $fields['width'],
                orig_height: $fields['height'],
                alt: $alt_text ?: $fields['alt'],
                is_priority: $is_priority,
                extra: $img_attr
            );

            return img_wrap_picture($sources, $img_tag, $classes, $id, $is_priority);
        }

        // ── Standard mode ─────────────────────────────────────────────────
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

// ---------------------------------------------------------------------------
// 21. img_print_picture_tag()
//
// Echo wrapper around img_generate_picture_tag(). Same parameters.
// ---------------------------------------------------------------------------

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
