<?php
/*
Template Name: Page of Posts
*/
get_header(); 
?>

<?php while( have_posts() ): the_post(); /* start main loop */ ?>

    <h1><?php the_title(); ?></h1>

    <?php
        /* Start Secondary Loop */
        $other_posts = new WP_Query( /*maybe some args here? */ );
        while( $others_posts->have_posts() ): $other_posts->the_post(); 
    ?>
        You can do anything you would in the main loop here and it will
        apply to the secondary loop's posts
    <?php 
        endwhile; /* end secondary loop */ 
        wp_reset_postdata(); /* Restore the original queried page to the $post variable */
    ?>

<?php endwhile; /* End the main loop */ ?>
