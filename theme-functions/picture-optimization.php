<?php

/**
 * Responsive Image Helper Functions
 * Generates <picture> elements with WebP support and multiple breakpoints.
 *
 * This version:
 * - Detects image sizes registered by WP (including custom add_image_size in functions.php)
 * - Maps sizes to breakpoints using heuristics
 * - Generates sources (including WebP if available) for each breakpoint
 * - Auto-detects cover sizes when is_cover=true
 * - Skips duplicate <source> entries when multiple breakpoints resolve to the same WP size
 * - Supports min_size to prevent serving images smaller than a given threshold (standard mode only)
 *
 * CUSTOMIZATION:
 * Define any of the following globals in functions.php BEFORE this file loads
 * to override the defaults:
 *
 *   $GLOBALS['breakpoints']       — custom breakpoint map
 *   $GLOBALS['preferred_size_map'] — explicit size-to-breakpoint overrides
 *   $GLOBALS['img_metadata_cache'] — pre-seeded cache (rarely needed)
 *
 * Example:
 *   $GLOBALS['breakpoints'] = [
 *       'mobile' => '0px',
 *       'tablet' => '768px',
 *       'ldpi'   => '1024px',
 *       'mdpi'   => '1280px',
 *       'hdpi'   => '1600px',
 *   ];
 */

/**
 * SUGGESTED BREAKPOINTS (adjustable per theme)
 * - mobile : 0    – 599px
 * - tablet : 600  – 1023px
 * - ldpi   : 1024 – 1199px
 * - mdpi   : 1200 – 1439px
 * - hdpi   : 1440px+
 */
if (!isset($GLOBALS['breakpoints'])) {
    $GLOBALS['breakpoints'] = [
        'mobile' => '0px',
        'tablet' => '600px',
        'ldpi'   => '1024px',
        'mdpi'   => '1200px',
        'hdpi'   => '1440px',
    ];
}

/**
 * Optional explicit size-to-breakpoint overrides.
 * These take priority over the heuristic mapping in po_map_size_to_breakpoint().
 *
 * Example:
 *   'cover-desktop' => 'mdpi',
 *   'cover-tablet'  => 'ldpi',
 */
if (!isset($GLOBALS['preferred_size_map'])) {
    $GLOBALS['preferred_size_map'] = [];
}

// Ensure theme custom add_image_size values are mapped to breakpoints
$GLOBALS['preferred_size_map'] = array_merge($GLOBALS['preferred_size_map'], [
    'cover-desktop'  => 'mdpi',
    'cover-tablet'   => 'tablet',
    'cover-mobile'   => 'mobile',
    'featured-small' => 'mobile',
]);

if (!isset($GLOBALS['img_metadata_cache'])) {
    $GLOBALS['img_metadata_cache'] = [];
}

/**
 * Initialize the global sizes list by reading all WP-registered image sizes.
 * Must run after theme setup so custom sizes added via add_image_size() are available.
 */
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

if (!function_exists('po_map_size_to_breakpoint')) {
    function po_map_size_to_breakpoint(string $size): string
    {
        if (!empty($GLOBALS['preferred_size_map'][$size])) {
            return $GLOBALS['preferred_size_map'][$size];
        }

        $s = strtolower($size);

        $manual_overrides = [
            'cover-desktop'  => 'mdpi',
            'cover-tablet'   => 'ldpi',
            'cover-mobile'   => 'mobile',
            'featured-small' => 'mobile',
        ];

        if (isset($manual_overrides[$s]) && in_array($s, $GLOBALS['sizes'], true)) {
            return $manual_overrides[$s];
        }

        global $_wp_additional_image_sizes;
        $sizes_with_width = [];

        foreach ($GLOBALS['sizes'] as $registered_size) {
            $k = strtolower($registered_size);

            if (!empty($_wp_additional_image_sizes[$registered_size]['width'])) {
                $sizes_with_width[$k] = (int) $_wp_additional_image_sizes[$registered_size]['width'];
                continue;
            }

            switch ($registered_size) {
                case 'thumbnail':
                    $sizes_with_width[$k] = (int) get_option('thumbnail_size_w');
                    break;
                case 'medium':
                    $sizes_with_width[$k] = (int) get_option('medium_size_w');
                    break;
                case 'medium_large':
                    $sizes_with_width[$k] = (int) get_option('medium_large_size_w') ?: (int) get_option('medium_size_w');
                    break;
                case 'large':
                    $sizes_with_width[$k] = (int) get_option('large_size_w');
                    break;
                default:
                    $sizes_with_width[$k] = 0;
                    break;
            }
        }

        $width = $sizes_with_width[$s] ?? 0;

        if ($width > 0) {
            if ($width <= 599)  return 'mobile';
            if ($width <= 1023) return 'tablet';
            if ($width <= 1199) return 'ldpi';
            if ($width <= 1439) return 'mdpi';
            return 'hdpi';
        }

        $defaults = [
            'thumbnail'    => 'mobile',
            'medium'       => 'tablet',
            'medium_large' => 'tablet',
            'large'        => 'ldpi',
            'full'         => 'hdpi',
        ];

        if (isset($defaults[$s])) {
            return $defaults[$s];
        }

        $token_map = [
            'mobile'  => 'mobile',
            'tablet'  => 'tablet',
            'ldpi'    => 'ldpi',
            'mdpi'    => 'mdpi',
            'hdpi'    => 'hdpi',
            'small'   => 'mobile',
            'large'   => 'ldpi',
            'desktop' => 'mdpi',
        ];

        foreach ($token_map as $token => $bp) {
            if (preg_match('/(^|[-_])' . preg_quote($token, '/') . '($|[-_])/', $s)) {
                return $bp;
            }
        }

        return 'hdpi';
    }
}

if (!function_exists('po_get_preferred_size_for_breakpoint')) {
    function po_get_preferred_size_for_breakpoint(string $breakpoint): ?string
    {
        $sizes = array_reverse($GLOBALS['sizes']);

        foreach ($sizes as $size) {
            if (po_map_size_to_breakpoint($size) === $breakpoint) {
                return $size;
            }
        }

        return null;
    }
}

if (!function_exists('po_get_sizes_for_breakpoint')) {
    function po_get_sizes_for_breakpoint(string $breakpoint): array
    {
        $sizes   = array_reverse($GLOBALS['sizes']);
        $matches = [];

        foreach ($sizes as $size) {
            if (po_map_size_to_breakpoint($size) === $breakpoint) {
                $matches[] = $size;
            }
        }

        if (empty($matches)) {
            // Fallback para imágenes verticales / que no generen tamaños medianos o grandes
            foreach (['large', 'medium_large', 'medium', 'thumbnail', 'full'] as $fallback_size) {
                if (in_array($fallback_size, $GLOBALS['sizes'], true)) {
                    $matches[] = $fallback_size;
                }
            }
            $matches = array_unique($matches);
        }

        return $matches;
    }
}

if (!function_exists('po_detect_cover_sizes')) {
    function po_detect_cover_sizes(): array
    {
        global $_wp_additional_image_sizes;

        $cover_sizes = [];

        foreach ($GLOBALS['sizes'] as $size) {
            $size_lower = strtolower($size);

            if (strpos($size_lower, 'cover') === false) {
                continue;
            }

            $width = 0;

            if (!empty($_wp_additional_image_sizes[$size]['width'])) {
                $width = (int) $_wp_additional_image_sizes[$size]['width'];
            }

            $cover_sizes[] = [
                'name'       => $size,
                'width'      => $width,
                'breakpoint' => po_map_size_to_breakpoint($size),
            ];
        }

        usort($cover_sizes, fn($a, $b) => $b['width'] - $a['width']);

        return $cover_sizes;
    }
}

if (!function_exists('po_get_media_for_breakpoint')) {
    function po_get_media_for_breakpoint(string $breakpoint): ?string
    {
        if ($breakpoint === 'mobile') {
            return null;
        }

        $bps = $GLOBALS['breakpoints'];
        $min = $bps[$breakpoint];

        $order = ['mobile', 'tablet', 'ldpi', 'mdpi', 'hdpi'];
        $idx = array_search($breakpoint, $order);

        if ($idx !== false && $idx < count($order) - 1) {
            $next_bp = $order[$idx + 1];
            $max = ((int) $bps[$next_bp] - 1) . 'px';
            return "(min-width: {$min}) and (max-width: {$max})";
        } else {
            return "(min-width: {$min})";
        }
    }
}

if (!function_exists('po_get_safe_size')) {
    function po_get_safe_size(array $img_fields, string $desired_size, int $max_width = PHP_INT_MAX): ?string
    {
        // Si el tamaño deseado existe, usarlo siempre que no sea más ancho que max_width.
        if (!empty($img_fields['urls'][$desired_size]) && ($img_fields['sizes'][$desired_size]['width'] ?? 0) > 0) {
            if (($img_fields['sizes'][$desired_size]['width'] ?? 0) <= $max_width || $max_width === PHP_INT_MAX) {
                return $desired_size;
            }
        }

        $candidates = [];

        foreach ($img_fields['sizes'] as $size_key => $dim) {
            $width = $dim['width'] ?? 0;
            if ($width > 0 && $width <= $max_width && !empty($img_fields['urls'][$size_key])) {
                $candidates[$size_key] = $width;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        arsort($candidates);
        return array_key_first($candidates);
    }
}

// ---------------------------------------------------------------------------
// HTML tag helpers
// ---------------------------------------------------------------------------

if (!function_exists('img_create_source_tag')) {
    function img_create_source_tag(string $srcset, string $type, ?string $media = null): string
    {
        $srcset_attr = "srcset='" . esc_url($srcset) . "'";
        $type_attr   = "type='"   . esc_attr($type)   . "'";
        $media_attr  = $media ? " media='" . esc_attr($media) . "'" : '';

        return "<source {$srcset_attr} {$type_attr}{$media_attr}>";
    }
}

if (!function_exists('img_create_img_tag')) {
    function img_create_img_tag(string $src, int $width = 0, int $height = 0, array $attrs = []): string
    {
        $src_attr           = "src='"      . esc_url($src)                                          . "'";
        $width_attr         = $width  ? "width='"  . (int) $width  . "'" : '';
        $height_attr        = $height ? "height='" . (int) $height . "'" : '';
        $alt_attr           = "alt='"      . esc_attr($attrs['alt'] ?? '')                          . "'";
        $loading_attr       = "loading='"  . ($attrs['loading']  ?? 'lazy')                         . "'";
        $fetchpriority_attr = (!empty($attrs['fetchpriority']) && $attrs['fetchpriority'] !== 'auto')
            ? " fetchpriority='{$attrs['fetchpriority']}'"
            : '';
        $decoding_attr      = "decoding='" . ($attrs['decoding'] ?? 'async')                        . "'";
        $extra              = $attrs['extra'] ?? '';

        $parts = array_filter([$src_attr, $width_attr, $height_attr, $alt_attr, $loading_attr . $fetchpriority_attr, $decoding_attr]);

        return "<img " . implode(' ', $parts) . "{$extra}>";
    }
}

if (!function_exists('img_wrap_picture')) {
    function img_wrap_picture(array $sources, string $img_tag, array $attrs, int $width = 0, int $height = 0): string
    {
        $id_attr       = !empty($attrs['id'])    ? "id='"    . esc_attr($attrs['id'])    . "'" : '';
        $class_attr    = !empty($attrs['class']) ? "class='" . esc_attr($attrs['class']) . "'" : '';

        $style_attr = ($width && $height)
            ? "style='aspect-ratio: {$width} / {$height};'"
            : '';

        $picture_attrs = trim("{$id_attr} {$class_attr} {$style_attr}");

        $picture  = $picture_attrs ? "<picture {$picture_attrs}>" : "<picture>";
        $picture .= implode('', $sources);
        $picture .= $img_tag;
        $picture .= "</picture>";

        return $picture;
    }
}

// ---------------------------------------------------------------------------
// Image metadata parsers
// ---------------------------------------------------------------------------

if (!function_exists('img_get_empty_fields')) {
    function img_get_empty_fields(): array
    {
        return [
            'sizes' => [],
            'urls'  => [],
            'alt'   => '',
            'title' => '',
            'type'  => 'image/jpeg',
        ];
    }
}

if (!function_exists('img_parse_acf_image')) {
    function img_parse_acf_image(array $img): array
    {
        $sizes_urls       = [];
        $sizes_dimensions = [];

        foreach ($GLOBALS['sizes'] as $size) {
            if ($size === 'full') {
                $sizes_urls[$size]       = $img['url'];
                $sizes_dimensions[$size] = [
                    'width'  => (int) $img['width'],
                    'height' => (int) $img['height'],
                ];
            } else {
                $sizes_urls[$size]       = $img['sizes'][$size] ?? $img['url'];
                $sizes_dimensions[$size] = [
                    'width'  => (int) ($img['sizes']["{$size}-width"]  ?? $img['width']),
                    'height' => (int) ($img['sizes']["{$size}-height"] ?? $img['height']),
                ];
            }
        }

        return [
            'sizes' => $sizes_dimensions,
            'urls'  => $sizes_urls,
            'alt'   => $img['alt']       ?? '',
            'title' => $img['title']     ?? '',
            'type'  => $img['mime_type'] ?? 'image/jpeg',
        ];
    }
}

if (!function_exists('img_parse_url_image')) {
    function img_parse_url_image(string $img_url, bool $is_webp = false): array
    {
        $img_id = attachment_url_to_postid($img_url);

        if (!$img_id) return img_get_empty_fields();

        $img_meta = wp_get_attachment_metadata($img_id);

        if (!$img_meta) return img_get_empty_fields();

        $img_type      = $is_webp ? 'image/webp' : get_post_mime_type($img_id);
        $img_extension = $is_webp ? '.webp' : '';

        $sizes_urls       = [];
        $sizes_dimensions = [];

        foreach ($GLOBALS['sizes'] as $size) {
            $source = wp_get_attachment_image_url($img_id, $size);

            if (!$source) {
                // Fallback a la imagen completa si el tamaño no existe para imágenes no estándar (verticales, recortes especiales, etc.).
                $source = wp_get_attachment_url($img_id);
            }

            if (!$source) {
                continue;
            }

            if ($is_webp) {
                $webp_path = str_replace(home_url('/'), ABSPATH, $source) . '.webp';
                if (file_exists($webp_path)) {
                    $source .= '.webp';
                }
            }

            $sizes_urls[$size] = $source;

            if ($size === 'full') {
                $sizes_dimensions[$size] = [
                    'width'  => (int) $img_meta['width'],
                    'height' => (int) $img_meta['height'],
                ];
            } else {
                $sizes_dimensions[$size] = [
                    'width'  => (int) ($img_meta['sizes'][$size]['width']  ?? $img_meta['width']),
                    'height' => (int) ($img_meta['sizes'][$size]['height'] ?? $img_meta['height']),
                ];
            }
        }

        return [
            'sizes' => $sizes_dimensions,
            'urls'  => $sizes_urls,
            'alt'   => get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: '',
            'title' => get_the_title($img_id) ?: '',
            'type'  => $img_type,
        ];
    }
}

// ---------------------------------------------------------------------------
// Caching layer
// ---------------------------------------------------------------------------

if (!function_exists('img_get_fields')) {
    function img_get_fields(array|string $img, bool $is_webp = false): array
    {
        $cache_key = is_array($img)
            ? md5(serialize($img))
            : md5($img . ($is_webp ? '_webp' : ''));

        if (isset($GLOBALS['img_metadata_cache'][$cache_key])) {
            return $GLOBALS['img_metadata_cache'][$cache_key];
        }

        if (is_array($img) && isset($img['sizes'])) {
            $result = img_parse_acf_image($img);
        } else {
            $result = img_parse_url_image((string) $img, $is_webp);
        }

        $GLOBALS['img_metadata_cache'][$cache_key] = $result;

        return $result;
    }
}

// ---------------------------------------------------------------------------
// WebP detection
// ---------------------------------------------------------------------------

if (!function_exists('img_evaluate_webp')) {
    function img_evaluate_webp(string $img_url): bool
    {
        static $webp_cache = [];

        if (isset($webp_cache[$img_url])) {
            return $webp_cache[$img_url];
        }

        $file_path = str_replace(home_url(), ABSPATH, $img_url) . '.webp';
        $exists    = file_exists($file_path);

        $webp_cache[$img_url] = $exists;

        return $exists;
    }
}

// ---------------------------------------------------------------------------
// Attribute preparation
// ---------------------------------------------------------------------------

if (!function_exists('img_prepare_attributes')) {
    function img_prepare_attributes(
        string $id,
        string $classes,
        string $alt_text,
        string $fallback_alt,
        string $img_attr,
        bool $is_priority
    ): array {
        return [
            'id'            => $id      ? esc_attr($id)      : '',
            'class'         => $classes ? esc_attr($classes)  : '',
            'alt'           => esc_attr($alt_text ?: $fallback_alt),
            'loading'       => $is_priority ? 'eager' : 'lazy',
            'fetchpriority' => $is_priority ? 'high'  : 'auto',
            'decoding'      => 'async',
            'extra'         => $img_attr ? ' ' . wp_kses_post($img_attr) : '',
        ];
    }
}

// ---------------------------------------------------------------------------
// Main generator
// ---------------------------------------------------------------------------

/**
 * Generate a responsive <picture> element with WebP and breakpoint support.
 *
 * @param array|string $img         Main image. Accepts an ACF image array or a plain URL.
 * @param array|string $mobile_img  Optional separate image for the mobile breakpoint.
 * @param array|string $tablet_img  Optional separate image for the tablet breakpoint.
 * @param string       $max_size    Maximum WP size to use. Defaults to 'full'.
 * @param string       $min_size    Minimum WP size to use. Breakpoints whose best candidate
 *                                  is smaller than this size are skipped entirely.
 *                                  Only applies in standard mode (not cover or thumbnail).
 * @param string       $classes     CSS class(es) applied to the <picture> element.
 * @param string       $id          HTML id applied to the <picture> element.
 * @param string       $alt_text    Alt text override (falls back to attachment metadata).
 * @param bool         $is_cover    When true, auto-detects cover-* sizes and maps them to breakpoints.
 * @param string       $img_attr    Extra raw HTML attributes to inject into the <img> tag.
 * @param bool         $is_priority When true, sets loading="eager" and fetchpriority="high" (LCP images).
 *
 * @return string HTML string (does not echo).
 */
if (!function_exists('img_generate_picture_tag')) {
    function img_generate_picture_tag(
        array|string $img,
        array|string $mobile_img = [],
        array|string $tablet_img = [],
        string $max_size = 'full',
        string $min_size = '',
        string $classes = '',
        string $id = '',
        string $alt_text = '',
        bool $is_cover = false,
        string $img_attr = '',
        bool $is_priority = false
    ): string {

        if (empty($img)) return '';

        if (empty($GLOBALS['sizes'])) {
            po_init_sizes();
        }

        if (!in_array($max_size, $GLOBALS['sizes'], true)) {
            $max_size = 'full';
        }

        if ($min_size !== '' && !in_array($min_size, $GLOBALS['sizes'], true)) {
            $min_size = '';
        }

        $img_fields = img_get_fields($img);

        if (in_array($img_fields['type'], ['image/svg+xml', 'image/svg'], true)) {
            if (function_exists('image_to_svg')) {
                return image_to_svg($img);
            }
            return '';
        }

        $img_webp_fields = img_evaluate_webp($img_fields['urls']['full'])
            ? img_get_fields($img_fields['urls']['full'], true)
            : null;

        $attrs = img_prepare_attributes($id, $classes, $alt_text, $img_fields['alt'], $img_attr, $is_priority);

        // ── Thumbnail ─────────────────────────────────────────────────────────
        if ($max_size === 'thumbnail') {
            $sources = [];

            if ($img_webp_fields) {
                $sources[] = img_create_source_tag($img_webp_fields['urls']['thumbnail'], $img_webp_fields['type']);
            }

            $img_tag = img_create_img_tag(
                $img_fields['urls']['thumbnail'],
                $img_fields['sizes']['thumbnail']['width']  ?? 0,
                $img_fields['sizes']['thumbnail']['height'] ?? 0,
                $attrs
            );

            return img_wrap_picture($sources, $img_tag, $attrs);
        }

        // ── Cover mode ────────────────────────────────────────────────────────
        if ($is_cover) {
            $sources = [];
            $desktop_fields = $img_fields;
            $tablet_fields = !empty($tablet_img) ? img_get_fields($tablet_img) : null;
            $mobile_fields = !empty($mobile_img) ? img_get_fields($mobile_img) : null;

            $pick_size = function (?array $fields, array $preferred) {
                if (!$fields) {
                    return null;
                }

                foreach ($preferred as $size) {
                    if (!empty($fields['urls'][$size])) {
                        return $size;
                    }
                }

                foreach ($fields['urls'] as $size => $url) {
                    if (!empty($url)) {
                        return $size;
                    }
                }

                return null;
            };

            $ranges = [
                'hdpi' => ['fields' => $desktop_fields, 'sizes' => ['full']],
                'mdpi' => ['fields' => $desktop_fields, 'sizes' => ['cover-desktop', 'cover-tablet', 'full']],
                'ldpi' => ['fields' => $desktop_fields, 'sizes' => ['cover-tablet', 'cover-desktop', 'full']],
                'tablet' => ['fields' => $tablet_fields ?: $desktop_fields, 'sizes' => ['cover-tablet', 'cover-mobile', 'full']],
                'mobile' => ['fields' => $mobile_fields ?: $tablet_fields ?: $desktop_fields, 'sizes' => ['cover-mobile', 'cover-tablet', 'full']],
            ];

            $seen_urls = [];

            foreach ($ranges as $range => $config) {
                $field_set = $config['fields'];
                $size = $pick_size($field_set, $config['sizes']);

                if (!$size || empty($field_set['urls'][$size])) {
                    continue;
                }

                $media = $range === 'mobile' ? null : po_get_media_for_breakpoint($range);
                $url = $field_set['urls'][$size];

                if (in_array($url, $seen_urls, true)) {
                    continue;
                }

                if (img_evaluate_webp($url)) {
                    $webp_url = $url . '.webp';
                    if (!in_array($webp_url, $seen_urls, true)) {
                        $sources[] = img_create_source_tag($webp_url, 'image/webp', $media);
                        $seen_urls[] = $webp_url;
                    }
                }

                $sources[] = img_create_source_tag($url, $field_set['type'], $media);
                $seen_urls[] = $url;
            }

            $fallback_fields = $mobile_fields ?: $tablet_fields ?: $desktop_fields;
            $fallback_size = $pick_size($fallback_fields, ['cover-mobile', 'cover-tablet', 'cover-desktop', 'full']);
            if (!$fallback_size) {
                $fallback_size = 'full';
            }

            $img_tag = img_create_img_tag(
                $fallback_fields['urls'][$fallback_size] ?? $fallback_fields['urls']['full'],
                $fallback_fields['sizes'][$fallback_size]['width'] ?? 0,
                $fallback_fields['sizes'][$fallback_size]['height'] ?? 0,
                $attrs
            );

            return img_wrap_picture($sources, $img_tag, $attrs);
        }

        // ── Standard mode ─────────────────────────────────────────────────────
        $order       = ['hdpi', 'mdpi', 'ldpi', 'tablet', 'mobile'];
        $sources     = [];
        $used_sizes  = [];
        $seen_urls   = [];

        $max_width  = $img_fields['sizes'][$max_size]['width'] ?? 0;
        $allow_full = ($max_size === 'full');
        $min_width  = ($min_size !== '') ? ($img_fields['sizes'][$min_size]['width'] ?? 0) : 0;

        foreach ($order as $bp) {
            $media = ($bp === 'mobile') ? null : "(min-width: {$GLOBALS['breakpoints'][$bp]})";

            if ($bp === 'tablet' && !empty($tablet_img)) {
                $device_fields = img_get_fields($tablet_img);
                $device_webp   = img_evaluate_webp($device_fields['urls']['full'])
                    ? img_get_fields($device_fields['urls']['full'], true)
                    : null;

                if ($device_webp && !in_array($device_webp['urls']['full'], $seen_urls, true)) {
                    $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
                    $seen_urls[] = $device_webp['urls']['full'];
                }

                if (!in_array($device_fields['urls']['full'], $seen_urls, true)) {
                    $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
                    $seen_urls[] = $device_fields['urls']['full'];
                }
                continue;
            }

            if ($bp === 'mobile' && !empty($mobile_img)) {
                $device_fields = img_get_fields($mobile_img);
                $device_webp   = img_evaluate_webp($device_fields['urls']['full'])
                    ? img_get_fields($device_fields['urls']['full'], true)
                    : null;

                if ($device_webp && !in_array($device_webp['urls']['full'], $seen_urls, true)) {
                    $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
                    $seen_urls[] = $device_webp['urls']['full'];
                }

                if (!in_array($device_fields['urls']['full'], $seen_urls, true)) {
                    $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
                    $seen_urls[] = $device_fields['urls']['full'];
                }
                continue;
            }

            $candidates = po_get_sizes_for_breakpoint($bp);
            $preferred  = null;

            foreach ($candidates as $candidate) {
                if (in_array($candidate, $used_sizes, true)) continue;

                // Evitar full repetido si hay otro tamaño usable en el mismo breakpoint
                if (!$allow_full && $candidate === 'full' && count(array_filter($candidates, fn($s) => $s !== 'full')) > 0) {
                    continue;
                }

                $candidate_width = $img_fields['sizes'][$candidate]['width'] ?? 0;

                if ($max_width > 0 && $candidate_width > 0 && $candidate_width > $max_width) continue;
                if ($min_width > 0 && $candidate_width > 0 && $candidate_width < $min_width) continue;

                if (empty($img_fields['urls'][$candidate])) continue;

                $preferred = $candidate;
                break;
            }

            if (!$preferred) continue;

            $used_sizes[] = $preferred;

            $candidate_url = $img_fields['urls'][$preferred];

            if (!empty($candidate_url) && img_evaluate_webp($candidate_url)) {
                $webp_url = $candidate_url . '.webp';
                if (!in_array($webp_url, $seen_urls, true)) {
                    $sources[] = img_create_source_tag($webp_url, 'image/webp', $media);
                    $seen_urls[] = $webp_url;
                }
            }

            if (!in_array($candidate_url, $seen_urls, true)) {
                $sources[] = img_create_source_tag($candidate_url, $img_fields['type'], $media);
                $seen_urls[] = $candidate_url;
            }
        }

        // ── Fallback <img> ────────────────────────────────────────────────────
        if (!empty($mobile_img)) {
            $mobile_fields = img_get_fields($mobile_img);

            $img_tag = img_create_img_tag(
                $mobile_fields['urls']['full'],
                $mobile_fields['sizes']['full']['width']  ?? 0,
                $mobile_fields['sizes']['full']['height'] ?? 0,
                $attrs
            );

            return img_wrap_picture($sources, $img_tag, $attrs);
        }

        if ($max_size === 'full' || $is_cover) {
            $fallback_size = $max_size;
        } elseif ($min_size !== '') {
            $fallback_size = $min_size;
        } else {
            $fallback_size = in_array('medium', $GLOBALS['sizes'], true) ? 'medium' : 'full';
        }

        $img_tag = img_create_img_tag(
            $img_fields['urls'][$fallback_size] ?? $img_fields['urls']['full'],
            $img_fields['sizes'][$fallback_size]['width']  ?? 0,
            $img_fields['sizes'][$fallback_size]['height'] ?? 0,
            $attrs
        );

        $fallback_w = $img_fields['sizes'][$fallback_size]['width']  ?? 0;
        $fallback_h = $img_fields['sizes'][$fallback_size]['height'] ?? 0;

        return img_wrap_picture($sources, $img_tag, $attrs, $fallback_w, $fallback_h);
    }
}

// ---------------------------------------------------------------------------
// Convenience wrapper
// ---------------------------------------------------------------------------

/**
 * Echo the responsive <picture> tag generated by img_generate_picture_tag().
 * Accepts the exact same parameters.
 */
if (!function_exists('img_print_picture_tag')) {
    function img_print_picture_tag(
        array|string $img,
        array|string $mobile_img = [],
        array|string $tablet_img = [],
        string $max_size = 'full',
        string $min_size = '',
        string $classes = '',
        string $id = '',
        string $alt_text = '',
        bool $is_cover = false,
        string $img_attr = '',
        bool $is_priority = false
    ): void {
        echo img_generate_picture_tag(
            $img,
            $mobile_img,
            $tablet_img,
            $max_size,
            $min_size,
            $classes,
            $id,
            $alt_text,
            $is_cover,
            $img_attr,
            $is_priority
        );
    }
}
