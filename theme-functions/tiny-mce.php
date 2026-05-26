<?php

/**
 * Helper interno — construye el array de colores del tema sanitizado
 * para uso en TinyMCE. No registrar como función pública.
 */
function _theme_get_tinymce_color_map(): array
{
    // Load central color defaults from color-scheme.php if available
    if (file_exists(__DIR__ . '/color-scheme.php')) {
        require_once __DIR__ . '/color-scheme.php';
    }

    global $default_colors;

    $primary_default   = $default_colors['primary']['default']   ?? '#15253f';
    $primary_dark      = $default_colors['primary']['dark']      ?? '#08182f';
    $primary_light     = $default_colors['primary']['light']     ?? '#2C3D5B';

    $secondary_default = $default_colors['secondary']['default'] ?? '#F4F3EE';
    $secondary_dark    = $default_colors['secondary']['dark']    ?? '#E7E5DF';
    $secondary_light   = $default_colors['secondary']['light']   ?? '#FFFFFF';

    $tertiary_default  = $default_colors['tertiary']['default']  ?? '#BC9061';
    $tertiary_dark     = $default_colors['tertiary']['dark']     ?? '#9D7A55';
    $tertiary_light    = $default_colors['tertiary']['light']    ?? '#DCAB77';

    $text_default      = $default_colors['text']                 ?? '#15253f';

    $colors = [
        sanitize_hex_color(get_theme_mod('primary_color',         $primary_default))   ?: $primary_default   => 'Primary Color',
        sanitize_hex_color(get_theme_mod('primary_color_dark',    $primary_dark))      ?: $primary_dark      => 'Primary Dark',
        sanitize_hex_color(get_theme_mod('primary_color_light',   $primary_light))     ?: $primary_light     => 'Primary Light',
        sanitize_hex_color(get_theme_mod('secondary_color',       $secondary_default)) ?: $secondary_default => 'Secondary Color',
        sanitize_hex_color(get_theme_mod('secondary_color_dark',  $secondary_dark))    ?: $secondary_dark    => 'Secondary Dark',
        sanitize_hex_color(get_theme_mod('secondary_color_light', $secondary_light))   ?: $secondary_light   => 'Secondary Light',
        sanitize_hex_color(get_theme_mod('tertiary_color',        $tertiary_default))  ?: $tertiary_default  => 'Tertiary Color',
        sanitize_hex_color(get_theme_mod('tertiary_color_dark',   $tertiary_dark))     ?: $tertiary_dark     => 'Tertiary Dark',
        sanitize_hex_color(get_theme_mod('tertiary_color_light',  $tertiary_light))    ?: $tertiary_light    => 'Tertiary Light',
        sanitize_hex_color(get_theme_mod('text_color',            $text_default))      ?: $text_default      => 'Text Color',
    ];

    $map = [];
    foreach ($colors as $hex => $name) {
        $map[] = str_replace('#', '', $hex);
        $map[] = $name;
    }

    return $map;
}

// 1️⃣ Load editor CSS
if (!function_exists('my_acf_editor_styles')) {
    function my_acf_editor_styles($mce_css)
    {
        $editor_style = get_template_directory_uri() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css';
        $editor_style .= '?ver=' . time();

        if (!empty($mce_css)) {
            $mce_css .= ',' . $editor_style;
        } else {
            $mce_css = $editor_style;
        }
        return $mce_css;
    }
}
add_filter('mce_css', 'my_acf_editor_styles');

// 2️⃣ TinyMCE configuration — standard WordPress
if (!function_exists('my_acf_wysiwyg_custom_settings')) {
    function my_acf_wysiwyg_custom_settings($init)
    {
        $init['font_formats']     = 'Mona Sans=Mona Sans,sans-serif;Roboto Serif=Roboto Serif,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px 56px 64px 72px 80px 88px 96px 104px 124px 148px 156px 168px';
        $init['lineheight_formats'] = '.9 .95 1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 2 2.5 3';
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_acf_wysiwyg_custom_settings', 1);

// 3️⃣ Apply to ACF WYSIWYG — fonts and sizes only
if (!function_exists('my_acf_tinymce_settings')) {
    function my_acf_tinymce_settings($init, $id)
    {
        $init['font_formats']     = 'Mona Sans=Mona Sans,sans-serif;Roboto Serif=Roboto Serif,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px 56px 64px 72px 80px 88px 96px 104px 124px 148px 156px 168px';
        $init['lineheight_formats'] = '.9 .95 1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 2 2.5 3';
        return $init;
    }
}
add_filter('acf_wysiwyg_tinymce_settings', 'my_acf_tinymce_settings', 10, 2);

// 4️⃣ Custom toolbar
if (!function_exists('my_acf_override_full_toolbar')) {
    function my_acf_override_full_toolbar($toolbars)
    {
        $toolbars['Full'][1] = [
            'formatselect',
            'fontselect',
            'fontsizeselect',
            'lineheightselect',
            'bold',
            'italic',
            'underline',
            'forecolor',
            'backcolor',
            'bullist',
            'numlist',
            'alignleft',
            'aligncenter',
            'alignright',
            'link',
            'unlink',
            'removeformat',
            'undo',
            'redo',
        ];
        return $toolbars;
    }
}
add_filter('acf/fields/wysiwyg/toolbars', 'my_acf_override_full_toolbar');

// 5️⃣ Inject colors dynamically via JavaScript — ACF
if (!function_exists('my_acf_tinymce_colors_script')) {
    function my_acf_tinymce_colors_script()
    {
        $colors_json = wp_json_encode(_theme_get_tinymce_color_map());
?>
        <script type="text/javascript">
            (function($) {
                var customColors = <?php echo $colors_json; ?>;

                acf.addFilter('wysiwyg_tinymce_settings', function(mceInit, id, field) {
                    mceInit.textcolor_map = customColors;
                    mceInit.textcolor_cols = 5;
                    return mceInit;
                });
            })(jQuery);
        </script>
    <?php
    }
}
add_action('acf/input/admin_head', 'my_acf_tinymce_colors_script');

// 6️⃣ Inject colors directly into TinyMCE init — standard WordPress
if (!function_exists('my_wp_editor_colors_direct')) {
    function my_wp_editor_colors_direct($init)
    {
        $init['textcolor_map']  = _theme_get_tinymce_color_map();
        $init['textcolor_cols'] = 5;
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_wp_editor_colors_direct', 10);

// 7️⃣ Editor styles and color palette
if (!function_exists('my_wp_editor_formats')) {
    function my_wp_editor_formats()
    {
        add_editor_style(get_template_directory_uri() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css?ver=' . time());

        // Ensure we have central defaults available
        if (file_exists(__DIR__ . '/color-scheme.php')) {
            require_once __DIR__ . '/color-scheme.php';
        }
        global $default_colors;

        $primary_default   = $default_colors['primary']['default']   ?? '#15253f';
        $primary_dark      = $default_colors['primary']['dark']      ?? '#08182f';
        $primary_light     = $default_colors['primary']['light']     ?? '#2C3D5B';

        $secondary_default = $default_colors['secondary']['default'] ?? '#F4F3EE';
        $secondary_dark    = $default_colors['secondary']['dark']    ?? '#E7E5DF';
        $secondary_light   = $default_colors['secondary']['light']   ?? '#FFFFFF';

        $tertiary_default  = $default_colors['tertiary']['default']  ?? '#BC9061';
        $tertiary_dark     = $default_colors['tertiary']['dark']     ?? '#9D7A55';
        $tertiary_light    = $default_colors['tertiary']['light']    ?? '#DCAB77';

        $text_default      = $default_colors['text']                 ?? '#15253f';

        add_theme_support('editor-color-palette', [
            ['name' => __('Primary Color',         'growthlabseotheme02'), 'slug' => 'primary',         'color' => sanitize_hex_color(get_theme_mod('primary_color',         $primary_default))   ?: $primary_default],
            ['name' => __('Primary Dark',          'growthlabseotheme02'), 'slug' => 'primary-dark',    'color' => sanitize_hex_color(get_theme_mod('primary_color_dark',    $primary_dark))      ?: $primary_dark],
            ['name' => __('Primary Light',         'growthlabseotheme02'), 'slug' => 'primary-light',   'color' => sanitize_hex_color(get_theme_mod('primary_color_light',   $primary_light))     ?: $primary_light],
            ['name' => __('Secondary Color',       'growthlabseotheme02'), 'slug' => 'secondary',       'color' => sanitize_hex_color(get_theme_mod('secondary_color',       $secondary_default)) ?: $secondary_default],
            ['name' => __('Secondary Dark',        'growthlabseotheme02'), 'slug' => 'secondary-dark',  'color' => sanitize_hex_color(get_theme_mod('secondary_color_dark',  $secondary_dark))    ?: $secondary_dark],
            ['name' => __('Secondary Light',       'growthlabseotheme02'), 'slug' => 'secondary-light', 'color' => sanitize_hex_color(get_theme_mod('secondary_color_light', $secondary_light))   ?: $secondary_light],
            ['name' => __('Tertiary Color',        'growthlabseotheme02'), 'slug' => 'tertiary',        'color' => sanitize_hex_color(get_theme_mod('tertiary_color',        $tertiary_default))  ?: $tertiary_default],
            ['name' => __('Tertiary Dark',         'growthlabseotheme02'), 'slug' => 'tertiary-dark',   'color' => sanitize_hex_color(get_theme_mod('tertiary_color_dark',   $tertiary_dark))     ?: $tertiary_dark],
            ['name' => __('Tertiary Light',        'growthlabseotheme02'), 'slug' => 'tertiary-light',  'color' => sanitize_hex_color(get_theme_mod('tertiary_color_light',  $tertiary_light))    ?: $tertiary_light],
            ['name' => __('Text Color',            'growthlabseotheme02'), 'slug' => 'text',            'color' => sanitize_hex_color(get_theme_mod('text_color',            $text_default))      ?: $text_default],
        ]);
    }
}
add_action('after_setup_theme', 'my_wp_editor_formats');

// 8️⃣ Default font and size settings
if (!function_exists('my_wp_editor_default_settings')) {
    function my_wp_editor_default_settings($init)
    {
        $init['font_formats']     = 'Mona Sans=Mona Sans,sans-serif;Roboto Serif=Roboto Serif,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px 56px 64px 72px 80px 88px 96px 104px 124px 148px 156px 168px';
        $init['lineheight_formats'] = '.9 .95 1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 2 2.5 3';
        $init['toolbar1']         = 'formatselect,fontselect,fontsizeselect,lineheightselect,bold,italic,underline,forecolor,backcolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,removeformat,undo,redo';
        $init['toolbar2']         = '';
        $init['textcolor_map']    = $init['textcolor_map']  ?? [];
        $init['textcolor_cols']   = 5;
        $init['plugins']          = ($init['plugins'] ?? '') . ' textcolor';
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_wp_editor_default_settings', 20);

// 9️⃣ Apply colors to each TinyMCE instance on add
if (!function_exists('my_wp_editor_colors_apply_on_add')) {
    function my_wp_editor_colors_apply_on_add()
    {
        $map_json = wp_json_encode(_theme_get_tinymce_color_map());
    ?>
        <script type="text/javascript">
            (function($) {
                var customColors = <?php echo $map_json; ?>;
                var customCols = 5;

                function applyToEditor(editor) {
                    if (!editor || !editor.settings) return;
                    try {
                        editor.settings.textcolor_map = customColors;
                        editor.settings.textcolor_cols = customCols;
                        try {
                            editor.nodeChanged();
                        } catch (e) {}
                    } catch (e) {
                        console.error('[growthlab] applyToEditor error', e);
                    }
                }

                function bindEditorManager() {
                    for (var id in tinymce.editors) {
                        if (tinymce.editors.hasOwnProperty(id)) {
                            applyToEditor(tinymce.editors[id]);
                        }
                    }
                    var mgr = tinymce.EditorManager || tinymce;
                    if (mgr && mgr.on) {
                        mgr.on('AddEditor', function(e) {
                            applyToEditor(e.editor);
                        });
                    }
                }

                if (window.tinymce && tinymce.EditorManager) {
                    bindEditorManager();
                } else {
                    var wait = setInterval(function() {
                        if (window.tinymce && tinymce.EditorManager) {
                            clearInterval(wait);
                            bindEditorManager();
                        }
                    }, 250);
                }
            })(jQuery);
        </script>
<?php
    }
}
add_action('admin_print_footer_scripts', 'my_wp_editor_colors_apply_on_add', 999);
