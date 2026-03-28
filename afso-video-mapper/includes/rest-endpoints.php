<?php
add_action('rest_api_init', function () {
    register_rest_route('afso/v1', '/videos', [
        'methods' => 'GET',
        'callback' => function () {
            $posts = get_posts(['post_type' => 'afso_videos', 'posts_per_page' => -1]);
            $data = [];
            foreach ($posts as $post) {
                $lat = get_post_meta($post->ID, 'afso_latitude', true);
                $lng = get_post_meta($post->ID, 'afso_longitude', true);
                $vid = get_post_meta($post->ID, 'afso_video_url', true);
                $county = wp_get_post_terms($post->ID, 'county', ['fields' => 'names'])[0] ?? '';
                $data[] = [
                    'title' => get_the_title($post),
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'video_id' => $vid,
                    'content' => $post->post_content,
                    'link' => get_permalink($post),
                    'date' => get_post_meta($post->ID, 'afso_date_filmed', true),
                    'county' => $county,
                ];
            }
            return rest_ensure_response($data);
        }
    ]);
});
