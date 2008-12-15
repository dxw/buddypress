<?php
require_once( 'bp-core.php' );

define ( 'BP_WIRE_IS_INSTALLED', 1 );
define ( 'BP_WIRE_VERSION', '1.0b1' );

include_once( 'bp-wire/bp-wire-classes.php' );
include_once( 'bp-wire/bp-wire-ajax.php' );
include_once( 'bp-wire/bp-wire-templatetags.php' );
include_once( 'bp-wire/bp-wire-cssjs.php' );
include_once( 'bp-wire/bp-wire-filters.php' );

/**************************************************************************
 bp_bp_wire_install()
 
 Sets up the component ready for use on a site installation.
 **************************************************************************/

function bp_wire_install() {
	global $wpdb, $bp;
	
	// No DB tables need to be installed, DB tables for each component wire
	// are set up within that component *if* this component is installed.
	add_site_option( 'bp-wire-version', BP_WIRE_VERSION );
}

/**************************************************************************
 bp_wire_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function bp_wire_setup_globals() {
	global $bp, $wpdb;
	
	if ( get_site_option('bp-wire-version') < BP_WIRE_VERSION ) {
		bp_wire_install();
	}
	
	$bp['wire'] = array(
		'image_base' => site_url() . '/wp-content/mu-plugins/bp-wire/images',
		'slug'		 => 'wire'
	);
}
add_action( 'wp', 'bp_wire_setup_globals', 1 );	
add_action( '_admin_menu', 'bp_wire_setup_globals', 1 );

/**************************************************************************
 bp_wire_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function bp_wire_setup_nav() {
	global $bp;

	/* Add 'Wire' to the main navigation */
	bp_core_add_nav_item( __('Wire', 'buddypress'), $bp['wire']['slug'] );
	bp_core_add_nav_default( $bp['wire']['slug'], 'bp_wire_screen_latest', 'all-posts' );

	/* Add the subnav items to the wire nav */
 	bp_core_add_subnav_item( $bp['wire']['slug'], 'all-posts', __('All Posts', 'buddypress'), $bp['loggedin_domain'] . $bp['wire']['slug'] . '/', 'bp_wire_screen_latest' );
	
	if ( $bp['current_component'] == $bp['wire']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Wire', 'buddypress');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'bp_wire_setup_nav', 2 );

/***** Screens **********/

function bp_wire_screen_latest() {
	bp_catch_uri( 'wire/latest' );	
}

function bp_wire_new_post( $item_id, $message, $table_name = null ) {
	global $bp;
	
	if ( empty($message) || !is_user_logged_in() )
		return false;
	
	if ( !$table_name )
		$table_name = $bp[$bp['current_component']]['table_name_wire'];

	$wire_post = new BP_Wire_Post( $table_name );
	$wire_post->item_id = $item_id;
	$wire_post->user_id = $bp['loggedin_userid'];
	$wire_post->date_posted = time();
	
	$message = strip_tags( $message, '<a>,<b>,<strong>,<i>,<em>,<img>' );
	$wire_post->content = $message;
	
	if ( !$wire_post->save() )
		return false;
	
	do_action( 'bp_wire_post_posted', $wire_post->id, $wire_post->item_id, $wire_post->user_id );
	
	return $wire_post->id;
}

function bp_wire_delete_post( $wire_post_id, $table_name = null ) {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	if ( !$table_name )
		$table_name = $bp[$bp['current_component']]['table_name_wire'];
	
	$wire_post = new BP_Wire_Post( $table_name, $wire_post_id );
	
	if ( !$bp['is_item_admin'] ) {
		if ( $wire_post->user_id != $bp['loggedin_userid'] )
			return false;
	}
	
	if ( !$wire_post->delete() )
		return false;

	do_action( 'bp_wire_post_deleted', $wire_post->id, $wire_post->item_id, $wire_post->user_id );
	
	return true;
}


?>