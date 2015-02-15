<?php 

class results_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_results_box';
	
	function __construct( $title = 'Search Results', $location = 'dashter_column2', $slug = 'results_box', $user = null ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
	}
	
	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}
	
	public function display_meta_box () {
		$singleUserView = $this->my_user;
		if ($singleUserView){
		?>
			<script type='text/javascript'>
			jQuery(document).ready(function () {
				getUserTweets( '<?php echo $singleUserView; ?>', 0);
			});
			</script>
		<?php 
		} else {
		
			if ($_REQUEST['sterm']){
			?>
			<script type='text/javascript'>
			jQuery(document).ready(function () {
				searchTwitter('<?php echo $_REQUEST["sterm"]; ?>');
			});
			</script>			
			<?php } else { ?>
			<script type='text/javascript'>
			jQuery(document).ready(function () {
				getTweets(0);
			});
			</script>
		<?php 
			}
		}
		?>
		<script type='text/javascript'>
		function updateTitle( title ){
			jQuery('#results_box').children('h3').children('span').text(title);
		}
		function updateButtons( state, sTerm ){
			if (state == 'home'){
				jQuery('#refreshSearch').fadeOut('fast', function(){
					jQuery('#ments').fadeIn();
					jQuery('#favs').fadeIn();
				});
				jQuery('#refreshHome').text('Refresh');
			} else {
				jQuery('#ments').fadeOut();
				jQuery('#favs').fadeOut();
			}
			if (state == 'search'){
				jQuery('#refreshHome').text('Home');
				jQuery('#refreshSearch').attr("href", "javascript:searchTwitter('" + sTerm + "')");
				jQuery('#refreshSearch').fadeIn();
			}
			if (state == 'list'){
				jQuery('#refreshHome').text('Home');
				jQuery('#refreshSearch').attr("href", "javascript:showList('" + sTerm + "',0)");
				jQuery('#refreshSearch').fadeIn();
			}
			if (state == 'other'){
				jQuery('#refreshSearch').fadeOut();
				jQuery('#refreshHome').text('Home');
			}
		}
		function resultsSpinner(){
			jQuery('#latestTweets').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		}
		function getUserTweets(userName, lastTweetID){
			updateTitle('Recent Tweets from @' + userName);
			if (lastTweetID == 0){
				var lastID = 0;
				resultsSpinner();
			} else {
				var lastID = new String();
				lastID = lastTweetID;
			}
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'dashter_latesttweets',
						topicAction: 'user',
						lastID: lastID,
						screen_name: userName
			}
			jQuery.post(ajaxurl, data, function(response){
				if (lastID == 0){
					jQuery('#latestTweets').html(response);
				} else {
					var hideButton = '#btn-' + lastID;
					jQuery(hideButton).hide();
					jQuery('#latestTweets').append(response);
				}
			});
		}
		
		function getTweets(lastTweetID){
			updateTitle( 'Recent Tweets' );
			updateButtons( 'home', null );
			if (lastTweetID == 0){
				var lastID = 0;
				jQuery('#displayedtweets').text('Latest Followed Tweets');
				resultsSpinner();
			} else {
				var lastID = new String();
				lastID = lastTweetID;
			}
			
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_latesttweets',
				lastID: lastID
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#dashter_refreshSearch').fadeOut('fast');
				if (lastID == 0){
					jQuery('#latestTweets').html(response);
				} else {
					var hideButton = '#btn-' + lastID;
					jQuery(hideButton).hide();
					jQuery('#latestTweets').append(response);
				}
			});
		}
		
		function showReplyToTweet(tweetID, rowID){
			var replyRow = '#rep-to-' + rowID;
			var preResponse = '<i><span style="color: #aaa;">The original tweet:</span></i> <br/>';
			var postResponse = '<br/><a href="javascript:closeReplyRow(\'' + rowID + '\');">Close This</a> ';
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_get_single',
				tweetID: tweetID
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery(replyRow).css('display', 'block');
				jQuery(replyRow).html(preResponse + response + postResponse);
				jQuery(replyRow).slideDown('fast');
			});
		}
		function closeReplyRow(rowID){
			var replyRow = '#rep-to-' + rowID;
			if ( jQuery(replyRow).is(':visible') ) {
				jQuery(replyRow).slideUp('fast');
			}
		}
		function favTweet(tweetID){
			var data = {	
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_favorite_tweet',
				tweetID: tweetID 	
			}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1 ){
					alert ('The tweet was added to your favorites.');
				}
			});
		}
		function reTweet(tweetID){
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'dashter_retweet',
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
		function showList(listSlug, lastTweetID){
			updateTitle ( 'Recent Tweets from ' + listSlug.replace('-',' ') + ' list.' );
			updateButtons ( 'list', listSlug );
			if (lastTweetID == 0){
				var lastID = 0;
				resultsSpinner();
			} else {
				var lastID = new String();
				lastID = lastTweetID;
			}
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'dashter_latesttweets',
						topicAction: 'list',
						lastID: lastID,
						listslug: listSlug
			}
			jQuery.post(ajaxurl, data, function(response){
				if (lastID == 0){
					jQuery('#latestTweets').html(response);
					if ( typeof peopleInList == 'function') {
						peopleInList(1,listSlug);
					} 
				} else {
					var hideButton = '#btn-' + lastID;
					jQuery(hideButton).hide();
					jQuery('#latestTweets').append(response);
				}
			});
		}
		function showFavorites(){
			updateTitle( 'Favorited Tweets' );
			updateButtons( 'other' );
			resultsSpinner();
			var data = { 
						action: '<?php echo $this->ajax_callback; ?>', 
						request: 'dashter_favorites', 
						showOn: 'dashter_home' 
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#latestTweets').html(response);
			});
			return true;
		}
		function showMoreFavorites(pg){
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'dashter_favorites',
						showOn: 'dashter_home',
						pageno: pg
			}
			jQuery.post(ajaxurl, data, function(response){
				var hideButton = '#btn-' + (pg-1);
				jQuery(hideButton).hide();
				jQuery('#latestTweets').append(response);
			});
		}
		function searchTwitter(sTerm){
			if (sTerm.toLowerCase() == '@<?php echo get_option("dashter_twitter_screen_name"); ?>'.toLowerCase()){
				updateTitle( 'Showing Mentions for ' + sTerm );
			} else {
				updateTitle( 'Searching Twitter for ' + sTerm );
			}
			updateButtons( 'search', sTerm );
			resultsSpinner();
			var data = {
						action: '<?php echo $this->ajax_callback; ?>',
						request: 'dashter_search',
						searchFor: sTerm
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#latestTweets').html(response);
			});
				
		}
		function moreSearchResults(searchTerm, nextPage){
			var hideButton = '#moreresults-' + nextPage.toString();
			jQuery(hideButton).hide();
			var data = {
				action:	'<?php echo $this->ajax_callback; ?>',
				request: 'dashter_search',
				searchFor: searchTerm,
				nextPage: nextPage
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#latestTweets').append(response);
			});
		}
		</script>
		<?php if (!$singleUserView){ ?>
		<div id="dashter_controls" style="text-align: right; margin: -22px 25px 10px 0;">
			<a href="javascript:showFavorites()" class="button-secondary" id="favs">&hearts;</a> 
			<a href="javascript:searchTwitter('@<?php echo get_option('dashter_twitter_screen_name'); ?>');" class="button-secondary" id="ments">@</a> 
			<a href="javascript:searchTwitter(null);" class="button-secondary" id="refreshSearch" style="display: none;">Refresh</a>
			<a href="javascript:getTweets(0);" class="button-secondary" id="refreshHome">Refresh</a>
		</div>
		<?php } ?>
		<table class="widefat">
			<tbody id="latestTweets">
			</tbody>
		</table>

		<?php
	}
	
	public function process_ajax () {
	
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
			?>
				<div class="row-actions" style="margin: 0 0 3px;">
					<span>
						<a title="Retweet This" href="Javascript:reTweet('<?php echo $tweetID; ?>')">Retweet</a> | 
						<a title="Reply To This" href="Javascript:replyToTweet('<?php echo $tweetID; ?>','<?php echo $theUser; ?>')">Reply</a> | 
						<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $tweetID; ?>')">Quote</a> | 
						<a href='<?php echo admin_url(); ?>admin.php?page=dashter-recommend-article&sn=<?php echo $theUser; ?>&tweetID=<?php echo $tweetID; ?>&TB_iframe=true&height=350' class='thickbox'>Recommend an Article</a> |  
						<a href='<?php echo admin_url(); ?>admin.php?page=dashter-curate-tweet&tweetID=<?php echo $tweetID; ?>&TB_iframe=true&height=600' class='thickbox' title='Curate this Tweet'>Curate This</a> |
					<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $tweetID; ?>')">Favorite</a>
					</span>
				</div>
				<div id="rep-to-<?php echo $tweetID; ?>" style="display: none; padding: 5px 0 5px 5px; margin: 5px; border-left: solid 1px #ccc; background: #eee; font-size: 8pt; line-height: 12pt;"></div>
			<?php 
		}
	
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			case 'dashter_latesttweets':
			
				$mysn = get_option('dashter_twitter_screen_name');
				$topicAction = $_POST['topicAction'];
				
				$lastID = $_POST['lastID'];
				
				if ($topicAction){
					switch ($topicAction){
						case "list":
							$listSlug = $_POST['listslug'];
							$params = array( 'owner_screen_name' => $mysn, 'slug' => $listSlug, 'include_entities' => '1' );
							if (!empty($lastID) && ($lastID != 0)){
								$params['max_id'] = $lastID;
							}
							$latest = $twitterconn->get('lists/statuses', $params);
							break;
						case "trend":
							$searchTerm = $_POST['searchterm'];
							break;
						case "user":
							$screen_name = $_POST['screen_name'];
							$params = array( 'screen_name' => $screen_name, 'include_entities' => '1' );
							if (!empty($lastID) && ($lastID != 0)){
								$params['max_id'] = $lastID;
							}
							$latest = $twitterconn->get( 'statuses/user_timeline', $params );
					}
				} else {
					$addEntities = array ( 'include_entities' => '1' );
					if (!empty($lastID) && ($lastID != 0)){
						$addEntities['max_id'] = $lastID;
					}
					$latest = $twitterconn->get('statuses/home_timeline', $addEntities);
				}
				if (!empty($latest)){
					foreach ($latest as $tweet){
						$tweetText = $tweet->text;
						
						if ($tweet->entities){
							
							$tweetUrls = $tweet->entities->urls;
							$tweetUserMentions = $tweet->entities->user_mentions;
							$tweetHashtags = $tweet->entities->hashtags;
							if (!empty($tweetUrls)){
								foreach ($tweetUrls as $url){
									$theLink = $url->url;
									$formatLink = "<a href='" . $url->url . "' target='_blank'>" . $url->url . "</a>";
									$tweetText = str_replace($theLink, $formatLink, $tweetText);
								}
							}
							if (!empty($tweetUserMentions)){
								foreach ($tweetUserMentions as $mention){
									$theMention = trim($mention->screen_name);
									$formatMention = "<a href='" . DASHTER_URL . "core/popups/user_details.php?screenname=" . $theMention . "' class='thickbox user-name' title='@" . $theMention . "'>" . $theMention . "</a>";
									$tweetText = str_replace(('@' . $theMention), ('@' . $formatMention), $tweetText);
								}
							}
							if (!empty($tweetHashtags)){
								foreach ($tweetHashtags as $hashtag){
									$theHashtag = trim($hashtag->text);
									$formatHashtag = '<a style="cursor: pointer;" title"Search ' . $theHashtag . '" href="admin.php?page=dashter&sterm=%23' . $theHashtag . '">' . $theHashtag . '</a>';
									$tweetText = str_replace(('#' . $theHashtag), ('#' . $formatHashtag), $tweetText);
								}
							}
						}
					
						$theUser = $tweet->user->screen_name;
						$theUserImg = $tweet->user->profile_image_url;
						$inReplyToID = $tweet->in_reply_to_status_id_str;
						$rightnow = date("U");
						$tweettime = date("U", strtotime($tweet->created_at));
						$timeBetween = $twitterconn->timeBetween($tweettime, $rightnow);
						$theTweet = $tweet->text;
						$tweetID = $tweet->id_str;
						$image = $tweet->entities->media[0]->media_url;
						$theNeedle = "@" . $mysn;
						if ( strpos( strtolower($theTweet), strtolower($theNeedle)) !== false ){
							$rowStyle = " style='background-color: #ccffff;' ";
						} else {
							$rowStyle = " ";
						}
						?>
						<tr <?php echo $rowStyle; ?>>
							<?php 
							if ($topicAction != 'user'){
								draw_row( $tweet->user, $timeBetween, $theTweet, $inReplyToID, $tweetID, false, $image ); 
							} else {
								draw_row( $tweet->user, $timeBetween, $theTweet, $inReplyToID, $tweetID, true, $image ); 
							}	
							?>
						</tr>
						<?php 
						$lastID = (string) $tweet->id_str;
						$intDown = (string) strval ( intval ( substr( $lastID, -5 ) ) - 1 );
						if (strlen($intDown) == 4) { $intDown = "9" . $intDown; }
						$lastID = substr ( $lastID, 0, ( strlen($lastID) - 5 ) ) . $intDown;
						
					}
					?>
					<tr>
						<td colspan='3' style="border:0;text-align:center;"><p style="margin:3px 0 5px;">
						<?php if ($topicAction == 'user'){ ?>
						<a href="javascript:getUserTweets('<?php echo $screen_name; ?>', '<?php echo $lastID; ?>');" class="button-secondary" id="btn-<?php echo $lastID; ?>">Load More Tweets</a>
						<?php } elseif ($topicAction == 'list') { ?>
						<a href="javascript:showList('<?php echo $listSlug; ?>', '<?php echo $lastID; ?>');" class="button-secondary" id="btn-<?php echo $lastID; ?>">Load More Tweets</a>
						<?php } else { ?>
						<a href="javascript:getTweets('<?php echo $lastID; ?>');" class="button-secondary" id="btn-<?php echo $lastID; ?>">Load More Tweets</a>
						<?php } ?>
						</p></td>
					</tr>
					<?php 
				} else {
					echo "<tr><td colspan='4'><p align='center'><img src='../wp-content/plugins/dashter/images/dfail.jpg'></p><p align='center'>Twitter service may be down.</p></td></tr>";
				}
				
				break;
			case 'dashter_get_single':
				
				$tweetid = $_POST['tweetID'];
				$singleParams = array ( 	'id'	=>	$tweetid	);
				$singleTweet = $twitterconn->get('statuses/show', $singleParams);
				if (!empty($singleTweet)){
					$screenname = $singleTweet->user->screen_name;
					$profileimg = $singleTweet->user->profile_image_url;
					$tweet = $twitterconn->dashter_parse_tweet($singleTweet->text);
					$posted = date('F d Y g:ia', strtotime($singleTweet->created_at));
					?>
					<a href="<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $screenname; ?>&TB_iframe=true" class='thickbox user-image' title='@<?php echo $screenname; ?>'><img src="<?php echo $profileimg; ?>" align="left" width="32" style="margin: 0 10px 0 0;"></a>
					<b>@<a href="<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $screenname; ?>&TB_iframe=true" class='thickbox user-name' title='@<?php echo $screenname; ?>'><?php echo $screenname; ?></a>:</b> 
					<?php echo $tweet; ?><br/>
					<i><?php echo $posted; ?></i>
					
					<?php 
				} else {
					echo "Twitter may be down, or the tweet may not be archived by Twitter anymore.";
				}
				
				break;
			// FAVORITE A TWEET
			case 'dashter_favorite_tweet':
				
				$tweetID = $_POST['tweetID'];
				
				$favorite = $twitterconn->post('favorites/create/' . $tweetID);
				if (!empty($favorite)){
					echo "Success!";
				}	
				
				break;
				
			// RETWEET A TWEET
			case 'dashter_retweet':
			
				$mysn = $_POST['myscreenname'];
				$tweetID = $_POST['tweetID'];
				if ($twitterconn) {
					$createRT = $twitterconn->post('statuses/retweet/' . $tweetID);
					$RTStatus = $createRT->id;
					if ($RTStatus) {
						echo "Success. Tweet " . $RTStatus . " was retweeted.";
					} else {
						echo "Retweet failed.";
					}
				} else {
					echo "Connection failed.";
				}
				
				break;
				
			 case 'dashter_search' :
				$searchFor = $_POST['searchFor'];
				$mysn = get_option('dashter_twitter_screen_name');
				$nextPage = $_POST['nextPage'];
				
				if (strtolower($searchFor) != strtolower('@' . $mysn)){
					
					$recentSearches = get_option('dashter_recent_searches');
					if (!$recentSearches){
						$recentSearches = array ( $searchFor );
					} else {
						$recentSearches[] = $searchFor;
					}	
					update_option('dashter_recent_searches', $recentSearches);
					
					$searchParams = array (	'q'	=>	$searchFor );
					if (!empty($nextPage)){
						$searchParams['page'] = $nextPage;
					}
					
					$k = 0;
					while (empty($searchResults)){
						$k++;
						$searchResults = $twitterconn->get('search', $searchParams);
						if ($k > 9){ break; } 		// Safety valve at 10 attempts (brute force search)
					}
					$mentionLookup = false;
				} else {
					$mentionLookup = true;
					$searchParams = array( 'count' => 20 );
					if (!empty($nextPage)){
						$searchParams['page'] = $nextPage;
					}
					while (empty($searchResults)){
						$k++;
						$searchResults = $twitterconn->get('statuses/mentions', $searchParams);
						if ($k > 9) { break; }
					}
				}
				if (!empty($searchResults->results)){
					foreach ($searchResults as $resultob){
						if (is_array($resultob)){
							$first = true;
							foreach ($resultob as $sres){
							
								$theUser = array();
								$theUser['screen_name'] = $sres->from_user;
								$theUser['img_url'] = $sres->profile_image_url;
								
								$rightnow = date("U");
								$tweettime = date("U", strtotime($sres->created_at));
								$timeBetween = $twitterconn->timeBetween($tweettime, $rightnow);
								$theTweet = $sres->text;
								$tweetID = $sres->id_str;
								?>
								<tr <?php echo $rowStyle; ?>>
									<?php draw_row( $theUser, $timeBetween, $theTweet, null, $tweetID ); ?>
								</tr>
							<?php 
							}
							?>
							<tr><td colspan="4">
							<?php 
							if (empty($nextPage)){ 
								$nextPage = 2; 
							} else {
								$nextPage++;
							}
							?>
							<p align="center">
							<a href="javascript:moreSearchResults('<?php echo $searchFor; ?>', <?php echo $nextPage; ?>);" id="moreresults-<?php echo $nextPage; ?>" class="button-secondary">
							Load More Results
							</a>
							</p>
							</td></tr>
							<?php 
						}
					}
				} elseif ($mentionLookup) {
					if ( !empty ( $searchResults ) ) {
						$first = true;
						foreach ( $searchResults as $tweet ){
							if ($first){
								if (strtolower($searchFor) == strtolower(("@" . $mysn))) {
									$mentionsLastChecked = $tweet->id_str;
								}
								if (!empty($mentionsLastChecked)){
									update_option('dashter_mentionsLastChecked', $mentionsLastChecked);
								}
								$first = false;
							}
							$theUser = $tweet->user->screen_name;
							$theUserImg = $tweet->user->profile_image_url;
							$inReplyToID = $tweet->in_reply_to_status_id_str;
							$rightnow = date("U");
							$tweettime = date("U", strtotime($tweet->created_at));
							$timeBetween = $twitterconn->timeBetween($tweettime, $rightnow);
							$theTweet = $tweet->text;
							$tweetID = $tweet->id_str;
							$theNeedle = "@" . $mysn;
							$rowStyle = " ";
							?>
							<tr <?php echo $rowStyle; ?>>
								<?php draw_row( $tweet->user, $timeBetween, $theTweet, $inReplyToID, $tweetID, false ); ?>
							</tr>
							<?php 
							$lastID = (string) $tweet->id_str;
							$intDown = (string) strval ( intval ( substr( $lastID, -5 ) ) - 1 );
							if (strlen($intDown) == 4) { $intDown = "9" . $intDown; }
							$lastID = substr ( $lastID, 0, ( strlen($lastID) - 5 ) ) . $intDown;
						}
					} else {
						echo "<tr><td colspan='4' align='center'><p><b>No mentions found.</b></p></td></tr>";
					}
				} else {
					echo "<tr><td colspan='4' align='center'><p><b>No results found.</b></p></td></tr>";
				}
				break;
				
			// ADMIN FAVORITES			
			case 'dashter_favorites':
			
				if ($_POST['showOn'] == 'dashter_home'){
					$dispHome = true;
				} else {
					$dispHome = false;
				}
				
				$favOpt = get_option('dashter_favorites_curation_rule');
				if ($favOpt == 'make_hidden'){
					$oRule = true;
				} else {
					$oRule = false;
				}
			
				$pg = $_POST['pageno'];
				if ($dispHome){
					$favParams = array ( 'count' => 20 );			
				} else {
					$favParams = array ( 'count' => 5 );
				}
				if (!empty($pg)){
					$favParams['page'] = intval($pg);
				}
				
				$favs = $twitterconn->get('favorites', $favParams);
				$noNext = false;
				if (empty($favs)){
					if ($pg == 1){
						echo "<p>You have not favorited any tweets (or Twitter is temporarily down). Once you do, you'll see them here.</p>";
						$noNext = true;
					} else {
						// Go back a page.
						$pg = ($pg - 1);
						$favParams['page'] = intval($pg);
						$noNext = true;
						$favs = $twitterconn->get('favorites', $favParams);
						if (empty($favs)){
							echo "<p>Looks like Twitter is temporarily down. Sorry.</p>";
							$noNext = true;
						}
					}
				}
				if (!empty($favs)){
				
					// Load list of curated tweets & posts.
					$curated_tweets = get_option('dashter_curated_tweets');
				
					$rightnow = date("U");
					if (!$dispHome){
						echo "<p class='sub'>Favorite Tweets</p><table class='widefat'>"; 
					}
					$i=0;
					foreach ($favs as $fav){
						$i++;
						$theUser = $fav->user->screen_name;
						$theUserImg = $fav->user->profile_image_url;
						$rightnow = date("U");
						$tweettime = date("U", strtotime($fav->created_at));
						$timeBetween = $twitterconn->timeBetween($tweettime, $rightnow);
						$theTweet = $fav->text;
						$tweetID = $fav->id_str;
						?>
						<tr <?php echo $rowStyle; ?>>
							<?php draw_row( $fav->user, $timeBetween, $theTweet, null, $tweetID ); ?>
							<td>
							<?php if (($tweetCurated <> "")) { 
								echo "<b>Post <a href='post.php?post=" . $tweetCurated . "&action=edit'>$tweetCurated</a></b>";
								} else {
								echo "&nbsp;";
								}
							?>
							</td>
						</tr>
					<?php 
					}
					if ( $dispHome ){
						if ( $i == 20 ){
						?>
						<tr><td colspan="4">
							<?php 
							if (empty($pg)){ 
								$pg = 2; 
							} else {
								$pg++;
							}
							?>
							<p align="center">i=<?php echo $i; ?>
							<a href="javascript:showMoreFavorites(<?php echo $pg; ?>);" id="btn-<?php echo $pg; ?>" class="button-secondary">
							Load More Results
							</a>
							</p>
							</td></tr>
						<?php 
						} else {
							echo "<tr><td colspan='4'><p align='center'>No more Favorites.</p></td></tr>";
						}
					} else {
						echo "</table>";
						if ($i < 5){
							$noNext = true;
						}
						echo "<p align='center'>";
						if ($pg > 1){
							echo "<span style='float: left;'><a style='cursor: pointer;' href='Javascript:dashter_loadhome(" . ($pg-1) . ");'>« Previous page</a></span>";
						}
						if (!$noNext){
							echo "<span style='float: right;'><a style='cursor: pointer;' href='Javascript:dashter_loadhome(" . ($pg+1) . ");'>Next page »</a></span>";
						}
						echo "</p>";
					}
					echo "<br class='clear' />";
				}
				break;
		}
	die();
	}

}