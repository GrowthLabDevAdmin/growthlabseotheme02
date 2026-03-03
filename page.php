<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();

global $post;
$post_id =  $post->ID;
?>

<section class="page__inner">

    <div class="page__wrapper container">

        <main role="main" class="page__main formatted-text">

            <?php while (have_posts()) {
                the_post();
                the_content();
            }
            ?>

        </main>

        <?php
        if (!get_field("hide_sidebar")) {
            $args = array('ID' => $post_id, 'classes' => 'page__sidebar');
            get_sidebar('sidebar', $args);
        }
        ?>

    </div>

</section>

<?php get_footer() ?>