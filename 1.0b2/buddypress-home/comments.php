<?php // Do not delete these lines
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>

			<p class="nocomments">This post is password protected. Enter the password to view comments.</p>

			<?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'class="alt" ';
?>

<!-- You can start editing here. -->

<div id="comments-section">
<?php if ($comments) : ?>
	<h3 id="responses"><?php comments_number('No Responses', 'One Response', '% Responses' );?> to &#8220;<?php the_title(); ?>&#8221;</h3>
		
	<ol class="commentlist" id="comments">

		<?php foreach ($comments as $comment) : ?>

		<li <?php echo $oddcomment; ?>id="comment-<?php comment_ID() ?>">
			<div class="comment-details">
				<?php bp_comment_author_avatar() ?>
				<p><?php comment_author_link() ?> said:</p>
			</div>

			<div class="comment-content">
				<?php if ($comment->comment_approved == '0') : ?>
					<p><strong>Your comment is awaiting moderation.</strong></p>
				<?php endif; ?>
				<?php comment_text() ?>

				<p class="commentmetadata"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date('F jS, Y') ?> at <?php comment_time() ?></a> <?php edit_comment_link('Edit','&nbsp; [ ',' ]'); ?></p>
			</div>
			<div class="clear"></div>
		</li>

		<?php
		/* Changes every other comment to a different class */
		$oddcomment = ( empty( $oddcomment ) ) ? 'class="alt" ' : '';
		?>

		<?php endforeach; /* end for each comment */ ?>

	</ol>
<?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments">Comments are closed.</p>
	<?php endif; ?>
	
<?php endif; ?>
</div>

<div id="compose-reply">
	<?php if ('open' == $post->comment_status) : ?>

<h3 id="respond">Leave a Reply</h3>

<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>">logged in</a> to post a comment.</p>
<?php else : ?>

<form action="<?php echo site_url(); ?>/wp-comments-post.php" method="post" id="commentform">

<?php if ( $user_ID ) : ?>

<p>Logged in as <a href="<?php echo site_url(); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo site_url(); ?>/wp-login.php?action=logout" title="Log out of this account">Logout &raquo;</a></p>

<?php else : ?>

<p><input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
<label for="author">Name <?php if ($req) echo "(required)"; ?></label></p>

<p><input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
<label for="email">Mail (will not be published) <?php if ($req) echo "(required)"; ?></label></p>

<p><input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
<label for="url">Website</label></p>

<?php endif; ?>

<!--<p><strong>XHTML:</strong> You can use these tags: <code><?php echo allowed_tags(); ?></code></p>-->

<p><textarea name="comment" id="comment" cols="38" rows="10" tabindex="4"></textarea></p>

<p><input name="submit" type="submit" id="submit" tabindex="5" value="Submit Comment" />
<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
</p>
<?php do_action('comment_form', $post->ID); ?>

<?php if ( $user_ID ) : ?>
</div>
<?php endif; ?>

</form>
</div>
<?php endif; // If registration required and not logged in ?>

<?php endif; // if you delete this the sky will fall on your head ?>

