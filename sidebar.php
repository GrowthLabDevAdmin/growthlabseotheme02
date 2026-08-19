<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<aside role="complementary" class="sidebar <?= $args["classes"] ?>">

    <?php dynamic_sidebar("sidebar-default" . (get_current_language()["slug"] !== "" ? "-" . get_current_language()["slug"] : "")) ?>

    <?php
    if (get_field("menus", $args["ID"]) && have_rows("menus", $args["ID"])):
        while (have_rows("menus", $args["ID"])): the_row();
    ?>

            <div class="widget widget--menu border-box tx-center">
                <?php if (get_sub_field('menu_title')): ?>
                    <p class="widget__title h4"><?= get_sub_field('menu_title') ?></p>
                <?php endif ?>
                <?php if (get_sub_field('meu_text_content')): ?>
                    <p class="widget__content formatted-text"><?= get_sub_field('meu_text_content') ?></p>
                <?php endif ?>
                <?php the_sub_field('menu') ?>
            </div>

    <?php
        endwhile;
    endif;
    ?>

</aside>