<?php

class MoWpnsUtility
{

	public static function icr() 
	{
		$email 			= get_site_option('mo2f_email');
		$customerKey 	= get_site_option('mo2f_customerKey');
		if( ! $email || ! $customerKey || !is_numeric( trim( $customerKey ) ) )
			return 0;
		else
			return 1;
	}
	
	public static function check_empty_or_null( $value )
	{
		if( ! isset( $value ) || empty( $value ) )
			return true;
		return false;
	}
	
	public static function is_curl_installed()
	{
		if  (in_array  ('curl', get_loaded_extensions()))
			return 1;
		else 
			return 0;
	}
	function sendNotificationToUserForUnusualActivities($username, $ipAddress, $reason)
	{
		$content = "";
		if(get_site_option($ipAddress.$reason)){
			return json_encode(array("status"=>'SUCCESS','statusMessage'=>'SUCCESS'));
		}
		
		global $moWpnsUtility;

		$user = get_user_by( 'login', $username );
		if($user && !empty($user->user_email))
			$toEmail = $user->user_email;
		else
			return;
		
	
		$fromEmail = get_site_option('mo2f_email');
		$subject   = 'Sign in from new location for your user account | '.get_bloginfo();

		if(get_site_option('custom_user_template'))
		{
			$content = get_site_option('custom_user_template');
			$content = str_replace("##ipaddress##",$ipAddress,$content);
			$content = str_replace("##username##",$username,$content);
		}
		
	}
	public static function hasLoginCookie(){
		if(isset($_COOKIE)){
			if(is_array($_COOKIE)){
				foreach($_COOKIE as $key => $val){
					if(strpos($key, 'wordpress_logged_in') === 0){
						return true;
					}
				}
			}
		}
		return false;
	}

}