<?php
class MoWpnsHandler
{

	function add_transactions($ipAddress, $username, $type, $status, $url=null)
	{
		global $wpnsDbQueries;
		$wpnsDbQueries->insert_transaction_audit($ipAddress, $username, $type, $status, $url);
	}

	function get_login_transaction_report()
	{
		global $wpnsDbQueries;
		return $wpnsDbQueries->get_login_transaction_report();
	}
	
	function get_error_transaction_report()
	{
		global $wpnsDbQueries;
		return $wpnsDbQueries->get_error_transaction_report();
	}


	function get_all_transactions()
	{
		global $wpnsDbQueries;
		return $wpnsDbQueries->get_transasction_list();
	}
	
	function move_failed_transactions_to_past_failed($ipAddress)
	{
		global $wpnsDbQueries;
		$wpnsDbQueries->update_transaction_table(array('status'=>MoWpnsConstants::FAILED),
			array('ip_address'=>$ipAddress,'status'=>MoWpnsConstants::PAST_FAILED));
	}
	
	function remove_failed_transactions($ipAddress)
	{
		global $wpnsDbQueries;
		$wpnsDbQueries->delete_transaction($ipAddress);	
	}
	
	function get_failed_attempts_count($ipAddress)
	{
		global $wpnsDbQueries;
		$count = $wpnsDbQueries->get_failed_transaction_count($ipAddress);
		if($count)
		{
			$count = intval($count);
			return $count;
		}
		return 0;
	}
	
} ?>