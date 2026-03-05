<?php
/* ACF Functions
-------------------------------------------------------------- */

// Custom Block Categories
function growthlabtheme02_blocks_category($categories, $post)
{
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'growthlabtheme02-blocks',
                'title' => __('Growthlab Theme 01 Blocks', 'growthlabtheme02-blocks'),
            )
        )
    );
}
add_filter('block_categories_all', 'growthlabtheme02_blocks_category', 10, 2);

// Register Block Types
function register_acf_blocks()
{
    $blocks = glob(get_stylesheet_directory() . '/blocks/*/block.json');

    foreach ($blocks as $block) {
        register_block_type(dirname($block));
    }
}
add_action('init', 'register_acf_blocks', 5);

/**
 * Load block assets only when block is present on the page
 * and move block scripts to footer
 */

// Prevent unused block assets from loading
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Dequeue block assets that aren't used on the current page
 */
add_action('wp_enqueue_scripts', function () {
    global $post;

    // Only run on singular pages with content
    if (!is_singular() || empty($post->post_content)) {
        return;
    }

    // Get all registered blocks
    $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

    // Parse blocks in the content
    $blocks = parse_blocks($post->post_content);
    $blocks_in_use = array();

    // Recursively find all blocks in use (including nested blocks)
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
    $blocks_in_use = array_unique($blocks_in_use);

    // Dequeue unused block assets
    foreach ($registered_blocks as $block_name => $block_type) {
        // Skip if block is in use
        if (in_array($block_name, $blocks_in_use)) {
            continue;
        }

        // Dequeue editor and frontend styles
        if (!empty($block_type->style)) {
            wp_dequeue_style($block_type->style);
        }

        if (!empty($block_type->editor_style)) {
            wp_dequeue_style($block_type->editor_style);
        }

        // Dequeue editor and frontend scripts
        if (!empty($block_type->script)) {
            wp_dequeue_script($block_type->script);
        }

        if (!empty($block_type->editor_script)) {
            wp_dequeue_script($block_type->editor_script);
        }

        if (!empty($block_type->view_script)) {
            wp_dequeue_script($block_type->view_script);
        }
    }
}, 100);


/**
 * Move block scripts to footer (only for blocks actually in use)
 */
add_action('wp_enqueue_scripts', function () {
    global $wp_scripts;

    if (empty($wp_scripts->registered)) return;

    foreach ($wp_scripts->registered as $handle => $script) {
        if (
            !empty($script->src)
            && str_contains($script->src, '/blocks/')
        ) {
            // Mover al footer
            $wp_scripts->registered[$handle]->extra['group'] = 1;
            // Agregar defer
            $wp_scripts->registered[$handle]->extra['strategy'] = 'defer';
        }
    }
}, 999);

/**
 * Prevent block styles from loading in <head> for blocks not in use
 */
add_filter('render_block', function ($block_content, $block) {
    // You can add custom logic here if needed
    return $block_content;
}, 10, 2);

// Add ACF Options Page
if (function_exists('acf_add_options_page') && current_user_can('manage_options')) {
    acf_add_options_page(array(
        'page_title' => 'Site Options',
        'menu_title' => 'Site Options',
        'menu_slug' => 'site_options',
        'position' => 70,
        'capability' => 'manage_options',
        'redirect' => false
    ));
}


// Customize ACF JSON Save and Load Points
function my_acf_json_save_point($path)
{
    // update path
    $path = get_stylesheet_directory() . '/acf-json';

    // return
    return $path;
}

function my_acf_json_load_point($paths)
{
    // remove original path (optional)
    unset($paths[0]);

    // append path
    $paths[] = get_stylesheet_directory() . '/acf-json';

    // return
    return $paths;
}

add_action('init', function () {
    add_filter('acf/settings/save_json', 'my_acf_json_save_point');
    add_filter('acf/settings/load_json', 'my_acf_json_load_point');
});


/**
 * Synchronize ACF Fields after theme updates (CI/CD Integration)
 * 
 * Importa campos ACF del tema padre automáticamente respetando:
 * - Overrides del tema hijo (si existe)
 * - Cambios realizados en el repositorio
 * - El hash MD5 de los archivos JSON para detectar cambios
 */
add_action('admin_notices', function () {
    if (wp_doing_ajax() || wp_doing_cron()) return;
    if (!current_user_can('manage_options')) return;
    if (defined('ACF_DOING_SYNC')) return;
    if (!function_exists('acf_get_field_groups')) return;

    $memory_start = memory_get_usage();

    $parent_json_path = get_template_directory() . '/acf-json';
    $child_json_path  = get_stylesheet_directory() . '/acf-json';
    $is_child_theme   = $child_json_path !== $parent_json_path;

    $parent_json_files = array_filter(
        glob($parent_json_path . '/group_*.json') ?: [],
        fn($f) => is_readable($f)
    );
    if (empty($parent_json_files)) return;

    try {
        $content_hash = md5(implode('', array_map('md5_file', $parent_json_files)));
        if (get_option('acf_json_parent_sync_hash', '') === $content_hash) return;

        if (get_transient('acf_sync_lock')) {
            error_log('[ACF sync] skipped — mutex active, another sync is running');
            return;
        }
        set_transient('acf_sync_lock', true, 30);

        define('ACF_DOING_SYNC', true);
        update_option('acf_json_parent_sync_hash', $content_hash, false);

        error_log('[ACF sync] starting at ' . current_time('mysql'));
        error_log('[ACF sync] files: ' . count($parent_json_files) . ' | mode: ' . ($is_child_theme ? 'child theme' : 'parent only'));

        $groups   = acf_get_field_groups();
        $synced   = 0;
        $skipped  = 0;
        $warnings = 0;

        foreach ($groups as $group) {
            if (empty($group['local']) || $group['local'] !== 'json') continue;
            if (empty($group['local_modified']) || $group['local_modified'] <= $group['modified']) continue;

            if ($is_child_theme && file_exists($child_json_path . '/' . $group['key'] . '.json')) {
                error_log('[ACF sync] skipped (child override): ' . ($group['title'] ?? $group['key']));
                $skipped++;
                continue;
            }

            $local = acf_get_local_field_group($group['key']);
            if (empty($local)) {
                error_log('[ACF sync] WARNING — group not found: ' . $group['key']);
                $warnings++;
                continue;
            }

            $local['fields'] = acf_get_local_fields($group['key']);
            acf_import_field_group($local);
            error_log('[ACF sync] imported: ' . ($group['title'] ?? $group['key']));
            $synced++;
        }

        $memory_used = round((memory_get_usage() - $memory_start) / 1024 / 1024, 2);
        $memory_peak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        delete_transient('acf_sync_lock');

        $status = $warnings === 0 ? 'OK' : 'COMPLETED WITH WARNINGS';
        error_log('[ACF sync] ' . $status . ' — synced: ' . $synced . ', skipped: ' . $skipped . ', warnings: ' . $warnings);
        error_log('[ACF sync] memory: ' . $memory_used . 'MB used | ' . $memory_peak . 'MB peak');
    } catch (Throwable $e) {
        delete_transient('acf_sync_lock');
        error_log('[ACF sync] ERROR — ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine());
    }
});

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
 * Adds custom color palette from Customizer to all ACF color picker fields
 */

/**
 * Get theme colors from Customizer
 * @return array Array of colors with hex codes and names
 */
function get_theme_color_palette_for_acf()
{
    return array(
        array(
            'name'  => 'Primary Color',
            'color' => get_theme_mod('primary_color', '#15253f'),
        ),
        array(
            'name'  => 'Primary Dark',
            'color' => get_theme_mod('primary_color_dark', '#08182f'),
        ),
        array(
            'name'  => 'Primary Light',
            'color' => get_theme_mod('primary_color_light', '#2C3D5B'),
        ),
        array(
            'name'  => 'Secondary Color',
            'color' => get_theme_mod('secondary_color', '#F4F3EE'),
        ),
        array(
            'name'  => 'Secondary Dark',
            'color' => get_theme_mod('secondary_color_dark', '#E7E5DF'),
        ),
        array(
            'name'  => 'Secondary Light',
            'color' => get_theme_mod('secondary_color_light', '#FFFFFF'),
        ),
        array(
            'name'  => 'Tertiary Color',
            'color' => get_theme_mod('tertiary_color', '#BC9061'),
        ),
        array(
            'name'  => 'Tertiary Dark',
            'color' => get_theme_mod('tertiary_color_dark', '#9D7A55'),
        ),
        array(
            'name'  => 'Tertiary Light',
            'color' => get_theme_mod('tertiary_color_light', '#DCAB77'),
        ),
        array(
            'name'  => 'Text Color',
            'color' => get_theme_mod('text_color', '#15253f'),
        ),
    );
}


/**
 * Inject color palette into ACF color picker via JavaScript
 */
function acf_color_picker_palette_script()
{
    $colors = get_theme_color_palette_for_acf();
    $palette = array();

    foreach ($colors as $color) {
        $palette[] = $color['color'];
    }

    $palette_json = json_encode($palette);
?>
    <script type="text/javascript">
        (function($) {
            if (typeof acf !== 'undefined') {
                acf.addAction('ready', function() {
                    // Override default ACF color picker settings
                    acf.add_filter('color_picker_args', function(args, $field) {
                        args.palettes = <?php echo $palette_json; ?>;
                        return args;
                    });
                });
            }
        })(jQuery);
    </script>
    <style>
        /* Style for ACF color picker palette */
        .acf-color-picker .wp-picker-container .iris-palette {
            width: 100% !important;
            max-width: 100% !important;
        }
    </style>
<?php
}
add_action('acf/input/admin_head', 'acf_color_picker_palette_script');
add_action('acf/input/admin_footer', 'acf_color_picker_palette_script'); // Backup for late-loaded fields
