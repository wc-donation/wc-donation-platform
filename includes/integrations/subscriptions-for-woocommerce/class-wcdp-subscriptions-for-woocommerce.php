<?php
if(!defined('ABSPATH')) exit;

/**
 * This class integrates Subscriptions for WooCommerce (Lite) with Donation Platform for WooCommerce
 * https://wordpress.org/plugins/subscriptions-for-woocommerce/
 */
class WCDP_Subscriptions_For_WooCommerce
{
	/**
	 * Bootstraps the class and hooks required actions & filters
	 */
	public static function init()
	{
		//set recurring total for regular donations
		add_filter('wps_sfw_cart_data_for_susbcription', 'WCDP_Subscriptions_For_WooCommerce::price_subscription', 10, 2);

		//Filter specific Subscriptions for WooCommerce to WCDP templates
		add_filter( 'wc_get_template', 'WCDP_Subscriptions_For_WooCommerce::modify_template', 10, 5 );

		//Rename Subscriptions Tab on My Account page
		add_filter( 'woocommerce_account_menu_items', 'WCDP_Subscriptions_For_WooCommerce::rename_menu_item', 11, 1 );

        add_filter( 'wps_sfw_check_pro_plugin', '__return_true' );
	}

	/**
	 * Update donation amount of recurring donation
	 * @param $mwb_recurring_data
	 * @param $cart_item
	 * @return mixed
	 */
	public static function price_subscription($mwb_recurring_data, $cart_item) {
		$min_donation_amount = get_option('wcdp_min_amount', 3);
		$max_donation_amount = get_option('wcdp_max_amount', 50000);

		if( isset( $cart_item["wcdp_donation_amount"] ) &&
			$cart_item["wcdp_donation_amount"] >= $min_donation_amount &&
			$cart_item["wcdp_donation_amount"] <= $max_donation_amount
		) {
			$mwb_recurring_data['wps_recurring_total'] = $cart_item["wcdp_donation_amount"];
		}
		return $mwb_recurring_data;
	}

	/**
	 * overwrite specific Subscriptions for WooCommerce templates to WCDP templates
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $args
	 * @param string $template_path
	 * @param string $default_path
	 * @return string
	 */
	public static function modify_template(string $template, string $template_name, $args, string $template_path, string $default_path ): string
	{
		//Only apply for Subscriptions for WooCommerce Templates
		if (! strpos($default_path, 'subscriptions-for-woocommerce')) {
			return $template;
		}
		//Return if the template has been overwritten in yourtheme/woocommerce/XXX
		//Checks if it's woocommerce/ or templates/ as before $template_name
		if ($template[strlen($template) - strlen($template_name) - 2] === 'e') {
			return $template;
		}
		$path = WCDP_DIR . 'includes/integrations/subscriptions-for-woocommerce/templates/';

		if ($template_name == 'myaccount/mwb-show-subscription-details.php') {
            $template = $path . $template_name;
		}
		return apply_filters( 'wcdp_get_template', $template, $template_name, $args, $template_path, $default_path );
	}

	/**
	 * Rename Menu item on Account page
	 *
	 * @param $menu_items
	 * @return mixed
	 */
	public static function rename_menu_item($menu_items) {
		if (array_key_exists('mwb_subscriptions', $menu_items)) {
			$menu_items['mwb_subscriptions'] = __( 'Recurring Donations', 'wc-donation-platform' );
		}
		return $menu_items;
	}
}
