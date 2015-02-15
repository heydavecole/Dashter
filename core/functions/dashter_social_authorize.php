<?php 

class dashter_social_authorize {

	// Dashster Social Connection
	// 1. Twitter
	// 2. Facebook
	
	var $oath_callback;
	
	function __construct() {
		
		$this->oath_callback = 'http://dashter.local/wp-admin/admin.php?page=dashter-settings';
		
		error_log('dashter_social_authorize');
		
		$act = $_REQUEST['connect_action'];
		if ($act == 'twitter'){
			// 1.0 Twitter configuration
			session_start();
			global $twitterconn;
			error_log(print_r($twitterconn, 1));
			$twitterconn->init();
			$request_token = $twitterconn->getRequestToken($this->oath_callback);
			
			// echo 'New connection attempted.';
			if (!$twitterconn){
				echo " Failed.";
			} else {
				error_log("Succeeded.");
				error_log("OAUTH TOKEN: " . $request_token['oauth_token']);
				error_log("OAUTH SECRET: " . $request_token['oauth_token_secret']);
				error_log("HTTP: " . $twitterconn->http_code);
				error_log("URL: " . $twitterconn->getAuthorizeURL($token));
				
				$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
				$url = $connection->getAuthorizeURL($request_token['oauth_token']);
				header('Location: ' . $url);
			}
		}
		if ($act == 'facebook'){
			require_once('includes/facebook/src/facebook.php');
			require_once('includes/facebook/config.php');
			
			$facebook = new Facebook( array (	'appId'	=> FACEBOOK_APP_ID,
												'secret' => FACEBOOK_SECRET_KEY,
												'cookie' => true
												));
			// print_r($facebook);
			$session = $facebook->getSession();
			// print_r($session);
			
			if (!$session) {
				$url = $facebook->getLoginUrl(array(
													'canvas' => 1,
													'fbconnect' => 0,
													'next' => 'http://192.168.0.103/dashster/wp-admin/admin.php?page=dashster-social-settings'
													));
				echo "<script type='text/javascript'>top.location.href = '$url';</script>";
			}	
		}
		
	}
}

new dashter_social_authorize;
?>