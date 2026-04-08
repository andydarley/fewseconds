<?php
function afso_register_post_type_and_taxonomy() {
    register_post_type('afso_videos', array(
        'labels' => array(
            'name' => 'AFSO Videos',
            'singular_name' => 'AFSO Video',
            'add_new_item' => 'Add New Video',
            'edit_item' => 'Edit Video',
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'videos'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-video-alt3',
    ));

    register_taxonomy('county', 'afso_videos', array(
        'label' => 'County',
        'hierarchical' => true,
        'rewrite' => array('slug' => 'county'),
        'show_admin_column' => true,
    ));
}
add_action('init', 'afso_register_post_type_and_taxonomy');

// Admin list filter: county dropdown on AFSO Videos screen.
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'afso_videos') return;

    $selected = isset($_GET['county']) ? sanitize_text_field(wp_unslash($_GET['county'])) : '';
    wp_dropdown_categories([
        'show_option_all' => 'All counties',
        'taxonomy' => 'county',
        'name' => 'county',
        'orderby' => 'name',
        'selected' => $selected,
        'hierarchical' => true,
        'show_count' => false,
        'hide_empty' => false,
        'value_field' => 'slug',
    ]);
});

/**
 * Try to read sequence from meta first; fallback to title prefix (e.g. "210: ...").
 */
function afso_get_sequence_for_admin($post_id, $post_title = '') {
    $sequence = get_post_meta($post_id, 'afso_sequence', true);

    if ($sequence !== '' && $sequence !== null) {
        return (string) $sequence;
    }

    if ($post_title === '') {
        $post_title = get_the_title($post_id);
    }

    if (preg_match('/^\s*(\d+)\s*[:\-]/', $post_title, $m)) {
        return $m[1];
    }

    return '';
}

/**
 * Try to read filmed datetime from modern meta first, then legacy date/time fields.
 */
function afso_get_filmed_datetime_for_admin($post_id) {
    $raw = (string) get_post_meta($post_id, 'afso_date_filmed', true);
    if ($raw !== '') return $raw;

    $legacy_date = trim((string) get_post_meta($post_id, 'afso_date', true));
    $legacy_time = trim((string) get_post_meta($post_id, 'afso_time', true));

    if ($legacy_date === '' && $legacy_time === '') {
        return '';
    }

    $candidate = trim($legacy_date . ' ' . strtolower($legacy_time));

    // Common historic formats seen in this project.
    $formats = [
        'd/m/Y g.ia',
        'd/m/Y g:ia',
        'd/m/Y H:i',
        'd/m/Y',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
    ];

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $candidate);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }

        // Try date-only formats against date only.
        if (in_array($format, ['d/m/Y', 'Y-m-d'], true)) {
            $dt = DateTime::createFromFormat($format, $legacy_date);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d 00:00:00');
            }
        }
    }

    $ts = strtotime($candidate);
    if ($ts !== false) {
        return date('Y-m-d H:i:s', $ts);
    }

    return '';
}

// Admin list columns: sequence first, county and filmed date visible.
add_filter('manage_afso_videos_posts_columns', function ($columns) {
    $new_columns = [];

    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }

    $new_columns['afso_sequence'] = 'Sequence';

    if (isset($columns['title'])) {
        $new_columns['title'] = $columns['title'];
    } else {
        $new_columns['title'] = 'Title';
    }

    if (isset($columns['taxonomy-county'])) {
        $new_columns['taxonomy-county'] = $columns['taxonomy-county'];
    } else {
        $new_columns['taxonomy-county'] = 'County';
    }

    $new_columns['afso_date_filmed'] = 'Date filmed';

    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }

    return $new_columns;
});

add_action('manage_afso_videos_posts_custom_column', function ($column, $post_id) {
    if ($column === 'afso_sequence') {
        $sequence = afso_get_sequence_for_admin($post_id);
        echo $sequence === '' ? '&mdash;' : esc_html($sequence);
        return;
    }

    if ($column === 'afso_date_filmed') {
        $raw = afso_get_filmed_datetime_for_admin($post_id);
        if ($raw === '') {
            echo '&mdash;';
            return;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            echo esc_html($raw);
            return;
        }

        echo esc_html(date_i18n('j M Y, g:ia', $ts));
        return;
    }
}, 10, 2);

add_filter('manage_edit-afso_videos_sortable_columns', function ($columns) {
    $columns['afso_sequence'] = 'afso_sequence';
    $columns['afso_date_filmed'] = 'afso_date_filmed';
    return $columns;
});

// Keep sorting behavior simple/reliable: sort by stored meta keys.
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'afso_videos') return;

    $orderby = $query->get('orderby');

    if ($orderby === 'afso_sequence') {
        $query->set('meta_key', 'afso_sequence');
        $query->set('orderby', 'meta_value_num');
    }

    if ($orderby === 'afso_date_filmed') {
        $query->set('meta_key', 'afso_date_filmed');
        $query->set('orderby', 'meta_value');
    }
});