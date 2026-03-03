<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();

global $post;
$post_id =  $post->ID;
?>

<section class="single__inner">

    <div class="single__wrapper container">

        <main role="main" class="single__main">

            <?php while (have_posts()) {
                the_post();
                img_print_picture_tag(img: get_the_post_thumbnail_url(), max_size: 'large', is_cover: true, classes: "single__image");
                the_content();
            }
            ?>

        </main>

        <?php
        if (!get_field("hide_sidebar")) {
            $args = array('ID' => $post_id, 'classes' => 'single__sidebar');
            get_sidebar('blog', $args);
        }
        ?>

    </div>

</section>

<?php get_footer() ?>