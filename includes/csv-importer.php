<?php
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=afso_videos', 'Import AFSO CSV', 'Import CSV', 'manage_options', 'import-afso-csv', 'afso_import_csv_page');
});

function afso_import_csv_page() {
    if ($_FILES && isset($_FILES['csv_file'])) {
        $csv = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
        $headers = array_map('trim', $csv[0]);
        unset($csv[0]);

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            $datetime = date('Y-m-d H:i:s', strtotime($data['afso_date'] . ' ' . $data['afso_time']));
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data['post_title']),
                'post_type' => 'afso_videos',
                'post_status' => 'publish',
                'post_content' => sanitize_text_field($data['afso_location']),
            ]);
            if ($post_id) {
                update_post_meta($post_id, 'afso_video_url', sanitize_text_field($data['afso_video_url']));
                update_post_meta($post_id, 'afso_sequence', intval($data['afso_sequence']));
                update_post_meta($post_id, 'afso_latitude', $data['afso_latitude']);
                update_post_meta($post_id, 'afso_longitude', $data['afso_longitude']);
                update_post_meta($post_id, 'afso_date_filmed', $datetime);
                wp_set_post_terms($post_id, [$data['county']], 'county');
            }
        }
        echo '<div class="notice notice-success"><p>AFSO CSV imported successfully.</p></div>';
    }
    echo '<div class="wrap"><h1>Import AFSO Videos from CSV</h1><form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" required><input type="submit" class="button-primary" value="Import CSV"></form></div>';
}
