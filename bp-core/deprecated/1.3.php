<?php

/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.3
 */

/** Loader ********************************************************************/

function bp_setup_root_components() {
	do_action( 'bp_setup_root_components' );
}
add_action( 'bp_init', 'bp_setup_root_components', 6 );

/** WP Abstraction ************************************************************/

/**
 * bp_core_is_multisite()
 *
 * This function originally served as a wrapper when WordPress and WordPress MU were separate entities.
 * Use is_multisite() instead.
 *
 * @deprecated 1.3
 * @deprecated Use is_multisite()
 */
function bp_core_is_multisite() {
	_deprecated_function( __FUNCTION__, '1.3', 'is_multisite()' );
	return is_multisite();
}

/**
 * bp_core_is_main_site
 *
 * Checks if current blog is root blog of site. Deprecated in 1.3.
 *
 * @deprecated 1.3
 * @deprecated Use is_main_site()
 * @package BuddyPress
 * @param int $blog_id optional blog id to test (default current blog)
 * @return bool True if not multisite or $blog_id is main site
 * @since 1.2.6
 */
function bp_core_is_main_site( $blog_id = '' ) {
	_deprecated_function( __FUNCTION__, '1.3', 'is_main_site()' );
	return is_main_site( $blog_id );
}

/** Admin ******************************************************************/

/**
 * In BuddyPress 1.1 - 1.2.x, this function provided a better version of add_menu_page()
 * that allowed positioning of menus. Deprecated in 1.3 in favour of a WP core function.
 *
 * @deprecated 1.3
 * @deprecated Use add_menu_page().
 * @since 1.1
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	$defaults = array(
		'page_title'   => '',
		'menu_title'   => '',
		'capability'   => 'manage_options',
		'file'         => '',
		'function'     => '',
		'icon_url'     => '',
		'position'     => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	_deprecated_function( __FUNCTION__, '1.3', 'Use add_menu_page()' );
	return add_menu_page( $page_title, $menu_title, $capability, $file, $function, $icon_url, $position );
}

/** Activity ******************************************************************/

function bp_is_activity_permalink() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_single_activity' );
	bp_is_single_activity();
}

/** Core **********************************************************************/

function bp_core_get_wp_profile() {
	_deprecated_function( __FUNCTION__, '1.3' );

	global $bp;

	$ud = get_userdata( $bp->displayed_user->id ); ?>

<div class="bp-widget wp-profile">
	<h4><?php _e( 'My Profile' ) ?></h4>

	<table class="wp-profile-fields">

		<?php if ( $ud->display_name ) : ?>

			<tr id="wp_displayname">
				<td class="label"><?php _e( 'Name', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->display_name; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_description ) : ?>

			<tr id="wp_desc">
				<td class="label"><?php _e( 'About Me', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->user_description; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_url ) : ?>

			<tr id="wp_website">
				<td class="label"><?php _e( 'Website', 'buddypress' ); ?></td>
				<td class="data"><?php echo make_clickable( $ud->user_url ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->jabber ) : ?>

			<tr id="wp_jabber">
				<td class="label"><?php _e( 'Jabber', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->jabber; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->aim ) : ?>

			<tr id="wp_aim">
				<td class="label"><?php _e( 'AOL Messenger', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->aim; ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->yim ) : ?>

			<tr id="wp_yim">
				<td class="label"><?php _e( 'Yahoo Messenger', 'buddypress' ); ?></td>
				<td class="data"><?php echo $ud->yim; ?></td>
			</tr>

		<?php endif; ?>

	</table>
</div>

<?php
}

function bp_is_home() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_my_profile' );
	return bp_is_my_profile();
}

/**
 * Is the user on the front page of the site?
 *
 * @deprecated 1.3
 * @deprecated Use is_front_page()
 * @return bool
 */
function bp_is_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "is_front_page()" );
	return is_front_page();
}

/**
 * Is the front page of the site set to the Activity component?
 *
 * @deprecated 1.3
 * @deprecated Use bp_is_component_front_page( 'activity' )
 * @return bool
 */
function bp_is_activity_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "bp_is_component_front_page( 'activity' )" );
	return bp_is_component_front_page( 'activity' );
}

function bp_is_member() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_is_user' );
	bp_is_user();
}

function bp_loggedinuser_link() {
	_deprecated_function( __FUNCTION__, '1.3', 'bp_logged_in_user_link' );
	bp_loggedin_user_link();
}

/**
 * Only show the search form if there are available objects to search for.
 * Deprecated in 1.3; not used anymore.
 *
 * @return bool
 */
function bp_search_form_enabled() {
	_deprecated_function( __FUNCTION__, '1.3', 'No longer required.' );
	return apply_filters( 'bp_search_form_enabled', true );
}

/**
 * Template tag version of bp_get_page_title()
 *
 * @deprecated 1.3
 * @deprecated Use wp_title()
 * @since 1.0
 */
function bp_page_title() {
	echo bp_get_page_title(); 
}
	/**
	 * Prior to BuddyPress 1.3, this was used to generate the page's <title> text.
	 * Now, just simply use wp_title().
	 *
	 * @deprecated 1.3
	 * @deprecated Use wp_title()
	 * @since 1.0
	 */
	function bp_get_page_title() {
		_deprecated_function( __FUNCTION__, '1.3', 'wp_title()' );
		$title = wp_title( '|', false, 'right' ) . get_bloginfo( 'name', 'display' );

		// Backpat for BP 1.2 filter
		$title = apply_filters( 'bp_page_title', esc_attr( $title ), esc_attr( $title ) );

		return apply_filters( 'bp_get_page_title', $title );
	}

/**
 * Generate a link to log out. Last used in BP 1.2-beta. You should be using wp_logout_url().
 *
 * @deprecated 1.3
 * @deprecated Use wp_logout_url()
 * @since 1.0
 */
function bp_log_out_link() {
	_deprecated_function( __FUNCTION__, '1.3', 'wp_logout_url()' );

	$logout_link = '<a href="' . wp_logout_url( bp_get_root_domain() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a>';
	echo apply_filters( 'bp_logout_link', $logout_link );
}

/**
 * Send an email and a BP notification on receipt of an @-mention in a group
 *
 * @deprecated 1.3
 * @deprecated Deprecated in favor of the more general bp_activity_at_message_notification()
 */
function groups_at_message_notification( $content, $poster_user_id, $group_id, $activity_id ) {
	global $bp;
	
	_deprecated_function( __FUNCTION__, '1.3', 'bp_activity_at_message_notification()' );

	/* Scan for @username strings in an activity update. Notify each user. */
	$pattern = '/[@]+([A-Za-z0-9-_\.@]+)/';
	preg_match_all( $pattern, $content, $usernames );

	/* Make sure there's only one instance of each username */
	if ( !$usernames = array_unique( $usernames[1] ) )
		return false;

	$group = new BP_Groups_Group( $group_id );

	foreach( (array)$usernames as $username ) {
		if ( !$receiver_user_id = bp_core_get_userid( $username ) )
			continue;

		/* Check the user is a member of the group before sending the update. */
		if ( !groups_is_user_member( $receiver_user_id, $group_id ) )
			continue;

		// Now email the user with the contents of the message (if they have enabled email notifications)
		if ( 'no' != bp_get_user_meta( $receiver_user_id, 'notification_activity_new_mention', true ) ) {
			$poster_name = bp_core_get_user_displayname( $poster_user_id );

			$message_link = bp_activity_get_permalink( $activity_id );
			$settings_link = bp_core_get_user_domain( $receiver_user_id ) . bp_get_settings_slug() . '/notifications/';

			$poster_name = stripslashes( $poster_name );
			$content = bp_groups_filter_kses( stripslashes( $content ) );

			// Set up and send the message
			$ud = bp_core_get_core_userdata( $receiver_user_id );
			$to = $ud->user_email;
			$sitename = wp_specialchars_decode( get_blog_option( bp_get_root_blog_id(), 'blogname' ), ENT_QUOTES );
			$subject  = '[' . $sitename . '] ' . sprintf( __( '%1$s mentioned you in the group "%2$s"', 'buddypress' ), $poster_name, $group->name );

$message = sprintf( __(
'%1$s mentioned you in the group "%2$s":

"%3$s"

To view and respond to the message, log in and visit: %4$s

---------------------
', 'buddypress' ), $poster_name, $group->name, $content, $message_link );

			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

			/* Send the message */
			$to = apply_filters( 'groups_at_message_notification_to', $to );
			$subject = apply_filters( 'groups_at_message_notification_subject', $subject, $group, $poster_name );
			$message = apply_filters( 'groups_at_message_notification_message', $message, $group, $poster_name, $content, $message_link, $settings_link );

			wp_mail( $to, $subject, $message );
		}
	}

	do_action( 'bp_groups_sent_mention_email', $usernames, $subject, $message, $content, $poster_user_id, $group_id, $activity_id );
}

/**
 * In BP 1.3, these functions were renamed for greater consistency
 */
function bp_forum_directory_permalink() { 
	_deprecated_function( __FUNCTION__, '1.3', 'bp_forums_directory_permalink()' );
	bp_forums_directory_permalink();
} 
	function bp_get_forum_directory_permalink() { 
		_deprecated_function( __FUNCTION__, '1.3', 'bp_get_forums_directory_permalink()' );
		return bp_get_forums_directory_permalink();
	}
	
/** Theme *********************************************************************/

/**
 * Contains functions which were moved out of BP-Default's functions.php
 * in BuddyPress 1.3.
 *
 * @since 1.3
 */
function bp_dtheme_deprecated() {
	if ( !function_exists( 'bp_dtheme_wp_pages_filter' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function filtered the dropdown on the
	 * Settings > Reading screen for selecting the page to show on front to
	 * include "Activity Stream." As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param string $page_html A list of pages as a dropdown (select list)
	 * @return string
	 * @see wp_dropdown_pages()
	 * @since 1.2
	 */
	function bp_dtheme_wp_pages_filter( $page_html ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $page_html;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_page_on_front_update' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function hijacked the saving of page on front setting to save the activity stream setting.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param $string $oldvalue Previous value of get_option( 'page_on_front' )
	 * @param $string $oldvalue New value of get_option( 'page_on_front' )
	 * @return string
	 * @since 1.2
	 */
	function bp_dtheme_page_on_front_update( $oldvalue, $newvalue ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		if ( !is_admin() || !is_super_admin() )
			return false;

		return $oldvalue;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_page_on_front_template' ) ) :
	/**
	 * In BuddyPress 1.2.x, this function loaded the activity stream template if the front page display settings allow.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param string $template Absolute path to the page template
	 * @return string
	 * @since 1.2
	 */
	function bp_dtheme_page_on_front_template( $template ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $template;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_fix_get_posts_on_activity_front' ) ) :
	/**
	 * In BuddyPress 1.2.x, this forced the page ID as a string to stop the get_posts query from kicking up a fuss.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @since 1.2
	 */
	function bp_dtheme_fix_get_posts_on_activity_front() {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
	}
	endif;

	if ( !function_exists( 'bp_dtheme_fix_the_posts_on_activity_front' ) ) :
	/**
	 * In BuddyPress 1.2.x, this was used as part of the code that set the activity stream to be on the front page.
	 * As of 1.3.x, it is no longer required.
	 *
	 * @deprecated 1.3
	 * @deprecated No longer required.
	 * @param array $posts Posts as retrieved by WP_Query
	 * @return array
	 * @since 1.2.5
	 */
	function bp_dtheme_fix_the_posts_on_activity_front( $posts ) {
		_deprecated_function( __FUNCTION__, '1.3', "No longer required." );
		return $posts;
	}
	endif;

	if ( !function_exists( 'bp_dtheme_add_blog_comments_js' ) ) :
	/**
	 * In BuddyPress 1.2.x, this added the javascript needed for blog comment replies.
	 * As of 1.3.x, we recommend that you enqueue the comment-reply javascript in your theme's header.php.
	 *
	 * @deprecated 1.3
	 * @deprecated Enqueue the comment-reply script in your theme's header.php.
	 * @since 1.2
	 */
	function bp_dtheme_add_blog_comments_js() {
		_deprecated_function( __FUNCTION__, '1.3', "Enqueue the comment-reply script in your theme's header.php." );
		if ( is_singular() && bp_is_blog_page() && get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );
	}
	endif;
}
add_action( 'after_setup_theme', 'bp_dtheme_deprecated', 15 );
?>