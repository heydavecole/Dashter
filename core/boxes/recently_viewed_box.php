<?php 

class recently_viewed_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	
	function __construct( $title = 'Recently Viewed', $location = 'dashter_column2',  $slug = 'recently_viewed_box' ) {
		$this->box_slug = $slug;
		$this->box_title = $title;
		$this->box_location = $location;
	}
	
	function init_meta_box () {
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);		
	}

	public function display_meta_box () {
		global $twitterconn;
		$twitterconn->init();
		?>
		<div id="recentspace">
		<?php 
			$recentUsers = get_option('dashter_t_recent_users');
			
			if ($recentUsers){
				$recentUsers = array_reverse($recentUsers);
				$i=0;
				foreach ($recentUsers as $person){
					$i++;
					if ($i > 10) break;
					$twitterconn->display_user($person);
				}
			} else {
				echo "You haven't looked at any users recently.";
			}
		?>
		<br class="clear" />
		</div>
		<?php
	}

}