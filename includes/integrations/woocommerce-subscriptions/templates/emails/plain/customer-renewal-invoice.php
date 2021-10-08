<?php
/**
 * Customer renewal invoice email (plain text)
 *
 * forked from WooCommerce_Subscription\Templates
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

if ( $order->has_status( 'pending' ) ) {
	// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end
	printf( esc_html_x( 'Please pay for your recurring donation to %1$s using the following link: %2$s', 'In customer renewal invoice email', 'wc-donation-platform' ), esc_html( get_bloginfo( 'name' ) ), esc_attr( $order->get_checkout_payment_url() ) ) . "\n\n";
} elseif ( $order->has_status( 'failed' ) ) {
	// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end
	printf( esc_html_x( 'The automatic payment to renew your recurring donation to %1$s has failed. To reactivate the recurring donation, please log in and pay for the renewal donation from your account page: %2$s', 'In customer renewal invoice email', 'wc-donation-platform' ), esc_html( get_bloginfo( 'name' ) ), esc_attr( $order->get_checkout_payment_url() ) );
}

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
