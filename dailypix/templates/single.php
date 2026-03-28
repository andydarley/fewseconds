<?php
if (!defined('ABSPATH')) exit;

get_header();

$project_slug = (string) get_query_var('dailypix_project');
$entry_slug   = (string) get_query_var('dailypix_entry');

$term = DailyPix_Plugin::get_project_term_by_slug($project_slug);

echo '<main class="dailypix dailypix-single">';

if (!$term || $entry_slug === '') {
    echo '<h1>Not found</h1></main>';
    get_footer();
    exit;
}

$q = new WP_Query([
    'post_type' => DailyPix_Plugin::CPT,
    'name' => $entry_slug,
    'posts_per_page' => 1,
    'tax_query' => [[
        'taxonomy' => DailyPix_Plugin::TAX,
        'field' => 'term_id',
        'terms' => $term->term_id,
    ]],
]);

if (!$q->have_posts()) {
    echo '<h1>Not found</h1></main>';
    get_footer();
    exit;
}

$q->the_post();

$src  = (string) get_post_meta(get_the_ID(), DailyPix_Plugin::META_SRC, true);
$date = (string) get_post_meta(get_the_ID(), DailyPix_Plugin::META_DATE, true);
$video_url = (string) get_term_meta($term->term_id, DailyPix_Plugin::TM_VIDEO, true);

echo '<header>';
echo '<p><a href="' . esc_url(home_url(user_trailingslashit('dailypix/' . $term->slug))) . '">&larr; ' . esc_html(DailyPix_Plugin::get_project_display_title($term)) . '</a></p>';
echo '<h1>' . esc_html(get_the_title()) . '</h1>';
if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo '<p style="opacity:.65">' . esc_html(date_i18n('j F Y', strtotime($date))) . '</p>';
}
echo '</header>';

if (has_post_thumbnail()) {
    echo '<figure class="dailypix-photo">';
    the_post_thumbnail('large', ['style' => 'max-width:100%;height:auto;']);
    echo '</figure>';
}

if ($src !== '') {
    echo '<p><a href="' . esc_url($src) . '" rel="noopener noreferrer">Source link</a></p>';
}

if ($video_url !== '') {
    // Let WP handle oEmbed where possible
    $embed = wp_oembed_get($video_url);
    if ($embed) {
        echo '<section class="dailypix-project-video">';
        echo '<h2>Animation</h2>';
        echo $embed; // oEmbed is trusted output from WP
        echo '</section>';
    }
}

// Prev/next within same project, based on dailypix_date
$curr_date = $date;

$prev_id = 0;
$next_id = 0;

if ($curr_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $curr_date)) {
    $prev = new WP_Query([
        'post_type' => DailyPix_Plugin::CPT,
        'posts_per_page' => 1,
        'tax_query' => [[
            'taxonomy' => DailyPix_Plugin::TAX,
            'field' => 'term_id',
            'terms' => $term->term_id,
        ]],
        'meta_query' => [[
            'key' => DailyPix_Plugin::META_DATE,
            'value' => $curr_date,
            'compare' => '<',
            'type' => 'DATE',
        ]],
        'meta_key' => DailyPix_Plugin::META_DATE,
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);
    if ($prev->have_posts()) { $prev->the_post(); $prev_id = get_the_ID(); }
    wp_reset_postdata();

    $next = new WP_Query([
        'post_type' => DailyPix_Plugin::CPT,
        'posts_per_page' => 1,
        'tax_query' => [[
            'taxonomy' => DailyPix_Plugin::TAX,
            'field' => 'term_id',
            'terms' => $term->term_id,
        ]],
        'meta_query' => [[
            'key' => DailyPix_Plugin::META_DATE,
            'value' => $curr_date,
            'compare' => '>',
            'type' => 'DATE',
        ]],
        'meta_key' => DailyPix_Plugin::META_DATE,
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);
    if ($next->have_posts()) { $next->the_post(); $next_id = get_the_ID(); }
    wp_reset_postdata();
}

echo '<nav class="dailypix-nav" style="display:flex;justify-content:space-between;gap:1rem;margin-top:2rem">';
echo '<div>';
if ($prev_id) echo '<a href="' . esc_url(get_permalink($prev_id)) . '">&larr; Previous</a>';
echo '</div><div>';
if ($next_id) echo '<a href="' . esc_url(get_permalink($next_id)) . '">Next &rarr;</a>';
echo '</div></nav>';

wp_reset_postdata();

echo '</main>';

get_footer();
