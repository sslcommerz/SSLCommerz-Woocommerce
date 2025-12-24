/**
 * SSLCommerz Payment Gateway - WooCommerce Blocks Integration
 *
 * @package SSLCommerz_Woocommerce
 * @since 6.3.0
 */

(function() {
    'use strict';

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities;
    const { createElement } = window.wp.element;

    const settings = getSetting('sslcommerz_data', {});

    const defaultLabel = 'SSLCommerz';
    const label = decodeEntities(settings.title) || defaultLabel;

    /**
     * Content component - displays the payment method description
     */
    const Content = () => {
        const description = decodeEntities(settings.description || '');

        return createElement(
            'div',
            { className: 'sslcommerz-payment-description' },
            description
        );
    };

    /**
     * Label component - displays the payment method title with icon
     */
    const Label = (props) => {
        const { PaymentMethodLabel } = props.components;

        const iconUrl = settings.icon || '';

        return createElement(
            'span',
            { className: 'sslcommerz-payment-label' },
            createElement(PaymentMethodLabel, { text: label }),
            iconUrl && createElement(
                'img',
                {
                    src: iconUrl,
                    alt: label,
                    style: {
                        maxWidth: '80px',
                        maxHeight: '24px',
                        marginLeft: '10px',
                        verticalAlign: 'middle',
                        display: 'inline-block'
                    }
                }
            )
        );
    };

    /**
     * SSLCommerz payment method configuration for WooCommerce Blocks
     */
    const SSLCommerzPaymentMethod = {
        name: 'sslcommerz',
        label: createElement(Label, null),
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports || ['products'],
        },
    };

    // Register the payment method with WooCommerce Blocks
    registerPaymentMethod(SSLCommerzPaymentMethod);

})();
