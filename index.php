<?php
/*
  Plugin Name: SSLCommerz V4(EasyCheckout)-WooCommerce WP V5.2.1
  Plugin URI: https://developer.sslcommerz.com/doc/v4/
  Description: This plugin allows you to accept payments on your WooCommerce store from customers using Visa Cards, Master cards, American Express etc. Via SSLCommerz payment gateway with new V4 EasyCheckout API.
  Version: 3.0.1
  Author: Prabal Mallick
  Author Email: integration@sslcommerz.com
  Copyright: © 2015-2019 SSLCommerz.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if (!defined('ABSPATH')) exit; // Exit if accessed directly
add_action('plugins_loaded', 'woocommerce_sslcommerz_init', 0);
add_action('plugins_loaded', array(Create_ssl_ipn_page_url::get_instance(), 'setup')); // IPN page setup
add_action('plugins_loaded', array(V4checkout_page::get_instance(), 'setup')); // V4checkout_page setup

function woocommerce_sslcommerz_init()
{
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
            $this->store_id      = $this->settings['store_id'];
            $this->store_id      = $this->settings['store_id'];
            $this->store_password   = $this->settings['store_password'];
            $this->testmode           = $this->get_option('testmode');
            $this->testurl            =  "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";
            $this->liveurl          =  "https://securepay.sslcommerz.com/gwprocess/v4/api.php";
            $this->redirect_page_id = $this->settings['redirect_page_id'];

            $this->msg['message'] = "";
            $this->msg['class'] = "";

            //add_action('init', array(&$this, 'check_SSLCommerz_response'));
            //update for woocommerce >2.0
            // add_action( 'woocommerce_api_wc_sslcommerz', array( $this, 'check_SSLCommerz_response' ) );
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_SSLCommerz_response'));
            //add_action('valid-SSLCommerz-request', array($this, 'successful_request'));
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }
            add_action('woocommerce_receipt_sslcommerz', array($this, 'receipt_page'));
            // add_action('woocommerce_thankyou_SSLCommerz',array($this, 'thankyou_page')); 
        }
  
        
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enabled', 'SSLWireless'),
                    'type' => 'checkbox',
                    'label' => __('Enable SSLCommerz Payment Module.', 'SSLWireless'),
                    'default' => 'no'
                ),
                'testmode' => array(
                    'title'       => __('Testmode', 'woocommerce'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Testmode', 'woocommerce'),
                    'default'     => 'no',
                    'description' => __('Use Sandbox (testmode) API for development purposes. Don\'t forget to uncheck before going live.'),
                ),
                'title' => array(
                    'title' => __('Title to show', 'SSLWireless'),
                    'type' => 'text',
                    'description' => __('This will be shown as the payment method name on the checkout page.', 'SSLWireless'),
                    'default' => __('SSLCommerz', 'SSLWireless')
                ),
                'description' => array(
                    'title' => __('Description to show', 'SSLWireless'),
                    'type' => 'textarea',
                    'description' => __( 'This will be shown as the payment method description on the checkout page.', 'SSLWireless'),
                    'default' => __('Pay securely by Credit/Debit card, Internet banking or Mobile banking through SSLCommerz.', 'SSLWireless')
                ),
                'store_id' => array(
                    'title' => __('Store ID', 'SSLWireless'),
                    'type' => 'text',
                    'description' => __( 'API store id <span style="color: red;">(NOT the merchant panel id)</span>. You should obtain this info from SSLCommerz.')
                ),
                'store_password' => array(
                    'title' => __('Store Password', 'SSLWireless'),
                    'type' => 'text',
                    'description' => __( 'API store password <span style="color: red;">(NOT the merchant panel password)</span>. You should obtain this info from SSLCommerz.')
                ),
                'redirect_page_id' => array(
                    'title' => __('Select Success Page'),
                    'type' => 'select',
                    'options' => $this->get_pages( 'Select Success Page'),
                    'description' => "User will be redirected here after a successful payment."
                ),
                'fail_page_id' => array(
                    'title' => __('Fail / Cancel Page'),
                    'type' => 'select',
                    'options' => $this->get_pages( 'Select Fail / Cancel Page'),
                    'description' => "User will be redirected here if transaction fails or get canceled."
                ),
            );
        }
        public function admin_options()
        {
            echo '<h3>' . __('SSLCommerz Payment Gateway', 'sslcommerz') . '</h3>';
            echo '<p>' . __('Configure parameters to start accepting payments.') . '</p>';
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
                } else {
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
            // echo $this->generate_sslcommerz_form($order);
            echo $this->button($order);
        }
        
        function button($order)
        {
            if ($this->testmode == 'yes') {
                $jsurl = "https://sandbox.sslcommerz.com/embed.min.js?";
            } else {
                $jsurl = "https://seamless-epay.sslcommerz.com/embed.min.js?";
            }
            $post_data = json_encode($this->generate_sslcommerz_form($order));
            ?>
                <button class="button alt" id="sslczPayBtn"
                    token="<?php echo $order;?>"
                    postdata=""
                    order="<?php echo $order;?>"
                    endpoint="<?php echo get_site_url(); ?>/index.php?v4checkout">Pay Via SSLCommerz
                </button>
                <a href="../" class="button cancel">Cancel</a>

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
            $order_id = $order_id;
            $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            $redirect_url = add_query_arg('wc-api', get_class($this), $redirect_url);
            $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);
            $declineURL = $order->get_cancel_order_url();

            $items = $woocommerce->cart->get_cart();
            #ship
            $shipping_method = @array_shift($order->get_shipping_methods());
            $shipping_method_id = $shipping_method['method_id'];

            if($shipping_method_id!="")
            {
                $smethod = "YES";
            }
            else
            {
                $smethod = "NO";
            }
            //---
            $product_title = array();

            foreach($items as $item => $values) 
            { 
                $_product =  wc_get_product( $values['data']->get_id()); 
                $product_title[] = $_product->get_title();
            } 

            $product_name = implode(",",$product_title);
            
            if ($this->testmode == 'yes') {
                $liveurl = $this->testurl;
                $sandbox = 'yes';
            } else {
                $liveurl = $this->liveurl;
                $sandbox = 'no';
            }

            //NEW V4 HOSTED API OF SSLCOMMERZ
            $post_data = array(
                'store_id'      => $this->store_id,
                'store_passwd'  => $this->store_password,
                'total_amount'  => $order->order_total,
                'tran_id'       => $order_id,
                'success_url'   => $redirect_url,
                'fail_url'      => $fail_url,
                'cancel_url'    => $declineURL,
                'ipn_url'       => __(get_site_url(null, null, null) . '/index.php?sslcommerzipn', 'SSLWireless'),
                'cus_name'      => $order->billing_first_name . ' ' . $order->billing_last_name,
                'cus_add1'      => trim($order->billing_address_1, ','),
                'cus_country'   => wc()->countries->countries[$order->billing_country],
                'cus_state'     => $order->billing_state,
                'cus_city'      => $order->billing_city,
                'cus_postcode'  => $order->billing_postcode,
                'cus_phone'     => $order->billing_phone,
                'cus_email'     => $order->billing_email,
                'ship_name'     => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                'ship_add1'     => trim($order->shipping_address_1, ','),
                'ship_country'  => $order->shipping_country,
                'ship_state'    => $order->shipping_state,
                'delivery_tel'  => '',
                'ship_city'     => $order->shipping_city,
                'ship_postcode' => $order->shipping_postcode,
                'currency'      => get_woocommerce_currency(),
                'product_category'  => 'ecommerce',
                'shipping_method'   => $smethod,
                'num_of_item'       => $woocommerce->cart->cart_contents_count,
                'product_name'      => $product_name,
                'product_profile'   => 'general',
                'api_url'           => $liveurl,
                'type'              => $sandbox
            );
            
            return $post_data;
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
            $info = explode("_", $_REQUEST['tran_id']);
            $order_id = $info[0];
            $order = wc_get_order($info[0]);
            $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
            $fail_url = add_query_arg('wc-api', get_class($this), $fail_url);

            if (isset($_REQUEST['tran_id'])) {
                $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                $fail_url = ($this->fail_page_id == "" || $this->fail_page_id == 0) ? get_site_url() . "/" : get_permalink($this->fail_page_id);
                $order_id = $info[0];
                $this->msg['class'] = 'error';
                $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

                if (isset($_POST['val_id'])) {
                    $val_id = urldecode($_POST['val_id']);
                } else {
                    $val_id = '';
                }

                $store_id = urlencode($this->store_id);
                $store_passwd = urlencode($this->store_password);

                if (empty($val_id)) {
                    if ('yes' == $this->testmode) {
                        $valid_url_own = ("https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?tran_id=" . $order_id . "&Store_Id=" . $store_id . "&Store_Passwd=" . $store_passwd . "&v=1&format=json");
                    } else {
                        $valid_url_own = ("https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?tran_id=" . $order_id . "&Store_Id=" . $store_id . "&Store_Passwd=" . $store_passwd . "&v=1&format=json");
                    }

                    $ownvalid = curl_init();
                    curl_setopt($ownvalid, CURLOPT_URL, $valid_url_own);
                    curl_setopt($ownvalid, CURLOPT_RETURNTRANSFER, true);

                    $ownvalid_result = curl_exec($ownvalid);
                    $ownvalid_code = curl_getinfo($ownvalid, CURLINFO_HTTP_CODE);

                    if ($ownvalid_code == 200 && !(curl_errno($ownvalid))) {
                        $result_own = json_decode($ownvalid_result, true);
                        $lastupdate_no = $result_own['no_of_trans_found'] - 1;
                        $own_data = $result_own['element'];
                        $val_id = $own_data[$lastupdate_no]['val_id'];
                        //echo $own_data[0]['val_id'];
                    }
                }

                if ('yes' == $this->testmode) {
                    $requested_url = ("https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&Store_Id=" . $store_id . "&Store_Passwd=" . $store_passwd . "&v=1&format=json");
                } else {
                    $requested_url = ("https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&Store_Id=" . $store_id . "&Store_Passwd=" . $store_passwd . "&v=1&format=json");
                }

                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $requested_url);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($handle);
                $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                if ($code == 200 && !(curl_errno($handle))) {
                    # TO CONVERT AS ARRAY
                    # $result = json_decode($result, true);
                    # $status = $result['status'];  

                    # TO CONVERT AS OBJECT
                    $result = json_decode($result);

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

                    //Payment Risk Status
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

                    if ($status == 'VALID') {
                        if ($risk_level == 0) {
                            $pay_status = 'success';
                        }
                        if ($risk_level == 1) {
                            $pay_status = 'risk';
                        }
                    } elseif ($status == 'VALIDATED') {
                        if ($risk_level == 0) {
                            $pay_status = 'success';
                        }
                        if ($risk_level == 1) {
                            $pay_status = 'risk';
                        }
                    } else {
                        $pay_status = 'failed';
                    }
                }

                if ($order_id != '') {
                    try {
                        $order = wc_get_order($info[0]);
                        $store_id = $_REQUEST['[tran_id'];
                        $amount = $_REQUEST['amount'];
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
                        } else if ($pay_status == "risk") {
                            $order->update_status('on-hold');
                            $order->add_order_note($message);
                            $this->msg['message'] = "Thank you for shopping with us. However, Your account has been charged and your transaction is Pendding. After Geting Verified from SSLCommerz. It will updated soon. Please Co-Operate with SSLCommerz.";
                            $this->msg['class'] = 'Failed';
                            wc_add_notice(__('Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'woocommerce'), 'error');
                            $redirect_url  = $fail_url;
                        } else if ($pay_status == "failed") {
                            $order->update_status('failed');
                            $order->add_order_note($message);
                            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed.";
                            $this->msg['class'] = 'Failed';
                            wc_add_notice(__('Unfortunately your card was declined and the order could not be processed. Please try again with a different card or payment method.', 'woocommerce'), 'error');
                            $redirect_url  = $fail_url;
                        } else {
                            $this->msg['class'] = 'error';
                            $this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                            //Here you need to put in the routines for a failed
                            //transaction such as sending an email to customer
                            //setting database status etc etc
                        }

                        //removed for WooCOmmerce 2.0
                        //add_action('the_content', array(&$this, 'showMessage'));
                    } catch (Exception $e) {
                        // $errorOccurred = true;
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

        // get all pages
        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_sslcommerz_gateway($methods)
    {
        $methods[] = 'WC_sslcommerz';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sslcommerz_gateway');

    function plugin_page_settings_link($links)
    {
        $links[] = '<a href="' .
        admin_url( 'admin.php?page=wc-settings&tab=checkout&section=sslcommerz') .
        '">' . __('Settings') . '</a>';
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_page_settings_link');

    /**
     *  Add Custom Icon 
     */

    function custom_gateway_icon($icon, $id)
    {
        if ($id === 'sslcommerz') {
            return '<img src="' . plugins_url( 'images/sslcz-verified.png', __FILE__) . '" > ';
        } else {
            return $icon;
        }
    }
    add_filter('woocommerce_gateway_icon', 'custom_gateway_icon', 10, 2);
}

class Create_ssl_ipn_page_url
{

    protected static $instance = NULL;

    public function __construct()
    { }

    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function setup()
    {
        add_action('init', array($this, 'rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'), 10, 1);
        add_action('parse_request', array($this, 'parse_request'), 10, 1);

        register_activation_hook(__FILE__, array($this, 'flush_rules'));
    }

    public function rewrite_rules()
    {
        add_rewrite_rule('sslcommerzipn/?$', 'index.php?sslcommerzipn', 'top');
    }

    public function flush_rules()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function query_vars($vars)
    {
        $vars[] = 'sslcommerzipn';
        return $vars;
    }

    public function parse_request($wp)
    {
        if (array_key_exists('sslcommerzipn', $wp->query_vars)) {
            include plugin_dir_path(__FILE__) . 'sslcommerz_ipn.php';
            exit();
        }
    }
}

class V4checkout_page
{

    protected static $instance = NULL;

    public function __construct()
    { }

    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function setup()
    {
        add_action('init', array($this, 'rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'), 10, 1);
        add_action('parse_request', array($this, 'parse_request'), 10, 1);

        register_activation_hook(__FILE__, array($this, 'flush_rules'));
    }

    public function rewrite_rules()
    {
        add_rewrite_rule('v4checkout/?$', 'index.php?v4checkout', 'top');
    }

    public function flush_rules()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function query_vars($vars)
    {
        $vars[] = 'v4checkout';
        return $vars;
    }

    public function parse_request($wp)
    {
        if (array_key_exists('v4checkout', $wp->query_vars)) {
            include plugin_dir_path(__FILE__) . 'v4checkout.php';
            exit();
        }
    }
}
