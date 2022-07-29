<?Php

include dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'mo2fa_common_login.php';
DEFINE('DS', DIRECTORY_SEPARATOR); 

class Miniorange_Mobile_Login {

	function mo2fa_default_login( $user, $username, $password ) {

		global $Mo2fdbQueries;
		$currentuser = wp_authenticate_username_password( $user, $username, $password );
		if ( is_wp_error( $currentuser ) ) {
			return $currentuser;
		} else {
			if(get_site_option('is_onprem') and (!get_site_option('mo2f_login_policy') or get_site_option('mo2f_enable_login_with_2nd_factor')))
			{
				$mo2f_configured_2FA_method = get_user_meta($currentuser->ID,'currentMethod',true);
				$attributes  = isset( $_POST['miniorange_rba_attribures'] ) ? sanitize_text_field($_POST['miniorange_rba_attribures']) : null;
				$session_id  = isset( $_POST['miniorange_user_session'] ) ? sanitize_text_field($_POST['miniorange_user_session']) : null;
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field($_REQUEST['redirect_to']) : null;
				$handleSecondFactor = new Miniorange_Password_2Factor_Login();
				if(is_null($session_id)) {
					$session_id	= $handleSecondFactor->create_session();
				}

				$key 		= get_site_option('mo2f_customer_token');
				$otp_token 	= '';
				$error=$handleSecondFactor->miniorange_initiate_2nd_factor( $currentuser, $attributes, $redirect_to, $otp_token, $session_id );

			}
			$this->miniorange_login_start_session();
			$pass2fa_login_session       = new Miniorange_Password_2Factor_Login();
			$session_id=$pass2fa_login_session->create_session();
			$mo2f_configured_2FA_method           = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $currentuser->ID );
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field($_REQUEST['redirect_to']) : null;
			if ( $mo2f_configured_2FA_method ) {
				$mo2f_user_email               = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $currentuser->ID );
				$mo2f_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $currentuser->ID );
				if ( $mo2f_user_email && $mo2f_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) { //checking if user has configured any 2nd factor method
					MO2f_Utility::set_user_values( $session_id, "mo2f_login_message", '<strong>ERROR</strong>: Login with password is disabled for you. Please Login using your phone.' );
					$this->mo_auth_show_error_message();
					$this->mo2f_redirectto_wp_login();
					$error = new WP_Error();
					return $error;
				} else { //if user has not configured any 2nd factor method then logged him in without asking 2nd factor
					$this->mo2f_verify_and_authenticate_userlogin( $currentuser, $redirect_to,$session_id );
				}
			} else { //plugin is not activated for non-admin then logged him in
				$this->mo2f_verify_and_authenticate_userlogin( $currentuser, $redirect_to,$session_id );
			}
		}
	}

	public function miniorange_login_start_session() {
		if ( ! session_id() || session_id() == '' || ! isset( $_SESSION ) ) {
			session_start();
		}
	}

	function mo_auth_show_error_message($value = null) {
		remove_filter( 'login_message', array( $this, 'mo_auth_success_message' ) );
		add_filter( 'login_message', array( $this, 'mo_auth_error_message' ) );
	}
	
	function mo2f_redirectto_wp_login() {
		global $Mo2fdbQueries;
		$pass2fa_login_session       = new Miniorange_Password_2Factor_Login();
		$session_id					 = $pass2fa_login_session->create_session();
		remove_action( 'login_enqueue_scripts', array( $this, 'mo_2_factor_hide_login' ) );
		if ( get_site_option( 'mo2f_enable_login_with_2nd_factor' ) ) {
			MO2f_Utility::set_user_values( $session_id, "mo_2factor_login_status", 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED' );
		} else {
			MO2f_Utility::set_user_values( $session_id, "mo_2factor_login_status", 'MO_2_FACTOR_SHOW_USERPASS_LOGIN_FORM' );
		}
	}

	function mo2f_verify_and_authenticate_userlogin( $user, $redirect_to = null, $session_id=null ) {
		$user_id = $user->ID;
		wp_set_current_user( $user_id, $user->user_login );
		$this->remove_current_activity($session_id);
		wp_set_auth_cookie( $user_id, true );
		do_action( 'wp_login', $user->user_login, $user );
		redirect_user_to( $user, $redirect_to );
		exit;
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
		MO2f_Utility::unset_temp_user_details_in_table( null, $session_id, 'destroy');
	}

	function custom_login_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css?version='.MO2F_VERSION.'', __FILE__ );
		$bootstrappath = str_replace('/handler/includes/css', '/includes/css', $bootstrappath);
		wp_enqueue_style( 'bootstrap_script', $bootstrappath );
	}

	function mo_2_factor_hide_login() {
		$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css?version='.MO2F_VERSION.'', __FILE__ );
		$bootstrappath = str_replace('/handler/includes/css', '/includes/css', $bootstrappath);
		
		$hidepath = plugins_url( 'includes/css/hide-login-form.css?version=5.1.21', __FILE__ );
		$hidepath = str_replace('/handler/includes/css', '/includes/css', $hidepath);
		
		wp_register_style( 'hide-login', $hidepath );
		wp_register_style( 'bootstrap', $bootstrappath );
		wp_enqueue_style( 'hide-login' );
		wp_enqueue_style( 'bootstrap' );

	}

	function mo_auth_success_message() {
		$message = isset($_SESSION['mo2f_login_message']) ? sanitize_text_field($_SESSION['mo2f_login_message']) : '';
		
		//if the php session folder has insufficient permissions, cookies to be used
		$message = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_login_message' );
		
		if($message == '')
		{
			$message = 'Please login into your account using password.';	
		}

		return "<div> <p class='message'>" . sanitize_text_field($message). "</p></div>";
	}

	function mo_auth_error_message() {
		$id      = "login_error";
		$message = isset($_SESSION['mo2f_login_message']) ? sanitize_text_field($_SESSION['mo2f_login_message']) : '';
		//if the php session folder has insufficient permissions, cookies to be used
		$message = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo2f_login_message' );
		if($message=='')
		{
			$message = 'Invalid Username';
		}
		if(get_site_option('mo_wpns_activate_recaptcha_for_login'))
		{
			$message = 'Invalid Username or recaptcha';	
		}
		return "<div id='" . sanitize_text_field($id) . "'> <p>" . sanitize_text_field($message) . "</p></div>";
	}

	function mo_auth_show_success_message($message = '') {
		remove_filter( 'login_message', array( $this, 'mo_auth_error_message' ) );
		add_filter( 'login_message', array( $this, 'mo_auth_success_message' ) );
	}

	function miniorange_login_form_fields( $mo2fa_login_status = null, $mo2fa_login_message = null ) {


		if (! get_site_option( 'mo2f_login_policy' )) { //login with phone overwrite default login  

			//if the php session folder has insufficient permissions, cookies to be used
			$login_status_phone_enable = MO2f_Utility::mo2f_retrieve_user_temp_values( 'mo_2factor_login_status' );
			
			if(get_site_option('is_onprem'))
			{
				$userName = isset($_POST['mo2fa_username']) ? sanitize_text_field($_POST['mo2fa_username']) : '';
				if(!empty($userName))
				{
					$user 	  		= get_user_by('login',$userName);
					if($user)
					{	
						$currentMethod 	= get_user_meta($user->ID, 'currentMethod', true);
						if($currentMethod == 'None' or $currentMethod == '')
							$login_status_phone_enable = 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED';
					}
				}
			}
			
			if(get_site_option('mo2f_show_loginwith_phone')){ //login with phone overwrite default login form
				if(isset( $_POST['miniorange_login_nonce'] ) && wp_verify_nonce( $_POST['miniorange_login_nonce'], 'miniorange-2-factor-login-nonce' )){
					$userName = isset($_POST['mo2fa_username']) ? sanitize_text_field($_POST['mo2fa_username']) : '';
					$current_user 	  		= get_user_by('login',$userName);
					$this->mo_2_factor_show_login_with_password_when_phonelogin_enabled();
					$this->mo_2_factor_show_wp_login_form_when_phonelogin_enabled();
					$mo2f_user_login = is_null($current_user) ? null : $current_user->user_login;
					?><script>
						jQuery('#user_login').val(<?php echo "'" . sanitize_text_field($mo2f_user_login) . "'";?>);
						</script><?php
					}else{
						$this->mo_2_factor_show_login();
						$this->mo_2_factor_show_wp_login_form();
					}
				}	 
		else { //Login with phone is alogin with default login form	
			$this->mo_2_factor_show_wp_login_form();
		}

	}
}

function mo_2_factor_show_login_with_password_when_phonelogin_enabled() {
	wp_register_style( 'show-login', plugins_url( 'includes/css/show-login.css?version=5.1.21', __FILE__ ) );
	wp_enqueue_style( 'show-login' );
}


	// login form fields

function mo_2_factor_show_wp_login_form_when_phonelogin_enabled() {
	?>
	<script>
		var content = ' <a href="javascript:void(0)" id="backto_mo" onClick="mo2fa_backtomologin()" style="float:right">← Back</a>';
		jQuery('#login').append(content);

		function mo2fa_backtomologin() {
			jQuery('#mo2f_backto_mo_loginform').submit();
		}
	</script>
	<?php
}

function mo_2_factor_show_login() {
	$hidepath=plugins_url('miniorange-login-security'.DS. 'includes'.DS.'css'.DS.'hide-login-form.css?version=5.1.21','');
	$showpath=plugins_url('miniorange-login-security'.DS.'includes'.DS.'css'.DS.'show-login.css?version=5.1.21','');
	if ( !get_site_option( 'mo2f_login_policy' ) ) {
		wp_register_style( 'show-login', $hidepath );
	} else {
		wp_register_style( 'show-login', $showpath );
	}
	wp_enqueue_style( 'show-login' );
}

function mo_2_factor_show_wp_login_form() {
	$mo2f_enable_login_with_2nd_factor = get_site_option( 'mo2f_login_policy' );
	?>
	<div class="mo2f-login-container">
		<?php if (! $mo2f_enable_login_with_2nd_factor ) { ?>
			<div style="position: relative" class="or-container">
				<h2>OR</h2>    
			</div>
		<?php } ?>
		
		<br>
		<div class="mo2f-button-container" id="mo2f_button_container">
			<input type="text" name="mo2fa_usernamekey" id="mo2fa_usernamekey" autofocus="true"
			placeholder="<?php echo mo2f_lt( 'Username' ); ?>"/>
			<p>
				<?php
				if(get_site_option('mo_wpns_activate_recaptcha_for_login'))
				{		
					echo "<script src='".MoWpnsConstants::RECAPTCHA_URL."'></script>";
					echo '<div class="g-recaptcha" data-sitekey="'.sanitize_text_field (get_site_option("mo_wpns_recaptcha_site_key")).'"></div>';
					echo '<style>#login{ width:349px;padding:2% 0 0; }.g-recaptcha{margin-bottom:5%;}#loginform{padding-bottom:20px;}</style>';
				}

				?>
				<input type="button" name="miniorange_login_submit" style="width:100% !important;"
				onclick="mouserloginsubmit();" id="miniorange_login_submit"
				class="button button-primary button-large"
				value="<?php echo mo2f_lt( 'Login with 2nd factor' ); ?>"/>
			</p>
			<br><br><br>
			<?php if ( ! $mo2f_enable_login_with_2nd_factor ) { ?><br><br><?php } ?>
		</div>
	</div>

	<script>
		jQuery(window).scrollTop(jQuery('#mo2f_button_container').offset().top);

		function mouserloginsubmit() {

            	var username = jQuery('#mo2fa_usernamekey').val();
            	var recap    = jQuery('#g-recaptcha-response').val();
            	
            	document.getElementById("mo2f_show_qrcode_loginform").elements[0].value = username;
            	document.getElementById("mo2f_show_qrcode_loginform").elements[1].value = recap;
            	
            	jQuery('#mo2f_show_qrcode_loginform').submit();

            }

            jQuery('#mo2fa_usernamekey').keypress(function (e) {
                if (e.which == 13) {//Enter key pressed
                	e.preventDefault();
                	var username = jQuery('#mo2fa_usernamekey').val();
                	document.getElementById("mo2f_show_qrcode_loginform").elements[0].value = username;
                	jQuery('#mo2f_show_qrcode_loginform').submit();
                }

            });
        </script>
        <?php
    }

    function miniorange_login_footer_form() {

    	?>
    	<input type="hidden" name="miniorange_login_nonce"
    	value="<?php echo wp_create_nonce( 'miniorange-2-factor-login-nonce' ); ?>"/>
    	<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" hidden>
    		<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
    		value="<?php echo wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ); ?>"/>
    	</form>
    	<form name="f" id="mo2f_show_qrcode_loginform" method="post" action="" hidden>
    		<input type="text" name="mo2fa_username" id="mo2fa_username" hidden/>
    		<input type="text" name="g-recaptcha-response" id = 'g-recaptcha-response' hidden/>
    		<input type="hidden" name="miniorange_login_nonce"
    		value="<?php echo wp_create_nonce( 'miniorange-2-factor-login-nonce' ); ?>"/>
    		
    	</form>
    	<?php

    }
}

?>