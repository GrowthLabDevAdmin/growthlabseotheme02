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
    $phone_number = $contact_phone ?: $main_phone_number;
    ?>

    <header class="site-header <?= !is_404() && get_field('hero_style') !== "nohero" && $sticky_header ? "site-header--sticky" : "" ?>">

        <div class="site-header__wrapper">

            <div class="site-header__logo">
                <a href="<?php echo esc_url(home_url('/' . get_current_language()['slug'])); ?>" class="site-logo">
                    <?php
                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        $custom_logo_id = get_theme_mod('custom_logo');
                        $image = wp_get_attachment_image_src($custom_logo_id, 'full');
                        img_print_picture_tag(img: $image[0], alt_text: get_bloginfo('name'), is_priority: true);
                    }
                    ?>
                    <span>Site Logo</span>
                </a>
            </div>

            <button class="site-header__mobile-btn" role="button" aria-label="Mobile Menu Button">
                <div>
                    <hr>
                    <hr>
                    <hr>
                </div>
                <span>Menu</span>
            </button>

            <div class="site-header__navigation">
                <div class="site-header__close-btn hide-on-desktop" role="button" aria-label="Close Menu Button">
                    <hr>
                    <hr>
                </div>

                <?php
                if (has_nav_menu('main')) {
                    wp_nav_menu(
                        array(
                            'theme_location'  => 'main' . get_current_language_suffix(),
                            'container'          => 'nav',
                            'container_class' => 'main-nav',
                            'menu_class'      => 'main-nav__menu',
                            'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                            'link_before'          => '<span>',
                            'link_after'              => '</span>'
                        )
                    );
                }
                ?>

                <?php if ($phone_number): ?>
                    <div class="site-header__callout">

                        <div class="callout">
                            <?php if (!empty(get_languages_map())): ?>
                                <div class="callout__languages">
                                    <a href="<?= get_site_url() ?>" class="language">
                                        EN
                                    </a>
                                    <?php foreach (get_languages_map() as $lang => $data): ?>
                                        <a href="<?= get_site_url() . '/' . $lang ?>" class="language">
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
                            <a href="tel:+1<?= get_flat_number($phone_number) ?>" class="callout__phone">
                                <?php include(get_template_directory() . "/assets/icons/icon-phone.svg") ?>
                                <?= $phone_number ?>
                            </a>
                        </div>

                    </div>
                <?php endif; ?>
            </div>

            <?php if ($cta_button): ?>
                <div class="site-header__cta">
                    <a href="<?= $cta_button['url'] ?>" class="cta-button" target="<?= $cta_button['target'] ?>">

                        <span class="cta-button__text">
                            <?= $cta_button['title'] ?>
                        </span>

                    </a>
                </div>
            <?php endif; ?>

        </div>
    </header>

    <?php
    $args = array(
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