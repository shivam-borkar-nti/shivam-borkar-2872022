<?php

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	class MoWpnsDB
	{
		private $transactionTable;
		private $emailAuditTable;
		function __construct()
		{
			global $wpdb;
			$this->transactionTable		= $wpdb->base_prefix.'mo2f_network_transactions';
			$this->emailAuditTable		= $wpdb->base_prefix.'mo2f_network_email_sent_audit';
		}

		function mo_plugin_activate()
		{
			global $wpdb;
			if(!get_site_option('mo_wpns_dbversion')||get_site_option('mo_wpns_dbversion')<MoWpnsConstants::DB_VERSION){
				update_site_option('mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				$this->generate_tables();
			} else {
				$current_db_version = get_site_option('mo_wpns_dbversion');
				if($current_db_version < MoWpnsConstants::DB_VERSION){
					update_site_option('mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
					
				}
			}
		}

		function generate_tables(){
			global $wpdb;
			
			// $tableName = $this->transactionTable;
			// if($wpdb->get_var("show tables like '$tableName'") != $tableName) 
			// {
			// 	$sql = "CREATE TABLE " . $tableName . " (
			// 	`id` bigint NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL ,  `username` mediumtext NOT NULL ,
			// 	`type` mediumtext NOT NULL , `url` mediumtext NOT NULL , `status` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );";
			// 	dbDelta($sql);
			// }

			// $tableName = $this->emailAuditTable;
			// if($wpdb->get_var("show tables like '$tableName'") != $tableName) 
			// {
			// 	$sql = "CREATE TABLE " . $tableName . " (
			// 	`id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `username` mediumtext NOT NULL, `reason` mediumtext, `created_timestamp` int, UNIQUE KEY id (id) );";
			// 	dbDelta($sql);
			// }

			// $tableName = $this->attackLogs;
			// if($wpdb->get_var("show tables like '$tableName'") != $tableName) 
			// {
			// 	$sql = "create table ". $tableName ." (
			// 			ip varchar(20),
			// 			type varchar(20),
			// 			time bigint,
			// 			input mediumtext );";
			// 			$results = $wpdb->get_results($sql);
				
			// }
			// $result= $wpdb->get_var("SHOW COLUMNS FROM `$tableName` LIKE 'scan_mode'");
			// if(is_null($result)){
			// 	$sql = "ALTER TABLE  `$tableName` ADD  `scan_mode` mediumtext NOT NULL DEFAULT 'Custom Scan' AFTER  `id` ;";
			// 	$results1 = $wpdb->query($sql);
			// }
	        
		}
	
		function get_email_audit_count($ipAddress,$username)
		{
			global $wpdb;
			return $wpdb->get_var( "SELECT COUNT(*) FROM ".$this->emailAuditTable." WHERE ip_address = '".$ipAddress."' AND 
			username='".$username."'" );
		}

		function insert_email_audit($ipAddress,$username,$reason)
		{
			global $wpdb;
			$wpdb->insert( 
				$this->emailAuditTable, 
				array( 
					'ip_address' => $ipAddress,
					'username' => $username,
					'reason' => $reason,
					'created_timestamp' => current_time( 'timestamp' )
				)
			);
			return;
		}

		function insert_transaction_audit($ipAddress,$username,$type,$status,$url=null)
		{
			global $wpdb;
			$data 		= array( 
							'ip_address' 		=> $ipAddress, 
							'username' 	 		=> $username,
							'type' 		 		=> $type,
							'status' 	 		=> $status,
							'created_timestamp' => current_time( 'timestamp' )
						);
			$data['url'] = is_null($url) ? '' : $url;  
			$wpdb->insert(  $this->transactionTable, $data);
			return;
		}

		function get_transasction_list()
		{
			global $wpdb;
			return $wpdb->get_results( "SELECT ip_address, username, type, status, created_timestamp FROM ".$this->transactionTable." order by id desc limit 5000" );
		}

		function get_login_transaction_report()
		{
			global $wpdb;
			return $wpdb->get_results( "SELECT ip_address, username, status, created_timestamp FROM ".$this->transactionTable." WHERE type='User Login' order by id desc limit 5000" );
		}

		function get_error_transaction_report()
		{
			global $wpdb;
			return $wpdb->get_results( "SELECT ip_address, username, url, type, created_timestamp FROM ".$this->transactionTable." WHERE type <> 'User Login' order by id desc limit 5000" );
		}

		function update_transaction_table($where,$update)
		{
			global $wpdb;

			$sql = "UPDATE ".$this->transactionTable." SET ";
			$i = 0;
			foreach($update as $key=>$value)
			{
				if($i%2!=0)
					$sql .= ' , ';
				$sql .= $key."='".$value."'";
				$i++;
			}
			$sql .= " WHERE ";
			$i = 0;
			foreach($where as $key=>$value)
			{
				if($i%2!=0)
					$sql .= ' AND ';
				$sql .= $key."='".$value."'";
				$i++;
			}
			
			$wpdb->query($sql);
			return;
		}

		function get_count_of_attacks_blocked(){
			global $wpdb;
			return $wpdb->get_var( "SELECT COUNT(*) FROM ".$this->transactionTable." WHERE status = '".MoWpnsConstants::FAILED."' OR status = '".MoWpnsConstants::PAST_FAILED."'" );
		}

		function get_failed_transaction_count($ipAddress)
		{
			global $wpdb;
			return $wpdb->get_var( "SELECT COUNT(*) FROM ".$this->transactionTable." WHERE ip_address = '".$ipAddress."'
			AND status = '".MoWpnsConstants::FAILED."'" );
		}

		function delete_transaction($ipAddress)
		{
			global $wpdb;
			$wpdb->query( 
				"DELETE FROM ".$this->transactionTable." 
				WHERE ip_address = '".$ipAddress."' AND status='".MoWpnsConstants::FAILED."'"
			);
			return;
		}	
	}