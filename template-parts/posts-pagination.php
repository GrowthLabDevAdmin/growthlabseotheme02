<?php
if (!defined('ABSPATH')) {
    exit;
}
$args = isset($args) && is_array($args) ? $args : array();
$args = wp_parse_args($args, array(
    'classes' => '',
    'paged' => max(1, (int) get_query_var('paged', 1)),
    'query' => null,
    'prev_text' => null,
    'next_text' => null,
    'mid_size' => 2,
    'end_size' => 1,
));

global $wp_query;
$query = $args['query'] instanceof WP_Query ? $args['query'] : $wp_query;
$paged = (int) $args['paged'];

if (empty($query) || $query->max_num_pages <= 1) {
    return;
}

$prev_arrow = '
           <svg width="54" height="54" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M18.75 9.99981C18.75 9.83405 18.6842 9.67508 18.5669 9.55787C18.4497 9.44066 18.2908 9.37481 18.125 9.37481H3.3837L7.3175 5.44231C7.4349 5.32495 7.5008 5.16578 7.5008 4.99981C7.5008 4.83384 7.4349 4.67467 7.3175 4.55731C7.2001 4.43995 7.041 4.37402 6.875 4.37402C6.709 4.37402 6.5499 4.43995 6.4325 4.55731L1.4325 9.55731C1.3743 9.61537 1.3281 9.68434 1.2966 9.76027C1.2651 9.8362 1.2489 9.9176 1.2489 9.99981C1.2489 10.082 1.2651 10.1634 1.2966 10.2394C1.3281 10.3153 1.3743 10.3843 1.4325 10.4423L6.4325 15.4423C6.5499 15.5597 6.709 15.6256 6.875 15.6256C7.041 15.6256 7.2001 15.5597 7.3175 15.4423C7.4349 15.325 7.5008 15.1658 7.5008 14.9998C7.5008 14.8338 7.4349 14.6747 7.3175 14.5573L3.3837 10.6248H18.125C18.2908 10.6248 18.4497 10.559 18.5669 10.4418C18.6842 10.3245 18.75 10.1656 18.75 9.99981Z" fill="currentColor"/>
</svg>
            <span class="arrow__placeholder">Prev</span>
        ';

$next_arrow = '
          <svg width="54" height="54" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M1.25 9.99981C1.25 9.83405 1.31585 9.67508 1.43306 9.55787C1.55027 9.44066 1.70924 9.37481 1.875 9.37481H16.6163L12.6825 5.44231C12.5651 5.32495 12.4992 5.16578 12.4992 4.99981C12.4992 4.83384 12.5651 4.67467 12.6825 4.55731C12.7999 4.43995 12.959 4.37402 13.125 4.37402C13.291 4.37402 13.4501 4.43995 13.5675 4.55731L18.5675 9.55731C18.6257 9.61537 18.6719 9.68434 18.7034 9.76027C18.7349 9.8362 18.7511 9.9176 18.7511 9.99981C18.7511 10.082 18.7349 10.1634 18.7034 10.2394C18.6719 10.3153 18.6257 10.3843 18.5675 10.4423L13.5675 15.4423C13.4501 15.5597 13.291 15.6256 13.125 15.6256C12.959 15.6256 12.7999 15.5597 12.6825 15.4423C12.5651 15.325 12.4992 15.1658 12.4992 14.9998C12.4992 14.8338 12.5651 14.6747 12.6825 14.5573L16.6163 10.6248H1.875C1.70924 10.6248 1.55027 10.559 1.43306 10.4418C1.31585 10.3245 1.25 10.1656 1.25 9.99981Z" fill="currentColor"/>
</svg>
            <span class="arrow__placeholder">Next</span>
        ';

$pagination = paginate_links(array(
    'format' => '?paged=%#%',
    'current' => max(1, $paged),
    'total' => $query->max_num_pages,
    'prev_text' => $prev_arrow,
    'next_text' => $next_arrow,
    'type' => 'array',
    'add_args' => array(),
    'mid_size' => (int) $args['mid_size'],
    'end_size' => (int) $args['end_size'],
));

$container_classes = esc_attr($args['classes']);
?>
<div class="<?= $container_classes ?>">
    <?php
    /*
    * Generate pagination links as an array
    * Returns array of link HTML for custom markup
    */
    if (!empty($pagination)):
    ?>
        <ul class="pagination pagination-buttons">
            <?php
            foreach ($pagination as $page_link):
                // Add general pagination link class
                $page_link = str_replace('page-numbers', 'page-numbers pagination__link', $page_link);

                // Determine li class based on link type
                $li_class = 'pagination__item';

                if (strpos($page_link, 'prev') !== false) {
                    $li_class .= ' pagination__item--prev arrow arrow--prev';
                    $page_link = str_replace('prev', 'prev pagination__link--nav', $page_link);
                } elseif (strpos($page_link, 'next') !== false) {
                    $li_class .= ' pagination__item--next arrow arrow--next';
                    $page_link = str_replace('next', 'next pagination__link--nav', $page_link);
                } elseif (strpos($page_link, 'current') !== false) {
                    $li_class .= ' pagination__item--current is-active';
                    $page_link = str_replace('current', 'current pagination__link--active', $page_link);
                } elseif (strpos($page_link, 'dots') !== false) {
                    $li_class .= ' pagination__item--dots';
                    $page_link = str_replace('dots', 'dots pagination__link--dots', $page_link);
                } else {
                    $li_class .= ' pagination__item--number';
                }
            ?>
                <li class="<?= esc_attr($li_class) ?>">
                    <?= $page_link ?>
                </li>
            <?php
            endforeach;
            ?>
        </ul>
    <?php
    endif;
    ?>
</div>