<?php
/**
 * @package Sixhours
 */

function sixhours_sidebars() {
	register_sidebar( array(
		'id' => 'right-sidebar',
		'name' => __( 'Right Sidebar' , 'sixhours' ),
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h2 class="widgettitle">',
		'after_title' => '</h2>'
		) 
	);
}

add_action( 'widgets_init', 'sixhours_sidebars' );

function sixhours_theme_setup() {

	if ( function_exists( 'register_nav_menu' ) ) {
		register_nav_menu( 'tabmenu', __( 'Tabbed Navigation Menu' , 'sixhours' ) );
	}
	 
	if ( ! isset( $content_width ) ) 
		$content_width = 600;
	
	add_theme_support('automatic-feed-links');
	
	add_editor_style();
	
	add_custom_background();
	
	load_theme_textdomain( 'sixhours', get_template_directory() . '/languages' );
	
	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
}

add_action( 'after_setup_theme', 'sixhours_theme_setup' );

function sixhours_custom_header_setup() {
	// The default header text color
	define( 'HEADER_TEXTCOLOR', 'fff' );

	// By leaving empty, we allow for random image rotation.
	define( 'HEADER_IMAGE', '' );

	// The height and width of your custom header.
	// Add a filter to sixhours_header_image_width and sixhours_header_image_height to change these values.
	define( 'HEADER_IMAGE_WIDTH', apply_filters( 'sixhours_header_image_width', 800 ) );
	define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'sixhours_header_image_height', 200 ) );

	// Turn on random header image rotation by default.
	add_theme_support( 'custom-header', array( 'random-default' => true ) );

	// Add a way for the custom header to be styled in the admin panel that controls custom headers
	add_custom_image_header( 'sixhours_header_style', 'sixhours_admin_header_style', 'sixhours_admin_header_image' );
}
add_action( 'after_setup_theme', 'sixhours_custom_header_setup' );

if ( ! function_exists( 'sixhours_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @since Patchwork 1.0
 */
function sixhours_header_style() {

	// If no custom options for text are set, let's bail
	// get_header_textcolor() options: HEADER_TEXTCOLOR is default, hide text (returns 'blank') or any hex value
	if ( HEADER_TEXTCOLOR == get_header_textcolor() )
		return;
	// If we get this far, we have custom styles. Let's do this.
	?>
	<style type="text/css">
	<?php
		// Has the text been hidden?
		if ( 'blank' == get_header_textcolor() ) :
	?>
		.site-title,
		.site-description {
			position: absolute !important;
			clip: rect(1px 1px 1px 1px); /* IE6, IE7 */
			clip: rect(1px, 1px, 1px, 1px);
		}
	<?php
		// If the user has set a custom color for the text use that
		else :
	?>
		.site-title a,
		.site-description {
			color: #<?php echo get_header_textcolor(); ?> !important;
		}
	<?php endif; ?>
	</style>
	<?php
}
endif; // sixhours_header_style

if ( ! function_exists( 'sixhours_admin_header_style' ) ) :
/**
 * Styles the header image displayed on the Appearance > Header admin panel.
 *
 * Referenced via add_custom_image_header() in sixhours_setup().
 *
 * @since Patchwork 1.0
 */
function sixhours_admin_header_style() {
?>
	<style type="text/css">
	.appearance_page_custom-header #headimg {
		border: none;
	}
	#headimg h1,
	#desc {
	}
	#headimg h1 {
	}
	#headimg h1 a {
	}
	#desc {
	}
	#headimg img {
	}
	</style>
<?php
}
endif; // sixhours_admin_header_style

if ( ! function_exists( 'sixhours_admin_header_image' ) ) :
/**
 * Custom header image markup displayed on the Appearance > Header admin panel.
 *
 * Referenced via add_custom_image_header() in sixhours_setup().
 *
 * @since Patchwork 1.0
 */
function sixhours_admin_header_image() { ?>
	<div id="headimg">
		<?php
		if ( 'blank' == get_theme_mod( 'header_textcolor', HEADER_TEXTCOLOR ) || '' == get_theme_mod( 'header_textcolor', HEADER_TEXTCOLOR ) )
			$style = ' style="display:none;"';
		else
			$style = ' style="color:#' . get_theme_mod( 'header_textcolor', HEADER_TEXTCOLOR ) . ';"';
		?>
		<h1><a id="name"<?php echo $style; ?> onclick="return false;" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
		<div id="desc"<?php echo $style; ?>><?php bloginfo( 'description' ); ?></div>
		<?php $header_image = get_header_image();
		if ( ! empty( $header_image ) ) : ?>
			<img src="<?php echo esc_url( $header_image ); ?>" alt="" />
		<?php endif; ?>
	</div>
<?php }
endif; // sixhours_admin_header_image

?>