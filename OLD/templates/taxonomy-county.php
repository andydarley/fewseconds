<?php get_header(); $term = get_queried_object(); ?>
<div class='county-archive'>
<h2>AFSO Videos in <?php echo esc_html($term->name); ?></h2>
<div class="afso-video-map" data-county-slug="<?php echo esc_attr($term->slug); ?>" style="height:600px"></div>
</div>
<?php get_footer(); ?>