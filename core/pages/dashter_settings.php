<?php 

class dashter_settings extends dashter_base {
	
	var $callback_url;
	var $page_title = 'Dashter Settings';
	var $menu_title = 'Settings';
	var $menu_slug = 'dashter-settings';
	var $ajax_callback = 'dashter_settings';
	var $conn;
	
	function __construct() {
		
		$this->callback_url = home_url() . "/wp-admin/admin.php?page=dashter-settings&action=twitter_callback";
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
		
	}
	
	function init () {
		if ($_REQUEST['action'] == 'twitter_authorize') { 
			$this->twitter_authorize();
		} else if ($_REQUEST['action'] == 'twitter_deauthorize') { 
			$this->twitter_deauthorize();
			add_thickbox();
		} else if ($_REQUEST['action'] == 'twitter_callback') { 
			$this->twitter_callback();
			add_thickbox();
		} else if ($_GET['page'] == $this->menu_slug) {
			add_thickbox();
		}
	}
	
	public function twitter_authorize () {
		
		session_start();
		
		global $twitterconn;
		$twitterconn->init();
		$request_token = $twitterconn->getRequestToken($this->callback_url);
		if (!$twitterconn){
			echo " Failed.";
		} else {
			$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
			$url = $twitterconn->getAuthorizeURL($request_token['oauth_token']);
			wp_redirect( $url );
			exit;
		}
		
	}
	
	public function twitter_deauthorize () {
		delete_option('dashter_user_twitter_oauth_token');
		delete_option('dashter_user_twitter_oauth_token_secret');
		delete_option('dashter_twitter_screen_name');
	}
	
	public function twitter_callback () {
		global $twitterconn;
		session_start();
		
		update_option('dashter_user_twitter_oauth_token', $_REQUEST['oauth_token']);
		update_option('dashter_user_twitter_oauth_token_secret', $_SESSION['oauth_token_secret']);
		
		unset($twitterconn);
		$twitterconn = new twitter_connection;
		$twitterconn->init();
		
		$access_token = $twitterconn->getAccessToken($_REQUEST['oauth_verifier']);
		$oauth_token = $access_token['oauth_token'];
		$oauth_token_secret = $access_token['oauth_token_secret'];
		update_option('dashter_user_twitter_oauth_token', $oauth_token);
		update_option('dashter_user_twitter_oauth_token_secret', $oauth_token_secret);
		
		unset($twitterconn);
		$twitterconn = new twitter_connection;
		$twitterconn->init();
		
		$user = $twitterconn->get('account/verify_credentials');
		update_option('dashter_twitter_screen_name', $user->screen_name);
	}
	
	public function display_page () {	
	
		if ($_POST['clearUnsent'] == 'true'){
			global $wpdb;
			$table_name = $wpdb->prefix . "dashter_queue";
			$delQueue = "DELETE FROM $table_name WHERE tweetStatus = 'queued'";
			$success = $wpdb->query($delQueue);
			$status_update = "Purged unsent tweets from the queue.";	
		}
		
		if ($_REQUEST['action'] == 'refreshScreenName'){
			global $twitterconn;
			$twitterconn->init();
			
			$user = $twitterconn->get('account/verify_credentials');
			update_option('dashter_twitter_screen_name', $user->screen_name);	
		}
		if ($_REQUEST['action'] == 'twitter_screenname') { 
			$this->twitter_screenname();
		}
		
		if(!get_option('dashter_default_post')){
			$default_post = "Hey ~user~, noticed you like ~tag~. You might like my post: ~link~";
			update_option('dashter_default_post', $default_post);
		}
		
		if ($_REQUEST['action'] == 'update_default_post'){
			$new_def_post = $_REQUEST['default_post'];
			update_option('dashter_default_post', $new_def_post);
			$status_update = "Successfully updated your default post to <b>" . $new_def_post . "</b>";
		}
	
		if ($_REQUEST['action'] == 'set_adv_metrics'){
			if (($_REQUEST['dashter_advanced_metrics'])=='enabled'){
				update_option('dashter_advanced_metrics', 'enabled');
			} else {
				update_option('dashter_advanced_metrics', 'disabled');
			}
		}
		if ($_REQUEST['open_queue_profile']){
		
			$select_queue_profile = intval($_REQUEST['select_queue_profile']);
		
		}
		
		if (isset($_POST['BitlyKey'])){
			$bitlykey = stripslashes($_POST['BitlyKey']);
			update_option('dashter_bitly_key', $bitlykey);
			$status_update = "Successfully added your bitly API key.";
		}	
		if (isset($_POST['GooglKey'])){
			$googlKey = stripslashes($_POST['GooglKey']);
			update_option('dashter_googl_key', $googlKey);
			$status_update = "Successfully added your googl API key.";
		}	
		
		if (isset($_POST['saveMiscSettings'])){
			
			if (isset($_POST['Dashter_Favorites_Curation_Rule'])){
				$favoritesrule = $_POST['Dashter_Favorites_Curation_Rule'];
				update_option('dashter_favorites_curation_rule', $favoritesrule);
			}
			
			if (isset($_POST['Dashter_Hide_Trending_In_Followers'])){
				update_option('dashter_hide_trending', 'enabled');
			} else {
				update_option('dashter_hide_trending', 'disabled');
			}
			
			if (isset($_POST['Dashter_BlackbirdPie_Curation'])){
				update_option('dashter_blackbirdpie_curation', 'enabled');
			} else {
				delete_option('dashter_blackbirdpie_curation');
			}
			
			$status_update = "Successfully updated Miscellaneous settings.";
		}
		
		
		
		if (isset($_POST['saveQueueSettings'])){
			$Qfrequency = intval( $_POST['Dashter_Queue_Frequency'] );
			$Qstart = intval( $_POST['Dashter_Queue_Start'] );
			$Qstop = intval( $_POST['Dashter_Queue_Stop'] );
			$dashterQueueRun = array ( 'start' => $Qstart, 'stop' => $Qstop );
			$Qalert = $_POST['Dashter_Queue_Alert'];
			
			global $dashter_cron_processor;
			$dashter_cron_processor->init_schedule($Qfrequency);
			$dashter_cron_processor->check_cron();
			
			update_option('dashter_queue_runtime', $dashterQueueRun);	
			if ( ($Qalert == 'true') || ($Qalert === true) ){
				update_option('dashter_queue_alert', true);
			} else {
				update_option('dashter_queue_alert', false);
			}
			if (isset($_POST['Dashter_Hold_Auto_Queue'])){
				update_option('dashter_auto_hold', true);
			} else {
				update_option('dashter_auto_hold', false);
			}
			if (isset($_POST['Dashter_Queue_Recommendations'])){
				update_option('dashter_queue_recommendations_auto', true);
			} else {
				update_option('dashter_queue_recommendations_auto', false);
			}
			$status_update = "Successfully updated your queue settings.";		
		}
		
		if ($_REQUEST['twitter_connection']){
			if ($_REQUEST['twitter_connection'] == 'disconnect'){
				delete_option('dashter_user_twitter_oauth_token');
				delete_option('dashter_user_twitter_oauth_token_secret');
				$display_screenname = get_option('dashter_twitter_screen_name');
				delete_option('dashter_twitter_screen_name');
				$status_update = 'You have successfully disconnected ' . $display_screenname . ' from your site.';
			}
		}	
	
	?>
	<script type="text/javascript">

	function detectPostFreq(){
		jQuery('#post-freq').html('<img src="<?php echo DASHTER_URL . "images/dashter-ajax-loading.gif"; ?>">');
		var data = { 
			action: '<?php echo $this->ajax_callback; ?>',
			request: 'dashter_post_frequency'
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#post-freq').html(response);
		});
	}

	function clickDefTab(tabid) {
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery('#' + tabid).addClass('nav-tab-active');
		
		var desc = '';
		switch (tabid){
			case 'default_newpostmessage':
				desc = 'This is the default message that will be posted to your twitter account every time that a blog post is published. This includes both regular published posts and scheduled posts. Posts that are updated will not be published to twitter. You can choose not to tweet a new post by clicking the checkbox on the publish window in your post. New site pages are not tweeted.';
				break;
			case 'default_mentionedusers':
				desc = 'When you publish a new blog post, you have the option of adding mentioned users in the post. This is the default tweet that will be sent out. These tweets will be queued.';
				break;
			case 'default_relevantlink':
				desc = 'Use this default tweet to notify a specific user about a relevant blog post on your website. This is your default \'Quick Reply\' option on the Twitter stream.';
				break;
			default: 
				desc = 'This is a different box.';
				break;
		}
		var data = {
			action: '<?php echo $this->ajax_callback; ?>',
			request: 'dashter_default_options',
			TabID: tabid
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('.default_tweet_settings').val(response);
			jQuery(drawPreview);
		});
		jQuery('#displayContent').html(desc);
	}
	function drawPreview(){
		var demoUser = '@HeyDaveCole';
		var demoLink = 'http://goo.gl/123456789';
		var demoFullLink = 'This is My New Blog Post http://goo.gl/123456789';
		var demoHash = '#hashtag1 #hashtag2 #hashtag3';
		var theTweet = jQuery('.default_tweet_settings').val();
		theTweet = theTweet.replace("~user~", demoUser);
		theTweet = theTweet.replace("~link~", demoLink);
		theTweet = theTweet.replace("~full~", demoFullLink);
		theTweet = theTweet.replace("~tags~", demoHash);
		var tweetLen = theTweet.length;
		jQuery('.preview_counter').html('<b>' + tweetLen + '</b>');
		jQuery('.default_preview').html(theTweet);
	}
	function showautorule(){
		if ( jQuery('#autolimitnotice').is(':visible') ){
			jQuery('#autolimitnotice').slideUp('fast');
			jQuery('#rulenote').text('Learn More');
		} else {
			jQuery('#autolimitnotice').slideDown('fast');
			jQuery('#rulenote').text('Hide Details');
		}
	} 
	function clearQueue(){
		var yesClear = confirm('Are you sure you want to delete unsent tweets from the queue?');
		if (yesClear){
			document.forms['clearQueue'].submit();
		}
	}
	function enableHelper(){
		jQuery('#helperResponse').html('<img src="<?php echo DASHTER_URL . "images/dashter-ajax-loading.gif"; ?>">');
		
		var data = { 	action: '<?php echo $this->ajax_callback; ?>',
						request: 'enableHelper'
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#helperResponse').html(response);
		});
	}
	
	jQuery(document).ready(function($){
		// onload //
		$('.trunc-notice').hide();
		$('#autolimitnotice').hide();
		$(clickDefTab('default_newpostmessage'));
		$('#default_save_status').hide();
		
		// click functions //
		$('#show_trunc_notice').click(function(){
			if ( $('.trunc-notice').is(':visible') ){
				$('.trunc-notice').slideUp('fast');	
			} else {
				$('.trunc-notice').slideDown('fast');
			}
		});
		
		$('.nav-tab').click(function(){
			$('.default_tweet_settings').val('');
			$('.preview_counter').html('');
			$('.default_preview').html('');
			var tabid = $(this).attr('id');
			$(clickDefTab(tabid));
		});
		
		$('#default_savetweet').click(function(){
			var theTab = $('.nav-tab-active').attr('id');
			var theTweet = $('.default_tweet_settings').val();
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_default_options',
				TabID: theTab,
				theMsg: theTweet
			};
			jQuery.post(ajaxurl, data, function(response) {
				if ( ( response.indexOf('Saved') ) > -1 ){
					$('#default_save_status').slideDown('fast', function(){
						$(this).delay(500).slideUp('slow');
					});
				}
			});
		});
				
		// keyup tweet // 
		$('.default_tweet_settings').keyup(function(){
			$(drawPreview);
		});
		
	});
	
	</script>
	<style type="text/css">
		.nav-tab {
			cursor: pointer;
		}
		.nav-tab-active {
			font-weight: bold;
		}
		.nav-tab-wrapper a:hover {
			color: #000;
		}
		.default_tweet_settings {
			width: 560px;
			height: 2em;
		}
		
	</style>
	
	<?php $this->display_wrap_header( $this->page_title ); ?>
	
	<?php 
		if ($status_update){
			?><div id="message" class="updated"><?php echo $status_update; ?></div><?php 
		}
	?>
	<h3>Social Networks</h3>
	<p>You can configure your social networking settings here, and can change them at any time. </p>
		<?php if ($twitter_setup_success){ ?>
			<div id="message" class="updated">
				Sweet! Your Twitter account has been configured.
			</div>
		<?php } ?>
		
		<table class="form-table" style="border: solid 1px #ccc;">
		<tr valign="top">
			<th scope="row">
				<label for="twitter_integrate">
				<img src='../wp-content/plugins/dashter/images/twitter_icon.png' width="20" align="absmiddle">
				Twitter Integration</label>
			</th>
			<td>
				<?php 
				if (get_option('dashter_twitter_screen_name')){
				?>
					<form method="POST" action="<?php echo $this->callback_url; ?>&action=twitter_deauthorize">
					<p>
					<img src='../wp-content/plugins/dashter/images/accept.png' width='20' height='20' align='absmiddle'>
					Connected to account <b>@<?php echo get_option('dashter_twitter_screen_name'); ?></b>.
					<input type="submit" value="Disconnect" class="button-secondary">
					</p></form>
				<?php 					
				} else {
				?>
				<form method="POST" action="<?php echo $this->callback_url; ?>&action=twitter_authorize">
					<input type="submit" value="Connect Twitter" class="button-primary">
				</form>
				<?php 
				}
				?>
			</td>
		</tr>
		<?php 
		if (!get_option('dashter_twitter_screen_name')){ 
			// Check if there are unsent items in the queue
			global $wpdb;
			$table_name = $wpdb->prefix . "dashter_queue";
			$query_queue = "SELECT id FROM $table_name WHERE tweetStatus = 'queued'";
			$countQueued = $wpdb->query($query_queue);
			if ($countQueued > 0){
		?>
				<tr>
					<th scope="row">
					<img src='<?php echo DASHTER_URL; ?>images/dashter-tray-icon.png' width="20" align="absmiddle"> 
					Queue Notice</th>
					<form method="POST" name="clearQueue"><input type="hidden" name="clearUnsent" value="true"></form>
					<td>
					<p>You have disconnected from twitter, but you have unset messages in the queue. Do nothing, or 
					<a href="javascript:clearQueue();" class="button-secondary">Delete Unsent Tweets</a> from the queue.
					</p>
					</td>
				</tr>
		<?php 
			} 
		} ?>
		</table>
		
				
		<h3>Default Twitter Options</h3>
		<p>You can configure default post options to expedite the process of sharing your content on your preferred social networks.<br/>Dashter will truncate your posts to make them fit Twitter's 140 character limit. <span id="show_trunc_notice"><a style="cursor: pointer;">How does truncation work?</a></span>
			<div id="message" class="updated trunc-notice">
				<p><b>How does Truncation Work?</b></p>
				<p>Great question. Since twitter limits tweets to 140 characters, there may be times that everything you want to include in a tweet won't fit. It happens. Dashter will truncate your tweet the following ways: 
				<ul>
					<li> &#187; Full links (with title + link) will be replaced with just the link</li>
					<li> &#187; If that's not enough, we'll start dropping tags</li>
					<li> &#187; If that doesn't work, we'll truncate your tweet until it can fit the link, and add a "..." to the end of your tweet.</li>
				</ul></p>
				<p><i>Because of these limits, we recommend you keep your general text as brief as possible! Short and sweet is gold on Twitter.</i></p>
				<p><b>What's the Preview For?</b></p>
				<p>The preview is provided just to give you an idea of what a tweet <i>might</i> look like. The length and the content of the tweet will obviously be specific to your post, who is mentioned, and what tags are featured. Don't count on the preview sample to be reflective of what might actually happen, so be aware of the truncate rules above. To keep it safe, you probably don't want to make your default message longer than about 100 characters.</p>
			</div>
		</p>
		
		<div class="nav-tab-wrapper">
			<a class="nav-tab" id="default_newpostmessage">New Post Message</a>
			<a class="nav-tab" id="default_mentionedusers">Users Mentioned in Posts</a>
			<a class="nav-tab" id="default_relevantlink">Mention / Share a Relevant Link</a>
		</div>
		<div style="border: solid 1px #ccc; padding: 2px 10px;">
			<div style="color: #999;">Shortcodes: <b>~user~</b>: Creates an @username / <b>~link~</b>: Adds Post shortlink / <b>~full~</b>: Adds Post title + shortlink / <b>~tags~</b>: Adds #hashtags from post tags</div>
			<p id="displayContent"></p>
			<div style="vertical-align: top;">
			<table>
			<tr valign="top">
				<td style='padding: 5px 0 0 0; width: 100px;'>Message: </td>
				<td><textarea class='default_tweet_settings'></textarea></td>
				<td style='padding: 2px 0 0 0;'><input id="default_savetweet" type="button" class="button" value="Save">
				<span style='font-weight: bold;' id='default_save_status'><img src='../wp-content/plugins/dashter/images/accept.png' width='20' height='20' align='absmiddle'> Saved!</span></td>
			</td></tr></table>
			<table>
			<tr valign="top">
				<td style='width: 75px;'>Preview: </td><td style='width: 25px;'><span class='preview_counter'></span></td><td style='padding: 0 0 0 5px; color: #4A766E;'><span class="default_preview"></span></td>
			</tr></table>
			</div>
		</div>
		<?php 
			$googlKey = get_option('dashter_googl_key');
		?>	
		<h3>Shortlink Setup</h3>
		<p>Configure your shortlink resource here, so that new posts and mentions are shortened.</p>
		<table class="form-table" style="border: solid 1px #ccc;">
			<tr>
				<th scope="row"><label for="GooglKey"><img src='../wp-content/plugins/dashter/images/google_icon.png' width="20" align="absmiddle"> goo.gl API Key
				<br/><span style="padding-left: 25px; font-size: 8pt;">(<a href="https://code.google.com/apis/console" target="_new">Get Yours</a>)</span>
				</label></th>
				<form method="POST">
				<td><input type="text" name="GooglKey" style="width: 350px;" value="<?php echo $googlKey; ?>"> <input type="submit" class="button" value="Save"></td>
				</form>
			</tr>
				<?php 
						// Test googl key
						if (isset($googlKey)){
							echo "<tr><td colspan='2'><p>";
							$key = $googlKey;
							$googer = new GoogleURLAPI($key);
							$testURL = "http://dashter.com";
							$shortURL = $googer->shorten($testURL);
							if (!$shortURL){
								echo "<b>Your Google API Key does not appear to be correct. URL's will not be shortened.";
							} else {
								echo "<b>Success!</b> Source URL: <a target='_blank' href='" . $testURL . "'>" . $testURL . "</a> Short URL: <a target='_blank' href='" . $shortURL . "'>" . $shortURL . "</a>";
							}
							echo "</p></td></tr>";
						}
					 ?>
		</table>
		
		<br/>
		
		<?php
			$qFreq = intval ( get_option('dashter_queue_frequency') );
			$qRuntime = get_option('dashter_queue_runtime');
			$qAlert = get_option('dashter_queue_alert');
			$qAutoHold = get_option('dashter_auto_hold');
			$qRecAuto = get_option('dashter_queue_recommendations_auto');
			if ($qFreq == 0){
				$qFreq = 1800;
			}
			if (!is_array($qRuntime)){
				$qStart = 0;
				$qStop = 0;
			} else {
				$qStart = intval( $qRuntime['start'] );
				$qStop = intval( $qRuntime['stop'] );
			}
		?>
		
		<h3>Queue Settings</h3>
		<table class="form-table" style="border: solid 1px #ccc;">
			<form method="POST">
			<tr>
				<th scope="row"><label for="Dashter_Queue_Frequency">Queue Frequency</label></th>
				<td>
					<select name="Dashter_Queue_Frequency">
						<option value="300" <?php selected( $qFreq, 300 ); ?>>5 Minutes</option>
						<option value="600" <?php selected( $qFreq, 600 ); ?>>10 Minutes</option>
						<option value="900" <?php selected( $qFreq, 900 ); ?>>15 Minutes</option>
						<option value="1200" <?php selected( $qFreq, 1200 ); ?>>20 Minutes</option>
						<option value="1800" <?php selected( $qFreq, 1800 ); ?>>30 Minutes</option>
						<option value="2700" <?php selected( $qFreq, 2700 ); ?>>45 Minutes</option>
						<option value="3600" <?php selected( $qFreq, 3600 ); ?>>60 Minutes</option>
						<option value="5400" <?php selected( $qFreq, 5400 ); ?>>90 Minutes</option>
						<option value="7200" <?php selected( $qFreq, 7200 ); ?>>120 Minutes</option>
					</select>
					Current Post Frequency: <a href="javascript:detectPostFreq();" class="button-secondary">Check Now</a>
					<div id="post-freq"></div>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="Dashter_Queue_Hours">Queue Hours</label></th>
				<td>
					Only post queued tweets between 
					<select name="Dashter_Queue_Start">
						<option value="00" <?php selected ( $qStart, 0 ); ?>> 12:00 am </option>
						<option value="01" <?php selected ( $qStart, 1 ); ?>> 01:00 am </option>
						<option value="02" <?php selected ( $qStart, 2 ); ?>> 02:00 am </option>
						<option value="03" <?php selected ( $qStart, 3 ); ?>> 03:00 am </option>
						<option value="04" <?php selected ( $qStart, 4 ); ?>> 04:00 am </option>
						<option value="05" <?php selected ( $qStart, 5 ); ?>> 05:00 am </option>
						<option value="06" <?php selected ( $qStart, 6 ); ?>> 06:00 am </option>
						<option value="07" <?php selected ( $qStart, 7 ); ?>> 07:00 am </option>
						<option value="08" <?php selected ( $qStart, 8 ); ?>> 08:00 am </option>
						<option value="09" <?php selected ( $qStart, 9 ); ?>> 09:00 am </option>
						<option value="10" <?php selected ( $qStart, 10 ); ?>> 10:00 am </option>
						<option value="11" <?php selected ( $qStart, 11 ); ?>> 11:00 am </option>
						<option value="12" <?php selected ( $qStart, 12 ); ?>> 12:00 pm </option>
						<option value="13" <?php selected ( $qStart, 13 ); ?>> 01:00 pm </option>
						<option value="14" <?php selected ( $qStart, 14 ); ?>> 02:00 pm </option>
						<option value="15" <?php selected ( $qStart, 15 ); ?>> 03:00 pm </option>
						<option value="16" <?php selected ( $qStart, 16 ); ?>> 04:00 pm </option>
						<option value="17" <?php selected ( $qStart, 17 ); ?>> 05:00 pm </option>
						<option value="18" <?php selected ( $qStart, 18 ); ?>> 06:00 pm </option>
						<option value="19" <?php selected ( $qStart, 19 ); ?>> 07:00 pm </option>
						<option value="20" <?php selected ( $qStart, 20 ); ?>> 08:00 pm </option>
						<option value="21" <?php selected ( $qStart, 21 ); ?>> 09:00 pm </option>
						<option value="22" <?php selected ( $qStart, 22 ); ?>> 10:00 pm </option>
						<option value="23" <?php selected ( $qStart, 23 ); ?>> 11:00 pm </option>
					</select>
					 and
					<select name="Dashter_Queue_Stop">
						<option value="00" <?php selected ( $qStop, 0 ); ?>> 12:00 am </option>
						<option value="01" <?php selected ( $qStop, 1 ); ?>> 01:00 am </option>
						<option value="02" <?php selected ( $qStop, 2 ); ?>> 02:00 am </option>
						<option value="03" <?php selected ( $qStop, 3 ); ?>> 03:00 am </option>
						<option value="04" <?php selected ( $qStop, 4 ); ?>> 04:00 am </option>
						<option value="05" <?php selected ( $qStop, 5 ); ?>> 05:00 am </option>
						<option value="06" <?php selected ( $qStop, 6 ); ?>> 06:00 am </option>
						<option value="07" <?php selected ( $qStop, 7 ); ?>> 07:00 am </option>
						<option value="08" <?php selected ( $qStop, 8 ); ?>> 08:00 am </option>
						<option value="09" <?php selected ( $qStop, 9 ); ?>> 09:00 am </option>
						<option value="10" <?php selected ( $qStop, 10 ); ?>> 10:00 am </option>
						<option value="11" <?php selected ( $qStop, 11 ); ?>> 11:00 am </option>
						<option value="12" <?php selected ( $qStop, 12 ); ?>> 12:00 pm </option>
						<option value="13" <?php selected ( $qStop, 13 ); ?>> 01:00 pm </option>
						<option value="14" <?php selected ( $qStop, 14 ); ?>> 02:00 pm </option>
						<option value="15" <?php selected ( $qStop, 15 ); ?>> 03:00 pm </option>
						<option value="16" <?php selected ( $qStop, 16 ); ?>> 04:00 pm </option>
						<option value="17" <?php selected ( $qStop, 17 ); ?>> 05:00 pm </option>
						<option value="18" <?php selected ( $qStop, 18 ); ?>> 06:00 pm </option>
						<option value="19" <?php selected ( $qStop, 19 ); ?>> 07:00 pm </option>
						<option value="20" <?php selected ( $qStop, 20 ); ?>> 08:00 pm </option>
						<option value="21" <?php selected ( $qStop, 21 ); ?>> 09:00 pm </option>
						<option value="22" <?php selected ( $qStop, 22 ); ?>> 10:00 pm </option>
						<option value="23" <?php selected ( $qStop, 23 ); ?>> 11:00 pm </option>
					</select>
					 <i>Current server time is: <?php echo date('D M j h:i:s a T O Y'); ?></i>
				</td>
			</tr>
			<tr>
				<th><label for="Dashter_Queue_Alert">Empty Queue Notification</label></th>
				<td>
					<input type="checkbox" name="Dashter_Queue_Alert" value="true" <?php checked ( $qAlert, true ); ?>> 
					Provide a notification if my Queue is empty (will appear in the admin header).
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="Dashter_Hold_Auto_Queue">Auto-Tweet Hold</label></th>
				<td>
					<p><input type="checkbox" name="Dashter_Hold_Auto_Queue" value="enabled"
					<?php checked ( $qAutoHold, true ); ?>> Hold auto-tweets in the queue until a non-auto tweet is detected. <a id="rulenote" href="javascript:showautorule();">Learn More</a></p>
					<div id="autolimitnotice" style="border: solid 1px #ccc; padding: 10px; background: #dfdfdf;">
					<p>When this feature is enabled, tweets automatically generated by Dashter will be held until a "fresh" (non auto-generated) tweet is detected.</p>
					<p>This feature exists to help ensure that you don't end up filling your tweet stream with automatic posts, which might make some of your followers want to stop following you. By blending system-generated tweets with your personally written tweets, you ensure that your Twitter stream stays fresh and relevant to your audience. <b>We recommend you keep this feature enabled.</b></p>
					</div>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><label for="Dashter_Queue_Recommendations">Queue Recommendations</label></th>
				<td>
					<p><input type="checkbox" name="Dashter_Queue_Recommendations" value="enabled"
					<?php checked ( $qRecAuto, true ); ?>> Treat queued recommendations as auto-tweets (for use with auto-hold). </p>
				</td>
			</tr>
			
			<tr>
				<th>&nbsp;</th>
				<td><input type="submit" value="Save Queue Settings" class="button-secondary"></td>
			</tr>
			<input type="hidden" name="saveQueueSettings" value="true">
			</form>
		</table>
		<br/>
		<?php 
			$favrule = get_option('dashter_favorites_curation_rule');
		?>
		
		<h3>Miscellaneous Dashter Settings</h3>
		<table class="form-table" style="border: solid 1px #ccc;">
			<form method="POST">
			<tr>
				<th scope="row"><label for="Dashter_Hide_Trending_In_Followers">Trending in Followers</label></th>
				<td>
					<p><input type="checkbox" name="Dashter_Hide_Trending_In_Followers" value="enabled"
					<?php if (get_option('dashter_hide_trending') == 'enabled') { echo 'checked="checked"'; } ?>
					>
					Hide trends in followers. <a id="trendrule" href="javascript:showLM('trendrule');">Learn More</a></p>
				</td>
			</tr>
		
			<?php if ( class_exists('BlackbirdPie') ) { ?>
			<tr>
				<th scope="row"><label for="Dashter_BlackbirdPie_Curation">Blackbird Pie Curation</label></th>
				<td>
					<p>Looks like you have the Blackbird Pie plugin installed. You can enable Dashter to curate tweets using the Blackbird Pie format. <br/>
					<span style="color: #f12;"><i>WARNING: </i> Dashter curated tweets are saved directly in your blog posts, but Blackbird Pie's are not. If you un-install Blackbird Pie, you will lose <b>all</b> your curated tweets.</span></p>
					<p><input type="checkbox" name="Dashter_BlackbirdPie_Curation" value="enabled"
					<?php if (get_option('dashter_blackbirdpie_curation') == 'enabled') { echo 'checked="checked"'; } ?>
					>
					Enable Blackbird Pie to curate tweets.
				</td>
			</tr>
			<?php 
			} else {  
				delete_option('dashter_blackbirdpie_curation');
			} 
			?>
			<tr>
				<th></th>
				<input type="hidden" name="saveMiscSettings" value="yes">
				<td><input type="submit" value="Save Settings" class="button-secondary"></td>
			</tr>
			</form>
		</table>
		<br class="clear" />
	<?php
	}
	
	public function process_ajax () {
	
		global $twitterconn;
		$twitterconn->init();
	
		$action = $_POST['request'];
		switch ($action){
			// DEFAULT AUTO TWEET SETTINGS // 
			case 'dashter_default_options':
				$theMsg = stripslashes($_POST['theMsg']);
				$tabID = $_POST['TabID'];
				if (!$theMsg){
					switch ($tabID){
						case 'default_newpostmessage':
							$optval = get_option('dashter_t_newpostmessage');
							break;
						case 'default_mentionedusers':
							$optval = get_option('dashter_t_mentionedusers');
							break;
						case 'default_relevantlink':
							$optval = get_option('dashter_t_relevantlink');
							break;
					}
					echo $optval;
				} else {
					$updateSuccess = false;
					switch ($tabID){
						case 'default_newpostmessage':
							update_option('dashter_t_newpostmessage', $theMsg);
							$updateSuccess = true;
							break;
						case 'default_mentionedusers':
							update_option('dashter_t_mentionedusers', $theMsg);
							$updateSuccess = true;
							break;
						case 'default_relevantlink':
							update_option('dashter_t_relevantlink', $theMsg);
							$updateSuccess = true;
							break;
						default:
							break;
					}
					if ($updateSuccess) { echo "Saved."; }
				}				
				break;
			case 'dashter_post_frequency':
				$mysn = get_option('dashter_twitter_screen_name');
				$params = array(
								'count' => 150
								);
				$myTweets = $twitterconn->get('statuses/user_timeline', $params);
				if (!empty($myTweets)){
					$tDist = 0;
					$numTweets = 0;
					$prevtime = 0;
					foreach ($myTweets as $tweet){
						$numTweets++;
						$tweetTime = date('U', strtotime( $tweet->created_at . " -7 hours" ) );
						if ($prevtime == 0){
							$firstTime = $tweetTime;
							$prevtime = $tweetTime;
						} else {
							$lastTime = $tweetTime;
							$tDist = $prevtime - $tweetTime;
						}						
					}
					$totalDist = $firstTime - $lastTime;
					echo "You posted " . $numTweets . " tweets in the last " . intval(floor($totalDist/86400)) . " days, and you averaged ";
					if ( intval ( floor( $totalDist / 86400 ) ) > 1 ){
						// Remove 28800 seconds per day
						$tDist = $tDist - (28800 * intval(floor($totalDist/86400)));
					}
					if ($numTweets > 0){
						$avgDist = $tDist / $numTweets;
						echo "about ";
						// echo $avgDist . " seconds between tweets. ";
						echo round( ($avgDist / 60), 0 ) . " minutes between tweets.";
						if ($avgDist > 0){
							echo "That's around " . round( 60 / ( ($avgDist) / 60 ) , 2 ) . " tweets per hour.";
						}
					}
				} else {
					echo "An error occurred.";
				}	
				break;
			case 'enableHelper':
				?>
				Helper goes here.
				<?php 
				break;
		}
		die();
	}

}

new dashter_settings;

	
