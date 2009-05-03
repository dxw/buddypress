<?php

Class BP_Messages_Template {
	var $current_thread = -1;
	var $current_thread_count;
	var $total_thread_count;
	var $threads;
	var $thread;
	
	var $in_the_loop;
	var $user_id;
	var $box;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;

	function bp_messages_template( $user_id, $box ) {
		$this->pag_page = isset( $_GET['mpage'] ) ? intval( $_GET['mpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;
		$this->user_id = $user_id;
		$this->box = $box;
		
		if ( $this->box == 'notices' )
			$this->threads = BP_Messages_Notice::get_notices();
		else
			$this->threads = BP_Messages_Thread::get_current_threads_for_user( $this->user_id, $this->box, $this->pag_num, $this->pag_page );
		
		if ( !$this->threads ) {
			$this->thread_count = 0;
			$this->total_thread_count = 0;
		} else { 
			$this->thread_count = count($this->threads);
		
			if ( $this->box == 'notices' )
				$this->total_thread_count = BP_Messages_Notice::get_total_notice_count();
			else
				$this->total_thread_count = BP_Messages_Thread::get_total_threads_for_user( $this->user_id, $this->box );
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'mpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_thread_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_threads() {
		if ( $this->thread_count )
			return true;
		
		return false;
	}
	
	function next_thread() {
		$this->current_thread++;
		$this->thread = $this->threads[$this->current_thread];
		
		return $this->thread;
	}
	
	function rewind_threads() {
		$this->current_thread = -1;
		if ( $this->thread_count > 0 ) {
			$this->thread = $this->threads[0];
		}
	}
	
	function message_threads() { 
		if ( $this->current_thread + 1 < $this->thread_count ) {
			return true;
		} elseif ( $this->current_thread + 1 == $this->thread_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_threads();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_message_thread() {
		global $thread;

		$this->in_the_loop = true;
		$thread = $this->next_thread();

		if ( $this->current_thread == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_message_threads() {
	global $bp, $messages_template;

	if ( $bp['current_action'] == 'notices' && !is_site_admin() ) {
		wp_redirect( $bp['current_userid'] );
	} else {
		if ( $bp['current_action'] == 'inbox' )
			bp_core_delete_notifications_for_user_by_type( $bp['loggedin_userid'], 'messages', 'new_message' );
	
		$messages_template = new BP_Messages_Template( $bp['loggedin_userid'], $bp['current_action'] );
	}
	
	return $messages_template->has_threads();
}

function bp_message_threads() { 
	global $messages_template;
	return $messages_template->message_threads();
}

function bp_message_thread() {
	global $messages_template;
	return $messages_template->the_message_thread();
}

function bp_message_thread_id() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_id', $messages_template->thread->thread_id );
}

function bp_message_thread_subject() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_subject', $messages_template->thread->last_message_subject );
}

function bp_message_thread_excerpt() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_excerpt', bp_create_excerpt($messages_template->thread->last_message_message, 20) );
}

function bp_message_thread_from() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_from', bp_core_get_userlink($messages_template->thread->last_sender_id) );
}

function bp_message_thread_to() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_to', BP_Messages_Thread::get_recipient_links($messages_template->thread->recipients) );
}

function bp_message_thread_view_link() {
	global $messages_template, $bp;
	echo apply_filters( 'bp_message_thread_view_link', $bp['loggedin_domain'] . $bp['messages']['slug'] . '/view/' . $messages_template->thread->thread_id );
}

function bp_message_thread_delete_link() {
	global $messages_template, $bp;
	echo apply_filters( 'bp_message_thread_delete_link', $bp['loggedin_domain'] . $bp['messages']['slug'] . '/' . $bp['current_action'] . '/delete/' . $messages_template->thread->thread_id );
}

function bp_message_thread_has_unread() {
	global $messages_template;
	
	if ( $messages_template->thread->unread_count )
		return true;
	
	return false;
}

function bp_message_thread_unread_count() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_unread_count', $messages_template->thread->unread_count );
}

function bp_message_thread_last_post_date() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_last_post_date', bp_format_time( strtotime($messages_template->thread->last_post_date) ) );
}

function bp_message_thread_avatar() {
	global $messages_template;
	echo apply_filters( 'bp_message_thread_avatar', bp_core_get_avatar($messages_template->thread->last_sender_id, 1) );
}

function bp_message_thread_view() {
	global $thread_id;

	messages_view_thread($thread_id);
}

function bp_total_unread_messages_count() {
	echo BP_Messages_Thread::get_inbox_count();
}

function bp_messages_pagination() {
	global $messages_template;
	echo apply_filters( 'bp_messages_pagination', $messages_template->pag_links );
}

function bp_messages_form_action() {
	global $bp;
	
	echo apply_filters( 'bp_messages_form_action', $bp['loggedin_domain'] . $bp['messages']['slug'] . '/' . $bp['current_action'] );
}

function bp_messages_username_value() {
	if ( isset( $_SESSION['send_to'] ) ) {
		echo apply_filters( 'bp_messages_username_value', $_SESSION['send_to'] );
	} else if ( isset( $_GET['r'] ) && !isset( $_SESSION['send_to'] ) ) {
		echo apply_filters( 'bp_messages_username_value', $_GET['r'] );
	}
}

function bp_messages_subject_value() {
	echo apply_filters( 'bp_messages_subject_value', $_SESSION['subject'] );
}

function bp_messages_content_value() {
	echo apply_filters( 'bp_messages_content_value', $_SESSION['content'] );
}

function bp_messages_options() {
?>
	<?php _e( 'Select:', 'buddypress' ) ?> 
		<select name="message-type-select" id="message-type-select">
			<option value=""></option>
			<option value="read"><?php _e('Read', 'buddypress') ?></option>
			<option value="unread"><?php _e('Unread', 'buddypress') ?></option>
			<option value="all"><?php _e('All', 'buddypress') ?></option>
		</select> &nbsp;
		<a href="#" id="mark_as_read"><?php _e('Mark as Read', 'buddypress') ?></a> &nbsp;
		<a href="#" id="mark_as_unread"><?php _e('Mark as Unread', 'buddypress') ?></a> &nbsp;
		<a href="#" id="delete_messages"><?php _e('Delete', 'buddypress') ?></a> &nbsp;
<?php	
}

function bp_message_is_active_notice() {
	global $messages_template;
	
	if ( $messages_template->thread->is_active ) {
		echo "<strong>";
		_e('Currently Active', 'buddypress');
		echo "</strong>";
	}
}

function bp_message_notice_post_date() {
	global $messages_template;
	echo apply_filters( 'bp_message_notice_post_date', bp_format_time( strtotime($messages_template->thread->date_sent) ) );
}

function bp_message_notice_subject() {
	global $messages_template;
	echo apply_filters( 'bp_message_notice_subject', $messages_template->thread->subject );
}

function bp_message_notice_text() {
	global $messages_template;
	echo apply_filters( 'bp_message_notice_text', $messages_template->thread->message );
}

function bp_message_notice_delete_link() {
	global $messages_template, $bp;
	
	echo apply_filters( 'bp_message_notice_delete_link', $bp['loggedin_domain'] . $bp['messages']['slug'] . '/notices/delete/' . $messages_template->thread->id );
}

function bp_message_activate_deactivate_link() {
	global $messages_template, $bp;

	if ( $messages_template->thread->is_active == "1" ) {
		$link = $bp['loggedin_domain'] . $bp['messages']['slug'] . '/notices/deactivate/' . $messages_template->thread->id;
	} else {
		$link = $bp['loggedin_domain'] . $bp['messages']['slug'] . '/notices/activate/' . $messages_template->thread->id;		
	}
	echo apply_filters( 'bp_message_activate_deactivate_link', $link );
}

function bp_message_activate_deactivate_text() {
	global $messages_template;
	
	if ( $messages_template->thread->is_active == "1" ) {
		$text = __('Deactivate', 'buddypress');
	} else {
		$text = __('Activate', 'buddypress');		
	}
	echo apply_filters( 'bp_message_activate_deactivate_text', $text );
}

function bp_message_get_notices() {
	global $userdata;
	
	$notice = BP_Messages_Notice::get_active();
	$closed_notices = get_usermeta( $userdata->ID, 'closed_notices');

	if ( !$closed_notices )
		$closed_notices = array();

	if ( is_array($closed_notices) ) {
		if ( !in_array( $notice->id, $closed_notices ) && $notice->id ) {
			?>
			<div class="notice" id="<?php echo $notice->id ?>">
				<h5><?php echo stripslashes($notice->subject) ?></h5>
				<?php echo stripslashes($notice->message) ?>
				<a href="#" id="close-notice">Close</a>
			</div>
			<?php
		}	
	}
}

function bp_send_message_button() {
	global $bp;
	
	if ( bp_is_home() || !is_user_logged_in() )
		return false;
	
	$ud = get_userdata( $bp['current_userid'] ); 
	?>
	<div class="generic-button">
		<a class="send-message" title="<?php _e( 'Send Message', 'buddypress' ) ?>" href="<?php echo $bp['loggedin_domain'] . $bp['messages']['slug'] ?>/compose/?r=<?php echo $ud->user_login ?>"><?php _e( 'Send Message', 'buddypress' ) ?></a>
	</div>
	<?php
}

function bp_message_loading_image_src() {
	global $bp;
	echo $bp['messages']['image_base'] . '/ajax-loader.gif';
}

function bp_message_get_recipient_tabs() {
	global $bp;
	
	if ( isset( $_GET['r'] ) ) {
		$user_id = bp_core_get_userid_from_user_login( $_GET['r'] );
		
		if ( $user_id ) {
			?>
			<li id="un-<?php echo $_GET['r'] ?>" class="friend-tab">
				<span>
					<?php echo bp_core_get_avatar( $user_id, 1, 15, 15 ) ?>
					<?php echo bp_core_get_userlink( $user_id ) ?>
				</span>
				<span class="p">X</span>
			</li>
			<?php
		}
	}
}

function bp_message_get_recipient_usernames() {
	echo $_GET['r'];
}
?>