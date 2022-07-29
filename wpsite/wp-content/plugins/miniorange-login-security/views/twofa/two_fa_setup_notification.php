<?php

function mo2f_display_test_2fa_notification( $user ) {
	global $Mo2fdbQueries;
	$mo2f_configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
    
    if(get_site_option('is_onprem'))
    {

        $mo2f_configured_2FA_method = get_user_meta($user->ID,'currentMethod',true);
        update_user_meta($user->ID,$mo2f_configured_2FA_method,1);

    }
?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <div id="twoFAtestAlertModal" class="modal" role="dialog">
        <div class="mo2f_modal-dialog">
            <!-- Modal content-->
            <div class="modal-content" style="width:660px !important;">
                <center>
                <div class="modal-header">
                    <h2 class="mo2f_modal-title" style="color: var(--mo2f-theme-color);">2FA Setup Successful.</h2>
                    <span type="button" id="test-methods" class="modal-span-close" data-dismiss="modal">&times;</span>
                </div>
                <div class="mo2f_modal-body">
                    <p style="font-size:14px;"><b><?php echo $mo2f_configured_2FA_method; ?> </b> has been set as your 2-factor authentication method.
                        <br><br>Please test the login flow once with 2nd factor in another browser or in an incognito window of the
                        same browser to ensure you don't get locked out of your site.</p>
                </div>
                <div class="mo2f_modal-footer">
                    <button type="button" id="test-methods-button" class="mo_wpsn_button mo_wpsn_button1" data-dismiss="modal">Got it!</button>
                </div>
            </center>
            </div>
        </div>
    </div>

    <script>
        jQuery('#twoFAtestAlertModal').css('display', 'block');
        jQuery('#test-methods').click(function(){
            jQuery('#twoFAtestAlertModal').css('display', 'none');
        });
        jQuery('#test-methods-button').click(function(){
            jQuery('#twoFAtestAlertModal').css('display', 'none');
        });
    </script>
<?php }
?>