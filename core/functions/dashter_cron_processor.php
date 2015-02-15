<?php

class dashter_cron_processor {

	function __construct(){
		add_action( 'wp', array( &$this, 'check_cron' ), 10, get_option('dashter_queue_frequency'), false );
	}
	
	function init_schedule($frequency = 1800) {
		update_option('dashter_queue_frequency', $frequency);
	}
	
	function check_cron() {
		error_log('check_cron');
		if (time() >= get_option('dashter_next_event')) {
			$this->process_cron();
			update_option('dashter_next_event', (time() + get_option('dashter_queue_frequency')) );
		}
	}

	public function process_cron(){
		
		error_log('process_cron');
		
		$qInterval = get_option('dashter_queue_frequency');
	
		// Correct Time Kludge
		$siteOffset = get_option('gmt_offset');
		$serverOffset = date('O');
		$cronTime = date('M j, Y @ h:ia', strtotime($siteOffset. ' hours'));
		$siteTime = date('M j, Y @ h:ia');
		if ($serverOffset == "+0000"){
			if ($cronTime <> $siteTime){
				$hourIndex = date('H', strtotime($siteOffset . ' hours'));
				$mysqldate = date( 'Y-m-d H:i:s', strtotime($siteOffset . ' hours') );
				$theTime = $cronTime;
			} else {
				$hourIndex = date('H');
				$mysqldate = date( 'Y-m-d H:i:s' );
				$theTime = $siteTime;
			}
		}
		// QUEUE SCHEDULE
		$qHours = get_option('dashter_queue_runtime');
		if (is_array($qHours)){
			$qStart = $qHours['start'];
			$qStop = $qHours['stop'];
		} else {
			$qStart = 0;
			$qStop = 0;
		}
		$processOK = false; // BOOL true = RUN CRON PROCESS
		if ($qStart == $qStop){
			$processOK = true;
		} elseif ($qStart > $qStop) {
			if ( ($qStart <= $hourIndex) || ($hourIndex < $qStop) ) { $processOK = true; }
		} elseif ($qStart < $qStop) {
			if ( ($qStart <= $hourIndex) && ($hourIndex < $qStop)){ $processOK = true; }
		}
		$qTest = "The time is: " . $theTime . " The index hour is: " . $hourIndex . " The Queue runs from " . $qStart . " to " . $qStop . ". The queue interval is currently " . ($qInterval/60) . " minutes. Process OK = " . $processOK; 
		
		
		if ($processOK){
			
			global $wpdb;
			global $twitterconn;
			$twitterconn->init();
			
			// Retrieve the next queued tweet
			$table_name = $wpdb->prefix . "dashter_queue";
			$query_queue = "SELECT id, tweetContent, replyToTweetID, tweetStatus, postTime, postType FROM $table_name WHERE tweetStatus = 'queued' ORDER BY id ASC LIMIT 1";
			$queueResult = $wpdb->get_results($query_queue);
			if ($queueResult){
				foreach ($queueResult as $row){
					// Should only be 1!
					$queueId = $row->id;
					$tweetContent = $row->tweetContent;
					$tweetReplyTo = (string) $row->replyToTweetId;
					$postType = $row->postType;		// NULL or 'auto'
				}
				// Check & Clear Auto Hold (if enabled)
				$autoHold = get_option('dashter_auto_hold');
				$ok_to_post = false;
				if ( ($autoHold == true) && ($postType == 'auto') ) {
					$ok_to_post = false;
					// This is an automatic post from Dashter
					// Check that the currently published tweet does not match the last auto post.
					$getArgs = array ( 'count' => 1 );
					$lastTweet = $twitterconn->get( 'statuses/user_timeline', $getArgs );
					if (is_array($lastTweet)){
						$lastTweetText = $lastTweet[0]->text;
						$query_queue_sent = "SELECT id, tweetContent, postType FROM $table_name WHERE (tweetStatus = 'sent') AND (postType = 'auto') ORDER BY id DESC LIMIT 1";
						$sentLast = (array) $wpdb->get_results($query_queue_sent);
						error_log(print_r($sentLast, 1));
						if (is_array($sentLast)){
							$sent_tweetContent = $sentLast[0]->tweetContent;
							if ( ( trim ( $lastTweetText ) ) == ( trim ( $sent_tweetContent ) ) ){
								$ok_to_post = false;
								error_log('Auto hold triggered.');
								$qTest .= " Auto hold tripped. Seeking next human tweet. ";
								// Get next available non-auto tweet in the queue.
								$non_auto_query = "SELECT id, tweetContent, replyToTweetID, tweetStatus, postTime, postType FROM $table_name WHERE (tweetStatus = 'queued') AND (postType IS NULL) ORDER BY id ASC LIMIT 1";
								$na_result = $wpdb->get_row($non_auto_query, OBJECT, 0);
								if ($na_result){
									$queueId = $na_result->id;
									$tweetContent = $na_result->tweetContent;
									$tweetReplyTo = (string) $na_result->replyToTweetId;
									$postType = $na_result->postType;		// NULL or 'auto'
									
									$ok_to_post = true;
								}
								
							} else {
								$ok_to_post = true;
								error_log('Auto hold clear.');
							}
						} else {
							$ok_to_post = true;
							error_log('Auto hold: Unable to retrieve last queued tweet sent.');
						}	
					} else {
						error_log('Auto hold: Unable to retrieve last tweet sent from Twitter.');
						$ok_to_post = true;
					}
				} else {
					$ok_to_post = true;
				}
				$qTest .= "<br/> The next tweet in the queue is $queueID: $tweetContent Reply To: $tweetReplyTo Post Type: $postType ";
				if ($ok_to_post){
					
					$twitter_args = array ( 'status' => $tweetContent );
					if (!empty($tweetReplyTo)){ $twitter_args['in_reply_to_status_id'] = $tweetReplyTo;	}
					
					// Post to Twitter
					$tweet_sent = $twitterconn->post( 'statuses/update', $twitter_args );
					$sentTweetID = $tweet_sent->id_str;
					if ($sentTweetID){
						// Tweet was successfully sent. Update database.
						$wpdb->update( $table_name, array( 'tweetStatus' => 'sent', 'postTime' => $mysqldate, 'sentTweetID' => $sentTweetID ), array( 'id' => $queueId ), array( '%s', '%s', '%s' ) );
						$qTest .= '<br/> Tweet ' . $sentTweetID . ' was sent successfully and ' . $queueId . ' was updated to sent.';
					} else {
						// Something appears to have gone wrong during send action. Do not update database.
						$qTest .= '<br/> The tweet was not successfully sent to twitter.';
					}
				}
				
			} else {
				$qTest .= "<br/> There are no tweets left in the queue. ";
			}
			
		}
		
		update_option('dashter_test_queue', $qTest);
	}

}

global $dashter_cron_processor;
$dashter_cron_processor = new dashter_cron_processor;