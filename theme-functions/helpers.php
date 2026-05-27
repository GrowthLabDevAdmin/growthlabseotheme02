<?php

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
                        $style_attr .= 'padding: ' . normalize_block_spacing($s['padding']) . '; ';
                    }
                    if (!empty($s['margin'])) {
                        $style_attr .= 'margin: ' . normalize_block_spacing($s['margin']) . '; ';
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

if (!function_exists('normalize_block_spacing')) {
    function normalize_block_spacing($value)
    {
        if (is_string($value) && $value !== '' && preg_match('/^\d+$/', $value)) {
            return $value . 'px';
        }

        if (is_string($value) && preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value . 'px';
        }

        if (is_array($value)) {
            $unit = $value['unit'] ?? $value['unitType'] ?? '';

            if (isset($value['top']) || isset($value['right']) || isset($value['bottom']) || isset($value['left'])) {
                $t = format_spacing_item($value['top'] ?? $value[0] ?? null, $unit);
                $r = format_spacing_item($value['right'] ?? $value[1] ?? $t, $unit);
                $b = format_spacing_item($value['bottom'] ?? $value[2] ?? $t, $unit);
                $l = format_spacing_item($value['left'] ?? $value[3] ?? $r, $unit);

                if ($t === $r && $t === $b && $t === $l) {
                    return $t;
                }
                if ($t === $b && $r === $l) {
                    return "$t $r";
                }
                if ($r === $l) {
                    return "$t $r $b";
                }

                return "$t $r $b $l";
            }

            if (!empty($value['size'])) {
                return format_spacing_item($value['size'], $unit);
            }

            return implode(' ', array_filter(array_map(function ($item) use ($unit) {
                return format_spacing_item($item, $unit);
            }, $value)));
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
