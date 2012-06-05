<?php
/**
 * @package Sixhours
 */

get_header();
?>

	<div id="content" class="content">

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div class="navigation">
			<div class="alignleft"><?php previous_post_link('&laquo; %link') ?></div>
			<div class="alignright"><?php next_post_link('%link &raquo;') ?></div>
		</div>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
			<small><?php the_time( get_option( 'date_format' ) ); ?> <?php _e( 'by' , 'sixhours' ) ?> <?php the_author() ?></small>
			<h2 class="page_title"><?php the_title(); ?></h2>

			<div class="entry">
				<?php the_content('<p>' . __( 'Read the rest of this entry' , 'sixhours' ) . ' &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p class="clear"><strong>' . __( 'Pages:' , 'sixhours' ) . '</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

				<?php the_tags( '<p class="postmetadata clear">' . __( 'Tags:', 'sixhours' ) . ' ', ', ', '</p>'); ?>

				<p class="postmetadata clear">
					<?php _e( 'Posted in' , 'sixhours' ) ?> <?php the_category(', ') ?> | 
					<?php edit_post_link( __( 'Edit', 'sixhours' ) , '', ' | '); ?> 
					<?php comments_popup_link( __( 'No Comments' , 'sixhours' ) . ' &#187;', '1 ' . __( 'Comment' , 'sixhours' ) . ' &#187;', '% ' . __( 'Comments' , 'sixhours' ) . ' &#187;'); ?>
				</p>

			</div>
		</div>

	<?php comments_template(); ?>

	<?php endwhile; else: ?>

		<h2 class="page_title"><?php _e( 'Not Found' , 'sixhours' ) ?></h2>
		<p class="aligncenter"><?php _e( 'Sorry, no posts matched your criteria.', 'sixhours' ) ?></p>
		<?php get_search_form(); ?>

<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
