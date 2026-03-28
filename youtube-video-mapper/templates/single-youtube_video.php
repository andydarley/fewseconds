<?php get_header(); $video_id = get_post_meta(get_the_ID(), 'video_id', true); $lat = get_post_meta(get_the_ID(), 'latitude', true); $lng = get_post_meta(get_the_ID(), 'longitude', true); $date = get_post_meta(get_the_ID(), 'date_filmed', true); ?>
<div class="yvm-container">
<h1><?php the_title(); ?></h1>
<iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" frameborder="0" allowfullscreen></iframe>
<p><strong>Date of Filming:</strong> <?php echo date('F j, Y, g:i a', strtotime($date)); ?></p>
<div class="yvm-description"><?php the_content(); ?></div>
<h3>Filming Location</h3>
<div id="video-map" style="height: 400px;"></div>
</div>
<?php get_footer(); ?>