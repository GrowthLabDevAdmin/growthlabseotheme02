<?php
if (!defined('ABSPATH')) {
    exit;
}
    $options = get_field_options("options");

    ?>
<div class="team-card <?= $args["classes"] ?>">
    <div class="team-card__wrapper">

        <div class='team-card__pic-wrapper'>
            <?php
            if (isset($args['picture']) && $args['picture']) {
                img_print_picture_tag(img: $options["logo_symbol"], classes: "team-card__symbol");
                img_print_picture_tag(img: $args["picture"], max_size: "large", min_size: "featured-small", classes: "team-card__pic");
            } else {
                include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
            }
            ?>
        </div>

        <div class="team-card__inner">
            <p class="team-card__role pretitle"><?= $args["role"] ?></p>

            <p class="team-card__title"><?= $args["title"] ?></p>

            <p class="team-card__content formatted-text"><?php if ($args["content"] && !empty($args["content"])) echo $args["content"] ?></p>

            <?php if ($args['link_url']): ?>
                <div class="team-card__btn">
                    <a href="<?= $args['link_url'] ?>" target="<?= $args['link_target'] ?>" class="btn btn--tertiary" aria-label="Meet <?= esc_attr($args['title']) ?>">
                        <span>MEET</span>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </div>

    <?php get_template_part("template-parts/logo", "separator", ["classes" => "team-card__separator"]); ?>
</div>