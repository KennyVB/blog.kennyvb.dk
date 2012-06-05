<?php
/**
 * @package Sixhours
 */

get_header();

?>

	<div id="content" class="content">

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
				<small><?php the_time( get_option( 'date_format' ) ); ?> <?php _e( 'by' , 'sixhours' ); ?> <?php the_author() ?></small><h2 class="page_title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to' , 'sixhours' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

				<div class="entry">
					<?php the_content( __( 'Read the rest of this entry' , 'sixhours' ) . ' &raquo;'); ?>
				</div>
				
				<?php wp_link_pages(array('before' => '<p class="clear"><strong>' . __( 'Pages:' , 'sixhours' ) . '</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

				<?php the_tags('<p class="postmetadata clear">' . __( 'Tags:' , 'sixhours' ) . ' ', ', ', '</p>'); ?>
				
				<p class="postmetadata clear">
					<a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to' , 'sixhours' ) ?> <?php the_title_attribute(); ?>"><?php _e( 'Permalink' , 'sixhours' ) ?></a> | 
					<?php _e( 'Posted in' , 'sixhours' ) ?> <?php the_category(', ') ?> | 
					<?php edit_post_link( __( 'Edit', 'sixhours' ), '', ' | ') ?> 
					<?php comments_popup_link( __( 'No Comments' , 'sixhours' ) . ' &#187;', '1 ' . __( 'Comment' , 'sixhours' ) . ' &#187;', '% ' . __( 'Comments' , 'sixhours' ) . ' &#187;'); ?>
				</p>
			</div>
            
            <div class="bullets">&nbsp; &bull; &nbsp; &bull; &nbsp; &bull; &nbsp; &bull; &nbsp; &bull;</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo;' . __( 'Older Entries' , 'sixhours' ) ) ?></div>
			<div class="alignright"><?php previous_posts_link( __( 'Newer Entries' , 'sixhours' ) . ' &raquo;') ?></div>
		</div>

	<?php else : ?>

		<h2 class="page_title"><?php _e( 'Not Found' , 'sixhours' ) ?></h2>
		<p class="aligncenter"><?php _e( 'Sorry, no posts matched your criteria.', 'sixhours' ) ?></p>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
