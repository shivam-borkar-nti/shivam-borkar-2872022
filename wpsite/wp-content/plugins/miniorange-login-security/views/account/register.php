<?php	
	
echo'<!--Register with miniOrange-->
<br><br><br><br>

	<form name="f" method="post" action="">
		<input type="hidden" name="option" value="mo_wpns_register_customer" />
		<div class="mo_wpns_divided_layout">
		<div class="mo2f_table_layout" style="margin-bottom:30px;">
			
				<h3>Register with miniOrange
					<div style="float: right;">';
                    if (isset( $two_fa )) {
                        echo '<a class="button button-primary button-large" href="'.esc_attr($two_fa).'">Back</a> ';
                    }
                    echo '</div>
				</h3>
				<p>Just complete the short registration below to configure miniOrange 2-Factor plugin. Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will send to this email.</p>
				<table class="mo_wpns_settings_table">
					<tr>
						<td><b><font color="#FF0000">*</font>Email:</b></td>
						<td><input class="mo_wpns_table_textbox" type="email" name="email"
							required placeholder="person@example.com"
							value="'.esc_attr($user->user_email).'" /></td>
					</tr>

					<tr>
						<td><b><font color="#FF0000">*</font>Password:</b></td>
						<td><input class="mo_wpns_table_textbox" required type="password"
							name="password" placeholder="Choose your password (Min. length 6)" /></td>
					</tr>
					<tr>
						<td><b><font color="#FF0000">*</font>Confirm Password:</b></td>
						<td><input class="mo_wpns_table_textbox" required type="password"
							name="confirmPassword" placeholder="Confirm your password" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><br /><input type="submit" name="submit" value="Next" style="width:100px;"
							class="button button-primary button-large" />
							<a class="button button-primary button-large" href="#mo2f_account_exist">SIGN IN</a>

					</tr>
				</table>
		</div>	
		</div>
	</form>
	 <form name="f" method="post" action="" class="mo2f_verify_customerform">
        <input type="hidden" name="option" value="mo2f_goto_verifycustomer">
         <input type="hidden" name="mo2f_goto_verifycustomer_nonce"
               value='. wp_create_nonce( "mo2f-goto-verifycustomer-nonce" ).' >
       </form>';
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

    <script>
		
        jQuery('a[href=\"#mo2f_account_exist\"]').click(function (e) {
            jQuery('.mo2f_verify_customerform').submit();
        });
		
		
		
    </script>