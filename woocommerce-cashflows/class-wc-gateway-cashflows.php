<?php

/**
 * woocommerce-cashflows
 * https://github.com/hwillson/woocommerce-cashflows
 *
 * @author     Hugh Willson, Octonary Inc.
 * @copyright  Copyright (c) 2015 Hugh Willson, Octonary Inc.
 * @license    http://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) exit;

/**
 * Provides a CashFlows Payment Gateway.
 */
class WC_Gateway_Cashflows extends WC_Payment_Gateway {

	public function __construct() {

		$this->test = intVal(preg_match('/\/beta\//', __FILE__));
	  $this->id = 'cashflows';
	  $this->has_fields = false;
	  $this->method_title = __('CashFlows', 'woocommerce');
	  $this->init_form_fields();
	  $this->init_settings();
		$this->title = $this->get_option('title');

	  add_action(
	    'woocommerce_update_options_payment_gateways_' . $this->id,
	    array($this, 'process_admin_options'));

		// Payment listener/API hook
		add_action(
		  'woocommerce_api_wc_gateway_cashflows',
		  array($this, 'cashflows_callback'));

	}

	public function init_form_fields() {
	  $this->form_fields =
	    array(
	      'enabled' => array(
	        'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable CashFlows', 'woocommerce' ),
					'default' => 'yes'
	      ),
	      'title' => array(
					'title' => __('Title', 'woocommerce'),
					'type' => 'text',
					'description' =>
						__('This controls the title which the user sees during checkout.',
							'woocommerce'),
					'default' => __('CashFlows', 'woocommerce'),
					'desc_tip' => true,
				)
	    );
	}

	public function process_payment($order_id) {
		return array(
			'result' => 'success',
			'redirect' =>
				($this->test ? '/beta' : '')
					. "/?wc-api=WC_Gateway_Cashflows&order_id=$order_id"
		);
	}

  public function cashflows_callback() {

    if (isset($_REQUEST['order_id'])) {
      $this->postToCashflows($_REQUEST['order_id']);
      exit;
    } else {
      $status = $_POST['auth_status'];
      $order_id = $_POST['cart_id'];
      $order = new WC_Order($order_id);
      if ($status == 'A') {
        $amount = $_POST['cart_cost'];
        $total = $order->get_total();
        $amount_paid_to_date =
          get_post_meta($order_id, 'lf_amount_paid', true);
        if (empty($amount_paid_to_date)) {
          add_post_meta($order_id, 'lf_amount_paid', $amount, true);
        } else {
          update_post_meta(
            $order_id, 'lf_amount_paid', $amount_paid_to_date + $amount);
        }
        if (($amount == $total)
            || (($amount_paid_to_date + $amount) == $total)) {
          update_post_meta($order_id, 'lf_paid_in_full', 1);
          // $order->add_order_note(
          //   "CashFlows - FULL payment of $total completed.");
          $order->update_status(
            'completed', "CashFlows - FULL payment of $total completed.");
        } else {
          // $order->update_status('on-hold', 'Awaiting payment.');
          update_post_meta($order_id, 'lf_paid_in_full', 0);
          $order->add_order_note(
            "CashFlows - PARTIAL payment of $amount completed.");
        }
        $order->payment_complete();
      } else if ($status == 'C') {
        $order->update_status(
          'cancelled',
          'Order cancelled by customer during CashFlows checkout process.');
      } else {
        $order->update_status(
          'failed',
          'Order failed; problem checking out with CashFlows.');
      }
    }

  }

  private function postToCashflows($order_id) {

    $order = new WC_Order($order_id);

    $description = '';
    foreach ($order->get_items() as $item) {
      $description .= $item['name'].'; ';
    }

    $will_pay_full = get_post_meta($order_id, '_lf_will_pay_full', true);
    $total = null;
    if ($will_pay_full) {
      $total = $order->get_total();
      $description .= 'Full Payment';
    } else {
      $deposit = get_post_meta($order_id, '_lf_deposit', true);
      $total = $deposit;
      $description .= 'Partial Payment';
    }
    $description =
			html_entity_decode(strip_tags($description), ENT_COMPAT, 'UTF-8');

    $secret_key = 'SECRET_KEY_GOES_HERE';
    $store_id = 'STORE_ID_GOES_HERE';
    $cart_id = $order_id;
    $currency = 'GBP';
    $check =
      hash(
        'sha256',
        "$secret_key:$store_id:$cart_id:$total:$currency:"
					. $this->test . ":$description");

    $name = $order->billing_first_name . ' ' . $order->billing_last_name;
    $address =
      $order->billing_address_1 . ' ' . $order->billing_address_2 . "\n"
      . $order->billing_city . ' ' . $order->billing_state;
    $postcode = $order->billing_postcode;
    $country = $order->billing_country;
    $tel = $order->billing_phone;
    $email = $order->billing_email;

    if ($this->test) {
      $url_callback_ok =
        'http://addhosthere.com/?wc-api=WC_Gateway_Cashflows';
      $url_callback_cancel =
        'http://addhosthere.com/?wc-api=WC_Gateway_Cashflows';
      $url_callback_fail =
        'http://addhosthere.com/?wc-api=WC_Gateway_Cashflows';
      $url_callback_check =
        'CALLBACK_CHECK_GOES_HERE';
      $url_return_ok =
        'http://addhosthere.com/checkout/order-received?order=@@cart_id@@';
      $url_return_cancel =
        'http://addhosthere.com';
      $url_return_check =
        'RETURN_CHECK_GOES_HERE';
    }

    $form = <<<HTML
      <html>
        <head>
          <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        </head>
        <body>
          <form name="checkout" id="cashflows-checkout" method="post"
              action="https://secure.cashflows.com/gateway/standard">
            <input type="hidden" name="store_id" value="$store_id" />
            <input type="hidden" name="cart_id" value="$cart_id" />
            <input type="hidden" name="amount" value="$total" />
            <input type="hidden" name="currency" value="$currency" />
            <input type="hidden" name="test" value="$this->test" />
            <input type="hidden" name="description" value="$description"/>
            <input type="hidden" name="check" value="$check" />
            <input type="hidden" name="name" value="$name" />
            <input type="hidden" name="address" value="$address" />
            <input type="hidden" name="postcode" value="$postcode" />
            <input type="hidden" name="country" value="$country" />
            <input type="hidden" name="tel" value="$tel" />
            <input type="hidden" name="email" value="$email" />
HTML;

    if ($this->test) {
      $form .= <<<HTML
            <input type="hidden" name="url_callback_ok"
              value="$url_callback_ok">
            <input type="hidden" name="url_callback_cancel"
              value="$url_callback_cancel">
            <input type="hidden" name="url_callback_fail"
              value="$url_callback_fail">
            <input type="hidden" name="url_callback_check"
              value="$url_callback_check">
            <input type="hidden" name="url_return_ok"
              value="$url_return_ok">
            <input type="hidden" name="url_return_cancel"
              value="$url_return_cancel">
            <input type="hidden" name="url_return_check"
              value="$url_return_check">
HTML;
    }

    $form .= <<<HTML
          </form>
          <script>
            jQuery(document).ready(function() {
              jQuery('#cashflows-checkout').submit();
            });
          </script>
        </body>
      </html>
HTML;
    echo $form;

  }

}

?>
