# woocommerce-cashflows

A [CashFlows](http://cashflows.com) payment gateway plugin for [WooCommerce](http://www.woothemes.com/woocommerce/). Allows CashFlows to be configured as a payment option in a WooCommerce based storefront. Payments will then be forwarded to the CashFlows hosted payment page.

## Requirements

This is a Wordpress + WooCommerce plugin; as things stand WooCommerce is required to make this function properly.

## Installation

1. Copy the "woocommerce-cashflows" directory and contents into your wp-content/plugins directory.
2. Add your CashFlows SECRET_KEY and STORE_ID into "class-wc-gateway-cashflows.php", and adjust the currency.
3. Update the callback URLs and callback/return check codes in "class-wc-gateway-cashflows.php".
4. Enable the plugin via Wordpress.
5. Enable CashFlows as a payment gateway option through your WooComm stores admin.

## Limitations

This plugin was built quickly to address a specific need. All CashFlow settings are hard coded for now. These should be extracted out and made configurable via the WooComm admin (things like secret key, store ID, callback URLs, etc.).
