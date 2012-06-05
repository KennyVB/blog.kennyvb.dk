<?php
class s2_frontend extends s2class {
/* ===== template and filter functions ===== */
	/**
	Display our form; also handles (un)subscribe requests
	*/
	function shortcode($atts) {
		extract(shortcode_atts(array(
			'hide'  => '',
			'id'    => '',
			'url' => '',
			'nojs' => 'false',
			'link' => '',
			'size' => 20
			), $atts));

		// if link is true return a link to the page with the ajax class
		if ( $link !== '' && !is_user_logged_in() ) {
			$this->s2form = "<a href=\"" . get_permalink($this->subscribe2_options['s2page']) . "\" class=\"s2popup\">" . $link . "</a>\r\n";
			return $this->s2form;
		}

		// Apply filters to button text
		$unsubscribe_button_value = apply_filters('s2_unsubscribe_button', __('Unsubscribe', 'subscribe2'));
		$subscribe_button_value = apply_filters('s2_subscribe_button', __('Subscribe', 'subscribe2'));

		// if a button is hidden, show only other
		if ( $hide == 'subscribe' ) {
			$this->input_form_action = "<input type=\"submit\" name=\"unsubscribe\" value=\"" . esc_attr($unsubscribe_button_value) . "\" />";
		} elseif ( $hide == 'unsubscribe' ) {
			$this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . esc_attr($subscribe_button_value) . "\" />";
		} else {
			// both form input actions
			$this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . esc_attr($subscribe_button_value) . "\" />&nbsp;<input type=\"submit\" name=\"unsubscribe\" value=\"" . esc_attr($unsubscribe_button_value) . "\" />";
		}
		// if ID is provided, get permalink
		if ( $id ) {
			$url = get_permalink( $id );
		} elseif ( $this->subscribe2_options['s2page'] > 0 ) {
			$url = get_permalink( $this->subscribe2_options['s2page'] );
		} else {
			$url = get_site_url();
		}
		// build default form
		if ( $nojs == 'true' ) {
			$this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"s2email\">" . __('Your email:', 'subscribe2') . "</label><br /><input type=\"text\" name=\"email\" id=\"s2email\" value=\"\" size=\"" . $size . "\" /></p><p>" . $this->input_form_action . "</p></form>";
		} else {
			$this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"s2email\">" . __('Your email:', 'subscribe2') . "</label><br /><input type=\"text\" name=\"email\" id=\"s2email\" value=\"" . __('Enter email address...', 'subscribe2') . "\" size=\"" . $size . "\" onfocus=\"if (this.value == '" . __('Enter email address...', 'subscribe2') . "') {this.value = '';}\" onblur=\"if (this.value == '') {this.value = '" . __('Enter email address...', 'subscribe2') . "';}\" /></p><p>" . $this->input_form_action . "</p></form>\r\n";
		}
		$this->s2form = $this->form;

		global $user_ID;
		get_currentuserinfo();
		if ( $user_ID ) {
			$this->s2form = $this->profile;
		}
		if ( isset($_POST['subscribe']) || isset($_POST['unsubscribe']) ) {
			global $wpdb, $user_email;
			if ( !is_email($_POST['email']) ) {
				$this->s2form = $this->form . $this->not_an_email;
			} elseif ( $this->is_barred($_POST['email']) ) {
				$this->s2form = $this->form . $this->barred_domain;
			} else {
				$this->email = $this->sanitize_email($_POST['email']);
				$this->ip = $_POST['ip'];
				// does the supplied email belong to a registered user?
				$check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email = '$this->email'");
				if ( '' != $check ) {
					// this is a registered email
					$this->s2form = $this->please_log_in;
				} else {
					// this is not a registered email
					// what should we do?
					if ( isset($_POST['subscribe']) ) {
						// someone is trying to subscribe
						// lets see if they've tried to subscribe previously
						if ( '1' !== $this->is_public($this->email) ) {
							// the user is unknown or inactive
							$this->add($this->email);
							$status = $this->send_confirm('add');
							// set a variable to denote that we've already run, and shouldn't run again
							$this->filtered = 1;
							if ( $status ) {
								$this->s2form = $this->confirmation_sent;
							} else {
								$this->s2form = $this->error;
							}
						} else {
							// they're already subscribed
							$this->s2form = $this->already_subscribed;
						}
						$this->action = 'subscribe';
					} elseif ( isset($_POST['unsubscribe']) ) {
						// is this email a subscriber?
						if ( false == $this->is_public($this->email) ) {
							$this->s2form = $this->form . $this->not_subscribed;
						} else {
							$status = $this->send_confirm('del');
							// set a variable to denote that we've already run, and shouldn't run again
							$this->filtered = 1;
							if ( $status ) {
								$this->s2form = $this->confirmation_sent;
							} else {
								$this->s2form = $this->error;
							}
						}
						$this->action='unsubscribe';
					}
				}
			}
		}
		return $this->s2form;
	} // end shortcode()

	/**
	Display form when deprecated <!--subscribe2--> is used
	*/
	function filter($content = '') {
		if ( '' == $content || !strstr($content, '<!--subscribe2-->') ) { return $content; }

		return preg_replace('|(<p>)?(\n)*<!--subscribe2-->(\n)*(</p>)?|', do_shortcode( '[subscribe2]' ), $content);
	} // end filter()

	/**
	Overrides the default query when handling a (un)subscription confirmation
	This is basically a trick: if the s2 variable is in the query string, just grab the first
	static page and override it's contents later with title_filter()
	*/
	function query_filter() {
		// don't interfere if we've already done our thing
		if ( 1 == $this->filtered ) { return; }

		global $wpdb;

		if ( 0 != $this->subscribe2_options['s2page'] ) {
			return "page_id=" . $this->subscribe2_options['s2page'];
		} else {
			$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status='publish' LIMIT 1");
			if ( $id ) {
				return "page_id=$id";
			} else {
				return "showposts=1";
			}
		}
	} // end query_filter()

	/**
	Overrides the page title
	*/
	function title_filter($title) {
		// don't interfere if we've already done our thing
		if ( in_the_loop() ) {
			return __('Subscription Confirmation', 'subscribe2');
		} else {
			return $title;
		}
	} // end title_filter()

	/**
	Confirm request from the link emailed to the user and email the admin
	*/
	function confirm($content = '') {
		global $wpdb;

		if ( 1 == $this->filtered ) { return $content; }

		$code = $_GET['s2'];
		$action = intval(substr($code, 0, 1));
		$hash = substr($code, 1, 32);
		$id = intval(substr($code, 33));
		if ( $id ) {
			$this->email = $this->sanitize_email($this->get_email($id));
			if ( !$this->email || $hash !== wp_hash($this->email) ) {
				return $this->no_such_email;
			}
		} else {
			return $this->no_such_email;
		}

		// get current status of email so messages are only sent once per emailed link
		$current = $this->is_public($this->email);

		if ( '1' == $action ) {
			// make this subscription active
			$this->message = $this->added;
			if ( '1' != $current ) {
				$this->ip = $_SERVER['REMOTE_ADDR'];
				$this->toggle($this->email);
				if ( $this->subscribe2_options['admin_email'] == 'subs' || $this->subscribe2_options['admin_email'] == 'both' ) {
					( '' == get_option('blogname') ) ? $subject = "" : $subject = "[" . stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)) . "] ";
					$subject .= __('New Subscription', 'subscribe2');
					$subject = html_entity_decode($subject, ENT_QUOTES);
					$message = $this->email . " " . __('subscribed to email notifications!', 'subscribe2');
					$role = array('fields' => array('user_email'), 'role' => 'administrator');
					$wp_user_query = get_users( $role );
					foreach ($wp_user_query as $user) {
						$recipients[] = $user->user_email;
					}
					$headers = $this->headers();
					// send individual emails so we don't reveal admin emails to each other
					foreach ( $recipients as $recipient ) {
						@wp_mail($recipient, $subject, $message, $headers);
					}
				}
			}
			$this->filtered = 1;
		} elseif ( '0' == $action ) {
			// remove this subscriber
			$this->message = $this->deleted;
			if ( '0' != $current ) {
				$this->delete($this->email);
				if ( $this->subscribe2_options['admin_email'] == 'unsubs' || $this->subscribe2_options['admin_email'] == 'both' ) {
					( '' == get_option('blogname') ) ? $subject = "" : $subject = "[" . stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)) . "] ";
					$subject .= __('New Unsubscription', 'subscribe2');
					$subject = html_entity_decode($subject, ENT_QUOTES);
					$message = $this->email . " " . __('unsubscribed from email notifications!', 'subscribe2');
					$role = array('fields' => array('user_email'), 'role' => 'administrator');
					$wp_user_query = get_users( $role );
					foreach ($wp_user_query as $user) {
						$recipients[] = $user->user_email;
					}
					$headers = $this->headers();
					// send individual emails so we don't reveal admin emails to each other
					foreach ( $recipients as $recipient ) {
						@wp_mail($recipient, $subject, $message, $headers);
					}
				}
			}
			$this->filtered = 1;
		}

		if ( '' != $this->message ) {
			return $this->message;
		}
	} // end confirm()

	/**
	Add hook for Minimeta Widget plugin
	*/
	function add_minimeta() {
		if ( $this->subscribe2_options['s2page'] != 0 ) {
			echo "<li><a href=\"" . get_permalink($this->subscribe2_options['s2page']) . "\">" . __('[Un]Subscribe to Posts', 'subscribe2') . "</a></li>\r\n";
		}
	} // end add_minimeta()

	/**
	Add jQuery code and CSS to front pages for ajax form
	*/
	function add_ajax() {
		// enqueue the jQuery script we need and let WordPress handle the dependencies
		wp_enqueue_script('jquery-ui-dialog');
		wp_register_style('jquery-ui-style', apply_filters('s2_jqueryui_css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/ui-darkness/jquery-ui.css'));
		wp_enqueue_style('jquery-ui-style');
	} // end add_ajax()

	/**
	Write Subscribe2 form js code dynamically so we can pull WordPress functions
	*/
	function add_s2_ajax() {
		echo "<script type=\"text/javascript\">\r\n";
		echo "//<![CDATA[\r\n";
		echo "jQuery(document).ready(function() {\r\n";
		echo "	var dialog = jQuery('<div></div>')\r\n";
		echo "	.html('" . do_shortcode('[subscribe2 nojs="true"]') . "')\r\n";
		if ( $this->s2form != $this->form && !is_user_logged_in() ) {
			echo "	.dialog({modal: true, zIndex: 10000, title: '" . __('Subscribe to this blog', 'subscribe2') . "'});\r\n";
		} else {
			echo "	.dialog({autoOpen: false, modal: true, zIndex: 10000, title: '" . __('Subscribe to this blog', 'subscribe2') . "'});\r\n";
		}
		echo "	jQuery('a.s2popup').click(function(){\r\n";
		echo "		dialog.dialog('open');\r\n";
		echo "		return false;\r\n";
		echo "	});\r\n";
		echo "});\r\n";
		echo "//]]>\r\n";
		echo "</script>\r\n";
	} // end add_s2_ajax()
}
?>