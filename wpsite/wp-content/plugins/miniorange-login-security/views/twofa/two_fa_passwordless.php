<?php 
$current_user = wp_get_current_user();

wp_enqueue_script( 'bootstrap_script', plugins_url('miniorange-login-security'.DIRECTORY_SEPARATOR. 'includes'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'bootstrap.min.js',''));

global $Mo2fdbQueries;
$user_registration_status=$Mo2fdbQueries->get_user_detail('mo_2factor_user_registration_status', $current_user->ID);
?>
<div class="mo2f_table_layout">
    
    <form name="f"  id="login_settings_form" method="post" action="">

        
        <input type="hidden" name="option" value="mo_auth_pwdlogin_settings_save" />
        <input type="hidden" name="mo_auth_pwdlogin_settings_save_nonce"
        value="<?php echo wp_create_nonce( 'mo-auth-pwdlogin-settings-save-nonce' ); ?>"/>                
        <div id="password_less">
            <h2>GO PASSWORDLESS</h2><hr><br>
            <h3><?php echo __('Select Login Screen Options','miniorange-2-factor-authentication');?>
            <br><br>
            <span>
                <input type="submit" name="submit" value="<?php echo __('Save Settings', 'miniorange-2-factor-authentication');?>" style="float:right;" class="button button-primary button-large" > 
                <br>
                
                <input type=checkbox name="mo2f_login_policy" value="0" <?php checked(get_site_option('mo2f_login_policy') == 0);?>>

                
                <?php echo __('Login with 2nd Factor only ', 'miniorange-2-factor-authentication');?>
                <span style="color:red">(<?php echo __('No password required.', 'miniorange-2-factor-authentication');?>)</span> &nbsp;
                
                

                <a class=" btn-link" data-toggle="collapse" id="showpreview1"  href="#preview1" onclick="mo2f_onClick(this.id)" aria-expanded="false"><?php echo __('See preview', 'miniorange-2-factor-authentication');?></a>
                <br>
                <div class="mo2f_collapse" id="preview1" style="height:300px; display: none;">
                    <center><br>
                        <img style="height:300px;" src="<?php echo plugins_url( 'includes/images/WP_default_login_PL.png"', dirname(dirname(__FILE__ ))); ?>">
                    </center>
                </div> 
                <br>
                <br>
                <div class="mo2f_advanced_options_note" style="margin-left: 2%;font-style:Italic;padding:2%; background-color: #bbccdd; border-radius: 2px; padding:2%;"><b><?php echo __('Note:', 'miniorange-2-factor-authentication');?></b> <?php echo __('Checking this option will add login with your phone button below default login form.', 'miniorange-2-factor-authentication');?></div>
                
                <br> 
                <input style="margin-left:6%;" type="checkbox" id="mo2f_loginwith_phone" name="mo2f_loginwith_phone" value="1" <?php checked(get_site_option('mo2f_show_loginwith_phone') == 1);
                ?> /> 
                <?php echo __(' I want to hide default login form.', 'miniorange-2-factor-authentication');?> 
                &nbsp;
                <a class=" btn-link" data-toggle="collapse" id="showpreview2"  href="#preview2" onclick="mo2f_onClick(this.id)" aria-expanded="false"><?php echo __('See preview', 'miniorange-2-factor-authentication');?></a>
                <br>
                <div class="mo2f_collapse" id="preview2" style="height:300px; display: none;">
                    <center><br>
                        <img style="height:300px;" src="<?php echo plugins_url( 'includes/images/WP_hide_default_PL.png"', dirname(dirname(__FILE__ ))); ?>">
                    </center>
                </div> 
                <br>
                <br><div class="mo2f_advanced_options_note" style="margin-left: 2%;font-style:Italic; background-color: #bbccdd; border-radius: 2px; padding:2%;"><b><?php echo __('Note:', 'miniorange-2-factor-authentication');?></b> <?php echo __('Checking this option will hide default login form and just show login with your phone. ', 'miniorange-2-factor-authentication');?></div>
                
                
            </div>
        </form>
    </div>

    
    <script>
          function mo2f_onClick($mo2f_id) {
            if($mo2f_id=='showpreview1')
                var mo2f_element = jQuery('#preview1')[0];
            else
                var mo2f_element = jQuery('#preview2')[0];

                if (mo2f_element.style.display === "none") {
                    mo2f_element.style.display = "block";

                } else {
                    mo2f_element.style.display = "none";
                }

            }
            
    </script>





