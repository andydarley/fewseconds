<?php
if (!defined('ABSPATH')) exit;

get_header();

$project_slug = (string) get_query_var('dailypix_project');
$term = DailyPix_Plugin::get_project_term_by_slug($project_slug);

echo '<main class="dailypix dailypix-project">';

if (!$term) {
    echo '<h1>Project not found</h1></main>';
    get_footer();
    exit;
}

$title = DailyPix_Plugin::get_project_display_title($term);
echo '<h1>' . esc_html($title) . '</h1>';

$entries = new WP_Query([
    'post_type' => DailyPix_Plugin::CPT,
    'posts_per_page' => -1,
    'tax_query' => [[
        'taxonomy' => DailyPix_Plugin::TAX,
        'field' => 'term_id',
        'terms' => $term->term_id,
    ]],
    'meta_key' => DailyPix_Plugin::META_DATE,
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'no_found_rows' => true,
]);

// Build a date=>permalink map for quick month grids
$by_month = []; // [YYYY-MM][day] => url
if ($entries->have_posts()) {
    foreach ($entries->posts as $p) {
        $d = get_post_meta($p->ID, DailyPix_Plugin::META_DATE, true);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;
        [$y,$m,$day] = explode('-', $d);
        $key = $y . '-' . $m;
        $by_month[$key][(int)$day] = get_permalink($p->ID);
    }
}

// Month grid (simple 1–31 grid, not weekday-aligned)
echo '<section class="dailypix-months">';
ksort($by_month);
foreach ($by_month as $ym => $days) {
    $month_title = date_i18n('F Y', strtotime($ym . '-01'));
    echo '<h2>' . esc_html($month_title) . '</h2>';
    echo '<div class="dailypix-month-grid" style="display:grid;grid-template-columns:repeat(7,minmax(2.2rem,1fr));gap:.25rem;max-width:28rem">';
    for ($i=1; $i<=31; $i++) {
        if (isset($days[$i])) {
            echo '<a class="dailypix-day" href="' . esc_url($days[$i]) . '" style="display:block;text-align:center;padding:.35rem;border:1px solid rgba(0,0,0,.1);border-radius:.35rem;text-decoration:none">'
                . esc_html((string)$i) . '</a>';
        } else {
            echo '<span class="dailypix-day-empty" style="display:block;text-align:center;padding:.35rem;color:rgba(0,0,0,.35)">'
                . esc_html((string)$i) . '</span>';
        }
    }
    echo '</div>';
}
echo '</section>';

// Ordered list
echo '<section class="dailypix-list">';
echo '<h2>All entries</h2>';

if ($entries->have_posts()) {
    echo '<ul>';
    while ($entries->have_posts()) {
        $entries->the_post();
        $d = get_post_meta(get_the_ID(), DailyPix_Plugin::META_DATE, true);
        $label = get_the_title();
        $date_label = ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) ? date_i18n('j M', strtotime($d)) : '';
        echo '<li>';
        echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html($label) . '</a>';
        if ($date_label !== '') echo ' <span style="opacity:.6">(' . esc_html($date_label) . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>No entries found.</p>';
}
wp_reset_postdata();

echo '</section>';
echo '</main>';

get_footer();
