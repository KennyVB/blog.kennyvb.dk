<?php
/**
Template Name: Forside
*/

get_header(); ?>


<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

											<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '' . __( 'Pages:', 'twentyten' ), 'after' => '' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'twentyten' ), '', '' ); ?>


<?php endwhile; ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>