<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        class="block locations bg-bicolor"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="locations__wrapper container border-box">

            <div class="locations__content tx-center">
                <?php
                print_title($title, $title_tag, "locations__title");
                echo $main_content;
                ?>
            </div>

            <div class="locations-cards">

                <?php if (isset($first_card) && $first_card['content'] && $locations_view_structure === "carousel"): ?>
                    <div class="location-card location-card--first">
                        <div class="location-card__wrapper">
                            <div class="location-card__inner flex-center">
                                <?php if ($first_card['logo']) img_print_picture_tag(img: $first_card['logo'], max_size: "thumbnail",  classes: "location-card__logo"); ?>
                                <div class="location-card__content tx-center">
                                    <?= $first_card['content'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                if ($show_all_locations) {
                    $options = get_current_language_options();
                    $locations = $options['offices'];
                } else {
                    $locations = $offices;
                }
                if (!empty($locations)):
                ?>
                    <div class="locations-cards<?= $locations_view_structure === "carousel" ? '__carousel' : '__grid' ?>">

                        <?php if (count($locations) > 1 && $locations_view_structure === "carousel"): ?>
                            <div class="splide">
                                <div class="splide__track">
                                    <div class="splide__list">
                                    <?php endif; ?>

                                    <?php if (isset($first_card) && $first_card['content'] && $locations_view_structure === "grid"): ?>
                                        <div class="location-card location-card--first">
                                            <div class="location-card__wrapper">
                                                <div class="location-card__inner flex-center">
                                                    <?php if ($first_card['logo']) img_print_picture_tag(img: $first_card['logo'], max_size: "thumbnail",  classes: "location-card__logo"); ?>
                                                    <div class="location-card__content tx-center">
                                                        <?= $first_card['content'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    foreach ($locations as $location) {
                                        get_template_part(
                                            'template-parts/location',
                                            'card',
                                            array(
                                                'location' => $location,
                                                'classes' => (count($locations) > 1 && $locations_view_structure === "carousel") ? "splide__slide" : ""
                                            )
                                        );
                                    }
                                    ?>

                                    <?php if (count($locations) > 1 && $locations_view_structure === "carousel"): ?>
                                    </div>
                                </div>
                                <?php
                                        get_template_part('template-parts/splide', 'navigation', array(
                                            'nav_link' => $cta_link,
                                            'classes' => 'locations-cards__arrows'
                                        ));
                                ?>
                            </div>

                        <?php endif ?>

                    </div>
                <?php
                endif;
                ?>

                <?php if ($cta_link && count($locations) === 1): ?>
                    <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="cta-btn btn btn--secondary">
                        <span><?= $cta_link['title'] ?></span>
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </section>

<?php
endif;
?>