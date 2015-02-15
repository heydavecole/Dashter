<?php 

class dashter_mobile extends dashter_base {
	
	var $tweet_box;
	var $results_box;
	
	var $page_title = 'Dashter Mobile';
	var $menu_title = 'Mobile';
	var $menu_slug = 'dashter-mobile';
	// var $ajax_callback = 'dashter_mobile';
	
	function __construct() {
		$this->tweet_box = new tweet_box;
		$this->results_box = new results_box( 'Latest Tweets', 'dashter_column1' ); 
		add_action( 'admin_init', array( &$this, 'init_css') );
		
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		// add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
		
	}
	function init_css () {
		if ($_GET['page'] == $this->menu_slug) {
			wp_enqueue_style( 'dashter-hide-admin' );
		}
	}
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			// $this->init_scripts();
			$this->tweet_box->init_meta_box();
			$this->results_box->init_meta_box();
		}
	}
	function init_submenu(){
		add_submenu_page( 'dashter-settings', $this->page_title, $this->menu_title, 'edit_pages', $this->menu_slug, array($this, 'display_page') );		
	}	
	public function display_page () {	
		?>
		<style type="text/css">
		
		body { 
			margin: 0 auto;
			width: 320px;
		}
		.wrap h2 {
			font-size: 12pt;
		}
		
		</style>
		<link rel="apple-touch-icon" href="<?php echo DASHTER_URL; ?>images/dashter_mobile.png" />
		<meta name="viewport" content="width=320, initial-scale=1.0"/>   
		<?php 
		$this->display_wrap_header( 'Dashter Mobile &#187; <a href="admin.php?page=dashter">Normal Mode</a>' );
		?>
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#hello').delay(3000).slideUp();
		});
		</script>
		<div id="hello" style="border-radius: 5px; text-align:center; background: #00ffff; color: #333;">
		Welcome to Dashter Mobile! On an iPhone? Press the <img src="<?php echo DASHTER_URL; ?>images/safari_share.png" align="absmiddle" alt="SHARE"> button to bookmark this page or save it to your home screen.
		</div>
		<?
		$this->display_dashboard(1);
		$this->display_wrap_footer();
		 
	}
	/*
	public function process_ajax () {
		die();
	}
	*/

}

new dashter_mobile;

	
