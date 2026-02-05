<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block cta-box <?= $box_position ?? "" ?> <?= $block_style ?? "" ?>  <?php if ($box_position === "within") echo "bg-bicolor"; ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if ($box_position === "full" && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "cta-box__bg bg-image gradient-overlay");
        if ($box_position === "full" && $background_side_image) img_print_picture_tag(img: $background_side_image, is_cover: true, classes: "cta-box__sidebg");
        ?>

        <div class="cta-box__wrapper container">

            <?php if ($box_position === "within" && $block_style === "light"): ?>

                <div class="cta-box__decoration">
                    <?php include get_stylesheet_directory() . "/assets/img/ampersand-symbol.svg" ?>
                </div>

            <?php endif ?>

            <div class="cta-box__box shadow-box <?php if ($box_position === "full") echo "border-box" ?>">
                <div class="cta-box__inner border-box">

                    <div class="cta-box__heading">

                        <?php if ($box_position === "full" && $pretitle_line): ?>
                            <p class="cta-box__pretitle"><?= $pretitle_line ?></p>
                        <?php endif ?>

                        <?php
                        print_title($title, $title_tag, "cta-box__title");
                        ?>
                    </div>

                    <div class="cta-box__main">
                        <div class="cta-box__content">
                            <?= $text_content ?>
                        </div>

                        <?php if ($cta_link): ?>
                            <div class="cta-box__btn">
                                <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="btn btn--tertiary btn--arrow">

                                    <span><?= $cta_link['title'] ?>

                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.25 9.99981C1.25 9.83405 1.31585 9.67508 1.43306 9.55787C1.55027 9.44066 1.70924 9.37481 1.875 9.37481H16.6163L12.6825 5.44231C12.5651 5.32495 12.4992 5.16578 12.4992 4.99981C12.4992 4.83384 12.5651 4.67467 12.6825 4.55731C12.7999 4.43995 12.959 4.37402 13.125 4.37402C13.291 4.37402 13.4501 4.43995 13.5675 4.55731L18.5675 9.55731C18.6257 9.61537 18.6719 9.68434 18.7034 9.76027C18.7349 9.8362 18.7511 9.9176 18.7511 9.99981C18.7511 10.082 18.7349 10.1634 18.7034 10.2394C18.6719 10.3153 18.6257 10.3843 18.5675 10.4423L13.5675 15.4423C13.4501 15.5597 13.291 15.6256 13.125 15.6256C12.959 15.6256 12.7999 15.5597 12.6825 15.4423C12.5651 15.325 12.4992 15.1658 12.4992 14.9998C12.4992 14.8338 12.5651 14.6747 12.6825 14.5573L16.6163 10.6248H1.875C1.70924 10.6248 1.55027 10.559 1.43306 10.4418C1.31585 10.3245 1.25 10.1656 1.25 9.99981Z" fill="#F4F3EE" />
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        <?php endif ?>
                    </div>

                </div>
            </div>
        </div>

    </section>

<?php
endif;
?>