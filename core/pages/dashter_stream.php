<?php 

class dashter_stream extends dashter_base {

	var $tweet_box;
	var $stream_boxes;
	var $page_title = 'Dashter Social Stream';
	var $menu_title = 'Stream';
	var $menu_slug = 'dashter-stream';
	var $ajax_callback = 'dashter_stream_page';
	
	function __construct() {
	
		$this->tweet_box = new tweet_box;
		$this->stream_boxes = array();
		$args = array(
			'numberposts' => 6
		);
		if ($_REQUEST['cat']){
			$cats = $_REQUEST['cat'];		
			$args['category_name'] = $cats;
		}
		$streamBlacklist = get_option( 'dashter_stream_blacklist' );
		
		$streamAction = $_REQUEST['action'];
		switch ( $streamAction ) {
			case 'favorites':
				$streamFavorites = get_option( 'dashter_stream_favorites' );
				if (is_array($streamFavorites)){
					foreach ($streamFavorites as $postID){
						if (!in_array($postID, $streamBlacklist)){
							$includePosts .= $postID . ",";	
						}
					}
					$includePosts = substr($includePosts, 0, ( strlen($includePosts) - 1 ));
					$args['include'] = $includePosts;
				}
				break;
		}
		// Get black list	
		if ( (is_array($streamBlacklist) ) ){
			$streamBlacklist = array_unique($streamBlacklist);
			foreach ($streamBlacklist as $postID){
				$blackList .= $postID . ",";
			}
			$blackList = substr ( $blackList, 0, ( strlen( $blackList) - 1 ) );
			if ($blackList){
				if ($streamAction != 'show_hidden'){	
					$args['exclude'] = $blackList;
				} else {
					$args['include'] = $blackList;
				}
			}
		}
		
		$myposts = get_posts( $args );
		foreach( $myposts as $post ):
			$this->stream_boxes[] = new stream_box(get_the_title($post->ID), ('dashter_column' . (($i % 2) +1)), $post->ID);
			$i++;
		endforeach;	
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
		add_action( 'admin_menu', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		
	}
	
	function init () {
		
		if ($_GET['page'] == $this->menu_slug) {
			$this->init_scripts();
			$this->tweet_box->init_meta_box();
			foreach($this->stream_boxes as $stream_box){
				$stream_box->init_meta_box();
			}
		}
	}
	
	public function display_page () {
		$this->display_wrap_header( $this->page_title );
		?>
		<script type="text/javascript">
		
		function streamBoxSpinner(boxID){
			jQuery(boxID).html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		}
		function allControls( stateChange ){
			if (stateChange == 'show'){
				jQuery('.stream_controls').slideDown(function(){
					jQuery('.cToggle').each(function(){
						jQuery(this).text('Hide Controls');
					});				
				});

			}	
			if (stateChange == 'hide') {
				jQuery('.stream_controls').slideUp(function(){
					jQuery('.cToggle').each(function(){
						jQuery(this).text('Show Controls');
					});
				});
			}
		}
		function favoritePost(postID){
			var checkedStatus = jQuery('#cbFav_' + postID).attr('checked');
			// alert ('The post at ' + postID + ' has a checked status of: ' + checkedStatus);
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'favPost',
						postID: postID
			}
			jQuery.post(ajaxurl, data, function(response){
				if (response.indexOf('favorites') > -1){
					alert(response);
				}
			});
		}
		function blockPost(postID, boxSlug){
			var confirmBlock = confirm('Are you sure you want to block post ' + postID + '?');
			if (confirmBlock){
				var data = {
							action: '<?php echo $this->ajax_callback; ?>',
							request: 'blockPost',
							postID: postID
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#' + boxSlug).fadeOut();
				});
			} else {
				jQuery('.blockCheckbox').removeAttr('checked');
			}
		}
		function deleteCustomTag(postID, tag){
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'deleteCustomTag',
						postID: postID,
						tag: tag
			}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1){
					tag = tag.replace("$","24");
					tag = tag.replace(' ', '');
					var theSpan = ('#post_' + postID + 'custom_' + tag);
					console.log('Span req: ' + theSpan);
					jQuery(theSpan).fadeOut();
				}
			});	
		}
		function deleteAllCustom(postID){
			var confirmDelete = confirm('Are you sure you want to delete the custom tags for post ' + postID + '?');
			if (confirmDelete){
				var data = {
							action: '<?php echo $this->ajax_callback; ?>',
							request: 'deleteCustom',
							postID: postID
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#custom_tags_div_' + postID).html("<b>Custom Tags</b> <span id='noCustomTags_" + postID + "'>This post has no custom search tags.</span>");
				});	
			} else {
				jQuery('.cbAllCustom').removeAttr('checked');
			}	
		}
		
		function refreshResults(postID, loadCount){
			var resultsBoxID = '#results_' + postID;
			streamBoxSpinner(resultsBoxID);
			var rdata = {
				action: 'dashter_stream_box_' + postID,
				request: 'results',
				refresh: 'selected',
				loadCount: loadCount
			}
			jQuery.post(ajaxurl, rdata, function(response){
				jQuery(resultsBoxID).html(response);
			});
		}
		
		function saveNewTag(postID){
			var newTagContents = jQuery('#custom_tag_' + postID).val();
			if (newTagContents){
				var data = { 	action: '<?php echo $this->ajax_callback; ?>',
								request: 'CreateCustomTag',
								postID: postID,
								customTag: newTagContents
				}
				jQuery.post(ajaxurl, data, function(response){
					if ( jQuery('#noCustomTags_' + postID).is(':visible') ){
						jQuery('#noCustomTags_' + postID).hide();
						
					}
					jQuery('#custom_tags_div_' + postID).html("<span style='float: right;'> Delete All Custom <input type='checkbox' class='cbAllCustom' onclick='javascript:deleteAllCustom(" + postID + ");'></span>");
					jQuery('#custom_tags_div_' + postID).append('<b>Custom Tags</b> ' + response);
					jQuery('#custom_tag_' + postID).val('');
				});
			} else {
				alert ('Please enter a valid tag');
			}
		}
		
		function toggleTag(postID, tagID){
			var myElement = '#post_' + postID + 'tagid_' + tagID;
			var data = {	action: '<?php echo $this->ajax_callback; ?>',
							request: 'toggleTag',
							postID: postID,
							tagID: tagID
			}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1 ) {
					if ( jQuery(myElement).css('text-decoration') == 'line-through') {
						jQuery(myElement).css('text-decoration', 'none');
					} else {
						jQuery(myElement).css('text-decoration', 'line-through');
					}
				} else {
					alert ('Something went wrong');
				}
			});
		}	
		function toggleAllTags(postID){
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							request: 'blockAllTags',
							postID: postID
						}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1 ) {
					jQuery('#stream_tags_' + postID).children('span.stream_tag').children('span').each(function(){
						jQuery(this).css('text-decoration', 'line-through');					
					});	
				}
			});
		}	
		function showControls(boxID){
			var myControl = '#controls_' + boxID;
			if ( jQuery(myControl).is(':hidden') ){
				jQuery(myControl).slideDown();
				jQuery('#controlToggle_' + boxID).text('Hide Controls');
			} else {
				jQuery(myControl).slideUp();
				jQuery('#controlToggle_' + boxID).text('Show Controls');
			}
		}
		function manageHidden(){
			if ( jQuery('#hiddenPosts').is(':visible') ){
				jQuery('#hiddenPosts').slideUp();
			} else {
				var data = { 	action: '<?php echo $this->ajax_callback; ?>',
								request: 'hiddenPosts'
							}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#hiddenPosts').html(response).slideDown();
				});
			}
		}
		function unHidePost(postID){
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							request: 'unBlockPost',
							postID: postID
			}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1 ){
					jQuery('#hiddenPost_' + postID).fadeOut();
				}
			});
		}
		function loadMore(postID, loadCount){
			var resultsBoxID = '#results_' + postID;
			if ( loadCount > 1 ){
				jQuery('#collapseResults_' + postID).slideDown();
			} else {
				jQuery('#collapseResults_' + postID).slideUp();
			}
			if ( jQuery('#refreshPost_' + postID).is(':checked') ){
				streamBoxSpinner(resultsBoxID);
				var data = {
							action: 'dashter_stream_box_' + postID,
							request: 'results',
							refresh: 'selected',
							loadCount: loadCount
				}
			} else {
				var data = {
							action: 'dashter_stream_box_' + postID,
							request: 'results',
							loadCount: loadCount
				}
			}
			
			jQuery.post(ajaxurl, data, function(response){
				jQuery(resultsBoxID).slideDown('slow', function(){
					jQuery('#refreshPost_' + postID).removeAttr('checked');
					jQuery(resultsBoxID).html(response);
				});
			});	
		}
		
		jQuery(document).ready(function($){
			
		});
		</script>
		<div style="text-align: right; width: 98%; margin: 0 0 10px 0;">
		<a href="javascript:allControls('show');" class="button-secondary">Show All Controls</a> 
		<a href="javascript:allControls('hide');" class="button-secondary">Hide All Controls</a>
		<span style="float: left;">
		<?php if ($_REQUEST['cat']) { ?>
			<a href="javascript:document.forms['normalView'].submit()" class="button-secondary">Recent Posts</a>
		<?php } ?>
		<?php if ($_REQUEST['action'] == 'favorites'){ ?>
			<a href="javascript:document.forms['normalView'].submit()" class="button-secondary">Recent Posts</a>
		<?php } else { ?>
			<a href="javascript:document.forms['showFavs'].submit()" class="button-secondary">Show Favorites</a>
		<?php } ?>
		
		<a href="javascript:manageHidden()" class="button-secondary">Unhide Posts</a>
		
		<?php 
		if ($_REQUEST['cat']) {
			echo "<b>Filtering by Category </b> " . $_REQUEST['cat'];
		} elseif ($_REQUEST['action'] == 'favorites') {
			echo "<b>Filtering by Favorites</b> ";
		}			
		?>
		</span>
		</div>
		<div id="hiddenPosts" style="width: 98%; display: none; border: solid 1px #ccc; padding: 5px 10px; margin: 5px 0;"></div>
		<?php 
		$this->display_dashboard(2);
		?>
		<form method="GET" action="admin.php" id="showFavs"><input type="hidden" name="page" value="dashter-stream"><input type="hidden" name="action" value="favorites"></form>
		<form method="GET" action="admin.php" id="normalView"><input type="hidden" name="page" value="dashter-stream"></form>
		<?php 
		$this->display_wrap_footer();
	}

	function process_ajax() {
		$request = $_POST['request'];
		
		switch ($request) {
			case 'hiddenPosts':
				?>
				<p>Restore posts that are hidden in the stream (will be displayed next time you reload the page):</p>
				<p style="line-height: 2em;">
				<?php 
					$blackList = get_option( 'dashter_stream_blacklist' );
					if (is_array($blackList) && (!empty($blackList))){
						$blackList = array_unique($blackList);
						foreach ($blackList as $postID){
							$hiddenPosts .= $postID . ",";
						}							
						$hiddenPosts = substr( $hiddenPosts, 0, ( strlen($hiddenPosts) - 1 ) );
						$args = array();
						$args['numberposts'] = -1;
						$args['include'] = $hiddenPosts;
						$getBlackListed = get_posts( $args );
						if ( $getBlackListed ) {
							foreach ( $getBlackListed as $post ){
								?>
								<span id="hiddenPost_<?php echo $post->ID; ?>" style="white-space: nowrap;">
								<input type="checkbox" onclick="javascript:unHidePost(<?php echo $post->ID; ?>)"> 
								<?php echo $post->post_title; ?> &nbsp;
								</span>
								<?php 
							}
						}
					} else {
						echo "No posts are hidden.";
					}
				?>
				</p>
				<p align="right"><a href="javascript:manageHidden();" class="button-secondary">Close This</a></p>
				<?php 
				break;
		
			case 'CreateCustomTag':
				$postID = $_POST['postID'];
				$customTag = $_POST['customTag'];
				if ($postID && $customTag){
					// Update meta option
					add_post_meta( $postID, '_dashter_custom_search_tags', $customTag);
					$theCustomTags = get_post_meta( $postID, '_dashter_custom_search_tags', false);
					// Return formatted
					foreach ($theCustomTags as $tag){
						$spanTag = str_replace("$", "24", $tag);
						echo "<span class='stream_tag' id='post_" . $postID . "custom_" . trim(str_replace(" ", "", $spanTag)) . "'>" . $tag . " <a href='javascript:deleteCustomTag(" . $postID . ",\"" . $tag . "\")'><b>X</b></a> </span>";
					}
				}
				break;
			case 'deleteCustomTag':
				$postID = $_POST['postID'];
				$customTag = $_POST['tag'];
				if ($postID && $customTag) {
					delete_post_meta ( $postID, '_dashter_custom_search_tags', $customTag );					
					echo "Success.";
				}
				break;
			case 'deleteCustom':
				$postID = intval($_POST['postID']);
				if (is_int($postID) && ($postID > 0)){
					delete_post_meta ( $postID, '_dashter_custom_search_tags');
				}	
				break;
			case 'blockPost':
				$postID = intval($_POST['postID']);
				if (is_int($postID) && ($postID > 0)){
					$blackList = get_option('dashter_stream_blacklist');
					$blackList[] = $postID;
					array_unique($blackList);
					update_option('dashter_stream_blacklist', $blackList);
				}
				break;
			case 'unBlockPost':
				$postID = intval($_POST['postID']);
				if (is_int($postID) && ($postID > 0)){
					$blackList = get_option('dashter_stream_blacklist');
					foreach ($blackList as $bKey => $blackListID) {
						if ($blackListID == $postID){
							unset($blackList[$bKey]);
						}
					}
					update_option('dashter_stream_blacklist', $blackList);
				}
				echo "Success.";
				break;
			case 'favPost':
				$postID = intval($_POST['postID']);
				if (is_int($postID) && ($postID > 0)){
					$favoritesList = get_option('dashter_stream_favorites');
					if (is_array($favoritesList)){
						if (in_array($postID, $favoritesList)){
							$remPost = array_search($postID, $favoritesList);
							unset( $favoritesList[$remPost] );
							$favoritesList = array_values( $favoritesList );
							echo "Post $postID has been removed from favorites.";
						} else {
							$favoritesList[] = $postID;
							echo "Post $postID has been added to favorites.";
						}
					} else {
						$favoritesList[] = $postID;
						echo "Post $postID has been added to favorites.";
					}
					update_option('dashter_stream_favorites', $favoritesList);
				}
				break;
			case 'toggleTag':
				$postID = intval($_POST['postID']);
				$tagID = intval($_POST['tagID']);
				$blockedTags = get_post_meta( $postID, '_dashter_stream_blocked_tags', false );
				if ( in_array( $tagID, $blockedTags ) ){
					delete_post_meta( $postID, '_dashter_stream_blocked_tags', $tagID );
				} else {
					add_post_meta( $postID, '_dashter_stream_blocked_tags', $tagID );
				}
				echo "Success";
				break;
			case 'blockAllTags':
				$postID = intval($_POST['postID']);
				if (is_int($postID) && ($postID > 0)){
					$thePostTags = get_the_tags( $postID );
					if ($thePostTags){
						delete_post_meta( $postID, '_dashter_stream_blocked_tags' );
						foreach ($thePostTags as $tag){
							add_post_meta( $postID, '_dashter_stream_blocked_tags', $tag->term_id );
						}
						echo "Success.";
					}
				}	
				break;
		}
	
		die();	
	}

}

new dashter_stream;
