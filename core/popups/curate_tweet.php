<?php

class curate_tweet_popup extends dashter_base {

	var $page_title = 'Dashter - Curate Tweet';
	var $menu_title = 'Curate Tweet';
	var $menu_slug = 'dashter-curate-tweet';
	var $ajax_callback = 'dashter_curate_tweet';
	
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
		global $wpdb;
		global $twitterconn;
		$twitterconn->init();
		$tweetID = $_REQUEST['tweetID'];
		$mysn = get_option('dashter_twitter_screen_name');
		
		// Load Categories
		$categories = get_categories();
		
		// Get Friendship details //
		$getParams = array (	'id'	=>	$tweetID,
								'include_entities' => true );
		$theTweet = $twitterconn->get('statuses/show', $getParams);
		$userData = array(	'real_name'		=>	$theTweet->user->name,
							'url'			=>	$theTweet->user->url,
							'img_url'		=>	$theTweet->user->profile_image_url,
							'screenname'	=>	$theTweet->user->screen_name	);
							
		// Add all the string processing in the future.
		$tweetContent = $theTweet->text;
		
		// Get tag + mention entities
		$theTags = $theTweet->entities->hashtags;
		if ($theTags){
			foreach ($theTags as $tag){
				$tTags[] = $tag->text;
			}
		}
		$theMentions = $theTweet->entities->user_mentions;
		if ($theMentions){
			foreach ($theMentions as $mention){
				$tMents[] = $mention->screen_name;
			}
		}
		
		$aCuratedPostArgs = array(	'numberposts'	=> 	-1,
									'post_status'	=>	'draft',
									'meta_key'		=>	'dashter_curated',
									'meta_value'	=>	'true' );
		$curPosts = get_posts($aCuratedPostArgs);
		
		$numCurPosts = sizeof($curPosts);
		if ($numCurPosts > 3) { $numCurPosts = 3; }
		?>
		
		<style type="text/css">
		#dashter_curatedcomments {
			font-family: 'Georgia', 'Times New Roman', Times, serif;
			font-size: 11pt;
		}
		body { background: #fff; }
		</style>
		<script type="text/javascript">
			function closePopup( returnPostID ){
				if (returnPostID != null){
					self.parent.window.location = 'post.php?post=' + returnPostID + '&action=edit';
					self.parent.tb_remove();
				} else {
					self.parent.tb_remove();
				}
			}
			console.log('Thickbox loaded + script loaded successfully.');
			function saveCuration(){
				// Clear all errors.
				jQuery('label').css('color', '#000000');
				
				var postReady = false;
				var select_post = jQuery('input:radio[name=select_post]:checked').val();
				if (select_post == null){ 
					// Try hidden
					select_post = jQuery('input:hidden[name=select_post]').val();
				}
				if (select_post != null){
					// they selected a checkbox.
					console.log('Post type selected.');
					if (select_post == 'existing') {
						console.log('Existing selected.');
						var post_selection = jQuery('select[name=post_selection]').val();
						if ( post_selection != null) {
							console.log('They selected an existing post.');
							postReady = true;
						} else {
							console.log('They did not select a post.');
							jQuery('label.#existing').css('color', '#ff1122');
							postReady = false;
						}
					}
					if (select_post == 'new') {
						console.log('New post chosen.');
						var post_title = jQuery('input[name=new_post_title]').val();
						var new_post_category = jQuery('select[name=new_post_category]').val();
						if ( post_title != '' ) {
							console.log('They have set a title.');
							postReady = true;
						} else {
							console.log('They have not set a title.');
							jQuery('label[for=new_post_title]').css('color', '#ff1122');
							postReady = false;
						}
					}
				} else {
					console.log('No post type selected.');
					jQuery('label[for=select_post]').css('color', '#ff1122');
					postReady = false;
				}
				
				var tweetTags = jQuery('select[name=tweetTags]').val();
				var tweetMentions = jQuery('select[name=tweetMentions]').val();
				var source_image = jQuery('input[name=source_image]').val();
				var source_name = jQuery('input[name=source_name]').val();
				var source_screen_name = jQuery('input[name=source_screen_name]').val();
				var source_content = jQuery('input[name=source_content]').val();
				var myComments = jQuery('textarea[name=myComments]').val();
				var tweetID = jQuery('input[name=tweetID]').val();
				
				var BlackbirdPie = jQuery('input[name=BlackbirdPie]').val();
				
				var data = {
							action: '<?php echo $this->ajax_callback; ?>',
							request: 'dashter_saveCuration', 
							select_post: select_post,
							post_selection: post_selection,
							post_title: post_title,
							new_post_category: new_post_category,
							tweetTags: tweetTags,
							tweetMentions: tweetMentions,
							source_image: source_image,
							source_name: source_name,
							source_screen_name: source_screen_name,
							source_content: source_content,
							myComments: myComments,
							tweetID: tweetID,
							BlackbirdPie: BlackbirdPie
							}
				console.log(data);
				if (postReady){
					
					jQuery.post(ajaxurl, data, function(response){
						console.log(response);
						// On success, change the display.
						var returnSuccess = response.indexOf('Success',0);
						if (returnSuccess == 0){
							alert('Uhm... Something went wrong. Sorry bout that.');
						} else {
							var returnPostStr = response.substring(0,((returnSuccess)));
							var returnPostID = parseInt(returnPostStr);
						}
						if (returnPostID > 0){
							jQuery('#curatePage').html('');
							var nextStep = '<p align="center" style="margin: 100px 0 0 0;"><img src="<?php echo DASHTER_URL; ?>images/dashter300.png"><br/><b>Success</b></p>';
							nextStep += '<p align="center"><a href="javascript:closePopup(' + returnPostID + ');" class="button-secondary" title="Edit the Post">Edit the Post</a> ';
							nextStep += '<a href="javascript:closePopup(null);" class="button-secondary" title="Close this Window">Close this Window</a></p>';
							jQuery('#curatePage').hide().html(nextStep).fadeIn();
						}
					});
				}
			}
		
		</script>
		<div id='curatePage'>
		<form method='POST' name='curateForm' id='curateForm'>
		<input type='hidden' name='submitCForm' value='true'>
		<input type='hidden' name='tweetID' value='<?php echo $tweetID; ?>'>
		<?php if ($curPosts) { ?>
			<input type='radio' name='select_post' value='existing'> 
			<label for='select_post' id='existing'> <b>Select Existing Post</b></label><br/>
			<p align='right' style='padding: 0; margin: 0;'>
			<select size='<?php echo $numCurPosts; ?>' multiple='false' style='width: 90%; height: 4em;' name='post_selection'>
			<?php 
			foreach ( (array) $curPosts as $post) {
				echo "<option value='" . $post->ID . "'>" . $post->post_title . "</option>";
			}
			?>
			</select></p>
			<input type='radio' name='select_post' value='new'> <label for='select_post'><b>Create a new Curated Post</b></label><br/>
		<?php } else { ?>
			<input type='hidden' name='select_post' value='new'>
			<b>Create a new Curated Post</b><br/>
		<?php } ?>
		
		<p align='right' style='margin: 0; padding: 0;'>
		<label for='new_post_title'>New Post Title:</label>
		<input type='text' name='new_post_title' style='width: 70%;'></p>
		<p align='right' style='margin: 0; padding: 0;'>
		<label for='new_post_category'>New Post Category:</label>
		<select name='new_post_category'>
		<option value=''>-Select-</option>
		<?php 
		if ($categories){
			foreach ($categories as $cat){
				echo "<option value='" . $cat->cat_ID . "'>" . $cat->name . "</option>";
			}
		}
		?>
		</select>
		</p>
		
		<br/>
		
		<b>The Tweet</b><br/>
		<div style='margin: 10px; padding: 10px; border: solid 1px #ccc; color: #555;'>
			<input type='hidden' name='source_image' value='<?php echo $userData['img_url']; ?>'>
			<input type='hidden' name='source_name' value='<?php echo $userData['real_name']; ?>'>
			<input type='hidden' name='source_screen_name' value='<?php echo $userData['screenname']; ?>'>
			<input type='hidden' name='source_content' value='<?php echo urlencode(strip_tags(addslashes($tweetContent))); ?>'>
			<img src='<?php echo $userData['img_url']; ?>' style='padding: 0px 10px; width='48' height='48' align='left'>
			<b><?php echo $userData['real_name']; ?></b> - @<?php echo $userData['screenname']; ?><br/>
			<?php echo $tweetContent; ?>
			<br class='clear' />
		</div>
		<?php if ( (get_option('dashter_blackbirdpie_curation') == 'enabled') && ( class_exists('BlackbirdPie') ) ) { ?>
		<p align="right">Curate with Blackbird Pie 
		<input type="checkbox" name="BlackbirdPie" value="enabled" checked="checked">
		</p>
		<?php } ?>
		<b>Your Commentary</b><br/>
		<div style='margin: 10px; padding: 10px; border: solid 1px #ccc; background-color: #ddd;'>
			<textarea style='width: 100%' name='myComments' id='dashter_curatedcomments'></textarea>
		</div>
		
		<b>Included Elements:</b><br/>
		<div style='margin: 10px; padding: 10px; border: solid 1px #ccc;'>
		<b>Tags: </b>
		<select style='visibility: hidden; display: none;' multiple='multiple' name='tweetTags'>
			<?php 
			if ($tTags){
				foreach ($tTags as $tag){
					$showTags .= "#" . $tag . " ";
					echo "<option value='$tag' selected='selected'>$tag</option>";
				}
			} else {
				$showTags .= "<i>This tweet has no tags.</i>";
			}
			?>
		</select>
		<?php echo $showTags; ?>
		<br/><b>Mentions: </b> 
		@<?php echo $userData['screenname']; ?> 
		<select style='visibility: hidden; display: none;' multiple='multiple' name='tweetMentions'>
		<option value='<?php echo $userData['screenname']; ?>' selected='selected'><?php echo $userData['screenname']; ?></option>
			<?php 
			if ($tMents){
				foreach ($tMents as $ment){
					$showMentions .= "@" . $ment . " "; 
					echo "<option value='$ment' selected='selected'>$ment</option>";
					// echo "<input type='hidden' name='tweetMentions[]' value='" . $ment . "'>";
				}
			} else {
				// echo "<i>Nobody was mentioned in this tweet.</i>";
			}
			?>
		</select>
		<?php echo $showMentions; ?>
		
		</div>
		</form>
		
		<p align='right'>
		<a class='button-primary' href='javascript:saveCuration();'>Save Curation</a>
		</p>
	
		</div>
		<?php 
	}
	
	public function process_ajax () {
		function dashter_parse_curatedTweet($tweet){
			$tweet = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@','<a href="$1" target="_new">$1</a>',$tweet);
			return $tweet;
		}
		
		$action = $_POST['request'];
		
		if ($action == 'dashter_saveCuration'){
			// Fields
			$postAge = $_POST['select_post'];
			if ($postAge == 'existing'){
				$state = 'update';
				$apost_id = $_POST['post_selection'];
				$post_id = (integer) $apost_id[0];
			}
			if ( ($postAge == 'new') || (!$postAge) ){
				$state = 'new';
				$post_title = $_POST['post_title'];
			}
						
			// Get Tags + Mentions
			$theTweetTags = $_POST['tweetTags']; 			// array
			$theMentions = $_POST['tweetMentions']; 		// array
			$source_image = $_POST['source_image'];
			$source_name = $_POST['source_name'];
			$source_screen_name = $_POST['source_screen_name'];
			$source_content = urldecode($_POST['source_content']);
			$myComments = $_POST['myComments'];
			
			$tweetID = $_POST['tweetID'];
			
			if ((class_exists('BlackbirdPie')) && ($_POST['BlackbirdPie'] == 'enabled')) {
				$writePost = "[blackbirdpie id='" . $tweetID . "']";
			} else {
				$writePost = "<blockquote class='curated'>";
				$writePost .= "<img src='" . $source_image . "' width='48' height='48' align='left' class='curated_tweet_img' alt='" . $source_name . "'>";
				$writePost .= $source_name . " - @<a href='http://twitter.com/$source_screen_name'>" . $source_screen_name . "</a> <br/>";
				$writePost .= dashter_parse_curatedTweet($source_content);
				$writePost .= "</blockquote>";
			}
			$writePost .= "<p>" . $myComments . "</p>";
			
			// Get existing content...
			if ($state == 'update'){
			
				// Get the existing post...
				$the_existing_post = get_post( $post_id );
				// Get the existing mention values...
				$the_existing_meta = get_post_meta( $post_id, '_dashter_meta_mentioned_users' );
				// Get the existing tags...
				$the_existing_post_content = $the_existing_post->post_content;
				
				// Scrub existing + create tags to append...
				$the_existing_tags = wp_get_post_tags( $post_id );
				if ( (!empty($theTweetTags)) && (is_array($theTweetTags))){
					foreach ($theTweetTags as $add_tag){
						$isExisting = false;
						if ($the_existing_tags){
							foreach ($the_existing_tags as $exist_tag){
								if ($add_tag == $exist_tag){
									$isExisting = true;
								}
							}
						}
						if (!$isExisting){
							$tagsList .= $add_tag . ",";
						}					
					}
					if ($tagsList){
						$tagsList = substr($tagsList, 0, (strlen($tagsList)-1) );
					}
				}
				// all the existing users in...
				if ($the_existing_meta){
					foreach ($the_existing_meta as $exist_meta){
						foreach ($exist_meta as $meta){
							$updateDashterMeta[] = $meta;
						}
						
					}
				}
				if ( (!empty($theMentions)) && (is_array($theMentions))){
					foreach ($theMentions as $add_mention){
						$isExisting = false;
						if ($the_existing_meta){
							foreach ($the_existing_meta as $eKey => $exist_meta){
								if ($add_mention == $exist_meta){
									$isExisting = true;
								}
							}
						}
						if (!$isExisting){
							$updateDashterMeta[] = $add_mention;
						} 
					}
				}
	
				$wp_update_vars = array();	
				$wp_update_vars['ID'] = intval($post_id);
				$wp_update_vars['post_content'] = $the_existing_post_content . $writePost;
				
				wp_update_post( $wp_update_vars );
				if ($tagsList){
					wp_set_post_terms ( $post_id, $tagsList, 'post_tag', true);
				}
				if ($updateDashterMeta){
					delete_post_meta ( $post_id, '_dashter_meta_mentioned_users' );
					add_post_meta ( $post_id, '_dashter_meta_mentioned_users', $updateDashterMeta );
				}
				
				$message .= "Updated post <a href='post.php?post=" . $post_id . "&action=edit'>$post_id</a>. ";
				if ((!empty($theTweetTags)) && (is_array($theTweetTags))){
					foreach ($theTweetTags as $tags){
						$message .= " #" . $tags;
					}
				}
				foreach ($theMentions as $mention){
					$message .= " @" . $mention; 
				}
				$thePostID = $post_id;
				
				update_post_meta( $post_id, 'dashter_curated', 'true');
			}
			
			if ($state == 'new'){
				global $current_user;
				get_currentuserinfo();
				$uid = $current_user->ID;
				if ((!empty($theTweetTags)) && (is_array($theTweetTags))){
					foreach ($theTweetTags as $tag){
						$csvTags .= $tag . ",";
					}
				}
				$csvTags = substr($csvTags, 0, (strlen($csvTags) - 1) );
				if (!empty($_POST['new_post_category'])){
					// $aPostCat = $_POST['new_post_category'];
					$category = array( intval($_POST['new_post_category']) );	
				} else {
					$category = array(1);
				}
				
				$prependPost = "<p>This post was generated by <a href='http://dashter.com/' target='_new'>Dashter</a></p>";
				
				$newPost = array(
				  'post_author' => $uid,
				  'post_content' => $prependPost . $writePost,
				  'post_status' => 'draft',
				  'post_title' => $post_title,
				  'post_category' => $category,
				  'post_type' => 'post',
				  'tags_input' => $csvTags
				);
	
				$new_post_id = wp_insert_post( $newPost );
				add_post_meta ( $new_post_id, '_dashter_meta_mentioned_users', $theMentions );
				add_post_meta ( $new_post_id, 'dashter_curated', 'true', true );
				$message .= "Updated post <a href='post.php?post=" . $new_post_id . "&action=edit'>$new_post_id</a>. ";
				$thePostID = $new_post_id;
			}
			$message .= " Success.";
			
			// Scrub list for favorite tweets that were curated.
			$curated_tweets = get_option('dashter_curated_tweets');
			$curated_tweets[$tweetID] = $thePostID; // append to array w new tweet / postid.
			update_option('dashter_curated_tweets', $curated_tweets);
			
			echo "$thePostID Success";
		}
		die();
	}
}
new curate_tweet_popup;
?>