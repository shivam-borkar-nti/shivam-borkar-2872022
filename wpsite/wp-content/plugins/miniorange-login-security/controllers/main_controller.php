<?php

	global $moWpnsUtility,$mo2f_dirName;

	$controller = $mo2f_dirName . 'controllers'.DIRECTORY_SEPARATOR;

	
	if(current_user_can('administrator'))
	{
	
	include $controller. 'navbar.php';

	if( isset( $_GET[ 'page' ])) 
	{
		if ($_GET['page'] == 'mo_2fa_upgrade') {
			include $controller . 'upgrade.php';                
			exit();
		}
		else
		{
		switch($_GET['page'])
		{
			case 'mo_2fa_dashboard':
		         include $controller . 'dashboard.php';			    break;
			case 'mo_2fa_account':
				include $controller . 'account.php';				break;			
			case 'mo_2fa_notifications':
				include $controller . 'notification-settings.php';	break;
			case 'mo_2fa_reports':
				include $controller . 'reports.php';				break;
			case 'mo_2fa_troubleshooting':
				include $controller . 'troubleshooting.php';		break;
			case 'mo_2fa_two_fa':
				include $controller .'twofa'.DIRECTORY_SEPARATOR. 'two_fa.php';					break;
		}
		include $controller . 'support.php';
	}
	}	
	}
	else
	{
		if( isset( $_GET[ 'page' ])) 
		{
			switch($_GET['page'])
			{
				case 'mo_2fa_two_fa':
					include $controller .'twofa'.DIRECTORY_SEPARATOR. 'two_fa.php';					break;	
			
			}

		}

	}
?>
