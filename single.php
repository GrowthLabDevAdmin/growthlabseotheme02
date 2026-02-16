<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();

global $post;
$post_id =  $post->ID;
?>

<<<<<<< HEAD
<section class="post__inner bg-gradient">

    <div class="post__wrapper container">

        <main class="post__main border-box">

            <?php while (have_posts()) {
                the_post();
                img_print_picture_tag(img: get_the_post_thumbnail_url(), max_size: 'large', is_cover: true, classes: "post__image");
=======
<section class="single__inner bg-gradient">

    <div class="single__wrapper container">

        <main class="single__main border-box">

            <?php while (have_posts()) {
                the_post();
                img_print_picture_tag(img: get_the_post_thumbnail_url(), max_size: 'large', is_cover: true, classes: "single__image");
>>>>>>> 1a1c395d1b87d8763cde298b0961287da44b9e95
                the_content();
            }
            ?>

        </main>

        <?php
<<<<<<< HEAD
        $args = array('ID' => $post_id, 'classes' => 'post__sidebar');
=======
        $args = array('ID' => $post_id, 'classes' => 'single__sidebar');
>>>>>>> 1a1c395d1b87d8763cde298b0961287da44b9e95
        get_sidebar('blog', $args);
        ?>

    </div>

</section>

<?php get_footer() ?>