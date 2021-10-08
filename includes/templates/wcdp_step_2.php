<?php
/*
WCDP Shortcode Form
*/

if(!defined('ABSPATH')) exit;

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( WC()->checkout()->get_checkout_fields() ) {
	do_action( 'woocommerce_checkout_before_customer_details' );
	do_action( 'woocommerce_checkout_billing' );
	do_action( 'woocommerce_checkout_shipping' );
	do_action( 'woocommerce_checkout_after_customer_details' );
}