<?php
class mo_2f_ajax
{
	function __construct(){

		add_action( 'admin_init'  , array( $this, 'mo_2f_two_factor' ) );
	}

	function mo_2f_two_factor(){ 
		add_action( 'wp_ajax_mo_two_factor_ajax', array($this,'mo_two_factor_ajax') );
	}

		function mo_two_factor_ajax(){
			switch ($_POST['mo_2f_two_factor_ajax']) {
				case 'mo2f_save_email_verification':
					$this->mo2f_save_email_verification();	break;	
				case 'mo2f_unlimitted_user':
					$this->mo2f_unlimitted_user();break;
				case 'CheckEVStatus':
					$this->CheckEVStatus();		break;
				case 'mo2f_role_based_2_factor':
					$this->mo2f_role_based_2_factor();break;
				case 'mo2f_shift_to_onprem':
					$this->mo2f_shift_to_onprem();break;	
				case 'mo2f_dismiss_button':
					$this->mo2f_dismiss_button();break;
			}
		}

		function mo2f_shift_to_onprem(){
		update_site_option('is_onprem', 1);
		wp_send_json('true');
	}

		function mo2f_dismiss_button(){
			update_site_option('donot_show_feedback_message',true);
			return true;
		}
		function mo2f_role_based_2_factor(){
			if ( !wp_verify_nonce($_POST['nonce'],'unlimittedUserNonce') ){
    			   			wp_send_json('ERROR');
    			   			return;
                        }
					    global $wp_roles;
		                if (!isset($wp_roles))
			             $wp_roles = new WP_Roles();
                        foreach($wp_roles->role_names as $id => $name) {
                        	update_site_option('mo2fa_'.$id, 0);
                        }

                        if(isset($_POST['enabledrole'])){
                        $enabledrole = $_POST['enabledrole'];
                         }
                         else{
                         	$enabledrole = array();
                         }
                         foreach($enabledrole as $role){
   							 update_site_option($role, 1);   						
  						}
  						//update_site_option('mo2fa_administrator_login_url',$_POST['mo2fa_administrator_login_url']);
                        wp_send_json('true');
                        return;
		 }
		

		function mo2f_unlimitted_user()
		{	
			if(!wp_verify_nonce($_POST['nonce'],'unlimittedUserNonce'))
			{
				echo "NonceDidNotMatch";
				exit;
			}
			else
			{
				if($_POST['enableOnPremise'] == 'on')
				{
					global $wp_roles;
					if (!isset($wp_roles))
						$wp_roles = new WP_Roles();
					foreach($wp_roles->role_names as $id => $name) {
					add_site_option('mo2fa_'.$id, 1);
						if($id == 'administrator'){
							add_site_option('mo2fa_'.$id.'_login_url',admin_url());
						}else{
							add_site_option('mo2fa_'.$id.'_login_url',home_url());
						}
					}
					echo "OnPremiseActive";
					exit;
				}
				else
				{
					echo "OnPremiseDeactive";
					exit;	
				}
			}
		}
		function mo2f_save_email_verification()
		{
			if(!wp_verify_nonce($_POST['nonce'],'EmailVerificationSaveNonce'))
			{
				echo "NonceDidNotMatch";
				exit;
			}
			else
			{
				$email 		= sanitize_text_field($_POST['email']);
				$error 		= false;
				$user_id 	= sanitize_text_field($_POST['user_id']);

				$onprem = get_site_option('is_onprem');
				if($onprem)
				{
					$twofactor_transactions = new Mo2fDB;
					$exceeded = $twofactor_transactions->check_user_limit_exceeded($user_id);

					if($exceeded){
						echo "USER_LIMIT_EXCEEDED";
						exit;
					}
				}
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
				{
					$error = true;
				}
				if($email!='' && !$error)
				{
					global $Mo2fdbQueries;
					$Mo2fdbQueries->update_user_details(get_current_user_id(),array('mo2f_EmailVerification_config_status'=>true));
					$Mo2fdbQueries->update_user_details(get_current_user_id(),array('mo2f_configured_2FA_method'=>"Email Verification"));
					update_user_meta($user_id,'email',$email);	
					update_site_option('is_onprem' , 1);
					echo "settingsSaved";
					exit;
				}
				else
				{
					echo "invalidEmail";
					exit;
				}

			}
			
		}
		function CheckEVStatus()
		{
			if(isset($_POST['txid']))
			{
				$txid = sanitize_text_field($_POST['txid']);
				$status = get_site_option($_POST['txid']);
				if($status ==1 || $status ==0)
				delete_site_option($_POST['txid']);
				echo $status;
				exit();
			}
			echo "empty txid";
			exit;
		}


}
	
new mo_2f_ajax;
?>