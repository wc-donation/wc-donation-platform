<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * forked from WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;

global $post;
global $product;

echo do_shortcode( '[wcdp_progress id="' . $post->ID . '" style="2"]' );

if ( ! $product->is_purchasable() &&  $product->get_type() != 'grouped') {
	return;
}

if ( $product->is_in_stock() ) {
	/**
	 * Display Donation Form
	 */
	echo WCDP_Form::wcdp_donation_form(array(
        'id' => $post->ID,
		'style' => '4',
    ), true);
}
