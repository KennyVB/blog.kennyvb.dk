<?php
/**
Template Name: Blog
*/

get_header(); ?>
<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

											<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '' . __( 'Pages:', 'twentyten' ), 'after' => '' ) ); ?>
						


<?php endwhile; ?>

<div id="sidste_post">
    <h4>Sidste 5 indl&aelig;g</h4>
    <?php $recent = new WP_Query("showposts=5"); while($recent->have_posts()) : $recent->the_post();?>
<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<?php the_excerpt(); ?>
<?php endwhile; ?>
</div>
	 <div id="box1"></div>
	 <div id="box2"></div>
	 <div id="box3"><?php edit_post_link( __( 'Edit', 'twentyten' ), '', '' ); ?></div>
<div id="archives">
<h4>Alle Indl&aelig;g :</h4><br>
<div class="content">
	<?php wp_get_archives('type=postbypost'); ?>
</div>
<br>
</div>


<?php get_sidebar(); ?>
<?php get_footer(); ?>
