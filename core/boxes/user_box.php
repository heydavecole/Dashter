<?php 

class user_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	var $ajax_callback = 'dashter_user_box';

	function __construct( $user = 'User', $location = 'dashter_top_column1', $slug = 'users_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = 'User @' . $user;
		$this->box_location = $location;	
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );			
	}

	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}
	
	public function display_meta_box () {

		global $twitterconn;
		$userdata = $twitterconn->get_userdata($this->my_user);
		$iFollowThem = $userdata['iFollowThem'];
		$theyFollowMe = $userdata['theyFollowMe'];
		$user = $this->my_user;
		?>
	<script type="text/javascript">
	function friendState(followUser){
		jQuery('#friend_status').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
		var data = { 
			action: '<?php echo $this->ajax_callback; ?>',
			iFollow: '<?php echo $iFollowThem; ?>',
			theyFollow: '<?php echo $theyFollowMe; ?>',
			screen_name: '<?php echo $user; ?>',
			followUser: followUser,
			request: 'friend_state'
		}
		jQuery.post(ajaxurl, data, function(response){
			jQuery('#friend_status').html(response);
		});
	}	
	friendState(false);
	</script>	
		<table width="100%">
			<tr valign="top">
				<td width="25%" align="left" style="padding: 10px; border-right: solid 1px #ccc;">
					<img src="<?php echo $userdata['image_url']; ?>" align="left" width="73" height="73" style='padding: 10px;'>
					<h2 style='padding: 0px 10px; margin: 0;'><?php echo $userdata['user_name']; ?></h2>
					<b>@<?php echo $this->my_user; ?></b>
					<br/><a target="_new" href="<?php echo $userdata['profile_url']; ?>"><?php echo $userdata['profile_url']; ?></a>
				</td>	
				<td width="25%" align="left" style="padding: 10px; border-right: solid 1px #ccc;">
					<i><?php echo $userdata['description']; ?></i>
					<?php
					if ($userdata['private_acct']){
						echo "This account is private. They must follow you before you can see their tweets.";
					}
					?>
					
				</td>	
				<td width="25%" align="left" style="padding: 10px; border-right: solid 1px #ccc;">
					<table width="100%" cellpadding="5" cellspacing="5">
						<tr>
							<td>
								<span style='font-size: 1.25em;'><b>Followers</b></span>
							</td>
							<td>
								<span style='font-size: 1.25em;'><?php echo number_format($userdata['followers_count'], 0, '.', ','); ?></span>
							</td>
						</tr>
						<tr>
							<td>
								<span style='font-size: 1.25em;'><b>Following</b></span>
							</td>
							<td>
								<span style='font-size: 1.25em;'><?php echo number_format($userdata['following_count'], 0, '.', ','); ?></span>
							</td>
						</tr>		
						<tr>
							<td>
								<span style='font-size: 1.25em;'><b>Statuses</b></span>
							</td>
							<td>
								<span style='font-size: 1.25em;'><?php echo number_format($userdata['statuses_count'], 0, '.', ','); ?></span>
							</td>
						</tr>																				
					</table>

				</td>
				<td width="25%" align="center" style="padding: 10px;" id="friend_status">
				
				</td>
			</tr>
		</table>
		<?php
	}

	public function process_ajax () {
		
		global $twitterconn;
		$twitterconn->init();
		
		$request = $_POST['request'];
		$iFollow = $_POST['iFollow'];
		$theyFollow = $_POST['theyFollow'];
		$screen_name = $_POST['screen_name'];
		$followUser = $_POST['followUser'];
		if ($followUser == 'true'){
			$params = array (	'screen_name' 	=>	$screen_name,
								'follow'		=> 	true );
			$createFriend = $twitterconn->post('friendships/create', $params);
			if ($createFriend){
				$iFollow = true;
			} else {
				echo "Sorry, your friendship was not created.";
			}
			
		}
		switch ($request){
			case 'friend_state':
				if ($iFollow && $theyFollow){
					$relsrc = "rel-mutual";
					$reldesc = "Mutual relationship. You follow each other.";
					$mysrc = "user_green";
					$theysrc = "user_green";
				}
				if ($iFollow && !$theyFollow){
					$relsrc = "rel-following";
					$reldesc = "You Follow @" . $this->my_user . ". They do not follow you.";
					$mysrc = "user_green";
					$theysrc = "user_red";
				}
				if (!$iFollow && $theyFollow){
					$relsrc = "rel-follower";
					$reldesc = "They follow you. You do not follow @" . $this->my_user . ".";
					$mysrc = "user_red";
					$theysrc = "user_green";
				}
				if (!$iFollow && !$theyFollow){
					$relsrc = "rel-none";
					$reldesc = "There is no relationship between you two.";
					$mysrc = "user_red";
					$theysrc = "user_red";
				}
				if (strtolower($screen_name) == strtolower(get_option('dashter_twitter_screen_name'))){
					?>
					<img src="../wp-content/plugins/dashter/images/user.png" width="64"><br/>
					This is your profile.
					<?php 									
				} else {
				?>
				<div id="rel_space">
					<img src="../wp-content/plugins/dashter/images/<?php echo $mysrc; ?>.png" width="64">
					<img src="../wp-content/plugins/dashter/images/<?php echo $relsrc; ?>.png" width="64">
					<img src="../wp-content/plugins/dashter/images/<?php echo $theysrc; ?>.png" width="64"><br/>
					<?php echo $reldesc; ?>
				</div>
					<?php if (!$iFollow){ ?>
					<div id="followbutton" style="padding: 10px;">
					<a class="button-primary" href="Javascript:friendState(true)">Follow @<?php echo $screen_name; ?></a>
				</div>
		<?php 
					}
				}
				break;
		}
		die();
	}

}