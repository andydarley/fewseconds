<?php
/**
 * Plugin Name: A Few Seconds Of (Simple)
 * Description: Manually entered video posts displayed on a Leaflet map with region taxonomy.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Register post type and taxonomy
add_action('init', function() {
    register_post_type('fewseconds_video', [
        'label' => 'Videos',
        'public' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'video'],
    ]);

    register_taxonomy('fewseconds_county', 'fewseconds_video', [
        'label' => 'County',
        'hierarchical' => false,
        'rewrite' => ['slug' => 'county'],
    ]);
});

// Meta box
add_action('add_meta_boxes', function() {
    add_meta_box('fewseconds_fields', 'Video Fields', function($post) {
        $fields = ['latitude', 'longitude', 'youtube_url', 'video_id'];
        foreach ($fields as $f) $$f = get_post_meta($post->ID, "fewseconds_$f", true);
        ?>
        <p><label>Latitude: <input type="text" name="fewseconds_latitude" value="<?= esc_attr($latitude) ?>"></label></p>
        <p><label>Longitude: <input type="text" name="fewseconds_longitude" value="<?= esc_attr($longitude) ?>"></label></p>
        <p><label>YouTube URL: <input type="text" name="fewseconds_youtube_url" value="<?= esc_attr($youtube_url) ?>"></label></p>
        <p><label>ID Number (3 digits): <input type="text" name="fewseconds_video_id" value="<?= esc_attr($video_id) ?>" maxlength="3"></label></p>
        <?php
    }, 'fewseconds_video');
});

// Save meta
add_action('save_post', function($post_id) {
    foreach (['latitude', 'longitude', 'youtube_url', 'video_id'] as $f) {
        if (isset($_POST["fewseconds_$f"])) {
            update_post_meta($post_id, "fewseconds_$f", sanitize_text_field($_POST["fewseconds_$f"]));
        }
    }
});

// Enqueue Leaflet
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
});

// Utility: get video post meta
function fewseconds_get_video($post_id) {
    return [
        'title' => get_the_title($post_id),
        'lat' => get_post_meta($post_id, 'fewseconds_latitude', true),
        'lng' => get_post_meta($post_id, 'fewseconds_longitude', true),
        'url' => get_post_meta($post_id, 'fewseconds_youtube_url', true),
        'id' => get_post_meta($post_id, 'fewseconds_video_id', true),
        'county' => get_the_terms($post_id, 'fewseconds_county')[0]->name ?? '',
        'link' => get_permalink($post_id)
    ];
}

// Shortcode: [fewseconds_full_map]
add_shortcode('fewseconds_full_map', function() {
    $posts = get_posts(['post_type' => 'fewseconds_video', 'posts_per_page' => -1]);
    $videos = array_map('fewseconds_get_video', wp_list_pluck($posts, 'ID'));

    ob_start(); ?>
    <div id="fewseconds-map" style="height: 500px;"></div>
    <ul>
    <?php
    foreach (get_terms(['taxonomy' => 'fewseconds_county']) as $term) {
        echo '<li><a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a></li>';
    }
    ?>
    </ul>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const map = L.map('fewseconds-map').setView([54.5, -3], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        const videos = <?php echo json_encode($videos); ?>;
        videos.forEach(v => {
            const icon = L.icon({
                iconUrl: '<?php echo plugin_dir_url(__FILE__); ?>video-' + v.county.toLowerCase().replace(/\s+/g, '-') + '.png',
                iconSize: [32, 37],
                iconAnchor: [16, 37]
            });
            const marker = L.marker([v.lat, v.lng], { icon }).addTo(map);
            const embed = v.url.replace('watch?v=', 'embed/');
            marker.bindPopup(`<iframe width='300' height='169' src='${embed}'></iframe><p><a href='${v.link}'>${v.title}</a></p>`);
        });
    });
    </script>
    <?php return ob_get_clean();
});
