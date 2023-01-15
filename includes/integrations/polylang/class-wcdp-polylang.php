<?php
/**
 * Class WCDP_Polylang
 *
 * Adjustments for Polylang
 */

if(!defined('ABSPATH')) exit;

class WCDP_Polylang
{
	/**
	 * Sum up the total for translated products
	 * @param $revenue
	 * @param $productid
	 * @return float|int
	 */
	public static function product_revenue($revenue, $productid) {
		if (function_exists('pll_get_post_translations')) {
			global $wpdb;
			$translationids = pll_get_post_translations($productid);
			$placeholder = '';
			foreach($translationids as $ignored) {
				$placeholder .= ', %s';
			}
			$placeholder = substr($placeholder, 2);
			$query = "
					SELECT
						SUM(ltoim.meta_value) as revenue
					FROM
						{$wpdb->prefix}woocommerce_order_itemmeta wcoim
					LEFT JOIN
						{$wpdb->prefix}woocommerce_order_items oi ON wcoim.order_item_id = oi.order_item_id
					LEFT JOIN
						{$wpdb->prefix}posts wpposts ON order_id = wpposts.ID
					LEFT JOIN
						{$wpdb->prefix}woocommerce_order_itemmeta ltoim ON ltoim.order_item_id = oi.order_item_id AND ltoim.meta_key = '_line_total'
					WHERE
						wcoim.meta_key = '_product_id' AND wcoim.meta_value in ($placeholder) AND wpposts.post_status = 'wc-completed';";

			$result = $wpdb->get_row($wpdb->prepare( $query, $translationids ), ARRAY_A);
			if (!is_null($result) && isset($result['revenue'])) {
				$revenue = (float) $result['revenue'];
			} else {
				$revenue = 0;
			}
			foreach($translationids as $id) {
				if ($id != $productid) {
					update_post_meta($id, 'wcdp_total_revenue', array('revenue' => $revenue, 'time' => time()));
				}
			}
		}
		return $revenue;
	}
}
