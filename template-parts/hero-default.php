<?php
if (!defined('ABSPATH')) {
    exit;
}

//Default Properties
foreach ($args as $field => $content) $$field = $content;

//Internal Fields
if (get_field('hero_properties') !== null && !empty(get_field('hero_properties'))) foreach (get_field('hero_properties') as $key => $value) $$key = $value;
$cta_button = isset($hero_cta_button) && $hero_cta_button ? $hero_cta_button : $hero_cta_button_default;

//Hero pictures
if (isset($hero_pictures)) foreach ($hero_pictures as $type => $picture) $$type = $picture;

$bg_desktop = isset($background_desktop) && $background_desktop ? $background_desktop :  $hero_image_desktop_default;
$bg_tablet = isset($background_tablet) && $background_tablet ? $background_tablet :  $hero_image_tablet_default;
$bg_mobile = isset($background_mobile) && $background_mobile ? $background_mobile :  $hero_image_mobile_default;

if (!$bg_desktop) $bg_desktop = [];
if (!$bg_tablet) $bg_tablet = [];
if (!$bg_mobile) $bg_mobile = [];

//Title Values
$hero_title_tag = $hero_title_tag ?? null;
$hero_title = $hero_title ?? null;

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

?>
<section id="hero" class="hero">

    <?php if (!empty($bg_desktop)) img_print_picture_tag(img: $bg_desktop, tablet_img: $bg_tablet, mobile_img: $bg_mobile, is_cover: true, classes: "hero__bg-image bg-image gradient-overlay", is_priority: true); ?>

    <div class="hero__wrapper container">
        <div class="hero__content tx-center">

            <div class="hero__title">
                <?= $hero_title ?>
            </div>

            <?php if ($cta_button): ?>
                <a href="<?= $cta_button['url'] ?>" target="<?= $cta_button['target'] ?>" class="hero__btn btn btn--tertiary" aria-label="<?= esc_attr($cta_button['title']) ?>">
                    <span><?= $cta_button['title'] ?></span>
                </a>
            <?php endif ?>
        </div>
    </div>

</section>