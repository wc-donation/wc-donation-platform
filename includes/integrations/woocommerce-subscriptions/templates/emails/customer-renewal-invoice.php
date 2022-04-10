<?php
/**
 * Customer renewal invoice email
 *
 * forked from WooCommerce_Subscription\Templates by Prospress
 * @package WooCommerce_Subscriptions/Templates/Emails
 * @version 2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<?php if ( $order->has_status( 'pending' ) ) : ?>
	<p><?php echo wp_kses(
	sprintf(
		// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end
		_x( 'A donation has been created for you to renew your recurring donation on %1$s. Please use the following link to pay for this donation: %2$s', 'In customer renewal invoice email', 'wc-donation-platform' ),
		esc_html( get_bloginfo( 'name' ) ),
		'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay Now &raquo;', 'woocommerce-subscriptions' ) . '</a>'
	), array( 'a' => array( 'href' => true ) ) ); ?>
	</p>
<?php elseif ( $order->has_status( 'failed' ) ) : ?>
	<p><?php echo wp_kses(
	sprintf(
		// translators: %1$s: name of the blog, %2$s: link to checkout payment url, note: no full stop due to url at the end
		_x( 'The automatic payment to renew your regular donation with %1$s has failed. Please log in and set up a different payment option: %2$s', 'In customer renewal invoice email', 'wc-donation-platform' ),
		esc_html( get_bloginfo( 'name' ) ),
		'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay Now &raquo;', 'woocommerce-subscriptions' ) . '</a>'
	), array( 'a' => array( 'href' => true ) ) ); ?>
	</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
