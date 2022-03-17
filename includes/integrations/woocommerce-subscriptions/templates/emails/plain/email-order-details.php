<?php
/**
 * Order/Subscription details table shown in emails.
 *
 * forked from WooCommerce_Subscription\Templates
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_before_' . $order_type . '_table', $order, $sent_to_admin, $plain_text, $email );

if ( 'order' == $order_type ) {
	/* translators: %s donation number*/
	echo sprintf( __( 'Donation number: %s', 'wc-donation-platform' ), $order->get_order_number() ) . "\n";
	/* translators: %s donation date*/
	echo sprintf( __( 'Donation date: %s', 'wc-donation-platform' ), wcs_format_datetime( wcs_get_objects_property( $order, 'date_created' ) ) ) . "\n";
} else {
	/* translators: %s recurring donation number*/
	echo sprintf( __( 'Recurring Donation Number: %s', 'wc-donation-platform' ), $order->get_order_number() ) . "\n";
}
echo "\n" . WC_Subscriptions_Email::email_order_items_table( $order, $order_items_table_args );

echo "----------\n\n";

$totals = $order->get_order_item_totals();
if ( $totals ) {
	foreach ( $totals as $total ) {
		echo $total['label'] . "\t " . $total['value'] . "\n";
	}
}

do_action( 'woocommerce_email_after_' . $order_type . '_table', $order, $sent_to_admin, $plain_text, $email );
