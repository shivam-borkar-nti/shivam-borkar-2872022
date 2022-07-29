<?php 
	
	global $moWpnsUtility,$mo2f_dirName,$Mo2fdbQueries;

	if ( current_user_can( 'manage_options' ) and isset( $_POST['option'] ) )
	{
		$option = trim($_POST['option']);
		switch($option)
		{
			case "mo_wpns_register_customer":
				_register_customer($_POST);																	   break;
			case "mo_wpns_verify_customer":
				_verify_customer($_POST);																	   break;
			case "mo_wpns_cancel":
				_revert_back_registration();																   break;
			case "mo_wpns_reset_password":
				_reset_password(); 																		  	   break;
		    case "mo2f_goto_verifycustomer":
		        _goto_sign_in_page();   break;
		}
	} 

	$user   = wp_get_current_user();
	$mo2f_current_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID);
 
	if((get_site_option('mo_wpns_registration_status') == 'MO_OTP_DELIVERED_SUCCESS' 
		|| get_site_option('mo_wpns_registration_status')  == 'MO_OTP_VALIDATION_FAILURE' 
		|| get_site_option('mo_wpns_registration_status')  == 'MO_OTP_DELIVERED_FAILURE') && in_array($mo2f_current_registration_status, array("MO_2_FACTOR_OTP_DELIVERED_SUCCESS", "MO_2_FACTOR_OTP_DELIVERED_FAILURE")))
	{
		$admin_phone = get_site_option('mo_wpns_admin_phone') ? get_site_option('mo_wpns_admin_phone') : "";
		include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'account'.DIRECTORY_SEPARATOR.'verify.php';
	} 
	else if ((get_site_option ( 'mo_wpns_verify_customer' ) == 'true' || (get_site_option('mo2f_email') && !get_site_option('mo2f_customerKey'))) && $mo2f_current_registration_status == "MO_2_FACTOR_VERIFY_CUSTOMER")
	{
		$admin_email = get_site_option('mo2f_email') ? get_site_option('mo2f_email') : "";		
		include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'account'.DIRECTORY_SEPARATOR.'login.php';
	}
	else if (! $moWpnsUtility->icr()) 
	{
		delete_site_option ( 'password_mismatch' );
		update_site_option ( 'mo_wpns_new_registration', 'true' );
    $Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'REGISTRATION_STARTED' ) );
		include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'account'.DIRECTORY_SEPARATOR.'register.php';
	} 
	else
	{
		$email = get_site_option('mo2f_email');
		$key   = get_site_option('mo2f_customerKey');
		$api   = get_site_option('mo2f_api_key');
		$token = get_site_option('mo2f_customer_token');
		include $mo2f_dirName . 'views'.DIRECTORY_SEPARATOR.'account'.DIRECTORY_SEPARATOR.'profile.php';
	}





	/* REGISTRATION RELATED FUNCTIONS */

	//Function to register new customer
	function _register_customer($post)
	{
		//validate and sanitize
		global $moWpnsUtility, $Mo2fdbQueries;
		$user   = wp_get_current_user();
		$email 			 = sanitize_email($post['email']);
		$company 		 = $_SERVER["SERVER_NAME"];

		$password 		 = sanitize_text_field($post['password']);
		$confirmPassword = sanitize_text_field($post['confirmPassword']);

		if( strlen( $password ) < 6 || strlen( $confirmPassword ) < 6)
		{
			do_action('wpns_show_message',MoWpnsMessages::showMessage('PASS_LENGTH'),'ERROR');
			return;
		}
		
		if( $password != $confirmPassword )
		{
			do_action('wpns_show_message',MoWpnsMessages::showMessage('PASS_MISMATCH'),'ERROR');
			return;
		}
		if( MoWpnsUtility::check_empty_or_null( $email ) || MoWpnsUtility::check_empty_or_null( $password ) 
			|| MoWpnsUtility::check_empty_or_null( $confirmPassword ) ) 
		{
			do_action('wpns_show_message',MoWpnsMessages::showMessage('REQUIRED_FIELDS'),'ERROR');
			return;
		} 

		update_site_option( 'mo2f_email', $email );
		
		update_site_option( 'mo_wpns_company'    , $company );
		
		update_site_option( 'mo_wpns_password'   , $password );

		$customer = new MocURL();
		$content  = json_decode($customer->check_customer($email), true);
		$Mo2fdbQueries->insert_user( $user->ID );
		switch ($content['status'])
		{
			case 'CUSTOMER_NOT_FOUND':
			      $customerKey = json_decode($customer->create_customer($email, $company, $password, $phone = '', $first_name = '', $last_name = ''), true);
				  
			   if(strcasecmp($customerKey['status'], 'SUCCESS') == 0) 
				{
					save_success_customer_config($email, $customerKey['id'], $customerKey['apiKey'], $customerKey['token'], $customerKey['appSecret']);
					_get_current_customer($email,$password);
				}
				
				break;
			default:
			     /*update_site_option('mo_wpns_verify_customer','true');
			     $Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );*/
				_get_current_customer($email,$password);
				break;
		}

	}


   function _goto_sign_in_page(){
   	   global  $Mo2fdbQueries;
   	   $user   = wp_get_current_user();
   	   update_site_option('mo_wpns_verify_customer','true');
	   $Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );
   }

	//Function to go back to the registration page
	function _revert_back_registration()
	{
		global $Mo2fdbQueries;
		$user   = wp_get_current_user();
		delete_site_option('mo2f_email');
		delete_site_option('mo_wpns_registration_status');
		delete_site_option('mo_wpns_verify_customer');
		$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => '' ) );
	}


	//Function to reset customer's password
	function _reset_password()
	{
		$customer = new MocURL();
		$forgot_password_response = json_decode($customer->mo_wpns_forgot_password());
		if($forgot_password_response->status == 'SUCCESS')
			do_action('wpns_show_message',MoWpnsMessages::showMessage('RESET_PASS'),'SUCCESS');
	}


	//Function to verify customer
	function _verify_customer($post)
	{
		global $moWpnsUtility;
		$email 	  = sanitize_email( $post['email'] );
		$password = sanitize_text_field( $post['password'] );

		if( $moWpnsUtility->check_empty_or_null( $email ) || $moWpnsUtility->check_empty_or_null( $password ) ) 
		{
			do_action('wpns_show_message',MoWpnsMessages::showMessage('REQUIRED_FIELDS'),'ERROR');
			return;
		} 
		_get_current_customer($email,$password);
	}


	//Function to validate OTP
	





	//Function to send OTP token
	


	//Function to get customer details
	function _get_current_customer($email,$password)
	{
		global $Mo2fdbQueries;
		$user   = wp_get_current_user();
		$customer 	 = new MocURL();
		$content     = $customer->get_customer_key($email, $password);
		$customerKey = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) 
		{
			if(isset($customerKey['phone'])){
				update_site_option( 'mo_wpns_admin_phone', $customerKey['phone'] );
				$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo2f_user_phone' => $customerKey['phone'] ) );
			}
			update_site_option('mo2f_email',$email);
			save_success_customer_config($email, $customerKey['id'], $customerKey['apiKey'], $customerKey['token'], $customerKey['appSecret']);
			do_action('wpns_show_message',MoWpnsMessages::showMessage('REG_SUCCESS'),'SUCCESS');
		} 
		else 
		{
			$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );
			update_site_option('mo_wpns_verify_customer', 'true');
			delete_site_option('mo_wpns_new_registration');
			do_action('wpns_show_message',MoWpnsMessages::showMessage('ACCOUNT_EXISTS'),'ERROR');
		}
	}
	
		
	//Save all required fields on customer registration/retrieval complete.
	function save_success_customer_config($email, $id, $apiKey, $token, $appSecret)
	{
		global $Mo2fdbQueries;

		$user   = wp_get_current_user();
		update_site_option( 'mo2f_customerKey'  , $id 		  );
		update_site_option( 'mo2f_api_key'       , $apiKey    );
		update_site_option( 'mo2f_customer_token'		 , $token 	  );
		update_site_option( 'mo2f_app_secret'			 , $appSecret );
		update_site_option( 'mo_wpns_enable_log_requests' , true 	  );
		update_site_option( 'mo2f_miniorange_admin', $user->ID );
		update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
		$Mo2fdbQueries->insert_user( $user->ID );
		$Mo2fdbQueries->update_user_details( $user->ID, array(
									'mo2f_EmailVerification_config_status' => get_site_option( 'mo2f_is_NC' ) == 0 ? true : false,
									'mo2f_user_email'                      => $email,
									'user_registration_with_miniorange'    => 'SUCCESS',
									'mo2f_2factor_enable_2fa_byusers'      => 1,
									'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS'
								) );
		$enduser               = new Two_Factor_Setup();
		$userinfo              = json_decode( $enduser->mo2f_get_userinfo( $email ), true );
		$mo2f_second_factor = 'NONE';
		if ( json_last_error() == JSON_ERROR_NONE ) {
			if ( $userinfo['status'] == 'SUCCESS' ) {
				$mo2f_second_factor = mo2f_update_and_sync_user_two_factor( $user->ID, $userinfo );
			}
		}
		$configured_2FA_method='';
		if( $mo2f_second_factor == 'EMAIL'){
			$enduser->mo2f_update_userinfo( $email, 'NONE', null, '', true );
			 $configured_2FA_method = 'NONE';
		}else if ( $mo2f_second_factor != 'NONE' ) {
			$configured_2FA_method = MO2f_Utility::mo2f_decode_2_factor( $mo2f_second_factor, "servertowpdb" );
			if ( get_site_option( 'mo2f_is_NC' ) == 0 ) {
				$auth_method_abr = str_replace( ' ', '', $configured_2FA_method );
				$Mo2fdbQueries->update_user_details( $user->ID, array(
					'mo2f_configured_2FA_method'                  => $configured_2FA_method,
					'mo2f_' . $auth_method_abr . '_config_status' => true
				) );
			} else {
				if ( in_array( $configured_2FA_method, array(
					'Email Verification',
					'Authy Authenticator',
					'OTP over SMS'
				) ) ) {
					$enduser->mo2f_update_userinfo( $email, 'NONE', null, '', true );
				}
			}
		}

		$mo2f_message = Mo2fConstants:: langTranslate( "ACCOUNT_RETRIEVED_SUCCESSFULLY" );
		if ( $configured_2FA_method != 'NONE' && get_site_option( 'mo2f_is_NC' ) == 0 ) {
			$mo2f_message .= ' <b>' . $configured_2FA_method . '</b> ' . Mo2fConstants:: langTranslate( "DEFAULT_2ND_FACTOR" ) . '. ';
		}
		$mo2f_message .= '<a href=\"admin.php?page=mo_2fa_two_fa\" >' . Mo2fConstants:: langTranslate( "CLICK_HERE" ) . '</a> ' . Mo2fConstants:: langTranslate( "CONFIGURE_2FA" );

		delete_user_meta( $user->ID, 'register_account' );

		$mo2f_customer_selected_plan = get_site_option( 'mo2f_customer_selected_plan' );
		if ( ! empty( $mo2f_customer_selected_plan ) ) {
			delete_site_option( 'mo2f_customer_selected_plan' );
			?><script>window.location.href="admin.php?page=mo_2fa_upgrade";</script><?php
		} else if ( $mo2f_second_factor == 'NONE' ) {
			if(get_user_meta( $user->ID, 'register_account_popup', true)){
				update_user_meta( $user->ID, 'configure_2FA', 1 );
			}
		}
		update_site_option( 'mo2f_message', $mo2f_message );
		delete_user_meta( $user->ID, 'register_account_popup' 	  );
		delete_site_option( 'mo_wpns_verify_customer'				  );
		delete_site_option( 'mo_wpns_registration_status'			  );
		delete_site_option( 'mo_wpns_password'						  );
	}