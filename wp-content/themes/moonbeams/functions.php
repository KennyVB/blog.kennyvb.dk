<?php
// Used to style the TinyMCE editor
add_editor_style('css/custom-editor-style.css');

// content width
if ( ! isset( $content_width ) ) $content_width = 460;

// translation
load_theme_textdomain( 'moonbeams', get_template_directory().'/languages' );

// automatic feed links
add_theme_support('automatic-feed-links');

// navigation
add_theme_support( 'menus' );
register_nav_menu('header_menu', 'Header Menu');
register_nav_menu('footer_menu', 'Footer Menu');

// thumbnails
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 100, 9999 );

// background
add_custom_background();

// widget
if ( function_exists('register_sidebar') ) {
register_sidebar(array(1,
'name'          => __('Sidebar') . ' 1',
'before_widget' => '<div id="%1$s" class="widget %2$s sidecolumn">',
'after_widget' =>'</div>',
'before_title' => '<h3>',
'after_title' => '</h3>',
));
register_sidebar(array(2,
'name'          => __('Sidebar') . ' 2',
'before_widget' => '<div id="%1$s" class="widget %2$s sidecolumn">',
'after_widget' =>'</div>',
'before_title' => '<h3>',
'after_title' => '</h3>',
));
register_sidebar(array(3,
'name'          => __('Footer') . ' 1',
'before_widget' => '<div id="%1$s" class="widget %2$s sidecolumn">',
'after_widget' =>'</div>',
'before_title' => '<h3>',
'after_title' => '</h3>',
));
register_sidebar(array(4,
'name'          => __('Footer') . ' 2',
'before_widget' => '<div id="%1$s" class="widget %2$s sidecolumn">',
'after_widget' =>'</div>',
'before_title' => '<h3>',
'after_title' => '</h3>',
));
}


// header image
function moonbeams_header_style() {
	$h_img = get_header_image();
	if($h_img) {
    ?><style type="text/css">
        #header {
            background: url(<?php header_image(); ?>);
            width:<?php echo HEADER_IMAGE_WIDTH; ?>px;
            height:<?php echo HEADER_IMAGE_HEIGHT; ?>px;
        }
    </style><?php
    }
}

function moonbeams_admin_header_style() {
	$h_img = get_header_image();
	if($h_img) {
    ?><style type="text/css">
        #headimg {
            width: <?php echo HEADER_IMAGE_WIDTH; ?>px;
            height: <?php echo HEADER_IMAGE_HEIGHT; ?>px;
        }
    </style><?php
    }
}

define('HEADER_IMAGE_WIDTH', 950);
define('HEADER_IMAGE_HEIGHT', 300);
define( 'NO_HEADER_TEXT', true );

add_custom_image_header('moonbeams_header_style', 'moonbeams_admin_header_style');

// favicon
function moonbeams_favicon_link() {
	$faviurl = '<link rel="shortcut icon" type="image/x-icon" href="' . get_stylesheet_directory_uri() . '/images/favicon.ico" />';
    echo $faviurl . "\n";
}
add_action('wp_head', 'moonbeams_favicon_link');

// style
function moonbeams_add_stylesheet() {
	wp_enqueue_style('print_style', get_stylesheet_directory_uri() . '/css/print.css', array(), '', 'print');
	wp_enqueue_style('droppy', get_stylesheet_directory_uri() . '/css/droppy.css', array());
}
add_action('wp_print_styles', 'moonbeams_add_stylesheet');

// script
function moonbeams_add_scripts() {
	wp_enqueue_script('DD_belatedPNG', get_template_directory_uri() . '/js/DD_belatedPNG_0.0.8a-min.js');
	wp_enqueue_script('minmax', get_template_directory_uri() . '/js/minmax.js');
	wp_enqueue_script('droppy', get_template_directory_uri() . '/js/jquery.droppy.js', array('jquery'));
	wp_enqueue_script('commonjs', get_template_directory_uri() . '/js/common.js');
}
add_action('wp_print_scripts', 'moonbeams_add_scripts');

// excerpt
function moonbeams_excerpt_more($post) {
	return ' ... <a href="'. get_permalink($post->ID) . '" title="' . get_the_title($post->ID) . '">' . ' more &raquo; ' . '</a>';	
}	
add_filter('excerpt_more', 'moonbeams_excerpt_more');

// comment
function moonbeams_comment($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; ?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
	<div id="comment-<?php comment_ID(); ?>">
	<div class="comment-author vcard">
	<?php echo get_avatar($comment,$size='32',$default='<path_to_url>' ); ?>
	<?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link()) ?>
	</div>
	<?php if ($comment->comment_approved == '0') : ?>
	<em><?php _e('Your comment is awaiting moderation.') ?></em>
	<br />
	<?php endif; ?>
	<div class="comment-meta commentmetadata"><a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s'), get_comment_date(),  get_comment_time()) ?></a><?php edit_comment_link(__('(Edit)'),'  ','') ?></div>
	<?php comment_text() ?>
	<div class="reply">
	<?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
	</div>
	</div>
<?php
}

?>
