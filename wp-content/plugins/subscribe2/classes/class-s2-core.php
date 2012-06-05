<?php
class s2class {
// variables and constructor are declared at the end
	/**
	Load all our strings
	*/
	function load_strings() {
		// adjust the output of Subscribe2 here

		$this->please_log_in = "<p class=\"s2_message\">" . __('To manage your subscription options please', 'subscribe2') . " <a href=\"" . get_option('siteurl') . "/wp-login.php\">" . __('login', 'subscribe2') . "</a>.</p>";

		$this->profile = "<p class=\"s2_message\">" . __('You may manage your subscription options from your', 'subscribe2') . " <a href=\"" . get_option('siteurl') . "/wp-admin/admin.php?page=s2\">" . __('profile', 'subscribe2') . "</a>.</p>";
		if ( $this->s2_mu === true ) {
			global $blog_id, $user_ID;
			if ( !is_blog_user($blog_id) ) {
				// if we are on multisite and the user is not a member of this blog change the link
				$this->use_profile_admin = "<p class=\"s2_message\"><a href=\"" . get_option('siteurl') . "/wp-admin/?s2mu_subscribe=" . $blog_id . "\">" . __('Subscribe', 'subscribe2') . "</a> " . __('to email notifications when this blog posts new content', 'subscribe2') . ".</p>";
			}
		}

		$this->confirmation_sent = "<p class=\"s2_message\">" . __('A confirmation message is on its way!', 'subscribe2') . "</p>";

		$this->already_subscribed = "<p class=\"s2_error\">" . __('That email address is already subscribed.', 'subscribe2') . "</p>";

		$this->not_subscribed = "<p class=\"s2_error\">" . __('That email address is not subscribed.', 'subscribe2') . "</p>";

		$this->not_an_email = "<p class=\"s2_error\">" . __('Sorry, but that does not look like an email address to me.', 'subscribe2') . "</p>";

		$this->barred_domain = "<p class=\"s2_error\">" . __('Sorry, email addresses at that domain are currently barred due to spam, please use an alternative email address.', 'subscribe2') . "</p>";

		$this->error = "<p class=\"s2_error\">" . __('Sorry, there seems to be an error on the server. Please try again later.', 'subscribe2') . "</p>";

		$this->no_page = "<p class=\"s2_error\">" . __('You must to create a WordPress page for this plugin to work correctly.', 'subscribe2') . "</p>";

		$this->mail_sent = "<p class=\"s2_message\">" . __('Message sent!', 'subscribe2') . "</p>";

		$this->mail_failed = "<p class=\"s2_error\">" . __('Message failed! Check your settings and check with your hosting provider', 'subscribe2') . "</p>";

		// confirmation messages
		$this->no_such_email = "<p class=\"s2_error\">" . __('No such email address is registered.', 'subscribe2') . "</p>";

		$this->added = "<p class=\"s2_message\">" . __('You have successfully subscribed!', 'subscribe2') . "</p>";

		$this->deleted = "<p class=\"s2_message\">" . __('You have successfully unsubscribed.', 'subscribe2') . "</p>";

		$this->subscribe = __('subscribe', 'subscribe2'); //ACTION replacement in subscribing confirmation email

		$this->unsubscribe = __('unsubscribe', 'subscribe2'); //ACTION replacement in unsubscribing in confirmation email

		// menu strings
		$this->options_saved = __('Options saved!', 'subscribe2');
		$this->options_reset = __('Options reset!', 'subscribe2');
	} // end load_strings()

/* ===== Install, upgrade, reset ===== */
	/**
	Install our table
	*/
	function install() {
		// include upgrade-functions for maybe_create_table;
		if ( !function_exists('maybe_create_table') ) {
			require_once(ABSPATH . 'wp-admin/install-helper.php');
		}
		$date = date('Y-m-d');
		$sql = "CREATE TABLE $this->public (
			id int(11) NOT NULL auto_increment,
			email varchar(64) NOT NULL default '',
			active tinyint(1) default 0,
			date DATE default '$date' NOT NULL,
			ip char(64) NOT NULL default 'admin',
			PRIMARY KEY (id) )";

		// create the table, as needed
		maybe_create_table($this->public, $sql);

		// safety check if options exist and if not create them
		if ( !is_array($this->subscribe2_options) ) {
			$this->reset();
		}
	} // end install()

	/**
	Upgrade function for the database and settings
	*/
	function upgrade() {
		global $wpdb, $wp_version, $wpmu_version;
		// include upgrade-functions for maybe_add_column;
		if ( !function_exists('maybe_add_column') ) {
			require_once(ABSPATH . 'wp-admin/install-helper.php');
		}
		$date = date('Y-m-d');
		maybe_add_column($this->public, 'date', "ALTER TABLE $this->public ADD date DATE DEFAULT '$date' NOT NULL AFTER active");
		maybe_add_column($this->public, 'ip', "ALTER TABLE $this->public ADD ip char(64) DEFAULT 'admin' NOT NULL AFTER date");

		// let's take the time to check process registered users
		// existing public subscribers are subscribed to all categories
		$users = $this->get_all_registered('ID');
		if ( !empty($users) ) {
			foreach ( $users as $user_ID ) {
				$check_format = get_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), true);
				// if user is already registered update format remove 's2_excerpt' field and update 's2_format'
				if ( 'html' == $check_format ) {
					delete_user_meta($user_ID, 's2_excerpt');
				} elseif ( 'text' == $check_format ) {
					update_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), get_user_meta($user_ID, 's2_excerpt'));
					delete_user_meta($user_ID, 's2_excerpt');
				} elseif ( empty($check_format) ) {
					// no prior settings so create them
					$this->register($user_ID);
				}
				$subscribed = get_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), true);
				if ( strstr($subscribed, '-1') ) {
					// make sure we remove '-1' from any settings
					$old_cats = explode(',', $subscribed);
					$pos = array_search('-1', $old_cats);
					unset($old_cats[$pos]);
					$cats = implode(',', $old_cats);
					update_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), $cats);
				}
				$check_authors = get_user_meta($user_ID, $this->get_usermeta_keyname('s2_authors'), true);
				if ( empty($check_authors) ) {
					update_user_meta($user_ID, $this->get_usermeta_keyname('s2_authors'), '');
				}
			}
		}
		// update the options table to serialized format
		$old_options = $wpdb->get_col("SELECT option_name from $wpdb->options where option_name LIKE 's2%' AND option_name != 's2_future_posts'");

		if ( !empty($old_options) ) {
			foreach ( $old_options as $option ) {
				$value = get_option($option);
				$option_array = substr($option, 3);
				$this->subscribe2_options[$option_array] = $value;
				delete_option($option);
			}
		}
		$this->subscribe2_options['version'] = S2VERSION;
		// ensure that the options are in the database
		require(S2PATH . "include/options.php");
		// correct autoformat to upgrade from pre 5.6
		if ( $this->subscribe2_options['autoformat'] == 'text' ) {
			$this->subscribe2_options['autoformat'] = 'excerpt';
		}
		if ( $this->subscribe2_options['autoformat'] == 'full' ) {
			$this->subscribe2_options['autoformat'] = 'post';
		}
		// change old CAPITALISED keywords to those in {PARENTHESES}; since version 6.4
		$keywords = array('BLOGNAME', 'BLOGLINK', 'TITLE', 'POST', 'POSTTIME', 'TABLE', 'TABLELINKS', 'PERMALINK', 'TINYLINK', 'DATE', 'TIME', 'MYNAME', 'EMAIL', 'AUTHORNAME', 'LINK', 'CATS', 'TAGS', 'COUNT', 'ACTION');
		$keyword = implode('|', $keywords);
		$regex = '/(?<!\{)\b('.$keyword.')\b(?!\{)/xm';
		$replace = '{\1}';
		$this->subscribe2_options['mailtext'] = preg_replace($regex, $replace, $this->subscribe2_options['mailtext']);
		$this->subscribe2_options['notification_subject'] = preg_replace($regex, $replace, $this->subscribe2_options['notification_subject']);
		$this->subscribe2_options['confirm_email'] = preg_replace($regex, $replace, $this->subscribe2_options['confirm_email']);
		$this->subscribe2_options['confirm_subject'] = preg_replace($regex, $replace, $this->subscribe2_options['confirm_subject']);
		$this->subscribe2_options['remind_email'] = preg_replace($regex, $replace, $this->subscribe2_options['remind_email']);
		$this->subscribe2_options['remind_subject'] = preg_replace($regex, $replace, $this->subscribe2_options['remind_subject']);
		update_option('subscribe2_options', $this->subscribe2_options);

		// upgrade old wpmu user meta data to new
		if ( $this->s2_mu === true ) {
			global $s2class_multisite;
			$s2class_multisite->namechange_subscribe2_widget();
			// loop through all users
			foreach ( $users as $user_ID ) {
				// get categories which the user is subscribed to (old ones)
				$categories = get_user_meta($user_ID, 's2_subscribed', true);
				$categories = explode(',', $categories);
				$format = get_user_meta($user_ID, 's2_format', true);
				$autosub = get_user_meta($user_ID, 's2_autosub', true);

				// load blogs of user (only if we need them)
				$blogs = array();
				if ( count($categories) > 0 && !in_array('-1', $categories) ) {
					$blogs = get_blogs_of_user($user_ID, true);
				}

				foreach ( $blogs as $blog ) {
					switch_to_blog($blog->userblog_id);

					$blog_categories = (array)$wpdb->get_col("SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'category'");
					$subscribed_categories = array_intersect($categories, $blog_categories);
					if ( !empty($subscribed_categories) ) {
						foreach ( $subscribed_categories as $subscribed_category ) {
							update_user_meta($user_ID, $this->get_usermeta_keyname('s2_cat') . $subscribed_category, $subscribed_category);
						}
						update_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), implode(',', $subscribed_categories));
					}
					if ( !empty($format) ) {
						update_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), $format);
					}
					if ( !empty($autosub) ) {
						update_user_meta($user_ID, $this->get_usermeta_keyname('s2_autosub'), $autosub);
					}
					restore_current_blog();
				}

				// delete old user meta keys
				delete_user_meta($user_ID, 's2_subscribed');
				delete_user_meta($user_ID, 's2_format');
				delete_user_meta($user_ID, 's2_autosub');
				foreach ( $categories as $cat ) {
					delete_user_meta($user_ID, 's2_cat' . $cat);
				}
			}
		}

		// ensure existing public subscriber emails are all sanitized
		$confirmed = $this->get_public();
		$unconfirmed = $this->get_public(0);
		$public_subscribers = array_merge((array)$confirmed, (array)$unconfirmed);

		foreach ( $public_subscribers as $email ) {
			$new_email = $this->sanitize_email($email);
			if ( $email !== $new_email ) {
				$wpdb->get_results("UPDATE $this->public SET email='$new_email' WHERE CAST(email as binary)='$email'");
			}
		}
		return;
	} // end upgrade()

	/**
	Reset our options
	*/
	function reset() {
		delete_option('subscribe2_options');
		wp_clear_scheduled_hook('s2_digest_cron');
		unset($this->subscribe2_options);
		require(S2PATH . "include/options.php");
		update_option('subscribe2_options', $this->subscribe2_options);
	} // end reset()

/* ===== mail handling ===== */
	/**
	Performs string substitutions for subscribe2 mail tags
	*/
	function substitute($string = '') {
		if ( '' == $string ) {
			return;
		}
		$string = str_replace("{BLOGNAME}", html_entity_decode(get_option('blogname'), ENT_QUOTES), $string);
		$string = str_replace("{BLOGLINK}", get_option('home'), $string);
		$string = str_replace("{TITLE}", stripslashes($this->post_title), $string);
		$link = "<a href=\"" . $this->get_tracking_link($this->permalink) . "\">" . $this->get_tracking_link($this->permalink) . "</a>";
		$string = str_replace("{PERMALINK}", $link, $string);
		if ( strstr($string, "{TINYLINK}") ) {
			$tinylink = file_get_contents('http://tinyurl.com/api-create.php?url=' . urlencode($this->get_tracking_link($this->permalink)));
			if ( $tinylink !== 'Error' || $tinylink != false ) {
				$tlink = "<a href=\"" . $tinylink . "\">" . $tinylink . "</a>";
				$string = str_replace("{TINYLINK}", $tlink, $string);
			} else {
				$string = str_replace("{TINYLINK}", $link, $string);
			}
		}
		$string = str_replace("{DATE}", $this->post_date, $string);
		$string = str_replace("{TIME}", $this->post_time, $string);
		$string = str_replace("{MYNAME}", stripslashes($this->myname), $string);
		$string = str_replace("{EMAIL}", $this->myemail, $string);
		$string = str_replace("{AUTHORNAME}", stripslashes($this->authorname), $string);
		$string = str_replace("{CATS}", $this->post_cat_names, $string);
		$string = str_replace("{TAGS}", $this->post_tag_names, $string);
		$string = str_replace("{COUNT}", $this->post_count, $string);

		return $string;
	} // end substitute()

	/**
	Delivers email to recipients in HTML or plaintext
	*/
	function mail($recipients = array(), $subject = '', $message = '', $type='text') {
		if ( empty($recipients) || '' == $message ) { return; }

		if ( 'html' == $type ) {
			$headers = $this->headers('html');
			if ( 'yes' == $this->subscribe2_options['stylesheet'] ) {
				$mailtext = apply_filters('s2_html_email', "<html><head><title>" . $subject . "</title><link rel=\"stylesheet\" href=\"" . get_stylesheet_uri() . "\" type=\"text/css\" media=\"screen\" /></head><body>" . $message . "</body></html>", $subject, $message);
			} else {
				$mailtext = apply_filters('s2_html_email', "<html><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>", $subject, $message);
			}
		} else {
			$headers = $this->headers();
			$message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
			$message = preg_replace('|&amp;|', '&', $message);
			$message = wordwrap(strip_tags($message), 80, "\n");
			$mailtext = apply_filters('s2_plain_email', $message);
		}

		// Replace any escaped html symbols in subject then apply filter
		$subject = html_entity_decode($subject, ENT_QUOTES);
		$subject = apply_filters('s2_email_subject', $subject);

		// Construct BCC headers for sending or send individual emails
		$bcc = '';
		natcasesort($recipients);
		if ( function_exists('wpmq_mail') || $this->subscribe2_options['bcclimit'] == 1 || count($recipients) == 1 ) {
			// BCCLimit is 1 so send individual emails or we only have 1 recipient
			foreach ( $recipients as $recipient ) {
				$recipient = trim($recipient);
				// sanity check -- make sure we have a valid email
				if ( !is_email($recipient) || empty($recipient) ) { continue; }
				// Use the mail queue provided we are not sending a preview
				if ( function_exists('wpmq_mail') && !$this->preview_email ) {
					@wp_mail($recipient, $subject, $mailtext, $headers, '', 0);
				} else {
					@wp_mail($recipient, $subject, $mailtext, $headers);
				}
			}
			return true;
		} elseif ( $this->subscribe2_options['bcclimit'] == 0 ) {
			// we're not using BCCLimit
			foreach ( $recipients as $recipient ) {
				$recipient = trim($recipient);
				// sanity check -- make sure we have a valid email
				if ( !is_email($recipient) ) { continue; }
				// and NOT the sender's email, since they'll get a copy anyway
				if ( !empty($recipient) && $this->myemail != $recipient ) {
					('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ", $recipient";
					// Bcc Headers now constructed by phpmailer class
				}
			}
			$headers .= "$bcc\n";
		} else {
			// we're using BCCLimit
			$count = 1;
			$batch = array();
			foreach ( $recipients as $recipient ) {
				$recipient = trim($recipient);
				// sanity check -- make sure we have a valid email
				if ( !is_email($recipient) ) { continue; }
				// and NOT the sender's email, since they'll get a copy anyway
				if ( !empty($recipient) && $this->myemail != $recipient ) {
					('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ", $recipient";
					// Bcc Headers now constructed by phpmailer class
				}
				if ( $this->subscribe2_options['bcclimit'] == $count ) {
					$count = 0;
					$batch[] = $bcc;
					$bcc = '';
				}
				$count++;
			}
			// add any partially completed batches to our batch array
			if ( '' != $bcc ) {
				$batch[] = $bcc;
			}
		}
		// rewind the array, just to be safe
		reset($recipients);

		// actually send mail
		if ( isset($batch) && !empty($batch) ) {
			foreach ( $batch as $bcc ) {
					$newheaders = $headers . "$bcc\n";
					$status = @wp_mail($this->myemail, $subject, $mailtext, $newheaders);
			}
		} else {
			$status = @wp_mail($this->myemail, $subject, $mailtext, $headers);
		}
		return $status;
	} // end mail()

	/**
	Construct standard set of email headers
	*/
	function headers($type='text') {
		if ( empty($this->myname) || empty($this->myemail) ) {
			if ( $this->subscribe2_options['sender'] == 'blogname' ) {
				$this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
				$this->myemail = get_option('admin_email');
			} else {
				$admin = $this->get_userdata($this->subscribe2_options['sender']);
				$this->myname = html_entity_decode($admin->display_name, ENT_QUOTES);
				$this->myemail = $admin->user_email;
				// fail safe to ensure sender details are not empty
				if ( empty($this->myname) ) {
					$this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
				}
				if ( empty($this->myemail) ) {
					// Get the site domain and get rid of www.
					$sitename = strtolower( $_SERVER['SERVER_NAME'] );
					if ( substr( $sitename, 0, 4 ) == 'www.' ) {
						$sitename = substr( $sitename, 4 );
					}
					$this->myemail = 'wordpress@' . $sitename;
				}
			}
		}

		$header['From'] = $this->myname . " <" . $this->myemail . ">";
		$header['Reply-To'] = $this->myname . " <" . $this->myemail . ">";
		$header['Return-path'] = "<" . $this->myemail . ">";
		$header['Precedence'] = "list\nList-Id: " . html_entity_decode(get_option('blogname'), ENT_QUOTES) . "";
		if ( $type == 'html' ) {
			// To send HTML mail, the Content-Type header must be set
			$header['Content-Type'] = get_option('html_type') . "; charset=\"". get_option('blog_charset') . "\"";
		} else {
			$header['Content-Type'] = "text/plain; charset=\"". get_option('blog_charset') . "\"";
		}

		// apply header filter to allow on-the-fly amendments
		$header = apply_filters('s2_email_headers', $header);
		// collapse the headers using $key as the header name
		foreach ( $header as $key => $value ) {
			$headers[$key] = $key . ": " . $value;
		}
		$headers = implode("\n", $headers);
		$headers .= "\n";

		return $headers;
	} // end headers()

	/**
	Function to add UTM tracking details to links
	*/
	function get_tracking_link($link) {
		if ( empty($link) ) { return; }
		if ( !empty($this->subscribe2_options['tracking']) ) {
				$delimiter = '?';
				if ( strpos($link, $delimiter) > 0 ) { $delimiter = '&'; }
				return $link . $delimiter . $this->subscribe2_options['tracking'];
		} else {
				return $link;
		}
	} // end get_tracking_link()

	/**
	Sends an email notification of a new post
	*/
	function publish($post = 0, $preview = '') {
		if ( !$post ) { return $post; }

		if ( $this->s2_mu ) {
			global $switched;
			if ( $switched ) { return; }
		}

		if ( $preview == '' ) {
			// we aren't sending a Preview to the current user so carry out checks
			$s2mail = get_post_meta($post->ID, 's2mail', true);
			if ( (isset($_POST['s2_meta_field']) && $_POST['s2_meta_field'] == 'no') || strtolower(trim($s2mail)) == 'no' ) { return $post; }

			// are we doing daily digests? If so, don't send anything now
			if ( $this->subscribe2_options['email_freq'] != 'never' ) { return $post; }

			// is the current post of a type that should generate a notification email?
			// uses s2_post_types filter to allow for custom post types in WP 3.0
			if ( $this->subscribe2_options['pages'] == 'yes' ) {
				$s2_post_types = array('page', 'post');
			} else {
				$s2_post_types = array('post');
			}
			$s2_post_types = apply_filters('s2_post_types', $s2_post_types);
			if ( !in_array($post->post_type, $s2_post_types) ) {
				return $post;
			}

			// Are we sending notifications for password protected posts?
			if ( $this->subscribe2_options['password'] == "no" && $post->post_password != '' ) {
					return $post;
			}

			// Is the post assigned to a format for which we should not be sending posts
			$post_format = get_post_format($post->ID);
			$excluded_formats = explode(',', $this->subscribe2_options['exclude_formats']);
			if ( $post_format !== false && in_array($post_format, $excluded_formats) ) {
				return $post;
			}

			$s2_taxonomies = array('category');
			$s2_taxonomies = apply_filters('s2_taxonomies', $s2_taxonomies);
			$post_cats = wp_get_object_terms($post->ID, $s2_taxonomies, array('fields' => 'ids'));
			$check = false;
			// is the current post assigned to any categories
			// which should not generate a notification email?
			foreach ( explode(',', $this->subscribe2_options['exclude']) as $cat ) {
				if ( in_array($cat, $post_cats) ) {
					$check = true;
				}
			}

			if ( $check ) {
				// hang on -- can registered users subscribe to
				// excluded categories?
				if ( '0' == $this->subscribe2_options['reg_override'] ) {
					// nope? okay, let's leave
					return $post;
				}
			}

			// Are we sending notifications for Private posts?
			// Action is added if we are, but double check option and post status
			if ( $this->subscribe2_options['private'] == "yes" && $post->post_status == 'private' ) {
				// don't send notification to public users
				$check = true;
			}

			// lets collect our subscribers
			if ( !$check ) {
				// if this post is assigned to an excluded
				// category, or is a private post then
				// don't send public subscribers a notification
				$public = $this->get_public();
			}
			if ( $post->post_type == 'page' ) {
				$post_cats_string = get_all_category_ids();
			} else {
				$post_cats_string = implode(',', $post_cats);
			}
			$registered = $this->get_registered("cats=$post_cats_string");

			// do we have subscribers?
			if ( empty($public) && empty($registered) ) {
				// if not, no sense doing anything else
				return $post;
			}
		} else {
			// make sure we prime the taxonomy variable for preview posts
			$s2_taxonomies = array('category');
			$s2_taxonomies = apply_filters('s2_taxonomies', $s2_taxonomies);
		}

		// we set these class variables so that we can avoid
		// passing them in function calls a little later
		$this->post_title = "<a href=\"" . get_permalink($post->ID) . "\">" . html_entity_decode($post->post_title, ENT_QUOTES) . "</a>";
		$this->permalink = get_permalink($post->ID);
		$this->post_date = get_the_time(get_option('date_format'));
		$this->post_time = get_the_time();

		$author = get_userdata($post->post_author);
		$this->authorname = $author->display_name;

		// do we send as admin, or post author?
		if ( 'author' == $this->subscribe2_options['sender'] ) {
			// get author details
			$user = &$author;
			$this->myemail = $user->user_email;
			$this->myname = html_entity_decode($user->display_name, ENT_QUOTES);
		} elseif ( 'blogname' == $this->subscribe2_options['sender'] ) {
			$this->myemail = get_option('admin_email');
			$this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
		} else {
			// get admin details
			$user = $this->get_userdata($this->subscribe2_options['sender']);
			$this->myemail = $user->user_email;
			$this->myname = html_entity_decode($user->display_name, ENT_QUOTES);
		}

		$this->post_cat_names = implode(', ', wp_get_object_terms($post->ID, $s2_taxonomies, array('fields' => 'names')));
		$this->post_tag_names = implode(', ', wp_get_post_tags($post->ID, array('fields' => 'names')));

		// Get email subject
		$subject = stripslashes(strip_tags($this->substitute($this->subscribe2_options['notification_subject'])));
		// Get the message template
		$mailtext = apply_filters('s2_email_template', $this->subscribe2_options['mailtext']);
		$mailtext = stripslashes($this->substitute($mailtext));

		$plaintext = $post->post_content;
		if ( function_exists('strip_shortcodes') ) {
			$plaintext = strip_shortcodes($plaintext);
		}
		$plaintext = preg_replace('|<s*>(.*)<\/s>|','', $plaintext);
		$plaintext = preg_replace('|<strike*>(.*)<\/strike>|','', $plaintext);
		$plaintext = preg_replace('|<del*>(.*)<\/del>|','', $plaintext);

		$gallid = '[gallery id="' . $post->ID . '"';
		$content = str_replace('[gallery', $gallid, $post->post_content);
		$content = apply_filters('the_content', $content);
		$content = str_replace("]]>", "]]&gt", $content);

		$excerpt = $post->post_excerpt;
		if ( '' == $excerpt ) {
			// no excerpt, is there a <!--more--> ?
			if ( false !== strpos($plaintext, '<!--more-->') ) {
				list($excerpt, $more) = explode('<!--more-->', $plaintext, 2);
				// strip leading and trailing whitespace
				$excerpt = strip_tags($excerpt);
				$excerpt = trim($excerpt);
			} else {
				// no <!--more-->, so grab the first 55 words
				$excerpt = strip_tags($plaintext);
				$words = explode(' ', $excerpt, $this->excerpt_length + 1);
				if (count($words) > $this->excerpt_length) {
					array_pop($words);
					array_push($words, '[...]');
					$excerpt = implode(' ', $words);
				}
			}
		}
		$html_excerpt = $post->post_excerpt;
		if ( '' == $html_excerpt ) {
			// no excerpt, is there a <!--more--> ?
			if ( false !== strpos($content, '<!--more-->') ) {
				list($html_excerpt, $more) = explode('<!--more-->', $content, 2);
				// balance HTML tags and then strip leading and trailing whitespace
				$html_excerpt = trim(balanceTags($html_excerpt, true));
			} else {
				// no <!--more-->, so grab the first 55 words
				$words = explode(' ', $content, $this->excerpt_length + 1);
				if (count($words) > $this->excerpt_length) {
					array_pop($words);
					array_push($words, '[...]');
					$html_excerpt = implode(' ', $words);
					// balance HTML tags and then strip leading and trailing whitespace
					$html_excerpt = trim(balanceTags($html_excerpt, true));
				} else {
					$html_excerpt = $content;
				}
			}
		}

		// remove excess white space from with $excerpt and $plaintext
		$excerpt = preg_replace('|[ ]+|', ' ', $excerpt);
		$plaintext = preg_replace('|[ ]+|', ' ', $plaintext);

		// prepare mail body texts
		$excerpt_body = str_replace("{POST}", $excerpt, $mailtext);
		$full_body = str_replace("{POST}", strip_tags($plaintext), $mailtext);
		$html_body = str_replace("\r\n", "<br />\r\n", $mailtext);
		$html_body = str_replace("{POST}", $content, $html_body);
		$html_excerpt_body = str_replace("\r\n", "<br />\r\n", $mailtext);
		$html_excerpt_body = str_replace("{POST}", $html_excerpt, $html_excerpt_body);

		if ( $preview != '' ) {
			$this->myemail = $preview;
			$this->myname = __('Plain Text Excerpt Preview', 'subscribe2');
			$this->mail(array($preview), $subject, $excerpt_body);
			$this->myname = __('Plain Text Full Preview', 'subscribe2');
			$this->mail(array($preview), $subject, $full_body);
			$this->myname = __('HTML Excerpt Preview', 'subscribe2');
			$this->mail(array($preview), $subject, $html_excerpt_body, 'html');
			$this->myname = __('HTML Full Preview', 'subscribe2');
			$this->mail(array($preview), $subject, $html_body, 'html');
		} else {
			// first we send plaintext summary emails
			$registered = $this->get_registered("cats=$post_cats_string&format=excerpt&author=$post->post_author");
			if ( empty($registered) ) {
				$recipients = (array)$public;
			} elseif ( empty($public) ) {
				$recipients = (array)$registered;
			} else {
				$recipients = array_merge((array)$public, (array)$registered);
			}
			$recipients = apply_filters('s2_send_plain_excerpt_suscribers', $recipients, $post->ID);
			$this->mail($recipients, $subject, $excerpt_body);

			// next we send plaintext full content emails
			$recipients = $this->get_registered("cats=$post_cats_string&format=post&author=$post->post_author");
			$recipients = apply_filters('s2_send_plain_fullcontent_suscribers', $recipients, $post->ID);
			$this->mail($recipients, $subject, $full_body);

			// next we send html excerpt content emails
			$recipients = $this->get_registered("cats=$post_cats_string&format=html_excerpt&author=$post->post_author");
			$recipients = apply_filters('s2_send_html_excerpt_suscribers', $recipients, $post->ID);
			$this->mail($recipients, $subject, $html_excerpt_body, 'html');

			// finally we send html full content emails
			$recipients = $this->get_registered("cats=$post_cats_string&format=html&author=$post->post_author");
			$recipients = apply_filters('s2_send_html_fullcontent_suscribers', $recipients, $post->ID);
			$this->mail($recipients, $subject, $html_body, 'html');
		}
	} // end publish()

	/**
	Send confirmation email to a public subscriber
	*/
	function send_confirm($what = '', $is_remind = false) {
		if ( $this->filtered == 1 ) { return true; }
		if ( !$this->email || !$what ) { return false; }
		$id = $this->get_id($this->email);
		if ( !$id ) {
			return false;
		}

		// generate the URL "?s2=ACTION+HASH+ID"
		// ACTION = 1 to subscribe, 0 to unsubscribe
		// HASH = wp_hash of email address
		// ID = user's ID in the subscribe2 table
		// use home instead of siteurl incase index.php is not in core wordpress directory
		$link = get_option('home') . "/?s2=";

		if ( 'add' == $what ) {
			$link .= '1';
		} elseif ( 'del' == $what ) {
			$link .= '0';
		}
		$link .= wp_hash($this->email);
		$link .= $id;

		// sort the headers now so we have all substitute information
		$mailheaders = $this->headers();

		if ( $is_remind == true ) {
			$body = $this->substitute(stripslashes($this->subscribe2_options['remind_email']));
			$subject = $this->substitute(stripslashes($this->subscribe2_options['remind_subject']));
		} else {
			$body = $this->substitute(stripslashes($this->subscribe2_options['confirm_email']));
			if ( 'add' == $what ) {
				$body = str_replace("{ACTION}", $this->subscribe, $body);
				$subject = str_replace("{ACTION}", $this->subscribe, $this->subscribe2_options['confirm_subject']);
			} elseif ( 'del' == $what ) {
				$body = str_replace("{ACTION}", $this->unsubscribe, $body);
				$subject = str_replace("{ACTION}", $this->unsubscribe, $this->subscribe2_options['confirm_subject']);
			}
			$subject = html_entity_decode($this->substitute(stripslashes($subject)), ENT_QUOTES);
		}

		$body = str_replace("{LINK}", $link, $body);

		if ( $is_remind == true && function_exists('wpmq_mail') ) {
			// could be sending lots of reminders so queue them if wpmq is enabled
			@wp_mail($this->email, $subject, $body, $mailheaders, '', 0);
		} else {
			return @wp_mail($this->email, $subject, $body, $mailheaders);
		}
	} // end send_confirm()

/* ===== Public Subscriber functions ===== */
	/**
	Return an array of all the public subscribers
	*/
	function get_public($confirmed = 1) {
		global $wpdb;
		if ( 1 == $confirmed ) {
			if ( '' == $this->all_public ) {
				$this->all_public = $wpdb->get_col("SELECT email FROM $this->public WHERE active='1'");
			}
			return $this->all_public;
		} else {
			if ( '' == $this->all_unconfirmed ) {
				$this->all_unconfirmed = $wpdb->get_col("SELECT email FROM $this->public WHERE active='0'");
			}
			return $this->all_unconfirmed;
		}
	} // end get_public()

	/**
	Given a public subscriber ID, returns the email address
	*/
	function get_email($id = 0) {
		global $wpdb;

		if ( !$id ) {
			return false;
		}
		return $wpdb->get_var("SELECT email FROM $this->public WHERE id=$id");
	} // end get_email()

	/**
	Given a public subscriber email, returns the subscriber ID
	*/
	function get_id($email = '') {
		global $wpdb;

		if ( !$email ) {
			return false;
		}
		return $wpdb->get_var("SELECT id FROM $this->public WHERE email='$email'");
	} // end get_id()

	/**
	Add an public subscriber to the subscriber table
	If added by admin it is immediately confirmed, otherwise as unconfirmed
	*/
	function add($email = '', $confirm = false) {
		if ( $this->filtered == 1 ) { return; }
		global $wpdb;

		if ( !is_email($email) ) { return false; }

		if ( false !== $this->is_public($email) ) {
			// is this an email for a registered user
			$check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email='$this->email'");
			if ( $check ) { return; }
			if ( $confirm ) {
				$wpdb->get_results("UPDATE $this->public SET active='1', ip='$this->ip' WHERE CAST(email as binary)='$email'");
			} else {
				$wpdb->get_results("UPDATE $this->public SET date=CURDATE() WHERE CAST(email as binary)='$email'");
			}
		} else {
			if ( $confirm ) {
				global $current_user;
				$wpdb->get_results($wpdb->prepare("INSERT INTO $this->public (email, active, date, ip) VALUES (%s, %d, CURDATE(), %s)", $email, 1, $current_user->user_login));
			} else {
				$wpdb->get_results($wpdb->prepare("INSERT INTO $this->public (email, active, date, ip) VALUES (%s, %d, CURDATE(), %s)", $email, 0, $this->ip));
			}
		}
	} // end add()

	/**
	Remove a public subscriber user from the subscription table
	*/
	function delete($email = '') {
		global $wpdb;

		if ( !is_email($email) ) { return false; }
		$wpdb->get_results("DELETE FROM $this->public WHERE CAST(email as binary)='$email'");
	} // end delete()

	/**
	Toggle a public subscriber's status
	*/
	function toggle($email = '') {
		global $wpdb;

		if ( '' == $email || !is_email($email) ) { return false; }

		// let's see if this is a public user
		$status = $this->is_public($email);
		if ( false === $status ) { return false; }

		if ( '0' == $status ) {
			$wpdb->get_results("UPDATE $this->public SET active='1' WHERE CAST(email as binary)='$email'");
		} else {
			$wpdb->get_results("UPDATE $this->public SET active='0' WHERE CAST(email as binary)='$email'");
		}
	} // end toggle()

	/**
	Send reminder email to unconfirmed public subscribers
	*/
	function remind($emails = '') {
		if ( '' == $emails ) { return false; }

		$recipients = explode(",", $emails);
		if ( !is_array($recipients) ) { $recipients = (array)$recipients; }
		foreach ( $recipients as $recipient ) {
			$this->email = $recipient;
			$this->send_confirm('add', true);
		}
	} //end remind()

	/**
	Check email is not from a barred domain
	*/
	function is_barred($email='') {
		$barred_option = $this->subscribe2_options['barred'];
		list($user, $domain) = explode('@', $email, 2);
		$bar_check = stristr($barred_option, $domain);

		if ( !empty($bar_check) ) {
			return true;
		} else {
			return false;
		}
	} // end is_barred()

	/**
	Is the supplied email address a public subscriber?
	*/
	function is_public($email = '') {
		global $wpdb;

		if ( '' == $email ) { return false; }

		// run the query and force case sensitivity
		$check = $wpdb->get_var("SELECT active FROM $this->public WHERE CAST(email as binary)='$email'");
		if ( '0' == $check || '1' == $check ) {
			return $check;
		} else {
			return false;
		}
	} // end is_public()

	/**
	Collects the signup date for all public subscribers
	*/
	function signup_date($email = '') {
		if ( '' == $email ) { return false; }

		global $wpdb;
		if ( !empty($this->signup_dates) ) {
			return $this->signup_dates[$email];
		} else {
			$results = $wpdb->get_results("SELECT email, date FROM $this->public", ARRAY_N);
			foreach ( $results as $result ) {
				$this->signup_dates[$result[0]] = $result[1];
			}
			return $this->signup_dates[$email];
		}
	} // end signup_date()

	/**
	Collects the ip address for all public subscribers
	*/
	function signup_ip($email = '') {
		if ( '' == $email ) {return false; }

		global $wpdb;
		if ( !empty($this->signup_ips) ) {
			return $this->signup_ips[$email];
		} else {
			$results = $wpdb->get_results("SELECT email, ip FROM $this->public", ARRAY_N);
			foreach ( $results as $result ) {
				$this->signup_ips[$result[0]] = $result[1];
			}
			return $this->signup_ips[$email];
		}
	} // end signup_ip()

/* ===== Registered User and Subscriber functions ===== */
	/**
	Is the supplied email address a registered user of the blog?
	*/
	function is_registered($email = '') {
		global $wpdb;

		if ( '' == $email ) { return false; }

		$check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email='$email'");
		if ( $check ) {
			return true;
		} else {
			return false;
		}
	} // end is_registered()

	/**
	Return Registered User ID from email
	*/
	function get_user_id($email = '') {
		global $wpdb;

		if ( '' == $email ) { return false; }

		$id = $wpdb->get_var("SELECT id FROM $wpdb->users WHERE user_email='$email'");

		return $id;
	} // end get_user_id()

	/**
	Return an array of all subscribers emails or IDs
	*/
	function get_all_registered($return = 'email') {
		global $wpdb;

		if ( $this->s2_mu ) {
			if ( $return === 'ID' ) {
				return $wpdb->get_col("SELECT user_id FROM $wpdb->usermeta WHERE meta_key='" . $wpdb->prefix . "capabilities'");
			} else {
				return $wpdb->get_col("SELECT a.user_email FROM $wpdb->users AS a INNER JOIN $wpdb->usermeta AS b ON a.ID = b.user_id WHERE b.meta_key='" . $wpdb->prefix . "capabilities'");
			}
		} else {
			if ( $return === 'ID' ) {
				return $wpdb->get_col("SELECT ID FROM $wpdb->users");
			} else {
				return $wpdb->get_col("SELECT user_email FROM $wpdb->users");
			}
		}
	} // end get_all_registered()

	/**
	Return an array of registered subscribers
	Collect all the registered users of the blog who are subscribed to the specified categories
	*/
	function get_registered($args = '') {
		global $wpdb;

		$format = '';
		$cats = '';
		$authors = '';
		$subscribers = array();

		parse_str($args, $r);
		if ( !isset($r['format']) )
			$r['format'] = 'all';
		if ( !isset($r['cats']) )
			$r['cats'] = '';
		if ( !isset($r['author']) )
			$r['author'] = '';

		$JOIN = ''; $AND = '';
		// text or HTML subscribers
		if ( 'all' != $r['format'] ) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS b ON a.user_id = b.user_id ";
			$AND .= " AND b.meta_key='" . $this->get_usermeta_keyname('s2_format') . "' AND b.meta_value=";
			if ( 'html' == $r['format'] ) {
				$AND .= "'html'";
			} elseif ( 'html_excerpt' == $r['format'] ) {
				$AND .= "'html_excerpt'";
			} elseif ( 'post' == $r['format'] ) {
				$AND .= "'post'";
			} elseif ( 'excerpt' == $r['format'] ) {
				$AND .= "'excerpt'";
			}
		}

		// specific category subscribers
		if ( '' != $r['cats'] ) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS c ON a.user_id = c.user_id ";
			$and = '';
			foreach ( explode(',', $r['cats']) as $cat ) {
				('' == $and) ? $and = "c.meta_key='" . $this->get_usermeta_keyname('s2_cat') . "$cat'" : $and .= " OR c.meta_key='" . $this->get_usermeta_keyname('s2_cat') . "$cat'";
			}
			$AND .= " AND ($and)";
		}

		// specific authors
		if ( '' != $r['author'] ) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS d ON a.user_id = d.user_id ";
			$AND .= " AND (d.meta_key='" . $this->get_usermeta_keyname('s2_authors') . "' AND NOT FIND_IN_SET(" . $r['author'] . ", d.meta_value))";
		}

		if ( $this->s2_mu ) {
			$sql = "SELECT a.user_id FROM $wpdb->usermeta AS a " . $JOIN . "WHERE a.meta_key='" . $wpdb->prefix . "capabilities'" . $AND;
		} else {
			$sql = "SELECT a.user_id FROM $wpdb->usermeta AS a " . $JOIN . "WHERE a.meta_key='" . $this->get_usermeta_keyname('s2_subscribed') . "'" . $AND;
		}
		$result = $wpdb->get_col($sql);
		if ( $result ) {
			$ids = implode(',', $result);
			$registered = $wpdb->get_col("SELECT user_email FROM $wpdb->users WHERE ID IN ($ids)");
		}

		if ( empty($registered) ) { return array(); }

		// apply filter to registered users to add or remove additional addresses, pass args too for additional control
		$registered = apply_filters('s2_registered_subscribers', $registered, $args);
		return $registered;
	} // end get_registered()

	/**
	Function to ensure email is compliant with internet messaging standards
	*/
	function sanitize_email($email) {
		if ( !is_email($email) ) { return; }

		// ensure that domain is in lowercase as per internet email standards
		list($name, $domain) = explode('@', $email, 2);
		return $name . "@" . strtolower($domain);
	} // end sanitize_email()

	/**
	Create the appropriate usermeta values when a user registers
	If the registering user had previously subscribed to notifications, this function will delete them from the public subscriber list first
	*/
	function register($user_ID = 0, $consent = false) {
		global $wpdb;

		if ( 0 == $user_ID ) { return $user_ID; }
		$user = get_userdata($user_ID);

		// Subscribe registered users to categories obeying excluded categories
		if ( 0 == $this->subscribe2_options['reg_override'] || 'no' == $this->subscribe2_options['newreg_override'] ) {
			$all_cats = $this->all_cats(true, 'ID');
		} else {
			$all_cats = $this->all_cats(false, 'ID');
		}

		$cats = '';
		foreach ( $all_cats as $cat ) {
			('' == $cats) ? $cats = "$cat->term_id" : $cats .= ",$cat->term_id";
		}

		if ( '' == $cats ) {
			// sanity check, might occur if all cats excluded and reg_override = 0
			return $user_ID;
		}

		// has this user previously signed up for email notification?
		if ( false !== $this->is_public($this->sanitize_email($user->user_email)) ) {
			// delete this user from the public table, and subscribe them to all the categories
			$this->delete($user->user_email);
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), $cats);
			foreach ( explode(',', $cats) as $cat ) {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_cat') . $cat, $cat);
			}
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), 'excerpt');
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_autosub'), $this->subscribe2_options['autosub_def']);
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_authors'), '');
		} else {
			// create post format entries for all users
			if ( in_array($this->subscribe2_options['autoformat'], array('html', 'html_excerpt', 'post', 'excerpt')) ) {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), $this->subscribe2_options['autoformat']);
			} else {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_format'), 'excerpt');
			}
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_autosub'), $this->subscribe2_options['autosub_def']);
			// if the are no existing subscriptions, create them if we have consent
			if (  true === $consent ) {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), $cats);
				foreach ( explode(',', $cats) as $cat ) {
					update_user_meta($user_ID, $this->get_usermeta_keyname('s2_cat') . $cat, $cat);
				}
			}
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_authors'), '');
		}
		return $user_ID;
	} // end register()

	/**
	Get admin data from record 1 or first user with admin rights
	*/
	function get_userdata($admin_id) {
		global $wpdb, $userdata;

		if ( is_numeric($admin_id) ) {
			$admin = get_userdata($admin_id);
		} elseif ( $admin_id == 'admin' ) {
			//ensure compatibility with < 4.16
			$admin = get_userdata('1');
		} else {
			$admin = &$userdata;
		}

		if ( empty($admin) || $admin->ID == 0 ) {
			$role = array('role' => 'administrator');
			$wp_user_query = get_users( $role );
			$admin = $wp_user_query[0];
		}

		return $admin;
	} //end get_userdata()

	/**
	Subscribe/unsubscribe user from one-click submission
	*/
	function one_click_handler($user_ID, $action) {
		if ( !isset($user_ID) || !isset($action) ) { return; }

		$all_cats = $this->all_cats(true);

		if ( 'subscribe' == $action ) {
			// Subscribe
			$new_cats = array();
			foreach ( $all_cats as $cat ) {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_cat') . $cat->term_id, $cat->term_id);
				$new_cats[] = $cat->term_id;
			}

			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), implode(',', $new_cats));

			if ( 'yes' == $this->subscribe2_options['show_autosub'] && 'no' != get_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), true) ) {
				update_user_meta($user_ID, $this->get_usermeta_keyname('s2_autosub'), 'yes');
			}
		} elseif ( 'unsubscribe' == $action ) {
			// Unsubscribe
			foreach ( $all_cats as $cat ) {
				delete_user_meta($user_ID, $this->get_usermeta_keyname('s2_cat') . $cat->term_id);
			}

			delete_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'));
			update_user_meta($user_ID, $this->get_usermeta_keyname('s2_autosub'), 'no');
		}
	} //end one_click_handler()

/* ===== helper functions: forms and stuff ===== */
	/**
	Get an object of all categories, include default and custom type
	*/
	function all_cats($exclude = false, $orderby = 'slug') {
		$all_cats = array();
		$s2_taxonomies = array('category');
		$s2_taxonomies = apply_filters('s2_taxonomies', $s2_taxonomies);

		foreach( $s2_taxonomies as $taxonomy ) {
			if ( taxonomy_exists($taxonomy) ) {
				$all_cats = array_merge($all_cats, get_categories(array('hide_empty' => false, 'orderby' => $orderby, 'taxonomy' => $taxonomy)));
			}
		}

		if ( $exclude === true ) {
			// remove excluded categories from the returned object
			$excluded = explode(',', $this->subscribe2_options['exclude']);

			// need to use $id like this as this is a mixed array / object
			$id = 0;
			foreach ( $all_cats as $cat) {
				if ( in_array($cat->term_id, $excluded) ) {
					unset($all_cats[$id]);
				}
				$id++;
			}
		}

		return $all_cats;
	} // end all_cats()

	/**
	Export subscriber emails and other details to CSV
	*/
	function prepare_export( $subscribers ) {
		$subscribers = explode(",\r\n", $subscribers);
		natcasesort($subscribers);

		$exportcsv = "User Email,User Type,User Name";
		$all_cats = $this->all_cats(false, 'ID');

		foreach ($all_cats as $cat) {
			$exportcsv .= "," . $cat->cat_name;
			$cat_ids[] = $cat->term_id;
		}
		$exportcsv .= "\r\n";

		if ( !function_exists('get_userdata') ) {
			require_once(ABSPATH . WPINC . '/pluggable.php');
		}

		foreach ( $subscribers as $subscriber ) {
			if ( $this->is_registered($subscriber) ) {
				$user_ID = $this->get_user_id( $subscriber );
				$user_info = get_userdata( $user_ID );

				$cats = explode(',', get_user_meta($user_ID, $this->get_usermeta_keyname('s2_subscribed'), true));
				$subscribed_cats = '';
				foreach ( $cat_ids as $cat ) {
					(in_array($cat, $cats)) ? $subscribed_cats .= ",Yes" : $subscribed_cats .= ",No";
				}

				$exportcsv .= $subscriber . ',';
				$exportcsv .= __('Registered User', 'subscribe2');
				$exportcsv .= ',' . $user_info->display_name;
				$exportcsv .= $subscribed_cats . "\r\n";
			} else {
				if ( $this->is_public($subscriber) === '1' ) {
					$exportcsv .= $subscriber . ',' . __('Confirmed Public Subscriber', 'subscribe2') . "\r\n";
				} elseif ( $this->is_public($subscriber) === '0' ) {
					$exportcsv .= $subscriber . ',' . __('Unconfirmed Public Subscriber', 'subscribe2') . "\r\n";
				}
			}
		}

		return $exportcsv;
	} // end prepare_export()

	/**
	Filter for usermeta table key names to adjust them if needed for WPMU blogs
	*/
	function get_usermeta_keyname($metaname) {
		global $wpdb;

		// Is this WordPressMU or not?
		if ( $this->s2_mu === true ) {
			switch( $metaname ) {
				case 's2_subscribed':
				case 's2_cat':
				case 's2_format':
				case 's2_autosub':
				case 's2_authors':
					return $wpdb->prefix . $metaname;
					break;
			}
		}
		// Not MU or not a prefixed option name
		return $metaname;
	} // end get_usermeta_keyname()

	/**
	Adds information to the WordPress registration screen for new users
	*/
	function register_form() {
		if ( 'no' == $this->subscribe2_options['autosub'] ) { return; }
		if ( 'wpreg' == $this->subscribe2_options['autosub'] ) {
			echo "<p>\r\n<label>";
			echo __('Check here to Subscribe to email notifications for new posts', 'subscribe2') . ":<br />\r\n";
			echo "<input type=\"checkbox\" name=\"reg_subscribe\"" . checked($this->subscribe2_options['wpregdef'], 'yes', false) . " />";
			echo "</label>\r\n";
			echo "</p>\r\n";
		} elseif ( 'yes' == $this->subscribe2_options['autosub'] ) {
			echo "<p>\r\n<center>\r\n";
			echo __('By registering with this blog you are also agreeing to receive email notifications for new posts but you can unsubscribe at anytime', 'subscribe2') . ".<br />\r\n";
			echo "</center></p>\r\n";
		}
	} // end register_form()

	/**
	Process function to add action if user selects to subscribe to posts during registration
	*/
	function register_post($user_ID = 0) {
		global $_POST;
		if ( 0 == $user_ID ) { return; }
		if ( 'yes' == $this->subscribe2_options['autosub'] || ( 'on' == $_POST['reg_subscribe'] && 'wpreg' == $this->subscribe2_options['autosub'] ) ) {
			$this->register($user_ID, true);
		} else {
			$this->register($user_ID, false);
		}
	} // end register_post()

/* ===== comment subscriber functions ===== */
	/**
	Display check box on comment page
	*/
	function s2_comment_meta_form() {
		if ( is_user_logged_in() ) {
			echo $this->profile;
		} else {
			echo "<label><input type=\"checkbox\" name=\"s2_comment_request\" value=\"1\" />" . __('Check here to Subscribe to notifications for new posts', 'subscribe2') . "</label>";
		}
	} // end s2_comment_meta_form()

	/**
	Process comment meta data
	*/
	function s2_comment_meta($comment_ID, $approved = 0) {
		if ( $_POST['s2_comment_request'] == '1' ) {
			switch ($approved) {
				case '0':
					// Unapproved so hold in meta data pending moderation
					add_comment_meta($comment_ID, 's2_comment_request', $_POST['s2_comment_request']);
					break;
				case '1':
					// Approved so add
					$is_public = $this->is_public($comment->comment_author_email);
					if ( $is_public == 0 ) {
						$this->toggle($comment->comment_author_email);
					}
					$is_registered = $this->is_registered($comment->comment_author_email);
					if ( !$is_public && !$is_registered ) {
						$this->add($comment->comment_author_email, true);
					}
					break;
				default :
					break;
			}
		}
	} // end s2_comment_meta()

	/**
	Action subscribe requests made on comment forms when comments are approved
	*/
	function comment_status($comment_ID = 0){
		global $wpdb;

		// get meta data
		$subscribe = get_comment_meta($comment_ID, 's2_comment_request', true);
		if ( $subscribe != '1' ) { return $comment_ID; }

		// Retrieve the information about the comment
		$sql = "SELECT comment_author_email, comment_approved FROM $wpdb->comments WHERE comment_ID='$comment_ID' LIMIT 1";
		$comment = $wpdb->get_row($sql, OBJECT);
		if ( empty($comment) ) { return $comment_ID; }

		switch ($comment->comment_approved) {
			case '0': // Unapproved
				break;
			case '1': // Approved
				$is_public = $this->is_public($comment->comment_author_email);
				if ( $is_public == 0 ) {
					$this->toggle($comment->comment_author_email);
				}
				$is_registered = $this->is_registered($comment->comment_author_email);
				if ( !$is_public && !$is_registered ) {
					$this->add($comment->comment_author_email, true);
				}
				delete_comment_meta($comment_ID, 's2_comment_request');
				break;
			default: // post is trash, spam or deleted
				delete_comment_meta($comment_ID, 's2_comment_request');
				break;
		}

		return $comment_ID;
	} // end comment_status()

/* ===== widget functions ===== */
	/**
	Register the form widget
	*/
	function subscribe2_widget() {
		require_once( S2PATH . 'include/widget.php');
		register_widget('S2_Form_widget');
	} // end subscribe2_widget()

	/**
	Register the counter widget
	*/
	function counter_widget() {
		require_once( S2PATH . 'include/counterwidget.php');
		register_widget('S2_Counter_widget');
	} // end counter_widget()

/* ===== wp-cron functions ===== */
	/**
	Add a weekly event to cron
	*/
	function add_weekly_sched($sched) {
		$sched['weekly'] = array('interval' => 604800, 'display' => __('Weekly', 'subscribe2'));
		return $sched;
	} // end add_weekly_sched()

	/**
	Send a daily digest of today's new posts
	*/
	function subscribe2_cron($preview = '', $resend = '') {
		if ( defined('DOING_S2_CRON') && DOING_S2_CRON ) { return; }
		define( 'DOING_S2_CRON', true );
		global $wpdb;

		if ( '' == $preview ) {
			// update last_s2cron execution time before completing or bailing
			$now = current_time('mysql');
			$prev = $this->subscribe2_options['last_s2cron'];
			$last = $this->subscribe2_options['previous_s2cron'];
			$this->subscribe2_options['last_s2cron'] = $now;
			$this->subscribe2_options['previous_s2cron'] = $prev;
			if ( '' == $resend ) {
				// update sending times provided this is not a resend
				update_option('subscribe2_options', $this->subscribe2_options);
			}

			// set up SQL query based on options
			if ( $this->subscribe2_options['private'] == 'yes' ) {
				$status	= "'publish', 'private'";
			} else {
				$status = "'publish'";
			}

			// send notifications for allowed post type (defaults for posts and pages)
			// uses s2_post_types filter to allow for custom post types in WP 3.0
			if ( $this->subscribe2_options['pages'] == 'yes' ) {
				$s2_post_types = array('page', 'post');
			} else {
				$s2_post_types = array('post');
			}
			$s2_post_types = apply_filters('s2_post_types', $s2_post_types);
			foreach( $s2_post_types as $post_type ) {
				('' == $type) ? $type = "'$post_type'" : $type .= ", '$post_type'";
			}

			// collect posts
			if ( $resend == 'resend' ) {
				if ( $this->subscribe2_options['cron_order'] == 'desc' ) {
					$posts = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_type, post_password, post_date, post_author FROM $wpdb->posts WHERE post_date >= '$last' AND post_date < '$prev' AND post_status IN ($status) AND post_type IN ($type) ORDER BY post_date DESC");
				} else {
					$posts = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_type, post_password, post_date, post_author FROM $wpdb->posts WHERE post_date >= '$last' AND post_date < '$prev' AND post_status IN ($status) AND post_type IN ($type) ORDER BY post_date ASC");
				}
			} else {
				if ( $this->subscribe2_options['cron_order'] == 'desc' ) {
					$posts = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_type, post_password, post_date, post_author FROM $wpdb->posts WHERE post_date >= '$prev' AND post_date < '$now' AND post_status IN ($status) AND post_type IN ($type) ORDER BY post_date DESC");
				} else {
					$posts = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content, post_type, post_password, post_date, post_author FROM $wpdb->posts WHERE post_date >= '$prev' AND post_date < '$now' AND post_status IN ($status) AND post_type IN ($type) ORDER BY post_date ASC");
				}
			}
		} else {
			// we are sending a preview
			$posts = get_posts('numberposts=1');
		}

		// do we have any posts?
		if ( empty($posts) && !has_filter('s2_digest_email') ) { return false; }
		$this->post_count = count($posts);

		// if we have posts, let's prepare the digest
		$datetime = get_option('date_format') . ' @ ' . get_option('time_format');
		$all_post_cats = array();
		$mailtext = apply_filters('s2_email_template', $this->subscribe2_options['mailtext']);
		$table = '';
		$tablelinks = '';
		$message_post= '';
		$message_posttime = '';
		foreach ( $posts as $post ) {
			$s2_taxonomies = array('category');
			$s2_taxonomies = apply_filters('s2_taxonomies', $s2_taxonomies);
			$post_cats = wp_get_object_terms($post->ID, $s2_taxonomies, array('fields' => 'ids'));
			$post_cats_string = implode(',', $post_cats);
			$all_post_cats = array_unique(array_merge($all_post_cats, $post_cats));
			$check = false;
			// Pages are put into category 1 so make sure we don't exclude
			// pages if category 1 is excluded
			if ( $post->post_type != 'page' ) {
				// is the current post assigned to any categories
				// which should not generate a notification email?
				foreach ( explode(',', $this->subscribe2_options['exclude']) as $cat ) {
					if ( in_array($cat, $post_cats) ) {
						$check = true;
					}
				}
			}
			// is the current post set by the user to
			// not generate a notification email?
			$s2mail = get_post_meta($post->ID, 's2mail', true);
			if ( strtolower(trim($s2mail)) == 'no' ) {
				$check = true;
			}
			// is the current post private
			// and should this not generate a notification email?
			if ( $this->subscribe2_options['password'] == 'no' && $post->post_password != '' ) {
				$check = true;
			}
			// is the post assigned a format that should
			// not be included in the notification email?
			$post_format = get_post_format($post->ID);
			$excluded_formats = explode(',', $this->subscribe2_options['exclude_formats']);
			if ( $post_format !== false && in_array($post_format, $excluded_formats) ) {
				$check = true;
			}
			// if this post is excluded
			// don't include it in the digest
			if ( $check ) {
				continue;
			}
			$post_title = html_entity_decode($post->post_title, ENT_QUOTES);
			('' == $table) ? $table .= "* " . $post_title : $table .= "\r\n* " . $post_title;
			('' == $tablelinks) ? $tablelinks .= "* " . $post_title : $tablelinks .= "\r\n* " . $post_title;
			$message_post .= $post_title;
			$message_posttime .= $post_title;
			if ( strstr($mailtext, "{AUTHORNAME}") ) {
				$author = get_userdata($post->post_author);
				if ( $author->display_name != '' ) {
					$message_post .= " (" . __('Author', 'subscribe2') . ": " . $author->display_name . ")";
					$message_posttime .= " (" . __('Author', 'subscribe2') . ": " . $author->display_name . ")";
				}
			}
			$message_post .= "\r\n";
			$message_posttime .= "\r\n";

			$tablelinks .= "\r\n" . $this->get_tracking_link(get_permalink($post->ID)) . "\r\n";
			$message_post .= $this->get_tracking_link(get_permalink($post->ID)) . "\r\n";
			$message_posttime .= __('Posted on', 'subscribe2') . ": " . mysql2date($datetime, $post->post_date) . "\r\n";
			$message_posttime .= $this->get_tracking_link(get_permalink($post->ID)) . "\r\n";
			if ( strstr($mailtext, "{CATS}") ) {
				$post_cat_names = implode(', ', wp_get_object_terms($post->ID, $s2_taxonomies, array('fields' => 'names')));
				$message_post .= __('Posted in', 'subscribe2') . ": " . $post_cat_names . "\r\n";
				$message_posttime .= __('Posted in', 'subscribe2') . ": " . $post_cat_names . "\r\n";
			}
			if ( strstr($mailtext, "{TAGS}") ) {
				$post_tag_names = implode(', ', wp_get_post_tags($post->ID, array('fields' => 'names')));
				if ( $post_tag_names != '' ) {
					$message_post .= __('Tagged as', 'subscribe2') . ": " . $post_tag_names . "\r\n";
					$message_posttime .= __('Tagged as', 'subscribe2') . ": " . $post_tag_names . "\r\n";
				}
			}
			$message_post .= "\r\n";
			$message_posttime .= "\r\n";

			$excerpt = $post->post_excerpt;
			if ( '' == $excerpt ) {
				// no excerpt, is there a <!--more--> ?
				if ( false !== strpos($post->post_content, '<!--more-->') ) {
					list($excerpt, $more) = explode('<!--more-->', $post->post_content, 2);
					$excerpt = strip_tags($excerpt);
					if ( function_exists('strip_shortcodes') ) {
						$excerpt = strip_shortcodes($excerpt);
					}
				} else {
					$excerpt = strip_tags($post->post_content);
					if ( function_exists('strip_shortcodes') ) {
						$excerpt = strip_shortcodes($excerpt);
					}
					$words = explode(' ', $excerpt, $this->excerpt_length + 1);
					if ( count($words) > $this->excerpt_length ) {
						array_pop($words);
						array_push($words, '[...]');
						$excerpt = implode(' ', $words);
					}
				}
				// strip leading and trailing whitespace
				$excerpt = trim($excerpt);
			}
			$message_post .= $excerpt . "\r\n\r\n";
			$message_posttime .= $excerpt . "\r\n\r\n";
		}

		// we add a blank line after each post excerpt now trim white space that occurs for the last post
		$message_post = trim($message_post);
		$message_posttime = trim($message_posttime);
		// remove excess white space from within $message_post and $message_posttime
		$message_post = preg_replace('|[ ]+|', ' ', $message_post);
		$message_posttime = preg_replace('|[ ]+|', ' ', $message_posttime);

		// apply filter to allow external content to be inserted or content manipulated
		$message_post = apply_filters('s2_digest_email', $message_post, $now, $prev, $last, $this->subscribe2_options['cron_order']);
		$message_posttime = apply_filters('s2_digest_email', $message_posttime, $now, $prev, $last, $this->subscribe2_options['cron_order']);

		//sanity check - don't send a mail if the content is empty
		if ( !$message_post && !$message_posttime && !$table && !$tablelinks ) {
			return;
		}

		// get sender details
		if ( $this->subscribe2_options['sender'] == 'blogname' ) {
			$this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
			$this->myemail = get_bloginfo('admin_email');
		} else {
			$user = $this->get_userdata($this->subscribe2_options['sender']);
			$this->myemail = $user->user_email;
			$this->myname = html_entity_decode($user->display_name, ENT_QUOTES);
		}

		$scheds = (array)wp_get_schedules();
		$email_freq = $this->subscribe2_options['email_freq'];
		$display = $scheds[$email_freq]['display'];
		( '' == get_option('blogname') ) ? $subject = "" : $subject = "[" . stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)) . "] ";
		$subject .= $display . " " . __('Digest Email', 'subscribe2');
		$mailtext = str_replace("{TABLELINKS}", $tablelinks, $mailtext);
		$mailtext = str_replace("{TABLE}", $table, $mailtext);
		$mailtext = str_replace("{POSTTIME}", $message_posttime, $mailtext);
		$mailtext = str_replace("{POST}", $message_post, $mailtext);
		$mailtext = stripslashes($this->substitute($mailtext));

		// prepare recipients
		if ( $preview != '' ) {
			$this->myemail = $preview;
			$this->myname = __('Digest Preview', 'subscribe2');
			$this->mail(array($preview), $subject, $mailtext);
		} else {
			$public = $this->get_public();
			$all_post_cats_string = implode(',', $all_post_cats);
			$registered = $this->get_registered("cats=$all_post_cats_string");
			$recipients = array_merge((array)$public, (array)$registered);
			$this->mail($recipients, $subject, $mailtext);
		}
	} // end subscribe2_cron()

/* ===== Our constructor ===== */
	/**
	Subscribe2 constructor
	*/
	function s2init() {
		global $wpdb, $table_prefix, $wp_version, $wpmu_version;
		// load the options
		$this->subscribe2_options = get_option('subscribe2_options');
		// if SCRIPT_DEBUG is true, use dev scripts
		$this->script_debug = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '.dev' : '';

		// get the WordPress release number for in code version comparisons
		$tmp = explode('-', $wp_version, 2);
		$this->wp_release = $tmp[0];

		load_plugin_textdomain('subscribe2', false, S2DIR);

		// load our strings
		$this->load_strings();

		// Is this WordPressMU or not?
		if ( isset($wpmu_version) || strpos($wp_version, 'wordpress-mu') ) {
			$this->s2_mu = true;
		}
		if ( function_exists('is_multisite') && is_multisite() ) {
			$this->s2_mu = true;
		}

		// add action to handle WPMU subscriptions and unsubscriptions
		if ( $this->s2_mu === true ) {
			require_once(S2PATH . "classes/class-s2_multisite.php");
			global $s2class_multisite;
			$s2class_multisite = new s2_multisite;
			if ( isset($_GET['s2mu_subscribe']) || isset($_GET['s2mu_unsubscribe']) ) {
				add_action('init', array(&$this, 'wpmu_subscribe'));
			}
		}

		// do we need to install anything?
		$this->public = $table_prefix . "subscribe2";
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$this->public}'") != $this->public ) { $this->install(); }
		//do we need to upgrade anything?
		if ( is_array($this->subscribe2_options) && $this->subscribe2_options['version'] !== S2VERSION ) {
			add_action('shutdown', array(&$this, 'upgrade'));
		}

		// add core actions
		add_filter('cron_schedules', array(&$this, 'add_weekly_sched'));
		// add actions for automatic subscription based on option settings
		add_action('register_form', array(&$this, 'register_form'));
		add_action('user_register', array(&$this, 'register_post'));
		if ( $this->s2_mu ) {
			add_action('add_user_to_blog', array(&$s2class_multisite, 'wpmu_add_user'), 10);
			add_action('remove_user_from_blog', array(&$s2class_multisite, 'wpmu_remove_user'), 10);
		}
		// add actions for processing posts based on per-post or cron email settings
		if ( $this->subscribe2_options['email_freq'] != 'never' ) {
			add_action('s2_digest_cron', array(&$this, 'subscribe2_cron'));
		} else {
			add_action('new_to_publish', array(&$this, 'publish'));
			add_action('draft_to_publish', array(&$this, 'publish'));
			add_action('auto-draft_to_publish', array(&$this, 'publish'));
			add_action('pending_to_publish', array(&$this, 'publish'));
			add_action('private_to_publish', array(&$this, 'publish'));
			add_action('future_to_publish', array(&$this, 'publish'));
			if ( $this->subscribe2_options['private'] == 'yes' ) {
				add_action('new_to_private', array(&$this, 'publish'));
				add_action('draft_to_private', array(&$this, 'publish'));
				add_action('auto-draft_to_private', array(&$this, 'publish'));
				add_action('pending_to_private', array(&$this, 'publish'));
			}
		}
		// add actions for comment subscribers
		if ( 'no' != $this->subscribe2_options['comment_subs'] ) {
			if ( 'before' == $this->subscribe2_options['comment_subs'] ) {
				add_action('comment_form_after_fields', array(&$this, 's2_comment_meta_form'));
			} else {
				add_action('comment_form', array(&$this, 's2_comment_meta_form'));
			}
			add_action('comment_post', array(&$this, 's2_comment_meta'), 1, 2);
			add_action('wp_set_comment_status', array(&$this, 'comment_status'));
		}
		// add action to display widget if option is enabled
		if ( '1' == $this->subscribe2_options['widget'] ) {
			add_action('widgets_init', array(&$this, 'subscribe2_widget'));
		}
		// add action to display counter widget if option is enabled
		if ( '1' == $this->subscribe2_options['counterwidget'] ) {
			add_action('widgets_init', array(&$this, 'counter_widget'));
		}

		// Add actions specific to admin or frontend
		if ( is_admin() ) {
			//add menu, authoring and category admin actions
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('admin_menu', array(&$this, 's2_meta_init'));
			add_action('save_post', array(&$this, 's2_meta_handler'));
			add_action('create_category', array(&$this, 'new_category'));
			add_action('delete_category', array(&$this, 'delete_category'));

			// Add filters for Ozh Admin Menu
			if ( function_exists('wp_ozh_adminmenu') ) {
				add_filter('ozh_adminmenu_icon_s2_posts', array(&$this, 'ozh_s2_icon'));
				add_filter('ozh_adminmenu_icon_s2_users', array(&$this, 'ozh_s2_icon'));
				add_filter('ozh_adminmenu_icon_s2_tools', array(&$this, 'ozh_s2_icon'));
				add_filter('ozh_adminmenu_icon_s2_settings', array(&$this, 'ozh_s2_icon'));
			}

			// add write button
			if ( '1' == $this->subscribe2_options['show_button'] ) {
				add_action('admin_init', array(&$this, 'button_init'));
			}

			// add counterwidget css and js
			if ( '1' == $this->subscribe2_options['counterwidget'] ) {
				add_action('admin_init', array(&$this, 'widget_s2counter_css_and_js'));
			}

			// add one-click handlers
			if ( 'yes' == $this->subscribe2_options['one_click_profile'] ) {
				add_action( 'show_user_profile', array(&$this, 'one_click_profile_form') );
				add_action( 'edit_user_profile', array(&$this, 'one_click_profile_form') );
				add_action( 'personal_options_update', array(&$this, 'one_click_profile_form_save') );
				add_action( 'edit_user_profile_update', array(&$this, 'one_click_profile_form_save') );
			}

			// capture CSV export
			if ( isset($_POST['s2_admin']) && isset($_POST['csv']) ) {
				$date = date('Y-m-d');
				header("Content-Description: File Transfer");
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=subscribe2_users_$date.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				echo $this->prepare_export($_POST['exportcsv']);
				exit(0);
			}
		} else {
			if ( isset($_GET['s2']) ) {
				// someone is confirming a request
				if ( defined('DOING_S2_CONFIRM') && DOING_S2_CONFIRM ) { return; }
				define( 'DOING_S2_CONFIRM', true );
				add_filter('query_string', array(&$this, 'query_filter'));
				add_filter('the_title', array(&$this, 'title_filter'));
				add_filter('the_content', array(&$this, 'confirm'));
			}

			// add the frontend filters
			add_shortcode('subscribe2', array(&$this, 'shortcode'));
			add_filter('the_content', array(&$this, 'filter'), 10);

			// add actions for other plugins
			if ( '1' == $this->subscribe2_options['show_meta'] ) {
				add_action('wp_meta', array(&$this, 'add_minimeta'), 0);
			}

			// add actions for ajax form if enabled
			if ( '1' == $this->subscribe2_options['ajax'] ) {
				add_action('wp_enqueue_scripts', array(&$this, 'add_ajax'));
				add_action('wp_head', array(&$this, 'add_s2_ajax'));
			}
		}
	} // end s2init()

/* ===== our variables ===== */
	// cache variables
	var $subscribe2_options = array();
	var $all_public = '';
	var $all_unconfirmed = '';
	var $all_authors = '';
	var $excluded_cats = '';
	var $post_title = '';
	var $permalink = '';
	var $post_date = '';
	var $post_time = '';
	var $myname = '';
	var $myemail = '';
	var $authorname = '';
	var $post_cat_names = '';
	var $post_tag_names = '';
	var $post_count = '';
	var $signup_dates = array();
	var $filtered = 0;
	var $preview_email = false;

	// state variables used to affect processing
	var $s2_mu = false;
	var $action = '';
	var $email = '';
	var $message = '';
	var $excerpt_length = 55;

	// some messages
	var $please_log_in = '';
	var $profile = '';
	var $confirmation_sent = '';
	var $already_subscribed = '';
	var $not_subscribed ='';
	var $not_an_email = '';
	var $barred_domain = '';
	var $error = '';
	var $mail_sent = '';
	var $mail_failed = '';
	var $form = '';
	var $no_such_email = '';
	var $added = '';
	var $deleted = '';
	var $subscribe = '';
	var $unsubscribe = '';
	var $confirm_subject = '';
	var $options_saved = '';
	var $options_reset = '';
} // end class subscribe2
?>