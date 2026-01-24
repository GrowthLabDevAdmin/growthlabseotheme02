<?php
// 1️⃣ Load editor CSS
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
add_filter('mce_css', 'my_acf_editor_styles');


// 2️⃣ TinyMCE configuration - For standard WordPress
function my_acf_wysiwyg_custom_settings($init)
{
    // Custom fonts
    $init['font_formats'] = 'Open Sans=Open Sans,sans-serif;Fraunces=Fraunces,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';

    // Font sizes
    $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';

    // DO NOT configure textcolor_map here for standard WordPress
    // We'll do it only in ACF with JavaScript

    return $init;
}
add_filter('tiny_mce_before_init', 'my_acf_wysiwyg_custom_settings', 1);


// 3️⃣ Apply to ACF WYSIWYG - Only fonts and sizes
function my_acf_tinymce_settings($init, $id)
{
    $init['font_formats'] = 'Open Sans=Open Sans,sans-serif;Fraunces=Fraunces,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';
    $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';

    // DO NOT configure textcolor_map here

    return $init;
}
add_filter('acf_wysiwyg_tinymce_settings', 'my_acf_tinymce_settings', 10, 2);


// 4️⃣ Custom toolbar
function my_acf_override_full_toolbar($toolbars)
{
    $toolbars['Full'][1] = array(
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
        'redo'
    );
    return $toolbars;
}
add_filter('acf/fields/wysiwyg/toolbars', 'my_acf_override_full_toolbar');


// 5️⃣ Inject colors dynamically with JavaScript
function my_acf_tinymce_colors_script()
{
    // Get colors dynamically from PHP
    $colors = array(
        get_theme_mod('primary_color', '#15253f') => 'Primary Color',
        get_theme_mod('primary_color_dark', '#08182f') => 'Primary Dark',
        get_theme_mod('primary_color_light', '#2C3D5B') => 'Primary Light',
        get_theme_mod('secondary_color', '#F4F3EE') => 'Secondary Color',
        get_theme_mod('secondary_color_dark', '#E7E5DF') => 'Secondary Dark',
        get_theme_mod('secondary_color_light', '#FFFFFF') => 'Secondary Light',
        get_theme_mod('tertiary_color', '#BC9061') => 'Tertiary Color',
        get_theme_mod('tertiary_color_dark', '#9D7A55') => 'Tertiary Dark',
        get_theme_mod('tertiary_color_light', '#DCAB77') => 'Tertiary Light',
        get_theme_mod('text_color', '#15253f') => 'Text Color',
    );

    // Build JavaScript array
    $color_array = array();
    foreach ($colors as $hex => $name) {
        $color_array[] = str_replace('#', '', $hex);
        $color_array[] = $name;
    }

    // Convert to valid JSON
    $colors_json = json_encode($color_array);
?>
    <script type="text/javascript">
        (function($) {
            // Custom colors from PHP
            var customColors = <?php echo $colors_json; ?>;

            //console.log('🎨 Colors loaded from PHP:', customColors);

            // Hook BEFORE ACF initializes TinyMCE
            acf.addFilter('wysiwyg_tinymce_settings', function(mceInit, id, field) {
                //console.log('🔧 Modifying configuration for:', id);

                // Inject custom colors
                mceInit.textcolor_map = customColors;
                mceInit.textcolor_cols = 5;

                //console.log('✅ Colors injected:', mceInit.textcolor_map);

                return mceInit;
            });

        })(jQuery);
    </script>
<?php
}
add_action('acf/input/admin_head', 'my_acf_tinymce_colors_script');

// 6️⃣ Inject colors directamente en la configuración de TinyMCE
function my_wp_editor_colors_direct($init)
{
    // Get colors dynamically from PHP
    $colors = array(
        get_theme_mod('primary_color', '#15253f') => 'Primary Color',
        get_theme_mod('primary_color_dark', '#08182f') => 'Primary Dark',
        get_theme_mod('primary_color_light', '#2C3D5B') => 'Primary Light',
        get_theme_mod('secondary_color', '#F4F3EE') => 'Secondary Color',
        get_theme_mod('secondary_color_dark', '#E7E5DF') => 'Secondary Dark',
        get_theme_mod('secondary_color_light', '#FFFFFF') => 'Secondary Light',
        get_theme_mod('tertiary_color', '#BC9061') => 'Tertiary Color',
        get_theme_mod('tertiary_color_dark', '#9D7A55') => 'Tertiary Dark',
        get_theme_mod('tertiary_color_light', '#DCAB77') => 'Tertiary Light',
        get_theme_mod('text_color', '#15253f') => 'Text Color',
    );

    // Build color array alternando hex y nombre
    $color_array = array();
    foreach ($colors as $hex => $name) {
        $color_array[] = str_replace('#', '', $hex);  // hex sin #
        $color_array[] = $name;                        // nombre del color
    }

    // Inyectar en el init
    $init['textcolor_map'] = $color_array;
    $init['textcolor_cols'] = 5;

    return $init;
}
add_filter('tiny_mce_before_init', 'my_wp_editor_colors_direct', 10);

// 7️⃣ Configure WordPress editor with same fonts and sizes
function my_wp_editor_formats()
{
    add_editor_style(get_template_directory_uri() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css?ver=' . time());

    // Add support for custom formats
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Primary Color', 'growthlabtheme02'),
            'slug'  => 'primary',
            'color' => get_theme_mod('primary_color', '#15253f'),
        ),
        array(
            'name'  => __('Primary Dark', 'growthlabtheme02'),
            'slug'  => 'primary-dark',
            'color' => get_theme_mod('primary_color_dark', '#08182f'),
        ),
        array(
            'name'  => __('Primary Light', 'growthlabtheme02'),
            'slug'  => 'primary-light',
            'color' => get_theme_mod('primary_color_light', '#2C3D5B'),
        ),
        array(
            'name'  => __('Secondary Color', 'growthlabtheme02'),
            'slug'  => 'secondary',
            'color' => get_theme_mod('secondary_color', '#F4F3EE'),
        ),
        array(
            'name'  => __('Secondary Dark', 'growthlabtheme02'),
            'slug'  => 'secondary-dark',
            'color' => get_theme_mod('secondary_color_dark', '#E7E5DF'),
        ),
        array(
            'name'  => __('Secondary Light', 'growthlabtheme02'),
            'slug'  => 'secondary-light',
            'color' => get_theme_mod('secondary_color_light', '#FFFFFF'),
        ),
        array(
            'name'  => __('Tertiary Color', 'growthlabtheme02'),
            'slug'  => 'tertiary',
            'color' => get_theme_mod('tertiary_color', '#BC9061'),
        ),
        array(
            'name'  => __('Tertiary Dark', 'growthlabtheme02'),
            'slug'  => 'tertiary-dark',
            'color' => get_theme_mod('tertiary_color_dark', '#9D7A55'),
        ),
        array(
            'name'  => __('Tertiary Light', 'growthlabtheme02'),
            'slug'  => 'tertiary-light',
            'color' => get_theme_mod('tertiary_color_light', '#DCAB77'),
        ),
        array(
            'name'  => __('Text Color', 'growthlabtheme02'),
            'slug'  => 'text',
            'color' => get_theme_mod('text_color', '#15253f'),
        ),
    ));
}
add_action('after_setup_theme', 'my_wp_editor_formats');

// 8️⃣ Set default font and size for WordPress editor
function my_wp_editor_default_settings($init)
{
    // Font formats (same as ACF)
    $init['font_formats'] = 'Open Sans=Open Sans,sans-serif;Fraunces=Fraunces,serif;Arial=Arial,Helvetica,sans-serif;Times New Roman=Times New Roman,Times,serif';

    // Font sizes (same as ACF)
    $init['fontsize_formats'] = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px';

    // Toolbar configuration (same buttons as ACF)
    $init['toolbar1'] = 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,removeformat,undo,redo';
    $init['toolbar2'] = '';

    $init['textcolor_map'] = isset($init['textcolor_map']) ? $init['textcolor_map'] : array();
    $init['textcolor_cols'] = 5;
    $init['plugins'] = (isset($init['plugins']) ? $init['plugins'] : '') . ' textcolor';

    return $init;
}
add_filter('tiny_mce_before_init', 'my_wp_editor_default_settings', 20);

// Inyecta colores directamente en cada instancia de TinyMCE cuando se crea (más fiable)
function my_wp_editor_colors_apply_on_add()
{
    $colors = array(
        get_theme_mod('primary_color', '#15253f') => 'Primary Color',
        get_theme_mod('primary_color_dark', '#08182f') => 'Primary Dark',
        get_theme_mod('primary_color_light', '#2C3D5B') => 'Primary Light',
        get_theme_mod('secondary_color', '#F4F3EE') => 'Secondary Color',
        get_theme_mod('secondary_color_dark', '#E7E5DF') => 'Secondary Dark',
        get_theme_mod('secondary_color_light', '#FFFFFF') => 'Secondary Light',
        get_theme_mod('tertiary_color', '#BC9061') => 'Tertiary Color',
        get_theme_mod('tertiary_color_dark', '#9D7A55') => 'Tertiary Dark',
        get_theme_mod('tertiary_color_light', '#DCAB77') => 'Tertiary Light',
        get_theme_mod('text_color', '#15253f') => 'Text Color',
    );

    // Format TinyMCE expects: ['RRGGBB','Name',...]
    $map = array();
    foreach ($colors as $hex => $name) {
        $map[] = str_replace('#', '', $hex);
        $map[] = $name;
    }

    $map_json = json_encode($map);
    ?>
    <script type="text/javascript">
    (function($){
        var customColors = <?php echo $map_json; ?>;
        var customCols = 5;

        function applyToEditor(editor) {
            if (!editor || !editor.settings) return;
            try {
                editor.settings.textcolor_map = customColors;
                editor.settings.textcolor_cols = customCols;

                // Forzar actualización de estado e intentar refrescar UI
                try {
                    editor.nodeChanged();
                } catch (err) { /* no crítico */ }

                // Si el botón forecolor existe, intentar forzar reconstrucción del menú
                try {
                    var btn = editor.ui.registry.getAll && editor.ui && editor.ui.registry && editor.ui.registry.getAll && editor.ui.registry.getAll().buttons && editor.ui.registry.getAll().buttons.forecolor;
                    if (btn && typeof btn.onAction === 'function') {
                        // No podemos reconstruir internamente el plugin desde aquí fácilmente,
                        // pero al tener editor.settings actualizado la próxima apertura del picker debería usarlo.
                    }
                } catch (e) {}
            } catch (e) {
                console.error('[growthlab] applyToEditor error', e);
            }
        }

        // Si tinymce ya está cargado
        if (window.tinymce && window.tinymce.EditorManager) {
            // Aplicar a los editores ya creados
            for (var id in tinymce.editors) {
                if (tinymce.editors.hasOwnProperty(id)) {
                    applyToEditor(tinymce.editors[id]);
                }
            }

            // Aplicar a nuevos editores cuando se añadan
            if (tinymce.EditorManager.on) {
                tinymce.EditorManager.on('AddEditor', function(e){
                    applyToEditor(e.editor);
                });
            } else if (tinymce.on) {
                tinymce.on('AddEditor', function(e){
                    applyToEditor(e.editor);
                });
            }
        } else {
            // Si tinymce no está aún, esperar y aplicar cuando esté listo
            var wait = setInterval(function(){
                if (window.tinymce && tinymce.EditorManager) {
                    clearInterval(wait);
                    for (var id2 in tinymce.editors) {
                        if (tinymce.editors.hasOwnProperty(id2)) {
                            applyToEditor(tinymce.editors[id2]);
                        }
                    }
                    if (tinymce.EditorManager.on) {
                        tinymce.EditorManager.on('AddEditor', function(e){
                            applyToEditor(e.editor);
                        });
                    } else if (tinymce.on) {
                        tinymce.on('AddEditor', function(e){
                            applyToEditor(e.editor);
                        });
                    }
                }
            }, 250);
        }
    })(jQuery);
    </script>
    <?php
}
add_action('admin_print_footer_scripts', 'my_wp_editor_colors_apply_on_add', 999);
