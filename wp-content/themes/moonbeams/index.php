<?php if (!have_posts()) { 
header("HTTP/1.1 404 Not Found");
get_template_part( '404' );
return; } ?>
<?php get_header(); ?>
	<div id="main" class="clearfix">
		<div class="maincolumn">
		<?php if(have_posts()) : ?>

		<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 		<?php /* If this is a category archive */ if (is_category()) { ?>
			<h1><?php single_cat_title(); ?></h1>
 		<?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
			<h1><?php _e('Posts Tagged &#8216;', 'moonbeams'); ?><?php single_tag_title(); ?>&#8217;</h1>
		<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
			<h1><?php printf( __( 'Daily Archives: <span>%s</span>', 'moonbeams' ), get_the_date() ); ?></h1>
		<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
			<h1><?php printf( __( 'Monthly Archives: <span>%s</span>', 'moonbeams' ), get_the_date( __('F Y', 'moonbeams') ) ); ?></h1>
		<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
			<h1><?php printf( __( 'Yearly Archives: <span>%s</span>', 'moonbeams' ), get_the_date( __('Y', 'moonbeams') ) ); ?></h1>
		<?php /* If this is an author archive */ } elseif (is_author()) { ?>
			<h1><?php printf( __( 'Author Archives: %s', 'moonbeams' ), "<span class='vcard'><a class='url fn n' href='" . get_author_posts_url( get_the_author_meta( 'ID' ) ) . "' title='" . esc_attr( get_the_author() ) . "' rel='me'>" . get_the_author() . "</a></span>" ); ?></h1>
 	  	<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<h1><?php _e('Blog Archives', 'moonbeams'); ?></h1>
		<?php } ?>

		<?php while (have_posts()) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
		<?php $title = the_title( '' , '' , false ); if(!$title) { $title = the_date('','','',false); } ?>
			<h2 class="post_title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php echo $title; ?>"><?php echo $title; ?></a></h2>
			<?php the_post_thumbnail(); ?>
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