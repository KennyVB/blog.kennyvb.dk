<?php
/**
Template Name: Blog
*/

get_header(); ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

											<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '' . __( 'Pages:', 'twentyten' ), 'after' => '' ) ); ?>
						<?php edit_post_link( __( 'Edit', 'twentyten' ), '', '' ); ?>


<?php endwhile; ?>

<div id="sidste_post">
    <h4>Sidste 5 indl&aelig;g</h4>
    <?php $recent = new WP_Query("showposts=5"); while($recent->have_posts()) : $recent->the_post();?>
<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<?php the_excerpt(); ?>
<?php endwhile; ?>
</div>
	 <div id="box1">
	
</div>
	 <div id="box2"></div>
	 <div id="box3"></div>
<div id="archives">
<div class="content">
	<p><?php wp_get_archives('type=postbypost'); ?></p>
</div>
<br>
<div id="catagories">
<?php // display categories in two columns
$cats = explode('<br />', wp_list_categories('title_li=&echo=0&depth=1&style=none'));
$cat_n = count($cats) - 1;
for ($i = 0; $i < $cat_n; $i++):
	if ($i < $cat_n/2):
		$cat_left = $cat_left.'<li>'.$cats[$i].'</li>';
	elseif ($i >= $cat_n/2):
		$cat_right = $cat_right.'<li>'.$cats[$i].'</li>';
	endif;
endfor; ?>

<ul class="left">
	<?php echo $cat_left; ?>
</ul>
<ul class="right">
	<?php echo $cat_right; ?>
</ul>
</div></div>


<?php get_sidebar(); ?>
<?php get_footer(); ?>
