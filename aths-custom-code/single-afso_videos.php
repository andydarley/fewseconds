<?php get_header(); 
 /*
	Template Name: AFSO
	Template Post Type: afso_videos
 */
 ?>

<div id="content">


    <?php
    $mypost = array( 'post_type' => 'afso_videos', );
    $loop = new WP_Query( $mypost );
    ?>
    <?php while ( $loop->have_posts() ) : $loop->the_post();?>

		<div class="post">
		
			<div class="postcontentdiv"><!-- google_ad_section_start -->			
            <header class="entry-header">
                <h2>A Few Seconds of: #<?php the_title(); ?></h2>
            </header>
            <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_video_url', true ) ); ?>
            <ul>

                <li><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_location', true ) ); ?></li>
                <li><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_date', true ) ); ?></li>
                <li><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_time', true ) ); ?></li>
                <li><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_longitude', true ) ); ?></li>
                <li><strong>Location:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'afso_latitude', true ) ); ?></li>

            </ul>
			</div>
			
			<div class="sidebarcontentdiv">
			<?php if(function_exists('the_tweetbutton')) the_tweetbutton();?>
			<div style="padding-bottom:12px;"><?php 
				do_action( 'mfields-plus-one-button', array(
    					'size'  => 'small',
    					'count' => 'true',
    					'url'   => 'http://www.andthenhesaid.com/',
    				) );
			?></div>
			<?php me_likey_button() ?>
			<script type="text/javascript"><!--
google_ad_client = "pub-3681765125542706";
/* 160x600, created 07/08/10 */
google_ad_slot = "9237066372";
google_ad_width = 160;
google_ad_height = 600;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
			</div>	
				
		</div>
<div class="clearing">&nbsp;</div>

			<?php edit_post_link('Edit this entry','<p>','.</p>'); ?>
	<?php endwhile; else: ?>

		<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

<?php wp_reset_query(); ?>

<div id="header">
	<div id="headerspace"><?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Header') ) : ?>
    <?php endif; ?></div>
	<h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
	<h2 title="Two-fingered typing and cheap photography, since November 2002">Two-fingered typing and cheap photography, since November 2002</h2>
	<?php do_action('wp_menubar','ATHS main'); ?> 	
	<?php if ( function_exists( 'breadcrumb_trail' ) ) { breadcrumb_trail(); } ?>
</div>

	
</div><!-- End of content div -->

<div id="wpsidebar">
			<h3>About this and other posts:</h3>
			<h4>The filing:</h4>
			<p>Categorised under <?php the_category(', ') ?>, tagged under <?php the_tags('') ?>.</p>
			<h4>Previous and next:</h4>
			<p style="width:45%;float:left;"><?php previous_post_link('&laquo;&nbsp;%link') ?></p>
			<p style="width:45%;float:right;text-align:right;"><?php next_post_link('%link&nbsp;&raquo;') ?></p>
			<div class="clearing">&nbsp;</div>
			<?php related_entries() ?>
</div><!-- End of wpsidebar div -->

<?php include (TEMPLATEPATH . '/offerstuff.php'); ?>

</div><!-- End of mainarea div -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>
