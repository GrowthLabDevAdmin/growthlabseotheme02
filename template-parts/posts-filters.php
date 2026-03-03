<?php
if (!defined('ABSPATH')) {
    exit;
}

// ── Categorías ────────────────────────────────────────────────────────────────
$categories = get_categories(array(
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

// ── Rango de fechas (primer y último post publicado) ──────────────────────────
$oldest_post = get_posts(array(
    'numberposts' => 1,
    'order'       => 'ASC',
    'orderby'     => 'date',
    'post_status' => 'publish',
));
$newest_post = get_posts(array(
    'numberposts' => 1,
    'order'       => 'DESC',
    'orderby'     => 'date',
    'post_status' => 'publish',
));

$year_start = !empty($oldest_post) ? (int) get_the_date('Y', $oldest_post[0]) : (int) date('Y');
$year_end   = !empty($newest_post) ? (int) get_the_date('Y', $newest_post[0]) : (int) date('Y');

$months = array(
    1  => 'January',
    2  => 'February',
    3  => 'March',
    4  => 'April',
    5  => 'May',
    6  => 'June',
    7  => 'July',
    8  => 'August',
    9  => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
);

$extra_classes = isset($args['classes']) ? esc_attr($args['classes']) : '';
?>

<section class="posts-filters <?= $extra_classes ?>" id="postsFilters">

    <form role="search" class="posts-filters__form" id="postsFiltersForm" novalidate>

        <!-- Search -->
        <div class="posts-filters__group posts-filters__group--search">
            <div class="posts-filters__input-wrap">
                <div class="posts-filters__icon">
                    <svg aria-hidden="true" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
                <input
                    class="posts-filters__input"
                    type="text"
                    id="pf-search"
                    name="search"
                    placeholder="Title, keywords, content…"
                    autocomplete="off" />
            </div>
        </div>

        <!-- Category -->
        <?php if (!empty($categories)): ?>
            <div class="posts-filters__group">
                <div class="posts-filters__select-wrap">
                    <select class="posts-filters__select" id="pf-category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc_attr($cat->term_id) ?>">
                                <?= esc_html($cat->name) ?> (<?= (int) $cat->count ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                    <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
            </div>
        <?php endif ?>

        <!-- Month -->
        <div class="posts-filters__group">
            <div class="posts-filters__select-wrap">
                <select class="posts-filters__select" id="pf-month" name="month">
                    <option value="">All months</option>
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= esc_attr($name) ?>">
                            <?= esc_html($name) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>

        <!-- Year -->
        <div class="posts-filters__group">
            <div class="posts-filters__select-wrap">
                <select class="posts-filters__select" id="pf-year" name="year">
                    <option value="">All years</option>
                    <?php for ($y = $year_end; $y >= $year_start; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor ?>
                </select>
                <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>

        <!-- Actions -->
        <div class="posts-filters__actions">
            <button type="submit" class="posts-filters__btn posts-filters__btn--apply">
                Apply filters
            </button>
            <button type="button" class="posts-filters__btn posts-filters__btn--reset" id="pfReset">
                Clear
            </button>
        </div>

    </form>

    <!-- Active pills -->
    <div class="posts-filters__pills" id="pfPills" aria-live="polite"></div>

</section>