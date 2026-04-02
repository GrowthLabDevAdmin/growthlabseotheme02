<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="default-card <?php echo $args["classes"];
                            if (isset($args["content"]) && $args["content"]) "content" ?>">

    <?php
    if ($args['link_url'] && $args['link_url'] !== '') {
        echo "<a href=" . $args['link_url'] . " class='default-card__wrapper' target=" . $args['link_target'] . " aria-label='" . esc_attr($args['title']) . "'>";
    } else {
        echo "<div class='default-card__wrapper'>";
    }
    if (isset($args['picture']) && $args['picture'] && $args['picture'] !== '') {
        img_print_picture_tag(img: $args["picture"], max_size: "featured-small", min_size: "featured-small", classes: "default-card__pic");
    } else {
        include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
    }
    ?>

    <div class="default-card__inner tx-center">

        <p class="default-card__title"><?= $args["title"] ?></p>

    </div>

    <?php
    if ($args['link_url'] && $args['link_url'] !== '') {
        echo "</a>";
    } else {
        echo "</div>";
    }
    ?>
</div>