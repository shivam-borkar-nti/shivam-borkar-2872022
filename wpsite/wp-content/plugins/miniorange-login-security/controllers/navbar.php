<?php
	
	global $moWpnsUtility,$mo2f_dirName;
	$profile_url	= add_query_arg( array('page' => 'mo_2fa_account'		), $_SERVER['REQUEST_URI'] );
	$twofa_url	    = add_query_arg( array('page' => 'mo_2fa_two_fa'		), $_SERVER['REQUEST_URI'] );
	$reports_url	= add_query_arg( array('page' => 'mo_2fa_reports'			), $_SERVER['REQUEST_URI'] );
	$two_fa         = add_query_arg( array('page' => 'mo_2fa_two_fa'           ), $_SERVER['REQUEST_URI'] );
	//Added for new design
    $dashboard_url	= add_query_arg(array('page' => 'mo_2fa_dashboard'			), $_SERVER['REQUEST_URI']);
    $upgrade_url	= add_query_arg(array('page' => 'mo_2fa_upgrade'				), $_SERVER['REQUEST_URI']);
    $license_url	= add_query_arg( array('page' => 'mo_2fa_upgrade'  		), $_SERVER['REQUEST_URI'] );
   //dynamic
    $logo_url = plugin_dir_url(dirname(__FILE__)) . 'includes/images/miniorange_logo.png';
    $shw_feedback	= get_site_option('donot_show_feedback_message') ? false: true;

    $active_tab 	= sanitize_text_field($_GET['page']);
    $hide_login_form_url= plugin_dir_url(dirname(__FILE__)) . 'includes/images/WP_hide_default_PL.png';
    $login_with_usename_only_url = plugin_dir_url(dirname(__FILE__)) . 'includes/images/WP_default_login_PL.png';

	include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'navbar.php';
