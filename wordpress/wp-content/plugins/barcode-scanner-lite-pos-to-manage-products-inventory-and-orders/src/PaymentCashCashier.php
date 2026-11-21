<?php

if (!defined('ABSPATH')) {
    exit;
}


add_filter('woocommerce_payment_gateways', function ($methods) {
    $methods[] = 'WC_Gateway_Payment_Cash_Cashier';
    return $methods;
});

