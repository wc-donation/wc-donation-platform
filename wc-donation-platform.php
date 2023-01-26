<?php
/*
 * Plugin Name: Donation Platform for WooCommerce: Fundraising & Donation Management
 * Plugin URI: https://wcdp.jonh.eu/
 * Description: Donation Platform for WooCommerce unlocks the power of WooCommerce for your online fundraising & crowdfunding.
 * Author: Jonas Höbenreich
 * Version: 1.2.9
 * Author URI: https://www.jonh.eu/
 * Plugin URI:  https://wcdp.jonh.eu/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-donation-platform
 * Domain Path: /languages
 * WC requires at least: 4.0.0
 * WC tested up to: 7.2.2
 * Requires at least: 5.8
*/

if(!defined('ABSPATH')) exit;

define( 'WCDP_DIR', dirname(__FILE__).'/' );
define( 'WCDP_DIR_URL', plugin_dir_url( __FILE__ ) );
const WCDP_VERSION = '1.2.9';

/**
 * Check if WooCommerce is active
 */
if (!function_exists('is_woocommerce_active')){
    function is_woocommerce_active(): bool
    {
        $active_plugins = (array) get_option('active_plugins', array());
        if(is_multisite()){
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
    }
}

if ( !class_exists( 'WCDP' ) ) {
    /**
     * Class WCDP
     */
    class WCDP {
        public static $wc_minimum_supported_version = '4.0.0';

        /**
         * WCDP constructor.
         */
        public function __construct() {
            $this->includes();
            new WCDP_Hooks();
            new WCDP_Product_Settings();
            new WCDP_Form();
			new WCDP_Progress();
			new WCDP_Fee_Recovery();

            new WCDP_Feedback();
            WCDP_Integrator::init();

			//Load textdomain
			add_action( 'init', function() {
				load_plugin_textdomain( 'wc-donation-platform', false, WCDP_DIR . '/languages' );
			} );

            //Add plugin action links
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(__CLASS__, 'plugin_action_links') );
            add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
        }

        /**
         * Include required files
         */
        private function includes() {
            //WCDP Donation Form
            include_once 'includes/class-wcdp-form.php';

            //WC Hooks
            include_once 'includes/class-wcdp-hooks.php';

            //Adapt products to be donable
            include_once 'includes/class-wcdp-product-settings.php';

            //WooCommerce settings tab
            include_once 'includes/class-wcdp-general-settings.php';

			//Fundraising Progress
			include_once 'includes/class-wcdp-progress.php';

			//Fee Recovery
			include_once 'includes/class-wcdp-fee-recovery.php';

            //Integration with other Extensions
            include_once 'includes/integrations/class-wcdp-integrator.php';

            //Deactivation survey & Feedback survey
            include_once 'includes/class-wcdp_feedback.php';
        }

        /**
         * Called when WooCommerce is inactive or running an unsupported version to display an inactive notice
         *
         * @since 1.0
         */
        public static function wcdp_inactive_notice() {
            if ( current_user_can( 'activate_plugins' ) ) {

                $admin_notice_content = '';

                if ( ! is_woocommerce_active() ) {
                    $admin_notice_content = esc_html__( 'Donation Platform for WooCommerce requires WooCommerce to be installed & activated.', 'wc-donation-platform' );
                } elseif ( version_compare( get_option( 'woocommerce_db_version' ), self::$wc_minimum_supported_version, '<' ) ) {
                    // translators: %s required WC version
                    $admin_notice_content = sprintf( esc_html__( 'Donation Platform for WooCommerce is inactive. This version of Donation Platform for WooCommerce requires WooCommerce %s or newer. Please update WooCommerce.', 'wc-donation-platform' ), self::$wc_minimum_supported_version );
                }

                if ( $admin_notice_content ) {
                    printf( '<div class="notice notice-error"><p>%s</p></div>', $admin_notice_content );
                }
            }
        }

        /**
         * Adds plugin action links
         * @param $links array plugin action links before filtering.
         * @return array Filtered links.
         */
        public static function plugin_action_links(array $links): array
        {
            $plugin_links = array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wc-donation-platform' ) . '" aria-label="' . esc_attr__( 'View WCDP settings', 'wc-donation-platform' ) . '">' . esc_html__( 'Settings', 'wc-donation-platform' ) . '</a>',
            );

            return array_merge( $plugin_links, $links );
        }

        /**
         * Show row meta on the plugin screen.
         *
         * @param mixed $links Plugin Row Meta.
         * @param mixed $file  Plugin Base file.
         *
         * @return array
         */
        public static function plugin_row_meta( $links, $file ): array
		{
            if (strpos( $file, basename(__FILE__) )) {
                $row_meta = array(
                    'docs'    => '<a href="' . esc_url( 'https://wcdp.jonh.eu/documentation/' ) . '" aria-label="' . esc_attr__( 'View Documentation of Donation Platform for WooCommerce', 'wc-donation-platform' ) . '">' . esc_html__( 'Documentation', 'wc-donation-platform' ) . '</a>',
                );

                return array_merge( $links, $row_meta );
            } else {
                return $links;
            }
        }
    }
}

/**
 * Check if WooCommerce is active and at the required minimum version, and if it isn't, disable WCDP.
 *
 * @since 1.0
 */
if( ! is_woocommerce_active() || version_compare( get_option( 'woocommerce_db_version' ), WCDP::$wc_minimum_supported_version, '<' )) {
    add_action( 'admin_notices', 'WCDP::wcdp_inactive_notice' );
    return;
} else {
	add_action('plugins_loaded', function () {
		new WCDP();
	});
}
