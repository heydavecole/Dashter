<script type="text/javascript">

	

	function getUserDetail(screen_name){
			jQuery.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
							{ action: 'getUserDetails', screenname: screen_name, myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
							function(data) {
								// dialog box
								var $dialog = jQuery('<div></div>')
									.addClass('myModal')
									.html (data)
									.dialog({
										autoOpen: false,
										title: '@' + screen_name,
										height: 'auto',
										width: 550,
										modal: true
									});
								$dialog.dialog('open');
							}
			);
		}
	function modalFollowUser(screen_name){		// Follow user from user popup
		if ( jQuery('.myModal').is('open') ){
			jQuery('.myModal').dialog('close');
		}
		jQuery.post(
				'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
				{ action: 'followuser', twittername: screen_name, myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
				function (data) {
					var myresponse = data.substr(-19);
					if (myresponse == 'Friendship created.'){
						var mymsg = 'Successfully followed @' + screen_name;
						jQuery('#followbutton').slideUp('fast');
			
						var myRelationship = '<img src="../wp-content/plugins/dashter/images/user_green.png" width="64"><img src="../wp-content/plugins/dashter/images/rel-following.png" width="64"><img src="../wp-content/plugins/dashter/images/user_red.png" width="64"><br/>You Follow @' + screen_name + '. They do not follow you.';
			
						jQuery('#rel_space').html(myRelationship);
					} else {
						var mymsg = 'You are already friends with @' + screen_name;
					}	
					// alert (mymsg);
				}
		);
		
	}
	function peopleIFollow(pg){
		jQuery('#followspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
			action: 'dashter_people',
			imgsize: '73',
			followmode: 'friends',
			page: pg,
			myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
			oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
			oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>'
			}
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#followspace').html(response);
		});
	}
	function destroyTag(id){
		jQuery('#tag-' + id).remove();
	}	
	
	
	function favTweet(tweetID){
		var data = {	action: 'dashter_favorite_tweet',
						tweetID: tweetID 	}
		jQuery.post(ajaxurl, data, function(response){
			if ( response.indexOf('Success') > -1 ){
				alert ('The tweet was added to your favorites.');
			}
		});
	}
	function queueTweet(){
		var tweetContent = document.forms['tweet_form'].elements['twitter_message'].value;
		var tweetReplyTo = document.forms['tweet_form'].elements['twitter_message_replyto'].value;
		var data = {
			action: 'dashter_queue',
			twitter_message: tweetContent,
			tweet_replyto: tweetReplyTo
			}
			jQuery.post(ajaxurl, data, function(response) {
				if (jQuery.trim(response) == 'queued.'){
					var mymsg = 'You\'re so social! Your tweet was queued: ' + tweetContent;
					var msgtype = 'updated';
				} else {
					var mymsg = 'Uhm. Something went wrong. The response was: ' + response;
					var msgtype = 'error';
				}	
				jQuery('#statusresponse').removeClass('updated');
				jQuery('#statusresponse').removeClass('error');
				jQuery('#statusresponse').addClass(msgtype);
				jQuery('#statusresponse').html('<p>' + mymsg + '</p>');
				// Clear tweet window
				jQuery('#twitter_message').val('');
				jQuery('#repto').fadeOut('fast', function(){
					jQuery('#repto').text('');
				});
				// Notify user pretty style.
				jQuery('#statusresponse').slideDown('fast').delay(2000).slideUp('slow');
				
			});				
	}
	function showReplyToTweet(tweetID, rowID){
		var replyRow = '#rep-to-' + rowID;
		var preResponse = '<i><span style="color: #aaa;">The original tweet:</span></i> <br/>';
		var postResponse = '<br/><a href="javascript:closeReplyRow(\'' + rowID + '\');">Close This</a> ';
		var data = {
			action: 'dashter_get_single',
			tweetID: tweetID
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery(replyRow).css('display', 'block');
			jQuery(replyRow).html(preResponse + response + postResponse);
			jQuery(replyRow).slideDown('fast');
		});
	}
	jQuery(document).ready(function($) {
		$('#statusresponse').hide();	// Hide the status response
		$('#lists_save_status').hide(); // Hide save notice
		$('#repto').hide();				// Hide reply to
		var tweetCount = 140;
		$('#counter').html(tweetCount);
		$('#twitter_message').keyup(function(){
			tweetCount = ( 140 - ( $('#twitter_message').val().length ) );
			$('#counter').html(tweetCount);
			var repTo = $('#twitter_message_replyto').val();
			var repToWindow = $('#repto').text();
			if ( (repTo > 0) && (repToWindow.length == 0) ){
				$('#repto').fadeIn('fast', function(){
					$('#repto').text('Reply');
				});
			}	
			if (tweetCount == 140){
				$('#twitter_message_replyto').val('false');
				$('#repto').fadeOut('fast', function(){
					$('#repto').text('');
				});				
			}	
		});
		$('#add_mention').click(function(){
			var themsg = $('#twitter_message').val();
			themsg = themsg + '@<?php echo $searchUser; ?>';
			$('#twitter_message').val(themsg);
			$('#twitter_message').focus();
		});
		$('#queueTweet').click(function(){
			$(queueTweet);
		});
		$('#publishTweet').click(function(){
			var tweetContent = document.forms['tweet_form'].elements['twitter_message'].value;
			var tweetReplyTo = document.forms['tweet_form'].elements['twitter_message_replyto'].value;
			alert ('The Reply To value is =' + tweetReplyTo);
			// alert ('The twitter message is: ' + twitter_message);
			
			$.post(
				'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
				{ action: 'postTweet', twitter_message: tweetContent, reply_to: tweetReplyTo, myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
				function (data) {
					var myresponse = data.substr(-17);
					if (myresponse == 'Tweet Successful.'){
						var mymsg = 'You\'re so social! Your tweet was sent: ' + tweetContent;
						var msgtype = 'updated';
					} else {
						var mymsg = 'Uhm. Something went wrong. Drat.';
						var msgtype = 'error';
					}	
					$('#statusresponse').removeClass('updated');
					$('#statusresponse').removeClass('error');
					$('#statusresponse').addClass(msgtype);
					$('#statusresponse').html('<p>' + mymsg + '</p>');
					// Clear tweet window
					$('#twitter_message').val('');
					// Notify user pretty style.
					$('#statusresponse').slideDown('fast').delay(2000).slideUp('slow');
				}
			);
			
		});
		
		$(peopleIFollow(1));
		$(getRecentMentions());
		$('#tagChoice').change(function(){
			var theOption = $('#tagChoice option:selected').attr('value');
			var theOptionName = $('#tagChoice option:selected').attr('name');
			// alert ('You chose: ' + theOption);
			$('#tagspace').append('<div style="padding: 5px 0;" id="tag-' + theOption + '"><input href="Javascript:destroyTag(\'' + theOption + '\');" type="checkbox" name="tagID[]" value="' + theOption + '" checked="checked"> ' + theOptionName + '</div>');
		});
		$('.listcheckbox').click(function(){
			var cssGreen = {
				'color' : 'green',
				'font-weight' : 'bold'
			}	
			var cssClear = {
				'color' : '',
				'font-weight' : ''
			}
			
			var listBoxSlug = $(this).attr('id');
			var listBoxState = $(this).attr('checked');
			if (listBoxState){
				
				// Add to list.
				$.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
						{ action: 'addToList', listSlug: listBoxSlug, username: '<?php echo $searchUser; ?>', myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
						function(data) {
							$(this).parent('div').css(cssGreen);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).delay(500).slideUp('slow');
							});
							console.log('The box is ' + listBoxSlug + ' and it is ' + listBoxState);
						}
				);
			} else {
				// Remove from list.
			
				$.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
						{ action: 'addToList', remList: true, listSlug: listBoxSlug, username: '<?php echo $searchUser; ?>', myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
						function(data) {
							$(this).parent('div').css(cssClear);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).delay(500).slideUp('slow');
							});
							console.log('The box is ' + listBoxSlug + ' and it is ' + listBoxState);
						}
				);
				
			}	
			
		});
	});

</script>