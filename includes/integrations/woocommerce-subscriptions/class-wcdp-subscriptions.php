<?php
/**
 * Class WCDP_Subscriptions
 *
 * Fits WooCommerce Subscriptions to use for recurring donations
 */

if(!defined('ABSPATH')) exit;

class WCDP_Subscriptions
{
    /**
     * Bootstraps the class and hooks required actions & filters
     */
    public static function init() {
 		//add_filter('woocommerce_subscriptions_product_price_string', 'WCDP_Subscriptions::product_price_string', 10, 1);
		add_filter('woocommerce_is_subscription', 'WCDP_Subscriptions::is_subscription', 10, 3);

		//Filter specific WC Subscription templates to WCDP templates
		add_filter( 'wc_get_template', 'WCDP_Subscriptions::modify_template', 10, 5 );

		//Rename Subscriptions Tab on My Account page
		add_filter( 'woocommerce_account_menu_items', 'WCDP_Subscriptions::rename_menu_item', 11, 1 );

		//Remove Subscription Info message on checkout page
		add_filter( 'woocommerce_subscriptions_thank_you_message', '__return_empty_string' );

		//Remove Subscription Info message on checkout page
		add_filter( 'woocommerce_add_message', 'WCDP_Subscriptions::add_message' );

		//TODO Find Better solution for Edit Recurring Donation
		//Remove feature to frontend subscription switching
		add_filter( 'woocommerce_subscriptions_can_item_be_switched_by_user', '__return_false' );
    }

    /**
     * For one-time donations whose product is created as a subscription, "for 1 month/day etc." is displayed by default
     * This function hides this note
     *
     * @param string $subscription_string
     * @return array|string|string[]
     */
    public static function product_price_string(string $subscription_string = '' ) {
        if ( strpos($subscription_string, ' 1 ') ) {
            return substr_replace($subscription_string, ' style="display:none"', strpos($subscription_string, 'class="subscription-details"'), 0);
        }
        return $subscription_string;
    }

    /**
     * Make one-time subscription product not a subscription
     * No not apply on admin pages (Otherwise, errors may occur when editing variable products)
     * @param $is_subscription
     * @param $product_id
     * @param $product
     * @return bool
     */
    public static function is_subscription($is_subscription, $product_id, $product) {
        if ($is_subscription && $product->get_meta( '_subscription_length', true ) == 1 && !is_admin()) {
            return false;
        }
        return $is_subscription;
    }

    /**
     * Filter specific WC Subscription templates to WCDP templates
     *
     * @param string $template
     * @param string $template_name
     * @param string $args
     * @param string $template_path
     * @param string $default_path
     * @return mixed|void
     */
    public static function modify_template(string $template, string $template_name, $args, string $template_path, string $default_path ): string
    {
        //Only apply for WC Subscription Templates
        if (! strpos($default_path, 'subscriptions')) {
            return $template;
        }

        //Return if the template has been overwritten in yourtheme/woocommerce/XXX
        //Checks if it's woocommerce/ or templates/ as before $template_name
        if ($template[strlen($template) - strlen($template_name) - 2] === 'e') {
            return $template;
        }

        $path = WCDP_DIR . 'includes/integrations/woocommerce-subscriptions/templates/';

        switch ($template_name) {
            case 'myaccount/my-subscriptions.php':
            case 'myaccount/related-orders.php':
            case 'myaccount/related-subscriptions.php':
            case 'myaccount/subscription-details.php':
            case 'myaccount/subscription-totals.php':
            case 'myaccount/subscription-totals-table.php':

            case 'checkout/form-change-payment-method.php':
            case 'checkout/subscription-receipt.php':

            case 'emails/admin-new-renewal-order.php':
            case 'emails/customer-processing-renewal-order.php':
            case 'emails/admin-new-switch-order.php':
            case 'emails/customer-renewal-invoice.php':
            case 'emails/admin-payment-retry.php':
            case 'emails/email-order-details.php':
            case 'emails/cancelled-subscription.php':
            case 'emails/expired-subscription.php':
            case 'emails/customer-completed-renewal-order.php':
            case 'emails/on-hold-subscription.php':
            case 'emails/customer-completed-switch-order.php':
            case 'emails/customer-on-hold-renewal-order.php':
            case 'emails/subscription-info.php':
            case 'emails/customer-payment-retry.php':

            case 'emails/plain/admin-new-renewal-order.php':
            case 'emails/plain/customer-processing-renewal-order.php':
            case 'emails/plain/admin-new-switch-order.php':
            case 'emails/plain/customer-renewal-invoice.php':
            case 'emails/plain/admin-payment-retry.php':
            case 'emails/plain/email-order-details.php':
            case 'emails/plain/cancelled-subscription.php':
            case 'emails/plain/expired-subscription.php':
            case 'emails/plain/customer-completed-renewal-order.php':
            case 'emails/plain/on-hold-subscription.php':
            case 'emails/plain/customer-completed-switch-order.php':
            case 'emails/plain/customer-on-hold-renewal-order.php':
            case 'emails/plain/subscription-info.php':
            case 'emails/plain/customer-payment-retry.php':
                $template = $path . $template_name;
                break;

            case 'single-product/add-to-cart/subscription.php' :
            case 'single-product/add-to-cart/variable-subscription.php' :
				if(WCDP_Form::is_donable(get_queried_object_id())) {
					$template = WCDP_DIR . 'includes/wc-templates/single-product/add-to-cart/product.php';
				}
				break;

			default:
                break;
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
        if (array_key_exists('subscriptions', $menu_items)) {
            $menu_items['subscriptions'] = __( 'Recurring Donations', 'wc-donation-platform' );
        }
        return $menu_items;
    }

    /**
     * Rename notice on renew order checkout page
     *
     * @param $message
     * @return mixed|string|void
     */
    public static function add_message($message) {
        switch($message){
            case __( 'Complete checkout to renew your subscription.', 'woocommerce-subscriptions' ):
                return __( 'Complete checkout to renew your recurring donation.', 'wc-donation-platform' );
            default:
                return $message;
        }
    }
}
