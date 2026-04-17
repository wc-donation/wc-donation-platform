<?php
/**
 * Class WCDP_Polylang
 *
 * Adjustments for Polylang
 */

if (!defined('ABSPATH'))
    exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WCDP_Polylang
{
    /**
     * Sum up the total for translated products
     * @param $revenue
     * @param $productid
     * @return float|int
     */
    public static function product_revenue($revenue, $productid)
    {
        if (function_exists('pll_get_post_translations')) {
            global $wpdb;
            $translationids = array_filter(array_map(static function ($id) {
                return max(0, intval($id));
            }, array_values((array) pll_get_post_translations($productid))));

            if (empty($translationids)) {
                $translationids = array(max(0, intval($productid)));
            }

            $placeholder = implode(', ', array_fill(0, count($translationids), '%d'));

            if (class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()) {
                $query = "SELECT
                            SUM(l.product_net_revenue) AS revenue
                          FROM
                            {$wpdb->prefix}wc_orders o
                            INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON o.id = l.order_id
                          WHERE
                                o.status = 'wc-completed'
                            AND o.type = 'shop_order'
                            AND l.product_id IN ({$placeholder});";
            } else {
                $query = "SELECT
                            SUM(l.product_net_revenue) AS revenue
                          FROM
                            {$wpdb->prefix}posts p
                            INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON p.ID = l.order_id
                          WHERE
                                p.post_type = 'shop_order'
                            AND p.post_status = 'wc-completed'
                            AND l.product_id IN ({$placeholder});";
            }

            $result = $wpdb->get_row($wpdb->prepare($query, $translationids), ARRAY_A);
            if (!is_null($result) && isset($result['revenue'])) {
                $revenue = (float) $result['revenue'];
            } else {
                $revenue = 0;
            }
            foreach ($translationids as $id) {
                if ($id != $productid) {
                    update_post_meta($id, 'wcdp_total_revenue', array('revenue' => $revenue, 'time' => time()));
                }
            }
        }
        return $revenue;
    }
}
