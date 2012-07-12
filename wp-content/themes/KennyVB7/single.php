<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Starkers
 * @since Starkers 3.0
 */

get_header(); ?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

<?php previous_post_link('<div class="alignleft">Tidligere Indl&aelig;g: %link</div>', '%title'); ?>
	<?php if(!get_adjacent_post(false, '', true)) { 
		echo '<div class="alignleft"><a href="http://perishablepress.com/press/archives/">Site Archives</a></div>'; 
	} ?>

	<?php next_post_link('<div class="alignright">N&aelig;ste indl&aelig;g   : %link</div>', '%title'); ?>
	
<div id="box1">
	</div>
<div id="box2"></div>
<div id="box3"><?php edit_post_link( __( 'Edit', 'twentyten' ), '', '' ); ?></div>
					<div id="title_h1"><h1><?php the_title(); ?></h1></div>

						<div id="posted_on"><?php twentyten_posted_on(); ?></div>

						<div id="the_content"><?php the_content(); ?></div>
						

				<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>