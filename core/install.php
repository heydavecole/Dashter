<?php

class install {
	
	function __construct () {
		$this->activate();
	}
	
	public function isInstalled() {
		return get_option('dashter_installed');
	}
	
	static function activate() {
		global $wpdb;
		
		update_option('dashter_queue_frequency', 1800);

		// INSTALL QUEUE DATABASE TABLE
		$table_name = $wpdb->prefix . "dashter_queue";
		if ( ($wpdb->get_var("show tables like '$table_name'") != $table_name) || ($wpdb->get_var("show columns from $table_name like 'queueScreenName'",0,0) != 'postType') ){
			$sql = "CREATE TABLE " . $table_name . " (
						id mediumint(9) NOT NULL AUTO_INCREMENT,
						tweetContent VARCHAR(255) NOT NULL,
						replyToTweetId VARCHAR(25) NULL,
						tweetStatus VARCHAR(25) NOT NULL,
						postTime DATETIME NULL,
						postType VARCHAR(25) NULL,
						sentTweetID VARCHAR(25) NULL,
						queueScreenName VARCHAR(25) NULL,
						UNIQUE KEY id (id)
					);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	
		// INSTALL NOTIFICATIONS DB
		// DC 7/4/2011
		// Deprecated 9/22/2011 DC (using option 'dashter_user_interests')
		/*
		$notify_table = $wpdb->prefix . "dashter_notifications";
		if ($wpdb->get_var("show tables like '$notify_table'") != $notify_table){
			$sql = "CREATE TABLE " . $notify_table . " (
						id mediumint (9) NOT NULL AUTO_INCREMENT,
						screenname VARCHAR(255) NOT NULL,
						notifytype VARCHAR (25) NOT NULL,
						notifyid mediumint (9) NOT NULL,
						notificationtext VARCHAR (255) NULL, 
						UNIQUE KEY id (id)
					);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		*/
		
		// DASHTER STREAM CACHE DB
		// DC 8/2/2011
		$stream_cache = $wpdb->prefix . "dashter_stream_cache";
		if ($wpdb->get_var("show tables like '$stream_cache'") != $stream_cache){
			$sql = "CREATE TABLE " . $stream_cache . " (
						resultid mediumint (9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						postid mediumint (9) NOT NULL,
						cachetime DATETIME NOT NULL,
						tweetid VARCHAR (25) NOT NULL,
						screenname VARCHAR (25) NOT NULL,
						profileimg VARCHAR (255) NOT NULL,
						tweettext VARCHAR (255) NOT NULL,
						tweettime DATETIME NOT NULL
					);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		update_option('dashter_installed', false);
	}
}

?>
