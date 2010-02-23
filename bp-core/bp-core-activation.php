<?php

function bp_core_screen_activation() {
	global $bp, $wpdb;

	if ( !bp_core_is_multisite() || BP_ACTIVATION_SLUG != $bp->current_component )
		return false;

	/* Check if an activation key has been passed */
	if ( isset( $_GET['key'] ) ) {

		require_once( ABSPATH . WPINC . '/registration.php' );

		/* Activate the signup */
		$signup = apply_filters( 'bp_core_activate_account', wpmu_activate_signup( $_GET['key'] ) );

		/* If there was errors, add a message and redirect */
		if ( $signup->errors ) {
			bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
			bp_core_redirect( $bp->root_domain . '/' . BP_ACTIVATION_SLUG );
		}

		/* Set the password */
		if ( !empty( $signup['meta']['password'] ) )
			$wpdb->update( $wpdb->users, array( 'user_pass' => $signup['meta']['password'] ), array( 'ID' => $signup['user_id'] ), array( '%s' ), array( '%d' ) );

		/* Set any profile data */
		if ( function_exists( 'xprofile_set_field_data' ) ) {

			if ( !empty( $signup['meta']['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $signup['meta']['profile_field_ids'] );

				foreach( (array)$profile_field_ids as $field_id ) {
					$current_field = $signup['meta']["field_{$field_id}"];

					if ( !empty( $current_field ) )
						xprofile_set_field_data( $field_id, $signup['user_id'], $current_field );
				}
			}

		}

		/* Check for an uploaded avatar and move that to the correct user folder */
		$hashed_key = wp_hash( $_GET['key'] );

		/* Check if the avatar folder exists. If it does, move rename it, move it and delete the signup avatar dir */
		if ( file_exists( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key ) ) {
			@rename( BP_AVATAR_UPLOAD_PATH . '/avatars/signups/' . $hashed_key, BP_AVATAR_UPLOAD_PATH . '/avatars/' . $signup['user_id'] );
		}

		/* Record the new user in the activity streams */
		if ( function_exists( 'bp_activity_add' ) ) {
			$userlink = bp_core_get_userlink( $signup['user_id'] );

			bp_activity_add( array(
				'user_id' => $signup['user_id'],
				'action' => apply_filters( 'bp_core_activity_registered_member_action', sprintf( __( '%s became a registered member', 'buddypress' ), $userlink ), $signup['user_id'] ),
				'component' => 'profile',
				'type' => 'new_member'
			) );
		}

		do_action( 'bp_core_account_activated', &$signup, $_GET['key'] );
		wp_cache_delete( 'bp_total_member_count', 'bp' );

		bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );

		$bp->activation_complete = true;
	}

	if ( '' != locate_template( array( 'registration/activate' ), false ) )
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'activate' ) );
	else
		bp_core_load_template( apply_filters( 'bp_core_template_activate', 'registration/activate' ) );
}
add_action( 'wp', 'bp_core_screen_activation', 3 );

/***
 * bp_core_filter_user_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
	add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );

/***
 * bp_core_filter_blog_welcome_email()
 *
 * Replace the generated password in the welcome email.
 * This will not filter when the site admin registers a user.
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {
	/* Don't touch the email if we don't have a custom registration template */
	if ( '' == locate_template( array( 'registration/register.php' ), false ) && '' == locate_template( array( 'register.php' ), false ) )
		return $welcome_email;

	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}
if ( !is_admin() && empty( $_GET['e'] ) )
	add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

// Notify user of signup success.
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {
	global $current_site;

	// Send email with activation link.
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = clean_url($activate_url);

	$admin_email = get_site_option( "admin_email" );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf(__("Thanks for registering! To complete the activation of your account and blog, please click the following link:\n\n%s\n\n\n\nAfter you activate, you can visit your blog here:\n\n%s", 'buddypress' ), $activate_url, clean_url("http://{$domain}{$path}" ) );
	$subject = '[' . $from_name . '] ' . sprintf(__('Activate %s', 'buddypress' ), clean_url('http://' . $domain . $path));

	/* Send the message */
	$to = apply_filters( 'bp_core_activation_signup_blog_notification_to', $user_email );
	$subject = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $subject );
	$message = apply_filters( 'bp_core_activation_signup_blog_notification_message', $message );

	wp_mail( $to, $subject, $message, $message_headers );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() )
	add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {
	global $current_site;

	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = clean_url($activate_url);
	$admin_email = get_site_option( "admin_email" );

	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];

	/* If this is an admin generated activation, add a param to email the user login details */
	if ( is_admin() )
		$email = '&e=1';

	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%s\n\n", 'buddypress' ), $activate_url . $email, clean_url("http://{$domain}{$path}" ) );
	$subject = '[' . $from_name . '] ' . __( 'Activate Your Account', 'buddypress' );

	/* Send the message */
	$to = apply_filters( 'bp_core_activation_signup_user_notification_to', $user_email );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message );

	wp_mail( $to, $subject, $message, $message_headers );

	// Return false to stop the original WPMU function from continuing
	return false;
}
if ( !is_admin() || ( is_admin() && empty( $_POST['noconfirmation'] ) ) )
	add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

?>