<?php

// Helper function to get ACF option fields
function get_field_options($field_name, $format_value = true)
{
    if (empty($field_name) || !is_string($field_name)) return null;

    static $cache = [];
    $key = $field_name . '|' . ($format_value ? '1' : '0');
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $value = get_field($field_name, 'option', $format_value);
    $cache[$key] = $value;
    return $value;
}

// Language Filter
function filterContentByLanguage($lang = 'es')
{
    if (empty($lang) || !is_string($lang)) return false;

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $prefix = '/' . ltrim($lang, '/');

    if ($path === $prefix) return true;
    if (strpos($path, $prefix . '/') === 0) return true;

    return false;
}

// Languages map accessor (cached per request)
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

// Get Current Language
function get_current_language()
{
    static $cached = null;
    if ($cached !== null) return $cached;

    $languages = get_languages_map();

    foreach ($languages as $slug => $language) {
        if (filterContentByLanguage($slug)) {
            $cached = [
                'slug' => $slug,
                'language' => $language
            ];
            return $cached;
        }
    }

    $cached = [
        'slug' => '',
        'language' => 'English'
    ];

    return $cached;
}

function get_current_language_suffix()
{
    if (get_current_language()['slug'] !== '')  return '_' . get_current_language()['slug'];
    return '';
}

// Get Current Language Options
function get_current_language_options()
{
    $current = get_current_language();
    $slug = $current['slug'] ?? '';

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

//Phone number format remover
function get_flat_number($phone)
{
    if (! $phone) return;
    return preg_replace("/[^0-9]/", '', $phone);
}

//Print title helper
function print_title($title, $tag = 'p', $classes = '', $is_hero = false)
{
    if (!$title) return;
    $tag = $tag ?? ($is_hero ? 'h1' : 'p');
    echo "<$tag class='$classes'>" . $title . "</$tag>";
}

//Debug helper
function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

//Format Numerical Amount
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

// YouTube Video ID Extractor
function get_yt_code($url = false)
{
    // Here is a sample of the URLs this regex matches: (there can be more content after the given URL that will be ignored)

    // http://youtu.be/dQw4w9WgXcQ
    // http://www.youtube.com/embed/dQw4w9WgXcQ
    // http://www.youtube.com/watch?v=dQw4w9WgXcQ
    // http://www.youtube.com/?v=dQw4w9WgXcQ
    // http://www.youtube.com/v/dQw4w9WgXcQ
    // http://www.youtube.com/e/dQw4w9WgXcQ
    // http://www.youtube.com/user/username#p/u/11/dQw4w9WgXcQ
    // http://www.youtube.com/sandalsResorts#p/c/54B8C800269D7C1B/0/dQw4w9WgXcQ
    // http://www.youtube.com/watch?feature=player_embedded&v=dQw4w9WgXcQ
    // http://www.youtube.com/?feature=player_embedded&v=dQw4w9WgXcQ

    // It also works on the youtube-nocookie.com URL with the same above options.
    // It will also pull the ID from the URL in an embed code (both iframe and object tags)
    if (! $url) return false;
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return $match[1];
}
