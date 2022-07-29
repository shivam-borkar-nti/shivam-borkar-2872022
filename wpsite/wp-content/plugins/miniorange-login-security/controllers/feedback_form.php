<?php
global $moWpnsUtility, $mo2f_dirName;
function wpns_handle_skip_feedback($postdata){
    do_action('wpns_show_message',MoWpnsMessages::showMessage('FEEDBACK'),'CUSTOM_MESSAGE');
    deactivate_plugins( __FILE__ );
}

function wpns_handle_feedback($postdata)
{

        $user = wp_get_current_user();

        $message = 'Plugin Deactivated';

        $deactivate_reason_message = array_key_exists('query_feedback', $_POST) ? htmlspecialchars($_POST['query_feedback']) : false;


        $reply_required = '';
        if (isset($_POST['get_reply']))
            $reply_required = htmlspecialchars($_POST['get_reply']);
        if (empty($reply_required)) {
            $reply_required = "don't reply";
            $message .= '<b style="color:red";> &nbsp; [Reply :' . $reply_required . ']</b>';
        } else {
            $reply_required = "yes";
            $message .= '[Reply :' . $reply_required . ']';
        }


        $message .= ', Feedback : ' . $deactivate_reason_message . '';

        if (isset($_POST['rate']))
            $rate_value = htmlspecialchars($_POST['rate']);

        $message .= ', [Rating :' . $rate_value . ']';

        $email = $_POST['query_mail'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = get_site_option('mo2f_email');
            if (empty($email))
                $email = $user->user_email;
        }
        $phone = get_site_option('mo_wpns_admin_phone');
        $feedback_reasons = new Customersaml();
        if (!is_null($feedback_reasons)) {
            if (!mo_saml_is_curl_installed()) {
                deactivate_plugins(__FILE__);
                wp_redirect('plugins.php');
            } else {
                $submited = json_decode($feedback_reasons->send_email_alert($email, $phone, $message), true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    if (is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR') {
                        update_site_option('mo_saml_message', $submited['message']);
                        $this->mo_saml_show_error_message();

                    } else {
                        if ($submited == false) {

                            update_site_option('mo_saml_message', 'Error while submitting the query.');
                            $this->mo_saml_show_error_message();
                        }
                    }
                }

                deactivate_plugins(__FILE__);
                update_site_option('mo_saml_message', 'Thank you for the feedback.');
                $this->mo_saml_show_success_message();
            }
        }
}