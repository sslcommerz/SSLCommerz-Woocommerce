<?php
/**
 * SSLCommerz Blocks Support
 *
 * Adds WooCommerce Checkout Blocks compatibility for SSLCommerz payment gateway.
 *
 * @package SSLCommerz_Woocommerce
 * @since 6.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * SSLCommerz Blocks integration
 *
 * @since 6.3.0
 */
final class WC_SSLCommerz_Blocks extends AbstractPaymentMethodType {

    /**
     * The gateway instance.
     *
     * @var WC_sslcommerz
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'sslcommerz';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_sslcommerz_settings', array() );
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
    }

    /**
     * Returns if this payment method should be active.
     *
     * @return boolean
     */
    public function is_active() {
        if ( ! $this->gateway ) {
            return false;
        }
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path       = 'assets/js/sslcommerz-blocks.js';
        $script_asset_path = SSLCOM_PATH . 'assets/js/sslcommerz-blocks.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require( $script_asset_path )
            : array(
                'dependencies' => array(),
                'version'      => SSLCOMMERZ_PLUGIN_VERSION
            );
        $script_url        = SSLCOM_URL . $script_path;

        wp_register_script(
            'wc-sslcommerz-blocks',
            $script_url,
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
            $script_asset['version'],
            true
        );

        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'wc-sslcommerz-blocks', 'sslcommerz', SSLCOM_PATH . 'languages/' );
        }

        return array( 'wc-sslcommerz-blocks' );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => array_filter( $this->gateway ? $this->gateway->supports : array(), array( $this->gateway, 'supports' ) ),
            'icon'        => SSLCOM_URL . 'images/sslcz-verified.png',
            'testmode'    => $this->get_setting( 'testmode' ) === 'yes',
        );
    }
}
