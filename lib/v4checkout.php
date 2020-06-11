<?php 
	$order_id = sanitize_text_field($_REQUEST['order']);
	
	$WC_sslcommerz = new WC_sslcommerz;
	$sslc_data     = $WC_sslcommerz->generate_sslcommerz_form($order_id);
	
	$post_data = array();
	$post_data['store_id']      = $sslc_data['store_id'];
	$post_data['store_passwd']  = $sslc_data['store_passwd'];
	$post_data['total_amount']  = $sslc_data['total_amount'];
	$post_data['currency']      = $sslc_data['currency'];
	$post_data['tran_id']       = $sslc_data['tran_id'];
	$post_data['success_url']   = $sslc_data['success_url'];
	$post_data['fail_url']      = $sslc_data['fail_url'];
	$post_data['cancel_url']    = $sslc_data['cancel_url'];
	$post_data['ipn_url']       = $sslc_data['ipn_url'];
	

	# CUSTOMER INFORMATION
	$post_data['cus_name']      = $sslc_data['cus_name'];
	$post_data['cus_email']     = $sslc_data['cus_email'];
	$post_data['cus_add1']      = $sslc_data['cus_add1'];
	$post_data['cus_city']      = $sslc_data['cus_city'];
	$post_data['cus_state']     = $sslc_data['cus_state'];
	$post_data['cus_postcode']  = $sslc_data['cus_postcode'];
	$post_data['cus_country']   = $sslc_data['cus_country'];
	$post_data['cus_phone']     = $sslc_data['cus_phone'];

	$post_data['num_of_item']       = $sslc_data['num_of_item'];
	$post_data['product_name']      = $sslc_data['product_name'];
	$post_data['product_category']  = $sslc_data['product_category'];
	$post_data['product_profile']   = $sslc_data['product_profile'];

	# SHIPMENT INFORMATION
	if($sslc_data['shipping_method'] == 'YES')
	{
		$post_data['shipping_method']   = $sslc_data['shipping_method'];
		$post_data['ship_name']         = $sslc_data['ship_name'];
		$post_data['ship_add1']        	= $sslc_data['ship_add1'];
		$post_data['ship_city']         = $sslc_data['ship_city'];
		$post_data['ship_state']        = $sslc_data['ship_state'];
		$post_data['ship_postcode']     = $sslc_data['ship_postcode'];
		$post_data['ship_country']      = $sslc_data['ship_country'];
	}
	else
	{
		$post_data['shipping_method']   = $sslc_data['shipping_method'];
	}

	# REQUEST SEND TO SSLCOMMERZ
	$direct_api_url                 = $sslc_data['api_url'];
	$api_type                 		= $sslc_data['type'];

	$response = wp_remote_post( $direct_api_url, array(
	    'method'      => 'POST',
		'timeout'     => 30,
		'redirection' => 10,
		'httpversion' => '1.1',
		'blocking'    => true,
		'headers'     => array(),
		'body'        => $post_data,
		'cookies'     => array(),
	    )
	);

	if($response['response']['code'] == 200)
	{
		$sslcz = json_decode($response['body'], true);
		
		if ($sslcz['status'] == 'FAILED') {
            echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
            echo "<br/>Failed Reason: " . $sslcz['failedreason'];
            exit;
        }
        else
        {
        	if(isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL']!="") {
				// this is important to show the popup, return or echo to sent json response back
				if($api_type == "no")
				{
					echo json_encode(['status' => 'SUCCESS', 'data' => $sslcz['GatewayPageURL'], 'logo' => $sslcz['storeLogo'] ]);
					exit;
				}
				else if($api_type == "yes")
				{
					echo json_encode(['status' => 'success', 'data' => $sslcz['GatewayPageURL'], 'logo' => $sslcz['storeLogo'] ]);
					exit;
				}
			} 
			else {
			   	$error = $sslcz['failedreason'];
			   	echo json_encode(['status' => 'FAILED', 'data' => null, 'message' => $error]);
			}
        }
	}
	else
	{
		if ( is_wp_error( $response ) ) {
			echo $response->get_error_message();
		}
		echo "Error Code: ".$response['response']['code'];
		echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
		exit;
	}                       	

?>



