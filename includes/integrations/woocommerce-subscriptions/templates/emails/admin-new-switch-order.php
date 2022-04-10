<?php
/**
 * Admin new switch order email
 *
 * forked from WooCommerce_Subscription\Templates by Brent Shepherd
 * @package WooCommerce_Subscriptions/Templates/Emails
 * @version 2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email );

$switched_count = count( $subscriptions );

/* translators: $1: customer's first name and last name, $2: how many subscriptions customer switched */ ?>
<p><?php echo esc_html( sprintf( _nx( 'Customer %1$s has switched their recurring donation. The details of their new recurring donation are as follows:', 'Customer %1$s has switched %2$d of their recurring donations. The details of their new recurring donations are as follows:', $switched_count, 'Used in switch notification admin email', 'wc-donation-platform' ), $order->get_formatted_billing_full_name(), $switched_count ) );?></p>

<h2><?php esc_html_e( 'Switch Order Details', 'woocommerce-subscriptions' ); ?></h2>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
?>

<h2><?php esc_html_e( 'New recurring donation details', 'wc-donation-platform' ); ?></h2>
<?php

foreach ( $subscriptions as $subscription ) {
	do_action( 'woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email );
}

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
