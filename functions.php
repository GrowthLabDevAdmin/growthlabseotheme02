<?php

/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage growthlabtheme02
 * 
 */

// Definir breakpoints personalizados para este tema
$GLOBALS['breakpoints'] = [
    'mobile' => '0px',
    'tablet' => '768px',   // diferente al default
    'ldpi'   => '1024px',
    'mdpi'   => '1280px',  // diferente al default
    'hdpi'   => '1600px',  // diferente al default
];


// Include Theme Functions
$includes = [
    'theme-functions/theme-optimization.php',
    'theme-functions/color-scheme.php',
    'theme-functions/acf-functions.php',
    'theme-functions/helpers.php',
    'theme-functions/svg-support.php',
    'theme-functions/picture-optimization.php',
    'theme-functions/tiny-mce.php',
];

foreach ($includes as $file) {
    if (file_exists(get_template_directory() . '/' . $file)) {
        require_once get_template_directory() . '/' . $file;
    }
}


if (!function_exists('growthlabtheme02_setup')) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     *
     *
     * @return void
     */

    function growthlabtheme02_setup()
    {
        /*
		* Let WordPress manage the document title.
		* This theme does not use a hard-coded <title> tag in the document head,
		* WordPress will provide it for us.
		*/
        add_theme_support('title-tag');

        /*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
        add_theme_support('post-thumbnails');

        // Custom Logo Support
        $defaults = array(
            'height'               => 200,
            'width'                => 360,
            'flex-height'          => true,
            'flex-width'           => true,
            'unlink-homepage-logo' => true,
        );

        add_theme_support('custom-logo', $defaults);

        //Add custom sized images
        add_image_size('cover-desktop', 1920, 1080, false);
        add_image_size('cover-tablet', 1280, 720, false);
        add_image_size('cover-mobile', 800, 533, false);
        add_image_size('featured-small', 400, 267, false);

        // Disable Cropped Pictures
        add_filter('intermediate_image_sizes_advanced', function ($sizes) {

            foreach ($sizes as $key => &$size) {
                update_option("{$key}_crop", 0);
            }
            return $sizes;
        }, 999);

        // Add custom image sizes to the media selector
        add_filter('image_size_names_choose', function ($sizes) {
            return array_merge([
                'thumbnail'    => __('Miniatura'),
                'medium'       => __('Mediano'),
                'medium_large' => __('Mediano grande'),
                'large'        => __('Grande'),
                'full'         => __('Tamaño completo'),
                // Tus tamaños personalizados:
                'cover-desktop'  => __('Cover Desktop (1920×1080)'),
                'cover-tablet'   => __('Cover Tablet (1280×720)'),
                'cover-mobile'   => __('Cover Mobile (800×533)'),
                'featured-small' => __('Featured Small (400×267)'),
            ], $sizes);
        });

        // Tipography and Color Support
        add_theme_support('appearance-tools');

        // Font Sizes support
        add_theme_support('editor-font-sizes', array(
            array(
                'name' => esc_attr__(
                    'Small',
                    'growthlabtheme02'
                ),
                'size' => 12,
                'slug' => 'small'
            ),
            array(
                'name' => esc_attr__(
                    'Regular',
                    'growthlabtheme02'
                ),
                'size' => 16,
                'slug' => 'regular'
            ),
            array(
                'name' => esc_attr__(
                    'Medium',
                    'growthlabtheme02'
                ),
                'size' => 18,
                'slug' => 'medium'
            ),
            array(
                'name' => esc_attr__(
                    'Large',
                    'growthlabtheme02'
                ),
                'size' => 22,
                'slug' => 'large'
            ),
            array(
                'name' => esc_attr__(
                    'Extra Large',
                    'growthlabtheme02'
                ),
                'size' => 28,
                'slug' => 'xl'
            ),
            array(
                'name' => esc_attr__(
                    'Huge',
                    'growthlabtheme02'
                ),
                'size' => 32,
                'slug' => 'xl'
            )
        ));

        // Color Palette support
        add_theme_support(
            'editor-color-palette',
            array(
                array(
                    'name'  => __(
                        'Primary Color',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'primary-color',
                    'color' => get_theme_mod('primary_color', '#15253f'),
                ),
                array(
                    'name'  => __(
                        'Primary Color Dark',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'primary-color-dark',
                    'color' => get_theme_mod('primary_color_dark', '#08182f'),
                ),
                array(
                    'name'  => __(
                        'Primary Color Light',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'primary-color-light',
                    'color' => get_theme_mod('primary_color_light', '#2C3D5B'),
                ),
                array(
                    'name'  => __(
                        'Secondary Color',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'secondary-color',
                    'color' => get_theme_mod('secondary_color', '#F4F3EE'),
                ),
                array(
                    'name'  => __(
                        'Secondary Color Dark',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'secondary-color-dark',
                    'color' => get_theme_mod('secondary_color_dark', '#E7E5DF'),
                ),
                array(
                    'name'  => __(
                        'Secondary Color Light',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'secondary-color-light',
                    'color' => get_theme_mod('secondary_color_light', '#FFFFFF'),
                ),
                array(
                    'name'  => __(
                        'Tertiary Color',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'tertiary-color',
                    'color' => get_theme_mod('tertiary_color', '#BC9061'),
                ),
                array(
                    'name'  => __(
                        'Tertiary Color Dark',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'tertiary-color-dark',
                    'color' => get_theme_mod('tertiary_color_dark', '#9D7A55'),
                ),
                array(
                    'name'  => __(
                        'Tertiary Color Light',
                        'growthlabtheme02'
                    ),
                    'slug'  => 'tertiary-color-light',
                    'color' => get_theme_mod('tertiary_color_light', '#DCAB77'),
                ),
                array(
                    'name'  => __(
                        'Text Color',
                        'growthlabtheme02 '
                    ),
                    'slug'  => 'text-color',
                    'color' => get_theme_mod('text_color', '#15253f'),
                ),
            )
        );

        // Register Navigation Menus
        register_nav_menus(
            array(
                'main' => esc_html__('Main Menu', 'growthlabtheme02')
            )
        );

        if (function_exists('get_languages_map')) {
            foreach (get_languages_map() as $slug => $language) {
                register_nav_menus(
                    array(
                        'main_' . $slug => esc_html__("Main Menu $language", 'growthlabtheme02')
                    )
                );
            }
        }
    }
}
add_action('after_setup_theme', 'growthlabtheme02_setup');

/**
 * Remove link from custom logo
 */
function growthlabtheme02_remove_custom_logo_link($html)
{
    // Extract just the <img> tag from the logo HTML
    preg_match('/<img[^>]+>/', $html, $matches);

    if (!empty($matches[0])) {
        return $matches[0];
    }

    return $html;
}
add_filter('get_custom_logo', 'growthlabtheme02_remove_custom_logo_link');

/*Custom Excerpt Size*/
function growthlabtheme02_custom_excerpt_length($length)
{
    if (get_post_type() === 'team') {
        return 50;
    }
    return 15;
}
add_filter('excerpt_length', 'growthlabtheme02_custom_excerpt_length', 999);

/*custom excerpt more*/
function wpdocs_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'wpdocs_excerpt_more');

/**
 * Add scripts and styles.
 *
 *
 * @return void
 */

// Inline critical CSS
// Comment this function while working on Dev Environment
function inline_main_critical_css()
{
    global $block_critical_css;

    // Dynamic Color Scheme
    $color_scheme = theme_get_customizer_css();

    $critical_css = file_get_contents(get_template_directory() . "/styles/main-min.css");
    $critical_css .= file_get_contents(get_stylesheet_uri());
    $critical_css = preg_replace('/\{theme-path\}/', get_template_directory_uri(), $critical_css);
    $critical_css =  $color_scheme . $critical_css;

    // Add block critical CSS if any
    if (!empty($block_critical_css)) {
        $critical_css .= "\n/* Block Critical CSS */\n" . $block_critical_css;
    }

    echo '<style id="main-css">' . $critical_css . '</style>';
}
add_action('wp_head', 'inline_main_critical_css', 5);

// Register third-party scripts early to ensure they are available as dependencies
add_action('init', function () {
    wp_register_script(
        'splide-js',
        get_template_directory_uri() . '/js/vendor/splide/splide-min.js',
        [],
        '4.1.4',
        ['strategy' => 'defer', 'in_footer' => true]
    );
}, 5);

function growthlabtheme02_scripts()
{
    if (is_admin()) return;

    // Global stylesheet.

    // Uncomment this while working on Dev Environment
    /* wp_enqueue_style(
        'growthlabtheme02-main-stylesheet',
        get_template_directory_uri() . "/styles/main-min.css",
        array(),
        filemtime(get_template_directory() . '/styles/main-min.css') 
    ); */

    // Move jQuery to footer (safe for GF)
    wp_scripts()->add_data('jquery', 'group', 1);
    wp_scripts()->add_data('jquery-core', 'group', 1);

    // Remove jQuery Migrate (not needed for modern GF)
    wp_deregister_script('jquery-migrate');

    // Gravity Forms - remove maps
    wp_dequeue_script('gform_gravityforms_maps');

    // Third party JS scripts.
    wp_enqueue_script('splide-js');

    // Main JS scripts.
    wp_enqueue_script(
        'growthlabtheme02-main-scripts',
        get_template_directory_uri() . '/js/main-min.js',
        array('splide-js'),
        filemtime(get_template_directory() . '/js/main-min.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    // Load specific template stylesheet
    if (is_page() || is_single()) {
        if (!is_page_template('page-templates/template-full-width.php')) {
            wp_enqueue_style('growthlabtheme02-template-default', get_template_directory_uri() . '/styles/page-templates/template-default-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-default-min.css'));
        }
        if (is_singular('team')) {
            wp_enqueue_style('growthlabtheme02-template-team-member', get_template_directory_uri() . '/styles/page-templates/template-team-member-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-team-member-min.css'));
        }
    }
    if (is_home() || is_archive()) {
        wp_enqueue_style('growthlabtheme02-template-default', get_template_directory_uri() . '/styles/page-templates/template-default-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-default-min.css'));
        wp_enqueue_style('growthlabtheme02-blog', get_template_directory_uri() . '/styles/page-templates/template-blog-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-blog-min.css'));
        wp_enqueue_script(
            'growthlabtheme02-posts-filters',
            get_template_directory_uri() . '/js/posts-filters-min.js',
            filemtime(get_template_directory() . '/js/posts-filters-min.js'),
            ['strategy' => 'defer', 'in_footer' => true]
        );
        wp_localize_script(
            'growthlabtheme02-posts-filters',
            'postsFiltersData',
            array(
                'restUrl' => get_rest_url(null, 'wp/v2'),
                'perPage' => 9,
                'defaultImage' => function_exists('get_field_options')
                    ? (get_field_options('options')['posts_default_image']['url'] ?? '')
                    : '',
            )
        );
    }
}

add_action('wp_enqueue_scripts', 'growthlabtheme02_scripts');

// Add theme and parent/child theme classes to body
add_filter('body_class', function ($classes) {
    if (is_child_theme()) {
        $theme = wp_get_theme();
        $classes[] = 'theme-child-' . sanitize_html_class($theme->get_stylesheet());
        $classes[] = 'theme-parent-' . sanitize_html_class($theme->get_template());
    }
    return $classes;
});

// add aria-label to navigation menu links using the menu item title
add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
    if (empty($atts['aria-label'])) {
        $atts['aria-label'] = esc_attr($item->title);
    }
    return $atts;
}, 10, 4);

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 *
 * @return void
 */
function growthlabtheme02_widgets_init()
{

    register_sidebar(
        array(
            'name'          => esc_html__('Default Sidebar', 'growthlabtheme02'),
            'id'            => 'sidebar-default',
            'description'   => esc_html__('Add widgets here to appear in the page sidebar.', 'growthlabtheme02'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<p class="widget-title">',
            'after_title'   => '</p>',
        )
    );

    register_sidebar(
        array(
            'name'          => esc_html__('Blog Sidebar', 'growthlabtheme02'),
            'id'            => 'sidebar-blog',
            'description'   => esc_html__('Add widgets here to appear in the Blog sidebar.', 'growthlabtheme02'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<p class="widget-title">',
            'after_title'   => '</p>',
        )
    );

    if (function_exists('get_languages_map')) {
        foreach (get_languages_map() as $slug => $language) {
            register_sidebar(
                array(
                    'name'          => esc_html__("{$language} Sidebar", 'growthlabtheme02'),
                    'id'            => "sidebar-default-{$slug}",
                    'description'   => esc_html__('Add widgets here to appear in the page sidebar.', 'growthlabtheme02'),
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<p class="widget-title">',
                    'after_title'   => '</p>',
                )
            );

            register_sidebar(
                array(
                    'name'          => esc_html__("{$language} Blog Sidebar", 'growthlabtheme02'),
                    'id'            => "sidebar-blog-{$slug}",
                    'description'   => esc_html__('Add widgets here to appear in the Blog sidebar.', 'growthlabtheme02'),
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<p class="widget-title">',
                    'after_title'   => '</p>',
                )
            );
        }
    }
}

add_action('widgets_init', 'growthlabtheme02_widgets_init');


// Gravity Forms 
add_filter('gform_disable_css', '__return_true');
add_filter('gform_disable_theme_editor_styles', '__return_true');
add_filter('gform_init_scripts_footer', '__return_true');


//Import All Theme Icons to the Media Library
//Run only once after theme installation
function import_theme_images_to_folder()
{
    // Path to your theme's images folder
    $image_folder = get_template_directory() . '/assets/icons/';

    // Create custom folder in uploads
    $upload_dir = wp_upload_dir();
    $custom_folder = $upload_dir['basedir'] . '/theme-icons/';
    $custom_url = $upload_dir['baseurl'] . '/theme-icons/';

    // Create folder if it doesn't exist
    if (!file_exists($custom_folder)) {
        wp_mkdir_p($custom_folder);
    }

    // Get all image files
    $images = glob($image_folder . '*.{svg}', GLOB_BRACE);

    foreach ($images as $image_path) {
        $filename = basename($image_path);

        // Check if file already exists
        $existing = get_posts([
            'post_type' => 'attachment',
            'meta_query' => [[
                'key' => '_wp_attached_file',
                'value' => 'theme-icons/' . $filename,
                'compare' => '='
            ]]
        ]);

        if (!empty($existing)) continue;

        // Copy file to custom folder
        $new_file = $custom_folder . $filename;
        copy($image_path, $new_file);

        // Create attachment
        $attachment = [
            'post_mime_type' => mime_content_type($new_file),
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $new_file);

        // Update attachment metadata with correct path
        update_attached_file($attach_id, 'theme-icons/' . $filename);

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }
}

// Run once by visiting: yoursite.com/?import_theme_images=1
/* if (isset($_GET['import_theme_images']) && current_user_can('manage_options')) {
    import_theme_images_to_folder();
    wp_die('Images imported to /uploads/theme-icons/!');
} */

// Forzar que las secciones de widgets permanezcan disponibles
add_action('customize_register', function ($wp_customize) {
    // Verificar y forzar panel de widgets
    $widgets_panel = $wp_customize->get_panel('widgets');
    if ($widgets_panel) {
        $widgets_panel->active_callback = '__return_true';
    }

    // Forzar que las secciones específicas siempre estén activas
    $sidebar_default = $wp_customize->get_section('sidebar-widgets-sidebar-default');
    if ($sidebar_default) {
        $sidebar_default->active_callback = '__return_true';
    }

    $sidebar_blog = $wp_customize->get_section('sidebar-widgets-sidebar-blog');
    if ($sidebar_blog) {
        $sidebar_blog->active_callback = '__return_true';
    }
}, 999);

// Prevenir que el Customizer oculte secciones de widgets dinámicamente
add_action('customize_controls_print_footer_scripts', function () {
?>
    <script>
        (function($) {
            wp.customize.bind('ready', function() {
                // Forzar que los paneles de widgets permanezcan visibles
                var widgetsPanel = wp.customize.panel('widgets');
                if (widgetsPanel) {
                    widgetsPanel.active.set(true);

                    // Prevenir que se oculte
                    widgetsPanel.active.validate = function() {
                        return true;
                    };
                }

                // Forzar secciones específicas
                ['sidebar-widgets-sidebar-default', 'sidebar-widgets-sidebar-blog'].forEach(function(sectionId) {
                    var section = wp.customize.section(sectionId);
                    if (section) {
                        section.active.set(true);
                        section.active.validate = function() {
                            return true;
                        };
                    }
                });
            });
        })(jQuery);
    </script>
<?php
}, 999);
