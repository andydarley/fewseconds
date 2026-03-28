<?php get_header(); $county = get_queried_object(); ?>
<div class="yvm-container">
<h1>Videos from <?php echo esc_html($county->name); ?></h1>
<div id="video-map" style="height: 600px;" data-county="<?php echo esc_attr($county->name); ?>"></div>
</div>
<?php get_footer(); ?>