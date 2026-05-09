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

                $date = trim((string) get_post_meta($post->ID, 'afso_date', true));
                $time = trim((string) get_post_meta($post->ID, 'afso_time', true));

                $data[] = [
                    'id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'video_id' => $vid,
                    'content' => $post->post_content,
                    'link' => get_permalink($post),
                    'date' => $date,
                    'time' => $time,
                    'county' => $county,
                    'county_slug' => $county_slug,
                    'icon_url' => $icon_url,
                ];
            }

            return rest_ensure_response($data);
        }
    ]);
});