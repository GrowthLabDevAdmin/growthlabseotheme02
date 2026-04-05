<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
    $reverse =  $reverse_order ? "reverse" : "";
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="
        block 
        two-columns
        <?= $background_type ?>
        <?= $background_type === "dark" ? "bg-gradient" : "" ?>
        <?php if ($reverse_order) echo "reverse" ?> 
        <?php if ($show_top_separator) echo "separator" ?>
        "
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if ($show_top_separator) get_template_part("template-parts/logo", "separator", ["classes" => "two-columns__separator $reverse"]);
        if (isset($background_type) && $background_type === "img") img_print_picture_tag(img: $background_image, is_cover: true, classes: "two-columns__bg bg-image gradient-overlay");
        ?>

        <div class="
        two-columns__wrapper container
        <?php if ($reverse_order) echo "reverse" ?> 
        <?php if ($show_top_separator) echo "separator" ?>
        ">
            <?php $title ? print_title($title, $title_tag, "two-columns__title") : "" ?>

            <div class="two-columns__col">
                <div class="formatted-text two-columns__content">
                    <?= $text_content ?? "" ?>
                </div>
            </div>

            <div class="two-columns__col two-columns__col--image">
                <?php
                if (isset($image) && $image) img_print_picture_tag(
                    img: $image,
                    max_size: "large",
                    min_size: "content",
                    classes: "two-columns__image $reverse"
                );
                ?>
            </div>
        </div>

    </section>

<?php
endif;
?>