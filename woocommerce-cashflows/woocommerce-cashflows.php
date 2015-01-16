<?php

/**
 * Plugin Name: WooCommerce CashFlows Plugin
 * Plugin URI: https://github.com/hwillson/woocommerce-cashflows
 * Description: WooCommerce CashFlows gateway plugin.
 * Author: Hugh Willson, Octonary Inc.
 * License: http://opensource.org/licenses/MIT
 * Version: 0.0.1
 */

if (!defined('ABSPATH')) exit;

if (in_array('woocommerce/woocommerce.php',
    apply_filters('active_plugins', get_option('active_plugins')))) {

  if (!class_exists('WC_Cashflows')) {

    class WC_Cashflows {

      public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
      }

      public function plugins_loaded() {
        require_once 'class-wc-gateway-cashflows.php';
      }

    }

  	global $wc_cashflows;
  	$wc_cashflows = new WC_Cashflows();

  }

}
