<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= esc_attr($block_id ?? '') ?>"
        class="
        block 
        content-intro
        <?php if (!isset($background_image) || !$background_image) echo "bg-gradient" ?><?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        "
       <?= block_style_attribute($block); ?>
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "content-intro__bg bg-image gradient-overlay");
        ?>

        <div class="content-intro__wrapper container">
            <?php if ($title): ?>
                <div class="content-intro__title tx-center">
                    <?= $title ?>
                </div>
            <?php endif ?>

            <?php foreach (get_field("columns") as $column => $col) $$column = $col; ?>

            <div class="col col--image">
                <?php
                if (isset($featured_image) && $featured_image) img_print_picture_tag(
                    img: $featured_image,
                    max_size: "content",
                    min_size: "featured-small",
                    classes: "col__image"
                );
                ?>
            </div>

            <div class="col">
                <?php
                if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "col__pretitle pretitle");
                if (isset($title) && $title)  print_title($title, $title_tag, "col__title");
                ?>
                <div class="col__content">
                    <?= $content ?? "" ?>
                </div>
            </div>

            <?php
            foreach (get_field("cta_box") as $box => $data) $$box = $data;
            if (isset($cta_link) && !empty($cta_link["url"]) || isset($cta_link_2) && !empty($cta_link_2["url"]) || isset($content) && $content):
            ?>
                <div class="inner-cta-box">

                    <?php if ($content && isset($content)): ?>
                        <?php get_template_part("template-parts/logo", "separator", ["classes" => "inner-cta-box__separator"]); ?>

                        <div class="inner-cta-box__content formatted-text">
                            <?= $content ?>
                        </div>
                    <?php endif ?>

                    <div class="inner-cta-box__buttons">
                        <?php if ($cta_link && isset($cta_link) && !empty($cta_link["url"])): ?>
                            <a href="<?= $cta_link["url"] ?>" target="<?= $cta_link["target"] ?>" class="btn btn--tertiary" aria-label="<?= esc_attr($cta_link["title"]) ?>">
                                <span><?= $cta_link["title"] ?></span>
                            </a>
                        <?php endif ?>
                        <?php if ($cta_link_2 && isset($cta_link_2) && !empty($cta_link_2["url"])): ?>
                            <a href="<?= $cta_link_2["url"] ?>" target="<?= $cta_link_2["target"] ?>" class="btn btn--primary" aria-label="<?= esc_attr($cta_link_2["title"]) ?>">
                                <span><?= $cta_link_2["title"] ?></span>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

        </div>

    </section>

<?php
endif;
?>