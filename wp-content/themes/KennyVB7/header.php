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
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,400italic,700,300|Tangerine' rel='stylesheet' type='text/css'>
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

	<div id="main-wrap">
    <div id="header">
        <div class="search-form">
                <?php $search_text = "S&oslash;g"; ?> 
        <form method="get" id="searchform"  
        action="<?php bloginfo('home'); ?>/"> 
        <input type="text" value="<?php echo $search_text; ?>"  
        name="s" id="s"  
        onblur="if (this.value == '')  
        {this.value = '<?php echo $search_text; ?>';}"  
        onfocus="if (this.value == '<?php echo $search_text; ?>')  
        {this.value = '';}" /> 
        <input type="hidden" id="searchsubmit" /> 
        </form>
                </div>
                <a href="/"><img src="<?php bloginfo( 'template_url' ); ?>/images/logo.png" alt="Kennyvb" id="logo-name"></a>
        <img src="<?php bloginfo( 'template_url' ); ?>/images/string.png" alt="Kennyvb" id="stringbig">
		<div id="navi">
            <ul id="nav">
                <?php wp_list_pages('title_li=&depth=0&sort_column=menu_order'); ?>
        </ul>
        </div>
        </div>
	 <div id="main-content">
