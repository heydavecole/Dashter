<?php 

class topics_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_topics_box';
	
	function __construct( $title = 'Hot Topics', $location = 'dashter_column1', $slug = 'topics_box' ) {
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
	}
	
	function init_meta_box () {		
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function display_meta_box () {
		$mysn = get_option('dashter_twitter_screen_name');
		?>
		
		<script type='text/javascript'>
		jQuery(document).ready(function () {
			jQuery(getFriendlyTopics());
			
			jQuery('#showFriendTopics').click(getFriendlyTopics);
			jQuery('#showTrending').click(getTrending);
			jQuery('#showLists').click(getLists);
			jQuery('#showSearch').click(function(){
				getSearch();
			});
			jQuery('#showMore').click(getMore);
		});
		
		function spinWaiter(tabid){
			jQuery('#displayHotTopic').html('<p align="center"><img src="<?php echo DASHTER_URL; ?>images/dashter-ajax-loading.gif"></p>');
			jQuery('.nav-tab').removeClass('nav-tab-active');
			jQuery(tabid).addClass('nav-tab-active');
			return true;
		}
		
		function getFriendlyTopics(){
			jQuery(spinWaiter('#showFriendTopics'));
			var data = { action: '<?php echo $this->ajax_callback; ?>', request: 'showfriendtopics' }
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#displayHotTopic').html(response);
			});
		}
		
		function getTrending(){
			jQuery(spinWaiter('#showTrending'));
			var data = { action: '<?php echo $this->ajax_callback; ?>', request: 'showtrending' }
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#displayHotTopic').html(response);
			});
		}
		
		function getLists(){
			jQuery(spinWaiter('#showLists'));
			var data = { action: '<?php echo $this->ajax_callback; ?>', request: 'showlists' }
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#displayHotTopic').html(response);
			});
		}
		
		function getSearch(search_term){
			jQuery(spinWaiter('#showSearch'));
			var showsearch = '<label for="twitter_search">Search term:</label> <input type="text" name="twitter_search" id="twitter_search">';
			showsearch = showsearch + '<input type="button" onclick="javascript:newSearchTwitter();" value="Search" class="button-primary">';
			jQuery('#displayHotTopic').html(showsearch);
			jQuery('#twitter_search').focus();
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'recent_searches',
				searchTerm: search_term
				}
			jQuery.post(ajaxurl, data, function(response) {		
				jQuery('#displayHotTopic').slideDown('slow').append(response);
			});
		}
		
		function newSearchTwitter(){
			var sTerm = jQuery('#twitter_search').val();
			if (sTerm.length == 0){
				alert ('Whoa there. Searching for nothing will get you everything. And that\'s a paradox we just aren\'t equipped for.');
			} else {
				searchTwitter(sTerm);
				getSearch(sTerm);
			}
		}
		
		function getMore(){
			spinWaiter('#showMore');
			var moreBox = '<p>View Favorites: <input type="button" onclick="javascript:getFavorites();" value="Favorites" class="button-secondary"></p>';
			moreBox += '<p>View Mentions: <input type="button" onclick="javascript:searchTwitter(\'<?php echo "@" . $mysn; ?>\')" value="Mentions" class="button-secondary"></p>';
			jQuery('#displayHotTopic').html(moreBox);
		}
		
		function getFavorites(){
			if ( typeof showFavorites == 'function') {
				console.log('Found showFavorites function.');
				var dispFavorites = showFavorites();
			} else {
				console.log('Cannot find showFavorites function.');
			}
		}

		</script>
		
		<div class="nav-tab-wrapper">
			<a href="#" class="nav-tab" id="showFriendTopics">Friendly</a>
			<a href="#" class="nav-tab" id="showTrending">Trends</a>
			<a href="#" class="nav-tab" id="showLists">Lists</a>
			<a href="#" class="nav-tab" id="showSearch">Search</a>
			<a href="#" class="nav-tab" id="showMore">More...</a>
		</div>
		<div style="border: solid 1px #ccc; padding: 2px 10px;">
		<p id="displayHotTopic"></p>
		</div>

		<?php
	}
	
	public function process_ajax () {
	
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			case 'showfriendtopics':
				$params = array	(	'count'	=>	'199', 
									'include_entities' => 'true' );
				$iFollow = $twitterconn->get('statuses/home_timeline', $params);
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
				
				// Trending in Followers //
				if ( get_option('dashter_hide_trending') == 'disabled' ) {
					$myFollow = $twitterconn->get('followers/ids');
					$myFollowList = "";
					$myFollowSize = sizeof($myFollow);
					if ($myFollowSize > 99) { $myFollowSize = 99; }
							
					for ($i=0; $i < $myFollowSize; $i++){
						$myFollowList .= $myFollow[$i] . ",";
					}
					$myFollowList = substr($myFollowList,0, (strlen($myFollowList)-1));
					$params = array ( 'user_id' => $myFollowList, 'include_entities' => 1 );
					$userInfo = $twitterconn->get('users/lookup', $params);
					
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
							echo "<br/><b>Trending in people who follow me...</b> (Disable in <a href='admin.php?page=dashter-settings'>Settings</a>)";
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
				} else {
					echo "<br/> <b>Trending in people who follow me...</b> is disabled.";
					echo "<br/>Go to <a href='admin.php?page=dashter-settings'>Settings</a> to enable. <i>Increases page load time.</i>";
				}
				break;
			/*** LIST TOP TRENDS ***/
			case 'showtrending':
				
				$trending = $twitterconn->get('trends/current');
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
				$trending = $twitterconn->get('trends/daily');
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
				
				break;
			/*** SHOW LISTS ***/
			case 'showlists':
				
				$lists = $twitterconn->get('lists');
				if ($lists){
					echo "<table width='100%'>";
					$i = 0;
					foreach ($lists as $lkey => $list){
						foreach ( (array) $list as $onelist){
							if (isset($onelist->name)){
								if ($i==0) { echo "<tr>"; }
								echo "<td width='33%'><a href='Javascript:showList(\"" . $onelist->slug . "\", 0);' class='listSelect' id='" . $onelist->name . "'>" . $onelist->name . "</a> ( " . $onelist->member_count . " ) </td> ";
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
				
				break;
			/*** RECENT SEARCHES ***/
			case 'recent_searches':
				$recentSearches = get_option('dashter_recent_searches');
				if (empty($recentSearches)){ $recentSearches = array(); }

				$latestSearch = $_POST['searchTerm'];
				if ($latestSearch){
					$recentSearches[] = $latestSearch;
				}
				if (!empty($recentSearches)){
					$recentSearchesUnique = array_unique($recentSearches);
					if ((count($recentSearchesUnique)) > 9){
						$recentSearchesSliced = array_slice($recentSearchesUnique, ((count($recentSearchesUnique)) - 9), 9);
						unset($recentSearchesUnique);
						$recentSearchesUnique = $recentSearchesSliced;
					}
					update_option('dashter_recent_searches', $recentSearchesUnique);
					echo "<div style='padding: 5px 0; margin: 5px 0;'>";
					foreach ($recentSearchesUnique as $search){
						?>
						<span style='display: block; float: left; width: 33%; padding: 0; margin: 0; text-align: center;'>
							<a href="javascript:searchTwitter('<?php echo $search; ?>')"><?php echo $search; ?></a>
						</span>
						<?php 
					}
					echo "</div><br class='clear' />";
				}	
				
				break;
		}
	die();
	}
}