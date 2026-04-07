<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block faq <?php if (!$background_image && !isset($$background_image) && !$lightdark_background) echo "bg-gradient"; ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "faq__bg bg-image gradient-overlay");
        ?>

        <div class="faq__wrapper container">
            <?php
            if (isset($side_images) && !empty($side_images)) :
            ?>
                <div class="faq__side-images">
                    <?php

                    foreach ($side_images as $image) {
                        img_print_picture_tag(img: $image['image'], max_size: "content", min_size: "featured-small", classes: "faq__side-image");
                    }

                    $options = get_field_options("options");

                    if ($options["logo_symbol"]) {
                        echo "<div class='faq__separator'>";
                        img_print_picture_tag(img: $options["logo_symbol"], max_size: "medium");
                        echo "</div>";
                    }
                    ?>
                </div>

            <?php
            endif;
            ?>

            <div class="faq__inner">
                <?php
                print_title($title, $title_tag, "faq__title");
                if (isset($subtitle) && $subtitle) print_title($subtitle, $subtitle_tag, "faq__subtitle pretitle");
                ?>

                <?php if ($text_content): ?>
                    <div class="faq__content formatted-text">
                        <?= $text_content ?>
                    </div>
                <?php endif ?>


                <div class="faq__sections">

                    <?php
                    if (isset($faq_sections) && !empty($faq_sections)) :
                        foreach ($faq_sections as $section) :
                            foreach ($section as $element => $content) $$element = $content;
                    ?>

                            <div class="faq__section">
                                <?php
                                print_title($heading, $heading_tag, "faq__heading");
                                ?>

                                <?php
                                if (isset($faq_items) && !empty($faq_items)) :
                                    foreach ($faq_items as $item) :
                                        foreach ($item as $key => $faq) $$key = $faq;
                                ?>

                                        <div class="faq__item accordion">
                                            <div class="faq__question accordion__heading">
                                                <?php print_title($question, $question_tag); ?>
                                            </div>
                                            <div class="faq__answer accordion__content">
                                                <div class="formatted-text accordion__inner">
                                                    <?= $answer ?>
                                                </div>
                                            </div>
                                        </div>

                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                    <?php
                        endforeach;
                    endif;
                    ?>

                </div>


                <?php if ($cta_link): ?>
                    <div class="faq__btn">
                        <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="btn btn--secondary" aria-label="<?= esc_attr($cta_link['title']) ?>">
                            <span><?= $cta_link['title'] ?></span>
                        </a>
                    </div>
                <?php endif ?>


            </div>
        </div>

    </section>

<?php
endif;
?>