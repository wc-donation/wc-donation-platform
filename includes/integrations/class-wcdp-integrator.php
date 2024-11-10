<?php
/**
 * This class implements integrations with other WooCommerce Extensions
 */

class WCDP_Integrator
{
    /**
     * Bootstraps the class and hooks required actions & filters
     */
    public static function init(): void
    {
        if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

        //some payment gateways do not load with empty checkout
        add_filter('woocommerce_cart_get_cart_contents_total', 'WCDP_Integrator::cart_contents_total', 10, 1);

        // Make sure payment gateways enqueue checkout scripts
        add_filter('woocommerce_cart_needs_payment', 'WCDP_Integrator::set_cart_needs_payment');

        //Integration with WooCommerce Subscriptions
        //https://woocommerce.com/products/woocommerce-subscriptions/
        $subscriptions_active = in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins)
            || in_array('woocommerce-subscriptions-main/woocommerce-subscriptions.php', $active_plugins)
            || is_plugin_active_for_network('woocommerce-subscriptions/woocommerce-subscriptions.php')
            || is_plugin_active_for_network('woocommerce-subscriptions-main/woocommerce-subscriptions.php');
        if ($subscriptions_active) {
            include_once 'woocommerce-subscriptions/class-wcdp-subscriptions.php';
            WCDP_Subscriptions::init();
        }

        //Integration with WooCommerce PDF Invoices & Packing Slips
        //https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
        include_once 'woocommerce-pdf-invoices-packing-slips/class-wcdp-pdf-Invoices.php';
        WCDP_Pdf_Invoices::init();

        //Add support for Stripe & PayPal Express Checkout
        //https://wordpress.org/plugins/woocommerce-gateway-stripe/
        //https://wordpress.org/plugins/woocommerce-paypal-payments/
        $stripe_active = in_array('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php', $active_plugins) || is_plugin_active_for_network('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php');
        $paypal_active = in_array('woocommerce-paypal-payments/woocommerce-paypal-payments.php', $active_plugins) || is_plugin_active_for_network('woocommerce-paypal-payments/woocommerce-paypal-payments.php');
        if ($stripe_active || $paypal_active) {
            include_once 'express-checkout/class-wcdp-express-checkout.php';
            new WCDP_Express_Checkout();
        }

        //Integration with Subscriptions for WooCommerce
        //https://wordpress.org/plugins/subscriptions-for-woocommerce/
        include_once 'subscriptions-for-woocommerce/class-wcdp-subscriptions-for-woocommerce.php';
        WCDP_Subscriptions_For_WooCommerce::init();

        $polylang_active = in_array('polylang/polylang.php', $active_plugins) || is_plugin_active_for_network('polylang/polylang.php');
        if ($polylang_active) {
            include_once 'polylang/class-wcdp-polylang.php';
            //update donation total revenue for translated products
            add_filter('wcdp_update_product_revenue', 'WCDP_Polylang::product_revenue', 10, 2);
        }
    }

    /**
     * Return true if cart contains a WooCommerce Subscriptions or Subscriptions for WooCommerce product
     * @param null $product
     * @return bool
     */
    public static function wcdp_contains_subscription($product = null): bool
    {
        if (class_exists('WC_Subscriptions_Cart')) {
            return WC_Subscriptions_Cart::cart_contains_subscription();
        } else if (function_exists('wps_sfw_check_product_is_subscription')) {
            return wps_sfw_check_product_is_subscription($product);
        } else {
            return false;
        }
    }

    /**
     * PayPal Payment Plugin only loads when the cart total != 0
     * @param $total
     * @return float|mixed
     */
    public static function cart_contents_total($total)
    {
        if (!wp_doing_ajax() && $total == 0 && WCDP_FORM::wcdp_has_donation_form()) {
            //Return very small amount (rounded to 0 in checkout)
            return 4.9E-324;
        }
        return $total;
    }

    /**
     * Mark the Cart as needs payment (needed for some payment gateways)
     *
     * @param $needs_payment
     * @return mixed|true
     */
    public static function set_cart_needs_payment($needs_payment) {
        if (!wp_doing_ajax() && !$needs_payment && WCDP_FORM::wcdp_has_donation_form()) {
            return true;
        }
        return $needs_payment;
    }
}
