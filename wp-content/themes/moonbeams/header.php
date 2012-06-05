<?php
$ua = $_SERVER['HTTP_USER_AGENT'];
if (!(ereg("Windows",$ua) && ereg("MSIE",$ua)) || ereg("MSIE 7",$ua)) {
     echo '<' . '?' . 'xml version="1.0" encoding="' . get_option('blog_charset') .'"?>' . "\n";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes( 'xhtml' ); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title><?php wp_title('',true); ?><?php if(wp_title('',false)) { ?> | <?php } ?><?php bloginfo('name'); ?></title> 
<meta name="description" content="<?php bloginfo('description'); ?>" /> 
<?php
if ( is_singular() && get_option( 'thread_comments' ) )
wp_enqueue_script( 'comment-reply' );
?>
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<link href="<?php echo get_stylesheet_directory_uri(); ?>/style.css" rel="stylesheet" type="text/css" media="all" />
<!--[if IE 6]>
	<script>
		DD_belatedPNG.fix('img, .menu-container, .maincolumn, .sidecolumn');
	</script>
<![endif]-->
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="header">
	<div id="info">
		<span class="sitename"><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></span>
		<span class="sitedesc"><?php bloginfo('description'); ?></span>
	</div>
</div><!--// header -->

	<?php wp_nav_menu( array( 'container_class' => 'menu-header menu-container clearfix', 'theme_location' => 'header_menu', 'fallback_cb' => '' ) ); ?>

<div id="wrap" class="clearfix">