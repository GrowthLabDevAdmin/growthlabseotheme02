<?php

/**
 * Helper interno — construye el array de colores del tema sanitizado
 * para uso en TinyMCE. No registrar como función pública.
 */
function _theme_get_tinymce_color_map(): array
{
    $colors = [
        sanitize_hex_color(get_theme_mod('primary_color',         '#15253f')) ?: '#15253f' => 'Primary Color',
        sanitize_hex_color(get_theme_mod('primary_color_dark',    '#08182f')) ?: '#08182f' => 'Primary Dark',
        sanitize_hex_color(get_theme_mod('primary_color_light',   '#2C3D5B')) ?: '#2C3D5B' => 'Primary Light',
        sanitize_hex_color(get_theme_mod('secondary_color',       '#F4F3EE')) ?: '#F4F3EE' => 'Secondary Color',
        sanitize_hex_color(get_theme_mod('secondary_color_dark',  '#E7E5DF')) ?: '#E7E5DF' => 'Secondary Dark',
        sanitize_hex_color(get_theme_mod('secondary_color_light', '#FFFFFF')) ?: '#FFFFFF' => 'Secondary Light',
        sanitize_hex_color(get_theme_mod('tertiary_color',        '#BC9061')) ?: '#BC9061' => 'Tertiary Color',
        sanitize_hex_color(get_theme_mod('tertiary_color_dark',   '#9D7A55')) ?: '#9D7A55' => 'Tertiary Dark',
        sanitize_hex_color(get_theme_mod('tertiary_color_light',  '#DCAB77')) ?: '#DCAB77' => 'Tertiary Light',
        sanitize_hex_color(get_theme_mod('text_color',            '#15253f')) ?: '#15253f' => 'Text Color',
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
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_acf_wysiwyg_custom_settings', 1);

// 3️⃣ Apply to ACF WYSIWYG — fonts and sizes only
if (!function_exists('my_acf_tinymce_settings')) {
    function my_acf_tinymce_settings($init, $id)
    {
        $init['font_formats']     = 'Mona Sans=Mona Sans,sans-serif;Roboto Serif=Roboto Serif,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';
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

        add_theme_support('editor-color-palette', [
            ['name' => __('Primary Color',         'growthlabseotheme02'), 'slug' => 'primary',        'color' => sanitize_hex_color(get_theme_mod('primary_color',         '#15253f')) ?: '#15253f'],
            ['name' => __('Primary Dark',          'growthlabseotheme02'), 'slug' => 'primary-dark',   'color' => sanitize_hex_color(get_theme_mod('primary_color_dark',    '#08182f')) ?: '#08182f'],
            ['name' => __('Primary Light',         'growthlabseotheme02'), 'slug' => 'primary-light',  'color' => sanitize_hex_color(get_theme_mod('primary_color_light',   '#2C3D5B')) ?: '#2C3D5B'],
            ['name' => __('Secondary Color',       'growthlabseotheme02'), 'slug' => 'secondary',      'color' => sanitize_hex_color(get_theme_mod('secondary_color',       '#F4F3EE')) ?: '#F4F3EE'],
            ['name' => __('Secondary Dark',        'growthlabseotheme02'), 'slug' => 'secondary-dark', 'color' => sanitize_hex_color(get_theme_mod('secondary_color_dark',  '#E7E5DF')) ?: '#E7E5DF'],
            ['name' => __('Secondary Light',       'growthlabseotheme02'), 'slug' => 'secondary-light', 'color' => sanitize_hex_color(get_theme_mod('secondary_color_light', '#FFFFFF')) ?: '#FFFFFF'],
            ['name' => __('Tertiary Color',        'growthlabseotheme02'), 'slug' => 'tertiary',       'color' => sanitize_hex_color(get_theme_mod('tertiary_color',        '#BC9061')) ?: '#BC9061'],
            ['name' => __('Tertiary Dark',         'growthlabseotheme02'), 'slug' => 'tertiary-dark',  'color' => sanitize_hex_color(get_theme_mod('tertiary_color_dark',   '#9D7A55')) ?: '#9D7A55'],
            ['name' => __('Tertiary Light',        'growthlabseotheme02'), 'slug' => 'tertiary-light', 'color' => sanitize_hex_color(get_theme_mod('tertiary_color_light',  '#DCAB77')) ?: '#DCAB77'],
            ['name' => __('Text Color',            'growthlabseotheme02'), 'slug' => 'text',           'color' => sanitize_hex_color(get_theme_mod('text_color',            '#15253f')) ?: '#15253f'],
        ]);
    }
}
add_action('after_setup_theme', 'my_wp_editor_formats');

// 8️⃣ Default font and size settings
if (!function_exists('my_wp_editor_default_settings')) {
    function my_wp_editor_default_settings($init)
    {
        $init['font_formats']     = 'Mona Sans=Mona Sans,sans-serif;Roboto Serif=Roboto Serif,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
        $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';
        $init['toolbar1']         = 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,removeformat,undo,redo';
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