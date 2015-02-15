<?php 

class user_recent_engagement_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	var $ajax_callback = 'user_recent_engagement_box'; 
	
	function __construct( $user = 'User', $title = 'Recent Engagement', $location = 'dashter_column2', $slug = 'user_recent_engagement_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = 'Recent Engagement by @' . $user;
		$this->box_location = $location;		
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
	}
	
	function init_meta_box(){
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}
	public function process_ajax(){
		
		// *** NOTE: DO NOT REMOVE HTML COMMENTS ***
		// This is used to differentiate results in AJAX return processing
	
		$user = $_POST['user'];
		global $twitterconn;
		$userdata = $twitterconn->get_userdata($user, false);

		if (!empty($userdata['userImgs'])){
			foreach ($userdata['userImgs'] as $userImage){
				?>
				<div style="float: left; width: 47%; padding: 1.5%;"><a href="<?php echo $userImage['url']; ?>" title="<?php echo str_replace("\"", "'", $userImage['tweet']); ?>" class="thickbox"><img src="<?php echo $userImage['url']; ?>:thumb" class="userImageDisplay" style="width: 100%;" align="left" alt="<?php echo str_replace("\"", "'", $userImage['tweet']); ?>"></a></div>
				<?php 
			}
			echo "<div class='clear'></div>";
		}
		?>
		<!-- ENGAGEMENT -->
		<?php

		if (!empty($userdata['aMentioned'])){
			foreach ($userdata['aMentioned'] as $person){
				$twitterconn->display_user($person, 'full', true, 10);
			}
			echo '<div style="clear:both;"></div>';
		} else {
			if (!$userdata['private_acct']){
				echo "<p><blockquote>Hmm.. Looks like @" . $this->my_user . " hasn't mentioned anyone recently, or we had trouble collecting the data from twitter.</p>";
				echo "<p align='center'> <a href='javascript:getRecentEngagement();' class='button-primary'>Wanna try again?</a> </p></blockquote>";	
			} else {
				echo "@" . $this->my_user . "'s tweet mentions are private.";
			}
			echo '<div style="clear:both;"></div>';
		}
		?>
		<!-- TOPICS -->
		<?php 
		if (!$userdata['private_acct']){
			if ($userdata['popTags']){
				?>
				<table width='100%'>
				<p align="center"><i>Clicking a topic will take you to the main Dashter window.</i></p>		
				<?php
				$i=0;
				$j=0;
				foreach ($userdata['popTags'] as $tagname=>$tagcount){
					$j++;
					if ($j==19){
						break;
					}
					if ($i==0){ echo "<tr>"; }
					echo "<td><a href='admin.php?page=dashter&sterm=%23$tagname'>#$tagname</a> ($tagcount)</td>";
					$i++;
					if ($i==2){
						$i=0;
						echo "</tr>";
					}
				}
				?>
				</tr></table>
				<?php
			}
		} else {
			echo "<tr><td>@" . $this->my_user . "'s tweet topics are private.</td></tr>";
		}
		die();
	}
	
	public function display_meta_box () {
		?>
		<script type="text/javascript">
		function getRecentEngagement(){
			jQuery('#recentEngagement,#recentTrendingTopics,#recentImages').html('<p align="center"><img src="<?php echo DASHTER_URL; ?>images/dashter-ajax-loading.gif"></p>');
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							user: '<?php echo $this->my_user; ?>'
			}
			jQuery.post(ajaxurl, data, function(response){
				var splitImgs = response.indexOf('<!-- ENGAGEMENT -->');
				var splitResults = response.indexOf('<!-- TOPICS -->');
				if ( splitResults > -1 ){
					if ( splitImgs < 5 ) {
						jQuery('#recentImages').html('<p align="center">No images found</p>');
					} else {
						var recentImages = response.substring( 0, splitImgs );
						jQuery('#recentImages').html(recentImages);
					}
					var recentUsers = response.substring( splitImgs, splitResults);
					var recentTrending = response.substring( splitResults );
					
					jQuery('#recentEngagement').html(recentUsers);
					
					if ( recentTrending.length < 20 ){
						jQuery('#user_trending_topics_box').html('<p align="center">No topics found</p>');
					} else {
						jQuery('#recentTrendingTopics').html(recentTrending);	
					}
				}
			});
		}
		jQuery(document).ready(function(){
			getRecentEngagement();
		});
		</script>
		<div id="recentEngagement"></div>
		<?php 
	}

}