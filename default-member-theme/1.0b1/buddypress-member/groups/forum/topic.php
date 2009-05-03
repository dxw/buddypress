<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>
		
		<div class="info-group">
			<h4><?php _e( 'Admins', 'buddypress' ); ?></h4>
			<?php bp_group_list_admins() ?>
		</div>
		
		<?php if ( bp_group_has_moderators() ) : ?>
		<div class="info-group">
			<h4><?php _e( 'Mods', 'buddypress' ); ?></h4>
			<?php bp_group_list_mods() ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="info-group">
				<?php if ( bp_has_topic_posts() ) : ?>
				<form action="<?php bp_forum_topic_action() ?>" method="post" id="forum-topic-form">
			
					<h4><?php _e( 'Forum', 'buddypress' ); ?></h4>
				
					<div id="post-count" class="pag-count">
						<?php bp_the_topic_pagination_count() ?>
					</div>
				
					<div class="pagination-links" id="topic-pag">
						<?php bp_the_topic_pagination() ?>
					</div>
				
					<ul id="topic-post-list" class="item-list">
						<li id="topic-meta">
							<a href="<?php bp_forum_permalink() ?>"><?php _e( 'Forum', 'buddypress') ?></a> &raquo; 
							<strong><?php bp_the_topic_title() ?> (<?php bp_the_topic_total_post_count() ?>)</strong>
						</li>
					<?php while ( bp_topic_posts() ) : bp_the_topic_post(); ?>
						<li id="post-<?php bp_the_topic_post_id() ?>">
							<div class="poster-meta">
								<?php bp_the_topic_post_poster_avatar() ?>
								<?php echo sprintf( __( '%s said %s ago:', 'buddypress' ), bp_the_topic_post_poster_name( false ), bp_the_topic_post_time_since( false ) ) ?>
							</div>
					
							<div class="post-content">
								<?php bp_the_topic_post_content() ?>
							</div>
						</li>
					<?php endwhile; ?>
					</ul>
					
					<?php if ( bp_group_is_member() ) : ?>
						
					<div id="post-topic-reply">
						<p><?php _e( 'Add a reply:', 'buddypress' ) ?></p>
						<textarea name="reply_text" id="reply_text"></textarea>
						<input type="submit" name="submit_reply" id="submit_reply" value="Post Reply"/>
					</div>
					
					<?php endif; ?>
					
				</form>	
				<?php else: ?>

					<div id="message" class="info">
						<p><?php _e( 'There are no posts for this topic.', 'buddypress' ) ?></p>
					</div>

				<?php endif;?>
			</div>

		</div>
	</div>
	
	<?php endwhile; endif; ?>
</div>

<?php get_footer() ?>