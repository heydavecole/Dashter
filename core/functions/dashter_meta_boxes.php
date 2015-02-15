<?php 

class dashter_meta_boxes extends dashter_base {
		
	function __construct() {
		add_action( 'admin_menu', array( &$this, 'init' ) );
		add_action('save_post', array( &$this, 'save_dashter_customtweet') );
		add_action('save_post', array( &$this, 'save_dashter_mentioned_users') );
		add_action('save_post', array( &$this, 'save_dashter_post_to_twitter') );
	}
	
	function init () {
		if( function_exists( 'add_meta_box' )) {
			$meta_id = 'dashter-social';
			$meta_title = 'Dashter - Custom Post Tweet';
			$meta_callback = array( &$this, 'dashter_social_metabox');
			$meta_page = 'post';
			$meta_context = 'normal'; 
			$meta_priority = 'core'; 
			$meta_callback_args = NULL;
					
			add_meta_box ( $meta_id, $meta_title, $meta_callback, $meta_page, $meta_context, $meta_priority, $meta_callback_args );
			
			// Sidebar //
			
			$side_meta_id = 'dashter-publish-to-twitter';
			$side_meta_title = 'Dashter Settings';
			$side_meta_callback = array( &$this, 'dashter_publish_twitter_metabox');
			$side_meta_page = 'post';
			$side_meta_context = 'side';
			$side_meta_priority = 'high';
			$side_meta_callback_args = NULL;
			
			add_meta_box ( $side_meta_id, $side_meta_title, $side_meta_callback, $side_meta_page, $side_meta_context, $side_meta_priority, $side_meta_callback_args );
			
			// Sidebar - Post Mentions // 
			
			$side_meta_id = 'dashter-post-mentions';
			$side_meta_title = 'Dashter - Post Mentions';
			$side_meta_callback = array( &$this, 'dashter_post_mentions_callback');
			$side_meta_page = 'post';
			$side_meta_context = 'side';
			$side_meta_priority = 'default';
			$side_meta_callback_args = NULL;
			
			add_meta_box ( $side_meta_id, $side_meta_title, $side_meta_callback, $side_meta_page, $side_meta_context, $side_meta_priority, $side_meta_callback_args );
		}
	}
	
	function dashter_social_metabox( $post ){
		?>
		<script type="text/javascript">
			function DashterToggleCustomTweetControls(){
				if ( jQuery('input[name=dashter_meta_customtweet_enabled]').is(':checked') ){
					jQuery('#dashter_customtweet_options').fadeIn('fast');
				} else {
					jQuery('#dashter_customtweet_options').fadeOut('fast');
				}
			}
			jQuery(document).ready(function(){
				// LOAD : Check toggle
				DashterToggleCustomTweetControls();
			});
		</script>
		<p>Instead of your default post tweet, you can create a different tweet message.</p>
		<?php 
		
		$dashter_meta_customtweet = get_post_meta( $post->ID, '_dashter_meta_customtweet', true );
		if (!$dashter_meta_customtweet){
			// $dashter_meta_customtweet = get_option('dashter_t_newpostmessage');
		}
		$dashter_meta_customtweet_enabled = get_post_meta( $post->ID, '_dashter_meta_customtweet_enabled', true );
		
		$dashter_meta_linktopost_enabled = get_post_meta( $post->ID, '_dashter_meta_linktopost_enabled', true );
		$dashter_meta_includetags_enabled = get_post_meta( $post->ID, '_dashter_meta_includetags_enabled', true );
		
		if (!$dashter_meta_linktopost_enabled) {
			$dashter_meta_linktopost_enabled = 'enabled';
		}
		if (!$dashter_meta_includetags_enabled) {
			$dashter_meta_includetags_enabled = 'enabled';
		}
		
		?>
		
		<p><textarea style="width: 100%;" name="dashter_meta_customtweet"><?php echo $dashter_meta_customtweet; ?></textarea>
		</p>
		<p>Enable Custom Tweet: <input type="checkbox" name="dashter_meta_customtweet_enabled" value="enable" 
		onclick="javascript:DashterToggleCustomTweetControls();"
		<?php if ($dashter_meta_customtweet_enabled == 'enable'){ echo "checked='checked'"; } ?>>
			<span id="dashter_customtweet_options" style="float: right; display: none;">
			Include Link to Post <input type="checkbox" name="dashter_meta_linktopost_enabled" value="enable" 
			<?php if ($dashter_meta_linktopost_enabled == 'enabled') { echo "checked='checked'"; } ?>> 
			Include Tags in Post <input type="checkbox" name="dashter_meta_includetags_enabled" value="enable" 	
			<?php if ($dashter_meta_includetags_enabled == 'enabled') { echo "checked='checked'"; } ?>>
			</span>		
		</p>
		<?php 
	}
	
	function dashter_publish_twitter_metabox( $post ){
		$dashter_meta_pub_twitter = get_post_meta( $post->ID, '_dashter_meta_pub_twitter', true );
		?>
		<p>
			<img src="<?php echo DASHTER_URL; ?>images/dashter-tray-icon.png" width="14" height="14" style="padding: 0 5px;" align="absmiddle"><b>Curation Post</b> <input type="checkbox" name="dashter_curation_checkbox" value="true"
			<?php if ( get_post_meta( $post->ID, 'dashter_curated', true) == 'true' ) { echo " checked='checked' "; } ?> >
		</p><p>
		<img src="<?php echo DASHTER_URL; ?>images/twitter_icon.png" width="20" height="13" align="absmiddle" style="padding: 0 2px;"><b>Publish to Twitter</b>
		<input type="hidden" name="new_check" value="setup">
		<input type="checkbox" name="dashter_pub_to_twitter" value="enable"
		<?php 
		if ( $dashter_meta_pub_twitter == 'enable' ) { echo " checked='checked' "; } 
		echo ">";
		echo "</p>";

	}
	
	function dashter_post_mentions_callback( $post ) {
		?>
		<script type="text/javascript">
			function delUser(userid){
				jQuery('.addedUser#'+userid).remove();
			}
			jQuery(document).ready(function(){
				jQuery('.mentionadd').click(function(){
					var mUser = (jQuery('#new-mention').val()).replace(/[@]/g, "");
					// mUser = mUser.replace(/[@]/g, "");
					// alert ('This is just a test: ' + mUser + ' to ' + mUser.replace(/[@]/g, "") );
					console.log('The length of mUser is ' + mUser.length + ' mUser is ' + mUser );
					if ( (mUser.length) > 0 ){
						console.log('Adding user ' + mUser);
						jQuery('.mentionchecklist').append('<span class="addedUser" id="' + mUser + '"><input type="hidden" name="dashter_mentionUser[]" value="' + mUser + '"><a id="' + mUser + '" href="Javascript:delUser(\'' + mUser + '\');">X</a> @' + mUser + '</span>');
						jQuery('#new-mention').val('');
					}
				});
				
			});
		</script>
		
		<div class="mentionsdiv" id="dashter_post_mentions">
			<div class="jaxmention">
				<div class="hide-if-no-js">
					<p>
						<input type="text" id="new-mention" name="newmention[user_mention]" class="newmention" size="16" autocomplete="off" value="" />
						<input type="button" class="button mentionadd" value="Add" tabindex="3" />
					</p>
				</div>
				<p class="howto">List any Twitter users mentioned in this post, and they will be notified about your new article.</p>
			</div>
			<div class="mentionchecklist">
			<?php 
			$mentionedUsers = get_post_meta( $post->ID, '_dashter_meta_mentioned_users', false );
			if ($mentionedUsers){
				$theMentions = $mentionedUsers[0];
				$theMentions = array_unique($theMentions);
			}

			if ($theMentions){
				foreach( (array) $theMentions as $user ){
					echo "<span class='addedUser' id='" . $user . "'><input type='hidden' name='dashter_mentionUser[]' value='" . $user . "'>";
					echo "<a id='" . $user . "' href='Javascript:delUser(\"" . $user . "\")'>X</a> @" . $user . "</span>";
				}
			}
			?>
			</div>
		</div>

		<?php
	}
	
	function save_dashter_mentioned_users( $post_id) {
		if ( isset ($_POST['dashter_mentionUser']) ){
			foreach ($_POST['dashter_mentionUser'] as $mkey => $muser) {
				// Populate array
				$mentions[] = strip_tags( $muser );
			}
			update_post_meta( $post_id, '_dashter_meta_mentioned_users', $mentions );
		}
	}
	function save_dashter_customtweet( $post_id ){
		if ( isset ($_POST['dashter_meta_customtweet'] ) ) {
			update_post_meta( $post_id, '_dashter_meta_customtweet', strip_tags( $_POST['dashter_meta_customtweet'] ));
		
			if (isset ($_POST['dashter_meta_customtweet_enabled'])){
				update_post_meta( $post_id, '_dashter_meta_customtweet_enabled', 'enabled' );	
				if (!isset ($_POST['dashter_meta_linktopost_enabled'] ) ) { 
					update_post_meta( $post_id, '_dashter_meta_linktopost_enabled', 'disabled' );
				} else {
					update_post_meta( $post_id, '_dashter_meta_linktopost_enabled', 'enabled' );
				}
				if (!isset ($_POST['dashter_meta_includetags_enabled'] ) ) {
					update_post_meta( $post_id, '_dashter_meta_includetags_enabled', 'disabled' );
				} else {
					update_post_meta( $post_id, '_dashter_meta_includetags_enabled', 'enabled' );
				}
				
			} else {
				delete_post_meta( $post_id, '_dashter_meta_customtweet_enabled' );
			}
		}
	}
	function save_dashter_post_to_twitter( $post_id ){
		if ( isset ($_POST['dashter_curation_checkbox']) ) {
			update_post_meta( $post_id, 'dashter_curated', 'true');
		} else {
			delete_post_meta( $post_id, 'dashter_curated' );
		}	
		if ( isset ($_POST['dashter_pub_to_twitter']) ){
			update_post_meta( $post_id, '_dashter_meta_pub_twitter', strip_tags( $_POST['dashter_pub_to_twitter'] ) );
		} else {
			if ($_POST['new_check']){
				update_post_meta( $post_id, '_dashter_meta_pub_twitter', 'disabled' );
			} else {
				update_post_meta( $post_id, '_dashter_meta_pub_twitter', 'enable' );
			}
		}
	}
}

new dashter_meta_boxes;