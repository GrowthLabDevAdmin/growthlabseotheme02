<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<article class="post-card <?= $args["classes"] ?>">
    <div class="post-card__wrapper">

        <?php
        if ($args['link_url']) {
            echo "<a href=" . $args['link_url'] . " class='post-card__pic-wrapper' target=" . $args['link_target'] . " aria-label='" . esc_attr($args['title']) . "'>";
        } else {
            echo "<div class='post-card__pic-wrapper'>";
        }
        if (isset($args['picture']) && $args['picture']) {
            img_print_picture_tag(img: $args["picture"], max_size: "content", min_size: "featured-small", classes: "post-card__pic");
        } elseif (get_field_options("options")["posts_default_image"] && !empty(get_field_options("options")["posts_default_image"])) {
            img_print_picture_tag(img: get_field_options("options")["posts_default_image"], max_size: "content", min_size: "featured-small", classes: "post-card__pic");
        } else {
            include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
        }
        if ($args['link_url']) {
            echo "</a>";
        } else {
            echo "</div>";
        }

        ?>

        <div class="post-card__inner">
            <span class="post-card__meta"><?= $args["meta"] ?></span>

            <p class="post-card__title"><?= $args["title"] ?></p>

            <p class="post-card__content"><?= $args["excerpt"] ?></p>

            <?php if ($args['link_url']): ?>
                <a href="<?= $args['link_url'] ?>" target="<?= $args['link_target'] ?>" class="post-card__link" aria-label="Read more about <?= esc_attr($args['title']) ?>">
                    <span>Read More
                        <?php include get_template_directory() . "\assets\icons\icon-circle-arrow.svg" ?>
                    </span>
                </a>
            <?php endif ?>
        </div>
    </div>
</article>