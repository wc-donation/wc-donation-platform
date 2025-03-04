<?php
/**
 * WCDP Shortcode Form
 *
 * @var array $value
 * @var string $context
 */

if (!defined('ABSPATH')) exit;

/**
 * Add woocommerce_after_add_to_cart_form hook in step 2
 * needed in order to support Stripe express checkout
 */
if ($value['style'] !== 4 && $context == 'shortcode') {
    do_action('woocommerce_after_add_to_cart_form');
}

// If checkout registration is disabled and not logged in, the user cannot check out.
if (WC()->checkout()->get_checkout_fields()) {
    do_action('woocommerce_checkout_before_customer_details');
    do_action('woocommerce_checkout_billing');
    do_action('woocommerce_checkout_shipping');
    do_action('woocommerce_checkout_after_customer_details');
}