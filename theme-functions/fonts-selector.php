<?php
// Definir fuentes por defecto
$default_fonts = [
    'primary' => 'Mona Sans',
    'secondary' => 'Roboto Serif'
];

// Fuentes disponibles
$available_fonts = [
    'Mona Sans' => 'Mona Sans',
    'Roboto Serif' => 'Roboto Serif'
];

if (!function_exists('theme_customize_register_fonts')) {
    function theme_customize_register_fonts($wp_customize)
    {
        global $default_fonts, $available_fonts;

        // Agregar sección para fuentes
        $wp_customize->add_section('fonts_section', [
            'title'       => esc_html__('Fonts', get_stylesheet()),
            'priority'    => 30,
            'description' => esc_html__('Select the fonts for your theme', get_stylesheet())
        ]);

        // Registrar selector para fuente primaria
        register_font_setting($wp_customize, 'font_primary', $default_fonts['primary'], 'Primary Font');

        // Registrar selector para fuente secundaria
        register_font_setting($wp_customize, 'font_secondary', $default_fonts['secondary'], 'Secondary Font');
    }
}

if (!function_exists('register_font_setting')) {
    function register_font_setting($wp_customize, $name, $default, $label)
    {
        global $available_fonts;

        $wp_customize->add_setting($name, [
            'default'           => $default,
            'transport'         => 'refresh',
            'sanitize_callback' => function ($value) use ($available_fonts) {
                return isset($available_fonts[$value]) ? $value : reset($available_fonts);
            }
        ]);

        $wp_customize->add_control($name, [
            'section'     => 'fonts_section',
            'label'       => esc_html__($label, get_stylesheet()),
            'type'        => 'select',
            'choices'     => $available_fonts
        ]);
    }
}

if (!function_exists('theme_get_fonts_css')) {
    function theme_get_fonts_css()
    {
        $font_primary = get_theme_mod('font_primary', 'Mona Sans');
        $font_secondary = get_theme_mod('font_secondary', 'Roboto Serif');

        $css = ":root {\n";
        $css .= "    --font-primary: \"{$font_primary}\", sans-serif;\n";
        $css .= "    --font-secondary: \"{$font_secondary}\", serif;\n";
        $css .= "}";

        return $css;
    }
}

// Registrar el selector de fuentes en el customizer
add_action('customize_register', 'theme_customize_register_fonts');
