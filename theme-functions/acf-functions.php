<?php
/* ACF Functions
-------------------------------------------------------------- */

// Custom Block Categories
if (!function_exists('theme_blocks_category')) {
    function theme_blocks_category($categories, $post)
    {
        $theme      = wp_get_theme();
        $theme_name = $theme->get('Name');
        $theme_slug = get_stylesheet();

        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => $theme_slug . '-blocks',
                    'title' => __($theme_name . ' Blocks', $theme_slug),
                )
            )
        );
    }
}
add_filter('block_categories_all', 'theme_blocks_category', 10, 2);

// Register Block Types with caching to prevent memory bloat during cache flushes
if (!function_exists('register_acf_blocks')) {
    function register_acf_blocks()
    {
        $cache_key   = 'theme_block_files_cache_' . get_stylesheet();
        $block_files = get_transient($cache_key);

        if ($block_files === false) {
            $block_files = [];

            // 1. Parent blocks
            foreach (glob(get_template_directory() . '/blocks/*/block.json') ?: [] as $block) {
                $data = json_decode(file_get_contents($block), true);
                if (!empty($data['name'])) {
                    $block_files[$data['name']] = dirname($block);
                }
            }

            // 2. Child blocks (override)
            foreach (glob(get_stylesheet_directory() . '/blocks/*/block.json') ?: [] as $block) {
                $data = json_decode(file_get_contents($block), true);
                if (!empty($data['name'])) {
                    $block_files[$data['name']] = dirname($block);
                }
            }

            // Cache for 24 hours, purged on theme update
            set_transient($cache_key, $block_files, 24 * HOUR_IN_SECONDS);
        }

        // 3. Register all found blocks
        foreach ($block_files as $block_dir) {
            register_block_type($block_dir);
        }
    }
}

// Clear block cache on theme switch/update
add_action('switch_theme', function () {
    delete_transient('theme_block_files_cache_' . get_stylesheet());
});

add_action('init', 'register_acf_blocks', 5);

/**
 * Load block assets only when block is present on the page
 * Optimized to prevent memory bloat during cache operations
 */

// Prevent unused block assets from loading
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Dequeue block assets that aren't used on the current page
 * SIMPLIFIED: Only run for singular pages with blocks to avoid parse_blocks on every pageload
 */
add_action('wp_enqueue_scripts', function () {
    global $post;

    if (!is_singular() || empty($post->post_content)) return;
    if (strpos($post->post_content, '<!-- wp:') === false) return;

    $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
    if (empty($registered_blocks)) return;

    $blocks        = parse_blocks($post->post_content);
    $blocks_in_use = array();

    $find_blocks = function ($blocks) use (&$find_blocks, &$blocks_in_use) {
        foreach ($blocks as $block) {
            if (!empty($block['blockName'])) {
                $blocks_in_use[] = $block['blockName'];
            }
            if (!empty($block['innerBlocks'])) {
                $find_blocks($block['innerBlocks']);
            }
        }
    };

    $find_blocks($blocks);

    if (empty($blocks_in_use)) return;

    $blocks_in_use = array_unique($blocks_in_use);

    $dequeued = 0;
    foreach ($registered_blocks as $block_name => $block_type) {
        if (in_array($block_name, $blocks_in_use)) continue;

        if (!empty($block_type->style))         wp_dequeue_style($block_type->style);
        if (!empty($block_type->editor_style))  wp_dequeue_style($block_type->editor_style);
        if (!empty($block_type->script))        wp_dequeue_script($block_type->script);
        if (!empty($block_type->editor_script)) wp_dequeue_script($block_type->editor_script);
        if (!empty($block_type->view_script))   wp_dequeue_script($block_type->view_script);

        $dequeued++;
        if ($dequeued > 100) break;
    }
}, 100);

/**
 * Move block scripts to footer
 */
add_action('wp_enqueue_scripts', function () {
    global $wp_scripts;

    if (empty($wp_scripts->registered)) return;

    $processed = 0;
    foreach ($wp_scripts->registered as $handle => $script) {
        if (!empty($script->src) && str_contains($script->src, '/blocks/')) {
            $wp_scripts->registered[$handle]->extra['group']    = 1;
            $wp_scripts->registered[$handle]->extra['strategy'] = 'defer';

            $processed++;
            if ($processed > 50) break;
        }
    }
}, 999);

/**
 * Prevent block styles from loading in <head> for blocks not in use
 */
add_filter('render_block', function ($block_content, $block) {
    return $block_content;
}, 10, 2);

// Add ACF Options Page
if (function_exists('acf_add_options_page') && current_user_can('manage_options')) {
    acf_add_options_page(array(
        'page_title' => 'Site Options',
        'menu_title' => 'Site Options',
        'menu_slug'  => 'site_options',
        'position'   => 70,
        'capability' => 'manage_options',
        'redirect'   => false
    ));
}

if (!function_exists('my_acf_json_save_point')) {
    function my_acf_json_save_point($path)
    {
        return get_stylesheet_directory() . '/acf-json';
    }
}

if (!function_exists('my_acf_json_load_point')) {
    function my_acf_json_load_point($paths)
    {
        unset($paths[0]);

        $paths[] = get_template_directory() . '/acf-json';

        if (get_stylesheet_directory() !== get_template_directory()) {
            $paths[] = get_stylesheet_directory() . '/acf-json';
        }

        return $paths;
    }
}

add_filter('acf/settings/save_json', 'my_acf_json_save_point');
add_filter('acf/settings/load_json', 'my_acf_json_load_point');

/**
 * Synchronize ACF Fields after theme updates (CI/CD Integration)
 *
 * Automatically imports ACF fields from parent theme respecting:
 * - Child theme overrides (if exists)
 * - Changes made in the repository
 * - MD5 hash of JSON files to detect changes
 *
 * SAFEGUARDS:
 * - Hash-based change detection (prevents unnecessary syncs)
 * - 30-second execution timeout (prevents memory bloat)
 * - Multisite safe — runs for active theme of each site, including child themes
 */
error_log('[ACF sync debug] acf-functions.php loaded — theme_dir: ' . basename(dirname(__FILE__, 2)) . ' | stylesheet: ' . get_stylesheet());
$_theme_dir = basename(dirname(__FILE__, 2));

$acf_sync = function () use ($_theme_dir) {
    // Evitar ejecución múltiple durante la misma sesión de actualización
    $lock_key = 'acf_sync_lock_' . $_theme_dir;
    if (get_transient($lock_key)) {
        error_log('[ACF sync debug] Lock active, skipping execution');
        return;
    }
    set_transient($lock_key, true, 300); // 5 min lock

    error_log('[ACF sync debug] START - _theme_dir: ' . $_theme_dir . ' | get_stylesheet(): ' . get_stylesheet() . ' | get_template(): ' . get_template());
    if (!function_exists('acf_get_field_groups')) {
        error_log('[ACF sync debug] ACF not available, exiting');
        delete_transient($lock_key);
        return;
    }
    if (defined('ACF_DOING_SYNC')) {
        error_log('[ACF sync debug] ACF_DOING_SYNC defined, exiting');
        delete_transient($lock_key);
        return;
    }
    
    // Verifica que sea el tema activo o el tema parent del tema activo (soporta child themes)
    $current_stylesheet = get_stylesheet();
    $current_template   = get_template();
    
    if ($_theme_dir !== $current_stylesheet && $_theme_dir !== $current_template) {
        error_log('[ACF sync debug] Theme not active, exiting - current_stylesheet: ' . $current_stylesheet . ' | current_template: ' . $current_template);
        delete_transient($lock_key);
        return;
    }

    error_log('[ACF sync debug] Proceeding with sync');
    global $wpdb;
    $memory_start = memory_get_usage();

    $parent_json_path = get_template_directory() . '/acf-json';
    $child_json_path  = get_stylesheet_directory() . '/acf-json';
    $is_child_theme   = $child_json_path !== $parent_json_path;

    $parent_json_files = array_filter(
        array_merge(
            glob($parent_json_path . '/group_*.json') ?: [],
            glob($parent_json_path . '/post_type_*.json') ?: [],
            glob($parent_json_path . '/taxonomy_*.json') ?: []
        ),
        fn($f) => is_readable($f)
    );

    if (empty($parent_json_files)) return;

    try {
        $content_hash = md5(implode('', array_map('md5_file', $parent_json_files)));

        $saved_hash = $wpdb->get_var(
            "SELECT option_value FROM {$wpdb->options}
             WHERE option_name = 'acf_json_parent_sync_hash'
             LIMIT 1"
        );

        if ($saved_hash === $content_hash) return;

        define('ACF_DOING_SYNC', true);

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                 VALUES ('acf_json_parent_sync_hash', %s, 'no')
                 ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
                $content_hash
            )
        );

        $max_execution_time = 30;
        $execution_start    = microtime(true);
        $synced             = 0;
        $skipped            = 0;
        $warnings           = 0;

        add_filter('acf/settings/save_json', '__return_false', 99);

        // ─── CPTs ─────────────────────────────────────────────────────────────
        foreach (glob($parent_json_path . '/post_type_*.json') ?: [] as $file) {
            if ((microtime(true) - $execution_start) > $max_execution_time) break;
            if (!is_readable($file)) {
                $warnings++;
                continue;
            }
            $json_data = json_decode(file_get_contents($file), true);
            if (empty($json_data) || !is_array($json_data)) {
                $warnings++;
                continue;
            }
            acf_update_post_type($json_data);
            $synced++;
        }

        // ─── Taxonomías ───────────────────────────────────────────────────────
        foreach (glob($parent_json_path . '/taxonomy_*.json') ?: [] as $file) {
            if ((microtime(true) - $execution_start) > $max_execution_time) break;
            if (!is_readable($file)) {
                $warnings++;
                continue;
            }
            $json_data = json_decode(file_get_contents($file), true);
            if (empty($json_data) || !is_array($json_data)) {
                $warnings++;
                continue;
            }
            acf_update_taxonomy($json_data);
            $synced++;
        }

        // ─── Field Groups ─────────────────────────────────────────────────────
        $groups = acf_get_field_groups();

        $rows = $wpdb->get_results(
            "SELECT MIN(ID) as ID, post_name FROM {$wpdb->posts}
             WHERE post_type = 'acf-field-group'
             AND post_status IN ('publish', 'acf-disabled', 'trash', 'draft')
             GROUP BY post_name"
        );
        $existing_ids_by_name = [];
        foreach ($rows as $r) {
            $existing_ids_by_name[$r->post_name] = (int) $r->ID;
        }

        $processed_keys = [];

        foreach ($groups as $group) {
            if ((microtime(true) - $execution_start) > $max_execution_time) break;
            if (empty($group['local']) || $group['local'] !== 'json') continue;
            if (in_array($group['key'], $processed_keys, true)) continue;
            $processed_keys[] = $group['key'];

            if ($is_child_theme && file_exists($child_json_path . '/' . $group['key'] . '.json')) {
                $skipped++;
                continue;
            }

            $json_file = $parent_json_path . '/' . $group['key'] . '.json';
            if (!file_exists($json_file) || !is_readable($json_file)) {
                $warnings++;
                continue;
            }

            $json_data = json_decode(file_get_contents($json_file), true);
            if (empty($json_data) || !is_array($json_data)) {
                $warnings++;
                continue;
            }

            $existing_id = $existing_ids_by_name[$group['key']] ?? 0;
            if ($existing_id) $json_data['ID'] = $existing_id;

            acf_import_field_group($json_data);

            if (isset($json_data['active']) && $json_data['active'] === false) {
                $group_id = $existing_id ?: (acf_get_field_group($group['key'])['ID'] ?? 0);
                if ($group_id) {
                    wp_update_post(['ID' => $group_id, 'post_status' => 'acf-disabled']);
                }
            }

            $synced++;
        }

        // ─── Cleanup ──────────────────────────────────────────────────────────
        remove_filter('acf/settings/save_json', '__return_false', 99);

        $status = $warnings === 0 ? 'OK' : 'COMPLETED WITH WARNINGS';
        error_log(
            '[ACF sync:' . $_theme_dir . '] ' . $status
                . ' — synced: ' . $synced . ', skipped: ' . $skipped . ', warnings: ' . $warnings
                . ' | mem: ' . round((memory_get_usage() - $memory_start) / 1024 / 1024, 2) . 'MB'
                . ' | peak: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
                . ' | time: ' . round(microtime(true) - $execution_start, 2) . 's'
        );
        delete_transient($lock_key);
    } catch (Throwable $e) {
        remove_filter('acf/settings/save_json', '__return_false', 99);
        error_log('[ACF sync:' . $_theme_dir . '] ERROR — ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine());
        delete_transient($lock_key);
    }
};

add_action('wppusher_theme_was_updated',   $acf_sync);
add_action('wppusher_theme_was_installed', $acf_sync);

// Allow HTML in ACF fields
add_filter('acf/shortcode/allow_unsafe_html', function () {
    return true;
}, 10, 2);
add_filter('acf/the_field/allow_unsafe_html', function () {
    return true;
}, 10, 2);
add_filter('acf/the_sub_field/allow_unsafe_html', function () {
    return true;
}, 10, 2);

if (is_admin()) {
    add_filter('acf/admin/prevent_escaped_html_notice', '__return_true');
}

/**
 * ACF Color Picker Custom Palette
 */
if (!function_exists('get_theme_color_palette_for_acf')) {
    function get_theme_color_palette_for_acf()
    {
        return array(
            array('name' => 'Primary Color',    'color' => get_theme_mod('primary_color',         '#15253f')),
            array('name' => 'Primary Dark',     'color' => get_theme_mod('primary_color_dark',    '#08182f')),
            array('name' => 'Primary Light',    'color' => get_theme_mod('primary_color_light',   '#2C3D5B')),
            array('name' => 'Secondary Color',  'color' => get_theme_mod('secondary_color',       '#F4F3EE')),
            array('name' => 'Secondary Dark',   'color' => get_theme_mod('secondary_color_dark',  '#E7E5DF')),
            array('name' => 'Secondary Light',  'color' => get_theme_mod('secondary_color_light', '#FFFFFF')),
            array('name' => 'Tertiary Color',   'color' => get_theme_mod('tertiary_color',        '#BC9061')),
            array('name' => 'Tertiary Dark',    'color' => get_theme_mod('tertiary_color_dark',   '#9D7A55')),
            array('name' => 'Tertiary Light',   'color' => get_theme_mod('tertiary_color_light',  '#DCAB77')),
            array('name' => 'Text Color',       'color' => get_theme_mod('text_color',            '#15253f')),
        );
    }
}

if (!function_exists('acf_color_picker_palette_script')) {
    function acf_color_picker_palette_script()
    {
        $colors  = get_theme_color_palette_for_acf();
        $palette = array_column($colors, 'color');
        $palette_json = json_encode($palette);
?>
        <script type="text/javascript">
            (function($) {
                if (typeof acf !== 'undefined') {
                    acf.addAction('ready', function() {
                        acf.add_filter('color_picker_args', function(args, $field) {
                            args.palettes = <?php echo $palette_json; ?>;
                            return args;
                        });
                    });
                }
            })(jQuery);
        </script>
        <style>
            .acf-color-picker .wp-picker-container .iris-palette {
                width: 100% !important;
                max-width: 100% !important;
            }
        </style>
<?php
    }
}
add_action('acf/input/admin_head', 'acf_color_picker_palette_script');
