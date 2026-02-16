<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block contact-form 
        <?php
        if (isset($side_picture) && $side_picture && !$show_only_contact_form) {
            echo "contact-form--side-pic ";
            if (($title || $main_content) && !$background_image) {
                echo "bg-gradient";
            }
        } else if (!isset($background_image) || !$background_image) {
            echo "bg-gradient";
        }
        ?>"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content && !$show_only_contact_form) echo "data-extract='$place'"; ?>>

        <div class="contact-form__layer">

            <div class="contact-form__wrapper
            <?php
            if (isset($side_picture) && $side_picture && !$title && !$main_content && !$background_image) echo "bg-gradient ";
            ?>
            ">
                <?php if (isset($background_image) && $background_image && !$show_only_contact_form) img_print_picture_tag(img: $background_image, is_cover: true, classes: "contact-form__bg bg-image gradient-overlay"); ?>

                <div class="contact-form__inner container <?php if (!$show_only_contact_form) echo "border-box" ?>">

                    <?php
                    if (!$show_only_contact_form):
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
                    endif;
                    ?>


                    <?php if (isset($contact_form) && $contact_form): ?>
                        <div class="form-box shadow-box">

                            <?php if (isset($contact_form_title) && $contact_form_title): ?>
                                <?php
                                print_title($contact_form_title, $contact_form_title_tag, "form-box__title tx-center");
                                ?>
                            <?php endif; ?>

                            <?php if (isset($contact_form_description) && $contact_form_description): ?>
                                <div class="form-box__description formatted-text tx-center">
                                    <?php echo wp_kses_post(wpautop($contact_form_description)); ?>
                                </div>
                            <?php endif ?>

                            <div class="form-box__form">
                                <?php gravity_form($contact_form, display_title: false, display_description: false); ?>
                            </div>

                            <?php if (isset($message_before_submit) && $message_before_submit): ?>
                                <div class="form-box__message formatted-text tx-center flex-center">
                                    <?= $message_before_submit ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                    <?php if (isset($side_picture) && $side_picture && !$show_only_contact_form) img_print_picture_tag(img: $side_picture,  classes: "contact-form__side-pic shadow-box"); ?>
                </div>

            </div>

        </div>

    </section>

<?php
endif;
?>