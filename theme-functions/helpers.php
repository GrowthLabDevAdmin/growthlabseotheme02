<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('get_text_domain')) {
    function get_text_domain()
    {
        return $GLOBALS['theme_text_domain'];
    }
}


if (!function_exists('get_field_options')) {
    function get_field_options($field_name, $format_value = true)
    {
        if (empty($field_name) || !is_string($field_name)) return null;

        static $cache = [];
        $key = $field_name . '|' . ($format_value ? '1' : '0');
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value       = get_field($field_name, 'option', $format_value);
        $cache[$key] = $value;
        return $value;
    }
}

if (!function_exists('block_style_attribute')) {
    function block_style_attribute($block = [])
    {
        if (empty($block['style'])) {
            return '';
        }

        $style_attr = '';

        if (is_string($block['style'])) {
            $style_attr = $block['style'];
        } elseif (is_array($block['style'])) {
            if (!empty($block['style']['spacing'])) {
                $s = $block['style']['spacing'];
                if (is_string($s)) {
                    $style_attr .= $s . ' ';
                } elseif (is_array($s)) {
                    if (!empty($s['padding'])) {
                        $style_attr .= normalize_spacing_property('padding', $s['padding']) . ' ';
                    }
                    if (!empty($s['margin'])) {
                        $style_attr .= normalize_spacing_property('margin', $s['margin']) . ' ';
                    }
                    if (!empty($s['blockGap'])) {
                        $style_attr .= 'gap: ' . normalize_block_spacing($s['blockGap']) . '; ';
                    }
                }
            }

            foreach ($block['style'] as $k => $v) {
                if ($k === 'spacing') {
                    continue;
                }
                if (is_string($v) || is_numeric($v)) {
                    $style_attr .= "$k: " . normalize_block_spacing($v) . '; ';
                }
            }
        }

        $style_attr = trim($style_attr);
        if (!$style_attr) {
            return '';
        }

        return ' style="' . esc_attr($style_attr) . '"';
    }
}

if (!function_exists('normalize_spacing_property')) {
    function normalize_spacing_property($property = 'padding', $value = null)
    {
        if (!$value) {
            return '';
        }

        if (is_string($value)) {
            return "$property: " . normalize_block_spacing($value) . ';';
        }

        if (is_numeric($value)) {
            return "$property: " . normalize_block_spacing($value) . ';';
        }

        if (!is_array($value)) {
            return '';
        }

        $unit = $value['unit'] ?? $value['unitType'] ?? '';
        $t = $value['top'] ?? null;
        $r = $value['right'] ?? null;
        $b = $value['bottom'] ?? null;
        $l = $value['left'] ?? null;

        $t_formatted = $t !== null ? format_spacing_item($t, $unit) : '';
        $r_formatted = $r !== null ? format_spacing_item($r, $unit) : '';
        $b_formatted = $b !== null ? format_spacing_item($b, $unit) : '';
        $l_formatted = $l !== null ? format_spacing_item($l, $unit) : '';

        if (!$t_formatted && !$r_formatted && !$b_formatted && !$l_formatted) {
            return '';
        }

        if ($t_formatted && $r_formatted && $b_formatted && $l_formatted) {
            if ($t_formatted === $r_formatted && $t_formatted === $b_formatted && $t_formatted === $l_formatted) {
                return "$property: $t_formatted;";
            }
            if ($t_formatted === $b_formatted && $r_formatted === $l_formatted) {
                return "$property: $t_formatted $r_formatted;";
            }
            if ($r_formatted === $l_formatted) {
                return "$property: $t_formatted $r_formatted $b_formatted;";
            }
            return "$property: $t_formatted $r_formatted $b_formatted $l_formatted;";
        }

        $output = '';
        if ($t_formatted) {
            $output .= "$property-top: $t_formatted; ";
        }
        if ($r_formatted) {
            $output .= "$property-right: $r_formatted; ";
        }
        if ($b_formatted) {
            $output .= "$property-bottom: $b_formatted; ";
        }
        if ($l_formatted) {
            $output .= "$property-left: $l_formatted; ";
        }

        return rtrim($output, ' ');
    }
}

if (!function_exists('normalize_block_spacing')) {
    function normalize_block_spacing($value)
    {
        if (is_string($value) && $value !== '' && preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        if (is_numeric($value)) {
            return $value . 'px';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $unit = $value['unit'] ?? $value['unitType'] ?? '';

            if (!empty($value['size'])) {
                return format_spacing_item($value['size'], $unit);
            }

            $items = array_filter(array_map(function ($item) use ($unit) {
                return format_spacing_item($item, $unit);
            }, $value));

            return implode(' ', $items);
        }

        return '';
    }
}

if (!function_exists('format_spacing_item')) {
    function format_spacing_item($value, $unit = '')
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value) && preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . ($unit ?: 'px');
        }

        if (is_numeric($value)) {
            return $value . ($unit ?: 'px');
        }

        return (string) $value;
    }
}

if (!function_exists('filterContentByLanguage')) {
    function filterContentByLanguage($lang = 'es')
    {
        if (empty($lang) || !is_string($lang)) return false;

        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url($uri, PHP_URL_PATH) ?: '/';
        $prefix = '/' . ltrim($lang, '/');

        if ($path === $prefix) return true;
        if (strpos($path, $prefix . '/') === 0) return true;

        return false;
    }
}

if (!function_exists('get_languages_map')) {
    function get_languages_map()
    {
        static $map = null;
        if ($map !== null) return $map;

        $lang_options = get_field_options('options_by_language') ?: [];
        if (!is_array($lang_options)) {
            $map = [];
            return $map;
        }

        $map = array_column($lang_options, 'language', 'url_language_slug');
        return $map;
    }
}

if (!function_exists('get_current_language')) {
    function get_current_language()
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $languages = get_languages_map();

        foreach ($languages as $slug => $language) {
            if (filterContentByLanguage($slug)) {
                $cached = [
                    'slug'     => $slug,
                    'language' => $language
                ];
                return $cached;
            }
        }

        $cached = [
            'slug'     => '',
            'language' => 'English'
        ];

        return $cached;
    }
}

if (!function_exists('get_current_language_suffix')) {
    function get_current_language_suffix()
    {
        if (get_current_language()['slug'] !== '') return '_' . get_current_language()['slug'];
        return '';
    }
}

if (!function_exists('get_current_language_options')) {
    function get_current_language_options()
    {
        $current = get_current_language();
        $slug    = $current['slug'] ?? '';

        if ($slug === '') {
            return get_field_options('options');
        }

        $lang_opts = get_field_options('options_by_language') ?: [];
        if (!is_array($lang_opts)) return get_field_options('options');

        foreach ($lang_opts as $lang_opt) {
            if (!isset($lang_opt['url_language_slug'])) continue;
            if ($lang_opt['url_language_slug'] === $slug && isset($lang_opt['options'])) {
                return $lang_opt['options'];
            }
        }

        return get_field_options('options');
    }
}

if (!function_exists('get_flat_number')) {
    function get_flat_number($phone)
    {
        if (!$phone) return;
        return preg_replace("/[^0-9]/", '', $phone);
    }
}

if (!function_exists('print_title')) {
    function print_title($title, $tag = 'p', $classes = '', $is_hero = false)
    {
        if (!$title) return;
        $tag = $tag ?? ($is_hero ? 'h1' : 'p');
        echo "<$tag class='$classes'>" . $title . "</$tag>";
    }
}

if (!function_exists('dd')) {
    function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

if (!function_exists('format_number_abbreviated')) {
    function format_number_abbreviated($number)
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return $number;
    }
}

if (!function_exists('get_yt_code')) {
    function get_yt_code($url = false)
    {
        if (!$url) return false;
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1];
    }
}

// Transforma los estilos en línea de font-size a variables CSS personalizadas
function transform_inline_font_sizes(
    string $html,
    string $font_size_var = '--fs'
): string {

    if (trim($html) === '') {
        return $html;
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();

    $dom->loadHTML(
        mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    foreach ($dom->getElementsByTagName('*') as $element) {

        if (!$element->hasAttribute('style')) {
            continue;
        }

        $style = $element->getAttribute('style');

        if (
            !preg_match(
                '/font-size\s*:\s*([^;]+)/i',
                $style,
                $matches
            )
        ) {
            continue;
        }

        $font_size = trim($matches[1]);

        // Elimina el font-size original
        $style = preg_replace(
            '/font-size\s*:\s*[^;]+;?/i',
            '',
            $style
        );

        $style = trim($style);

        if ($style !== '' && substr($style, -1) !== ';') {
            $style .= ';';
        }

        // Agrega la variable y la nueva regla
        $style .= sprintf(
            '%s:%s;font-size:calc(var(%s) * var(--font-scale, 1));',
            $font_size_var,
            $font_size,
            $font_size_var
        );

        $element->setAttribute('style', $style);
    }

    return $dom->saveHTML();
}

if (!function_exists('get_youtube_thumbnail')) {
    function get_youtube_thumbnail(string $video_id, string $size = 'hqdefault'): string
    {
        $sizes = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];
        $base  = "https://img.youtube.com/vi/{$video_id}";

        // Validar desde el tamaño solicitado hacia abajo
        $start = array_search($size, $sizes, true);
        $sizes = array_slice($sizes, $start !== false ? $start : 2);

        foreach ($sizes as $s) {
            $url      = "{$base}/{$s}.jpg";
            $response = wp_remote_head($url);

            if (! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                // YouTube devuelve un placeholder 120×90 para tamaños no disponibles
                $headers = wp_remote_retrieve_headers($response);
                $length  = (int) ($headers['content-length'] ?? 0);
                if ($length > 2000) return $url; // el placeholder pesa ~1.4kb
            }
        }

        return "{$base}/hqdefault.jpg"; // fallback seguro
    }
}
