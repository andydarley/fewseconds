<?php
add_action('rest_api_init', function () {
    register_rest_route('yvm/v1', '/videos', [
        'methods' => 'GET',
        'callback' => function () {
            $posts = get_posts([
                'post_type' => 'youtube_video',
                'posts_per_page' => -1,
            ]);
            $data = [];

            foreach ($posts as $post) {
                $lat = get_post_meta($post->ID, 'latitude', true);
                $lng = get_post_meta($post->ID, 'longitude', true);
                if ($lat && $lng) {
                    $data[] = [
                        'title' => $post->post_title,
                        'content' => $post->post_content,
                        'date' => get_post_meta($post->ID, 'date_filmed', true),
                        'lat' => floatval($lat),
                        'lng' => floatval($lng),
                        'video_id' => get_post_meta($post->ID, 'video_id', true),
                        'link' => get_permalink($post),
                        'county' => wp_get_post_terms($post->ID, 'video_county', ['fields' => 'names'])[0] ?? '',
                    ];
                }
            }
            return rest_ensure_response($data);
        }
    ]);
});
