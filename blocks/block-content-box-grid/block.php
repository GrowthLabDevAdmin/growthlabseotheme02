<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block content-box"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="content-box__wrapper container">
            <div class="content-box__inner">

                <?php if ($title || $text_content): ?>

                    <div class="content-box__content formatted-text">

                        <?php print_title($title, $title_tag, "content-box__title"); ?>
                        <?= $text_content; ?>

                    </div>

                <?php endif ?>

            </div>
        </div>

    </section>

<?php
endif;
?>