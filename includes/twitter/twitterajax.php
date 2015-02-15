<?php 
	function dashter_parse_curatedTweet($tweet){
		$tweet = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@','<a href="$1" target="_new">$1</a>',$tweet);
		return $tweet;
	}
	
	function display_user($person) {
		$img = $person['img_url'];
		$img = str_replace('_normal', '_bigger', $img);
		?>
		<div class="dashter_user">
			<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $person['screen_name']; ?>' class='thickbox user-image' title='@<?php echo $person['screen_name']; ?>'><img src="<?php echo $img; ?>" width="73" height="73" /></a>
			<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $person['screen_name']; ?>' class='thickbox user-name' title='@<?php echo $person['screen_name']; ?>'>@<?php echo $person['screen_name']; ?></a>
		</div>
		<?php 
	}
	$action = $_POST['request'];
	
	// Twitter connection scripts
	require_once('twitteroauth.php');
	require_once('config.php');	
	include('timefunction.php');
	include('twitsearch.php');
	/* if (defined( DASHTER_PATH )){
		require_once( DASHTER_PATH . 'functions.php');
	} */
	// Twitter connection vars
	$token = $_POST['oauth_token'];
	$secret = $_POST['oauth_secret'];	
	if (empty($token) && empty($secret)){
		if (function_exists('get_option')){
			$token = get_option('dashter_user_twitter_oauth_token');
			$secret = get_option('dashter_user_twitter_oauth_token_secret');
		}
	}
	// Connect to Twitter
	$conn = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $token, $secret);
	// ************************ //	
	// *** FAVORITE A TWEET *** //
	// ************************ //
	if ($action == 'dashter_favorite_tweet'){
		$tweetID = $_POST['tweetID'];
		// Add to favorites
		$favorite = $conn->post('favorites/create/' . $tweetID);
		if (!empty($favorite)){
			echo "Success!";
			print_r($favorite);
		}	
	}	
	
	// *************************** //
	// *** BLOCK TAG ON STREAM *** // 
	// *************************** //
	if ($action == 'dashter_block_tag'){
		$postID = $_POST['postID'];
		$tagID = $_POST['tagID'];
		$aTagBlocks = get_option('dashter_tag_blocks');
		if (empty($aTagBlocks)){ 
			$aTagBlocks = array(); // Make an empty array so the in_array doesn't break
		}	
		$aTagBlocks[] = $tagID;
		update_option('dashter_tag_blocks', $aTagBlocks);
	}
	
	// ************************* //
	// *** DASHTER LISTENER *** //
	// ************************* //
	if ($action == 'dashter_listener'){
		$lastID = get_option('dashter_mentionsLastChecked');
		if (empty($lastID)){ $lastID = '1'; }
		$searchParams = array (	'since_id'		=>		$lastID,
								'count'			=>		'20',
								'trim_user'		=>		true,
								'include_rts'	=>		0 );
		$sResults = $conn->get('statuses/mentions', $searchParams);
		// print_r($searchParams);
		// print_r($sResults);
		if (!empty($sResults)){
			$i = 0;
			foreach ($sResults as $tweet){
				// Just a counter.
				$i++;
			}
			echo "Found $i";
		} else {
			echo "Found 0";
		}
	}

	// ************************** //
	// *** GET A SINGLE TWEET *** //
	// ************************** //
	if ($action == 'dashter_get_single'){
		$tweetid = $_POST['tweetID'];
		$singleParams = array ( 	'id'	=>	$tweetid	);
		$singleTweet = $conn->get('statuses/show', $singleParams);
		if (!empty($singleTweet)){
			$screenname = $singleTweet->user->screen_name;
			$profileimg = $singleTweet->user->profile_image_url;
			$tweet = dashter_parse_tweet($singleTweet->text);
			$posted = date('F d Y g:ia', strtotime($singleTweet->created_at));
			?>
			<a href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-image' title='@<?php echo $screenname; ?>'><img src="<?php echo $profileimg; ?>" align="left" width="32" style="margin: 0 10px 0 0;"></a>
			<b>@<a href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-name' title='@<?php echo $screenname; ?>'><?php echo $screenname; ?></a>:</b> 
			<?php echo $tweet; ?><br/>
			<i><?php echo $posted; ?></i>
			
			<?php 
		} else {
			echo "Twitter may be down, or the tweet may not be archived by Twitter anymore.";
		}
	}
	
	// *************************** //
	// *** GET RECENT MENTIONS *** //
	// *************************** //
	if ($action == 'dashter_get_mentions'){
		$mysn = $_POST['myscreenname'];
		$searchParams = array ( 	'q'		=> 		'@' . $mysn,
									'rpp'	=> 		100		);
		$mysnSearch = $conn->get('search', $searchParams);
		/* 
		echo "<pre>";
		print_r($mysnSearch);
		echo "</pre>";
		*/
		$aTheyLikeMe = array();
		$aUserImages = array();
		if (!empty($mysnSearch->results)){
			foreach ($mysnSearch->results as $res){
				$fromUser = $res->from_user;
				$aTheyLikeMe[$fromUser] = $aTheyLikeMe[$fromUser] + 1;
				// save user info
				if (empty($aUserImages[$fromUser])){
					$aUserImages[$fromUser] = $res->profile_image_url;
				}
			}
			arsort($aTheyLikeMe);
			/* 
			echo "<pre>";
			print_r($aTheyLikeMe);
			print_r($aUserImages);
			echo "</pre>";
			*/
			$i=0;
			foreach ($aTheyLikeMe as $name=>$count){
				$i++;
				if ($i > 10){
					break;
				}
				$person = array ( 	'screen_name'	=>	$name, 'img_url' 	=>	$aUserImages[$name]	);
				display_user($person);
			}
		} else {
			echo "Sorry, not getting any results right now. Try again in a little bit.";
		}
	}
	
	// ********************* //
	// *** PEOPLE SEARCH *** //
	// ********************* //
	if ($action == 'dashter_people_search'){
		$sterm = $_POST['searchterm'];
		$page = $_POST['pg'];
		if (empty($page)){	$page = '1'; }
		$searchParams = array ( 	'q'			=>	$sterm,
									'per_page'	=> 	10,
									'page'		=>	$page	);
		$psResults = $conn->get('users/search', $searchParams);

		$rightnow = date("U");

		if (!empty($psResults)){
			echo "<p align='center'><a class='button-secondary' href='Javascript:closeSearch();'>Hide Results</a></p>";
			foreach ($psResults as $person){
				$name = $person->name;
				$location = $person->location;
				$screenname = $person->screen_name;
				$profileimg = $person->profile_image_url;
				$desc = $person->description;
				$start = $person->created_at;
				$start = date('F jS Y', strtotime($start));
				$recent = $person->status->created_at;
				$recent = date("U", strtotime($recent));
				$friendcount = $person->friends_count;
				$followercount = $person->followers_count;
				$statuses = $person->statuses_count;
				
				?>
				<table width="100%" style="border-top: solid 1px #ccc; padding: 5px 0;">
				<tr>
					<td valign="top" width="100">
					<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>' class='thickbox user-image' title='@<?php echo $screenname; ?>'>
					<img src="<?php echo str_replace('_normal', '_bigger', $profileimg); ?>" align="left" style="width: 73px; height: 73px; padding: 5px;"></a>
					</td>
					<td width="*">
					<b>
					<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>' class='thickbox user-name' title='@<?php echo $screenname; ?>'>
					<?php echo $name; ?> - @<?php echo $screenname; ?></a></b><br/>
					<span style="font-size: 8pt;">Since <?php echo $start; ?> | <?php echo $location; ?>
					<br/><i><?php echo dashter_parse_tweet($desc); ?></i><br/>
					Friends <b><?php echo $friendcount; ?></b> | Followers <b><?php echo $followercount; ?></b> | Statuses <b><?php echo $statuses; ?></b> | FSRatio: <b><?php if ($statuses > 0) { echo round(($followercount / $statuses), 2); } ?></b><br/>
					Last Activity: <?php echo timeBetween($recent, $rightnow); ?></span>
					</td>
				</tr>
				
				</table>
				<br class="clear" />
				<?php
			}
				/*
				echo "<pre>";
				print_r($psResults);
				echo "</pre>";
				*/
		} else {
			echo "<p align='center'>Twitter service may be down.</p>";
		}
		
	}

	// *********************** // 	
	// *** ADMIN FAVORITES *** // 
	// *********************** // 
	if ($action == 'dashter_favorites'){
		if ($_POST['showOn'] == 'dashter_home'){
			$dispHome = true;
		} else {
			$dispHome = false;
		}
		// Favorites option
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
		// print_r($favParams);
		$favs = $conn->get('favorites', $favParams);
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
				$favs = $conn->get('favorites', $favParams);
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
				$username = $fav->user->name;
				$screenname = $fav->user->screen_name;
				$profileimg = $fav->user->profile_image_url;
				
				$tweetID = $fav->id_str;
				// Check to see if tweet has already been curated.
				$tweetCurated = "";
				if (!empty($curated_tweets)){
					if (array_key_exists($tweetID, $curated_tweets)){
						$tweetCurated = $curated_tweets[$tweetID];
					}
				}
				
				$tweettext = dashter_parse_tweet($fav->text);
				$tweettime = date("U", strtotime($fav->created_at));
				
				?>
				<tr>
				<td><a href="../wp-content/plugins/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class="thickbox user-image" title="@<?php echo $screenname; ?>">
				<img src="<?php echo $profileimg ?>" width="32" height="32"></a></td>
				<td><b><a href="../wp-content/plugins/dashter/core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class="thickbox user-image" title="@<?php echo $screenname; ?>"><?php echo $screenname; ?></a></b><br/><br/>
				</td>
				<td <?php if (($tweetCurated <> "") && ($oRule)) { echo 'style="color: #ccc;"'; }?>>
					<?php echo $tweettext; ?>
					<div style="color: #aaa; padding: 5px 0px;"><?php echo timeBetween($tweettime, $rightnow); ?> ago.</div>
					<?php if (($tweetCurated == "") || (!$oRule)){ ?>
					<div class="row-actions">
						<span>
							<a title="Retweet This" href="Javascript:reTweet('<?php echo $tweetID; ?>')">Retweet</a> | 
							<a title="Reply To This" href="Javascript:replyToTweet('<?php echo $tweetID; ?>','<?php echo $screenname; ?>')">Reply</a> | 
							<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $tweetID; ?>')">Quote</a> | 
							<!-- <a title="Quote / Reply to This" href="Javascript:quoteTweet('<?php echo $tweetID; ?>')">Quote/Reply</a> | -->
							<a title="Recommend an article in response" href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/rec-article.php?act=recommend&sn=<?php echo $screenname; ?>&tweetID=<?php echo $tweetID; ?>&height=350" class="thickbox">Recommend an Article</a> | 
							<a title='Curate this Tweet' href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/curate-tweet.php?tweetID=<?php echo $tweetID; ?>&height=600' class='thickbox'>Curate This</a> |
							<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $tweetID; ?>')">Favorite</a>
						</span>
					</div>
					<?php } ?>
				</td>
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
			if (!$dispHome){
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
		/* 
		echo "<pre>";
		print_r($favs);
		echo "</pre>";
		*/
	}
	
	// ***************** //
	// *** R2 SEARCH *** //
	// ***************** //
	if ($action == 'dashter_new_stream'){

		// Stream Cache database
		$stream_cache = $wpdb->prefix . "dashter_stream_cache";
		
		// Detect database
		$dbOk = false;
		if ($wpdb->get_var("show tables like '$stream_cache'") != $stream_cache){
			// DB is not active; old version :(
			$dbOk = false;
			// echo "No database detected.";
		} else {
			$dbOk = true;
			// echo "Database found.";
		}
		
		// Check db for existing cached tweets
		$reqPostID = $_POST['post_id'];
		$searchFor = $_POST['searchFor'];
		$moreSearch = $_POST['moreSearch'];
		$refreshCache = $_POST['refCache'];
		if (!empty($reqPostID)){
			// Query
			$qGetCached = "SELECT resultid, cachetime, tweetid, screenname, profileimg, tweettext, tweettime FROM $stream_cache WHERE postid = " . intval($reqPostID) . " ORDER BY tweettime DESC";
			$cacheResults = $wpdb->get_results($qGetCached);
			$countResults = $wpdb->query($qGetCached);
			if ( (empty($cacheResults)) || ($refreshCache == 'selected') ){
				// echo "Nothing in the database.";
				// No results were found. Fresh search required.
				$rpp = 50; // Return up to 50 results for cache
				$searchParams = array ( 'q' => $searchFor, 'rpp' => $rpp, 'result_type' => 'recent' );
				// Brute force search
				$attempts = 0;
				while (empty($searchResults)){
					$searchResults = $conn->get('search', $searchParams);
					$attempts++;
					if ($attempts > 9) {
						break; // while. Brute force fail.
					}
				}
				if (!empty($searchResults)){
					// we have results, maybe
					if (!empty($searchResults->results)){
						// Actual results were returned.
						$iPostID = intval($reqPostID);
						$iCacheTime = date('Y-m-d H:i:s');	// Now
						$sqlInsertCache = "INSERT INTO $stream_cache (postid, cachetime, tweetid, screenname, profileimg, tweettext, tweettime) VALUES ";
						foreach ($searchResults->results as $tweet){
							
							$theID = $tweet->id_str;
							$userName = addslashes($tweet->from_user);
							$userImg = $tweet->profile_image_url;
							$tweetText = addslashes($tweet->text);
							$tweetTime = date('Y-m-d H:i:s', strtotime($tweet->created_at));
							$aResults[] = array (	
											'cachetime'		=>		$iCacheTime,
 											'tweetid'		=>		$theID,
 											'screenname'	=>		$userName,
 											'profileimg'	=>		$userImg,
 											'tweettext'		=>		$tweetText,
 											'tweettime'		=>		$tweetTime
 										); 
							// Create database insert
							$sqlInsertCache .= "(" .  $iPostID . ", '" . $iCacheTime . "', '" . $theID . "',";
							$sqlInsertCache .= "'" . $userName . "', '" . $userImg . "', '" . $tweetText . "', '" . $tweetTime . "'),";
						}
						$sqlInsertCache = substr($sqlInsertCache, 0, (strlen($sqlInsertCache) - 1));
						$sqlInsertCache .= ";";
						// Insert into database
						// echo $sqlInsertCache;
						if ($refreshCache == 'selected'){
							// Delete the existing cache before updating
							$sqlDeleteCache = "DELETE FROM $stream_cache WHERE postid = $iPostID";
							$delete = $wpdb->query($sqlDeleteCache);
						}
						$insertSuccess = $wpdb->query($sqlInsertCache);
						if ($insertSuccess){
							echo "Successfully inserted $insertSuccess results in to the cache.";
						} else {
							echo "Ruh roh. Something went wrong.";
						}
					} else {
						// No results were returned
						?>
						<div style='margin: 5px 2px; padding: 2px; border: solid 1px #ccc; background-color: #ddd; font-size: 8pt; color: #999;'>
							<a href="javascript:loadPostReq('<?php echo $reqPostID; ?>', true, 1);">
							<img src="<?php echo DASHTER_URL; ?>images/repeat.png" align="right" title="Click to Retry Search" alt="No results">
							</a>
							<br/>
							Sorry, this article doesn't have any current conversations. 
							<br/><span style="color: #333">Try again tomorrow, or start the conversation!</span>
						</div>
						<?php 
					}
				} else {
					// No results; twitter is probably down.
				}
			} else {
				// Results were found.
				// Determine request conditions (return cache, new search params, etc...)
				// echo "Results are in the database.";
				// print_r($cacheResults);
				$aResults = array();
				foreach ($cacheResults as $row){
 					$aResults[] = array (	
 											'cachetime'		=>		$row->cachetime,
 											'tweetid'		=>		$row->tweetid,
 											'screenname'	=>		$row->screenname,
 											'profileimg'	=>		$row->profileimg,
 											'tweettext'		=>		$row->tweettext,
 											'tweettime'		=>		$row->tweettime
 										); 						
				}
			}
			if (!empty($aResults)){
				$prevtime = "";
				$currtime = "";
				$distance = (float) 0;
 				foreach ( $aResults as $row ){
 					$cacheTime = strtotime($row['cachetime']);
					// echo "<br/>" . $row->resultid . " | " . $reqPostID. " | " . $row->cachetime . " | " . $row->tweetid . " | " . $row->screenname . " | " . $row->profileimg . " | " . $row->tweettext . " | " . $row->tweettime;
					// Get velocity.
					if ($prevtime == ""){
						// first
						$prevtime = $row['cachetime'];
					} else {
						$currtime = $row['tweettime'];
						$distance = $distance + ( ( strtotime($prevtime) - strtotime($currtime) ) );
						$prevtime = $currtime;
					}
				}
				
				
				$distance = round( ( $distance / count($aResults) ), 2 );
				if ($distance == 0){
					$distance = 432000;
				}
				$cacheDuration = round( ( ( strtotime("now") - $cacheTime ) ) , 2 );
				
				$concernLevel = (float) 0;
				$concernLevel = round(($cacheDuration / $distance), 2);
				$concernInt = intval ( round ( $concernLevel ), 0 );
				if ($concernInt > 5){
					$concernInt = 5;
				}
				switch ($concernInt){
					case 0:
						$colorCode = "#ffffff";
						$note = "Hot off the presses!";
						break;
					case 1:
						$colorCode = "#cce5e5";
						$note = "These tweets are very current.";
						break;
					case 2:
						$colorCode = "#cce5d3";
						$note = "These are fairly current, maybe a little dated.";
						break;
					case 3:
						$colorCode = "#d9e5cc";
						$note = "These tweets are starting to lose that 'fresh tweet' aroma.";
						break;
					case 4:
						$colorCode = "#e5e5cc";
						$note = "These tweets are getting old.";
						break;
					case 5:
						$colorCode = "#e5cccc";
						$note = "These tweets have seen better days. How bout some new ones?";
						break;
				}
				/* $distance = $distance / 60;
				if ($distance < 60){
					$descDistance = round($distance, 0) . " minutes";
				} else {
					$hours = floor($distance / 60);
					$mins = round($distance - ($hours * 60),0);
					if ($hours > 1){
						$descDistance = $hours . " hours, " . $mins . " minutes";
					} else {
						$descDistance = $hours . " hour, " . $mins . " minutes";
					}
				} */
				echo "<div style='margin: 5px 2px; padding: 5px 5px 10px 5px; border: solid 1px #ccc; background-color: " . $colorCode . "; font-size: 8pt; color: #777;'>";
				echo "<a href='javascript:loadPostReq(\"" . $reqPostID . "\", true, 1);' style='text-decoration: none; background: #eee; padding: 1px 5px 2px 5px; font-size: 8pt; border: solid 1px #aaa; border-radius: 8px; float:right;'>Refresh Results</a>";
				echo $note . "<br/>" . round ( ( (60 * 60) / $distance ), 1) . " tweets / hour" ;
				echo ", last cached " . timeBetween( (date("U", $cacheTime)) , (date("U")) , 'ignore' );
				if (timeBetween( (date("U", $cacheTime)) , (date("U")) , 'ignore' ) == ""){
					echo "Just a moment ";
				}
				echo " ago.</div>";
				/* 
				if ( ($cacheDuration >= ($distance * 2)) && ( count($aResults) > 1 ) ){
					// The cache needs to be updated.
					echo "<a href='javascript:loadPostReq(\"" . $reqPostID . "\", true, 1);'>";
					echo "<img src='" . DASHTER_URL . "/images/vel332.png' align='right' title='Click to Update Tweets' alt='332 High Velocity'>";
					echo "</a>";
					$velNote = "These tweets are probably out of date. Press this to update --->";
				}
				if ( ( $cacheDuration >= ($distance) ) && ( $cacheDuration < ($distance * 2) ) ){
					// Medium velocity
					echo "<a href='javascript:loadPostReq(\"" . $reqPostID . "\", true, 1);'>";
					echo "<img src='" . DASHTER_URL . "/images/vel232.png' align='right' title='Click to Update Tweets' alt='232 Medium Velocity'>";
					echo "</a>";
					$velNote = "These tweets are starting to age. Press this to update --->";
				}
				if ( ( $cacheDuration < $distance ) || ( count($aResults) == 1 ) ){
					// Low velocity
					echo "<a href='javascript:loadPostReq(\"" . $reqPostID . "\", true, 1);'>";
					echo "<img src='" . DASHTER_URL . "/images/vel132.png' align='right' title='Click to Update Tweets' alt='132 Low Velocity'>";
					echo "</a>";
					$velNote = "These are pretty current tweets. Press this to update --->";
				}
				if ($distance < 60){
					$descDistance = round($distance, 0) . " minutes";
				} else {
					$hours = floor($distance / 60);
					$mins = round($distance - ($hours * 60),0);
					if ($hours > 1){
						$descDistance = $hours . " hours, " . $mins . " minutes";
					} else {
						$descDistance = $hours . " hour, " . $mins . " minutes";
					}
				}
				echo "Avg. Time Between Tweets: " . $descDistance . "";
				
				// echo "<br/> Time since cached: " . round($cacheDuration,0) . " minutes.";
				echo "<br/> Time since last cached: " . timeBetween( (date("U", $cacheTime)) , (date("U")) , 'ignore' );
				if (timeBetween( (date("U", $cacheTime)) , (date("U")) , 'ignore' ) == ""){
					echo "<b> Just a moment ago. </b>";
				}
				echo "<br/> <span style='color: #333;'>$velNote</span>";
				echo "</div>";
				
				*/
				
				$showMax = 1;
				if (!empty($_POST['showNum'])){
					$showMax = $_POST['showNum'];
				}
				if ($showMax > count($aResults)){
					$showMax = count($aResults);
				}
				for ($i=0; $i<$showMax; $i++){
					$theID = $aResults[$i]['tweetid'];
					$userName = $aResults[$i]['screenname'];
					$userImg =  $aResults[$i]['profileimg'];
					$tweetText = $aResults[$i]['tweettext'];
					$tweetTime = $aResults[$i]['tweettime'];
				?>
					<script type="text/javascript">
						/* 
						jQuery('.stream-tweet').hover(function(){
							var showID = jQuery(this).children('div').attr('id');
							jQuery('#' + showID).fadeIn('fast');
						});
						*/
					</script>
					<div style='margin: 5px 2px; padding: 2px; border: solid 1px #ccc; background-color: #fff;' class="stream-tweet">
						
						<!-- <a href="Javascript:getUserDetail('<?php echo $userName; ?>')"> -->
						<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $userName; ?>' class='thickbox user-image' title='@<?php echo $userName; ?>'>
						<img src="<?php echo str_replace('_normal', '_bigger', $userImg); ?>" width="48" height="48" style="float: left; padding: 3px;"></a>
						<!-- <a href="Javascript:getUserDetail('<?php echo $userName; ?>')"> -->
						<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $userName; ?>' class='thickbox user-image' title='@<?php echo $userName; ?>'><b><?php echo $userName; ?></b></a>
						<p style="margin: 1px; padding: 0; font-size: 10pt;"><?php echo dashter_parse_tweet($tweetText); ?></p>
						<p style="color: #aaa; padding: 0px; margin: 1px;"><?php echo timeBetween(date("U", strtotime($tweetTime)), date("U")); ?> ago.</p>
						<div id="tweet-<?php echo $theID; ?>" style="font-size: 7pt; margin: 1px; padding: 0; clear: left;">
							<span><a title="Retweet This" href="Javascript:reTweet('<?php echo $theID; ?>')">Retweet</a> | 
							<!-- <a title="Reply To This" href="Javascript:replyToTweet('<?php echo $theID; ?>','<?php echo $userName; ?>')">Reply</a> | 
							<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $theID; ?>')">Quote</a> | -->
							<a title="Recommend an article in response" href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/rec-article.php?act=recommend&sn=<?php echo $userName; ?>&tweetID=<?php echo $theID; ?>&height=350" class="thickbox">Recommend an Article</a> | 
							<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/curate-tweet.php?tweetID=<?php echo $theID; ?>&height=600' class='thickbox' title='Curate this Tweet'>Curate This</a>  |
							<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $theID; ?>')">Favorite</a></span>
						</div>
					</div>
				<?php 
				}
				?>
				<div style='text-align: center; padding: 5px 0px'>
					<i>Showing <?php echo $showMax; ?></i> 
				<?php if ($showMax > 1) {
					?>
					<a href="javascript:loadPostReq('<?php echo $reqPostID; ?>', false, 1);" class="button-primary">Show 1</a> 
					<?php 
				}
				?>
				<?php if ($_POST['showNum'] != '5') { ?>
				<a href="javascript:loadPostReq('<?php echo $reqPostID; ?>', false, 5);" class="button-secondary">Show 5</a> 
				<?php } 
				if ($_POST['showNum'] != '20') { ?>
				<a href="javascript:loadPostReq('<?php echo $reqPostID; ?>', false, 20);" class="button-secondary">Show 20</a> 
				<?php } 
				if ($_POST['showNum'] != '50') { ?>
				<a href="javascript:loadPostReq('<?php echo $reqPostID; ?>', false, 50);" class="button-secondary">Show All</a>
				<?php } ?>
				</div>
				<?php 
			}
		}
		
	
		// *** DEPRECATED *** // 
		// $searchFor = $_POST['searchFor'];
		// $moreSearch = $_POST['showMore'];
		/* 
		$rpp = 1;
		if ($moreSearch == 'true' || $moreSearch === true){ 
			$rpp = 5; 
		}
		// Quick parameters test...
		$searchParams = array (	'q'	=>	$searchFor, 'rpp' => $rpp, 'result_type' => 'recent' );
		// Brute force search results from Twitter (limit 10 search attempts per search).
		$sFail = 0;
		while (empty($searchResults)){
			$searchResults = $conn->get('search', $searchParams);
			$sFail++;
			if ($sFail > 9){
				break;	// while
			}
		}
		*/
		
	}
	
	// ******************************* // 
	// *** GET AN INDIVIDUAL TWEET *** // 
	// ******************************* // 
	if ($action == 'dashter_getTweet'){
		$tweetID = $_POST['tweetID'];
		$theTweet = $conn->get('statuses/show/' . $tweetID);
		echo "@" . $theTweet->user->screen_name . " " . $theTweet->text;
	}
	// ********************* //
	// *** SAVE CURATION *** // 
	// ********************* //

	if ($action == 'dashter_saveCuration'){
		// Fields
		$postAge = $_POST['select_post'];
		if ($postAge == 'existing'){
			$state = 'update';
			$apost_id = $_POST['post_selection'];
			$post_id = (integer) $apost_id[0];
		}
		if ( ($postAge == 'new') || (!$postAge) ){
			$state = 'new';
			$post_title = $_POST['post_title'];
		}
		
		// Get Tags + Mentions
		$theTweetTags = $_POST['tweetTags']; 			// array
		$theMentions = $_POST['tweetMentions']; 		// array
		$source_image = $_POST['source_image'];
		$source_name = $_POST['source_name'];
		$source_screen_name = $_POST['source_screen_name'];
		$source_content = urldecode($_POST['source_content']);
		$myComments = $_POST['myComments'];
		
		$tweetID = $_POST['tweetID'];

	
			
		$writePost = "<blockquote class='curated'>";
		$writePost .= "<img src='" . $source_image . "' width='48' height='48' align='left' class='curated_tweet_img' alt='" . $source_name . "'>";
		$writePost .= $source_name . " - @<a href='http://twitter.com/$source_screen_name'>" . $source_screen_name . "</a> <br/>";
		$writePost .= dashter_parse_curatedTweet($source_content);
		$writePost .= "</blockquote>";
		$writePost .= "<p>" . $myComments . "</p>";
		
		// Get existing content...
		
		if ($state == 'update'){
		
			// Get the existing post...
			$the_existing_post = get_post( $post_id );
			// Get the existing mention values...
			$the_existing_meta = get_post_meta( $post_id, '_dashter_meta_mentioned_users' );
			// Get the existing tags...
			$the_existing_post_content = $the_existing_post->post_content;
			
			// Scrub existing + create tags to append...
			$the_existing_tags = wp_get_post_tags( $post_id );
			if ( (!empty($theTweetTags)) && (is_array($theTweetTags))){
				foreach ($theTweetTags as $add_tag){
					$isExisting = false;
					if ($the_existing_tags){
						foreach ($the_existing_tags as $exist_tag){
							if ($add_tag == $exist_tag){
								$isExisting = true;
							}
						}
					}
					if (!$isExisting){
						$tagsList .= $add_tag . ",";
					}					
				}
				if ($tagsList){
					$tagsList = substr($tagsList, 0, (strlen($tagsList)-1) );
				}
			}
			// all the existing users in...
			if ($the_existing_meta){
				foreach ($the_existing_meta as $exist_meta){
					foreach ($exist_meta as $meta){
						$updateDashterMeta[] = $meta;
					}
					
				}
			}
			if ( (!empty($theMentions)) && (is_array($theMentions))){
				foreach ($theMentions as $add_mention){
					$isExisting = false;
					if ($the_existing_meta){
						foreach ($the_existing_meta as $eKey => $exist_meta){
							if ($add_mention == $exist_meta){
								$isExisting = true;
							}
						}
					}
					if (!$isExisting){
						$updateDashterMeta[] = $add_mention;
					} 
				}
			}

			$wp_update_vars = array();	
			$wp_update_vars['ID'] = intval($post_id);
			$wp_update_vars['post_content'] = $the_existing_post_content . $writePost;
			
			wp_update_post( $wp_update_vars );
			if ($tagsList){
				wp_set_post_terms ( $post_id, $tagsList, 'post_tag', true);
			}
			if ($updateDashterMeta){
				delete_post_meta ( $post_id, '_dashter_meta_mentioned_users' );
				add_post_meta ( $post_id, '_dashter_meta_mentioned_users', $updateDashterMeta );
			}
			
			$message .= "Updated post <a href='post.php?post=" . $post_id . "&action=edit'>$post_id</a>. ";
			if ((!empty($theTweetTags)) && (is_array($theTweetTags))){
				foreach ($theTweetTags as $tags){
					$message .= " #" . $tags;
				}
			}
			foreach ($theMentions as $mention){
				$message .= " @" . $mention; 
			}
			$thePostID = $post_id;
		}
		// echo $message;
		
		if ($state == 'new'){
			global $current_user;
			get_currentuserinfo();
			$uid = $current_user->ID;
			if ((!empty($theTweetTags)) && (is_array($theTweetTags))){
	 			foreach ($theTweetTags as $tag){
	 				$csvTags .= $tag . ",";
	 			}
			}
			$csvTags = substr($csvTags, 0, (strlen($csvTags) - 1) );
			if (!empty($_POST['new_post_category'])){
				// $aPostCat = $_POST['new_post_category'];
				$category = array( intval($_POST['new_post_category']) );	
			} else {
				$category = array(1);
      		}
      		
      		$prependPost = "<p>This post was generated by <a href='http://dashter.com/' target='_new'>Dashter</a></p>";
      		
			$newPost = array(
			  'post_author' => $uid,
			  'post_content' => $prependPost . $writePost,
			  'post_status' => 'draft',
			  'post_title' => $post_title,
			  'post_category' => $category,
			  'post_type' => 'post',
			  'tags_input' => $csvTags
			);

			$new_post_id = wp_insert_post( $newPost );
			add_post_meta ( $new_post_id, '_dashter_meta_mentioned_users', $theMentions );
			add_post_meta ( $new_post_id, 'dashter_curated', 'true', true );
			$message .= "Updated post <a href='post.php?post=" . $new_post_id . "&action=edit'>$new_post_id</a>. ";
			$thePostID = $new_post_id;
		}
		$message .= " Success.";
		
		// Scrub list for favorite tweets that were curated.
		$curated_tweets = get_option('dashter_curated_tweets');
		$curated_tweets[$tweetID] = $thePostID; // append to array w new tweet / postid.
		update_option('dashter_curated_tweets', $curated_tweets);
		
		echo "$thePostID Success";
	}
	
		
	// ********************** //
	// *** CURATE A TWEET *** //
	// ********************** //
	// DEPRECATED 8/4/2011 // 
	/* 
	if ($action == 'dashter_curate'){
	
		$categories = get_categories();
	
		echo "<form method='POST' name='curateForm' id='curateForm'>";
		if (isset($_POST['userprofile'])){
			echo "<input type='hidden' name='searchuser' value='" . $_POST['userprofile'] . "'>";
		}
		echo "<input type='hidden' name='submitCForm' value='true'>";
		$tweetID = $_POST['tweetID'];
		echo "<input type='hidden' name='tweetID' value='" . $tweetID . "'>";
		// Pull the tweet data...
		$getParams = array (	'id'	=>	$tweetID,
								'include_entities' => true );
		$theTweet = $conn->get('statuses/show', $getParams);
		
		// Load the vars to use in the tweet
		
		$userData = array(	'real_name'		=>	$theTweet->user->name,
							'url'			=>	$theTweet->user->url,
							'img_url'		=>	$theTweet->user->profile_image_url,
							'screenname'	=>	$theTweet->user->screen_name	);
							
		// Add all the string processing in the future.
		$tweetContent = $theTweet->text;
		
		// Get tag + mention entities
		$theTags = $theTweet->entities->hashtags;
		if ($theTags){
			foreach ($theTags as $tag){
				$tTags[] = $tag->text;
			}
		}
		$theMentions = $theTweet->entities->user_mentions;
		if ($theMentions){
			foreach ($theMentions as $mention){
				$tMents[] = $mention->screen_name;
			}
		}
		
		// echo "You are trying to curate tweet " . $tweetID;
		
		// Step 1... Get all active post drafts meta-tagged "curated"
		$aCuratedPostArgs = array(	'numberposts'	=> 	-1,
									'post_status'	=>	'draft',
									'meta_key'		=>	'dashter_curated',
									'meta_value'	=>	'true' );
		$curPosts = get_posts($aCuratedPostArgs);
		
		if ($curPosts){
			echo "<input type='radio' name='select_post' value='existing'> <label for='select_post'> <b>Select Existing Post</b></label><br/>";
			$numCurPosts = sizeof($curPosts);
			if ($numCurPosts > 3) { $numCurPosts = 3; }
			echo "<p align='right' style='padding: 0; margin: 0;'>";
			echo "<select size='$numCurPosts' multiple='false' style='width: 90%;' name='post_selection'>";
			foreach ( (array) $curPosts as $post) {
				echo "<option value='" . $post->ID . "'>" . $post->post_title . "</option>";
			}
			echo "</select></p>";
			echo "<input type='radio' name='select_post' value='new'> <label for='select_post'><b>Create a new Curated Post</b></label><br/>";
		} else {
			// There are no existing curated posts...
			// echo "There are no posts found.";
			echo "<input type='hidden' name='select_post' value='new'>";
			echo "<b>Create a new Curated Post</b><br/>";
		}
		echo "<p align='right' style='margin: 0; padding: 0;'>";
		echo "<label for='new_post_title'>New Post Title:</label> ";
		echo "<input type='text' name='new_post_title' style='width: 70%;'></p>";
		echo "<p align='right' style='margin: 0; padding: 0;'>";
		echo "<label for='new_post_category'>New Post Category:</label> ";
		echo "<select name='new_post_category'>";
		echo "<option value=''>-Select-</option>";
		if ($categories){
			foreach ($categories as $cat){
				echo "<option value='" . $cat->cat_ID . "'>" . $cat->name . "</option>";
			}
		}
		echo "</select>";
		echo "</p>";
		
		echo "<br/>";
		
		echo "<b>The Tweet</b><br/>";
		echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc; color: #555;'>";
		echo "<input type='hidden' name='source_image' value='" . $userData['img_url'] . "'>";
		echo "<input type='hidden' name='source_name' value='" . $userData['real_name'] . "'>";
		echo "<input type='hidden' name='source_screen_name' value='" . $userData['screenname'] . "'>";
		echo "<input type='hidden' name='source_content' value='" . urlencode(strip_tags(addslashes($tweetContent))) . "'>";
		echo "<img src='" . $userData['img_url'] . "' style='padding: 0px 10px; width='48' height='48' align='left'>";
		echo "<b>" . $userData['real_name'] . "</b> - @" . $userData['screenname'] . " <br/>";
		echo $tweetContent;
		echo "<br class='clear' />";
		echo "</div>";
		
		echo "<b>Your Commentary</b><br/>";
		echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc; background-color: #ddd;'>";
		echo "<textarea style='width: 100%' name='myComments'></textarea>";
		echo "</div>";
		
		echo "<b>Included Elements:</b><br/>";
		echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc;'>";
		echo "<b>Tags: </b>";
		if ($tTags){
			foreach ($tTags as $tag){
				echo "#" . $tag . " ";
				echo "<input type='hidden' name='tweetTags[]' value='" . $tag . "'>";
			}
		} else {
			echo "<i>This tweet has no tags.</i>";
		}
		echo "<br/><b>Mentions: </b>"; 
		echo "@" . $userData['screenname'] . " ";
		echo "<input type='hidden' name='tweetMentions[]' value='" . $userData['screenname'] . "'>";
		if ($tMents){
			foreach ($tMents as $ment){
				echo "@" . $ment . " "; 
				echo "<input type='hidden' name='tweetMentions[]' value='" . $ment . "'>";
			}
		} else {
			// echo "<i>Nobody was mentioned in this tweet.</i>";
		}
		echo "</div>";
		echo "</form>";
		echo "<p align='right'>";
		echo "<input type='button' class='button-primary' value='Save Curation' href='Javascript:document.forms[\"curateForm\"].submit();'>";
		// echo "<input type='button' class='button-secondary' value='Cancel'>";
		
		echo "</p>";
		
	}
	*/
	// ******************* //
	// *** ADD TO LIST *** //
	// ******************* //
	if ($action == 'addToList'){
		$userName = $_POST['username'];
		$listSlug = $_POST['listSlug'];
		$mysn = $_POST['myscreenname'];
		$params = array (	'slug'	=>	$listSlug,
							'owner_screen_name'	=>	$mysn,
							'screen_name'	=> $userName	);
		if ($_POST['remlist']){
			$addToList = $conn->post('lists/members/destroy', $params);
		} else {
			$addToList = $conn->post('lists/members/create', $params);
		}
		// print_r($addToList);
		echo "listed."; // NEED TO ADD ERROR RESPONSE // 
	} 
	
	// ******************* //
	// *** REPORT SPAM *** //
	// ******************* //
	if ($action == 'reportspam'){
		$userName = $_POST['username'];
		$spamParam = array (	'screen_name' 	=>	$userName 	);
		$reportSpam = $conn->post('report_spam', $spamParam);
		if (($reportSpam->id)){
			echo "reported.";	
		} else {
			echo "failed.";
		}
	}
	
	// ****************** //
	// *** SHOW LISTS *** // 
	// ****************** // 
	if ($action == 'showlists'){
		$lists = $conn->get('lists');
		if ($lists){
			echo "<table width='100%'>";
			$i = 0;
			foreach ($lists as $lkey => $list){
				foreach ( (array) $list as $onelist){
					if (isset($onelist->name)){
						if ($i==0) { echo "<tr>"; }
						echo "<td width='33%'><a href='Javascript:showList(\"" . $onelist->slug . "\");' class='listSelect' id='" . $onelist->name . "'>" . $onelist->name . "</a> ( " . $onelist->member_count . " ) </td> ";
						$i++;
						if ($i==3) {
							$i=0;
							echo "</tr>";
						}
					}
				}
			}
			echo "</tr></table>";
		} else {
			echo "No lists.";
		}	
	}
	// *********************** // 
	// *** FRIENDLY TOPICS *** // 
	// *********************** // 
	if ($action == 'showfriendtopics'){
	
		// Step 1 - get home timeline (200 = max)
		$params = array	(	'count'	=>	'199', 
							'include_entities' => 'true' );
		$iFollow = $conn->get('statuses/home_timeline', $params);
		if (!empty($iFollow)){
			foreach ($iFollow as $tweet){
				if (!empty($tweet)){
					foreach ($tweet as $key => $value){
						if ($key == 'entities'){
							foreach ($value as $valkey => $valval){
								if ($valkey == 'hashtags'){
									foreach ($valval as $hashtag){
										// Run first to create array structure...
										$theTag = strtolower($hashtag->text);
										if ($myFollowTags[$theTag]){
											$myFollowTags[$theTag] = $myFollowTags[$theTag] + 1;
										} else {
											$myFollowTags[$theTag] = 1;
										}
									}
								}					
							}
						}
					}
				} else {
					echo "<p align='center'><img src='../wp-content/plugins/dashter/images/dfail.jpg'></p>";
					echo "<p align='center'>Twitter service may be down.</p>";
				}
			}
		} else {
			echo "<p align='center'><img src='../wp-content/plugins/dashter/images/dfail.jpg'></p>";
			echo "<p align='center'>Twitter service may be down.</p>";
		}
		if (!empty($myFollowTags)){ 
			arsort($myFollowTags); 
			echo "<b>Trending in people I follow...</b>";
			echo "<table width='100%'>";
			$k=0;
			$j=0;
			foreach ($myFollowTags as $tagname=>$tagcount){
				$j++;
				if ($j==19){
					break;
				}
				if ($k==0){
					echo "<tr>";
				}
				echo "<td><a href='Javascript:searchTwitter(\"#" . $tagname . "\");'>#" . $tagname . "</a> ($tagcount)</td>";
				$k++;
				if ($k==3){
					$k=0;
					echo "</tr>";
				}
			}
			echo "</tr></table>";
		}
		// Follower cross section 
		// THIS DOES NOT PULL ALL, JUST A *RANDOM* SAMPLING OF FOLLOWERS
		// Max 100 followers
		// No idea how these followers are chosen (order ranking)
		
		$myFollow = $conn->get('followers/ids');
		$myFollowList = "";
		$myFollowSize = sizeof($myFollow);
		if ($myFollowSize > 99) { $myFollowSize = 99; }
				
		for ($i=0; $i < $myFollowSize; $i++){
			$myFollowList .= $myFollow[$i] . ",";
		}
		$myFollowList = substr($myFollowList,0, (strlen($myFollowList)-1));
		$params = array ( 'user_id' => $myFollowList, 'include_entities' => 1 );
		$userInfo = $conn->get('users/lookup', $params);
		
		if (!empty($userInfo)){
			foreach ($userInfo as $user){
				$hash = $user->status->entities->hashtags;
				if (!empty($hash)){
					foreach ($hash as $hashtag){
						$theTag = strtolower($hashtag->text);
						if ($followMeTags[$theTag]){
							$followMeTags[$theTag] = $followMeTags[$theTag] + 1;
						} else {
							$followMeTags[$theTag] = 1;
						}
					}
				}
			}
			if (!empty($followMeTags)){
				arsort($followMeTags);
				echo "<br/><b>Trending in people who follow me...</b>";
				echo "<br/><i>Note: This is just a random sampling from 100 followers.</i>";
				echo "<table width='100%'>";
				$k=0;
				$j=0;
				foreach ($followMeTags as $tagname=>$tagcount){
					$j++;
					if ($j==19){
						break;
					}
					if ($k==0){
						echo "<tr>";
					}
					echo "<td><a href='Javascript:searchTwitter(\"#" . $tagname . "\");'>#" . $tagname . "</a> ($tagcount)</td>";
					$k++;
					if ($k==3){
						$k=0;
						echo "</tr>";
					}
				}
				echo "</tr></table>";			
			}
		}

		
	}
	
	// *********************** //
	// *** LIST TOP TRENDS *** //
	// *********************** //
	if ($action == 'showtrending'){
		$trending = $conn->get('trends/current');
		$mytrends = $trending->trends;
		$k=0;
		echo "<p>Current</p><table width='100%'>";
		foreach ($mytrends as $datetime=>$vals){
			foreach ($vals as $trend){
				if ($k==0) { echo "<tr>"; }
				echo "<td><a href='Javascript:searchTwitter(\"" . $trend->name . "\");'>" . $trend->name . "</a></td> ";
				$k++;
				if ($k==3) {
					$k=0;
					echo "</tr>";
				}
			}
		}
		echo "</tr></table>";
		$trending = $conn->get('trends/daily');
		$mytrends = $trending->trends;
		$k=0;
		$j=0;
		echo "<p>Recent</p><table width='100%'>";
		foreach ($mytrends as $datetime=>$vals){
			$j++;
			foreach ($vals as $trend){
				if ($k==0) { echo "<tr>"; }
				echo "<td><a href='Javascript:searchTwitter(\"" . $trend->name . "\");'>" . $trend->name . "</a></td> ";
				$k++;
				
				if ($k==3) {
					$k=0;
					echo "</tr>";
				}
			}
			if ($j==1){
					break;
				}
		}
		echo "</tr></table>";
	}
	
	
	// ********************* //
	// *** FOLLOW A USER *** //
	// ********************* //
	if ($action == 'followuser'){
		$mysn = $_POST['myscreenname'];
		$username = $_POST['twittername'];
		if ($conn) {
			echo "Connection made. Will now add user $username. ";
			// Check if friendship exists.
			$existsparams = array ( 	'target_screen_name'	=>	$username,
										'source_screen_name'	=>	$mysn );
			$checkExists = $conn->get('friendships/show', $existsparams);
			$isFriend = $checkExists->relationship->source->following;
			if (!$isFriend){
				$params = array (	'screen_name' 	=>	$username,
									'follow'		=> 	true );
				$createFriend = $conn->post('friendships/create', $params);
				if ($createFriend){
					echo "Friendship created.";
				} else {
					echo "Woops. Something went wrong.";
				}
			} else {
				echo "Friendship already existed.";
			}
		} else {
			echo "Connection failed.";
		}
	}
	
	// ******************** //
	// *** BLOCK A USER *** //
	// ******************** // 
	if ($action == 'blockUser'){
		$mysn = $_POST['myscreenname'];
		$username = $_POST['twittername']; // User name of user to be blocked.
		if ($conn){
			
			$blockParams = array (	'screen_name' =>	$username	);
			$blockResponse = $conn->post('blocks/create', $blockParams);
			if ($blockResponse->id_str){
				echo "Block successful.";
			} else {
				echo "Block failed.";
			}
			
		} else {
			echo "Connection failed.";
		}
	}
	 	
	// ************************ // 
	// *** FAVORITE A TWEET *** //
	// ************************ // 
	// ### DC 8/7/2011 ### THIS WILL PROBABLY BE DEPRECATED FOR NEW FAV AJAX WP Friendly METHOD ### // 
	if($action == 'favoriteTweet'){
		$mysn = $_POST['myscreenname'];
		$tweetID = $_POST['tweetID'];
		if ($conn) {
			$favParams = array ('id'=>floatval($tweetID));
			$createFavorite = $conn->post('favorites/create/' . $tweetID);
			$favStatus = $createFavorite->favorited;
			if ($favStatus == 1){
				echo "Success. Tweet " . $createFavorite->id_str . " was favorited.";
			} else {
				echo "Failure. Tweet $tweetID was not found or something went wrong.";
			}
		} else {
			echo "Connection failed.";
		}
	}
	// *********************** //
	// *** RETWEET A TWEET *** // 
	// *********************** //
	
	if ($action == 'dashter_retweet'){
	
		$mysn = $_POST['myscreenname'];
		$tweetID = $_POST['tweetID'];
		if ($conn) {
			$createRT = $conn->post('statuses/retweet/' . $tweetID);
			$RTStatus = $createRT->id;
			if ($RTStatus) {
				echo "Success. Tweet " . $RTStatus . " was retweeted.";
			} else {
				echo "Retweet failed.";
			}
		} else {
			echo "Connection failed.";
		}
	
	}
	// *********************** // 
	// *** SEARCH RESPONSE *** // 
	// *********************** // 
	if ($action == 'dashter_search'){
		
		// SEARCH API DOES NOT INCLUDE TWEET ENTITIES
		// WE WILL NEED TO MAKE AN INDEPENDENT TWEET PROCESSOR
		// LATER.
		
		$searchFor = $_POST['searchFor'];
		$nextPage = $_POST['nextPage'];
		
		$mysn = get_option('dashter_twitter_screen_name');
		echo "My screen name: " . $mysn;
		// Quick parameters test...
		$searchParams = array (	'q'	=>	$searchFor );
		if (!empty($nextPage)){
			$searchParams['page'] = $nextPage;
		}
		print_r($searchParams);
		$k = 0;
		while (empty($searchResults)){
			$k++;
			$searchResults = $conn->get('search', $searchParams);
			if ($k > 9){
				// Safety valve at 10 attempts.
				break;
			}
		}
		if ($k > 1){
			echo "Attempts: " . $k;
		}
		// print_r($searchResults);
		if (!empty($searchResults->results)){
			foreach ($searchResults as $resultob){
				if (is_array($resultob)){
					$first = true;
					foreach ($resultob as $sres){
						if ($first){
							// Quick hack to reflect checked mentions.
							if (strtolower($searchFor) == strtolower(("@" . $mysn))) {
								$mentionsLastChecked = $sres->id_str;
							}
							if (!empty($mentionsLastChecked)){
								update_option('dashter_mentionsLastChecked', $mentionsLastChecked);
							}
							$first = false;
						}
						$tweetText = $sres->text;
						$inReplyToID = $sres->in_reply_to_status_id_str;
						$theUser = $sres->from_user;
						$rightnow = date("U");
						$tweettime = date("U", strtotime($sres->created_at));
				?>
					<tr>
						<td>
						<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $sres->from_user; ?>' class='thickbox user-image' title='@<?php echo $sres->from_user; ?>'>
						<img src="<?php echo $sres->profile_image_url; ?>" width="48" height="48"></a></td>
						<td><b>
						<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $sres->from_user; ?>' class='thickbox user-name' title='@<?php echo $sres->from_user; ?>'><?php echo $sres->from_user; ?></a></b><br/><br/>
						<?php 
							if ($_POST['dashter_advanced_metrics'] == 'enabled'){
						?>
						<span style="margin: 10px 0; padding: 2px 0;">
							<img src="../wp-content/plugins/dashter/images/klout_icon.png" align="absmiddle">
							<?php echo rand(10,100); ?>
							<img src="../wp-content/plugins/dashter/images/empire_icon.png" align="absmiddle">
						<?php 
								if(rand(1,2) == 1){
									$empColor = "#458B00";
								} else {
									$empColor = "#FF1122";
								}
								$empValue = strval(rand(0,100)) . "." . strval(rand(0,999));
								echo "<font color='" . $empColor . "'>" . $empValue . "</font></span>";
							}
						?>
						</td>
						<td>
									
						<?php echo dashter_parse_tweet($tweetText); ?>
						<div style="color: #aaa; padding: 5px 0px;"><?php echo timeBetween($tweettime, $rightnow); ?> ago.
						<span style="float: right;">
						<?php 
						if (!empty($inReplyToID)){
							echo "<a href='javascript:showReplyToTweet(\"" . $inReplyToID . "\", \"" . $tweet->id_str . "\");'>In reply to...</a>";
						}
						?>
						</span>
						</div>
						<div class="row-actions">
							<span>
								<a title="Retweet This" href="Javascript:reTweet('<?php echo $sres->id_str; ?>')">Retweet</a> | 
								<a title="Reply To This" href="Javascript:replyToTweet('<?php echo $sres->id_str; ?>','<?php echo $sres->from_user; ?>')">Reply</a> | 
							<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $sres->id_str; ?>')">Quote</a> |  
								<a title="Recommend an article in response" href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/rec-article.php?act=recommend&sn=<?php echo $sres->from_user; ?>&tweetID=<?php echo $sres->id_str; ?>&height=350" class="thickbox">Recommend an Article</a> | 
								<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/curate-tweet.php?tweetID=<?php echo $sres->id_str; ?>&height=600' class='thickbox' title='Curate this Tweet'>Curate This</a>  |
							<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $sres->id_str; ?>')">Favorite</a>
							</span>
						</div>
						<div id="rep-to-<?php echo $tweet->id_str; ?>" style="display: none; padding: 5px 0 5px 5px; margin: 5px; border-left: solid 1px #ccc; background: #eee; font-size: 8pt; line-height: 12pt;"></div>
						</td>
						<!-- <td align="center"><img src="../wp-content/plugins/dashter/images/twitter_icon.png" width="20"></td> -->
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
		} else {
			echo "<tr><td colspan='4' align='center'><p><b>No results found.</b></p></td></tr>";
		}
	
	}
			
	// ********************* //
	// *** LATEST TWEETS *** //
	// ********************* //
	if ($action == 'dashter_latesttweets'){
		$mysn = $_POST['myscreenname'];
		$topicAction = $_POST['topicAction'];
		
		$lastID = $_POST['lastID'];
		
		if ($topicAction){
			if ($topicAction == 'list'){
				$listSlug = $_POST['listslug'];
				$params = array( 'owner_screen_name' => $mysn, 'slug' => $listSlug, 'include_entities' => '1' );
				$latest = $conn->get('lists/statuses', $params);
			}
			if ($topicAction == 'trend'){
				$searchTerm = $_POST['searchterm'];
			}
		} else {
			$addEntities = array ( 'include_entities' => '1' );
			if (!empty($lastID) && ($lastID != 0)){
				$addEntities['max_id'] = $lastID;
			}
			print_r($addEntities);
			$latest = $conn->get('statuses/home_timeline', $addEntities);
		}
		if (!empty($latest)){
			foreach ($latest as $tweet){
				$tweetText = $tweet->text;
				// Tweet processor ... This will add links, mentions, and hashtags
				if ($tweet->entities){
					// Entities exist.
					// 3 Types: urls / user_mentions / hashtags
					$tweetUrls = $tweet->entities->urls;
					$tweetUserMentions = $tweet->entities->user_mentions;
					$tweetHashtags = $tweet->entities->hashtags;
					if (!empty($tweetUrls)){
						foreach ($tweetUrls as $url){
							$theLink = $url->url;
							$formatLink = "<a href='" . $url->url . "' target='_new'>" . $url->url . "</a>";
							$tweetText = str_replace($theLink, $formatLink, $tweetText);
						}
					}
					if (!empty($tweetUserMentions)){
						foreach ($tweetUserMentions as $mention){
							$theMention = trim($mention->screen_name);
							$formatMention = "<a href='" . WP_PLUGIN_URL . "/dashter/core/popups/user_details.php?screenname=" . $theMention . "' class='thickbox user-name' title='@" . $theMention . "'>" . $theMention . "</a>";
							// $formatMention = '<a style="cursor: pointer;" title="View ' . $theMention . '" href="Javascript:getUserDetail(\'' . $theMention . '\')">' . $theMention . '</a>';
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
				$inReplyToID = $tweet->in_reply_to_status_id_str;
				// Color: Look for mention //
				$theTweet = $tweet->text;
				$theNeedle = "@" . $mysn;
				if ( strpos( strtolower($theTweet), strtolower($theNeedle)) !== false ){
					$rowStyle = " style='background-color: #ccffff;' ";
				} else {
					$rowStyle = " ";
				}
				?>
				<tr <?php echo $rowStyle; ?>>
					<td>
					<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $tweet->user->screen_name; ?>' class='thickbox user-image' title='@<?php echo $tweet->user->screen_name; ?>'>
					<img src="<?php echo $tweet->user->profile_image_url; ?>" width="48" height="48"></a></td>
					<td><b>
					<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/user_details.php?screenname=<?php echo $tweet->user->screen_name; ?>' class='thickbox user-image' title='@<?php echo $tweet->user->screen_name; ?>'><?php echo $tweet->user->screen_name; ?></a></b><br/><br/>
					<?php 
						if ($_POST['dashter_advanced_metrics'] == 'enabled'){
					?>
					<span style="margin: 10px 0; padding: 2px 0;">
						<img src="../wp-content/plugins/dashter/images/klout_icon.png" align="absmiddle">
						<?php echo rand(10,100); ?>
						<img src="../wp-content/plugins/dashter/images/empire_icon.png" align="absmiddle">
					<?php 
							if(rand(1,2) == 1){
								$empColor = "#458B00";
							} else {
								$empColor = "#FF1122";
							}
							$empValue = strval(rand(0,100)) . "." . strval(rand(0,999));
							echo "<font color='" . $empColor . "'>" . $empValue . "</font></span>";
						}
						$rightnow = date("U");
						$tweettime = date("U", strtotime($tweet->created_at));
					?>
					</td>
					<td>
						<?php echo $tweetText; ?>
						<div style="color: #aaa; padding: 5px 0px;"><?php echo timeBetween($tweettime, $rightnow); ?> ago.
						<span style="float: right;">
						<?php 
						if (!empty($inReplyToID)){
							echo "<a href='javascript:showReplyToTweet(\"" . $inReplyToID . "\", \"" . $tweet->id_str . "\");'>In reply to...</a>";
						}
						?>
						</span>
						</div>
						
						<div class="row-actions">
							<span>
								<a title="Retweet This" href="Javascript:reTweet('<?php echo $tweet->id_str; ?>')">Retweet</a> | 
								<a title="Reply To This" href="Javascript:replyToTweet('<?php echo $tweet->id_str; ?>','<?php echo $tweet->user->screen_name; ?>')">Reply</a> | 
							<a title="Quote This Tweet" href="Javascript:quoteTweet('<?php echo $tweet->id_str; ?>')">Quote</a> | 
								<a title="Recommend an article in response" href="<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/rec-article.php?act=recommend&sn=<?php echo $tweet->user->screen_name; ?>&tweetID=<?php echo $tweet->id_str; ?>&height=350" class="thickbox">Recommend an Article</a> | 
								<a href='<?php echo WP_PLUGIN_URL; ?>/dashter/core/popups/curate-tweet.php?tweetID=<?php echo $tweet->id_str; ?>&height=600' class='thickbox' title='Curate this Tweet'>Curate This</a> |
							<a title="Favorite This Tweet" href="Javascript:favTweet('<?php echo $tweet->id_str; ?>')">Favorite</a>
								<!-- <a title="Add This Tweet to a Curated Post" href="Javascript:curateTweet('<?php echo $tweet->id_str; ?>')">Curate This</a> --> 
							</span>
						</div>
						<div id="rep-to-<?php echo $tweet->id_str; ?>" style="display: none; padding: 5px 0 5px 5px; margin: 5px; border-left: solid 1px #ccc; background: #eee; font-size: 8pt; line-height: 12pt;"></div>
					</td>
					<!-- <td align="center" valign="bottom"><img src="../wp-content/plugins/dashter/images/twitter_icon.png" width="20"></td> -->
				</tr>
				<?php 
				$lastID = $tweet->id_str;
			}
			echo "<tr><td colspan='3'><p align='center'>";
			echo "<a href='javascript:getTweets(\"" . $lastID . "\");' class='button-secondary' id='btn-" . $lastID . "'>Load More Tweets</a></p></td></tr>";
		} else {
			echo "<tr><td colspan='4'><p align='center'><img src='../wp-content/plugins/dashter/images/dfail.jpg'></p><p align='center'>Twitter service may be down.</p></td></tr>";
		}
	}
	
	// ******************** // 
	// *** POST A TWEET *** // 
	// ******************** // 
	
	if ($action == 'postTweet'){
		
		$tweet = stripslashes($_POST['twitter_message']); 	// echo $tweet;
		if ( strlen($tweet) > 140 ) {
			$tweet = substr($tweet, 0, 137);
			$tweet .= "...";
		}
		$in_reply_to_id = $_POST['reply_to'];
		if (!empty($in_reply_to_id)){
			$status_value = array( 'status' => $tweet, 'in_reply_to_status_id' => $in_reply_to_id );
		} else {
			$status_value = array( 'status' => $tweet );
		}
		
		// $status_value = array('status' => $tweet);
		print_r($status_value);
		$post_tweet = $conn->post('statuses/update', $status_value);
		if ($post_tweet){
			$twitter_post_id = $post_tweet->id_str;
			echo "RepTo: " . $in_reply_to_id . " " . $twitter_post_id . " Tweet Successful.";
		} else	{
			echo 'Uhm... Something went wrong. Dunno what... But something.';
		}
		
	}
	// ********************* //
	// *** QUEUE A TWEET *** //
	// ********************* //
	if ($action == 'dashter_queue'){
		$table_name = $wpdb->prefix . "dashter_queue";
		$tweet = stripslashes($_POST['twitter_message']);
		if ( strlen($tweet) > 140 ) {
			$tweet = substr($tweet, 0, 137);
			$tweet .= "...";
		}
		$replyTo = $_POST['tweet_replyto'];
		// Insert in queue database table
		$sqlInsert = "INSERT INTO $table_name (tweetContent, replyToTweetId, tweetStatus) VALUES ( %s, %s, %s )";
		$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweet, $replyTo, 'queued' ) ) ); 
		$success = $wpdb->insert_id;
		if (isset($success)){
		
			// Set cron hook to fire...
			// if (!wp_next_scheduled('dashter_t_cron_hook')){
			$timestamp = wp_next_scheduled( 'dashter_t_cron_hook' );
			if ($timestamp){ wp_unschedule_event( $timestamp, 'dashter_t_cron_hook'); }
			// Reset to queue delay from now...
			wp_schedule_event( time() + get_option('dashter_queue_frequency'), 'dashter', 'dashter_t_cron_hook' );	
			// }
		
			echo "queued.";
		} else {
			echo "failed.";
		}
	}
	
	// ******************************* // 
	// *** Show follow / following *** //
	// ******************************* //
	if($action == 'dashter_people'){
		// include(DASHTER_PATH . 'functions.php');
		$imgsize = '48';
		if ($_POST['imgsize']){
			$imgsize = $_POST['imgsize'];
		}
		$mysn = $_POST['myscreenname'];
		$followmode = $_POST['followmode'];
		$goPage = $_POST['page'];
		if (!$goPage){ $goPage = 1; }
		// FollowMode determines callback function for pagination
		switch ($followmode) {
			case "friends":
				$func = "peopleIFollow";
				break;
			case "followers":
				$func = "peopleWhoFollowMe";
				break;
			case "list":
				// Need list function here.
				break;
			default:
				$func = "peopleIFollow";
				break;
		}
		
		if ($conn){
			if ($followmode == 'list'){
				$listSlug = $_POST['listslug'];
				$membersParams = array(	'slug'				=> 	$listSlug,
										'owner_screen_name'	=>	$mysn );
				$listFriends = $conn->get('lists/members', $membersParams);
				$friendInfo = $listFriends->users;
			} else {
				$myFollow = $conn->get($followmode . '/ids');
				
				$myFollowList = "";
				$myFollowSize = sizeof($myFollow);
				
				$numPages = ceil($myFollowSize / 40);
				if (!empty($myFollow)){
					for ($i=0; $i < 40; $i++){
						$myFollowList .= $myFollow[((40*($goPage-1))+$i)] . ",";
					}
					$myFollowList = substr($myFollowList,0, (strlen($myFollowList)-1));
				
					$params = array ( 'user_id' => $myFollowList );
					$friendInfo = $conn->get('users/lookup', $params);
				}
			}
			$peopleIFollow = array();
			if (!empty($friendInfo)){
				foreach ($friendInfo as $friend){
					$peopleIFollow[] = array (	'screen_name'	=>	$friend->screen_name,
												'img_url'		=>	$friend->profile_image_url	);
				}
			}
			if (!empty($peopleIFollow)){
				foreach ($peopleIFollow as $person){
					display_user($person);
				}
			}
				?>
				<br class="clear" />
				<div class="tablenav" style="margin: 0 auto; text-align: center;">
			<div class="tablenav-pages" style="float: none; margin: 0 auto; text-align: center;">
				<span class="displaying-num">Total: <?php echo $myFollowSize; ?> 
				<?php 
					$low = ( ($goPage - 1) * 40) + 1;
					$high = ( ($goPage - 1) * 40) + 40;
					if ($high > $myFollowSize){
						$high = $myFollowSize;
					}
				?>
				Displaying <?php echo $low; ?>-<?php echo $high; ?></span>
				<?php 
					for ($i=1; $i < ($numPages+1); $i++){
						if ($i==5){
							echo '&nbsp;...&nbsp;';
							break;
						}
						?>
						<span class="page-numbers <?php if ($goPage == $i) { echo 'current'; } ?>">
						<?php 
							if ($goPage != $i){
						?>
						<a href="Javascript:<?php echo $func; ?>('<?php echo ($i); ?>');">
						<?php } ?>
						<?php echo ($i); ?></a></span>&nbsp;
						<?php 
					}
				?>
				
				<!-- <a href="Javascript:<?php echo $func; ?>();" class="next page-numbers">&raquo;</a> -->
				
			</div>
		</div>
				<?php 
		} else {
			echo "Connection failed.";
		}
	}

	// ************************ //
	// *** GET USER DETAILS *** //
	// ************************ //
	if($action == 'getUserDetails'){
		$screenname = $_POST['screenname'];
		$mysn = $_POST['myscreenname'];
		if($conn){
			// Get Friendship details //
			$fparams = array ( 	'source_screen_name' => $mysn, 
								'target_screen_name' => $screenname );
			$friendship = $conn->get('friendships/show', $fparams);
			$iFollowThem = $friendship->relationship->source->following;
			$theyFollowMe = $friendship->relationship->source->followed_by;
			
			// Get User details //
			$params = array( 'screen_name' => $screenname );
			$userResponse = $conn->get('users/lookup', $params);
			foreach ($userResponse as $userval){
				$userpic = $userval->profile_image_url;
				$userrealname = $userval->name;
				$userdescription = $userval->description;
				$followstat = $userval->following;
				$followercount = $userval->followers_count;
				$followscount = $userval->friends_count;
				$statuscount = $userval->statuses_count;
			}
			
			// Get Lists details //
			$listsParams = array (		'screen_name' =>	$screenname,
										'filter_to_owned_lists' =>	true );
			$twitterListResponse = $conn->get('lists/memberships', $listsParams);
			$theLists = $twitterListResponse->lists;
			if (!empty($theLists)){
				foreach ($theLists as $list){
					$aListed[] = $list->name;
				}
			}
			
			echo "<table width='100%'><tr><td valign='top'><img src='$userpic' width='96' style='padding: 0px 8px;'><br/>";
			echo "<form action='admin.php?page=dashter-user-profile' method='POST'>";
			echo "<input type='hidden' name='searchuser' value='" . $screenname . "'>";
			echo "<input type='submit' value='Full View' class='button-primary'></form></td>";
			echo "<td width='*' valign='top'>";
			echo "<b>$userrealname</b>";
			if (!$iFollowThem) {
				echo " <a onClick=\"modalFollowUser('" . $screenname . "');\" style=\"float: right;\" id=\"followButton\" class=\"button-primary\" style=\"font-size: 0.8em;\">Follow @$screenname</a>";
			}
			echo "<br/>";
			echo "<span style='font-size: 0.8em;'><i>$userdescription</i></span><br/>";
			echo "<b>Relationship: </b><br/>";
			echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc; text-align: center;'>";
			$green = '<img src="../wp-content/plugins/dashter/images/user_green.png" width="32">';
			$red = '<img src="../wp-content/plugins/dashter/images/user_red.png" width="32">';
			$mutual = '<img src="../wp-content/plugins/dashter/images/rel-mutual.png" width="32">';
			$following = '<img src="../wp-content/plugins/dashter/images/rel-following.png" width="32">';
			$follower = '<img src="../wp-content/plugins/dashter/images/rel-follower.png" width="32">';
			$none = '<img src="../wp-content/plugins/dashter/images/rel-none.png" width="32">';
			// * The four types of relationships... 
				if ($iFollowThem && $theyFollowMe){
					echo "$green $mutual $green <br/>Mutual. You both follow.";
				}
				if ($iFollowThem && !$theyFollowMe){
					echo "$green $following $red <br/>You follow them. @$screenname does not follow you.";
				}
				if (!$iFollowThem && $theyFollowMe){
					echo "$red $follower $green <br/>@$screenname follows you, but you don't follow them.";
				}
				if (!$iFollowThem && !$theyFollowMe){
					echo "$red $none $red <br/>There is no relationship between you and @$screenname.";
				}
			echo "</div>";
			echo "<b>Stats</b><br/>";
			echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc;'>";
				
				echo "<b>Followers</b>: $followercount <br/>";
				echo "<b>Following</b>: $followscount <br/>";
				echo "<b>Statuses</b>: $statuscount <br/>";
			echo "</div>";
			
			// Lists //
			echo "<b>Lists</b><br/>";
			if (!empty($aListed)){
				echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc;'>";
				foreach ($aListed as $list){
					echo "&#187; <b>" . $list . "</b> ";
				}
				echo "</div>";
			} else {
				echo "<i>You have not added @$screenname to any lists.</i><br/>";
			}
			
			echo "<b>Actions</b>";
			echo "<div style='margin: 10px; padding: 10px; border: solid 1px #ccc;'>";
				echo " <a class='button-primary'>@ Mention</a> ";
				if ($theyFollowMe){
					echo " <a class='button-primary'>Direct Msg</a> ";
				}
			echo "</div>";
			
			echo "</td></tr></table>";
			echo "<div style='visibility: hidden; display: none;'>";
			echo "<pre>";
			// print_r($profileimage);
			echo "</pre></div>";
			
			// print_r($userResponse);
			
		} else {
			echo "Connection failed.";
		}
	}			
?>
