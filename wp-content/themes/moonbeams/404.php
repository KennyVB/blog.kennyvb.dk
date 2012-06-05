<?php get_header(); ?>
	<div id="main">
		<div class="maincolumn">
		<div id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
			<h1><?php _e('Sorry! We coudn\'t find it.', 'moonbeams'); ?></h1>
			<div class="content">
			<p><?php _e('We are sorry, the object you requested was not found on this server.', 'moonbeams'); ?></p>
			</div><!--// content -->
			</div><!--// post -->
		</div><!--// maincolumn -->
		<?php get_template_part('footer_menu') ?> 
	</div><!--// main -->
	<?php get_sidebar(); ?>
<?php get_footer(); ?>