<?php

$Back_button = admin_url().'admin.php?page=mo_2fa_two_fa';
echo'
<br><br><br><br>
    <div class="mo_wpns_divided_layout">
        <div class="mo2f_table_layout" >
            <div>
                <h4>Thank You for registering with miniOrange.</h4>
                <h3>Your Profile</h3>
                <table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:85%">
                    <tr>
                        <td style="width:45%; padding: 10px;">Username/Email</td>
                        <td style="width:55%; padding: 10px;">'.esc_attr($email).'</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Customer ID</td>
                        <td style="width:55%; padding: 10px;">'.esc_attr($key).'</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">API Key</td>
                        <td style="width:55%; padding: 10px;">'.esc_attr($api).'</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Token Key</td>
                        <td style="width:55%; padding: 10px;">'.esc_attr($token).'</td>
                    </tr>
                </table>
                <br/>
                 <center>';
                if (isset( $Back_button )) {

                        echo '<a class="button button-primary " href="'.esc_attr($Back_button).'">Back</a> ';
                    }
                echo '
                <a id="mo_mfa_log_out" class="button button-primary" >Remove Account and Reset Settings</a>
                </center>
                <p><a href="#mo_wpns_forgot_password_link">Click here</a> if you forgot your password to your miniOrange account.</p>
            </div>
        </div>
    </div>
	<form id="forgot_password_form" method="post" action="">
		<input type="hidden" name="option" value="mo_wpns_reset_password" />
	</form>
    <form id="mo_mfa_remove_account" method="post" action="">
        <input type="hidden" name="mo_mfa_remove_account" value="mo_wpns_reset_account" />
    </form>
	
	<script>
		jQuery(document).ready(function(){
			$(\'a[href="#mo_wpns_forgot_password_link"]\').click(function(){
				$("#forgot_password_form").submit();
			});
           jQuery("#mo_mfa_log_out").click(function(){
            jQuery("#mo_mfa_remove_account").submit();

           });
		});
	</script>';