<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block content-box-grid bg-bicolor"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="content-box-grid__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "content-box-grid__pretitle pretitle");
            if (isset($title) && $title) print_title($title, $title_tag, "content-box-grid__title tx-center");
            ?>

            <?php if ($text_content): ?>
                <div class="content-box-grid__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <ul class="content-box-grid__grid">
                <?php
                if (!$is_a_menu && $items && count($items) > 0):

                    foreach ($items as $item):
                        foreach ($item as $item_key => $item_value) $$item_key = $item_value;
                ?>

                        <li class="content-box">
                            <div class="content-box__wrapper">
                                <div class="content-box__inner">

                                    <?php if ($title || $text_content): ?>

                                        <div class="content-box__content formatted-text">

                                            <<?= $title_tag ?> class="content-box__title">
                                                <?php
                                                if ($title_link) echo "<a href='" . $title_link['url'] . "' target='" . $title_link['target'] . "' aria-label='" . esc_attr($title) . "'>";
                                                echo $title;
                                                if ($title_link) echo "</a>";
                                                ?>
                                            </<?= $title_tag ?>>
                                            <?= $text_content; ?>

                                        </div>

                                    <?php endif ?>

                                </div>
                            </div>
                        </li>

                    <?php
                    endforeach;

                elseif ($is_a_menu && $menu):
                    $menu_items = wp_get_nav_menu_items($menu);
                    foreach ($menu_items as $menu_item):
                    ?>

                        <li class="content-box content-box--menu-item">
                            <a href="<?= $menu_item->url ?>" target="<?= $menu_item->target ?>" class="content-box__wrapper" aria-label="<?= esc_attr($menu_item->title) ?>">
                                <div class="content-box__inner">

                                    <div class="content-box__content formatted-text">

                                        <strong class="content-box__title">
                                            <?= $menu_item->title ?>
                                        </strong>

                                    </div>

                                </div>
                            </a>
                        </li>
                <?php
                    endforeach;
                endif;
                ?>
            </ul>

        </div>

    </section>

<?php
endif;
?>