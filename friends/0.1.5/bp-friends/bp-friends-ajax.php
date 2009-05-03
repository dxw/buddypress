<?php

function friends_ajax_friends_search() {
	global $bp;

	check_ajax_referer('friend_search');

	if ( !$bp ) {
		bp_core_setup_globals();
		friends_setup_globals();
		
		if ( function_exists('xprofile_setup_globals') )
			xprofile_setup_globals();
	}
	
	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_friend_count = 0;

	if ( $_POST['friend-search-box'] == "" ) {
		$friendships = friends_get_friendships( $bp['current_userid'], false, $pag_num, $pag_page, false );
	} else {
		$friendships = BP_Friends_Friendship::search_friends( $_POST['friend-search-box'], $bp['current_userid'], $pag_num, $pag_page );
	}
	
	$total_friend_count = (int)$friendships['count'];

	if ( $total_friend_count ) {
		$pag_links = paginate_links( array(
			'base' => $bp['current_domain'] . $bp['friends']['slug'] . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_friend_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $friendships['friendships'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($friendships['friendships']); $i++ ) {
			$friend = $friendships['friendships'][$i]->friend;
			?>
			<li>
				<?php echo $friend->avatar_thumb ?>
				<h4><?php echo $friend->user_link ?></h4>
				<?php if ( $friend->last_active ) { ?>
					<span class="activity"><?php echo $friend->last_active ?></span>
				<?php } ?>
			</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['friends']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No friends matched your search.", 'buddypress');
	}
}
add_action( 'wp_ajax_friends_search', 'friends_ajax_friends_search' );

function friends_ajax_finder_search() {
	global $bp;
	
	check_ajax_referer('finder_search');

	if ( !$bp ) {
		bp_core_setup_globals();
		friends_setup_globals();
		
		if ( function_exists('xprofile_setup_globals') )
			xprofile_setup_globals();
	}
		
	$pag_page = isset( $_POST['fpage'] ) ? intval( $_POST['fpage'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 5;
	$total_user_count = 0;

	if ( $_POST['finder-search-box'] == "" ) {
		echo "-1[[SPLIT]]" . __("Please enter something to search for.", 'buddypress');
		return;
	}
	
	$users = friends_search_users( $_POST['finder-search-box'], $bp['loggedin_userid'], $pag_num, $pag_page );

	$total_user_count = (int)$users['count'];

	if ( $total_user_count ) {
		$pag_links = paginate_links( array(
			'base' => $bp['current_domain'] . $bp['friends']['slug'] . add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($total_user_count / $pag_num),
			'current' => $pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	if ( $users['users'] ) {
		echo '0[[SPLIT]]'; // return valid result.
	
		for ( $i = 0; $i < count($users['users']); $i++ ) {
			$user = $users['users'][$i];
			?>
				<li>
					<?php echo $user->avatar_thumb ?>
					<h4><?php echo $user->user_link ?></h4>
					<?php if ( $user->last_active ) { ?>
						<span class="activity"><?php echo $user->last_active ?></span>
					<?php } ?>
					<?php bp_add_friend_button( $user->id ) ?>
				</li>
			<?php	
		}
		echo '[[SPLIT]]' . $pag_links;
	} else {
		$result['message'] = '<img src="' . $bp['friends']['image_base'] . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[SPLIT]]" . __("No site users matched your search.", 'buddypress');
	}
}
add_action( 'wp_ajax_finder_search', 'friends_ajax_finder_search' );


function friends_ajax_addremove_friend() {
	global $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		friends_setup_globals();
	}

	if ( BP_Friends_Friendship::check_is_friend( $bp['loggedin_userid'], $_POST['fid'] ) == 'is_friend' ) {
		if ( !friends_remove_friend( $bp['loggedin_userid'], $bp['current_userid'] ) ) {
			echo __("Friendship could not be canceled.", 'buddypress');
		} else {
			friends_update_friend_totals( $bp['loggedin_userid'], $bp['current_userid'], 'remove' );
			echo __('Add Friend', 'buddypress');
		}
	} else if ( BP_Friends_Friendship::check_is_friend( $bp['loggedin_userid'], $_POST['fid'] ) == 'not_friends' ) {
		if ( !friends_add_friend( $bp['loggedin_userid'], $_POST['fid'] ) ) {
			echo __("Friendship could not be requested.", 'buddypress');
		} else {
			echo __('Friendship Requested', 'buddypress');
		}
	} else {
		echo __('Request Pending', 'buddypress');
	}
	
	return false;
}
add_action( 'wp_ajax_addremove_friend', 'friends_ajax_addremove_friend' );

?>