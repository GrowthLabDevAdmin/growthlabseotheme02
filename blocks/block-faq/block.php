<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block faq <?php if (!$background_image) echo "bg-bicolor"; ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "faq__bg bg-image gradient-overlay");
        ?>

        <div class="faq__wrapper container border-box">

            <?php
            print_title($title, $title_tag, "faq__title");
            ?>

            <?php if ($text_content): ?>
                <div class="faq__content formatted-text tx-center">
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
                                    foreach ($item as $item => $faq) $$item = $faq;
                            ?>

                                    <div class="faq__item accordeon">
                                        <div class="faq__question accordeon__heading"><?php print_title($question, $question_tag); ?></div>
                                        <div class="faq__answer formatted-text accordeon__content"><?= $answer ?></div>
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
                    <a href="<?= $cta_link['url'] ?>" target="<?= $cta_link['target'] ?>" class="btn btn--secondary">
                        <span><?= $cta_link['title'] ?></span>
                    </a>
                </div>
            <?php endif ?>


        </div>

    </section>

<?php
endif;
?>