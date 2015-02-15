<script type="text/javascript">

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

	function getFavorites(){
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery('#showFavorites').addClass('nav-tab-active');
		var data = {
					action: 'dashter_favorites',
					showOn: 'dashter_home'
		}	
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#displayedtweets').text('Favorited Tweets');
			jQuery('#displayHotTopic').text('Currently displaying your most recent favorited tweets.');
			jQuery('#latestTweets').html(response);
			jQuery('#dashter_refreshSearch').fadeOut('fast');
		});
	}	

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

	// Friends and followers //
	
	function peopleIFollow(pg){
		jQuery('#followspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
			action: 'dashter_people',
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
	function peopleWhoFollowMe(pg){
		jQuery('#followspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = {
			action: 'dashter_people',
			followmode: 'followers',
			page: pg,
			myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
			oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
			oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>'
			}
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#followspace').html(response);
		});
	}
	function newSearchTwitter(){
		// Called when searcing on the search box
		console.log('New twitter search triggered.');
		var sTerm = jQuery('#twitter_search').val();
		if (sTerm.length == 0){
			alert ('Whoa there. Searching for nothing will get you everything. And that\'s a paradox we just aren\'t equipped for.');
		} else {
			searchTwitter(sTerm);
		}
	}
	function getSearch(search_term){
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery('#showSearch').addClass('nav-tab-active');
		var showsearch = '<label for="twitter_search">Search term:</label> <input type="text" name="twitter_search" id="twitter_search">';
		showsearch = showsearch + '<input type="button" onclick="javascript:newSearchTwitter();" value="Search" class="button-primary">';
		jQuery('#displayHotTopic').html(showsearch);
		var data = {
			action: 'dashter_recent_searches',
			searchTerm: search_term
			}
		jQuery.post(ajaxurl, data, function(response) {		
			jQuery('#displayHotTopic').slideDown('slow').append(response);
			jQuery('#twitter_search').focus();
		});
	}
	
	/* Creating common functions for AJAX interaction */
	// getTweets, getTrending, 
	function getTweets(lastTweetID){
		if (lastTweetID == 0){
			var lastID = 0;
			jQuery('#displayedtweets').text('Latest Followed Tweets');
			jQuery('#latestTweets').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		} else {
			var lastID = new String();
			lastID = lastTweetID;
		}
		
		var data = {
					action: 'dashter_latesttweets',
					myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>',
					oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>',
					oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>',
					lastID: lastID
					}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#dashter_refreshSearch').fadeOut('fast');
			if (lastID == 0){
				// getFriendlyTopics(); // Save a twitter hit
				jQuery('#latestTweets').html(response);
			} else {
				var hideButton = '#btn-' + lastID;
				jQuery(hideButton).hide();
				jQuery('#latestTweets').append(response);
			}
		});
	}
	function getFriendlyTopics(){
		jQuery('#displayHotTopic').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		jQuery('.nav-tab').removeClass('nav-tab-active');
		jQuery('#showFriendTopics').addClass('nav-tab-active');
		jQuery.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
				{ action: 'showfriendtopics', myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
				function(data) {
					jQuery('#displayHotTopic').html(data);
				}
		);
	}
	jQuery(document).ready(function($) {
		
		$('#dashter_refreshSearch').hide();
		$('#repto').hide();
		
		// *** Post Size Counter *** // 
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
	
		function getTrending(){
			$('#displayHotTopic').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
			$('.nav-tab').removeClass('nav-tab-active');
			$('#showTrending').addClass('nav-tab-active');
			$.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
					{ action: 'showtrending', myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
					function(data) {
						$('#displayHotTopic').html(data);
					}
			);
		}
		function getLists(){
			$('#displayHotTopic').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
			$('.nav-tab').removeClass('nav-tab-active');
			$('#showLists').addClass('nav-tab-active');
			$.post(	'../wp-content/plugins/dashter/includes/twitter/twitterajax.php',
					{ action: 'showlists', myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>', oauth_token: '<?php echo get_option('dashter_user_twitter_oauth_token'); ?>', oauth_secret: '<?php echo get_option('dashter_user_twitter_oauth_token_secret'); ?>' },
					function(data) {
						$('#displayHotTopic').html(data);
					}
			);
		}
		

		
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