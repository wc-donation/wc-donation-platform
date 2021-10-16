<?php
if(!defined('ABSPATH')) exit;

class WCDP_General_Settings {

    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public function __construct() {
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50 );
        add_action( 'woocommerce_settings_tabs_wc-donation-platform', array($this, 'settings_tab') );
        add_action( 'woocommerce_update_options_wc-donation-platform', array($this, 'update_settings') );
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wc-donation-platform'] = __( 'Donations', 'wc-donations-settings' );
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
    }


    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public function get_settings(): array
	{
    	$decimals = pow(10, wc_get_price_decimals() * (-1));
        $settings = array(
				array(
					'title' => __( 'General Options', 'wc-donation-platform' ),
					'type'  => 'title',
					'id'    => 'wcdp_settings_general',
				),
				array(
					'title'    	=> __( 'Minimum Donation amount', 'wc-donation-platform' ),
					'id'       	=> 'wcdp_min_amount',
					'type'     	=> 'number',
					'default'	=> '3',
					'custom_attributes'	=> array(
						'min'	=> $decimals,
						'step'	=> $decimals,
					),
				),
				array(
					'title'    	=> __( 'Maximum Donation amount', 'wc-donation-platform' ),
					'id'       	=> 'wcdp_max_amount',
					'type'     	=> 'number',
					'default'	=> '5000',
					'custom_attributes'	=> array(
						'min'	=> $decimals,
						'step'	=> $decimals,
					),
				),
				array(
					'title'    	=> __( 'Maximum Amount for range input', 'wc-donation-platform' ),
					'id'       	=> 'wcdp_max_range',
					'type'     	=> 'number',
					'default'	=> '500',
					'custom_attributes'	=> array(
						'min'	=> '1',
						'step'	=> 'any',
					),
				),
            array(
                'title'           => __( 'Disable Order / Donation notes', 'wc-donation-platform' ),
                'desc'            => __( 'Enable to disable notes on checkout', 'wc-donation-platform' ),
                'id'              => 'wcdp_disable_order_notes',
                'default'         => 'no',
                'type'            => 'checkbox',
                'show_if_checked' => 'option',
            ),

				array(
					'type' => 'sectionend',
					'id'   => 'wcdp_section_general',
				),

				array(
					'title' => __( 'Design Options', 'wc-donation-platform' ),
					'type'  => 'title',
					'id'    => 'wcdp_settings_design',
				),

				array(
					'title'    => __( 'Main Color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'Primary Color used in the frontend. Default: %s.', 'wc-donation-platform' ), '<code>#00753a</code>' ),
					'id'       => 'wcdp_main_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#00753a',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Secondary Color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'Secondary Color used in the frontend. Default: %s.', 'woocommerce' ), '<code>#30bf76</code>' ),
					'id'       => 'wcdp_secondary_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#30bf76',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Error Color', 'woocommerce' ),
					// translators: %s: default color //
					'desc'     => sprintf( __( 'Error Color used in the frontend. Default: %s.', 'woocommerce' ), '<code>#de0000</code>' ),
					'id'       => 'wcdp_error_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#de0000',
					'autoload' => false,
					'desc_tip' => true,
				),


				array(
					'type' => 'sectionend',
					'id'   => 'wcdp_section_design',
				),

			array(
				'title' => __( 'Support', 'wc-donation-platform' ),
				'desc' => __( 'Do you like Donation Platform for WooCommerce? Please consider supporting the plugin with your review or suggestion for improvement.', 'wc-donation-platform' ),
				'type'  => 'title',
				'id'    => 'wcdp_settings_support',
			),


		);

        return apply_filters( 'wcdp-general-settings', $settings );
    }

}

$wc_settings = new WCDP_General_Settings();
