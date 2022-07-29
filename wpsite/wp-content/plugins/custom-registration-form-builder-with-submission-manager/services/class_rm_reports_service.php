<?php

class RM_Reports_Service extends RM_Services
{
    public function generate_reports_data($filter,$limit){
        //$req = new stdClass;
        
        if(isset($filter->filter_date) && isset($filter->form_id)){
            $filter->start_date = $filter->start_date;
            $filter->end_date = $filter->end_date;
            $filter->form_id = $filter->form_id;
        }
        elseif(isset($filter->filter_date) && !isset($filter->form_id)){
            $filter->start_date = $filter->start_date;
            $filter->end_date = $filter->end_date;
            $filter->form_id = 'all';
        }
        elseif(!isset($filter->filter_date) && isset($filter->form_id)){
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            $filter->form_id = $filter->form_id;
        }
        else{
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            $filter->form_id = 'all';
        }
        return $filter;
    }
    
    public function get_submission($req, $limit=5, $column='*'){
        
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('SUBMISSIONS');
        $qry = "";
        $interval_string = "";
        $limit_string = "";
        $email_string = "";
        $results = new stdClass;
        
        $para = new stdClass();
        $para->start_date = $req->start_date;
        $para->end_date = $req->end_date;
        $para->form_id = $req->form_id;
        $para->email = $req->email;
        if($limit != 0){
            $limit_string = "LIMIT ".$limit;
        }
        if($req->email){
            $email_string = "user_email = '$req->email' AND ";
        }
        $interval_string = "BETWEEN '" .date('Y-m-d',strtotime($req->start_date)). "' AND '".date('Y-m-d',strtotime($req->end_date))."'  ORDER BY `submission_id` DESC ".$limit_string;
        $count_interval_string = "BETWEEN '" .date('Y-m-d',strtotime($req->start_date)). "' AND '".date('Y-m-d',strtotime($req->end_date))."'  ORDER BY `submission_id` DESC ";
        if($req->form_id =='all'){
            $qry = "SELECT $column FROM `$table_name` WHERE $email_string CAST(submitted_on AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` WHERE $email_string CAST(submitted_on AS date) $count_interval_string";
            $submissions = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }else{
            $qry = "SELECT $column FROM `$table_name` WHERE `form_id` = $req->form_id AND $email_string CAST(submitted_on AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` WHERE `form_id` = $req->form_id AND $email_string CAST(submitted_on AS date) $count_interval_string";
            $submissions = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }
        $results->submissions = $submissions;
        $results->submissions_count = $sub_count;
        $results->submissions_chart = $this->generate_submission_chart_data($para);
        return $results; 
    }
    public function generate_submission_chart_data($req){
        $chart_data = new stdClass;
        $chart_date = array();
        $chart_value = array();
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('SUBMISSIONS');
        $email_string = '';
        if(isset($req->email) && $req->email!=''){
            $email_string = "user_email = '$req->email' AND ";
        }
        $start_date = new DateTime(date('Y-m-d',strtotime($req->start_date)));
        $end_date = new DateTime(date('Y-m-d',strtotime($req->end_date)));
        $diff = strtotime($req->end_date) - strtotime($req->start_date);
        $req->start_date = date('Y-m-d',strtotime($req->start_date . ' -1 day'));
        $day = abs(round($diff / 86400)) + 1;
        if($day <= 1) {
            $day =2;
            $req->start_date = date('Y-m-d',strtotime($req->start_date . ' -1 day'));
        }
        while($day > 0 ){
            $req->start_date = date('Y-m-d',strtotime($req->start_date . ' +1 day'));
            $chart_date[] = date("j M", strtotime($req->start_date));
            $count_interval_string = "= '" .date('Y-m-d',strtotime($req->start_date))."'";
            if($req->form_id =='all'){
                $count_qry = "SELECT * FROM `$table_name` WHERE $email_string CAST(submitted_on AS date) $count_interval_string";
                $sub_count= count($wpdb->get_results($count_qry));
            }else{
                $count_qry = "SELECT * FROM `$table_name` WHERE $email_string `form_id` = $req->form_id AND CAST(submitted_on AS date) $count_interval_string";
                $sub_count= count($wpdb->get_results($count_qry));
            }
            $chart_value[] = $sub_count;
            $day--;
        }
        $chart_data->chart_date = $chart_date;
        $chart_data->chart_value = $chart_value;
        return $chart_data;
    }
    
    public function prepare_submission_export_data($filter,$submission_ids){
        $export_data = array();
        $is_payment = false;
        $option = new RM_Options;
        $form_id = $filter->form_id;
        
        if (!(int) $form_id)
            return false;

        $fields = $this->get_all_form_fields($form_id);
        
        if (!$fields){
            return false;
        }
        $field_ids = array();
        $export_data[0]['s_id']= 'Submission ID';
        $export_data[0]['s_date']= 'Submission On';
        $export_data[0]['u_token']= 'Unique Token';
        $export_data[0]['s_ip']= 'IP';
        $export_data[0]['s_browser']= 'Browser';
        foreach ($fields as $field) {
            if (!in_array($field->field_type,RM_Utilities::csv_excluded_widgets())) {
                $field_ids[] = $field->field_id;
                $export_data[0][$field->field_id] = $field->field_label;
            }
            $i = 0;
            if ($field->field_type == 'Price' && $i == 0) {
                $is_payment = true;                
                $i++;
            }
        }
        
        if($is_payment)
        {
            $export_data[0]['invoice'] = 'Payment Invoice';
            $export_data[0]['txn_id'] = 'Payment TXN Id';
            $export_data[0]['status'] = 'Payment Status';
            $export_data[0]['total_amount'] = 'Paid Amount';
            $export_data[0]['date'] = 'Date of Payment';
        }
        if (!$submission_ids)
                return false;
        $submissions = RM_DBManager::get_sub_fields_for_array('SUBMISSION_FIELDS', 'field_id', $field_ids, 'submission_id', $submission_ids);

        foreach ($submission_ids as $s_id) {
            $export_data[$s_id] = array();
            $export_data[$s_id]['s_id']= $s_id;
            
            $submission= new RM_Submissions();
            $submission->load_from_db($s_id);
            $export_data[$s_id]['s_date']= RM_Utilities::localize_time($submission->get_submitted_on(), get_option('date_format').' H:i:s');
            $export_data[$s_id]['u_token']= $submission->get_unique_token();
            $export_data[$s_id]['s_ip']= $submission->get_submission_ip();
            $export_data[$s_id]['s_browser']= $submission->get_submission_browser();
            $parent_s_id = RM_DBManager::get_oldest_submission_from_group($s_id);
            if(!$parent_s_id)
                $parent_s_id = $s_id;
            $payment = $this->get('PAYPAL_LOGS', array('submission_id' => $parent_s_id), array('%d'), 'row', 0, 10, '*', null, true);

            foreach ($field_ids as $f_id) {
                $export_data[$s_id][$f_id] = null;
            }

            if ($is_payment) {
                $export_data[$s_id]['invoice'] = isset($payment->invoice) ? $payment->invoice : null;
                $export_data[$s_id]['txn_id'] = isset($payment->txn_id) ? $payment->txn_id : null;
                $export_data[$s_id]['status'] = isset($payment->status) ? $payment->status : null;
                $export_data[$s_id]['total_amount'] = isset($payment->total_amount) ? $option->get_formatted_amount($payment->total_amount, $payment->currency) : null;
                $export_data[$s_id]['date'] = isset($payment->posted_date) ? RM_Utilities::localize_time($payment->posted_date, get_option('date_format')) : null;
            }
        }
        
        $WCBilling_str = '';
        $WCShipping_str = '';
        foreach ($submissions as $submission) {
            $value = maybe_unserialize($submission->value);
            if (is_array($value)) {
                if (isset($value['rm_field_type']) && $value['rm_field_type'] == 'File') {
                    unset($value['rm_field_type']);
                    if (count($value) == 0)
                        $value = null;
                    else {
                        $file = array();
                        foreach ($value as $a)
                            $file[] = wp_get_attachment_url($a);

                        $value = implode(',', $file);
                    }
                }elseif (isset($value['rm_field_type']) && $value['rm_field_type'] == 'Address'){
                       unset($value['rm_field_type']);
                       foreach($value as $in =>  $val){
                           if(empty($val))
                               unset($value[$in]);
                       }
                    $value = implode(',', $value);   
                } else
                    $value = implode(', ',RM_Utilities::get_lable_for_option($submission->field_id, $value));
            }
            else
                $value = RM_Utilities::get_lable_for_option($submission->field_id, $value);
            
            $value = html_entity_decode($value);
            
            if (array_key_exists($submission->submission_id, $export_data))
                $export_data[$submission->submission_id][$submission->field_id] = stripslashes($value);
            
            $field_data = new RM_Fields();            
            $field_data->load_from_db($submission->field_id);
            $WCBilling_str = '';
            $WCShipping_str = '';
            if($field_data->field_type=='WCBilling'){
                $WCBilling_str .= stripslashes($value).', ';
                $export_data[$submission->submission_id][$submission->field_id] = $WCBilling_str;
            }
            
            if($field_data->field_type=='WCShipping'){
                $WCShipping_str .= stripslashes($value).', ';
                $export_data[$submission->submission_id][$submission->field_id] = $WCShipping_str;
            }
        }  
        return $export_data;
    }
    
    public function attachment_manage($parameter){
        $service = new RM_Attachment_Service;
        $data = new stdClass();
        if (isset($parameter->form_id)){
            $form_id = $parameter->form_id;
        }
        if($parameter->form_id=='all' ){
            $form_id = $service->get('FORMS', 1, array('%d'), 'var', 0, 15, $column = 'form_id', null, true);
        }
        $attachments = $service->get_all_form_reports_attachments($form_id, $parameter);
        $chart_data = $this->attachment_chart_data($form_id, $attachments );
        $data->attachments = $attachments;
        $data->chart_data = $chart_data;
        
        if(!empty($attachments)):
            $data->count = count($attachments);
        else:
            $data->count = 0;
        endif;
         return $data;
    }
    public function attachment_chart_data($form_id,$attachments){
        $field_ext_types = RM_DBManager_Addon::get('FIELDS', array('field_type' => 'File', 'form_id' => $form_id), array('%s','%d'), 'col', 0, 99999, 'field_value', null, false);
        $types = array();
        
        if(!empty($field_ext_types) && $field_ext_types[0]!=''){
            foreach($field_ext_types as $type){
                $ext = explode('|',$type);
                $types = array_merge($types, $ext);
            }
        }
        $ext_data = array();
        if(!empty($types)){
            foreach($types as $type){
                $ext_data[$type] = 0;
            }
            unset($ext_data['JPEG']);
        }
        if(!empty($attachments)){
            foreach($attachments as $attachment){
                $file_url = wp_get_attachment_url( $attachment );
                $filetype = wp_check_filetype( $file_url );
                $ext_type = strtoupper($filetype['ext']);
                if(!isset($ext_data[$ext_type])){
                    $ext_data[$ext_type] = 0;
                }
                //
                $ext_count = $ext_data[$ext_type];
                $ext_data[$ext_type] = $ext_count+1;
            }
            
        }
        foreach($ext_data as $key => $ext):
            if($key=='' || $ext_data[$key] < 1): 
                unset($ext_data[$key]);
            endif;
        endforeach;
        return $ext_data;
    }
    
    public function get_payments($submission_ids,$status='all'){
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('PAYPAL_LOGS');
        $qry = "";
        $where_string = "";
        $results = new stdClass;
        if(!empty($submission_ids)){
            if($status !='all'){
                $where_string = "AND `status` = '$status'";
            }
            $submission_ids = implode(',',$submission_ids);
            $qry = "SELECT * FROM `$table_name` WHERE `submission_id` IN ($submission_ids) " . $where_string;
            $payments = $wpdb->get_results($qry);
            $results->payments = $payments;
            $results->payments_count = count($payments);
        }
        else{
            $results->payments = array();
            $results->payments_count = 0;
        }
        //$results->submissions_chart = $this->generate_submission_chart_data($parameter);
        return $results; 
    }
    public function get_payments_with_submissions($parameter, $status='all', $limit=5, $column='*'){
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('SUBMISSIONS');
        $payment_table_name = RM_Table_Tech::get_table_name_for('PAYPAL_LOGS');
        $forms_table_name = RM_Table_Tech::get_table_name_for('FORMS');
        $qry = "";
        $interval_string = "";
        $limit_string = "";
        $status_string = "";
        $results = new stdClass;
        
        $para = new stdClass();
        $para->start_date = $parameter->start_date;
        $para->end_date = $parameter->end_date;
        $para->form_id = $parameter->form_id;
        $para->status = $status;
        if($limit != 0){
            $limit_string = "LIMIT ".$limit;
        }
        
        if($status !='all'){
            if($status == 'Completed'){
                $status_list = '"Completed", "Succeeded", "succeeded"';
            }
            else{
                $status_list = '"';
                $status_list .= $status;
                $status_list .='"';
            }
            $status_string =  "$payment_table_name.status IN ($status_list) AND";
        }
        $interval_string = "BETWEEN '" .date('Y-m-d',strtotime($parameter->start_date)). "' AND '".date('Y-m-d',strtotime($parameter->end_date))."'  ORDER BY $table_name.submission_id DESC ".$limit_string;
        $count_interval_string = "BETWEEN '" .date('Y-m-d',strtotime($parameter->start_date)). "' AND '".date('Y-m-d',strtotime($parameter->end_date))."'  ORDER BY $table_name.submission_id DESC ";
        if($parameter->form_id =='all'){
            $qry = "SELECT $column FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $status_string CAST($table_name.submitted_on AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $status_string CAST($table_name.submitted_on AS date) $count_interval_string";
            $payments = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }else{
            $qry = "SELECT $column FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $parameter->form_id AND $status_string CAST($table_name.submitted_on AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $status_string $table_name.form_id = $parameter->form_id AND CAST($table_name.submitted_on AS date) $count_interval_string";
            $payments = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }
        
        $results->payments = $payments;
        $results->payments_count = $sub_count;
        $results->payments_chart = $this->generate_payments_chart_data($para);
        return $results; 
    }
    public function prepare_payments_export_data($parameter, $status){
        
        $export_data = array();
        $export_data[0]['s_date']= 'Submission On';
        $export_data[0]['s_id']= 'Submission ID';
        $export_data[0]['f_id'] = 'Form ID';
        $export_data[0]['f_name'] = 'Form Name';
        $export_data[0]['s_email']= 'User Email';
        $export_data[0]['p_currency']= 'Currency';
        $export_data[0]['p_amount']= 'Amount';
        $export_data[0]['p_status']= 'Status';
        $export_data[0]['p_method']= 'Payment Method';
        $export_data[0]['p_txn_id'] = 'Payment TXN Id';
        $export_data[0]['p_date']= 'Date of Payment';
        $payments_data = $this->get_payments_with_submissions($parameter,$status,0);
        if($payments_data->payments_count){
            $row = 1;
            foreach($payments_data->payments as $payment){
                $export_data[$row]['s_date'] = $payment->submitted_on;
                $export_data[$row]['s_id'] = $payment->submission_id;
                $export_data[$row]['f_id'] = $payment->form_id;
                $export_data[$row]['f_name'] = $payment->form_name;
                $export_data[$row]['s_email'] = $payment->user_email;
                $export_data[$row]['p_currency'] = $payment->currency;
                $export_data[$row]['p_amount'] = $payment->total_amount;
                $export_data[$row]['p_status'] = $payment->status;
                $export_data[$row]['p_method'] = $payment->pay_proc;
                $export_data[$row]['p_txn_id'] = $payment->txn_id;
                $export_data[$row]['p_date'] = $payment->posted_date;
                
                $row++;
            }
        }
        return $export_data;
    }
    
    public function generate_payments_chart_data($parameter){
        global $wpdb;
        $chart_data = new stdClass;
        $chart_date = array();
        $chart_revenue = array();
        $chart_count = array();
        $qry = "";
        $where_string = "";
        $results = new stdClass;
        $start_date = new DateTime(date('Y-m-d',strtotime($parameter->start_date)));
        $end_date = new DateTime(date('Y-m-d',strtotime($parameter->end_date)));
        $diff = strtotime($parameter->end_date) - strtotime($parameter->start_date);
        $parameter->start_date = date('Y-m-d',strtotime($parameter->start_date . ' -1 day'));
        $day = abs(round($diff / 86400)) + 1;
        if($day <= 1) {
            $day =2;
            $parameter->start_date = date('Y-m-d',strtotime($parameter->start_date . ' -1 day'));
        }
        $table_name = RM_Table_Tech::get_table_name_for('SUBMISSIONS');
        $payment_table_name = RM_Table_Tech::get_table_name_for('PAYPAL_LOGS');
        $forms_table_name = RM_Table_Tech::get_table_name_for('FORMS');
        $status_string = '';
        if($parameter->status !='all'){
            if($parameter->status == 'Completed'){
                $status_list = '"Completed", "Succeeded", "succeeded"';
            }
            else{
                $status_list = '"';
                $status_list .= $parameter->status;
                $status_list .='"';
            }
            $status_string =  "$payment_table_name.status IN ($status_list) AND";
        }
        
        $count_interval_string = "= '" .date('Y-m-d',strtotime($parameter->start_date))."' ORDER BY $table_name.submission_id DESC ";
        while($day > 0 ){
            $parameter->start_date = date('Y-m-d',strtotime($parameter->start_date . ' +1 day'));
            $chart_date[] = date("j M", strtotime($parameter->start_date));
            $count_interval_string = "= '" .date('Y-m-d',strtotime($parameter->start_date))."'";
            if($parameter->form_id =='all'){
                $count_qry = "SELECT SUM(total_amount) AS total_amount, Count(id) AS records FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $status_string CAST($table_name.submitted_on AS date) $count_interval_string";
                $sub_count= $wpdb->get_results($count_qry);
            }else{
                $count_qry = "SELECT SUM(total_amount) AS total_amount, Count(id) AS records FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id INNER JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $status_string $table_name.form_id = $parameter->form_id AND CAST($table_name.submitted_on AS date) $count_interval_string";
                
                $sub_count= $wpdb->get_results($count_qry);
            }
            foreach($sub_count as $count){
                $chart_revenue[] = $count->total_amount =='' ? 0 : $count->total_amount;
                $chart_count[] = $count->records;
            }
            $day--;
        }
        $results->payment_date = $chart_date;
        $results->payment_total = $chart_revenue;
        $results->payment_count = $chart_count;
        
        return $results;
    }
    public function generate_reports_data_compare($filter,$forms){
        //$req = new stdClass;
        
        if(isset($filter->filter_date) && isset($filter->form_id_1) && isset($filter->form_id_2)){
            $filter->start_date = $filter->start_date;
            $filter->end_date = $filter->end_date;
            $filter->form_id_1 = $filter->form_id_1;
            $filter->form_id_2 = $filter->form_id_2;
        }
        elseif(isset($filter->filter_date) && ( !isset($filter->form_id_1) && !isset($filter->form_id_2) ) ){
            $filter->start_date = $filter->start_date;
            $filter->end_date = $filter->end_date;
            if(isset($forms)):
                $form_count = 1;
                foreach($forms as $id =>$title):
                    $form_id = 'form_id_'.$form_count;
                    $filter->$form_id = $id;
                    $form_count++;
                    if($form_count > 2) break;
                endforeach;
            endif;
        }
        elseif(!isset($filter->filter_date) && ( isset($filter->form_id_1) && isset($filter->form_id_2))){
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            $filter->form_id_1 = $filter->form_id_1;
            $filter->form_id_2 = $filter->form_id_2;
        }
        else{
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            if(isset($forms)):
                $form_count = 1;
                foreach($forms as $id =>$title):
                    $filter->form_id_.''.$form_count = $id;
                    $form_count++;
                    if($form_count > 2) break;
                endforeach;
            endif;
        }
        return $filter;
    }
    
    public function get_submission_compare($parameter, $limit=5, $column='*'){
        $submissions = new stdClass();
        $submissions->form_data_1 = $this->generate_form_compare_data($parameter, $parameter->form_id_1);
        $submissions->form_data_2 = $this->generate_form_compare_data($parameter, $parameter->form_id_2);
        $chart_data_submissions = array();
        if($submissions->form_data_2->total_submission || $submissions->form_data_1->total_submission):
            $chart_data_submissions = array($submissions->form_data_2->total_submission, $submissions->form_data_1->total_submission);
        endif;
        $submissions->chart_data_submissions = $chart_data_submissions;
        $chart_data_payment_completed = array();
        if($submissions->form_data_2->payment_completed_sum || $submissions->form_data_1->payment_completed_sum):
            $chart_data_payment_completed = array($submissions->form_data_2->payment_completed_sum, $submissions->form_data_1->payment_completed_sum);
        endif;
        $submissions->chart_data_payment_completed = $chart_data_payment_completed;
        
        $chart_data_payment_pending = array();
        if($submissions->form_data_2->payment_pending_sum || $submissions->form_data_1->payment_pending_sum):
            $chart_data_payment_pending = array($submissions->form_data_2->payment_pending_sum, $submissions->form_data_1->payment_pending_sum);
        endif;
        $submissions->chart_data_payment_pending = $chart_data_payment_pending;
        
        $chart_data_payment_refunded = array();
        if($submissions->form_data_2->payment_refunded_sum || $submissions->form_data_1->payment_refunded_sum):
            $chart_data_payment_refunded = array($submissions->form_data_2->payment_refunded_sum, $submissions->form_data_1->payment_refunded_sum);
        endif;
        $submissions->chart_data_payment_refunded = $chart_data_payment_refunded;
        
        $chart_data_payment_canceled = array();
        if($submissions->form_data_2->payment_canceled_sum || $submissions->form_data_1->payment_canceled_sum):
            $chart_data_payment_canceled = array($submissions->form_data_2->payment_canceled_sum, $submissions->form_data_1->payment_canceled_sum);
        endif;
        $submissions->chart_data_payment_canceled = $chart_data_payment_canceled;
        
        $chart_data_form_1 = $this->generate_compare_forms_chart_data($parameter, $parameter->form_id_1);
        $chart_data_form_2 = $this->generate_compare_forms_chart_data($parameter, $parameter->form_id_2);
        
        $submissions->chart_date_range = $chart_data_form_1->chart_date;
        $submissions->chart_form_data_1 = $chart_data_form_1->chart_value;
        $submissions->chart_form_data_2 = $chart_data_form_2->chart_value;
        
        
        
        return $submissions;
    }
    public function generate_compare_forms_chart_data($parameter, $form_id){
        $parameter->form_id = $parameter->form_id_1;
        $req = new stdClass();
        $req->start_date = $parameter->start_date;
        $req->end_date = $parameter->end_date;
        $req->form_id = $form_id;
        return $this->generate_submission_chart_data($req);
        
    }
    
    public function generate_form_compare_data($parameter, $form_id){
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('SUBMISSIONS');
        $payment_table_name = RM_Table_Tech::get_table_name_for('PAYPAL_LOGS');
        $forms_table_name = RM_Table_Tech::get_table_name_for('FORMS');
        $stats_table_name = RM_Table_Tech::get_table_name_for('STATS');
        $fields_table_name = RM_Table_Tech::get_table_name_for('FIELDS');
        // Form 
        $count_interval_string = "BETWEEN '" .date('Y-m-d',strtotime($parameter->start_date)). "' AND '".date('Y-m-d',strtotime($parameter->end_date))."'  ORDER BY $table_name.submission_id DESC ";
        $count_qry = "SELECT COUNT($table_name.submission_id) AS total_submission, COUNT($payment_table_name.id) AS total_payments_count, $forms_table_name.created_on, form_type, form_options, currency FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id LEFT JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $form_id AND CAST($table_name.submitted_on AS date) $count_interval_string";
        $submissions= $wpdb->get_results($count_qry);
        
        $qry_payment_completed = "SELECT COUNT($payment_table_name.id) AS payment_completed_count, SUM($payment_table_name.total_amount) AS payment_completed_sum FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id LEFT JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $form_id AND $payment_table_name.status IN ('Completed','Succeeded','succeeded') AND CAST($table_name.submitted_on AS date) $count_interval_string";
        $payment_completed = $wpdb->get_results($qry_payment_completed);
        
        $qry_payment_pending = "SELECT COUNT($payment_table_name.id) AS payment_pending_count, SUM($payment_table_name.total_amount) AS payment_pending_sum FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id LEFT JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $form_id AND $payment_table_name.status = 'Pending' AND CAST($table_name.submitted_on AS date) $count_interval_string";
        $payment_pending = $wpdb->get_results($qry_payment_pending);
        
        $qry_payment_canceled = "SELECT COUNT($payment_table_name.id) AS payment_canceled_count, SUM($payment_table_name.total_amount) AS payment_canceled_sum FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id LEFT JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $form_id AND $payment_table_name.status = 'Canceled' AND CAST($table_name.submitted_on AS date) $count_interval_string";
        $payment_canceled = $wpdb->get_results($qry_payment_canceled);
        
        $qry_payment_refunded = "SELECT COUNT($payment_table_name.id) AS payment_refunded_count, SUM($payment_table_name.total_amount) AS payment_refunded_sum FROM `$table_name` INNER JOIN `$forms_table_name` ON $forms_table_name.form_id = $table_name.form_id LEFT JOIN `$payment_table_name` ON $payment_table_name.submission_id = $table_name.submission_id WHERE $table_name.form_id = $form_id AND $payment_table_name.status = 'Refunded' AND CAST($table_name.submitted_on AS date) $count_interval_string";
        $payment_refunded = $wpdb->get_results($qry_payment_refunded);
        $form_stats = $this->generate_comparison_stats($parameter, $form_id);
        $form_data = new stdClass();
        $form_data->total_submission = $submissions[0]->total_submission ? $submissions[0]->total_submission : 0;
        $form_data->total_payments_count = $submissions[0]->total_payments_count ? $submissions[0]->total_payments_count : 0;
        $form_data->form_currency = $submissions[0]->currency;
        $form_data->created_on = $submissions[0]->created_on;
        $pages = (array)maybe_unserialize($submissions[0]->form_options);
        $pages = (array)$pages['form_pages'];
        $form_data->total_pages = count($pages) ? count($pages) : 1;
        $form_data->registration_form = $submissions[0]->form_type ? 'Yes' : 'No';
        $form_data->payment_completed_count = $payment_completed[0]->payment_completed_count ? $payment_completed[0]->payment_completed_count : 0;
        $form_data->payment_completed_sum = $payment_completed[0]->payment_completed_sum ? $payment_completed[0]->payment_completed_sum : 0;
        $form_data->payment_pending_count = $payment_pending[0]->payment_pending_count ? $payment_pending[0]->payment_pending_count : 0;
        $form_data->payment_pending_sum = $payment_pending[0]->payment_pending_sum ? $payment_pending[0]->payment_pending_sum : 0;
        $form_data->payment_refunded_count = $payment_refunded[0]->payment_refunded_count ? $payment_refunded[0]->payment_refunded_count : 0;
        $form_data->payment_refunded_sum = $payment_refunded[0]->payment_refunded_sum ? $payment_refunded[0]->payment_refunded_sum : 0;
        $form_data->payment_canceled_count = $payment_canceled[0]->payment_canceled_count ? $payment_canceled[0]->payment_canceled_count : 0;
        $form_data->payment_canceled_sum = $payment_canceled[0]->payment_canceled_sum ? $payment_canceled[0]->payment_canceled_sum : 0;
        $form_data->attachment_count = '';
        $form_data->avg_filling_time = $form_stats->avg_filling_time;
        $form_data->total_view = $form_stats->total_view;
        $form_data->success_rate = $form_stats->success_rate;
        $form_data->total_fields = $form_stats->total_fields ? $form_stats->total_fields : 0;
        return $form_data; 
    }
    public function generate_comparison_stats($parameter, $form_id){
        global $wpdb;
        
        $stats_table_name = RM_Table_Tech::get_table_name_for('STATS');
        $fields_table_name = RM_Table_Tech::get_table_name_for('FIELDS');
        // Form 
        $data = new stdClass;
        $count_interval_string = "BETWEEN '" .strtotime($parameter->start_date). "' AND '".strtotime($parameter->end_date)."'";
        
        $count_qry = "SELECT AVG($stats_table_name.time_taken) AS avg_filling_time, COUNT($stats_table_name.stat_id) AS total_view FROM `$stats_table_name` WHERE $stats_table_name.form_id = $form_id AND $stats_table_name.visited_on $count_interval_string";
        $submissions = $wpdb->get_results($count_qry);
        
        $success_query = "SELECT COUNT($stats_table_name.submission_id) AS success_submission FROM `$stats_table_name` WHERE $stats_table_name.form_id = $form_id AND $stats_table_name.submission_id IS NOT NULL AND $stats_table_name.visited_on $count_interval_string";
        $success = $wpdb->get_results($success_query);
        
        $fields_query = "SELECT COUNT($fields_table_name.field_id) AS total_fields FROM `$fields_table_name` WHERE $fields_table_name.form_id = $form_id";
        $fields_counts = $wpdb->get_results($fields_query);
        
        $success_rate = '0%';
        if($submissions[0]->total_view > 0){
            $success_rate = round ( (($success[0]->success_submission / $submissions[0]->total_view) *100) , 2).'%';
        }
        $data->avg_filling_time = round($submissions[0]->avg_filling_time, 2).'S';
        $data->total_view = $submissions[0]->total_view;
        $data->success_rate = $success_rate;
        $data->total_fields = $fields_counts[0]->total_fields;
        return $data;
    }
    public function generate_login_parameter($filter){
        //$req = new stdClass;
        
        if(isset($filter->filter_date) && isset($filter->status)){
            $filter->start_date = date('Y-m-d', strtotime($filter->start_date));
            $filter->end_date = date('Y-m-d', strtotime($filter->end_date));
            $filter->form_id = $filter->status;
        }
        elseif(isset($filter->filter_date) && !isset($filter->status)){
            $filter->start_date = date('Y-m-d', strtotime($filter->start_date));
            $filter->end_date = date('Y-m-d', strtotime($filter->end_date));
            $filter->form_id = 'all';
        }
        elseif(!isset($filter->filter_date) && isset($filter->status)){
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            $filter->form_id = $filter->status;
        }
        else{
            $filter->start_date = date('Y-m-d');
            $filter->end_date = date('Y-m-d');
            $filter->form_id = 'all';
        }
        return $filter;
    }
    
    public function get_logins($req, $limit=5, $column='*'){
        
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('LOGIN_LOG');
        $qry = "";
        $interval_string = "";
        $limit_string = "";
        $status = "all";
        $results = new stdClass;
        
        $para = new stdClass();
        $para->start_date = $req->start_date;
        $para->end_date = $req->end_date;
        $para->status = $req->status;
        
        if($req->status !='all'){
            $status = $req->status=='success' ? 1 : 0;
        }
        if($limit != 0){
            $limit_string = "LIMIT ".$limit;
        }
        $interval_string = "BETWEEN '" .date('Y-m-d',strtotime($req->start_date)). "' AND '".date('Y-m-d',strtotime($req->end_date))."'  ORDER BY `id` DESC ".$limit_string;
        $count_interval_string = "BETWEEN '" .date('Y-m-d',strtotime($req->start_date)). "' AND '".date('Y-m-d',strtotime($req->end_date))."'  ORDER BY `id` DESC ";
        if($req->status =='all'){
            $qry = "SELECT $column FROM `$table_name` WHERE CAST(time AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` WHERE  CAST(time AS date) $count_interval_string";
            $logins = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }else{
            $qry = "SELECT $column FROM `$table_name` WHERE `status` = '$status' AND CAST(time AS date) $interval_string";
            $count_qry = "SELECT $column FROM `$table_name` WHERE `status` = '$status' AND CAST(time AS date) $count_interval_string";
            $logins = $wpdb->get_results($qry);
            $sub_count= count($wpdb->get_results($count_qry));
        }
        $results->logins = $logins;
        $results->login_count = $sub_count;
        $results->login_chart = $this->generate_login_chart_data($para);
        return $results; 
    }
    
    public function prepare_login_export_data($data){
        $export_data = array();
        
        $export_data[0]['l_id']= 'ID';
        $export_data[0]['l_email']= 'Email';
        $export_data[0]['l_username']= 'Username Used';
        $export_data[0]['l_time']= 'Date';
        $export_data[0]['l_status']= 'Status';
        $export_data[0]['l_ip']= 'IP';
        $export_data[0]['l_browser']= 'Chrome';
        $export_data[0]['l_type']= 'Type';
        $export_data[0]['l_ban']= 'Ban';
        $export_data[0]['l_result']= 'Result';
        $export_data[0]['l_reason']= 'Failure Reason';
        $export_data[0]['l_ban_til']= 'Ban Til';
        $export_data[0]['l_url']= 'Login Url';
        $export_data[0]['l_social']= 'Social Type';
        
        if(isset($data->logins) && $data->login_count):
            $row_count = 1;
            foreach($data->logins as $login):
                $export_data[$row_count]['l_id'] = $login->id;
                $export_data[$row_count]['l_email']= $login->email;
                $export_data[$row_count]['l_username']= $login->username_used;
                $export_data[$row_count]['l_time']= esc_html(RM_Utilities::localize_time($login->time,'j M Y, h:i a'));
                $export_data[$row_count]['l_status']= $login->status;
                $export_data[$row_count]['l_ip']= $login->ip;
                $export_data[$row_count]['l_browser']= $login->browser;
                $export_data[$row_count]['l_type']= $login->type;
                $export_data[$row_count]['l_ban']= $login->ban;
                $export_data[$row_count]['l_result']= $login->result;
                $export_data[$row_count]['l_reason']= $login->failure_reason;
                $export_data[$row_count]['l_ban_til']= $login->ban_til;
                $export_data[$row_count]['l_url']= $login->login_url;
                $export_data[$row_count]['l_social']= $login->social_type;
                $row_count++;
            endforeach;
        endif;
        
        return $export_data;
        
    }
    public function generate_login_chart_data($req){
        $chart_data = new stdClass;
        $chart_date = array();
        $chart_success = array();
        $chart_failure = array();
        $status = 'all';
        if($req->status !='all'){
            $status = $req->status=='success' ? 1 : 0;
        }
        global $wpdb;
        $table_name = RM_Table_Tech::get_table_name_for('LOGIN_LOG');
        
        $start_date = new DateTime(date('Y-m-d',strtotime($req->start_date)));
        $end_date = new DateTime(date('Y-m-d',strtotime($req->end_date)));
        $diff = strtotime($req->end_date) - strtotime($req->start_date);
        $req->start_date = date('Y-m-d',strtotime($req->start_date . ' -1 day'));
        $day = abs(round($diff / 86400)) + 1;
        if($day <= 1) {
            $day =2;
            $req->start_date = date('Y-m-d',strtotime($req->start_date . ' -1 day'));
        }
        while($day > 0 ){
            $req->start_date = date('Y-m-d',strtotime($req->start_date . ' +1 day'));
            $chart_date[] = date("j M", strtotime($req->start_date));
            $count_interval_string = "= '" .date('Y-m-d',strtotime($req->start_date))."'";
            if($req->status =='all'){
                $success_qry = "SELECT * FROM `$table_name` WHERE `status` = 1 AND CAST(time AS date) $count_interval_string";
                $success_count= count($wpdb->get_results($success_qry));
                $chart_success[] = $success_count;
                
                $failure_qry = "SELECT * FROM `$table_name` WHERE `status` = 0 AND CAST(time AS date) $count_interval_string";
                $failure_count = count($wpdb->get_results($failure_qry));
                $chart_failure[] = $failure_count;
            }else{
                if($req->status == 'success'):
                    $count_qry = "SELECT * FROM `$table_name` WHERE `status` = 1 AND CAST(time AS date) $count_interval_string";
                    $sub_count= count($wpdb->get_results($count_qry));
                    $chart_success[] = $sub_count;
                else:
                    $count_qry = "SELECT * FROM `$table_name` WHERE `status` = 0 AND CAST(time AS date) $count_interval_string";
                    $sub_count= count($wpdb->get_results($count_qry));
                    $chart_failure[] = $sub_count;
                endif;
                
            }
            
            $day--;
        }
        $chart_data->chart_date = $chart_date;
        $chart_data->chart_success = $chart_success;
        $chart_data->chart_failure = $chart_failure;
        return $chart_data;
    }
}