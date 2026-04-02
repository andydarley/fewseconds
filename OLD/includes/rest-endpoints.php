<?php
add_action('rest_api_init', function () {
    register_rest_route('afso/v1', '/videos', [
        'methods' => 'GET',
        'callback' => function () {
            $posts = get_posts([
                'post_type' => 'afso_videos',
                'posts_per_page' => -1
            ]);

            $data = [];

            foreach ($posts as $post) {
                $lat = get_post_meta($post->ID, 'afso_latitude', true);
                $lng = get_post_meta($post->ID, 'afso_longitude', true);
                $vid = get_post_meta($post->ID, 'afso_video_url', true);

                $county_terms = wp_get_post_terms($post->ID, 'county');
                $county = '';
                $county_slug = '';
                if (!is_wp_error($county_terms) && !empty($county_terms)) {
                    $county = $county_terms[0]->name;
                    $county_slug = $county_terms[0]->slug;
                }

                $icon_url = '';
                if ($county_slug !== '') {
                    $icon_rel_path = 'assets/icons/county/video-' . $county_slug . '.png';
                    if (file_exists(AFSO_PATH . $icon_rel_path)) {
                        $icon_url = AFSO_URL . $icon_rel_path;
                    }
                }

                // Preferred modern field
                $date_value = get_post_meta($post->ID, 'afso_date_filmed', true);

                // Legacy fallback: afso_date + afso_time
                if ($date_value === '') {
                    $legacy_date = trim((string) get_post_meta($post->ID, 'afso_date', true)); // e.g. 05/03/2013
                    $legacy_time = trim((string) get_post_meta($post->ID, 'afso_time', true)); // e.g. 3.18pm

                    if ($legacy_date !== '' && $legacy_time !== '') {
                        $candidate = $legacy_date . ' ' . strtolower($legacy_time);

                        $dt = DateTime::createFromFormat('d/m/Y g.ia', $candidate);
                        if (!$dt) $dt = DateTime::createFromFormat('d/m/Y g:ia', $candidate);
                        if (!$dt) $dt = DateTime::createFromFormat('d/m/Y H:i', $candidate);

                        if ($dt instanceof DateTime) {
                            $date_value = $dt->format('Y-m-d H:i:s');
                        }
                    }

                    // Date-only fallback
                    if ($date_value === '' && $legacy_date !== '') {
                        $dt = DateTime::createFromFormat('d/m/Y', $legacy_date);
                        if ($dt instanceof DateTime) {
                            $date_value = $dt->format('Y-m-d 00:00:00');
                        }
                    }
                }

                $data[] = [
                    'id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'video_id' => $vid,
                    'content' => $post->post_content,
                    'link' => get_permalink($post),
                    'date' => $date_value,
                    'county' => $county,
                    'county_slug' => $county_slug,
                    'icon_url' => $icon_url,
                ];
            }

            return rest_ensure_response($data);
        }
    ]);
});