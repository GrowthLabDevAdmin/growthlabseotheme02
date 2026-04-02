<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="team-card-grid <?= $args["classes"] ?>">
    <?php if ($args['link_url'] && $args['link_url'] !== ''): ?>
        <a href="<?= $args['link_url'] ?>" target="<?= $args['link_target'] ?>" class="team-card-grid__wrapper" aria-label="<?= esc_attr($args['name']) ?>">
            <?php
            if (isset($args['picture']) && $args['picture'] && $args['picture'] !== '') {
                img_print_picture_tag(img: $args["picture"], max_size: "cover-mobile", min_size: "featured-small", classes: "team-card-grid__pic");
            } else {
                include get_template_directory() . '/assets/icons/icon-file-image.svg';
            }
            ?>

            <div class="team-card-grid__inner tx-center">

                <p class="team-card-grid__title"><?= $args["name"] ?></p>

                <p class="team-card-grid__role"><?= $args["role"] ?></p>

            </div>
        </a>
    <?php endif ?>
</div>