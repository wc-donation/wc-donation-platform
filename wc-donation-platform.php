<?php
/*
 * Plugin Name: Donation Platform for WooCommerce: Fundraising & Donation Management
 * Plugin URI: https://www.wc-donation.com/
 * Description: Donation Platform for WooCommerce unlocks the power of WooCommerce for your online fundraising & crowdfunding.
 * Author: Jonas Höbenreich
 * Version: 1.3.4.2
 * Author URI: https://www.jonh.eu/
 * Plugin URI:  https://www.wc-donation.com/
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-donation-platform
 * Domain Path: /languages
 * WC requires at least: 4.0.0
 * WC tested up to: 9.8.5
 * Requires at least: 5.8
 */

if (!defined('ABSPATH'))
    exit;

define('WCDP_DIR', dirname(__FILE__) . '/');
define('WCDP_DIR_URL', plugin_dir_url(__FILE__));
const WCDP_VERSION = '1.3.4.2';

/**
 * Check if WooCommerce is active
 */
if (!function_exists('is_woocommerce_active')) {
    function is_woocommerce_active(): bool
    {
        $active_plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
    }
}

if (!class_exists('WCDP')) {
    /**
     * Class WCDP
     */
    class WCDP
    {
        public static $wc_minimum_supported_version = '4.0.0';

        /**
         * WCDP constructor.
         */
        public function __construct()
        {
            $this->includes();
            new WCDP_Hooks();
            new WCDP_Product_Settings();
            new WCDP_Form();
            new WCDP_Progress();
            new WCDP_Fee_Recovery();
            new WCDP_Leaderboard();

            new WCDP_Feedback();
            WCDP_Integrator::init();

            //Add plugin action links
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'plugin_action_links'));
            add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);
        }

        /**
         * Include required files
         */
        private function includes()
        {
            //WCDP Donation Form
            include_once WCDP_DIR . 'includes/class-wcdp-form.php';

            //WC Hooks
            include_once WCDP_DIR . 'includes/class-wcdp-hooks.php';

            //Adapt products to be donable
            include_once WCDP_DIR . 'includes/class-wcdp-product-settings.php';

            //WooCommerce settings tab
            include_once WCDP_DIR . 'includes/class-wcdp-general-settings.php';

            //Fundraising Progress
            include_once WCDP_DIR . 'includes/class-wcdp-progress.php';

            //Fee Recovery
            include_once WCDP_DIR . 'includes/class-wcdp-fee-recovery.php';

            //Integration with other Extensions
            include_once WCDP_DIR . 'includes/integrations/class-wcdp-integrator.php';

            //Deactivation survey & Feedback survey
            include_once WCDP_DIR . 'includes/class-wcdp_feedback.php';

            //Leaderboard
            include_once WCDP_DIR . 'includes/class-wcdp_leaderboard.php';
        }

        /**
         * Called when WooCommerce is inactive or running an unsupported version to display an inactive notice
         *
         * @since 1.0
         */
        public static function wcdp_inactive_notice()
        {
            if (current_user_can('activate_plugins')) {

                $admin_notice_content = '';

                if (!is_woocommerce_active()) {
                    $admin_notice_content = __('Donation Platform for WooCommerce requires WooCommerce to be installed & activated.', 'wc-donation-platform');
                } elseif (version_compare(get_option('woocommerce_db_version'), self::$wc_minimum_supported_version, '<')) {
                    // translators: %s required WC version
                    $admin_notice_content = sprintf(__('Donation Platform for WooCommerce is inactive. This version of Donation Platform for WooCommerce requires WooCommerce %s or newer. Please update WooCommerce and run all database migrations.', 'wc-donation-platform'), self::$wc_minimum_supported_version);
                }

                if ($admin_notice_content) {
                    printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($admin_notice_content));
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
                'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=wc-donation-platform') . '" aria-label="' . esc_attr__('View settings', 'wc-donation-platform') . '">' . esc_html__('Settings', 'wc-donation-platform') . '</a>',
            );

            return array_merge($plugin_links, $links);
        }

        /**
         * Show row meta on the plugin screen.
         *
         * @param mixed $links Plugin Row Meta.
         * @param mixed $file  Plugin Base file.
         *
         * @return array
         */
        public static function plugin_row_meta($links, $file): array
        {
            if (strpos($file, basename(__FILE__))) {
                $row_meta = array(
                    'docs' => '<a href="' . esc_url('https://www.wc-donation.com/documentation/') . '" aria-label="' . esc_attr__('View Documentation of Donation Platform for WooCommerce', 'wc-donation-platform') . '">' . esc_html__('Documentation', 'wc-donation-platform') . '</a>',
                );

                return array_merge($links, $row_meta);
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
if (!is_woocommerce_active() || version_compare(get_option('woocommerce_db_version'), WCDP::$wc_minimum_supported_version, '<')) {
    add_action('admin_notices', 'WCDP::wcdp_inactive_notice');
    return;
} else {
    add_action('plugins_loaded', function () {
        new WCDP();
    });
}

/**
 * declare
 * - compatibility with High performance order storage
 * - incompatibility with new WooCommerce Checkout Block
 * - incompatibility with new WooCommerce product editor
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, false);
    }
});

/**
 * Plugin activation hook to automatically enable compatibility mode for sites that already have a proper WooCommerce shop
 *
 * @since 1.3.3
 */
register_activation_hook(
    __FILE__,
    /**
     * @throws Exception
     */ function () {
        //Disable new Product editor
        update_option('woocommerce_feature_product_block_editor_enabled', 'no');

        if (!class_exists('WC_Admin_Notices') || !current_user_can('activate_plugins') || get_option('wcdp_compatibility_mode', false))
            return;

        // Check if there are at least 4 WooCommerce orders
        $order_query = new WC_Order_Query(array(
            'status' => array('wc-completed'),
            'type' => 'shop_order',
            'limit' => 4,
            'return' => 'ids',
        ));
        $orders = $order_query->get_orders();
        if (count($orders) < 4)
            return;

        //enable compatibility mode
        add_option('wcdp_compatibility_mode', 'yes');
    }
);

if (!function_exists('wcdp_clear_cache')) {
    /**
     * Clear Donation Platform for WooCommerce Cache
     *
     * @return void
     * @since v1.3.3
     */
    function wcdp_clear_cache()
    {
        if (class_exists('WCDP_General_Settings')) {
            WCDP_General_Settings::clear_cached_data();
        } else {
            add_action('init', function () {
                WCDP_General_Settings::clear_cached_data();
            });
        }
    }
}