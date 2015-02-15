<?php 

class stream_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $post_id;
	var $ajax_callback;
	
	function __construct( $title = 'Stream Result', $location = 'dashter_column1', $pid, $slug = 'stream_box' ) {
		$this->box_slug = $slug . "-" . sanitize_title_with_dashes($title);
		$this->box_title = $title;
		$this->box_location = $location;
		$this->post_id = $pid;
		$this->ajax_callback = "dashter_stream_box_" . $pid; // This is called directly by the ajax call on stream page. Do not rename.
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
	}
	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function process_ajax(){
		function draw_row( $theUser, $timeBetween, $theTweet, $reply_to_id = null, $tweetID , $hideProfile = false, $tweetImage = null ){
			global $twitterconn;
			if (is_object($theUser)) $theUser = (array) $theUser;
			if (!$hideProfile){
			?>
				<td width="72">
					<?php $twitterconn->display_user($theUser, 'small', false); ?>
				</td>
			<?php } ?>
			
			<td width="*">
				<?php if ($tweetImage){ ?>
				<a href="<?php echo $tweetImage; ?>" title="<?php echo str_replace("\"", "'", $theTweet); ?>" class="thickbox"><img src="<?php echo $tweetImage; ?>:thumb" class="userImageDisplay displayRight" width="75"></a>
				<?php } ?>
				<?php if (!$hideProfile) { ?>
				<p><?php echo $twitterconn->display_username( $theUser ); ?></p>
				<?php } ?>
				<p><?php echo $twitterconn->dashter_parse_tweet( $theTweet ); ?></p>
				<p style="color: #aaa;"><?php echo $timeBetween; ?> ago.
					<span style="float: right;">
					<?php if (!empty($reply_to_id)){ echo "<a href='javascript:showReplyToTweet(\"" . $reply_to_id . "\", \"" . $tweetID . "\");'>In reply to...</a>"; } ?>
					</span>
				</p>
				
				<?php draw_tweet_actions( $tweetID, $theUser['screen_name'] ); ?>
			</td>
			<?php 
		}
	
		function draw_tweet_actions( $tweetID, $theUser ) {
			$postID = $_POST['postID'];
			?>
				<div class="row-actions" style="margin: 0 0 3px;">
					<span>
						<a title="Retweet This" href="Javascript:reTweet('<?php echo $tweetID; ?>')">Retweet</a> | 
						<a title="Reply To This" href="Javascript:replyToTweet('<?php echo $tweetID; ?>','<?php echo $theUser; ?>')">Reply</a> | 
						<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $tweetID; ?>')">Quote</a> | 
						<a title="Recommend an article in response" href="<?php echo admin_url(); ?>admin.php?page=dashter-recommend-article&sn=<?php echo $theUser; ?>&tweetID=<?php echo $tweetID; ?>&postID=<?php echo $postID; ?>&TB_iframe=true&height=350" class="thickbox">Recommend an Article</a> | 
						<a href='<?php echo admin_url(); ?>admin.php?page=dashter-curate-tweet&tweetID=<?php echo $tweetID; ?>&TB_iframe=true&height=600' class='thickbox' title='Curate this Tweet'>Curate This</a> |
					<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $tweetID; ?>')">Favorite</a>
					</span>
				</div>
				<div id="rep-to-<?php echo $tweetID; ?>" style="display: none; padding: 5px 0 5px 5px; margin: 5px; border-left: solid 1px #ccc; background: #eee; font-size: 8pt; line-height: 12pt;"></div>
			<?php 
		}
	
		global $wpdb;
		global $twitterconn;
		$twitterconn->init();
		
		$request = $_POST['request'];
		switch ($request){
			case 'controls':
				$postInfoArgs = array ( 'numberposts' => 1, 'include' => $this->post_id );
				$postInfo = get_posts( $postInfoArgs );
				$theCats = get_the_category ( $this->post_id );
				if ($theCats) {
					foreach ($theCats as $cat) {
						$aCats[] = array ( 	'id'	=>	$cat->cat_ID,
											'slug'	=>	$cat->category_nicename,
											'name'	=>	$cat->cat_name
						);				
					}
				}
				$postPubDate = date ( 'M jS, Y g:ia', strtotime( $postInfo[0]->post_date ) );
				$streamFavorites = get_option( 'dashter_stream_favorites' );
				if (!is_array($streamFavorites)) { $streamFavorites = array(); }
				?>
				<div style='text-align: right; font-size: 8pt;'>
				<span style='float: left;'><input type='checkbox' class='favoriteCheckbox' id='cbFav_" . $this->post_id . "' onclick='javascript:favoritePost(<?php echo $this->post_id; ?>)' <?php checked( in_array( $this->post_id, $streamFavorites ), true ); ?>> Favorite &nbsp; </span>
				<span style='float: left;'><input type='checkbox' class='blockCheckbox' onclick='javascript:blockPost(<?php echo $this->post_id; ?>, "<?php echo $this->box_slug; ?>")'> Hide this Post</span>
				<a class='button-secondary cToggle' id='controlToggle_<?php echo $this->post_id; ?>' href='javascript:showControls(<?php echo $this->post_id; ?>)'>Show Controls</a></div>
				<div class='stream_controls' id='controls_<?php echo $this->post_id; ?>'>
				<div class='stream_postdata'>
				Published <?php echo $postPubDate; ?> <b>Categories</b>
				<span class='stream_tag'>
				
				<?php 
				foreach ($aCats as $cat){
					echo " // <a href='admin.php?page=dashter-stream&cat=" . $cat['slug'] . "'>" . $cat['name'] . "</a> ";
				}
				echo "</span>";
				echo "</div>";
				// Get the post tags for search
				$theTags = get_the_tags( $this->post_id );
				if ($theTags){
					foreach ($theTags as $tag){
						$aTags[] = array (	'id'	=>	$tag->term_id,
											'name'	=>	$tag->name,
											'slug'	=>	$tag->slug
						);
					}
				}
				$theBlockedPostTags = get_post_meta( $this->post_id, '_dashter_stream_blocked_tags', false );
				echo "<div class='stream_taglist'  id='stream_tags_" . $this->post_id . "'><b>Tags</b> ";
				echo "<span style='float: right;'> Ignore All Tags <input type='checkbox' onclick='javascript:toggleAllTags(\"" . $this->post_id . "\")'></span>";
				if ($aTags){
					foreach ($aTags as $tag){
						echo "<span class='stream_tag'><span id='post_" . $this->post_id . "tagid_" . $tag['id'] . "'";
						if ( in_array( $tag['id'], $theBlockedPostTags ) ) { echo "style='text-decoration: line-through;'"; }
						echo ">" . $tag['name'] . "</span> <a href='javascript:toggleTag(" . $this->post_id . ", " . $tag['id'] . ");'><b>X</b></a> </span>";
					}
				} else {
					echo "This post has no tags.";
				}
				echo "</div>";
				$theCustomTags = get_post_meta( $this->post_id, '_dashter_custom_search_tags', false );
				
				echo "<div class='stream_taglist' id='custom_tags_div_" . $this->post_id . "'><b>Custom Tags</b> ";
				if (!empty($theCustomTags)) {
					echo "<span style='float: right;'> Delete All Custom <input type='checkbox' class='cbAllCustom' onclick='javascript:deleteAllCustom(" . $this->post_id . ");'></span>";
					foreach ($theCustomTags as $customTag){
						$spanTag = str_replace("$","24",$customTag);
						echo "<span class='stream_tag' id='post_" . $this->post_id . "custom_" . trim(str_replace(" ", "", $spanTag)) . "'>";
						echo $customTag . " <a href='javascript:deleteCustomTag(" . $this->post_id . ",\"" . $customTag . "\")'><b>X</b></a> </span>";
					}
				} else {
					echo "<span id='noCustomTags_" . $this->post_id . "'>This post has no custom search tags.</span>";
				}
				echo "</div>";
				echo "<div class='stream_taglist'>";
				?>
				<span style="float: right;"><a href="javascript:refreshResults(<?php echo $this->post_id; ?>, 1)" class="button-primary">Refresh Results</a></span>
				<label for="post_custom_search_tag">Custom Tag:</label> 
				<input type="text" id="custom_tag_<?php echo $this->post_id; ?>"> 
				<a href="javascript:saveNewTag('<?php echo $this->post_id; ?>')" class="button-secondary">Save</a>
				<?php 
				echo "</div>";
				echo "</div>";
				
				break;
			case 'results':
			
				$thePostTags = get_the_tags ( $this->post_id );
				$theBlockedPostTags = get_post_meta( $this->post_id, '_dashter_stream_blocked_tags', false);
				
				if ( is_array($theBlockedPostTags) && (!empty($theBlockedPostTags)) ) {
					foreach ($thePostTags as $tagKey => $tag){
						if ( in_array( ($tag->term_id), $theBlockedPostTags ) ) {
							unset($thePostTags[$tagKey]);
						}
					}
				}
				
				$theCustomTags = get_post_meta ( $this->post_id , '_dashter_custom_search_tags', false );
				
				if (!empty($theCustomTags)){
					foreach ($theCustomTags as $cTag){
						$searchString .= "\"" . $cTag . "\" OR ";
					}
				}
				$searchString = substr ( $searchString, 0, ( strlen( $searchString) - 4 ) );
				if ($thePostTags){
					foreach ($thePostTags as $tag){
						$searchString .= " OR " . $tag->name . " ";
					}
				}
				
				if (empty($theCustomTags)){ $searchString = substr( $searchString, 4 );	}
				
				$refreshCache = $_POST['refresh']; 				
				$stream_cache = $wpdb->prefix . "dashter_stream_cache";
				$dbOk = false;
				if ($wpdb->get_var("show tables like '$stream_cache'") != $stream_cache){
					$dbOk = false;
				} else {
					$dbOk = true;
				}
				$qGetCached = "SELECT resultid, cachetime, tweetid, screenname, profileimg, tweettext, tweettime FROM $stream_cache WHERE postid = " . intval($this->post_id) . " ORDER BY tweettime DESC";
				$cacheResults = $wpdb->get_results($qGetCached);
				$countResults = $wpdb->query($qGetCached);
				if (!empty($searchString)){
					if ( (empty($cacheResults)) || ($refreshCache == 'selected') ){
						$searchParams = array (	
												'q'	=>	$searchString, 
												'rpp' => 50, 
												'lang' => 'en',
												'result_type' => 'recent' );
						$searchResults = $twitterconn->get( 'search', $searchParams );
						
						if (!empty($searchResults)){
							$cacheTime = date('Y-m-d H:i:s');
							$sqlInsertCache = "INSERT INTO $stream_cache (postid, cachetime, tweetid, screenname, profileimg, tweettext, tweettime) VALUES ";
							// error_log(print_r($searchResults,1) );
							$resultob = $searchResults->results;
								if ($resultob){
									foreach ($resultob as $sres){
										
										$userName = $sres->from_user;
										$userImg = $sres->profile_image_url;
										$tweetTime = date('Y-m-d H:i:s', strtotime($sres->created_at));
										$tweetText = $sres->text;
										$tweetID = $sres->id_str; 	
										$aResults[] = array (	'cachetime'		=>		$cacheTime,
					 											'tweetid'		=>		$tweetID,
					 											'screenname'	=>		$userName,
					 											'profileimg'	=>		$userImg,
					 											'tweettext'		=>		$tweetText,
					 											'tweettime'		=>		$tweetTime ); 
										$sqlInsertCache .= "(" .  $this->post_id . ", '" . $cacheTime . "', '" . $tweetID . "',";
										$sqlInsertCache .= "'" . addslashes($userName) . "', '" . $userImg . "', '" . addslashes($tweetText) . "', '" . $tweetTime . "'),";
									}
								} else {
									$noSQL = true;
								}
							
							
							$sqlInsertCache = substr($sqlInsertCache, 0, (strlen($sqlInsertCache) - 1));
							$sqlInsertCache .= ";";
							if ($refreshCache == 'selected'){
								// Delete the existing cache before updating
								$sqlDeleteCache = "DELETE FROM $stream_cache WHERE postid = " . $this->post_id;
								$delete = $wpdb->query($sqlDeleteCache);
							}
							if (!$noSQL){
								$insertSuccess = $wpdb->query($sqlInsertCache);
							}
							if ($insertSuccess){
								
							} else {
								echo "These results were not stored in the cache, something is configured wrong in the database. Check that you have the latest version of Dashter installed.";
							}
						}
					} else {
						$aResults = array();
						foreach ($cacheResults as $row){
		 					$aResults[] = array (	'cachetime'		=>		$row->cachetime,
		 											'tweetid'		=>		$row->tweetid,
		 											'screenname'	=>		$row->screenname,
		 											'profileimg'	=>		$row->profileimg,
		 											'tweettext'		=>		$row->tweettext,
		 											'tweettime'		=>		$row->tweettime ); 						
						}
					}
					
					echo "<table class='widefat'><tbody id='latestTweets'>";
					$rightnow = date("U");
					$loadCount = intval($_POST['loadCount']);
					if ($loadCount == 0){ 
						$loadCount = 2; 
					} else {
						$loadCount++;
					}
					if ($aResults){
						foreach ($aResults as $row){
							$loadCount--;
							if ($loadCount == 0){ break; }
							$theUser = array();
							$theUser['screen_name'] = $row['screenname'];
							$theUser['profile_image_url'] = $row['profileimg'];
							$tweettime = date("U", strtotime($row['tweettime']));
							$timeBetween = $twitterconn->timeBetween($tweettime, $rightnow);
							$theTweet = $row['tweettext'];
							$tweetID = $row['tweetid'];
							?>
							<tr <?php echo $rowStyle; ?>>
								<?php draw_row( $theUser, $timeBetween, $theTweet, null, $tweetID ); ?>
							</tr>
							<?php
						}
						?>
						
						<?php 
					} else { 
						echo "<tr><td>RESULTS ARE EMPTY.</td></tr>";
					}
				} else {
					echo "<tr><td>Nothing to search. Unblock post tags or create a custom tag, and try again.</td></tr>";
				}
				echo "</tbody></table>";
				
				break;
		}
		die();
	}

	public function display_meta_box () {
		?>
		
		<script type="text/javascript">
		
		jQuery(document).ready(function($){
			var boxID = '#stream_<?php echo $this->post_id; ?>';
			$(streamBoxSpinner(boxID));
			var data = { 
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'controls' 
			}
			$.post(ajaxurl, data, function(response){
				$(boxID).html(response);
				
				var resultsBoxID = '#results_<?php echo $this->post_id; ?>';
				$(streamBoxSpinner(resultsBoxID));
				
				var rdata = {
					action: '<?php echo $this->ajax_callback; ?>',
					request: 'results',
					postID: '<?php echo $this->post_id; ?>'
				}
				
				$.post(ajaxurl, rdata, function(response){
					$(resultsBoxID).html(response);
				});
			});
		});
		</script>
		<div class="postbox_stream" id="stream_<?php echo $this->post_id; ?>"></div>
		<div class="postbox_results" style="line-height: 1.25em; padding: 5px; border: solid 1px #ccc;" id="interests_<?php echo $this->post_id; ?>">
			<?php
			$thePostTags = get_the_tags ( $this->post_id );
			$theInterests = get_option('dashter_user_interests');
			if (!is_array($theInterests)) { $theInterests = array(); } 
			if ($thePostTags && !empty($theInterests)){		
				foreach ($thePostTags as $termID => $tagObj){
					foreach ($theInterests as $interestedUser => $termArr){
						if ( in_array ( $termID, $termArr ) ){
							$interestedPeople[] = $interestedUser;
						}
					}
				}
				echo "Recommend this to ";
				if (is_array($interestedPeople)){
					$interestedPeople = array_unique($interestedPeople);
					foreach ($interestedPeople as $screen_name){
						?>
						// <span style="white-space: nowrap;"><b><a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $screen_name; ?>&TB_iframe=true' class='thickbox user-name' title='@<?php echo $screen_name; ?>'>@<?php echo $screen_name; ?></a></b>
				(<a href="<?php admin_url(); ?>?page=dashter-users&user=<?php echo $screen_name; ?>">Full</a>) - <a href="<?php echo admin_url(); ?>admin.php?page=dashter-recommend-article&sn=<?php echo $screen_name; ?>&postID=<?php echo $this->post_id; ?>&TB_iframe=true&height=350" class="thickbox">Recommend</a></span>
						<?php
					}
				} else {
				?>
					<script type="text/javascript">
					jQuery('#interests_<?php echo $this->post_id; ?>').hide();
					</script>
				<?php 
				}
			} else {
				?>
				<script type="text/javascript">
				jQuery('#interests_<?php echo $this->post_id; ?>').hide();
				</script>
				<?php 
			}
			?>
		</div>
		<div class="postbox_results" id="collapseResults_<?php echo $this->post_id; ?>" style="cursor: pointer; display: none; background: #ddd; text-align: center;"
		onclick="javascript:loadMore(<?php echo $this->post_id; ?>, 1);">
			<a>Collapse Results</a>
		</div>
		<div class="postbox_results" id="results_<?php echo $this->post_id; ?>"></div>
		<div class="postbox_moreResults" id="moreResults_<?php echo $this->post_id; ?>">
			<table class="widefat">
				<tr>
					<td colspan="3" style="background: #ddd; text-align: center;">
					<a href="javascript:loadMore(<?php echo $this->post_id; ?>, 1);">Show 1</a> | 
					<a href="javascript:loadMore(<?php echo $this->post_id; ?>, 5);">Show 5</a> | 
					<a href="javascript:loadMore(<?php echo $this->post_id; ?>, 20);">Show 20</a> | 
					<a href="javascript:loadMore(<?php echo $this->post_id; ?>, 50);">Show All</a> | 
					Refresh Results <input type="checkbox" id="refreshPost_<?php echo $this->post_id; ?>" checked="checked">
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
?>