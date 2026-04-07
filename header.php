<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php wp_body_open(); ?>

    <?php
    global $post;
    $post_id = $post ? $post->ID : 0;

    $options = get_current_language_options();
    foreach ($options as $key => $value) $$key = $value;

    $logo_link = home_url('/' . get_current_language()['slug']);
    $phone_number = $contact_phone ?: $main_phone_number;

    if (get_field('custom_header', $post_id)) {
        $logo_link = get_field('logo_link', $post_id) ?? '#';
        $phone_number = get_field("contact_phone", $post_id) ?? null;
        $top_callout_first_line = get_field("top_callout_first_line", $post_id) ?: '';
        $top_callout_second_line = get_field("top_callout_second_line", $post_id) ?: '';
        $cta_button = get_field("cta_button", $post_id) ?? null;
    }
    ?>

    <header role="banner" class="site-header <?= !is_404() && get_field('hero_style') !== "nohero" && $sticky_header ? "site-header--sticky" : "" ?>">

        <div class="site-header__wrapper">

            <div class="site-header__logo">
                <a href="<?= esc_url($logo_link); ?>" class="site-logo" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
                    <?php
                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        $custom_logo_id = get_theme_mod('custom_logo');
                        $image = wp_get_attachment_image_url($custom_logo_id, 'full');
                        img_print_picture_tag(img: $image, max_size: "medium", alt_text: get_bloginfo('name'), is_priority: true);
                    }
                    ?>

                    <div class="site-logo__symbol">
                        <?php
                        $options = get_field_options("options");
                        if ($options["logo_symbol"]) {
                            img_print_picture_tag(img: $options["logo_symbol"], max_size: "medium", min_size: "medium");
                        }
                        ?>
                    </div>
                    <span>Site Logo</span>
                </a>
            </div>

            <?php
            if (get_field("custom_header", $post_id) || has_nav_menu('main')) {
                $menu_args = array(
                    'container'          => 'nav',
                    'container_role'     => 'navigation',
                    'container_class' => 'main-nav',
                    'menu_class'      => 'main-nav__menu',
                    'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                    'link_before'          => '<span>',
                    'link_after'              => '</span>'
                );

                if (get_field("custom_header", $post_id)) {
                    if (get_field("menu", $post_id)) {
                        $menu_args['menu'] = get_field("menu", $post_id);
                    } else {
                        $menu_args = [];
                    }
                } else {
                    $menu_args['theme_location'] = 'main' . get_current_language_suffix();
                }
            }
            ?>

            <?php if (!empty($menu_args)) : ?>
                <button class="site-header__mobile-btn" role="button" aria-label="Mobile Menu Button">
                    <div>
                        <hr>
                        <hr>
                        <hr>
                    </div>
                    <span>Menu</span>
                </button>
            <?php endif ?>

            <div class="site-header__navigation">

                <?php if (!empty($menu_args)) : ?>

                    <div class="site-header__close-btn hide-on-desktop" role="button" aria-label="Close Menu Button">
                        <hr>
                        <hr>
                    </div>

                    <nav class="main-nav" role="navigation">
                        <?php wp_nav_menu($menu_args); ?>
                    </nav>
                <?php endif ?>

                <?php if ($phone_number): ?>
                    <div class="site-header__callout">

                        <div class="callout">
                            <?php if (!empty(get_languages_map())): ?>
                                <div class="callout__languages">
                                    <a href="<?= get_site_url() ?>" class="language" aria-label="Select English language">
                                        EN
                                    </a>
                                    <?php foreach (get_languages_map() as $lang => $data): ?>
                                        <a href="<?= get_site_url() . '/' . $lang ?>" class="language" aria-label="<?= 'Select ' . esc_attr($lang) . ' language' ?>">
                                            <?= $lang ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($top_callout_first_line): ?>
                                <span><?= $top_callout_first_line ?></span>
                            <?php endif; ?>
                            <?php if ($top_callout_second_line): ?>
                                <span><?= $top_callout_second_line ?></span>
                            <?php endif; ?>
                            <a href="tel:+1<?= get_flat_number($phone_number) ?>" class="callout__phone" aria-label="Call us at <?= esc_attr($phone_number) ?>">
                                <?php include(get_template_directory() . "/assets/icons/icon-phone.svg") ?>
                                <?= $phone_number ?>
                            </a>
                        </div>

                    </div>
                <?php endif; ?>
            </div>

            <?php if ($cta_button): ?>
                <div class="site-header__cta">
                    <a href="<?= $cta_button['url'] ?>" class="cta-button btn btn--tertiary" target="<?= $cta_button['target'] ?>" aria-label="<?= esc_attr($cta_button['title']) ?>">

                        <span class="cta-button__text">
                            <?= $cta_button['title'] ?>
                        </span>

                    </a>
                </div>
            <?php endif; ?>

        </div>
    </header>

    <?php
    $hero_title = get_field("hero_properties", $post_id)["hero_title"] ?? null;

    if ($hero_title === null || $hero_title === "") {
        if (is_home()) {
            $hero_title = get_the_title(get_option('page_for_posts'));
        } elseif (is_page() || is_single()) {
            $hero_title = get_the_title($id);
        } elseif (is_post_type_archive()) {
            $hero_title = post_type_archive_title('', false);
        } elseif (is_tax()) {
            $hero_title = single_term_title('', false);
        }
    }

    $args = array(
        "hero_title" => $hero_title,
        "hero_image_desktop_default" => $hero_image_desktop,
        "hero_image_tablet_default" => $hero_image_tablet,
        "hero_image_mobile_default" => $hero_image_mobile,
        "hero_cta_button_default" => $hero_cta_button,
    );

    if (!is_404()) {
        switch (get_field('hero_style')) {
            case 'home':
                get_template_part('template-parts/hero', 'homepage', $args);
                break;
            case 'default':
                get_template_part('template-parts/hero', 'default', $args);
                break;
            case 'home_2':
                get_template_part('template-parts/hero', 'homepage-v2', $args);
                break;
            case 'home_3':
                get_template_part('template-parts/hero', 'homepage-v3', $args);
                break;
            case 'home_4':
                get_template_part('template-parts/hero', 'homepage-v4', $args);
                break;
            case 'home_5':
                get_template_part('template-parts/hero', 'homepage-v5', $args);
                break;
            case 'home_6':
                get_template_part('template-parts/hero', 'homepage-v6', $args);
                break;
            case 'nohero':
                break;
            default:
                get_template_part('template-parts/hero', 'default', $args);
                break;
        }
    }
    ?>