<?php 

class user_interests_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	var $ajax_callback = 'dashter_user_interests';
	
	function __construct( $user = 'User', $title = 'Interests', $location = 'dashter_column3', $slug = 'user_interests_box' ) {
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
			function interestSpinner(){
				jQuery('#tagspace').html('<p align="center"><img src="../wp-content/plugins/dashter/images/dashter-ajax-loading.gif"></p>');
			}
			function destroyTag(tagID){
				interestSpinner();
				var data = { 
					action: '<?php echo $this->ajax_callback; ?>',
					user: '<?php echo $this->my_user; ?>',
					tag: tagID,
					destroy: true
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#tagspace').html(response);					
				});				
			}
			function createTag(tagID){
				interestSpinner();
				var data = {
					action: '<?php echo $this->ajax_callback; ?>',
					user: '<?php echo $this->my_user; ?>',
					tag: tagID,
					destroy: false
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#tagspace').html(response);
				});
			}
			function matchTrending(){
				interestSpinner();
				var data = {
					action: '<?php echo $this->ajax_callback; ?>',
					request: 'matchTrending',
					user: '<?php echo $this->my_user; ?>'
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#tagspace').html(response);
				});
			}
			jQuery(document).ready(function($) {
				
				interestSpinner();
				var data = { 
					action: '<?php echo $this->ajax_callback; ?>',
					user: '<?php echo $this->my_user; ?>'
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery('#tagspace').html(response);
				});
				
				$('#tagChoice').change(function(){
					var tagSelection = $('#tagChoice option:selected').attr('value');
					console.log('You selected: ' + tagSelection);
					createTag(tagSelection);
				});
			});
		</script>
		<p>Save <?php echo $this->my_user; ?>'s interests. This is a great way to keep article recommendations handy. Anytime you publish a new article, you can check the relationship window to see new recommendations you can send.</p>

		<?php 
		$theTags = get_tags();
		
		echo "<select id='tagChoice'>";
		echo "<option>Tags</option>\n";
		foreach ($theTags as $tag)
		{
			echo "<option name=\"" . $tag->name . "\" value=\"";
			echo $tag->term_id;
			echo "\">".$tag->name."</option>\n";
		}
		echo "</select>";
		?>
		<div id="tagspace" style="padding: 15px;"></div>
		
		<a href='javascript:matchTrending();'>Match Trending Tags</a>

		<?php
	}
	public function process_ajax() {
		global $wpdb; 
		
		$user = $_POST['user'];
		$tag = $_POST['tag'];
		$destroy = $_POST['destroy'];
	
		if ($_POST['request'] == 'matchTrending'){
			$theTags = get_tags();
			if (is_array($theTags)){
				foreach ($theTags as $tag){
					$wpTagID = $tag->term_id;
					$wpTagNames[$wpTagID] = strtolower($tag->name);
				}
			}
			global $twitterconn;
			$twitterconn->init();
			$userdata = $twitterconn->get_userdata($this->my_user, false);
			if ( (!$userdata['private_acct']) && (is_array($userdata['popTags']) ) ){
				$j=0;
				foreach ($userdata['popTags'] as $tagname=>$tagcount){
					$j++;
					if ($j==19){
						break;
					}
					foreach ($wpTagNames as $wpTagID => $wpTag){
						if ( ( trim ( strtolower( $tagname ) ) == trim ( $wpTag ) ) || ( trim ( strtolower ( $tagname ) ) == trim ( str_replace (" ", "", $wpTag ) ) ) ) {
							$addInterest[] = $wpTagID;
						}
					}
				}
				if (is_array($addInterest)){
					$interests = get_option('dashter_user_interests');
					if (is_array($interests)){
						$myUserInterests = $interests[$user];
						if (!is_array($myUserInterests)) { $myUserInterests = array(); }
						foreach ($addInterest as $addTag){
							$myUserInterests[] = $addTag;
						}
						$myUserInterests = array_unique($myUserInterests);
						$interests[$user] = $myUserInterests;
						update_option('dashter_user_interests', $interests);
					}
				}
			}
			
		}
	
		$interests = get_option('dashter_user_interests');
		if (!$interests){ 
			$interests = array(); 
		}
		$myUserInterests = $interests[$user];
		if (!$myUserInterests) { $myUserInerests = array(); }
		if ( (isset($tag)) && ($destroy == 'false') ){
		
			$myUserInterests[] = $tag;
			$myUserInterests = array_unique($myUserInterests);
			$interests[$user] = $myUserInterests;
			
			update_option('dashter_user_interests', $interests);
		} 
		if ( (isset($tag)) && ($destroy == 'true') ) {
			if (!empty($myUserInterests)){
				foreach ($myUserInterests as $intKey => $intVal){
					if ($intVal == $tag){
						$destroyKey = $intKey;
					}
				}
			}
			if (isset($destroyKey)){
				unset( $myUserInterests[$destroyKey] );
				$myUserInterests = array_values($myUserInterests);
			}
			$interests[$user] = $myUserInterests;
			update_option('dashter_user_interests', $interests);
		}	
		if (!empty($myUserInterests)){
			$myUserInterests = array_unique($myUserInterests);
			foreach ($myUserInterests as $uInt){
				$theTag = &get_tag( $uInt, ARRAY_A, 'raw');
				$tagName = $theTag['name'];
				?>
				<span style="white-space: nowrap; line-height: 20pt;">[ <b><a href="javascript:destroyTag('<?php echo $uInt; ?>')">x</a></b> <?php echo $tagName; ?> ]</span>&nbsp;
				<?php 
			}
			
			$suggPostArgs = array ( 'numberposts' 	=> 5,
									'tag__in'		=> $myUserInterests );
			$suggPosts = get_posts( $suggPostArgs );
			if ($suggPosts) {
				echo "<p>" . $user . " might be interested in these articles: </p>";
				foreach ($suggPosts as $post){
					$postID = $post->ID;
					$postTitle = $post->post_title;
					echo "<p><a href='" . admin_url() . "admin.php?page=dashter-recommend-article&sn=" . $user . "&postID=" . $postID . "&TB_iframe=true&height=220' class='thickbox'>" . $postTitle . "</a></p>";
				}
			}
			
		} else {
			echo "<p>You haven't saved any interests for @$user yet.</p>";
		}	
	
		die();
	}

	
}