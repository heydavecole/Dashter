<?php 

class users_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_friendfollow_box';
	
	function __construct( $title = 'Friends and Followers', $location = 'dashter_column1', $slug = 'users_box' ) {
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
	}
	
	function init_meta_box () {	
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function display_meta_box () {
		?>
		
		<script type='text/javascript'>
		function userBoxTitle( title ){
			jQuery('#users_box').children('h3').children('span').text(title);
		}
		jQuery(document).ready(function () {
			jQuery(peopleIFollow(1));
			jQuery('#showPeopleIFollow').click(function(){
				peopleIFollow(1);
			});
			jQuery('#showPeopleWhoFollow').click(function(){
				peopleWhoFollowMe(1);
			});
			
		});
		
		function spinFriends(){
			jQuery('#followspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
			return true;
		}
		
		function peopleIFollow(pg){
			userBoxTitle ( 'People I Follow' );
			spinFriends();
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_people',
				followmode: 'friends',
				page: pg
				}
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#followspace').html(response);
			});
		}
		
		function peopleWhoFollowMe(pg){
			userBoxTitle ( 'People who Follow Me' );
			spinFriends();
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_people',
				followmode: 'followers',
				page: pg
				}
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#followspace').html(response);
			});
		}
		function peopleInList(pg, listSlug){
			userBoxTitle ( 'People in ' + listSlug.replace('-',' ') + ' list' );
			spinFriends();
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_people',
				followmode: 'list',
				listslug: listSlug,
				page: pg
			}
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#followspace').html(response);
			});
		}
		</script>
		
		<ul class="subsubsub">
			<li><a id="showPeopleIFollow" style="cursor: pointer;">People I Follow</a> | </li>
			<li><a id="showPeopleWhoFollow" style="cursor: pointer;">People Who Follow Me</a></li>
			
		</ul>
		<br class="clear" />
		<div id="followspace">
		</div>
		<?php
	}
	
	public function process_ajax () {
		
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			
			case 'dashter_people':
				
				$imgsize = '48';
				if ($_POST['imgsize']){
					$imgsize = $_POST['imgsize'];
				}
				$mysn = get_option('dashter_twitter_screen_name');
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
				
				if ($twitterconn){
					if ($followmode == 'list'){
						$listSlug = $_POST['listslug'];
						$membersParams = array(	'slug'				=> 	$listSlug,
												'owner_screen_name'	=>	$mysn );
						$listFriends = $twitterconn->get('lists/members', $membersParams);
						$friendInfo = $listFriends->users;
					} else {
						$getFollows = $twitterconn->get($followmode . '/ids');
						
						// *** TWITTER API CHANGES *** // 
						// print_r($getFollows);
						// $myFollow = $getFollows->ids;
						$myFollow = $getFollows;
						$myFollowList = "";
						$myFollowSize = sizeof($myFollow);
						
						$numPages = ceil($myFollowSize / 40);
						if ($myFollow){
							for ($i=0; $i < 40; $i++){
								$myFollowList .= $myFollow[((40*($goPage-1))+$i)] . ",";
							}
							$myFollowList = substr($myFollowList, 0, (strlen($myFollowList)-1));
							$params = array ( 'user_id' => $myFollowList );
							$friendInfo = $twitterconn->get('users/lookup', $params);
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
							$twitterconn->display_user($person, 'full', true, 10);
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
			
				break;
				
		}
		die();
	}

}