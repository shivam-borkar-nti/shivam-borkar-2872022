<?php

include 'two_fa_pass2login.php';
include dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'twofa'.DIRECTORY_SEPARATOR.'two_fa_setup_notification.php';
include dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'class_miniorange_2fa_strong_password.php';
class Miniorange_Authentication {

	private $defaultCustomerKey = "16555";
	private $defaultApiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

	function __construct() {
		add_action( 'admin_init', array( $this, 'miniorange_auth_save_settings' ) );
		add_action( 'plugins_loaded', array( $this, 'mo2f_update_db_check' ) );

		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		if (get_site_option('mo2f_activate_plugin') == 1) {
			
			$mo2f_rba_attributes = new Miniorange_Rba_Attributes();
			$pass2fa_login       = new Miniorange_Password_2Factor_Login();
			$mo2f_2factor_setup  = new Two_Factor_Setup();
			add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect' ) );
			//for shortcode addon
			$mo2f_ns_config = new MoWpnsUtility();
			$mo2f_strong_password = new class_miniorange_2fa_strong_password();

			if($mo2f_ns_config->hasLoginCookie())
			{
				add_action('user_profile_update_errors', array( $mo2f_strong_password, 'validatePassword'), 0, 3 );
				add_action( 'woocommerce_save_account_details_errors', array( $mo2f_strong_password, 'woocommerce_password_edit_account' ),1,2 );
			}
			add_filter( 'woocommerce_process_registration_errors', array($mo2f_strong_password,'woocommerce_password_protection'),1,4);
			add_filter( 'woocommerce_registration_errors', array($mo2f_strong_password,'woocommerce_password_registration_protection'),1,3);
			
			add_filter( 'mo2f_shortcode_rba_gauth', array( $mo2f_rba_attributes, 'mo2f_validate_google_auth' ), 10, 3 );
			add_filter( 'mo2f_shortcode_kba', array( $mo2f_2factor_setup, 'register_kba_details' ), 10, 7 );
			add_filter( 'mo2f_update_info', array( $mo2f_2factor_setup, 'mo2f_update_userinfo' ), 10, 5 );
			add_action( 'mo2f_shortcode_form_fields', array(
				$pass2fa_login,
				'miniorange_pass2login_form_fields'
			), 10, 5 );
			add_filter( 'mo2f_gauth_service', array( $mo2f_rba_attributes, 'mo2f_google_auth_service' ), 10, 1 );
			if ( get_site_option( 'mo2f_login_policy' ) ) { //password + 2nd factor enabled
				if ( get_site_option( 'mo_2factor_admin_registration_status' ) == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' or get_site_option('is_onprem') ) {
					remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );

					add_filter( 'authenticate', array( $pass2fa_login, 'mo2f_check_username_password' ), 99999, 4 );
					add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect' ) );
					add_action( 'login_form', array(
						$pass2fa_login,
						'mo_2_factor_pass2login_show_wp_login_form'
					), 10 );

					if ( get_site_option( 'mo2f_remember_device' ) ) {
						add_action( 'login_footer', array( $pass2fa_login, 'miniorange_pass2login_footer_form' ) );
						add_action( 'woocommerce_before_customer_login_form', array(
							$pass2fa_login,
							'miniorange_pass2login_footer_form'
						) );
					}
					add_action( 'login_enqueue_scripts', array(
						$pass2fa_login,
						'mo_2_factor_enable_jquery_default_login'
					) );

					add_action( 'woocommerce_login_form_end', array(
						$pass2fa_login,
						'mo_2_factor_pass2login_show_wp_login_form'
					) );
					add_action( 'wp_enqueue_scripts', array(
						$pass2fa_login,
						'mo_2_factor_enable_jquery_default_login'
					) );

					//Actions for other plugins to use miniOrange 2FA plugin
					add_action( 'miniorange_pre_authenticate_user_login', array(
						$pass2fa_login,
						'mo2f_check_username_password'
					), 1, 4 );
					add_action( 'miniorange_post_authenticate_user_login', array(
						$pass2fa_login,
						'miniorange_initiate_2nd_factor'
					), 1, 3 );
					add_action( 'miniorange_collect_attributes_for_authenticated_user', array(
						$pass2fa_login,
						'mo2f_collect_device_attributes_for_authenticated_user'
					), 1, 2 );

				}

			} else { //login with phone enabled
				if ( get_site_option( 'mo_2factor_admin_registration_status' ) == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' or get_site_option('is_onprem')) {

					$mobile_login = new Miniorange_Mobile_Login();
					add_action( 'login_form', array( $mobile_login, 'miniorange_login_form_fields' ), 99999,10 );
					add_action( 'login_footer', array( $mobile_login, 'miniorange_login_footer_form' ) );

					remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
					add_filter( 'authenticate', array( $mobile_login, 'mo2fa_default_login' ), 99999, 3 );
					add_action( 'login_enqueue_scripts', array( $mobile_login, 'custom_login_enqueue_scripts' ) );
				}
			}
		}
	}

	function define_global() {
		global $Mo2fdbQueries;
		$Mo2fdbQueries = new Mo2fDB();
	}

	function mo2f_update_db_check() {

		update_site_option('mo_2f_switch_all',1);
		$userid = wp_get_current_user()->ID;
		add_site_option('mo2f_onprem_admin' ,  $userid );
		// Deciding on On-Premise solution
		$is_NC=get_site_option( 'mo2f_is_NC' );
		$is_NNC=get_site_option( 'mo2f_is_NNC' );
		// Old users 
		if ( get_site_option( 'mo2f_customerKey' ) && ! $is_NC ) 
			add_site_option( 'is_onprem', 0 );
		
		//new users using cloud  
		if(get_site_option( 'mo2f_customerKey' ) && $is_NC && $is_NNC)
			add_site_option( 'is_onprem', 0 ); 
		
		if(get_site_option( 'mo2f_app_secret' ) && $is_NC && $is_NNC){
			add_site_option( 'is_onprem', 0 ); 
		}else{
			add_site_option( 'is_onprem', 1 ); 

		}
		if(get_site_option('mo2f_network_features',"not_exits")=="not_exits"){
			do_action('mo2f_network_create_db');
			update_site_option('mo2f_network_features',1);
		}
		if(get_site_option('mo2f_encryption_key',"not_exits")=="not_exits"){
			$get_encryption_key = MO2f_Utility::random_str(16);
			update_site_option('mo2f_encryption_key',$get_encryption_key);

		}
		global $Mo2fdbQueries;
		$user_id = get_site_option( 'mo2f_miniorange_admin' );
		$current_db_version = get_site_option( 'mo2f_dbversion' );
		
		if ( $current_db_version < 143 ) {
			update_site_option( 'mo2f_dbversion', 143 );
			$Mo2fdbQueries->generate_tables();

		}
		if ( ! get_site_option( 'mo2f_existing_user_values_updated' ) ) {

			if ( get_site_option( 'mo2f_customerKey' ) && ! get_site_option( 'mo2f_is_NC' ) ) {
				update_site_option( 'mo2f_is_NC', 0 );
			}

			$check_if_user_column_exists = false;

			if ( $user_id && ! get_site_option( 'mo2f_is_NC' ) ) {
				$does_table_exist = $Mo2fdbQueries->check_if_table_exists();
				if ( $does_table_exist ) {
					$check_if_user_column_exists = $Mo2fdbQueries->check_if_user_column_exists( $user_id );
				}
				if ( ! $check_if_user_column_exists ) {
					$Mo2fdbQueries->generate_tables();
					$Mo2fdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );

					add_site_option( 'mo2f_phone', get_site_option( 'user_phone' ) );
					add_site_option( 'mo2f_enable_login_with_2nd_factor', get_site_option( 'mo2f_show_loginwith_phone' ) );
					add_site_option( 'mo2f_remember_device', get_site_option( 'mo2f_deviceid_enabled' ) );
					add_site_option( 'mo2f_transactionId', get_site_option( 'mo2f-login-transactionId' ) );
					add_site_option( 'mo2f_is_NC', 0 );
					$phone      = get_user_meta( $user_id, 'mo2f_user_phone', true );
					$user_phone = $phone ? $phone : get_user_meta( $user_id, 'mo2f_phone', true );

					$Mo2fdbQueries->update_user_details( $user_id,
						array(
							'mo2f_GoogleAuthenticator_config_status' => get_user_meta( $user_id, 'mo2f_google_authentication_status', true ),
							'mo2f_SecurityQuestions_config_status'   => get_user_meta( $user_id, 'mo2f_kba_registration_status', true ),
							'mo2f_EmailVerification_config_status'   => true,
							'mo2f_AuthyAuthenticator_config_status'  => get_user_meta( $user_id, 'mo2f_authy_authentication_status', true ),
							'mo2f_user_email'                        => get_user_meta( $user_id, 'mo_2factor_map_id_with_email', true ),
							'mo2f_user_phone'                        => $user_phone,
							'user_registration_with_miniorange'      => get_user_meta( $user_id, 'mo_2factor_user_registration_with_miniorange', true ),
							'mobile_registration_status'             => get_user_meta( $user_id, 'mo2f_mobile_registration_status', true ),
							'mo2f_configured_2FA_method'             => get_user_meta( $user_id, 'mo2f_selected_2factor_method', true ),
							'mo_2factor_user_registration_status'    => get_user_meta( $user_id, 'mo_2factor_user_registration_status', true )
						) );

					if ( get_user_meta( $user_id, 'mo2f_mobile_registration_status', true ) ) {
						$Mo2fdbQueries->update_user_details( $user_id,
							array(
								'mo2f_miniOrangeSoftToken_config_status'            => true,
								'mo2f_miniOrangeQRCodeAuthentication_config_status' => true,
								'mo2f_miniOrangePushNotification_config_status'     => true
							) );
					}

					if ( get_user_meta( $user_id, 'mo2f_otp_registration_status', true ) ) {
						$Mo2fdbQueries->update_user_details( $user_id,
							array(
								'mo2f_OTPOverSMS_config_status' => true
							) );
					}

					$mo2f_external_app_type = get_user_meta( $user_id, 'mo2f_external_app_type', true ) == 'AUTHY 2-FACTOR AUTHENTICATION' ?
					'Authy Authenticator' : 'Google Authenticator';

					update_user_meta( $user_id, 'mo2f_external_app_type', $mo2f_external_app_type );

					delete_site_option( 'mo2f_show_loginwith_phone' );
					delete_site_option( 'mo2f_remember_device' );
					delete_site_option( 'mo2f-login-transactionId' );
					delete_user_meta( $user_id, 'mo2f_google_authentication_status' );
					delete_user_meta( $user_id, 'mo2f_kba_registration_status' );
					delete_user_meta( $user_id, 'mo2f_email_verification_status' );
					delete_user_meta( $user_id, 'mo2f_authy_authentication_status' );
					delete_user_meta( $user_id, 'mo_2factor_map_id_with_email' );
					delete_user_meta( $user_id, 'mo_2factor_user_registration_with_miniorange' );
					delete_user_meta( $user_id, 'mo2f_mobile_registration_status' );
					delete_user_meta( $user_id, 'mo2f_otp_registration_status' );
					delete_user_meta( $user_id, 'mo2f_selected_2factor_method' );
					delete_user_meta( $user_id, 'mo2f_configure_test_option' );
					delete_user_meta( $user_id, 'mo_2factor_user_registration_status' );

					update_site_option( 'mo2f_existing_user_values_updated', 1 );

				}
			}
		}

		if ( $user_id && ! get_site_option( 'mo2f_login_option_updated' ) ) {

			$does_table_exist = $Mo2fdbQueries->check_if_table_exists();
			if ( $does_table_exist ) {
				$check_if_user_column_exists = $Mo2fdbQueries->check_if_user_column_exists( $user_id );
				if ( $check_if_user_column_exists ) {
					$selected_2FA_method        = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );
					update_site_option( 'mo2f_login_option_updated', 1 );
				}
			}
		}	
	}


	/**
	 * Function tells where to look for translations.
	 */
	function mo2fa_load_textdomain() {
		load_plugin_textdomain( 'miniorange-2-factor-authentication', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	function feedback_request() {
		display_feedback_form();
	}

	
	function mo_auth_success_message() {
		$message = get_site_option( 'mo2f_message' ); ?>
		<script>
			jQuery(document).ready(function () {
				var message = "<?php echo $message; ?>";
				jQuery('#messages').append("<div  style='padding:5px;'><div class='error notice is-dismissible mo2f_error_container' style='position: fixed;left: 60.4%;top: 6%;width: 37%;z-index: 99999;background-color: bisque;font-weight: bold;'> <p class='mo2f_msgs'>" + message + "</p></div></div>");
			});
		</script>
		<?php
	}

	function mo_auth_error_message() {
		$message = get_site_option( 'mo2f_message' ); ?>

		<script>
			jQuery(document).ready(function () {
				var message = "<?php echo $message; ?>";
				jQuery('#messages').append("<div  style='padding:5px;'><div class='updated notice is-dismissible mo2f_success_container' style='position: fixed;left: 60.4%;top: 6%;width: 37%;z-index: 9999;background-color: #bcffb4;font-weight: bold;'> <p class='mo2f_msgs'>" + message + "</p></div></div>");
			});
		</script>
		<?php

	}

	function miniorange_auth_save_settings() {

		if ( array_key_exists( 'page', $_REQUEST ) && $_REQUEST['page'] == 'mo_2fa_two_fa' ) {
			if ( ! session_id() || session_id() == '' || ! isset( $_SESSION ) ) {
				session_start();
			}
		}

		global $user;
		global $Mo2fdbQueries;
		$defaultCustomerKey = $this->defaultCustomerKey;
		$defaultApiKey      = $this->defaultApiKey;

		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( current_user_can( 'manage_options' ) ) {
			
			if(strlen(get_site_option('mo2f_encryption_key'))>17){
				$get_encryption_key = MO2f_Utility::random_str(16);
				update_site_option('mo2f_encryption_key',$get_encryption_key);
			}
			
			if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_deactivate_account" ) {
				$nonce = sanitize_text_field($_POST['mo_auth_deactivate_account_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo-auth-deactivate-account-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {
					$url = admin_url( 'plugins.php' );
					wp_redirect( $url );
				}
			}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_remove_account" ) {
				$nonce = sanitize_text_field($_POST['mo_auth_remove_account_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo-auth-remove-account-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					update_site_option( 'mo2f_register_with_another_email', 1 );
					$this->mo_auth_deactivate();
				}
			}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option'] )== "mo2f_save_proxy_settings" ) {
				$nonce = sanitize_text_field($_POST['mo2f_save_proxy_settings_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo2f-save-proxy-settings-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					$proxyHost     = sanitize_text_field($_POST['proxyHost']);
					$portNumber    = sanitize_text_field($_POST['portNumber']);
					$proxyUsername = sanitize_text_field($_POST['proxyUsername']);
					$proxyPassword = sanitize_text_field($_POST['proxyPass']);

					update_site_option( 'mo2f_proxy_host', $proxyHost );
					update_site_option( 'mo2f_port_number', $portNumber );
					update_site_option( 'mo2f_proxy_username', $proxyUsername );
					update_site_option( 'mo2f_proxy_password', $proxyPassword );
					update_site_option( 'mo2f_message', 'Proxy settings saved successfully.' );
					$this->mo_auth_show_success_message();
				}

			}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_register_customer" ) {    //register the admin to miniOrange
				//miniorange_register_customer_nonce
				$nonce = sanitize_text_field($_POST['miniorange_register_customer_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'miniorange-register-customer-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {
					//validate and sanitize
					$email           = '';
					$password        = '';
					$confirmPassword = '';
					$is_registration = get_user_meta( $user->ID, 'mo2f_email_otp_count', true );

					if ( MO2f_Utility::mo2f_check_empty_or_null(sanitize_email($_POST['email'] )) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['password'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['confirmPassword'] ) ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );

						return;
					} else if ( strlen( $_POST['password'] ) < 6 || strlen( $_POST['confirmPassword'] ) < 6 ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "MIN_PASS_LENGTH" ) );

					} else {
						$email           = sanitize_email( $_POST['email'] );
						$password        = sanitize_text_field( $_POST['password'] );
						$confirmPassword = sanitize_text_field( $_POST['confirmPassword'] );

						$email = strtolower( $email );
						
						$pattern = '/^[(\w)*(\!\@\#\$\%\^\&\*\.\-\_)*]+$/';

						if(preg_match($pattern,$password)){
							if ( strcmp( $password, $confirmPassword ) == 0 ) {
								update_site_option( 'mo2f_email', $email );

								$Mo2fdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );
								update_site_option( 'mo2f_password', stripslashes( $password ) );
								$customer    = new Customer_Setup();
								$customerKey = json_decode( $customer->check_customer(), true );

								if ( strcasecmp( $customerKey['status'], 'CUSTOMER_NOT_FOUND' ) == 0 ) {
									if ( $customerKey['status'] == 'ERROR' ) {
										update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $customerKey['message'] ) );
									} else {
										$this->mo2f_create_customer( $user );
										delete_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account' );
										delete_user_meta( $user->ID, 'register_account_popup' );
										if(get_user_meta( $user->ID, 'mo2f_2FA_method_to_configure'))
											update_user_meta( $user->ID, 'configure_2FA', 1 );

									}
								} else { //customer already exists, redirect him to login page

									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ACCOUNT_ALREADY_EXISTS" ) );
									$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );

								}

							} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "PASSWORDS_MISMATCH" ) );
								$this->mo_auth_show_error_message();
							}
						}
						else{
							update_site_option( 'mo2f_message', "Password length between 6 - 15 characters. Only following symbols (!@#.$%^&*-_) should be present." );
							$this->mo_auth_show_error_message();
						}
					}
				}
			}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo2f_goto_verifycustomer" ) {
				$nonce = sanitize_text_field($_POST['mo2f_goto_verifycustomer_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo2f-goto-verifycustomer-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					$Mo2fdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ENTER_YOUR_EMAIL_PASSWORD" ) );
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );
				}
			}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo_2factor_gobackto_registration_page' ) { //back to registration page for admin
				$nonce = sanitize_text_field($_POST['mo_2factor_gobackto_registration_page_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-gobackto-registration-page-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					delete_site_option( 'mo2f_email' );
					delete_site_option( 'mo2f_password' );
					update_site_option( 'mo2f_message', "" );

					MO2f_Utility::unset_session_variables( 'mo2f_transactionId' );
					delete_site_option( 'mo2f_transactionId' );
					delete_user_meta( $user->ID, 'mo2f_sms_otp_count' );
					delete_user_meta( $user->ID, 'mo2f_email_otp_count' );
					delete_user_meta( $user->ID, 'mo2f_email_otp_count' );
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'REGISTRATION_STARTED' ) );
				}

			}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_registration_closed' ) {
				$nonce = sanitize_text_field($_POST['mo2f_registration_closed_nonce']);
				if ( ! wp_verify_nonce( $nonce, 'mo2f-registration-closed-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
				
					delete_user_meta( $user->ID, 'register_account_popup' );
				}
			}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_verify_customer" ) {    //register the admin to miniOrange if already exist

				$nonce = sanitize_text_field($_POST['miniorange_verify_customer_nonce']);

				if ( ! wp_verify_nonce( $nonce, 'miniorange-verify-customer-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {

					//validation and sanitization
					$email    = '';
					$password = '';
					$Mo2fdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );


					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['email'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['password'] ) ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
						$this->mo_auth_show_error_message();

						return;
					} else {
						$email    = sanitize_email( $_POST['email'] );
						$password = sanitize_text_field( $_POST['password'] );
					}

					update_site_option( 'mo2f_email', $email );
					update_site_option( 'mo2f_password', stripslashes( $password ) );
					$customer    = new Customer_Setup();
					$content     = $customer->get_customer_key();
					$customerKey = json_decode( $content, true );

					if ( json_last_error() == JSON_ERROR_NONE ) {
						if ( is_array( $customerKey ) && array_key_exists( "status", $customerKey ) && $customerKey['status'] == 'ERROR' ) {
							update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( $customerKey['message'] ) );
							$this->mo_auth_show_error_message();
						} else if ( is_array( $customerKey ) ) {
							if ( isset( $customerKey['id'] ) && ! empty( $customerKey['id'] ) ) {
								update_site_option( 'mo2f_customerKey', $customerKey['id'] );
								update_site_option( 'mo2f_api_key', $customerKey['apiKey'] );
								update_site_option( 'mo2f_customer_token', $customerKey['token'] );
								update_site_option( 'mo2f_app_secret', $customerKey['appSecret'] );
								$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo2f_user_phone' => $customerKey['phone'] ) );
								update_site_option( 'mo2f_miniorange_admin', $user->ID );

								$mo2f_emailVerification_config_status = get_site_option( 'mo2f_is_NC' ) == 0 ? true : false;

								delete_site_option( 'mo2f_password' );
								update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );

								$Mo2fdbQueries->update_user_details( $user->ID, array(
									'mo2f_EmailVerification_config_status' => $mo2f_emailVerification_config_status,
									'mo2f_user_email'                      => get_site_option( 'mo2f_email' ),
									'user_registration_with_miniorange'    => 'SUCCESS',
									'mo2f_2factor_enable_2fa_byusers'      => 1,
								) );
								$mo_2factor_user_registration_status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
								$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
								$configured_2FA_method = 'NONE';
								$user_email            = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
								$enduser               = new Two_Factor_Setup();
								$userinfo              = json_decode( $enduser->mo2f_get_userinfo( $user_email ), true );

								$mo2f_second_factor = 'NONE';
								if ( json_last_error() == JSON_ERROR_NONE ) {
									if ( $userinfo['status'] == 'SUCCESS' ) {
										$mo2f_second_factor = mo2f_update_and_sync_user_two_factor( $user->ID, $userinfo );

									}
								}
								if ( $mo2f_second_factor != 'NONE' ) {
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
											$enduser->mo2f_update_userinfo( $user_email, 'NONE', null, '', true );
										}
									}


								}

								$mo2f_message = Mo2fConstants:: langTranslate( "ACCOUNT_RETRIEVED_SUCCESSFULLY" );
								if ( $configured_2FA_method != 'NONE' && get_site_option( 'mo2f_is_NC' ) == 0 ) {
									$mo2f_message .= ' <b>' . $configured_2FA_method . '</b> ' . Mo2fConstants:: langTranslate( "DEFAULT_2ND_FACTOR" ) . '.';
								}
								$mo2f_message .= ' ' . '<a href=\"admin.php?page=mo_2fa_two_fa\" >' . Mo2fConstants:: langTranslate( "CLICK_HERE" ) . '</a> ' . Mo2fConstants:: langTranslate( "CONFIGURE_2FA" );

								delete_user_meta( $user->ID, 'register_account_popup' );

								$mo2f_customer_selected_plan = get_site_option( 'mo2f_customer_selected_plan' );
								if ( ! empty( $mo2f_customer_selected_plan ) ) {
									delete_site_option( 'mo2f_customer_selected_plan' );
									header( 'Location: admin.php?page=mo_2fa_upgrade' );
								} else if ( $mo2f_second_factor == 'NONE' ) {
									update_user_meta( $user->ID, 'configure_2FA', 1 );
								}

								update_site_option( 'mo2f_message', $mo2f_message );
								$this->mo_auth_show_success_message();
							} else {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_EMAIL_OR_PASSWORD" ) );
								$mo_2factor_user_registration_status = 'MO_2_FACTOR_VERIFY_CUSTOMER';
								$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
								$this->mo_auth_show_error_message();
							}

						}
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_EMAIL_OR_PASSWORD" ) );
						$mo_2factor_user_registration_status = 'MO_2_FACTOR_VERIFY_CUSTOMER';
						$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
						$this->mo_auth_show_error_message();
					}

					delete_site_option( 'mo2f_password' );
				}
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == 'mo_2factor_phone_verification' ) { //at registration time
				$phone = sanitize_text_field( $_POST['phone_number'] );
				$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo2f_user_phone' => $phone ) );

				$phone     = str_replace( ' ', '', $phone );
				$auth_type = 'SMS';
				$customer  = new Customer_Setup();

				$send_otp_response = json_decode( $customer->send_otp_token( $phone, $auth_type, $defaultCustomerKey, $defaultApiKey ), true );

				if ( strcasecmp( $send_otp_response['status'], 'SUCCESS' ) == 0 ) {
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
					update_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', $send_otp_response['txId'] );

					if ( get_user_meta( $user->ID, 'mo2f_sms_otp_count', true ) ) {
						update_site_option( 'mo2f_message', 'Another One Time Passcode has been sent <b>( ' . get_user_meta( $user->ID, 'mo2f_sms_otp_count', true ) . ' )</b> for verification to ' . $phone );
						update_user_meta( $user->ID, 'mo2f_sms_otp_count', get_user_meta( $user->ID, 'mo2f_sms_otp_count', true ) + 1 );
					} else {
						update_site_option( 'mo2f_message', 'One Time Passcode has been sent for verification to ' . $phone );
						update_user_meta( $user->ID, 'mo2f_sms_otp_count', 1 );
					}

					$this->mo_auth_show_success_message();
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SENDING_SMS" ) );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
					$this->mo_auth_show_error_message();
				}

			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == "mo_2factor_resend_otp" ) { //resend OTP over email for admin

				$nonce = $_POST['mo_2factor_resend_otp_nonce'];

				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-resend-otp-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {
					$customer = new Customer_Setup();
					$content  = json_decode( $customer->send_otp_token( get_site_option( 'mo2f_email' ), 'EMAIL', $defaultCustomerKey, $defaultApiKey ), true );
					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
						if ( get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) ) {
							update_user_meta( $user->ID, 'mo2f_email_otp_count', get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) + 1 );
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "RESENT_OTP" ) . ' <b>( ' . get_user_meta( $user->ID, 'mo2f_email_otp_count', true ) . ' )</b> to <b>' . ( get_site_option( 'mo2f_email' ) ) . '</b> ' . Mo2fConstants:: langTranslate( "ENTER_OTP" ) );
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "OTP_SENT" ) . '<b> ' . ( get_site_option( 'mo2f_email' ) ) . ' </b>' . Mo2fConstants:: langTranslate( "ENTER_OTP" ) );
							update_user_meta( $user->ID, 'mo2f_email_otp_count', 1 );
						}
						$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS';
						$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
						update_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', $content['txId'] );
						$this->mo_auth_show_success_message();
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_EMAIL" ) );
						$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
						$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
						$this->mo_auth_show_error_message();
					}
				}


			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == "mo2f_dismiss_notice_option" ) {
				update_site_option( 'mo2f_bug_fix_done', 1 );
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == "mo_2factor_validate_otp" ) { //validate OTP over email for admin

				$nonce = $_POST['mo_2factor_validate_otp_nonce'];

				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-validate-otp-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {
				//validation and sanitization
					$otp_token = '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
						$this->mo_auth_show_error_message();

						return;
					} else {
						$otp_token = sanitize_text_field( $_POST['otp_token'] );
					}

					$customer = new Customer_Setup();

					$transactionId = get_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', true );

					$content = json_decode( $customer->validate_otp_token( 'EMAIL', null, $transactionId, $otp_token, $defaultCustomerKey, $defaultApiKey ), true );

					if ( $content['status'] == 'ERROR' ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $content['message'] ) );

					} else {

						if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //OTP validated
							$this->mo2f_create_customer( $user );
							delete_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account' );
							delete_user_meta( $user->ID, 'register_account_popup' );
							update_user_meta( $user->ID, 'configure_2FA', 1 );
						} else {  // OTP Validation failed.
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_OTP_DELIVERED_FAILURE' ) );

						}
					}
				}
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == "mo_2factor_validate_user_otp" ) { //validate OTP over email for additional admin

				//validation and sanitization
				$nonce = $_POST['mo_2factor_validate_user_otp_nonce'];

				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-validate-user-otp-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {
					$otp_token = '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
						update_site_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.' );
						$this->mo_auth_show_error_message();

						return;
					} else {
						$otp_token = sanitize_text_field( $_POST['otp_token'] );
					}

					$user_email = get_user_meta( $user->ID, 'user_email', true );
					$customer           = new Customer_Setup();
					$mo2f_transactionId = isset( $_SESSION['mo2f_transactionId'] ) && ! empty( $_SESSION['mo2f_transactionId'] ) ? $_SESSION['mo2f_transactionId'] : get_site_option( 'mo2f_transactionId' );

					$content = json_decode( $customer->validate_otp_token( 'EMAIL', '', $mo2f_transactionId, $otp_token, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

					if ( $content['status'] == 'ERROR' ) {
						update_site_option( 'mo2f_message', $content['message'] );
						$this->mo_auth_show_error_message();
					} else {
						if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //OTP validated and generate QRCode
							$this->mo2f_create_user( $user, $user_email );
							delete_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account' );
						} else {
							update_site_option( 'mo2f_message', 'Invalid OTP. Please try again.' );
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_OTP_DELIVERED_FAILURE' ) );
							$this->mo_auth_show_error_message();
						}
					}
				}
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == "mo_2factor_send_query" ) { //Help me or support
				$nonce = $_POST['mo_2factor_send_query_nonce'];

				if ( ! wp_verify_nonce( $nonce, 'mo-2factor-send-query-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

					return $error;
				} else {

					$query = '';
					if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['EMAIL_MANDATORY'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['query'] ) ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "EMAIL_MANDATORY" ) );
						$this->mo_auth_show_error_message();

						return;
					} else {
						$query      = sanitize_text_field( $_POST['query'] );
						$email      = sanitize_text_field( $_POST['EMAIL_MANDATORY'] );
						$phone      = sanitize_text_field( $_POST['query_phone'] );
						$contact_us = new Customer_Setup();
						$submited   = json_decode( $contact_us->submit_contact_us( $email, $phone, $query ), true );
						if ( json_last_error() == JSON_ERROR_NONE ) {
							if ( is_array( $submited ) && array_key_exists( 'status', $submited ) && $submited['status'] == 'ERROR' ) {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $submited['message'] ) );
								$this->mo_auth_show_error_message();
							} else {
								if ( $submited == false ) {
									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SUBMITTING_QUERY" ) );
									$this->mo_auth_show_error_message();
								} else {
									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "QUERY_SUBMITTED_SUCCESSFULLY" ) );
									$this->mo_auth_show_success_message();
								}
							}
						}

					}
				}
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == 'mo_auth_advanced_options_save' ) {
				update_site_option( 'mo2f_message', 'Your settings are saved successfully.' );
				$this->mo_auth_show_success_message();
			}else if  ( isset( $_POST['option'] ) and $_POST['option'] == 'mo_auth_pwdlogin_settings_save' ) {
				
				$nonce = $_POST['mo_auth_pwdlogin_settings_save_nonce'];
				if ( ! wp_verify_nonce( $nonce, 'mo-auth-pwdlogin-settings-save-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
					return $error;
				} else {
					$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
					if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' or get_site_option('is_onprem') ) 
					{
						update_site_option( 'mo2f_remember_device', isset( $_POST['mo2f_remember_device'] ) ? $_POST['mo2f_remember_device'] : 0 );
						if ( get_site_option( 'mo2f_login_policy' ) == 0 ) {
							update_site_option( 'mo2f_remember_device', 0 );
						}
						if(isset($_POST['mo2f_enable_login_with_2nd_factor']))
						{
							update_site_option('mo2f_login_policy',1);
						}
						update_site_option( 'mo2f_enable_forgotphone', isset( $_POST['mo2f_forgotphone'] ) ? $_POST['mo2f_forgotphone'] : 0 );
						update_site_option( 'mo2f_enable_login_with_2nd_factor', isset( $_POST['mo2f_login_with_username_and_2factor'] ) ? $_POST['mo2f_login_with_username_and_2factor'] : 0 );
						update_site_option( 'mo2f_enable_xmlrpc', isset( $_POST['mo2f_enable_xmlrpc'] ) ? $_POST['mo2f_enable_xmlrpc'] : 0 );
						if ( get_site_option( 'mo2f_remember_device' ) && ! get_site_option( 'mo2f_app_secret' ) ) {
							$get_app_secret = new Miniorange_Rba_Attributes();
							$rba_response   = json_decode( $get_app_secret->mo2f_get_app_secret(), true ); //fetch app secret
							if ( json_last_error() == JSON_ERROR_NONE ) {
								if ( $rba_response['status'] == 'SUCCESS' ) {
									update_site_option( 'mo2f_app_secret', $rba_response['appSecret'] );
								} else {
									update_site_option( 'mo2f_remember_device', 0 );
									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SAVING_SETTINGS" ) );
									$this->mo_auth_show_error_message();
								}
							} else {
								update_site_option( 'mo2f_remember_device', 0 );
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SAVING_SETTINGS" ) );
								$this->mo_auth_show_error_message();
							}
						}

						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "SETTINGS_SAVED" ) );
						$this->mo_auth_show_success_message();

						if (isset($_POST['mo2f_loginwith_phone']) && $_POST['mo2f_loginwith_phone']) {
							if(get_site_option('mo2f_login_policy','1'))
							{
								update_site_option('mo2f_show_loginwith_phone', 0);
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQUEST_PSWDLESS" ) );
								$this->mo_auth_show_error_message();
							}
							else
							{
								update_site_option('mo2f_show_loginwith_phone', 1);
								update_site_option('mo2f_remember_device', 0);
							}
						} else {
							update_site_option('mo2f_show_loginwith_phone', 0);
						}
						if(isset($_POST['mo2f_login_policy']))
						{
							update_site_option('mo2f_login_policy',0);
							update_site_option('mo2f_enable_2fa_prompt_on_login_page','0');
						}

						else
						{
							update_site_option('mo2f_login_policy', 1);
						}

						if(!isset($_POST['mo2f_login_policy'])) 
							update_site_option('mo2f_show_loginwith_phone', 0);


					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQUEST" ) );
						$this->mo_auth_show_error_message();
					}
				}
			}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_sync_sms_transactions" ) {
				$customer = new Customer_Setup();
				$content  = json_decode( $customer->get_customer_transactions( get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
				if ( ! array_key_exists( 'smsRemaining', $content ) ) {
					$smsRemaining = 0;
				} else {
					$smsRemaining = $content['smsRemaining'];
					if ( $smsRemaining == null ) {
						$smsRemaining = 0;
					}
				}
				update_site_option( 'mo2f_number_of_transactions', $smsRemaining );
			}


		}

		if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_fix_database_error' ) {
			$nonce = sanitize_text_field($_POST['mo2f_fix_database_error_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo2f-fix-database-error-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				global $Mo2fdbQueries;

				$Mo2fdbQueries->database_table_issue();

			}
		}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_skip_feedback' ) {

			$nonce = sanitize_text_field($_POST['mo2f_skip_feedback_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo2f-skip-feedback-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				deactivate_plugins( '/miniorange-2-factor-authentication/miniorange_2_factor_settings.php' );
			}

		}else if ( isset( $_POST['mo2f_feedback'] ) and sanitize_text_field($_POST['mo2f_feedback']) == 'mo2f_feedback' ) {
			
			$nonce = sanitize_text_field($_POST['mo2f_feedback_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo2f-feedback-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$reasons_not_to_worry_about = array( "Upgrading to Standard / Premium", "Temporary deactivation - Testing" );

				$message = 'Plugin Deactivated:';

				if ( isset( $_POST['deactivate_plugin'] ) ) {
					if ( $_POST['query_feedback'] == '' and sanitize_text_field($_POST['deactivate_plugin']) == 'Other Reasons:' ) {
						// feedback add
						update_site_option( 'mo2f_message', 'Please let us know the reason for deactivation so that we improve the user experience.' );
					} else {

						if ( ! in_array( $_POST['deactivate_plugin'], $reasons_not_to_worry_about ) ) {

							$message .= sanitize_text_field($_POST['deactivate_plugin']);

							if ( $_POST['query_feedback'] != '' ) {
								$message .= ':' . sanitize_text_field($_POST['query_feedback']);
							}


							if($_POST['deactivate_plugin'] == "Conflicts with other plugins"){
								$plugin_selected = $_POST['plugin_selected'];
								$plugin = MO2f_Utility::get_plugin_name_by_identifier($plugin_selected);

								$message .= ", Plugin selected - " . sanitize_text_field($plugin) . ".";
							}

							$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
							if ( $email == '' ) {
								$email = $user->user_email;
							}

							$phone = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );;

							$contact_us = new Customer_Setup();
							$submited   = json_decode( $contact_us->send_email_alert( $email, $phone, $message ), true );

							if ( json_last_error() == JSON_ERROR_NONE ) {
								if ( is_array( $submited ) && array_key_exists( 'status', $submited ) && $submited['status'] == 'ERROR' ) {
									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $submited['message'] ) );
									$this->mo_auth_show_error_message();
								} else {
									if ( $submited == false ) {
										update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SUBMITTING_QUERY" ) );
										$this->mo_auth_show_error_message();
									} else {
										update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "QUERY_SUBMITTED_SUCCESSFULLY" ) );
										$this->mo_auth_show_success_message();
									}
								}
							}
						}

						deactivate_plugins( '/miniorange-2-factor-authentication/miniorange_2_factor_settings.php' );

					}

				} else {
					update_site_option( 'mo2f_message', 'Please Select one of the reasons if your reason isnot mention please select Other Reasons' );

				}
			}

		}else if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_2factor_resend_user_otp" ) { //resend OTP over email for additional admin and non-admin user
			
			$nonce = sanitize_text_field($_POST['mo_2factor_resend_user_otp_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo-2factor-resend-user-otp-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$customer = new Customer_Setup();
				$content  = json_decode( $customer->send_otp_token( get_user_meta( $user->ID, 'user_email', true ), 'EMAIL', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
				if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "OTP_SENT" ) . ' <b>' . ( get_user_meta( $user->ID, 'user_email', true ) ) . '</b>. ' . Mo2fConstants:: langTranslate( "ENTER_OTP" ) );
					update_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', $content['txId'] );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
					$this->mo_auth_show_success_message();
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_EMAIL" ) );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
					$this->mo_auth_show_error_message();

				}
			}

		}else if  ( isset( $_POST['option'] ) and ( sanitize_text_field($_POST['option']) == "mo2f_configure_miniorange_authenticator_validate" || sanitize_text_field($_POST['option']) == 'mo_auth_mobile_reconfiguration_complete' ) ) { //mobile registration successfully complete for all users

			$nonce = sanitize_text_field($_POST['mo2f_configure_miniorange_authenticator_validate_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-miniorange-authenticator-validate-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				delete_site_option( 'mo2f_transactionId' );
				$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
				MO2f_Utility::unset_session_variables( $session_variables );

				$email                     = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$TwoFA_method_to_configure = get_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', true );
				$enduser                   = new Two_Factor_Setup();
				$current_method            = MO2f_Utility::mo2f_decode_2_factor( $TwoFA_method_to_configure, "server" );

				$response = json_decode( $enduser->mo2f_update_userinfo( $email, $current_method, null, null, null ), true );

				if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate Qr code */
					if ( $response['status'] == 'ERROR' ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );

						$this->mo_auth_show_error_message();


					} else if ( $response['status'] == 'SUCCESS' ) {

						$selectedMethod = $TwoFA_method_to_configure;

						delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );


						$Mo2fdbQueries->update_user_details( $user->ID, array(
							'mo2f_configured_2FA_method'                        => $selectedMethod,
							'mobile_registration_status'                        => true,
							'mo2f_miniOrangeQRCodeAuthentication_config_status' => true,
							'mo2f_miniOrangeSoftToken_config_status'            => true,
							'mo2f_miniOrangePushNotification_config_status'     => true,
							'user_registration_with_miniorange'                 => 'SUCCESS',
							'mo_2factor_user_registration_status'               => 'MO_2_FACTOR_PLUGIN_SETTINGS'
						) );

						delete_user_meta( $user->ID, 'configure_2FA' );
						mo2f_display_test_2fa_notification($user);

					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
						$this->mo_auth_show_error_message();
					}

				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
					$this->mo_auth_show_error_message();
				}
			}
		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_mobile_authenticate_success' ) { // mobile registration for all users(common)

			$nonce = sanitize_text_field($_POST['mo2f_mobile_authenticate_success_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-mobile-authenticate-success-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {

				if ( current_user_can( 'manage_options' ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
				}

				$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
				MO2f_Utility::unset_session_variables( $session_variables );

				delete_user_meta( $user->ID, 'mo2f_test_2FA' );
				$this->mo_auth_show_success_message();
			}
		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_mobile_authenticate_error' ) { //mobile registration failed for all users(common)
			$nonce = sanitize_text_field($_POST['mo2f_mobile_authenticate_error_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-mobile-authenticate-error-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "AUTHENTICATION_FAILED" ) );
				MO2f_Utility::unset_session_variables( 'mo2f_show_qr_code' );
				$this->mo_auth_show_error_message();
			}

		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_setting_configuration" )  // redirect to setings page
		{	
			
			$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS' ) );

		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_auth_refresh_mobile_qrcode" ) { // refrsh Qrcode for all users
			
			$nonce = sanitize_text_field($_POST['mo_auth_refresh_mobile_qrcode_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo-auth-refresh-mobile-qrcode-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
				if ( in_array( $mo_2factor_user_registration_status, array(
					'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
					'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION',
					'MO_2_FACTOR_PLUGIN_SETTINGS'
				) ) ) {
					$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
					$this->mo2f_get_qr_code_for_mobile( $email, $user->ID );
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "REGISTER_WITH_MO" ) );
					$this->mo_auth_show_error_message();

				}
			}
		}else if  ( isset( $_POST['mo2fa_register_to_upgrade_nonce'] ) ) { //registration with miniOrange for upgrading
			$nonce = sanitize_text_field($_POST['mo2fa_register_to_upgrade_nonce']);
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-user-reg-to-upgrade-nonce' ) ) {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
			} else {
				$requestOrigin = sanitize_text_field($_POST['requestOrigin']);
				update_site_option( 'mo2f_customer_selected_plan', $requestOrigin );
				header( 'Location: admin.php?page=mo_2fa_account' );

			}
		}else if ( isset( $_POST['miniorange_get_started'] ) && isset( $_POST['miniorange_user_reg_nonce'] ) ) { //registration with miniOrange for additional admin and non-admin
			$nonce = sanitize_text_field($_POST['miniorange_user_reg_nonce']);
			$Mo2fdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-user-reg-nonce' ) ) {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
			} else {
				$email = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo_useremail'] ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ENTER_EMAILID" ) );

					return;
				} else {
					$email = sanitize_email( $_POST['mo_useremail'] );
				}

				if ( ! MO2f_Utility::check_if_email_is_already_registered( $email ) ) {
					update_user_meta( $user->ID, 'user_email', $email );

					$enduser    = new Two_Factor_Setup();
					$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );

					if ( json_last_error() == JSON_ERROR_NONE ) {
						if ( $check_user['status'] == 'ERROR' ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $check_user['message'] ) );
							$this->mo_auth_show_error_message();

							return;
						} else if ( strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) == 0 ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "EMAIL_IN_USE" ) );
							$this->mo_auth_show_error_message();

							return;
						} else if ( strcasecmp( $check_user['status'], 'USER_FOUND' ) == 0 || strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) == 0 ) {


							$enduser = new Customer_Setup();
							$content = json_decode( $enduser->send_otp_token( $email, 'EMAIL', get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
							if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "OTP_SENT" ) . ' <b>' . ( $email ) . '</b>. ' . Mo2fConstants:: langTranslate( "ENTER_OTP" ) );
								$_SESSION['mo2f_transactionId'] = $content['txId'];
								update_site_option( 'mo2f_transactionId', $content['txId'] );
								$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS';
								$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
								update_user_meta( $user->ID, 'mo_2fa_verify_otp_create_account', $content['txId'] );
								$this->mo_auth_show_success_message();
							} else {
								$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
								$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_OTP_OVER_EMAIL" ) );
								$this->mo_auth_show_error_message();
							}


						}
					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "EMAIL_IN_USE" ) );
					$this->mo_auth_show_error_message();
				}
			}
		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo_2factor_backto_user_registration' ) { //back to registration page for additional admin and non-admin
			$nonce = sanitize_text_field($_POST['mo_2factor_backto_user_registration_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo-2factor-backto-user-registration-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				delete_user_meta( $user->ID, 'user_email' );
				$Mo2fdbQueries->delete_user_details( $user->ID );
				MO2f_Utility::unset_session_variables( 'mo2f_transactionId' );
				delete_site_option( 'mo2f_transactionId' );
			}

		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_validate_soft_token' ) {  // validate Soft Token during test for all users
			
			$nonce = sanitize_text_field($_POST['mo2f_validate_soft_token_nonce']);


			if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-soft-token-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$otp_token = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ENTER_VALUE" ) );
					$this->mo_auth_show_error_message();

					return;
				} else {
					$otp_token = sanitize_text_field( $_POST['otp_token'] );
				}
				$email    = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$customer = new Customer_Setup();
				$content  = json_decode( $customer->validate_otp_token( 'SOFT TOKEN', $email, null, $otp_token, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
				if ( $content['status'] == 'ERROR' ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $content['message'] ) );
					$this->mo_auth_show_error_message();
				} else {
					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //OTP validated and generate QRCode
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );

						delete_user_meta( $user->ID, 'mo2f_test_2FA' );
						$this->mo_auth_show_success_message();


					} else {  // OTP Validation failed.
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
						$this->mo_auth_show_error_message();

					}
				}
			}
		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_validate_otp_over_sms' ) { //validate otp over sms and phone call during test for all users
			
			$nonce = sanitize_text_field($_POST['mo2f_validate_otp_over_sms_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-otp-over-sms-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$otp_token = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ENTER_VALUE" ) );
					$this->mo_auth_show_error_message();

					return;
				} else {
					$otp_token = sanitize_text_field( $_POST['otp_token'] );
				}

				//if the php session folder has insufficient permissions, temporary options to be used
				$mo2f_transactionId        = isset( $_SESSION['mo2f_transactionId'] ) && ! empty( $_SESSION['mo2f_transactionId'] ) ? $_SESSION['mo2f_transactionId'] : get_site_option( 'mo2f_transactionId' );
				$email                     = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$selected_2_2factor_method = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$customer                  = new Customer_Setup();
				$content                   = json_decode( $customer->validate_otp_token( get_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', true ), $email, $mo2f_transactionId, $otp_token, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

				if ( $content['status'] == 'ERROR' ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $content['message'] ) );
					$this->mo_auth_show_error_message();
				} else {
					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //OTP validated
						if ( current_user_can( 'manage_options' ) ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( "COMPLETED_TEST" ) );
						}

						delete_user_meta( $user->ID, 'mo2f_test_2FA' );
						$this->mo_auth_show_success_message();

					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
						$this->mo_auth_show_error_message();
					}

				}
			}
		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_out_of_band_success' ) {
			$nonce = sanitize_text_field($_POST['mo2f_out_of_band_success_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-out-of-band-success-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$show = 1;
				if(get_site_option('is_onprem') )
				{
					$txid   = $_POST['TxidEmail'];
					$status = get_site_option($txid);
					if($status != '')
					{
						if($status != 1)
						{
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_EMAIL_VER_REQ" ));
							$show = 0;
							$this->mo_auth_show_error_message();

						}
					}
				}
				$mo2f_configured_2FA_method           = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
				$mo2f_EmailVerification_config_status = $Mo2fdbQueries->get_user_detail( 'mo2f_EmailVerification_config_status', $user->ID );
				if ( ! current_user_can( 'manage_options' ) && $mo2f_configured_2FA_method == 'OUT OF BAND EMAIL' ) {
					if ( $mo2f_EmailVerification_config_status ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
					} else {
						$email    = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
						$enduser  = new Two_Factor_Setup();
						$response = json_decode( $enduser->mo2f_update_userinfo( $email, $mo2f_configured_2FA_method, null, null, null ), true );
						update_site_option( 'mo2f_message', '<b> ' . Mo2fConstants:: langTranslate( "EMAIL_VERFI" ) . '</b> ' . Mo2fConstants:: langTranslate( "SET_AS_2ND_FACTOR" ) );
					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
				}
				delete_user_meta( $user->ID, 'mo2f_test_2FA' );
				$Mo2fdbQueries->update_user_details( $user->ID, array(
					'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
					'mo2f_EmailVerification_config_status' => true
				) );
				if($show)
					$this->mo_auth_show_success_message();
			}


		}else if  ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo2f_out_of_band_error' ) { //push and out of band email denied
			$nonce = sanitize_text_field($_POST['mo2f_out_of_band_error_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-out-of-band-error-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "DENIED_REQUEST" ) );
				delete_user_meta( $user->ID, 'mo2f_test_2FA' );
				$Mo2fdbQueries->update_user_details( $user->ID, array(
					'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
					'mo2f_EmailVerification_config_status' => true
				) );
				$this->mo_auth_show_error_message();
			}

		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_validate_google_authy_test' ) {
			
			$nonce = sanitize_text_field($_POST['mo2f_validate_google_authy_test_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-google-authy-test-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$otp_token = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ENTER_VALUE" ) );
					$this->mo_auth_show_error_message();

					return;
				} else {
					$otp_token = sanitize_text_field( $_POST['otp_token'] );
				}
				$email    = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				if(get_site_option('is_onprem')){
					include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR. 'gaonprem.php';
					$gauth_obj= new Google_auth_onpremise();
					$secret= $gauth_obj->mo_GAuth_get_secret($user->ID);
					$content=$gauth_obj->verifyCode($secret, $otp_token );
					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //Google OTP validated
						if ( current_user_can( 'manage_options' ) ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
						}

						delete_user_meta( $user->ID, 'mo2f_test_2FA' );
						$this->mo_auth_show_success_message();


					} else {  // OTP Validation failed.
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
						$this->mo_auth_show_error_message();

					}
				}else{
					$customer = new Customer_Setup();
					$content  = json_decode( $customer->validate_otp_token( 'GOOGLE AUTHENTICATOR', $email, null, $otp_token, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );
					if ( json_last_error() == JSON_ERROR_NONE ) {

					if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //Google OTP validated

						if ( current_user_can( 'manage_options' ) ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
						}

						delete_user_meta( $user->ID, 'mo2f_test_2FA' );
						$this->mo_auth_show_success_message();


					} else {  // OTP Validation failed.
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
						$this->mo_auth_show_error_message();

					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_VALIDATING_OTP" ) );
					$this->mo_auth_show_error_message();

				}
			}
		}
	}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_google_appname' ) {
		$nonce = sanitize_text_field($_POST['mo2f_google_appname_nonce']);
		
		if ( ! wp_verify_nonce( $nonce, 'mo2f-google-appname-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		} else {	

			update_site_option('mo2f_google_appname',((isset($_POST['mo2f_google_auth_appname']) && $_POST['mo2f_google_auth_appname']!='') ? sanitize_text_field($_POST['mo2f_google_auth_appname']) : 'miniOrangeAuth'));
		}

	}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_configure_google_authenticator_validate' ) {
		$nonce = sanitize_text_field($_POST['mo2f_configure_google_authenticator_validate_nonce']);
		
		if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-google-authenticator-validate-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		} else {	
			$otpToken  = sanitize_text_field($_POST['google_token']);
			$ga_secret = isset( $_POST['google_auth_secret'] ) ? sanitize_text_field($_POST['google_auth_secret']) : null;

			if ( MO2f_Utility::mo2f_check_number_length( $otpToken ) ) {
				$email           = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				if(get_site_option('is_onprem')){

					$twofactor_transactions = new Mo2fDB;
					$exceeded = $twofactor_transactions->check_user_limit_exceeded($user->ID);

					if($exceeded){
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "USER_LIMIT_EXCEEDED" ) );
						$this->mo_auth_show_error_message();
						return;
					}
					global $current_user;
					$current_user = wp_get_current_user();
					$email = (string) $current_user->user_email;
					include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR. 'gaonprem.php';
					$gauth_obj= new Google_auth_onpremise();
					$content=$gauth_obj->verifyCode($_SESSION['secret_ga'] , $otpToken );

					if ( $content['status'] == 'SUCCESS' ) {

						delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );

						delete_user_meta( $user->ID, 'configure_2FA' );

						$Mo2fdbQueries->update_user_details( $user->ID, array(
							'mo2f_GoogleAuthenticator_config_status' => true,
							'mo2f_AuthyAuthenticator_config_status'  => false,
							'mo2f_configured_2FA_method'             => "Google Authenticator",
							'user_registration_with_miniorange'      => 'SUCCESS',
							'mo_2factor_user_registration_status'    => 'MO_2_FACTOR_PLUGIN_SETTINGS'
						) );
						update_user_meta($user->ID,'mo2f_2FA_method_to_configure','Google Authenticator');
						update_user_meta( $user->ID, 'mo2f_external_app_type', "Google Authenticator" );
						update_user_meta($user->ID, 'currentMethod','Google Authenticator');
						mo2f_display_test_2fa_notification($user);
						$gauth_obj->mo_GAuth_set_secret($user->ID, sanitize_text_field($_SESSION['secret_ga']));
						unset($_SESSION['secret_ga']);

					}else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_OTP_CAUSES" ) . '<br>1. ' . Mo2fConstants:: langTranslate( "INVALID_OTP" ) . '<br>2. ' . Mo2fConstants:: langTranslate( "APP_TIME_SYNC" ) );
						$this->mo_auth_show_error_message();

					}
				}else{
					$google_auth     = new Miniorange_Rba_Attributes();
					$google_response = json_decode( $google_auth->mo2f_validate_google_auth( $email, $otpToken, $ga_secret ), true );
					if ( json_last_error() == JSON_ERROR_NONE ) {
						if ( $google_response['status'] == 'SUCCESS' ) {
							$enduser  = new Two_Factor_Setup();
							$response = json_decode( $enduser->mo2f_update_userinfo( $email, "GOOGLE AUTHENTICATOR", null, null, null ), true );


							if ( json_last_error() == JSON_ERROR_NONE ) {

								if ( $response['status'] == 'SUCCESS' ) {

									delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );

									delete_user_meta( $user->ID, 'configure_2FA' );

									$Mo2fdbQueries->update_user_details( $user->ID, array(
										'mo2f_GoogleAuthenticator_config_status' => true,
										'mo2f_AuthyAuthenticator_config_status'  => false,
										'mo2f_configured_2FA_method'             => "Google Authenticator",
										'user_registration_with_miniorange'      => 'SUCCESS',
										'mo_2factor_user_registration_status'    => 'MO_2_FACTOR_PLUGIN_SETTINGS'
									) );

									update_user_meta( $user->ID, 'mo2f_external_app_type', "Google Authenticator" );
									mo2f_display_test_2fa_notification($user);

								} else {
									update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
									$this->mo_auth_show_error_message();

								}
							} else {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
								$this->mo_auth_show_error_message();

							}
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_OTP_CAUSES" ) . '<br>1. ' . Mo2fConstants:: langTranslate( "INVALID_OTP" ) . '<br>2. ' . Mo2fConstants:: langTranslate( "APP_TIME_SYNC" ) );
							$this->mo_auth_show_error_message();

						}
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_VALIDATING_USER" ) );
						$this->mo_auth_show_error_message();

					}
				}
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ONLY_DIGITS_ALLOWED" ) );
				$this->mo_auth_show_error_message();

			}
		}
	}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_configure_authy_authenticator' ) {
		$nonce = sanitize_text_field($_POST['mo2f_configure_authy_authenticator_nonce']);
		
		if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-authy-authenticator-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		} else {	
			$authy          = new Miniorange_Rba_Attributes();
			$user_email     = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
			$authy_response = json_decode( $authy->mo2f_google_auth_service( $user_email ), true );
			if ( json_last_error() == JSON_ERROR_NONE ) {
				if ( $authy_response['status'] == 'SUCCESS' ) {
					$mo2f_authy_keys                      = array();
					$mo2f_authy_keys['authy_qrCode']      = $authy_response['qrCodeData'];
					$mo2f_authy_keys['mo2f_authy_secret'] = $authy_response['secret'];
					$_SESSION['mo2f_authy_keys']          = $mo2f_authy_keys;
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
					$this->mo_auth_show_error_message();
				}
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
				$this->mo_auth_show_error_message();
			}
		}
	}else if( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_configure_authy_authenticator_validate' ) {
		$nonce = sanitize_text_field($_POST['mo2f_configure_authy_authenticator_validate_nonce']);
		
		if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-authy-authenticator-validate-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		} else {	
			$otpToken     = sanitize_text_field($_POST['mo2f_authy_token']);
			$authy_secret = isset( $_POST['mo2f_authy_secret'] ) ? sanitize_text_field($_POST['mo2f_authy_secret']) : null;
			if ( MO2f_Utility::mo2f_check_number_length( $otpToken ) ) {
				$email          = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$authy_auth     = new Miniorange_Rba_Attributes();
				$authy_response = json_decode( $authy_auth->mo2f_validate_google_auth( $email, $otpToken, $authy_secret ), true );
				if ( json_last_error() == JSON_ERROR_NONE ) {
					if ( $authy_response['status'] == 'SUCCESS' ) {
						$enduser  = new Two_Factor_Setup();
						$response = json_decode( $enduser->mo2f_update_userinfo( $email, 'GOOGLE AUTHENTICATOR', null, null, null ), true );
						if ( json_last_error() == JSON_ERROR_NONE ) {

							if ( $response['status'] == 'SUCCESS' ) {
								$Mo2fdbQueries->update_user_details( $user->ID, array(
									'mo2f_GoogleAuthenticator_config_status' => false,
									'mo2f_AuthyAuthenticator_config_status'  => true,
									'mo2f_configured_2FA_method'             => "Authy Authenticator",
									'user_registration_with_miniorange'      => 'SUCCESS',
									'mo_2factor_user_registration_status'    => 'MO_2_FACTOR_PLUGIN_SETTINGS'
								) );
								update_user_meta( $user->ID, 'mo2f_external_app_type', "Authy Authenticator" );
								delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
								delete_user_meta( $user->ID, 'configure_2FA' );

								mo2f_display_test_2fa_notification($user);

							} else {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
								$this->mo_auth_show_error_message();
							}
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
							$this->mo_auth_show_error_message();
						}
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_IN_SENDING_OTP_CAUSES" ) . '<br>1. ' . Mo2fConstants:: langTranslate( "INVALID_OTP" ) . '<br>2. ' . Mo2fConstants:: langTranslate( "APP_TIME_SYNC" ) );
						$this->mo_auth_show_error_message();
					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_VALIDATING_USER" ) );
					$this->mo_auth_show_error_message();
				}
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ONLY_DIGITS_ALLOWED" ) );
				$this->mo_auth_show_error_message();
			}
		}
	}
	else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_save_kba' ) {
		$nonce = sanitize_text_field($_POST['mo2f_save_kba_nonce']);
		if ( ! wp_verify_nonce( $nonce, 'mo2f-save-kba-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		}
		$twofactor_transactions = new Mo2fDB;
		$exceeded = $twofactor_transactions->check_user_limit_exceeded($user_id);

		if($exceeded){
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "USER_LIMIT_EXCEEDED" ) );
			$this->mo_auth_show_error_message();
			return;
		}
		if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_3'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans3'] ) ) {
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
			$this->mo_auth_show_error_message();
			return;
		}

		$kba_q1 = sanitize_text_field($_POST['mo2f_kbaquestion_1']);
		$kba_a1 = sanitize_text_field( $_POST['mo2f_kba_ans1'] );
		$kba_q2 = sanitize_text_field($_POST['mo2f_kbaquestion_2']);
		$kba_a2 = sanitize_text_field( $_POST['mo2f_kba_ans2'] );
		$kba_q3 = sanitize_text_field( $_POST['mo2f_kbaquestion_3'] );
		$kba_a3 = sanitize_text_field( $_POST['mo2f_kba_ans3'] );

		if ( strcasecmp( $kba_q1, $kba_q2 ) == 0 || strcasecmp( $kba_q2, $kba_q3 ) == 0 || strcasecmp( $kba_q3, $kba_q1 ) == 0 ) {
			update_site_option( 'mo2f_message', 'The questions you select must be unique.' );
			$this->mo_auth_show_error_message();
			return;
		}
		$kba_q1 = addcslashes( stripslashes( $kba_q1 ), '"\\' );
		$kba_q2 = addcslashes( stripslashes( $kba_q2 ), '"\\' );
		$kba_q3 = addcslashes( stripslashes( $kba_q3 ), '"\\' );
		if(get_site_option('is_onprem')){

			$kba_a1 = md5(addcslashes( stripslashes( $kba_a1 ), '"\\' ));
			$kba_a2 = md5(addcslashes( stripslashes( $kba_a2 ), '"\\' ));
			$kba_a3 = md5(addcslashes( stripslashes( $kba_a3 ), '"\\' ));

			$question_answer  = array($kba_q1 => $kba_a1 ,$kba_q2 => $kba_a2 , $kba_q3 => $kba_a3 );
			update_user_meta( $user_id , 'mo2f_kba_challenge', $question_answer  );
			delete_user_meta( $user_id, 'configure_2FA' );
			$Mo2fdbQueries->update_user_details( $user->ID, array(
				'mo2f_SecurityQuestions_config_status' => true,
				'mo2f_configured_2FA_method'           => "Security Questions",
				'mo_2factor_user_registration_status'  => "MO_2_FACTOR_PLUGIN_SETTINGS"
			) );
			update_user_meta($user->ID,'currentMethod','Security Questions');
			mo2f_display_test_2fa_notification($user);	
		}
		else{
			$kba_a1 = addcslashes( stripslashes( $kba_a1 ), '"\\' );
			$kba_a2 = addcslashes( stripslashes( $kba_a2 ), '"\\' );
			$kba_a3 = addcslashes( stripslashes( $kba_a3 ), '"\\' );

			$email            = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
			$kba_registration = new Two_Factor_Setup();
			$kba_reg_reponse  = json_decode( $kba_registration->register_kba_details( $email, $kba_q1, $kba_a1, $kba_q2, $kba_a2, $kba_q3, $kba_a3 ), true );
			if ( json_last_error() == JSON_ERROR_NONE ) {
				if ( $kba_reg_reponse['status'] == 'SUCCESS' ) {
					if ( isset( $_POST['mobile_kba_option'] ) && $_POST['mobile_kba_option'] == 'mo2f_request_for_kba_as_emailbackup' ) {
						MO2f_Utility::unset_session_variables( 'mo2f_mobile_support' );

						delete_user_meta( $user->ID, 'configure_2FA' );
						delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );

						$message = mo2f_lt( 'Your KBA as alternate 2 factor is configured successfully.' );
						update_site_option( 'mo2f_message', $message );
						$this->mo_auth_show_success_message();

					} else {
						$enduser  = new Two_Factor_Setup();
						$response = json_decode( $enduser->mo2f_update_userinfo( $email, 'KBA', null, null, null ), true );
						if ( json_last_error() == JSON_ERROR_NONE ) {
							if ( $response['status'] == 'ERROR' ) {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );
								$this->mo_auth_show_error_message();

							} else if ( $response['status'] == 'SUCCESS' ) {
								delete_user_meta( $user->ID, 'configure_2FA' );

								$Mo2fdbQueries->update_user_details( $user->ID, array(
									'mo2f_SecurityQuestions_config_status' => true,
									'mo2f_configured_2FA_method'           => "Security Questions",
									'mo_2factor_user_registration_status'  => "MO_2_FACTOR_PLUGIN_SETTINGS"
								) );
										
								mo2f_display_test_2fa_notification($user);

							}else {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
								$this->mo_auth_show_error_message();

							}
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
							$this->mo_auth_show_error_message();

						}
					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SAVING_KBA" ) );
					$this->mo_auth_show_error_message();


					return;
				}
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_WHILE_SAVING_KBA" ) );
				$this->mo_auth_show_error_message();


				return;
			}	

		}
	}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_validate_kba_details' ) {
		$nonce = sanitize_text_field($_POST['mo2f_validate_kba_details_nonce']);
		
		if ( ! wp_verify_nonce( $nonce, 'mo2f-validate-kba-details-nonce' ) ) {
			$error = new WP_Error();
			$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

			return $error;
		} else {	
			$kba_ans_1 = '';
			$kba_ans_2 = '';
			if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_1'] ) ) {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
				$this->mo_auth_show_error_message();

				return;
			} else {
				$kba_ans_1 = sanitize_text_field( $_POST['mo2f_answer_1'] );
				$kba_ans_2 = sanitize_text_field( $_POST['mo2f_answer_2'] );
			}
				//if the php session folder has insufficient permissions, temporary options to be used
			$kba_questions = isset( $_SESSION['mo_2_factor_kba_questions'] ) && ! empty( $_SESSION['mo_2_factor_kba_questions'] ) ? sanitize_text_field($_SESSION['mo_2_factor_kba_questions']) : get_site_option( 'kba_questions' );

			$kbaAns    = array();
			$kbaAns[0] = isset($kba_questions[0])?$kba_questions[0]:'';
			$kbaAns[1] = $kba_ans_1;
			$kbaAns[2] = isset($kba_questions[1])?$kba_questions[1]:'';
			$kbaAns[3] = $kba_ans_2;

				//if the php session folder has insufficient permissions, temporary options to be used
			$mo2f_transactionId = isset( $_SESSION['mo2f_transactionId'] ) && ! empty( $_SESSION['mo2f_transactionId'] ) ? sanitize_text_field($_SESSION['mo2f_transactionId']) : get_site_option( 'mo2f_transactionId' );

			$kba_validate          = new Customer_Setup();
			$kba_validate_response = json_decode( $kba_validate->validate_otp_token( 'KBA', null, $mo2f_transactionId, $kbaAns, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

			if ( json_last_error() == JSON_ERROR_NONE ) {
				if ( strcasecmp( $kba_validate_response['status'], 'SUCCESS' ) == 0 ) {
					unset( $_SESSION['mo_2_factor_kba_questions'] );
					unset( $_SESSION['mo2f_transactionId'] );
					delete_site_option('mo2f_transactionId');
					delete_site_option('kba_questions');
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "COMPLETED_TEST" ) );
					delete_user_meta( $user->ID, 'mo2f_test_2FA' );
					$this->mo_auth_show_success_message();
				} 
			
				else {  // KBA Validation failed.
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ANSWERS" ) );
					$this->mo_auth_show_error_message();

				}
			}

			}
		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_configure_otp_over_sms_send_otp' ) { // sendin otp for configuring OTP over SMS
			
			$nonce = sanitize_text_field($_POST['mo2f_configure_otp_over_sms_send_otp_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-otp-over-sms-send-otp-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {	
				$phone = sanitize_text_field( $_POST['verify_phone'] );

				if ( MO2f_Utility::mo2f_check_empty_or_null( $phone ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
					$this->mo_auth_show_error_message();

					return;
				}

				$phone                  = str_replace( ' ', '', $phone );
				$_SESSION['user_phone'] = $phone;
				update_site_option( 'user_phone_temp', $phone );
				$customer      = new Customer_Setup();
				$currentMethod = "SMS";

				$content = json_decode( $customer->send_otp_token( $phone, $currentMethod, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

				if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate otp token */
					if ( $content['status'] == 'ERROR' ) {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );
						$this->mo_auth_show_error_message();
					} else if ( $content['status'] == 'SUCCESS' ) {
						$_SESSION['mo2f_transactionId'] = $content['txId'];
						update_site_option( 'mo2f_transactionId', $content['txId'] );
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "OTP_SENT" ) . ' ' . $phone . ' .' . Mo2fConstants:: langTranslate( "ENTER_OTP" ) );
						update_site_option( 'mo2f_number_of_transactions', get_site_option( 'mo2f_number_of_transactions' ) - 1 );
						$this->mo_auth_show_success_message();
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( $content['message'] ) );
						$this->mo_auth_show_error_message();
					}

				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
					$this->mo_auth_show_error_message();
				}
			}
		}else if  ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_configure_otp_over_sms_validate' ) {
			$nonce = sanitize_text_field($_POST['mo2f_configure_otp_over_sms_validate_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-configure-otp-over-sms-validate-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {	
				$otp_token = '';
				if ( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_ENTRY" ) );
					$this->mo_auth_show_error_message();

					return;
				} else {
					$otp_token = sanitize_text_field( $_POST['otp_token'] );
				}

				//if the php session folder has insufficient permissions, temporary options to be used
				$mo2f_transactionId         = isset( $_SESSION['mo2f_transactionId'] ) && ! empty( $_SESSION['mo2f_transactionId'] ) ? sanitize_text_field($_SESSION['mo2f_transactionId']) : get_site_option( 'mo2f_transactionId' );
				$user_phone                 = isset( $_SESSION['user_phone'] ) && $_SESSION['user_phone'] != 'false' ? sanitize_text_field($_SESSION['user_phone']) : get_site_option( 'user_phone_temp' );
				$mo2f_configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
				$phone                      = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
				$customer                   = new Customer_Setup();
				$content                    = json_decode( $customer->validate_otp_token( $mo2f_configured_2FA_method, null, $mo2f_transactionId, $otp_token, get_site_option( 'mo2f_customerKey' ), get_site_option( 'mo2f_api_key' ) ), true );

				if ( $content['status'] == 'ERROR' ) {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $content['message'] ) );

				} else if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) { //OTP validated
					if ( $phone && strlen( $phone ) >= 4 ) {
						if ( $user_phone != $phone ) {
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mobile_registration_status' => false ) );

						}
					}
					$email = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );

					$enduser                   = new Two_Factor_Setup();
					$TwoFA_method_to_configure = get_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', true );
					$current_method            = MO2f_Utility::mo2f_decode_2_factor( $TwoFA_method_to_configure, "server" );
					$response                  = json_decode( $enduser->mo2f_update_userinfo( $email, $current_method, $user_phone, null, null ), true );

					if ( json_last_error() == JSON_ERROR_NONE ) {

						if ( $response['status'] == 'ERROR' ) {
							MO2f_Utility::unset_session_variables( 'user_phone' );
							delete_site_option( 'user_phone_temp' );

							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );
							$this->mo_auth_show_error_message();
						} else if ( $response['status'] == 'SUCCESS' ) {

							$Mo2fdbQueries->update_user_details( $user->ID, array(
								'mo2f_configured_2FA_method'          => 'OTP Over SMS',
								'mo2f_OTPOverSMS_config_status'       => true,
								'user_registration_with_miniorange'   => 'SUCCESS',
								'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
								'mo2f_user_phone'                     => $user_phone
							) );

							delete_user_meta( $user->ID, 'configure_2FA' );
							delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );

							unset( $_SESSION['user_phone'] );
							MO2f_Utility::unset_session_variables( 'user_phone' );
							delete_site_option( 'user_phone_temp' );

							mo2f_display_test_2fa_notification($user);
						} else {
							MO2f_Utility::unset_session_variables( 'user_phone' );
							delete_site_option( 'user_phone_temp' );
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
							$this->mo_auth_show_error_message();
						}
					} else {
						MO2f_Utility::unset_session_variables( 'user_phone' );
						delete_site_option( 'user_phone_temp' );
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
						$this->mo_auth_show_error_message();
					}

				} else {  // OTP Validation failed.
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_OTP" ) );
					$this->mo_auth_show_error_message();
				}
			}

		}else if ( ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_save_free_plan_auth_methods' ) ) {// user clicks on Set 2-Factor method

			$nonce = sanitize_text_field($_POST['miniorange_save_form_auth_methods_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'miniorange-save-form-auth-methods-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );
				return $error;
			} else {
				$configuredMethod = sanitize_text_field($_POST['mo2f_configured_2FA_method_free_plan']);
				$selectedAction   = sanitize_text_field($_POST['mo2f_selected_action_free_plan']);
				if(get_site_option('is_onprem') and  $configuredMethod =='EmailVerification')
				{
					update_user_meta($user->ID,'currentMethod','Email Verification');
					mo2f_display_test_2fa_notification($user);
				}
				else if($selectedAction == 'select2factor' and get_site_option('is_onprem'))
				{
					if($configuredMethod == 'SecurityQuestions')
						update_user_meta($user->ID,'currentMethod','Security Questions');
					else if($configuredMethod == 'GoogleAuthenticator')
						update_user_meta($user->ID,'currentMethod','Google Authenticator');	
					else 
						update_user_meta($user->ID,'currentMethod',$configuredMethod);
					mo2f_display_test_2fa_notification($user);	
				}
				$is_customer_registered = $Mo2fdbQueries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) == 'SUCCESS' ? true : false;
				$selected_2FA_method = MO2f_Utility::mo2f_decode_2_factor( isset( $_POST['mo2f_configured_2FA_method_free_plan'] ) ? sanitize_text_field($_POST['mo2f_configured_2FA_method_free_plan']) : sanitize_text_field($_POST['mo2f_selected_action_standard_plan']), "wpdb" );
				update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $selected_2FA_method );
				if(get_site_option('is_onprem'))
					$is_customer_registered = 1;
				if ( $is_customer_registered ) {
					$selected_2FA_method        = MO2f_Utility::mo2f_decode_2_factor( isset( $_POST['mo2f_configured_2FA_method_free_plan'] ) ? sanitize_text_field($_POST['mo2f_configured_2FA_method_free_plan']) : sanitize_text_field($_POST['mo2f_selected_action_standard_plan']), "wpdb" );
					$selected_action            = isset( $_POST['mo2f_selected_action_free_plan'] ) ? sanitize_text_field($_POST['mo2f_selected_action_free_plan']) : sanitize_text_field($_POST['mo2f_selected_action_standard_plan']);
					$user_phone                 = '';
					if ( isset( $_SESSION['user_phone'] ) ) {
						$user_phone = $_SESSION['user_phone'] != 'false' ? sanitize_text_field($_SESSION['user_phone']) : $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
					}

				// set it as his 2-factor in the WP database and server
					if ( $selected_action == "select2factor" ) {

						if ( $selected_2FA_method == 'OTP Over SMS' && $user_phone == 'false' ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "PHONE_NOT_CONFIGURED" ) );
							$this->mo_auth_show_error_message();
						} else {
						// update in the Wordpress DB
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo2f_configured_2FA_method' => $selected_2FA_method ) );

						// update the server
							if(get_site_option('is_onprem')==0)
								$this->mo2f_save_2_factor_method( $user, $selected_2FA_method );
							if ( in_array( $selected_2FA_method, array(
								"miniOrange QR Code Authentication",
								"miniOrange Soft Token",
								"miniOrange Push Notification",
								"Google Authenticator",
								"Security Questions",
								"Authy Authenticator",
								"Email Verification",
								"OTP Over SMS",
								"OTP Over Email",
								"OTP Over SMS and Email",
								"Hardware Token"
							) ) ) {

							} else {
								update_site_option( 'mo2f_enable_2fa_prompt_on_login_page', 0 );
							}

						}
					} else if ( $selected_action == "configure2factor" ) {

					//show configuration form of respective Two Factor method
						update_user_meta( $user->ID, 'configure_2FA', 1 );
						update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $selected_2FA_method );
					}

				} else {
					$Mo2fdbQueries->insert_user( $user->ID );
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => "REGISTRATION_STARTED" ) );
					update_user_meta( $user->ID, 'register_account_popup', 1 );
					update_site_option( 'mo2f_message', "" );
				}
			}
		}else if ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_enable_2FA_for_users_option' ) {
			$nonce = sanitize_text_field($_POST['mo2f_enable_2FA_for_users_option_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-2FA-for-users-option-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {	
				update_site_option( 'mo2f_enable_2fa_for_users', isset( $_POST['mo2f_enable_2fa_for_users'] ) ? $_POST['mo2f_enable_2fa_for_users'] : 0 );
			}
		}else if ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_disable_proxy_setup_option' ) {
			$nonce = sanitize_text_field($_POST['mo2f_disable_proxy_setup_option_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-disable-proxy-setup-option-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {	
				delete_site_option( 'mo2f_proxy_host' );
				delete_site_option( 'mo2f_port_number' );
				delete_site_option( 'mo2f_proxy_username' );
				delete_site_option( 'mo2f_proxy_password' );
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "Proxy Configurations Reset." ) );
				$this->mo_auth_show_success_message();
			}
		}else if ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_enable_2FA_option' ) {
			$nonce = sanitize_text_field($_POST['mo2f_enable_2FA_option_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-2FA-option-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				update_site_option( 'mo2f_enable_2fa', isset( $_POST['mo2f_enable_2fa'] ) ? sanitize_text_field($_POST['mo2f_enable_2fa']) : 0 );
			}
		}else if( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_enable_2FA_on_login_page_option' ) {
			$nonce = sanitize_text_field($_POST['mo2f_enable_2FA_on_login_page_option_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-2FA-on-login-page-option-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				update_site_option('mo2f_login_policy','1');
				update_site_option('mo2f_show_loginwith_phone','0');
				update_site_option('mo2f_enable_2fa_prompt_on_login_page',isset($_POST['mo2f_enable_2fa_prompt_on_login_page'])?sanitize_text_field($_POST['mo2f_enable_2fa_prompt_on_login_page']):0);
				
			}
		}
		else if ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo_2factor_test_authentication_method' ) {
		//network security feature 
			$nonce = sanitize_text_field($_POST['mo_2factor_test_authentication_method_nonce']);
			
			if ( ! wp_verify_nonce( $nonce, 'mo-2factor-test-authentication-method-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				update_user_meta( $user->ID, 'mo2f_test_2FA', 1 );

				$selected_2FA_method        = sanitize_text_field($_POST['mo2f_configured_2FA_method_test']);
				$customer                   = new Customer_Setup();
				$email                      = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
				$customer_key               = get_site_option( 'mo2f_customerKey' );
				$api_key                    = get_site_option( 'mo2f_api_key' );

				if(!get_site_option('is_onprem'))
				$selected_2FA_method_server = MO2f_Utility::mo2f_decode_2_factor( $selected_2FA_method, "server" );
			
                if ( $selected_2FA_method == 'Security Questions' ) {

					if(get_site_option('is_onprem')){
						$question_answers = get_user_meta($user->ID , 'mo2f_kba_challenge');
						$challenge_questions = array_keys($question_answers[0]);
						$random_keys = array_rand($challenge_questions,2);
						$challenge_ques1 = $challenge_questions[$random_keys[0]];
						$challenge_ques2 = $challenge_questions[$random_keys[1]];
						$questions =  array($challenge_ques1,$challenge_ques2);
						update_user_meta( $user->ID, 'kba_questions_user', $questions );
					}	
					else{
						$response = json_decode( $customer->send_otp_token( $email, $selected_2FA_method_server, $customer_key, $api_key ), true );
						if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate KBA Questions*/
							if ( $response['status'] == 'SUCCESS' ) {
								$_SESSION['mo2f_transactionId'] = $response['txId'];
								update_site_option( 'mo2f_transactionId', $response['txId'] );
								$questions                             = array();
								$questions[0]                          = $response['questions'][0]['question'];
								$questions[1]                          = $response['questions'][1]['question'];
								$_SESSION['mo_2_factor_kba_questions'] = $questions;
								update_site_option( 'kba_questions', $questions );

								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ANSWER_SECURITY_QUESTIONS" ) );
								$this->mo_auth_show_success_message();

							} else if ( $response['status'] == 'ERROR' ) {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_FETCHING_QUESTIONS" ) );
								$this->mo_auth_show_error_message();

							}
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_FETCHING_QUESTIONS" ) );
							$this->mo_auth_show_error_message();

						}
					}
				}


				else if ( $selected_2FA_method == 'miniOrange QR Code Authentication' ) {
					$response = json_decode( $customer->send_otp_token( $email, $selected_2FA_method_server, $customer_key, $api_key ), true );

					if ( json_last_error() == JSON_ERROR_NONE ) { /* Generate Qr code */

						if ( $response['status'] == 'ERROR' ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );
							$this->mo_auth_show_error_message();

						} else {
							if ( $response['status'] == 'SUCCESS' ) {
								$_SESSION['mo2f_qrCode']        = $response['qrCode'];
								$_SESSION['mo2f_transactionId'] = $response['txId'];
								$_SESSION['mo2f_show_qr_code']  = 'MO_2_FACTOR_SHOW_QR_CODE';
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "SCAN_QR_CODE" ) );
								$this->mo_auth_show_success_message();

							} else {
								unset( $_SESSION['mo2f_qrCode'] );
								unset( $_SESSION['mo2f_transactionId'] );
								unset( $_SESSION['mo2f_show_qr_code'] );
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
								$this->mo_auth_show_error_message();

							}
						}
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
						$this->mo_auth_show_error_message();

					}
				} 
			}

			update_user_meta( $user->ID, 'mo2f_2FA_method_to_test', $selected_2FA_method );
		}
		

		else if ( isset( $_POST['option'] ) && sanitize_text_field($_POST['option']) == 'mo2f_go_back' ) {
			$nonce = sanitize_text_field($_POST['mo2f_go_back_nonce']);

			if ( ! wp_verify_nonce( $nonce, 'mo2f-go-back-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Request.' ) );

				return $error;
			} else {
				$session_variables = array(
					'mo2f_qrCode',
					'mo2f_transactionId',
					'mo2f_show_qr_code',
					'user_phone',
					'mo2f_google_auth',
					'mo2f_mobile_support',
					'mo2f_authy_keys'
				);
				MO2f_Utility::unset_session_variables( $session_variables );
				delete_site_option( 'mo2f_transactionId' );
				delete_site_option( 'user_phone_temp' );

				delete_user_meta( $user->ID, 'mo2f_test_2FA' );
				delete_user_meta( $user->ID, 'configure_2FA' );
			}
		}

	}

	function mo_auth_deactivate() {
		global $Mo2fdbQueries;
		$mo2f_register_with_another_email = get_site_option( 'mo2f_register_with_another_email' );
		$is_EC                            = !get_site_option( 'mo2f_is_NC' ) ? 1 : 0;
		$is_NNC                           = get_site_option( 'mo2f_is_NC' ) && get_site_option( 'mo2f_is_NNC' ) ? 1 : 0;

		if ( $mo2f_register_with_another_email || $is_EC || $is_NNC ) {
			update_site_option( 'mo2f_register_with_another_email', 0 );
			$users = get_users( array() );
			$this->mo2f_delete_user_details( $users );
			$this->mo2f_delete_mo_options();
			$url = admin_url( 'plugins.php' );
			wp_redirect( $url );
		}
	}

	function mo2f_delete_user_details( $users ) {
		global $Mo2fdbQueries;
		foreach ( $users as $user ) {
			$Mo2fdbQueries->delete_user_details( $user->ID );
			delete_user_meta( $user->ID, 'phone_verification_status' );
			delete_user_meta( $user->ID, 'mo2f_test_2FA' );
			delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
			delete_user_meta( $user->ID, 'configure_2FA' );
			delete_user_meta( $user->ID, 'mo2f_2FA_method_to_test' );
			delete_user_meta( $user->ID, 'mo2f_phone' );
			delete_user_meta( $user->ID, 'register_account_popup' );
		}

	}

	function mo2f_delete_mo_options() {
		delete_site_option( 'mo2f_email' );
		delete_site_option( 'mo2f_dbversion' );
		delete_site_option( 'mo2f_host_name' );
		delete_site_option( 'user_phone' );
		delete_site_option( 'mo2f_miniorange_admin');
		delete_site_option( 'mo2f_api_key' );
		delete_site_option( 'mo2f_customer_token' );
		delete_site_option( 'mo_2factor_admin_registration_status' );
		delete_site_option( 'mo2f_number_of_transactions' );
		delete_site_option( 'mo2f_set_transactions' );
		delete_site_option( 'mo2f_show_sms_transaction_message' );
		delete_site_option( 'mo_app_password' );
		delete_site_option( 'mo2f_login_policy' );
		delete_site_option( 'mo2f_remember_device' );
		delete_site_option( 'mo2f_enable_forgotphone' );
		delete_site_option( 'mo2f_enable_login_with_2nd_factor' );
		delete_site_option( 'mo2f_enable_xmlrpc' );
		delete_site_option( 'mo2f_register_with_another_email' );
		delete_site_option( 'mo2f_proxy_host' );
		delete_site_option( 'mo2f_port_number' );
		delete_site_option( 'mo2f_proxy_username' );
		delete_site_option( 'mo2f_proxy_password' );
		delete_site_option( 'mo2f_customer_selected_plan' );
		delete_site_option( 'mo2f_ns_whitelist_ip' );
		delete_site_option( 'mo2f_enable_brute_force' );
		delete_site_option( 'mo2f_show_remaining_attempts' );
		delete_site_option( 'mo2f_ns_blocked_ip' );
		delete_site_option( 'mo2f_allwed_login_attempts' );
		delete_site_option( 'mo2f_time_of_blocking_type' );
		delete_site_option( 'mo2f_network_features' );
		
	}

	function mo_auth_show_success_message() {
		do_action('wpns_show_message', get_site_option( 'mo2f_message' ), 'SUCCESS');
		
	}

	function mo2f_create_customer( $user ) {
		global $Mo2fdbQueries;
		delete_user_meta( $user->ID, 'mo2f_sms_otp_count' );
		delete_user_meta( $user->ID, 'mo2f_email_otp_count' );
		$customer    = new Customer_Setup();
		$customerKey = json_decode( $customer->create_customer(), true );

		if ( $customerKey['status'] == 'ERROR' ) {
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $customerKey['message'] ) );
			$this->mo_auth_show_error_message();
		} else {
			if ( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS' ) == 0 ) {    //admin already exists in miniOrange
				$content     = $customer->get_customer_key();
				$customerKey = json_decode( $content, true );

				if ( json_last_error() == JSON_ERROR_NONE ) {
					if ( array_key_exists( "status", $customerKey ) && $customerKey['status'] == 'ERROR' ) {
						update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( $customerKey['message'] ) );
						$this->mo_auth_show_error_message();
					} else {
						if ( isset( $customerKey['id'] ) && ! empty( $customerKey['id'] ) ) {
							update_site_option( 'mo2f_customerKey', $customerKey['id'] );
							update_site_option( 'mo2f_api_key', $customerKey['apiKey'] );
							update_site_option( 'mo2f_customer_token', $customerKey['token'] );
							update_site_option( 'mo2f_app_secret', $customerKey['appSecret'] );
							update_site_option( 'mo2f_miniorange_admin', $user->ID );
							delete_site_option( 'mo2f_password' );
							$email = get_site_option( 'mo2f_email' );
							$Mo2fdbQueries->update_user_details( $user->ID, array(
								'mo2f_EmailVerification_config_status' => true,
								'user_registration_with_miniorange'    => 'SUCCESS',
								'mo2f_user_email'                      => $email
							) );
							$mo_2factor_user_registration_status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
							update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
							$enduser = new Two_Factor_Setup();
							$enduser->mo2f_update_userinfo( $email, 'OUT OF BAND EMAIL', null, 'API_2FA', true );
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ACCOUNT_RETRIEVED_SUCCESSFULLY" ) . ' <b>' . Mo2fConstants:: langTranslate( "EMAIL_VERFI" ) . '</b> ' . Mo2fConstants:: langTranslate( "DEFAULT_2ND_FACTOR" ) . ' <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >' . Mo2fConstants:: langTranslate( "CLICK_HERE" ) . '</a> ' . Mo2fConstants:: langTranslate( "CONFIGURE_2FA" ) );
							$this->mo_auth_show_success_message();
						} else {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_CREATE_ACC_OTP" ) );
							$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
							$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
							$this->mo_auth_show_error_message();
						}

					}

				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_EMAIL_OR_PASSWORD" ) );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_VERIFY_CUSTOMER';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );

					$this->mo_auth_show_error_message();
				}


			} else {
				if ( isset( $customerKey['id'] ) && ! empty( $customerKey['id'] ) ) {
					update_site_option( 'mo2f_customerKey', $customerKey['id'] );
					update_site_option( 'mo2f_api_key', $customerKey['apiKey'] );
					update_site_option( 'mo2f_customer_token', $customerKey['token'] );
					update_site_option( 'mo2f_app_secret', $customerKey['appSecret'] );
					update_site_option( 'mo2f_miniorange_admin', $user->ID );
					delete_site_option( 'mo2f_password' );

					$email = get_site_option( 'mo2f_email' );

					update_site_option( 'mo2f_is_NC', 1 );
					update_site_option( 'mo2f_is_NNC', 1 );

					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ACCOUNT_CREATED" ) );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_PLUGIN_SETTINGS';
					$Mo2fdbQueries->update_user_details( $user->ID, array(
						'mo2f_2factor_enable_2fa_byusers'     => 1,
						'user_registration_with_miniorange'   => 'SUCCESS',
						'mo2f_configured_2FA_method'          => 'NONE',
						'mo2f_user_email'                     => $email,
						'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status
					) );

				   update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );

					$enduser = new Two_Factor_Setup();
					$enduser->mo2f_update_userinfo( $email, 'NONE', null, 'API_2FA', true );

					$this->mo_auth_show_success_message();

					$mo2f_customer_selected_plan = get_site_option( 'mo2f_customer_selected_plan' );
					if ( ! empty( $mo2f_customer_selected_plan ) ) {
						delete_site_option( 'mo2f_customer_selected_plan' );
						header( 'Location: admin.php?page=mo_2fa_upgrade' );
					} else {
						header( 'Location: admin.php?page=mo_2fa_two_fa' );
					}

				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_CREATE_ACC_OTP" ) );
					$mo_2factor_user_registration_status = 'MO_2_FACTOR_OTP_DELIVERED_FAILURE';
					$Mo2fdbQueries->update_user_details( $user->ID, array( 'mo_2factor_user_registration_status' => $mo_2factor_user_registration_status ) );
					$this->mo_auth_show_error_message();
				}


			}
		}
	}

	public static function mo2f_get_GA_parameters($user){
		global $Mo2fdbQueries;
		$email           = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
		$google_auth     = new Miniorange_Rba_Attributes();
		$gauth_name= get_site_option('mo2f_google_appname');
		$gauth_name = $gauth_name ? $gauth_name : 'miniOrangeAuth';
		$google_response = json_decode( $google_auth->mo2f_google_auth_service( $email,$gauth_name ), true );
		if ( json_last_error() == JSON_ERROR_NONE ) {
			if ( $google_response['status'] == 'SUCCESS' ) {
				$mo2f_google_auth              = array();
				$mo2f_google_auth['ga_qrCode'] = $google_response['qrCodeData'];
				$mo2f_google_auth['ga_secret'] = $google_response['secret'];
				$_SESSION['mo2f_google_auth']  = $mo2f_google_auth;
			}else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
				do_action('mo_auth_show_error_message');
			}
		}else {
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
			do_action('mo_auth_show_error_message');

		}
	}

	function mo_auth_show_error_message() {
		do_action('wpns_show_message', get_site_option( 'mo2f_message' ), 'ERROR');
		
	}

	function mo2f_create_user( $user, $email ) {
		global $Mo2fdbQueries;
		$email      = strtolower( $email );
		$enduser    = new Two_Factor_Setup();
		$check_user = json_decode( $enduser->mo_check_user_already_exist( $email ), true );

		if ( json_last_error() == JSON_ERROR_NONE ) {
			if ( $check_user['status'] == 'ERROR' ) {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $check_user['message'] ) );
				$this->mo_auth_show_error_message();
			} else {
				if ( strcasecmp( $check_user['status'], 'USER_FOUND' ) == 0 ) {

					$Mo2fdbQueries->update_user_details( $user->ID, array(
						'user_registration_with_miniorange'   => 'SUCCESS',
						'mo2f_user_email'                     => $email,
						'mo2f_configured_2FA_method'          => 'NONE',
						'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS'
					) );


					delete_user_meta( $user->ID, 'user_email' );
					$enduser->mo2f_update_userinfo( $email, 'NONE', null, 'API_2FA', true );
					$message = Mo2fConstants:: langTranslate( "REGISTRATION_SUCCESS" );
					update_site_option( 'mo2f_message', $message );
					$this->mo_auth_show_success_message();
					header( 'Location: admin.php?page=mo_2fa_two_fa' );

				} else if ( strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) == 0 ) {
					$content = json_decode( $enduser->mo_create_user( $user, $email ), true );
					if ( json_last_error() == JSON_ERROR_NONE ) {
						if ( $content['status'] == 'ERROR' ) {
							update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $content['message'] ) );
							$this->mo_auth_show_error_message();
						} else {
							if ( strcasecmp( $content['status'], 'SUCCESS' ) == 0 ) {
								delete_user_meta( $user->ID, 'user_email' );
								$Mo2fdbQueries->update_user_details( $user->ID, array(
									'user_registration_with_miniorange'   => 'SUCCESS',
									'mo2f_user_email'                     => $email,
									'mo2f_configured_2FA_method'          => 'NONE',
									'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS'
								) );
								$enduser->mo2f_update_userinfo( $email, 'NONE', null, 'API_2FA', true );
								$message = Mo2fConstants:: langTranslate( "REGISTRATION_SUCCESS" );
								update_site_option( 'mo2f_message', $message );
								$this->mo_auth_show_success_message();
								header( 'Location: admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mobile_configure' );

							} else {
								update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
								$this->mo_auth_show_error_message();
							}
						}
					} else {
						update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
						$this->mo_auth_show_error_message();
					}
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
					$this->mo_auth_show_error_message();
				}
			}
		} else {
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_USER_REGISTRATION" ) );
			$this->mo_auth_show_error_message();
		}
	}

	function mo2f_get_qr_code_for_mobile( $email, $id ) {

		$registerMobile = new Two_Factor_Setup();
		$content        = $registerMobile->register_mobile( $email );
		$response       = json_decode( $content, true );
		if ( json_last_error() == JSON_ERROR_NONE ) {
			if ( $response['status'] == 'ERROR' ) {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $response['message'] ) );
				$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
				MO2f_Utility::unset_session_variables( $session_variables );
				delete_site_option( 'mo2f_transactionId' );
				$this->mo_auth_show_error_message();

			} else {
				if ( $response['status'] == 'IN_PROGRESS' ) {
					update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( "SCAN_QR_CODE" ) );
					$_SESSION['mo2f_qrCode']        = $response['qrCode'];
					$_SESSION['mo2f_transactionId'] = $response['txId'];
					update_site_option( 'mo2f_transactionId', $response['txId'] );
					$_SESSION['mo2f_show_qr_code'] = 'MO_2_FACTOR_SHOW_QR_CODE';
					$this->mo_auth_show_success_message();
				} else {
					update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
					$session_variables = array( 'mo2f_qrCode', 'mo2f_transactionId', 'mo2f_show_qr_code' );
					MO2f_Utility::unset_session_variables( $session_variables );
					delete_site_option( 'mo2f_transactionId' );
					$this->mo_auth_show_error_message();
				}
			}
		}
	}

	function mo2f_save_2_factor_method( $user, $mo2f_configured_2FA_method ) {
		global $Mo2fdbQueries;
		$email          = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
		$enduser        = new Two_Factor_Setup();
		$phone          = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
		$current_method = MO2f_Utility::mo2f_decode_2_factor( $mo2f_configured_2FA_method, "server" );

		$response = json_decode( $enduser->mo2f_update_userinfo( $email, $current_method, $phone, null, null ), true );

		if ( json_last_error() == JSON_ERROR_NONE ) {
			if ( $response['status'] == 'ERROR' ) {
				update_site_option( 'mo2f_message', Mo2fConstants::langTranslate( $response['message'] ) );
				$this->mo_auth_show_error_message();
			} else if ( $response['status'] == 'SUCCESS' ) {
				$configured_2fa_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );

				if ( in_array( $configured_2fa_method, array( "Google Authenticator", "Authy Authenticator" ) ) ) {
					update_user_meta( $user->ID, 'mo2f_external_app_type', $configured_2fa_method );
				}
				$Mo2fdbQueries->update_user_details( $user->ID, array(
					'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS'
				) );
				delete_user_meta( $user->ID, 'configure_2FA' );
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( $configured_2fa_method ) . ' ' . Mo2fConstants:: langTranslate( "SET_2FA" ) );

				$this->mo_auth_show_success_message();
			} else {
				update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ERROR_DURING_PROCESS" ) );
				$this->mo_auth_show_error_message();
			}
		} else {
			update_site_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
			$this->mo_auth_show_error_message();
		}
	}

}

function mo2f_is_customer_registered() {
	$email       = get_site_option( 'mo2f_email' );
	$customerKey = get_site_option( 'mo2f_customerKey' );
	if ( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
		return 0;
	} else {
		return 1;
	}
}
new Miniorange_Authentication;
?>