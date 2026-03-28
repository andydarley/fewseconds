<?php
function yvm_register_post_type() {
    register_post_type('youtube_video', [
        'labels' => [
            'name' => 'YouTube Videos',
            'singular_name' => 'YouTube Video',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'videos'],
        'supports' => ['title', 'editor', 'thumbnail'],
    ]);

    register_taxonomy('video_county', 'youtube_video', [
        'label' => 'Counties',
        'hierarchical' => true,
        'rewrite' => ['slug' => 'county'],
    ]);
}
add_action('init', 'yvm_register_post_type');
