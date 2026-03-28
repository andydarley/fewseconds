<?php
function afso_enqueue_leaflet_assets() {
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], null, true);

    wp_enqueue_script('leaflet-cluster', 'https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js', ['leaflet-js'], null, true);
    wp_enqueue_style('leaflet-cluster-css', 'https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css');

    wp_enqueue_script('afso-map', AFSO_URL . 'assets/js/map.js', ['leaflet-js', 'leaflet-cluster'], null, true);
    wp_enqueue_style('afso-style', AFSO_URL . 'assets/css/map-style.css');
}
add_action('wp_enqueue_scripts', 'afso_enqueue_leaflet_assets');
