<?php

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=afso_videos',
        'Import AFSO CSV',
        'Import CSV',
        'manage_options',
        'afso-import-csv',
        'afso_render_csv_import_page'
    );
});

function afso_render_csv_import_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }

    $result = null;

    if (!empty($_POST['afso_import_csv_submit'])) {
        check_admin_referer('afso_import_csv_action', 'afso_import_csv_nonce');

        if (!isset($_FILES['afso_csv_file']) || empty($_FILES['afso_csv_file']['tmp_name'])) {
            $result = [
                'ok' => false,
                'message' => 'No CSV file uploaded.',
                'stats' => [],
                'errors' => [],
            ];
        } else {
            $result = afso_process_csv_import($_FILES['afso_csv_file']['tmp_name']);
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Import AFSO Videos from CSV</h1>';
    echo '<p><strong>Expected core columns:</strong> afso_sequence, post_title, county, afso_location, afso_time, afso_date, afso_latitude, afso_longitude, afso_video_url, menu_order, post_status</p>';
    echo '<p><em>Notes:</em> menu_order defaults to afso_sequence if empty. afso_date_filmed is auto-built from afso_date + afso_time.</p>';

    if (is_array($result)) {
        if (!empty($result['ok'])) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }

        if (!empty($result['stats'])) {
            echo '<ul>';
            foreach ($result['stats'] as $k => $v) {
                echo '<li><strong>' . esc_html($k) . ':</strong> ' . esc_html((string) $v) . '</li>';
            }
            echo '</ul>';
        }

        if (!empty($result['errors'])) {
            echo '<h2>Row errors</h2><ol>';
            foreach ($result['errors'] as $err) {
                echo '<li>' . esc_html($err) . '</li>';
            }
            echo '</ol>';
        }
    }

    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('afso_import_csv_action', 'afso_import_csv_nonce');
    echo '<input type="file" name="afso_csv_file" accept=".csv,text/csv" required /> ';
    echo '<button type="submit" name="afso_import_csv_submit" class="button button-primary">Import CSV</button>';
    echo '</form>';
    echo '</div>';
}

function afso_process_csv_import($tmp_path) {
    $handle = fopen($tmp_path, 'r');
    if (!$handle) {
        return [
            'ok' => false,
            'message' => 'Could not open uploaded file.',
            'stats' => [],
            'errors' => [],
        ];
    }

    $header = fgetcsv($handle);
    if (!$header || !is_array($header)) {
        fclose($handle);
        return [
            'ok' => false,
            'message' => 'CSV appears empty or invalid.',
            'stats' => [],
            'errors' => [],
        ];
    }

    $normalized_header = array_map('afso_normalize_header', $header);

    $stats = [
        'Rows processed' => 0,
        'Posts created' => 0,
        'Rows skipped' => 0,
    ];
    $errors = [];

    $row_num = 1; // header row
    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        $stats['Rows processed']++;

        $row = array_pad($row, count($normalized_header), '');
        $data = array_combine($normalized_header, $row);

        if (!$data || afso_row_is_empty($data)) {
            $stats['Rows skipped']++;
            continue;
        }

        $mapped = afso_map_csv_row($data);
        $validation = afso_validate_mapped_row($mapped);

        if (!empty($validation)) {
            $errors[] = 'Row ' . $row_num . ': ' . implode('; ', $validation);
            $stats['Rows skipped']++;
            continue;
        }

        $inserted = afso_insert_video_from_row($mapped, $row_num, $errors);
        if ($inserted) {
            $stats['Posts created']++;
        } else {
            $stats['Rows skipped']++;
        }
    }

    fclose($handle);

    return [
        'ok' => true,
        'message' => 'Import complete.',
        'stats' => $stats,
        'errors' => $errors,
    ];
}

function afso_normalize_header($h) {
    $h = trim((string) $h);
    $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // UTF-8 BOM
    $h = strtolower($h);
    $h = preg_replace('/[^a-z0-9_]/', '', $h);
    return $h;
}

function afso_row_is_empty($data) {
    foreach ($data as $v) {
        if (trim((string) $v) !== '') return false;
    }
    return true;
}

function afso_map_csv_row($d) {
    $sequence = afso_pick($d, ['afso_sequence', 'sequence']);
    $menu_order = afso_pick($d, ['menu_order', 'post_menu_order']);
    if ($menu_order === '' && $sequence !== '') {
        $menu_order = $sequence;
    }

    $status = strtolower(trim((string) afso_pick($d, ['post_status', 'status'])));
    if ($status === '' || $status === 'published') {
        $status = 'publish';
    }

    $date = trim((string) afso_pick($d, ['afso_date', 'date']));
    $time = trim((string) afso_pick($d, ['afso_time', 'time']));
    $date_filmed = afso_build_date_filmed($date, $time);

    $video_raw = trim((string) afso_pick($d, ['afso_video_url', 'video_url', 'video_id']));
    $video = afso_extract_youtube_id($video_raw);

    $title = trim((string) afso_pick($d, ['post_title', 'title']));
    if ($title === '' && $sequence !== '') {
        $title = 'A few seconds of… #' . $sequence;
    }

    return [
        'post_title' => $title,
        'post_status' => $status,
        'post_content' => trim((string) afso_pick($d, ['post_content', 'content'])),
        'menu_order' => (int) $menu_order,
        'county' => trim((string) afso_pick($d, ['county'])),
        'afso_sequence' => trim((string) $sequence),
        'afso_location' => trim((string) afso_pick($d, ['afso_location', 'location'])),
        'afso_time' => $time,
        'afso_date' => $date,
        'afso_date_filmed' => $date_filmed,
        'afso_latitude' => trim((string) afso_pick($d, ['afso_latitude', 'latitude', 'lat'])),
        'afso_longitude' => trim((string) afso_pick($d, ['afso_longitude', 'longitude', 'lng'])),
        'afso_video_url' => $video,
    ];
}

function afso_pick($d, $keys) {
    foreach ($keys as $k) {
        if (isset($d[$k]) && trim((string) $d[$k]) !== '') {
            return trim((string) $d[$k]);
        }
    }
    return '';
}

function afso_validate_mapped_row($m) {
    $errors = [];

    if ($m['post_title'] === '') {
        $errors[] = 'Missing title (or afso_sequence for fallback title)';
    }
    if ($m['county'] === '') {
        $errors[] = 'Missing county';
    }
    if ($m['afso_video_url'] === '') {
        $errors[] = 'Missing afso_video_url / video_id';
    }

    if ($m['afso_latitude'] !== '' && !is_numeric($m['afso_latitude'])) {
        $errors[] = 'Latitude not numeric';
    }
    if ($m['afso_longitude'] !== '' && !is_numeric($m['afso_longitude'])) {
        $errors[] = 'Longitude not numeric';
    }

    return $errors;
}

function afso_insert_video_from_row($m, $row_num, &$errors) {
    $postarr = [
        'post_type' => 'afso_videos',
        'post_title' => $m['post_title'],
        'post_status' => $m['post_status'],
        'post_content' => $m['post_content'],
        'menu_order' => $m['menu_order'],
    ];

    $post_id = wp_insert_post($postarr, true);
    if (is_wp_error($post_id)) {
        $errors[] = 'Row ' . $row_num . ': wp_insert_post failed: ' . $post_id->get_error_message();
        return false;
    }

    update_post_meta($post_id, 'afso_sequence', $m['afso_sequence']);
    update_post_meta($post_id, 'afso_location', $m['afso_location']);
    update_post_meta($post_id, 'afso_time', $m['afso_time']);
    update_post_meta($post_id, 'afso_date', $m['afso_date']);
    update_post_meta($post_id, 'afso_date_filmed', $m['afso_date_filmed']);
    update_post_meta($post_id, 'afso_latitude', $m['afso_latitude']);
    update_post_meta($post_id, 'afso_longitude', $m['afso_longitude']);
    update_post_meta($post_id, 'afso_video_url', $m['afso_video_url']);

    if ($m['county'] !== '') {
        wp_set_object_terms($post_id, [$m['county']], 'county', false);
    }

    return true;
}

function afso_extract_youtube_id($value) {
    $value = trim((string) $value);
    if ($value === '') return '';

    // Spreadsheet-safe wrappers:
    // 1) leading apostrophe: '-abc123XYZ
    // 2) formula wrapper: ="-abc123XYZ"
    if (str_starts_with($value, "'")) {
        $value = ltrim($value, "'");
    }

    if (preg_match('/^=\s*"(.+)"$/', $value, $m)) {
        $value = trim($m[1]);
    }

    // Already looks like a raw YouTube ID (allow leading - or _)
    if (preg_match('/^[A-Za-z0-9_-]{8,20}$/', $value)) {
        return $value;
    }

    // youtu.be/<id>
    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{8,20})~', $value, $m)) {
        return $m[1];
    }

    // youtube.com/watch?v=<id>
    if (preg_match('~[?&]v=([A-Za-z0-9_-]{8,20})~', $value, $m)) {
        return $m[1];
    }

    // youtube.com/embed/<id>
    if (preg_match('~/embed/([A-Za-z0-9_-]{8,20})~', $value, $m)) {
        return $m[1];
    }

    return $value;
}

function afso_build_date_filmed($date, $time) {
    $date = trim((string) $date);
    $time = trim((string) $time);

    if ($date === '' && $time === '') return '';

    $candidate = trim($date . ' ' . str_replace('.', ':', strtolower($time)));

    $formats = [
        'd/m/Y g:ia',
        'd/m/Y H:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'd/m/Y',
        'Y-m-d',
    ];

    foreach ($formats as $f) {
        $dt = DateTime::createFromFormat($f, $candidate);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }

    $ts = strtotime($candidate);
    if ($ts !== false) {
        return date('Y-m-d H:i:s', $ts);
    }

    return '';
}