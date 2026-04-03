<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    if (is_front_page()) {
        $paged = (get_query_var('page')) ? get_query_var('page') : 1;
    }

    $args = array(
        'post_type' => $grid_type,
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'order' => 'DESC',
        'paged' => $paged,
    );

    if ($grid_type === "testimonial") {
        $args['posts_per_page'] = -1;
    }

    if ($grid_type === "case-result") {
        $args['posts_per_page'] = -1;
        $args['meta_key'] = 'numerical_amount';
        $args['orderby'] = 'meta_value_num';
    }

    if ($grid_type === "custom" && $select_or_create_items) {
        $args['post_type'] = "any";
        $args['post__in'] = $select_posts;
        $args['orderby'] = 'post__in';
    }

    $query = new WP_Query($args);
?>


    <section
        class="block posts-grid"
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "posts-grid__bg bg-image gradient-overlay");
        ?>

        <div class="posts-grid__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "posts-grid__pretitle pretitle");
            if (isset($title) && $title) print_title($title, $title_tag, "posts-grid__title tx-center");
            ?>

            <?php if ($text_content): ?>
                <div class="posts-grid__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <div class="posts-grid__feed">

                <div class="posts-grid__grid  <?= $grid_type ?>">

                    <?php
                    if (isset($custom_grid) && !empty($custom_grid) && !$select_or_create_items && $grid_type === "custom") {
                        foreach ($custom_grid as $item) {
                            foreach ($item as $field => $data) $$field = $data;

                            get_template_part('template-parts/default', 'card', array(
                                "classes" => "posts-grid__card " . $grid_type,
                                "picture" => $picture ?? '',
                                "title" => $title ?? '',
                                "link_url" => $link['url'] ?? '',
                                "link_target" => $link['target'] ?? '_self',
                            ));
                        }
                    } elseif (isset($query) && $query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();

                            if (!empty(get_fields(get_the_ID()))) foreach (get_fields(get_the_ID()) as $field => $content) $$field = $content;

                            switch ($grid_type) {
                                case 'case-result':
                                    get_template_part('template-parts/result', 'card', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "numerical_amount" => $numerical_amount,
                                        "case_title" => $case_title,
                                        "case_description" => $case_description,
                                    ));
                                    break;

                                case 'team':
                                    get_template_part('template-parts/team', 'card-grid', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => $headshot,
                                        "name" => get_the_title(),
                                        "role" => $role,
                                        "link_url" => get_the_permalink(),
                                        "link_target" => '_self',
                                    ));
                                    break;

                                case 'post':
                                    get_template_part('template-parts/post', 'card', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => get_the_post_thumbnail_url(),
                                        "meta" => get_the_date(),
                                        "title" => get_the_title(),
                                        "excerpt" => get_the_excerpt(),
                                        "link_url" => get_the_permalink(),
                                        "link_target" => '_self',
                                    ));
                                    break;

                                case 'testimonial':
                                    get_template_part('template-parts/testimonial', 'card', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => $author_picture,
                                        "author" => $author_name,
                                        "role" => $author_role,
                                        "content" => $testimonial_content,
                                        "source" => $testimonial_source,
                                        "link_url" => $testimonial_source_link,
                                    ));
                                    break;

                                default:
                                    get_template_part('template-parts/default', 'card', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => get_the_post_thumbnail_url(),
                                        "title" => get_the_title(),
                                        "link_url" => get_the_permalink(),
                                        "link_target" => '_self',
                                    ));
                                    break;
                            }
                        }
                    }
                    ?>

                </div>

                <?php
                if ($query->max_num_pages > 1) {
                    get_template_part('template-parts/posts', 'pagination', array(
                        'paged' => $paged,
                        'query' => $query,
                        'classes' => 'case-results__pagination'
                    ));
                }
                ?>

            </div>

        </div>
    </section>

<?php
    wp_reset_postdata();
endif;
