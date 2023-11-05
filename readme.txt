=== SSLCommerz Payment Gateway ===
Contributors: prabalsslw, rkbi
Tags: sslcommerz, Payment, gateway, easycheckout, hosted, bangladesh, official
Author URI: https://www.sslcommerz.com
Plugin URI: https://github.com/sslcommerz/SSLCommerz-Woocommerce
Version: 6.1.0
Requires PHP: 7.4
Requires at least: 3.6
Tested up to: 6.4
Stable tag: 6.1.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Prerequisites ==
1. Wordpress 5.1 or later
2. WooCommerce 3.6 or later
3. cURL php extension enabled.
4. [Sandbox Credentials](https://developer.sslcommerz.com/registration/ "SSLCommerz Sandbox Registration") or [Live Credentials](https://signup.sslcommerz.com/register/ "Merchant Registration")


== Installation ==

1. Download zip file or Clone the repository.
2. Go to `Plugins > Add New`.
3. Click on `Upload Plugin` button.
4. Go to `Choose File` and upload the Zip file of plugin.
5. Active the plugin.

== Gateway Configuration ==

1. Open Admin Panel. [Check Screenshot-1]
2. Navigate to ```Woocommerce > Settings > Payments``` tab. [Check Screenshot-2,3]
3. Click on SSLCommerz to edit the settings. If you do not see SSLCommerz in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager.
4. Enable the Payment Method, give a proper title and description to show on the checkout page,  fill up stroe id and store passowrd fields carefully, select success and fail/cancel page.
5. You can enable or disable Hosted/Popup mode from `Hosted EasyCheckout` 
6. Setup is complete. Check if everything is working properly.

== Frequently Asked Questions == 

### I am getting error which says my store is de-active.
> check Testmode, Store ID and Store Password in the settings. If issue still persists, communicate with merchnat's Key Account Manager (**KAM**).
 
### How can I enable IPN?
> This plugin handled IPN with ZERO configuration. No action needed from your end. 

### I want to create order in woocommerce only if transaction is successful. Otherwise order will not be placed.
> This is NOT possible. Order gets created before going to payment page with "Pending" status. After transaction order status will be updated within a short time. 

### I want to enable EMI option, how it works?
> To enable EMI option first you have to make an agreement with us. To do that please communicate with the Business person, you have communicate at the time of Store registration. Besides this in live, you will have EMI configuration option after login to your Merchant report panel go to `My Stores>EMI Settings`.

### I want my customer will bear the gateway charges. How can I do that?
> Yes, We have solution for this, you can configure this from your merchant panel or mail to `operation@sslcommerz.com` .


== License ==
- GPL3

     
== Screenshots ==
1. Install the plugin.
2. Plugin configure page 1.
3. Plugin configure page 2.
