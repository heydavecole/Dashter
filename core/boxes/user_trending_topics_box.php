<?php 

class user_trending_topics_box extends dashter_base {
	
	var $box_slug;
	var $box_title;
	var $box_location;
	var $my_user;
	var $ajax_callback = 'user_trending_topics_box';
	
	function __construct( $user = 'User', $title = 'Trending Topics', $location = 'dashter_column2', $slug = 'user_trending_topics_box' ) {
		$this->my_user = $user;
		$this->box_slug = $slug;
		$this->box_title = 'Trending Topics by @' . $user;
		$this->box_location = $location;		
		add_action( ('wp_ajax_' . $this->ajax_callback) , array(&$this, 'process_ajax') );
	}
	
	function init_meta_box(){
		add_meta_box($this->box_slug, $this->box_title, array( &$this, 'display_meta_box' ), 'dashter', $this->box_location);
	}
	
	public function process_ajax() {
		// Relies on user_recent_engagement for output 
	}
	
	public function display_meta_box () {
		?>
		<div id="recentTrendingTopics"></div>
		<?php 
	}

}