<?php 

class user_lists_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	var $ajax_callback = 'dashter_user_lists_box';
	
	function __construct( $user = 'User', $title = 'Lists', $location = 'dashter_column3', $slug = 'user_lists_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );			
	}

	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function display_meta_box () {
		global $twitterconn;
		$twitterconn->init();
		?>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#lists_save_status').hide(); // Hide save notice
			$('.listcheckbox').click(function(){
				var cssGreen = {
					'color' : 'green',
					'font-weight' : 'bold'
				}	
				var cssClear = {
					'color' : '',
					'font-weight' : ''
				}
				
				var listBoxSlug = $(this).attr('id');
				var listBoxState = $(this).attr('checked');
				if (listBoxState){
					var data = {
								action: '<?php echo $this->ajax_callback; ?>',
								request: 'addToList',
								listSlug: listBoxSlug,
								username: '<?php echo $this->my_user; ?>',
					}
					$.post(ajaxurl, data, function(response){
						if ( response.indexOf('listed') > -1 ){
							$(this).parent('div').css(cssGreen);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).slideUp('slow');
							});
						} else {
							alert ( '<?php echo $this->my_user; ?> may not have been added to your list.' );
						}
					});
				} else {
					var data = {
								action: '<?php echo $this->ajax_callback; ?>',
								request: 'addToList',
								remList: true,
								listSlug: listBoxSlug,
								username: '<?php echo $this->my_user; ?>',
					}
					$.post(ajaxurl, data, function(response){
						if ( response.indexOf('removed') > -1 ) {
							$(this).parent('div').css(cssClear);
							$('#lists_save_status').slideDown('fast', function(){
								$(this).slideUp('slow');
							});
						} else {
							alert ( '<?php echo $this->my_user; ?> may not have been removed from your list.' );
						}
					});
				}	
			});
		});
		</script>
		<div>
		<span style='font-weight: bold;' id='lists_save_status'><img src='../wp-content/plugins/dashter/images/accept.png' width='16' height='16' align='absmiddle'> Saved!</span>
		</div>
		<?php 
		$listsParams = array (
			'screen_name' =>	$this->my_user,
			'filter_to_owned_lists' =>	true 
		);
		$twitterListResponse = $twitterconn->get('lists/memberships', $listsParams);
		$theLists = $twitterListResponse->lists;
		if (!empty($theLists)){
			foreach ($theLists as $list){
				$aListed[] = $list->name;
			}
		} else {
			$aListed = array();
		}
		$myLists = get_option('dashter_t_lists');
		$i=0;
		foreach ($myLists as $listname){
			?>
			<div style='width: 49%; float: left; padding: 5px 0;'>
			<input type="checkbox" name="lists[]" value="<?php echo $listname; ?>" class="listcheckbox" id="<?php echo str_replace(" ", "-", (strtolower($listname))); ?>"
			<?php if (in_array($listname, $aListed)) { echo "checked='checked'"; } ?> />
			
			<?php echo $listname; ?></div>
			<?php
			$i++;
		}
		?>
		<br class="clear" />
		<?php
	}

	public function process_ajax () {
		
		global $twitterconn;
		$twitterconn->init();
		
		$action = $_POST['request'];
		$userName = $_POST['username'];
		$listSlug = $_POST['listSlug'];
		$mysn = get_option('dashter_twitter_screen_name');
		
		$remList = $_POST['remList'];
		
		if ($action == 'addToList'){
			$params = array (	'slug'	=>	$listSlug,
								'owner_screen_name'	=>	$mysn,
								'screen_name'	=> $userName	);
			if ( ($remList == true) || ($remList == 1) ){
				$addToList = $twitterconn->post('lists/members/destroy', $params);
				echo "removed";
			} else {
				$addToList = $twitterconn->post('lists/members/create', $params);
				echo "listed";
			}
		} 
		die();
	}
}