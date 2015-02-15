<?php 

class dashter_new_tweet extends dashter_base {
	
	var $page_title = 'Dashter - New Tweet';
	var $menu_title = 'New Tweet';
	var $menu_slug = 'dashter-new-tweet';
	var $ajax_callback = 'dashter_new_tweet';
	
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
		
		$this->my_user = $_REQUEST['mention'];
		if (!empty($this->my_user)){
			$this->my_user = "@" . $this->my_user . " ";
		}
		?>
		<style type="text/css">
		body { background: #fff; }
		</style>
		<h3>Send a Tweet</h3>
		<div id="pop_statusresponse"></div>
		<textarea style="width: 99%;" id="tweetContent" name="tweetContent"><?php echo $this->my_user; ?></textarea>
		<script type="text/javascript">
			function popSendTweet(when){
				var tweetContent = jQuery('#tweetContent').val();
				var data = { 
							action: '<?php echo $this->ajax_callback; ?>',
							tweetContent: tweetContent,
							postTweet: when
				}
				jQuery.post(ajaxurl, data, function(response){
					if ( response.indexOf('Success') > -1 ){
						var mymsg = 'You\'re so social! Your tweet was posted/queued: ' + tweetContent;
						var msgtype = 'updated';
					} else {
						var mymsg = 'Damn, looks like we forgot to pay the Twitter bill. Something went wrong.';
						var msgtype = 'error';
					}
					jQuery('#pop_statusresponse').removeClass('updated');
					jQuery('#pop_statusresponse').removeClass('error');
					jQuery('#pop_statusresponse').addClass(msgtype);
					jQuery('#pop_statusresponse').html('<p>' + mymsg + '</p>');
					// Clear tweet window
					jQuery('#tweetContent').val('');
					jQuery('#pop_charcounter').text('140');
					// Notify user pretty style.
					jQuery('#pop_statusresponse').slideDown('fast').delay(2000).slideUp('slow');
				});
			}
			function killWindow(){
				self.parent.tb_remove();
			}
			
			jQuery(document).ready(function($) {
				$('#tweetContent').focus();
				var tCount = 140;
				$('#pop_charcounter').html(tCount);
				$('#tweetContent').keyup(function(){
					tCount = ( 140 - ( $('#tweetContent').val().length ) );
					$('#pop_charcounter').text(tCount);
				});
				
				$('#close_window').click(function(){
					$(killWindow);
				});
				
			});
		</script>
		<p align="center">
		<span style="float: left;"><input type="button" id="close_window" value="Close This"></span>
		<span style="float: right;">
		<span id="pop_charcounter"></span>
		<a href="javascript:popSendTweet('post_now');" id="send_tweet" class="button-primary">Send Tweet</a> | 
		<a href="javascript:popSendTweet('queued');" id="queue_tweet" class="button-primary">Queue Tweet</a>
		</p>
		<br class="clear" />
		<?php 
		
	}
	
	public function process_ajax () {
		global $twitterconn;
		$twitterconn->init();
		$tweetContent = stripslashes($_POST['tweetContent']);
		$whenPost = $_POST['postTweet'];
		if ($whenPost == 'post_now'){
			if (trim($tweetContent)){
				$params = array ( 'status' => $tweetContent );
				$post_tweet = $twitterconn->post('statuses/update', $params);
				if ($post_tweet){
					$twitter_post_id = $post_tweet->id_str;
					echo "Success! Tweet id = " . $twitter_post_id;
				} else	{
					echo 'Failure. Did not post successfully. Twitter may be down.';
				}
			}
		} elseif ( $whenPost == 'queued' ){
			$mysn = get_option('dashter_twitter_screen_name');
			global $wpdb;
			$table_name = $wpdb->prefix . "dashter_queue";
			$tweet = stripslashes($_POST['tweetContent']);
			if ( strlen($tweet) > 140 ) {
				$tweet = substr($tweet, 0, 137);
				$tweet .= "...";
			}
			// Insert in queue database table
			$sqlInsert = "INSERT INTO $table_name (tweetContent, replyToTweetId, tweetStatus, queueScreenName) VALUES ( %s, %s, %s, %s )";
			$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweet, null, 'queued', $mysn ) ) ); 
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

new dashter_new_tweet;