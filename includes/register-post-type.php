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
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-video-alt3',
    ));

    register_taxonomy('county', 'afso_videos', array(
        'label' => 'County',
        'hierarchical' => false, // per your latest decision
        'rewrite' => array('slug' => 'county'),
        'show_admin_column' => true,
        'show_in_rest' => true,
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

// Helpers for mixed legacy/new data.
function afso_get_sequence_for_admin($post_id, $post_title = '') {
    $sequence = get_post_meta($post_id, 'afso_sequence', true);

    if ($sequence !== '' && $sequence !== null) {
        return (string) $sequence;
    }

    if ($post_title === '') {
        $post_title = get_the_title($post_id);
    }

    // Fallback from title prefix: "210: ...", "210 - ...", etc.
    if (preg_match('/^\s*(\d+)\s*[:\-]/', $post_title, $m)) {
        return $m[1];
    }

    return '';
}

function afso_get_filmed_datetime_for_admin($post_id) {
    $raw = (string) get_post_meta($post_id, 'afso_date_filmed', true);
    if ($raw !== '') return $raw;

    $legacy_date = trim((string) get_post_meta($post_id, 'afso_date', true));
    $legacy_time = trim((string) get_post_meta($post_id, 'afso_time', true));

    if ($legacy_date === '' && $legacy_time === '') {
        return '';
    }

    $candidate = trim($legacy_date . ' ' . strtolower($legacy_time));

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

        // date-only fallback against date-only value
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

// Admin list columns: sequence first, then title/county/date filmed.
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

    // Keep county visible
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

// Sorting fix: include posts with missing meta instead of hiding them.
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'afso_videos') return;

    $orderby = $query->get('orderby');

    if ($orderby === 'afso_sequence') {
        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key' => 'afso_sequence',
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'afso_sequence',
                'compare' => 'NOT EXISTS',
            ],
        ]);
        $query->set('meta_key', 'afso_sequence');
        $query->set('orderby', 'meta_value_num title');
    }

    if ($orderby === 'afso_date_filmed') {
        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key' => 'afso_date_filmed',
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'afso_date_filmed',
                'compare' => 'NOT EXISTS',
            ],
        ]);
        $query->set('meta_key', 'afso_date_filmed');
        $query->set('orderby', 'meta_value title');
    }
});

/**
 * Editable AFSO metadata metabox.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'afso_metadata_edit_panel',
        'AFSO Metadata',
        'afso_render_metadata_edit_panel',
        'afso_videos',
        'normal',
        'high'
    );
});

function afso_render_metadata_edit_panel($post) {
    wp_nonce_field('afso_save_metadata_panel', 'afso_metadata_panel_nonce');

    $fields = [
        'afso_sequence'   => 'Sequence',
        'afso_date'       => 'Date (legacy, e.g. 05/03/2013)',
        'afso_time'       => 'Time (legacy, e.g. 3.18pm)',
        'afso_date_filmed'=> 'Date filmed (YYYY-mm-dd HH:ii:ss)',
        'afso_location'   => 'Location',
        'afso_latitude'   => 'Latitude',
        'afso_longitude'  => 'Longitude',
        'afso_video_url'  => 'YouTube ID / URL',
    ];

    echo '<table class="form-table"><tbody>';

    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr((string) $value) . '" class="regular-text" /></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

add_action('save_post_afso_videos', function ($post_id) {
    // Security + permissions
    if (!isset($_POST['afso_metadata_panel_nonce'])) return;
    if (!wp_verify_nonce($_POST['afso_metadata_panel_nonce'], 'afso_save_metadata_panel')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $keys = [
        'afso_sequence',
        'afso_date',
        'afso_time',
        'afso_date_filmed',
        'afso_location',
        'afso_latitude',
        'afso_longitude',
        'afso_video_url',
    ];

    foreach ($keys as $key) {
        if (!isset($_POST[$key])) continue;
        $raw = wp_unslash($_POST[$key]);
        $value = sanitize_text_field($raw);

        if ($value === '') {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }

    // Optional normalization: if afso_date_filmed blank but legacy date/time present, build it.
    $date_filmed = get_post_meta($post_id, 'afso_date_filmed', true);
    if ($date_filmed === '') {
        $legacy_date = trim((string) get_post_meta($post_id, 'afso_date', true));
        $legacy_time = trim((string) get_post_meta($post_id, 'afso_time', true));

        if ($legacy_date !== '' || $legacy_time !== '') {
            $candidate = trim($legacy_date . ' ' . $legacy_time);
            $ts = strtotime($candidate);
            if ($ts !== false) {
                update_post_meta($post_id, 'afso_date_filmed', date('Y-m-d H:i:s', $ts));
            }
        }
    }
});