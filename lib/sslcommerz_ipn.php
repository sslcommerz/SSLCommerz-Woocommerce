<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if(get_option('woocommerce_sslcommerz_settings')!='') 
	{
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
	} 
	else {
		die("SSLCommerz payment gateway is not enabled!");
	}

	if (empty($_POST['tran_id'])) {
		die("Invalid IPN Data!");
	}

	if (empty($_POST['status'])) {
		die("Invalid IPN Data!");
	}


	global $woocommerce;
	$tran_id = sanitize_text_field($_POST['tran_id']);
	$order = new WC_Order($tran_id);

	if(isset($_POST['val_id']) && (isset($_POST['status']) && $_POST['status'] == "VALID")) 
	{
		$val_id = sanitize_text_field($_POST['val_id']);

		$requested_url = ($url."?val_id=".$val_id."&store_id=".$store_id."&store_passwd=".$store_passwd."&v=1&format=json");
		$result = wp_remote_post(
			$requested_url,
			array(
				'method'      => 'GET',
				'timeout'     => 30,
				'redirection' => 10,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array(),
				'cookies'     => array(),
			)
		);

		if($result['response']['code'] == 200)
		{	
			$result = json_decode($result['body']);
	
			if($order->get_total() == trim($result->currency_amount))
			{ 
				if($result->status=='VALIDATED' || $result->status=='VALID') 
				{ 
					if($order->get_status() != 'processing')
					{
						if($_POST['amount'] != "")
						{		
							if($result->risk_level == 0)
							{
								$order->update_status('Processing');
								$order->payment_complete();
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
								$order->update_status('on-hold');
								$order->payment_complete();
								$woocommerce->cart->empty_cart();
								$result_msg =  "<b>IPN Message</b><br>";
								$result_msg .=  "IPN: Validation success but found Risky-".$result->status."<br>";
								$result_msg .=  "IPN: Risk Level-".$result->risk_level."<br>";
								$result_msg .=  "IPN: Tran ID-".$tran_id."<br>";
								$result_msg .=  "IPN: Val ID-".$val_id."<br>";
								$result_msg .=  "IPN: Order Status Updated<br>";
								$result_msg .=  "IPN: Cart Empty<br>";
								$result_msg .=  "IPN: Hit Time - ".date("l d-m-yy h:i:s");
							}
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
		}
	}
	elseif (in_array($_POST['status'], ["FAILED", "CANCELLED", "UNATTEMPTED", "EXPIRED"]))
	{
		if($order->get_status() == 'pending')
		{
			if(!empty($_POST['amount']))
			{
					if($_POST['status'] == "FAILED") {
						$new_status = 'failed';
					} else {
						$new_status = 'cancelled';
					}

					$order->update_status($new_status);
					$result_msg =  "<b>IPN Message</b><br>";
					$result_msg .=  "IPN: Transaction was - ".$_POST['status']."<br>";
					$result_msg .=  "IPN: Error - ".$_POST['error']."<br>";
					$result_msg .=  "IPN: Tran ID-".$tran_id."<br>";
					$result_msg .=  "IPN: Order status changed to $new_status<br>";
					$result_msg .=  "IPN: Hit Time - ".date("l d-m-yy h:i:s");
			}
			else
			{
				$result_msg=  "IPN: Amount can not be empty!";
			}
		}	
		else
		{
			$result_msg=  "IPN: Order status already updated!";
		}
	}
	else{
		die("No IPN Request Received"); 
	}

	$order->add_order_note($result_msg);
	// echo $result_msg;
