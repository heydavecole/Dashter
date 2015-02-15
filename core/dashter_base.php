<?php

class dashter_base {

	var $errors;
	var $page_title;
	var $menu_title;
	var $menu_slug;
	var $parent_slug = 'dashter';
	
	function __construct() {
	
	}
	
	function init_scripts() {
		wp_enqueue_script( 'dashboard' );
		//wp_enqueue_script( 'plugin-install' );
		//wp_enqueue_script( 'media-upload' );
		wp_admin_css( 'dashboard' );
		//wp_admin_css( 'plugin-install' );
		add_thickbox();
	}
	
	function init_menu(){
		$icon_url = DASHTER_URL . 'images/dashter-tray-icon.png';
		add_menu_page( $this->page_title, $this->menu_title, 'edit_posts', $this->parent_slug, array($this, 'display_page'), $icon_url, 3 );
	}
	
	function init_submenu(){
		add_submenu_page( $this->parent_slug, $this->page_title, $this->menu_title, 'edit_pages', $this->menu_slug, array($this, 'display_page') );		
	}
	
	function display_wrap_header ($page_title = "Dashter") {
		require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
		wp_dashboard_setup();
		?>
		
		<div class="wrap">
			<div class="icon32"><img src="<?php echo DASHTER_URL; ?>images/dashtericon-v1-32.png"></div>
			<h2><?php echo $page_title; ?></h2>
			
			<?php 
			if ($message){
				echo "<div id='message' class='updated'>";
				echo $message;
				echo "</div>";
			}
	}
	
	function display_wrap_footer () {
		?>
		</div>
		<?php
	}
	
	function display_dashboard ($screen_layout_columns, $dashboard_name = 'dashter') {
		?>
	
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">
				<?php
				$hide2 = $hide3 = $hide4 = '';
				switch ( $screen_layout_columns ) {
					case 4:
						$width = 'width:24.5%;';
						break;
					case 3:
						$width = 'width:32.67%;';
						$hide4 = 'display:none;';
						break;
					case 2:
						$width = 'width:49%;';
						$hide3 = $hide4 = 'display:none;';
						break;
					default:
						$width = 'width:98%;';
						$hide2 = $hide3 = $hide4 = 'display:none;';
				}
				echo "\t<div class='postbox-container' style='$width'>\n";
				do_meta_boxes( 'dashter', $dashboard_name . '_column1', '' );
			
				echo "\t</div><div class='postbox-container' style='{$hide2}$width'>\n";
				do_meta_boxes( 'dashter', $dashboard_name . '_column2', '' );
			
				echo "\t</div><div class='postbox-container' style='{$hide3}$width'>\n";
				do_meta_boxes( 'dashter', $dashboard_name . '_column3', '' );
			
				echo "\t</div><div class='postbox-container' style='{$hide4}$width'></div>\n";
				do_meta_boxes( 'dashter', $dashboard_name . '_column4', '' );
				?>
			</div>
		<div class="clear"></div>
		
		<?php /*
		<form style="display:none" method="get" action="">
			<p>
		<?php
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		?>
			</p>
		</form>
		*/ ?>
		
		</div><!-- dashboard-widgets-wrap -->
		<?php
	}
	
	function dashter_parse_curatedTweet($tweet){
		$tweet = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@','<a href="$1" target="_new">$1</a>',$tweet);
		return $tweet;
	}
	
	function init_base() {
		
		//error_log('init');
		
		
		/*
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'save_post', array( &$this, 'save_post' ) );
		*/
		
		//update_option('dashter_installStatus', 'Installed');
		
		/*
		add_action( 'user_register', array( &$this, 'create_dashter_license_key' ) );
		
		add_action( 'edit_user_profile', array( &$this, 'display_user_fields' ) );
		add_action( 'show_user_profile', array( &$this, 'display_user_fields' ) );
		
		add_action( 'profile_update', array( &$this, 'save_dashter_user_fields' ) );
		add_action( 'personal_options_update', array( &$this, 'save_dashter_user_fields' ) );
		*/
		
		
		/*
		add_action('admin_menu', 'dashter_curation_include');
		function dashter_curation_include(){
			include 'dashter_curate_tweet.php';
		}
		*/	
	}

}

?>
