<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block contact-form <?= isset($background_style) && $background_style ? $background_style : 'light' ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="contact-form__layer">

            <div class="contact-form__wrapper <?= $reverse_columns ? "reverse" : "" ?>">

                <?php if (isset($side_image) && $side_image) img_print_picture_tag(img: $side_image, is_cover: true, classes: "contact-form__side-img") ?>

                <div class="contact-form__inner container">

                    <?php
                    if (isset($title) && $title) {
                        print_title($title, $title_tag, "contact-form__title");
                    }

                    if (isset($main_content) && !empty($main_content)):
                    ?>
                        <div class="contact-form__content formatted-text tx-center">
                            <?= $main_content ?>
                        </div>
                    <?php
                    endif;
                    ?>


                    <?php if (isset($contact_form) && $contact_form): ?>
                        <div class="form-box">

                            <div class="form-box__form">
                                <?php gravity_form($contact_form, display_title: false, display_description: false); ?>
                            </div>

                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

<?php
endif;
?>