<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block content-intro <?php if (!$background_image) echo "bg-bicolor"; ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "content-intro__bg bg-image gradient-overlay");
        ?>

        <div class="content-intro__wrapper container">
            <div class="content-intro__inner">

                <?php if ($title || $first_paragraph): ?>
                    <div class="content-intro__heading tx-center <?php if ($first_paragraph) echo "border-box"; ?>">
                        <?php
                        print_title($title, $title_tag, "content-intro__title");
                        echo $first_paragraph;
                        ?>
                    </div>
                <?php endif ?>


                <div class="content-intro__content formatted-text">

                    <?php if (
                        isset($featured_image['picture']) && $featured_image['picture']
                        && $featured_image['position'] !== "below"
                    ): ?>
                        <div class="content-intro__pic-wrapper content-intro__pic-wrapper--<?= $featured_image['position'] ?>">
                            <?php
                            img_print_picture_tag(img: $featured_image['picture'], max_size: "medium", classes: "content-intro__pic shadow-box");
                            if (isset($featured_image['picture_caption']) && $featured_image['picture_caption']):
                            ?>

                                <div class="content-intro__caption">
                                    <?php include get_stylesheet_directory() . '/assets/img/ampersand-symbol.svg'; ?>
                                    <p><?= $featured_image['picture_caption'] ?></p>
                                </div>

                            <?php endif ?>
                        </div>
                    <?php endif ?>

                    <?= $text_content ?>

                    <?php if ($cta_link): ?>
                        <div class="content-intro__btn">
                            <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="btn btn--secondary">
                                <span><?= $cta_link['title'] ?></span>
                            </a>
                        </div>
                    <?php endif ?>

                    <?php if (
                        isset($featured_image['picture']) && $featured_image['picture']
                        && $featured_image['position'] === "below"
                    ): ?>
                        <div class="content-intro__pic-wrapper content-intro__pic-wrapper--<?= $featured_image['position'] ?>">
                            <?php
                            img_print_picture_tag(img: $featured_image['picture'], max_size: "medium", classes: "content-intro__pic shadow-box");
                            if (isset($featured_image['picture_caption']) && $featured_image['picture_caption']):
                            ?>

                                <div class="content-intro__caption">
                                    <?php include get_stylesheet_directory() . '/assets/img/ampersand-symbol.svg'; ?>
                                    <p><?= $featured_image['picture_caption'] ?></p>
                                </div>

                            <?php endif ?>
                        </div>
                    <?php endif ?>

                </div>

            </div>
        </div>

    </section>

<?php
endif;
?>