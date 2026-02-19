<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;

    switch ($carousel_type) {
        case 'case-result':
            $posts = $select_results_posts;
            break;

        case 'team':
            $posts = $select_team_members_posts;
            break;

        case 'post':
            $posts = $select_blog_posts;
            break;

        case 'testimonial':
            $posts = $select_testimonials_posts;
            break;

        default:
            $carousel_type = isset($select_or_create_items) && $select_or_create_items ? "any" : "";
            $posts = isset($select_or_create_items) && $select_or_create_items ? $select_posts : [];
            break;
    }

    $args = array(
        'post_type' => $carousel_type,
        'posts_per_page' => -1,
        'post__in' => $posts,
        'post_status' => 'publish',
        'orderby' => 'post__in',
    );

    $query = new WP_Query($args);
?>

    <section
        class="
        block 
        posts-carousel
        <?php if ($carousel_type === "team") echo " bg-gradient"; ?>
        <?= $carousel_type ?>
        "
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "posts-carousel__bg bg-image gradient-overlay");
        ?>

        <div class="posts-carousel__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "posts-carousel__pretitle pretitle tx-center");
            if (isset($title) && $title) print_title($title, $title_tag, "posts-carousel__title tx-center");
            ?>

            <?php if (isset($pretitle) && $text_content): ?>
                <div class="posts-carousel__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <div class="posts-carousel__carousel" data-type=<?= $carousel_type ?>>

                <div class="splide">
                    <div class="splide__track">
                        <div class="splide__list">

                            <?php
                            if (isset($custom_carousel) && !empty($custom_carousel) && !$select_or_create_items && $carousel_type === "") {
                                foreach ($custom_carousel as $item) {
                                    foreach ($item as $field => $data) $$field = $data;

                                    get_template_part('template-parts/default', 'card', array(
                                        "classes" => "splide__slide posts-carousel__card",
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

                                    switch ($carousel_type) {
                                        case 'case-result':
                                            get_template_part('template-parts/result', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "numerical_amount" => $numerical_amount,
                                                "case_title" => $case_title,
                                            ));
                                            break;

                                        case 'team':
                                            get_template_part('template-parts/team', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => $headshot,
                                                "title" => get_the_title(),
                                                "role" => $role,
                                                "content" => get_the_excerpt(),
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_blank',
                                            ));
                                            break;

                                        case 'post':
                                            get_template_part('template-parts/post', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => get_the_post_thumbnail_url(),
                                                "meta" => get_the_date(),
                                                "title" => get_the_title(),
                                                "excerpt" => get_the_excerpt(),
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_blank',
                                            ));
                                            break;

                                        case 'testimonial':
                                            get_template_part('template-parts/testimonial', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "author" => $author_name,
                                                "role" => $author_role,
                                                "content" => $testimonial_content,
                                            ));
                                            break;

                                        default:
                                            get_template_part('template-parts/default', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => get_the_post_thumbnail_url(),
                                                "title" => get_the_title(),
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_blank',
                                            ));
                                            break;
                                    }
                                }
                            }
                            ?>

                        </div>
                    </div>

                    <?php
                    get_template_part('template-parts/splide', 'navigation', array(
                        'nav_link' => $cta_link,
                        'classes' => 'posts-carousel__arrows'
                    ));
                    ?>

                </div>
            </div>

        </div>
    </section>

<?php
    wp_reset_postdata();
endif;
?>