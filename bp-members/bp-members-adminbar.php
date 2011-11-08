<?php
/**
 * BuddyPress Members Admin Bar
 *
 * Handles the member functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add the "My Account" menu and all submenus.
 *
 * @since BuddyPress (r4151)
 * @todo Deprecate WP 3.2 admin bar compatibility when we drop 3.2 support
 */
function bp_members_admin_bar_my_account_menu() {
	global $bp, $wp_admin_bar, $wp_version;

	// Bail if this is an ajax request
	if ( defined( 'DOING_AJAX' ) )
		return;

	// Logged in user
	if ( is_user_logged_in() ) {

		// User avatar
		$avatar = bp_core_fetch_avatar( array(
			'item_id' => bp_loggedin_user_id(),
			'email'   => $bp->loggedin_user->userdata->user_email,
			'width'   => 16,
			'height'  => 16
		) );

		// Some admin bar setup in WP 3.2 differs from WP 3.3+.
		// Backward-compatibility will be deprecated at some point.
		if ( version_compare( (float)$wp_version, '3.3', '>=' ) ) {

			// Stored in the global so we can add menus easily later on
			$bp->my_account_menu_id = 'my-account-buddypress';

			$title = bp_get_loggedin_user_fullname() . $avatar;

			$class = 'opposite';
			if ( !empty( $avatar ) )
				$class .= ' with-avatar';

			$meta  = array(
				'class' => $class
			);
		} else {
			$bp->my_account_menu_id = ( ! empty( $avatar ) ) ? 'my-account-with-avatar' : 'my-account';
			$title = $avatar . bp_get_loggedin_user_fullname();
			$meta  = array();
		}

		// Create the main 'My Account' menu
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->my_account_menu_id,
			'title' => $title,
			'href'  => $bp->loggedin_user->domain,
			'meta'  => $meta
		) );

	// Show login and sign-up links
	} elseif ( !empty( $wp_admin_bar ) ) {

		add_filter ( 'show_admin_bar', '__return_true' );

		// Create the main 'My Account' menu
		$wp_admin_bar->add_menu( array(
			'id'    => 'bp-login',
			'title' => __( 'Log in', 'buddypress' ),
			'href'  => wp_login_url()
		) );

		// Sign up
		if ( bp_get_signup_allowed() ) {
			$wp_admin_bar->add_menu( array(
				'id'    => 'bp-register',
				'title' => __( 'Register', 'buddypress' ),
				'href'  => bp_get_signup_page()
			) );
		}
	}
}
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu', 4 );

/**
 * Adds the User Admin top-level menu to user pages
 *
 * @package BuddyPress
 * @since 1.5
 */
function bp_members_admin_bar_user_admin_menu() {
	global $bp, $wp_admin_bar;

	// Only show if viewing a user
	if ( !bp_is_user() )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !current_user_can( 'edit_users' ) || bp_is_my_profile() )
		return false;

	// User avatar
	$avatar = bp_core_fetch_avatar( array(
		'item_id' => bp_displayed_user_id(),
		'email'   => $bp->displayed_user->userdata->user_email,
		'width'   => 16,
		'height'  => 16
	) );

	// Unique ID for the 'My Account' menu
	$bp->user_admin_menu_id = ( ! empty( $avatar ) ) ? 'user-admin-with-avatar' : 'user-admin';

	// Add the top-level User Admin button
	$wp_admin_bar->add_menu( array(
		'id'    => $bp->user_admin_menu_id,
		'title' => $avatar . bp_get_displayed_user_fullname(),
		'href'  => bp_displayed_user_domain()
	) );

	// User Admin > Edit this user's profile
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'edit-profile',
		'title'  => __( "Edit Profile", 'buddypress' ),
		'href'   => bp_get_members_component_link( 'profile', 'edit' )
	) );

	// User Admin > Edit this user's avatar
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'change-avatar',
		'title'  => __( "Edit Avatar", 'buddypress' ),
		'href'   => bp_get_members_component_link( 'profile', 'change-avatar' )
	) );

	// User Admin > Spam/unspam
	if ( !bp_core_is_user_spammer( bp_displayed_user_id() ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->user_admin_menu_id,
			'id'     => 'spam-user',
			'title'  => __( 'Mark as Spammer', 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/mark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as a spammer?', 'buddypress' ) . '");' )
		) );
	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->user_admin_menu_id,
			'id'     => 'unspam-user',
			'title'  => __( 'Not a Spammer', 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/unmark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as not a spammer?', 'buddypress' ) . '");' )
		) );
	}

	// User Admin > Delete Account
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'delete-user',
		'title'  => __( 'Delete Account', 'buddypress' ),
		'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/delete-user/', 'delete-user' ),
		'meta'   => array( 'onclick' => 'confirm(" ' . __( "Are you sure you want to delete this user's account?", 'buddypress' ) . '");' )
	) );
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_user_admin_menu', 99 );

/**
 * Build the "Notifications" dropdown
 *
 * @package Buddypress
 * @since 1.5
 */
function bp_members_admin_bar_notifications_menu() {
	global $bp, $wp_admin_bar;

	if ( !is_user_logged_in() )
		return false;

	$notifications = bp_core_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
	$count         = !empty( $notifications ) ? count( $notifications ) : '0';
	$alert_class   = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
	$menu_title    = '<span id="ab-pending-notifications" class="' . $alert_class . '">' . $count . '</span>';

	// Add the top-level Notifications button
	$wp_admin_bar->add_menu( array(
		'id'    => 'bp-notifications',
		'title' => $menu_title,
		'href'  => bp_loggedin_user_domain(),
		'meta'  => array(
			'class' => 'opposite',
		)
	) );

	if ( !empty( $notifications ) ) {
		foreach ( (array)$notifications as $notification ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'bp-notifications',
				'id'     => 'notification-' . $notification->id,
				'title'  => $notification->content,
				'href'   => $notification->href
			) );
		}
	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => 'bp-notifications',
			'id'     => 'no-notifications',
			'title'  => __( 'No new notifications', 'buddypress' ),
			'href'   => bp_loggedin_user_domain()
		) );
	}

	return;
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_notifications_menu', 90 );

?>