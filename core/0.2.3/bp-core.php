<?php

define( 'PROTOCOL', 'http://' );
define( 'BP_CORE_VERSION', '0.2.3' );

require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-catchuri.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-classes.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-cssjs.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-thirdlevel.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-settingstab.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-avatars.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-templatetags.php' );

if ( !get_site_option('bp_disable_blog_tab') ) {
	include_once(ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-blogtab.php');
}

if ( isset($_POST['submit']) && $_POST['save_admin_settings'] ) {
	save_admin_settings();
}

/**
 * bp_core_setup_globals()
 *
 * Sets up default global BuddyPress configuration settings and stores
 * them in a $bp variable.
 *
 * @package BuddyPress
 * @uses $current_user A WordPress global containing current user information
 * @uses $current_component Which is set up in /bp-core/bp-core-catch-uri.php
 * @uses $current_action Which is set up in /bp-core/bp-core-catch-uri.php
 * @uses $action_variables Which is set up in /bp-core/bp-core-catch-uri.php
 * @uses bp_core_get_loggedin_domain() Returns the domain for the logged in user
 * @uses bp_core_get_current_domain() Returns the domain for the current user being viewed
 * @uses bp_core_get_current_userid() Returns the user id for the current user being viewed
 * @uses bp_core_get_loggedin_userid() Returns the user id for the logged in user
 */
function bp_core_setup_globals() {
	global $bp;
	global $current_user, $current_component, $current_action;
	global $action_variables;
	
	$bp = array(
		/* The user ID of the user who is currently logged in. */
		'loggedin_userid' 	=> $current_user->ID,
		
		/* The domain for the user currently logged in. eg: http://andy.domain.com/ */
		'loggedin_domain' 	=> bp_core_get_loggedin_domain(),
		
		/* The domain for the user currently being viewed */
		'current_domain'  	=> bp_core_get_current_domain(),
		
		/* The user id of the user currently being viewed */
		'current_userid'  	=> bp_core_get_current_userid(),
		
		/* The component being used eg: http://andy.domain.com/ [profile] */
		'current_component' => $current_component, // type: string
		
		/* The current action for the component eg: http://andy.domain.com/profile/ [edit] */
		'current_action'	=> $current_action, // type: string
		
		/* The action variables for the current action eg: http://andy.domain.com/profile/edit/ [group] / [6] */
		'action_variables'	=> $action_variables, // type: array
		
		/* Sets up the array container for the component navigation rendered by bp_get_nav() */
		'bp_nav'		  	=> array(),
		
		/* Sets up the array container for the user navigation rendered by bp_get_user_nav() */
		'bp_users_nav'	  	=> array(),
		
		/* Sets up the array container for the component options navigation rendered by bp_get_options_nav() */
		'bp_options_nav'	=> array(),
		
		/* Sets up container used for the title of the current component option and rendered by bp_get_options_title() */
		'bp_options_title'	=> '',
		
		/* Sets up container used for the avatar of the current component being viewed. Rendered by bp_get_options_avatar() */
		'bp_options_avatar'	=> '',
		
		/* Sets up container for callback messages rendered by bp_render_notice() */
		'message'			=> '',
		
		/* Sets up container for callback message type rendered by bp_render_notice() */
		'message_type'		=> '' // error/success
	);
}
add_action( 'wp', 'bp_core_setup_globals', 1 );
add_action( 'admin_menu', 'bp_core_setup_globals' );

/**
 * bp_core_setup_nav()
 *
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 * $bp['bp_nav'] represents the main component navigation 
 * $bp['bp_users_nav'] represents the sub navigation when viewing a users
 * profile other than that of the current logged in user.
 * 
 * @package BuddyPress
 * @uses $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_is_blog() Checks to see current page is a blog page eg: /blog/ or /archives/2008/09/01/
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_core_setup_nav() {
	global $bp;
	
	/* Add "Blog" to the main component navigation */
	$bp['bp_nav'][1] = array(
		'id'	=> 'blog',
		'name'  => 'Blog', 
		'link'  => $bp['loggedin_domain'] . 'blog'
	);
	
	/* Add "Blog" to the sub nav for a current user */
	$bp['bp_users_nav'][1] = array(
		'id'	=> 'blog',
		'name'  => 'Blog', 
		'link'  => $bp['current_domain'] . 'blog'
	);
	
	/* This will be a check to see if profile or blog is set as the default component. */
	if ( $bp['current_component'] == '' ) {
		if ( function_exists('xprofile_setup_nav') ) {
			$bp['current_component'] = 'profile';
		} else {
			$bp['current_component'] = 'blog';
		}
	/* If we are on a blog specific page, always set the current component to Blog */
	} else if ( bp_is_blog() ) {
		$bp['current_component'] = 'blog';
	}
	
	/* Set up the component options navigation for Blog */
	if ( $bp['current_component'] == 'blog' ) {
		if ( bp_is_home() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp['bp_options_title'] = __('My Blog'); 
				$bp['bp_options_nav']['blog'] = array(
					''   => array(
						'name' => __('Public'),
						'link' => $bp['loggedin_domain'] . 'blog/' ),
					'admin'	   => array( 
						'name' => __('Blog Admin'),
						'link' => $bp['loggedin_domain'] . 'wp-admin/' )
				);
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp['bp_options_avatar'] = core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false ); 
		}
	}
}
add_action( 'wp', 'bp_core_setup_nav', 2 );

/**
 * bp_core_get_loggedin_domain()
 *
 * Returns the domain for the user that is currently logged in.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress
 * @uses $current_user WordPress global variable containing current logged in user information
 * @uses bp_is_blog() Checks to see current page is a blog page eg: /blog/ or /archives/2008/09/01/
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_core_get_loggedin_domain() {
	global $current_user;
	
	if ( VHOST == 'yes' ) {
		$loggedin_domain = PROTOCOL . get_usermeta( $current_user->ID, 'source_domain' ) . '/';
	} else {
		$loggedin_domain = PROTOCOL . get_usermeta( $current_user->ID, 'source_domain' ) . '/' . get_usermeta( $current_user->ID, 'user_login' ) . '/';
	}

	return $loggedin_domain;
}

/**
 * bp_core_get_current_domain()
 *
 * Returns the domain for the user that is currently being viewed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress
 * @uses $current_blog WordPress global variable containing information for the current blog being viewed.
 * @uses get_bloginfo() WordPress function to return the value of a blog setting based on param passed
 */
function bp_core_get_current_domain() {
	global $current_blog;
	
	if ( VHOST == 'yes' ) {
		$current_domain = PROTOCOL . $current_blog->domain . '/';
	} else {
		$current_domain = get_bloginfo('wpurl') . '/';
	}
	
	return $current_domain;
}

function bp_core_get_current_userid() {
	$siteuser = bp_core_get_primary_username();
	$current_userid = bp_core_get_userid($siteuser);
	
	return $current_userid;
}

function bp_core_get_primary_username() {
	global $current_blog;
	
	if ( VHOST == 'yes' ) {
		$siteuser = explode('.', $current_blog->domain);
		$siteuser = $siteuser[0];
	} else {
		$siteuser = str_replace('/', '', $current_blog->path);
	}
	
	return $siteuser;
}

function start_buffer() {
	ob_start();
	add_action( 'dashmenu', 'stop_buffer' );
} 
add_action( 'admin_menu', 'start_buffer' );

function stop_buffer() {
	$contents = ob_get_contents();
	ob_end_clean();
	buddypress_blog_switcher( $contents );
}

function buddypress_blog_switcher( $contents ) {
	global $current_user, $blog_id; // current blog
	
	// This code is duplicated from the MU core so it can
	// be modified for BuddyPress.
	
	$filter = preg_split( '/\<ul id=\"dashmenu\"\>[\S\s]/', $contents );
	echo $filter[0];
	
	$list = array();
	$options = array();

	$primary_blog = get_usermeta( $current_user->ID, 'primary_blog' );
	
	foreach ( $blogs = get_blogs_of_user( $current_user->ID ) as $blog ) {
		if ( !$blog->blogname )
			continue;

		// Use siteurl for this in case of mapping
		$parsed = parse_url( $blog->siteurl );
		$domain = $parsed['host'];
		
		if ( $blog->userblog_id == $primary_blog ) {
			$current = ' id="primary_blog"';
			$image   = ' style="background-image: url(' . get_option('home') . '/wp-content/mu-plugins/bp-core/images/member.png);
							  background-position: 2px 4px;
							  background-repeat: no-repeat;
							  padding-left: 22px;"';
		} else { 
			$current = ''; 
			$image   = ' style="background-image: url(' . get_option('home') . '/wp-content/mu-plugins/bp-core/images/blog.png);
							  background-position: 3px 3px;
							  background-repeat: no-repeat;
							  padding-left: 22px;"';; 
		}
			
		if ( VHOST == 'yes' ) {
			if ( $_SERVER['HTTP_HOST'] === $domain ) {
				$current  .= ' class="current"';
				$selected  = ' selected="selected"';
			} else {
				$current  .= '';
				$selected  = '';
			}			
		} else {
			$path = explode( '/', str_replace( '/wp-admin', '', $_SERVER['REQUEST_URI'] ) );

			if ( $path[1] == str_replace( '/', '', $blog->path ) ) {
				$current  .= ' class="current"';
				$selected  = ' selected="selected"';
			} else {
				$current  .= '';
				$selected  = '';
			}
		}

		$url = clean_url( $blog->siteurl ) . '/wp-admin/';
		$name = wp_specialchars( strip_tags( $blog->blogname ) );
		
		$list_item   = "<li><a$image href='$url'$current>$name</a></li>";
		$option_item = "<option value='$url'$selected>$name</option>";

		$list[]    = $list_item;
		$options[] = $option_item; // [sic] don't reorder dropdown based on current blog
	
	}
	ksort($list);
	ksort($options);

	$list = array_slice( $list, 0, 4 ); // First 4

	$select = "\n\t\t<select>\n\t\t\t" . join( "\n\t\t\t", $options ) . "\n\t\t</select>";

	echo "<ul id=\"dashmenu\">\n\t" . join( "\n\t", $list );

	if ( count($list) < count($options) ) :
?>
	<li id="all-my-blogs-tab" class="wp-no-js-hidden"><a href="#" class="blog-picker-toggle"><?php _e( 'All my blogs' ); ?></a></li>

	</ul>

	<form id="all-my-blogs" action="" method="get" style="display: none">
		<p>
			<?php printf( __( 'Choose a blog: %s' ), $select ); ?>

			<input type="submit" class="button" value="<?php _e( 'Go' ); ?>" />
			<a href="#" class="blog-picker-toggle"><?php _e( 'Cancel' ); ?></a>
		</p>
	</form>
<?php
	endif; // counts
}

function add_settings_tab() {
	add_submenu_page( 'wpmu-admin.php', "BuddyPress", "BuddyPress", 1, basename(__FILE__), "core_admin_settings" );
}
add_action( 'admin_menu', 'add_settings_tab' );


function core_admin_settings() {
	if ( get_site_option('bp_disable_blog_tab') ) {
		$blog_tab_checked = ' checked="checked"';
	}
	
	if ( get_site_option('bp_disable_design_tab') ) {
		$design_tab_checked = ' checked="checked"';		
	}
	
?>	
	<div class="wrap">
		
		<h2><?php _e("BuddyPress Settings") ?></h2>
		
		<form action="" method="post">
			<table class="form-table">
			<tbody>
			<tr valign="top">
			<th scope="row" valign="top">Tabs</th>
			<td>
				<input type="checkbox" value="1" name="disable_blog_tab"<?php echo $blog_tab_checked; ?> />
				<label for="disable_blog_tab"> Disable merging of 'Write', 'Manage' and 'Comments' into one 'Blog' tab.</label>
				<br />
				<input type="checkbox" value="1" name="disable_design_tab"<?php echo $design_tab_checked; ?> />
				<label for="disable_design_tab"> Disable 'Design' tab for all members except site administrators.</label>
			</td>
			</tr>
			</tbody>
			</table>

			<p class="submit">
				  <input name="submit" value="Save Changes" type="submit" />
			</p>
		
			<input type="hidden" name="save_admin_settings" value="1" />
		</form>
		
	</div>
<?php
}

function save_admin_settings() {
	if ( !isset($_POST['disable_blog_tab']) ) {
		$_POST['disable_blog_tab'] = 0;
	}
	else if ( !isset($_POST['disable_design_tab']) )
	{
		$_POST['disable_design_tab'] = 0;
	}

	// temp code for now, until full settings page is added
	add_site_option( 'bp_disable_blog_tab', $_POST['disable_blog_tab'] );
	add_site_option( 'bp_disable_design_tab', $_POST['disable_design_tab'] );
}

// Commenting out dashboard replacement for now, until more is implemented.

// /* Are we viewing the dashboard? */
// if ( strpos( $_SERVER['SCRIPT_NAME'],'/index.php') ) {
// 	add_action( 'admin_head', 'start_dash' );
// }

// function start_dash($dash_contents) {	
// 	ob_start();
// 	add_action('admin_footer', 'end_dash');
// }
// 
// function replace_dash($dash_contents) {
// 	$filter = preg_split( '/\<div class=\"wrap\"\>[\S\s]*\<div id=\"footer\"\>/', $dash_contents );
// 	$filter[0] .= '<div class="wrap">';
// 	$filter[1] .= '</div>';
// 	
// 	echo $filter[0];
// 	echo render_dash();
// 	echo '<div style="clear: both">&nbsp;<br clear="all" /></div></div><div id="footer">';
// 	echo $filter[1];
// }
// 
// function end_dash() {
// 	$dash_contents = ob_get_contents();
// 	ob_end_clean();
// 	replace_dash($dash_contents);
// }
// 
// function render_dash() {
// 	$dash .= '
// 		
// 		<h2>' . __("My Activity Feed") . '</h2>
// 		<p>' . __("This is where your personal activity feed will go.") . '</p>
// 		<p>&nbsp;</p><p>&nbsp;</p>
// 	';
// 	
// 	if ( is_site_admin() ) {	
// 		$dash .= '
// 			
// 			<h4>Admin Options</h4>
// 			<ul>
// 				<li><a href="wpmu-blogs.php">' . __("Manage Site Members") . '</a></li>
// 				<li><a href="wpmu-options.php">' . __("Manage Site Options") . '</a></li>
// 		';
// 		
// 	}
// 	return $dash;	
// }

function bp_core_get_userid( $username ) {
	global $wpdb;
	$sql = $wpdb->prepare( "SELECT ID FROM " . $wpdb->base_prefix . "users WHERE user_login = %s", $username );
	return $wpdb->get_var($sql);
}

function bp_core_get_username( $uid ) {
	global $userdata;
	
	if ( $uid == $userdata->ID )
		return 'You';
	
	$ud = get_userdata($uid);
	return $ud->user_login;	
}

function bp_core_get_blogdetails( $domain ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->site WHERE domain = %s", $domain) );
}

function bp_core_get_userurl( $uid ) {
	global $userdata;
	
	$ud = get_userdata($uid);
	
	if ( VHOST == 'no' )
		$ud->path = $ud->user_login;
	else
		$ud->path = null;
		
	$url = PROTOCOL . $ud->source_domain . '/' . $ud->path;
	
	if ( $url == PROTOCOL . '/' )
		return false;
	
	return $url;
}

function bp_core_get_user_email( $uid ) {
	$ud = get_userdata($uid);
	return $ud->user_email;
}

function bp_core_get_userlink( $uid, $no_anchor = false, $just_link = false, $no_you = false ) {
	global $userdata;
	
	$ud = get_userdata($uid);

	if ( function_exists('bp_user_fullname') )
		$display_name = bp_user_fullname($uid, false);
	else
		$display_name = $ud->display_name;
	
	if ( $uid == $userdata->ID && !$no_you )
		$display_name = 'You';

	if ( $no_anchor )
		return $display_name;
		
	if ( VHOST == 'no' )
		$ud->path = $ud->user_login;
	else
		$ud->path = null;
	
	if ( $just_link )
		return PROTOCOL . $ud->source_domain . '/' . $ud->path;

	return '<a href="' . PROTOCOL . $ud->source_domain . '/' . $ud->path . '">' . $display_name . '</a>';	
}

function bp_core_clean( $dirty ) {
	if ( get_magic_quotes_gpc() ) {
		$clean = mysql_real_escape_string( stripslashes( $dirty ) );
	} else {
		$clean = mysql_real_escape_string( $dirty );
	}
	
	return $clean;
}

function bp_core_truncate( $text, $numb ) {
	$text = html_entity_decode( $text, ENT_QUOTES );
	
	if ( strlen($text) > $numb ) {
		$text = substr( $text, 0, $numb );
		$text = substr( $text, 0, strrpos( $text, " " ) );
		$etc  = " ..."; 
		$text = $text . $etc;
	}
	
	$text = htmlentities( $text, ENT_QUOTES ); 
	
	return $text;
}

function bp_core_validate( $num ) {	
	if( !is_numeric($num) ) {
		return false;
	}
	
	return true;
}

function bp_format_time( $time, $just_date = false ) {
	$date = date( "F j, Y ", $time );
	
	if ( !$just_date ) {
		$date .= __('at') . date( ' g:iA', $time );
	}
	
	return $date;
}

function bp_endkey( $array ) {
	end( $array );
	return key( $array );
}

function bp_get_homeurl() {
	return get_blogaddress_by_id( 0 );
}

function bp_create_excerpt( $text, $excerpt_length = 55 ) { // Fakes an excerpt if needed
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '[...]');
		$text = implode(' ', $words);
	}
	
	return stripslashes($text);
}

function bp_is_serialized( $data ) {
   if ( trim($data) == "" ) {
      return false;
   }

   if ( preg_match( "/^(i|s|a|o|d)(.*);/si", $data ) ) {
      return true;
   }

   return false;
}

function bp_upload_dir( $time = NULL, $blog_id ) {
	// copied from wordpress, need to be able to create a users
	// upload dir on activation, before 'upload_path' is
	// placed into options table.
	// Fix for this would be adding a hook for 'activate_footer'
	// in wp-activate.php

	$siteurl = get_option( 'siteurl' );
	$upload_path = 'wp-content/blogs.dir/' . $blog_id . '/files';
	if ( trim($upload_path) === '' )
		$upload_path = 'wp-content/uploads';
	$dir = $upload_path;
	
	// $dir is absolute, $path is (maybe) relative to ABSPATH
	$dir = path_join( ABSPATH, $upload_path );
	$path = str_replace( ABSPATH, '', trim( $upload_path ) );

	if ( !$url = get_option( 'upload_url_path' ) )
		$url = trailingslashit( $siteurl ) . $path;

	if ( defined('UPLOADS') ) {
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	$subdir = '';
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Generate the yearly and monthly dirs
		if ( !$time )
			$time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );
		$subdir = "/$y/$m";
	}

	$dir .= $subdir;
	$url .= $subdir;
	
	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $dir ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $dir );
		return array( 'error' => $message );
	}

	$uploads = array( 'path' => $dir, 'url' => $url, 'subdir' => $subdir, 'error' => false );
	return apply_filters( 'upload_dir', $uploads );
}

function bp_get_page_id($page_title, $output = object) {
	global $wpdb;
	
	$sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'page'", $page_title);
	$page = $wpdb->get_var($sql);
	
	if ( $page )
		return $page;

	return null;
}

function bp_is_blog() {
	global $bp, $wp_query, $cached_page_id;
	
	$blog_page_id = bp_get_page_id('Blog');
	if ( is_tag() || is_category() || is_day() || is_month() || is_year() || is_paged() || is_single() )
		return true;
	if ( isset($cached_page_id) && ($blog_page_id == $cached_page_id ) )
		return true;
	if ( is_page('Blog') )
		return true;
	if ( $bp['current_component'] == 'blog' )
		return true;
		
	return false;
}

function bp_render_notice( ) {
	global $bp;

	if ( $bp['message'] != '' ) {
		$type = ( $bp['message_type'] == 'success' ) ? 'updated' : 'error';
	?>
		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo $bp['message']; ?></p>
		</div>
	<?php 
	}
}

function bp_time_since( $older_date, $newer_date = false ) {
	// array of time period chunks
	$chunks = array(
	array( 60 * 60 * 24 * 365 , 'year' ),
	array( 60 * 60 * 24 * 30 , 'month' ),
	array( 60 * 60 * 24 * 7, 'week' ),
	array( 60 * 60 * 24 , 'day' ),
	array( 60 * 60 , 'hour' ),
	array( 60 , 'minute' ),
	);

	// $newer_date will equal false if we want to know the time elapsed between a date and the current time
	// $newer_date will have a value if we want to work out time elapsed between two known dates
	$newer_date = ( $newer_date == false ) ? ( time() + ( 60*60*0 ) ) : $newer_date;

	// difference in seconds
	$since = $newer_date - $older_date;

	// we only want to output two chunks of time here, eg:
	// x years, xx months
	// x days, xx hours
	// so there's only two bits of calculation below:

	// step one: the first chunk
	for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];

		// finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	// set output var
	$output = ( $count == 1 ) ? '1 '. $name : "$count {$name}s";

	// step two: the second chunk
	if ( $i + 1 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
	
		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			// add to output var
			$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
		}
	}

	return $output;
}

function bp_core_record_activity() {
	global $userdata;
	
	// Updated last site activity for this user.
	update_usermeta( $userdata->ID, 'last_activity', time() ); 
}
add_action( 'login_head', 'bp_core_record_activity' );


?>