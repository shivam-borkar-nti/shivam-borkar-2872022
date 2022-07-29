<?php
	
	$email_registered = 1;
	global $Mo2fdbQueries;
	$email = get_user_meta(get_current_user_id(),'email',true);
	if(isset($email))
		$email_registered = 1;
	else
		$email_registered = 0;

	if(current_user_can( 'manage_options' ) && isset($_POST['option']))
	{
		switch($_POST['option'])
		{
			case "mo2f_enable_2FA_on_login_page_option":
				wpns_handle_enable_2fa_login_prompt($_POST);						break;			
		}
	}

	include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'twofa'.DIRECTORY_SEPARATOR.'setup_twofa.php';

	function wpns_handle_enable_2fa_login_prompt($postvalue)
	{
		if( get_site_option( 'mo2f_enable_2fa_prompt_on_login_page' ) == 1 )
			do_action('wpns_show_message',MoWpnsMessages::showMessage('TWO_FA_ON_LOGIN_PROMPT_ENABLED'),'SUCCESS');
		else
			do_action('wpns_show_message',MoWpnsMessages::showMessage('TWO_FA_ON_LOGIN_PROMPT_DISABLED'),'ERROR');
	}