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
 */

/**
 * SUGGESTED BREAKPOINTS (adjustable)
 * - mobile : 0    – 599px
 * - tablet : 600  – 1023px
 * - ldpi   : 1024 – 1199px
 * - mdpi   : 1200 – 1439px
 * - hdpi   : 1440px+
 */
$GLOBALS['breakpoints'] = [
    'mobile' => '0px',
    'tablet' => '600px',
    'ldpi'   => '1024px',
    'mdpi'   => '1200px',
    'hdpi'   => '1440px',
];

/**
 * Initialize the global sizes list by reading all WP-registered image sizes.
 * Must run after theme setup so custom sizes added via add_image_size() are available.
 */
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

add_action('after_setup_theme', 'po_init_sizes', 999);

/**
 * Optional explicit size-to-breakpoint overrides.
 * These take priority over the heuristic mapping in po_map_size_to_breakpoint().
 *
 * Example:
 *   'cover-desktop' => 'mdpi',
 *   'cover-tablet'  => 'ldpi',
 */
$GLOBALS['preferred_size_map'] = [];

/**
 * Map a registered WP image size name to a breakpoint key.
 * Resolution order:
 *   1. Global $preferred_size_map overrides
 *   2. Hardcoded manual overrides for known custom size names
 *   3. Numeric width from WP size data → threshold-based mapping
 *   4. Exact name matches for WP default sizes
 *   5. Token-based pattern matching on the size name string
 *   6. Final fallback: 'hdpi'
 */
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

/**
 * Return the preferred WP size name for a given breakpoint.
 */
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

/**
 * Return all registered WP size names that map to a given breakpoint,
 * ordered from largest → smallest.
 */
function po_get_sizes_for_breakpoint(string $breakpoint): array
{
    $sizes = array_reverse($GLOBALS['sizes']);
    $matches = [];

    foreach ($sizes as $size) {
        if (po_map_size_to_breakpoint($size) === $breakpoint) {
            $matches[] = $size;
        }
    }

    return $matches;
}

/**
 * Detect registered sizes whose name contains 'cover'.
 * Returns an array sorted by width descending (largest cover first).
 * Each entry: ['name' => string, 'width' => int, 'breakpoint' => string]
 *
 * NOTE: 'full' is intentionally excluded.
 */
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

// ---------------------------------------------------------------------------
// HTML tag helpers
// ---------------------------------------------------------------------------

function img_create_source_tag(string $srcset, string $type, ?string $media = null): string
{
    $srcset_attr = "srcset='" . esc_url($srcset) . "'";
    $type_attr   = "type='"   . esc_attr($type)   . "'";
    $media_attr  = $media ? " media='" . esc_attr($media) . "'" : '';

    return "<source {$srcset_attr} {$type_attr}{$media_attr}>";
}

function img_create_img_tag(string $src, int $width = 0, int $height = 0, array $attrs = []): string
{
    $src_attr           = "src='"      . esc_url($src)             . "'";
    $width_attr         = $width  ? "width='"  . (int) $width  . "'" : '';
    $height_attr        = $height ? "height='" . (int) $height . "'" : '';
    $alt_attr           = "alt='"      . esc_attr($attrs['alt'] ?? '')   . "'";
    $loading_attr       = "loading='"  . ($attrs['loading']  ?? 'lazy')  . "'";
    $fetchpriority_attr = (!empty($attrs['fetchpriority']) && $attrs['fetchpriority'] !== 'auto')
        ? " fetchpriority='{$attrs['fetchpriority']}'"
        : '';
    $decoding_attr      = "decoding='" . ($attrs['decoding'] ?? 'async') . "'";
    $extra              = $attrs['extra'] ?? '';

    $parts = array_filter([$src_attr, $width_attr, $height_attr, $alt_attr, $loading_attr . $fetchpriority_attr, $decoding_attr]);

    return "<img " . implode(' ', $parts) . "{$extra}>";
}

function img_wrap_picture(array $sources, string $img_tag, array $attrs): string
{
    $id_attr      = !empty($attrs['id'])    ? "id='"    . esc_attr($attrs['id'])    . "'" : '';
    $class_attr   = !empty($attrs['class']) ? "class='" . esc_attr($attrs['class']) . "'" : '';
    $picture_attrs = trim($id_attr . ' ' . $class_attr);

    $picture  = $picture_attrs ? "<picture {$picture_attrs}>" : "<picture>";
    $picture .= implode('', $sources);
    $picture .= $img_tag;
    $picture .= "</picture>";

    return $picture;
}

// ---------------------------------------------------------------------------
// Image metadata parsers
// ---------------------------------------------------------------------------

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

function img_parse_url_image(string $img_url, bool $is_webp = false): array
{
    $img_id = attachment_url_to_postid($img_url);

    if (!$img_id) {
        return img_get_empty_fields();
    }

    $img_meta = wp_get_attachment_metadata($img_id);

    if (!$img_meta) {
        return img_get_empty_fields();
    }

    $img_type      = $is_webp ? 'image/webp' : get_post_mime_type($img_id);
    $img_extension = $is_webp ? '.webp' : '';

    $sizes_urls       = [];
    $sizes_dimensions = [];

    foreach ($GLOBALS['sizes'] as $size) {
        $sizes_urls[$size] = wp_get_attachment_image_url($img_id, $size) . $img_extension;

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

// ---------------------------------------------------------------------------
// Caching layer
// ---------------------------------------------------------------------------

$GLOBALS['img_metadata_cache'] = [];

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

// ---------------------------------------------------------------------------
// WebP detection
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Attribute preparation
// ---------------------------------------------------------------------------

function img_prepare_attributes(
    string $id,
    string $classes,
    string $alt_text,
    string $fallback_alt,
    string $img_attr,
    bool $is_priority
): array {
    return [
        'id'            => $id      ? esc_attr($id)     : '',
        'class'         => $classes ? esc_attr($classes) : '',
        'alt'           => esc_attr($alt_text ?: $fallback_alt),
        'loading'       => $is_priority ? 'eager' : 'lazy',
        'fetchpriority' => $is_priority ? 'high'  : 'auto',
        'decoding'      => 'async',
        'extra'         => $img_attr ? ' ' . wp_kses_post($img_attr) : '',
    ];
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

    if (empty($img)) {
        return '';
    }

    if (empty($GLOBALS['sizes'])) {
        po_init_sizes();
    }

    // Validate max_size; fall back to 'full' if not registered
    if (!in_array($max_size, $GLOBALS['sizes'], true)) {
        $max_size = 'full';
    }

    // Validate min_size; ignore if not registered
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

    // ------------------------------------------------------------------
    // Shortcut: thumbnail — min_size no aplica en este modo
    // ------------------------------------------------------------------
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

    // ------------------------------------------------------------------
    // Cover mode — min_size no aplica en este modo
    // ------------------------------------------------------------------
    if ($is_cover && empty($tablet_img) && empty($mobile_img)) {
        $cover_sizes = po_detect_cover_sizes();

        if (!empty($cover_sizes)) {
            $sources        = [];
            $smallest_cover = null;

            foreach ($cover_sizes as $cover_info) {
                $size_name  = $cover_info['name'];
                $breakpoint = $cover_info['breakpoint'];

                if (
                    $smallest_cover === null ||
                    $cover_info['width'] < $smallest_cover['width']
                ) {
                    if (
                        strpos(strtolower($size_name), 'mobile') !== false ||
                        $breakpoint === 'mobile'
                    ) {
                        $smallest_cover = $cover_info;
                    }
                }

                $media = ($breakpoint === 'mobile')
                    ? null
                    : "(min-width: {$GLOBALS['breakpoints'][$breakpoint]})";

                if (empty($img_fields['urls'][$size_name])) {
                    continue;
                }

                if (img_evaluate_webp($img_fields["urls"][$size_name])) {
                    $sources[] = img_create_source_tag(
                        $img_fields["urls"][$size_name] . ".webp",
                        "image/webp",
                        $media
                    );
                }

                $sources[] = img_create_source_tag(
                    $img_fields['urls'][$size_name],
                    $img_fields['type'],
                    $media
                );
            }

            $fallback_size = $smallest_cover ? $smallest_cover['name'] : 'cover-mobile';

            if (empty($img_fields['urls'][$fallback_size])) {
                $fallback_size = 'full';
            }

            $img_tag = img_create_img_tag(
                $img_fields['urls'][$fallback_size],
                $img_fields['sizes'][$fallback_size]['width']  ?? 0,
                $img_fields['sizes'][$fallback_size]['height'] ?? 0,
                $attrs
            );

            return img_wrap_picture($sources, $img_tag, $attrs);
        }
    }

    // ------------------------------------------------------------------
    // Standard mode
    // ------------------------------------------------------------------
    $order      = ['hdpi', 'mdpi', 'ldpi', 'tablet', 'mobile'];
    $sources    = [];
    $used_sizes = [];

    $max_width  = $img_fields['sizes'][$max_size]['width'] ?? 0;
    $allow_full = ($max_size === 'full');

    // Ancho mínimo permitido; 0 = sin restricción
    $min_width  = ($min_size !== '') ? ($img_fields['sizes'][$min_size]['width'] ?? 0) : 0;

    foreach ($order as $bp) {
        $media = ($bp === 'mobile') ? null : "(min-width: {$GLOBALS['breakpoints'][$bp]})";

        // --- Tablet override ---
        if ($bp === 'tablet' && !empty($tablet_img)) {
            $device_fields = img_get_fields($tablet_img);
            $device_webp   = img_evaluate_webp($device_fields['urls']['full'])
                ? img_get_fields($device_fields['urls']['full'], true)
                : null;

            if ($device_webp) {
                $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
            }

            $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
            continue;
        }

        // --- Mobile override ---
        if ($bp === 'mobile' && !empty($mobile_img)) {
            $device_fields = img_get_fields($mobile_img);
            $device_webp   = img_evaluate_webp($device_fields['urls']['full'])
                ? img_get_fields($device_fields['urls']['full'], true)
                : null;

            if ($device_webp) {
                $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
            }

            $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
            continue;
        }

        $candidates = po_get_sizes_for_breakpoint($bp);
        $preferred  = null;

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $used_sizes, true)) {
                continue;
            }

            if (!$allow_full && $candidate === 'full') {
                continue;
            }

            $candidate_width = $img_fields['sizes'][$candidate]['width'] ?? 0;

            // Descartar candidatos por encima del máximo
            if ($max_width > 0 && $candidate_width > 0 && $candidate_width > $max_width) {
                continue;
            }

            // Saltar breakpoint completo si el candidato está por debajo del mínimo
            if ($min_width > 0 && $candidate_width > 0 && $candidate_width < $min_width) {
                continue;
            }

            $preferred = $candidate;
            break;
        }

        if (!$preferred) {
            continue;
        }

        $used_sizes[] = $preferred;

        if (!empty($img_fields['urls'][$preferred]) && img_evaluate_webp($img_fields['urls'][$preferred])) {
            $sources[] = img_create_source_tag(
                $img_fields['urls'][$preferred] . '.webp',
                'image/webp',
                $media
            );
        }

        if (!empty($img_fields['urls'][$preferred])) {
            $sources[] = img_create_source_tag($img_fields['urls'][$preferred], $img_fields['type'], $media);
        }
    }

    // ------------------------------------------------------------------
    // <img> fallback
    // ------------------------------------------------------------------
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

    // Si se especificó min_size y el fallback natural quedara por debajo, usar min_size.
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

    return img_wrap_picture($sources, $img_tag, $attrs);
}

// ---------------------------------------------------------------------------
// Convenience wrapper
// ---------------------------------------------------------------------------

/**
 * Echo the responsive <picture> tag generated by img_generate_picture_tag().
 * Accepts the exact same parameters.
 */
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
