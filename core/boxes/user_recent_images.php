<?php 

class user_recent_images_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	
	function __construct( $user = 'User', $title = 'Recent Images', $location = 'dashter_column2', $slug = 'user_recent_images_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = 'Recent Images by @' . $user;
		$this->box_location = $location;		
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}

	public function display_meta_box () {
		?>
		<div id="recentImages"></div>
		<?php 
	}

}