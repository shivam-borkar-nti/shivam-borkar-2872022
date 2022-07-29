<?php

function mo2f_configure_google_authenticator( $user ) {
    $mo2f_google_auth     = isset( $_SESSION['mo2f_google_auth'] ) ? sanitize_text_field($_SESSION['mo2f_google_auth']) : null;
    $data                 = isset( $_SESSION['mo2f_google_auth'] ) ? sanitize_text_field($mo2f_google_auth['ga_qrCode']) : null;
    $ga_secret            = isset( $_SESSION['mo2f_google_auth'] ) ? sanitize_text_field($mo2f_google_auth['ga_secret']) : null;
    $h_size               = 'h3';
    $gauth_name= get_site_option('mo2f_google_appname');
    $gauth_name = $gauth_name ? $gauth_name : 'miniOrangeAuth';
    ?>
    <table>
        <tr>
            <td class="mo2f_google_authy_step2">
                <?php echo '<' . esc_attr($h_size) . '>' . mo2f_lt( 'Step-1: Set up Google/Authy/LastPass Authenticator' ) . '</' . esc_attr($h_size) . '>'; ?>

                <hr>

                <p style="background-color:#a3e8c2;padding:5px;">
                    <?php echo mo2f_lt( 'You can configure this method in your Google/Authy/LastPass Authenticator apps.' ); ?>
                </p>

                    <h4>1. <?php echo mo2f_lt( 'Install the Authenticator App that you wish to configure, in your phone.' ); ?></h4>
                    <div style="margin-left:40px;">
                        <input type="radio" name="google" value="ga" checked> Google Authenticator &nbsp;&nbsp;
                        <input type="radio" name="authy" value="aa"> Authy Authenticator &nbsp;&nbsp;
                        <input type="radio" name="lastpass" value="lpa"> LastPass Authenticator &nbsp;&nbsp;
                    </div>

                <span id="links_to_apps"></span>
                <div id="mo2f_change_app_name">
                <h4>2. <?php echo mo2f_lt('Choose the account name to be configured in the App:'); ?></h4>
                <div style="margin-left:40px;">
                    <form name="f"  id="login_settings_appname_form" method="post" action="">
                        <input type="hidden" name="option" value="mo2f_google_appname" />
                        <input type="hidden" name="mo2f_google_appname_nonce"
                        value="<?php echo wp_create_nonce( "mo2f-google-appname-nonce" ) ?>"/>
                        <input type="text" class="mo2f_table_textbox" style="width:22% !important;" pattern="[^\s][A-Z]*[a-z]*[0-9]*[^\s]" name="mo2f_google_auth_appname" placeholder="Enter the app name" value="<?php echo esc_attr($gauth_name);?>"  />&nbsp;&nbsp;&nbsp;

                        <input type="submit" name="submit" value="Save App Name" class="mo_wpns_button mo_wpns_button1" />

                                    <br>
                    </form>
                </div>
                </div>
                <h4><span id="step_number"></span><?php echo mo2f_lt( 'Scan the QR code from the Authenticator App.' ); ?></h4>
                <div style="margin-left:40px;">
                    <ol>
                        <li><?php echo mo2f_lt( 'In the app, tap on Menu and select "Set up account".' ); ?></li>
                        <li><?php echo mo2f_lt( 'Select "Scan a barcode". Use your phone\'s camera to scan this barcode.' ); ?></li>
                        <div id="displayQrCode"style="padding:10px;"><?php echo '<img src="data:image/jpg;base64,' .esc_attr($data). '" />'; ?></div>

                    </ol>

                    <div><a data-toggle="collapse" href="#mo2f_scanbarcode_a"
                            aria-expanded="false"><b><?php echo mo2f_lt( 'Can\'t scan the barcode? ' ); ?></b></a>
                    </div>
                    <div class="mo2f_collapse" id="mo2f_scanbarcode_a">
                        <ol class="mo2f_ol">
                            <li><?php echo mo2f_lt( 'Tap on Menu and select' ); ?>
                                <b> <?php echo mo2f_lt( ' Set up account ' ); ?></b>.
                            </li>
                            <li><?php echo mo2f_lt( 'Select' ); ?>
                                <b> <?php echo mo2f_lt( ' Enter provided key ' ); ?></b>.
                            </li>
                            <li><?php echo mo2f_lt( 'For the' ); ?>
                                <b> <?php echo mo2f_lt( ' Enter account name ' ); ?></b>
                                <?php echo mo2f_lt( 'field, type your preferred account name' ); ?>.
                            </li>
                            <li><?php echo mo2f_lt( 'For the' ); ?>
                                <b> <?php echo mo2f_lt( ' Enter your key ' ); ?></b>
                                <?php echo mo2f_lt( 'field, type the below secret key' ); ?>:
                            </li>

                            <div class="mo2f_google_authy_secret_outer_div">
                                <div class="mo2f_google_authy_secret_inner_div">
                                    <?php echo esc_attr($ga_secret); ?>
                                </div>
                                <div class="mo2f_google_authy_secret">
                                    <?php echo mo2f_lt( 'Spaces do not matter' ); ?>.
                                </div>
                            </div>
                            <li><?php echo mo2f_lt( 'Key type: make sure' ); ?>
                                <b> <?php echo mo2f_lt( ' Time-based ' ); ?></b>
                                <?php echo mo2f_lt( ' is selected' ); ?>.
                            </li>

                            <li><?php echo mo2f_lt( 'Tap Add.' ); ?></li>
                        </ol>
                    </div>
                <br>
                </div>

            </td>
            <td class="mo2f_vertical_line"></td>
            <td class="mo2f_google_authy_step3">
                <h4><?php echo '<' . esc_attr($h_size) . '>' . mo2f_lt( 'Step-2: Verify and Save' ) . '</' . esc_attr($h_size) . '>';; ?></h4>
                <hr>
                <div style="<?php echo isset( $_SESSION['mo2f_google_auth'] ) ? 'display:block' : 'display:none'; ?>">
                    <div><?php echo mo2f_lt( 'After you have scanned the QR code and created an account, enter the verification code from the scanned account here.' ); ?></div>
                    <br>
                    <form name="f" method="post" action="">
                        <span><b><?php echo mo2f_lt( 'Code:' ); ?> </b>&nbsp;
                        <input class="mo2f_table_textbox" style="width:200px;" autofocus="true" required="true"
                               type="text" name="google_token" placeholder="<?php echo mo2f_lt( 'Enter OTP' ); ?>"
                               style="width:95%;"/></span><br><br>
                        <input type="hidden" name="google_auth_secret" value="<?php echo esc_attr($ga_secret) ?>"/>
                        <input type="hidden" name="option" value="mo2f_configure_google_authenticator_validate"/>
                        <input type="hidden" name="mo2f_configure_google_authenticator_validate_nonce"
                        value="<?php echo wp_create_nonce( "mo2f-configure-google-authenticator-validate-nonce" ) ?>"/>
                        <input type="submit" name="validate" id="validate" class="mo_wpns_button mo_wpns_button1"
                               style="float:left;" value="<?php echo mo2f_lt( 'Verify and Save' ); ?>"/>
                    </form>
                    <form name="f" method="post" action="" id="mo2f_go_back_form">
                                        <input type="hidden" name="option" value="mo2f_go_back"/>
                                        <input type="submit" name="back" id="go_back" class="mo_wpns_button mo_wpns_button1"
                                                value="<?php echo mo2f_lt( 'Back' ); ?>"/>
                                               <input type="hidden" name="mo2f_go_back_nonce"
                        value="<?php echo wp_create_nonce( "mo2f-go-back-nonce" ) ?>"/>
                                    </form>
                </div>
            </td>
        </tr>
    </table>
    <script>
        jQuery(document).ready(function(){
            jQuery(this).scrollTop(0);
            if(jQuery('input[type=radio][name=google]').is(':checked')){
                jQuery('#links_to_apps').html('<p style="background-color:#e8e4e4;padding:5px;margin-left:40px;width:65%">' +
                    'Get the Google Authenticator App - <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank"><b><?php echo mo2f_lt( "Android Play Store" ); ?></b></a>, &nbsp;' +
                    '<a href="http://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank"><b><?php echo mo2f_lt( "iOS App Store" ); ?>.</b>&nbsp;</p>');
                jQuery('#mo2f_change_app_name').show();
                jQuery('#links_to_apps').show();
            }
        });

        jQuery('input[type=radio][name=mo2f_app_type_radio]').change(function () {
            jQuery('#mo2f_configure_google_authy_form1').submit();
        });

        jQuery('#links_to_apps').show();
        jQuery('#mo2f_change_app_name').hide();
        jQuery('#step_number').html('2. ');

        jQuery('input[type=radio][name=google]').click(function(){
            jQuery('#links_to_apps').html('<p style="background-color:#e8e4e4;padding:5px;margin-left:40px;width:65%">' +
                'Get the Google Authenticator App - <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank"><b><?php echo mo2f_lt( "Android Play Store" ); ?></b></a>, &nbsp;' +
                '<a href="http://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank"><b><?php echo mo2f_lt( "iOS App Store" ); ?>.</b>&nbsp;</p>');
            jQuery('#step_number').html('3. ');
            jQuery("input[type=radio][name=authy]").prop("checked", false);
            jQuery("input[type=radio][name=lastpass]").prop("checked", false);
            jQuery('#mo2f_change_app_name').show();
            jQuery('#links_to_apps').show();
        });

        jQuery('input[type=radio][name=authy]').click(function(){
            jQuery('#links_to_apps').html('<p style="background-color:#e8e4e4;padding:5px;margin-left:40px;width:65%">' +
                'Get the Authy Authenticator App - <a href="https://play.google.com/store/apps/details?id=com.authy.authy" target="_blank"><b><?php echo mo2f_lt( "Android Play Store" ); ?></b></a>, &nbsp;' +
                '<a href="https://itunes.apple.com/in/app/authy/id494168017" target="_blank"><b><?php echo mo2f_lt( "iOS App Store" ); ?>.</b>&nbsp;</p>');
            jQuery("input[type=radio][name=google]").prop("checked", false);
            jQuery("input[type=radio][name=lastpass]").prop("checked", false);
            jQuery('#mo2f_change_app_name').hide();
            jQuery('#step_number').html('2. ');
            jQuery('#links_to_apps').show();
        });

        jQuery('input[type=radio][name=lastpass]').click(function(){
            jQuery('#links_to_apps').html('<p style="background-color:#e8e4e4;padding:5px;margin-left:40px;width:65%">' +
                'Get the LastPass Authenticator App - <a href="https://play.google.com/store/apps/details?id=com.lastpass.authenticator" target="_blank"><b><?php echo mo2f_lt( "Android Play Store" ); ?></b></a>, &nbsp;' +
                '<a href="https://itunes.apple.com/in/app/lastpass-authenticator/id1079110004" target="_blank"><b><?php echo mo2f_lt( "iOS App Store" ); ?>.</b>&nbsp;</p>');
            jQuery("input[type=radio][name=authy]").prop("checked", false);
            jQuery("input[type=radio][name=google]").prop("checked", false);
            jQuery('#mo2f_change_app_name').show();
            jQuery('#step_number').html('3. ');
            jQuery('#links_to_apps').show();
        });
    </script>
    <?php
}

?>
