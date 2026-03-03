<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$post_id = get_option('page_for_posts');
?>

<section class="blog__inner">

    <div class="blog__wrapper container">

        <main role="main" class="blog__main border-box">

            <div class="blog__loop">

                <?php while (have_posts()) {

                    the_post();

                    get_template_part('template-parts/post', 'card', array(
                        "classes" => "blog__card post-card--horizontal",
                        "picture" => get_the_post_thumbnail_url(),
                        "meta" => get_the_date(),
                        "title" => get_the_title(),
                        "excerpt" => get_the_excerpt(),
                        "link_url" => get_the_permalink(),
                        "link_target" => '_self',
                    ));
                }
                ?>

            </div>

            <?php get_template_part('template-parts/posts', 'pagination', array(
                'classes'    => 'blog__pagination',
                'paged'      => max(1, get_query_var('paged', 1)),
                'query'      => $wp_query,
            )); ?>
        </main>

        <?php
        $args = array('ID' => $post_id, 'classes' => 'blog__sidebar');
        get_sidebar('blog', $args);
        ?>

    </div>

</section>

<?php get_footer() ?>