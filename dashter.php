<?php
/*
Plugin Name: Dashter
Plugin URI: http://dashter.com
Description: Your Content is Social
Version: 0.10.3
Author: Dashter LLC
Author URI: http://dashter.com
License: GPL2 
*/

// INIT CONSTANTS
define('DASHTER_TESTMODE', false);
define('DASHTER_PATH', WP_PLUGIN_DIR . '/dashter/');
define('DASHTER_URL', plugins_url('dashter/'));
define('DASHTER_CORE_PATH', DASHTER_PATH . 'core/' );
define('DASHTER_SLUG', plugin_basename( __FILE__ ));

//delete_site_transient( 'update_plugins' ); //testing
//delete_option('dashter_key'); //testing
//exit;

function dashter_sanitize_path ($path) {
	return str_replace('\\', '/', $path);
}

function dashter_loader() {
	
	global $dashter_object;
	
	// INCLUDES
	require_once( DASHTER_CORE_PATH . 'config.php' );
	require_once( DASHTER_CORE_PATH . 'twitter/OAuth.php' );
	require_once( DASHTER_CORE_PATH . 'twitter/twitter_oauth.php' );
	require_once( DASHTER_CORE_PATH . 'twitter/twitter_connection.php' );
	require_once( DASHTER_CORE_PATH . 'dashter_object.php' );
	require_once( DASHTER_CORE_PATH . 'dashter_base.php' );
	
	// Google URL Shortener
	require_once( DASHTER_PATH . 'includes/google/googleshort.php' );
	
	$dashter_object = new dashter_object;
	if (get_option('dashter_key')) {
	
		// INIT QUEUE PROCESSOR 
		require_once( DASHTER_CORE_PATH . 'functions/dashter_cron_processor.php' );
		
		// INIT META BOXES
		require_once( DASHTER_CORE_PATH . 'boxes/tweet_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/topics_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/users_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/results_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/stream_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/search_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_results_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/recently_viewed_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_recent_engagement_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_trending_topics_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_lists_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_interests_management_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_interests_box.php' );
		require_once( DASHTER_CORE_PATH . 'boxes/user_recent_images.php' );
		
		// require_once( DASHTER_CORE_PATH . 'boxes/notification_rules_box.php' ); // DC DEPRECATED 10/11/11
		// require_once( DASHTER_CORE_PATH . 'boxes/user_messaging_box.php' ); // DC DEPRECATED 10/11/11
		// require_once( DASHTER_CORE_PATH . 'boxes/user_recent_tweets_box.php' ); // DC DEPRECATED 10/11/11
		
		// INIT PAGES
		require_once( DASHTER_CORE_PATH . 'pages/dashter_home.php' );
		require_once( DASHTER_CORE_PATH . 'pages/dashter_relationships.php' );
		require_once( DASHTER_CORE_PATH . 'pages/dashter_stream.php' );
		require_once( DASHTER_CORE_PATH . 'pages/dashter_queue.php' );
		require_once( DASHTER_CORE_PATH . 'pages/dashter_settings.php' );
		
		// POPUPS
		require_once( DASHTER_CORE_PATH . 'popups/dashter_new_tweet.php' );
		require_once( DASHTER_CORE_PATH . 'popups/curate_tweet.php' );
		require_once( DASHTER_CORE_PATH . 'popups/rec_article.php' );
		require_once( DASHTER_CORE_PATH . 'popups/user_popup.php' );	
		
		// INIT HEADER
		require_once( DASHTER_CORE_PATH . 'pages/dashter_header.php' );
		
		// INIT META BOXES
		require_once( DASHTER_CORE_PATH . 'functions/dashter_meta_boxes.php' );

		// INIT POST PUBLISHING
		require_once( DASHTER_CORE_PATH . 'functions/dashter_post_publish.php' );	
		
		// ALPHA! INIT DASHTER MOBILE
		require_once( DASHTER_CORE_PATH . 'pages/dashter_mobile.php' );
	} else {
		require_once( DASHTER_CORE_PATH . 'pages/dashter_splash.php' );
	}
	
}

add_action( 'init', 'dashter_loader' );

function dashter_install() {
	require_once( DASHTER_CORE_PATH . 'install.php' );
	new install;
}

register_activation_hook( __FILE__ , 'dashter_install' );
