<script type="text/javascript">

	/* ************************************** THIS IS COPIED FROM USER PROFILE **** */

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
	
	/* *********************************** THIS IS THE JS FOR THIS PAGE *** */
	function blockTag(postID, tagID, tagName){
		var data = {
			action: 'dashter_block_tag',
			postID: postID,
			tagID: tagID
		}	
		jQuery.post(ajaxurl, data, function(response){
			var linkID = '#' + postID + 'tag-' + tagID;
			jQuery(linkID).css('text-decoration', 'line-through');
			var theQuery = jQuery('#'+postID).attr('dquery');		// Get the query
			var theTag = '#' + tagName.toLowerCase();				// Format the tag
			if ( theQuery.indexOf( (' OR ' + theTag), 0 ) > -1 ) {
				theQuery = theQuery.replace((' OR ' + theTag), '');
			}
			if ( theQuery.indexOf( (theTag + ' OR '), 0 ) > -1 ) {
				theQuery = theQuery.replace((theTag + ' OR '), '');
			}	
			if ( theQuery.indexOf( (theTag), 0 ) > -1 ){
				// Query will be empty
				jQuery('#'+postID).slideUp('fast');
			}	
			jQuery('#'+postID).attr('dquery', theQuery);			// Replace the query
			if ( theQuery.indexOf( (theTag), 0 ) == -1 ){
				loadPostReq(postID, true, 1);						// Refresh the cache
			}
		});
	}	
	function addTag(postID){
	
	}
	function refreshAllCaches(){
		var yesRefresh = confirm('Refreshing the caches could take a moment. Keep going?');
		if (yesRefresh == true){
			jQuery('.post-window').each(function(index){
				var theID = jQuery(this).attr('id');
				setTimeout(function(){
					loadPostReq(theID, true, 1);
				}, index*1000);
			});
		}
	}
	function loadWindows(){
		jQuery('.post-window').each(function(index){
			var theID = jQuery(this).attr('id');
			var theQuery = jQuery(this).attr('dquery');
			var theResponse = ('#solo-' + theID);
			console.log('The Query: ' + theQuery + ' The response window: ' + theResponse);
			if (theQuery !== 'none'){
				var data = {
					action: 'dashter_new_stream',
					searchFor: theQuery,
					post_id: theID
				}
				setTimeout(function(){
					jQuery.post(ajaxurl, data, function(response) {
						jQuery(theResponse).html(response);
						if ( (response.indexOf('No results found.', 0)) < 0 ){
							jQuery('#' + theID).fadeIn('fast');
						} else {
							jQuery('#' + theID).fadeIn('slow');
						}	
					});	
				}, index*500);
			}
		});
	}
	function loadPostReq(postID, refreshReq, loadcount){
		console.log('Trying to refresh the cache. On the id: ' + postID);
		var theID = postID;
		var theQuery = jQuery('#' + theID).attr('dquery');
		var theResponse = ('#solo-' + theID);
		if (theQuery !== 'none'){
			if (!refreshReq){
				var data = {
					action: 'dashter_new_stream',
					searchFor: theQuery,
					post_id: theID,
					showNum: loadcount
				}
			} else {
				var data = {
					action: 'dashter_new_stream',
					searchFor: theQuery,
					post_id: theID,
					refCache: 'selected',
					showNum: loadcount
				}
			}
			setTimeout(function(){
				jQuery.post(ajaxurl, data, function(response) {
					jQuery(theResponse).html(response);
					if ( (response.indexOf('No results found.', 0)) < 0 ){
						jQuery('#' + theID).fadeIn('fast');
					} else {
						jQuery('#' + theID).fadeIn('slow');
					}	
				});	
			}, 500);
		}
	}	

	jQuery(document).ready(function($) {

		$('#bulkPostList').hide();
		$('.more-tweets').hide();
		$(loadWindows);
		$('.addTag').hide();
		$('.makebig').click(function(){
			var theElement = $(this).attr('id');
			var theID = $(this).attr('id');
			theID = theID.replace("big-", "#");
			var theMoreID = theID.replace("#", "#more-");
			if ( !$(theID).hasClass('large') ) {
				theQuery = $(this).parent('div').attr('dquery');
				var data = {
					action: 'dashter_new_stream',
					searchFor: theQuery,
					showMore: 'true'
				}
				jQuery.post(ajaxurl, data, function(response) {
					if ( (response.indexOf('No results found.', 0)) < 0 ){
						$(theMoreID).html(response)
					} else {
						$(theMoreID).html('Sorry, there was an error.');
					}
					$(theMoreID).slideDown('slow');
					$('#' + theElement).text('Collapse');
					$(theID).addClass('large');
				});	
				
				console.log('Attempted to change #more-' + theElement);

			} else {
				$(theMoreID).html('').slideUp('slow');
				$('#' + theElement).text('Expand');
				$(theID).removeClass('large');
			}
		});
	});
	// Show addTagToPost box
	function addTagToPost(postDivID){
		var showDiv = '#' + postDivID;
		if ( jQuery(showDiv).is(':visible') ){
			jQuery(showDiv).slideUp('fast');
		} else {
			jQuery(showDiv).slideDown('fast');
		}
	}	
	function showBulkPosts(){
		jQuery('#bulkPostList').slideDown('fast');
	}
</script>