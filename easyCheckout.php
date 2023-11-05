<?php 
/**
*  Plugin Name: SSLCommerz Payment Gateway
*  Plugin URI: https://sslcommerz.com/
*  Description: This plugin allows you to accept payments on your WooCommerce store from customers using Visa Cards, Master cards, American Express etc. Via SSLCommerz payment gateway with new V4 API & both Hosted & Popup.
*  Version: 6.1.0
*  Stable tag: 6.1.0
*  WC tested up to: 6.4
*  Author: SSLCommerz
*  Author URI: https://github.com/sslcommerz/
*  Author Email: integration@sslcommerz.com
*  License: GNU General Public License v3.0
*  License URI: http://www.gnu.org/licenses/gpl-3.0.html
**/
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0.0
 * @package    SSLCommerz_Woocommerce
 * @author     SSLCommerz Integration Team <integration@sslcommerz.com>
 */

	if (!defined('ABSPATH')) exit; // Exit if accessed directly

	define( 'SSLCOM_PATH', plugin_dir_path( __FILE__ ) );
	define( 'SSLCOM_URL', plugin_dir_url( __FILE__ ) );

	define ( 'SSLCOMMERZ_PLUGIN_VERSION', '6.1.0');
	
	global $plugin_slug;
	$plugin_slug = 'sslcommerz';

	require_once( SSLCOM_PATH . 'lib/sslcommerz-easypopup.php' );
	require_once( SSLCOM_PATH . 'lib/sslcommerz-webhook.php' );

	add_action('plugins_loaded', 'woocommerce_sslcommerz_init', 0);
	add_action('plugins_loaded', array(V4checkout_page::get_instance(), 'setup')); // V4checkout_page setup
	add_action('plugins_loaded', array(SSLCommerz_Ipn::get_instance(), 'setup')); // IPN page setup
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

	/**
	 * Hook plugin activation
	*/
	register_activation_hook( __FILE__, 'WcSslcommerzActivator' );
	function WcSslcommerzActivator() {
		$installed_version = get_option( "sslcommerz_easy_version" );
		if ( $installed_version == SSLCOMMERZ_PLUGIN_VERSION ) {
			return true;
		}
		update_option( 'sslcommerz_easy_version', SSLCOMMERZ_PLUGIN_VERSION );
	}

	/**
	 * Hook plugin deactivation
	 */
	register_deactivation_hook( __FILE__, 'WcSslcommerzDeactivator' );
	function WcSslcommerzDeactivator() { }


	function woocommerce_sslcommerz_init()
	{
		require_once( SSLCOM_PATH . 'lib/sslcommerz-class.php' );

		function woocommerce_add_sslcommerz_gateway($methods)
	    {
	        $methods[] = 'WC_sslcommerz';
	        return $methods;
	    }

	    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sslcommerz_gateway');

	    function sslcom_settings_link($links)
		{
		    $pluginLinks = array(
	            'settings' => '<a href="'. esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=sslcommerz')) .'">Settings</a>',
	            'docs'     => '<a href="https://developer.sslcommerz.com/doc/v4/" target="blank">Docs</a>',
	            'sandbox'     => '<a href="https://developer.sslcommerz.com/registration/" target="blank">Create Sandbox</a>',
	            'support'  => '<a href="mailto:integration@sslcommerz.com">Support</a>'
	        );

		    $links = array_merge($links, $pluginLinks);

		    return $links;
		}

		add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sslcom_settings_link');

	    /**
	     *  Add Custom Icon 
	    */
	    function sslcom_gateway_icon($icon, $id)
	    {
	        if ($id === 'sslcommerz') {
	            return '<img src="' . plugins_url( 'images/sslcz-verified.png', __FILE__) . '" > ';
	        } else {
	            return $icon;
	        }
	    }
	    add_filter('woocommerce_gateway_icon', 'sslcom_gateway_icon', 10, 2);
	}

?>