<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="testimonial-card <?= $args["classes"] ?>">
    <div class="testimonial-card__wrapper">
        <div class="testimonial-card__inner">

            <div class=" testimonial-card__stars">
                <?php
                $i = 1;
                $star_url = get_template_directory() . '/assets/icons/icon-star.svg';
                while ($i <= 5) {
                    echo '<span class="star">';
                    include $star_url;
                    echo '</span>';
                    $i++;
                }
                ?>
            </div>

            <blockquote class="testimonial-card__content">
                <p>
                    "<?= $args["content"] ?>"
                </p>
            </blockquote>

            <p class="testimonial-card__author">
                <?= $args["author"] ?>
                <span><?= $args["role"]  ?></span>
            </p>

        </div>
    </div>
</div>