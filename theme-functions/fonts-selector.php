<?php

// ─── DEFAULTS DEL TEMA ────────────────────────────────────────────────────────
$default_fonts = [
    'primary'   => 'Mona Sans',
    'secondary' => 'Roboto Serif',
];

// ─── FUNCIÓN CENTRAL: lee Font Library + fuentes del tema ────────────────────
if (! function_exists('theme_get_all_fonts')) {
    /**
     * Retorna todas las fuentes disponibles combinando:
     *   1. Fuentes base del tema (hardcoded — siempre presentes).
     *   2. Fuentes instaladas vía Font Library de WP 7+ (sub-clave 'custom'
     *      en WP_Theme_JSON_Resolver).
     *
     * NO lee la sub-clave 'theme' del resolver para evitar condiciones de
     * carrera con el filter wp_theme_json_data_theme: en ciertos contextos
     * (ej. customize_register) ese filter aún no ha corrido y el resolver
     * devuelve los datos del archivo theme.json en disco.
     *
     * @return array<string, array{ name: string, font_family: string, slug: string }>
     *   Keyed por slug. Las fuentes custom de la Font Library van primero.
     */
    function theme_get_all_fonts(): array
    {
        // 1. Fuentes base del tema — siempre disponibles, keyed por slug
        $fonts = [
            'primary' => [
                'name'        => 'Mona Sans',
                'font_family' => '"Mona Sans", sans-serif',
                'slug'        => 'primary',
            ],
            'secondary' => [
                'name'        => 'Roboto Serif',
                'font_family' => '"Roboto Serif", serif',
                'slug'        => 'secondary',
            ],
        ];

        // 2. Fuentes instaladas vía Font Library (solo 'custom')
        if (! class_exists('WP_Theme_JSON_Resolver')) {
            return $fonts;
        }

        $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
        $custom   = $settings['typography']['fontFamilies']['custom'] ?? [];

        // Insertar fuentes custom AL INICIO para que aparezcan primero
        $custom_fonts = [];
        foreach ($custom as $font) {
            $slug = $font['slug'] ?? sanitize_title($font['name'] ?? '');
            if (! $slug || isset($fonts[$slug])) {
                continue;
            }
            $custom_fonts[$slug] = [
                'name'        => $font['name']       ?? $slug,
                'font_family' => $font['fontFamily'] ?? $font['name'],
                'slug'        => $slug,
            ];
        }

        // custom primero, luego las del tema
        return array_merge($custom_fonts, $fonts);
    }
}

// ─── CUSTOMIZER ──────────────────────────────────────────────────────────────

if (! function_exists('theme_customize_register_fonts')) {
    function theme_customize_register_fonts($wp_customize)
    {
        global $default_fonts;

        $wp_customize->add_section('fonts_section', [
            'title'       => esc_html__('Fonts', get_stylesheet()),
            'priority'    => 30,
            'description' => esc_html__('Select the fonts for your theme', get_stylesheet()),
        ]);

        register_font_setting($wp_customize, 'font_primary',   $default_fonts['primary'],   'Primary Font');
        register_font_setting($wp_customize, 'font_secondary', $default_fonts['secondary'], 'Secondary Font');
    }
}

if (! function_exists('register_font_setting')) {
    function register_font_setting($wp_customize, $name, $default, $label)
    {
        // Construir choices desde todas las fuentes disponibles.
        // Se llama aquí (en customize_register) para que WP_Theme_JSON_Resolver
        // ya tenga los datos de la DB cargados.
        $all_fonts = theme_get_all_fonts();

        // Fallback: si por alguna razón no hay fuentes aún, mostrar el default
        if (empty($all_fonts)) {
            $choices = [$default => $default];
        } else {
            $choices = [];
            foreach ($all_fonts as $slug => $data) {
                // Usar el nombre de la fuente como valor guardado (igual que antes)
                // para mantener compatibilidad con get_theme_mod existente.
                $choices[$data['name']] = $data['name'];
            }
        }

        $wp_customize->add_setting($name, [
            'default'           => $default,
            'transport'         => 'refresh',
            'sanitize_callback' => function ($value) use ($choices) {
                return isset($choices[$value]) ? $value : array_key_first($choices);
            },
        ]);

        $wp_customize->add_control($name, [
            'section' => 'fonts_section',
            'label'   => esc_html__($label, get_stylesheet()),
            'type'    => 'select',
            'choices' => $choices,
        ]);
    }
}

// ─── CSS CUSTOM PROPERTIES ───────────────────────────────────────────────────

if (! function_exists('theme_get_fonts_css')) {
    /**
     * Genera el bloque :root con las custom properties de fuente.
     * Usado en wp_head y para live preview del Customizer.
     */
    function theme_get_fonts_css(): string
    {
        global $default_fonts;

        $font_primary   = get_theme_mod('font_primary',   $default_fonts['primary']);
        $font_secondary = get_theme_mod('font_secondary', $default_fonts['secondary']);

        // Buscar el font-family completo (con fallback genérico) si existe en la library
        $all_fonts      = theme_get_all_fonts();

        $primary_family   = _theme_resolve_font_family($font_primary,   $all_fonts, 'sans-serif');
        $secondary_family = _theme_resolve_font_family($font_secondary, $all_fonts, 'serif');

        $css  = ":root {\n";
        $css .= "    --font-primary: {$primary_family};\n";
        $css .= "    --font-secondary: {$secondary_family};\n";
        $css .= "}";

        return $css;
    }
}

if (! function_exists('_theme_resolve_font_family')) {
    /**
     * Dado el nombre de una fuente (guardado en theme_mod), devuelve el
     * valor CSS completo (con fallback) si está en la librería, o construye
     * uno sencillo con el fallback genérico recibido.
     *
     * @internal
     */
    function _theme_resolve_font_family(string $font_name, array $all_fonts, string $generic_fallback): string
    {
        // Buscar por nombre entre todas las fuentes disponibles
        foreach ($all_fonts as $data) {
            if (strtolower($data['name']) === strtolower($font_name)) {
                return $data['font_family']; // ya incluye fallback genérico desde theme.json / Font Library
            }
        }
        // No encontrada: construir valor seguro
        return "\"{$font_name}\", {$generic_fallback}";
    }
}

// ─── HOOKS ───────────────────────────────────────────────────────────────────

add_action('customize_register', 'theme_customize_register_fonts');
