<?php 

class user_popup extends dashter_base {
	
	var $page_title = 'Dashter - User Details';
	var $menu_title = 'User Details';
	var $menu_slug = 'dashter-user-details';
	var $ajax_callback = 'dashter_user_details';
	
	function __construct() {
		add_action( 'admin_init', array( &$this, 'init_css') );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
		
	}
	
	function init_css () {
		if ($_GET['page'] == $this->menu_slug) {
			wp_enqueue_style( 'dashter-hide-admin' );
		}
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) { }
	}
	
	function init_submenu(){
		add_submenu_page( 'dashter-settings', $this->page_title, $this->menu_title, 'edit_pages', $this->menu_slug, array($this, 'display_page') );		
	}
	
	public function display_page () {
		global $twitterconn;	
		$twitterconn->init();
		$this->my_user = $_REQUEST['screenname'];
		$userdata = $twitterconn->get_userdata($this->my_user, true);
		$iFollowThem = $userdata['iFollowThem'];
		$theyFollowMe = $userdata['theyFollowMe'];
		?>
		<style type="text/css">
		body { background: #fff; }
		</style>
		<script type="text/javascript">
		
			function closePopup( screenname ){
				if (screenname != null){
					self.parent.window.location = 'admin.php?page=dashter-users&user=' + screenname;
					self.parent.tb_remove();
				} else {
					self.parent.tb_remove();
				}
			}
		
			function do_mention(user){
				var p = parent;
				p.jQuery("#TB_window").remove();
				p.jQuery("body").append("<div id='TB_window'></div>");
				p.tb_show(null, '<?php echo admin_url(); ?>admin.php?page=dashter-new-tweet&mention=' + user + '&TB_iframe=true&height=180' );
			}
			function friendState(followUser){
				jQuery('#pop_relationship').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
				var data = { 
					action: '<?php echo $this->ajax_callback; ?>',
					iFollow: '<?php echo $iFollowThem; ?>',
					theyFollow: '<?php echo $theyFollowMe; ?>',
					screen_name: '<?php echo $this->my_user; ?>',
					followUser: followUser,
					request: 'friend_state'
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#pop_relationship').html(response);
					if (followUser){
						jQuery('#pop_followUser').fadeOut();
					}
				});
			}	
			jQuery(document).ready(function($) {
				friendState(false);
				$('#lists_save_status').hide(); // Hide save notice
				$('.listcheckbox').click(function(){
					var cssGreen = {
						'color' : 'green',
						'font-weight' : 'bold'
					}	
					var cssClear = {
						'color' : '',
						'font-weight' : ''
					}
					
					var listBoxSlug = $(this).attr('id');
					var listBoxState = $(this).attr('checked');
					if (listBoxState){
						var data = {
									action: '<?php echo $this->ajax_callback; ?>',
									request: 'addToList',
									listSlug: listBoxSlug,
									username: '<?php echo $this->my_user; ?>',
						}
						$.post(ajaxurl, data, function(response){
							$(this).parent('div').css(cssGreen);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).slideUp('slow');
							});
						});
					} else {
						var data = {
									action: '<?php echo $this->ajax_callback; ?>',
									request: 'addToList',
									remList: true,
									listSlug: listBoxSlug,
									username: '<?php echo $this->my_user; ?>',
						}
						$.post(ajaxurl, data, function(response){
							$(this).parent('div').css(cssClear);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).slideUp('slow');
							});
						});
					}	
				});
			});
			
		</script>
	
		<table width='100%'>
			<tr>
				<td valign='top' width='120' align="center">
					<p><img src='<?php echo $userdata['image_url']; ?>' style='padding: 0px 8px;' width="73"></p>
					<p><input type='button' value='Full View' class='button-primary' style='width: 95px;' onclick="javascript:closePopup('<?php echo $this->my_user; ?>')"></p>
					
					<?php if (!$userdata['iFollowThem']) { ?>
						<p><a id="pop_followUser" href="javascript:friendState(true);" class="button-primary" style="display: block; width: 95px;">Follow</a></p>
					
					<?php } ?>
					<p>
						<a href="Javascript:do_mention('<?php echo $this->my_user; ?>');" class="button-primary" title="Create a new Tweet" style="display: block; width: 95px;"><span style="color: #fff;">@Mention</span></a>
					</p>
				</td>
				
				<td width='*' valign='top' style="padding: 0 0 0 10px;">
					<div style="padding: 10px 0 3px; 0; margin: 10px 0 5px; 0; font-size: 24pt; font-weight: bold;"><?php echo $userdata['user_name']; ?></div>
					
					<div style='padding: 5px 0; margin: 2px 0; font-size: 10pt;'><?php echo $userdata['description']; ?></div>
					<div style='padding: 5px; margin: 2px 0; font-size: 8pt; font-style: italic; '>
						"<?php echo $userdata['tweet_text']; ?>"<br/>
						<span style='font-size: 8pt; color: #555; float: right;'><?php echo date("D, M jS y g:ia", strtotime($userdata['tweet_time'])); ?></span>
						</div>
					<table border="0" width="100%" cellpadding="0" cellspacing="0" style="margin: 5px 0px;">
						<tr>
							<td style="border-bottom: solid 1px #ccc; margin: 0 0 5px 0; padding: 0 0 5px 50px; color: #555; font-size: 8pt;">Stats</td>
							<td style="border-bottom: solid 1px #ccc; margin: 0 0 5px 0; padding: 0 0 5px 50px; color: #555; font-size: 8pt;">Relationship</td>
						</tr>
						<tr>
							<td width="35%" valign="top" style="margin: 5px 0 0 0; padding: 5px 0 0 0; text-align: center;">
								<b>Followers</b>: <?php echo $userdata['followers_count']; ?> <br/>
								<b>Following</b>: <?php echo $userdata['following_count']; ?> <br/>
								<b>Statuses</b>: <?php echo $userdata['statuses_count']; ?> <br/>
							</td>
							<td width="65%" valign="top" style="text-align: center; margin: 5px 0 0 0; padding: 5px 0 0 0;" id="pop_relationship"></td> 
						</tr>
					</table>

					<div>
					<div colspan="4" style="border-bottom: solid 1px #ccc; margin: 0 0 5px 0; padding: 0 0 5px 50px; color: #555; font-size: 8pt;">
							Lists
					</div>
					<span style='font-weight: bold;' id='lists_save_status'><img src='../wp-content/plugins/dashter/images/accept.png' width='16' height='16' align='absmiddle'> Saved!</span>
					</div>
					<?php 
					$listsParams = array (
						'screen_name' =>	$this->my_user,
						'filter_to_owned_lists' =>	true 
					);
					$twitterListResponse = $twitterconn->get('lists/memberships', $listsParams);
					$theLists = $twitterListResponse->lists;
					if (!empty($theLists)){
						foreach ($theLists as $list){
							$aListed[] = $list->name;
						}
					} else {
						$aListed = array();
					}
					$myLists = get_option('dashter_t_lists');
					$i=0;
					foreach ($myLists as $listname){
						?>
						<div style='width: 49%; float: left; padding: 0px 0;'>
						<input type="checkbox" name="lists[]" value="<?php echo $listname; ?>" class="listcheckbox" id="<?php echo str_replace(" ", "-", (strtolower($listname))); ?>"
						<?php if (in_array($listname, $aListed)) { echo "checked='checked'"; } ?> />
						
						<?php echo $listname; ?></div>
						<?php
						$i++;
					}
					?>
					<br class="clear" />
				</td>
			</tr>
		</table>
		
		<?php
	}
	
	function process_ajax () {
		global $twitterconn;
		$twitterconn->init();
		
		$request = $_POST['request'];
		$iFollow = $_POST['iFollow'];
		$theyFollow = $_POST['theyFollow'];
		$screen_name = $_POST['screen_name'];
		$followUser = $_POST['followUser'];
		if ($followUser == 'true' || $followUser == 1){
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
					<img src="../wp-content/plugins/dashter/images/user.png" width="32"><br/>
					This is your profile.
					<?php 									
				} else {
				?>
				<div id="rel_space">
					<img src="../wp-content/plugins/dashter/images/<?php echo $mysrc; ?>.png" width="32">
					<img src="../wp-content/plugins/dashter/images/<?php echo $relsrc; ?>.png" width="32">
					<img src="../wp-content/plugins/dashter/images/<?php echo $theysrc; ?>.png" width="32"><br/>
					<?php echo $reldesc; ?>
				</div>
				<?php 	
				}
				break;
			case 'addToList': 
				$userName = $_POST['username'];
				$listSlug = $_POST['listSlug'];
				$mysn = get_option('dashter_twitter_screen_name');
				$remList = $_POST['remList'];
				
				$params = array (	'slug'	=>	$listSlug,
									'owner_screen_name'	=>	$mysn,
									'screen_name'	=> $userName	);
				if ( ($remList == true) || ($remList == 1) ){
					$addToList = $twitterconn->post('lists/members/destroy', $params);
					echo "removed.";
				} else {
					$addToList = $twitterconn->post('lists/members/create', $params);
					echo "listed.";
				}
				
			}
		die();
	}
}
new user_popup;