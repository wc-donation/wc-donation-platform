<?php
/**
 * This class implements integrations with other WooCommerce Extensions
 */

class WCDP_Integrator
{
    /**
     * Bootstraps the class and hooks required actions & filters
     */
    public static function init() {
        //Integration with WooCommerce Subscriptions
        //https://woocommerce.com/products/woocommerce-subscriptions/
        include_once 'woocommerce-subscriptions/class-wcdp-subscriptions.php';
        WCDP_Subscriptions::init();

        //Integration with WooCommerce PDF Invoices & Packing Slips
        //https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
        include_once 'woocommerce-pdf-invoices-packing-slips/class-wcdp-pdf-Invoices.php';
        WCDP_Pdf_Invoices::init();
    }
}
