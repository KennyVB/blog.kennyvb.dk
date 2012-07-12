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
<div id="seneste_nyt">
 <?php
    $args=array(
      'post_type' => 'post',
      'post_status' => 'publish',
      'posts_per_page' => 1,
      'caller_get_posts'=> 1
      );
    $my_query = null;
    $my_query = new WP_Query($args);
    if( $my_query->have_posts() ) {
      echo '';
      while ($my_query->have_posts()) : $my_query->the_post(); ?>
      <li><h3><a class="seneste_post" href="<?php tumblrPostTitles(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3><h5 class="postdate"><?php the_time('d.m.y') ?></h5></li>
      <?php endwhile;
    }
wp_reset_query(); ?>
</div>
<?php get_footer(); ?>