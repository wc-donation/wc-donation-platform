<?php
/**
 * This class adapts WC Products as donable products
 */

if(!defined('ABSPATH')) exit;

class WCDP_Product_Settings
{
	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
    public function __construct() {
        //Add donation tab on product edit page
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'wcdp_product_data_tabs'), 10, 3);

        //Add donation data panel on product edit page
        add_action( 'woocommerce_product_data_panels', array( $this, 'wcdp_product_data_panel') );

        //Safe donation specific product meta
        add_action( 'woocommerce_process_product_meta', array( $this, 'wcdp_process_product_meta') );

        //donable Product
		add_filter("product_type_options", array($this, 'wcdp_add_product_type_option'));

		//Safe donable Product
		add_action("save_post_product", array($this, 'wcdp_save_post_product'), 10, 3);
    }

    /**
     * Add donation tab on product edit page
     *
     * @param array $tabs
     * @return array
     */
    public function wcdp_product_data_tabs( $tabs = array() ): array
    {
        $wcdp_options =  array(
            'label'    => __( 'Donation Form', 'wc-donation-platform' ),
            'target'   => 'wcdp_donation_form_data',
            'class'    => 'show_if_donable hidden donation_options hide_if_external',
            'priority' => 65,
        );
        array_push($tabs, $wcdp_options);
        return $tabs;
    }

    /**
     * Add donation data panel on product edit page
     */
    public function wcdp_product_data_panel() {
        include WCDP_DIR . '/includes/templates/wcdp_product_settings.php';
    }

    /**
     * Add donation data panel on product edit page
     * @param $post_id
     */
    public function wcdp_process_product_meta( $post_id ) {
        $product = wc_get_product( $post_id );
        $price_format = get_woocommerce_price_format();
        $currency_symbol = get_woocommerce_currency_symbol();
        $wc_donation_platform = array();

        if (isset($_POST['wcdp-amount-layout'])) {
			if ($_POST['wcdp-amount-layout'] == 0) {
				$wc_donation_platform[0] = 0;
			} else if ($_POST['wcdp-amount-layout'] == 1) {
				$wc_donation_platform[0] = 1;
			} else if ($_POST['wcdp-amount-layout'] == 2) {
				$wc_donation_platform[0] = 2;
			} else if ($_POST['wcdp-amount-layout'] == 3) {
				$wc_donation_platform[0] = 3;
			}
		} else {
            $wc_donation_platform[0] = '0';
        }

        if (isset($_POST['wcdp-settings']) && wp_is_numeric_array($_POST['wcdp-settings'])){
			$prices = array();
			foreach ($_POST['wcdp-settings'] as $value) {
				array_push($prices, (float) $value);
			}
			$wc_donation_platform[1] = json_encode($prices);
        } else {
            $wc_donation_platform[1] = '';
        }

        if (isset($_POST['wcdp-activate-embed']) && $_POST['wcdp-activate-embed'] = 'yes'){
            $wc_donation_platform[4] = 'yes';
        } else {
            $wc_donation_platform[4] = 'no';
        }

        if (is_a( $product, 'WC_Product_Variable' )) {
            $attributes = $product->get_variation_attributes();
            foreach ( $attributes as $attribute => $options ) {
                $attribute_name = esc_attr( sanitize_title( $attribute ) );
                $wc_donation_platform['wcdp-attr-'.$attribute_name] = '0';
                if (isset($_POST['wcdp-attr-'.$attribute_name])) {
                    if ($_POST['wcdp-attr-'.$attribute_name] == '1') {
                        $wc_donation_platform['wcdp-attr-'.$attribute_name] = '1';
                    } else if ($_POST['wcdp-attr-'.$attribute_name] == '2') {
                        $wc_donation_platform['wcdp-attr-'.$attribute_name] = '2';
                    }
                }
            }
        }

        foreach ($wc_donation_platform as $key => $value) {
            $product->update_meta_data( 'wcdp-settings[' . $key . ']', $value );
        }

        $product->save();
    }

	/**
	 * Add "Donable" as a product type option
	 * Only if this option is enabled one can donate to the product
	 *
	 * @param $product_type_options
	 * @return mixed
	 */
    public function wcdp_add_product_type_option($product_type_options) {

		$product_type_options["donable"] = [
			"id"            => "_donable",
			"wrapper_class" => "show_if_simple show_if_variable show_if_grouped",
			"label"         => __( 'Donable', 'wc-donation-platform' ),
			"description"   => __( 'This product will only be used for donations if activated', 'wc-donation-platform' ),
			"default"       => "no",
		];

		return $product_type_options;
	}

	/**
	 * Safe "Donable" product type option
	 *
	 * @param $post_ID
	 * @param $product
	 * @param $update
	 */
	public function wcdp_save_post_product($post_ID, $product, $update) {
		update_post_meta(
			$product->ID
			, "_donable"
			, isset($_POST["_donable"]) ? "yes" : "no"
		);
	}
}
