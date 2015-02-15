<?php 

class dashter_home extends dashter_base {
	
	var $tweet_box;
	var $topics_box;
	var $users_box;
	var $results_box;
	var $page_title = 'Dashter Home';
	var $menu_title = 'Dashter';
	var $menu_slug = 'dashter';
	
	function __construct() {
		
		$this->tweet_box = new tweet_box;
		$this->topics_box = new topics_box;
		$this->users_box = new users_box;
		$this->results_box = new results_box;
		add_action( 'admin_menu', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_menu' ) );
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			$this->init_scripts();
			$this->tweet_box->init_meta_box();
			$this->topics_box->init_meta_box();
			$this->users_box->init_meta_box();
			$this->results_box->init_meta_box();
		}
	}
    
	public function display_page () {
		
		// Get a search term from another page.
		// This will prevent 'gettweets' from being called and instead load on the search term.
		$sterm = $_REQUEST['sterm'];
		if (empty($sterm)){
			unset($sterm);
		}
		
		$this->display_wrap_header();
		$this->display_dashboard(2);
		$this->display_wrap_footer();
		
	}

}

new dashter_home;