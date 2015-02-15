<?php 

	class dashter_post_publish {
	
		function __construct() {
			add_action('new_to_publish', array( &$this, 'dashter_set_social'), 10, 1);
			add_action('draft_to_publish', array( &$this, 'dashter_set_social'), 10, 1);
			add_action('pending_to_publish', array( &$this, 'dashter_set_social'), 10, 1);
			add_action('future_to_publish', array( &$this, 'dashter_set_social'), 10, 1);
		
			add_action('save_post', array( &$this, 'dashter_publish_post_social') );
		
		}
		
		function dashter_set_social( $post ){
			update_option('dashter_socialize_post', $post->ID );
		}
		
		function dashter_publish_post_social ( $post_id ) {
			$socialize_post = get_option('dashter_socialize_post');
			if ($socialize_post == $post_id){
				$postType = get_post_type( $post_id );
				if ($postType == 'post') {
					error_log('Post published. Run Dashter processes for post ' . $socialize_post);
					$pub_to_twitter = get_post_meta( $post_id, '_dashter_meta_pub_twitter', true );
					if ($pub_to_twitter == 'enable') {
						global $wpdb;
						global $twitterconn;
						$twitterconn->init();
						
						$baseTweet = get_option('dashter_t_newpostmessage');
						$myPostTags = get_the_terms($post_id, 'post_tag');
				
						// Custom Tweet (Override default)
						$customTweet = get_post_meta( $post_id, '_dashter_meta_customtweet_enabled', true );
						if ($customTweet == 'enabled'){
							$cus_tweetContent = get_post_meta( $post_id, '_dashter_meta_customtweet', true );
							if ( trim($cus_tweetContent) == "" ){ unset($customTweet);	}
							$cus_tweetLinkToPost = get_post_meta( $post_id, '_dashter_meta_linktopost_enabled', true );
							$cus_tweetIncludeTags = get_post_meta( $post_id, '_dashter_meta_includetags_enabled', true );	
						} else {
							unset($customTweet);
						}
						if ($customTweet){
							$baseTweet = $cus_tweetContent;
							if ($cus_tweetLinkToPost == 'enabled'){
								$baseTweet .= " ~link~ ";
							}
							if ($cus_tweetIncludeTags == 'enabled') {
								$baseTweet .= " ~tags~ ";
							}
						}

						// Get post details
						$pubPost = get_post( $post_id );
						$postTitle = $pubPost->post_title;
						$postPermalink = get_permalink( $post_id );
						
						$googlKey = get_option('dashter_googl_key');
						if (isset($googlKey)){
							// Shorten the permalink ...
							$googer = new GoogleURLAPI($googlKey);
							$shortLink = $googer->shorten($postPermalink);
							if (!empty($shortLink)){
								$postPermalink = $shortLink;
								update_post_meta ( $post_id , '_dashter_googlURL', $postPermalink );
							}
						}
						
						$theLink = $postPermalink;
						$theLink_len = strlen($theLink);
						
						$theFull = $postTitle . " " . $postPermalink;
						$theFull_len = strlen($theFull);
						
						$theTags = "";
						if ($myPostTags){
							foreach ($myPostTags as $tag){
								$currTag = $tag->slug;
								$currTag = strtolower($currTag);
								$currTag = str_replace("-", "", $currTag);
								$theTags .= "#" . $currTag . " ";
							}
						}
						$theTags_len = strlen($theTags);
						
						$theTweet = $baseTweet;
						$base_len = strlen($baseTweet);
						
						$has_link = strpos($baseTweet, '~link~');
						$has_full = strpos($baseTweet, '~full~');
						$has_tags = strpos($baseTweet, '~tags~');
						if ($has_link !== FALSE){
							$theTweet = str_replace('~link~', $theLink, $theTweet);
						}
						if ($has_full !== FALSE){
							if ( ( ($base_len - 6) + $theFull_len) > 138 ){
								// Too long; replace with the link...
								error_log('This should be clipping = ' . (129 - ( ($base_len) ) + ( $theLink_len ) ));
								$trimTitle = substr( $postTitle, 0,  ( 129 - ( ($base_len) + ( $theLink_len ) ) ) );
								$trimTitle .= "... " . $theLink;
								$theTweet = str_replace('~full~', $trimTitle, $theTweet);
							} else {
								// Should fit; replace with the full...
								$theTweet = str_replace('~full~', $theFull, $theTweet);
							}
						}
						if ( ($has_tags !== FALSE) && ($myPostTags)){	
							if ( ( ( strlen($theTweet) - 6 ) + $theTags_len ) > 139 ){
								$textlimit = 132 - ( strlen($theTweet) );
								error_log('Text limit is: ' . $textlimit);
								// too long, trim tags
								$shortTags = "";
								foreach ($myPostTags as $tag){
									if ( strlen($shortTags) < $textlimit){
										$newTag = $tag->slug;
										$newTag = strtolower($newTag);
										$newTag = "#" . str_replace("-","", $newTag) . " ";
										if ( (strlen($shortTags) + strlen($newTag)) < $textlimit ){
											$shortTags .= $newTag;
										}
										error_log('The tags are: ' . $shortTags . ' L=' . strlen($shortTags) );
									}
								}
								$theTweet = str_replace('~tags~', $shortTags, $theTweet);
							} else {
								$theTweet = str_replace('~tags~', $theTags, $theTweet);
							}
						}
						if ( ($has_tags !== FALSE) && (!$myPostTags)) {
							$theTweet = str_replace('~tags~', '', $theTweet);
						}
						
						// Just in case they accidentally included ~user~
						$theTweet = str_replace('~user~', '', $theTweet);
				
						// Final length save...
						if ( strlen($theTweet) > 139 ){
							$theTweet = substr($theTweet, 0, 136);
							$theTweet .= "...";
						}
						
						// Restore link					
						if ($has_link) { 
							if ( strpos($theTweet, $theLink) === false ){
								// Restore the link
								$theTweet = substr($theTweet, 0, ( 135 - (strlen($theLink)) ) );
								$theTweet .= "... ";
								$theTweet .= $theLink;
							}
						}
				
						// Post to twitter
						$status = array ( 'status' => $theTweet );
						$post_tweet = $twitterconn->post('statuses/update', $status);
						error_log('The tweet should be: ' . $theTweet);
						
						// *** USER MENTIONS ***
						$mysn = get_option('dashter_twitter_screen_name');
						if (!$mysn) { $mysn = null; }
						
						$myPostMentions = get_post_meta( $post_id, '_dashter_meta_mentioned_users', false );
						
						if ($myPostMentions){
							$mentionList = $myPostMentions[0];
						}	
						
						$table_name = $wpdb->prefix . "dashter_queue";	
						
						$mentionCount = count($mentionList);
						error_log('Count mentions: ' . $mentionCount);
						
						$mentionTweet = get_option('dashter_t_mentionedusers');
						
						$mentionTweet = str_replace('~full~', $theFull, $mentionTweet);
						$mentionTweet = str_replace('~link~', $theLink, $mentionTweet);
						$mentionTweet = str_replace('~tags~', $theTags, $mentionTweet);
						
						$mentionLength = ( 133 - ( strlen($mentionTweet) ) );
						error_log('Allowable length = ' . $mentionLength);
						$k=0;
						while ($mentionCount > 0){
							if ($mentionList) {
								$mentionGroup = "";
								foreach ($mentionList as $key => $mention){
									if (strlen($mentionGroup) < $mentionLength){
										$newMention = "@" . $mention . " ";
										error_log('Mention Group= ' . $mentionGroup . ' New mention = ' . $newMention);
										if ( (strlen($newMention) + strlen($mentionGroup) ) < $mentionLength ){
											$mentionGroup .= $newMention;
											$mentionCount--;
											unset($mentionList[$key]);
										}
									} else {
										break;
									}
								}
							}
							$k++;
							if ($k > 12){ break; } // Loop failsafe
							$myTweet = str_replace('~user~', $mentionGroup, $mentionTweet);
							error_log('My queued tweet is: ' . $myTweet);
							
							$myInsert = "INSERT INTO $table_name (tweetContent, tweetStatus, postType, queueScreenName) VALUES (%s, %s, %s, %s)";
							$wpdb->query( $wpdb->prepare( $myInsert, array ($myTweet, 'queued', 'auto', $mysn ) ) ); 
						}
					}
					
					delete_option('dashter_socialize_post');
				}
			}
		}
	}
new dashter_post_publish;

?>