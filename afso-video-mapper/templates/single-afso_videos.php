<?php get_header(); $lat = get_post_meta(get_the_ID(), 'afso_latitude', true); $lng = get_post_meta(get_the_ID(), 'afso_longitude', true); $video_id = get_post_meta(get_the_ID(), 'afso_video_url', true); $date = get_post_meta(get_the_ID(), 'afso_date_filmed', true); ?>
<div class='afso-single'>
<h2><?php the_title(); ?></h2>
<iframe width='560' height='315' src='https://www.youtube-nocookie.com/embed/<?php echo esc_attr($video_id); ?>' frameborder='0' allowfullscreen></iframe>
<p><strong>Filming Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($date)); ?></p>
<div id='video-map'></div>
<script>document.addEventListener('DOMContentLoaded',function(){var map=L.map('video-map').setView([<?php echo esc_js($lat); ?>,<?php echo esc_js($lng); ?>],13);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);L.marker([<?php echo esc_js($lat); ?>,<?php echo esc_js($lng); ?>]).addTo(map);});</script>
</div>
<?php get_footer(); ?>