<?php
require_once( 'bp-core.php' );

/* Set the version number */
define ( 'BP_XPROFILE_VERSION', '1.0b2' );
define ( 'BP_XPROFILE_DB_VERSION', '948' );

define ( 'BP_XPROFILE_SLUG', apply_filters( 'xprofile_slug', 'profile' ) );

/* Database access classes and functions */
require_once( 'bp-xprofile/bp-xprofile-classes.php' );

/* Functions for handling the admin area tabs for administrators */
require_once( 'bp-xprofile/bp-xprofile-admin.php' );

/* Functions for applying filters to Xprofile specfic output */
require_once( 'bp-xprofile/bp-xprofile-filters.php' );

/* Functions to handle the modification and saving of signup pages */
require_once( 'bp-xprofile/bp-xprofile-signup.php' );

/* Template tag functions that can be used in theme template files */
require_once( 'bp-xprofile/bp-xprofile-templatetags.php' );

/* Functions to handle the sending of email notifications */
require_once( 'bp-xprofile/bp-xprofile-notifications.php' );

/* Functions to handle the selective inclusion of CSS and JS files */
require_once( 'bp-xprofile/bp-xprofile-cssjs.php' );

/* Assign the base group and fullname field names to constants to use in SQL statements */
define ( 'BP_XPROFILE_BASE_GROUP_NAME', get_site_option( 'bp-xprofile-base-group-name' ) );
define ( 'BP_XPROFILE_FULLNAME_FIELD_NAME', get_site_option( 'bp-xprofile-fullname-field-name' ) );

/**
 * xprofile_install()
 *
 * Set up the database tables needed for the xprofile component.
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses dbDelta() Takes SQL statements and compares them to any existing tables and creates/updates them.
 * @uses add_site_option() adds a value for a meta_key into the wp_sitemeta table
 */
function xprofile_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	if ( get_site_option( 'bp-xprofile-base-group-name' ) == '' )
		update_site_option( 'bp-xprofile-base-group-name', 'Base' );
	
	if ( get_site_option( 'bp-xprofile-fullname-field-name' ) == '' )
		update_site_option( 'bp-xprofile-fullname-field-name', 'Full Name' );	
	
	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_groups'] . " (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  name varchar(150) NOT NULL,
			  description mediumtext NOT NULL,
			  can_delete tinyint(1) NOT NULL,
			  KEY can_delete (can_delete)
	) {$charset_collate};";
	
	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_fields'] . " (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  group_id int(11) unsigned NOT NULL,
			  parent_id int(11) unsigned NOT NULL,
			  type varchar(150) NOT NULL,
			  name varchar(150) NOT NULL,
			  description longtext NOT NULL,
			  is_required tinyint(1) NOT NULL DEFAULT '0',
			  is_default_option tinyint(1) NOT NULL DEFAULT '0',
			  field_order int(11) NOT NULL DEFAULT '0',
			  option_order int(11) NOT NULL DEFAULT '0',
			  order_by varchar(15) NOT NULL,
			  is_public int(2) NOT NULL DEFAULT '1',
			  can_delete tinyint(1) NOT NULL DEFAULT '1',
			  KEY group_id (group_id),
			  KEY parent_id (parent_id),
			  KEY is_public (is_public),
			  KEY can_delete (can_delete),
			  KEY is_required (is_required)
	) {$charset_collate};";
	
	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_data'] . " (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  field_id int(11) unsigned NOT NULL,
			  user_id int(11) unsigned NOT NULL,
			  value longtext NOT NULL,
			  last_updated datetime NOT NULL,
			  KEY field_id (field_id),
			  KEY user_id (user_id)
	) {$charset_collate};";
	
	if ( get_site_option( 'bp-xprofile-db-version' ) == '' ) {
		$sql[] = "INSERT INTO ". $bp['profile']['table_name_groups'] . " VALUES ( 1, '" . get_site_option( 'bp-xprofile-base-group-name' ) . "', '', 0 );";
	
		$sql[] = "INSERT INTO ". $bp['profile']['table_name_fields'] . " ( 
					id, group_id, parent_id, type, name, description, is_required, field_order, option_order, order_by, is_public, can_delete
				  ) VALUES (
					1, 1, 0, 'textbox', '" . get_site_option( 'bp-xprofile-fullname-field-name' ) . "', '', 1, 1, 0, '', 1, 0
				  );";
	}
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );

	dbDelta($sql);
	
	update_site_option( 'bp-xprofile-db-version', BP_XPROFILE_DB_VERSION );
}

function xprofile_wire_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE ". $bp['profile']['table_name_wire'] ." (
	  		id int(11) NOT NULL AUTO_INCREMENT,
			item_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			content longtext NOT NULL,
			date_posted datetime NOT NULL,
			PRIMARY KEY id (id),
			KEY item_id (item_id),
		    KEY user_id (user_id)
	 	   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );

	dbDelta($sql);
}

/**
 * xprofile_setup_globals()
 *
 * Add the profile globals to the $bp global for use across the installation
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses site_url() Returns the site URL
 */
function xprofile_setup_globals() {
	global $bp, $wpdb;
	
	$bp['profile'] = array(
		'table_name_groups' => $wpdb->base_prefix . 'bp_xprofile_groups',
		'table_name_fields' => $wpdb->base_prefix . 'bp_xprofile_fields',
		'table_name_data' 	=> $wpdb->base_prefix . 'bp_xprofile_data',
		'format_activity_function' => 'xprofile_format_activity',
		'image_base' 		=> site_url( MUPLUGINDIR . '/bp-xprofile/images' ),
		'slug'		 		=> BP_XPROFILE_SLUG
	);
	
	$bp['version_numbers'][$bp['profile']['slug']] = BP_XPROFILE_VERSION;
	
	if ( function_exists('bp_wire_install') )
		$bp['profile']['table_name_wire'] = $wpdb->base_prefix . 'bp_xprofile_wire';
}
add_action( 'wp', 'xprofile_setup_globals', 1 );	
add_action( 'admin_menu', 'xprofile_setup_globals', 1 );

/**
 * xprofile_add_admin_menu()
 *
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses bp_xprofile_install() runs the installation of DB tables for the xprofile component
 * @uses wp_enqueue_script() Adds a JS file to the JS queue ready for output
 * @uses add_submenu_page() Adds a submenu tab to a top level tab in the admin area
 * @uses xprofile_install() Runs the DB table installation function
 * @return 
 */
function xprofile_add_admin_menu() {
	global $wpdb, $bp;
	
	if ( is_site_admin() ) {
		wp_enqueue_script( 'jquery.tablednd', site_url( MUPLUGINDIR . '/bp-core/js/jquery/jquery.tablednd.js' ), array( 'jquery' ), '0.4' );
	
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __("Profile Fields", 'buddypress'), __("Profile Fields", 'buddypress'), 1, "xprofile_settings", "xprofile_admin" );

		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( ( $wpdb->get_var("show tables like '%" . $bp['profile']['table_name_groups'] . "%'") == false ) || ( get_site_option('bp-xprofile-db-version') < BP_XPROFILE_DB_VERSION )  )
			xprofile_install();
		
		if ( ( function_exists('bp_wire_install') && $wpdb->get_var("show tables like '%" . $bp['profile']['table_name_wire'] . "%'") == false ) || ( get_site_option('bp-xprofile-db-version') < BP_XPROFILE_DB_VERSION )  )
			xprofile_wire_install();
	}
}
add_action( 'admin_menu', 'xprofile_add_admin_menu' );

/**
 * xprofile_setup_nav()
 *
 * Sets up the navigation items for the xprofile component
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_add_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_add_nav_default() Sets which sub navigation item is selected by default
 * @uses bp_core_add_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_get_avatar() Returns the either the thumb (1) or full (2) avatar URL for the user_id passed
 */
function xprofile_setup_nav() {
	global $bp;
	
	/* Add 'Profile' to the main navigation */
	bp_core_add_nav_item( __('Profile', 'buddypress'), $bp['profile']['slug'] );
	bp_core_add_nav_default( $bp['profile']['slug'], 'xprofile_screen_display_profile', 'public' );
	
	$profile_link = $bp['loggedin_domain'] . $bp['profile']['slug'] . '/';
	
	/* Add the subnav items to the profile */
	bp_core_add_subnav_item( $bp['profile']['slug'], 'public', __('Public', 'buddypress'), $profile_link, 'xprofile_screen_display_profile' );
	bp_core_add_subnav_item( $bp['profile']['slug'], 'edit', __('Edit Profile', 'buddypress'), $profile_link, 'xprofile_screen_edit_profile' );
	bp_core_add_subnav_item( $bp['profile']['slug'], 'change-avatar', __('Change Avatar', 'buddypress'), $profile_link, 'xprofile_screen_change_avatar' );

	if ( $bp['current_component'] == $bp['profile']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Profile', 'buddypress');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'xprofile_setup_nav', 2 );
add_action( 'admin_menu', 'xprofile_setup_nav', 2 );

/********
 * Functions to handle screens and URL based actions
 */

/**
 * xprofile_screen_display_profile()
 *
 * Handles the display of the profile page by loading the correct template file.
 * 
 * @package BuddyPress Xprofile
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_display_profile() {
	global $bp, $is_new_friend;
	
	// If this is a first visit to a new friends profile, delete the friend accepted notifications for the
	// logged in user. $is_new_friend is set in bp-core/bp-core-catchuri.php in bp_core_set_uri_globals()
	if ( $is_new_friend )
		bp_core_delete_notifications_for_user_by_item_id( $bp['loggedin_userid'], $bp['current_userid'], 'friends', 'friendship_accepted' );
	
	do_action( 'xprofile_screen_display_profile', $is_new_friend );
	bp_core_load_template( 'profile/index' );
}

/**
 * xprofile_screen_edit_profile()
 *
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 * 
 * @package BuddyPress Xprofile
 * @uses bp_is_home() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_edit_profile() {
	if ( bp_is_home() ) {
		do_action( 'xprofile_screen_edit_profile' );
		bp_core_load_template( 'profile/edit' );
	}
}

/**
 * xprofile_screen_change_avatar()
 *
 * Handles the display of the change avatar page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 * 
 * @package BuddyPress Xprofile
 * @uses bp_is_home() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_change_avatar() {
	if ( bp_is_home() ) {
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
		do_action( 'xprofile_screen_change_avatar' );
		bp_core_load_template( 'profile/change-avatar' );
	}
}

/**
 * xprofile_screen_notification_settings()
 *
 * Loads the notification settings for the xprofile component.
 * Settings are hooked into the function: bp_core_screen_notification_settings_content()
 * in bp-core/bp-core-settings.php
 * 
 * @package BuddyPress Xprofile
 * @global $current_user WordPress global variable containing current logged in user information
 */
function xprofile_screen_notification_settings() { 
	global $current_user; ?>
	<table class="notification-settings" id="profile-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Profile', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<?php if ( function_exists('bp_wire_install') ) { ?>
		<tr>
			<td></td>
			<td><?php _e( 'A member posts on your wire', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_profile_wire_post]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_profile_wire_post' ) || get_usermeta( $current_user->id, 'notification_profile_wire_post' ) == 'yes' ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_profile_wire_post]" value="no" <?php if ( get_usermeta( $current_user->id, 'notification_profile_wire_post' ) == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<?php } ?>
		
		<?php do_action( 'xprofile_screen_notification_settings' ) ?>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'xprofile_screen_notification_settings', 1 );

/**
 * xprofile_action_delete_avatar()
 *
 * This function runs when an action is set for a screen:
 * domain.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * The function will delete the active avatar for a user.
 * 
 * @package BuddyPress Xprofile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_delete_avatar() Deletes the active avatar for the logged in user.
 * @uses add_action() Runs a specific function for an action when it fires.
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_action_delete_avatar() {
	global $bp;
	
	if ( $bp['current_action'] != 'delete-avatar' )
		return false;
	
	if ( bp_is_home() ) {
		bp_core_delete_avatar();
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
		bp_core_load_template( 'profile/change-avatar' );
	}
}
add_action( 'wp', 'xprofile_action_delete_avatar', 3 );

/**
 * xprofile_action_new_wire_post()
 *
 * Posts a new wire post to the users profile wire. 
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_new_post() Adds a new wire post to a specific wire using the ID of the item passed and the table name.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function xprofile_action_new_wire_post() {
	global $bp;
	
	if ( $bp['current_component'] != $bp['wire']['slug'] )
		return false;
	
	if ( $bp['current_action'] != 'post' )
		return false;
	
	if ( !$wire_post_id = bp_wire_new_post( $bp['current_userid'], $_POST['wire-post-textarea'], $bp['profile']['slug'], false, $bp['profile']['table_name_wire'] ) ) {
		bp_core_add_message( __('Wire message could not be posted. Please try again.', 'buddypress'), 'error' );
	} else {
		bp_core_add_message( __('Wire message successfully posted.', 'buddypress') );
				
		do_action( 'xprofile_new_wire_post', $wire_post_id );	
	}

	if ( !strpos( $_SERVER['HTTP_REFERER'], $bp['wire']['slug'] ) ) {
		bp_core_redirect( $bp['current_domain'] );
	} else {
		bp_core_redirect( $bp['current_domain'] . $bp['wire']['slug'] );
	}
}
add_action( 'wp', 'xprofile_action_new_wire_post', 3 );

/**
 * xprofile_action_delete_wire_post()
 *
 * Deletes a wire post from the users profile wire. 
 * 
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_delete_post() Deletes a wire post for a specific wire using the ID of the item passed and the table name.
 * @uses xprofile_delete_activity() Deletes an activity item for the xprofile component and a particular user.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function xprofile_action_delete_wire_post() {
	global $bp;
	
	if ( $bp['current_component'] != $bp['wire']['slug'] )
		return false;
	
	if ( $bp['current_action'] != 'delete' )
		return false;
			
	$wire_post_id = $bp['action_variables'][0];
	
	if ( bp_wire_delete_post( $wire_post_id, $bp['profile']['slug'], $bp['profile']['table_name_wire'] ) ) {
		bp_core_add_message( __('Wire message successfully deleted.', 'buddypress') );

		do_action( 'xprofile_delete_wire_post', $wire_post_id );						
	} else {
		bp_core_add_message( __('Wire post could not be deleted, please try again.', 'buddypress'), 'error' );
	}
	
	if ( !strpos( $_SERVER['HTTP_REFERER'], $bp['wire']['slug'] ) ) {
		bp_core_redirect( $bp['current_domain'] );
	} else {
		bp_core_redirect( $bp['current_domain']. $bp['wire']['slug'] );
	}
}
add_action( 'wp', 'xprofile_action_delete_wire_post', 3 );


/********
 * Activity and notification recording functions
 */

/**
 * xprofile_record_activity()
 *
 * Records activity for the logged in user within the profile component so that
 * it will show in the users activity stream (if installed)
 * 
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_activity_record() Adds an entry to the activity component tables for a specific activity
 */
function xprofile_record_activity( $args = true ) {
	if ( function_exists('bp_activity_record') ) {
		extract($args);
		bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
	}
}

/**
 * xprofile_delete_activity()
 *
 * Deletes activity for a user within the profile component so that
 * it will be removed from the users activity stream and sitewide stream (if installed)
 * 
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_activity_delete() Deletes an entry to the activity component tables for a specific activity
 */
function xprofile_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}

/**
 * xprofile_format_activity()
 *
 * The function xprofile_record_activity() simply records ID's, which component and action, and dates into
 * the database. These variables need to be formatted into something that can be read and displayed to
 * the user.
 *
 * This function will format an activity item based on the component action and return it for saving
 * in the activity cache database tables. It can then be selected and displayed with far less load on
 * the server.
 * 
 * @package BuddyPress Xprofile
 * @param $item_id The ID of the specific item for which the activity is recorded (could be a wire post id, user id etc)
 * @param $action The component action name e.g. 'new_wire_post' or 'updated_profile'
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses BP_Wire_Post Class Creates a new wire post object based on the table name and ID.
 * @uses BP_XProfile_Group Class Creates a new group object based on the group ID.
 * @return The readable activity item
 */
function xprofile_format_activity( $item_id, $user_id, $action, $secondary_item_id = false, $for_secondary_user = false ) {
	global $bp;
	
	switch( $action ) {
		case 'new_wire_post':
			if ( class_exists('BP_Wire_Post') ) {
				$wire_post = new BP_Wire_Post( $bp['profile']['table_name_wire'], $item_id );
			}
			
			if ( !$wire_post )
				return false;
			
			if ( ( $wire_post->item_id == $bp['loggedin_userid'] && $wire_post->user_id == $bp['loggedin_userid'] ) || ( $wire_post->item_id == $bp['current_userid'] && $wire_post->user_id == $bp['current_userid'] ) ) {
				
				$from_user_link = bp_core_get_userlink($wire_post->user_id);
				$to_user_link = false;
								
				$content = sprintf( __('%s wrote on their own wire', 'buddypress'), $from_user_link ) . ': <span class="time-since">%s</span>';				
				$return_values['primary_link'] = bp_core_get_userlink( $wire_post->user_id, false, true );
			
			} else if ( ( $wire_post->item_id != $bp['loggedin_userid'] && $wire_post->user_id == $bp['loggedin_userid'] ) || ( $wire_post->item_id != $bp['current_userid'] && $wire_post->user_id == $bp['current_userid'] ) ) {
			
				$from_user_link = bp_core_get_userlink($wire_post->user_id);
				$to_user_link = bp_core_get_userlink( $wire_post->item_id, false, false, true, true );
				
				$content = sprintf( __('%s wrote on %s wire', 'buddypress'), $from_user_link, $to_user_link ) . ': <span class="time-since">%s</span>';			
				$return_values['primary_link'] = bp_core_get_userlink( $wire_post->item_id, false, true );
			
			} 
			
			if ( $content != '' ) {
				$post_excerpt = bp_create_excerpt($wire_post->content);
				
				$content .= '<blockquote>' . $post_excerpt . '</blockquote>';
				$return_values['content'] = $content;
				
				$return_values['content'] = apply_filters( 'xprofile_new_wire_post_activity', $content, $from_user_link, $to_user_link, $post_excerpt );
				
				return $return_values;
			} 
			
			return false;
		break;
		case 'updated_profile':
			$profile_group = new BP_XProfile_Group( $item_id );
			
			if ( !$profile_group )
				return false;
			
			$user_link = bp_core_get_userlink($user_id);
			
			return array( 
				'primary_link' => bp_core_get_userlink( $user_id, false, true ),
				'content' => apply_filters( 'xprofile_updated_profile_activity', sprintf( __('%s updated the "%s" information on their profile', 'buddypress'), $user_link, '<a href="' . $bp['current_domain'] . $bp['profile']['slug'] . '">' . $profile_group->name . '</a>' ) . ' <span class="time-since">%s</span>', $user_link, $profile_group->name )
			);
		break;
	}
	
	do_action( 'xprofile_format_activity', $action, $item_id, $user_id, $action, $secondary_item_id, $for_secondary_user );
	
	return false;
}

/**
 * xprofile_format_notifications()
 *
 * Format notifications into something that can be read and displayed
 * 
 * @package BuddyPress Xprofile
 * @param $item_id The ID of the specific item for which the activity is recorded (could be a wire post id, user id etc)
 * @param $action The component action name e.g. 'new_wire_post' or 'updated_profile'
 * @param $total_items The total number of identical notification items (used for grouping)
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_global_user_fullname() Returns the display name for the user
 * @return The readable notification item
 */
function xprofile_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	if ( $action == 'new_wire_post') {
		if ( (int)$total_items > 1 ) {
			return apply_filters( 'bp_xprofile_multiple_new_wire_post_notification', '<a href="' . $bp['loggedin_domain'] . $bp['wire']['slug'] . '" title="Wire">' . sprintf( __('You have %d new posts on your wire'), (int)$total_items ) . '</a>', $total_items );		
		} else {
			$user_fullname = bp_core_global_user_fullname( $item_id );
			return apply_filters( 'bp_xprofile_single_new_wire_post_notification', '<a href="' . $bp['loggedin_domain'] . $bp['wire']['slug'] . '" title="Wire">' . sprintf( __('%s posted on your wire'), $user_fullname ) . '</a>', $user_fullname );
		}
	}
	
	do_action( 'xprofile_format_notifications', $action, $item_id, $secondary_item_id, $total_items );
	
	return false;
}


/********
 * Core action functions
 */

/**
 * xprofile_edit()
 *
 * Renders the edit form for the profile fields within a group as well as
 * handling the save action.
 *
 * [NOTE] This is old code that was written when support for the admin area was also
 * available. It is big and clunky and needs to be broken up.
 * 
 * @package BuddyPress XProfile
 * @param $group_id The ID of the group of fields to edit
 * @param $action The HTML form action
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @global $userdata WordPress global object containing current logged in user userdata
 */
function xprofile_edit( $group_id, $action ) {
	global $wpdb, $userdata, $bp;

	// Create a new group object based on the group ID.
	$group = new BP_XProfile_Group($group_id);
?>
	<div class="wrap">
		
		<h2><?php echo $group->name ?> <?php _e("Information", 'buddypress') ?></h2>
		
		<?php
			// If this group has fields then continue
			if ( $group->fields ) {
				$errors    = null;
				$list_html = '<ul class="forTab" id="' . strtolower($group_name) . '">';
				
				// Loop through each field in the group
				for ( $j = 0; $j < count($group->fields); $j++ ) {
					
					// Create a new field object for this field based on the field ID.
					$field = new BP_XProfile_Field( $group->fields[$j]->id );
					
					// Add the ID for this field to the field_ids array	
					$field_ids[] = $group->fields[$j]->id;
					
					// If the user has submitted the form - validate and save the new value for this field
					if ( isset($_GET['mode']) && $_GET['mode'] == 'save' ) {
						
						// If the current field is a datebox, we need to append '_day' to the end of the field name
						// otherwise the field name will not exist
						$post_field_string = ( $group->fields[$j]->type == 'datebox' ) ? '_day' : null;
						
						// Explode the posted field IDs into an array so we know which fields have been submitted
						$posted_fields = explode( ',', $_POST['field_ids'] );
						
						// Fetch the current field from the _POST array based on field ID. 
						$current_field = $_POST['field_' . $posted_fields[$j] . $post_field_string];
						
						// If the field is required and has been left blank then we need to add a callback error.
						if ( ( $field->is_required && !isset($current_field) ) ||
						     ( $field->is_required && $current_field == '' ) ) {
							
							// Add the error message to the errors array
							$field->message = sprintf( __('%s cannot be left blank.', 'buddypress'), $field->name );
							$errors[] = $field->message . "<br />";
						
						// If the field is not required and the field has been left blank, delete any values for the
						// field from the database.
						} else if ( !$field->is_required && ( $current_field == '' || is_null($current_field) ) ) {
							
							// Create a new profile data object for the logged in user based on field ID.								
							$profile_data = new BP_Xprofile_ProfileData( $group->fields[$j]->id );
							
							// Delete any data
							$profile_data->delete();
							
							// Also remove any selected profile field data from the $field object.
							$field->data->value = null;
						
						// If we get to this point then the field validates ok and we have new data.
						} else {
							
							// Create an empty profile data object and populate it with new data
							$profile_data = new BP_Xprofile_ProfileData;
							$profile_data->field_id = $group->fields[$j]->id;
							$profile_data->user_id = $userdata->ID;
							$profile_data->last_updated = time();
							
							// If the $post_field_string we set up earlier is not null, then this is a datebox
							// we need to concatenate each of the three select boxes for day, month and year into
							// one value.
							if ( $post_field_string != null ) {
								
								// Concatenate the values.
								$date_value = $_POST['field_' . $group->fields[$j]->id . '_day'] . 
										      $_POST['field_' . $group->fields[$j]->id . '_month'] . 
											  $_POST['field_' . $group->fields[$j]->id . '_year'];
								
								// Turn the concatenated value into a timestamp
								$profile_data->value = strtotime($date_value);
								
							} else {
								
								// Checkbox and multi select box fields will submit an array as their value
								// so we need to serialize them before saving to the DB.
								if ( is_array($current_field) )
									$current_field = serialize($current_field);
									
								$profile_data->value = $current_field;
							}
							
							// Finally save the value to the database.
							if( !$profile_data->save() ) {
								$field->message = __('There was a problem saving changes to this field, please try again.', 'buddypress');
							} else {
								$field->data->value = $profile_data->value;
							}
						}
					}
					
					// Each field object comes with HTML that can be rendered to edit that field.
					// We just need to render that to the page by adding it to the $list_html variable
					// that will be rendered when the field loop has finished.
					$list_html .= '<li>' . $field->get_edit_html() . '</li>';
				}
				
				// Now that the loop has finished put the final touches on the HTML including the submit button.
				$list_html .= '</ul>';
				
				$list_html .= '<p class="submit">
								<input type="submit" name="save" id="save" value="'.__('Save Changes &raquo;', 'buddypress').'" />
							   </p>';

				// If the user submitted the form to save new values, and there were errors, make sure we display them.
				if ( $errors && isset($_POST['save']) ) {
					$type = 'error';
					$message = __('There were problems saving your information. Please fix the following:<br />', 'buddypress');
					
					for ( $i = 0; $i < count($errors); $i++ ) {
						$message .= $errors[$i];
					}
					
				// If there were no errors then we can display a nice "Changes saved." message.
				} else if ( !$errors && isset($_POST['save'] ) ) {
					$type = 'success';
					$message = __('Changes saved.', 'buddypress');
					
					// Record in activity stream
					xprofile_record_activity( array( 'item_id' => $group->id, 'component_name' => 'profile', 'component_action' => 'updated_profile', 'is_private' => 0 ) );
					
					do_action( 'xprofile_updated_profile', $group->id ); 
				}
			}
			// If this is an invalid group, then display an error.
			else { ?>
				<div id="message" class="error fade">
					<p><?php _e('That group does not exist.', 'buddypress'); ?></p>
				</div>
			<?php
			}

		?>
		
		<?php // Finally, we can now render everything to the screen. ?>
		
		<?php
			if ( $message != '' ) {
				$type = ( $type == 'error' ) ? 'error' : 'updated';
		?>
			<div id="message" class="<?php echo $type; ?> fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>

		<p><form action="<?php echo $action ?>" method="post">
		<?php 
			if ( $field_ids )
				$field_ids = implode( ",", $field_ids );
		?>
		<input type="hidden" name="field_ids" id="field_ids" value="<?php echo $field_ids; ?>" />
		
		<?php echo $list_html; ?>

		</form>
		</p>
		
	</div> 
<?php
}

/**
 * xprofile_get_random_profile_data()
 *
 * Fetches a random piece of profile data for the user.
 * 
 * @package BuddyPress Core
 * @param $user_id User ID of the user to get random data for
 * @param $exclude_fullname whether or not to exclude the full name field as random data.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses xprofile_format_profile_field() Formats profile field data so it is suitable for display.
 * @return $field_data The fetched random data for the user.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );
	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );
	
	if ( !$field_data[0]->value || $field_data[0]->value == '' )
		return false;
	
	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * xprofile_format_profile_field()
 *
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 * 
 * @package BuddyPress Core
 * @param $field_type The type of field: datebox, selectbox, textbox etc
 * @param $field_value The actual value
 * @uses bp_format_time() Formats a time value based on the WordPress date format setting
 * @return $field_value The formatted value
 */
function xprofile_format_profile_field( $field_type, $field_value ) {
	if ( !isset($field_value) || $field_value == '' )
		return false;
	
	if ( $field_type == "datebox" ) {
		$field_value = bp_format_time( $field_value, true );
	} else {
		$content = $field_value;
		$content = apply_filters('the_content', $content);
		$field_value = str_replace(']]>', ']]&gt;', $content);
	}
	
	return stripslashes( stripslashes( $field_value ) );
}

/**
 * xprofile_remove_data_on_user_deletion()
 *
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the usermeta table
 * that this component uses.
 * 
 * @package BuddyPress XProfile
 * @param $user_id The ID of the deleted user
 * @uses get_usermeta() Get a user meta value based on meta key from wp_usermeta
 * @uses delete_usermeta() Delete user meta value based on meta key from wp_usermeta
 * @uses delete_data_for_user() Removes all profile data from the xprofile tables for the user
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );
	
	// delete any avatar files.
	@unlink( get_usermeta( $user_id, 'bp_core_avatar_v1_path' ) );
	@unlink( get_usermeta( $user_id, 'bp_core_avatar_v2_path' ) );
	
	// unset the usermeta for avatars from the usermeta table.
	delete_usermeta( $user_id, 'bp_core_avatar_v1' );
	delete_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2_path' );
}
add_action( 'wpmu_delete_user', 'xprofile_remove_data', 1 );
add_action( 'delete_user', 'xprofile_remove_data', 1 );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'xprofile_updated_profile', 'bp_core_clear_cache' );

?>
