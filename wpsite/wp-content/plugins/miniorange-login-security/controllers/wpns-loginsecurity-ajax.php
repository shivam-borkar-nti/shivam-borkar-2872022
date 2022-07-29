<?php
class wpns_ajax
{
	function __construct(){
		//add comment here
		add_action( 'admin_init'  , array( $this, 'mo_login_security_ajax' ) );
	}

	function mo_login_security_ajax(){
		 
		add_action( 'wp_ajax_wpns_login_security', array($this,'wpns_login_security') );
	}

		function wpns_login_security(){
			switch($_POST['wpns_loginsecurity_ajax'])
			{
				case "wpns_bruteforce_form":
					$this->wpns_handle_bf_configuration_form();	break;
				case "wpns_save_captcha":
					$this->wpns_captcha_settings();break;
				case "save_strong_password":
					$this->wpns_strong_password_settings();break;
					case 'wpns_ManualIPBlock_form':
					$this->wpns_handle_IP_blocking();break;
				case 'wpns_WhitelistIP_form':
					$this->wpns_whitelist_ip(); break;
				case 'wpns_waf_settings_form':
					$this->wpns_waf_settings_form(); break;
				case 'wpns_waf_rate_limiting_form':
					$this->wpns_waf_rate_limiting_form(); break;	
				case 'wpns_ip_lookup':
					$this->wpns_ip_lookup(); 	break;	
			}
		}


	   function wpns_handle_bf_configuration_form(){

	   		$nonce = $_POST['nonce'];
	   		if ( ! wp_verify_nonce( $nonce, 'wpns-brute-force' ) ){
	   			wp_send_json('ERROR');
	   			return;
	   		}
	   		$brute_force        = $_POST['bf_enabled/disabled'];
	  		if($brute_force == 'true'){$brute_force = "on";}else if($brute_force == 'false') {$brute_force = "";}  
			$login_attempts 	= $_POST['allwed_login_attempts'];
			$blocking_type  	= $_POST['time_of_blocking_type'];
			$blocking_value 	= isset($_POST['time_of_blocking_val'])	 ? $_POST['time_of_blocking_val']	: false;
			$show_login_attempts= $_POST['show_remaining_attempts'];
			if($show_login_attempts == 'true'){$show_login_attempts = "on";} else if($show_login_attempts == 'false') { $show_login_attempts = "";}
			if($brute_force == 'on' && $login_attempts == "" ){
				wp_send_json('empty');
				return;
			}
	  		update_site_option( 'mo2f_enable_brute_force' 		, $brute_force 		  	  );
			update_site_option( 'mo2f_allwed_login_attempts'		, $login_attempts 		  );
			update_site_option( 'mo_wpns_time_of_blocking_type'	, $blocking_type 		  );
			update_site_option( 'mo_wpns_time_of_blocking_val' 	, $blocking_value   	  );
			update_site_option('mo2f_show_remaining_attempts' 	, $show_login_attempts    );
			if($brute_force == "on"){
				wp_send_json('true');
			}
			else if($brute_force == ""){
				wp_send_json('false');
			} 
			
		}
	function wpns_handle_IP_blocking()
	{
	
		global $mo2f_dirName;	
		if(!wp_verify_nonce($_POST['nonce'],'manualIPBlockingNonce'))
		{
			echo "NonceDidNotMatch";
			exit;
		}
		else
		{	
			include_once($mo2f_dirName.'controllers'.DIRECTORY_SEPARATOR.'ip-blocking.php');
		}
	}
	function wpns_whitelist_ip()
	{
		global $mo2f_dirName;
		if(!wp_verify_nonce($_POST['nonce'],'IPWhiteListingNonce'))
		{
			echo "NonceDidNotMatch";
			exit;
		}
		else
		{
			include_once($mo2f_dirName.'controllers'.DIRECTORY_SEPARATOR.'ip-blocking.php');
		}
	}
	
	function wpns_ip_lookup()
	{

		if(!wp_verify_nonce($_POST['nonce'],'IPLookUPNonce'))
		{
			echo "NonceDidNotMatch";
			exit;
		}
		else
		{
			$ip  = $_POST['IP'];
	        if(!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/',$ip))
			{
				echo("INVALID_IP_FORMAT");
				exit;
			}
			else if(! filter_var($ip, FILTER_VALIDATE_IP)){
				echo("INVALID_IP");
				exit;
			}
	        $result=@json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip),true);
			$hostname 	= gethostbyaddr($result["geoplugin_request"]);
			try{
	            $timeoffset	= timezone_offset_get(new DateTimeZone($result["geoplugin_timezone"]),new DateTime('now'));
	            $timeoffset = $timeoffset/3600;

	        }catch(Exception $e){
	            $result["geoplugin_timezone"]="";
	            $timeoffset="";
	        }
			$ipLookUpTemplate  = MoWpnsConstants::IP_LOOKUP_TEMPLATE;
			if($result['geoplugin_request']==$ip) {

	            $ipLookUpTemplate = str_replace("{{status}}", $result["geoplugin_status"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{ip}}", $result["geoplugin_request"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{region}}", $result["geoplugin_region"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{country}}", $result["geoplugin_countryName"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{city}}", $result["geoplugin_city"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{continent}}", $result["geoplugin_continentName"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{latitude}}", $result["geoplugin_latitude"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{longitude}}", $result["geoplugin_longitude"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{timezone}}", $result["geoplugin_timezone"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{curreny_code}}", $result["geoplugin_currencyCode"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{curreny_symbol}}", $result["geoplugin_currencySymbol"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{per_dollar_value}}", $result["geoplugin_currencyConverter"], $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{hostname}}", $hostname, $ipLookUpTemplate);
	            $ipLookUpTemplate = str_replace("{{offset}}", $timeoffset, $ipLookUpTemplate);

	            $result['ipDetails'] = $ipLookUpTemplate;
	        }else{
	            $result["ipDetails"]["status"]="ERROR";
	        }
	      	wp_send_json( $result );
		}
	}
	function wpns_waf_settings_form()
	{
		$dir_name =  dirname(__FILE__);
		$dir_name1 = explode('wp-content', $dir_name);
		$dir_name = $dir_name1[0];
		$filepath = str_replace('\\', '/', $dir_name1[0]);
		$fileName = $filepath.'/wp-includes/mo-waf-config.php';
		
		if(!file_exists($fileName))
		{
			$file = fopen($fileName, "a+");
			$string = "<?php".PHP_EOL;
			$string .= '$SQL=1;'.PHP_EOL;
			$string .= '$XSS=1;'.PHP_EOL;
			$string .= '$RCE=0;'.PHP_EOL;
			$string .= '$LFI=0;'.PHP_EOL;
			$string .= '$RFI=0;'.PHP_EOL;
			$string .= '$RateLimiting=1;'.PHP_EOL;
			$string .= '$RequestsPMin=120;'.PHP_EOL;
			$string .= '$actionRateL="ThrottleIP";'.PHP_EOL;
			$string .= '?>'.PHP_EOL;
			
			fwrite($file, $string);
			fclose($file);
		}
		else
		{
			if(!is_writable($fileName) or !is_readable($fileName))
			{
				echo "FilePermissionDenied";
				exit;
			}
		}
		
		if(!wp_verify_nonce($_POST['nonce'],'WAFsettingNonce'))
		{
			echo "NonceDidNotMatch";
			exit;
		}
		else
		{
			switch ($_POST['optionValue']) {
				case "SQL": 
					$this->savesql();			break;
				case "XSS": 
					$this->savexss();			break;
				case "RCE": 
					$this->saverce();			break;
				case "RFI": 
					$this->saverfi();			break;
				case "LFI": 
					$this->savelfi();			break;
				case "WAF": 
					$this->saveWAF();			break;
				case "HWAF": 
					$this->saveHWAF();			break;
				case "backupHtaccess":
					$this->backupHtaccess();	break;
				case "limitAttack":
					$this->limitAttack();		break;
				default:
					break;
			}
				
		}	

	}
    function wpns_waf_rate_limiting_form()
	{
		if(!wp_verify_nonce($_POST['nonce'],'RateLimitingNonce'))
		{
			echo "NonceDidNotMatch";
			exit;
		}
		else
		{
			if(get_site_option('WAFEnabled') != 1)
			{
				echo "WAFNotEnabled";
				exit;
			}

			if($_POST['Requests']!='')
			{
				if(is_numeric($_POST['Requests']))
				{
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';
				
				$file = file_get_contents($fileName);
				$data = $file;
			
				$req  =	sanitize_text_field($_POST['Requests']);
				if($req >1)
				{
					update_site_option('Rate_request',$req);
					if(isset($_POST['rateCheck']))
					{
						if($_POST['rateCheck'] == 'on')
						{
							update_site_option('Rate_limiting','1');
							echo "RateEnabled";
							if(strpos($file, 'RateLimiting')!=false)
							{
								$file = str_replace('$RateLimiting=0;', '$RateLimiting=1;', $file);
								$data = $file;
								file_put_contents($fileName,$file);	
								
							}
							else
							{
								$content = explode('?>', $file);
								$file = $content[0];
								$file .= PHP_EOL;
								$file .= '$RateLimiting=1;'.PHP_EOL;
								$file .='?>';
								file_put_contents($fileName,$file);
								$data = $file;
							}
						

						}
					}	
					else
					{
						update_site_option('Rate_limiting','0');
						echo "Ratedisabled";
						if(strpos($file, 'RateLimiting')!=false)
						{
							$file = str_replace('$RateLimiting=1;', '$RateLimiting=0;', $file);
							$data = $file;
							file_put_contents($fileName,$file);	
						}
						else
						{
							$content = explode('?>', $file);
							$file = $content[0];
							$file .= PHP_EOL;
							$file .= '$RateLimiting=0;'.PHP_EOL;
							$file .='?>';
							file_put_contents($fileName,$file);
							$data = $file;
						}

					}				

					
					$file = $data;
					if(strpos($file, 'RequestsPMin')!=false)
					{
						$content = explode(PHP_EOL, $file);
						$con = '';
						$len =  sizeof($content);
						
						for($i=0;$i<$len;$i++)
						{
							if(strpos($content[$i], 'RequestsPMin')!=false)
							{
								$con.='$RequestsPMin='.$req.';'.PHP_EOL;
							}
							else
							{
								$con .= $content[$i].PHP_EOL;
							}
						}
					
						file_put_contents($fileName,$con);
						$data = $con;
						
					}

					else
					{
						$content = explode('?>', $file);
						$file = $content[0];
						$file .= PHP_EOL;
						$file .= '$RequestsPMin='.$req.';'.PHP_EOL;
						$file .='?>';
						file_put_contents($fileName,$file);
						$data = $file;
					}
				
					if($_POST['actionOnLimitE']=='BlockIP' || $_POST['actionOnLimitE'] == 1)
					{
						update_site_option('actionRateL',1);

						$file = $data;
						if(strpos($file, 'actionRateL')!=false)
						{
							$content = explode(PHP_EOL, $file);
							$con = '';
							foreach ($content as $line => $lineV) {
								if(strpos($lineV, 'actionRateL')!=false)
								{
									$con.='$actionRateL="BlockIP";'.PHP_EOL;
								}
								else
								{
									$con .= $lineV.PHP_EOL;
								}
							}
							file_put_contents($fileName,$con);	
						}
						else
						{
							$content = explode('?>', $file);
							$file = $content[0];
							$file .= PHP_EOL;
							$file .= '$actionRateL="BlockIP";'.PHP_EOL;
							$file .='?>';
							file_put_contents($fileName,$file);
							$file = $data;
						}
					}
					else if($_POST['actionOnLimitE']=='ThrottleIP' || $_POST['actionOnLimitE'] == 0)
					{

						$file = $data;
						update_site_option('actionRateL',0);
						if(strpos($file, 'actionRateL')!=false)
						{
							$content = explode(PHP_EOL, $file);
							$con = '';
							foreach ($content as $line => $lineV) {
								if(strpos($lineV, 'actionRateL')!=false)
								{
									$con.='$actionRateL="ThrottleIP";'.PHP_EOL;
								}
								else
								{
									$con .= $lineV.PHP_EOL;
								}
							}
							file_put_contents($fileName,$con);	
						}
						else
						{
							$content = explode('?>', $file);
							$file = $content[0];
							$file .= PHP_EOL;
							$file .= '$actionRateL="ThrottleIP";'.PHP_EOL;
							$file .='?>';
							file_put_contents($fileName,$file);
						}	
					}

			}
			exit;
		}
		
			
			
		}
		echo("Error");
		exit;
		}
		
		
	}

	private function saveWAF()
	{	
		if(isset($_POST['pluginWAF']))
		{
			if($_POST['pluginWAF']=='on')
			{
				update_site_option('WAF','PluginLevel');
				update_site_option('WAFEnabled','1');
				echo("PWAFenabled");exit;
			}
		}
		else
		{
			update_site_option('WAFEnabled','0');
			update_site_option('WAF','wafDisable');
			echo("PWAFdisabled");exit;
		}
	}
	private function saveHWAF()
	{
		if(!function_exists('mysqli_connect'))
		{
			echo "mysqliDoesNotExit";
			exit;
		}
		if(isset($_POST['htaccessWAF']))
		{
			if($_POST['htaccessWAF']=='on')
			{
				update_site_option('WAF','HtaccessLevel');
				update_site_option('WAFEnabled','1');
				$dir_name =  dirname(__FILE__);
				$dirN = $dir_name;
				$dirN = str_replace('\\', '/', $dirN);
				$dirN = str_replace('controllers', 'handler', $dirN);
				
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$dir_name1 = str_replace('\\', '/', $dir_name1[0]);
				$dir_name .='.htaccess';
			 	$file =  file_get_contents($dir_name);
			 	if(strpos($file, 'php_value auto_prepend_file')!=false)
			 	{
			 		echo("WAFConflicts");
			 		exit;
			 	}

			 	$cont 	 = $file.PHP_EOL.'# BEGIN miniOrange WAF'.PHP_EOL;
			 	$cont 	.= 'php_value auto_prepend_file '.$dir_name1.'mo-check.php'.PHP_EOL;
			 	$cont 	.= '# END miniOrange WAF'.PHP_EOL;
			 	file_put_contents($dir_name, $cont);

				$filecontent = file_get_contents($dir_name);

				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'mo-check.php';
				$file = fopen($fileName, 'w+');
				$dir_name = dirname(__FILE__);
				$filepath = str_replace('\\', '/', $dir_name);
				$filepath = explode('controllers', $filepath);
				$filepath = $filepath[0].'handler'.DIRECTORY_SEPARATOR.'WAF'.DIRECTORY_SEPARATOR.'mo-waf.php';	

				$string   = '<?php'.PHP_EOL;
				$string  .= 'if(file_exists("'.$filepath.'"))'.PHP_EOL;
				$string  .= 'include_once("'.$filepath.'");'.PHP_EOL;
				$string  .= '?>'.PHP_EOL;
							
				fwrite($file, $string);
				fclose($file);

				if(strpos($filecontent,'mo-check.php')!=false)
				{
					echo "HWAFEnabled";
					exit;
				}
				else
				{
					echo "HWAFEnabledFailed";
					exit;
				}
			}
		}
		else
		{
			update_site_option('WAF','wafDisable');
			if(isset($_POST['pluginWAF']))
			{
				if($_POST['pluginWAF'] == 'on')
				{
					update_site_option('WAFEnabled',1);
					update_site_option('WAF','PluginLevel');
				}
			}
			else
				update_site_option('WAFEnabled',0);
			$dir_name 	=  dirname(__FILE__);
			$dirN 		= $dir_name;
			$dirN 		= str_replace('\\', '/', $dirN);
			$dirN 		= explode('wp-content', $dirN);
			$dir_name1 	= explode('wp-content', $dir_name);
			$dir_name 	= $dir_name1[0];
			$dir_name1 	= str_replace('\\', '/', $dir_name1[0]);
			$dir_name00 = $dir_name1; 
			$dir_name1 .='.htaccess';
		 	$file 		=  file_get_contents($dir_name1);

		 	$cont 	 = PHP_EOL.'# BEGIN miniOrange WAF'.PHP_EOL;
		 	$cont 	.= 'php_value auto_prepend_file '.$dir_name00.'mo-check.php'.PHP_EOL;
		 	$cont 	.= '# END miniOrange WAF'.PHP_EOL;
		 	$file =str_replace($cont,'',$file);
			file_put_contents($dir_name1, $file);

			$filecontent = file_get_contents($dir_name1);
			if(strpos($filecontent,'mo-check.php')==false)
			{
				echo "HWAFdisabled";
				exit;
			}
			else
			{
				echo "HWAFdisabledFailed";
				exit;
			}
		}


	}
	private function savesql()
	{
		if(isset($_POST['SQL']))
		{
			if($_POST['SQL']=='on')
			{
				update_site_option('SQLInjection',1);
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, 'SQL')!=false)
			{
				$file = str_replace('$SQL=0;', '$SQL=1;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$SQL=1;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}
			echo("SQLenable");
			exit;

			}
		}
		else
		{
			update_site_option('SQLInjection',0);

			$dir_name =  dirname(__FILE__);
			$dir_name1 = explode('wp-content', $dir_name);
			$dir_name = $dir_name1[0];
			$filepath = str_replace('\\', '/', $dir_name1[0]);
			$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, '$SQL')!=false)
			{
				$file = str_replace('$SQL=1;', '$SQL=0;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$SQL=0;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}
	
			echo("SQLdisable");
			exit;

		}

	}
	private function saverce()
	{
		if(isset($_POST['RCE']))
		{
			if($_POST['RCE']=='on')
			{
				update_site_option('RCEAttack',1);
				
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';

				$file = file_get_contents($fileName);
				if(strpos($file, 'RCE')!=false)
				{
					$file = str_replace('$RCE=0;', '$RCE=1;', $file);
					file_put_contents($fileName,$file);	
				}
				else
				{
					$content = explode('?>', $file);
					$file = $content[0];
					$file .= PHP_EOL;
					$file .= '$RCE=1;'.PHP_EOL;
					$file .='?>';
					file_put_contents($fileName,$file);
				}
				echo("RCEenable");
				exit;
			}
		}
		else
		{
			update_site_option('RCEAttack',0);

			$dir_name =  dirname(__FILE__);
			$dir_name1 = explode('wp-content', $dir_name);
			$dir_name = $dir_name1[0];
			$filepath = str_replace('\\', '/', $dir_name1[0]);
			$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, '$RCE')!=false)
			{
				$file = str_replace('$RCE=1;', '$RCE=0;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$RCE=0;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}	
			echo("RCEdisable");
			exit;

		}

	}
	private function savexss()
	{
		if(isset($_POST['XSS']))
		{
			if($_POST['XSS']=='on')
			{
				update_site_option('XSSAttack',1);
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';
				
				$file = file_get_contents($fileName);
				if(strpos($file, 'XSS')!=false)
				{
					$file = str_replace('$XSS=0;', '$XSS=1;', $file);
					file_put_contents($fileName,$file);	
				}
				else
				{
					$content = explode('?>', $file);
					$file = $content[0];
					$file .= PHP_EOL;
					$file .= '$XSS=1;'.PHP_EOL;
					$file .='?>';
					file_put_contents($fileName,$file);
				}
				echo("XSSenable");
				exit;
			}
		}
		else
		{
			update_site_option('XSSAttack',0);
			$dir_name =  dirname(__FILE__);
			$dir_name1 = explode('wp-content', $dir_name);
			$dir_name = $dir_name1[0];
			$filepath = str_replace('\\', '/', $dir_name1[0]);
			$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, '$XSS')!=false)
			{
				$file = str_replace('$XSS=1;', '$XSS=0;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$XSS=0;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}	
			echo("XSSdisable");
			exit;	
		}

	}
	private function savelfi()
	{
		if(isset($_POST['LFI']))
		{
			if($_POST['LFI']=='on')
			{
				update_site_option('LFIAttack',1);
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';
		
				$file = file_get_contents($fileName);
				if(strpos($file, 'LFI')!=false)
				{
					$file = str_replace("LFI=0;", "LFI=1;", $file);
					file_put_contents($fileName,$file);	
				}
				else
				{
					$content = explode('?>', $file);
					$file = $content[0];
					$file .= PHP_EOL;
					$file .= '$LFI=1;'.PHP_EOL;
					$file .='?>';
					file_put_contents($fileName,$file);
				}
				$file = file_get_contents($fileName);
				
				echo("LFIenable");
				exit;
			}
		}
		else
		{
			update_site_option('LFIAttack',0);
			$dir_name =  dirname(__FILE__);
			$dir_name1 = explode('wp-content', $dir_name);
			$dir_name = $dir_name1[0];
			$filepath = str_replace('\\', '/', $dir_name1[0]);
			$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, '$LFI')!=false)
			{
				$file = str_replace('$LFI=1;', '$LFI=0;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$LFI=0;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}
			echo("LFIdisable");
			exit;		
		}

	}
	private function saverfi()
	{
		if(isset($_POST['RFI']))
		{
			if($_POST['RFI']=='on')
			{
				update_site_option('RFIAttack',1);
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';
				
				$file = file_get_contents($fileName);
				if(strpos($file, 'RFI')!=false)
				{
					$file = str_replace('$RFI=0;', '$RFI=1;', $file);
					file_put_contents($fileName,$file);	
				}
				else
				{
					$content = explode('?>', $file);
					$file = $content[0];
					$file .= PHP_EOL;
					$file .= '$RFI=1;'.PHP_EOL;
					$file .='?>';
					file_put_contents($fileName,$file);
				}
				echo("RFIenable");
				exit;
			}
		}
		else
		{
			update_site_option('RFIAttack',0);
			$dir_name =  dirname(__FILE__);
			$dir_name1 = explode('wp-content', $dir_name);
			$dir_name = $dir_name1[0];
			$filepath = str_replace('\\', '/', $dir_name1[0]);
			$fileName = $filepath.'/wp-includes/mo-waf-config.php';

			$file = file_get_contents($fileName);
			if(strpos($file, '$RFI')!=false)
			{
				$file = str_replace('$RFI=1;', '$RFI=0;', $file);
				file_put_contents($fileName,$file);	
			}
			else
			{
				$content = explode('?>', $file);
				$file = $content[0];
				$file .= PHP_EOL;
				$file .= '$RFI=0;'.PHP_EOL;
				$file .='?>';
				file_put_contents($fileName,$file);
			}	
			echo("RFIdisable");
			exit;		
		}

	}
	private function saveRateL()
	{
		
		if($_POST['time']!='' && $_POST['req']!='')
		{
			if(is_numeric($_POST['time']) && is_numeric($_POST['req']))
			{
				$dir_name =  dirname(__FILE__);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$filepath = str_replace('\\', '/', $dir_name1[0]);
				$fileName = $filepath.'/wp-includes/mo-waf-config.php';
				
				$file = file_get_contents($fileName);
				$data = $file;
				$time = sanitize_text_field($_POST['time']);
				$req  =	sanitize_text_field($_POST['req']);
				if($time>0 && $req >0)
				{
					update_site_option('Rate_time',$time);
					update_site_option('Rate_request',$req);
					update_site_option('Rate_limiting','1');

					if(strpos($file, 'RateLimiting')!=false)
					{
						$file = str_replace('$RateLimiting=0;', '$RateLimiting=1;', $file);
						$data = $file;
						file_put_contents($fileName,$file);	
					}
					else
					{
						$content = explode('?>', $file);
						$file = $content[0];
						$file .= PHP_EOL;
						$file .= '$RateLimiting=1;'.PHP_EOL;
						$file .='?>';
						file_put_contents($fileName,$file);
						$data = $file;
					}
					
					$file = $data;
					if(strpos($file, 'RequestsPMin')!=false)
					{
						$content = explode(PHP_EOL, $file);
						$con = '';
						$len =  sizeof($content);
						
						for($i=0;$i<$len;$i++)
						{
							if(strpos($content[$i], 'RequestsPMin')!=false)
							{
								$con.='$RequestsPMin='.$req.';'.PHP_EOL;
							}
							else
							{
								$con .= $content[$i].PHP_EOL;
							}
						}
						
						file_put_contents($fileName,$con);
						$data = $con;
						
					}

					else
					{
						$content = explode('?>', $file);
						$file = $content[0];
						$file .= PHP_EOL;
						$file .= '$RequestsPMin='.$req.';'.PHP_EOL;
						$file .='?>';
						file_put_contents($fileName,$file);
						$data = $file;
					}
				

					
					if($_POST['action']=='BlockIP')
					{
						update_site_option('actionRateL',1);

						$file = $data;
						if(strpos($file, 'actionRateL')!=false)
						{
							$content = explode(PHP_EOL, $file);
							$con = '';
							foreach ($content as $line => $lineV) {
								if(strpos($lineV, 'actionRateL')!=false)
								{
									$con.='$actionRateL="BlockIP";'.PHP_EOL;
								}
								else
								{
									$con .= $lineV.PHP_EOL;
								}
							}
							file_put_contents($fileName,$con);	
						}
						else
						{
							$content = explode('?>', $file);
							$file = $content[0];
							$file .= PHP_EOL;
							$file .= '$actionRateL="BlockIP";'.PHP_EOL;
							$file .='?>';
							file_put_contents($fileName,$file);
							$file = $data;
						}
					}
					elseif($_POST['action']=='ThrottleIP')
					{
						$file = $data;
						update_site_option('actionRateL',0);
						if(strpos($file, 'actionRateL')!=false)
						{
							$content = explode(PHP_EOL, $file);
							$con = '';
							foreach ($content as $line => $lineV) {
								if(strpos($lineV, 'actionRateL')!=false)
								{
									$con.='$actionRateL="ThrottleIP";'.PHP_EOL;
								}
								else
								{
									$con .= $lineV.PHP_EOL;
								}
							}
							file_put_contents($fileName,$con);	
						}
						else
						{
							$content = explode('?>', $file);
							$file = $content[0];
							$file .= PHP_EOL;
							$file .= '$actionRateL="ThrottleIP";'.PHP_EOL;
							$file .='?>';
							file_put_contents($fileName,$file);
						}	
					}

			}

		}	
			
		}

	}
	private function disableRL()
	{
		update_site_option('Rate_limiting',0);

		$dir_name =  dirname(__FILE__);
		$dir_name1 = explode('wp-content', $dir_name);
		$dir_name = $dir_name1[0];
		$filepath = str_replace('\\', '/', $dir_name1[0]);
		$fileName = $filepath.'/wp-includes/mo-waf-config.php';
		$file = file_get_contents($fileName);
			
		if(strpos($file, 'RateLimiting')!=false)
		{
			$file = str_replace('$RateLimiting=1;', '$RateLimiting=0;', $file);
			file_put_contents($fileName,$file);	
		}
		else
		{
			$content = explode('?>', $file);
			$file = $content[0];
			$file .= PHP_EOL;
			$file .= '$RateLimiting=0;'.PHP_EOL;
			$file .='?>';
			file_put_contents($fileName,$file);
		}

	}
	private function backupHtaccess()
	{
		if(isset($_POST['htaccessWAF']))
		{
			if($_POST['htaccessWAF']=='on')
			{
				$dir_name =  dirname(__FILE__);
				$dirN = $dir_name;
				$dirN = str_replace('\\', '/', $dirN);
				$dir_name1 = explode('wp-content', $dir_name);
				$dir_name = $dir_name1[0];
				$dir_name1 = str_replace('\\', '/', $dir_name1[0]);
				$dir_name =$dir_name1.'.htaccess';
			 	$file =  file_get_contents($dir_name);
				$dir_backup = $dir_name1.'htaccess';
				$handle = fopen($dir_backup, 'c+');
				fwrite($handle,$file);
			}
		}
	}
	private function limitAttack()
	{
		if(isset($_POST['limitAttack']))
		{
			$value = sanitize_text_field($_POST['limitAttack']);
			if($value>1)
			{
				update_site_option('limitAttack',$value);
				echo "limitSaved";
				exit;
			}
			else 
			{
				echo "limitIsLT1";
				exit;
			}

		}
	}
	

	
	function wpns_captcha_settings(){
		$nonce = $_POST['nonce'];
	   		if ( ! wp_verify_nonce( $nonce, 'wpns-captcha' ) ){
	   			wp_send_json('ERROR');
	   			return;
	   		}
		$site_key = sanitize_text_field($_POST['site_key']);
		$secret_key = sanitize_text_field($_POST['secret_key']);
		$enable_captcha = $_POST['enable_captcha'];
		if($enable_captcha == 'true'){$enable_captcha = "on";}else if($enable_captcha == 'false') {$enable_captcha = "";}
		$login_form_captcha = $_POST['login_form'];
		if($login_form_captcha == 'true'){$login_form_captcha = "on";}else if($login_form_captcha == 'false') {$login_form_captcha = "";}
		$reg_form_captcha = $_POST['registeration_form'];
		if($reg_form_captcha == 'true'){$reg_form_captcha = "on";}else if($reg_form_captcha == 'false') {$reg_form_captcha = "";}

		if(($site_key == "" || $secret_key == "") and $enable_captcha == 'true'){
			wp_send_json('empty');
			return;
		} 

		update_site_option( 'mo_wpns_recaptcha_site_key'			 		, $site_key     );
		update_site_option( 'mo_wpns_recaptcha_secret_key'				, $secret_key   );
		update_site_option( 'mo_wpns_activate_recaptcha'			 		,  $enable_captcha );
		
		if($enable_captcha == "on"){
				update_site_option( 'mo_wpns_activate_recaptcha_for_login'	, $login_form_captcha );
				update_site_option( 'mo_wpns_activate_recaptcha_for_woocommerce_login', $login_form_captcha );
				update_site_option('mo_wpns_activate_recaptcha_for_registration', $reg_form_captcha   );
				update_site_option( 'mo_wpns_activate_recaptcha_for_woocommerce_registration',$reg_form_captcha   );
				wp_send_json('true');
			}
			else if($enable_captcha == ""){
				update_site_option( 'mo_wpns_activate_recaptcha_for_login'	, '' );
				update_site_option( 'mo_wpns_activate_recaptcha_for_woocommerce_login', '' );
				update_site_option('mo_wpns_activate_recaptcha_for_registration', ''   );
				update_site_option( 'mo_wpns_activate_recaptcha_for_woocommerce_registration','' );
				wp_send_json('false');
			}
		
	}	

	function wpns_strong_password_settings(){
		$nonce = $_POST['nonce'];
	   		if ( ! wp_verify_nonce( $nonce, 'wpns-strn-pass' ) ){
	   			wp_send_json('ERROR');
	   			return;
	   		}
		$enable_strong_pass = $_POST['enable_strong_pass'];
		if($enable_strong_pass == 'true'){$enable_strong_pass = 1;}else if($enable_strong_pass == 'false') {$enable_strong_pass = 0;}
		$strong_pass_accounts = $_POST['accounts_strong_pass'];
		update_site_option('mo2f_enforce_strong_passswords_for_accounts',$strong_pass_accounts);  
		update_site_option('mo2f_enforce_strong_passswords' , $enable_strong_pass);
		if($enable_strong_pass){
			update_site_option('mo_wpns_enable_rename_login_url',"");
				wp_send_json('true');
			}
			else{
				wp_send_json('false');
			}
	}
	
}
new wpns_ajax;

?>