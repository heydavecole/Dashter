<?php 

class dashter_relationships extends dashter_base {
	
	var $search_box;
	var $user_results_box;
	var $recently_viewed_box;
	var $notification_rules_box;
	var $users_box;
	var $user_interests_management_box;
	
	var $user_box;
	var $results_box;
	var $user_recent_images_box;
	var $user_recent_engagement_box;
	var $user_trending_topics_box;
	var $tweet_box;
	var $user_lists_box;
	var $user_interests_box;
	
	var $page_title = 'Dashter Relationship Manager';
	var $menu_title = 'Relationships';
	var $menu_slug = 'dashter-users';

	function __construct() {
	
		$user = $_REQUEST['user'];
		$this->user_box = new user_box($user);
		$this->user_interests_management_box = new user_interests_management_box($user);
		$this->user_interests_box = new user_interests_box($user);
		$this->user_recent_engagement_box = new user_recent_engagement_box($user);
		$this->user_trending_topics_box = new user_trending_topics_box($user);
		$this->tweet_box = new tweet_box('Post to Twitter', 'dashter_column3', 'tweet_box');
		$this->results_box = new results_box('Recent Tweets', 'dashter_column1', 'results_box', $user );
		$this->search_box = new search_box;
		$this->user_results_box = new user_results_box('People Who Have Recently Mentioned @' . get_option('dashter_twitter_screen_name'));
		$this->user_lists_box = new user_lists_box($user);
		$this->recently_viewed_box = new recently_viewed_box;
		$this->users_box = new users_box('People I Follow', 'dashter_column2');
		add_action( 'admin_menu', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			$this->init_scripts();
			$user = $_REQUEST['user'];
			if ($user) {
				$this->user_box->init_meta_box();
				$this->results_box->init_meta_box();
				$this->user_recent_images_box = new user_recent_images_box($user);
				$this->user_recent_engagement_box->init_meta_box($user);
				$this->user_trending_topics_box->init_meta_box($user);
				$this->tweet_box->init_meta_box();
				$this->user_lists_box->init_meta_box();
				$this->user_interests_box->init_meta_box();
				
			} else {
				$this->search_box->init_meta_box();
				$this->user_results_box->init_meta_box();
				$this->user_interests_management_box->init_meta_box();
				$this->recently_viewed_box->init_meta_box();
				$this->users_box->init_meta_box();
			}
		}
	}
	
	public function display_page () {
		
		$user = $_REQUEST['user'];
		if ($user) {
			$this->display_wrap_header( $this->page_title . " - @" . $user );
			$this->display_dashboard(1, 'dashter_top');
			$this->display_dashboard(3);
			$this->display_wrap_footer();
		} else {
			$this->display_wrap_header( $this->page_title);
			$this->display_dashboard(2);
			$this->display_wrap_footer();
		}
				
	}

}

new dashter_relationships;
