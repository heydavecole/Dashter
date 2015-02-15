<?php 

class tweet_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_tweet_box';
	
	function __construct( $title = 'Post To Twitter', $location = 'dashter_column1', $slug = 'tweet_box' ) {
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
	}
	
	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}
	
	public function display_meta_box () {
		?>
		<script type='text/javascript'>
		jQuery(document).ready(function () {
			jQuery('#repto').hide();
			jQuery('#counter').html('140');
			jQuery('#twitter_message').bind('keydown keyup keypress', doCount);
			function doCount(){
				var val = jQuery(this).val(),
				length = 140 - val.length;
				jQuery('#counter').html(length);
				var repTo = jQuery('#twitter_message_replyto').val();
				var repToWindow = jQuery('#repto').text();
				if ( (repTo > 0) && (repToWindow.length == 0) ){
					jQuery('#repto').fadeIn('fast', function(){
						jQuery('#repto').text('Reply');
					});
				}
				if (length == 140){
					jQuery('#twitter_message_replyto').val('');
					jQuery('#repto').fadeOut('fast', function(){
						jQuery('#repto').text('');
					});				
				}
			}
		});
		
		function screenUpdateMessage(msg, msgtype){
			jQuery('#statusresponse').removeClass('updated');
			jQuery('#statusresponse').removeClass('error');
			jQuery('#statusresponse').addClass(msgtype);
			jQuery('#statusresponse').html('<p>' + msg + '</p>');
			if (msgtype != 'error'){
				jQuery('#twitter_message').val('');
				jQuery('#counter').html('140');
				jQuery('#repto').fadeOut('fast', function(){
					jQuery('#repto').text('');
				});
			}
			jQuery('#statusresponse').slideDown('fast').delay(2000).slideUp('slow');
		}
		
		function publishTweet() {
			var tweetContent = jQuery('#twitter_message').val();
			var tweetReplyTo = jQuery('#twitter_message_replyto').val();
			if (tweetContent.length < 140){
				var data = {
					action: '<?php echo $this->ajax_callback; ?>',
					request: 'postTweet',
					twitter_message: tweetContent,
					reply_to: tweetReplyTo
				}
				jQuery.post(ajaxurl, data, function(response){
					if ( (response.indexOf("Tweet Successful.") > -1) ){
						var mymsg = 'You\'re so social! Your tweet was sent: ' + tweetContent;
						var msgtype = 'updated';
					} else {
						var mymsg = 'Uhm. Something went wrong. Drat.';
						var msgtype = 'error';
					}	
					screenUpdateMessage(mymsg, msgtype);
				});
			} else {
				var mymsg = 'Whoa there! Gotta keep your tweets under 140 characters... Don\'t blame us - the blue bird is running the show here.';
				var msgtype = 'error';
				screenUpdateMessage(mymsg, msgtype);
			}
		}
		
		function queueTweet(){
			var tweetContent = jQuery('#twitter_message').val();
			var tweetReplyTo = jQuery('#twitter_message_replyto').val();	
			if (tweetContent.length < 140){
				var data = {
					action: '<?php echo $this->ajax_callback; ?>',
					request: 'dashter_queue',
					twitter_message: tweetContent,
					tweet_replyto: tweetReplyTo
				}
				jQuery.post(ajaxurl, data, function(response) {
					if ( response.indexOf("queued.") > -1 ) {
						var mymsg = 'You\'re so social! Your tweet was queued: ' + tweetContent;
						var msgtype = 'updated';
					} else {
						var mymsg = 'Uhm. Something went wrong. The response was: ' + response;
						var msgtype = 'error';
					}	
					screenUpdateMessage(mymsg, msgtype);
				});				
			} else {
				var mymsg = 'Whoa there! Gotta keep your tweets under 140 characters... Don\'t blame us - the blue bird is running the show here.';
				var msgtype = 'error';
				screenUpdateMessage(mymsg, msgtype);	
			}
		}
		
		function replyToTweet(tweetID,screenName){
			jQuery('#twitter_message_replyto').val(tweetID);
			jQuery('#twitter_message').val('@' + screenName + ' ');
			jQuery('#twitter_message').focus();
			jQuery('#repto').fadeIn('fast', function(){
				jQuery('#repto').text('Reply');
			});
			tweetCount = ( 140 - ( jQuery('#twitter_message').val().length ) );
			jQuery('#counter').html(tweetCount);
		}
		
		function quoteTweet(tweetID){
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_getTweet',
				tweetID: tweetID
			}
			jQuery.post(ajaxurl, data, function(response){
				var theTweet = jQuery.trim(response);
				jQuery('#twitter_message').val('"' + theTweet + '"');
				jQuery('#twitter_message_replyto').val(tweetID);
				jQuery('#repto').fadeIn('fast', function(){
					jQuery('#repto').text('Reply');
				});
				tweetCount = ( 140 - ( jQuery('#twitter_message').val().length ) );
				jQuery('#counter').html(tweetCount);
				jQuery('#twitter_message').focus();
			});
		}
		
		</script>
		
		<div id="statusresponse"></div>

		<input type="hidden" name="action" value="post_tweet">
		<input type="hidden" id="twitter_message_replyto" name="twitter_message_replyto" value="">
		<p><textarea
			id="twitter_message"
			name="twitter_message"
			style="width: 99%; height: 50px;"
			></textarea></p>
		
			<p align="right" style="line-height: 2.5em;">
			<span id="repto" style="background: #00ff00; padding: 0px 3px; border-radius: 3px;"></span> 
			<span id="counter">140</span>
			<a href="Javascript:publishTweet()" class="button-secondary"><img src="../wp-content/plugins/dashter/images/twitter_icon.png" height="12"> Publish Tweet</a>
			<a href="Javascript:queueTweet()" class="button-secondary" id="queueTweet"><img src="../wp-content/plugins/dashter/images/twitter_icon.png" height="12"> Queue Tweet</a></p></p>
			
		<?php
	}
	
	public function process_ajax () {
		
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			case 'postTweet':
				
				$tweet = stripslashes($_POST['twitter_message']); 	// echo $tweet;
				if ( strlen($tweet) > 140 ) {
					$tweet = substr($tweet, 0, 137);
					$tweet .= "...";
				}
				$in_reply_to_id = $_POST['reply_to'];
				if (!empty($in_reply_to_id)){
					$status_value = array( 'status' => $tweet, 'in_reply_to_status_id' => $in_reply_to_id );
				} else {
					$status_value = array( 'status' => $tweet );
				}
				
				$post_tweet = $twitterconn->post('statuses/update', $status_value);
				if ($post_tweet){
					$twitter_post_id = $post_tweet->id_str;
					echo "RepTo: " . $in_reply_to_id . " " . $twitter_post_id . " Tweet Successful.";
				} else	{
					echo 'Uhm... Something went wrong. Dunno what... But something.';
				}
			
				break;
			
			case 'dashter_queue':
				$mysn = get_option('dashter_twitter_screen_name');
				global $wpdb;
				$table_name = $wpdb->prefix . "dashter_queue";
				$tweet = stripslashes($_POST['twitter_message']);
				if ( strlen($tweet) > 140 ) {
					$tweet = substr($tweet, 0, 137);
					$tweet .= "...";
				}
				$replyTo = $_POST['tweet_replyto'];
				
				$sqlInsert = "INSERT INTO $table_name (tweetContent, replyToTweetId, tweetStatus, queueScreenName) VALUES ( %s, %s, %s, %s )";
				$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweet, $replyTo, 'queued', $mysn ) ) ); 
				$success = $wpdb->insert_id;
				if (isset($success)){
					echo "queued.";
				} else {
					echo "failed.";
				}
				
				break;
			
			case 'dashter_getTweet':
			
				$tweetID = $_POST['tweetID'];
				$theTweet = $twitterconn->get('statuses/show/' . $tweetID);
				echo "@" . $theTweet->user->screen_name . " " . $theTweet->text;
			
				break;		
		}
	die();
	}

}