<?php
/**
 * This class displays a leaderboard of your donations
 *
 * * @since 1.3.0
 */

if(!defined('ABSPATH')) exit;

class WCDP_Leaderboard
{
    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public function __construct() {
        //Leaderboard shortcode
        add_shortcode( 'wcdp_leaderboard', array($this, 'wcdp_leaderboard'));

        //Delete cache on order change
        add_action('woocommerce_order_status_transition_completed', 'delete_old_latest_orders_cache', 10, 4);
    }

    /**
     * Leaderboard Shortcode
     * @param $atts
     * @return string
     */
    function wcdp_leaderboard($atts): string
    {
        // Extract attributes
        $atts = shortcode_atts(array(
            'limit' => 100,
            'ids' => ''
        ), $atts, 'latest_orders');

        $limit = intval($atts['limit']);
        $ids = sanitize_text_field($atts['ids']);
        $cache_key = 'wcdp_latest_orders_' . $limit . '_' . $ids;

        // Try to get the orders from cache
        $orders = get_transient($cache_key);
        $expiration = apply_filters("wcdp_leaderboard_cache_time", 6 * HOUR_IN_SECONDS);

        // If cache is empty, fetch the latest orders
        if (empty($orders)) {
            $args = array(
                'limit' => $limit,
                'status' => 'completed',
                'include' => explode(',', $ids),
            );
            $orders = wc_get_orders($args);

            set_transient($cache_key, $orders, $expiration);
        }

        $output = '<ul>';
        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $order_date = $order->get_date_created()->date('Y-m-d');
            $customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $city = $order->get_billing_city();
            $country = $order->get_billing_country();
            $amount = $order->get_total();

            $output .= '<li>';
            $output .= 'Order ID: ' . $order_id . '<br>';
            $output .= 'Order Date: ' . $order_date . '<br>';
            $output .= 'Customer Name: ' . $customer . '<br>';
            $output .= 'Customer City: ' . $city . '<br>';
            $output .= 'Customer Country: ' . $country . '<br>';
            $output .= 'Amount: ' . $amount . '<br>';
            $output .= '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    function delete_old_latest_orders_cache($order_id, $old_status, $new_status, $order): void
    {
        if ($old_status !== 'completed' && $new_status !==  'completed') return;

        $threshold = time() - 90;

        // Get all transients matching the pattern "latest_orders_*"
        $transients = get_option('alloptions');
        $delete_keys = array();
        foreach ($transients as $key => $value) {
            if (str_starts_with($key, 'wcdp_latest_orders_')) {
                // Extract the timestamp part of the cache key
                $timestamp = substr($key, strrpos($key, '_') + 1);

                // Delete the cache if the timestamp is older than the threshold
                if ($timestamp < $threshold) {
                    delete_transient($key);
                    $delete_keys[] = $key;
                }
            }
        }

        // Output the deleted cache keys (for debugging purposes)
        if (!empty($delete_keys)) {
            error_log('Deleted latest orders cache keys: ' . implode(', ', $delete_keys));
        }
    }
}
