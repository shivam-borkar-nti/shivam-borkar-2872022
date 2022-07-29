<?php 
	global $current_user;
	$current_user = wp_get_current_user();
	global $Mo2fdbQueries;
	?>
	<form name="f" id="custom_css_form_add" method="post" action="">
			<input type="hidden" name="option" value="mo_auth_custom_options_save" />
			
			<div class="mo2f_table_layout">
				<div id="mo2f_custom_addon_hide">
	            
			    <h3 id="custom_description" >
			        <?php echo __( 'This helps you to modify and redesign the 2FA prompt to match according to your website and various customizations in the plugin dashboard.', 'miniorange-2-factor-authentication' ); ?>
			        <b ><a  href="admin.php?page=mo_2fa_upgrade" style="color: red;"&nbsp;&nbsp;> [ PREMIUM ] </a></b>
			    </h3>
			    <br>
			</div>
						
				<h3><?php echo mo2f_lt('Customize Plugin Icon');?></h3><hr><br>
				<div style="margin-left:2%">
					<input type="checkbox" id="mo2f_enable_custom_icon" name="mo2f_enable_custom_icon" value="1" <?php checked( get_site_option('mo2f_enable_custom_icon') == 1 ); 
					  echo 'disabled'; ?> />
					 
					 <?php echo mo2f_lt('Change Plugin Icon.');?>
					 <div class="mo2f_advanced_options_note" ><p style="padding:5px;"><i><?php echo mo2f_lt('
						Go to /wp-content/uploads/miniorange folder and upload a .png image with the name "plugin_icon" (Max Size: 20x34px).');?></i></p>
					 </div>
				</div>
				 
<br>
				<h3><?php echo mo2f_lt('Customize Plugin Name');?></h3><hr><br>
				<div style="margin-left:2%">
					 <?php echo mo2f_lt('Change Plugin Name:');?> &nbsp;
				     <input type="text" class="mo2f_table_textbox" style="width:35% 	" id="mo2f_custom_plugin_name" name="mo2f_custom_plugin_name" <?php  echo 'disabled'; ?> value="<?php echo get_site_option('mo2f_custom_plugin_name')?>" placeholder="<?php echo mo2f_lt('Enter a custom Plugin Name.');?>" />
					 
					 <div class="mo2f_advanced_options_note"><p style="padding:5px;"><i>
						<?php echo mo2f_lt('This will be the Plugin Name You and your Users see in  WordPress Dashboard.');?>
					</i></p> </div>
					<input type="submit" name="submit" value="Save Settings" style="margin-left:2%; background-color: var(--mo2f-theme-color); color: white;box-shadow:none;" class="mo_wpns_button mo_wpns_button1" <?php 
						 echo 'disabled' ;  ?> />
				</div>	 	
					<br>
					
					
            </div>

        <br>


    </form>
					
	<?php show_2_factor_custom_design_options($current_user);?>
	<br>
	<div class="mo2f_table_layout">
	<h3><?php echo mo2f_lt('Custom Email and SMS Templates');

	?>
	</h3><hr>
	<div style="margin-left:2%">
					<p><?php echo mo2f_lt('You can change the templates for Email and SMS as per your requirement.');?></p>
					<?php if(mo2f_is_customer_registered()){ 
							if( get_site_option('mo2f_miniorange_admin') == $current_user->ID ){ ?>
								<a style="box-shadow: none;" class="mo_wpns_button mo_wpns_button1"<?php  echo 'disabled'; ?>><?php echo mo2f_lt('Customize Email Template');?></a><span style="margin-left:10px;"></span>
								<a style="box-shadow: none;" class="mo_wpns_button mo_wpns_button1"<?php  echo 'disabled'; ?> ><?php echo mo2f_lt('Customize SMS Template');?></a>
						<?php	} 
						}else{ ?>
						<a class="mo_wpns_button mo_wpns_button1"<?php  echo 'disabled'; ?>style="pointer-events: none;cursor: default;box-shadow: none;"><?php echo mo2f_lt('Customize Email Template');?></a>
							<span style="margin-left:10px;"></span>
						<a class="mo_wpns_button mo_wpns_button1"<?php  echo 'disabled'; ?> style="pointer-events: none;cursor: default;box-shadow: none;"><?php echo mo2f_lt('Customize SMS Template');?></a>
					<?php } ?>
					</div>
					</div>
				 <br>

        <div class="mo2f_table_layout">
            <h3><?php echo mo2f_lt('Integrate your websites\'s theme with the 2FA plugin\'s popups');?></h3><hr>
            <div style="margin-left:2%">
                <p><?php echo mo2f_lt('Contact Us through the support forum in the right for the UI integration.');?></p>
            </div>

        </div>
        <br>
				 <form style="display:none;" id="mo2fa_addon_loginform" action="<?php echo esc_attr (get_site_option( 'mo2f_host_name')).'/moas/login'; ?>" 
		target="_blank" method="post">
			<input type="email" name="username" value="<?php echo esc_attr($Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $current_user->ID ));?>" />
			<input type="text" name="redirectUrl" value="" />
		</form>
				 <script>
			function mo2fLoginMiniOrangeDashboard(redirectUrl){ 
				document.getElementById('mo2fa_addon_loginform').elements[1].value = redirectUrl;
				jQuery('#mo2fa_addon_loginform').submit();
			}
		</script>

	  <?php 
	  function show_2_factor_custom_design_options($current_user){
	?>   
			
			<br>
				<div class="mo2f_table_layout">
			<div id="mo2f_custom_addon_hide">
            
		    <br>
		</div>
			<form name="f"  id="custom_css_reset_form" method="post" action="" >
			<input type="hidden" name="option" value="mo_auth_custom_design_options_reset" />
			
			<h3><?php echo mo2f_lt('Customize UI of Login Pop up\'s');?></h3>
			<input type="submit" name="submit" value="Reset Settings" class="mo_wpns_button mo_wpns_button1"  style="float:right; background-color: var(--mo2f-theme-color); color: white;box-shadow: none;"<?php 
						 echo 'disabled' ;  ?> />
			
						</form>
			<form name="f"  id="custom_css_form" method="post" action="">
			<input type="hidden" name="option" value="mo_auth_custom_design_options_save" />
						
					
					
					<table class="mo2f_settings_table" style="margin-left:2%">
					<tr>
						<td><?php echo mo2f_lt('Background Color:');?> </td>
						<td><input type="text" id="mo2f_custom_background_color" name="mo2f_custom_background_color" <?php  echo 'disabled'; ?> value="<?php echo esc_attr (get_site_option('mo2f_custom_background_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('Popup Background Color:');?> </td>
						<td><input type="text" id="mo2f_custom_popup_bg_color" name="mo2f_custom_popup_bg_color" <?php  echo 'disabled'; ?> value="<?php echo esc_attr (get_site_option('mo2f_custom_popup_bg_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('Button Color:');?> </td>
						<td><input type="text" id="mo2f_custom_button_color" name="mo2f_custom_button_color" <?php  echo 'disabled'; ?> value="<?php echo esc_attr (get_site_option('mo2f_custom_button_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('Links Text Color:');?> </td>
						<td><input type="text" id="mo2f_custom_links_text_color" name="mo2f_custom_links_text_color" <?php  echo 'disabled'; ?> value="<?php echo esc_attr(get_site_option('mo2f_custom_links_text_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('Popup Message Text Color:');?> </td>
						<td><input type="text" id="mo2f_custom_notif_text_color" name="mo2f_custom_notif_text_color" <?php  echo 'disabled';?> value="<?php echo esc_attr(get_site_option('mo2f_custom_notif_text_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('Popup Message Background Color:');?> </td>
						<td><input type="text" id="mo2f_custom_notif_bg_color" name="mo2f_custom_notif_bg_color" <?php echo 'disabled'; ?> value="<?php echo esc_attr(get_site_option('mo2f_custom_notif_bg_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('OTP Token Background Color:');?> </td>
						<td><input type="text" id="mo2f_custom_otp_bg_color" name="mo2f_custom_otp_bg_color" <?php echo 'disabled'; ?> value="<?php echo esc_attr (get_site_option('mo2f_custom_otp_bg_color'))?>" class="my-color-field" /> </td>
					</tr>
					<tr>
						<td><?php echo mo2f_lt('OTP Token Text Color:');?> </td>
						<td><input type="text" id="mo2f_custom_otp_text_color" name="mo2f_custom_otp_text_color" <?php echo 'disabled'; ?> value="<?php echo esc_attr (get_site_option('mo2f_custom_otp_text_color'))?>" class="my-color-field" /> </td>
					</tr>
					</table>
					</br>
					<input type="submit" name="submit"   style="margin-left:2%; background-color: var(--mo2f-theme-color); color: white; box-shadow: none;" value="Save Settings" class="mo_wpns_button mo_wpns_button1" <?php 
						 echo 'disabled' ;  ?> />
									
			</form>
			</div>
	<?php
	}

