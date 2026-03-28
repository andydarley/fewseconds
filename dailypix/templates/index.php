<?php
if (!defined('ABSPATH')) exit;

get_header();

$terms = get_terms([
    'taxonomy' => DailyPix_Plugin::TAX,
    'hide_empty' => false,
]);

// Sort by year meta (numeric), then name
if (!is_wp_error($terms)) {
    usort($terms, function($a, $b) {
        $ya = (int) get_term_meta($a->term_id, DailyPix_Plugin::TM_YEAR, true);
        $yb = (int) get_term_meta($b->term_id, DailyPix_Plugin::TM_YEAR, true);
        if ($ya === $yb) return strcmp($a->name, $b->name);
        return $ya <=> $yb;
    });
}

echo '<main class="dailypix dailypix-index">';
echo '<h1>Daily Photo Projects</h1>';

if (empty($terms) || is_wp_error($terms)) {
    echo '<p>No projects found.</p>';
} else {
    echo '<div class="dailypix-tiles" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">';
    foreach ($terms as $term) {
        $title = DailyPix_Plugin::get_project_display_title($term);
        $desc  = (string) get_term_meta($term->term_id, DailyPix_Plugin::TM_DESC, true);
        $url   = home_url(user_trailingslashit('dailypix/' . $term->slug));

        echo '<article class="dailypix-tile" style="padding:1rem;border:1px solid rgba(0,0,0,.1);border-radius:.5rem">';
        echo '<h2 style="margin-top:0"><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></h2>';
        if ($desc !== '') echo '<p>' . esc_html($desc) . '</p>';
        echo '</article>';
    }
    echo '</div>';
}

echo '</main>';

get_footer();
