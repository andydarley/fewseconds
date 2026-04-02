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
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-video-alt3',
    ));

    register_taxonomy('county', 'afso_videos', array(
        'label' => 'County',
        'hierarchical' => true,
        'rewrite' => array('slug' => 'county'),
    ));
}
add_action('init', 'afso_register_post_type_and_taxonomy');
