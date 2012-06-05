<?php if (!have_posts()) { 
header("HTTP/1.1 404 Not Found");
get_template_part( '404' );
return; } ?>
<?php get_header(); ?>
	<div id="main" class="clearfix">
		<div class="maincolumn">
<?php if ( have_posts() ) : ?>
			<h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'moonbeams' ), '<span>' . get_search_query() . '</span>' ); ?></h1>
		<?php get_search_form(); ?>
		<br />
		<?php while (have_posts()) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
			<h2 class="post_title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h2>
			<div class="content clearfix"><?php the_content( __('more &raquo;', 'moonbeams') ); ?></div>
		    <div class="link_pages"><?php wp_link_pages(); ?></div>
		</div><!--// post -->
		<?php endwhile; ?>
		<div class="nav-interior clearfix">
			<div class="nav-previous"><?php next_posts_link( __('&laquo; Older Entries', 'moonbeams') ) ?></div>
			<div class="nav-next"><?php previous_posts_link( __('Newer Entries &raquo;', 'moonbeams') ) ?></div>
		</div>
		<?php else : ?>
		<?php endif; ?>
		</div><!--// maincolumn -->
		<?php get_template_part('footer_menu') ?> 
	</div><!--// main -->
	<?php get_sidebar(); ?>
<?php get_footer(); ?>