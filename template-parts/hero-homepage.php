<?php
if (!defined('ABSPATH')) {
    exit;
}

//Default Properties
foreach ($args as $field => $content) $$field = $content;
//Internal Fields
foreach (get_field('hero_properties') as $key => $value) $$key = $value;
$cta_button = isset($hero_cta_button) && $hero_cta_button ? $hero_cta_button :   $hero_cta_button_default;

//Hero pictures
if (isset($hero_pictures)) foreach ($hero_pictures as $type => $picture) $$type = $picture;

$bg_desktop = isset($background_desktop) && $background_desktop ? $background_desktop :  $hero_image_desktop_default;
$bg_tablet = isset($background_tablet) && $background_tablet ? $background_tablet :  $hero_image_tablet_default;
$bg_mobile = isset($background_mobile) && $background_mobile ? $background_mobile :  $hero_image_mobile_default;

if (!$bg_desktop) $bg_desktop = [];
if (!$bg_tablet) $bg_tablet = [];
if (!$bg_mobile) $bg_mobile = [];

?>
<section id="hero-homepage" class="hero hero--homepage">

    <?php if (!empty($bg_desktop)) img_print_picture_tag(
        img: $bg_desktop,
        tablet_img: $bg_tablet,
        mobile_img: $bg_mobile,
        is_cover: true,
        classes: "hero__bg-image bg-image",
        is_priority: true
    ); ?>

    <div class="hero__wrapper container">
        <?php if ($side_portrait) img_print_picture_tag(img: $side_portrait, max_size: "content", min_size: "featured-small", classes: "hero__side-portrait", is_priority: true); ?>

        <div class="hero__content">
            <div class="hero__title">
                <?= $args["hero_title"] ?>
            </div>

            <?php if (isset($badges) && $badges && !empty($badges)): ?>
                <div class="hero__badges">
                    <?php foreach ($badges as $badge) {
                        $pic = $badge['badge'];
                        img_print_picture_tag(img: $pic, classes: "hero__badge", is_priority: false, max_size: "thumbnail");
                    } ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</section>