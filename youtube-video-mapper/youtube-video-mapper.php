<?php
/**
 * Plugin Name: YouTube Video Mapper
 * Description: Geolocates YouTube videos on a Leaflet map with clustering, CSV-based upload.
 * Version: 1.0
 * Author: Plugin Architect
 */

defined('ABSPATH') || exit;

define('YVM_PATH', plugin_dir_path(__FILE__));
define('YVM_URL', plugin_dir_url(__FILE__));

require_once YVM_PATH . 'includes/register-post-type.php';
require_once YVM_PATH . 'includes/enqueue-scripts.php';
require_once YVM_PATH . 'includes/csv-importer.php';
require_once YVM_PATH . 'includes/map-functions.php';
require_once YVM_PATH . 'includes/shortcode-functions.php';

add_filter('template_include', function ($template) {
    if (is_page('video-map')) {
        return YVM_PATH . 'templates/page-main-map.php';
    } elseif (is_tax('video_county')) {
        return YVM_PATH . 'templates/page-county-map.php';
    } elseif (get_post_type() == 'youtube_video') {
        return YVM_PATH . 'templates/single-youtube_video.php';
    }
    return $template;
});
