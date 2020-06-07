<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if(get_option('woocommerce_sslcommerz_settings')!='') {
		$data=get_option('woocommerce_sslcommerz_settings');
		if ($data['store_id'] != '' || $data['store_password'] != '') {
			$store_id = $data['store_id'];
			$store_passwd = $data['store_password'];
		} else {
			die("Invalid or Empty Information ");
		}

		if($data[ 'testmode'] == 'yes') {
			$url = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";
		} else {
			$url = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php";
		}
	} else {
		die("SSLCOMMERZ payment gateway is not enabled!");
	}

	$tran_id = $_POST['tran_id'];
	$val_id = $_POST['val_id'];

	if(empty($val_id) && empty($tran_id)) {
		die("No IPN Request Received"); 
	}

	$orderid = substr($tran_id, 0, strpos($tran_id, '_'));
	$order = wc_get_order($orderid);

	$requested_url = ($url."?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");
	$handle = curl_init();
	curl_setopt($handle, CURLOPT_URL, $requested_url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($handle);
	$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

	if($code == 200 && !( curl_errno($handle))) {	
		$result = json_decode($result);

		if(sslcommerz_hash_key($store_passwd,$_POST))
		{
			if(sslcommerz_hash_key($store_passwd,$_POST))
			{
			if($order->get_total()== trim($result->currency_amount))
			{ 
				if($result->status=='VALIDATED' || $result->status=='VALID') 
				{ 
					if($order->get_status() == 'pending')
					{
						if($_POST['card_type'] != "")
						{							        
							$order -> update_status('Processing');
							$order -> payment_complete();
							$result_msg =  "Hash validation success.";
						}
						else
						{
						        $result_msg=  "Card Type Empty or Mismatched";
						}
					}	
					else
					{
						$result_msg=  "Order already in processing Status";
					}
				}
				else
				{
					 $result_msg=  "Your Validation id could not be Verified";
				}
			}
			else
			{
				$result_msg= "Your Paid Amount is Mismatched";
			}
                   }
		   else
		   {
			$result_msg =  "Your Currency is Mismatched";
		   }

		}
		else
		{
			$result_msg =  "Hash validation failed.";
			               		
		}

		echo $result_msg;
	}

	function sslcommerz_hash_key($store_passwd="", $parameters=array()) 
	{
		if(isset($_POST) && isset($_POST['verify_sign']) && isset($_POST['verify_key'])) {
			# NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST

			$pre_define_key = explode(',', $_POST['verify_key']);

			$new_data = array();
			if(!empty($pre_define_key )) {
				foreach($pre_define_key as $value) {
					if(isset($_POST[$value])) {
						$new_data[$value] = ($_POST[$value]);
					}
				}
			}
			# ADD MD5 OF STORE PASSWORD
			$new_data['store_passwd'] = md5($store_passwd);

			# SORT THE KEY AS BEFORE
			ksort($new_data);

			$hash_string="";
			foreach($new_data as $key=>$value) { $hash_string .= $key.'='.($value).'&'; }
			$hash_string = rtrim($hash_string,'&');

			if(md5($hash_string) == $_POST['verify_sign']) {

				return true;

			} else {
				return false;
			}
		} else return false;
	}
?>