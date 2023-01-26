<?php
/**
 * This class integrates WooCommerce PDF Invoices & Packing Slips with Donation Platform for WooCommerce
 */
if(!defined('ABSPATH')) exit;

class WCDP_Pdf_Invoices
{
    /**
     * Bootstraps the class and hooks required actions & filters
     */
    public static function init() {
        $pdf_invoices_active = in_array('woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php', apply_filters('active_plugins', get_option('active_plugins')));

        if ($pdf_invoices_active) {
            //Add a Tax Receipt Template
            add_filter('wpo_wcpdf_template_paths', 'WCDP_Pdf_Invoices::add_template', 10, 1);

            //Rename pdf file name
            add_filter('wpo_wcpdf_filename', 'WCDP_Pdf_Invoices::filename', 10, 3);

			//Rename pdf file name
			add_filter('wpo_wcpdf_document_classes', 'WCDP_Pdf_Invoices::add_document_type' );

			//Rename Invoice to Donation Receipt
			add_filter('wpo_wcpdf_invoice_title', function() {
				return __('Donation Receipt', 'wc-donation-platform');
			} );
        }
    }

    /**
     * @param $template_paths
     * @return array
     */
    public static function add_template($template_paths) {
        $template_paths['wcdp'] = WCDP_DIR . 'includes/integrations/woocommerce-pdf-invoices-packing-slips/templates/';
        return $template_paths;
    }

    /**
     * @param $filename
     * @return string
     */
    public static function filename($filename, $type, $order_ids): string
    {
		$type = 'invoice' == $type ? 'receipt' : $type;
        return sanitize_title(get_bloginfo( 'name' ), 'wcdp') . '_' .$type . '_' . implode('-', $order_ids) . '.pdf';
    }

    /**
     * @param array $documents
     * @return array
     */
	public static function add_document_type(array $documents = array()): array
    {
		if (file_exists(WP_PLUGIN_DIR . '/woocommerce-pdf-invoices-packing-slips/includes/documents/abstract-wcpdf-order-document-methods.php')) {
			$documents['WCDP_Donation_Receipt'] = include_once 'class-wcdp-thank-you-certificate.php';
		}
		return $documents;
	}
}
