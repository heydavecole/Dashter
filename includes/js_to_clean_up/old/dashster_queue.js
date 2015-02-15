<script type="text/javascript">
	function closeReplyRow(rowID){
		var replyRow = '#rep-to-' + rowID;
		if ( jQuery(replyRow).is(':visible') ) {
			jQuery(replyRow).slideUp('fast');
		}
	}
	function showReplyToTweet(tweetID, rowID){
		var replyRow = '#rep-to-' + rowID;
		var preResponse = 'Your tweet was in response to this one: <br/>';
		var postResponse = '<br/><a href="javascript:closeReplyRow(' + rowID + ');">Close This</a> ';
		var data = {
			action: 'dashter_get_single',
			tweetID: tweetID
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery(replyRow).html(preResponse + response + postResponse);
			jQuery(replyRow).slideDown('fast');
		});
	}
	function saveEdit(rowid){
		console.log('Success? ' + rowid);
	}
	jQuery(document).ready(function($){
		$('.replyToContent').hide();
		$('.editTweet').click(function(){
			
			var theItem = $(this).attr('id');
			var theTweet = $('#disp-' + theItem).text();
			var theEditor = ('#edit-disp-' + theItem);
			
			var writeEditor = "<form method='post'><input type='hidden' name='save-edit-id' value='" + theItem + "'><textarea class='row-editor' id='editor-" + theItem + "' name='save-edits'>" + theTweet + "</textarea> <input type='submit' class='row-save-button' id='save-edit-" + theItem + "' value='Save Edit'></form>";
			
			$(theEditor).html(writeEditor);
		});
	});
	
</script>