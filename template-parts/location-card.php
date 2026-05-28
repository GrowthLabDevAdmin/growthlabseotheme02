<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php $location = $args['location']; ?>
<div class="location-card accordion <?= isset($args['classes']) ? $args['classes'] : '' ?>">
    <?php
    $tp_url = $location['target_page_url'];
    $city = $tp_url ? "<a href='$tp_url' target='_blank' aria-label='" . esc_attr($location['city']) . "'>" : '';
    $city .= $location['city'];
    $city .= $tp_url ? "</a>" : '';
    ?>

    <div class="location-card__container">
        <?= print_title($city, $location['city_tag'], "location-card__city accordion__heading"); ?>

        <div class="location-card__wrapper accordion__content">

            <div class="location-card__inner accordion__inner">
                <div class="location-card__col">
                    <?php if ($location['google_maps_embed_code']): ?>
                        <?php
                        $args = array(
                            "iframe_src" => $location['google_maps_embed_code'],
                            "name" => $location['city'],
                            "classes" => "location-card__map"
                        );
                        get_template_part("template-parts/google", "maps", $args);
                        ?>
                    <?php endif ?>
                </div>
                <div class="location-card__col">

                    <p class="location-card__address"><?= $location['address'] ?></p>

                    <?php if ($location['cta_button']['url']): ?>
                        <a href="<?= $location['cta_button']['url'] ?>" class="location-card__cta" aria-label="<?= esc_attr($location['cta_button']['title']) ?>">
                            <?= $location['cta_button']['title'] ?>
                        </a>
                    <?php endif ?>

                    <?php if ($location['phone']): ?>
                        <a href="tel:+1<?= get_flat_number($location['phone']) ?>" class="location-card__btn btn btn--tertiary" aria-label="Call us at <?= esc_attr($location['phone']) ?>">
                            <span>
                                <?= $location['phone'] ?>
                            </span>
                        </a>
                    <?php endif ?>

                </div>
            </div>
        </div>
    </div>
</div>