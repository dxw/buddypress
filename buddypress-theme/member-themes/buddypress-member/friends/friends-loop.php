<div id="friends-loop">
	<?php if ( bp_has_friendships() ) : ?>
		<div class="pagination-links" id="pag">
			<?php bp_friend_pagination() ?>
		</div>
		
		<ul id="friend-list" class="item-list">
		<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
			<li>
				<?php bp_friend_avatar_thumb() ?>
				<h4><?php bp_friend_link() ?></h4>
				<span class="activity"><?php bp_friend_last_active() ?></span>
				
				<div class="action">
					<?php bp_add_friend_button() ?>
				</div>
			</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_word_or_name( __( "Your friends list is currently empty", 'buddypress' ), __( "%s's friends list is currently empty", 'buddypress' ) ) ?></p>
		</div>

	<?php endif;?>
</div>