<?php

class BP_Activity_Template {
	var $current_activity = -1;
	var $activity_count;
	var $total_activity_count;
	var $activities;
	var $activity;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;

	var $full_name;

	function bp_activity_template( $page, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments, $show_hidden ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['acpage'] ) ? intval( $_REQUEST['acpage'] ) : $page;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		/* Check if blog/forum replies are disabled */
		$this->disable_blogforum_replies = get_site_option( 'bp-disable-blogforum-comments' );

		/* Get an array of the logged in user's favorite activities */
		$this->my_favs = maybe_unserialize( get_usermeta( $bp->loggedin_user->id, 'bp_favorite_activities' ) );

		if ( !empty( $include ) ) {
			/* Fetch specific activity items based on ID's */
			$this->activities = bp_activity_get_specific( array( 'activity_ids' => explode( ',', $include ), 'max' => $max, 'page' => $this->pag_page, 'per_page' => $this->pag_num, 'sort' => $sort, 'display_comments' => $display_comments ) );
		} else {
			$this->activities = bp_activity_get( array( 'display_comments' => $display_comments, 'max' => $max, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'sort' => $sort, 'search_terms' => $search_terms, 'filter' => $filter, 'show_hidden' => $show_hidden ) );
		}

		if ( !$max || $max >= (int)$this->activities['total'] )
			$this->total_activity_count = (int)$this->activities['total'];
		else
			$this->total_activity_count = (int)$max;

		$this->activities = $this->activities['activities'];

		if ( $max ) {
			if ( $max >= count($this->activities) )
				$this->activity_count = count($this->activities);
			else
				$this->activity_count = (int)$max;
		} else {
			$this->activity_count = count($this->activities);
		}

		$this->full_name = $bp->displayed_user->fullname;

		if ( (int) $this->total_activity_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( 'acpage', '%#%' ),
				'format' => '',
				'total' => ceil( (int)$this->total_activity_count / (int)$this->pag_num ),
				'current' => (int)$this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size' => 1
			));
		}
	}

	function has_activities() {
		if ( $this->activity_count )
			return true;

		return false;
	}

	function next_activity() {
		$this->current_activity++;
		$this->activity = $this->activities[$this->current_activity];

		return $this->activity;
	}

	function rewind_activities() {
		$this->current_activity = -1;
		if ( $this->activity_count > 0 ) {
			$this->activity = $this->activities[0];
		}
	}

	function user_activities() {
		if ( $this->current_activity + 1 < $this->activity_count ) {
			return true;
		} elseif ( $this->current_activity + 1 == $this->activity_count ) {
			do_action('activity_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_activities();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_activity() {
		global $activity;

		$this->in_the_loop = true;
		$this->activity = $this->next_activity();

		if ( is_array( $this->activity ) )
			$this->activity = (object) $this->activity;

		if ( $this->current_activity == 0 ) // loop has just started
			do_action('activity_loop_start');
	}
}

function bp_has_activities( $args = '' ) {
	global $bp, $activities_template;

	/* Note: any params used for filtering can be a single value, or multiple values comma separated. */

	$defaults = array(
		'display_comments' => false, // false for none, stream/threaded - show comments in the stream or threaded under items
		'include' => false, // pass an activity_id or string of ID's comma separated
		'sort' => 'DESC', // sort DESC or ASC
		'page' => 1, // which page to load
		'per_page' => 25, // number of items per page
		'max' => false, // max number to return
		'show_hidden' => false, // Show activity items that are hidden site-wide?

		/* Filtering */
		'user_id' => false, // user_id to filter on
		'object' => false, // object to filter on e.g. groups, profile, status, friends
		'action' => false, // action to filter on e.g. activity_update, new_forum_post, profile_updated
		'primary_id' => false, // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		'secondary_id' => false, // secondary object ID to filter on e.g. a post_id

		/* Searching */
		'search_terms' => false // specify terms to search on
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( ( 'personal' == $type || 'friends' == $type ) && !$user_id )
		$user_id = (int)$bp->displayed_user->id;

	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}

	/* Support for permalinks on single item pages: /groups/my-group/activity/124/ */
	if ( $bp->current_action == $bp->activity->slug )
		$include = $bp->action_variables[0];

	/* Support for basic filters in earlier BP versions. */
	if ( isset( $_GET['afilter'] ) )
		$filter = array( 'object' => $_GET['afilter'] );
	else
		$filter = array( 'user_id' => $user_id, 'object' => $object, 'action' => $action, 'primary_id' => $primary_id, 'secondary_id' => $secondary_id );

	$activities_template = new BP_Activity_Template( $page, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments, $show_hidden );

	return apply_filters( 'bp_has_activities', $activities_template->has_activities(), &$activities_template );
}

function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

function bp_activity_pagination_count() {
	global $bp, $activities_template;

	$from_num = bp_core_number_format( intval( ( $activities_template->pag_page - 1 ) * $activities_template->pag_num ) + 1 );
	$to_num = bp_core_number_format( ( $from_num + ( $activities_template->pag_num - 1 ) > $activities_template->total_activity_count ) ? $activities_template->total_activity_count : $from_num + ( $activities_template->pag_num - 1 ) );
	$total = bp_core_number_format( $activities_template->total_activity_count );

	echo sprintf( __( 'Viewing item %s to %s (of %s items)', 'buddypress' ), $from_num, $to_num, $total ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_activity_pagination_links() {
	echo bp_get_activity_pagination_links();
}
	function bp_get_activity_pagination_links() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_pagination_links', $activities_template->pag_links );
	}

function bp_activity_count() {
	echo bp_get_activity_count();
}
	function bp_get_activity_count() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_count', (int)$activities_template->activity_count );
	}

function bp_activity_per_page() {
	echo bp_get_activity_per_page();
}
	function bp_get_activity_per_page() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_per_page', (int)$activities_template->pag_num );
	}

function bp_activities_title() {
	global $bp_activity_title;

	echo bp_get_activities_title();
}
	function bp_get_activities_title() {
		global $bp_activity_title;

		return apply_filters( 'bp_get_activities_title', $bp_activity_title );
	}

function bp_activities_no_activity() {
	global $bp_activity_no_activity;

	echo bp_get_activities_no_activity();
}
	function bp_get_activities_no_activity() {
		global $bp_activity_no_activity;

		return apply_filters( 'bp_get_activities_no_activity', $bp_activity_no_activity );
	}

function bp_activity_id() {
	echo bp_get_activity_id();
}
	function bp_get_activity_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_id', $activities_template->activity->id );
	}

function bp_activity_item_id() {
	echo bp_get_activity_item_id();
}
	function bp_get_activity_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_item_id', $activities_template->activity->item_id );
	}

function bp_activity_secondary_item_id() {
	echo bp_get_activity_secondary_item_id();
}
	function bp_get_activity_secondary_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_secondary_item_id', $activities_template->activity->secondary_item_id );
	}

function bp_activity_date_recorded() {
	echo bp_get_activity_date_recorded();
}
	function bp_get_activity_date_recorded() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_date_recorded', $activities_template->activity->date_recorded );
	}

function bp_activity_object_name() {
	echo bp_get_activity_object_name();
}
	function bp_get_activity_object_name() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_object_name', $activities_template->activity->component );
	}

function bp_activity_type() {
	echo bp_get_activity_type();
}
	function bp_get_activity_type() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_type', $activities_template->activity->type );
	}
	function bp_activity_action_name() { echo bp_activity_type(); }
	function bp_get_activity_action_name() { return bp_get_activity_type(); }

function bp_activity_user_id() {
	echo bp_get_activity_user_id();
}
	function bp_get_activity_user_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_user_id', $activities_template->activity->user_id );
	}

function bp_activity_user_link() {
	echo bp_get_activity_user_link();
}
	function bp_get_activity_user_link() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_user_link', bp_core_get_user_domain( $activities_template->activity->user_id, $activities_template->activity->user_nicename, $activities_template->activity->user_login ) );
	}

function bp_activity_avatar( $args = '' ) {
	echo bp_get_activity_avatar( $args );
}
	function bp_get_activity_avatar( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'type' => 'thumb',
			'width' => 20,
			'height' => 20,
			'class' => 'avatar',
			'alt' => __( 'Avatar', 'buddypress' ),
			'email' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		$item_id = false;
		if ( (int)$activities_template->activity->user_id )
			$item_id = $activities_template->activity->user_id;
		else if ( $activities_template->activity->item_id )
			$item_id = $activities_template->activity->item_id;

		$object = 'user';
		if ( $bp->groups->id == $activities_template->activity->component && !(int) $activities_template->activity->user_id )
			$object = 'group';
		if ( $bp->blogs->id == $activities_template->activity->component && !(int) $activities_template->activity->user_id )
			$object = 'blog';

		$object = apply_filters( 'bp_get_activity_avatar_object_' . $activities_template->activity->component, $object );

		/* If this is a user object pass the users' email address for Gravatar so we don't have to refetch it. */
		if ( 'user' == $object && empty($email) )
			$email = $activities_template->activity->user_email;

		return apply_filters( 'bp_get_activity_avatar', bp_core_fetch_avatar( array( 'item_id' => $item_id, 'object' => $object, 'type' => $type, 'alt' => $alt, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $email ) ) );
	}

function bp_activity_action() {
	echo bp_get_activity_action();
}
	function bp_get_activity_action() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_action', bp_activity_content_filter( $activities_template->activity->action, $activities_template->activity->date_recorded ), $activities_template->activity->component, $activities_template->activity->type );
	}

function bp_activity_content_body() {
	echo bp_get_activity_content_body();
}
	function bp_get_activity_content_body() {
		global $activities_template;

		if ( empty( $activities_template->activity->action ) )
			$activities_template->activity->content = bp_activity_content_filter( $activities_template->activity->content, $activities_template->activity->date_recorded );

		return apply_filters( 'bp_get_activity_content_body', $activities_template->activity->content );
	}

function bp_activity_content() {
	echo bp_get_activity_content();
}
	function bp_get_activity_content() {
		global $activities_template;

		if ( empty( $activities_template->activity->action ) )
			$content = bp_activity_content_filter( $activities_template->activity->content, $activities_template->activity->date_recorded );
		else {
			$content = bp_activity_content_filter( $activities_template->activity->action, $activities_template->activity->date_recorded );
			$content .= $activities_template->activity->content;
		}

		return apply_filters( 'bp_get_activity_content', $content, $activities_template->activity->component, $activities_template->activity->type );
	}

function bp_activity_content_filter( $content, $date_recorded ) {
	global $activities_template, $bp;

	if ( !$content )
		return false;

	/* Split the content so we don't evaluate and replace text on content we don't want to */
	$content = explode( '%s', $content );

	/* Re-add the exploded %s */
	$content[0] .= '%s';

	/* Insert the time since */
	$content[0] = bp_activity_insert_time_since( $content[0], $date_recorded );

	/* Add the permalink */
	$meta = ' &middot; <a href="' . bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ) . '" class="view" title="' . __( 'View Thread / Permalink', 'buddypress' ) . '">' . __( 'View', 'buddypress' ) . '</a>';

	/* Add the delete link if the user has permission on this item */
	if ( ( $activities_template->activity->user_id == $bp->loggedin_user->id ) || $bp->is_item_admin || is_site_admin() )
		 $meta .= ' &middot; ' . bp_get_activity_delete_link();

	$content[1] = $meta . '</span>' . $content[1];
	$content_new = '';

	for ( $i = 0; $i < count($content); $i++ )
		$content_new .= $content[$i];

	return apply_filters( 'bp_activity_content_filter', $content_new );
}

function bp_activity_parent_content( $args = '' ) {
	echo bp_get_activity_parent_content($args);
}
	function bp_get_activity_parent_content( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'hide_user' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/* Get the ID of the parent activity content */
		if ( !$parent_id = $activities_template->activity->item_id )
			return false;

		/* Get the content of the parent by first checking to see if we already have it */
		$parent_activity = false;

		foreach( $activities_template->activities as $activity ) {
			if ( $parent_id == $activity->id ) {
				/* Need a copy not a reference, this was the only PHP4 compat way I could find. */
				$parent_activity = (array)$activity;
				$parent_activity = (object)$parent_activity;
			}
		}

		/* We didn't find it, so let's get it from the DB */
		if ( !$parent_activity ) {
			$parent_activity = bp_activity_get_specific( array( 'activity_ids' => $parent_id ) );
			$parent_activity = $parent_activity['activities'][0];
		}

		if ( !$parent_activity )
			return false;

		/* Remove the time since content */
		$parent_activity->content = str_replace( '<span class="time-since">%s</span>', '', $parent_activity->content );

		/* Remove images */
		$parent_activity->content = preg_replace( '/<img[^>]*>/Ui', '', $parent_activity->content );

		return apply_filters( 'bp_get_activity_parent_content', $parent_activity->content );
	}

function bp_activity_is_favorite() {
	echo bp_get_is_activity_favorite();
}
	function bp_get_activity_is_favorite() {
		global $bp, $activities_template;

 		return apply_filters( 'bp_get_activity_is_favorite', in_array( $activities_template->activity->id, (array)$activities_template->my_favs ) );
	}

function bp_activity_comments( $args = '' ) {
	echo bp_activity_get_comments( $args );
}
	function bp_activity_get_comments( $args = '' ) {
		global $activities_template, $bp;

		if ( !$activities_template->activity->children )
			return false;

		$comments_html = bp_activity_recurse_comments( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comments', $comments_html );
	}
		/* TODO: The HTML in this function is temporary and will be moved to the template in a future version. */
		function bp_activity_recurse_comments( $comment ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return false;

			$content .= '<ul>';
			foreach ( $comment->children as $comment ) {
				if ( !$comment->user_fullname )
					$comment->user_fullname = $comment->display_name;

				$content .= '<li id="acomment-' . $comment->id . '">';
				$content .= '<div class="acomment-avatar"><a href="' . bp_core_get_user_domain( $comment->user_id, $comment->user_nicename, $comment->user_login ) . '">' . bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'width' => 25, 'height' => 25, 'email' => $comment->user_email ) ) . '</a></div>';
				$content .= '<div class="acomment-meta"><a href="' . bp_core_get_user_domain( $comment->user_id, $comment->user_nicename, $comment->user_login ) . '">' . apply_filters( 'bp_get_member_name', $comment->user_fullname ) . '</a> &middot; ' . sprintf( __( '%s ago', 'buddypress' ), bp_core_time_since( strtotime( $comment->date_recorded ) ) );

				/* Reply link */
				if ( is_user_logged_in() )
					$content .= ' &middot; <a href="#acomment-' . $comment->id . '" class="acomment-reply" id="acomment-reply-' . $activities_template->activity->id . '">' . __( 'Reply', 'buddypress' ) . '</a>';

				/* Delete link */
				if ( is_site_admin() || $bp->loggedin_user->id == $comment->user_id )
					$content .= ' &middot; <a href="' . wp_nonce_url( $bp->activity->id . '/delete/?cid=' . $comment->id, 'bp_activity_delete_link' ) . '" class="delete acomment-delete">' . __( 'Delete', 'buddypress' ) . '</a>';

				$content .= '</div>';
				$content .= '<div class="acomment-content">' . apply_filters( 'bp_get_activity_content', $comment->content ) . '</div>';

				$content .= bp_activity_recurse_comments( $comment );
				$content .= '</li>';
			}
			$content .= '</ul>';

			return $content;
		}

function bp_activity_comment_count() {
	echo bp_activity_get_comment_count();
}
	function bp_activity_get_comment_count( $args = '' ) {
		global $activities_template, $bp;

		if ( !$activities_template->activity->children )
			return 0;

		$count = bp_activity_recurse_comment_count( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comment_count', (int)$count );
	}
		function bp_activity_recurse_comment_count( $comment, $count = 0 ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return $count;

			foreach ( $comment->children as $comment ) {
				$count++;
				$count = bp_activity_recurse_comment_count( $comment, $count );
			}

			return $count;
		}

function bp_activity_insert_time_since( $content, $date ) {
	if ( !$content || !$date )
		return false;

	// Make sure we don't have any URL encoding in links when trying to insert the time.
	$content = urldecode($content);

	return apply_filters( 'bp_activity_insert_time_since', @sprintf( $content, @sprintf( __( '&nbsp; %s ago', 'buddypress' ), bp_core_time_since( $date ) ) ) );
}

function bp_activity_permalink_id() {
	echo bp_get_activity_permalink_id();
}
	function bp_get_activity_permalink_id() {
		global $bp;

		return apply_filters( 'bp_get_activity_permalink_id', $bp->current_action );
	}

function bp_activity_thread_permalink() {
	echo bp_get_activity_thread_permalink();
}
	function bp_get_activity_thread_permalink() {
		global $bp, $activities_template;

		$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );

	 	return apply_filters( 'bp_get_activity_thread_permalink', $link );
	}

function bp_activity_css_class() {
	echo bp_get_activity_css_class();
}
	function bp_get_activity_css_class() {
		global $activities_template;

		$mini_activity_actions = apply_filters( 'bp_activity_mini_activity_types', array(
			'friendship_accepted',
			'friendship_created',
			'new_blog',
			'joined_group',
			'created_group',
			'new_member'
		) );

		$class = '';
		if ( in_array( $activities_template->activity->type, (array)$mini_activity_actions ) || empty( $activities_template->activity->content ) )
			$class = ' mini';

		if ( bp_activity_get_comment_count() && bp_activity_can_comment() )
			$class .= ' has-comments';

		return apply_filters( 'bp_get_activity_css_class', $activities_template->activity->component . ' ' . $activities_template->activity->type . $class );
	}

function bp_activity_delete_link() {
	echo bp_get_activity_delete_link();
}
	function bp_get_activity_delete_link() {
		global $activities_template, $bp;

		return apply_filters( 'bp_get_activity_delete_link', '<a href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->activity->slug . '/delete/' . $activities_template->activity->id, 'bp_activity_delete_link' ) . '" class="item-button delete-activity confirm">' . __( 'Delete', 'buddypress' ) . '</a>' );
	}

function bp_activity_latest_update( $user_id = false ) {
	echo bp_get_activity_latest_update( $user_id );
}
	function bp_get_activity_latest_update( $user_id = false ) {
		global $bp;

		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		if ( !$update = get_usermeta( $user_id, 'bp_latest_update' ) )
			return false;

		$latest_update = '&quot;' . trim( strip_tags( bp_create_excerpt( $update['content'], 40 ) ) ) . '&quot;';
		$latest_update .= ' &middot; <a href="' . $bp->root_domain . '/' . BP_ACTIVITY_SLUG . '/p/' . $update['id'] . '/"> ' . __( 'View', 'buddypress' ) . '</a>';

		return apply_filters( 'bp_get_activity_latest_update', $latest_update  );
	}

function bp_activity_filter_links( $args = false ) {
	echo bp_get_activity_filter_links( $args );
}
	function bp_get_activity_filter_links( $args = false ) {
		global $activities_template, $bp;

		$defaults = array(
			'style' => 'list'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/* Fetch the names of components that have activity recorded in the DB */
		$components = BP_Activity_Activity::get_recorded_components();

		if ( !$components )
			return false;

		foreach ( (array) $components as $component ) {
			/* Skip the activity comment filter */
			if ( 'activity' == $component )
				continue;

			if ( isset( $_GET['afilter'] ) && $component == $_GET['afilter'] )
				$selected = ' class="selected"';
			else
				unset($selected);

			$component = attribute_escape( $component );

			switch ( $style ) {
				case 'list':
					$tag = 'li';
					$before = '<li id="afilter-' . $component . '"' . $selected . '>';
					$after = '</li>';
				break;
				case 'paragraph':
					$tag = 'p';
					$before = '<p id="afilter-' . $component . '"' . $selected . '>';
					$after = '</p>';
				break;
				case 'span':
					$tag = 'span';
					$before = '<span id="afilter-' . $component . '"' . $selected . '>';
					$after = '</span>';
				break;
			}

			$link = add_query_arg( 'afilter', $component );
			$link = remove_query_arg( 'acpage' , $link );

			$link = apply_filters( 'bp_get_activity_filter_link_href', $link, $component );

			/* Make sure all core internal component names are translatable */
			$translatable_components = array( __( 'profile', 'buddypress'), __( 'friends', 'buddypress' ), __( 'groups', 'buddypress' ), __( 'status', 'buddypress' ), __( 'blogs', 'buddypress' ) );

			$component_links[] = $before . '<a href="' . attribute_escape( $link ) . '">' . ucwords( __( $component, 'buddypress' ) ) . '</a>' . $after;
		}

		$link = remove_query_arg( 'afilter' , $link );

		if ( isset( $_GET['afilter'] ) )
			$component_links[] = '<' . $tag . ' id="afilter-clear"><a href="' . attribute_escape( $link ) . '"">' . __( 'Clear Filter', 'buddypress' ) . '</a></' . $tag . '>';

 		return apply_filters( 'bp_get_activity_filter_links', implode( "\n", $component_links ) );
	}

function bp_activity_can_comment() {
	global $activities_template, $bp;

	if ( false === $activities_template->disable_blogforum_replies || (int)$activities_template->disable_blogforum_replies ) {
		if ( 'new_blog_post' == bp_get_activity_action_name() || 'new_blog_comment' == bp_get_activity_action_name() || 'new_forum_topic' == bp_get_activity_action_name() || 'new_forum_post' == bp_get_activity_action_name() )
			return false;
	}

	if ( 'activity_comment' == bp_get_activity_action_name() )
		return false;

	return true;
}

function bp_total_favorite_count_for_user( $user_id = false ) {
	echo bp_get_total_favorite_count_for_user( $user_id );
}
	function bp_get_total_favorite_count_for_user( $user_id = false ) {
		return apply_filters( 'bp_get_total_favorite_count_for_user', bp_activity_total_favorites_for_user( $user_id ) );
	}

function bp_total_mention_count_for_user( $user_id = false ) {
	echo bp_get_total_favorite_count_for_user( $user_id );
}
	function bp_get_total_mention_count_for_user( $user_id = false ) {
		return apply_filters( 'bp_get_total_mention_count_for_user', get_usermeta( $user_id, 'bp_new_mention_count' ) );
	}

function bp_send_public_message_link() {
	echo bp_get_send_public_message_link();
}
	function bp_get_send_public_message_link() {
		global $bp;

		return apply_filters( 'bp_get_send_public_message_link', $bp->loggedin_user->domain . $bp->activity->slug . '/?r=' . bp_core_get_username( $bp->displayed_user->user_id, $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login ) );
	}


/* RSS Feed Template Tags ***************************/

function bp_sitewide_activity_feed_link() {
	echo bp_get_sitewide_activity_feed_link();
}
	function bp_get_sitewide_activity_feed_link() {
		global $bp;

		return apply_filters( 'bp_get_sitewide_activity_feed_link', site_url( $bp->activity->slug . '/feed/' ) );
	}

function bp_member_activity_feed_link() {
	echo bp_get_member_activity_feed_link();
}
function bp_activities_member_rss_link() { echo bp_get_member_activity_feed_link(); }

	function bp_get_member_activity_feed_link() {
		global $bp;

		if ( ( $bp->current_component == $bp->profile->slug ) || 'just-me' == $bp->current_action )
			return apply_filters( 'bp_get_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/feed/' );
		else
			return apply_filters( 'bp_get_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/my-friends/feed/' );
	}
	function bp_get_activities_member_rss_link() { return bp_get_member_activity_feed_link(); }


/* Template tags for RSS feed output */

function bp_activity_feed_item_guid() {
	echo bp_get_activity_feed_item_guid();
}
	function bp_get_activity_feed_item_guid() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_guid', md5( $activities_template->activity->date_recorded . '-' . $activities_template->activity->content ) );
	}

function bp_activity_feed_item_title() {
	echo bp_get_activity_feed_item_title();
}
	function bp_get_activity_feed_item_title() {
		global $activities_template;

		if ( !empty( $activities_template->activity->action ) )
			$content = $activities_template->activity->action;
		else
			$content = $activities_template->activity->content;

		$content = explode( '<span', $content );
		$title = trim( strip_tags( html_entity_decode( utf8_encode( $content[0] ) ) ) );

		if ( ':' == substr( $title, -1 ) )
			$title = substr( $title, 0, -1 );

		if ( 'activity_update' == $activities_template->activity->type ) {
			$content = explode( '<div class="activity-inner">', $activities_template->activity->content );
			$title .= ': ' . strip_tags( bp_create_excerpt( $content[1], 15 ));
		}

		return apply_filters( 'bp_get_activity_feed_item_title', $title );
	}

function bp_activity_feed_item_link() {
	echo bp_get_activity_feed_item_link();
}
	function bp_get_activity_feed_item_link() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_link', $activities_template->activity->primary_link );
	}

function bp_activity_feed_item_date() {
	echo bp_get_activity_feed_item_date();
}
	function bp_get_activity_feed_item_date() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_date', $activities_template->activity->date_recorded );
	}

function bp_activity_feed_item_description() {
	echo bp_get_activity_feed_item_description();
}
	function bp_get_activity_feed_item_description() {
		global $activities_template;

		if ( empty( $activities_template->activity->action ) )
			$content = $activities_template->activity->content;
		else
			$content = $activities_template->activity->action . $activities_template->activity->content;

		return apply_filters( 'bp_get_activity_feed_item_description', html_entity_decode( utf8_encode( str_replace( '%s', '', $content ) ) ) );
	}

?>