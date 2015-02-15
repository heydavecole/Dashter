<?php 

class dashter_header extends dashter_base {
	
	var $ajax_callback = 'dashter_header';
	
	function __construct() {
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
		add_action( 'admin_footer', array( &$this, 'init' ) );
	}
	
	function init () {
		add_thickbox();
		$mysn = get_option('dashter_twitter_screen_name');
		?>
				
		<script type="text/javascript">
			var curr_html = document.getElementById('wphead-info').innerHTML;
			var new_twitter = '<div style="float: right; padding: 4px 0 0 0; margin: 0px 0px;"><span id="dashter_queue_empty" class="dashter_header_alert" style="display: none;">Queue Empty!</span> <a id="dashter_notifier" class="dashter_header_alert button-secondary" href="admin.php?page=dashter&sterm=%40<?php echo $mysn; ?>">&nbsp;</a> <input type="text" name="dashter_sterm" id="dashter_sterm"> <a href="javascript:dashter_adminanywhere_search();" class="button-secondary"><img src="<?php echo DASHTER_URL; ?>images/twitter_icon.png" height="10"> Search</a> <a href="<?php echo admin_url(); ?>admin.php?page=dashter-new-tweet&TB_iframe=true&height=220" class="thickbox button-primary" title="Create a new Tweet"><img src="<?php echo DASHTER_URL; ?>images/twitter_icon.png" height="10"> New Tweet</a></div>';
			var new_html = curr_html + new_twitter;
			document.getElementById('wphead-info').innerHTML = new_html;
			var dashter_load_first = dashter_consoleListener(0);
			function dashter_adminanywhere_search(){
				var sTerm = jQuery('#dashter_sterm').val();
				sTerm = sTerm.replace("#", "%23");
				if (sTerm.length > 0){
					window.location = ( "admin.php?page=dashter&sterm=" + sTerm );
				}
			}
			function dashter_listener(listenerCount){
				listenerCount++;
				jQuery('#dashter_notifier').fadeOut('fast');
				var universalMonitor = setTimeout('dashter_consoleListener(' + listenerCount + ')', 120000);
			}
			function dashter_consoleListener(listenerCount){
				jQuery('#dashter_notifier').fadeOut('fast');
				var data = {
					action: '<?php echo $this->ajax_callback; ?>',
					request: 'listener'
				}
				jQuery.post(ajaxurl, data, function(response){
					if ( ( response.indexOf('QueueEmpty') > -1 ) ){
						jQuery('#dashter_queue_empty').fadeIn('slow');
					}
					if ( response.indexOf('N=') > -1 ) {
						var numMentions = response.substring( (response.indexOf('N=') + 2), (response.length) );
						jQuery('#dashter_notifier').html('<b>' + numMentions + '</b> New Mentions Received!').fadeIn('slow');
					}
				});

				if (listenerCount < 15){
					dashter_listener(listenerCount);
				}
			}
		</script>
		<?php 
	}
	
	public function process_ajax() {
		global $twitterconn;
		global $wpdb;
		$twitterconn->init();
		$request = $_POST['request'];
		$queue_alert = get_option('dashter_queue_alert');
		
		if ($request == 'listener'){
			if ($queue_alert){
				$table_name = $wpdb->prefix . "dashter_queue";
				$get_total_query = "SELECT COUNT(id) as IdCount FROM $table_name WHERE tweetStatus = 'queued'";
				$countResult = $wpdb->get_var($get_total_query);
				if ($countResult == 0){ echo "QueueEmpty"; }
			} 
			$lastID = get_option('dashter_mentionsLastChecked');
			if (empty($lastID)){ $lastID = '1'; }
			$searchParams = array (	'since_id'		=>		$lastID,
									'count'			=>		'20',
									'trim_user'		=>		true,
									'include_rts'	=>		0 );
			$sResults = $twitterconn->get('statuses/mentions', $searchParams);
			if (!empty($sResults)){
				$i = 0;
				foreach ($sResults as $tweet){
					$i++;
				}
				echo "N=$i";
			}
		}
		die();
	}
}

new dashter_header;