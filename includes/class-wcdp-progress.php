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
		add_action( 'woocommerce_order_status_changed', array($this, 'update_total_revenue'), 10, 4);
	}

	/**
	 * Update product revenue after order status changed
	 * @param $orderid
	 * @param $from
	 * @param $to
	 * @param $order
	 * @return void
	 */
	public function update_total_revenue($orderid, $from, $to, $order) {
        if ($from == 'completed' || $to == 'completed') {
            foreach ( $order->get_items() as $item ) {
                $revenue = get_post_meta( $item->get_product_id(), 'wcdp_total_revenue' );
                //Recalculate the Revenue only if it has not been calculated recently (Avoid performance problems during peak loads)
                if (!$revenue || time() - $revenue[0]['time'] > 30) {
                    $this->updateTotalRevenueOfProduct($item->get_product_id());
                }
            }
        }
	}

	/**
	 * Display a fundraising progress bar
	 * @param array $atts
	 * @return false|string
     */
	public function wcdp_progress(array $atts = array()) {
		if (!isset($atts['id'])) {
			return esc_html__('wcdp_progress: Required attribute "id" missing.', 'wc-donation-platform');
		}
		$goal_db = get_post_meta( $atts['id'], 'wcdp-settings[wcdp_fundraising_goal]', true );
		$end_date_db = get_post_meta( $atts['id'], 'wcdp-settings[wcdp_fundraising_end_date]', true );

		$atts = shortcode_atts( array(
			'id'		=> -1,
			'goal'		=> $goal_db,
			'style'		=> 1,
            'addids'    => '',
            'cheat'     => 0,
		), $atts );

		if (!is_numeric($atts['goal'])) {
			$atts['goal'] = 100;
		}

		$revenue = (float) $this->getTotalRevenueOfProduct($atts['id']);

        //Add revenue of additional Product IDs
        $ids = explode(",", $atts['addids']);
        foreach ($ids as $id) {
            $revenue += (float) $this->getTotalRevenueOfProduct($id);
        }

        //Add specified amount to revenue
        $revenue += (float) $atts['cheat'];

        $revenue = apply_filters('wcdp_progress_revenue', $revenue, $atts);

		if ((float) $atts['goal'] != 0) {
			$width = ($revenue*100) / (float) $atts['goal'];
		} else {
			$width = 100;
		}

		if ($width > 100) {
			$width = 100;
		}

		//Translators: %1$s: donation amount raised, %2$s: fundraising goal
		$label = esc_html__('%1$s of %2$s', 'wc-donation-platform');

		$template = '';

		switch ($atts['style']) {
			case 2:
				$template = 'wcdp_progress_style_2.php';
				break;
			case 3:
				$template = 'wcdp_progress_style_3.php';
				break;
			default:
				$template = 'wcdp_progress_style_1.php';
		}

		ob_start(); ?>
		<style>
		<?php if (!defined('WCDP_PROGRESS_3') && !defined('WCDP_PROGRESS_2') && !defined('WCDP_PROGRESS_1')) : ?>
				:root {
					--wcdp-main: <?php echo sanitize_hex_color(get_option('wcdp_secondary_color', '#30bf76')); ?>;
					--wcdp-main-2: <?php echo sanitize_hex_color(get_option('wcdp_main_color', '#00753a')); ?>;
					--label-text-checked: white;
				}
				@keyframes wcdp-progress {
					0% {
						width: 0;
					}
				}
		<?php endif;

		include(WCDP_DIR . 'includes/templates/styles/progress/' . $template);
		$r = ob_get_contents();
		ob_end_clean();
		return $r;
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
		//Calculate revenue if not set or calculated revenue older than 21600 seconds
		if (!$totalrevenue || !isset($totalrevenue[0]) || time() - $totalrevenue[0]['time'] > 21600) {
            return $this->updateTotalRevenueOfProduct($productid);
		}

		return (float) $totalrevenue[0]['revenue'];
	}

    /**
     * Calculate and update the total revenue of a product
     * @param $productid
     * @return float
     */
	private function updateTotalRevenueOfProduct($productid) {
		global $wpdb;
		$query = "SELECT
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
			$revenue = $result['revenue'];
		} else {
			$revenue = 0;
		}
		$revenue = (float) apply_filters('wcdp_update_product_revenue', $revenue, $productid);
		update_post_meta( $productid, 'wcdp_total_revenue', array('revenue' => $revenue, 'time' => time()));
        return $revenue;
	}

	private function get_human_time_diff( $timestamp ) {
		$time_diff = strtotime( $timestamp ) - strtotime( 'now' );

		$human_diff = '<span class="wcdp-emphasized">' . human_time_diff( strtotime($timestamp) ) . '</span>';
		if ( $time_diff > 0) {
			// translators: placeholder is human time diff (e.g. "3 weeks")
			$date_to_display = sprintf( __( '%s to go', 'wc-donation-platform' ), $human_diff );
		} else {
			// translators: placeholder is human time diff (e.g. "3 weeks")
			$date_to_display = sprintf( __( 'ended %s ago', 'wc-donation-platform' ), $human_diff );
		}

		return $date_to_display;
	}
}
