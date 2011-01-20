<?php

// Load the files containing functions that we globally will need.
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-component.php'  );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-hooks.php'      );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-catchuri.php'   );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-classes.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-filters.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-cssjs.php'      );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-avatars.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-template.php'   );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-widgets.php'    );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-deprecated.php' );

// If BP_DISABLE_ADMIN_BAR is defined, do not load the global admin bar.
if ( !defined( 'BP_DISABLE_ADMIN_BAR' ) )
	require ( BP_PLUGIN_DIR . '/bp-core/bp-core-adminbar.php' );

/** "And now for something completely different" ******************************/

/**
 * Sets up default global BuddyPress configuration settings and stores
 * them in a $bp variable.
 *
 * @package BuddyPress Core Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $current_user A WordPress global containing current user information
 * @global $current_component Which is set up in /bp-core/bp-core-catch-uri.php
 * @global $current_action Which is set up in /bp-core/bp-core-catch-uri.php
 * @global $action_variables Which is set up in /bp-core/bp-core-catch-uri.php
 * @uses bp_core_get_user_domain() Returns the domain for a user
 */
function bp_core_setup_globals() {
	global $bp;
	global $current_user, $current_component, $current_action, $current_blog;
	global $displayed_user_id, $bp_pages;
	global $action_variables;

	$current_user = wp_get_current_user();

	// Get the base database prefix
	$bp->table_prefix = bp_core_get_table_prefix();

	// The domain for the root of the site where the main blog resides
	$bp->root_domain  = bp_core_get_root_domain();

	// The names of the core WordPress pages used to display BuddyPress content
	$bp->pages        = $bp_pages;

	/** Members Component *****************************************************/

	// Set up the members id and active components entry
	$bp->members->id  = 'members';

	// Members Slugs
	$bp->members->slug = $bp->pages->members->slug;
	$bp->active_components[$bp->members->slug] = $bp->members->id;

	// The user ID of the user who is currently logged in.
	$bp->loggedin_user->id = $current_user->ID;

	/** Logged in user ********************************************************/

	// The domain for the user currently logged in. eg: http://domain.com/members/andy
	$bp->loggedin_user->domain = bp_core_get_user_domain( $bp->loggedin_user->id );

	// The core userdata of the user who is currently logged in.
	$bp->loggedin_user->userdata = bp_core_get_core_userdata( $bp->loggedin_user->id );

	// is_super_admin() hits the DB on single WP installs, so we need to get this separately so we can call it in a loop.
	$bp->loggedin_user->is_super_admin = is_super_admin();
	$bp->loggedin_user->is_site_admin  = $bp->loggedin_user->is_super_admin; // deprecated 1.2.6

	/** Displayed user ********************************************************/

	// The user id of the user currently being viewed, set in /bp-core/bp-core-catchuri.php
	$bp->displayed_user->id = $displayed_user_id;

	// The domain for the user currently being displayed
	$bp->displayed_user->domain = bp_core_get_user_domain( $bp->displayed_user->id );

	// The core userdata of the user who is currently being displayed
	$bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );

	/** Component and Action **************************************************/

	// The component being used eg: http://domain.com/members/andy/ [profile]
	$bp->current_component = $current_component; // type: string

	// The current action for the component eg: http://domain.com/members/andy/profile/ [edit]
	$bp->current_action = $current_action; // type: string

	// The action variables for the current action eg: http://domain.com/members/andy/profile/edit/ [group] / [6]
	$bp->action_variables = $action_variables; // type: array

	// Only used where a component has a sub item, e.g. groups: http://domain.com/members/andy/groups/ [my-group] / home - manipulated in the actual component not in catch uri code.
	$bp->current_item = ''; // type: string

	// Used for overriding the 2nd level navigation menu so it can be used to
	// display custom navigation for an item (for example a group)
	$bp->is_single_item = false;

	// The default component to use if none are set and someone visits: http://domain.com/members/andy
	if ( !defined( 'BP_DEFAULT_COMPONENT' ) ) {
		if ( isset( $bp->pages->activity ) )
			$bp->default_component = $bp->activity->id;
		else
			$bp->default_component = $bp->profile->id;
	} else {
		$bp->default_component = BP_DEFAULT_COMPONENT;
	}

	// Fetches all of the core BuddyPress settings in one fell swoop
	$bp->site_options = bp_core_get_site_options();

	// Sets up the array container for the component navigation rendered
	// by bp_get_nav()
	$bp->bp_nav = array();

	// Sets up the array container for the component options navigation
	// rendered by bp_get_options_nav()
	$bp->bp_options_nav = array();

	// Contains an array of all the active components. The key is the slug,
	// value the internal ID of the component.
	$bp->active_components = array();

	/** Avatars ***************************************************************/

	// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar
	$bp->grav_default->user  = apply_filters( 'bp_user_gravatar_default',  $bp->site_options['avatar_default'] );
	$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );
	$bp->grav_default->blog  = apply_filters( 'bp_blog_gravatar_default',  $bp->grav_default->user );

	// Fetch the full name for the logged in and current user
	$bp->loggedin_user->fullname  = bp_core_get_user_displayname( $bp->loggedin_user->id );
	$bp->displayed_user->fullname = bp_core_get_user_displayname( $bp->displayed_user->id );

	/**
	 * Used to determine if user has admin rights on current content. If the logged in user is viewing
	 * their own profile and wants to delete something, is_item_admin is used. This is a
	 * generic variable so it can be used by other components. It can also be modified, so when viewing a group
	 * 'is_item_admin' would be 1 if they are a group admin, 0 if they are not.
	 */
	$bp->is_item_admin = bp_user_has_access();

	// Used to determine if the logged in user is a moderator for the current content.
	$bp->is_item_mod = false;

	// Notifications Table
	$bp->core->table_name_notifications = $bp->table_prefix . 'bp_notifications';

	// Default Component
	if ( !$bp->current_component && $bp->displayed_user->id )
		$bp->current_component = $bp->default_component;

	// The default text for the members directory search box
	$bp->default_search_strings[$bp->members->slug] = __( 'Search Members...', 'buddypress' );

	do_action( 'bp_core_setup_globals' );
}
add_action( 'bp_setup_globals', 'bp_core_setup_globals', 1 );

/**
 * Allow filtering of database prefix. Intended for use in multinetwork installations.
 *
 * @global object $wpdb WordPress database object
 * @return string Filtered database prefix
 */
function bp_core_get_table_prefix() {
	global $wpdb;

	return apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
}

/**
 * Define the slugs used for BuddyPress pages, based on the slugs of the WP pages used.
 * These can be overridden manually by defining these slugs in wp-config.php.
 *
 * The fallback values are only used during initial BP page creation, when no slugs have been
 * explicitly defined.
 *
 * @package BuddyPress Core Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_define_slugs() {
	global $bp;

	if ( !defined( 'BP_MEMBERS_SLUG' ) )
		define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );

	if ( !defined( 'BP_REGISTER_SLUG' ) )
		define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );

	if ( !defined( 'BP_ACTIVATION_SLUG' ) )
		define( 'BP_ACTIVATION_SLUG', $bp->pages->activate->slug );

}
add_action( 'bp_setup_globals', 'bp_core_define_slugs' );


/**
 * Fetches BP pages from the meta table, depending on setup
 *
 * @package BuddyPress Core Core
 */
function bp_core_get_page_meta() {
	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
		$page_ids = get_blog_option( BP_ROOT_BLOG, 'bp-pages' );
	else
		$page_ids = get_option( 'bp-pages' );

	return $page_ids;
}

/**
 * Stores BP pages in the meta table, depending on setup
 *
 * @package BuddyPress Core Core
 */
function bp_core_update_page_meta( $page_ids ) {
	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
		update_blog_option( BP_ROOT_BLOG, 'bp-pages', $page_ids );
	else
		update_option( 'bp-pages', $page_ids );
}

function bp_core_get_page_names() {
	global $wpdb, $bp;

	// Set pages as standard class
	$pages = new stdClass;

	// When upgrading to BP 1.3+ from a version of BP that does not use WP
	// pages, $bp->pages must be populated with dummy info to avoid crashing the
	// site while the db is upgraded.
	if ( !$page_ids = bp_core_get_page_meta() ) {
		foreach ( $bp->active_components as $component ) {
			$pages->{$component->id}->name = $component->id;
			$pages->{$component->id}->slug = $component->id;
			$pages->{$component->id}->id   = $component->id;
		}

		return $pages;
	}

	$posts_table_name = is_multisite() && !defined( 'BP_ENABLE_MULTIBLOG' ) ? $wpdb->get_blog_prefix( BP_ROOT_BLOG ) . 'posts' : $wpdb->posts;
	$page_ids_sql     = implode( ',', (array)$page_ids );
	$page_names       = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_name, post_parent FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) " ) );

	foreach ( (array)$page_ids as $key => $page_id ) {
		foreach ( (array)$page_names as $page_name ) {
			if ( $page_name->ID == $page_id ) {
				$pages->{$key}->name = $page_name->post_name;
				$pages->{$key}->id   = $page_name->ID;
				$slug[]              = $page_name->post_name;

				// Get the slug
				while ( $page_name->post_parent != 0 ) {
					$parent                 = $wpdb->get_results( $wpdb->prepare( "SELECT post_name, post_parent FROM {$posts_table_name} WHERE ID = %d", $page_name->post_parent ) );
					$slug[]                 = $parent[0]->post_name;
					$page_name->post_parent = $parent[0]->post_parent;
				}

				$pages->{$key}->slug = implode( '/', array_reverse( (array)$slug ) );
			}

			unset( $slug );
		}
	}

	return apply_filters( 'bp_core_get_page_names', $pages );
}

/**
 * Creates a default component slug from a WP page root_slug
 *
 * Since 1.3, BP components get their root_slug (the slug used immediately
 * following the root domain) from the slug of a corresponding WP page.
 *
 * E.g. if your BP installation at example.com has its members page at
 * example.com/community/people, $bp->members->root_slug will be 'community/people'.
 *
 * By default, this function creates a shorter version of the root_slug for
 * use elsewhere in the URL, by returning the content after the final '/'
 * in the root_slug ('people' in the example above).
 *
 * Filter on 'bp_core_component_slug_from_root_slug' to override this method
 * in general, or define a specific component slug constant (e.g. BP_MEMBERS_SLUG)
 * to override specific component slugs.
 *
 * @package BuddyPress Core
 * @since 1.3
 *
 * @param str $root_slug The root slug, which comes from $bp->pages->[component]->slug
 * @return str $slug The short slug for use in the middle of URLs
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	$slug_chunks = explode( '/', $root_slug );
 	$slug        = array_pop( $slug_chunks );

 	return apply_filters( 'bp_core_component_slug_from_root_slug', $slug, $root_slug );
}

/**
 * Initializes the wp-admin area "BuddyPress" menus and sub menus.
 *
 * @package BuddyPress Core
 * @uses is_super_admin() returns true if the current user is a site admin, false if not
 */
function bp_core_admin_menu_init() {
	if ( !is_super_admin() )
		return false;

	require ( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin.php' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_core_admin_menu_init' );

/**
 * Adds the "BuddyPress" admin submenu item to the Site Admin tab.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses is_super_admin() returns true if the current user is a site admin, false if not
 * @uses add_submenu_page() WP function to add a submenu item
 */
function bp_core_add_admin_menu() {
	if ( !is_super_admin() )
		return false;

	// Don't add this version of the admin menu if a BP upgrade is in progress
 	// See bp_core_update_add_admin_menu()
	if ( defined( 'BP_IS_UPGRADE' ) && BP_IS_UPGRADE )
 		return false;

	$hooks = array();

	// Add the administration tab under the "Site Admin" tab for site administrators
	$hooks[] = bp_core_add_admin_menu_page( array(
		'menu_title' => __( 'BuddyPress', 'buddypress' ),
		'page_title' => __( 'BuddyPress', 'buddypress' ),
		'capability' => 'manage_options',
		'file'       => 'bp-general-settings',
		'function'   => 'bp_core_admin_dashboard',
		'position'   => 2
	) );

	$hooks[] = add_submenu_page( 'bp-general-settings', __( 'BuddyPress Dashboard', 'buddypress' ), __( 'Dashboard', 'buddypress' ), 'manage_options', 'bp-general-settings', 'bp_core_admin_dashboard' );
	$hooks[] = add_submenu_page( 'bp-general-settings', __( 'Settings', 'buddypress' ),       __( 'Settings',  'buddypress' ), 'manage_options', 'bp-settings',         'bp_core_admin_settings'  );
	$hooks[] = add_submenu_page( 'bp-general-settings', __( 'Component Setup', 'buddypress' ),             __( 'Component Setup',  'buddypress' ), 'manage_options', 'bp-component-setup',         'bp_core_admin_component_setup'  );

	// Add a hook for css/js
	foreach( $hooks as $hook ) {
		add_action( "admin_print_styles-$hook", 'bp_core_add_admin_menu_styles' );
	}
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_core_add_admin_menu', 9 );

/**
 * Sets up the profile navigation item if the Xprofile component is not installed.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_new_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_new_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_my_profile() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_fetch_avatar() Returns the either the thumb or full avatar URL for the user_id passed
 */
function bp_core_setup_nav() {
	global $bp;

	/***
	 * If the extended profiles component is disabled, we need to revert to using the
	 * built in WordPress profile information
	 */
	if ( !bp_is_active( 'xprofile' ) ) {

		// Fallback values if xprofile is disabled
		$bp->core->profile->slug = 'profile';
		$bp->active_components[$bp->core->profile->slug] = $bp->core->profile->slug;

		// Add 'Profile' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Profile', 'buddypress' ),
			'slug'                => $bp->core->profile->slug,
			'position'            => 20,
			'screen_function'     => 'bp_core_catch_profile_uri',
			'default_subnav_slug' => 'public'
		) );

		$profile_link = $bp->loggedin_user->domain . '/profile/';

		// Add the subnav items to the profile
		bp_core_new_subnav_item( array(
			'name'            => __( 'Public', 'buddypress' ),
			'slug'            => 'public',
			'parent_url'      => $profile_link,
			'parent_slug'     => $bp->core->profile->slug,
			'screen_function' => 'bp_core_catch_profile_uri'
		) );

		// Looking at a profile
		if ( 'profile' == $bp->current_component ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Profile', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
				$bp->bp_options_title  = $bp->displayed_user->fullname;
			}
		}
	}
}
add_action( 'bp_setup_nav', 'bp_core_setup_nav' );

/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

/**
 * Listens to the $bp component and action variables to determine if the user is viewing the members
 * directory page. If they are, it will set up the directory and load the members directory template.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_enqueue_script() Loads a JS script into the header of the page.
 * @uses bp_core_load_template() Loads a specific template file.
 */
function bp_core_action_directory_members() {
	global $bp;

	if ( is_null( $bp->displayed_user->id ) && $bp->current_component == $bp->members->slug ) {
		$bp->is_directory = true;

		do_action( 'bp_core_action_directory_members' );
		bp_core_load_template( apply_filters( 'bp_core_template_directory_members', 'members/index' ) );
	}
}
add_action( 'wp', 'bp_core_action_directory_members', 2 );

/**
 * When a site admin selects "Mark as Spammer/Not Spammer" from the admin menu
 * this action will fire and mark or unmark the user and their blogs as spam.
 * Must be a site admin for this function to run.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_action_set_spammer_status() {
	global $bp, $wpdb, $wp_version;

	if ( !is_super_admin() || bp_is_my_profile() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && ( 'mark-spammer' == $bp->current_action || 'unmark-spammer' == $bp->current_action ) ) {
		// Check the nonce
		check_admin_referer( 'mark-unmark-spammer' );

		// Get the functions file
		if ( is_multisite() )
			require_once( ABSPATH . 'wp-admin/includes/ms.php' );

		if ( 'mark-spammer' == $bp->current_action )
			$is_spam = 1;
		else
			$is_spam = 0;

		// Get the blogs for the user
		$blogs = get_blogs_of_user( $bp->displayed_user->id, true );

		foreach ( (array) $blogs as $key => $details ) {
			// Do not mark the main or current root blog as spam
			if ( 1 == $details->userblog_id || BP_ROOT_BLOG == $details->userblog_id )
				continue;

			// Update the blog status
			update_blog_status( $details->userblog_id, 'spam', $is_spam );

			// Fire the standard multisite hook
			do_action( 'make_spam_blog', $details->userblog_id );
		}

		// Finally, mark this user as a spammer
		if ( is_multisite() )
			$wpdb->update( $wpdb->users, array( 'spam' => $is_spam ), array( 'ID' => $bp->displayed_user->id ) );

		$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $bp->displayed_user->id ) );

		if ( $is_spam )
			bp_core_add_message( __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' ) );
		else
			bp_core_add_message( __( 'User removed as spammer.', 'buddypress' ) );

		// Hide this user's activity
		if ( $is_spam && function_exists( 'bp_activity_hide_user_activity' ) )
			bp_activity_hide_user_activity( $bp->displayed_user->id );

		// We need a special hook for is_spam so that components can
		// delete data at spam time
		if ( $is_spam )
			do_action( 'bp_make_spam_user', $bp->displayed_user->id );

		do_action( 'bp_core_action_set_spammer_status', $bp->displayed_user->id, $is_spam );

		bp_core_redirect( wp_get_referer() );
	}
}
add_action( 'wp', 'bp_core_action_set_spammer_status', 3 );

/**
 * Allows a site admin to delete a user from the adminbar menu.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_action_delete_user() {
	global $bp;

	if ( !is_super_admin() || bp_is_my_profile() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && 'delete-user' == $bp->current_action ) {
		// Check the nonce
		check_admin_referer( 'delete-user' );

		$errors = false;

		if ( bp_core_delete_account( $bp->displayed_user->id ) ) {
			bp_core_add_message( sprintf( __( '%s has been deleted from the system.', 'buddypress' ), $bp->displayed_user->fullname ) );
		} else {
			bp_core_add_message( sprintf( __( 'There was an error deleting %s from the system. Please try again.', 'buddypress' ), $bp->displayed_user->fullname ), 'error' );
			$errors = true;
		}

		do_action( 'bp_core_action_delete_user', $errors );

		if ( $errors )
			bp_core_redirect( $bp->displayed_user->domain );
		else
			bp_core_redirect( $bp->loggedin_user->domain );
	}
}
add_action( 'wp', 'bp_core_action_delete_user', 3 );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/**
 * Return an array of users IDs based on the parameters passed.
 *
 * @package BuddyPress Core
 */
function bp_core_get_users( $args = '' ) {
	global $bp;

	$defaults = array(
		'type' => 'active', // active, newest, alphabetical, random or popular
		'user_id' => false, // Pass a user_id to limit to only friend connections for this user
		'exclude' => false, // Users to exclude from results
		'search_terms' => false, // Limit to users that match these search terms

		'include' => false, // Pass comma separated list of user_ids to limit to only these users
		'per_page' => 20, // The number of results to return per page
		'page' => 1, // The page to return if limiting per page
		'populate_extras' => true, // Fetch the last active, where the user is a friend, total friend count, latest update
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_core_get_users', BP_Core_User::get_users( $type, $per_page, $page, $user_id, $include, $search_terms, $populate_extras, $exclude ), $params );
}

/**
 * Returns the domain for the passed user: e.g. http://domain.com/members/andy/
 *
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @param user_id The ID of the user.
 * @uses get_user_meta() WordPress function to get the usermeta for a user.
 */
function bp_core_get_user_domain( $user_id, $user_nicename = false, $user_login = false ) {
	global $bp;

	if ( !$user_id ) return;

	if ( !$domain = wp_cache_get( 'bp_user_domain_' . $user_id, 'bp' ) ) {
		$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

		// If we are using a members slug, include it.
		if ( !defined( 'BP_ENABLE_ROOT_PROFILES' ) )
			$domain = $bp->root_domain . '/' . $bp->members->slug . '/' . $username . '/';
		else
			$domain = $bp->root_domain . '/' . $username . '/';

		// Cache the link
		if ( !empty( $domain ) )
			wp_cache_set( 'bp_user_domain_' . $user_id, $domain, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_domain', $domain );
}

/**
 * Fetch everything in the wp_users table for a user, without any usermeta.
 *
 * @package BuddyPress Core
 * @param user_id The ID of the user.
 * @uses BP_Core_User::get_core_userdata() Performs the query.
 */
function bp_core_get_core_userdata( $user_id ) {
	if ( empty( $user_id ) )
		return false;

	if ( !$userdata = wp_cache_get( 'bp_core_userdata_' . $user_id, 'bp' ) ) {
		$userdata = BP_Core_User::get_core_userdata( $user_id );
		wp_cache_set( 'bp_core_userdata_' . $user_id, $userdata, 'bp' );
	}
	return apply_filters( 'bp_core_get_core_userdata', $userdata );
}

/**
 * Returns the domain for the root blog.
 * eg: http://domain.com/ OR https://domain.com
 *
 * @package BuddyPress Core
 * @uses get_blog_option() WordPress function to fetch blog meta.
 * @return $domain The domain URL for the blog.
 */
function bp_core_get_root_domain() {
	global $current_blog;

	if ( defined( 'BP_ENABLE_MULTIBLOG' ) )
		$domain = get_home_url( $current_blog->blog_id );
	else
		$domain = get_home_url( BP_ROOT_BLOG );

	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Returns the user id for the user that is currently being displayed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 *
 * @package BuddyPress Core
 * @uses bp_core_get_userid_from_user_login() Returns the user id for the username passed
 * @return The user id for the user that is currently being displayed, return zero if this is not a user home and just a normal blog.
 */
function bp_core_get_displayed_userid( $user_login ) {
	return apply_filters( 'bp_core_get_displayed_userid', bp_core_get_userid( $user_login ) );
}

/**
 * Adds a navigation item to the main navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name'                    => false, // Display name for the nav item
		'slug'                    => false, // URL slug for the nav item
		'item_css_id'             => false, // The CSS ID to apply to the HTML of the nav item
		'show_for_displayed_user' => true,  // When viewing another user does this nav item show up?
		'site_admin_only'         => false, // Can only site admins see this nav item?
		'position'                => 99,    // Index of where this nav item should be positioned
		'screen_function'         => false, // The name of the function to run when clicked
		'default_subnav_slug'     => false  // The slug of the default subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If we don't have the required info we need, don't create this subnav item
	if ( empty($name) || empty($slug) )
		return false;

	// If this is for site admins only and the user is not one, don't create the subnav item
	if ( $site_admin_only && !is_super_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_nav[$slug] = array(
		'name'                    => $name,
		'slug'                    => $slug,
		'link'                    => $bp->loggedin_user->domain . $slug . '/',
		'css_id'                  => $item_css_id,
		'show_for_displayed_user' => $show_for_displayed_user,
		'position'                => $position,
		'screen_function'         => &$screen_function
	);

 	/***
	 * If this nav item is hidden for the displayed user, and
	 * the logged in user is not the displayed user
	 * looking at their own profile, don't create the nav item.
	 */
	if ( !$show_for_displayed_user && !bp_user_has_access() )
		return false;

	/***
 	 * If we are not viewing a user, and this is a root component, don't attach the
 	 * default subnav function so we can display a directory or something else.
 	 */
	if ( bp_is_root_component( $slug ) && !$bp->displayed_user->id )
		return;

	if ( $bp->current_component == $slug && !$bp->current_action ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( $default_subnav_slug )
			$bp->current_action = $default_subnav_slug;
	}
}

/**
 * Modify the default subnav item to load when a top level nav item is clicked.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_default( $args = '' ) {
	global $bp;

	$defaults = array(
		'parent_slug'     => false, // Slug of the parent
		'screen_function' => false, // The name of the function to run when clicked
		'subnav_slug'     => false  // The slug of the subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( $function = $bp->bp_nav[$parent_slug]['screen_function'] ) {
		if ( !is_object( $function[0] ) )
			remove_action( 'wp', $function, 3 );
		else
			remove_action( 'wp', array( &$function[0], $function[1] ), 3 );
	}

	$bp->bp_nav[$parent_slug]['screen_function'] = &$screen_function;

	if ( $bp->current_component == $parent_slug && !$bp->current_action ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( $subnav_slug )
			$bp->current_action = $subnav_slug;
	}
}

/**
 * We can only sort nav items by their position integer at a later point in time, once all
 * plugins have registered their navigation items.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_sort_nav_items() {
	global $bp;

	if ( empty( $bp->bp_nav ) || !is_array( $bp->bp_nav ) )
		return false;

	foreach ( (array)$bp->bp_nav as $slug => $nav_item ) {
		if ( empty( $temp[$nav_item['position']]) )
			$temp[$nav_item['position']] = $nav_item;
		else {
			// increase numbers here to fit new items in.
			do {
				$nav_item['position']++;
			} while ( !empty( $temp[$nav_item['position']] ) );

			$temp[$nav_item['position']] = $nav_item;
		}
	}

	ksort( $temp );
	$bp->bp_nav = &$temp;
}
add_action( 'wp_head',    'bp_core_sort_nav_items' );
add_action( 'admin_head', 'bp_core_sort_nav_items' );

/**
 * Adds a navigation item to the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_subnav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name'            => false, // Display name for the nav item
		'slug'            => false, // URL slug for the nav item
		'parent_slug'     => false, // URL slug of the parent nav item
		'parent_url'      => false, // URL of the parent item
		'item_css_id'     => false, // The CSS ID to apply to the HTML of the nav item
		'user_has_access' => true,  // Can the logged in user see this nav item?
		'site_admin_only' => false, // Can only site admins see this nav item?
		'position'        => 90,    // Index of where this nav item should be positioned
		'screen_function' => false  // The name of the function to run when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If we don't have the required info we need, don't create this subnav item
	if ( empty( $name ) || empty( $slug ) || empty( $parent_slug ) || empty( $parent_url ) || empty( $screen_function ) )
		return false;

	// If this is for site admins only and the user is not one, don't create the subnav item
	if ( $site_admin_only && !is_super_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_options_nav[$parent_slug][$slug] = array(
		'name'            => $name,
		'link'            => $parent_url . $slug . '/',
		'slug'            => $slug,
		'css_id'          => $item_css_id,
		'position'        => $position,
		'user_has_access' => $user_has_access,
		'screen_function' => &$screen_function
	);

	if ( ( $bp->current_action == $slug && $bp->current_component == $parent_slug ) && $user_has_access ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );
	}
}

function bp_core_sort_subnav_items() {
	global $bp;

	if ( empty( $bp->bp_options_nav ) || !is_array( $bp->bp_options_nav ) )
		return false;

	foreach ( (array)$bp->bp_options_nav as $parent_slug => $subnav_items ) {
		if ( !is_array( $subnav_items ) )
			continue;

		foreach ( (array)$subnav_items as $subnav_item ) {
			if ( empty( $temp[$subnav_item['position']]) )
				$temp[$subnav_item['position']] = $subnav_item;
			else {
				// increase numbers here to fit new items in.
				do {
					$subnav_item['position']++;
				} while ( !empty( $temp[$subnav_item['position']] ) );

				$temp[$subnav_item['position']] = $subnav_item;
			}
		}
		ksort( $temp );
		$bp->bp_options_nav[$parent_slug] = &$temp;
		unset( $temp );
	}
}
add_action( 'wp_head',    'bp_core_sort_subnav_items' );
add_action( 'admin_head', 'bp_core_sort_subnav_items' );

/**
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_nav_item( $parent_id ) {
	global $bp;

	// Unset subnav items for this nav item
	if ( is_array( $bp->bp_options_nav[$parent_id] ) ) {
		foreach( (array)$bp->bp_options_nav[$parent_id] as $subnav_item ) {
			bp_core_remove_subnav_item( $parent_id, $subnav_item['slug'] );
		}
	}

	if ( $function = $bp->bp_nav[$parent_id]['screen_function'] ) {
		if ( !is_object( $function[0] ) )
			remove_action( 'wp', $function, 3 );
		else
			remove_action( 'wp', array( &$function[0], $function[1] ), 3 );
	}

	unset( $bp->bp_nav[$parent_id] );
}

/**
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_subnav_item( $parent_id, $slug ) {
	global $bp;

	$screen_function = $bp->bp_options_nav[$parent_id][$slug]['screen_function'];

	if ( $screen_function ) {
		if ( !is_object( $screen_function[0] ) )
			remove_action( 'wp', $screen_function, 3 );
		else
			remove_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );
	}

	unset( $bp->bp_options_nav[$parent_id][$slug] );

	if ( !count( $bp->bp_options_nav[$parent_id] ) )
		unset($bp->bp_options_nav[$parent_id]);
}

/**
 * Clear the subnav items for a specific nav item.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_reset_subnav_items( $parent_slug ) {
	global $bp;

	unset( $bp->bp_options_nav[$parent_slug] );
}

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_random_member() {
	global $bp;

	if ( isset( $_GET['random-member'] ) ) {
		$user = bp_core_get_users( array( 'type' => 'random', 'per_page' => 1 ) );
		bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->id ) );
	}
}
add_action( 'wp', 'bp_core_get_random_member' );

/**
 * Returns the user_id for a user based on their username.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid( $username ) {
	global $wpdb;

	if ( empty( $username ) )
		return false;

	return apply_filters( 'bp_core_get_userid', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) ) );
}

/**
 * Returns the user_id for a user based on their user_nicename.
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid_from_nicename( $user_nicename ) {
	global $wpdb;

	if ( empty( $user_nicename ) )
		return false;

	return apply_filters( 'bp_core_get_userid_from_nicename', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_nicename = %s", $user_nicename ) ) );
}

/**
 * Returns the username for a user based on their user id.
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str the username of the matched user.
 */
function bp_core_get_username( $user_id, $user_nicename = false, $user_login = false ) {
	global $bp;

	if ( !$username = wp_cache_get( 'bp_user_username_' . $user_id, 'bp' ) ) {
		if ( empty( $user_nicename ) && empty( $user_login ) ) {
			$ud = false;

			if ( isset( $bp->loggedin_user->id ) && $bp->loggedin_user->id == $user_id )
				$ud = &$bp->loggedin_user->userdata;

			if ( isset( $bp->displayed_user->id ) && $bp->displayed_user->id == $user_id )
				$ud = &$bp->displayed_user->userdata;

			if ( empty( $ud ) ) {
				if ( !$ud = bp_core_get_core_userdata( $user_id ) )
					return false;
			}

			$user_nicename = $ud->user_nicename;
			$user_login = $ud->user_login;
		}

		if ( defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) )
			$username = $user_login;
		else
			$username = $user_nicename;
	}

	// Add this to cache
	if ( !empty( $username ) )
		wp_cache_set( 'bp_user_username_' . $user_id, $username, 'bp' );

	return apply_filters( 'bp_core_get_username', $username );
}

/**
 * Returns the email address for the user based on user ID
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str The email for the matched user.
 */
function bp_core_get_user_email( $uid ) {
	if ( !$email = wp_cache_get( 'bp_user_email_' . $uid, 'bp' ) ) {
		$ud = bp_core_get_core_userdata($uid);
		$email = $ud->user_email;
		wp_cache_set( 'bp_user_email_' . $uid, $email, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_email', $email );
}

/**
 * Returns a HTML formatted link for a user with the user's full name as the link text.
 * eg: <a href="http://andy.domain.com/">Andy Peatling</a>
 * Optional parameters will return just the name, or just the URL, or disable "You" text when
 * user matches the logged in user.
 *
 * [NOTES: This function needs to be cleaned up or split into separate functions]
 *
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @param $no_anchor bool Disable URL and HTML and just return full name. Default false.
 * @param $just_link bool Disable full name and HTML and just return the URL text. Default false.
 * @param $no_you bool Disable replacing full name with "You" when logged in user is equal to the current user. Default false.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @uses bp_fetch_user_fullname() Returns the full name for a user based on user ID.
 * @uses bp_core_get_userurl() Returns the URL for the user with no anchor tag based on user ID
 * @return false on no match
 * @return str The link text based on passed parameters.
 */
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false ) {
	$display_name = bp_core_get_user_displayname( $user_id );

	if ( empty( $display_name ) )
		return false;

	if ( $no_anchor )
		return $display_name;

	if ( !$url = bp_core_get_user_domain($user_id) )
		return false;

	if ( $just_link )
		return $url;

	return apply_filters( 'bp_core_get_userlink', '<a href="' . $url . '" title="' . $display_name . '">' . $display_name . '</a>', $user_id );
}


/**
 * Fetch the display name for a user. This will use the "Name" field in xprofile if it is installed.
 * Otherwise, it will fall back to the normal WP display_name, or user_nicename, depending on what has been set.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_cache_get() Will try and fetch the value from the cache, rather than querying the DB again.
 * @uses get_userdata() Fetches the WP userdata for a specific user.
 * @uses xprofile_set_field_data() Will update the field data for a user based on field name and user id.
 * @uses wp_cache_set() Adds a value to the cache.
 * @return str The display name for the user in question.
 */
function bp_core_get_user_displayname( $user_id_or_username ) {
	global $bp;

	if ( !$user_id_or_username )
		return false;

	if ( !is_numeric( $user_id_or_username ) )
		$user_id = bp_core_get_userid( $user_id_or_username );
	else
		$user_id = $user_id_or_username;

	if ( !$user_id )
		return false;

	if ( !$fullname = wp_cache_get( 'bp_user_fullname_' . $user_id, 'bp' ) ) {
		if ( bp_is_active( 'xprofile' ) ) {
			$fullname = xprofile_get_field_data( stripslashes( $bp->site_options['bp-xprofile-fullname-field-name'] ), $user_id );

			if ( empty($fullname) ) {
				$ud = bp_core_get_core_userdata( $user_id );

				if ( !empty( $ud->display_name ) )
					$fullname = $ud->display_name;
				else
					$fullname = $ud->user_nicename;

				xprofile_set_field_data( 1, $user_id, $fullname );
			}
		} else {
			$ud = bp_core_get_core_userdata($user_id);

			if ( !empty( $ud->display_name ) )
				$fullname = $ud->display_name;
			else
				$fullname = $ud->user_nicename;
		}

		if ( !empty( $fullname ) )
			wp_cache_set( 'bp_user_fullname_' . $user_id, $fullname, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_displayname', $fullname, $user_id );
}
add_filter( 'bp_core_get_user_displayname', 'strip_tags', 1 );
add_filter( 'bp_core_get_user_displayname', 'trim'          );
add_filter( 'bp_core_get_user_displayname', 'stripslashes'  );


/**
 * Returns the user link for the user based on user email address
 *
 * @package BuddyPress Core
 * @param $email str The email address for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @uses get_user_by_email() WordPress function to get userdata via an email address
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by_email( $email );
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false, true ) );
}

/**
 * Returns the user link for the user based on user's username
 *
 * @package BuddyPress Core
 * @param $username str The username for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	global $wpdb;

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) );
	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false, true ) );
}

/**
 * Returns the total number of members for the installation.
 *
 * @package BuddyPress Core
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb, $bp;

	if ( !$count = wp_cache_get( 'bp_total_member_count', 'bp' ) ) {
		$status_sql = bp_core_get_status_sql();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM " . CUSTOM_USER_TABLE . " WHERE {$status_sql}" ) );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Checks if the user has been marked as a spammer.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if spammer, 0 if not.
 */
function bp_core_is_user_spammer( $user_id ) {
	global $wpdb;

	if ( is_multisite() )
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT spam FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );
	else
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_status FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );

	return apply_filters( 'bp_core_is_user_spammer', $is_spammer );
}

/**
 * Checks if the user has been marked as deleted.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if deleted, 0 if not.
 */
function bp_core_is_user_deleted( $user_id ) {
	global $wpdb;

	return apply_filters( 'bp_core_is_user_spammer', (int) $wpdb->get_var( $wpdb->prepare( "SELECT deleted FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) ) );
}

/**
 * Get the current GMT time to save into the DB
 *
 * @package BuddyPress Core
 * @since 1.2.6
 */
function bp_core_current_time( $gmt = true ) {
	// Get current time in MYSQL format
	$current_time = current_time( 'mysql', $gmt );

	return apply_filters( 'bp_core_current_time', $current_time );
}

/**
 * Adds a feedback (error/success) message to the WP cookie so it can be
 * displayed after the page reloads.
 *
 * @package BuddyPress Core
 *
 * @global obj $bp
 * @param str $message Feedback to give to user
 * @param str $type updated|success|error|warning
 */
function bp_core_add_message( $message, $type = '' ) {
	global $bp;

	// Success is the default
	if ( empty( $type ) )
		$type = 'success';

	// Send the values to the cookie for page reload display
	@setcookie( 'bp-message',      $message, time() + 60 * 60 * 24, COOKIEPATH );
	@setcookie( 'bp-message-type', $type,    time() + 60 * 60 * 24, COOKIEPATH );

	/***
	 * Send the values to the $bp global so we can still output messages
	 * without a page reload
	 */
	$bp->template_message      = $message;
	$bp->template_message_type = $type;
}

/**
 * Checks if there is a feedback message in the WP cookie, if so, adds a
 * "template_notices" action so that the message can be parsed into the template
 * and displayed to the user.
 *
 * After the message is displayed, it removes the message vars from the cookie
 * so that the message is not shown to the user multiple times.
 *
 * @package BuddyPress Core
 * @global $bp_message The message text
 * @global $bp_message_type The type of message (error/success)
 * @uses setcookie() Sets a cookie value for the user.
 */
function bp_core_setup_message() {
	global $bp;

	if ( empty( $bp->template_message ) && isset( $_COOKIE['bp-message'] ) )
		$bp->template_message = $_COOKIE['bp-message'];

	if ( empty( $bp->template_message_type ) && isset( $_COOKIE['bp-message-type'] ) )
		$bp->template_message_type = $_COOKIE['bp-message-type'];

	add_action( 'template_notices', 'bp_core_render_message' );

	@setcookie( 'bp-message',      false, time() - 1000, COOKIEPATH );
	@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH );
}
add_action( 'wp', 'bp_core_setup_message', 2 );

/**
 * Renders a feedback message (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_render_message() {
	global $bp;

	if ( isset( $bp->template_message ) && $bp->template_message ) :
		$type = ( 'success' == $bp->template_message_type ) ? 'updated' : 'error'; ?>

		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo stripslashes( esc_attr( $bp->template_message ) ); ?></p>
		</div>

	<?php

		do_action( 'bp_core_render_message' );

	endif;
}

/**
 * Based on function created by Dunstan Orchard - http://1976design.com
 *
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours and 50 minutes
 * eg: 4 days
 * eg: 4 weeks and 6 days
 *
 * @package BuddyPress Core
 * @param $older_date int Unix timestamp of date you want to calculate the time since for
 * @param $newer_date int Unix timestamp of date to compare older date to. Default false (current time).
 * @return str The time since.
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	// array of time period chunks
	$chunks = array(
		array( 60 * 60 * 24 * 365 , __( 'year', 'buddypress' ), __( 'years', 'buddypress' ) ),
		array( 60 * 60 * 24 * 30 , __( 'month', 'buddypress' ), __( 'months', 'buddypress' ) ),
		array( 60 * 60 * 24 * 7, __( 'week', 'buddypress' ), __( 'weeks', 'buddypress' ) ),
		array( 60 * 60 * 24 , __( 'day', 'buddypress' ), __( 'days', 'buddypress' ) ),
		array( 60 * 60 , __( 'hour', 'buddypress' ), __( 'hours', 'buddypress' ) ),
		array( 60 , __( 'minute', 'buddypress' ), __( 'minutes', 'buddypress' ) ),
		array( 1, __( 'second', 'buddypress' ), __( 'seconds', 'buddypress' ) )
	);

	if ( !is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
		$older_date  = gmmktime( (int)$time_chunks[1], (int)$time_chunks[2], (int)$time_chunks[3], (int)$date_chunks[1], (int)$date_chunks[2], (int)$date_chunks[0] );
	}

	/**
	 * $newer_date will equal false if we want to know the time elapsed between
	 * a date and the current time. $newer_date will have a value if we want to
	 * work out time elapsed between two known dates.
	 */
	$newer_date = ( !$newer_date ) ? strtotime( bp_core_current_time() ) : $newer_date;

	// Difference in seconds
	$since = $newer_date - $older_date;

	// Something went wrong with date calculation and we ended up with a negative date.
	if ( 0 > $since )
		return __( 'sometime', 'buddypress' );

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */

	// Step one: the first chunk
	for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
		$seconds = $chunks[$i][0];

		// Finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	// If $i iterates all the way to $j, then the event happened 0 seconds ago
	if ( !isset( $chunks[$i] ) )
		return '0 ' . __( 'seconds', 'buddypress' );

	// Set output var
	$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];

	// Step two: the second chunk
	if ( $i + 2 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			// Add to output var
			$output .= ( 1 == $count2 ) ? _x( ',', 'Separator in time since', 'buddypress' ) . ' 1 '. $chunks[$i + 1][1] : _x( ',', 'Separator in time since', 'buddypress' ) . ' ' . $count2 . ' ' . $chunks[$i + 1][2];
		}
	}

	if ( !(int)trim( $output ) )
		$output = '0 ' . __( 'seconds', 'buddypress' );

	return $output;
}

/**
 * Record user activity to the database. Many functions use a "last active" feature to
 * show the length of time since the user was last active.
 * This function will update that time as a usermeta setting for the user every 5 minutes.
 *
 * @package BuddyPress Core
 * @global $userdata WordPress user data for the current logged in user.
 * @uses update_user_meta() WordPress function to update user metadata in the usermeta table.
 */
function bp_core_record_activity() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	$activity = get_user_meta( $bp->loggedin_user->id, 'last_activity', true );

	if ( !is_numeric( $activity ) )
		$activity = strtotime( $activity );

	// Get current time
	$current_time = bp_core_current_time();

	if ( empty( $activity ) || strtotime( $current_time ) >= strtotime( '+5 minutes', $activity ) )
		update_user_meta( $bp->loggedin_user->id, 'last_activity', $current_time );
}
add_action( 'wp_head', 'bp_core_record_activity' );

/**
 * Formats last activity based on time since date given.
 *
 * @package BuddyPress Core
 * @param last_activity_date The date of last activity.
 * @param $before The text to prepend to the activity time since figure.
 * @param $after The text to append to the activity time since figure.
 * @uses bp_core_time_since() This function will return an English representation of the time elapsed.
 */
function bp_core_get_last_activity( $last_activity_date, $string ) {
	if ( !$last_activity_date || empty( $last_activity_date ) )
		$last_active = __( 'not recently active', 'buddypress' );
	else
		$last_active = sprintf( $string, bp_core_time_since( $last_activity_date ) );

	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

function bp_core_number_format( $number, $decimals = false ) {
	// Check we actually have a number first.
	if ( empty( $number ) )
		return $number;

	return apply_filters( 'bp_core_number_format', number_format( $number, $decimals ), $number, $decimals );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress user data for the current logged in user.
 * @return array of post ids.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $bp, $wpdb;

	if ( empty( $user_id ) )
		$user_id = $bp->displayed_user->id;

	return apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
}

/**
 * Get the path of of the current site.
 *
 * @package BuddyPress Core
 *
 * @global $bp $bp
 * @global object $current_site
 * @return string
 */
function bp_core_get_site_path() {
	global $bp, $current_site;

	if ( is_multisite() )
		$site_path = $current_site->path;
	else {
		$site_path = (array) explode( '/', site_url() );

		if ( count( $site_path ) < 2 )
			$site_path = '/';
		else {
			// Unset the first three segments (http(s)://domain.com part)
			unset( $site_path[0] );
			unset( $site_path[1] );
			unset( $site_path[2] );

			if ( !count( $site_path ) )
				$site_path = '/';
			else
				$site_path = '/' . implode( '/', $site_path ) . '/';
		}
	}

	return apply_filters( 'bp_core_get_site_path', $site_path );
}

/**
 * Performs a status safe wp_redirect() that is compatible with bp_catch_uri()
 *
 * @package BuddyPress Core
 * @global $bp_no_status_set Makes sure that there are no conflicts with status_header() called in bp_core_do_catch_uri()
 * @uses get_themes()
 * @return An array containing all of the themes.
 */
function bp_core_redirect( $location, $status = 302 ) {
	global $bp_no_status_set;

	// Make sure we don't call status_header() in bp_core_do_catch_uri()
	// as this conflicts with wp_redirect()
	$bp_no_status_set = true;

	wp_redirect( $location, $status );
	die;
}

/**
 * Returns the referrer URL without the http(s)://
 *
 * @package BuddyPress Core
 * @return The referrer URL
 */
function bp_core_referrer() {
	$referer = explode( '/', wp_get_referer() );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

/**
 * Adds illegal names to WP so that root components will not conflict with
 * blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 */
function bp_core_add_illegal_names() {
	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}

/**
 * Allows a user to completely remove their account from the system
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses is_super_admin() Checks to see if the user is a site administrator.
 * @uses wpmu_delete_user() Deletes a user from the system on multisite installs.
 * @uses wp_delete_user() Deletes a user from the system on singlesite installs.
 * @uses get_site_option Checks if account deletion is allowed
 */
function bp_core_delete_account( $user_id = false ) {
	global $bp, $wp_version;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Make sure account deletion is not disabled
	if ( !empty( $bp->site_options['bp-disable-account-deletion'] ) && !$bp->loggedin_user->is_super_admin )
		return false;

	// Site admins cannot be deleted
	if ( is_super_admin( bp_core_get_username( $user_id ) ) )
		return false;

	// Specifically handle multi-site environment
	if ( is_multisite() ) {
		if ( $wp_version >= '3.0' )
			require_once( ABSPATH . '/wp-admin/includes/ms.php' );
		else
			require_once( ABSPATH . '/wp-admin/includes/mu.php' );

		require_once( ABSPATH . '/wp-admin/includes/user.php' );

		return wpmu_delete_user( $user_id );

	// Single site user deletion
	} else {
		require_once( ABSPATH . '/wp-admin/includes/user.php' );
		return wp_delete_user( $user_id );
	}
}

/**
 * A javascript free implementation of the search functions in BuddyPress
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {
	global $bp;

	if ( BP_SEARCH_SLUG != $bp->current_component )
		return;

	if ( empty( $_POST['search-terms'] ) ) {
		bp_core_redirect( $bp->root_domain );
		return;
	}

	$search_terms = stripslashes( $_POST['search-terms'] );
	$search_which = !empty( $_POST['search-which'] ) ? $_POST['search-which'] : '';
	$query_string = '/?s=';

	if ( empty( $slug ) ) {
		switch ( $search_which ) {
			case 'blogs':
				$slug = bp_is_active( 'blogs' )  ? $bp->blogs->slug  : '';
				break;

			case 'forums':
				$slug = bp_is_active( 'forums' ) ? $bp->forums->slug : '';
				$query_string = '/?fs=';
				break;

			case 'groups':
				$slug = bp_is_active( 'groups' ) ? $bp->groups->slug : '';
				break;

			case 'members':
			default:
				$slug = $bp->members->slug;
				break;
		}

		if ( empty( $slug ) ) {
			bp_core_redirect( $bp->root_domain );
			return;
		}
	}

	bp_core_redirect( apply_filters( 'bp_core_search_site', site_url( $slug . $query_string . urlencode( $search_terms ) ), $search_terms ) );
}
add_action( 'bp_init', 'bp_core_action_search_site', 7 );

/**
 * Localization safe ucfirst() support.
 *
 * @package BuddyPress Core
 */
function bp_core_ucfirst( $str ) {
	if ( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$fc = mb_strtoupper( mb_substr( $str, 0, 1 ) );
		return $fc.mb_substr( $str, 1 );
	} else {
		return ucfirst( $str );
	}
}

/**
 * Strips spaces from usernames that are created using add_user() and wp_insert_user()
 *
 * @package BuddyPress Core
 */
function bp_core_strip_username_spaces( $username ) {
	// Don't alter the user_login of existing users, as it causes user_nicename problems.
	// See http://trac.buddypress.org/ticket/2642
	if ( username_exists( $username ) && ( !defined( 'BP_ENABLE_USER_COMPATIBILITY_MODE' ) || !BP_ENABLE_USER_COMPATIBILITY_MODE ) )
		return $username;

	return str_replace( ' ', '-', $username );
}
add_action( 'pre_user_login', 'bp_core_strip_username_spaces' );

/**
 * REQUIRES WP-SUPER-CACHE
 *
 * When wp-super-cache is installed this function will clear cached pages
 * so that success/error messages are not cached, or time sensitive content.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_cache() {
	global $cache_path, $cache_filename;

	if ( function_exists( 'prune_super_cache' ) ) {
		do_action( 'bp_core_clear_cache' );

		return prune_super_cache( $cache_path, true );
	}
}

/**
 * Prints the generation time in the footer of the site.
 *
 * @package BuddyPress Core
 */
function bp_core_print_generation_time() {
?>

<!-- Generated in <?php timer_stop(1); ?> seconds. (<?php echo get_num_queries(); ?> q) -->

	<?php
}
add_action( 'wp_footer', 'bp_core_print_generation_time' );

/**
 * A better version of add_admin_menu_page() that allows positioning of menus.
 *
 * @package BuddyPress Core
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	global $menu, $admin_page_hooks, $_registered_pages;

	$defaults = array(
		'page_title'   => '',
		'menu_title'   => '',
		'capability'   => 'manage_options',
		'file'         => false,
		'function'     => false,
		'icon_url'     => false,
		'position'     => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$file = plugin_basename( $file );

	$admin_page_hooks[$file] = sanitize_title( $menu_title );

	$hookname = get_plugin_page_hookname( $file, '' );
	if (!empty ( $function ) && !empty ( $hookname ))
		add_action( $hookname, $function );

	if ( empty($icon_url) )
		$icon_url = 'images/generic.png';
	elseif ( is_ssl() && 0 === strpos($icon_url, 'http://') )
		$icon_url = 'https://' . substr($icon_url, 7);

	do {
		$position++;
	} while ( !empty( $menu[$position] ) );

	$menu[$position] = array ( $menu_title, $capability, $file, $page_title, 'menu-top ' . $hookname, $hookname, $icon_url );

	$_registered_pages[$hookname] = true;

	return $hookname;
}

/**
 * When a user logs in, check if they have been marked as a spammer. If yes then simply
 * redirect them to the home page and stop them from logging in.
 *
 * @package BuddyPress Core
 * @param $auth_obj The WP authorization object
 * @param $username The username of the user logging in.
 * @uses get_userdatabylogin() Get the userdata object for a user based on their username
 * @uses bp_core_redirect() Safe redirect to a page
 * @return $auth_obj If the user is not a spammer, return the authorization object
 */
function bp_core_boot_spammer( $auth_obj, $username ) {
	global $bp;

	if ( !$user = get_userdatabylogin( $username ) )
		return false;

	if ( ( is_multisite() && (int)$user->spam ) || 1 == (int)$user->user_status )
		return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been marked as a spammer.', 'buddypress' ) );
	else
		return $auth_obj;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 30, 2 );

/**
 * Deletes usermeta for the user when the user is deleted.
 *
 * @package BuddyPress Core
 * @param $user_id The user id for the user to delete usermeta for
 * @uses delete_user_meta() deletes a row from the wp_usermeta table based on meta_key
 */
function bp_core_remove_data( $user_id ) {
	// Remove usermeta
	delete_user_meta( $user_id, 'last_activity' );

	// Flush the cache to remove the user from all cached objects
	wp_cache_flush();
}
add_action( 'wpmu_delete_user',  'bp_core_remove_data' );
add_action( 'delete_user',       'bp_core_remove_data' );
add_action( 'bp_make_spam_user', 'bp_core_remove_data' );

/**
 * Load the buddypress translation file for current language
 *
 * @package BuddyPress Core
 */
function bp_core_load_buddypress_textdomain() {
	$locale        = apply_filters( 'buddypress_locale', get_locale() );
	$mofile        = sprintf( 'buddypress-%s.mo', $locale );
	$mofile_global = WP_LANG_DIR . '/' . $mofile;
	$mofile_local  = BP_PLUGIN_DIR . '/bp-languages/' . $mofile;

	if ( file_exists( $mofile_global ) )
		return load_textdomain( 'buddypress', $mofile_global );
	elseif ( file_exists( $mofile_local ) )
		return load_textdomain( 'buddypress', $mofile_local );
	else
		return false;
}
add_action ( 'bp_init', 'bp_core_load_buddypress_textdomain', 2 );

function bp_core_add_ajax_hook() {
	// Theme only, we already have the wp_ajax_ hook firing in wp-admin
	if ( !defined( 'WP_ADMIN' ) && isset( $_REQUEST['action'] ) )
		do_action( 'wp_ajax_' . $_REQUEST['action'] );
}
add_action( 'bp_init', 'bp_core_add_ajax_hook' );

/**
 * Add an extra update message to the update plugin notification.
 *
 * @package BuddyPress Core
 */
function bp_core_update_message() {
	echo '<p style="color: red; margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">' . __( 'IMPORTANT: <a href="http://codex.buddypress.org/getting-started/upgrading-from-10x/">Read this before attempting to update BuddyPress</a>', 'buddypress' ) . '</p>';
}
add_action( 'in_plugin_update_message-buddypress/bp-loader.php', 'bp_core_update_message' );

/**
 * When BuddyPress is activated we must make sure that mod_rewrite is enabled.
 * We must also make sure a BuddyPress compatible theme is enabled. This function
 * will show helpful messages to the administrator.
 *
 * @package BuddyPress Core
 */
function bp_core_activation_notice() {
	global $wp_rewrite, $current_blog, $bp;

	if ( isset( $_POST['permalink_structure'] ) )
		return false;

	if ( !is_super_admin() )
		return false;

	if ( !empty( $current_blog ) ) {
		if ( $current_blog->blog_id != BP_ROOT_BLOG )
			return false;
	}

	if ( empty( $wp_rewrite->permalink_structure ) ) { ?>

		<div id="message" class="updated fade">
			<p><?php printf( __( '<strong>BuddyPress is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddypress' ), admin_url( 'options-permalink.php' ) ) ?></p>
		</div><?php

	} else {
		// Get current theme info
		$ct = current_theme_info();

		// The best way to remove this notice is to add a "buddypress" tag to
		// your active theme's CSS header.
		if ( !defined( 'BP_SILENCE_THEME_NOTICE' ) && !in_array( 'buddypress', (array)$ct->tags ) ) { ?>

			<div id="message" class="updated fade">
				<p style="line-height: 150%"><?php printf( __( "<strong>BuddyPress is ready</strong>. You'll need to <a href='%s'>activate a BuddyPress compatible theme</a> to take advantage of all of the features. We've bundled a default theme, but you can always <a href='%s'>install some other compatible themes</a> or <a href='%s'>update your existing WordPress theme</a>.", 'buddypress' ), admin_url( 'themes.php' ), admin_url( 'theme-install.php?type=tag&s=buddypress&tab=search' ), admin_url( 'plugin-install.php?type=term&tab=search&s=%22bp-template-pack%22' ) ) ?></p>
			</div>

		<?php
		}
	}
}
add_action( 'admin_notices', 'bp_core_activation_notice' );

/**
 * When switching from single to multisite we need to copy blog options to
 * site options.
 *
 * @package BuddyPress Core
 */
function bp_core_activate_site_options( $keys = array() ) {
	global $bp;

	if ( !empty( $keys ) && is_array( $keys ) ) {
		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = get_blog_option( BP_ROOT_BLOG, $key, $default );

				if ( !update_site_option( $key, $bp->site_options[ $key ] ) )
					$errors = true;
			}
		}

		if ( empty( $errors ) )
			return true;
	}

	return false;
}

/**
 * BuddyPress uses site options to store configuration settings. Many of these settings are needed
 * at run time. Instead of fetching them all and adding many initial queries to each page load, let's fetch
 * them all in one go.
 *
 * @package BuddyPress Core
 */
function bp_core_get_site_options() {
	global $bp, $wpdb;

	// These options come from the options table in WP single, and sitemeta in MS
	$site_options = apply_filters( 'bp_core_site_options', array(
		'bp-deactivated-components',
		'bp-blogs-first-install',
		'bp-disable-blog-forum-comments',
		'bp-xprofile-base-group-name',
		'bp-xprofile-fullname-field-name',
		'bp-disable-profile-sync',
		'bp-disable-avatar-uploads',
		'bp-disable-account-deletion',
		'bp-disable-forum-directory',
		'bp-disable-blogforum-comments',
		'bb-config-location',
		'hide-loggedout-adminbar',

		// Useful WordPress settings used often
		'tags_blog_id',
		'registration',
		'fileupload_maxk'
	) );

	// These options always come from the options table of BP_ROOT_BLOG
	$root_blog_options = apply_filters( 'bp_core_root_blog_options', array(
		'avatar_default'
	) );

	$meta_keys = "'" . implode( "','", (array)$site_options ) ."'";

	if ( is_multisite() )
		$site_meta = $wpdb->get_results( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ({$meta_keys}) AND site_id = {$wpdb->siteid}" );
	else
		$site_meta = $wpdb->get_results( "SELECT option_name AS name, option_value AS value FROM {$wpdb->options} WHERE option_name IN ({$meta_keys})" );

	$root_blog_meta_keys  = "'" . implode( "','", (array)$root_blog_options ) ."'";
	$root_blog_meta_table = $wpdb->get_blog_prefix( BP_ROOT_BLOG ) . 'options';
	$root_blog_meta       = $wpdb->get_results( $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM {$root_blog_meta_table} WHERE option_name IN ({$root_blog_meta_keys})" ) );

	$site_options = array();
	foreach( array( $site_meta, $root_blog_meta ) as $meta ) {
		if ( !empty( $meta ) ) {
			foreach( (array)$meta as $meta_item )
				$site_options[$meta_item->name] = $meta_item->value;
		}
	}
	return apply_filters( 'bp_core_get_site_options', $site_options );
}

/********************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

/**
 * Add's 'bp' to global group of network wide cachable objects
 *
 * @package BuddyPress Core
 */
function bp_core_add_global_group() {
	wp_cache_init();

	if ( function_exists( 'wp_cache_add_global_groups' ) )
		wp_cache_add_global_groups( array( 'bp' ) );
}
add_action( 'bp_loaded', 'bp_core_add_global_group' );

/**
 * Clears all cached objects for a user, or a user is part of.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
}

// List actions to clear super cached pages on, if super cache is installed
add_action( 'wp_login',              'bp_core_clear_cache' );
add_action( 'bp_core_render_notice', 'bp_core_clear_cache' );

?>