<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block trust-cards <?php if (!$background_image) echo "bg-bicolor"; ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "trust-cards__bg bg-image gradient-overlay");
        ?>

        <div class="trust-cards__wrapper container">
            <div class="trust-cards__inner border-box">

                <?php if ($title): ?>
                    <?php
                    print_title($title, $title_tag, "trust-cards__title");
                    ?>
                <?php endif ?>


                <div class="trust-cards__grid tx-center">

                    <?php if (isset($heading_card) && ($heading_card["first_line"] || $heading_card["second_line"])): ?>
                        <div class="trust-card trust-card--heading">
                            <div class="trust-card__wrapper">
                                <div class="trust-card__inner border-box">
                                    <?php
                                    echo "<span>" . $heading_card['first_line'] . "</span>";
                                    echo "<span>" . $heading_card['second_line'] . "</span>";
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>

                    <?php if ($intro_card_content): ?>
                        <div class="trust-card trust-card--intro">
                            <div class="trust-card__wrapper">
                                <div class="trust-card__inner border-box">
                                    <div class="formatted-text">
                                        <?= $intro_card_content ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>

                    <?php if (!empty($items)): ?>
                        <div class="trust-cards__carousel">
                            <div class="splide">
                                <div class="splide__track">
                                    <ol class="trust-cards__list splide__list">

                                        <?php
                                        $i = 1;
                                        foreach ($items as $item) :
                                            if (!empty($item)):
                                                foreach ($item as $field => $content) $$field = $content;
                                        ?>
                                                <li class="trust-card splide__slide">
                                                    <div class="trust-card__wrapper">
                                                        <div class="trust-card__inner">
                                                            <span class="trust-card__number"><?= $i ?></span>

                                                            <p class="trust-card__title"><?= $title ?></p>

                                                            <p class="trust-card__content"><?= $content ?></p>
                                                        </div>
                                                    </div>
                                                </li>
                                        <?php
                                                $i++;
                                            endif;
                                        endforeach;
                                        ?>

                                    </ol>
                                </div>

                                <?php
                                get_template_part('template-parts/splide', 'navigation', array(
                                    'nav_link' => isset($cta_link) && $cta_link ? $cta_link : null,
                                    'classes' => 'trust-cards__arrows'
                                ));
                                ?>

                            </div>
                        </div>
                    <?php endif ?>

                </div>

            </div>
        </div>

    </section>

<?php
endif;
?>