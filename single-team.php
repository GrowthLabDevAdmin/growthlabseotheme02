<?php
foreach (get_fields() as $key => $value) $$key = $value;
?>
<?php get_header(); ?>

<section class="single__inner">

    <div class="single__wrapper container">

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

                <aside role="complementary" class="single__sidebar single-team__sidebar formatted-text">
                    <?php
                    if (has_post_thumbnail()) {
                        img_print_picture_tag(img: $headshot, max_size: "cover-mobile", classes: "single-team__picture");
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

                <main role="main" class="single__main">
                    <div class="single-team__heading">
                        <p class="single-team__role pretitle"><?= $role; ?></p>
                        <p class="single-team__title"><?php the_title(); ?></p>
                    </div>
                    <div class="single-team__content formatted-text">
                        <?php the_content(); ?>
                    </div>
                </main>
        <?php endwhile;
        endif; ?>
    </div>
</section>

<?php get_footer(); ?>