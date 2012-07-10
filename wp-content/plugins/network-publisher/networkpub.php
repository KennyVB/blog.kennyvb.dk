<?php
/*
Plugin Name: Network Publisher
Plugin URI: http://wordpress.org/extend/plugins/network-publisher/
Description: Automatically publish your blog posts to multiple Social Networks including Twitter, Facebook Profile, Facebook Pages, LinkedIn, MySpace, Yammer, Yahoo, Identi.ca, and <a href="http://www.linksalpha.com/networks" target="_blank">more</a>. Click <a href="http://help.linksalpha.com/networks/getting-started">here</a> for instructions. Email us at post@linksalpha.com if you have any queries.
Version: 5.3
Author: LinksAlpha
Author URI: http://www.linksalpha.com
*/

/*
    Copyright (C) 2010 LinksAlpha.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('NETWORKPUB_WP_PLUGIN_URL',                  networkpub_get_plugin_dir());
define('NETWORKPUB_WIDGET_NAME',                    __("Network Publisher"));
define('NETWORKPUB_WIDGET_NAME_INTERNAL',           'networkpub');
define('NETWORKPUB_PLUGIN_ADMIN_URL', 				admin_url().'plugins.php?page='.NETWORKPUB_WIDGET_NAME_INTERNAL);
define('NETWORKPUB_WIDGET_NAME_POSTBOX',            __("Postbox"));
define('NETWORKPUB_WIDGET_NAME_POSTBOX_INTERNAL', 	'networkpubpostbox');
define('NETWORKPUB_WIDGET_PREFIX',                  'networkpub');
define('NETWORKPUB',                                __('Automatically publish your blog posts to 25+ Social Networks including Facebook, Twitter, LinkedIn, Yahoo, Yammer, MySpace, Identi.ca'));
define('NETWORKPUB_ERROR_INTERNAL',                 'internal error');
define('NETWORKPUB_ERROR_INVALID_URL',              'invalid url');
define('NETWORKPUB_ERROR_INVALID_KEY',              'invalid key');
define('NETWORKPUB_CURRENTLY_PUBLISHING',        	__('You are currently Publishing your Blog to'));
define('NETWORKPUB_SOCIAL_NETWORKS',        		__('Social Networks'));
define('NETWORKPUB_SOCIAL_NETWORK',                 __('Social Network'));
define('NETWORKPUB_PLUGIN_VERSION',                 '5.3');

$networkpub_settings['api_key'] =   array('label'=>'API Key:', 'type'=>'text', 'default'=>'');
$networkpub_settings['id'] =        array('label'=>'id', 'type'=>'text', 'default'=>'');
$options =                          get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);


function networkpub_init() {
	networkpub_options();
	if ( is_admin() ) {
		wp_enqueue_style('thickbox');
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		wp_register_script('networkpublisherjs', NETWORKPUB_WP_PLUGIN_URL .'networkpub.js');
		wp_enqueue_script('networkpublisherjs');
		wp_register_script('postmessagejs', NETWORKPUB_WP_PLUGIN_URL .'jquery.ba-postmessage.min.js');
		wp_enqueue_script('postmessagejs');
		wp_register_style('networkpublishercss', NETWORKPUB_WP_PLUGIN_URL . 'networkpub.css');
		wp_enqueue_style('networkpublishercss');
		add_action('admin_menu', 'networkpub_pages');
		add_action('activate_{$plugin}', 'networkpub_pushpresscheck');
		add_action("activated_plugin", "networkpub_pushpresscheck");
		register_deactivation_hook( __FILE__, 'networkpub_deactivate' );
	}
}

add_action('init', 'networkpub_init');
register_activation_hook( __FILE__, 			'networkpub_activate' );
add_action('admin_notices', 					'networkpub_warning');
add_action('admin_notices', 					'networkpub_auth_errors');
add_action('init', 								'networkpub_remove');

add_action('init', 								'networkpub_get_posts');

add_action('xmlrpc_publish_post', 				'networkpub_ping');
add_action('{$new_status}_{$post->post_type}', 	'networkpub_ping');
add_action('publish_post', 						'networkpub_ping');
add_action('future_to_publish', 				'networkpub_ping');
add_action('transition_post_status',			'networkpub_ping_custom', 12, 3);

add_action('xmlrpc_publish_post', 				'networkpub_post_xmlrpc');
add_action('{$new_status}_{$post->post_type}', 	'networkpub_post');
add_action('publish_post', 						'networkpub_post');
add_action('future_to_publish', 				'networkpub_post');
add_action('transition_post_status',			'networkpub_post_custom', 12, 3);

add_action('{$new_status}_{$post->post_type}', 	'networkpub_convert');
add_action('publish_post', 						'networkpub_convert');
add_action('future_to_publish', 				'networkpub_convert');

add_action('admin_menu', 						'networkpub_create_post_meta_box' );
add_action('save_post', 						'networkpub_save_post_meta_box', 5, 2 );
add_action('save_post', 						'networkpub_post_publish_status', 4, 2 );

add_action ('wp_head', 							'networkpub_add_metatags');
add_filter ('language_attributes', 				'networkpub_html_schema');


function networkpub_options() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!array_key_exists('networkpub_auth_error_show', $options)) {
		$options['networkpub_auth_error_show'] = 1;
	}
	if(!array_key_exists('networkpub_mixed_mode_alert_show', $options)) {
		$options['networkpub_mixed_mode_alert_show'] = 1;
	}
	if(!array_key_exists('networkpub_metatags_facebook', $options)) {
		$options['networkpub_metatags_facebook'] = 1;
	}
	if(!array_key_exists('networkpub_lang_facebook', $options)) {
		$options['networkpub_lang_facebook'] = 'en_US';
	}
	if(!array_key_exists('networkpub_facebook_page_type', $options)) {
		$options['networkpub_facebook_page_type'] = 'article';
	}
	if(!array_key_exists('networkpub_facebook_app_id', $options)) {
		$options['networkpub_facebook_app_id'] = '';
	}
	if(!array_key_exists('networkpub_metatags_googleplus', $options)) {
		$options['networkpub_metatags_googleplus'] = 1;
	}
	if(!array_key_exists('networkpub_googleplus_page_type', $options)) {
		$options['networkpub_googleplus_page_type'] = 'Article';
	}
	if(!array_key_exists('networkpub_custom_field_image', $options)) {
		$options['networkpub_custom_field_image'] = '';
	}
	if(!array_key_exists('networkpub_post_types', $options)) {
		$options['networkpub_post_types'] = 'post';
	}
	update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
}


// Add a link to this plugin's settings page
function networkpub_actlinks( $links ) { 
    $settings_link = '<a href="'.NETWORKPUB_PLUGIN_ADMIN_URL.'">'.__('Settings').'</a>'; 
    array_unshift( $links, $settings_link ); 
    return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'networkpub_actlinks' ); 


function networkpub_create_post_meta_box() {
	add_meta_box( 'networkpub_meta_box', NETWORKPUB_WIDGET_NAME, 'networkpub_post_meta_box', 'post', 'side', 'core' );
    add_meta_box( 'networkpub_meta_box', NETWORKPUB_WIDGET_NAME, 'networkpub_post_meta_box', 'page', 'side', 'core' );
    add_meta_box( 'networkpub_meta_box', NETWORKPUB_WIDGET_NAME, 'networkpub_post_meta_box', 'link', 'side', 'core' );
    if(function_exists('get_post_types')) {
        $args=array('public'   => true,
                    '_builtin' => false);
        $post_types=get_post_types($args, '');
        foreach($post_types as $key=>$val) {
            add_meta_box( 'networkpub_meta_box', NETWORKPUB_WIDGET_NAME, 'networkpub_post_meta_box', $val->name, 'side', 'core' );
        }
    }
}


function networkpub_post_meta_box( $object, $box ) {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	$this_post_type = $object->post_type;
	//Published State
    $networkpub_meta_published = get_post_meta( $object->ID, '_networkpub_meta_published', true );
    if(in_array($networkpub_meta_published, array('done','failed'))) {
        $inputs_disabled = 'disabled="disabled"';
    } else {
        $inputs_disabled = '';
    }
	//Publish
	$curr_val_publish = get_post_meta( $object->ID, '_networkpub_meta_publish', true );
	if($curr_val_publish == '') {
		$curr_val_publish = 1;
	}
    $html  = '<div class="networkpublisher_post_meta_box">';
    $html_label = '&nbsp;<label for="networkpub_meta_cb_publish">'.__('Publish this').' '.ucfirst($this_post_type).__(' to').' <a href="'.NETWORKPUB_PLUGIN_ADMIN_URL.'">'.__('configured Networks').'</a></label>';
    $html_label_type_disabled = '&nbsp;<label for="networkpub_meta_cb_publish">'.__('Publishing of').' '.ucfirst($this_post_type).' <a href="http://codex.wordpress.org/Post_Types" target="_blank">'.__('Post Type').'</a>'.__(' to').' <a href="'.NETWORKPUB_PLUGIN_ADMIN_URL.'">'.__('configured Networks').'</a>'.' '.__('has been disabled. ').'<a href="'.NETWORKPUB_PLUGIN_ADMIN_URL.'#setting_networkpub_post_types">'.__('Click Here').'</a>'.__(' to enable again.').'</label>';
	if($curr_val_publish) {
		if(array_key_exists('networkpub_post_types', $options)) {
			if(in_array($this_post_type, explode(',', $options['networkpub_post_types']))) {
				$html .= '<input type="checkbox" name="networkpub_meta_cb_publish" id="networkpub_meta_cb_publish" checked '.$inputs_disabled.' />';		
			} else {
				$inputs_disabled = 'disabled="disabled"';
				$html .= '<input type="checkbox" name="networkpub_meta_cb_publish" id="networkpub_meta_cb_publish" '.$inputs_disabled.' />';
				$html_label = $html_label_type_disabled;
			}
		} else {
			$html .= '<input type="checkbox" name="networkpub_meta_cb_publish" id="networkpub_meta_cb_publish" checked '.$inputs_disabled.' />';
		}
	} else {
		if(in_array($this_post_type, explode(',', $options['networkpub_post_types']))) {
			$html .= '<input type="checkbox" name="networkpub_meta_cb_publish" id="networkpub_meta_cb_publish" '.$inputs_disabled.' />';		
		} else {
			$inputs_disabled = 'disabled="disabled"';
			$html .= '<input type="checkbox" name="networkpub_meta_cb_publish" id="networkpub_meta_cb_publish" '.$inputs_disabled.' />';
			$html_label = $html_label_type_disabled;
		}
	}
	$html .= $html_label;
	$html .= '</div>';
	//Content
	$curr_val_content = get_post_meta( $object->ID, '_networkpub_meta_content', true );
	if($curr_val_content == '') {
		$curr_val_content = 0;
	}
	$html .= '<div class="networkpublisher_post_meta_box">';
	if($curr_val_content) {
		$html .= '<input type="checkbox" name="networkpub_meta_cb_content" id="networkpub_meta_cb_content" checked '.$inputs_disabled.' />';
	} else {
		$html .= '<input type="checkbox" name="networkpub_meta_cb_content" id="networkpub_meta_cb_content" '.$inputs_disabled.' />';
	}
	$html .= '&nbsp;<label for="networkpub_meta_cb_content">'.__('Use Excerpt for publishing to Networks').'</label>';
    $html .= '</div>';
    //_networkpub_meta_published
    if($networkpub_meta_published == 'failed') {
        $html .= '<div class="networkpublisher_post_meta_box" style="color:red;"><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" />&nbsp;'.__('Post to social networks failed.').'</div>';
    } elseif($networkpub_meta_published == 'done') {
        $html .= '<div class="networkpublisher_post_meta_box" style="color:green;"><input type="checkbox" checked disabled="disabled" />&nbsp;<label for="networkpub_meta_cb_content">'.__('Data sent successfully.').'</label></div>';
    }
	//Hidden
	$html .= '<input type="hidden" name="networkpub_meta_nonce" value="'. wp_create_nonce( plugin_basename( __FILE__ ) ).'" />';
    //Return
	echo $html;
}


function networkpub_save_post_meta_box( $post_id, $post ) {
	if(empty($_POST['networkpub_meta_nonce'])) {
		return $post_id;
	}
	if (!wp_verify_nonce($_POST['networkpub_meta_nonce'], plugin_basename( __FILE__ ))) {
		return $post_id;	
	}
	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}
	//Publish
	$new_meta_value_publish = 0;
	if(!empty($_POST['networkpub_meta_cb_publish'])) {
		if($_POST['networkpub_meta_cb_publish']) {
			$new_meta_value_publish = 1;
		}	
	}
	update_post_meta( $post_id, '_networkpub_meta_publish', $new_meta_value_publish );
	//Content
	$new_meta_value_content = 0;
	if(!empty($_POST['networkpub_meta_cb_content'])) {
		if($_POST['networkpub_meta_cb_content']) {
			$new_meta_value_content = 1;
		}
	} 
	update_post_meta( $post_id, '_networkpub_meta_content', $new_meta_value_content);
}


function networkpub_post_publish_status($post_id, $post) {
	add_post_meta( $post_id, '_networkpub_meta_published', 'new', true);
}


function networkpub_warning() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(empty($options['api_key'])) {
		if (!isset($_POST['submit'])) {
			echo '
			<div class="updated fade" style="padding:10px;text-align:left">
				<div style="font-weight:bold;"><a href="http://wordpress.org/extend/plugins/network-publisher/" target="_blank">'.NETWORKPUB_WIDGET_NAME.'</a> '.__('plugin is almost ready.').'</div>
				<div>'.__('You must').' <a href="'.NETWORKPUB_PLUGIN_ADMIN_URL.'">'.__('enter API key').'</a> '.__(' on the settings page for plugin').' '.NETWORKPUB_WIDGET_NAME.' '.__('for automatic posting of your blog articles to 25+ Social Networks including Twitter, Facebook Profile, Facebook Pages, LinkedIn, MySpace, Yammer, Yahoo, Identi.ca, and more to work').'</div>
			</div>';
		}
	}
}


function networkpub_auth_errors() {
	//Get options
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!is_array($options)) {
		return;
	}
	if(empty($options['networkpub_auth_error_show'])) {
		return;
	}
	$networkpub_auth_error_show = $options['networkpub_auth_error_show'];
	if(!$networkpub_auth_error_show) {
		return;
	}
	if (empty($options['api_key'])) {
		return;
	}
	$api_key = $options['api_key'];
	$link = 'http://www.linksalpha.com/a/networkpubautherrors';
	$params = array('api_key'=>$api_key,
					'plugin'=>'nw',
					'plugin_version'=>networkpub_version(),
					);
	$response_full = networkpub_http_post($link, $params);
	$response_code = $response_full[0];
	if($response_code == 200) {
        return;
	}
	if($response_code == 401) {
		echo "
		<div class='updated fade' style='padding:10px;'>
			<div style='color:red;font-weight:bold;'>
				<img src='".NETWORKPUB_WP_PLUGIN_URL ."alert.png' style='vertical-align:text-bottom;' />&nbsp;".__("Network Publisher Authorization Error")."
			</div>
			<div style='padding-top:0px;'>
				".__("Authorization provided on one or more of your Network accounts has expired. Please")." <a target='_blank' href='http://www.linksalpha.com/networks'>".__("add the related Account")."</a> ".__("again to be able to publish content. To learn more, ")."<a target='_blank' href='http://help.linksalpha.com/networks/authorization-error'>".__("Click Here")."</a>. ".__("To access Settings page of the plugin, ")."<a href='plugins.php?page=networkpub'>".__("Click Here.")."</a>
			</div>
		</div>
		";
        return;
	}
    return;
}


function networkpub_mixed_mode() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!is_array($options)) {
		return;
	}
	if(empty($options['networkpub_mixed_mode_alert_show'])) {
		return;
	}
	$networkpub_mixed_mode_alert_show = $options['networkpub_mixed_mode_alert_show'];
	if(!$networkpub_mixed_mode_alert_show) {
		return;
	}
	if (empty($options['id_2'])) {
		return;
	}
	$id = $options['id_2'];
	$link = 'http://www.linksalpha.com/a/networkpubmixedmode';
	$params = array('id'=>$id,
					'plugin'=>'nw',
					'plugin_version'=>networkpub_version(),
					);
	$response_full = networkpub_http_post($link, $params);
	$response_code = $response_full[0];
	if($response_code == 200) {
		$response = networkpub_json_decode($response_full[1]);
		if ($response->errorCode > 0) {
			if($response->errorMessage == 'mixed mode') {
				echo "
				<div class='updated fade' style='padding:10px;'>
					<div style='color:red;font-weight:bold;'>
						<img src='".NETWORKPUB_WP_PLUGIN_URL ."alert.png' style='vertical-align:text-bottom;' />&nbsp;".__("Network Publisher - Mixed Mode Alert")."
					</div>
					<div style='padding-top:0px;'>
						".__("Publishing of your website content via LinksAlpha Network Publisher seems to be configured using both the Network Publisher Plugin and RSS Feed of your website. LinksAlpha recommends use of Network Publisher plugin over RSS Feed. ")."<a target='_blank' href='http://help.linksalpha.com/wordpress-plugin-network-publisher/mixed-mode-alert'>".__("Click here")."</a> ".__("to read the help document that will help resolve this Mixed Mode configuration issue.")."
					</div>
				</div>
				";
			}
		}
	}
}


function networkpub_ping($id) {
	if(!$id) {
		return;
	}
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(empty($options['id']) or empty($options['api_key'])) {
		return;
	}
	$link = 'http://www.linksalpha.com/a/ping?id='.$options['id'];
	$response_full = networkpub_http($link);
	return;
}


function networkpub_ping_custom($new, $old, $post) {
    if ($new == 'publish' && $old != 'publish') {
        $post_types = get_post_types( array('public' => true), 'objects' );
        foreach ( $post_types as $post_type ) {
            if ( $post->post_type == $post_type->name ) {
                networkpub_ping($post->ID, $post);
                break;
            }
        }
    }
    return;
}


function networkpub_convert($id) {
	if(!$id) {
		return;
	}
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!empty($options['id_2'])) {
		return;
	}
	if(empty($options['id']) or empty($options['api_key'])) {
		return;
	}
	// Build Params
	$link = 'http://www.linksalpha.com/a/networkpubconvert';
	$params = array('id'=>$options['id'],
					'api_key'=>$options['api_key'],
					'plugin'=>'nw',
					);
	//HTTP Call
	$response_full = networkpub_http_post($link, $params);
	$response_code = $response_full[0];
	if ($response_code != 200) {
		return;
	}
	$response = networkpub_json_decode($response_full[1]);
	if ($response->errorCode > 0) {
		return;
	}
	//Update options
	$options['id_2'] = $response->results;
	//Save
	update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
	return;
}


function networkpub_post($post_id) {
    //Get options
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!is_array($options)) {
		return;
	}
	//Publishing enabled?
	if(array_key_exists('networkpub_enable', $options)) {
		$networkpub_enable_value = $options['networkpub_enable'];
	} else {
		$networkpub_enable_value = 1;
	}
	if(!$networkpub_enable_value) {
		return;
	}
	//Post type
	if(!$options['networkpub_post_types']) {
		return;
	}
	$post_types_enabled = explode(',', $options['networkpub_post_types']);
	$post_type = get_post_type($post_id);
	if(!in_array($post_type, $post_types_enabled)) {
		return;
	}
	//Network keys
	if (empty($options['api_key']) or empty($options['id_2'])) {
		return;
	}
	$id = $options['id_2'];
	$api_key = $options['api_key'];
	//Post data
	$post_data = get_post( $post_id, ARRAY_A );
	//Post Published?
	if(!in_array($post_data['post_status'], array('future', 'publish'))) {
		return;	
	}
	//Post too old
	$post_date = strtotime($post_data['post_date_gmt']);
	$current_date = time();
	$diff = $current_date - $post_date;
	$days = floor( $diff / (60*60*24) );
	if($days > 3) {
		return;
	}
	//Post meta - networkpub_meta_publish
	$networkpub_meta_publish = get_post_meta( $post_id, '_networkpub_meta_publish', true );
	if($networkpub_meta_publish == "") {
	} elseif ($networkpub_meta_publish == 0) {
		return;
	}
	//Post meta - networkpub_meta_published
	$networkpub_meta_published = get_post_meta( $post_id, '_networkpub_meta_published', true );
	if($networkpub_meta_published == 'done') {
		return;
	}
	//Post meta - networkpub_meta_content
	$networkpub_meta_content = get_post_meta( $post_id, '_networkpub_meta_content', true );
	//Post data: id, content and title
	$post_title = $post_data['post_title'];
	if($networkpub_meta_content) {
		$post_content = $post_data['post_excerpt'];
	} else {
		$post_content = $post_data['post_content'];
	}
	//Post data: Permalink
	$post_link = get_permalink($post_id);
	//Post data: Categories
	$post_categories_array = array();
	$post_categories_data = get_the_category( $post_id );
	foreach($post_categories_data as $category) {
		$post_categories_array[] = $category->cat_name;
	}
	$post_categories = implode(",", $post_categories_array);
	//Post tags
	$post_tags_array = array();
	$post_tags_data = wp_get_post_tags( $post_id );
	foreach($post_tags_data as $tag) {
		$post_tags_array[] = $tag->name;
	}
	$post_tags = implode(",", $post_tags_array);
	//Post Geo
	if(function_exists('get_wpgeo_latitude')) {
		if(get_wpgeo_latitude( $post_id ) and get_wpgeo_longitude( $post_id )) {
			$post_geotag = get_wpgeo_latitude( $post_id ).' '.get_wpgeo_longitude( $post_id );
		}
	}
	if(!isset($post_geotag)) {
		$post_geotag = '';
	}
	// Build Params
	$link = 'http://www.linksalpha.com/a/networkpubpost';
	$params = array('id'=>$id,
					'api_key'=>$api_key,
					'post_id'=>$post_id,
					'post_link'=>$post_link,
					'post_title'=>$post_title,
					'post_content'=>$post_content,
					'plugin'=>'nw',
					'plugin_version'=>networkpub_version(),
					'post_categories'=>$post_categories,
					'post_tags'=>$post_tags,
					'post_geotag'=>$post_geotag
					);
	//Featured Image
	$post_image = networkpub_thumbnail_link( $post_id, $post_data['post_content'] );
	if($post_image) {
		$params['post_image'] = $post_image;
	}
	//HTTP Call
	$response_full = networkpub_http_post($link,$params);
	$response_code = $response_full[0];
	if($response_code == 200) {
		update_post_meta( $post_id, '_networkpub_meta_published', 'done' );
        return;
	}
    update_post_meta( $post_id, '_networkpub_meta_published', 'failed' );
	return;
}


function networkpub_post_xmlrpc($post_id) {
	networkpub_post($post_id);
    return;
}


function networkpub_post_custom($new, $old, $post) {
    if ($new == 'publish' && $old != 'publish') {
        $post_types = get_post_types( array('public' => true), 'objects' );
        foreach ( $post_types as $post_type ) {
            if ( $post->post_type == $post_type->name ) {
                networkpub_post($post->ID);
                break;
            }
        }
	}
    return;
}


function networkpub_conf() {
    $options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
    global $networkpub_settings;
    if ( isset($_POST['submit']) ) {
    	if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
			die(__('Cheatin&#8217; uh?'));
		}
		$field_name = sprintf('%s_%s', NETWORKPUB_WIDGET_PREFIX, 'api_key');
		if(array_key_exists($field_name, $_POST)) {
			$value = strip_tags(stripslashes($_POST[$field_name]));
			if($value) {
				$networkadd = networkpub_add($value);
			}
		} else {
			if($_POST['networkpub_form_type'] == 'networkpub_enable') {
				if(array_key_exists('networkpub_enable', $_POST)) {
					$networkpub_enable = 1;	
				} else {
					$networkpub_enable = 0;
				}
				networkpub_update_option('networkpub_enable', $networkpub_enable);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_auth_error_show') {
				if(array_key_exists('networkpub_auth_error_show', $_POST)) {
					$networkpub_auth_error_show = 1;	
				} else {
					$networkpub_auth_error_show = 0;
				}
				networkpub_update_option('networkpub_auth_error_show', $networkpub_auth_error_show);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_mixed_mode_alert_show') {
				if(array_key_exists('networkpub_mixed_mode_alert_show', $_POST)) {
					$networkpub_mixed_mode_alert_show = 1;	
				} else {
					$networkpub_mixed_mode_alert_show = 0;
				}
				networkpub_update_option('networkpub_mixed_mode_alert_show', $networkpub_mixed_mode_alert_show);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_metatags_facebook') {
				if(array_key_exists('networkpub_metatags_facebook', $_POST)) {
					$networkpub_metatags_facebook = 1;	
				} else {
					$networkpub_metatags_facebook = 0;
				}
				if(array_key_exists('networkpub_lang_facebook', $_POST)) {
					$networkpub_lang_facebook = $_POST['networkpub_lang_facebook'];	
				} else {
					$networkpub_lang_facebook = 'en_US';
				}
				if(array_key_exists('networkpub_facebook_page_type', $_POST)) {
					$networkpub_facebook_page_type = $_POST['networkpub_facebook_page_type'];	
				} else {
					$networkpub_facebook_page_type = 'article';
				}
				if(array_key_exists('networkpub_facebook_app_id', $_POST)) {
					$networkpub_facebook_app_id = $_POST['networkpub_facebook_app_id'];	
				} else {
					$networkpub_facebook_app_id = '';
				}
				networkpub_update_option('networkpub_metatags_facebook', $networkpub_metatags_facebook);
				networkpub_update_option('networkpub_lang_facebook', $networkpub_lang_facebook);
				networkpub_update_option('networkpub_facebook_page_type', $networkpub_facebook_page_type);
				networkpub_update_option('networkpub_facebook_app_id', $networkpub_facebook_app_id);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_metatags_googleplus') {
				if(array_key_exists('networkpub_metatags_googleplus', $_POST)) {
					$networkpub_metatags_googleplus = 1;	
				} else {
					$networkpub_metatags_googleplus = 0;
				}
				if(array_key_exists('networkpub_googleplus_page_type', $_POST)) {
					$networkpub_googleplus_page_type = $_POST['networkpub_googleplus_page_type'];	
				} else {
					$networkpub_googleplus_page_type = 'Article';
				}
				networkpub_update_option('networkpub_metatags_googleplus', $networkpub_metatags_googleplus);
				networkpub_update_option('networkpub_googleplus_page_type', $networkpub_googleplus_page_type);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_custom_field_image') {
				if(array_key_exists('networkpub_custom_field_image', $_POST)) {
					$networkpub_custom_field_image = implode(',', $_POST['networkpub_custom_field_image']);	
				} else {
					$networkpub_custom_field_image = '';
				}
				networkpub_update_option('networkpub_custom_field_image', $networkpub_custom_field_image);
			} elseif ($_POST['networkpub_form_type'] == 'networkpub_post_types') {
				if(array_key_exists('networkpub_post_types', $_POST)) {
					$networkpub_post_types = implode(',', $_POST['networkpub_post_types']);
				} else {
					$networkpub_post_types = '';
				}
				networkpub_update_option('networkpub_post_types', $networkpub_post_types);
			}
		}
	}
	if (!empty($_GET['linksalpha_request_type'])) {
		if($_GET['linksalpha_request_type'] == 'get_posts') {
 			networkpub_get_posts();
 		}		
 		return;
	}
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(is_array($options)) {
		//Publishing Enable/Disable
		if(array_key_exists('networkpub_enable', $options)) {
			$networkpub_enable = $options['networkpub_enable'];
			if($networkpub_enable) {
				$networkpub_enable = 'checked';	
			} else {
				$networkpub_enable = '';
			}
		} else {
			$networkpub_enable = 'checked';
		}
		//Auth Error show hide
    	if(array_key_exists('networkpub_auth_error_show', $options)) {
			$networkpub_auth_error_show = $options['networkpub_auth_error_show'];
			if($networkpub_auth_error_show) {
				$networkpub_auth_error_show = 'checked';	
			} else {
				$networkpub_auth_error_show = '';
			}
		} else {
			$networkpub_auth_error_show = 'checked';
		}
		//Mixed Mode Alert
    	if(array_key_exists('networkpub_mixed_mode_alert_show', $options)) {
			$networkpub_mixed_mode_alert_show = $options['networkpub_mixed_mode_alert_show'];
			if($networkpub_mixed_mode_alert_show) {
				$networkpub_mixed_mode_alert_show = 'checked';	
			} else {
				$networkpub_mixed_mode_alert_show = '';
			}
		} else {
			$networkpub_mixed_mode_alert_show = 'checked';
		}
		//Facebook Metatags
    	if(array_key_exists('networkpub_metatags_facebook', $options)) {
			$networkpub_metatags_facebook = $options['networkpub_metatags_facebook'];
			if($networkpub_metatags_facebook) {
				$networkpub_metatags_facebook = 'checked';	
			} else {
				$networkpub_metatags_facebook = '';
			}
		} else {
			$networkpub_metatags_facebook = 'checked';
		}
		//Facebook Lang
    	if(array_key_exists('networkpub_lang_facebook', $options)) {
			$networkpub_lang_facebook = $options['networkpub_lang_facebook'];
		} else {
			$networkpub_metatags_facebook = 'en_US';
		}
		//Facebook Type
    	if(array_key_exists('networkpub_facebook_page_type', $options)) {
			$networkpub_facebook_page_type = $options['networkpub_facebook_page_type'];
		} else {
			$networkpub_facebook_page_type = 'article';
		}
		//Facebook APP Id
    	if(array_key_exists('networkpub_facebook_app_id', $options)) {
			$networkpub_facebook_app_id = $options['networkpub_facebook_app_id'];
		} else {
			$networkpub_facebook_app_id = '';
		}
		//Google Plus Metatags
    	if(array_key_exists('networkpub_metatags_googleplus', $options)) {
			$networkpub_metatags_googleplus = $options['networkpub_metatags_googleplus'];
			if($networkpub_metatags_googleplus) {
				$networkpub_metatags_googleplus = 'checked';	
			} else {
				$networkpub_metatags_googleplus = '';
			}
		} else {
			$networkpub_metatags_googleplus = 'checked';
		}
		//Google Plus Page Type
    	if(array_key_exists('networkpub_googleplus_page_type', $options)) {
			$networkpub_googleplus_page_type = $options['networkpub_googleplus_page_type'];
		} else {
			$networkpub_googleplus_page_type = 'Article';
		}
		//Custom Field Image
    	if(array_key_exists('networkpub_custom_field_image', $options)) {
			$networkpub_custom_field_image = $options['networkpub_custom_field_image'];
		} else {
			$networkpub_custom_field_image = '';
		}
	} else {
		$networkpub_enable = 'checked';
		$networkpub_auth_error_show = 'checked';
		$networkpub_mixed_mode_alert_show = 'checked';
		$networkpub_metatags_facebook = 'checked';
		$networkpub_lang_facebook = 'en_US';
		$networkpub_facebook_page_type = 'article';
		$networkpub_facebook_app_id = '';
		$networkpub_metatags_googleplus = 'checked';
		$networkpub_googleplus_page_type = 'Article';
		$networkpub_custom_field_image = '';
	}
	//FB Langs
	$fb_langs = networkpub_fb_langs();
	$fb_langs_options = '';
	asort($fb_langs );
    foreach($fb_langs as $key=>$val) {
	    if($networkpub_lang_facebook == $key) {
	    	$fb_langs_options = $fb_langs_options.'<option value="'.htmlentities($key).'" selected>'.htmlentities($val).'</option>';
	    } else{
	    	$fb_langs_options = $fb_langs_options.'<option value="'.htmlentities($key).'">'.htmlentities($val).'</option>';
	   	}
    }
    $facebook_page_type = array('article'=>__('Article'), 'blog'=>__('Blog'), 'book'=>__('Book'), 'profile'=>__('External Profile'), 'video.movie'=>__('Movie'), 'video.episode'=>__('TV Episode'), 'video.tv_show'=>__('TV Show'), 'video.other'=>__('Video'), 'website'=>__('Website'));
	$facebook_page_type_options = '';
	foreach($facebook_page_type as $key=>$val) {
	    if($networkpub_facebook_page_type == $key) {
	    	$facebook_page_type_options = $facebook_page_type_options.'<option value="'.htmlentities($key).'" selected>'.htmlentities($val).'</option>';
	    } else{
	    	$facebook_page_type_options = $facebook_page_type_options.'<option value="'.htmlentities($key).'">'.htmlentities($val).'</option>';
	   	}
    }
    //Google Plus
    $googleplus_page_type = array('Article'=>__('Article'), 'Blog'=>__('Blog'), 'Book'=>__('Book'), 'Event'=>__('Event'), 'LocalBusiness'=>__('Local Business'), 'Organization'=>__('Organization'), 'Person'=>__('Person'), 'Product'=>__('Product'), 'Review'=>__('Review'));
    $googleplus_page_type_options = '';
	foreach($googleplus_page_type as $key=>$val) {
	    if($networkpub_googleplus_page_type == $key) {
	    	$googleplus_page_type_options = $googleplus_page_type_options.'<option value="'.htmlentities($key).'" selected>'.htmlentities($val).'</option>';
	    } else{
	    	$googleplus_page_type_options = $googleplus_page_type_options.'<option value="'.htmlentities($key).'">'.htmlentities($val).'</option>';
	   	}
    }
    //Mixed mode
	networkpub_mixed_mode();
	//HTML
	$html  = '
			<div id="networkpub_msg"></div>
			<div class="wrap">
				<span><div class="icon32" id="networkpubisher_laicon"><br /></div><h2>'.NETWORKPUB_WIDGET_NAME.'</h2></span>
			</div>
			<div class="wrap">
			<div style="width:76%;float:left;">
				<div class="networkpublisher_share_box">
					<table>
						<tr>
							<td>
								<a href="'.networkpub_postbox_url().'">'.__('Postbox').'</a>&nbsp;&nbsp;|&nbsp;&nbsp;'.__('Share').'	
							</td>
							<td>
								<div class="linksalpha-email-button" id="linksalpha_tag_208867858" data-url="http://www.linksalpha.com" data-text="LinksAlpha - Making Social Media Easy!" data-desc="LinksAlpha provides quick and easy way for companies and users to connect and share on social web. Using LinksAlpha tools, you can integrate Social Media Buttons into your website, Publish your Website Content Automatically to Social Media Sites, and Track Social Media Profiles, all from one place." data-image="http://www.linksalpha.com/images/LALOGO_s175.png"></div>
								<script type="text/javascript" src="http://www.linksalpha.com/social/loader?tag_id=linksalpha_tag_208867858&fblikefont=arial&vkontakte=1&livejournal=1&twitter=1&xinglang=de&linkedin=1&tumblr=1&hyves=1&fblikelang=en_US&delicious=1&twitterw=110&gpluslang=en-US&gmail=1&weibo=1&posterous=1&xing=1&sonico=1&twitterlang=en&pinterest=1&myspace=1&msn=1&print=1&mailru=1&email=1&counters=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&reddit=1&hotmail=1&netlog=1&twitterrelated=linksalpha&aolmail=1&link=http%3A%2F%2Fwww.linksalpha.com&diigo=1&evernote=1&digg=1&yahoomail=1&yammer=1&stumbleupon=1&instapaper=1&facebookw=90&googleplus=1&fblikeverb=like&fblikeref=linksalpha&halign=left&readitlater=1&v=2&facebook=1&button=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&identica=1"></script>
							</td>
						</tr>
					</table>
				</div>
				<div>
					<div class="networkpublisher_started">
						<div style="padding:0px 0px 5px 0px;"><strong>'.__('Network Publisher').'</strong> '.__('makes it easy to Publish your Blog Posts to Social Networks. To configure:').'</div>
						<div><b>1.</b>&nbsp;'.__('Connect to your Social Networks at').' <a target="_blank" href="http://www.linksalpha.com/networks">'.__('LinksAlpha.com').'</a></div>
						<div><b>2.</b>&nbsp;'.__('Get your').' <a target="_blank" href="http://www.linksalpha.com/account/your_api_key">'.__('User API Key').'</a> '.__('or').' <a target="_blank" href="http://www.linksalpha.com/networks">'.__('Network API Key').'</a> '.__('and enter it below.').'</div>
						<div style="padding:5px 0px 0px 0px;">'.__('Once setup, your Blog posts content appears on the social networks as soon as you hit the Publish button.').'</div>
						<div>'.__('You can').' <a href="http://help.linksalpha.com/networks/getting-started" target="_blank">'.__('read more about this process at LinksAlpha.com.').'</a></div>
					</div>
					<div class="networkpublisher_header">
						<strong>'.__('Setup').'</strong>
					</div>
					<div style="padding-left:0px;margin-bottom:40px;">
						<div class="networkpublisher_content_box">
							<form action="" method="post">
							<fieldset class="networkpublisher_fieldset">
								<legend>'.__('API Key').'</legend>';

	$curr_field = 'api_key';
	$field_name = sprintf('%s_%s', NETWORKPUB_WIDGET_PREFIX, $curr_field);
	$html .= 				   '<table style="width:100%;">
                                    <tr>
                                        <td>
                                            <label for="'.$field_name.'">
                                                '.__('Get your').' <a href="http://www.linksalpha.com/account/your_api_key" target="_blank">'.__('User API Key').'</a> '.__('or').' <a target="_blank" href="http://www.linksalpha.com/networks">'.__('Network API Key').'</a> '.__('and enter it below.').'
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="padding-bottom:10px;padding-top:3px;">
                                                <input style="width:400px;  border-width:1px;border-color:gray;border-style:solid" class="widefat" id="'.$field_name.'" name="'.$field_name.'" type="text" TABINDEX=1000 />
                                            </div>
                                        </td>
                                    </tr>
                                </table>
							</fieldset>
							<div style="padding-top:0px;">
                                <table class="networkpublisher_add_key_submit">
                                    <tr>
                                        <td class="networkpublisher_add_key_submit_button">
                                            <input type="submit" name="submit" class="button-primary" value="'.__('Add API Key').'" TABINDEX=1001 />
                                        </td>
                                        <td class="networkpublisher_add_key_submit_social">
                                            <div class="linksalpha-email-button" id="linksalpha_tag_20886785812" data-url="http://www.linksalpha.com" data-text="LinksAlpha - Making Social Media Easy!" data-desc="LinksAlpha provides quick and easy way for companies and users to connect and share on social web. Using LinksAlpha tools, you can integrate Social Media Buttons into your website, Publish your Website Content Automatically to Social Media Sites, and Track Social Media Profiles, all from one place." data-image="http://www.linksalpha.com/images/LALOGO_s175.png"></div>
											<script type="text/javascript" src="http://www.linksalpha.com/social/loader?tag_id=linksalpha_tag_20886785812&fblikefont=arial&vkontakte=1&livejournal=1&twitter=1&xinglang=de&linkedin=1&tumblr=1&hyves=1&fblikelang=en_US&delicious=1&twitterw=110&gpluslang=en-US&gmail=1&weibo=1&posterous=1&xing=1&sonico=1&twitterlang=en&pinterest=1&myspace=1&msn=1&print=1&mailru=1&email=1&counters=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&reddit=1&hotmail=1&netlog=1&twitterrelated=linksalpha&aolmail=1&link=http%3A%2F%2Fwww.linksalpha.com&diigo=1&evernote=1&digg=1&yahoomail=1&yammer=1&stumbleupon=1&instapaper=1&facebookw=90&googleplus=1&fblikeverb=like&fblikeref=linksalpha&halign=left&readitlater=1&v=2&facebook=1&button=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&identica=1"></script>
                                        </td>
                                    </tr>
                                </table>
							</div>
							<input type="hidden" value="'.NETWORKPUB_WP_PLUGIN_URL.'" id="networkpub_plugin_url" />
							<input type="hidden" value="'.__('Removing...').'" id="networkpub_text_removing" />
							<input type="hidden" value="'.__('An error occured while removing the Publication. As a workaround, you can remove this Publication from').'" id="networkpub_text_an_error_occured" />
							<input type="hidden" value="'.__('LinksAlpha Publisher').'" id="networkpub_text_linksalpha_publisher" />
							<input type="hidden" value="'.__('Publication has been removed successfully').'" id="networkpub_text_publication_has_been_removed" />
							</form>
						</div>
						<div style="font-size:14px;margin:10px 0px 0px 0px;padding:5px;" id="networkpub_remove">&nbsp;</div>
						<div style="padding:5px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Currently Publishing').'</strong>
							</div>
							<div class="networkpublisher_content_box">'.networkpub_load().'</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Enable/Disable Publishing').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div>
											<input type="checkbox" id="networkpub_enable" name="networkpub_enable" '.$networkpub_enable.' /><label for="networkpub_enable">&nbsp;&nbsp;'.__('Check this box to Enable publishing').'</label>
										</div>
										<div style="padding-top:5px;">
											<input type="hidden" name="networkpub_form_type" value="networkpub_enable" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
								<div>
									<div style="padding-bottom:5px;">'.__('Notes:').'</div>
									<ol>
										<li>'.__('You should typically use this option when you are making mass updates to your posts to prevent them from getting published to the configured networks').'</li>
										<li>'.__('Deactivating the plugin will not disable publishing. You need to use this checkbox to disable publishing').'</li>
									</ol>
								</div>
							</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Show/Hide Authorization Errors').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div>
											<input type="checkbox" id="networkpub_auth_error_show" name="networkpub_auth_error_show" '.$networkpub_auth_error_show.' /><label for="networkpub_auth_error_show">&nbsp;&nbsp;'.__('Check this box to show error message in WordPress Admin Console in case there are Authorization Errors with any of your social network profiles.').' <a target="_blank" href="http://help.linksalpha.com/networks/authorization-error">'.__('Click Here').'</a> '.__('to learn more.').'</label>
										</div>
										<div style="padding-top:5px;">
											<input type="hidden" name="networkpub_form_type" value="networkpub_auth_error_show" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
							</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Show/Hide Mixed Mode Configuration Alert').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div>
											<input type="checkbox" id="networkpub_mixed_mode_alert_show" name="networkpub_mixed_mode_alert_show" '.$networkpub_mixed_mode_alert_show.' /><label for="networkpub_mixed_mode_alert_show">&nbsp;&nbsp;'.__('Check this box to show alert if Mixed Mode configuration is detected.').' <a target="_blank" href="http://help.linksalpha.com/wordpress-plugin-network-publisher/mixed-mode-alert">'.__('Click Here').'</a> '.__('to learn more.').'</label>
										</div>
										<div style="padding-top:5px;">
											<input type="hidden" name="networkpub_form_type" value="networkpub_mixed_mode_alert_show" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
							</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Facebook Open Graph Metatags and Locale').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div>
											<input type="checkbox" id="networkpub_metatags_facebook" name="networkpub_metatags_facebook" '.$networkpub_metatags_facebook.' /><label for="networkpub_metatags_facebook">&nbsp;&nbsp;'.__('Check this box if you want Facebook Open Graph Metatags added to your pages. ').' <a target="_blank" href="http://help.linksalpha.com/wordpress-plugin-network-publisher/metatags">'.__('Click Here').'</a> '.__('to learn more.').'</label>
										</div>
										<div style="padding:10px 0px;">
											<div style="width:220px;float:left;">
				                            	<select name="networkpub_lang_facebook" id="networkpub_lang_facebook">
				                                	'.$fb_langs_options.'
				                            	</select>
				                            </div>
				                            <div style="float:left;width:400px;">
												<label for="networkpub_lang_facebook">'.__('Facebook Locale').'</label>
											</div>
				                            <br style="clear:both;"/>
			                            </div>
			                            <div style="padding:10px 0px;">
											<div style="width:120px;float:left;">
				                            	<select name="networkpub_facebook_page_type" id="networkpub_facebook_page_type">
				                                	'.$facebook_page_type_options.'
				                            	</select>
				                            </div>
				                            <div style="float:left;width:400px;">
												<label for="networkpub_facebook_page_type">'.__('Page Type').'</label>
											</div>
				                            <br style="clear:both;"/>
			                            </div>
										<div style="padding:10px 0px;">
											<div style="width:120px;float:left;">
				                            	<input style="width:110px" type="text" name="networkpub_facebook_app_id" id="networkpub_facebook_app_id" value="'.$networkpub_facebook_app_id.'" />
				                            </div>
				                            <div style="float:left;width:400px;">
												<label for="networkpub_facebook_app_id">'.__('Facebook App ID').'</label>
											</div>
				                            <br style="clear:both;"/>
			                            </div>
										<div>
											<input type="hidden" name="networkpub_form_type" value="networkpub_metatags_facebook" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
							</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Google Plus Metatags and Page Type').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div>
											<input type="checkbox" id="networkpub_metatags_googleplus" name="networkpub_metatags_googleplus" '.$networkpub_metatags_googleplus.' /><label for="networkpub_metatags_googleplus">&nbsp;&nbsp;'.__('Check this box if you want Google Plus Metatags added to your pages. ').' <a target="_blank" href="http://help.linksalpha.com/wordpress-plugin-network-publisher/metatags">'.__('Click Here').'</a> '.__('to learn more.').'</label>
										</div>
										<div style="padding:10px 0px;">
											<div style="width:120px;float:left;">
				                            	<select name="networkpub_googleplus_page_type" id="networkpub_googleplus_page_type">
				                                	'.$googleplus_page_type_options.'
				                            	</select>
				                            </div>
				                            <div style="float:left;width:400px;">
												<label for="networkpub_googleplus_page_type">'.__('Page Type').'</label>
											</div>
				                            <br style="clear:both;"/>
			                            </div>
										<div>
											<input type="hidden" name="networkpub_form_type" value="networkpub_metatags_googleplus" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
							</div>
						</div>
						<div style="padding:40px 0px 0px 0px;">
							<div class="networkpublisher_header">
								<strong>'.__('Image URL from Custom Field').'</strong>
							</div>
							<div class="networkpublisher_content_box">
								<div style="padding-bottom:10px;">
									<form action="" method="post">
										<div style="padding:10px 0px 0px 0px;">
											<div style="width:220px;float:left;">
				                            	<input style="width:210px;" type="text" name="networkpub_custom_field_image" id="networkpub_custom_field_image" value="'.$networkpub_custom_field_image.'" />
				                            </div>
				                            <div style="float:left;width:400px;">
												<label for="networkpub_custom_field_image">'.__('Custom Field Name').'</label>
											</div>
				                            <br style="clear:both;"/>
			                            </div>
			                            <div style="padding:3px 0px 10px 0px;color:gray;">
			                            '.__('If your posts store images in a custom field, input the name of the custom field here. Please note that the custom field should contain direct url to the image. If this is not the case, please contact').' <a href="http://support.linksalpha.com">'.__('LinksAlpha Support').'</a> 
			                            </div>
										<div>
											<input type="hidden" name="networkpub_form_type" value="networkpub_custom_field_image" />
											<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
										</div>
									</form>
								</div>
							</div>
						</div>
						<a href="#setting_networkpub_post_types" style="text-decoration:none;color:#333;">
							<div style="padding:40px 0px 0px 0px;">
								<div class="networkpublisher_header">
									<strong>'.__('Post Types to Publish').'</strong>
								</div>
								<div class="networkpublisher_content_box networkpublisher_highlight"  name="setting_networkpub_post_types" id="setting_networkpub_post_types">
									<div style="padding-bottom:10px;">
										<form action="" method="post">
											<div style="padding:0px 0px 10px 0px;">
				                            '.__('Select the WordPress ').'<a href="http://codex.wordpress.org/Post_Types" target="_blank">'.__('Post Types').'</a>'.__(' that you want to Publish to your Social Network profiles.').'</a>
				                             
				                            </div>
				                            <div style="padding:0px 0px 10px 0px;">'.networkpub_post_types().'</div>
											<div>
												<input type="hidden" name="networkpub_form_type" value="networkpub_post_types" />
												<input type="submit" name="submit" class="button-primary" value="'.__('Update').'" />
											</div>
										</form>
									</div>
								</div>
							</div>
						</a>
						<div style="font-size:13px;margin:40px 0px 0px 0px;">
                            <div class="networkpublisher_header">
								<strong>'.__('Note').'</strong>
							</div>
							<div class="networkpublisher_content_box">
                                '.__('If you decide to stop using this plugin permanently, please remove your blog URL from').' <a href="http://www.linksalpha.com/websites" target="_blank">'.__('LinksAlpha Website Manager').'</a>. '.__('Otherwise, your blog posts may continue to get posted even after you remove this plugin.').'
                            </div>
						</div>
					</div>
				</div>
			</div>
			<div style="vertical-align:top;padding-left:2%;text-align:right;width:20%;float:left;">
				<div style="margin-bottom:20px;text-align:center;width:150px;margin-top:10px;">
					<a href="'.networkpub_postbox_url().'" style="text-decoration:none;">
						<div class="networkpublisher_button_yellow">'.__('View Postbox').'</div>
					</a>
				</div>
				<div class="networkpublisher_header_3">'.__('Supported Networks').'</div>
				<div class="networkpublisher_content_box_3">
					'.networkpub_supported_networks().'
				</div>
			</div>
			</div>';
	echo $html;
}


function networkpub_add($api_key) {
	if (!$api_key) {
		$errdesc = networkpub_error_msgs('invalid key');
		echo $errdesc;
		return;
	}
	$url = get_bloginfo('url');
	if (!$url) {
		$errdesc = networkpub_error_msgs('invalid url');
		echo $errdesc;
		return;
	}
	$desc = get_bloginfo('description');
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!empty($options['id'])) {
		$id = $options['id'];
	} elseif (!empty($options['id_2'])) {
		$id = $options['id_2'];
	} else {
		$id = '';
	}
	$url_parsed = parse_url($url);
	$url_host = $url_parsed['host'];
	if( substr_count($url, 'localhost') or strpos($url_host, '192.168.') === 0 or strpos($url_host, '127.0.0') === 0 or (strpos($url_host, '172.') === 0 and (int)substr($url_host, 4, 2) > 15 and (int)substr($url_host, 4, 2) < 32 ) or strpos($url_host, '10.') === 0 ) {
		$errdesc = networkpub_error_msgs('localhost url');
		echo $errdesc;
		return FALSE;
	}
	$link   = 'http://www.linksalpha.com/a/networkpubaddone';
	// Build Params
	$params = array('url'=>urlencode($url),
					'key'=>$api_key,
					'plugin'=>'nw',
					'version'=>NETWORKPUB_PLUGIN_VERSION,
					'all_keys'=>$options['api_key'],
					'id'=>$id);
	//HTTP Call
	$response_full = networkpub_http_post($link,$params);
	$response_code = $response_full[0];
	if ($response_code != 200) {
		$errdesc = networkpub_error_msgs($response_full[1]);
		echo $errdesc;
		return FALSE;
	}
	$response = networkpub_json_decode($response_full[1]);
	if ($response->errorCode > 0) {
		$errdesc = networkpub_error_msgs($response->errorMessage);
		echo $errdesc;
		return FALSE;
	}
	//Update options - Site id
	$options['id_2'] = $response->results->id;
	//Update options - Network Keys
	if(empty($options['api_key'])) {
		$options['api_key'] = $response->results->api_key;	
	} else {
		$option_api_key_array = explode(',', $options['api_key']);
		$option_api_key_new = $response->results->api_key;
		$option_api_key_new_array = explode(',', $option_api_key_new);
		foreach($option_api_key_new_array as $key=>$val) {
			if(!in_array($val, $option_api_key_array)) {
				$options['api_key'] = $options['api_key'].','.$val;
			}
		}
	}
	//Save
	update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
	//Return
	echo '<div class="updated fade wrap" style="padding:5px;text-align:center">'.__('API Key has been added successfully').'</div>';
	return;
}


function networkpub_load() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if (empty($options['api_key'])) {
		$html = '<div class="networkpublisher_msg_error">'.__('You have not added an API Key').'</div>';
		return $html;
	}
	$link = 'http://www.linksalpha.com/a/networkpubget';
	$body = array('key'=>$options['api_key'], 'version'=>2);
	$response_full = networkpub_http_post($link, $body);
	$response_code = $response_full[0];
	if ($response_code != 200) {
		$errdeschtml = networkpub_error_msgs('misc');
		return $errdeschtml;
	}
	$response = networkpub_json_decode($response_full[1]);
	if($response->errorCode > 0) {
		$html = '<div class="networkpublisher_msg_error">'.__('Error occured while trying to load the API Keys. Please try again later.').'</div>';
		return $html;
	}
	if(count($response->results_deleted)) {
		$option_api_key_array = explode(',', $options['api_key']);
		foreach($response->results_deleted as $row) {
			if(in_array($row, $option_api_key_array)) {
				$pos = $option_api_key_array[$row];
				unset($option_api_key_array[$pos]);
			}
		}
		$api_key = implode(",", $option_api_key_array);
		$options['api_key'] = $api_key;
		update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
	}
	if(!count($response->results)) {
		return '<div class="networkpublisher_msg_error">'.__('You have not added an API Key').'</div>';
	}
	if(count($response->results) == 1) {
		$html = '<div style="padding:0px 10px 10px 10px;">'.NETWORKPUB_CURRENTLY_PUBLISHING.'&nbsp;'.count($response->results).'&nbsp;'.NETWORKPUB_SOCIAL_NETWORK.'</div>';	
	} else {
		$html = '<div style="padding:0px 10px 10px 10px;">'.NETWORKPUB_CURRENTLY_PUBLISHING.'&nbsp;'.count($response->results).'&nbsp;'.NETWORKPUB_SOCIAL_NETWORKS.'</div>';
	}
	$html .= '<table class="networkpublisher_added"><tr><th>'.__('Network Account').'</th><th>'.__('Options').'</th><th>'.__('Remove').'</th></tr>';
	$i = 1;
	foreach($response->results as $row) {
		if($row->auth_expired) {
			$auth_error_class = 'class="networkpublisher_auth_error"';
			$auth_error_image = '<img alt="'.__('Authorization provided to LinksAlpha.com on this account has expired. Please Add the Account again to be able to publish content').'" title="'.__('Authorization provided to LinksAlpha.com on this account has expired. Please Add the Account again to be able to publish content').'" src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;';
		} else {
			$auth_error_class = '';
			$auth_error_image = '';
		}
		$html .= '<tr id="r_key_'.$row->api_key.'">';
		if($i%2) {
			$html .= '<td '.$auth_error_class.'>';
		} else {
			$html .= '<td '.$auth_error_class.' style="background-color:#F7F7F7;">';
		}
		$html .= $auth_error_image.'<a target="_blank" href="'.$row->profile_url.'">'.$row->name.'</a></td>';
		if($i%2) {
			$html .= '<td '.$auth_error_class.' style="text-align:center;">';
		} else {
			$html .= '<td '.$auth_error_class.' style="text-align:center;background-color:#F7F7F7;">';
		}
		$html .= '<a href="https://www.linksalpha.com/a/networkpuboptions?api_key='.$row->api_key.'&id='.$options['id_2'].'&version='.networkpub_version().'&KeepThis=true&TB_iframe=true&height=465&width=650" title="Publish Options" class="thickbox" type="button" />'.__('Options').'</a></td>';
		if($i%2) {
			$html .= '<td '.$auth_error_class.' style="text-align:center;">';
		} else {
			$html .= '<td '.$auth_error_class.' style="text-align:center;background-color:#F7F7F7;">';
		}
		$html .= '<a href="#" id="key_'.$row->api_key.'" class="networkpublisherremove">'.__('Remove').'</a></td>';
		$html .= '</tr>';
		$i++;
	}
	$html .= '</table>';
	return $html;
}


function networkpub_post_types() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if (empty($options['networkpub_post_types'])) {
		$post_types_in_options = array();
	} else {
		$post_types_in_options = explode(',', $options['networkpub_post_types']);
	}
	if(!function_exists('get_post_types')) {
		return;
	}
	$html = '';
	$args=array( 'public'   => true,
                 '_builtin' => false
               ); 
	$output = 'names';
    $operator = 'and';
    $post_types=get_post_types($args,$output,$operator);
    array_unshift($post_types, 'post', 'page');
	foreach ($post_types  as $post_type ) {
		$checked = '';
		if(in_array($post_type, $post_types_in_options)) {
			$checked = 'checked';
		}
    	$html .= '<div style="padding-bottom:2px;"><input id="networkpub_post_type_'.$post_type.'" type="checkbox" value="'.$post_type.'" name="networkpub_post_types[]" '.$checked.' />&nbsp;<label for="networkpub_post_type_'.$post_type.'" >'.$post_type.'</label></div>';
    }
    return $html;
}


function networkpub_remove() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if (!empty($_POST['networkpub_key'])) {
		$key_full = strip_tags($_POST['networkpub_key']);
		$key_only = substr($key_full, 4);
		$link = 'http://www.linksalpha.com/a/networkpubremove';
		$body = array('id'=>$options['id_2'], 'key'=>$key_only);
		$response_full = networkpub_http_post($link, $body);
		$response_code = $response_full[0];
		if ($response_code != 200) {
			$errdesc = networkpubnw_error_msgs($response_full[1]);
			echo $errdesc;
			return;
		}
		$api_key = $options['api_key'];
		$api_key_array = explode(',', $api_key);
		$loc = array_search($key_only, $api_key_array);
		if($loc !== FALSE) {
			unset($api_key_array[$loc]);
		}
		$api_key = implode(",", $api_key_array);
		$options['api_key'] = $api_key;
		update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
		echo $key_full;
		return;
	}
}


function networkpub_update_option($option, $value) {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	$options[$option] = $value;
	update_option(NETWORKPUB_WIDGET_NAME_INTERNAL, $options);
	return;
}


function networkpub_pages() {
	if ( function_exists('add_submenu_page') ) {
		$page = add_submenu_page('plugins.php', NETWORKPUB_WIDGET_NAME, NETWORKPUB_WIDGET_NAME, 'manage_options', NETWORKPUB_WIDGET_NAME_INTERNAL, 'networkpub_conf');
		if ( is_admin() ) {
			$page = add_submenu_page('edit.php', 	NETWORKPUB_WIDGET_NAME_POSTBOX, NETWORKPUB_WIDGET_NAME_POSTBOX, 'manage_options', NETWORKPUB_WIDGET_NAME_POSTBOX_INTERNAL, 'networkpub_postbox');
		}
	}
}


function networkpub_postbox() {
	$html  = '<div class="wrap"><div class="icon32" id="networkpubisher_laicon"><br /></div><h2>'.NETWORKPUB_WIDGET_NAME.' - '.NETWORKPUB_WIDGET_NAME_POSTBOX.'</h2></div>';
	$html .= '<iframe id="networkpub_postbox" src="http://www.linksalpha.com/post?source=wordpress&netpublink='.urlencode(NETWORKPUB_WP_PLUGIN_URL).'&sourcelink='.urlencode(networkpub_postbox_url()).'#'.urlencode(networkpub_postbox_url()).'" width="1050px;" height="650px;" scrolling="no" style="background-color: transparent; border:none !important;" allowTransparency="allowTransparency" frameBorder="0"></iframe>';
	$html .= '<div style="padding:10px 10px 6px 10px;background-color:#FFFFFF;margin-bottom:15px;margin-top:0px;border:1px solid #F0F0F0;width:1005px;">
				<div style="width:130px;float:left;font-weight:bold;">
					'.__('Share this Plugin').' 
				</div>
				<div style="width:700px">
					<div class="linksalpha-email-button" id="linksalpha_tag_20886785813" data-url="http://www.linksalpha.com" data-text="LinksAlpha - Making Social Media Easy!" data-desc="LinksAlpha provides quick and easy way for companies and users to connect and share on social web. Using LinksAlpha tools, you can integrate Social Media Buttons into your website, Publish your Website Content Automatically to Social Media Sites, and Track Social Media Profiles, all from one place." data-image="http://www.linksalpha.com/images/LALOGO_s175.png"></div>
					<script type="text/javascript" src="http://www.linksalpha.com/social/loader?tag_id=linksalpha_tag_20886785813&fblikefont=arial&vkontakte=1&livejournal=1&twitter=1&xinglang=de&linkedin=1&tumblr=1&hyves=1&fblikelang=en_US&delicious=1&twitterw=110&gpluslang=en-US&gmail=1&weibo=1&posterous=1&xing=1&sonico=1&twitterlang=en&pinterest=1&myspace=1&msn=1&print=1&mailru=1&email=1&counters=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&reddit=1&hotmail=1&netlog=1&twitterrelated=linksalpha&aolmail=1&link=http%3A%2F%2Fwww.linksalpha.com&diigo=1&evernote=1&digg=1&yahoomail=1&yammer=1&stumbleupon=1&instapaper=1&facebookw=90&googleplus=1&fblikeverb=like&fblikeref=linksalpha&halign=left&readitlater=1&v=2&facebook=1&button=googleplus%2Cfacebook%2Clinkedin%2Ctwitter&identica=1"></script>
				</div>
			  </div>';
	echo $html;
	return;
}


function networkpub_supported_networks() {
	$html = '';
	$response_full = networkpub_http('http://www.linksalpha.com/a/networkpubsupported');
	$response_code = $response_full[0];
	if ($response_code != 200) {
		return $html;
	}
	$response = $response_full[1];
	$content = networkpub_json_decode($response);
	if(!$content) {
		return $html;	
	}
	$html .= '<ul style="bullet-style:none ! important;">';
	$i = 0;
	foreach($content as $key=>$val) {
		if($i%2) {
			$bg_color = "#FFFFFF";
		} else {
			$bg_color = "#F7F7F7";
		}
		$html .= '<li style="padding:5px 10px;background-color:'.$bg_color.';margin:0px;"><a href="https://www.linksalpha.com/networks?tab='.$val->type.'" target="_blank" style="text-decoration:none ! important;"><img src="http://www.linksalpha.com/images/'.$val->type.'_icon.png" style="vertical-align:bottom;border:0px;" />&nbsp;'.$val->name.'</a></li>';
		$i++;
	}
	$html .= '</ul>';
	return $html;
}


function networkpub_json_decode($str) {
	if (function_exists("json_decode")) {
	    return json_decode($str);
	} else {
		if (!class_exists('Services_JSON')) {
			require_once("JSON.php");
		}
	    $json = new Services_JSON();
	    return $json->decode($str);
	}
}


function networkpub_http($link) {
    if (!$link) {
		return array(500, 'invalid url');
	}
	if( !class_exists( 'WP_Http' ) ) {
		include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	if (class_exists('WP_Http')) {
		$request = new WP_Http;
		$headers = array( 'Agent' => NETWORKPUB_WIDGET_NAME.' - '.get_bloginfo('url') );
		$response_full = $request->request( $link, array( 'method' => 'GET', 'headers'=>$headers, 'timeout'=>60 ) );
        if(isset($response_full->errors)) {
			return array(500, 'internal error');
		}
		if(!is_array($response_full['response'])) {
            return array(500, 'internal error');
        }
		$response_code = $response_full['response']['code'];
		if ($response_code == 200) {
			$response = $response_full['body'];
			return array($response_code, $response);
		}
		$response_msg = $response_full['response']['message'];
		return array($response_code, $response_msg);
	}
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = NETWORKPUB_WIDGET_NAME.' - '.get_bloginfo('url');
	if($snoop->fetchtext($link)){
		if (strpos($snoop->response_code, '200')) {
			$response = $snoop->results;
			return array(200, $response);
		}
	}
	return array(500, 'internal error');
}


function networkpub_http_post($link, $body) {
    if (!$link) {
		return array(500, 'invalid url');
	}
	if( !class_exists( 'WP_Http' ) ) {
		include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	if (class_exists('WP_Http')) {
		$request = new WP_Http;
		$headers = array( 'Agent' => NETWORKPUB_WIDGET_NAME.' - '.get_bloginfo('url') );
		$response_full = $request->request( $link, array( 'method' => 'POST', 'body' => $body, 'headers'=>$headers, 'timeout'=>60 ) );
		if(isset($response_full->errors)) {
			return array(500, 'internal error');
		}
		if(!is_array($response_full['response'])) {
            return array(500, 'internal error');
        }
		$response_code = $response_full['response']['code'];
		if ($response_code == 200) {
			$response = $response_full['body'];
			return array($response_code, $response);
		}
		$response_msg = $response_full['response']['message'];
		return array($response_code, $response_msg);
	}
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = NETWORKPUB_WIDGET_NAME.' - '.get_bloginfo('url');
	if($snoop->submit($link, $body)){
		if (strpos($snoop->response_code, '200')) {
			$response = $snoop->results;
			return array(200, $response);
		}
	}
	return array(500, 'internal error');
}


function networkpub_error_msgs($errMsg) {
	$arr_errCodes  = explode(";", $errMsg);
	$errCodesCount = count($arr_errCodes);

	switch (trim($arr_errCodes[0])) {

		case 'internal error':
			$html = '<div class="networkpublisher_msg_error">	
                        <div class="networkpublisher_msg_error_header"><b><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;'.__('Unknown Error').'</b></div>
                        <div>'.__('There was an unknown error. Please try again').'.</div>
					    <div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">'.__('LinksAlpha.com Help Desk').'</a></div>
                    </div>';
			return $html;		
			break;
	
		case 'invalid url':
			$html  = '<div class="networkpublisher_msg_error"><b>'.__('Your blog URL is invalid').':</b>'.$arr_errCodes[$errCodesCount-1];			
			if($errCodesCount == 3) {
				$html .= '.&nbsp;'.__('Error Code').'&nbsp;='.$arr_errCodes[$errCodesCount-2];
			}			
			$html .= '<div>
					'.__('You can also').'&nbsp;<a href="http://www.linksalpha.com/websites?show_add=1" target="_blank">'.__('Click here').'</a>'.__(' to enter blog URL on LinksAlpha manually.
					  Also ensure that in ').'<b>'.__('Settings').'->'.__('General').'->"'.__('Blog address (URL)').'"</b> '.__('the URL is filled-in correctly').'.</div> 
					  <div>'.__('If you still face issues then email us at').'&nbsp;<a href="mailto:post@linksalpha.com">post@linksalpha.com</a>&nbsp;'.__('with error description').'.</div>'
                    ;			
            
			return $html;
			break;
		
		case 'localhost url':
			$html  = '<div class="networkpublisher_msg_error"><div><b>'.__('Website/Blog inaccessible').'</b></div>';
			$html .= '<div>'.__('You are trying to use the plugin on ').'<b>localhost</b> '.__('or behind a').' <b>'.__('firewall').'</b>, '.__('which is not supported. Please install the plugin on a Wordpress blog on a live server').'.</div>
				  </div>';
			return $html;
			break;
			
		case 'remote url error':		
			$html  = '<div class="networkpublisher_msg_error"><div><b>'.__('Remote URL error').': </b>'.$arr_errCodes[$errCodesCount-1];
			if($errCodesCount == 3) {
				$html .= '. '.__('Error Code').'&nbsp;='.$arr_errCodes[$errCodesCount-2];
			}
			$html .= '</div>
					<div>
						<b>'.__('Description:').'</b>
						<b>'.__('Please try again').'. </b> '.__('Your site either did not respond (it is extremely slow) or it is not operational').'.
					</div>
					<div>
						'.__('You can also').' <a href="http://www.linksalpha.com/websites?show_add=1" target="_blank">'.__('Click here').'</a> '.__('to enter blog URL on LinksAlpha manually').'. 
						'.__('Also ensure that in').' <b>'.__('Settings').'->'.__('General').'->"'.__('Blog address (URL)').'"</b> '.__('the URL is filled-in correctly').'. 
					</div>
					<div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
				</div>';
			return $html;		
			break;
			
		case 'feed parsing error':
			$html  = '<div class="networkpublisher_msg_error"><div><b>'.__('Feed parsing error').': </b>'.$arr_errCodes[$errCodesCount-1];			
			if($errCodesCount == 3) {
				$html .= '. '.__('Error Code').'=&nbsp;'.$arr_errCodes[$errCodesCount-2];
			}
			$html .= '	</div>
					<div>
						<b>'.__('Description').': </b>
						'.__('Your RSS feed has errors. Pls go to').' <a href=http://beta.feedvalidator.org/ target="_blank">href=http://beta.feedvalidator.org/</a> '.__('to validate your RSS feed').'.
					</div>
                    <div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
				</div>';			
			return $html;		
			break;

		case 'feed not found':
			$html ='<div class="networkpublisher_msg_error">
					<div>
						<b>'.__('We could not find feed URL for your blog').'.</b>
					</div>
					<div>
						<a href="http://www.linksalpha.com/websites?show_add=1" target="_blank">'.__('Click here').'</a> '.__('to enter feed URL on LinksAlpha manually').'.
						'.__('Also ensure that in ').'<b>'.__('Settings').'->'.__('General').'->"'.__('Blog address (URL)').'"</b> '.__('the URL is filled-in correctly').'.
					</div>
					<div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
				</div>';
			return $html;		
			break;
			
		case 'invalid key':
			$html = '<div class="networkpublisher_msg_error">
                        <div class="networkpublisher_msg_error_header"><b><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;Invalid Key</b></div>
                        <div>'.__('The key that you entered is incorrect. Please input a valid <a target="_blank" href="http://www.linksalpha.com/account/your_api_key">User</a> or <a target="_blank" href="http://www.linksalpha.com/networks">Network</a> API key and try again').'.</div>
                        <div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
                    </div>';
			return $html;
			break;
			
		case 'network key':
			$html = '<div class="networkpublisher_msg_error">
					<div>
						'.__('Invalid Key').':&nbsp;'.__('Please make sure you enter the ').'<a target="_blank" href="http://www.linksalpha.com/account/your_api_key">'.__('User API key').'</a>'.__(', and not the key of a Network').'.
					</div>
				</div>';			
			return $html;
			break;
			
		case 'subscription upgrade required':
			$html = '<div class="networkpublisher_msg_error">
					<b>'.__('Upgrade account').'.</b> '.__('Please').' <a href="http://www.linksalpha.com/account" target="_blank">'.__('upgrade your subscription').'</a> '.__('to continue using current number of networks and websites').'.
				</div>';
			return $html;
			break;
		
		case 'multiple accounts':
			$html = '<div class="networkpublisher_msg_error">
                        <div class="networkpublisher_msg_error_header"><b><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;'.__('Account Error').'</b></div>
                        <div>'.__('The key that you entered is for a LinksAlpha account that is different from the currently used account for this website. You can use API key from only one account on this website. Please input a valid <a target="_blank" href="http://www.linksalpha.com/account/your_api_key">User</a> or <a target="_blank" href="http://www.linksalpha.com/networks">Network</a> API key and try again').'.</div>
                        <div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
                    </div>';
			return $html;
			break;
			
		case 'no networks':
			$html = '<div class="networkpublisher_msg_error">
                        <div class="networkpublisher_msg_error_header"><b><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;'.__('No Network Accounts Found').'</b></div>
                        <div>'.__('You should first authorize LinksAlpha to publish to your social network profiles').' <a target="_blank" href="http://www.linksalpha.com/networks">'.__('Click Here').'</a> '.__('to get started.').'</div>
                        <div>'.__('If you still face issues, please open a ticket at: ').'<a target="_blank" href="http://support.linksalpha.com/">LinksAlpha.com '.__('Help Desk').'</a></div>
                    </div>';
			return $html;
			break;
			
		default:
			$html = '<div class="networkpublisher_msg_error">
						<div class="networkpublisher_msg_error_header"><b><img src="'.NETWORKPUB_WP_PLUGIN_URL .'alert.png" style="vertical-align:text-bottom;" />&nbsp;'.__('Not able to connect to').' <a href="http://www.linksalpha.com" target="_blank">'.__('LinksAlpha.com').'</a></b></div>
						<div style="text-align:left;">
							<div class="networkpublisher_msg_error_item">'.__('Your website is not able to connect to LinksAlpha.com. This might be due to:').'</div>
							<div>
								<ul class="networkpublisher_msg_error_list">
									<li class="networkpublisher_msg_error_item">'.__('An issue with your hosting company where they might be preventing HTTP calls to external sites.').'</li>
									<li class="networkpublisher_msg_error_item">'.__('A plugin you are using has overwritten the .htaccess preventing access to external websites').'</li>
								</ul>
							</div>
							<div class="networkpublisher_msg_error_item">'.__('As an alternative you can configure publishing as described in Method-2 of the -').' <a href="http://help.linksalpha.com/networks/getting-started" target="_blank">'.__('Getting Started help document.').'</a></div>
						</div>
					</div>';
			return $html;
			break;
	}
}


function networkpub_get_plugin_dir() {
	global $wp_version;
	if ( version_compare($wp_version, '2.8', '<') ) {
		$path = dirname(plugin_basename(__FILE__));
		if ( $path == '.' )
		$path = '';
		$plugin_path = trailingslashit( plugins_url( $path ) );
	}
	else {
		$plugin_path = trailingslashit( plugins_url( '', __FILE__) );
	}
	return $plugin_path;
}


function networkpub_activate() {
	$networkpub_eget = get_bloginfo('admin_email'); $networkpub_uget = get_bloginfo('url'); $networkpub_nget = get_bloginfo('name');
	$networkpub_dget = get_bloginfo('description'); $networkpub_cget = get_bloginfo('charset'); $networkpub_vget = get_bloginfo('version');
	$networkpub_lget = get_bloginfo('language');
	$link='http://www.linksalpha.com/a/bloginfo';
	$networkpub_bloginfo = array('email'=>$networkpub_eget, 'url'=>$networkpub_uget, 'name'=>$networkpub_nget, 'desc'=>$networkpub_dget, 'charset'=>$networkpub_cget, 'version'=>$networkpub_vget, 'lang'=>$networkpub_lget,  'plugin'=>'nw');
	networkpub_http_post($link, $networkpub_bloginfo);
	//Convert
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(empty($options['id_2']) or empty($options['api_key'])) {
		return;
	}
	// Build Params
	$link = 'http://www.linksalpha.com/a/networkpubconvertdirect';
	$params = array('id'=>$options['id_2'],
					'key'=>$options['api_key'],
					'plugin'=>'nw',
					);
	//HTTP Call
	$response_full = networkpub_http_post($link, $params);
	$response_code = $response_full[0];
	return;
}


function networkpub_deactivate() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(empty($options['id_2']) or empty($options['api_key'])) {
		return;
	}
	// Build Params
	$link = 'http://www.linksalpha.com/a/networkpubconvertfeed';
	$params = array('id'=>$options['id_2'],
					'key'=>$options['api_key'],
					);
	//HTTP Call
	$response_full = networkpub_http_post($link, $params);
	$response_code = $response_full[0];
	return;
}


function networkpub_pushpresscheck() {
	$active_plugins = get_option('active_plugins');
	$pushpress_plugin = 'pushpress/pushpress.php';
	$this_plugin_key = array_search($pushpress_plugin, $active_plugins);
	if ($this_plugin_key) {
		$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
		if(array_key_exists('id', $options)) {
			if(!empty($options['id_2'])) {
				$link = 'http://www.linksalpha.com/a/pushpress';
				$body = array('id'=>$options['id_2']);
				$response_full = networkpub_http_post($link, $body);
				$response_code = $response_full[0];	
			}	
		}
	}
}


function networkpub_postbox_url() {
	global $wp_version;
	if ( version_compare($wp_version, '3.0.0', '<') ) {
		$admin_url = site_url().'/wp-admin/edit.php?page='.NETWORKPUB_WIDGET_NAME_POSTBOX_INTERNAL;	
	} else {
		$admin_url = site_url().'/wp-admin/edit.php?page='.NETWORKPUB_WIDGET_NAME_POSTBOX_INTERNAL;
	}
	return $admin_url;
}


function networkpub_version() {
	return NETWORKPUB_PLUGIN_VERSION;
}


function networkpub_get_posts() {
	if(!empty($_GET['linksalpha_request_type'])) {
		$args = array(
	    'numberposts'     => 20,
	    'offset'          => 0,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'post',
	    'post_status'     => 'publish' );
		$posts_array = get_posts( $args );
		$html  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>';
		$html .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>';
		$html .= '<script type="text/javascript" src="'.NETWORKPUB_WP_PLUGIN_URL.'jquery.ba-postmessage.min.js"></script>';
		$html .= '<script type="text/javascript" src="'.NETWORKPUB_WP_PLUGIN_URL.'networkpub.js"></script>';
		$html .= '</head><body style="margin:0 !important;padding:0 !important;">';
		$html .= '<select style="margin:0 !important;padding:0 !important;width:300px !important;" id="site_links" name="site_links" class="post_network" >';
		$html .= '<option class="post_network" value="" selected >---</option>';
		foreach( $posts_array as $post ) {
			$params = array();
			$post_link = get_permalink($post->ID);
			$params['content_link'] = $post_link;
			$params['title'] = trim(strip_tags($post->post_title));
			$params['content_text'] = trim(strip_tags($post->post_title));
			$params['content_body'] = trim(strip_tags($post->post_content));
			$post_image = networkpub_thumbnail_link( $post_id, $post->post_content );
			if($post_image) {
				$params['content_image'] = $post_image;
			}
			$form_data = http_build_query($params);
			$html .= '<option class="post_network" value="'.$form_data.'">'.$post->post_title.'</option>';
		}
		$html .= '</select></body></html>';
		echo $html;
	}
	return;
}


function networkpub_fb_langs() {
	$langs = array();
	$response_full = networkpub_http_post("http://www.facebook.com/translations/FacebookLocales.xml", array());
	$response_code = $response_full[0];
	if ($response_code == 200) {
		preg_match_all('/<locale>\s*<englishName>([^<]+)<\/englishName>\s*<codes>\s*<code>\s*<standard>.+?<representation>([^<]+)<\/representation>/s', utf8_decode($response_full[1]), $langslist, PREG_PATTERN_ORDER);
		foreach ($langslist[1] as $key=>$val) {
			$langs[$langslist[2][$key]] = $val;
		}
	} else {
		$langs['default'] = "Default";
	}
	return $langs;
}


function networkpub_add_metatags() {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!is_array($options)) {
		return ;
	}
	if(!array_key_exists('networkpub_metatags_facebook', $options)) {
		return ;
	}
	$networkpub_metatags_facebook = $options['networkpub_metatags_facebook'];
	$networkpub_metatags_googleplus = $options['networkpub_metatags_googleplus'];
	if(!$networkpub_metatags_facebook and !$networkpub_metatags_googleplus) {
		return ;
	}
    global $posts;
    //Site name
    $og_site_name = get_bloginfo('name');
    //Post or Page
    if ( is_single() || is_page() ) {
		//Post data
        $post_data = get_post( $posts[0]->ID, ARRAY_A );
        //Title
        $og_title = networkpub_prepare_text($post_data['post_title']);
        //Link
        $og_link = get_permalink($posts[0]->ID);
        //Image Link
        $og_link_image = networkpub_thumbnail_link($posts[0]->ID, $post_data['post_content']);
        //Content
        if(!empty($post_data['post_excerpt'])) {
            $og_desc = $post_data['post_excerpt'];
        } else {
            $og_desc = $post_data['post_content'];
        }
		$og_desc = networkpub_prepare_text($og_desc);
		//Facebook Page Type
	    if(!empty($options['networkpub_facebook_page_type'])) {
			$og_type = $options['networkpub_facebook_page_type'];	
		} else {
			$og_type = 'article';	
		}
    } else {
        //Title
		$og_title = networkpub_prepare_text($og_site_name);
        //Link
        $og_link = get_bloginfo('url');
		//Image Link
		$og_link_image = '';
        //Desc
		$og_desc = get_bloginfo('description');
		$og_desc = networkpub_prepare_text($og_desc);
		//Type
		$og_type = 'website';
    }
	//Facebook Locale
	if(!empty($options['networkpub_lang_facebook'])) {
		$og_locale = $options['networkpub_lang_facebook'];	
	} else {
		$og_locale = 'en_US';	
	}
	//Facebook App ID
	if(!empty($options['networkpub_facebook_app_id'])) {
		$og_fb_app_id = $options['networkpub_facebook_app_id'];	
	} else {
		$og_fb_app_id = '';	
	}
	//Google Plus Page Type
	if(!empty($options['networkpub_googleplus_page_type'])) {
		$og_type_google = $options['networkpub_googleplus_page_type'];	
	} else {
		$og_type_google = 'Article';
	}
	//Show tags
	if($networkpub_metatags_facebook) {
		networkpub_build_meta_facebook($og_site_name, $og_title, $og_link, $og_link_image, $og_desc, $og_type, $og_locale, $og_fb_app_id);
	}
	if($networkpub_metatags_googleplus) {
		networkpub_build_meta_googleplus($og_title, $og_link_image, $og_desc, $og_type_google);
	}
	return;
}


function networkpub_build_meta_facebook($og_site_name, $og_title, $og_link, $og_link_image, $og_desc, $og_type, $og_locale, $og_fb_app_id) {
	$opengraph_meta = '';
	if($og_site_name) {
		$opengraph_meta .= "\n<meta property=\"og:site_name\" content=\"" . $og_site_name . "\" />";
	}
	if($og_title) {    
		$opengraph_meta .= "\n<meta property=\"og:title\" content=\"" . $og_title . "\" />";
	}
	if($og_link) {
		$opengraph_meta .= "\n<meta property=\"og:url\" content=\"" . $og_link . "\" />";
	}
	if($og_link_image) {
		$opengraph_meta .= "\n<meta property=\"og:image\" content=\"" . $og_link_image . "\" />";
	}
	if($og_desc) {
		$opengraph_meta .= "\n<meta property=\"og:description\" content=\"" . $og_desc . "\" />";
	}
	if($og_type) {
		$opengraph_meta .= "\n<meta property=\"og:type\" content=\"". $og_type ."\" />";
	}
	if($og_locale) {
		$opengraph_meta .= "\n<meta property=\"og:locale\" content=\"" . strtolower($og_locale) . "\" />";
	}
	if($og_fb_app_id) {
		$opengraph_meta .= "\n<meta property=\"fb:app_id\" content=\"" . trim($og_fb_app_id) . "\" />";
	}
	echo "\n<!-- Facebook Open Graph metatags added by WordPress plugin - Network Publisher. Get it at: http://wordpress.org/extend/plugins/network-publisher/ -->" . $opengraph_meta . "\n<!-- End Facebook Open Graph metatags-->\n";
}


function networkpub_build_meta_googleplus($og_title, $og_link_image, $og_desc, $og_type) {
	$opengraph_meta = '';
	if($og_title) {
		$opengraph_meta  = "\n<meta itemprop=\"name\"  content=\"" . $og_title . "\" />";
	}
	if($og_link_image) {
		$opengraph_meta .= "\n<meta itemprop=\"image\" content=\"" . $og_link_image . "\" />";
	}
	if($og_desc) {
		$opengraph_meta .= "\n<meta itemprop=\"description\" content=\"" . $og_desc . "\" />";
	}
	echo "\n<!-- Google Plus metatags added by WordPress plugin - Network Publisher. Get it at: http://wordpress.org/extend/plugins/network-publisher/ -->" . $opengraph_meta . "\n<!-- End Google Plus metatags-->\n";
}


function networkpub_html_schema($attr) {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(!is_array($options)) {
		return ;
	}
	if(!array_key_exists('networkpub_metatags_facebook', $options)) {
		return ;
	}
	$networkpub_metatags_facebook = $options['networkpub_metatags_facebook'];
	$networkpub_metatags_googleplus = $options['networkpub_metatags_googleplus'];
	if(!$networkpub_metatags_facebook and !$networkpub_metatags_googleplus) {
		return ;
	}
	if($networkpub_metatags_facebook) {
		$attr .= " xmlns:og=\"http://opengraphprotocol.org/schema/\"";
		$attr .= " xmlns:fb=\"http://www.facebook.com/2008/fbml\"";
		//$attr .= " xmlns:fb=\"http://ogp.me/ns/fb#\">";
	}
	if($networkpub_metatags_googleplus) {
		//Type
		if(!empty($options['networkpub_googleplus_page_type'])) {
			$og_type = $options['networkpub_googleplus_page_type'];	
		} else {
			$og_type = 'Article';
		}
		$attr .= " itemscope itemtype=\"http://schema.org/".$og_type."\"";
	}
	return $attr;
}


function networkpub_prepare_text($text) {
	$text = stripslashes($text);
	$text = strip_tags($text);
	$text = preg_replace("/\[.*?\]/", '', $text);
	$text = preg_replace('/([\n \t\r]+)/', ' ', $text); 
	$text = preg_replace('/( +)/', ' ', $text);
	$text = preg_replace('/\s\s+/', ' ', $text);
	$text = networkpub_prepare_string($text, 310);
	$text = networkpub_smart_truncate($text, 300);
	$text = trim($text);
	$text = htmlspecialchars($text);
	return $text;
}


function networkpub_smart_truncate($string, $required_length) {
  $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
  $parts_count = count($parts);
  $length = 0;
  $last_part = 0;
  for (; $last_part < $parts_count; ++$last_part) {
    $length += strlen($parts[$last_part]);
    if ($length > $required_length) {break;}
  }
  return implode(array_slice($parts, 0, $last_part));
}


function networkpub_prepare_string($string, $string_length) {
	$final_string='';
	$utf8marker=chr(128); 
    $count=0; 
    while(isset($string{$count})){ 
		if($string{$count}>=$utf8marker) { 
			$parsechar=substr($string,$count,2);
			$count+=2; 
		} else { 
			$parsechar=$string{$count}; 
			$count++; 
		}
		if($count > $string_length) {
			return $final_string;
		}
		$final_string=$final_string.$parsechar;
    }
	return $final_string;
}


function networkpub_thumbnail_link($post_id, $post_content) {
	$options = get_option(NETWORKPUB_WIDGET_NAME_INTERNAL);
	if(is_array($options)) {
		if(!empty($options['networkpub_custom_field_image'])) {
			$networkpub_custom_field_image = $options['networkpub_custom_field_image']; 
			$post_data_custom = get_post_custom( $post_id, ARRAY_A );
			if(!empty($post_data_custom[$networkpub_custom_field_image])) {
				if(is_array($post_data_custom[$networkpub_custom_field_image])) {
					return  $post_data_custom[$networkpub_custom_field_image][0];
				}
			}
		}
	}
    if(function_exists('get_post_thumbnail_id') and function_exists('wp_get_attachment_image_src')) {
        $src = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'medium');
        if($src) {
            $src = $src[0];
            return $src;
        }
    }
	if(!$post_content) {
		return False;
	}
    if(class_exists("DOMDocument") and function_exists('simplexml_import_dom')) {
		libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if(!($doc->loadHTML($post_content))){
			return False;
		}
		try {
			$xml = @simplexml_import_dom($doc);
			if($xml) {
				$images = $xml->xpath('//img');
				if(!empty($images)) {
					return $images[0]['src'];
				}
			} else {
				return False;	
			}
		} catch (Exception $e) {
			return False;
		}
    }
    return False;
}


register_deactivation_hook( __FILE__, 'networkpub_deactivate' );


?>
