<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Starkers
 * @since Starkers 3.0
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 * We filter the output of wp_title() a bit -- see
	 * twentyten_filter_wp_title() in functions.php.
	 */
	wp_title( '|', true, 'right' );

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<?php
	/* We add some JavaScript to pages with the comment form
	 * to support sites with threaded comments (when in use).
	 */
	if ( is_singular() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );

	/* Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();
?>
<script type="text/javascript" src="<?php bloginfo( 'template_url' ); ?>/js/kennyvb.js"></script>
<script type="text/javascript" src="<?php bloginfo( 'template_url' ); ?>/js/jquery.tweet.js"></script>
</head>

<body <?php body_class(); ?>>
<a href="/"><img src="<?php bloginfo( 'template_url' ); ?>/images/logo.png" alt="Kennyvb" id="logo"></a>
<div id="page-wrap">

<div id="navi">
	<ul id="nav">
        <?php wp_list_pages('title_li=&depth=0&sort_column=menu_order'); ?>
</ul>
	
</div>

		<div id="twitter">
			<h4>Twitter</h4>
			<h5>Sidste tweets</h5>
			<div class="tweet"></div>
			</div>
			<div id="random-photo">
			<?php
   if (function_exists('generateRandomImgTag'))
   {
      generateRandomImgTag();
   }
?>	
			</div>
			
			
	<div id="main-content">