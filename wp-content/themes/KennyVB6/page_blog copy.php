<?php
/**
Template Name: Blog2
*/

get_header(); ?>

<table id="archives-table">
	<tr>
		<?php query_posts('posts_per_page=-1&cat=-52'); ?>
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<td>
			<a href="<?php tumblrPostTitles(); ?>" class="article-block">
				<span class="title"><?php the_title(); ?></span>
				<img src="<?php echo get_post_meta($post->ID, 'PostThumb', true); ?>" alt="" />
				<span class="ex"><?php the_excerpt(); ?></span>
			</a>
		</td>
		<?php endwhile; endif; ?>
	</tr>
</table>
<?php get_sidebar(); ?>
<?php get_footer(); ?>