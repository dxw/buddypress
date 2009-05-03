<?php
/**
 * bp_get_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp['bp_nav'] global to render out the navigation within a BuddyPress install.
 * Each component adds to this navigation array within its own [component_name]_setup_nav() function.
 * 
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Friends] ...
 *
 * The function will also analyze the current component the user is in, to determine whether
 * or not to highlight a particular nav item.
 *
 * It will also compare the current user to the logged in user, if a user profile is being viewed.
 * This allows the "Friends" item to be highlighted if the users are friends. This is only if the friends
 * component is installed.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses friends_check_friendship() Checks to see if the logged in user is a friend of the currently viewed user.
 */
function bp_get_nav() {
	global $bp, $current_blog;
	
	/* Sort the nav by key as the array has been put together in different locations */
	$bp['bp_nav'] = bp_core_sort_nav_items( $bp['bp_nav'] );

	/* Loop through each navigation item */
	foreach( (array) $bp['bp_nav'] as $nav_item ) {
		/* If the current component matches the nav item id, then add a highlight CSS class. */
		if ( $bp['current_component'] == $nav_item['css_id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		/* If we are viewing another person (current_userid does not equal loggedin_userid)
		   then check to see if the two users are friends. if they are, add a highlight CSS class
		   to the friends nav item if it exists. */
		if ( !bp_is_home() && $bp['current_userid'] ) {
			if ( function_exists('friends_install') ) {
				if ( friends_check_friendship( $bp['loggedin_userid'], $bp['current_userid'] ) && $nav_item['css_id'] == $bp['friends']['slug'] ) {
					$selected = ' class="current"';
				} else { 
					$selected = '';
				}
			}
		}
		
		/* echo out the final list item */
		echo '<li' . $selected . '><a id="my-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>';
	}
	
	/* Always add a log out list item to the end of the navigation */
	if ( function_exists( 'wp_logout_url' ) ) {
		echo '<li><a id="wp-logout" href="' .  wp_logout_url( site_url() . $_SERVER['REQUEST_URI'] ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';		
	} else {
		echo '<li><a id="wp-logout" href="' . site_url() . '/wp-login.php?action=logout&amp;redirect_to=' . site_url() . $_SERVER['REQUEST_URI'] . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
	}
}

/**
 * bp_get_options_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp['bp_options_nav'] global to render out the sub navigation for the current component.
 * Each component adds to its sub navigation array within its own [component_name]_setup_nav() function.
 * 
 * This sub navigation array is the secondary level navigation, so for profile it contains:
 *      [Public, Edit Profile, Change Avatar]
 *
 * The function will also analyze the current action for the current component to determine whether
 * or not to highlight a particular sub nav item.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_get_user_nav() Renders the navigation for a profile of a currently viewed user.
 */
function bp_get_options_nav() {
	global $bp, $is_single_group;

	/* Only render this navigation when the logged in user is looking at one of their own pages. */
	if ( bp_is_home() || $is_single_group ) {
		if ( count( $bp['bp_options_nav'][$bp['current_component']] ) < 1 )
			return false;
	
		/* Loop through each navigation item */
		foreach ( $bp['bp_options_nav'][$bp['current_component']] as $slug => $values ) {
			$title = $values['name'];
			$link = $values['link'];
			$css_id = $values['css_id'];
			
			/* If the current action or an action variable matches the nav item id, then add a highlight CSS class. */
			if ( $slug == $bp['current_action'] || in_array( $slug, $bp['action_variables'] ) ) {
				$selected = ' class="current"';
			} else {
				$selected = '';
			}
			
			/* echo out the final list item */
			echo '<li' . $selected . '><a id="' . $css_id . '" href="' . $link . '">' . $title . '</a></li>';		
		}
	} else {
		if ( !$bp['bp_users_nav'] )
			return false;

		bp_get_user_nav();
	}
}

/**
 * bp_get_user_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp['bp_users_nav'] global to render out the user navigation when viewing another user other than
 * yourself.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_user_nav() {
	global $bp;

	/* Sort the nav by key as the array has been put together in different locations */	
	$bp['bp_users_nav'] = bp_core_sort_nav_items( $bp['bp_users_nav'] );

	foreach ( $bp['bp_users_nav'] as $user_nav_item ) {	
		if ( $bp['current_component'] == $user_nav_item['css_id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a id="user-' . $user_nav_item['css_id'] . '" href="' . $user_nav_item['link'] . '">' . $user_nav_item['name'] . '</a></li>';
	}	
}

/**
 * bp_has_options_avatar()
 * TEMPLATE TAG
 *
 * Check to see if there is an options avatar. An options avatar is an avatar for something
 * like a group, or a friend. Basically an avatar that appears in the sub nav options bar.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_has_options_avatar() {
	global $bp;
	
	if ( $bp['bp_options_avatar'] == '' )
		return false;
	
	return true;
}

/**
 * bp_get_options_avatar()
 * TEMPLATE TAG
 *
 * Gets the avatar for the current sub nav (eg friends avatar or group avatar).
 * Does not check if there is one - so always use if ( bp_has_options_avatar() )
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_options_avatar() {
	global $bp;

	echo apply_filters( 'bp_get_options_avatar', $bp['bp_options_avatar'] );
}

function bp_get_options_title() {
	global $bp;
	
	if ( $bp['bp_options_title'] == '' )
		$bp['bp_options_title'] = __('Options', 'buddypress');
	
	echo apply_filters( 'bp_get_options_avatar', $bp['bp_options_title'] );
}

function bp_is_home() {
	global $bp, $current_blog, $doing_admin_bar;
	
	if ( is_user_logged_in() && $bp['loggedin_userid'] == $bp['current_userid'] )
		return true;
		
	return false;
}

function bp_comment_author_avatar() {
	global $comment;
	
	if ( function_exists('bp_core_get_avatar') ) {
		echo apply_filters( 'bp_comment_author_avatar', bp_core_get_avatar( $comment->user_id, 1 ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_post_author_avatar() {
	global $post;
	
	if ( function_exists('bp_core_get_avatar') ) {
		echo apply_filters( 'bp_post_author_avatar', bp_core_get_avatar( $post->post_author, 1 ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_loggedinuser_avatar( $width = false, $height = false ) {
	global $bp;
	
	if ( $width && $height )
		echo apply_filters( 'bp_loggedinuser_avatar', bp_core_get_avatar( $bp['loggedin_userid'], 2, $width, $height ) );
	else
		echo apply_filters( 'bp_loggedinuser_avatar', bp_core_get_avatar( $bp['loggedin_userid'], 2 ) );
}

function bp_loggedinuser_avatar_thumbnail( $width = false, $height = false ) {
	global $bp;
	
	if ( $width && $height )
		echo apply_filters( 'bp_get_options_avatar', bp_core_get_avatar( $bp['loggedin_userid'], 1, $width, $height ) );
	else
		echo apply_filters( 'bp_get_options_avatar', bp_core_get_avatar( $bp['loggedin_userid'], 1 ) );
}

function bp_fetch_user_fullname( $user_id = false, $echo = true ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['current_userid'];
	
	if ( function_exists('xprofile_install') ) {
		// First check the usermeta table for a easily fetchable value
		//$data = get_usermeta( $user_id, 'bp_display_name' );

		//if ( $data == '' ) {
			$data = bp_get_field_data( BP_XPROFILE_FULLNAME_FIELD_NAME, $user_id );

			if ( empty($data) ) {
				$ud = get_userdata($user_id);
				$data = $ud->display_name;
			} else {
				$data = ucfirst($data);
			}
			
			// store this in usermeta for less expensive fetching.
		//	update_usermeta( $user_id, 'bp_display_name', $data );
		//}
	} else {
		$ud = get_userdata($user_id);
		$data = $ud->display_name;
	}
	
	if ( $echo )
		echo apply_filters( 'bp_fetch_user_fullname', stripslashes( trim( $data ) ) );
	else
		return apply_filters( 'bp_fetch_user_fullname', stripslashes ( trim ( $data ) ) );
}

function bp_last_activity( $user_id = false, $echo = true ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['current_userid'];
	
	$last_activity = bp_core_get_last_activity( get_usermeta( $user_id, 'last_activity' ), __('active %s ago', 'buddypress') );

	if ( $echo )
		echo apply_filters( 'bp_last_activity', $last_activity );
	else
		return apply_filters( 'bp_last_activity', $last_activity );
}

function bp_the_avatar() {
	global $bp;
	echo apply_filters( 'bp_the_avatar', bp_core_get_avatar( $bp['current_userid'], 2 ) );
}

function bp_the_avatar_thumbnail() {
	global $bp;
	echo apply_filters( 'bp_the_avatar_thumbnail', bp_core_get_avatar( $bp['current_userid'], 1 ) );
}

function bp_user_link() {
	global $bp;
	
	echo apply_filters( 'bp_the_avatar_thumbnail', $bp['current_domain'] );
}

function bp_core_get_wp_profile() {
	
}

function bp_get_profile_header() {
	load_template( TEMPLATEPATH . '/profile/profile-header.php' );
}

function bp_exists( $component_name ) {
	if ( function_exists($component_name . '_install') )
		return true;
	
	return false;
}

function bp_format_time( $time, $just_date = false ) {
	$date = date( "F j, Y ", $time );
	
	if ( !$just_date ) {
		$date .= __('at', 'buddypress') . date( ' g:iA', $time );
	}
	
	return apply_filters( 'bp_format_time', $date );
}


function bp_word_or_name( $youtext, $nametext, $capitalize = true, $echo = true ) {
	global $bp;
	
	if ( $capitalize )
		$youtext = ucfirst($youtext);
	
	if ( $bp['current_userid'] == $bp['loggedin_userid'] ) {
		if ( $echo )
			echo apply_filters( 'bp_word_or_name', $youtext );
		else
			return apply_filters( 'bp_word_or_name', $youtext );
	} else {
		$nametext = sprintf($nametext, $bp['current_fullname']);
		if ( $echo )
			echo apply_filters( 'bp_word_or_name', $nametext );
		else
			return apply_filters( 'bp_word_or_name', $nametext );
	}
}

function bp_your_or_their( $capitalize = true, $echo = true ) {
	global $bp;
	
	if ( $capitalize )
		$yourtext = ucfirst($yourtext);
	
	if ( $bp['current_userid'] == $bp['loggedin_userid'] ) {
		if ( $echo )
			echo apply_filters( 'bp_your_or_their', $yourtext );
		else
			return apply_filters( 'bp_your_or_their', $yourtext );
	} else {
		if ( $echo )
			echo apply_filters( 'bp_your_or_their', $theirtext );
		else
			return apply_filters( 'bp_your_or_their', $theirtext );
	}
}

function bp_loggedinuser_link() {
	global $bp, $current_user;
	
	if ( $link = bp_core_get_userlink( $bp['loggedin_userid'] ) ) {
		echo apply_filters( 'bp_loggedinuser_link', $link );
	} else {
		$ud = get_userdata($current_user->ID);
		echo apply_filters( 'bp_loggedinuser_link', $ud->user_login );
	}
}

function bp_get_plugin_sidebar() {
	if ( file_exists(TEMPLATEPATH . '/plugin-sidebar.php') )
		load_template( TEMPLATEPATH . '/plugin-sidebar.php' );
}

function bp_is_blog_page() {
	global $bp, $is_member_page;
	
	$root_components = explode( ',', BP_CORE_ROOT_COMPONENTS );
	
	if ( $bp['current_component'] == 'news')
		return true;
		
	if ( !$is_member_page && !in_array( $bp['current_component'], $root_components ) )
		return true;
	
	return false;
}

function bp_page_title() {
	global $bp;
	
	if ( $bp['current_fullname'] != '' ) {
	 	echo apply_filters( 'bp_page_title', strip_tags( $bp['current_fullname'] . ' &raquo; ' . ucwords($bp['current_component']) . ' &raquo; ' . $bp['bp_options_nav'][$bp['current_component']][$bp['current_action']]['name'] ) );
	} else {
		echo apply_filters( 'bp_page_title', strip_tags( ucwords($bp['current_component']) . ' &raquo; ' . ucwords($bp['bp_options_title']) . ' &raquo; ' . ucwords($bp['current_action']) ) );
	}
}

function bp_styles() {
	do_action( 'bp_styles' );
	wp_print_styles();
}

function bp_is_page($page) {
	global $bp;

	if ( $bp['current_userid'] )
		return false;

	if ( $page == $bp['current_component'] || $page == 'home' && $bp['current_component'] == $bp['default_component'] )
		return true;
	
	return false;
}

function bp_has_custom_signup_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( 1, 'template') . '/register.php') )
		return true;
	
	return false;
}

function bp_signup_page( $echo = true ) {
	if ( bp_has_custom_signup_page() ) {
		if ( $echo )
			echo site_url(REGISTER_SLUG);
		else
			return site_url(REGISTER_SLUG);
	} else {
		if ( $echo )
			echo site_url('wp-signup.php');
		else
			return site_url('wp-signup.php');
	}
}

function bp_has_custom_activation_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( 1, 'template') . '/activate.php') )
		return true;
	
	return false;
}

function bp_activation_page( $echo = true ) {
	if ( bp_has_custom_activation_page() ) {
		if ( $echo )
			echo site_url(ACTIVATION_SLUG);
		else
			return site_url(ACTIVATION_SLUG);
	} else {
		if ( $echo )
			echo site_url('wp-activate.php');
		else
			return site_url('wp-activate.php');
	}
}

function bp_search_form_action() {
	global $bp;
	
	echo site_url('search');
}

function bp_search_form_type_select() {
	// Eventually this won't be needed and a page will be built to integrate all search results.
?>
	<select name="search-which" id="search-which" style="width: auto">
		
		<?php if ( function_exists( 'xprofile_install' ) ) { ?>
		<option value="members"><?php _e( 'Members', 'buddypress' ) ?></option>
		<?php } ?>
		
		<?php if ( function_exists( 'groups_install' ) ) { ?>
		<option value="groups"><?php _e( 'Groups', 'buddypress' ) ?></option>
		<?php } ?>
		
		<?php if ( function_exists( 'bp_blogs_install' ) ) { ?>
		<option value="blogs"><?php _e( 'Blogs', 'buddypress' ) ?></option>
		<?php } ?>
		
		<?php do_action( 'bp_search_form_type_select_options') ?>
	</select>
<?php
}

function bp_profile_wire_can_post() {
	global $bp;
	
	if ( bp_is_home() )
		return true;
	
	if ( function_exists('friends_install') ) {
		if ( friends_check_friendship( $bp['loggedin_userid'], $bp['current_userid'] ) )
			return true;
		else
			return false;
	} 
	
	return true;
}

/* Template functions for fetching globals, without querying the DB again
   also means we dont have to use the $bp variable in the template (looks messy) */

function bp_current_user_id() {
	global $bp;
	return apply_filters( 'bp_current_user_id', $bp['current_userid'] );
}

function bp_user_fullname() {
	global $bp;
	echo apply_filters( 'bp_user_fullname', $bp['current_fullname'] );
}



?>
