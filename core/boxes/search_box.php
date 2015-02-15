<?php 

class search_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_search_box';
	
	function __construct( $title = 'Search for User', $location = 'dashter_column1', $slug = 'search_box' ) {
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
		function searchUser(){
			jQuery('#searchresults').slideUp('fast');
			var searchval = jQuery('#userSearchTerm').val();
			var showpage = 1;
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: 'dashter_people_search',
				searchterm: searchval,
				pg: showpage
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#searchresults').html(response).slideDown('slow');
			});
		}	
		
		function closeSearch(){
			jQuery('#searchresults').slideUp('fast');	
		}
		
		</script>
		<div style="text-align: center;">
		<label for="searchuser">Search for a user on Twitter</label>
		<input type="text" name="searchuser" id="userSearchTerm" tabindex=1>
		<input type="button" tabindex=2 class="button-primary" onclick="Javascript:searchUser();" value="Lookup">
		</div>
		<div id="searchresults"></div>
		<?php
	}
	
	public function process_ajax () {
	
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			case 'dashter_people_search':
			
				$sterm = $_POST['searchterm'];
				$page = $_POST['pg'];
				if (empty($page)){	$page = '1'; }
				$searchParams = array ( 	'q'			=>	$sterm,
											'per_page'	=> 	10,
											'page'		=>	$page	);
				$psResults = $twitterconn->get('users/search', $searchParams);
		
				$rightnow = date("U");
		
				if (!empty($psResults)){
					echo "<p align='center'><a class='button-secondary' href='Javascript:closeSearch();'>Hide Results</a></p>";
					foreach ($psResults as $person){
						$name = $person->name;
						$location = $person->location;
						$screenname = $person->screen_name;
						$profileimg = $person->profile_image_url;
						$desc = $person->description;
						$lastTweet = $person->status->text;
						$lastTweetTime = $person->status->created_at;
						$start = $person->created_at;
						$start = date('F jS Y', strtotime($start));
						$recent = $person->status->created_at;
						$recent = date("U", strtotime($recent));
						$friendcount = $person->friends_count;
						$followercount = $person->followers_count;
						$statuses = $person->statuses_count;
						
						?>
						<table width="100%" style="border-top: solid 1px #ccc; padding: 0;">
						<tr>
							<td valign="top" width="100">
							<a href='<?php echo DASHTER_URL; ?>core/popups/user_details.php?screenname=<?php echo $screenname; ?>' class='thickbox user-image' title='@<?php echo $screenname; ?>'>
							<img src="<?php echo str_replace('_normal', '_bigger', $profileimg); ?>" align="left" style="width: 73px; height: 73px; padding: 5px; margin: 5px; border: solid 1px #ccc;"></a>
							</td>
							<td width="*" style="padding: 5px 0;">
							<div style="padding: 5px 0;">
							<b><?php echo $name; ?></b> - 
							<a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $screenname; ?>&TB_iframe=true' class='thickbox user-name' title='@<?php echo $screenname; ?>'>@<?php echo $screenname; ?></a> [ 
							<a href="<?php echo get_admin_url() ?>admin.php?page=dashter-users&user=<?php echo $screenname; ?>">Full View</a> ]<br/>
							<span style="font-size: 8pt;">Since <?php echo $start; ?> | <?php echo $location; ?></span>
							
							<p><?php echo $twitterconn->dashter_parse_tweet($desc); ?></p>
							<?php if ($lastTweet) { ?>
							<p>"<i><?php echo $lastTweet; ?></i>"<i>
							<span style="color: #ccc;">
							<?php echo date ('M j Y g:ia', strtotime($lastTweetTime)); ?> 
							</span>
							</p>
							<?php } ?>
							<span style="font-size: 8pt;">
							Friends <b><?php echo $friendcount; ?></b> | Followers <b><?php echo $followercount; ?></b> | Statuses <b><?php echo $statuses; ?></b> | FSRatio: <b><?php if ($statuses > 0) { echo round(($followercount / $statuses), 2); } ?></b><br/>
							Last Activity: <?php echo $twitterconn->timeBetween($recent, $rightnow); ?></span>
							</div>
							</td>
						</tr>
						</table>
						<?php
					}
				} else {
					echo "<p align='center'>Twitter service may be down.</p>";
				}
				
				break;
		}
	die();
	}

}