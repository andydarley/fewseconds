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
        'hierarchical' => false,
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
        'hierarchical' => false,
        'show_count' => false,
        'hide_empty' => false,
        'value_field' => 'slug',
    ]);
});

// Helper: sequence comes ONLY from afso_sequence meta.
function afso_get_sequence_for_admin($post_id) {
    $sequence = get_post_meta($post_id, 'afso_sequence', true);
    if ($sequence === '' || $sequence === null) {
        return '';
    }
    return (string) $sequence;
}

// Admin list columns: sequence first, title, county, filming date.
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

    // Keep date display simple and from legacy afso_date.
    $new_columns['afso_date'] = 'Filming date';

    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }

    return $new_columns;
});

add_action('manage_afso_videos_posts_custom_column', function ($column, $post_id) {
    if ($column === 'afso_sequence') {
        $sequence = afso_get_sequence_for_admin($post_id);
        echo $sequence === '' ? '—' : esc_html($sequence);
        return;
    }

    if ($column === 'afso_date') {
        $date = trim((string) get_post_meta($post_id, 'afso_date', true));
        echo $date === '' ? '—' : esc_html($date);
        return;
    }
}, 10, 2);

// Only sequence is sortable from custom meta columns.
add_filter('manage_edit-afso_videos_sortable_columns', function ($columns) {
    $columns['afso_sequence'] = 'afso_sequence';
    return $columns;
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'afso_videos') return;

    $orderby = $query->get('orderby');

    if ($orderby === 'afso_sequence') {
        $query->set('meta_query', [
            'relation' => 'OR',
            ['key' => 'afso_sequence', 'compare' => 'EXISTS'],
            ['key' => 'afso_sequence', 'compare' => 'NOT EXISTS'],
        ]);
        $query->set('meta_key', 'afso_sequence');
        $query->set('orderby', 'meta_value_num');
    }
});

/**
 * Editable AFSO metadata metabox.
 * Uses afso_date + afso_time directly. No afso_date_filmed creation.
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
        'afso_date'       => 'Date (e.g. 05/03/2013)',
        'afso_time'       => 'Time (e.g. 3.18pm)',
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
    if (!isset($_POST['afso_metadata_panel_nonce'])) return;
    if (!wp_verify_nonce($_POST['afso_metadata_panel_nonce'], 'afso_save_metadata_panel')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $keys = [
        'afso_sequence',
        'afso_date',
        'afso_time',
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
});