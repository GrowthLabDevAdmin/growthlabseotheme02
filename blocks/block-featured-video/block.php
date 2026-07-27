<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section
        id="<?= $block_id ?? "" ?>"
        class="block featured-video<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="featured-video__wrapper">
            <?php
            if (isset($cover_image) && $cover_image) {
                img_print_picture_tag(img: $cover_image, is_cover: true, classes: "featured-video__cover");
            } elseif (isset($youtube_url) && $youtube_url && $is_youtube_video) {
                $video_id = get_yt_code($youtube_url);
                $thumb = get_youtube_thumbnail($video_id, 'maxresdefault');
                echo "<img srcset = '$thumb' class='featured-video__cover lazy-image'>";
            }
            ?>

            <?php
            if ((isset($youtube_url) && $youtube_url) || (isset($video) && $video)) :
            ?>
                <button
                    type="button"
                    class="featured-video__btn"
                    <?php
                    if ($is_youtube_video) {
                        echo "data-videourl='$youtube_url'";
                    } else {
                        echo "data-videoid='$video'";
                    }
                    ?>
                    data-mode="<?= $enable_lightbox ? 'lightbox' : 'inline' ?>"
                    data-target=".featured-video__wrapper">
                    <span>Play</span>
                </button>
            <?php
            endif;
            ?>
            <hr>
        </div>

    </section>

<?php
endif;
?>