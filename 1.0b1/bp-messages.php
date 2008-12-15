<?php
require_once( 'bp-core.php' );

define ( 'BP_MESSAGES_IS_INSTALLED', 1 );
define ( 'BP_MESSAGES_VERSION', '1.0b1' );

include_once( 'bp-messages/bp-messages-classes.php' );
include_once( 'bp-messages/bp-messages-ajax.php' );
include_once( 'bp-messages/bp-messages-cssjs.php' );
include_once( 'bp-messages/bp-messages-admin.php' );
include_once( 'bp-messages/bp-messages-templatetags.php' );
include_once( 'bp-messages/bp-messages-notifications.php' );
include_once( 'bp-messages/bp-messages-filters.php' );

/**************************************************************************
 messages_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function messages_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE ". $bp['messages']['table_name_threads'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		message_ids varchar(150) NOT NULL,
				sender_ids varchar(150) NOT NULL,
		  		first_post_date datetime NOT NULL,
		  		last_post_date datetime NOT NULL,
		  		last_message_id int(11) NOT NULL,
				last_sender_id int(11) NOT NULL,
			    KEY message_ids (message_ids),
			    KEY sender_ids (sender_ids),
			    KEY last_message_id (last_message_id),
			    KEY last_sender_id (last_sender_id)
		 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE ". $bp['messages']['table_name_recipients'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		user_id int(11) NOT NULL,
		  		thread_id int(11) NOT NULL,
				sender_only tinyint(1) NOT NULL DEFAULT '0',
		  		unread_count int(10) NOT NULL DEFAULT '0',
				is_deleted tinyint(1) NOT NULL DEFAULT '0',
			    KEY user_id (user_id),
			    KEY thread_id (thread_id),
				KEY is_deleted (is_deleted),
			    KEY sender_only (sender_only),
			    KEY unread_count (unread_count)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $bp['messages']['table_name_messages'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		sender_id int(11) NOT NULL,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				message_order int(10) NOT NULL,
				sender_is_group tinyint(1) NOT NULL DEFAULT '0',
			    KEY sender_id (sender_id),
			    KEY message_order (message_order),
			    KEY sender_is_group (sender_is_group)
		 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE ". $bp['messages']['table_name_notices'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '0',
			    KEY is_active (is_active)
		 	   ) {$charset_collate};";
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['messages']['table_name_threads'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['messages']['table_name_recipients'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['messages']['table_name_messages'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['messages']['table_name_notices'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	
	add_site_option( 'bp-messages-version', BP_MESSAGES_VERSION );
}


/**************************************************************************
 messages_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function messages_setup_globals() {
	global $bp, $wpdb;
	
	$bp['messages'] = array(
		'table_name' 		       => $wpdb->base_prefix . 'bp_messages',
		'table_name_threads'       => $wpdb->base_prefix . 'bp_messages_threads',
		'table_name_messages'      => $wpdb->base_prefix . 'bp_messages_messages',
		'table_name_recipients'    => $wpdb->base_prefix . 'bp_messages_recipients',
		'table_name_notices' 	   => $wpdb->base_prefix . 'bp_messages_notices',
		'format_activity_function' => 'messages_format_activity',
		'image_base' 		 	   => site_url() . '/wp-content/mu-plugins/bp-messages/images',
		'slug'		 		 	   => 'messages'
	);
}
add_action( 'wp', 'messages_setup_globals', 1 );	
add_action( '_admin_menu', 'messages_setup_globals', 1 );


/**************************************************************************
 messages_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function messages_check_installed() {	
	global $wpdb, $bp;

	if ( is_site_admin() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( ( $wpdb->get_var( "show tables like '%" . $bp['messages']['table_name'] . "%'" ) == false ) || ( get_site_option('bp-messages-version') < BP_MESSAGES_VERSION ) )
			messages_install();
	}
}
add_action( 'admin_menu', 'messages_check_installed' );

/**************************************************************************
 messages_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function messages_setup_nav() {
	global $bp;

	$inbox_count = BP_Messages_Thread::get_inbox_count();
	$inbox_display = ( $inbox_count ) ? ' style="display:inline;"' : ' style="display:none;"';
	$count_indicator = '&nbsp; <span' . $inbox_display . ' class="unread-count inbox-count">' . BP_Messages_Thread::get_inbox_count() . '</span>';
	
	/* Add 'Profile' to the main navigation */
	bp_core_add_nav_item( __('Messages', 'buddypress'), $bp['messages']['slug'], false, false );
	bp_core_add_nav_default( $bp['messages']['slug'], 'messages_screen_inbox', 'inbox', bp_is_home() );
	
	$messages_link = $bp['loggedin_domain'] . $bp['messages']['slug'] . '/';
	
	/* Add the subnav items to the profile */
	bp_core_add_subnav_item( $bp['messages']['slug'], 'inbox', __('Inbox', 'buddypress') . $count_indicator, $messages_link, 'messages_screen_inbox', false, bp_is_home() );
	bp_core_add_subnav_item( $bp['messages']['slug'], 'sentbox', __('Sent Messages', 'buddypress'), $messages_link, 'messages_screen_sentbox', false, bp_is_home() );
	bp_core_add_subnav_item( $bp['messages']['slug'], 'compose', __('Compose', 'buddypress'), $messages_link, 'messages_screen_compose', false, bp_is_home() );
	bp_core_add_subnav_item( $bp['messages']['slug'], 'notices', __('Notices', 'buddypress'), $messages_link, 'messages_screen_notices', false, true, true );

	if ( $bp['current_component'] == $bp['messages']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Messages', 'buddypress');			
		} else {
			$bp_options_avatar = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'messages_setup_nav', 2 );

/***** Screens **********/

function messages_screen_inbox() {
	bp_catch_uri( 'messages/index' );	
}

function messages_screen_sentbox() {
	bp_catch_uri( 'messages/sentbox' );
}

function messages_screen_compose() {
	
	// Remove any saved message data from a previous session.
	messages_remove_callback_values();
	
	//var_dump($_POST['send_to_usernames']);
	
	if ( isset($_POST['send_to_usernames']) || ( isset($_POST['send-notice']) && is_site_admin() ) ) {
		messages_send_message( $_POST['send_to_usernames'], $_POST['subject'], $_POST['content'], $_POST['thread_id'], false, true );
	}
	
	bp_catch_uri( 'messages/compose' );
}

function messages_screen_notices() {
	global $bp, $notice_id;
		
	if ( !is_site_admin() )
		return false;
		
	$notice_id = $bp['action_variables'][1];

	if ( $notice_id && is_numeric($notice_id) ) {
		$notice = new BP_Messages_Notice($notice_id);

		if ( $bp['action_variables'][0] == 'deactivate' ) {
			if ( !$notice->deactivate() ) {
				bp_core_add_message( __('There was a problem deactivating that notice.', 'buddypress'), 'error' );	
			} else {
				bp_core_add_message( __('Notice deactivated.', 'buddypress') );
			}
		} else if ( $bp['action_variables'][0] == 'activate' ) {
			if ( !$notice->activate() ) {
				bp_core_add_message( __('There was a problem activating that notice.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Notice activated.', 'buddypress') );
			}
		} else if ( $bp['action_variables'][0] == 'delete' ) {
			if ( !$notice->delete() ) {
				bp_core_add_message( __('There was a problem deleting that notice.', 'buddypress'), 'buddypress' );
			} else {
				bp_core_add_message( __('Notice deleted.', 'buddypress') );
			}
		}
		bp_core_redirect( $bp['loggedin_domain'] . $bp['messages']['slug'] . '/notices' );
	}
	bp_catch_uri( 'messages/notices' );	
}

function messages_screen_notification_settings() { 
	global $current_user; ?>
	<table class="notification-settings" id="messages-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Messages', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member sends you a new message', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_messages_new_message]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_messages_new_message' ) || get_usermeta( $current_user->id, 'notification_messages_new_message' ) == 'yes' ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_messages_new_message]" value="no" <?php if ( get_usermeta( $current_user->id, 'notification_messages_new_message' ) == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A new site notice is posted', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_messages_new_notice]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_messages_new_notice' ) || get_usermeta( $current_user->id, 'notification_messages_new_notice' ) == 'yes' ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_messages_new_notice]" value="no" <?php if ( get_usermeta( $current_user->id, 'notification_messages_new_notice' ) == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );

/***** Actions **********/

function messages_action_view_message() {
	global $bp, $thread_id;
	
	if ( $bp['current_component'] != $bp['messages']['slug'] || $bp['current_action'] != 'view' )
		return false;
		
	$thread_id = $bp['action_variables'][0];

	if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
		bp_core_redirect( $bp['current_domain'] . $bp['current_component'] );
	} else {
		$bp['bp_options_nav'][$bp['messages']['slug']]['view'] = array(
			'name' => __('From: ' . BP_Messages_Thread::get_last_sender($thread_id), 'buddypress'),
			'link' => $bp['loggedin_domain'] . $bp['messages']['slug'] . '/'			
		);

		bp_catch_uri( 'messages/view' );
	}
}
add_action( 'wp', 'messages_action_view_message', 3 );


function messages_action_delete_message() {
	global $bp, $thread_id;
	
	if ( $bp['current_component'] != $bp['messages']['slug'] || $bp['action_variables'][0] != 'delete' )
		return false;
	
	$thread_id = $bp['action_variables'][1];

	if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
		bp_core_redirect( $bp['current_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );
	} else {
		//echo $bp['loggedin_domain'] . $bp['current_component'] . '/' . $bp['current_action']; die;
		// delete message
		if ( !messages_delete_thread($thread_id) ) {
			bp_core_add_message( __('There was an error deleting that message.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Message deleted.', 'buddypress') );
		}
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );
	}
}
add_action( 'wp', 'messages_action_delete_message', 3 );


function messages_action_bulk_delete() {
	global $bp, $thread_ids;
	
	if ( $bp['current_component'] != $bp['messages']['slug'] || $bp['action_variables'][0] != 'bulk-delete' )
		return false;
	
	$thread_ids = $_POST['thread_ids'];

	if ( !$thread_ids || !BP_Messages_Thread::check_access($thread_ids) ) {
		bp_core_redirect( $bp['current_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );			
	} else {
		if ( !BP_Messages_Thread::delete( explode(',', $thread_ids ) ) ) {
			bp_core_add_message( __('There was an error deleting messages.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Messages deleted.', 'buddypress') );
		}
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );
	}
}
add_action( 'wp', 'messages_action_bulk_delete', 3 );


/**************************************************************************
 messages_record_activity()
 
 Records activity for the logged in user within the friends component so that
 it will show in the users activity stream (if installed)
 **************************************************************************/

function messages_record_activity( $args = true ) {
	if ( function_exists('bp_activity_record') ) {
		extract($args);
		bp_activity_record( $item_id, $component_name, $component_action, $is_private );
	} 
}

function messages_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}

function messages_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	if ( $action == 'new_message') {
		if ( (int)$total_items > 1 )
			return '<a href="' . $bp['loggedin_domain'] . $bp['messages']['slug'] . '/inbox" title="Inbox">' . sprintf( __('You have %d new messages'), (int)$total_items ) . '</a>';		
		else
			return '<a href="' . $bp['loggedin_domain'] . $bp['messages']['slug'] . '/inbox" title="Inbox">' . sprintf( __('You have %d new message'), (int)$total_items ) . '</a>';
	}
}


/**************************************************************************
 messages_send_message()
  
 Send a message.
 **************************************************************************/

function messages_send_message( $recipients, $subject, $content, $thread_id, $from_ajax = false, $from_template = false, $is_reply = false ) {
	global $pmessage;
	global $message, $type;
	global $bp, $current_user;
	
	messages_add_callback_values( $recipients, $subject, $content );
	
	if ( isset( $_POST['send-notice'] ) ) {
		if ( messages_send_notice( $subject, $content, $from_template ) ) {
			bp_core_add_message( __('Notice posted successfully.', 'buddypress') );
		} else {
			bp_core_add_message( __('There was an error posting that notice.', 'buddypress'), 'error' );			
		}
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/notices' );
		return true;
	}
	
	$recipients = explode( ' ', $recipients );
	
	// If there are no recipients
	if ( count( $recipients ) < 1 ) {
		if ( !$from_ajax ) {	
			bp_core_add_message( __('Please enter at least one valid user to send this message to.', 'buddypress'), 'error' );
			bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/compose' );
		} else {
			return array('status' => 0, 'message' => __('There was an error sending the reply, please try again.', 'buddypress'));
		}
		
	// If there is only 1 recipient and it is the logged in user.
	} else if ( count( $recipients ) == 1 && $recipients[0] == $current_user->user_login ) {
		bp_core_add_message( __('You must send your message to one or more users not including yourself.', 'buddypress'), 'error' );
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/compose' );	
	
	// If the subject or content boxes are empty.
	} else if ( $subject == '' || $content == '' ) {
		if ( !$from_ajax ) {
			bp_core_add_message( __('Please make sure you fill in all the fields.', 'buddypress'), 'error' );
			bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/compose' );
		} else {
			return array('status' => 0, 'message' => __('Please make sure you have typed a message before sending a reply.', 'buddypress'));
		}
		
	// Passed validation continue.
	} else {

		// Strip the logged in user from the recipient list if they exist
		if ( $key = array_search( $current_user->user_login, $recipients ) )
			unset( $recipients[$key] );
		
		$pmessage = new BP_Messages_Message;

		$pmessage->sender_id = $bp['loggedin_userid'];
		$pmessage->subject = $subject;
		$pmessage->message = $content;
		$pmessage->thread_id = $thread_id;
		$pmessage->date_sent = time();
		$pmessage->message_order = 0; // TODO
		$pmessage->sender_is_group = 0;
		
		if ( $is_reply ) {
			$thread = new BP_Messages_Thread($thread_id);
			$pmessage->recipients = $thread->get_recipients();
		} else {
			$pmessage->recipients = BP_Messages_Message::get_recipient_ids( $recipients );
		}

		if ( !is_null( $pmessage->recipients ) ) {
			if ( !$pmessage->send() ) {
				$message = __('Message could not be sent, please try again.', 'buddypress');
				$type = 'error';
		
				if ( $from_ajax ) {
					return array('status' => 0, 'message' => $message);
				} else {
					bp_core_add_message( $message, $type );
					bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/compose' );
				} 
			} else {
				$message = __('Message sent successfully!', 'buddypress') . ' <a href="' . $bp['loggedin_domain'] . $bp['messages']['slug'] . '/view/' . $pmessage->thread_id . '">' . __('View Message', 'buddypress') . '</a> &raquo;';
				$type = 'success';
				
				// Send notices to the recipients
				for ( $i = 0; $i < count($pmessage->recipients); $i++ ) {
					if ( $pmessage->recipients[$i] != $bp['loggedin_userid'] ) {
						bp_core_add_notification( $pmessage->id, $pmessage->recipients[$i], 'messages', 'new_message' );	
					}
				}
				
				do_action( 'bp_messages_message_sent', array( 'item_id' => $pmessage->id, 'recipient_ids' => $pmessage->recipients, 'thread_id' => $pmessage->thread_id, 'component_name' => 'messages', 'component_action' => 'message_sent', 'is_private' => 1 ) );
		
				if ( $from_ajax ) {
					return array('status' => 1, 'message' => $message, 'reply' => $pmessage);
				} else {
					bp_core_add_message( $message );
					bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/inbox' );
				}
			}
		} else {
			$message = __('Message could not be sent, please try again.', 'buddypress');
			$type = 'error';
		
			if ( $from_ajax ) {
				return array('status' => 0, 'message' => $message);
			} else {
				bp_core_add_message( $message, $type );
				bp_core_redirect( $bp['loggedin_domain'] . $bp['messages']['slug'] . '/compose' );
			}
		}
	}

}

function messages_add_callback_values( $recipients, $subject, $content ) {
	$_SESSION['send_to'] = $recipients;
	$_SESSION['subject'] = $subject;
	$_SESSION['content'] = $content;
}

function messages_remove_callback_values() {
	unset($_SESSION['send_to']);
	unset($_SESSION['subject']);
	unset($_SESSION['content']);
}

/**************************************************************************
 messages_send_notice()
  
 Handles the sending of notices by an administrator
 **************************************************************************/

function messages_send_notice( $subject, $message, $from_template ) {
	if ( !is_site_admin() || $subject == '' || $message == '' ) {
		return false;
	} else {
		// Has access to send notices, lets do it.
		$notice = new BP_Messages_Notice;
		$notice->subject = $subject;
		$notice->message = $message;
		$notice->date_sent = time();
		$notice->is_active = 1;
		$notice->save(); // send it.
		
		do_action( 'bp_messages_notice_sent', $subject, $message );
		
		return true;
	}
}

/**************************************************************************
 messages_delete_thread()
  
 Handles the deletion of a single or multiple threads.
 **************************************************************************/

function messages_delete_thread( $thread_ids ) {
	if ( is_array($thread_ids) ) {
		$error = 0;
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			if ( !$status = BP_Messages_Thread::delete($thread_ids[$i]) )
				$error = 1;
		}
		
		if ( $error )
			return false;
		
		return true;
	} else {
		if ( !$status = BP_Messages_Thread::delete($thread_ids) ) {
			return false;
		} else {
			return true;
		}
	}
}

function messages_is_user_sender( $user_id, $message_id ) {
	return BP_Messages_Message::is_user_sender( $user_id, $message_id );
}

function messages_get_message_sender( $message_id ) {
	return BP_Messages_Message::get_message_sender( $message_id );
}


/**************************************************************************
 messages_view_thread()
  
 Displays a message thread.
 **************************************************************************/

function messages_view_thread( $thread_id ) {
	global $bp;

	$thread = new BP_Messages_Thread( $thread_id, true );
	
	if ( !$thread->has_access ) {
		unset($_GET['mode']); ?>
		<div id="message" class="error">
			<p><?php _e( 'There was an error when viewing that message', 'buddypress' ) ?></p>
		</div>
	<?php	
	} else {
		if ( $thread->messages ) { ?>
			<?php $thread->mark_read() ?>
				
			<div class="wrap">
				<h2 id="message-subject"><?php echo $thread->subject; ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<img src="<?php echo $bp['messages']['image_base'] ?>/email_open.gif" alt="Message" style="vertical-align: top;" /> &nbsp;
								<?php _e('Sent between ', 'buddypress') ?> <?php echo BP_Messages_Thread::get_recipient_links($thread->recipients) ?> 
								<?php _e('and', 'buddypress') ?> <?php echo bp_core_get_userlink($bp['loggedin_userid']) ?>. 
							</td>
						</tr>
					</tbody>
				</table>
				
		<?php
			foreach ( $thread->messages as $message ) {
				?>
					<a name="<?php echo 'm-' . $message->id ?>"></a>
					<div class="message-box">
						<div class="avatar-box">
							<?php echo apply_filters( 'bp_message_sender_avatar', bp_core_get_avatar( $message->sender_id, 1 ) ) ?>
							<h3><?php echo apply_filters( 'bp_message_sender_id', bp_core_get_userlink( $message->sender_id ) ) ?></h3>
							<small><?php echo apply_filters( 'bp_message_date_sent', bp_format_time( strtotime($message->date_sent ) ) ) ?></small>
						</div>
						<?php echo apply_filters( 'bp_message_content', stripslashes($message->message) ); ?>
						<div class="clear"></div>
					</div>
				<?php
			}
		
			?>
				<form id="send-reply" action="<?php echo get_option('home'); ?>/wp-admin/admin.php?page=bp-messages.php&amp;mode=send" method="post">
					<div class="message-box">
							<div id="messagediv">
								<div class="avatar-box">
									<?php if ( function_exists('bp_core_get_avatar') ) 
										echo bp_core_get_avatar($bp['loggedin_userid'], 1);
									?>
					
									<h3><?php _e("Reply: ", 'buddypress') ?></h3>
								</div>
								<label for="reply"></label>
								<div>
									<textarea name="content" id="message_content" rows="15" cols="40"><?php echo $content; ?></textarea>
								</div>
							</div>
							<p class="submit">
								<input type="submit" name="send" value="Send Reply &raquo;" id="send_reply_button"/>
							</p>
							<input type="hidden" id="thread_id" name="thread_id" value="<?php echo $thread->thread_id ?>" />
							<input type="hidden" name="subject" id="subject" value="<?php _e('Re: ', 'buddypress'); echo str_replace( 'Re: ', '', $thread->last_message_subject); ?>" />
					</div>
					<?php if ( function_exists('wp_nonce_field') )
						wp_nonce_field('messages_sendreply');
					?>
				</form>
			</div>
			<?php
		}
	}
}



?>