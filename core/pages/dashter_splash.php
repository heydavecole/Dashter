<?php 

class dashter_splash extends dashter_base {
	
	var $page_title = 'Splash';
	var $menu_title = 'Dashter';
	var $menu_slug = 'dashter';
	
	function __construct() {
		
		if ($_REQUEST['action'] == 'dashter_auth') {
			update_option('dashter_key', $_REQUEST['dashter_key']);
			global $dashter_object;
			if ($dashter_object->is_authorized()) wp_redirect( admin_url() . 'admin.php?page=dashter-settings' );
		}
		
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_menu' ) );
		
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			add_thickbox();
		}
	}
	
	public function display_page () {
		?>
		<div class="wrap">
		<div class="icon32"><img src="<?php echo DASHTER_URL; ?>images/dashtericon-v1-32.png"></div>
		<h2>Dashter - <?php echo $this->page_title; ?></h2>
		
		<?php 
		if ($_REQUEST['action'] == 'dashter_auth') {
			global $dashter_object;
			echo "<div id='message' class='updated'>";
			echo $dashter_object->get_current_error();
			echo "</div>";
		}
		?>		
		
		<p>Dashter requires a login to use, so <strong>pay up bitches!</strong></p>
		
		<form name="" method="post" action="<?php ?>">
		<input name="action" type="hidden" value="dashter_auth" />
		<table class="form-table">		
			<tr>
				<th>Dashter Key</th>
				<td><input class="regular-text" type="text" value="<?php echo get_option('dashter_key'); ?>" name="dashter_key"></td>
			</tr>
		</table>
		
		<p class="submit">
			<input class="button-primary" type="submit" value="Submit" name="dashter_submit">
		</p>
		
		</form>
		
		<?php
	}
}

new dashter_splash;
