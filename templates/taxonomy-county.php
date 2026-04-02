<?php get_header(); $term = get_queried_object(); ?>
<div class='county-archive'>
<main class="wp-block-group alignwide" style="padding-top:2rem;padding-bottom:2rem;">
    <?php echo do_shortcode('[afso_term_map]'); ?>
</main>
</div>
<?php get_footer(); ?>