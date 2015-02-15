<?php 

class user_interests_management_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $ajax_callback = 'dashter_manage_user_interests';
	
	function __construct( $user = 'User', $title = 'Manage Interests', $location = 'dashter_column1', $slug = 'user_interests_management_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;		
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
	}
	
	function init_meta_box(){
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function display_meta_box () {
		?>
		<script type="text/javascript">
		function testPop(postID, screenName){
			if ( screenName != '' ) {
				var myLink = '<?php echo admin_url(); ?>admin.php?page=dashter-recommend-article&sn='+screenName+'&postID='+postID+'&TB_iframe=true&height=350';
				tb_show(null,myLink,null);
			}
		}
		function loadInterests(){
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							request: 'loadInterests' }
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#interest_response').html(response);
			});
		}
		loadInterests();
		function showByTag(tagID){
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							request: 'showByTag',
							tagID: tagID
			}
			jQuery.post(ajaxurl, data, function(response){
				jQuery('#interest_response').html(response);
			});
		}
		function delTag(userName, tagID){
			var data = { 	action: '<?php echo $this->ajax_callback; ?>',
							request: 'delTag',
							userName: userName,
							tagID: tagID
						}
			jQuery.post(ajaxurl, data, function(response){
				if ( response.indexOf('Success') > -1 ){
					loadInterests();
				}
			});
		}
		</script>
		<div id="interest_response"></div>
	<?php
	
		
	}
	public function process_ajax() {	
		global $wpdb;
		$action = $_POST['request'];
		switch ($action){
			case 'loadInterests':
				// Get Interests 
				$interests = get_option('dashter_user_interests');
				if ($interests){
					arsort($interests);
					if (count($interests) > 20 ) { $addPagination = true; }
					foreach ($interests as $user => $intArr){
						if (!empty($intArr)){
							?>
							<div >
							<p style="line-height: 1.5em;">
							<b><a href='<?php echo admin_url(); ?>admin.php?page=dashter-user-details&screenname=<?php echo $user; ?>&TB_iframe=true' class='thickbox user-name' title='@<?php echo $user; ?>'><?php echo $user; ?></a></b> (<a href="<?php admin_url(); ?>?page=dashter-users&user=<?php echo $user; ?>">Full</a>) 
								<?php 
								foreach ($intArr as $tagID){
										$theTag = &get_tag( $tagID, ARRAY_A, 'raw');
										$tagID = $theTag['term_id'];
										$tagName = $theTag['name'];
										echo " [ <a href=\"javascript:showByTag(" . $tagID . ")\">" . $tagName . "</a> <a href=\"javascript:delTag('" . $user . "'," . $tagID . ");\"><b>X</b></a> ] ";
								}
								?>
							</p>
							</div>
							<?php 
						} else {
							unset($interests[$user]);
						}	
					}
					update_option('dashter_user_interests', $interests);
					if (empty($interests)){
						echo "<p>You have not set any interests yet. You can add interests in an individual profile view. </p>";
					}
				} else {
					echo "<p>You have not set any interests yet. You can add interests in an individual profile view. </p>";
				}
				break;
			case 'showByTag':
				$tagID = $_POST['tagID'];
				if ($tagID){
					
					$theTag = &get_tag( $tagID, ARRAY_A, 'raw');
					$tagName = $theTag['name'];
					?>
					<span style="float:right;">
					<a href="javascript:loadInterests();">
					Back to List
					</a>
					</span>
					<?php
					echo "<h2>Tag: '" . $tagName . "'</h2>";
					?>
					<p>
					<?php 
					$interested = get_option('dashter_user_interests');
					if (is_array($interested)){
						foreach ($interested as $user => $tagArr){
							if ( in_array( $tagID, $tagArr ) ){
								$intUserArr[] = $user;
								$intUserList .= " <b>@" . $user . "</b>,"; 
							}
						}
					}
					$intUserList = substr($intUserList, 0, ( strlen($intUserList) - 1) );
					echo $intUserList;
					?>
					...might like these articles:</p>
					<?php
					
					$suggPostArgs = array ( 'numberposts' 	=> 20,
											'tag_id'		=> $tagID );
					$suggPosts = get_posts( $suggPostArgs );
					if ($suggPosts) {
						foreach ($suggPosts as $post){
							$postID = $post->ID;
							$postTitle = $post->post_title;
							echo "<p>Recommend <b>" . $postTitle . "</b> to ";
							echo "<select onchange='javascript:testPop($postID,this.options[this.selectedIndex].value);'>";
							echo "<option value=''>-Select-</option>";
							foreach ($intUserArr as $screen_name){
								echo "<option value='$screen_name'>@$screen_name</option>";
							}
							echo "</select>";
							$cats = get_the_category( $postID );
							$snippet = substr( strip_tags($post->post_content), 0, 140 ) . "...";
							?>
							<blockquote style="color: #999;">
							<?php echo date ( 'M j Y', strtotime( $post->post_date ) ); ?>
							<?php foreach ($cats as $cat) { echo "&#187; " . $cat->cat_name . " "; } ?>
							&#187; Comments: <?php echo $post->comment_count; ?> 
							&#187; <?php echo $snippet; ?>
							</blockquote>
							<?php 
							echo "</p>";
						}
					}
					?>
					
					<?php 
				} else {
					?>
					<p>Woops, something went wrong. <a href="javascript:loadInterests();">Go back</a>.</p>
					<?php
				}
				break;
			case 'delTag':
				$user = $_POST['userName'];
				$tagID = $_POST['tagID'];
				$userInterests = get_option('dashter_user_interests');
				if (is_array($userInterests)){
					$myTags = $userInterests[$user];
					foreach ($myTags as $key=>$tag){
						if ($tag == $tagID){
							$theKey = $key;
						}
					}
					unset($myTags[$theKey]);
					$userInterests[$user] = $myTags;
					update_option('dashter_user_interests', $userInterests);
					echo "Success.";
				}
				break;
		}
		die();
	}

	
}