<?php
if (!defined('ABSPATH')) {
    exit;
}
foreach ($args as $key => $value) $$key = $value;
?>

<div class="ampersand-separator <?= esc_attr($classes); ?>">
    <hr>
    <?php
    $options = get_field_options("options");

    if ($options["logo_symbol"]) {
        img_print_picture_tag(img: $options["logo_symbol"], max_size: "medium");
    } else {
        include get_stylesheet_directory() . '/assets/img/ampersand-symbol.svg';
    } ?>
    <hr>
</div>