<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block cta-box <?= $box_style ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>


        <div class="cta-box__wrapper container">

            <?php
            if ($box_style === "full" && $side_image) img_print_picture_tag(img: $side_image, max_size: "large", min_size: "featured-small", classes: "cta-box__side-img");
            ?>

            <div class="cta-box__inner">

                <div class="cta-box__heading">
                    <?php
                    if ($box_style === "full" && isset($pretitle) && $pretitle) {
                        print_title($pretitle, $pretitle_tag, "cta-box__pretitle pretitle tx-center");
                    }
                    if (isset($title) && $title) {
                        print_title($title, $title_tag, "cta-box__title tx-center");
                    }
                    if (isset($subtitle) && $subtitle) {
                        print_title($subtitle, $subtitle_tag, "cta-box__subtitle pretitle tx-center");
                    }
                    ?>
                </div>

                <div class="cta-box__main">
                    <?php if ($box_style === "full" && isset($text_content) && $text_content): ?>
                        <div class="cta-box__content tx-center formatted-text">
                            <?= $text_content ?>
                        </div>
                    <?php endif ?>

                    <?php
                    if (isset($cta_buttons) && !empty($cta_buttons)):
                    ?>
                        <div class="cta-box__buttons">
                            <?php
                            $i = 0;
                            foreach ($cta_buttons as $button):
                                $cta_link = $button['link'];
                                $btn_style = $i === 0 ? "tertiary" : "primary";
                            ?>
                                <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="btn btn--<?= $btn_style ?>" aria-label="<?= esc_attr($cta_link['title']) ?>">
                                    <span><?= $cta_link['title'] ?></span>
                                </a>
                            <?php
                                $i++;
                            endforeach;
                            ?>
                        </div>
                    <?php
                    endif;
                    ?>
                </div>

            </div>
        </div>

    </section>

<?php
endif;
?>