<?php
$setup_dirName = dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'twofa'.DIRECTORY_SEPARATOR.'link_tracer.php';
include $setup_dirName;

?>

<?php 

	function miniorange_2_factor_user_roles($current_user) { 

		global $wp_roles;
		if (!isset($wp_roles))
			$wp_roles = new WP_Roles();
		
		    print '<div><span style="font-size:16px;">Roles<div style="float:right;">Custom Redirect Login Url <b style = "color:red"> [PREMIUM] </b> </div></span><br /><br />';
		    foreach($wp_roles->role_names as $id => $name) {	
			     $setting = get_site_option('mo2fa_'.$id);
?>
			     <div>
                     <input type="checkbox" name="role" value="<?php echo 'mo2fa_'.esc_attr($id); ?>" 
    				 <?php 
                        if($id=='administrator'){ 					     
    					    if(get_site_option('mo2fa_administrator'))
                                echo 'checked' ;
                            else{
                                echo 'unchecked';
                                } 
    						} 
    					else{ 
    					   echo 'disabled' ; 
    					} 
                    ?>/>
                    <?php
                        echo esc_attr($name);
                        if($name != 'Administrator')
                            echo " <b style='color:red;padding-left:10px;'> [PREMIUM] </b>"; 
                    ?>
    			     <input type="text" class="mo2f_table_textbox" style="width:50% !important;float:right;" id="<?php echo esc_attr('mo2fa_'.$id); ?>_login_url" value="<?php echo esc_attr( get_site_option('mo2fa_' .$id . '_login_url')); ?>" 
    			     <?php
                        echo 'disabled' ;  
                     ?>
                     />
			     </div> 
			     <br/>
		<?php
		      }
		     print '</div>';
	}
            $user = wp_get_current_user();
            $configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
            $configured_meth = array();
            $configured_meth = array('Email Verification','Google Authenticator','Security Questions','Authy Authenticator');
            $method_exisits = in_array($configured_2FA_method, $configured_meth);
        ?>
      <?php
            if(get_site_option("is_onprem")== 1 && current_user_can('administrator'))
            {
        ?>
                <div class="mo2f_table_layout" id="2fa_method">
                    <input type="hidden" name="option" value="" />
                    <span>
                            <h3>Select Roles to enable 2-Factor for Users <b  style="font-size: 70%;color: red;">(Upto 3 users in Free version)</b></h3>
                        <span>

                            <hr> <a href= '<?php echo esc_attr($two_factor_premium_doc['Custom Redirect Login Url']);?>' target="_blank">
                        <span class="dashicons dashicons-text-page" title="More Information" style="font-size:19px;color:#4a47a3; margin-top:0.9em ;float: right;"></span>
                        </a>


                            <br>

        <?php
                           echo miniorange_2_factor_user_roles($current_user); 
        ?>
                            <br>
                        </span>
                        <input type="submit" id="save_role_2FA"  name="submit" value="Save Settings" class="mo_wpns_button mo_wpns_button1" />
                    </span>
                    <br><br>
                    <div id="mo2f_note">
                        <b>Note:</b> Selecting the above roles will enable 2-Factor for all users associated with that role.
                    </div>
                </div>


    <script>
        jQuery("#save_role_2FA").click(function(){
            var enabledrole = [];
            $.each($("input[name='role']:checked"), function(){            
            enabledrole.push($(this).val());
            });
            var mo2fa_administrator_login_url   =   $('#mo2fa_administrator_login_url').val();
            var nonce = '<?php echo wp_create_nonce("unlimittedUserNonce");?>';
            var data =  {
            'action'                        : 'mo_two_factor_ajax',
            'mo_2f_two_factor_ajax'         : 'mo2f_role_based_2_factor',
            'nonce'                         :  nonce,
            'enabledrole'                   :  enabledrole,                    
            'mo2fa_administrator_login_url' :  mo2fa_administrator_login_url
              };
            jQuery.post(ajaxurl, data, function(response) {
                var response = response.replace(/\s+/g,' ').trim();
                if (response == "true"){
                    jQuery('#mo_scan_message').empty();
                    jQuery('#mo_scan_message').append("<div id='notice_div' class='overlay_success'><div class='popup_text'>&nbsp&nbsp Settings are saved.</div></div>");
                    window.onload =  nav_popup();
                }
            });
        });
    </script>

    <?php
            }
    if(get_site_option("is_onprem")== 0 && current_user_can('administrator')){
	?>
        <div id="wpns_message" >
        </div>
        <div class="mo2f_table_layout" id="onpremisediv">
            <p class="modal-body-para" style="text-align: center;">
                <b>Two-Factor Authentication for Multiple Users<span style="color: red;"> [No Payment Needed]</span></b>
            </p>
            <hr>
            <p class="modal-body-para">
            <span  style="font-size: 15px;">
                <b>Current Solution</b>
            </span>
            <ul style="list-style-type:disc; padding-left: 5%;">
                <li style="font-size: 15px;">You are currently using a Cloud Solution for 2-factor Authentication</li>
                <li style="font-size: 15px;">In this solution miniOrange provides you 2-factor authentication free only for one user.</li>
            </ul>
            <br>
            <span  style="font-size: 15px;">
                <b>2FA For Multiple User</b>
            </span>
            <ul style="list-style-type:disc; padding-left: 5%;">
                <li style="font-size: 15px;">If you want to use 2-factor authentication for multiple users, you need to enable the Wordpress Solution [On-Premise 2-factor Authentication].</li>
                <li  style="font-size: 15px;">You can get two-factor authentication <b>FREE</b> for <u>upto 3 Administrators</u>.</li> 
                <li  style="font-size: 15px;">By clicking the button below all dependecies will be shifted to wordpress [On-Premise Solution] and there will be no inclusion of any 3rd party not even miniOrange so this will increase the process speed for authentication.</li>
            </ul>
            <br>
            <span  style="font-size: 15px;color: red;">
                <b>Not Supported in Wordpress Solution [On-Premise Solution]</b>
            </span>
            <ul style="list-style-type:disc; padding-left: 5%;">
                <li style="font-size: 15px;"><b>2FA Methods</b></li>
            </ul>
            <div style="padding-left: 10%;">
                <ul  style="font-size: 15px; list-style-type:circle;">
                <?php
                    if (get_site_option('mo2f_is_NC') == 0) {
                ?>
                        <li>OTP Over SMS</li>
                <?php
                    } 
                ?>
                    <li>miniOrange QR Code Authentication</li>
                    <li>miniOrange Soft Token</li>
                    <li>miniOrange Push Notification</li>
                </ul>
        </div>
        <ul style="list-style-type:disc; padding-left: 5%;">
            <li style="font-size: 15px;"><b>Remember Device</b></li>
            <li style="font-size: 15px;"><b>XML-RPC Login</b></li>
        </ul>
        </p>
        <strong style="color: #ff0000">[Note]: By enabling this you will have to reconfigure the second factor and all configuration of previous account will be deleted.</strong>
        <p class="modal-body-para" style="font">
            <h2  style="text-align: center;"> Enable Two-Factor for all Users
            <label class='mo_wpns_switch' >
            <input type="checkbox" name="unlimittedUser" id="unlimittedUser"/>
            <span class='mo_wpns_slider mo_wpns_round'></span>
            </label>
            </h2>
            <hr>
            <p><i class="mo_wpns_not_bold"><h4> <strong style="color: #ff0000">[WARNING]: </strong> This will disconfigure the two-factor for the current account and you need to configure it again. By enabling it you will not be able to use the cloud solution again.</h4> </i></p>
            </p>


<?php
?>
</div>
<div id="ConfirmOnPrem" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" style="text-align: center; font-size: 20px; color: #ff0000">WARNING</h3>
                    <p class="modal-body-para">
                    	<?php if($method_exisits && $configured_2FA_method != '' ){
                                    if ($configured_2FA_method=='Email Verification') {
                                        ?>
                                        <div style="text-align: center; font-size: 130%;">
                                            Current 2FA method:- <b><?php echo esc_attr($configured_2FA_method) ?></b>
                                            <hr>
                                            <ul style="list-style-type:circle;font-size: 14px">
                                                <li style="text-align: left;">This 2FA method is available in Wordpress Solution.</li>
                                            </ul>
                                            
                                        </div>
                                        <?php
                                    }
                                    elseif ($configured_2FA_method == 'Authy Authenticator') 
                                    {
                                        ?>
                                        Current 2FA method:- <b><?php echo esc_attr($configured_2FA_method) ?></b>
                                        <hr>
                                        <ul style="list-style-type:circle;font-size: 14px;text-align: left;">
                                            <li>Authy Authenticator and Google Authenticator are same in the wordpress Solution.</li>
                                            <li>You will need to reconfigure it if you want to proceed with Wordpress Solution.</li>
                                        </ul>
                                        <?php
                                    }
                                    else
                                    {
                        				?>
                        				Current 2FA method:- <b><?php echo esc_attr($configured_2FA_method) ?></b>
                                        <hr> 
                                        <ul style="list-style-type:circle;font-size: 14px;text-align: left;">
                        				    <li>You will need to reconfigure it if you want to proceed with Wordpress Solution.</li>
                                        </ul>
                        				<?php
                                    }
                                   
                    			} 
                    			else if($configured_2FA_method != ''){
                    				?>
                    				Current 2FA method:- <b><?php echo esc_attr($configured_2FA_method) ?></b>
                    				<hr>
                                    <p>
                                    <ul style="list-style-type:circle;font-size: 14px;text-align: left;">
                    				    <li>This method is <b> not supported </b> in Wordpress Solution[On-Premise Solution]</li>
                    				    <br>
                    				    <li><b>You can still use other 2FA methods for multiple users by clicking on confirm.</b> </li>
                    				<?php
                    			}
                    			else{
                    				?>
                    				We support only the following 2-Factor Authentication methods in Wordpress Solution.
                    				<br>
                    				<li>Google Authentication</li>
                    				<li>Security Questions</li>
                    				<?php if(get_site_option('mo2f_is_NC') == 0){ ?>
                    					<li>Email Verification</li>
                    				<?php }
                    			}
                    ?>
                </p>
                <span id="closeConfirmOnPrem" class="modal-span-close">X</span>
                </div>
                <div class="modal-body_multi_user" style="height: auto">
                     
                </div>
                <div class="modal-footer">
                    <button type="button" class="mo_wpns_button mo_wpns_button1 modal-button" style="width: 40%;" id="ConfirmOnPremButton">Confirm</button>
               
                </div>
            </div>
    </div>

    <div id="afterMigrate" class="modal" style="display: none;"  fixed>
        <div  class="modal-content" style="width: 80%;overflow: hidden;" >

        <div class="modal-header">
            <h3 class="modal-title" style="text-align: center; font-size: 20px; color: #2980b9">
            Select a method to set as your 2nd factor.  
            </h3>
        </div>

        <div class="modal-body_multi_user" fixed>
            <?php
                $user = wp_get_current_user();
                $configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
                $configured_meth = array();
                if(get_site_option('mo2f_is_NC') == 0) 
                {
                $configured_meth = array('Email Verification','Google Authenticator','Security Questions','Authy Authenticator');
                }
                else
                {
                    $configured_meth = array('Google Authenticator','Security Questions','Authy Authenticator');
                }
                $method_exisits = in_array($configured_2FA_method, $configured_meth);
            ?>
            <p class="modal-body-para">
            <?php
        if($method_exisits){
        ?>
        <p class="modal-body-para">
        Your Current 2FA method : <b> <?php echo esc_attr ($configured_2FA_method) ?></b>

            <p class="modal-body-para" style="font-size: 12px;color:#FF0000;padding-top: -5px;" >
                <?php 
                        if ($configured_2FA_method == 'Email Verification') {?>
                            <b>Please Reconfigure your Email ID.</b>

                       <?php }
                       else
                       {
                        ?>
                            <b>In order to continue using <?php echo esc_attr($configured_2FA_method) ?> as your 2nd factor for authentication, You will need to reconfigure it
                            </b>
                        <?php
                       }
                ?>    
            </p>
            <hr>


            <div id="reconfig">
                <?php if($configured_2FA_method == 'Google Authenticator'){
                echo '

                <button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="reconfigGA()" >Click here to Reconfigure <b style="font-weight: 700;">Google/Authy/LassPass Authenticator</b> </button>
                ';
                }
                else if($configured_2FA_method == 'Email Verification'){
                    $email = $user->user_email;
              
                echo "<div>
                <input type ='email' id='emalEntered' name='emalEntered' size= '50' required value=".sanitize_text_field($email).">";

                echo '<span style="display:inline;"><input type="submit" id="save_email" name="" class="mo_wpns_button mo_wpns_button1" value="Save Email"></span></div>';


                }
                else if($configured_2FA_method == 'Security Questions'){
                echo '
                <button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="reconfigKBA()" >Click here to Reconfigure <b style="font-weight: 700;">Security Questions</b> </button>
                ';
                }

                ?>

            </div>
        </p>
        <div id="reconfigTable">
            <p class="modal-body-para">
            The following are the other 2-Factor Authentication methods that are available in the Wordpress[On-Premise] version.
            </p>
            <div>
                <?php
                foreach($configured_meth as $value){
                    if($value != $configured_2FA_method ){
                        if($value == 'Security Questions'){
                        echo '
                        <button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="reconfigKBA()" >Click here to Configure <b style="font-weight: 700;">Security Questions</b> </button>
                        ';
                        }
                        else if($value == 'Email Verification' ){
                        echo '<button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="emailVerification()" >Click here to Configure <b style="font-weight: 700;">Email Verification</b> </button>';
                        }
                        else if($value == 'Google Authenticator'){
                        echo '<button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="reconfigGA()" >Click here to Configure <b style="font-weight: 700;">Google/Authy/LassPass Authenticator </b></button>';
                        }
                    }
                    echo "<br>";
                }

                ?>
            </div>
        </div>
            <center>
                <table id="Emailreconfig" style="display: none;" >
                    <tr>
                        <td>
                        <b>Enter Your email that you will use as your 2nd factor.</b>
                        </td>
                    </tr>

                    <tr>
                        <td>
                        <input type="text" name="" value="" id="emalEntered" />
                        </td>
                    </tr>

                    <tr>
                        <td>
                        <input type="submit" id="save_email" name="" class="mo_wpns_button mo_wpns_button1" value="Save Email">

                        <input type="button" id="emailBack" value="Back" class="mo_wpns_button mo_wpns_button1" />
                        </td>
                    </tr>
                </table>
            </center>
            <?php
        }

else{
    ?>
    


    
    <div class="modal-body_multi_user" fixed>
    <p class="modal-body-para">
    <?php 
    	if($configured_2FA_method != ''){?>	
    		Your Current 2FA method : <b> <?php echo esc_attr($configured_2FA_method) ?></b>
    <p class="modal-body-para" style="font-size: 12px;color:#FF0000;padding-top: -5px;" >
    <b>
    <?php echo esc_attr($configured_2FA_method) ?> is not supported for Multiple users, please choose some other method as your 2 factor.
    </b>
    </p>
    <hr>
    							<?php }
    								  else{
    								  	echo "";
    								
    								  }

    							 ?>
    <div id="msg">
	<p class="modal-body-para">
	The following 2-Factor Authentication methods are available in the Wordpress[On-Premise] version.
	</p>
	<?php 
		echo '

    <button class="mo_wpns_button mo_wpns_button1" id="google_auth" style="width:100%;" onclick ="reconfigGA()" >Click here to Configure <b style="font-weight: 700;">Google/Authy/LassPass Authenticator</b> </button>
    ';
    echo "<br>";
    if(get_site_option('mo2f_is_NC') == 0)
    { ?>
        <button class="mo_wpns_button mo_wpns_button1" style="width:100%;" onclick ="emailVerification()" >Click here to Configure <b style="font-weight: 700;">Email Verification</b> </button>
                                    <?php }
    ?>
     
    <?php
    echo "<br>";
    echo '
    <button class="mo_wpns_button mo_wpns_button1" id="secu_que" style="width:100%;" onclick ="reconfigKBA()" >Click here to Configure <b style="font-weight: 700;">Security Questions</b> </button>
    ';
    ?>
    </div>
    <center>
        <table id="Emailreconfig" style="display: none;">
            <tr>
                <td>
                <b>Enter Your email that you will use as your 2nd factor.</b>
                </td>
            </tr>

            <tr>
                <td>
                <input type="text" name="" value="" id="emalEntered" />
                </td>
            </tr>

            <tr>
                <td>
                <input type="submit" id="save_email" name="" class="mo_wpns_button mo_wpns_button1" value="Save Email">

                <input type="button" id="emailBack" value="Back" class="mo_wpns_button mo_wpns_button1" />
                </td>
            </tr>
        </table>
    </center>
    </div>


<?php }
?>
 
    </p>
    </div>
   </div>
    </div>

<script type="text/javascript">

function reconfigKBA(){
            var data = {
                'action'                    : 'mo_two_factor_ajax',
                'mo_2f_two_factor_ajax'     : 'mo2f_shift_to_onprem',
            };
            jQuery.post(ajaxurl, data, function(response) {

                if(response == 'true'){

                    jQuery('#mo2f_configured_2FA_method_free_plan').val('SecurityQuestions');
                    jQuery('#mo2f_selected_action_free_plan').val('configure2factor');
                    jQuery('#mo2f_save_free_plan_auth_methods_form').submit();
                    openTab2fa(setup_2fa);
                }
            });
        }
function reconfigGA(){

            var data = {
                'action'                    : 'mo_two_factor_ajax',
                'mo_2f_two_factor_ajax'     : 'mo2f_shift_to_onprem',
            };
            jQuery.post(ajaxurl, data, function(response) {

                if(response == 'true'){
                    jQuery('#mo2f_configured_2FA_method_free_plan').val('GoogleAuthenticator');
                    jQuery('#mo2f_selected_action_free_plan').val('configure2factor');
                    jQuery('#mo2f_save_free_plan_auth_methods_form').submit();
                    openTab2fa(setup_2fa);
                }
            });
        }

function emailVerification(){
jQuery('#reconfigTable').hide();
jQuery('#Emailreconfig').show();
jQuery('#reconfig').hide();
jQuery('#msg').hide();
}
</script>

<script type="text/javascript">
jQuery('#closeConfirmOnPrem').click(function(){
                document.getElementById('unlimittedUser').checked = false;
                //close_modal();
                window.location.reload();
        });
jQuery('#ConfirmOnPremButton').click(function(){
jQuery('#ConfirmOnPrem').hide();
var enableOnPremise = jQuery("input[name='unlimittedUser']:checked").val();
var nonce = '<?php echo wp_create_nonce("unlimittedUserNonce");?>';
var data = {
			'action'					: 'mo_two_factor_ajax',
			'mo_2f_two_factor_ajax' 	: 'mo2f_unlimitted_user',
'nonce' :  nonce,
'enableOnPremise' :  enableOnPremise
};
jQuery.post(ajaxurl, data, function(response) {
var response = response.replace(/\s+/g,' ').trim();
if(response =='OnPremiseActive')
{
jQuery('#wpns_message').empty();
jQuery('#wpns_message').append("<div class= 'notice notice-success is-dismissible' style='height : 25px;padding-top: 10px;  '> Congratulations! Now you can use 2-factor Authentication for your administrators for  free.  ");

jQuery('#onpremisediv').hide();
jQuery('#afterMigrate').show();
}
else if(response =='OnPremiseDeactive')
{
jQuery('#wpns_message').empty();
jQuery('#wpns_message').append("<div class= 'notice notice-success is-dismissible' style='height : 25px;padding-top: 10px;  '> Cloud Solution deactivated");
close_modal();
}
else
{
jQuery('#wpns_message').empty();
jQuery('#wpns_message').append("<div class= 'notice notice-error is-dismissible' style='height : 25px;padding-top: 10px;  '> An Unknown Error has occured. ");
close_modal();
}
});

});

jQuery('#emailBack').click(function(){
jQuery('#reconfigTable').show();
jQuery('#Emailreconfig').hide();
jQuery('#msg').show();
jQuery('#reconfig').show();
});
jQuery('#save_email').click(function(){
var email   = jQuery('#emalEntered').val();
                var nonce   = '<?php echo esc_attr (wp_create_nonce('EmailVerificationSaveNonce'));?>';
                var user_id = '<?php echo get_current_user_id();?>';

                if(email != '')
                {
                var data = {
                    'action'                    : 'mo_two_factor_ajax',
                    'mo_2f_two_factor_ajax'     : 'mo2f_save_email_verification',
                    'nonce'                     : nonce,
                    'email'                     : email,
                    'user_id'                   : user_id
                    };
                jQuery.post(ajaxurl, data, function(response) {    
                            var response = response.replace(/\s+/g,' ').trim();
                            if(response=="settingsSaved")
                            {
                                jQuery('#mo2f_configured_2FA_method_free_plan').val('EmailVerification');
                                jQuery('#mo2f_selected_action_free_plan').val('select2factor');
                                jQuery('#mo2f_save_free_plan_auth_methods_form').submit();  \
                            }
                        });
                }
});
jQuery('#unlimittedUser').click(function(){
jQuery('#ConfirmOnPrem').css('display', 'block');
            jQuery('.modal-content').css('width', '35%');

});

</script>
<script type="text/javascript">
    
</script>

<?php
}
?>
