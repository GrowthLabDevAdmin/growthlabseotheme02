<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_current_language_options();
$selected_social_networks = $args['social_networks'] ?? array();

$social_networks = array(
    'facebook_url'   => 'icon-facebook.svg',
    'youtube_url'    => 'icon-youtube.svg',
    'tiktok_url'     => 'icon-tiktok.svg',
    'twitterx_url'   => 'icon-twitter-x.svg',
    'instagram_url'  => 'icon-instagram.svg',
    'linkedin_url'   => 'icon-linkedin.svg',
);

if (!empty($selected_social_networks)) {
    $social_networks = array_filter($social_networks, function ($key) use ($selected_social_networks) {
        return in_array($key, $selected_social_networks);
    }, ARRAY_FILTER_USE_KEY);
} else {
    $social_networks = array();
}

$icon_dir = get_template_directory() . '/assets/icons/';
?>

<ul class="social-networks">
    <?php foreach ($social_networks as $field_key => $icon_file) : ?>
        <?php $url = $options[$field_key] ?? ''; ?>
        <?php if (!empty($url)) : ?>
            <li>
                <a href="<?= esc_url($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="Visit our <?= esc_attr(ucwords(str_replace('_url', '', $field_key))) ?> page">
                    <?php
                    $icon_path = $icon_dir . $icon_file;
                    if (file_exists($icon_path)) {
                        include $icon_path;
                    } else {
                        echo '<!-- Icon ' . esc_attr($icon_file) . ' not found -->';
                    }
                    ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>