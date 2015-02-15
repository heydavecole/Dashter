<script type="text/javascript">

	function showList(listSlug){
		// console.log('Click to listSelect detected.');
		// Update latest followed tweets section
		jQuery('#latestTweets').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
					action: 'dashter_latesttweets',
					topicAction: 'list',
					listslug: listSlug,
					myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
					oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
					oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>'
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#displayedtweets').text('Showing results for ' + listSlug + ' list.');
			jQuery('#latestTweets').html(response);
			jQuery('#dashter_refreshSearch').attr('href', 'javascript:showList("' + listSlug + '");').fadeIn('slow');
		});
		
		// Update listed users section
		jQuery('#followspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
			action: 'dashter_people',
			followmode: 'list',
			listslug: listSlug,
			// page: pg,
			myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
			oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
			oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>'
			}
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#followspace').html(response);
		});
	}
	function searchTwitter(searchTerm){
		jQuery('#latestTweets').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
			action: 'dashter_search',
			searchFor: searchTerm,
			myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
			oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
			oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>'
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#displayedtweets').text('Search results for ' + searchTerm);
			jQuery('#latestTweets').html(response);
			getSearch(searchTerm);
			jQuery('#dashter_refreshSearch').attr('href', 'javascript:searchTwitter("' + searchTerm + '");').fadeIn('slow');
		});
	}
	function moreSearchResults(searchTerm, nextPage){
		var hideButton = '#moreresults-' + nextPage.toString();
		jQuery(hideButton).hide();
		var data = {
			action: 'dashter_search',
			searchFor: searchTerm,
			myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
			oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
			oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>',
			nextPage: nextPage
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#latestTweets').append(response);
		});
	}
	
	// Friends and followers //
	
	


	
	/* Creating common functions for AJAX interaction */
	// getTweets, getTrending, 
	

	jQuery(document).ready(function($) {
		
		$('#dashter_refreshSearch').hide();
		$('#repto').hide();
		

	
		
		
		

		
		$('#statusresponse').hide();
		// $(getFriendlyTopics); // NOW CALLING GET FRIENDLY TOPICS
		$(peopleIFollow(1)); // Call peopleIFollow on load.
		<?php if(!isset($sterm)){ ?>
			$(getFriendlyTopics);
			$(getTweets(0)); // Call getTweets on load.				
		<?php } else { ?>
			$(getSearch());
			$(searchTwitter('<?php echo $sterm; ?>')); // Search terms from other sources. Preempt getTweets
		<?php } ?>
		
		// setInterval( getTweets, (5*60*1000) ); // Refresh the latest tweets every 5 minutes *** THIS NEEDS TO BE A VAR
		
		// AJAX click links
		$('#refreshTweets').click(function(){
			getTweets(0);
			});
		$('#showFriendTopics').click(getFriendlyTopics);
		$('#showTrending').click(getTrending);
		$('#showLists').click(getLists);
		$('#showFavorites').click(getFavorites);
		$('#showPeopleIFollow').click(function() {
			peopleIFollow(1);
			});
		$('#showPeopleWhoFollow').click(function() {
			peopleWhoFollowMe(1);
			});
		$('#showSearch').click(function(){
			getSearch();
			});
		
		$('#queueTweet').click(queueTweet);
		
		
		$('#publishTweet').click(function(){
			var tweetContent = document.forms['tweet_form'].elements['twitter_message'].value;
			var tweetReplyTo = document.forms['tweet_form'].elements['twitter_message_replyto'].value;
			alert ('The Reply To value is =' + tweetReplyTo);
			// alert ('The twitter message is: ' + twitter_message);
			if (tweetContent.length < 140){
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
						$('#repto').fadeOut('fast', function(){
							$('#repto').text('');
						});
						// Notify user pretty style.
						$('#statusresponse').slideDown('fast').delay(2000).slideUp('slow');
						// Reset the twitter window
						getTweets(0);
						// peopleIFollow(1); // Save a call. 
					}
				);
			} else {
				var mymsg = 'Whoa there! Gotta keep your tweets under 140 characters... Don\'t blame us - the blue bird is running the show here.';
				var msgtype = 'error';
				$('#statusresponse').removeClass('updated');
				$('#statusresponse').removeClass('error');
				$('#statusresponse').addClass(msgtype);
				$('#statusresponse').html('<p>' + mymsg + '</p>');
				$('#twitter_message').focus();
				// Notify user pretty style.
				$('#statusresponse').slideDown('fast').delay(3000).slideUp('slow');
			}
		});
		
		$('.followUser').click(function() {
			$(this).children().each(function() {;
				var kid = $(this);
				var kidID = kid.attr('id');
				$.post('../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
					{ twittername: kidID, myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' });
				
			});
		});
		
	});
</script>