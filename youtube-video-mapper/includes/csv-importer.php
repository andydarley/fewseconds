<?php
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=youtube_video', 'Import CSV', 'Import CSV', 'manage_options', 'import-csv', 'yvm_csv_import_page');
});

function yvm_csv_import_page() {
    if ($_FILES && isset($_FILES['csv_file'])) {
        $csv = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
        $headers = array_map('trim', $csv[0]);
        unset($csv[0]);

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data['title']),
                'post_type' => 'youtube_video',
                'post_status' => 'publish',
                'post_content' => sanitize_textarea_field($data['description']),
            ]);
            if ($post_id) {
                update_post_meta($post_id, 'video_id', sanitize_text_field($data['video_id']));
                update_post_meta($post_id, 'latitude', floatval($data['latitude']));
                update_post_meta($post_id, 'longitude', floatval($data['longitude']));
                update_post_meta($post_id, 'date_filmed', sanitize_text_field($data['date_filmed']));
                wp_set_post_terms($post_id, [$data['county']], 'video_county');
            }
        }
        echo '<div class="notice notice-success"><p>Videos imported successfully!</p></div>';
    }

    echo '<div class="wrap"><h1>Import YouTube Videos CSV</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csv_file" required>';
    echo '<input type="submit" class="button-primary" value="Import">';
    echo '</form></div>';
}
