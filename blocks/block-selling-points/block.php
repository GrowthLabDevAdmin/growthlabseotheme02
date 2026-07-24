<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block selling-points bg-gradient <?= $block_classes ?? "" ?>"
        <?php
        if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'";
        ?>>

        <div class="selling-points__wrapper container">

            <?php
            if (isset($title) && $title) {
                print_title($title, $title_tag, "selling-points__title");
                get_template_part('template-parts/logo', 'separator', array('classes' => 'selling-points__separator'));
            }
            if (isset($text_content) && $text_content):
            ?>
                <div class="selling-points__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php
            endif
            ?>

            <?php if (!empty($items)): ?>
                <div class="selling-points__carousel">
                    <div class="splide">
                        <div class="splide__track">
                            <div class="splide__list selling-points__grid">

                                <?php
                                foreach ($items as $item) :
                                    if (!empty($item)):
                                        foreach ($item as $field => $content) $$field = $content;
                                ?>

                                        <div class="item-card splide__slide">
                                            <div class="item-card__inner">
                                                <div class="item-card__icon">
                                                    <?php img_print_picture_tag(img: $icon, classes: 'item-card__icon-img', max_size: "thumbnail") ?>
                                                </div>

                                                <<?= $title_tag ?> class="item-card__title">
                                                    <?= isset($link) && $link ? "<a href='" . $link["url"] . "' target='" . $link["target"] . "' class='item-card__link'>" : "" ?>
                                                    <?= $title ?>
                                                    <?= isset($link) && $link ? "</a>" : "" ?>
                                                </<?= $title_tag ?>>

                                                <?php if (isset($content) && $content): ?>
                                                    <p class="item-card__description"><?= $content ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                <?php
                                    endif;
                                endforeach;
                                ?>

                            </div>
                        </div>

                        <?php
                        get_template_part('template-parts/splide', 'navigation', array(
                            'nav_link' => isset($cta_link) && $cta_link ? $cta_link : null,
                            'classes' => 'selling-points__arrows'
                        ));
                        ?>

                    </div>
                </div>

            <?php endif; ?>
        </div>
    </section>

<?php
endif;
?>