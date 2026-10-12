<?php
/**
 * Class WCDP_Subscriptions
 *
 * Fits WooCommerce Subscriptions to use for recurring donations
 */

if (!defined('ABSPATH'))
    exit;

class WCDP_Subscriptions
{
    /**
     * Bootstraps the class and hooks required actions & filters
     */
    public static function init()
    {
        //make one-time donation not a subscription
        add_filter('woocommerce_is_subscription', 'WCDP_Subscriptions::is_subscription', 10, 3);

        //Filter specific WC Subscription templates to WCDP templates
        add_filter('wc_get_template', 'WCDP_Subscriptions::modify_template', 11, 5);

        if (get_option('wcdp_compatibility_mode', 'no') === 'no') {
            //Rename Subscriptions Tab on My Account page
            add_filter('woocommerce_account_menu_items', 'WCDP_Subscriptions::rename_menu_item', 11, 1);

            //Remove Subscription Info message on checkout page
            add_filter('woocommerce_add_message', 'WCDP_Subscriptions::add_message');

            //Remove Subscription Info message on checkout page
            add_filter('woocommerce_subscriptions_thank_you_message', 'WCDP_Subscriptions::thank_you_message', 10, 2);
        }

        //TODO Find Better solution for Edit Recurring Donation
        //Remove feature to frontend subscription switching
        add_filter('woocommerce_subscriptions_can_item_be_switched_by_user', 'WCDP_Subscriptions::can_item_be_switched_by_user', 10, 3);
    }

    /**
     * For one-time donations whose product is created as a subscription, "for 1 month/day etc." is displayed by default
     * This function hides this note
     *
     * @param string $subscription_string
     * @return array|string|string[]
     */
    public static function product_price_string(string $subscription_string = '')
    {
        if (strpos($subscription_string, ' 1 ')) {
            return substr_replace($subscription_string, ' style="display:none"', strpos($subscription_string, 'class="subscription-details"'), 0);
        }
        return $subscription_string;
    }

    /**
     * Return true if a product or its parent is marked as a donation product.
     *
     * @param int $product_id
     * @param mixed $product
     * @return bool
     */
    private static function is_donation_product(int $product_id, $product = null): bool
    {
        if (WCDP_Form::is_donable($product_id)) {
            return true;
        }

        if ($product instanceof WC_Product && WCDP_Form::is_donable($product->get_id())) {
            return true;
        }

        if ($product instanceof WC_Product && $product->get_parent_id()) {
            return WCDP_Form::is_donable($product->get_parent_id());
        }

        return false;
    }

    /**
     * Make one-time subscription product not a subscription
     * No not apply on admin pages (Otherwise, errors may occur when editing variable products)
     * @param $is_subscription
     * @param $product_id
     * @param $product
     * @return bool
     */
    public static function is_subscription($is_subscription, $product_id, $product): bool
    {
        if (
            $is_subscription &&
            $product instanceof WC_Product &&
            $product->get_meta('_subscription_length', true) == 1 &&
            self::is_donation_product((int) $product_id, $product) &&
            !is_admin()
        ) {
            return false;
        }
        return $is_subscription;
    }

    /**
     * Disable frontend switching for recurring donations.
     *
     * @param bool $can_switch
     * @param mixed $item
     * @param mixed $subscription
     * @return bool
     */
    public static function can_item_be_switched_by_user($can_switch, $item, $subscription): bool
    {
        if (self::order_contains_only_donations($subscription)) {
            return false;
        }

        return (bool) $can_switch;
    }

    /**
     * Return true if an order-like object contains only donation products.
     *
     * @param mixed $order
     * @return bool
     */
    private static function order_contains_only_donations($order): bool
    {
        return $order instanceof WC_Order && WCDP_Form::order_contains_only_donations($order);
    }

    /**
     * Return true if any subscription in a list is a donation subscription.
     *
     * @param array $subscriptions
     * @return bool
     */
    private static function subscriptions_contain_donation(array $subscriptions): bool
    {
        foreach ($subscriptions as $subscription) {
            if (self::order_contains_only_donations($subscription)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return true if all subscriptions in a non-empty list are donation subscriptions.
     *
     * @param array $subscriptions
     * @return bool
     */
    private static function subscriptions_contain_only_donations(array $subscriptions): bool
    {
        if (empty($subscriptions)) {
            return false;
        }

        foreach ($subscriptions as $subscription) {
            if (!self::order_contains_only_donations($subscription)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve donation context from the args passed to a subscription template.
     *
     * @param array $args
     * @return bool
     */
    private static function template_args_contain_only_donations(array $args): bool
    {
        if (isset($args['order']) && self::order_contains_only_donations($args['order'])) {
            return true;
        }

        if (isset($args['order_id'])) {
            $order = wc_get_order($args['order_id']);

            if (self::order_contains_only_donations($order)) {
                return true;
            }
        }

        if (isset($args['subscription']) && self::order_contains_only_donations($args['subscription'])) {
            return true;
        }

        if (isset($args['subscriptions']) && is_array($args['subscriptions'])) {
            return self::subscriptions_contain_only_donations($args['subscriptions']);
        }

        return false;
    }

    /**
     * Filter specific WC Subscription templates to WCDP templates
     *
     * @param string $template
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public static function modify_template($template = '', $template_name = '', $args = array(), $template_path = '', $default_path = ''): string
    {
        //Only apply for WC Subscription Templates
        if (strpos($default_path, 'subscriptions') === false) {
            return $template;
        }

        $path = WCDP_DIR . 'includes/integrations/woocommerce-subscriptions/templates/';
        global $product;
        $donable = self::is_donation_product((int) get_queried_object_id(), $product);

        switch ($template_name) {
            case 'checkout/form-change-payment-method.php':
            case 'checkout/subscription-receipt.php':
                if (
                    get_option('wcdp_compatibility_mode', 'no') === 'no' &&
                    self::template_args_contain_only_donations($args)
                ) {
                    $template = WCDP_Hooks::resolve_template_precedence($template, $path . $template_name, $template_name, 'woocommerce-subscriptions');
                }
                break;

            case 'checkout/recurring-subtotals.php':
                if (
                    get_option('wcdp_compatibility_mode', 'no') === 'no' &&
                    isset($args['recurring_carts']) &&
                    !empty($args['recurring_carts']) &&
                    WC()->cart &&
                    !empty(WC()->cart->get_cart_contents()) &&
                    WCDP_Form::cart_contains_only_donations()
                ) {
                    $template = WCDP_Hooks::resolve_template_precedence($template, $path . $template_name, $template_name, 'woocommerce-subscriptions');
                }
                break;

            case 'myaccount/my-subscriptions.php':
                if (
                    get_option('wcdp_compatibility_mode', 'no') === 'no' &&
                    isset($args['subscriptions']) &&
                    is_array($args['subscriptions']) &&
                    self::subscriptions_contain_donation($args['subscriptions'])
                ) {
                    $template = WCDP_Hooks::resolve_template_precedence($template, $path . $template_name, $template_name, 'woocommerce-subscriptions');
                }
                break;

            case 'myaccount/related-orders.php':
            case 'myaccount/related-subscriptions.php':
            case 'myaccount/subscription-details.php':
            case 'myaccount/subscription-totals.php':
            case 'myaccount/subscription-totals-table.php':

            case 'emails/customer-processing-renewal-order.php':
            case 'emails/customer-renewal-invoice.php':
            case 'emails/email-order-details.php':
            case 'emails/cancelled-subscription.php':
            case 'emails/expired-subscription.php':
            case 'emails/customer-completed-renewal-order.php':
            case 'emails/on-hold-subscription.php':
            case 'emails/customer-completed-switch-order.php':
            case 'emails/customer-on-hold-renewal-order.php':
            case 'emails/subscription-info.php':
            case 'emails/customer-payment-retry.php':

            case 'emails/plain/customer-processing-renewal-order.php':
            case 'emails/plain/customer-renewal-invoice.php':
            case 'emails/plain/email-order-details.php':
            case 'emails/plain/cancelled-subscription.php':
            case 'emails/plain/expired-subscription.php':
            case 'emails/plain/customer-completed-renewal-order.php':
            case 'emails/plain/on-hold-subscription.php':
            case 'emails/plain/customer-completed-switch-order.php':
            case 'emails/plain/customer-on-hold-renewal-order.php':
            case 'emails/plain/subscription-info.php':
            case 'emails/plain/customer-payment-retry.php':
                if (
                    get_option('wcdp_compatibility_mode', 'no') === 'no' &&
                    self::template_args_contain_only_donations($args)
                ) {
                    $template = WCDP_Hooks::resolve_template_precedence($template, $path . $template_name, $template_name, 'woocommerce-subscriptions');
                }
                break;

            case 'single-product/add-to-cart/subscription.php':
            case 'single-product/add-to-cart/variable-subscription.php':
                if ($donable) {
                    $template = WCDP_Hooks::resolve_template_precedence($template, WCDP_DIR . 'includes/wc-templates/single-product/add-to-cart/product.php', $template_name, 'woocommerce', 'single-product/add-to-cart/product.php');
                }
                break;

            default:
                break;
        }
        return apply_filters('wcdp_get_template', $template, $template_name, $args, $template_path, $default_path);
    }

    /**
     * Rename Menu item on Account page
     *
     * @param $menu_items
     * @return mixed
     */
    public static function rename_menu_item($menu_items)
    {
        if (array_key_exists('subscriptions', $menu_items)) {
            $subscriptions = function_exists('wcs_get_users_subscriptions') ? wcs_get_users_subscriptions() : array();

            if (empty($subscriptions) || self::subscriptions_contain_only_donations($subscriptions)) {
                $menu_items['subscriptions'] = __('Recurring Donations', 'wc-donation-platform');
            }
        }
        return $menu_items;
    }

    /**
     * Rename notice on renew order checkout page
     *
     * @param $message
     * @return mixed|string|void
     */
    public static function add_message($message)
    {
        switch ($message) {
            case __('Complete checkout to renew your subscription.', 'woocommerce-subscriptions'):
                if (WCDP_Form::is_donation_checkout_context()) {
                    return __('Complete checkout to renew your recurring donation.', 'wc-donation-platform');
                }

                return $message;
            default:
                return $message;
        }
    }

    /**
     * Hide the WooCommerce Subscriptions thank you message for recurring donations.
     *
     * @param string $message
     * @param int $order_id
     * @return string
     */
    public static function thank_you_message(string $message, int $order_id = 0): string
    {
        $order = wc_get_order($order_id);

        if (self::order_contains_only_donations($order)) {
            return '';
        }

        return $message;
    }
}
