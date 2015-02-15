/* DASHTER JAVASCRIPT LIBRARY */

// ************************ //
// *** FAVORITE A TWEET *** //
// ************************ //
// Usage: Home, Stream, Rel Mgr
function favTweet(tweetID){
	var data = {	action: 'dashter_favorite_tweet',
					tweetID: tweetID 	}
	jQuery.post(ajaxurl, data, function(response){
		if ( response.indexOf('Success') > -1 ){
			alert ('The tweet was added to your favorites.');
		}
	});
}

// *********************** //
// *** RETWEET A TWEET *** // 
// *********************** //
// Usage: Home, Stream, Rel Mgr
// Requires: #statusresponse (update notification div), #twitter_message (shown textarea)
function reTweet(tweetID){
	var data = {
				action: 'dashter_retweet',
				tweetID: tweetID
	}
	jQuery.post(ajaxurl, data, function(response){
		var myresponse = response.substr(-10);
		if (myresponse == 'retweeted.') {
			var mymsg = 'Score! You\'ve retweeted.';
			var msgtype = 'updated';
		} else {
			var mymsg = 'Retweet failed. Sometimes it\'s just not meant to be.';
			var msgtype = 'error';
		}
		jQuery('#statusresponse').focus();
		jQuery('#statusresponse').removeClass('updated');
		jQuery('#statusresponse').removeClass('error');
		jQuery('#statusresponse').addClass(msgtype);
		jQuery('#statusresponse').html('<p>' + mymsg + '</p>');
		// Notify user pretty style.
		jQuery('#statusresponse').slideDown('fast').delay(2000).slideUp('slow');
	});
}
// ************************ //
// *** REPLY TO A TWEET *** // 
// ************************ //
// Usage: Home, Profile //
// Requires: #twitter_message_replyto (hidden input field), #twitter_message (shown textarea), #repto (reply status div)
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
// *** QUOTE TWEET *** //

function quoteTweet(tweetID){
	var data = {
				action: 'dashter_getTweet',
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
// *********************** // 
// *** CLOSE REPLY ROW *** //
// *********************** //
// Usage: Home, Profile
