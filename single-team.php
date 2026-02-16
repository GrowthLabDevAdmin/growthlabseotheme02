<?php
foreach (get_fields() as $key => $value) $$key = $value;
?>
<?php get_header(); ?>

<section class="single__inner">

    <div class="single__wrapper container">

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

                <aside class="single__sidebar single-team__sidebar border-box formatted-text">
                    <?php
                    if (has_post_thumbnail()) {
                        img_print_picture_tag(img: get_the_post_thumbnail_url(), max_size: "cover-mobile", classes: "single-team__picture");
                    }
                    ?>

                    <ul class="single-team__points">
                        <?php
                        if ($highlighted_points && !empty($highlighted_points)) :
                            foreach ($highlighted_points as $point) : ?>
                                <li class="single-team__point">
                                    <p class="h4"><?= $point['heading'] ?></p>
                                    <?= $point['content'] ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </aside>

                <main class="single__main border-box formatted-text">
                    <div class="single-team__heading">
                        <p class="single-team__title h3"><?php the_title(); ?></p>
                        <p class="single-team__role h5"><?= $role; ?></p>
                    </div>
                    <?php the_content(); ?>
                </main>
        <?php endwhile;
        endif; ?>
    </div>
</section>

<?php get_footer(); ?>