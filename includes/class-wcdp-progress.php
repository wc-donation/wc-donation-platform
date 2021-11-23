<?php
/**
 * This class illustrates the progress of a fundraising campaign
 */

if(!defined('ABSPATH')) exit;

class WCDP_Progress
{
	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public function __construct() {
		//progress shortcode
		add_shortcode( 'wcdp_progress', array($this, 'wcdp_progress'));

		//update donation revenue
		add_action( 'woocommerce_order_status_changed', array($this, 'updateTotalRevenue'), 10, 4);
	}

	/**
	 * Update product revenue after order status changed
	 * @param $orderid
	 * @param $from
	 * @param $to
	 * @param $order
	 * @return void
	 */
	public function updateTotalRevenue($orderid, $from, $to, $order) {
		foreach ( $order->get_items() as $item ) {
			$revenue = get_post_meta( $item->get_product_id(), 'wcdp_total_revenue' );
			//Recalculate the Revenue only if it has not been calculated recently (Avoid performance problems during peak loads)
			if (!$revenue || time() - $revenue[0]['time'] > 15) {
				$this->updateTotalRevenueOfProduct($item->get_product_id());
			}
		}
	}

	/**
	 * Display a fundraising progress bar
	 * @param string $atts
	 * @return string|void
	 */
	public function wcdp_progress($atts = '') {
		if (!isset($atts['id'])) {
			return esc_html_e('wcdp_progress: Required attribute "id" missing.', 'wc-donation-platform');
		}
		$atts = shortcode_atts( array(
			'id'		=> -1,
			'goal'		=> 100,
		), $atts );
		if (!is_numeric($atts['goal'])) {
			$atts['goal'] = 100;
		}

		$revenue = (float) $this->getTotalRevenueOfProduct($atts['id']);
		$width = ($revenue*100) / (float) $atts['goal'];
		if ($width > 100) {
			$width = 100;
		}

		//Translators: %1$s: donation amount raised, %2$s: fundraising goal
		$label = esc_html__('%1$s of %2$s', 'wc-donation-amount');
		$label = sprintf($label, wc_price($revenue), wc_price($atts['goal']));

		return '
<style>
	:root{
		--wcdp-main: ' . sanitize_hex_color(get_option('wcdp_secondary_color', '#30bf76')) . ';
		--wcdp-main-2: '. sanitize_hex_color(get_option('wcdp_main_color', '#00753a')) . ';
		--label-text-checked: white;
	}
	.wcdp-thermometer {
		height: 2em;
		border-radius: 0.5em;
	}
	.wcdp-thermometer-bg {
		background-color: var(--wcdp-main);
		margin: 0;
		height: 2em;
	}
	.wcdp-progress > .wcdp-thermometer-fg {
		background-color: var(--wcdp-main-2);
		margin-top: -2em;
		animation: progress 2s ease-in;
	}
	@keyframes progress {
		0% {
			width: 0%;
		}
	}
	.wcdp-thermometer > .wcdp-label, .wcdp-thermometer > .wcdp-label .woocommerce-Price {
		white-space: nowrap;
		color: var(--label-text-checked);
		text-align: right;
		padding: 0 1ch 0 1ch;
		font-size: 1em;
		line-height: 2em;
	}
</style>
<div class="wcdp-progress"><div class="wcdp-thermometer wcdp-thermometer-bg"></div>
<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: ' . $width . '%">
	<div class="wcdp-label">' . $label . '</div>
</div>';
	}

	/**
	 * Return the Revenue of a Product (sum of all completed orders)
	 * @param $productid
	 * @return float|int
	 */
	private function getTotalRevenueOfProduct($productid) {
		$totalrevenue = get_post_meta( $productid, 'wcdp_total_revenue' );
		if ($totalrevenue === false) {
			return 0;
		}
		//Calculate revenue if not set of revenue older than 21600 seconds
		if (!$totalrevenue|| time() - $totalrevenue[0]['time'] > 21600) {
			$this->updateTotalRevenueOfProduct($productid);
			$totalrevenue = get_post_meta( $productid, 'wcdp_total_revenue' );
		}

		return (float) $totalrevenue[0]['revenue'];
	}

	/**
	 * Calculate and update the total revenue of a product
	 * @param $productid
	 */
	private function updateTotalRevenueOfProduct($productid) {
		global $wpdb;
		$query ="SELECT
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
                        wcoim.meta_key = '_product_id' AND wcoim.meta_value = %d AND wpposts.post_status = 'wc-completed';";

		$result = $wpdb->get_row($wpdb->prepare( $query, $productid ), ARRAY_A);

		if (!is_null($result) && isset($result['revenue'])) {
			update_post_meta( $productid, 'wcdp_total_revenue', array('revenue' => (float) $result['revenue'], 'time' => time()));
		}
	}
}
