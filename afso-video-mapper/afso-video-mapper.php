<?php
/**
 * Plugin Name: AFSO Video Mapper
 * Description: Displays geolocated YouTube videos with Leaflet maps, county taxonomy, and CSV import.
 * Version: 2.0
 * Author: WordPress Plugin AI
 */

defined('ABSPATH') || exit;

define('AFSO_PATH', plugin_dir_path(__FILE__));
define('AFSO_URL', plugin_dir_url(__FILE__));

require_once AFSO_PATH . 'includes/register-post-type.php';
require_once AFSO_PATH . 'includes/enqueue-scripts.php';
require_once AFSO_PATH . 'includes/csv-importer.php';
require_once AFSO_PATH . 'includes/rest-endpoints.php';

add_filter('template_include', function ($template) {
    if (is_tax('county')) {
        return AFSO_PATH . 'templates/taxonomy-county.php';
    }/* elseif (is_singular('afso_videos')) {
        return AFSO_PATH . 'templates/single-afso_videos.php';
    }*/
    return $template;
});
