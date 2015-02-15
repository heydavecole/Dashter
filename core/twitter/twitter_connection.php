<?php 

class twitter_connection extends TwitterOAuth {
	
	var $connected;
	var $userdata;
	var $lastuser;
	
	function __construct() {
		
	}
	
	function init () {
		// Twitter connection scripts
		/*
		$token = $_POST['oauth_token'];
		$secret = $_POST['oauth_secret'];	
		if (empty($token) && empty($secret)){
			if (function_exists('get_option')){
				$token = get_option('dashter_user_twitter_oauth_token');
				$secret = get_option('dashter_user_twitter_oauth_token_secret');
			}
		}
		*/
		
		if (!$this->connected) {
			// Connect to Twitter
			parent::__construct(
				get_option('dashter_consumer_key'),
				get_option('dashter_consumer_secret'),
				get_option('dashter_user_twitter_oauth_token'), 
				get_option('dashter_user_twitter_oauth_token_secret')
			);
			$this->connected = true;
		}
	}
	
	function get_userdata ( $user, $isPopup = true ) {
	
		$this->init();
		
		if ($this->lastuser == $user) {
			return $this->userdata;
		} else {
		
			$this->lastuser = $user;
			$this->userdata = array(
				'error' => ''
			);
		
			$rightnow = date("U");
			$lookupParams = array( 'screen_name' => $user, 'include_entities' => true );
			$getTheUser = $this->get('users/lookup', $lookupParams);
			$lookupUser = $getTheUser[0];
	
			if ( $lookupUser->name ) {
				// Fields
				
				// Locked profiles
				$protected = $lookupUser->protected;
				$following = $lookupUser->following;
				
				if ($protected == '1' && !$following){
					$this->userdata['private_acct'] = true;
				} else {
					$this->userdata['private_acct'] = false;
				}
				
				$this->userdata['user_name'] = $lookupUser->name;
				$imageURL = $lookupUser->profile_image_url;
				$this->userdata['image_url'] = str_replace("_normal", "_bigger", $imageURL); // upsize image per SZ
				$this->userdata['description'] = $lookupUser->description;
				$this->userdata['profile_url'] = $lookupUser->url;
				$this->userdata['followers_count'] = $lookupUser->followers_count;
				$this->userdata['following_count'] = $lookupUser->friends_count;
				$this->userdata['statuses_count'] = $lookupUser->statuses_count;
				
				// Latest status update
				$this->userdata['tweet_text'] = $lookupUser->status->text;
				$this->userdata['tweet_time'] = $lookupUser->status->created_at;
				
				$fparams = array (
					'source_screen_name' => get_option('dashter_twitter_screen_name'), 
					'target_screen_name' => $user
				);
				$friendship = $this->get('friendships/show', $fparams);
				$this->userdata['iFollowThem'] = $friendship->relationship->source->following;
				$this->userdata['theyFollowMe'] = $friendship->relationship->source->followed_by;
				
				
				// Add to 'recently viewed' option...
	
				$theUsers = get_option('dashter_t_recent_users');
				// This is a workaround for array_unique glitch // 
				foreach ($theUsers as $key => $recent){
					if ($recent['screen_name'] == $user){
						unset($theUsers[$key]);
					}
				}
						
				// Trim the array down to 10 results only...
				if (sizeof($theUsers) > 10){
					$theUsers = array_slice($theUsers, (sizeof($theUsers) - 9) );
				}
				$thisUser = array(	'screen_name' =>	$user,
									'img_url' =>	$imageURL );
				$theUsers[] = $thisUser;
				// $theUsers = array_unique($theUsers);	// Unique-ify the list (this has issues, dropped)
				update_option('dashter_t_recent_users', $theUsers);
							
				
				// Get Lists
				$lists = $this->get('lists');
				if ($lists){
					$myLists = array();
					foreach ($lists as $lkey => $list){
						foreach ( (array) $list as $onelist){
							if (isset($onelist->name)){
								$myLists[] = $onelist->name;
							}
						}
					}
					// set this as an option to avoid hitting twitter //
					// This should have some sort of scrub to re-check lists //
					update_option('dashter_t_lists', $myLists);
				}
				
				// Get recent topics + mentions
				if ((!$this->userdata['private_acct']) && (!$isPopup)){
					$recentParams = array(	'screen_name' => $user, 'count' => 120, 'include_entities' => true );
					$recent = $this->get( 'statuses/user_timeline', $recentParams );
					$recentCount = 0;
					
					if ($recent){
						foreach ($recent as $tweet){
							foreach ($tweet->entities->hashtags as $tag){
								$theTag = strtolower($tag->text);
								if ($this->userdata['popTags'][$theTag]){
									$this->userdata['popTags'][$theTag] = $this->userdata['popTags'][$theTag] + 1;
								} else {
									$this->userdata['popTags'][$theTag] = 1;
								}
							}
							$user_mentions = $tweet->entities->user_mentions;
							foreach ($user_mentions as $user){
								$theUser = strtolower($user->screen_name);
								if ($this->userdata['popUsers'][$theUser]){
									$this->userdata['popUsers'][$theUser] = $this->userdata['popUsers'][$theUser] + 1;
								} else {
									$this->userdata['popUsers'][$theUser] = 1;
								}
							}
							// *** NEW! *** User Images
							$user_images = $tweet->entities->media;
							$maxImgs = 0;
							if ($user_images){
								foreach ($user_images as $media){
									$maxImgs++;
									$theTweet = $tweet->text;
									$theImgUrl = $media->media_url;
									$this->userdata['userImgs'][] = array( 'url' => $theImgUrl, 'tweet' => $theTweet );
									if ($maxImgs == 4) { break; }
								}
							}
						}
					}
				} // else private account //
				if ($this->userdata['popTags']) {	arsort($this->userdata['popTags']); }
				if ($this->userdata['popUsers']) { arsort($this->userdata['popUsers']); }
				// Do a little more processing on users... Get top 20. 
				$h=0;
				$mentionlist = "";
				if ($this->userdata['popUsers']){
					foreach ($this->userdata['popUsers'] as $username=>$usercount){
						$h++;
						if ($h==21){ break; }
						$mentionlist .= $username . ",";
					}
				} else {
					// echo "This user has not mentioned anyone lately.";
				}
				$mentionlist = substr($mentionlist, 0, ( strlen($mentionlist)-1));
				if ( strlen($mentionlist) > 0 ) {
					$mentionparams = array ( 'screen_name' => $mentionlist);
					$mentionedUsers = $this->get('users/lookup', $mentionparams);
					$this->userdata['aMentioned'] = array();
					if ($mentionedUsers){
						foreach ($mentionedUsers as $user){
							$this->userdata['aMentioned'][] = array (
								'screen_name' => $user->screen_name,
								'img_url' 	  => str_replace("_normal", "_bigger", $user->profile_image_url ) 
							);
						}
					} 
				}
				
			} else {
				// Lookup failed. Return error msg.
				$this->userdata['error'] = "Sorry, user @$user doesn't appear to exist.";
				//unset($user);
			}
			
			return $this->userdata;
		
		}
		
	}
	
	function dashter_parse_tweet($tweet){
		// REGEX from http://saturnboy.com/2010/02/parsing-twitter-with-regexp/
		// Raw links
		$tweet = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@','<a href="$1" target="_new">$1</a>',$tweet);
		// User Mentions
		$boxURL = admin_url() . 'admin.php?page=dashter-user-details&screenname=';
		// $boxURL = "../wp-content/plugins/dashter/core/popups/user_details.php?screenname=";
		$tweet = preg_replace('/@(\w+)/','@<a href="' . $boxURL . '$1&TB_iframe=true" class="thickbox user-image" title="@$1">$1</a>',$tweet);
		// Hashtags
		$tweet = preg_replace('/\s+#(\w+)/',' <a href="admin.php?page=dashter&sterm=%23$1">#$1</a>',$tweet);
		return $tweet;
	}
	
	function dashter_parse_curatedTweet($tweet){
		$tweet = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@','<a href="$1" target="_new">$1</a>',$tweet);
		return $tweet;
	}
	
	function display_user($person, $type = 'full', $display_name = true, $bottom_margin = 0) {
		if ($person['img_url']) $img = $person['img_url'];
		if ($person['profile_image_url']) $img = $person['profile_image_url'];
		if ($type == 'full') $img = str_replace('_normal', '_bigger', $img);
		$thumb_size = 48;
		$margin = "0";
		if ($type == 'full') {
			$thumb_size = 73;
			$margin = "0 5px";			
		}
		$style = "width:" . $thumb_size . "px;margin:" . $margin . ";";
		?>
		<div class="dashter_user"<?php if ($bottom_margin) { ?> style="margin-bottom:<?php echo $bottom_margin; ?>px;"<?php } ?>>
			<a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $person['screen_name']; ?>&TB_iframe=true' class='thickbox user-image' style='<?php echo $style; ?>' title='@<?php echo $person['screen_name']; ?>'><img src="<?php echo $img; ?>" width="<?php echo $thumb_size; ?>" height="<?php echo $thumb_size; ?>" /></a>
			<?php if ($display_name) { ?>
			<div class="user-name"><a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $person['screen_name']; ?>&TB_iframe=true' class='thickbox user-name' title='@<?php echo $person['screen_name']; ?>'>@<?php echo $person['screen_name']; ?></a></div>
			<div class="full-view"><a href="<?php admin_url(); ?>?page=dashter-users&user=<?php echo $person['screen_name']; ?>">Full View</a></div>
			<?php } ?>
		</div>
		<?php 
	}
	
	function display_username($person) {
		?>
		<b style="font-size:13px;"><a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $person['screen_name']; ?>&TB_iframe=true' class='thickbox user-name' title='@<?php echo $person['screen_name']; ?>'>@<?php echo $person['screen_name']; ?></a></b> <span style="font-size:10px;">(<a href="<?php admin_url(); ?>?page=dashter-users&user=<?php echo $person['screen_name']; ?>">Full View</a>)</span>
		<?php
	}
	
	function timeBetween($start_date,$end_date,$no_sec="") {
		
		// *** SOURCE: http://www.linein.org/blog/2008/04/04/find-time-between-two-dates-in-php/ ***
		
		$diff = $end_date-$start_date;
 		$seconds = 0;
 		$hours   = 0;
 		$minutes = 0;

		if($diff % 86400 <= 0){$days = $diff / 86400;}  // 86,400 seconds in a day
		if($diff % 86400 > 0)
		{
			$rest = ($diff % 86400);
			$days = ($diff - $rest) / 86400;
     		if($rest % 3600 > 0)
			{
				$rest1 = ($rest % 3600);
				$hours = ($rest - $rest1) / 3600;
        		if($rest1 % 60 > 0)
				{
					$rest2 = ($rest1 % 60);
           		$minutes = ($rest1 - $rest2) / 60;
           		$seconds = $rest2;
        		}
        		else{$minutes = $rest1 / 60;}
     		}
     		else{$hours = $rest / 3600;}
		}
		if($days > 0){
			if ($days == 1) { $days = $days.' day, '; }
			if ($days > 1) { $days = $days.' days, '; }
		} else {
			$days = false;
		}
		if($hours > 0){$hours = $hours.' hours, ';}
		else{$hours = false;}
		if($minutes > 0){$minutes = $minutes.' minutes, ';}
		else{$minutes = false;}
		$seconds = $seconds.' seconds'; // always be at least one second			
		if ($no_sec == 'ignore'){
			return $days.''.$hours.''.substr($minutes, 0, (strlen($minutes)-2));
		} else {
			return $days.''.$hours.''.$minutes.''.$seconds;
		}
	}
	
}

global $twitterconn;
$twitterconn = new twitter_connection;

?>