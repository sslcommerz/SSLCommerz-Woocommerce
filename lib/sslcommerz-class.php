<?php 
	if (!class_exists('WC_Payment_Gateway')) return;

    class WC_sslcommerz extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'sslcommerz';
            $this->medthod_title = 'sslcommerz';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title            = $this->settings['title'];
            $this->description      = $this->settings['description'];
            $this->store_id         = $this->settings['store_id'];
            $this->store_id         = $this->settings['store_id'];
            $this->store_password   = $this->settings['store_password'];
            $this->testmode         = $this->get_option('testmode');
            $this->testurl          =  "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";
            $this->liveurl          =  "https://securepay.sslcommerz.com/gwprocess/v4/api.php";
            $this->redirect_page_id = $this->settings['redirect_page_id'];
            $this->fail_page_id		= $this->settings['fail_page_id'];

            $this->msg['message']   = "";
            $this->msg['class']     = "";

            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_SSLCommerz_response'));
 
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } 
            else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }
            add_action('woocommerce_receipt_sslcommerz', array($this, 'receipt_page'));
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Enabled', 'sslcommerz'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable SSLCommerz Payment Module.', 'sslcommerz'),
                    'default'     => 'yes'
                ),
                'testmode' => array(
                    'title'       => __('Testmode', 'woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Testmode', 'woocommerce'),
                    'default'     => 'no',
                    'description' => __('Use Sandbox (testmode) API for development purposes. Don\'t forget to uncheck before going live.'),
                ),
                'hosted' => array(
                    'title'       => __('Hosted EasyCheckout', 'woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Hosted Checkout', 'woocommerce'),
                    'default'     => 'yes',
                    'description' => __('Hosted checkout will redirect customer to SSLCommerz Server.'),
                ),
                'title' => array(
                    'title'       => __('Title to show', 'sslcommerz'),
                    'type'        => 'text',
                    'description' => __('This will be shown as the payment method name on the checkout page.', 'sslcommerz'),
                    'default'     => __('Pay Online(Credit/Debit Card/MobileBanking/NetBanking/bKash)', 'sslcommerz')
                ),
                'description' => array(
                    'title'       => __('Description to show', 'sslcommerz'),
                    'type'        => 'textarea',
                    'description' => __( 'This will be shown as the payment method description on the checkout page.', 'sslcommerz'),
                    'default'     => __('Pay securely by Credit/Debit card, Internet banking or Mobile banking through SSLCommerz.', 'sslcommerz')
                ),
                'store_id' => array(
                    'title'       => __('Store ID', 'sslcommerz'),
                    'type'        => 'text',
                    'description' => __( 'API store id <span style="color: red;">(NOT the merchant panel id)</span>. You should obtain this info from SSLCommerz.')
                ),
                'store_password' => array(
                    'title'       => __('Store Password', 'sslcommerz'),
                    'type'        => 'text',
                    'description' => __( 'API store password <span style="color: red;">(NOT the merchant panel password)</span>. You should obtain this info from SSLCommerz.')
                ),
                'redirect_page_id' => array(
                    'title'       => __('Select Success Page'),
                    'type'        => 'select',
                    'options'     => $this->get_pages( 'Select Success Page'),
                    'description' => "User will be redirected here after a successful payment. We recommend <span style='color: green;'><b>Checkout Page</b></span>."
                ),
                'fail_page_id' => array(
                    'title'       => __('Fail / Cancel Page'),
                    'type'        => 'select',
                    'options'     => $this->get_pages( 'Select Fail / Cancel Page'),
                    'description' => "User will be redirected here if transaction fails or get canceled. We recommend <span style='color: green;'><b>Cart Page</b></span>."
                )
            );
        }

        public function admin_options()
        {
            echo '<h2>' . __('SSLCommerz Payment Gateway', 'sslcommerz') . '</h2>';
            echo '<p>' . __('Configure parameters to start accepting payments.') . '</p><hr>';
            echo "<h4 style='color:green;'>" . __("Register for sandbox merchant panel & store credentials <a href='https://developer.sslcommerz.com/registration/' target='blank'>Click Here</a> .") . "</h4><hr>";
            
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        function plugins_url($path = '', $plugin = '')
        {
            $path = wp_normalize_path($path);
            $plugin = wp_normalize_path($plugin);
            $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

            if (!empty($plugin) && 0 === strpos($plugin, $mu_plugin_dir)) {
                $url = WPMU_PLUGIN_URL;
            }
            else {
                $url = WP_PLUGIN_URL;
            }

            $url = set_url_scheme($url);

            if (!empty($plugin) && is_string($plugin)) {
                $folder = dirname(plugin_basename($plugin));
                if ('.' != $folder){
                    $url .= '/' . ltrim($folder, '/');
                }
            }

            if ($path && is_string($path))
            {
                $url .= '/' . ltrim($path, '/');
            }

            /**
             * Filters the URL to the plugins directory.
             *
             * @since 2.8.0
             *
             * @param string $url    The complete URL to the plugins directory including scheme and path.
             * @param string $path   Path relative to the URL to the plugins directory. Blank string
             *                       if no path is specified.
             * @param string $plugin The plugin file path to be relative to. Blank string if no plugin
             *                       is specified.
             */
            return apply_filters('plugins_url', $url, $path, $plugin);
        }

        /**
         *  There are no payment fields for sslcommerz, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this->description) echo wpautop(wptexturize($this->description));
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order)
        {
            echo '<p>' . __('Thank you for your order, please click the button below to pay with sslcommerz.', 'sslcommerz') . '</p>';
            if($this->settings['hosted'] == 'yes')
            {
            	echo $this->generate_sslcommerz_form($order);
            }
            else
            {
            	echo $this->button($order);
            }
        }

        function button($order)
        {
            if ($this->testmode == 'yes') {
                $jsurl = "https://sandbox.sslcommerz.com/embed.min.js?";
                $sandbox = 'yes';
            } else {
                $jsurl = "https://seamless-epay.sslcommerz.com/embed.min.js?";
                $sandbox = 'no';
            }
            $post_data = json_encode($this->generate_sslcommerz_form($order));
            ?>
                <button class="button alt" id="sslczPayBtn"
                    token="<?php echo $order;?>"
                    postdata=""
                    order="<?php echo $order;?>"
                    endpoint="<?php echo get_site_url(); ?>/easyCheckout.php?v4checkout">Pay Via SSLCommerz
                </button>
                &nbsp;&nbsp; <a href="../">Cancel</a>
                <script type="text/javascript">
                    var url = <?php echo "'$jsurl'"; ?>;
                    (function (window, document) {
                        var loader = function () {
                            var script = document.createElement("script"), tag = document.getElementsByTagName("script")[0];
                            script.src =  url+ Math.random().toString(36).substring(7);
                            tag.parentNode.insertBefore(script, tag);
                        };
                    
                        window.addEventListener ? window.addEventListener("load", loader, false) : window.attachEvent("onload", loader);
                    })(window, document);
                </script>
            <?php
        }

        /**
         * Generate sslcommerz button link
         **/
        public function generate_sslcommerz_form($order_id)
        {
            global $woocommerce;
            // global $product;
            $order = new WC_Order($order_id);
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            $redirect_url = add_query_arg('wc-api', get_class($this), $redirect_url);
            // $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);
            $declineURL = $order->get_cancel_order_url();

            $items = $woocommerce->cart->get_cart();

            #shipping method
            $shipping_method = @array_shift($order->get_shipping_methods());
            $shipping_method_id = $shipping_method['method_id'];

            if($shipping_method_id != "") {
                $shipping_enabled = "YES";
            } else {
                $shipping_enabled = "NO";
            }

            $product_title = array();

            foreach($items as $item => $values) 
            { 
                $_product =  wc_get_product( $values['data']->get_id()); 
                $product_title[] = $_product->get_title();
            } 

            $product_name = implode(",",$product_title);

            //NEW V4 HOSTED API OF SSLCOMMERZ
            $post_data = array(
                'store_id'      => $this->store_id,
                'store_passwd'  => $this->store_password,
                'total_amount'  => $order->get_total(),
                'tran_id'       => $order_id,
                'success_url'   => $redirect_url,
                'fail_url'      => $fail_url,
                'cancel_url'    => $declineURL,
                'ipn_url'       => __(get_site_url(null, null, null) . '/easyCheckout.php?sslcommerzipn', 'sslcommerz'),
                'cus_name'      => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'cus_add1'      => trim($order->get_billing_address_1(), ','),
                'cus_country'   => wc()->countries->countries[$order->get_billing_country()],
                'cus_state'     => $order->get_billing_state(),
                'cus_city'      => $order->get_billing_city(),
                'cus_postcode'  => $order->get_billing_postcode(),
                'cus_phone'     => $order->get_billing_phone(),
                'cus_email'     => $order->get_billing_email(),
                'ship_name'     => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'ship_add1'     => $order->get_shipping_address_1(),
                'ship_country'  => $order->get_shipping_country(),
                'ship_state'    => $order->get_shipping_state(),
                'ship_city'     => $order->get_shipping_city(),
                'ship_postcode' => $order->get_shipping_postcode(),
                'currency'      => get_woocommerce_currency(),
                'product_category'  => 'ecommerce',
                'shipping_method'   => $shipping_enabled,
                'num_of_item'       => $woocommerce->cart->cart_contents_count,
                'product_name'      => $product_name,
                'product_profile'   => 'general'
            );

            if ($this->testmode == 'yes') {
                $liveurl = $this->testurl;
                $sandbox = 'yes';
            } else {
                $liveurl = $this->liveurl;
                $sandbox = 'no';
            }

            if($this->settings['hosted'] == 'yes')
            {
            	# REQUEST SEND TO SSLCOMMERZ
	     	    $response = wp_remote_post( $liveurl, array(
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

	            return '<form action="' . $sslcz['GatewayPageURL'] . '" method="post" id="sslcommerz_payment_form">
	                <input type="submit" class="button-alt" id="submit_sslcommerz_payment_form" value="' . __('Pay via sslcommerz', 'sslcommerz') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'sslcommerz') . '</a>
	                <script type="text/javascript">
	                    jQuery(function(){
	                        jQuery("body").block({
	                            message: "' . __('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'sslcommerz') . '",
	                            overlayCSS: {
	                                background: "#fff",
	                                    opacity: 0.6
	                            },
	                            css: {
	                                padding:        20,
	                                textAlign:      "center",
	                                color:          "#555",
	                                border:         "3px solid #aaa",
	                                backgroundColor:"#fff",
	                                cursor:         "wait",
	                                lineHeight:"32px"
	                            }
	                        });
	                        jQuery("#submit_sslcommerz_payment_form").click();
	                    });
	                </script>
	            </form>';
            }
            else
            {
            	$post_data['api_url'] = $liveurl;
            	$post_data['type'] = $sandbox;

            	return $post_data;
            }
            
        }
        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
        }

        /**
         * Check for valid sslcommerz server callback
         **/
        function check_sslcommerz_response()
        {
            global $woocommerce;
            $tran_id = sanitize_text_field($_REQUEST['tran_id']);
            $val_id = sanitize_text_field($_REQUEST['val_id']);
            $order = wc_get_order($tran_id);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            # $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);

            if (isset($tran_id) && isset($val_id)) 
            {
                $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);

                $this->msg['class'] = 'error';
                $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

                if (isset($_POST['val_id'])) {
                    $val_id = urldecode($_POST['val_id']);
                } else {
                    $val_id = '';
                }

                $store_id = urlencode($this->store_id);
                $store_passwd = urlencode($this->store_password);

                if ('yes' == $this->testmode) {
                    $requested_url = ("https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");
                } else {
                    $requested_url = ("https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");
                }

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

                    # TRANSACTION INFO
                    $status = $result->status;
                    $tran_date = $result->tran_date;
                    $tran_id = $result->tran_id;
                    $val_id = $result->val_id;
                    $amount = $result->amount;
                    $store_amount = $result->store_amount;
                    $bank_tran_id = $result->bank_tran_id;
                    $card_type = $result->card_type;

                    # ISSUER INFO
                    $card_no = $result->card_no;
                    $card_issuer = $result->card_issuer;
                    $card_brand = $result->card_brand;
                    $card_issuer_country = $result->card_issuer_country;
                    $card_issuer_country_code = $result->card_issuer_country_code;

                    # Payment Risk Status
                    $risk_level = $result->risk_level;
                    $risk_title = $result->risk_title;

                    $message = '';
                    $message .= 'Payment Status = ' . $status . "\n";
                    $message .= 'Bank txnid = ' . $bank_tran_id . "\n";
                    $message .= 'Your Oder id = ' . $tran_id . "\n";
                    $message .= 'Payment Date = ' . $tran_date . "\n";
                    $message .= 'Card Number = ' . $card_no . "\n";
                    $message .= 'Card Type = ' . $card_brand . '-' . $card_type . "\n";
                    $message .= 'Transaction Risk Level = ' . $risk_level . "\n";
                    $message .= 'Transaction Risk Description = ' . $risk_title . "\n";

                    if ($status == 'VALID') 
                    {
                        if ($risk_level == 0) 
                        {
                            $pay_status = 'success';
                        }
                        if ($risk_level == 1) {
                            $pay_status = 'risk';
                        }
                    } 
                    elseif ($status == 'VALIDATED') 
                    {
                        if ($risk_level == 0) {
                            $pay_status = 'success';
                        }
                        if ($risk_level == 1) {
                            $pay_status = 'risk';
                        }
                    } 
                    else {
                        $pay_status = 'failed';
                    }
	            }

                if ($tran_id != '') {
                    try 
                    {
                        $order = wc_get_order($tran_id);
                        $store_id = sanitize_text_field($_REQUEST['tran_id']);
                        $amount = sanitize_text_field($_REQUEST['amount']);
                        $transauthorised = false;

                        if ($pay_status == "success") {

                            $transauthorised = true;
                            $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                            $this->msg['class'] = 'success';

                            if ($order->get_status() == 'pending') // If IPN Enable. Then oreder status will be updated by IPN page.So no need to update again.
                            {
                                $order->update_status('Processing');
                                $order->payment_complete();
                            }
                            $order->add_order_note($message);
                            $order->add_order_note($this->msg['message']);
                            $woocommerce->cart->empty_cart();
                            $return_url = $order->get_checkout_order_received_url();
                            $redirect_url  = str_replace('http:', 'http:', $return_url);
                        } 
                        else if ($pay_status == "risk") 
                        {
                            $order->update_status('on-hold');
                            $order->add_order_note($message);
                            $this->msg['message'] = "Thank you for shopping with us. However, Your account has been charged and your transaction is Pendding. After Geting Verified from SSLCommerz. It will updated soon. Please Co-Operate with SSLCommerz.";
                            $this->msg['class'] = 'Failed';
                            wc_add_notice(__('Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'woocommerce'), 'error');
                            $redirect_url  = $fail_url;
                        } 
                        else if ($pay_status == "failed") 
                        {
                            $order->update_status('failed');
                            $order->add_order_note($message);
                            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed.";
                            $this->msg['class'] = 'Failed';
                            wc_add_notice(__('Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'woocommerce'), 'error');
                            $redirect_url  = $fail_url;
                        } 
                        else {
                            $this->msg['class'] = 'error';
                            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                        }

                    } 
                    catch (Exception $e) {
                        $msg = "Error";
                    }
                }
                wp_redirect($redirect_url);
            }
        }

        function showMessage($content)
        {
            return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
        }

        # get all pages
        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                # show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                # add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }
    }