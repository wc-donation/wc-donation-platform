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
		add_filter("product_type_options", array($this, 'wcdp_add_product_type_option'), 11);

		//Safe donable Product
		add_action("save_post_product", array($this, 'wcdp_save_post_product'), 10, 3);
    }

    /**
     * Add donation tab on product edit page
     *
     * @param array $tabs
     * @return array
     */
    public function wcdp_product_data_tabs(array $tabs = array() ): array
    {
        $wcdp_options =  array(
            'label'    => __( 'Donation Form', 'wc-donation-platform' ),
            'target'   => 'wcdp_donation_form_data',
            'class'    => 'show_if_donable hidden donation_options hide_if_external',
            'priority' => 65,
        );
        $tabs[] = $wcdp_options;
        return $tabs;
    }

    /**
     * Add donation data panel on product edit page
     */
    public function wcdp_product_data_panel() {
        include WCDP_DIR . '/includes/templates/wcdp_product_settings.php';
    }

    /**
     * Save donation data from product edit page
     * @param $post_id
     */
    public function wcdp_process_product_meta( $post_id ) {
        $product = wc_get_product( $post_id );
		$product_settings = $this->product_meta_style(array());
		$product_settings = $this->product_meta_amount_selection($product_settings);
		$product_settings = $this->product_meta_variation_styles($product_settings, $product);
		$product_settings = $this->product_meta_fundraising_meta($product_settings);

        foreach ($product_settings as $key => $value) {
            $product->update_meta_data( 'wcdp-settings[' . $key . ']', $value );
        }
        $product->save();
    }

	/**
	 * Determine style of product
	 * @param $product_settings
	 * @return array
	 */
	private function product_meta_style($product_settings): array
	{
		if (isset($_POST['wcdp-amount-layout'])) {
			if ($_POST['wcdp-amount-layout'] == '1') {
				$product_settings[0] = 1;
			} else if ($_POST['wcdp-amount-layout'] == '2') {
				$product_settings[0] = 2;
			} else if ($_POST['wcdp-amount-layout'] == '3') {
				$product_settings[0] = 3;
			} else {
                $product_settings[0] = 0;
            }
		} else {
			//Default style
			$product_settings[0] = 0;
		}
		return $product_settings;
	}

	/**
	 * Determine predefined amounts
	 * @param $product_settings
	 * @return array
	 */
	private function product_meta_amount_selection($product_settings): array
	{
		if (isset($_POST['wcdp-settings']) && wp_is_numeric_array($_POST['wcdp-settings']) && $product_settings[0] == 1){
			$prices = array();
			foreach ($_POST['wcdp-settings'] as $value) {
				$prices[] = (float)$value;
			}
			sort($prices);
			$product_settings[1] = json_encode($prices);
		} else {
			$product_settings[1] = '';
            if ($product_settings[0] == 1) {
                $product_settings[0] = 0;
            }
		}
		return $product_settings;
	}

	/**
	 * Determine style of variation attributes
	 * @param $product_settings
	 * @param $product
	 * @return array
	 */
	private function product_meta_variation_styles($product_settings, $product): array
	{
		if (is_a( $product, 'WC_Product_Variable' )) {
			$attributes = $product->get_variation_attributes();
			foreach ( $attributes as $attribute => $options ) {
				$attribute_name = esc_attr( sanitize_title( $attribute ) );
				$product_settings['wcdp-attr-'.$attribute_name] = '0';
				if (isset($_POST['wcdp-attr-'.$attribute_name])) {
					if ($_POST['wcdp-attr-'.$attribute_name] == '1') {
						$product_settings['wcdp-attr-'.$attribute_name] = '1';
					} else if ($_POST['wcdp-attr-'.$attribute_name] == '2') {
						$product_settings['wcdp-attr-'.$attribute_name] = '2';
					}
				}
			}
		}
		return $product_settings;
	}

	/**
	 * Determine fundraising end date & goal
	 * @param $product_settings
	 * @return array
	 */
	private function product_meta_fundraising_meta($product_settings): array
	{
		if (isset($_POST['wcdp_fundraising_goal']) && $_POST['wcdp_fundraising_goal'] != ''){
			$product_settings['wcdp_fundraising_goal'] = (float) $_POST['wcdp_fundraising_goal'];
		} else {
			$product_settings['wcdp_fundraising_goal'] = 0;
		}

		if (isset($_POST['wcdp_fundraising_end_date']) && $_POST['wcdp_fundraising_end_date'] != ''){
			$product_settings['wcdp_fundraising_end_date'] = preg_replace( '/[^0-9-]/', '', $_POST['wcdp_fundraising_end_date'] );
		} else {
			$product_settings['wcdp_fundraising_end_date'] = '';
		}
		return $product_settings;
	}

	/**
	 * Add "Donable" as a product type option
	 * Only if this option is enabled one can donate to the product
	 *
	 * @param $product_type_options
	 * @return array
	 */
    public function wcdp_add_product_type_option($product_type_options): array
    {
		$product_type_options["donable"] = [
			"id"            => "_donable",
			"wrapper_class" => "show_if_simple show_if_variable show_if_grouped",
			"label"         => __( 'Donation Product', 'wc-donation-platform' ),
			"description"   => __( 'This product will only be used for donations if activated', 'wc-donation-platform' ),
			"default"       => "on",
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
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}
		update_post_meta(
			$product->ID
			, "_donable"
			, isset($_POST["_donable"]) ? "yes" : "no"
		);
	}
}
