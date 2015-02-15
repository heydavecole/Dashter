<?php

class recommend_article_popup extends dashter_base {

	var $page_title = 'Dashter - Recommend Article';
	var $menu_title = 'Recommend Article';
	var $menu_slug = 'dashter-recommend-article';
	var $ajax_callback = 'dashter_recommend_article';
	
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
		if ($_GET['page'] == $this->menu_slug) {
			//$this->init_scripts();
		}
	}
	
	function init_submenu(){
		add_submenu_page( 'dashter-settings', $this->page_title, $this->menu_title, 'edit_pages', $this->menu_slug, array($this, 'display_page') );		
	}

	public function display_page () {
		global $wpdb;
		global $twitterconn;
		$twitterconn->init();
		$screenname = $_REQUEST['sn']; 		// User to recommend to
		$tweetID = $_REQUEST['tweetID']; 	// Tweet to reply to (if set)
		$postID = $_REQUEST['postID']; 		// Recommend a specific article (if set)
		
		if ($tweetID) {
			$singleParams = array ( 	'id'	=>	$tweetID	);
			$singleTweet = $twitterconn->get('statuses/show', $singleParams);
			if ($singleTweet){
				$screenname = $singleTweet->user->screen_name;
				$profileimg = $singleTweet->user->profile_image_url;
				$tweet = $singleTweet->text;
				$posted = date('F d Y g:ia', strtotime($singleTweet->created_at));
			} else {
				// Throw error.
			}	
		} else {
			$userParams = array ( 'screen_name' => $screenname );
			$singleUser = $twitterconn->get('users/show', $userParams);
			if ($singleUser){
				$profileimg = $singleUser->profile_image_url;
			} else {
				// Throw error.
			}
		}
		$postArgs = array ( 	'numberposts'	=>	10 );
		if ($postID){
			$postArgs['numberposts'] = 1;
			$postArgs['include'] = $postID;
		}	
		$thePosts = get_posts( $postArgs );
		
		$relLinkDef = get_option('dashter_t_relevantlink'); // Default relevant link 
		$relLinkDef = str_ireplace("~user~", "@" . $screenname, $relLinkDef);

		?>
		<style type="text/css">
		body { background: #fff; }
		</style>
		<script type="text/javascript">
			function getMoreArticles( ) {
				jQuery('#moreArticles').fadeOut('fast', function(){
					jQuery('#moreArticleSpan').append('<span id="moreloading">... grabbing those for ya.</span>');
					var offset = jQuery('input[name=theOffset]').val();
					var data = { 
								action: '<?php echo $this->ajax_callback; ?>',
								request: 'moreArts',
								offset: offset
					}
					jQuery.post(ajaxurl, data, function(response){
						jQuery('#moreloading').remove();
						if (response.indexOf('option') == -1) {
							jQuery('#moreArticles').fadeOut('fast', function(){
								jQuery('#moreArticleSpan').text('All articles loaded.');
							});
						} else {
							jQuery('#moreArticleSpan').fadeIn();
							jQuery('#selectArticle').append(response);
							var newOffset = parseInt(offset) + 10;
							jQuery('input[name=theOffset]').val( newOffset );
						}
					});
				});
			}
			function selectArticle() {
				jQuery('#send_tweet').css('visibility', 'visible');
				jQuery('#queue_tweet').css('visibility', 'visible');
				var articleTitle = (jQuery('#selectArticle').val()).replace("%27", "'");
				var theTweet = '<?php echo $relLinkDef; ?>';
				var newTweet = theTweet.replace("~full~", articleTitle);
				jQuery('#tweetContent').val(newTweet);
				tCount = ( 140 - ( jQuery('#tweetContent').val().length ) );
				jQuery('#pop_charcounter').text(tCount);
			}
			function killWindow(){
				self.parent.tb_remove();
			}
			function sendTweet(when){
				var tweetContent = jQuery('#tweetContent').val();
				if (jQuery('#tweetReplyTo').length) {
					var replyTo = jQuery('#tweetReplyTo').val();
				} else {
					var replyTo = null;
				}	
				var data = {
							action: '<?php echo $this->ajax_callback; ?>',
							request: when,
							tweetContent: tweetContent,
							tweetReplyTo: replyTo
				}
				jQuery.post(ajaxurl, data, function(response){
					if ( response.indexOf("Success") > -1 ) {
						var mymsg = 'You\'re so social! Your tweet was sent/queued: ' + tweetContent;
						var msgtype = 'updated';
					} else {
						var mymsg = 'Uhm. Something went wrong. The response was: ' + response;
						var msgtype = 'error';
					}	
					jQuery('#pop_statusresponse').removeClass('updated');
					jQuery('#pop_statusresponse').removeClass('error');
					jQuery('#pop_statusresponse').addClass(msgtype);
					jQuery('#pop_statusresponse').html('<p>' + mymsg + '</p>');
					jQuery('#tweetContent').val('');
					jQuery('#pop_charcounter').text('140');
					jQuery('#pop_statusresponse').slideDown('fast').delay(2000).slideUp('slow');
				});
			}
			
			jQuery(document).ready(function($) {
				$('#close_window').click(function(){
					$(killWindow);
				});
				$('#tweetContent').focus();
				var tCount = 140 - ( $('#tweetContent').val().length );
				$('#pop_charcounter').html(tCount);
				$('#tweetContent').keyup(function(){
					tCount = ( 140 - ( $('#tweetContent').val().length ) );
					$('#pop_charcounter').text(tCount);
				});
			});		
		
		</script>
		
		<div id="pop_statusresponse"></div>
		<?php if ($tweetID) { ?>
			<b>Original Tweet:</b> <br/>
			<div style="border: solid 1px #ccc; background: #eee; padding: 5px;" id="pop_originalTweet">
				<a href="<?php echo WP_PLUGIN_URL; ?>/dashster/popups/user-details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-image' title='@<?php echo $screenname; ?>'><img src="<?php echo $profileimg; ?>" align="left" width="32" style="margin: 0 10px 0 0;"></a>
				<b>@<a href="<?php echo WP_PLUGIN_URL; ?>/dashster/popups/user-details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-name' title='@<?php echo $screenname; ?>'><?php echo $screenname; ?></a>:</b> 
				<?php echo $tweet; ?><br/>
				<i><?php echo $posted; ?></i>
			</div>
			<input type='hidden' id='tweetReplyTo' value='<?php echo $tweetID; ?>'>
		<?php } else { ?>
			<b>Recommend Article to:</b><br/>
			<div style="border: solid 1px #ccc; background: #eee; padding: 5px;" id="pop_originalTweet">
				<a href="<?php echo WP_PLUGIN_URL; ?>/dashster/popups/user-details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-image' title='@<?php echo $screenname; ?>'><img src="<?php echo $profileimg; ?>" align="left" width="32" style="margin: 0 10px 0 0;"></a>
				<b>@<a href="<?php echo WP_PLUGIN_URL; ?>/dashster/popups/user-details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-name' title='@<?php echo $screenname; ?>'><?php echo $screenname; ?></a></b>
				<br class='clear' />
			</div>
			
		<?php } ?>
		<?php if ( !$postID ) { ?>
		<b>Choose an Article:</b> <br/>
		<select size="4" style="height: 6em; width: 100%;" id="selectArticle" onchange="javascript:selectArticle();">
		<?php } ?>
			<?php 
			foreach ($thePosts as $post){
				$myPostTags = get_the_terms( ($post->ID ), 'post_tag');
				
				$theTags = "";
				if (!empty($myPostTags)){
					foreach ($myPostTags as $tag){
						$currTag = $tag->slug;
						$currTag = strtolower($currTag);
						$currTag = str_replace("-", "", $currTag);
						$theTags .= "#" . $currTag . " ";
					}
				}
				
				$myShortLink = get_post_meta( intval($post->ID) , '_dashter_googlURL', true );
				if (empty($myShortLink)){
					$googlKey = get_option('dashter_googl_key');
					if (isset($googlKey)){
						// Shorten the permalink ...
						$postPermalink = get_permalink( $post->ID );
						$googer = new GoogleURLAPI($googlKey);
						$shortLink = $googer->shorten( $postPermalink );
						if (!empty($shortLink)){
							$postPermalink = $shortLink;
							// Set this in the database for next time.
							update_post_meta( intval( $post->ID ), '_dashter_googlURL', $postPermalink );
						}
					}
					
				} else {
					$postPermalink = $myShortLink;
				}
				if (!$postID){
					echo "<option value='" . str_replace("'", "%27", $post->post_title) . " " . $postPermalink . " " . $theTags . "'>" . $post->post_title . "</option>";
				} else {
					$postTweet = $post->post_title . " " . $postPermalink;
					$relLinkDef = str_replace( "~tags~" , $theTags, $relLinkDef );
					$relLinkDef = str_replace( "~link~" , $postPermalink, $relLinkDef );
					$relLinkDef = str_replace( "~full~" , $postTweet, $relLinkDef );
					$tempCharCounter = ( 140 - strlen($relLinkDef) );
				}
			}
		if (!$postID) { ?>
		</select>
		<input type="hidden" name="theOffset" value="10">
		<span style="float: right;" id="moreArticleSpan"><a id="moreArticles" href="javascript:getMoreArticles( 0 );">Get More Articles</a></span>
		<?php } else { ?>
		<div style="padding: 2px 0; margin: 2px 0; border: solid 1px #ccc; background: #eee; " ><b>Article</b> <?php echo $post->post_title; ?></div>
		<?php } ?>
		<b>Your Tweet:</b> <br/>
		<textarea style="width: 100%;" rows="4" id="tweetContent"><?php echo $relLinkDef; ?></textarea>
		<p align="right">
		<a href="javascript:killWindow();" style="float: left;" class="button-secondary">Close This</a>
		<span id="pop_charcounter"><?php echo $tempCharCounter; ?></span> 
		<a id="send_tweet" href="javascript:sendTweet('now');" class="button-primary" 
		<?php if (!$postID) { ?> style="visibility: hidden;" <?php } ?>>
		Post Tweet</a> &nbsp; 
		<a id="queue_tweet" href="javascript:sendTweet('queue');" class="button-primary" 
		<?php if (!$postID) { ?> style="visibility: hidden;" <?php } ?>>Queue Tweet</a></p>
		<?php
	}
	public function process_ajax() {
		$action = $_POST['request'];
		if ( $action == 'moreArts' ) {
			$offset = $_POST['offset'];
			$postArgs = array ( 	'numberposts'	=>	10,
									'offset' 		=>	$offset );
			$thePosts = get_posts( $postArgs );
			if (is_array($thePosts)) {
				foreach ($thePosts as $post){
					$myPostTags = get_the_terms( ($post->ID ), 'post_tag');
					
					$theTags = "";
					if (!empty($myPostTags)){
						foreach ($myPostTags as $tag){
							$currTag = $tag->slug;
							$currTag = strtolower($currTag);
							$currTag = str_replace("-", "", $currTag);
							$theTags .= "#" . $currTag . " ";
						}
					}
					
					$myShortLink = get_post_meta( intval($post->ID) , '_dashter_googlURL', true );
					if (empty($myShortLink)){
						$googlKey = get_option('dashter_googl_key');
						if (isset($googlKey)){
							$postPermalink = get_permalink( $post->ID );
							$googer = new GoogleURLAPI($googlKey);
							$shortLink = $googer->shorten( $postPermalink );
							if (!empty($shortLink)){
								$postPermalink = $shortLink;
								// Set this in the database for next time.
								update_post_meta( intval( $post->ID ), '_dashter_googlURL', $postPermalink );
							}
						}
						
					} else {
						$postPermalink = $myShortLink;
					}
					echo "<option value='" . str_replace("'", "%27", $post->post_title) . " " . $postPermalink . " " . $theTags . "'>" . $post->post_title . "</option>";
				}
			}
		} else {
			global $twitterconn;
			$twitterconn->init();					
			$tweetContent = stripslashes( $_POST['tweetContent'] );
			$tweetReplyTo = $_POST['tweetReplyTo'];
		}
		if ( ($action == 'now') && (trim($tweetContent)) ){
			$params = array ( 'status' => $tweetContent );
			if ($tweetReplyTo){
				$params['in_reply_to_status_id'] = $tweetReplyTo;
			}
			$post_tweet = $twitterconn->post('statuses/update', $params);
			if ($post_tweet){
				$twitter_post_id = $post_tweet->id_str;
				echo "Success! Tweet id = " . $twitter_post_id;
			} else	{
				echo 'Failure. Did not post successfully. Twitter may be down.';
			}	
		} elseif ( ($action == 'queue') && (trim($tweetContent)) ) {
			$mysn = get_option('dashter_twitter_screen_name');
			global $wpdb;
			$table_name = $wpdb->prefix . "dashter_queue";
			$tweet = stripslashes($_POST['tweetContent']);
			if ( strlen($tweet) > 140 ) {
				$tweet = substr($tweet, 0, 137);
				$tweet .= "...";
			}
			// Insert in queue database table
			$sqlInsert = "INSERT INTO $table_name (tweetContent, replyToTweetId, tweetStatus, postType, queueScreenName) VALUES ( %s, %s, %s, %s, %s )";
			if (get_option('dashter_queue_recommendations_auto')){
				$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweet, $tweetReplyTo, 'queued', 'auto', $mysn ) ) ); 
			} else {
				$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweet, $tweetReplyTo, 'queued', null, $mysn ) ) ); 
			}
			$success = $wpdb->insert_id;
			if (isset($success)){
				echo "Success!";
			} else {
				echo "Failure. The database may not be configured correctly.";
			}
		}
		die();
	}
}
new recommend_article_popup;
?>