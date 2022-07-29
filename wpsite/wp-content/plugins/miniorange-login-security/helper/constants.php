<?php
	
	class MoWpnsConstants
	{
		const SUCCESS 					= "success";
		const FAILED 					= "failed";
		const PAST_FAILED 				= "pastfailed";
		const ACCESS_DENIED				= "accessDenied";
		const LOGIN_TRANSACTION 		= "User Login";
		const DEFAULT_CUSTOMER_KEY		= "16555";
		const DEFAULT_API_KEY 			= "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
		const DB_VERSION				= 147;
		const SUPPORT_EMAIL				= 'info@xecurify.com';
		const HOST_NAME					= "https://login.xecurify.com";
		const FOOTER_LINK				= '<a style="display:none;" href="http://miniorange.com/cyber-security">Secured By miniOrange</a>';

		//plugins
		const TWO_FACTOR_SETTINGS		= 'miniorange-2-factor-authentication/miniorange_2_factor_settings.php';
		const FAQ_PAYMENT_URL			= 'https://faq.miniorange.com/knowledgebase/all-i-want-to-do-is-upgrade-to-a-premium-licence/';
		//arrays
		
		function __construct()
		{
			$this->define_global();
		}

		function define_global()
		{
			global $wpnsDbQueries,$moWpnsUtility,$mo2f_dirName,$Mo2fdbQueries;
			$wpnsDbQueries	 	= new MoWpnsDB();
			$moWpnsUtility  	= new MoWpnsUtility();
			$mo2f_dirName 		= dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR;
			$Mo2fdbQueries 		= new Mo2fDB();
		}
		
	}
	new MoWpnsConstants;

?>