<?php
/**
 * Review donation table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
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

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	return;
}

?>
<div class="woocommerce-form-login-toggle">
	<?php wc_print_notice( esc_html__( 'Returning donor?', 'wc-donation-platform' ) . ' <a href="#" class="showlogin">' . esc_html__( 'Click here to login', 'wc-donation-platform' ) . '</a>', 'notice' ); ?>
</div>
<?php

woocommerce_login_form(
	array(
		'message'  => esc_html__( 'Sign in with your account to contribute faster!', 'wc-donation-platform' ),
		'hidden'   => true,
	)
);
