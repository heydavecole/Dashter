<?php

class config {
	
	function __construct () {
		
		add_action('admin_init', array( &$this, 'dashter_plugin_styles') );			
		add_action('admin_init', array( &$this, 'load_jquery_ui') );
		
		// Set default tweet options
		add_action('admin_init', array( &$this, 'dashter_set_default_options') );
		
		add_action('wp_dashboard_setup', array( &$this, 'dashter_dashboard_widgets' ) );
	}
	
	function dashter_dashboard_widgets(){
	
		function dashboard_widget_function() {
			$device = $_SERVER['HTTP_USER_AGENT'];
			$device = strtolower($device);
			$aMobileDevices = array ( "iphone", "ipad", "ipod", "blackberry", "android", "nokia" );
			$bIsMobile = false;
			foreach($aMobileDevices as $mobiledevice){
				if (strpos($device, $mobiledevice)){
					$bIsMobile = true;
				}
			}
			if ($bIsMobile){
				?>
				<p align="center"><a href="admin.php?page=dashter-mobile" class="button-primary" style="font-size: 20pt;">Launch Dashter Mobile</a></p>
				<?php 
			} else {
				echo "You're logged in as @" . get_option('dashter_twitter_screen_name') . " and you are cool, 'cause you have Dashter. ";
			}
		}
		wp_add_dashboard_widget('dashboard_widget', 'Dashter Snapshot', 'dashboard_widget_function');
	}
	
	function dashter_cron_interval( $schedules ){
		if (empty($schedules['dashter_cron'])){
			$Qfrequency = intval(get_option('dashter_queue_frequency'));
			if ($Qfrequency == 0){
				$Qfrequency = 1800;
			}
			$QDisplay = "Dashter " . strval( $Qfrequency / 60 );
			$schedules['dashter_cron'] = array ( 	'interval' => 	$Qfrequency,
													'display' =>	__($QDisplay)	);
			return $schedules;
		} 
	}
	
	function dashter_set_default_options(){
		// DEFAULT TWEETS
		$newpostdef = "~full~ ~tags~";
		$mentiondef = "~user~ you were mentioned in: ~full~";
		$relevantdef = "~user~ you might like ~link~ ~tags~";
		if (!get_option('dashter_t_newpostmessage')){
			update_option('dashter_t_newpostmessage', $newpostdef);
		}
		if (!get_option('dashter_t_mentionedusers')){
			update_option('dashter_t_mentionedusers', $mentiondef);
		}
		if (!get_option('dashter_t_relevantlink')){
			update_option('dashter_t_relevantlink', $relevantdef);
		}
		
		// DEFAULT FAVORITES / CURATION SETTINGS
		if (!get_option('dashter_favorites_curation_rule')){
			update_option('dashter_favorites_curation_rule', 'make_hidden');
		}
	}
	
	function load_jquery_ui(){
		wp_enqueue_style( 'dashter' );
	}
	
	public function dashter_plugin_styles(){
		wp_register_style ( 'jQueryUISmoothness', WP_PLUGIN_URL . '/dashter/includes/jqui/smoothness.css' );
		wp_register_style ( 'dashter', WP_PLUGIN_URL . '/dashter/core/css/dashter.css' ); // SZ 6/26/11
		wp_register_style ( 'dashter-hide-admin', WP_PLUGIN_URL . '/dashter/core/css/hide-admin.css' ); // SZ 6/26/11
	}
	
}

?>
