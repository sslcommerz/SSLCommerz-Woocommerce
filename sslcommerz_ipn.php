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

		if($data['testmode'] == 'yes') {
			$url = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";
		} else {
			$url = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php";
		}
	} else {
		die("SSLCOMMERZ payment gateway is not enabled!");
	}

	if(isset($_POST['tran_id']) && isset($_POST['val_id']) && (isset($_POST['status']) && $_POST['status'] == "VALID")) 
	{
		global $woocommerce;
		$tran_id = $_POST['tran_id'];
		$val_id = $_POST['val_id'];

		// echo $orderid = substr($tran_id, 0, strpos($tran_id, '_'));

        	$order = new WC_Order($tran_id);

		$requested_url = ($url."?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $requested_url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($handle);
		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		if($code == 200 && !( curl_errno($handle))) 
		{	
			$result = json_decode($result);
	
			if($order->get_total() == trim($result->currency_amount))
			{ 
				if($result->status=='VALIDATED' || $result->status=='VALID') 
				{ 
					if($order->get_status() == 'pending')
					{
						if($_POST['amount'] != "")
						{		
							date_default_timezone_set("Asia/Dhaka");
							$order -> update_status('Processing');
							$order -> payment_complete();
							$woocommerce->cart->empty_cart();
							$result_msg =  "<b>IPN Message</b><br>";
							$result_msg .=  "IPN: Validation success-".$result->status."<br>";
							$result_msg .=  "IPN: Tran ID-".$tran_id."<br>";
							$result_msg .=  "IPN: Val ID-".$val_id."<br>";
							$result_msg .=  "IPN: Order Status Updated<br>";
							$result_msg .=  "IPN: Cart Empty<br>";
							$result_msg .=  "IPN: Hit Time - ".date("l d-m-yy h:i:s");
						}
						else
						{
						    $result_msg=  "IPN: Amount can not be empty!";
						}
					}	
					else
					{
						$result_msg=  "IPN: Order already in processing Status!";
					}
				}
				else
				{
					 $result_msg=  "IPN: Your Validation id could not be Verified!";
				}
			}
			else
			{
				$result_msg= "IPN: Your Paid Amount is Mismatched!";
			}
			$order->add_order_note($result_msg);
			echo $result_msg;
		}
	}
	else{
		die("No IPN Request Received"); 
	}
?>
