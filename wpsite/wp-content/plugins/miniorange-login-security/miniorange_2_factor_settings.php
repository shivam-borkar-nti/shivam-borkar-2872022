<?php
/**
 * Plugin Name: Multi Factor Authentication
 * Plugin URI: https://miniorange.com
 * Description: This is a simple 2FA plugin that provides various two-factor authentication methods as an additional layer of security after the default wordpress login. We Support Google/Authy/LastPass Authenticator and Security Questions(KBA) for 3 Users in the free version of the plugin.
 * Version: 1.2.4
 * Author: miniOrange
 * Author URI: https://miniorange.com
 * License: GPL2
 */
    define( 'MO_HOST_NAME', 'https://login.xecurify.com' );
	define( 'MO2F_VERSION', '1.2.4' );
	define( 'MO2F_TEST_MODE', false );
	global $mainDir;
	$mainDir = plugin_dir_url(__FILE__);
	class Miniorange_twoFactor{
	

		function __construct()
		{
			register_deactivation_hook(__FILE__		 , array( $this, 'mo_wpns_deactivate'		       )		);
			register_activation_hook  (__FILE__		 , array( $this, 'mo_wpns_activate'			       )		);
			add_action( 'admin_menu'				 , array( $this, 'mo_wpns_widget_menu'		  	   )		);
			add_action( 'admin_enqueue_scripts'		 , array( $this, 'mo_wpns_settings_style'	       )		);
			add_action( 'admin_enqueue_scripts'		 , array( $this, 'mo_wpns_settings_script'	       )	    );
			add_action( 'wpns_show_message'		 	 , array( $this, 'mo_show_message' 				   ), 1 , 2 );
			add_action( 'wp_footer'					 , array( $this, 'footer_link'					   ),100	);

			add_action( 'admin_init'                 , array( $this, 'miniorange_reset_save_settings'  )         );		
			add_filter('manage_users_columns'        , array( $this, 'mo2f_mapped_email_column'        )         );
			add_action('manage_users_custom_column'  , array( $this, 'mo2f_mapped_email_column_content'), 10,  3 );

			$actions = add_filter('user_row_actions' , array( $this, 'miniorange_reset_users'          ),10 , 2 );
            add_action( 'admin_footer'				 , array( $this, 'feedback_request' 			   )        );
			if(get_site_option('mo2f_disable_file_editing')) 	 define('DISALLOW_FILE_EDIT', true);
			$this->includes();

		}
        // As on plugins.php page not in the plugin
        function feedback_request() {
            if ( 'plugins.php' != basename( $_SERVER['PHP_SELF'] ) ) {
                return;
            }
            global $mo2f_dirName;

            $email = get_site_option("mo2f_email");
            if(empty($email)){
                $user = wp_get_current_user();
                $email = $user->user_email;
            }
            $imagepath=plugins_url( '/includes/images/', __FILE__ );
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
            wp_enqueue_script( 'utils' );
            wp_enqueue_style( 'mo_wpns_admin_plugins_page_style', plugins_url( '/includes/css/style_settings.css?ver=4.8.60', __FILE__ ) );

            include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'feedback_form.php';;

        }
		
		function mo_wpns_widget_menu()
		{
		$user  = wp_get_current_user();
		$userID = wp_get_current_user()->ID;
		$onprem_admin = get_site_option('mo2f_onprem_admin');
        $roles = ( array ) $user->roles;
        $flag  = 0;
  		foreach ( $roles as $role ) {
            if(get_site_option('mo2fa_'.$role)=='1')
            	$flag=1;
        }
         
         $is_2fa_enabled=(($flag) or ($userID == $onprem_admin));
        
            if( $is_2fa_enabled){	
				$menu_slug = 'mo_2fa_two_fa';
				add_menu_page (	'miniOrange 2-Factor' , 'Multi-factor Authentication' , 'administrator', $menu_slug , array( $this, 'mo_wpns'), plugin_dir_url(__FILE__) . 'includes/images/miniorange_icon.png' );
			}
			else{
				$menu_slug =  'mo_2fa_dashboard';
			}

			if(get_site_option('is_onprem'))
			{	if( $is_2fa_enabled){
					
				add_submenu_page( $menu_slug	,'miniOrange 2-Factor'	,'Two Factor'		,'read',		'mo_2fa_two_fa'			, array( $this, 'mo_wpns'),1);
				}
			}
			else{
			add_submenu_page( $menu_slug	,'miniOrange 2-Factor'	,'Two Factor'		   	,'administrator','mo_2fa_two_fa'			, array( $this, 'mo_wpns'),2);
			}
			
            add_submenu_page( $menu_slug	,'miniOrange 2-Factor'	,'Troubleshooting'		,'administrator','mo_2fa_troubleshooting'	, array( $this, 'mo_wpns'),10);
            add_submenu_page( $menu_slug	,'miniOrange 2-Factor'	,'Account'				,'administrator','mo_2fa_account'			, array( $this, 'mo_wpns'),11);
            add_submenu_page( $menu_slug	,'miniOrange 2-Factor'	,'Upgrade'				,'administrator','mo_2fa_upgrade'			, array( $this, 'mo_wpns'),12);
			$mo2fa_hook_page = add_users_page ('Reset 2nd Factor',  null , 'manage_options', 'reset', array( $this, 'mo_reset_2fa_for_users_by_admin' ),66);
                 
    }
		function mo_wpns()
		{
			global $wpnsDbQueries,$Mo2fdbQueries;
			$Mo2fdbQueries->mo_plugin_activate();	
			include 'controllers/main_controller.php';
		}

		function mo_wpns_activate()
		{
			global $wpnsDbQueries,$Mo2fdbQueries;
			$userid = wp_get_current_user()->ID;
			$wpnsDbQueries->mo_plugin_activate();
			$Mo2fdbQueries->mo_plugin_activate();
			add_site_option( 'mo2f_activate_plugin', 1 );
			add_site_option( 'mo2f_login_policy', 1 );
			add_site_option( 'mo2f_is_NC', 1 );
			add_site_option( 'mo2f_is_NNC', 1 );
			add_site_option( 'mo2f_number_of_transactions', 1 );
			add_site_option( 'mo2f_set_transactions', 0 );
			add_site_option( 'mo2f_enable_forgotphone', 1 );
			add_site_option( 'mo2f_enable_2fa_for_users', 1 );
			add_site_option( 'mo2f_enable_2fa_prompt_on_login_page', 0 );
			add_site_option( 'mo2f_enable_xmlrpc', 0 );
			add_site_option( 'mo2fa_administrator',1 );
			add_site_option( 'mo2f_custom_plugin_name','miniOrange 2-Factor' );
			add_action( 'mo_auth_show_success_message', array($this, 'mo_auth_show_success_message'), 10, 1 );
			add_action( 'mo_auth_show_error_message', array($this, 'mo_auth_show_error_message'), 10, 1 );
			add_site_option('mo2f_onprem_admin' ,  $userid );
		
		}

		function mo_wpns_deactivate() 
		{

			global $moWpnsUtility;
			if( !$moWpnsUtility->check_empty_or_null( get_site_option('mo_wpns_registration_status') ) ) {
				delete_site_option('mo2f_email');
			}
			update_site_option('mo2f_activate_plugin', 1);
			delete_site_option('mo2f_customerKey');
			delete_site_option('mo2f_api_key');
			delete_site_option('mo2f_customer_token');
			delete_site_option('mo_wpns_transactionId');
			delete_site_option('mo_wpns_registration_status');

      		$two_fa_settings = new Miniorange_Authentication();
			$two_fa_settings->mo_auth_deactivate();
		}

		function mo_wpns_settings_style($hook)
		{
			if(strpos($hook, 'page_mo_2fa')){
				wp_enqueue_style( 'mo_wpns_admin_settings_style'			, plugins_url('includes/css/style_settings.css', __FILE__));
				wp_enqueue_style( 'mo_wpns_admin_settings_datatable_style'	, plugins_url('includes/css/jquery.dataTables.min.css', __FILE__));
				wp_enqueue_style( 'mo_wpns_button_settings_style'			, plugins_url('includes/css/button_styles.css',__FILE__));
			}

		}

		function mo_wpns_settings_script($hook)
		{
			wp_enqueue_script( 'mo_wpns_admin_settings_script'			, plugins_url('includes/js/settings_page.js', __FILE__ ), array('jquery'));
			if(strpos($hook, 'page_mo_2fa')){
			
				wp_enqueue_script( 'mo_wpns_admin_datatable_script'			, plugins_url('includes/js/jquery.dataTables.min.js', __FILE__ ), array('jquery'));
				wp_enqueue_script( 'mo_wpns_qrcode_script', plugins_url( "/includes/jquery-qrcode/jquery-qrcode.js", __FILE__ ) );
				wp_enqueue_script( 'mo_wpns_min_qrcode_script', plugins_url( "/includes/jquery-qrcode/jquery-qrcode.min.js", __FILE__ ) );
			}
		}
		function mo_show_message($content,$type) 
		{
		     if($type=="CUSTOM_MESSAGE")
			{
				echo "<div class='overlay_not_JQ_success' id='pop_up_success'><p class='popup_text_not_JQ'>".$content."</p> </div>";
				?>
				<script type="text/javascript">
				 setTimeout(function () {
					var element = document.getElementById("pop_up_success");
					   element.classList.toggle("overlay_not_JQ_success");
					   element.innerHTML = "";
						}, 4000);
						
				</script>
				<?php
			}
			 if($type=="NOTICE")
			{
				echo "<div class='overlay_not_JQ_error' id='pop_up_error'><p class='popup_text_not_JQ'>".$content."</p> </div>";
				?>
				<script type="text/javascript">
				 setTimeout(function () {
					var element = document.getElementById("pop_up_error");
					   element.classList.toggle("overlay_not_JQ_error");
					   element.innerHTML = "";
						}, 4000);
						
				</script>
				<?php
			}
			 if($type=="ERROR")
			 {
				echo "<div class='overlay_not_JQ_error' id='pop_up_error'><p class='popup_text_not_JQ'>".$content."</p> </div>";
				?>
				<script type="text/javascript">
				 setTimeout(function () {
					var element = document.getElementById("pop_up_error");
					   element.classList.toggle("overlay_not_JQ_error");
					   element.innerHTML = "";
						}, 4000);
						
				</script>
				<?php
			 }
			 if($type=="SUCCESS")
			 	{
					echo "<div class='overlay_not_JQ_success' id='pop_up_success'><p class='popup_text_not_JQ'>".$content."</p> </div>";
					?>
					<script type="text/javascript">
					 setTimeout(function () {
						var element = document.getElementById("pop_up_success");
						   element.classList.toggle("overlay_not_JQ_success");
						   element.innerHTML = "";
							}, 4000);
							
					</script>
					<?php
				}
		}

		function footer_link()
		{
			echo MoWpnsConstants::FOOTER_LINK;
		}

		function includes()
		{
			require('helper/pluginUtility.php');
			require('database/database_functions.php');
			require('database/database_functions_2fa.php');
			require('helper/utility.php');
			require('api/class-customer-setup.php');
			require('api/class-rba-attributes.php');
			require('api/class-two-factor-setup.php');	
			require('handler/feedback_form.php');
			require('handler/twofa/setup_twofa.php');
			require('handler/twofa/two_fa_settings.php');
			require('handler/twofa/two_fa_utility.php');
			require('handler/twofa/two_fa_constants.php');
			// require('handler/logger.php');
			require('helper/curl.php');
			require('helper/constants.php');
			require('helper/messages.php');	 
			require('controllers/wpns-loginsecurity-ajax.php');
			require('controllers/twofa/two_factor_ajax.php');
			require('controllers/dashboard_ajax.php');
		}

		function miniorange_reset_users($actions, $user_object){
		if ( current_user_can( 'administrator', $user_object->ID )  && get_user_meta($user_object->ID,'currentMethod', true) ) {		
			if(get_current_user_id() != $user_object->ID){
				$actions['miniorange_reset_users'] = "<a class='miniorange_reset_users' href='" . admin_url( "users.php?page=reset&action=reset_edit&amp;user=$user_object->ID") . "'>" . __( 'Reset 2 Factor', 'cgc_ub' ) . "</a>";
			}
		}	
		return $actions;
		
	}


	function mo2f_mapped_email_column($columns) {
		$columns['current_method'] = '2FA Method';
		return $columns;
	}

	function mo_reset_2fa_for_users_by_admin(){
		$nonce = wp_create_nonce('ResetTwoFnonce');
		if(isset($_GET['action']) && $_GET['action']== 'reset_edit'){
			$user_id = sanitize_text_field($_GET['user']);
			$user_info = get_userdata($user_id);	
			if(is_numeric($user_id))
			{
				?> 
					<form method="post" name="reset2fa" id="reset2fa" action="<?php echo esc_url('users.php'); ?>">						
						<div class="wrap">
						<h1>Reset 2nd Factor</h1>
						<p>You have specified this user for reset:</p>
						<ul>
						<li>ID #<?php echo esc_attr($user_info->ID); ?>: <?php echo esc_attr($user_info->user_login); ?></li> 
						</ul>
							<input type="hidden" name="userid" value="<?php echo esc_attr
							($user_id); ?>">
							<input type="hidden" name="miniorange_reset_2fa_option" value="mo_reset_2fa">
							<input type="hidden" name="nonce" value="<?php echo esc_attr($nonce);?>">
						<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Confirm Reset" ></p>
						</div>
					</form>
				<?php
			}
		}	
	}

	function miniorange_reset_save_settings(){
		if(isset($_POST['miniorange_reset_2fa_option']) && sanitize_text_field
			($_POST['miniorange_reset_2fa_option']) == 'mo_reset_2fa'){
				$nonce = sanitize_text_field($_POST['nonce']);
				if(!wp_verify_nonce($nonce,'ResetTwoFnonce'))
				{
					
					return;
				}
				$user_id = isset($_POST['userid']) && !empty($_POST['userid']) ? sanitize_text_field($_POST['userid']) : '';
				if(!empty($user_id)){
					if ( current_user_can( 'edit_user' ) )
					delete_user_meta($user_id,'currentMethod');							
					delete_user_meta($user_id,'mo2f_kba_challenge');
					delete_user_meta($user_id,'mo2f_2FA_method_to_configure');
					delete_user_meta($user_id,'Security Questions');
					delete_user_meta($user_id,'kba_questions_user');
					delete_user_meta($user_id,'mo2f_2FA_method_to_test');
				}
			}
			if (isset($_POST['mo_mfa_remove_account']) && $_POST['mo_mfa_remove_account'] == 'mo_wpns_reset_account' ) {
				delete_site_option( 'mo2f_customerKey' );
				delete_site_option( 'mo2f_api_key'  );
				delete_site_option( 'mo2f_customer_token' );
				delete_site_option( 'mo2f_app_secret'	);
				delete_site_option( 'mo_wpns_enable_log_requests'  );
				delete_site_option( 'mo2f_miniorange_admin');
				delete_site_option( 'mo_2factor_admin_registration_status');
			}
		}

	function mo2f_mapped_email_column_content($value, $column_name, $user_id) {
		$user = get_userdata( $user_id );
		if(get_site_option('is_onprem'))
		{
			$currentMethod = get_user_meta($user->ID,'currentMethod', true);
			if(!$currentMethod)
			$currentMethod = 'Not Registered for 2FA';
		}
		else
		{
			global $Mo2fdbQueries;
			$currentMethod = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
			if(!$currentMethod)
			$currentMethod = 'Not Registered for 2FA';           
		}
		
		if ( 'current_method' == $column_name )
			return $currentMethod;
		return $value;
	}

	}

	new Miniorange_twoFactor;