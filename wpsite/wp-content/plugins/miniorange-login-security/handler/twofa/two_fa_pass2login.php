<?php


include 'two_fa_login.php';
class Miniorange_Password_2Factor_Login {

	private $mo2f_kbaquestions;
	private $mo2f_userID;
	private $mo2f_rbastatus;
	private $mo2f_transactionid;

	
	public function miniorange_pass2login_redirect() {

		if(get_site_option('is_onprem') == 1)
		{
			if(isset($_GET['Txid'])&&isset($_GET['accessToken']))
			{
				$otpToken 	  = get_site_option($_GET['userID']);
				$txidstatus   = get_site_option($_GET['Txid']);
				$userIDd 	  = sanitize_text_field($_GET['userID']).'D';
				$otpTokenD 	  = get_site_option($userIDd); 
				$mo2f_dirName = dirname(__FILE__);
				$mo2f_dirName = explode('wp-content', $mo2f_dirName);
				$mo2f_dirName = explode('handler', $mo2f_dirName[1]);

				if(3 == $txidstatus)
				{
					$time 					= "time".sanitize_text_field($_GET['Txid']);
					$currentTimeInMillis 	= round(microtime(true) * 1000);
					$generatedTimeINMillis  = get_site_option($time); 
					$difference 			= ($currentTimeInMillis-$generatedTimeINMillis)/1000 ;
					if($difference <= 300)
					{
						if(sanitize_text_field($_GET['accessToken']) == $otpToken)
						{
							update_site_option($_GET['Txid'],1);
							delete_site_option($_GET['userID']);
							delete_site_option($userIDd);
							delete_site_option($time);
							
							$url =get_site_option('siteurl').DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR.$mo2f_dirName[0]."api".DIRECTORY_SEPARATOR."Validate.php";
							header("Location:$url");
							exit;
						}
						else if(sanitize_text_field($_GET['accessToken'])==$otpTokenD)
						{
							update_site_option($_GET['Txid'],0);
							delete_site_option($_GET['userID']);
							delete_site_option($userIDd);
							delete_site_option($time);

							$url =get_site_option('siteurl').DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR.$mo2f_dirName[0]."api".DIRECTORY_SEPARATOR."Denied.php";
							header("Location:$url");
							exit;
						}
						else
						{
							$url =get_site_option('siteurl').DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR.$mo2f_dirName[0]."api".DIRECTORY_SEPARATOR."Expire.php";
							header("Location:$url");	
							exit;	
						}	
					}
					else
					{
						delete_site_option(sanitize_text_field($_GET['userID']));
						delete_site_option($userIDd);
						delete_site_option($time);
						$url =get_site_option('siteurl').DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR.$mo2f_dirName[0]."api".DIRECTORY_SEPARATOR."Expire.php";
						header("Location:$url");	
						exit;
					}
				}
				else
				{
					$url =get_site_option('siteurl').DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR.$mo2f_dirName[0]."api".DIRECTORY_SEPARATOR."Expire.php";
					header("Location:$url");	
					exit;
				}
			}
			if(isset($_POST['txid']))
			{
				$status = get_site_option(sanitize_text_field($_POST['txid']));
				if($status ==1 || $status ==0)
					delete_site_option($_POST['txid']);
				echo esc_attr($status);
				exit();
			}
		}
		global $Mo2fdbQueries;
		
		if (!get_site_option( 'mo2f_login_policy' ) ) {
			
			if ( isset($_POST['miniorange_login_nonce'])) {
				$nonce = sanitize_text_field($_POST['miniorange_login_nonce']);
				$session_id = $this->create_session();
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-login-nonce' ) ) {
					$this->remove_current_activity($session_id);
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					
					$this->miniorange_pass2login_start_session();
					$mobile_login = new Miniorange_Mobile_Login();
					//validation and sanitization
					$username = '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2fa_username'] ) ) {
						MO2f_Utility::set_user_values($session_id, 'mo2f_login_message', 'Please enter username to proceed' );
						$mobile_login->mo_auth_show_error_message();
						return;
					} else {
						$username = sanitize_text_field( $_POST['mo2fa_username'] );
					}
					if ( username_exists( $username ) ) {	 /*if username exists in wp site */
						$user = new WP_User( $username );
						$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field($_REQUEST['redirect_to']) : null;
						MO2f_Utility::set_user_values($session_id, 'mo2f_current_user_id', $user->ID );
						MO2f_Utility::set_user_values($session_id, 'mo2f_1stfactor_status', 'VALIDATE_SUCCESS' );
						$this->mo2f_userId=$user->ID;
						$this->fstfactor='VALIDATE_SUCCESS';						
						$current_roles = miniorange_get_user_role( $user );
						$mo2f_configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
						$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
						$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
						$kba_configuration_status = $Mo2fdbQueries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $user->ID );

						if(get_site_option('is_onprem') and !empty(get_user_meta($user->ID,'currentMethod',true)))
						{
							$configuredMethod = get_user_meta($user->ID,'currentMethod',true);
							$mo2f_configured_2FA_method = empty($configuredMethod) ? 0 : 1;
							$mo_2factor_user_registration_status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
							$email 							 	 = get_user_meta($user->ID , 'email',true);

						}
						if ( $mo2f_configured_2FA_method ) {
							if ( $email && $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' or (get_site_option('is_onprem') and  $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS')) {
								if ( MO2f_Utility::check_if_request_is_from_mobile_device( $_SERVER['HTTP_USER_AGENT'] ) && $kba_configuration_status ) {
									$this->mo2f_pass2login_kba_verification( $user->ID, $redirect_to, $session_id );
								} else {
									$mo2f_second_factor = '';
									$mo2f_second_factor = mo2f_get_user_2ndfactor( $user );
									if(get_site_option('is_onprem'))
									{
										$mo2f_second_factor = get_user_meta($user->ID,'currentMethod',true);

										if($mo2f_second_factor == 'Security Questions')
										{
											$mo2f_second_factor = 'KBA';
										}
										else if($mo2f_second_factor == 'Google Authenticator')
										{
											$mo2f_second_factor = 'GOOGLE AUTHENTICATOR';
										}
										else if($mo2f_second_factor == 'Email Verification')
										{
											$mo2f_second_factor = 'OUT OF BAND EMAIL';
										}
										else
											$mo2f_second_factor = 'NONE';
									}

									if ( $mo2f_second_factor == 'MOBILE AUTHENTICATION' ) {
										$this->mo2f_pass2login_mobile_verification( $user, $redirect_to, $session_id );
									} else if ( $mo2f_second_factor == 'PUSH NOTIFICATIONS' || $mo2f_second_factor == 'OUT OF BAND EMAIL' ) {
										$this->mo2f_pass2login_push_oobemail_verification( $user, $mo2f_second_factor, $redirect_to, $session_id );
									} else if ( $mo2f_second_factor == 'SOFT TOKEN' || $mo2f_second_factor == 'SMS' || $mo2f_second_factor == 'PHONE VERIFICATION' || $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
										$this->mo2f_pass2login_otp_verification( $user, $mo2f_second_factor, $redirect_to, $session_id );
									} else if ( $mo2f_second_factor == 'KBA' ) {
										$this->mo2f_pass2login_kba_verification( $user->ID, $redirect_to, $session_id );
									} else {
										$this->remove_current_activity($session_id);
										MO2f_Utility::set_user_values($session_id, 'mo2f_login_message', 'Please try again or contact your admin.' );
										$mobile_login->mo_auth_show_success_message();
									}
								}
							} else {
								MO2f_Utility::set_user_values($session_id, 'mo2f_login_message', 'Please login into your account using password.' );
								$mobile_login->mo_auth_show_success_message('Please login into your account using password.');
								update_user_meta($user->ID,'userMessage','Please login into your account using password.');
								$mobile_login->mo2f_redirectto_wp_login();
							}
						} else {	
							MO2f_Utility::set_user_values( $session_id, "mo2f_login_message", 'Please login into your account using password.' );
							$mobile_login->mo_auth_show_success_message('Please login into your account using password.');
							update_user_meta($user->ID,'userMessage','Please login into your account using password.');
							$mobile_login->mo2f_redirectto_wp_login();
						}
					} else {
						$mobile_login->remove_current_activity($session_id);
						MO2f_Utility::set_user_values( $session_id, "mo2f_login_message", 'Invalid Username.' );
						$mobile_login->mo_auth_show_error_message('Invalid Username.');
					}
				}
			}

		}

		if ( isset( $_POST['mo2f_trust_device_confirm_nonce'] ) ) { /*register device as rba profile */
			$nonce = sanitize_text_field($_POST['mo2f_trust_device_confirm_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-trust-device-confirm-nonce' ) ) {
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$this->remove_current_activity($session_id_encrypt);
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR ' ) . '</strong>:' . mo2f_lt( 'Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				try {
					$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id', $session_id_encrypt );
					$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );
					$mo2f_rba_status = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_rba_status',$session_id_encrypt );
					mo2f_register_profile( $email, 'true', $mo2f_rba_status );
				} catch ( Exception $e ) {
					echo $e->getMessage();
				}
				$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}else if ( isset( $_POST['mo2f_trust_device_cancel_nonce'] ) ) { /*do not register device as rba profile */
			$nonce = sanitize_text_field($_POST['mo2f_trust_device_cancel_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-trust-device-cancel-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}else if ( isset( $_POST['miniorange_challenge_forgotphone_nonce'] ) ) { /*check kba validation*/
			$nonce = sanitize_text_field($_POST['miniorange_challenge_forgotphone_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-challenge-forgotphone-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . __( 'ERROR' ) . '</strong>:' . __( 'Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$forgot_phone_enable = get_site_option( 'mo2f_enable_forgotphone' );
				$forgot_phone_kba_enable = get_site_option( 'mo2f_enable_forgotphone_kba' );
				$forgot_phone_email_enable = get_site_option( 'mo2f_enable_forgotphone_email' );
				$second_factor = isset( $_POST['mo2f_configured_2FA_method'] ) ? sanitize_text_field($_POST['mo2f_configured_2FA_method']) : 'KBA';
				$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$user = unserialize( $_SESSION['mo2f_current_user'] );
				$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id', $session_id_encrypt );
				$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );
				$kba_configuration_status = $Mo2fdbQueries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $user_id );

				if ( $forgot_phone_enable && $forgot_phone_email_enable && $second_factor == 'OTP OVER EMAIL' ) {
					$customer = new Customer_Setup();
					$content  = json_decode( $customer->send_otp_token( $email, 'EMAIL', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
					$mo2fa_login_message = '';
					$mo2f_login_status   = '';

					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
						MO2f_Utility::set_user_values( $session_id_encrypt, "mo2f_transactionId", $content['txId'] );
						$this->mo2f_transactionid=$content['txId'];
						$mo2fa_login_message = 'A one time passcode has been sent to <b>' . MO2f_Utility::mo2f_get_hidden_email( $email ) . '</b>. Please enter the OTP to verify your identity.';
						$mo2f_login_status   = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL';
					} else {
						$mo2fa_login_message = 'Error occured while sending OTP over your regsitered email. Please try again.';
						$mo2f_login_status   = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL';
					}
					$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2f_login_status, $mo2fa_login_message, $redirect_to,null );
				} else if ( $forgot_phone_enable && $forgot_phone_kba_enable ) {
					if ( $kba_configuration_status ) {
						$this->mo2f_pass2login_kba_verification( $user_id, $redirect_to, $session_id_encrypt );
					} else {
						$mo2fa_login_message = 'Your KBA is not configured. Please choose other option to procedd further.';
						$mo2f_login_status   = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL';
						$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2f_login_status, $mo2fa_login_message, $redirect_to, null );
					}
				}
			}
		}else if ( isset( $_POST['miniorange_alternate_login_kba_nonce'] ) ) { /*check kba validation*/
			$nonce = sanitize_text_field($_POST['miniorange_alternate_login_kba_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-alternate-login-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
				$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$this->mo2f_pass2login_kba_verification( $user_id, $redirect_to,$session_id_encrypt );
			}
		}else if ( isset( $_POST['miniorange_kba_nonce'] ) ) { /*check kba validation*/
			$nonce = sanitize_text_field($_POST['miniorange_kba_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			}
			else{
				
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
				$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				if ( isset( $user_id ) ) {
					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_2'] ) ) {
						$mo2fa_login_message = 'Please provide both the answers.';
						$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
						$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
					}
					$otpToken      = array();
					$kba_questions = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo_2_factor_kba_questions',$session_id_encrypt );
					$otpToken[0] = $kba_questions[0];
					$otpToken[1] = sanitize_text_field( $_POST['mo2f_answer_1'] );
					$otpToken[2] = $kba_questions[1];
					$otpToken[3] = sanitize_text_field( $_POST['mo2f_answer_2'] );
					$check_trust_device = isset( $_POST['mo2f_trust_device'] ) ? sanitize_text_field($_POST['mo2f_trust_device']) : 'false';
					//if the php session folder has insufficient permissions, cookies to be used
					$mo2f_login_transaction_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_transactionId', $session_id_encrypt );

					$mo2f_rba_status = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_rba_status',$session_id_encrypt );
					$kba_validate = new Customer_Setup();
					$kba_validate_response = json_decode( $kba_validate->validate_otp_token( 'KBA', null, $mo2f_login_transaction_id, $otpToken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
					$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );
					if ( strcasecmp( $kba_validate_response['status'], 'SUCCESS' ) == 0 ) {
						if ( get_site_option( 'mo2f_remember_device' ) && $check_trust_device == 'on' ) {
							try {
								mo2f_register_profile( $email, 'true', $mo2f_rba_status );
							} catch ( Exception $e ) {
								echo $e->getMessage();
							}
							$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
						} else {
							$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
						}
					} else {

						$mo2fa_login_message = 'The answers you have provided are incorrect.';
						$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
						$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null);
					}
				} else {
					$this->remove_current_activity($session_id_encrypt);
					return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again..' ) );
				}
				
			}
		}else if ( isset( $_POST['miniorange_mobile_validation_nonce'] ) ) { 
			/*check mobile validation */
			$nonce = $_POST['miniorange_mobile_validation_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				if(get_site_option('is_onprem') )
				{
					$txid   = sanitize_email($_POST['TxidEmail']);
					$status = get_site_option($txid);
					if($status != '')
					{
						if($status != 1)
						{
							return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again.' ) );	
						}
					}
				}
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				//if the php session folder has insufficient permissions, cookies to be used
				$mo2f_login_transaction_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_transactionId',$session_id_encrypt );
				$redirect_to       = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$checkMobileStatus = new Two_Factor_Setup();
				$content           = $checkMobileStatus->check_mobile_status( $mo2f_login_transaction_id );
				$response          = json_decode( $content, true );
				if(get_site_option("is_onprem")==1)
				{
					$this->mo2fa_pass2login($redirect_to,$session_id_encrypt);
				}
				if ( json_last_error() == JSON_ERROR_NONE ) {
					if ( $response['status'] == 'SUCCESS' ) {
						if ( get_site_option( 'mo2f_remember_device' ) ) {
							$mo2fa_login_status = 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE';
							$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, null, $redirect_to, null );
						} else {
							$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
						}
					} else {
						$this->remove_current_activity($session_id_encrypt);
						return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again.' ) );
					}
				} else {
					$this->remove_current_activity($session_id_encrypt);
					return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again.' ) );
				}
			}
		}else if ( isset( $_POST['miniorange_mobile_validation_failed_nonce'] ) ) { /*Back to miniOrange Login Page if mobile validation failed and from back button of mobile challenge, soft token and default login*/
			$nonce = sanitize_text_field($_POST['miniorange_mobile_validation_failed_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-failed-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$this->remove_current_activity($session_id_encrypt);
				$currentMethod = isset( $_POST['currentMethod'] ) ? sanitize_text_field($_POST['currentMethod']) : null;
				
			}
		}else if ( isset( $_POST['miniorange_forgotphone'] ) ) { /*Click on the link of forgotphone */
			$nonce = sanitize_text_field($_POST['miniorange_forgotphone']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-forgotphone' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
				return $error;
			} else {
				$mo2fa_login_status  = isset( $_POST['request_origin_method'] ) ? sanitize_text_field($_POST['request_origin_method']) : null;
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$redirect_to         = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
				$mo2fa_login_message = '';
				$this->miniorange_pass2login_start_session();
				$customer                 = new Customer_Setup();
				$user_id                  = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
				$user_email               = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );
				$kba_configuration_status = $Mo2fdbQueries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $user_id );

				if ( $kba_configuration_status ) {
					$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL';
					$pass2fa_login      = new Miniorange_Password_2Factor_Login();
					$pass2fa_login->mo2f_pass2login_kba_verification( $user_id, $redirect_to,$session_id_encrypt );
				} else {
					$hidden_user_email = MO2f_Utility::mo2f_get_hidden_email( $user_email );
					$content           = json_decode( $customer->send_otp_token( $user_email, 'EMAIL', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
					
					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
						$session_cookie_variables = array( 'mo2f-login-qrCode', 'mo2f_transactionId' );
						MO2f_Utility::unset_session_variables( $session_cookie_variables );
						MO2f_Utility::unset_cookie_variables( $session_cookie_variables );
						MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId',$session_id_encrypt );

						//if the php session folder has insufficient permissions, cookies to be used
						MO2f_Utility::set_user_values( $session_id_encrypt,'mo2f_login_message', 'A one time passcode has been sent to <b>' . $hidden_user_email . '</b>. Please enter the OTP to verify your identity.' );
						MO2f_Utility::set_user_values( $session_id_encrypt, 'mo2f_transactionId', $content['txId'] );
						$this->mo2f_transactionid=$content['txId'];
						$mo2fa_login_message = 'A one time passcode has been sent to <b>' . $hidden_user_email . '</b>. Please enter the OTP to verify your identity.';
						$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL';
					} else {



				$mo2fa_login_message = 'Error occurred while sending OTP over email. Please try again.';
				if($mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION'){
					$challengeMobile = new Customer_Setup();
					$content         = $challengeMobile->send_otp_token( $user_email, 'MOBILE AUTHENTICATION', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) );
					$response        = json_decode( $content, true );
					if ( $response['status'] == 'SUCCESS' ) {
						$qrCode = $response['qrCode'];
						MO2f_Utility::set_user_values( $session_id_encrypt,'mo2f_transactionId', $response['txId'] );
						$this->mo2f_transactionid=$response['txId'];
						$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, $qrCode );
						
					}
				}
			}
			$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, null );
		}
		$pass2fa_login = new Miniorange_Password_2Factor_Login();
		$pass2fa_login->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
	}
}else if ( isset( $_POST['miniorange_softtoken'] ) ) { /*Click on the link of phone is offline */
	$nonce = sanitize_text_field($_POST['miniorange_softtoken']);
	if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-softtoken' ) ) {
		$error = new WP_Error();
		$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
		return $error;
	} else {
		$this->miniorange_pass2login_start_session();
		$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
		$session_cookie_variables = array( 'mo2f-login-qrCode', 'mo2f_transactionId' );
		MO2f_Utility::unset_session_variables( $session_cookie_variables );
		MO2f_Utility::unset_cookie_variables( $session_cookie_variables );
		MO2f_Utility::unset_temp_user_details_in_table('mo2f_transactionId',$session_id_encrypt );
		$redirect_to         = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
		$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
		$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
		$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
	}
}else if ( isset( $_POST['miniorange_soft_token_nonce'] ) ) { /*Validate Soft Token,OTP over SMS,OTP over EMAIL,Phone verification */
	$nonce = sanitize_text_field($_POST['miniorange_soft_token_nonce']);
	if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
		$error = new WP_Error();
		$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
		return $error;
	} else {
		$this->miniorange_pass2login_start_session();
		$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
		$mo2fa_login_status = isset( $_POST['request_origin_method'] ) ? sanitize_text_field($_POST['request_origin_method']) : null;
		$redirect_to        = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
		$softtoken          = '';
		$user_id    		= MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
		
		$attempts = get_site_option('mo2f_attempts_before_redirect', 3);
		if ( MO2f_utility::mo2f_check_empty_or_null( $_POST['mo2fa_softtoken'] ) ) {
			if($attempts>1 || $attempts=='disabled')
			{
				update_site_option('mo2f_attempts_before_redirect', $attempts-1 );
				$mo2fa_login_message = 'Please enter OTP to proceed.';
				$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
			}else{
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
				$this->remove_current_activity($session_id_encrypt);
				return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.');
			}
		} else {

			$softtoken = sanitize_text_field( $_POST['mo2fa_softtoken'] );
			if ( ! MO2f_utility::mo2f_check_number_length( $softtoken ) ) {
				if($attempts>1|| $attempts=='disabled')
				{
					update_site_option('mo2f_attempts_before_redirect', $attempts-1 );
					$mo2fa_login_message = 'Invalid OTP. Only digits within range 4-8 are allowed. Please try again.';
					$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
					
				}else{
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
					$this->remove_current_activity($session_id_encrypt);
					update_site_option('mo2f_attempts_before_redirect', 3);
					return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.');
				}
			}
		}		
		$user_email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );

		if ( isset( $user_id ) ) {
			$customer = new Customer_Setup();
			$content  = '';
					//if the php session folder has insufficient permissions, cookies to be used
			$mo2f_login_transaction_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_transactionId', $session_id_encrypt );
			if ( isset( $mo2fa_login_status ) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' ) {
				$content = json_decode( $customer->validate_otp_token( 'EMAIL', null, $mo2f_login_transaction_id, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
			} else if ( isset( $mo2fa_login_status ) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' ) {
				$content = json_decode( $customer->validate_otp_token( 'SMS', null, $mo2f_login_transaction_id, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
			} else if ( isset( $mo2fa_login_status ) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION' ) {
				$content = json_decode( $customer->validate_otp_token( 'PHONE VERIFICATION', null, $mo2f_login_transaction_id, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
			} else if ( isset( $mo2fa_login_status ) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' ) {
				$content = json_decode( $customer->validate_otp_token( 'SOFT TOKEN', $user_email, null, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
			} else if ( isset( $mo2fa_login_status ) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION' ) {
				if(get_site_option('is_onprem')){
					include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR. 'gaonprem.php';
					$gauth_obj= new Google_auth_onpremise();
					$secret= $gauth_obj->mo_GAuth_get_secret($user_id);
					$content=$gauth_obj->verifyCode($secret, $softtoken);
				}
				else{
					$content = json_decode( $customer->validate_otp_token( 'GOOGLE AUTHENTICATOR', $user_email, null, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

				}
			} else {
				$this->remove_current_activity($session_id_encrypt);
				return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Invalid Request. Please try again.' ) );
			}

			if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
				update_site_option('mo2f_attempts_before_redirect', 3);
				if ( get_site_option( 'mo2f_remember_device' ) ) {
					$mo2fa_login_status = 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE';
					$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, null, $redirect_to,null );
				} else {
					$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
				}
			} else {
				if($attempts>1 || $attempts=='disabled')
				{
					update_site_option('mo2f_attempts_before_redirect', $attempts-1);
					$message = $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' ? 'You have entered an invalid OTP.<br>Please click on <b>Sync Time</b> in the miniOrange Authenticator app to sync your phone time with the miniOrange servers and try again.' : 'Invalid OTP. Please try again.';
					$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $message, $redirect_to,null );
				}else{
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
					$this->remove_current_activity($session_id_encrypt);
					update_site_option('mo2f_attempts_before_redirect', 3);
					return new WP_Error( 'limit_exceeded', '<strong>ERROR</strong>: Number of attempts exceeded.');
				}
			}
		} else {
			$this->remove_current_activity($session_id_encrypt);
			return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again..' ) );
		}
	}
}else if ( isset( $_POST['miniorange_inline_skip_registration_nonce'] ) ) { /*Validate Soft Token,OTP over SMS,OTP over EMAIL,Phone verification */
	$nonce = sanitize_text_field($_POST['miniorange_inline_skip_registration_nonce']);
	if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-skip-registration-nonce' ) ) {
		$error = new WP_Error();
		$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );

		return $error;
	} else {
		$this->miniorange_pass2login_start_session();
		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
		$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id);
		$this->mo2fa_pass2login(null, $session_id );
	}
}else if ( isset( $_POST['miniorange_attribute_collection_nonce'] ) ) { /*Handling Rba Attributes from other plugins */
	$nonce = sanitize_text_field($_POST['miniorange_attribute_collection_nonce']);
	if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-login-attribute-collection-nonce' ) ) {
		$error = new WP_Error();
		$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );

		return $error;
	} else {
		$this->miniorange_pass2login_start_session();

		$user_id     = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
		$currentuser = get_user_by( 'id', $user_id );
		$attributes  = isset( $_POST['miniorange_rba_attribures'] ) ? sanitize_text_field($_POST['miniorange_rba_attribures']) : null;
		$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field($_POST['redirect_to']) : null;
		$session_id  = isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null;
		$this->miniorange_initiate_2nd_factor( $currentuser, $attributes, $redirect_to,$session_id );
	}
}
}

function deniedMessage($message)
{
	if(empty($message) && get_site_option("deniedMessage") )
		delete_site_option('deniedMessage');
	else
		return $message;
}
function remove_current_activity($session_id) {
	global $Mo2fdbQueries;
	$session_variables = array(
		'mo2f_current_user_id',
		'mo2f_1stfactor_status',
		'mo_2factor_login_status',
		'mo2f-login-qrCode',
		'mo2f_transactionId',
		'mo2f_login_message',
		'mo2f_rba_status',
		'mo_2_factor_kba_questions',
		'mo2f_show_qr_code',
		'mo2f_google_auth',
		'mo2f_authy_keys'
	);

	$cookie_variables = array(
		'mo2f_current_user_id',
		'mo2f_1stfactor_status',
		'mo_2factor_login_status',
		'mo2f-login-qrCode',
		'mo2f_transactionId',
		'mo2f_login_message',
		'mo2f_rba_status_status',
		'mo2f_rba_status_sessionUuid',
		'mo2f_rba_status_decision_flag',
		'kba_question1',
		'kba_question2',
		'mo2f_show_qr_code',
		'mo2f_google_auth',
		'mo2f_authy_keys'
	);

	$temp_table_variables = array(
		'session_id',
		'mo2f_current_user_id',
		'mo2f_login_message',
		'mo2f_1stfactor_status',
		'mo2f_transactionId',
		'mo_2_factor_kba_questions',
		'mo2f_rba_status',
		'ts_created'
	);

	MO2f_Utility::unset_session_variables( $session_variables );
	MO2f_Utility::unset_cookie_variables( $cookie_variables );
	$key        = get_site_option( 'mo2f_encryption_key' );
	$session_id = MO2f_Utility::decrypt_data( $session_id, $key );
	$Mo2fdbQueries->save_user_login_details( $session_id, array( 
		
		'mo2f_current_user_id' => '',
		'mo2f_login_message' => '',
		'mo2f_1stfactor_status' => '',
		'mo2f_transactionId' => '',
		'mo_2_factor_kba_questions' => '',
		'mo2f_rba_status' => '',
		'ts_created' => ''
	) );
	

}

public function miniorange_pass2login_start_session() {
	if ( ! session_id() || session_id() == '' || ! isset( $_SESSION ) ) {
		$session_path = ini_get('session.save_path');
		if( is_writable($session_path) && is_readable($session_path) ) {
			session_start(); 
		}
	}
}

function mo2f_pass2login_kba_verification( $user_id, $redirect_to, $session_id  ) {
	global $Mo2fdbQueries,$LoginuserID;
	$LoginuserID = $user_id;
	$user_email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user_id );
	if(is_null($session_id)) {
		$session_id=$this->create_session();
	}
	if(get_site_option('is_onprem')){
		$question_answers 			= get_user_meta($user_id , 'mo2f_kba_challenge', true);
		$challenge_questions 		= array_keys($question_answers);
		$random_keys 				= array_rand($challenge_questions,2);
		$challenge_ques1 			= $challenge_questions[$random_keys[0]];
		$challenge_ques2 			= $challenge_questions[$random_keys[1]];
		$questions 					=  array($challenge_ques1,$challenge_ques2);
		update_user_meta( $user_id, 'kba_questions_user', $questions );
		$mo2fa_login_message 		= 'Please answer the following questions:';
		$mo2fa_login_status  		= 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
		$mo2f_kbaquestions 			= $questions;
		MO2f_Utility::set_user_values( $session_id, 'mo_2_factor_kba_questions', $questions );	
		$this->miniorange_pass2login_form_fields($session_id , $mo2fa_login_status, $mo2fa_login_message, $redirect_to=null, $this->mo2f_kbaquestions );
	}

	else{
		$challengeKba = new Customer_Setup();
		$content      = $challengeKba->send_otp_token( $user_email, 'KBA', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) );
		$response     = json_decode( $content, true );
		if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate Qr code */
			if ( $response['status'] == 'SUCCESS' ) {
				MO2f_Utility::set_user_values( $session_id,"mo2f_transactionId", $response['txId'] );
				$this->mo2f_transactionid = $response['txId'];
				$questions                             = array();
				$questions[0]                          = $response['questions'][0]['question'];
				$questions[1]                          = $response['questions'][1]['question'];
				MO2f_Utility::set_user_values( $session_id, 'mo_2_factor_kba_questions', $questions );
				$this->mo2f_kbaquestions=$questions;
				$mo2fa_login_message = 'Please answer the following questions:';
				$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
				$this->miniorange_pass2login_form_fields( $session_id ,$mo2fa_login_status, $mo2fa_login_message, $redirect_to = null, $this->mo2f_kbaquestions );
			} else if ( $response['status'] == 'ERROR' ) {
				$this->remove_current_activity($session_id);
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );
				return $error;
			}
		} else {
			$this->remove_current_activity($session_id);
			$error = new WP_Error();
			$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );
			return $error;
		}
	}
}

function miniorange_pass2login_form_fields(  $session_id_encrypt,$mo2fa_login_status = null, $mo2fa_login_message = null, $redirect_to = null, $qrCode = null  ) {

	$login_status  = $mo2fa_login_status;
	$login_message = $mo2fa_login_message;
		if ( $this->miniorange_pass2login_check_mobile_status( $login_status ) ) { //for mobile
			$transactionid = $this->mo2f_transactionid ? $this->mo2f_transactionid : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_transactionId',$session_id_encrypt );
			mo2f_get_qrcode_authentication_prompt( $login_status, $login_message, $redirect_to, $qrCode, $session_id_encrypt, $transactionid  );
			exit;
		} else if ( $this->miniorange_pass2login_check_otp_status( $login_status ) ) { //for soft-token,otp over email,sms,phone verification,google auth
			$user_id = $this->mo2f_userID ? $this->mo2f_userID : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
			mo2f_get_otp_authentication_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt,$user_id  );
			exit;
		} else if ( $this->miniorange_pass2login_check_forgotphone_status( $login_status ) ) { // forgot phone page if both KBA and Email are configured.
			mo2f_get_forgotphone_form( $login_status, $login_message, $redirect_to, $session_id_encrypt  );
			exit;
		}else if ( $this->miniorange_pass2login_check_push_oobemail_status( $login_status ) ) { //for push and out of band email.
			$transactionid = $this->mo2f_transactionid ? $this->mo2f_transactionid : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_transactionId',$session_id_encrypt );
			$user_id = $this->mo2f_userID ? $this->mo2f_userID : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
			mo2f_get_push_notification_oobemail_prompt( $user_id, $login_status, $login_message, $redirect_to, $session_id_encrypt, $transactionid  );
			exit;
		} else if ( $this->miniorange_pass2login_reconfig_google( $login_status ) ) { //MO_2_FACTOR_RECONFIG_GOOGLE
			// shortcode
			$user_id = $this->mo2f_userID ? $this->mo2f_userID : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
			$this->mo2f_redirect_shortcode_addon( $user_id, $login_status, $login_message, 'reconfigure_google' );
			exit;
		} else if ( $this->miniorange_pass2login_reconfig_kba( $login_status ) ) { //MO_2_FACTOR_RECONFIG_KBA
			$user_id = $this->mo2f_userID ? $this->mo2f_userID : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
			$this->mo2f_redirect_shortcode_addon( $user_id, $login_status, $login_message, 'reconfigure_kba' );
			exit;
		} else if ( $this->miniorange_pass2login_check_kba_status( $login_status ) ) { // for Kba
			$kbaquestions = $this->mo2f_kbaquestions ? $this->mo2f_kbaquestions : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo_2_factor_kba_questions',$session_id_encrypt );
			if(get_site_option('is_onprem')){
				$user_id = $this->mo2f_userID ? $this->mo2f_userID : MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id',$session_id_encrypt );
				$ques = get_user_meta( $user_id, 'kba_questions_user');
				mo2f_get_kba_authentication_prompt( $login_message, $redirect_to, $session_id_encrypt, $ques[0]  );
			}
			else{
				mo2f_get_kba_authentication_prompt( $login_message, $redirect_to, $session_id_encrypt, $kbaquestions  );
			}
			exit;
		} else if ( $this->miniorange_pass2login_check_trusted_device_status( $login_status ) ) { // trusted device
			mo2f_get_device_form( $redirect_to, $session_id_encrypt  );
			exit;
		} else { //show login screen
			$this->mo_2_factor_pass2login_show_wp_login_form();
			if(get_site_option('is_onprem')){
				$this->mo_2_factor_pass2login_show_wp_login_form();
			}
		}
	}

	function miniorange_pass2login_check_mobile_status( $login_status ) {    //mobile authentication
		if ( $login_status == 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_check_otp_status( $login_status, $sso = false ) {
		if ( $login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' || $login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' || $login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' || $login_status == 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION' || $login_status == 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_check_forgotphone_status( $login_status ) {  // after clicking on forgotphone link when both kba and email are configured
		if ( $login_status == 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_check_push_oobemail_status( $login_status ) {  // for push and out of and email
		if ( $login_status == 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' || $login_status == 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_reconfig_google( $login_status ) {
		if ( $login_status == 'MO_2_FACTOR_RECONFIG_GOOGLE' ) {
			return true;
		}

		return false;
	}

	function mo2f_redirect_shortcode_addon( $current_user_id, $login_status, $login_message, $identity ) {

		do_action( 'mo2f_shortcode_addon', $current_user_id, $login_status, $login_message, $identity );


	}

	function miniorange_pass2login_reconfig_kba( $login_status ) {
		if ( $login_status == 'MO_2_FACTOR_RECONFIG_KBA' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_check_kba_status( $login_status ) {
		if ( $login_status == 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION' ) {
			return true;
		}

		return false;
	}

	function miniorange_pass2login_check_trusted_device_status( $login_status ) {

		if ( $login_status == 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE' ) {
			return true;
		}

		return false;
	}

	function mo_2_factor_pass2login_show_wp_login_form() {

		$session_id_encrypt = isset( $_POST['miniorange_user_session'] ) ? sanitize_text_field($_POST['miniorange_user_session']) : (isset( $_POST['session_id'] ) ? sanitize_text_field($_POST['session_id']) : null);
		if(is_null($session_id_encrypt)) {
			$session_id_encrypt=$this->create_session();
		}
		?>
		<p><input type="hidden" name="miniorange_login_nonce"
			value="<?php echo wp_create_nonce( 'miniorange-2-factor-login-nonce' ); ?>"/>

			<input type="hidden" id="sessid" name="miniorange_user_session"
			value="<?php echo $session_id_encrypt; ?>"/>

		</p>

		<?php
		
			if( get_site_option('mo2f_enable_2fa_prompt_on_login_page'))
			{
				echo "\t<p>\n";
				echo "\t\t<label class=\"mo2f_instuction1\" title=\"".__('If you don\'t have 2-factor authentication enabled for your WordPress account, leave this field empty.','google-authenticator')."\">".__('2 Factor Authentication code*','google-authenticator')."<span id=\"google-auth-info\"></span><br />\n";
				echo "\t\t<input type=\"text\" placeholder=\"No soft Token ? Skip\" name=\"mo_softtoken\" id=\"mo2f_2fa_code\" class=\"mo2f_2fa_code\" value=\"\" size=\"20\" style=\"ime-mode: inactive;\" /></label>\n";
				echo "\t<p class=\"mo2f_instuction2\" style='color:red; font-size:12px;padding:5px'>* Skip the authentication code if it doesn't apply.</p>\n";
				echo "\t</p>\n";
				echo " \r\n";
				echo " \r\n";
				echo "\n";
			}
		

	}

	function mo2f_pass2login_mobile_verification( $user, $redirect_to, $session_id_encrypt=null ) {
		global $Mo2fdbQueries;
		if (is_null($session_id_encrypt)){
			$session_id_encrypt=$this->create_session();
		}
		$user_email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if ( MO2f_Utility::check_if_request_is_from_mobile_device( $useragent ) ) {
			$session_cookie_variables = array( 'mo2f-login-qrCode', 'mo2f_transactionId' );

			MO2f_Utility::unset_session_variables( $session_cookie_variables );
			MO2f_Utility::unset_cookie_variables( $session_cookie_variables);
			MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId',$session_id_encrypt);

			$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
			$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
		} else {
			$challengeMobile = new Customer_Setup();
			$content         = $challengeMobile->send_otp_token( $user_email, 'MOBILE AUTHENTICATION', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) );
			$response        = json_decode( $content, true );
			if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate Qr code */
				if ( $response['status'] == 'SUCCESS' ) {
					$qrCode = $response['qrCode'];
					MO2f_Utility::set_user_values( $session_id_encrypt,'mo2f_transactionId', $response['txId'] );
					$this->mo2f_transactionid=$response['txId'];
					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION';
					$this->miniorange_pass2login_form_fields( $session_id_encrypt ,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, $qrCode);
				} else if ( $response['status'] == 'ERROR' ) {
					$this->remove_current_activity($session_id_encrypt);
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

					return $error;
				}
			} else {
				$this->remove_current_activity($session_id_encrypt);
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

				return $error;
			}
		}

	}

	function mo2f_pass2login_push_oobemail_verification( $current_user, $mo2f_second_factor, $redirect_to, $session_id=null ) {
		
		if(get_site_option('is_onprem')==1)
		{
			global $Mo2fdbQueries;
			if(is_null($session_id)){
				$session_id=$this->create_session();
			}
			$email 		= get_user_meta($current_user->ID,'email',true);
			$subject 	= "Email verification";
			$headers 	= array('Content-Type: text/html; charset=UTF-8');
			$txid 		= '';
			$otpToken 	= '';
			$otpTokenD 	= '';
			for($i=1;$i<7;$i++)
			{
				$otpToken .= rand(0,9);
				$txid .= rand(100,999);
				$otpTokenD .= rand(0,9);
			}
			$otpTokenH =  hash('sha512',$otpToken);
			$otpTokenDH = hash('sha512', $otpTokenD);
			$_SESSION['txid'] = $txid;
			$_SESSION['otpToken'] = $otpToken;
			$userID = hash('sha512',$current_user->ID);
			update_site_option($userID,$otpTokenH);
			update_site_option($txid,3);
			$userIDd = $userID . 'D';
			update_site_option($userIDd,$otpTokenDH);
			$url =  get_site_option('siteurl').'/wp-login.php?';
			$message =  '<table cellpadding="25" style="margin:0px auto">
			<tbody>
			<tr>
			<td>
			<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
			<tbody>
			<tr>
			<td><img src="https://ci5.googleusercontent.com/proxy/10EQeM1udyBOkfD2dwxGhIaMXV4lOwCRtUecpsDkZISL0JIkOL2JhaYhVp54q6Sk656rW2rpAFJFEgGQiAOVcYIIKxXYMHHMNSNB=s0-d-e1-ft#https://login.xecurify.com/moas/images/xecurify-logo.png" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
			</tr>
			</tbody>
			</table>
			<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
			<tbody>
			<tr>
			<td>
			<p style="margin-top:0;margin-bottom:20px">Dear Customers,</p>
			<p style="margin-top:0;margin-bottom:10px">You initiated a transaction <b>WordPress 2 Factor Authentication Plugin</b>:</p>
			<p style="margin-top:0;margin-bottom:10px">To accept, <a href="'.sanitize_text_field($url).'userID='.sanitize_text_field($userID).'&amp;accessToken='.sanitize_text_field($otpTokenH).'&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid='.$txid.'&amp;user='.sanitize_text_field($email).'" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://login.xecurify.com/moas/rest/validate-otp?customerKey%3D182589%26otpToken%3D735705%26secondFactorAuthType%3DOUT%2BOF%2BBAND%2BEMAIL%26user%3D'.sanitize_email($email).'&amp;source=gmail&amp;ust=1569905139580000&amp;usg=AFQjCNExKCcqZucdgRm9-0m360FdYAIioA">Accept Transaction</a></p>
			<p style="margin-top:0;margin-bottom:10px">To deny, <a href="'.sanitize_text_field($url).'userID='.sanitize_text_field($userID).'&amp;accessToken='.sanitize_text_field ($otpTokenDH).'&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid='.$txid.'&amp;user='.sanitize_text_field($email).'" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://login.xecurify.com/moas/rest/validate-otp?customerKey%3D182589%26otpToken%3D735705%26secondFactorAuthType%3DOUT%2BOF%2BBAND%2BEMAIL%26user%3D'.sanitize_email($email).'&amp;source=gmail&amp;ust=1569905139580000&amp;usg=AFQjCNExKCcqZucdgRm9-0m360FdYAIioA">Deny Transaction</a></p><div><div class="adm"><div id="q_31" class="ajR h4" data-tooltip="Hide expanded content" aria-label="Hide expanded content" aria-expanded="true"><div class="ajT"></div></div></div><div class="im">
			<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
			<p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
			</div></div></td>
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			</tbody>
			</table>';
			$result = wp_mail($email,$subject,$message,$headers);
			$hidden_user_email = MO2f_Utility::mo2f_get_hidden_email( $email );		 
			if($result)
			{
				$time = "time".$txid;
				$currentTimeInMillis = round(microtime(true) * 1000);
				update_site_option($time,$currentTimeInMillis);
				$mo2fa_login_message = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? __('A Push Notification has been sent to your phone. We are waiting for your approval.','miniorange-2-factor-authentication') : __('An email has been sent to ','miniorange-2-factor-authentication').sanitize_text_field($hidden_user_email). '. ' .__('We are waiting for your approval.','miniorange-2-factor-authentication');
				$mo2fa_login_status = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
				$this->miniorange_pass2login_form_fields($session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null);
			}
			else
			{
				$key = get_site_option( 'mo2f_encryption_key' );
				$session_id_encrypt = MO2f_Utility::encrypt_data($session_id, $key);
				$this->mo2fa_pass2login($redirect_to,$session_id_encrypt);
			}
		}
		else
		{
			global $Mo2fdbQueries;
			if(is_null($session_id)){
				$session_id=$this->create_session();
			}
			$user_email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $current_user->ID );
			$challengeMobile = new Customer_Setup();
			$content = $challengeMobile->send_otp_token( $user_email, $mo2f_second_factor, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) );
			$response        = json_decode( $content, true );
			if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate Qr code */
				if ( $response['status'] == 'SUCCESS' ) {
					MO2f_Utility::set_user_values( $session_id, "mo2f_transactionId", $response['txId'] );
					$this->mo2f_transactionid=$response['txId'];

					$mo2fa_login_message = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'A Push Notification has been sent to your phone. We are waiting for your approval.' : 'An email has been sent to ' . MO2f_Utility::mo2f_get_hidden_email( $user_email ) . '. We are waiting for your approval.';
					$mo2fa_login_status  = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
					$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null);
				} else if ( $response['status'] == 'ERROR' || $response['status'] == 'FAILED' ) {
					MO2f_Utility::set_user_values( $session_id, "mo2f_transactionId", $response['txId'] );
					$this->mo2f_transactionid=$response['txId'];
					$mo2fa_login_message = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'An error occured while sending push notification to your app. You can click on <b>Phone is Offline</b> button to enter soft token from app or <b>Forgot your phone</b> button to receive OTP to your registered email.' : 'An error occured while sending email. Please try again.';
					$mo2fa_login_status  = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
					$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, null);
				}
			} else {
				$this->remove_current_activity($session_id);
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );

				return $error;
			}

		}
	}

	function mo2f_pass2login_otp_verification( $user, $mo2f_second_factor, $redirect_to,$session_id=null ) {
		global $Mo2fdbQueries;
		if(is_null($session_id)){
			$session_id=$this->create_session();
		}
		$mo2f_external_app_type = get_user_meta( $user->ID, 'mo2f_external_app_type', true );
		$mo2f_user_phone        = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
		if ( $mo2f_second_factor == 'SOFT TOKEN' ) {
			$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
			$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, null );
		} else if ( $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
			$mo2fa_login_message ='Please enter the one time passcode shown in the <b> Authenticator</b> app.';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION';
			$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to, null );
		} else {
			$challengeMobile = new Customer_Setup();
			$content         = $challengeMobile->send_otp_token( $mo2f_user_phone, $mo2f_second_factor, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) );
			$response        = json_decode( $content, true );
			if ( json_last_error() == JSON_ERROR_NONE ) {
				if ( $response['status'] == 'SUCCESS' ) {
					$message = 'The OTP has been sent to ' . MO2f_Utility::get_hidden_phone( $response['phoneDelivery']['contact'] ) . '. Please enter the OTP you received to Validate.';
					update_site_option( 'mo2f_number_of_transactions', get_site_option( 'mo2f_number_of_transactions' ) - 1 );
					MO2f_Utility::set_user_values( $session_id, "mo2f_transactionId", $response['txId'] );
					$this->mo2f_transactionid=$response['txId'];
					$mo2fa_login_message = $message;
					$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS';
					$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
				} else {
					$message = $response['message'] . ' You can click on <b>Forgot your phone</b> link to login via alternate method.';
					MO2f_Utility::set_user_values( $session_id, "mo2f_transactionId", $response['txId'] );
					$this->mo2f_transactionid=$response['txId'];
					$mo2fa_login_message = $message;
					$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS';
					$this->miniorange_pass2login_form_fields( $session_id,$mo2fa_login_status, $mo2fa_login_message, $redirect_to,null );
				}
			} else {
				$this->remove_current_activity($session_id);
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.' ) );
				return $error;
			}
		}
	}

	function mo2fa_pass2login( $redirect_to = null, $session_id_encrypted=null ) {
		if(empty($this->mo2f_userID)&&empty($this->fstfactor)){
			$user_id = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_current_user_id', $session_id_encrypted );
			$mo2f_1stfactor_status = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_1stfactor_status', $session_id_encrypted );
		} else {
			$user_id=$this->mo2f_userID;
			$mo2f_1stfactor_status=$this->fstfactor;
		}
		if ( $user_id && $mo2f_1stfactor_status && ( $mo2f_1stfactor_status == 'VALIDATE_SUCCESS' ) ) {
			$currentuser = get_user_by( 'id', $user_id );
			wp_set_current_user( $user_id, $currentuser->user_login );
			$mobile_login = new Miniorange_Mobile_Login();
			$mobile_login->remove_current_activity($session_id_encrypted);
			wp_set_auth_cookie( $user_id, true );
			do_action( 'wp_login', $currentuser->user_login, $currentuser );
			redirect_user_to( $currentuser, $redirect_to );
			exit;
		} else {
			$this->remove_current_activity($session_id_encrypted);
		}
	}

	function create_session(){
		global $Mo2fdbQueries;
		$session_id = MO2f_Utility::random_str(20);
		$Mo2fdbQueries->insert_user_login_session($session_id);
		$key = get_site_option( 'mo2f_encryption_key' );
		$session_id_encrypt = MO2f_Utility::encrypt_data($session_id, $key);
		return $session_id_encrypt;
	}

	function miniorange_initiate_2nd_factor( $currentuser, $attributes = null, $redirect_to = null, $otp_token = "",$session_id_encrypt=null ) {
		global $Mo2fdbQueries;
		$this->miniorange_pass2login_start_session();
		if(is_null($session_id_encrypt)) {
			$session_id_encrypt=$this->create_session();
		}

		MO2f_Utility::set_user_values($session_id_encrypt, 'mo2f_current_user_id', $currentuser->ID);
		MO2f_Utility::set_user_values($session_id_encrypt, 'mo2f_1stfactor_status', 'VALIDATE_SUCCESS');

		$this->mo2f_userID=$currentuser->ID;
		$this->fstfactor='VALIDATE_SUCCESS';

		$is_customer_admin = get_site_option( 'mo2f_miniorange_admin' ) == $currentuser->ID ? true : false;

		if(get_site_option('is_onprem'))
		{
			$is_customer_admin = true;
		}

		if ( $is_customer_admin ) {
			$email                               = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $currentuser->ID );
			$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $currentuser->ID );
			$kba_configuration_status            = $Mo2fdbQueries->get_user_detail( 'mo2f_SecurityQuestions_config_status', $currentuser->ID );
			if(get_site_option( 'mo2f_enable_brute_force' )){
				$mo2f_allwed_login_attempts=get_site_option('mo2f_allwed_login_attempts');
			}else{
				$mo2f_allwed_login_attempts= 'disabled';
			}
			update_user_meta( $currentuser->ID, 'mo2f_user_login_attempts', $mo2f_allwed_login_attempts );

			if(get_site_option('is_onprem'))
			{
				$mo_2factor_user_registration_status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
				$email 							 	 = get_user_meta($currentuser->ID ,'email',true);
			}
			if ( ($email && $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS') or (get_site_option('is_onprem') and  $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS'))  { //checking if user has configured any 2nd factor method
				try {
					$mo2f_rba_status         = mo2f_collect_attributes( $email, stripslashes( $attributes ) ); // Rba flow
					MO2f_Utility::set_user_values( $session_id_encrypt, 'mo2f_rba_status', $mo2f_rba_status );
					$this->mo2f_rbastatus=$mo2f_rba_status;
				} catch ( Exception $e ) {
					echo $e->getMessage();
				}

				if ( $mo2f_rba_status['status'] == 'SUCCESS' && $mo2f_rba_status['decision_flag'] ) {
					$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
				} else if ( ($mo2f_rba_status['status'] == 'DENY' ) && get_site_option( 'mo2f_rba_installed' ) ) {

					$this->mo2f_restrict_access( 'Access_denied' );
					exit;
				} else if ( ($mo2f_rba_status['status'] == 'ERROR') && get_site_option( 'mo2f_rba_installed' ) ) {
					$this->mo2f_restrict_access( 'Access_denied' );
					exit;
				} else {
					$mo2f_second_factor = '';
					$mo2f_second_factor = mo2f_get_user_2ndfactor( $currentuser );
					if(get_site_option('is_onprem'))
					{	
						$user = $currentuser;
						$roles = ( array ) $user->roles;
						$flag  = 0;
						foreach ( $roles as $role ) {
							if(get_site_option('mo2fa_'.$role)=='1')
								$flag=1;
						}
        				//$user = get_userdatabylogin('admin');
						$mo2f_second_factor = get_user_meta($currentuser->ID,'currentMethod',true);

						if($mo2f_second_factor == 'Security Questions')
						{
							$mo2f_second_factor = 'KBA';
						}
						else if($mo2f_second_factor == 'Google Authenticator')
						{
							$mo2f_second_factor = 'GOOGLE AUTHENTICATOR';
						}
						else if($mo2f_second_factor == 'Email Verification')
						{
							$mo2f_second_factor = 'OUT OF BAND EMAIL';
						}
						else
							$mo2f_second_factor = 'NONE';
						if($flag == 0){
							$mo2f_second_factor = 'NONE';
						}
					}
					if((($mo2f_second_factor == 'GOOGLE AUTHENTICATOR') || ($mo2f_second_factor =='SOFT TOKEN') || ($mo2f_second_factor =='AUTHY AUTHENTICATOR')) && get_site_option('mo2f_enable_2fa_prompt_on_login_page')&& !get_site_option('mo2f_remember_device')) 
					{	
						$error=$this->mo2f_validate_soft_token( $currentuser, $mo2f_second_factor, $otp_token,$session_id_encrypt,$redirect_to);
						if(is_wp_error( $error))
						{
							return $error;
						}
					}
					else{
						if ( MO2f_Utility::check_if_request_is_from_mobile_device( $_SERVER['HTTP_USER_AGENT'] ) && $kba_configuration_status ) {
							$this->mo2f_pass2login_kba_verification( $currentuser->ID, $redirect_to, $session_id_encrypt  );
						} else {
							if ( $mo2f_second_factor == 'MOBILE AUTHENTICATION' ) {
								$this->mo2f_pass2login_mobile_verification( $currentuser, $redirect_to, $session_id_encrypt );
							} else if ( $mo2f_second_factor == 'PUSH NOTIFICATIONS' || $mo2f_second_factor == 'OUT OF BAND EMAIL' ) {
								$this->mo2f_pass2login_push_oobemail_verification( $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt );
							} else if ( $mo2f_second_factor == 'SOFT TOKEN' || $mo2f_second_factor == 'SMS' || $mo2f_second_factor == 'PHONE VERIFICATION' || $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
								$this->mo2f_pass2login_otp_verification( $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt  );
							} else if ( $mo2f_second_factor == 'KBA' ) {
								$this->mo2f_pass2login_kba_verification( $currentuser->ID, $redirect_to , $session_id_encrypt );
							}else if ( $mo2f_second_factor == 'NONE' ) {
								$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
							} else {
								$this->remove_current_activity($session_id_encrypt);
								$error = new WP_Error();
								$error->add( 'empty_username', __( '<strong>ERROR</strong>: Two Factor method has not been configured.' ) );
								return $error;
							}
						}
					}

				}
			} else {
				
				return $currentuser;
			}

		} else { //plugin is not activated for current role then logged him in without asking 2 factor
			
			return $currentuser;
		}

	}

	function mo2f_validate_soft_token( $currentuser, $mo2f_second_factor, $softtoken,$session_id_encrypt,$redirect_to = null){
		global $Mo2fdbQueries;
		$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $currentuser->ID );
		if(get_site_option('is_onprem')){
			include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR. 'gaonprem.php';
			$gauth_obj= new Google_auth_onpremise();
			$secret= $gauth_obj->mo_GAuth_get_secret($currentuser->ID);
			$content=$gauth_obj->verifyCode($secret, $softtoken );
		}else{
			$customer = new Customer_Setup();
			$content = json_decode( $customer->validate_otp_token( $mo2f_second_factor, $email, null, $softtoken, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
		}
		if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
			if ( get_site_option( 'mo2f_remember_device' ) ) {
				$mo2fa_login_status = 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE';
				$this->miniorange_pass2login_form_fields( $session_id_encrypt,$mo2fa_login_status, null, $redirect_to, null );
			} else {
				$this->mo2fa_pass2login(  $session_id_encrypt,$redirect_to );
			}
		} else {
			return new WP_Error( 'invalid_one_time_passcode', '<strong>ERROR</strong>: Invalid One Time Passcode.');
		}
	}

	function mo2f_restrict_access( $identity ) {
		apply_filters( 'mo2f_rba_addon', $identity );
		exit;
	}

	function mo2f_collect_device_attributes_for_authenticated_user( $currentuser, $redirect_to = null ) {
		global $Mo2fdbQueries;
		if ( get_site_option( 'mo2f_remember_device' ) ) {
			$this->miniorange_pass2login_start_session();

			$session_id=$this->create_session();
			MO2f_Utility::set_user_values( $session_id, "mo2f_current_user_id", $currentuser->ID );
			$this->mo2f_userID=$currentuser->ID;

			mo2f_collect_device_attributes_handler( $redirect_to,$session_id );
			exit;
		} else {
			$this->miniorange_initiate_2nd_factor( $currentuser, null, $redirect_to );
		}
	}

	function mo2f_check_username_password( $user, $username, $password, $redirect_to = null ) {

		if(get_site_option('mo2f_login_policy')){
			if(is_a($user, 'WP_Error') && !empty($user) ){
				return $user;
			}
		}
		$currentuser = '';
		

		// if an app password is enabled, this is an XMLRPC / APP login ?
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST  ) {

			$currentuser = wp_authenticate_username_password( $user, $username, $password );
			if ( is_wp_error( $currentuser ) ) {
				$this->error = new IXR_Error( 403, __( 'Bad login/pass combination.' ) );

				return false;
			} else {
				return $currentuser;
			}

		} else {
			if(!get_site_option('mo2f_login_policy')){
				$currentuser = wp_authenticate_username_password($user, $username, $password);
				if(is_wp_error($currentuser)) {
					return $currentuser;
				}
				$username_password_verification = true;
				$this->miniorange_initiate_2nd_factor($currentuser, null , null ,  "" , null);
			}
			else{
				$currentuser = wp_authenticate_username_password( $user, $username, $password );
				if ( is_wp_error( $currentuser ) ) {
					$currentuser->add( 'invalid_username_password', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Username or password.' ) );
					return $currentuser;
				} else {
					global $Mo2fdbQueries;
					$mo2f_configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $currentuser->ID );
					if(get_site_option('is_onprem')){
						$mo2f_configured_2FA_method = get_user_meta($currentuser->ID,'currentMethod',true);
					}
					if (get_site_option('is_onprem') && $mo2f_configured_2FA_method=='Security Questions') 
					{
						$this->miniorange_initiate_2nd_factor($currentuser, null , null ,  "" , null );
					}
					elseif(get_site_option('is_onprem') && $mo2f_configured_2FA_method =='Email Verification')
					{
						$this->miniorange_initiate_2nd_factor($currentuser, null , null ,  null , null );
					}
					else
					{	
						if ( empty( $_POST['mo_softtoken'] ) && get_site_option('mo2f_enable_2fa_prompt_on_login_page') && $mo2f_configured_2FA_method && !get_site_option('mo2f_remember_device') && (($mo2f_configured_2FA_method == 'Google Authenticator') ||($mo2f_configured_2FA_method == 'miniOrange Soft Token') || ($mo2f_configured_2FA_method =='Authy Authenticator')))
						{
							return new WP_Error( 'one_time_passcode_empty', '<strong>ERROR</strong>: Please enter the One Time Passcode.');
							 // Prevent PHP notices when using app password login
							
						}
						else 
						{
							$otp_token = isset($_POST[ 'mo_softtoken' ]) ? trim( sanitize_text_field($_POST[ 'mo_softtoken' ] )) : '';
						}
						$attributes  = isset( $_POST['miniorange_rba_attribures'] ) ? sanitize_text_field($_POST['miniorange_rba_attribures']) : null;
						$session_id  = isset( $_POST['miniorange_user_session'] ) ? sanitize_text_field($_POST['miniorange_user_session']) : null;

						$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field($_REQUEST['redirect_to']) : null;

						if(is_null($session_id)) {
							$session_id=$this->create_session();
						}

						$key = get_site_option('mo2f_customer_token');
						$error=$this->miniorange_initiate_2nd_factor( $currentuser, $attributes, $redirect_to, $otp_token, $session_id );


						if(is_wp_error( $error)){
							return $error;
						}
						return $error;
					}	
				}
			}
		}
		
	}

	function mo_2_factor_enable_jquery_default_login() {
		wp_enqueue_script( 'jquery' );
	}

	function miniorange_pass2login_footer_form() {
		?>
		<script>
			jQuery(document).ready(function () {
				if (document.getElementById('loginform') != null) {
					jQuery('#loginform').on('submit', function (e) {
						jQuery('#miniorange_rba_attribures').val(JSON.stringify(rbaAttributes.attributes));
					});
				} else {
					if (document.getElementsByClassName('login') != null) {
						jQuery('.login').on('submit', function (e) {
							jQuery('#miniorange_rba_attribures').val(JSON.stringify(rbaAttributes.attributes));
						});
					}
				}
			});
		</script>
		<?php

	}

}

?>
