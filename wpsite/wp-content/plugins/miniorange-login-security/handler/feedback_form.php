<?php
class FeedbackHandler
{
    function __construct()
    {
        add_action('admin_init', array($this, 'mo_wpns_feedback_actions'));
    }
    function mo_wpns_feedback_actions()
    {

        global $moWpnsUtility, $mo2f_dirName;
        if (current_user_can('manage_options') && isset($_POST['option'])) {

            switch ($_POST['option']) {
                case "mo_wpns_skip_feedback":              
                case "mo_wpns_feedback":                    
                  $this->wpns_handle_feedback($_POST);                    
                 break;
            }
        }
    }
    function wpns_handle_feedback($postdata)
    {
		if(MO2F_TEST_MODE){
			deactivate_plugins(dirname(dirname(__FILE__ ))."\\miniorange_2_factor_settings.php");
                return;
		}	
        $user = wp_get_current_user();
        $feedback_option = sanitize_text_field($_POST['option']);
        $message = 'Plugin Deactivated';
        $deactivate_reason_message = array_key_exists('wpns_query_feedback', $_POST) ? htmlspecialchars(sanitize_text_field( $_POST['wpns_query_feedback'])) : false;
        $reply_required = '';
        if (isset($_POST['get_reply']))
            $reply_required = htmlspecialchars(sanitize_text_field($_POST['get_reply']));
        if (empty($reply_required)) {
            $reply_required = "don't reply";
            $message .= '<b style="color:red";> &nbsp; [Reply :' .sanitize_text_field($reply_required) . ']</b>';
        } else {
            $reply_required = "yes";
            $message .= '[Reply :' . sanitize_text_field($reply_required) . ']';
        }
        $message .= ', Feedback : ' . sanitize_text_field($deactivate_reason_message) . '';
        if (isset($_POST['rate']))
            $rate_value = htmlspecialchars(sanitize_text_field($_POST['rate']));
		else
			$rate_value = "--";
        $message .= ', [Rating :' . sanitize_text_field($rate_value) . ']';

        $email = isset($_POST['query_mail'])? sanitize_text_field($_POST['query_mail']): '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = get_site_option('mo2f_email');
            if (empty($email))
                $email = $user->user_email;
        }

        $phone = get_site_option('mo_wpns_admin_phone');
        $feedback_reasons = new MocURL();
        global $moWpnsUtility;
        if (!is_null($feedback_reasons)) {
            if (!$moWpnsUtility->is_curl_installed()) {
                deactivate_plugins(dirname(dirname(__FILE__ ))."\\miniorange_2_factor_settings.php");
                wp_redirect('plugins.php');
            } else {             
            $submited = json_decode($feedback_reasons->send_email_alert($email, $phone, $message, $feedback_option), true);

                if (json_last_error() == JSON_ERROR_NONE) {
                    if (is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR') {
                        do_action('wpns_show_message',$submited['message'],'ERROR');

                    } else {
                        if ($submited == false) {
                            do_action('wpns_show_message','Error while submitting the query.','ERROR');
                        }
                    }
                }

                deactivate_plugins(dirname(dirname(__FILE__ ))."\\miniorange_2_factor_settings.php");
                do_action('wpns_show_message','Thank you for the feedback.','SUCCESS');

            }
        }
    }

}new FeedbackHandler();
