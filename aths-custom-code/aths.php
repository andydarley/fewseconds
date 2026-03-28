<?php
/*
Plugin Name: ATHS custom code
Plugin URI: http://www.andthenhesaid.com/
Description: Declares a plugin for any extra stuff that shouldn't be in the theme.
Version: 1.0
Author: Andy Darley
Author URI: http://www.andydarley.com/
License: Why bother? It's not original stuff for the most part.
*/

function afso_taxonomy() {

    register_taxonomy(
        'county',
        'afso_videos',
        array(
            'label' => __( 'County' ),
            'rewrite' => array( 'slug' => 'county' ),
            'hierarchical' => true,
        )
    );
}
add_action( 'init', 'afso_taxonomy' );

function few_seconds_of() {
    register_post_type( 'afso_videos',
        array(
            'labels' => array(
                'name' => 'A Few Seconds Of...',
                'singular_name' => 'AFSO Video',
                'add_new' => 'Add New',
                'add_new_item' => 'Add new video',
                'edit' => 'Edit',
                'edit_item' => 'Edit video details',
                'new_item' => 'New video',
                'view' => 'View',
                'view_item' => 'View video',
                'search_items' => 'Search videos',
                'not_found' => 'No videos found',
                'not_found_in_trash' => 'No videos found in trash',
                'parent' => 'Parent video',
                'menu_name' => 'AFSof...'
            ),

            'public' => true,
            'description' => 'A project to shoot short videos capturing fleeting moments in time. Often, nothing much is happening – running water, a bit of traffic, some snow – but I hope together these give a sense of the world rolling by in all its beauty, ugliness and banality.',
            'menu_position' => 20,
			'show_in_rest' => true,
            'hierarchical' => true,
            'supports' => array( 'title', 'revisions', 'thumbnail', 'page-attributes' ),
            'taxonomies' => array( 'county' ),
            'menu_icon' => 'dashicons-video-alt',
            'has_archive' => true
        )
    );
}

add_action( 'init', 'few_seconds_of' );

function my_admin() {
    add_meta_box( 'afso_place_and_time_meta_box',
        'Place and time details',
        'display_afso_place_and_time_meta_box',
        'afso_videos', 'normal', 'high'
    );
    add_meta_box( 'afso_map_reference_meta_box',
        'Longitude and latitude',
        'display_afso_map_reference_meta_box',
        'afso_videos', 'normal', 'high'
    );
    add_meta_box( 'afso_video_location_meta_box',
        'URL of video',
        'display_afso_video_location_meta_box',
        'afso_videos', 'normal', 'high'
    );
}

add_action( 'admin_init', 'my_admin' );

function display_afso_place_and_time_meta_box( $afso_place_and_time ) {
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
    $afso_sequence = esc_html( get_post_meta( $afso_place_and_time->ID, 'afso_sequence', true ) );
    $afso_location = esc_html( get_post_meta( $afso_place_and_time->ID, 'afso_location', true ) );
    $afso_date = esc_html( get_post_meta( $afso_place_and_time->ID, 'afso_date', true ) );
    $afso_time = esc_html( get_post_meta( $afso_place_and_time->ID, 'afso_time', true ) );
    ?>
    <table style="width: 100%">
        <tr>
            <td style="width: 20%">Number</td>
            <td style="width: 80%"><input type="text" name="afso_sequence_name" value="<?php echo $afso_sequence; ?>" /></td>
        </tr>
        <tr>
            <td style="width: 20%">Location</td>
            <td style="width: 80%"><input type="text" name="afso_location_name" value="<?php echo $afso_location; ?>" /></td>
        </tr>
        <tr>
            <td>Date</td>
            <td>
                <script>
                    jQuery(document).ready(function(){
                        jQuery('#afso_video_date').datepicker({
                            dateFormat : 'M d yy'
                        });
                    });
                </script>
                <input type="text" name="afso_date_name" id="afso_video_date" value="<?php echo $afso_date; ?>" />
            </td>
        </tr>
        <tr>
            <td>Time</td>
            <td><input type="text" name="afso_time_name" value="<?php echo $afso_time; ?>" /></td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post', 'add_afso_place_and_time_fields', 10, 2 );

function display_afso_map_reference_meta_box( $afso_map_reference ) {
    $afso_longitude = esc_html( get_post_meta( $afso_map_reference->ID, 'afso_longitude', true ) );
    $afso_latitude = esc_html( get_post_meta( $afso_map_reference->ID, 'afso_latitude', true ) );
    ?>
    <table style="width: 100%">
        <tr>
            <td style="width: 20%">Longitude</td>
            <td style="width: 80%"><input type="text" name="afso_longitude_name" value="<?php echo $afso_longitude; ?>" /></td>
        </tr>
        <tr>
            <td>Latitude</td>
            <td><input type="text" name="afso_latitude_name" value="<?php echo $afso_latitude; ?>" /></td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post', 'add_afso_map_reference_fields', 10, 2 );

function display_afso_video_location_meta_box( $afso_video_location ) {
    $afso_video_url = esc_html( get_post_meta( $afso_video_location->ID, 'afso_video_url', true ) );
    ?>
    <table style="width: 100%">
        <tr>
            <td style="width: 20%">Video URL</td>
            <td style="width: 80%">https://youtu.be/<input type="text" name="afso_video_url_name" value="<?php echo $afso_video_url; ?>" /></td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post', 'add_afso_video_location_fields', 10, 2 );

function add_afso_place_and_time_fields( $afso_place_and_time_id, $afso_place_and_time ) {
    if ( $afso_place_and_time->post_type == 'afso_videos' ) {
        if ( isset( $_POST['afso_sequence_name'] ) && $_POST['afso_sequence_name'] != '' ) {
            update_post_meta( $afso_place_and_time_id, 'afso_sequence', $_POST['afso_sequence_name'] );
        }
        if ( isset( $_POST['afso_location_name'] ) && $_POST['afso_location_name'] != '' ) {
            update_post_meta( $afso_place_and_time_id, 'afso_location', $_POST['afso_location_name'] );
        }
        if ( isset( $_POST['afso_date_name'] ) && $_POST['afso_date_name'] != '' ) {
            update_post_meta( $afso_place_and_time_id, 'afso_date', $_POST['afso_date_name'] );
        }
        if ( isset( $_POST['afso_time_name'] ) && $_POST['afso_time_name'] != '' ) {
            update_post_meta( $afso_place_and_time_id, 'afso_time', $_POST['afso_time_name'] );
        }
    }
}

function add_afso_map_reference_fields( $afso_map_reference_id, $afso_map_reference ) {
    if ( $afso_map_reference->post_type == 'afso_videos' ) {
        if ( isset( $_POST['afso_longitude_name'] ) && $_POST['afso_longitude_name'] != '' ) {
            update_post_meta( $afso_map_reference_id, 'afso_longitude', $_POST['afso_longitude_name'] );
        }
        if ( isset( $_POST['afso_latitude_name'] ) && $_POST['afso_latitude_name'] != '' ) {
            update_post_meta( $afso_map_reference_id, 'afso_latitude', $_POST['afso_latitude_name'] );
        }
    }
}

function add_afso_video_location_fields( $afso_video_location_id, $afso_video_location ) {
    if ( $afso_video_location->post_type == 'afso_videos' ) {
        if ( isset( $_POST['afso_video_url_name'] ) && $_POST['afso_video_url_name'] != '' ) {
            update_post_meta( $afso_video_location_id, 'afso_video_url', $_POST['afso_video_url_name'] );
        }
    }
}
?>