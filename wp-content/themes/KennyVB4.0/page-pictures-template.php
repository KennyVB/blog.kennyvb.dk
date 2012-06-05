<?php
/*
Template Name: Billeder
*/?>

<?php get_header(); ?>
<?php the_content(''); ?>
							
			<div class="gallery-include">
				<?php
					$mypath = get_post_meta($post->ID, 'galleryPath', true);
					$mypath .= "/1";
					include $_SERVER['DOCUMENT_ROOT']."/imgbrowz0r/index.php";
				?>
			</div>
<?php get_footer(); ?>