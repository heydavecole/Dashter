<script type="text/javascript">

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
		action: 'dashter_default_options',
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
		// alert ('The tab is: ' + theTab);
		// AJAX Save call here.
		var data = {
			action: 'dashter_default_options',
			TabID: theTab,
			theMsg: theTweet
		};
		jQuery.post(ajaxurl, data, function(response) {
			$('#default_save_status').slideDown('fast', function(){
				$(this).delay(500).slideUp('slow');
			});
		});
		
		
		});
			
	// keyup tweet // 
	$('.default_tweet_settings').keyup(function(){
		$(drawPreview);
	});
	
});

</script>