<?php

class BP_Friendship_Template {
	var $current_friendship = -1;
	var $friendship_count;
	var $friendships;
	var $friendship;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_friend_count;
	
	function bp_friendship_template() {
		global $action_variables, $current_action;
		global $loggedin_userid;
		global $current_userid;
		
		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		if ( $current_action == 'my-friends' && in_array( 'search', $action_variables) && $_POST['friend-search-box'] != '' ) {
			// Search results
			$this->friendships = BP_Friends_Friendship::search_friends( $_POST['friend-search-box'], $current_userid, $this->pag_num, $this->pag_page );
			$this->total_friend_count = (int)$this->friendships['count'];
			$this->friendships = $this->friendships['friendships'];
		} else if ( $current_action == 'requests' ) {
			// Friendship Requests
			$this->friendships = friends_get_friendships( $current_userid, false, $this->pag_num, $this->pag_page, true );
			$this->total_friend_count = (int)$this->friendships['count'];
			$this->friendships = $this->friendships['friendships'];
		} else if ( $current_action == 'friend-finder' ) {
			if ( $action_variables && $action_variables[0] == 'search' ) {
				$this->friendships = friends_search_users( $action_variables[1], false, $this->pag_num, $this->pag_page );
				$this->total_friend_count = (int)$this->friendships['count'];
				$this->friendships = $this->friendships['users'];
			} else {
				$this->friendships = null;
				$this->total_friend_count = 0;
			}
		} else {
			// All confirmed friendships
			$this->friendships = friends_get_friendships( $current_userid, false, $this->pag_num, $this->pag_page, false );
			$this->total_friend_count = (int)$this->friendships['count'];
			$this->friendships = $this->friendships['friendships'];
		}

		$this->friendship_count = count($this->friendships);
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_friend_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_friendships() {
		if ( $this->friendship_count )
			return true;
		
		return false;
	}
	
	function next_friendship() {
		$this->current_friendship++;
		$this->friendship = $this->friendships[$this->current_friendship];
		
		return $this->friendship;
	}
	
	function rewind_friendships() {
		$this->current_friendship = -1;
		if ( $this->friendship_count > 0 ) {
			$this->friendship = $this->friendships[0];
		}
	}
	
	function user_friendships() { 
		if ( $this->current_friendship + 1 < $this->friendship_count ) {
			return true;
		} elseif ( $this->current_friendship + 1 == $this->friendship_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_friendships();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_friendship() {
		global $friendship, $current_userid;

		$this->in_the_loop = true;
		$this->friendship = $this->next_friendship();

		if ( $this->current_friendship == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_friendships() {
	global $friends_template;
	
	return $friends_template->has_friendships();
}

	function bp_has_users() {
		bp_has_friendships();
	}

function bp_the_friendship() {
	global $friends_template;
	return $friends_template->the_friendship();
}
	function bp_the_user() {
		bp_the_friendship();
	}

function bp_user_friendships() {
	global $friends_template;
	return $friends_template->user_friendships();
}
	function bp_friends_user_users() {
		bp_friends_user_friendships();
	}

function bp_friend_avatar_thumb( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	echo $template->avatar;
}
	function bp_friends_user_avatar_thumb() {
		global $friends_template;
		bp_friend_avatar_thumb( $friends_template->friendship );
	}

function bp_friend_status( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	if ( $template->status )
		echo $template->status;
}
	function bp_user_status_message() {
		global $friends_template;
		bp_friend_status( $friends_template->friendship );
	}

function bp_friend_link( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;
	
	echo $template->user_link;
}
	function bp_user_url() {
		global $friends_template;
		bp_friend_link( $friends_template->friendship );
	}

function bp_friend_last_active( $time = false, $template = false ) {
	global $friends_template;
	
	if ( !$time )
		$time = $friends_template->friendship->friend->last_active;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;

	if ( $time == "0000-00-00 00:00:00" || $time == NULL ) {
		_e('not recently active');
	} else {
		echo __('active') . ' ' . bp_time_since( strtotime( $time ) ) . ' ' . __('ago');
	}
}
	function bp_user_last_active( $time = false ) {
		global $friends_template;
		bp_friend_last_active( $time, $friends_template->friendship );
	}

function bp_friend_last_profile_update( $template = false ) {
	global $friends_template;

	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo $template->profile_last_updated;
}
	function bp_user_last_profile_update() {
		global $friends_template;
		bp_friend_last_profile_update( $friends_template->friendship );
	}

function bp_friend_last_status_update( $template = false ) {
	global $friends_template;

	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo $template->profile_last_updated;
}
	function bp_user_last_status_update() {
		global $friends_template;
		bp_friend_last_status_update( $friends_template->friendship );
	}

function bp_friend_last_content_update( $template = false ) {
	global $friends_template;
	
	if ( !$template )
		$template = &$friends_template->friendship->friend;

	echo $template->content_last_updated;
}
	function bp_user_last_content_update() {
		global $friends_template;
		bp_friend_last_content_update( $friends_template->friendship );
	}

function bp_friend_time_since_requested() {
	global $friends_template;
	
	if ( $friends_template->friendship->friend->date_created != "0000-00-00 00:00:00" ) {
		echo __('requested') . ' ' . bp_time_since( strtotime( $friends_template->friendship->friend->date_created ) ) . ' ' . __('ago');
	}
}

function bp_friend_accept_request_link() {
	global $friends_template, $loggedin_domain, $bp_friends_slug;
	
	echo $loggedin_domain . $bp_friends_slug . '/requests/accept/' . $friends_template->friendship->friend->id;
}

function bp_friend_reject_request_link() {
	global $friends_template, $loggedin_domain, $bp_friends_slug;
	
	echo $loggedin_domain . $bp_friends_slug . '/requests/reject/' . $friends_template->friendship->friend->id;	
}

function bp_friend_pagination() {
	global $friends_template;
	echo $friends_template->pag_links;
}

function bp_friend_search_form() {
	global $current_domain, $current_userid, $bp_friends_image_base, $bp_friends_slug;
	global $friends_template;
	global $current_action, $action_variables;

	if ( $current_action == 'my-friends' || !$current_action ) {
		$action = $current_domain . $bp_friends_slug . '/my-friends/search/';
		$label = __('Search Friends');
		$type = 'friend';
	} else {
		$action = $current_domain . $bp_friends_slug . '/friend-finder/search/';
		$label = __('Find Friends');
		$type = 'finder';
		$value = $action_variables[1];
	}

	if ( !$friends_template->friendship_count && $current_action != 'friend-finder' ) {
		$disabled = ' disabled="disabled"';
	}
?>
	<form action="<?php echo $action ?>" id="friend-search-form" method="post">
		<label for="<?php echo $type ?>-search-box" id="<?php echo $type ?>-search-label"><?php echo $label ?> <img id="ajax-loader" src="<?php echo $bp_friends_image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></label>
		<input type="search" name="<?php echo $type ?>-search-box" id="<?php echo $type ?>-search-box" value="<?php echo $value ?>"<?php echo $disabled ?> />
		<?php if ( function_exists('wp_nonce_field') )
			wp_nonce_field( $type . '_search' );
		?>
		<input type="hidden" name="initiator" id="initiator" value="<?php echo $current_userid ?>" />
	</form>
<?php
}

function bp_friend_all_friends_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/all-friends';
}

function bp_friend_latest_update_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/last-updated';	
}

function bp_friend_recent_activity_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/recently-active';	
}

function bp_friend_recent_status_link() {
	global $current_domain;
	echo $current_domain . 'my-friends/status-updates';	
}

function bp_add_friend_button( $potential_friend_id = false, $creds = false ) {
	global $loggedin_userid, $current_userid;
	global $loggedin_domain, $bp_friends_slug;
	
	if ( !$potential_friend_id )
		$potential_friend_id = $current_userid;
	
	if ( $loggedin_userid == $potential_friend_id )
		return false;
	
	if ( $creds ) {
		$loggedin_userid = $creds['loggedin_userid'];
		$loggedin_domain = $creds['loggedin_domain'];
	}
	
	$friend_status = BP_Friends_Friendship::check_is_friend( $loggedin_userid, $potential_friend_id );
	
	echo '<div class="friendship-button" id="friendship-button-' . $potential_friend_id . '">';
	if ( $friend_status == 'pending' ) {
		_e('Friendship Requested');
	} else if ( $friend_status == 'is_friend') {
		echo '<a href="' . $loggedin_domain . $bp_friends_slug . '/remove-friend/' . $potential_friend_id . '" title="' . __('Cancel Friendship') . '" id="friend-' . $potential_friend_id . '" rel="remove" class="remove">' . __('Cancel Friendship') . '</a>';
	} else {
		echo '<a href="' . $loggedin_domain . $bp_friends_slug . '/add-friend/' . $potential_friend_id . '" title="' . __('Add Friend') . '" id="friend-' . $potential_friend_id . '" rel="add">' . __('Add Friend') . '</a>';
	}
	echo '</div>';
	
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('addremove_friend');
}

?>
