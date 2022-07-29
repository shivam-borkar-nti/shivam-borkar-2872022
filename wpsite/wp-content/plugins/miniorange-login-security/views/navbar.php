<?php
	$user = wp_get_current_user();
	$userID = wp_get_current_user()->ID;
	$onprem_admin = get_site_option('mo2f_onprem_admin');
	$roles = ( array ) $user->roles;
	$is_onprem = get_site_option('is_onprem');
        $flag  = 0;
  		foreach ( $roles as $role ) {
            if(get_site_option('mo2fa_'.$role)=='1')
            	$flag=1;
        }
	if($shw_feedback)
		echo MoWpnsMessages::showMessage('FEEDBACK');

	echo'<div class="mo2f-header" id="momls_wrap" >
				<div><img  style="float:left;margin-top:5px;" src="'.esc_url($logo_url).'"></div>
				<h1>
					<a class="button button-secondary button-large" href="'.esc_url($profile_url).'">My Account</a>
					<a class="license-button button button-secondary button-large" href="'.esc_url($license_url).'">See Plan and Pricing</a>
				</h1>			
		</div>';
?>

		<br>
		
			