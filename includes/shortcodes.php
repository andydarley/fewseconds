<?php

function afso_get_county_icon_url_by_slug($slug) {
    $slug = sanitize_title($slug);
    if ($slug === '') return '';

    $relative = 'assets/icons/county/video-' . $slug . '.png';
    if (file_exists(AFSO_PATH . $relative)) {
        return AFSO_URL . $relative;
    }
    return '';
}

function afso_render_map_container($args = []) {
    $defaults = [
        'county_slug' => '',
        'post_id' => 0,
        'height' => 600,
    ];
    $args = wp_parse_args($args, $defaults);
    $height = max(240, (int) $args['height']);

    $attrs = [
        'class' => 'afso-video-map',
        'data-county-slug' => sanitize_title($args['county_slug']),
        'data-post-id' => (int) $args['post_id'],
        'style' => 'height:' . $height . 'px',
    ];

    $html = '<div';
    foreach ($attrs as $key => $value) {
        $html .= ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"';
    }
    $html .= '></div>';
    return $html;
}

add_shortcode('afso_overview', function ($atts = []) {
    $atts = shortcode_atts([
        'height' => 600,
        'show_table' => 'true',
    ], $atts, 'afso_overview');

    // 1) Suppress counties with zero entries
    $terms = get_terms([
        'taxonomy' => 'county',
        'hide_empty' => true,
    ]);

    // Default server-side order: alphabetic by county name
    if (!is_wp_error($terms) && is_array($terms)) {
        usort($terms, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
    }

    $table_id = 'afso-summary-table-' . wp_rand(1000, 999999);

    ob_start();
    echo '<div class="afso-overview">';
    echo afso_render_map_container(['height' => (int) $atts['height']]);

    if ($atts['show_table'] !== 'false' && !is_wp_error($terms)) {
        echo '<h3>Videos by county</h3>';
        echo '<table class="afso-summary-table" id="' . esc_attr($table_id) . '">';
        echo '<thead><tr>';
        echo '<th>Icon</th>';
        echo '<th><button type="button" class="afso-sort-btn" data-target="' . esc_attr($table_id) . '" data-sort="county" data-dir="asc">County</button></th>';
        echo '<th><button type="button" class="afso-sort-btn" data-target="' . esc_attr($table_id) . '" data-sort="count" data-dir="desc">Videos</button></th>';
        echo '</tr></thead><tbody>';

        foreach ($terms as $term) {
            $icon = afso_get_county_icon_url_by_slug($term->slug);
            $term_link = get_term_link($term);

            echo '<tr data-county="' . esc_attr(mb_strtolower($term->name)) . '" data-count="' . esc_attr((int) $term->count) . '">';
            echo '<td class="afso-summary-icon">';
            if ($icon !== '') {
                echo '<img src="' . esc_url($icon) . '" alt="' . esc_attr($term->name) . '" width="24" height="28" />';
            }
            echo '</td>';

            echo '<td>';
            if (!is_wp_error($term_link)) {
                echo '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a>';
            } else {
                echo esc_html($term->name);
            }
            echo '</td>';

            echo '<td>' . esc_html((string) $term->count) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // 2) Orderable table: county alpha and totals numeric
echo '<script>
(function() {
    function sortTable(tableId, mode, dir) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var tbody = table.querySelector("tbody");
        if (!tbody) return;

        var rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));
        rows.sort(function(a, b) {
            if (mode === "count") {
                var av = parseInt(a.getAttribute("data-count") || "0", 10);
                var bv = parseInt(b.getAttribute("data-count") || "0", 10);
                return dir === "asc" ? (av - bv) : (bv - av);
            } else {
                var an = (a.getAttribute("data-county") || "");
                var bn = (b.getAttribute("data-county") || "");
                if (an < bn) return dir === "asc" ? -1 : 1;
                if (an > bn) return dir === "asc" ? 1 : -1;
                return 0;
            }
        });

        rows.forEach(function(row) { tbody.appendChild(row); });
    }

    function setActiveButtonState(tableId, activeBtn) {
        var allBtns = document.querySelectorAll(\'.afso-sort-btn[data-target="\'+ tableId +\'"]\');
        allBtns.forEach(function(btn) {
            btn.classList.remove("is-active");
        });
        activeBtn.classList.add("is-active");
    }

    document.addEventListener("click", function(e) {
        var btn = e.target.closest(".afso-sort-btn");
        if (!btn) return;

        var tableId = btn.getAttribute("data-target");
        var mode = btn.getAttribute("data-sort");
        var currentDir = btn.getAttribute("data-dir") || "asc";
        var nextDir = currentDir === "asc" ? "desc" : "asc";

        sortTable(tableId, mode, currentDir);
        setActiveButtonState(tableId, btn);
        btn.setAttribute("data-dir", nextDir);
    });
})();
</script>';
    }

    echo '</div>';
    return ob_get_clean();
});

add_shortcode('afso_term_map', function ($atts = []) {
    $atts = shortcode_atts([
        'slug' => '',
        'height' => 600,
        'title' => '',
    ], $atts, 'afso_term_map');

    $slug = sanitize_title($atts['slug']);
    $title = sanitize_text_field($atts['title']);

    if ($slug === '' && is_tax('county')) {
        $term = get_queried_object();
        if ($term && !empty($term->slug)) {
            $slug = sanitize_title($term->slug);
            if ($title === '') {
                $title = 'AFSO Videos in ' . $term->name;
            }
        }
    }

    ob_start();
    if ($title !== '') {
        echo '<h2>' . esc_html($title) . '</h2>';
    }
    echo afso_render_map_container([
        'county_slug' => $slug,
        'height' => (int) $atts['height'],
    ]);
    return ob_get_clean();
});

add_shortcode('afso_single_video', function ($atts = []) {
    $atts = shortcode_atts([
        'id' => 0,
        'height' => 400,
    ], $atts, 'afso_single_video');

    $post_id = (int) $atts['id'];
    if ($post_id <= 0) {
        $post_id = get_the_ID();
    }
    if ($post_id <= 0) return '';

    $video_id = get_post_meta($post_id, 'afso_video_url', true);
    if ($video_id === '') return '';

    ob_start();
    echo '<div class="afso-single-shortcode">';
    echo '<iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
    echo afso_render_map_container([
        'post_id' => $post_id,
        'height' => (int) $atts['height'],
    ]);
    echo '</div>';
    return ob_get_clean();
});