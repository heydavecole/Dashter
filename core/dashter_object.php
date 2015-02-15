<?php

class dashter_object {
	
	var $Config;
	var $auth_url;
	var $update_url;
	var $current_error;
		
	function __construct() {
		$this->update_url = "http://dashter.com/download/";
		$this->auth_url = "https://dashter.com/auth/";
		$this->Config = new config();
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'dashter_altapi_check' ) );
		add_filter( 'plugins_api', array( &$this, 'dashter_altapi_information' ), 10, 3 );
	}
	
	function is_authorized() {
		$domain = parse_url($_SERVER['HTTP_REFERER']);
		
		$details = array(
			'key'	=> get_option('dashter_key'),
			'domain' => $domain['host']
		);
				
		$dashter_auth = wp_remote_post( $this->auth_url, array(
			'body' => $details,
			'sslverify' => false
		) );
		
		$dashter_xml = wp_remote_retrieve_body( $dashter_auth );
		$data = simplexml_load_string( $dashter_xml );
		
		if ($data->error == 0) {
			if ($data->user->license) {
				
				update_option('dashter_consumer_key', strval($data->user->consumer_key));
				update_option('dashter_consumer_secret', strval($data->user->consumer_secret));
				return true;
			
			}
		}
		
		$this->current_error = strval($data->error);
		delete_option('dashter_key');
		delete_option('dashter_consumer_key');
		delete_option('dashter_consumer_secret');
		return false;
	}
	
	function get_current_error() {
		return $this->current_error;
	}
	
	function dashter_altapi_check( $transient ) {
	
		// Check if the transient contains the 'checked' information
		// If no, just return its value without hacking it
		if( empty( $transient->checked ) )
			return $transient;
		
		// POST data to send to your API
		$args = array(
			'action' => 'update-check',
			'plugin_name' => DASHTER_SLUG,
			'version' => $transient->checked[DASHTER_SLUG],
		);
				
		// Send request checking for an update
		$response = $this->dashter_altapi_request( $args );
		
		// If response is false, don't alter the transient
		if( false !== $response ) {
			$transient->response[DASHTER_SLUG] = $response;
		}
		
		return $transient;
	}
	
	// Send a request to the alternative API, return an object
	function dashter_altapi_request( $args ) {
		
		if (!$this->is_authorized()) {
			wp_redirect( admin_url() . 'admin.php?page=dashter' );
			return false;
		}
		
		// Send request
		$request = wp_remote_post( $this->update_url, array( 'body' => $args ) );
		
		// Make sure the request was successful
		if( is_wp_error( $request )
		or
		wp_remote_retrieve_response_code( $request ) != 200
		) {
			// Request failed
			return false;
		}
		
		// Read server response, which should be an object
		$response = unserialize( wp_remote_retrieve_body( $request ) );
		if( is_object( $response ) ) {
			return $response;
		} else {
			// Unexpected response
			return false;
		}
	}
		
	function dashter_altapi_information( $false, $action, $args ) {
		
		// Check if this plugins API is about this plugin
		if( $args->slug != DASHTER_SLUG ) {
			return false;
		}
			
		// POST data to send to your API
		$args = array(
			'action' => 'plugin_information',
			'plugin_name' => DASHTER_SLUG,
			'version' => $transient->checked[DASHTER_SLUG],
		);
				
		// Send request for detailed information
		$response = $this->dashter_altapi_request( $args );
		
		// Send request checking for information
		$request = wp_remote_post( $this->update_url, array( 'body' => $args ) );
	
		return $response;
	}
}

?>
