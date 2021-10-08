<?php
/**
 * Customer payment retry email (plain text)
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
/* translators: %s: lowercase human time diff in the form returned by wcs_get_human_time_diff(), e.g. 'in 12 hours' */
echo sprintf( esc_html_x( 'The automatic payment to renew your recurring donation has failed. We will retry the payment %s.', 'In customer renewal invoice email', 'wc-donation-platform' ), esc_html( wcs_get_human_time_diff( $retry->get_time() ) ) ) . "\n\n";

// translators: %1$s: link to checkout payment url, note: no full stop due to url at the end
echo sprintf( esc_html_x( 'To reactivate the recurring donation now, you can also log in and pay for the renewal from your account page: %1$s', 'In customer renewal invoice email', 'wc-donation-platform' ), esc_attr( $order->get_checkout_payment_url() ) );

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
