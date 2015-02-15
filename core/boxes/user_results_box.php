<?php 

class user_results_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $request;
	var $ajax_callback = 'dashter_user_results_box';
	
	function __construct( $title = 'User Results', $req = 'dashter_get_mentions', $location = 'dashter_column1',  $slug = 'user_results_box' ) {
		
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		$this->request = $req;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );	
	}
	
	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
		
	}

	public function display_meta_box () {
		?>
		<script type='text/javascript'>
		jQuery(document).ready(function () {
			jQuery('#recentMentions').html('<p align="center"><img src="<?php echo DASHTER_URL; ?>images/dashter-ajax-loading.gif"></p>');
			var data = {
				action: '<?php echo $this->ajax_callback; ?>',
				request: '<?php echo $this->request; ?>',
				myscreenname: '<?php echo get_option('dashter_twitter_screen_name'); ?>'
			}	
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#recentMentions').html(response);
			});
		});
		</script>
		<p style="padding: 0 5px;"><a href="admin.php?page=dashter&sterm=%40<?php echo get_option('dashter_twitter_screen_name'); ?>">
		View Your Recent Mentions</a></p>
		<div id="recentMentions"></div><br class="clear" />
		<?php
	}
	
	public function process_ajax () {
	
		global $twitterconn;
		$twitterconn->init();
		$action = $_POST['request'];

		switch ($action) {
			
			case 'dashter_get_mentions':
			
				$mysn = $_POST['myscreenname'];
				$searchParams = array ( 	'q'		=> 		'@' . $mysn,
											'rpp'	=> 		100		);
				$mysnSearch = $twitterconn->get('search', $searchParams);

				$aTheyLikeMe = array();
				$aUserImages = array();
				if (!empty($mysnSearch->results)){
					foreach ($mysnSearch->results as $res){
						$fromUser = $res->from_user;
						$aTheyLikeMe[$fromUser] = $aTheyLikeMe[$fromUser] + 1;
						
						if (empty($aUserImages[$fromUser])){
							$aUserImages[$fromUser] = $res->profile_image_url;
						}
					}
					arsort($aTheyLikeMe);
					
					$i=0;
					foreach ($aTheyLikeMe as $name=>$count){
						$i++;
						if ($i > 10){
							break;
						}
						$person = array ( 	'screen_name'	=>	$name, 'img_url' 	=>	$aUserImages[$name]	);
						$twitterconn->display_user($person);
					}
				} else {
					echo "Sorry, not getting any results right now. Try again in a little bit.";
				}
				break;
	
		}
		die();
		
	}

}