<?php 

class dashter_queue extends dashter_base {
	
	var $page_title = 'Dashter Queue';
	var $menu_title = 'Queue';
	var $menu_slug = 'dashter-queue';
	var $ajax_callback = 'dashter_queue_page';
	
	function __construct() {
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			add_thickbox();
		}
	}
	
	public function display_page () {
		/*
		// dashter_test_queue Is a report from the queue after it processes
		// Use for testing & ensuring queue operations 
		$testCron = get_option('dashter_test_queue');
		echo $testCron;
		*/
		global $wpdb;
		$table_name = $wpdb->prefix . "dashter_queue";
				
		$dformat = 'n/j/y g:i:sa';
		
		if ( isset($_POST['save-edit-id']) && isset($_POST['save-edits']) ){
			$saveID = $_POST['save-edit-id'];
			$saveID = str_replace("tweet-", "", $saveID);
			$saveTweet = stripslashes($_POST['save-edits']);
			if (strlen($saveTweet) > 139){
				$saveTweet = substr($saveTweet, 0, 136);
				$saveTweet .= "...";
			}
			$wpdb->update( $table_name, array( 'tweetContent' => $saveTweet ), array( 'id' => $saveID ), array( '%s' ) );
			$message = "The tweet at ID $saveID has been updated: <i> $saveTweet </i>";
		}
		if (isset($_POST['deleteTweet'])){
			$delID = $_POST['deleteTweet'];
			// Change status to 'deleted'
			$wpdb->update( $table_name, array( 'tweetStatus' => 'deleted' ), array( 'id' => $delID ), array( '%s' ) );
			$message = "The tweet at ID $delID has been marked as deleted.";		
		}

		if (isset($_REQUEST['restoreTweet'])){
			$restoreID = $_REQUEST['restoreTweet'];
			$restoreTweet=true;
		} else {
			$restoreTweet=false;
		}

		if (isset($_POST['rescheduleTweet']) || $restoreTweet==true){
			$resID = intval($_POST['rescheduleTweet']);
			if ($restoreTweet==true){ $resID = $restoreID; }
			$query_queue = "SELECT id, tweetContent, replyToTweetID, tweetStatus, postTime FROM $table_name WHERE id = $resID";
			$queueResult = $wpdb->get_results($query_queue);
			if ($queueResult){
				$tweetContent = $queueResult[0]->tweetContent;
				$replyToTweetID = $queueResult[0]->replyToTweetID;
				$postType = $queueResult[0]->postType;
				$queueScreenName = $queueResult[0]->queueScreenName;
				$wpdb->query("DELETE FROM $table_name WHERE id = $resID");
				$sqlInsert = "INSERT INTO $table_name (tweetContent, replyToTweetId, tweetStatus, postType, queueScreenName) VALUES ( %s, %s, %s, %s, %s )";
				$wpdb->query( $wpdb->prepare( $sqlInsert, array ($tweetContent, $replyToTweetID, 'queued', $postType, $queueScreenName ) ) ); 
				$success = $wpdb->insert_id;
				
				$message = "Your selected tweet has been moved to the end of the queue";
			} else {
				$message = "Hmmm... Your selected tweet has not been rescheduled.";
			}
		}
		
		$showType = $_GET['show'];
		$pageShow = $_GET['pg'];
		if (!$pageShow){
			$pageShow = 1;
		}
		if (!$showType){
			$showType = 'queued';
		}
	
	?>
		<style type="text/css">
			.row-editor {
				width: 99%;
				height: 2em;
			}
			.row-save-button {
				float: right;
				clear: both;
			}
		</style>
		<script type="text/javascript">
			function closeReplyRow(rowID){
				var replyRow = '#rep-to-' + rowID;
				if ( jQuery(replyRow).is(':visible') ) {
					jQuery(replyRow).slideUp('fast');
				}
			}
			function showReplyToTweet(tweetID, rowID){
				var replyRow = '#rep-to-' + rowID;
				var preResponse = 'Your tweet was in response to this one: <br/>';
				var postResponse = '<br/><a href="javascript:closeReplyRow(' + rowID + ');">Close This</a> ';
				var data = { 
							action: '<?php echo $this->ajax_callback; ?>',
							tweetID: tweetID
				}
				jQuery.post(ajaxurl, data, function(response){
					jQuery(replyRow).html(preResponse + response + postResponse);
					jQuery(replyRow).slideDown('fast');
				});
			}
			function saveEdit(rowid){
				console.log('Success? ' + rowid);
			}
			jQuery(document).ready(function($){
				$('.replyToContent').hide();
				$('.editTweet').click(function(){
					
					var theItem = $(this).attr('id');
					var theTweet = $('#disp-' + theItem).text();
					var theEditor = ('#edit-disp-' + theItem);
					
					var writeEditor = "<form method='post'><input type='hidden' name='save-edit-id' value='" + theItem + "'><textarea class='row-editor' id='editor-" + theItem + "' name='save-edits'>" + theTweet + "</textarea> <input type='submit' class='row-save-button' id='save-edit-" + theItem + "' value='Save Edit'></form>";
					
					$(theEditor).html(writeEditor);
				});
			});
			
		</script>
	
	<?php $this->display_wrap_header( $this->page_title ); ?>
	
		<div>
		<?php 
			function qToTime( $qVal ){
				if ($qVal == 0){	
					$qTime = "12:00 am";
				}
				if ($qVal >= 12){
					$qTime = ($qVal - 12) . ":00 pm";
				} else {
					$qTime = $qVal . ":00 am";
				}
				return $qTime;
			}
			$qFreq = get_option('dashter_queue_frequency');
			$qRuntime = get_option('dashter_queue_runtime');
			if (!$qFreq){
				$qFreq = 1800; // 30 minutes
			} 
			if (!is_array($qRuntime)){
				$qStart = 0;
				$qStop = 0;
			} else {
				$qStart = intval( $qRuntime['start'] );
				$qStop = intval( $qRuntime['stop'] );
			}
			
		?>
		<p><b>Queue Settings: </b> Post every <b><?php echo ($qFreq / 60); ?></b> minutes. 
		Post between <b><?php echo qToTime($qStart); ?></b> and <b><?php echo qToTime($qStop); ?></b>. 
		<i>Change in <a href="admin.php?page=dashter-settings">Settings</a></i>
		</p>
		</div>
	
		<h3 class="nav-tab-wrapper">
			<a href="admin.php?page=dashter-queue&show=queued" class="nav-tab <?php if($showType=='queued'){ echo 'nav-tab-active'; } ?>">In the Queue</a>
			<a href="admin.php?page=dashter-queue&show=sent" class="nav-tab <?php if($showType=='sent'){ echo 'nav-tab-active'; } ?>">Recently Sent</a>
			<a href="admin.php?page=dashter-queue&show=deleted" class="nav-tab <?php if($showType=='deleted'){ echo 'nav-tab-active'; } ?>">Deleted</a>
		</h3>
		<?php 
		if ($message){
			?>
			<br />
			<div class="updated" id="message"><?php echo $message; ?></div>
			<?php
		}
		?>
		<table class="widefat">
			<thead>
				<th scope="col">Post Content</th>
				<th scope="col">Reply To</th>
				<?php if($showType=='queued' || $showType=='deleted'){ 	echo '<th scope="col">Actions</th>'; } ?>
				<?php if($showType=='sent'){	echo '<th scope="col">Sent At</th>'; } ?>
			</thead>
			<tbody>
				<?php 
				
				// Get posts remaining in the queue
				
				$rpp = 20;
				$get_total_query = "SELECT COUNT(id) as IdCount FROM $table_name WHERE tweetStatus = '$showType'";
				$countResult = $wpdb->get_results($get_total_query);
				if ($countResult){
					$idCount = $countResult[0]->IdCount;
					$resultsPages = ceil($idCount / $rpp);
				}
				
				$query_queue = "SELECT id, tweetContent, replyToTweetID, tweetStatus, postTime FROM $table_name WHERE tweetStatus = '$showType'";
				if ($showType=='sent'){
					$query_queue .= " ORDER BY postTime DESC";
				} else {
					$query_queue .= " ORDER BY id ASC";
				}
				
				// Pagination
				$query_queue .= " LIMIT $rpp";
				if ($pageShow){
					$query_queue .= " OFFSET " . (($pageShow - 1) * $rpp);
				}
				
				$queueResult = $wpdb->get_results($query_queue);
				if ($queueResult){
					foreach ($queueResult as $row){
					?>
					<tr valign="top">
						<td id="edit-disp-tweet-<?php echo $row->id; ?>"><span id="disp-tweet-<?php echo $row->id; ?>"><?php echo $row->tweetContent; ?></span>
							<div style="margin: 2px 2px 2px 10px; padding: 3px 3px 3px 10px; border-left: solid 1px #ccc;" class="replyToContent" id="rep-to-<?php echo $row->id; ?>"></div>						
						</td>
						<td id="reply-to-status">
						<?php 
							if (!empty($row->replyToTweetID) && ($row->replyToTweetID <> 'false')){
								echo "This is a reply to <a href='javascript:showReplyToTweet(\"" . $row->replyToTweetID . "\"," . $row->id . ")'>tweet</a>";
							} else {
								echo "&nbsp;";
							}
						?>
						</td>
						<?php if($showType=='queued'){ ?>
					<form method="POST" name="del-<?php echo $row->id; ?>"><input type="hidden" name="deleteTweet" value="<?php echo $row->id; ?>"></form>
					<form method="POST" name="res-<?php echo $row->id; ?>"><input type="hidden" name="rescheduleTweet" value="<?php echo $row->id; ?>"></form>
						<td width="25%" align="center" style="line-height: 3em; padding: 0px 3px 6px 3px;">
						<a href="#" class="editTweet button-secondary" id="tweet-<?php echo $row->id; ?>"><img src='../wp-content/plugins/dashter/images/edit.png' width="12" align="absmiddle"> Edit</a> 
						
						<a class="button-secondary" class="deleteTweet" id="<?php echo $row->id; ?>" href="Javascript:document.forms['del-<?php echo $row->id; ?>'].submit();"><img src='../wp-content/plugins/dashter/images/delete.png' width="12" align="absmiddle"> Delete</a> 
						<a class="button-secondary" href="Javascript:document.forms['res-<?php echo $row->id; ?>'].submit();"><img src='../wp-content/plugins/dashter/images/clock.png' width="12" align="absmiddle"> Reschedule</a> 
						</td>
					
						<?php } ?>
						
						<?php 
						if($showType=='sent'){	
							echo '<td scope="col">';
							$dbTime = date('D M j, Y g:ia', strtotime($row->postTime));
							echo $dbTime;		
							echo '</td>'; 
						} 
						if ($showType=='deleted'){
							?>
							<form method="GET" action="<?php echo admin_url(); ?>admin.php" name="undel-<?php echo $row->id; ?>"><input type="hidden" name="page" value="dashter-queue"><input type="hidden" name="show" value="deleted"><input type="hidden" name="restoreTweet" value="<?php echo $row->id; ?>"></form>
							<td scope="col">
							<a class="button-secondary" href="javascript:document.forms['undel-<?php echo $row->id;?>'].submit();">Restore</a>
							</td>
							<?php
						}
						?>
						
					</tr>
					<?php 
					}
				} else {
					?>
					<tr><td colspan="5" align="center"><b>The Queue is Empty.</b></td></tr>
					<?php 
				}
			?>
			</tbody>
		</table>
		<?php 
		if ($idCount > 0){
		?>
			<div class="tablenav" style="margin: 0 auto; text-align: center;">
				<div class="tablenav-pages" style="float: none; margin: 0 auto; text-align: center;">
							<span class="displaying-num">Total: <?php echo $idCount; ?>, 
							Displaying: <?php echo ((($pageShow - 1) * $rpp) + 1) . "-";
							if (($pageShow * $rpp) > $idCount){
								echo $idCount; 
							} else {
								echo ($pageShow * $rpp);
							} ?>
							</span>
				<?php 
					for ($i=1; $i<($resultsPages+1); $i++){
						?>
						<span class="page-numbers <?php if ($pageShow == $i){ echo "current"; } ?>">
							<?php if ($pageShow != $i) { ?>
							<a href="?page=dashter-queue&show=<?php echo $showType; ?>&pg=<?php echo $i; ?>"><?php echo $i; ?></a>
							<?php } else { 	echo $i; } ?></span>
						<?php 
					}
				?>
				</div>
			</div>
		<?php 
		}
	}
	
	function process_ajax () {
		$tweetid = $_POST['tweetID'];
		if ($tweetid){
			global $twitterconn;
			$twitterconn->init();
			$singleParams = array ( 	'id'	=>	$tweetid	);
			$singleTweet = $twitterconn->get('statuses/show', $singleParams);
			if (!empty($singleTweet)){
				$screenname = $singleTweet->user->screen_name;
				$profileimg = $singleTweet->user->profile_image_url;
				$tweet = $twitterconn->dashter_parse_tweet($singleTweet->text);
				$posted = date('F d Y g:ia', strtotime($singleTweet->created_at));
				?>
				<a href="<?php echo DASHTER_URL; ?>core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-image' title='@<?php echo $screenname; ?>'><img src="<?php echo $profileimg; ?>" align="left" width="32" style="margin: 0 10px 0 0;"></a>
				<b>@<a href="<?php echo DASHTER_URL; ?>core/popups/user_details.php?screenname=<?php echo $screenname; ?>" class='thickbox user-name' title='@<?php echo $screenname; ?>'><?php echo $screenname; ?></a>:</b> 
				<?php echo $tweet; ?><br/>
				<i><?php echo $posted; ?></i>
				
				<?php 
			} else {
				echo "Twitter may be down, or the tweet may not be archived by Twitter anymore.";
			}
		}
		die();
	}
	
}

new dashter_queue;
