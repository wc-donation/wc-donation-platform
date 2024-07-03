<?php
/**
 * This class displays a leaderboard of your donations
 *
 * * @since 1.3.0
 */

if (!defined('ABSPATH')) exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WCDP_Leaderboard
{
    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public function __construct()
    {
        //Leaderboard shortcode
        add_shortcode('wcdp_leaderboard', array($this, 'wcdp_leaderboard'));

        //Delete cache on order change
        add_action('woocommerce_order_status_changed', array($this, 'delete_old_latest_orders_cache'), 10, 4);

        if (get_option("wcdp_enable_checkout_checkbox", "no") === "yes") {
            // Add checkbox to WooCommerce checkout
            add_action('woocommerce_review_order_before_submit', array($this, 'add_anonymous_donation_checkbox'));

            //Save the value of the WooCommerce checkout checkbox
            add_action('woocommerce_checkout_create_order', array($this, 'save_anonymous_donation_checkbox'));

            //Display the value of the WooCommerce checkout checkbox to the user
            add_action('woocommerce_order_details_after_customer_details', array($this, 'display_anonymous_donation_checkbox_in_order_details'));
        }
    }

    /**
     * Clear the entire cache of the leaderboard
     * @return void
     */
    public static function delete_cached_leaderboard_total()
    {
        // Retrieve cache keys from the option.
        $cache_keys_option = get_option('wcdp_transient_cache_keys', array());

        // Loop through the cache keys and delete the corresponding transients.
        foreach ($cache_keys_option as $cache_key => $timestamp) {
            delete_transient($cache_key);
        }

        // Clear the cache keys option.
        delete_option('wcdp_transient_cache_keys');
    }

    /**
     * Leaderboard Shortcode
     * @param $atts
     * @return string
     */
    function wcdp_leaderboard($atts): string
    {
        // Do not allow executing this Shortcode via AJAX
        if (wp_doing_ajax()) return "";

        // Extract attributes
        $atts = shortcode_atts(array(
            'limit' => 10,
            'id' => '-1',
            'orderby' => 'date',
            "style" => 1,
            "split" => -1,
            "button" => __("Show more", "wc-donation-platform"),
        ), $atts, 'latest_orders');

        $atts['orderby'] = $atts['orderby'] === 'date' ? 'date' : 'total';

        if ($atts['id'] === 'current') {
            $atts['id'] = get_the_ID();
        }

        $limit = intval($atts['limit']);
        $id = (int)$atts['id'];

        // Get the latest orders
        $orders = $this->get_orders($id, $atts['orderby'], $limit);

        // Generate the HTML output
        return $this->generate_leaderboard($orders, (int)$atts['style'], (int)$atts['split'], $atts['button']);
    }

    /**
     * Return all the latest WooCommerce orders
     * @param int $product_id
     * @param string $orderby date or total
     * @param int $limit
     * @return array
     */
    private function get_orders(int $product_id, string $orderby, int $limit): array
    {
        $cache_key = 'wcdp_orders_' . $product_id . '_' . $orderby . '_' . $limit;
        $orders = json_decode(get_transient($cache_key), true);

        if (empty($orders)) {
            if ($product_id === -1) {
                $orders = $this->get_orders_db($orderby, $limit);
            } else {
                $orders = $this->get_orders_db_id($product_id, $orderby, $limit);
            }
            set_transient($cache_key, wp_json_encode($orders), apply_filters("wcdp_cache_expiration", 6 * HOUR_IN_SECONDS));

            // Save the cache key in a separate option for later deletion.
            $cache_keys_option = get_option('wcdp_transient_cache_keys', array());
            $cache_keys_option[$cache_key] = time();
            update_option('wcdp_transient_cache_keys', $cache_keys_option);
        }
        return $orders;
    }

    /**
     * Return orders that included at least one of the specified ids
     * @param $limit
     * @param $ids
     * @param string $orderby date or total
     * @return array
     * private function wcdp_get_orders($limit, $ids, string $orderby): array
     * {
     * $all_orders = $this->get_orders($orderby);
     * if ($ids === ['-1']) {
     * if ($limit === -1) return $all_orders;
     * return array_slice($all_orders, 0, $limit);
     * }
     *
     * $filtered_orders = array_filter($all_orders, function ($order) use ($ids) {
     * return !empty(array_intersect($order['ids'], $ids));
     * });
     * if ($limit === -1) return $filtered_orders;
     * return array_slice($filtered_orders, 0, $limit);
     * }
     */

    /**
     * get an array with all WooCommerce orders
     * @param string $orderby date or total
     * @param int $limit
     * @return array
     */
    private function get_orders_db(string $orderby, int $limit): array
    {
        $args = array(
            'limit' => $limit,
            'status' => 'completed',
            'order' => 'DESC',
            'type' => 'shop_order',
        );
        if ($orderby === 'date') {
            $args['orderby'] = 'date';
        } else {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_order_total';
        }

        $all_orders = wc_get_orders($args);

        $orders_clean = array();
        foreach ($all_orders as $order) {
            $meta = $order->get_meta('wcdp_checkout_checkbox');
            if ($meta === "yes") {
                $chk = 1;
            } else {
                $chk = 0;
            }

            $orders_clean[] = array(
                'date' => $order->get_date_created()->getTimestamp(),
                'first' => $order->get_billing_first_name(),
                'last' => $order->get_billing_last_name(),
                'co' => $order->get_billing_company(),
                'city' => $order->get_billing_city(),
                'country' => $order->get_billing_country(),
                'zip' => $order->get_billing_postcode(),
                'total' => $order->get_total(),
                'cy' => $order->get_currency(),
                'cmnt' => $order->get_customer_note(),
                'chk' => $chk,
            );
        }
        return $orders_clean;
    }

    /**
     * Get all orders with a specified product
     * @param int $product_id
     * @param string $orderby
     * @param int $limit
     * @return array
     */
    private function get_orders_db_id(int $product_id, string $orderby, int $limit): array
    {
        if ($orderby === 'date') {
            $orderby = 'l.date_created';
        } else {
            $orderby = 'l.product_gross_revenue';
        }
        global $wpdb;
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $query = "SELECT 
                    o.ID as order_id,
                    l.product_gross_revenue as revenue
                FROM 
                    {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON o.id = l.order_id
                WHERE 
                        o.status = 'wc-completed'
                    AND o.type = 'shop_order'
                    AND l.product_id = %d
                ORDER BY $orderby DESC
                LIMIT %d;";
        } else {
            $query = "SELECT 
                    p.ID as order_id,
                    l.product_gross_revenue as revenue
                FROM 
                    {$wpdb->prefix}posts p
                    INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON p.ID = l.order_id
                WHERE 
                    p.post_type = 'shop_order' 
                    AND p.post_status = 'wc-completed'
                    AND l.product_id = %d
                ORDER BY $orderby DESC
                LIMIT %d;";
        }
        $query = $wpdb->prepare($query, $product_id, $limit);

        $order_data = $wpdb->get_results($query, ARRAY_A);
        $orders_clean = array();

        foreach ($order_data as $order_row) {
            $order = wc_get_order($order_row['order_id']);

            if ($order) {
                $meta = $order->get_meta('wcdp_checkout_checkbox');
                $chk = ($meta === "yes") ? 1 : 0;

                $orders_clean[] = array(
                    'date' => $order->get_date_created()->getTimestamp(),
                    'first' => $order->get_billing_first_name(),
                    'last' => $order->get_billing_last_name(),
                    'co' => $order->get_billing_company(),
                    'city' => $order->get_billing_city(),
                    'country' => $order->get_billing_country(),
                    'zip' => $order->get_billing_postcode(),
                    'total' => (double)$order_row['revenue'],
                    'cy' => $order->get_currency(),
                    'cmnt' => $order->get_customer_note(),
                    'chk' => $chk,
                );
            }
        }
        return $orders_clean;
    }

    /**
     * Generate the HTML output for the orders
     * @param array $orders
     * @param int $style
     * @param int $split
     * @param string $button
     * @return string
     */
    public function generate_leaderboard(array $orders, int $style, int $split, string $button): string
    {
        $title = sanitize_text_field(get_option("wcdp_lb_title", __('{firstname} donated {amount}', 'wc-donation-platform')));
        $subtitle = sanitize_text_field(get_option("wcdp_lb_subtitle", "{timediff}"));
        $title_checked = sanitize_text_field(get_option("wcdp_lb_title_checked", ""));
        $title_unchecked = sanitize_text_field(get_option("wcdp_lb_title_unchecked", ""));
        $subtitle_checked = sanitize_text_field(get_option("wcdp_lb_subtitle_checked", ""));
        $subtitle_unchecked = sanitize_text_field(get_option("wcdp_lb_subtitle_unchecked", ""));
        $id = 'wcdp_' . wp_generate_password(6, false);

        if (sizeof($orders) === 0) {
            return esc_html('No donation to this project yet.', 'wc-donation-platform');
        }

        $output = "<style>#" . $id . " .wcdp-leaderboard-hidden {
                    display: none;
                }";

        if ($style === 1) {
            $output .= $this->get_css_style_1($id);
        } else {
            $output .= $this->get_css_style_2($id);
        }
        $hideClass = '';
        foreach ($orders as $pos => $order) {
            if ($pos === $split) {
                $hideClass = ' wcdp-leaderboard-hidden';
            }
            $placeholders = array(
                '{firstname}' => "<span class='wcdp-leaderboard-firstname'>" . wp_strip_all_tags($order['first']) . "</span>",
                '{firstname_initial}' => "<span class='wcdp-leaderboard-firstname_initial'>" . wp_strip_all_tags($this->get_initials($order['first'])) . "</span>",
                '{lastname}' => "<span class='wcdp-leaderboard-lastname'>" . wp_strip_all_tags($order['last']) . "</span>",
                '{lastname_initial}' => "<span class='wcdp-leaderboard-lastname_initial'>" . wp_strip_all_tags($this->get_initials($order['last'])) . "</span>",
                '{company}' => "<span class='wcdp-leaderboard-company'>" . wp_strip_all_tags($order['co']) . "</span>",
                '{company_or_name}' => "<span class='wcdp-leaderboard-company_or_name'>" . $this->get_company_or_name($order['co'], $order['first'], $order['last']) . "</span>",
                '{amount}' => "<span class='wcdp-leaderboard-amount'>" . wc_price($order['total'], array('currency' => $order['cy'],)) . "</span>",
                '{timediff}' => "<span class='wcdp-leaderboard-timediff'>" . $this->get_human_time_diff($order['date']) . "</span>",
                '{datetime}' => "<span class='wcdp-leaderboard-datetime'>" . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $order['date']) . "</span>",
                '{date}' => "<span class='wcdp-leaderboard-date'>" . date_i18n(get_option('date_format'), $order['date']) . "</span>",
                '{city}' => "<span class='wcdp-leaderboard-city'>" . wp_strip_all_tags($order['city']) . "</span>",
                '{country}' => "<span class='wcdp-leaderboard-country'>" . WC()->countries->countries[esc_attr($order['country'])] . "</span>",
                '{country_code}' => "<span class='wcdp-leaderboard-country_code'>" . wp_strip_all_tags($order['country']) . "</span>",
                '{postcode}' => "<span class='wcdp-leaderboard-postcode'>" . wp_strip_all_tags($order['zip']) . "</span>",
                '{currency}' => "<span class='wcdp-leaderboard-currency'>" . wp_strip_all_tags($order['cy']) . "</span>",
                '{comment}' => "<span class='wcdp-leaderboard-comment'>" . wp_strip_all_tags($order['cmnt']) . "</span>",
                '{anonymous}' => "<span class='wcdp-leaderboard-anonymous'>" . esc_html__("Anonymous donor", "wc-donation-platform") . "</span>",
            );

            $output .= '<li class="wcdp-leaderboard-li' . $hideClass . '"><div>';
            if ($title_checked !== "" && $order['chk'] == 1) {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-checked-title">' . strtr($title_checked, $placeholders) . '</span><br>';
            } else if ($title_unchecked !== "" && $order['chk'] == 0) {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-unchecked-title">' . strtr($title_unchecked, $placeholders) . '</span><br>';
            } else if ($title != "") {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-default-title">' . strtr($title, $placeholders) . '</span><br>';
            }

            if ($subtitle_checked !== "" && $order['chk'] == 1) {
                $output .= '<span class="wcdp-leaderboard-subtitle wcdp-leaderboard-checked-subtitle">' . strtr($subtitle_checked, $placeholders) . '</span>';
            } else if ($subtitle_unchecked !== "" && $order['chk'] == 0) {
                $output .= '<span class="wcdp-leaderboard-subtitle wcdp-leaderboard-unchecked-subtitle">' . strtr($subtitle_unchecked, $placeholders) . '</span>';
            } else if ($subtitle != "") {
                $output .= '<span class="wcdp-leaderboard-subtitle wcdp-leaderboard-default-subtitle">' . strtr($subtitle, $placeholders) . '</span>';
            }
            $output .= '</div></li>';
        }
        $output .= '</ul>';

        if (sizeof($orders) > $split && $split > 0) {
            $output .= '<button class="button wcdp-button wp-element-button" type="button" id="' . $id . '-button">' . esc_html($button) . "</button>
                        <script>
                          const " . $id . " = document.querySelector('#" . $id . "-button');
                          " . $id . ".addEventListener('click', () => {
                            const elementsToToggle = document.querySelectorAll('#" . $id . " .wcdp-leaderboard-hidden');
                            for (let i = 0; i < Math.min(" . (int)$split . ", elementsToToggle.length); i++) {
                              elementsToToggle[i].classList.remove('wcdp-leaderboard-hidden');
                            }
                            if (elementsToToggle.length <= " . (int)$split . ") {
                              " . $id . ".style.display = 'none';
                            }
                          });
                        </script>";
        }

        return $output;
    }

    /**
     * Get HTML part for leaderboard style 1
     * @param string $id leaderboard id
     * @return string
     */
    private function get_css_style_1(string $id): string
    {
        return ':root {
					--wcdp-main-2: ' . sanitize_hex_color(get_option('wcdp_main_color', '#30bf76')) . ';
                    --label-inactive: lightgrey;
                }
                ul.wcdp-leaderboard-s1 {
                  list-style: none;
                  padding: 0;
                  margin: 0;
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-li {
                  position: relative;
                  padding: 12px 0 12px 36px;
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-li::before {
                  content: "";
                  position: absolute;
                  left: 12px;
                  top: 0;
                  bottom: 0;
                  width: 2px;
                  background-color: var(--label-inactive);
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-li:first-child::before {
                  top: 50%;
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-li:last-child::before {
                  bottom: 50%;
                }
                .wcdp-leaderboard-s1  .wcdp-leaderboard-title {
                  font-size: 1.2em;
                  font-weight: bold;
                }
                .wcdp-leaderboard-s1 .woocommerce-Price-amount {
                  font-weight: bold;
                  color: var(--wcdp-main-2);
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-subtitle {
                  font-size: 1em;
                }
                .wcdp-leaderboard-s1 .wcdp-leaderboard-li::after {
                  content: "";
                  position: absolute;
                  left: 8px;
                  top: 50%;
                  transform: translateY(-50%);
                  width: 10px;
                  height: 10px;
                  background-color: var(--wcdp-main-2);
                  border-radius: 50%;
                }
            </style>
            <ul class="wcdp-leaderboard-s1 wcdp-leaderboard" id="' . $id . '">';
    }

    /**
     * Get HTML part for leaderboard style 2
     * @param string $id leaderboard id
     * @return string
     */
    private function get_css_style_2(string $id): string
    {
        return 'ul.wcdp-leaderboard-s2 {
                  list-style: none;
                  padding: 0;
                  margin: 0;
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-li {
                  padding: 3px 0;
                  display: flex;
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-li div {
                  display: inline-block;
                  width: calc(100% - 1.4em);
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-li::before {
                      content: "";
                      background-image: url(' . WCDP_DIR_URL . 'assets/svg/donation.svg);
                      background-size: auto;
                      width: 1.39em;
                      height: 1em;
                      display: inline-block;
                      margin: auto 5px auto 0;
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-title, .wcdp-leaderboard-s2 .woocommerce-Price-amount, .wcdp-leaderboard-s2 .wcdp-emphasized {
                  font-weight: bold;
                }
                .wcdp-leaderboard-li div {
                    max-width: calc(100% - 2em);
                }
            </style>
            <ul class="wcdp-leaderboard-s2 wcdp-leaderboard" id="' . $id . '">';
    }

    /**
     * Get the initials of a name
     * @param $name
     * @return string
     */
    private function get_initials($name): string
    {
        $parts = explode(' ', $name);
        $initials = '';
        foreach ($parts as $part) {
            $initials .= strtoupper(substr($part, 0, 1)) . '.';
        }
        return $initials;
    }

    /**
     * If company is set return company
     * else return name as firstname lastname_initial (John D.)
     * @param string $company
     * @param string $first
     * @param string $last
     * @return string
     */
    private function get_company_or_name(string $company, string $first, string $last): string
    {
        if (!empty($company)) return esc_html($company);

        return esc_html(esc_html($first) . ' ' . esc_html($this->get_initials($last)));
    }

    /**
     * Returns human time diff. Expects $timestamp to be in the past
     *
     * @param $timestamp int UNIX timestamp
     * @return string
     */
    private function get_human_time_diff(int $timestamp): string
    {
        $human_diff = '<span class="wcdp-emphasized">' . human_time_diff($timestamp) . '</span>';
        // Translators: %s: time difference e.g. 1 week ago or 3 months ago
        return sprintf(esc_html__('%s ago', 'wc-donation-platform'), $human_diff);
    }

    /**
     * Hook that's triggered when an order changes its status
     * Deletes cache when old/new status is completed and the cache hasn't been recently generated
     * @param $order_id
     * @param $old_status
     * @param $new_status
     * @param $order
     * @return void
     */
    function delete_old_latest_orders_cache($order_id, $old_status, $new_status, $order): void
    {
        if ($old_status !== 'completed' && $new_status !== 'completed') return;

        // Retrieve cache keys from the 'wcdp_transient_cache_keys' option.
        $cache_keys_option = get_option('wcdp_transient_cache_keys', array());

        if (!is_array($cache_keys_option)) {
            return; // No cache keys found, nothing to delete.
        }

        // Retrieve the product IDs from the order items.
        $product_ids = [-1];
        foreach ($order->get_items() as $item) {
            $product_ids[] = $item->get_product_id();
        }

        // Loop through cache keys to find and delete matching keys.
        foreach ($cache_keys_option as $cache_key => $timestamp) {
            foreach ($product_ids as $product_id) {
                if (strpos($cache_key, 'wcdp_orders_' . $product_id) === 0) {
                    delete_transient($cache_key);
                }
            }
        }
    }

    /**
     * Add a "anonymous donation" checkbox to the checkout
     * @return void
     */
    public function add_anonymous_donation_checkbox()
    {
        echo '<div class="anonymous-donation-checkbox">';
        woocommerce_form_field('wcdp_checkout_checkbox', array(
            'type' => 'checkbox',
            'class' => array('input-checkbox'),
            'label' => get_option("wcdp_checkout_checkbox_text", __('Do not show my name in the leaderboard', 'wc-donation-platform')),
        ), WC()->checkout->get_value('wcdp_checkout_checkbox'));
        echo '</div>';
    }

    /**
     * Save the value of the anonymous checkbox
     * @param $order
     * @return void
     */
    public function save_anonymous_donation_checkbox($order)
    {
        if (isset($_POST['wcdp_checkout_checkbox']) && $_POST['wcdp_checkout_checkbox'] == 1) {
            $order->update_meta_data('wcdp_checkout_checkbox', 'yes');
        } else {
            $order->update_meta_data('wcdp_checkout_checkbox', 'no');
        }
    }

    /**
     * Display the value of the anonymous checkbox to the user
     * @param $order
     * @return void
     */
    public function display_anonymous_donation_checkbox_in_order_details($order)
    {
        $checkbox_value = $order->get_meta('wcdp_checkout_checkbox');
        $e = '<p><strong>' . get_option("wcdp_checkout_checkbox_text", __('Do not show my name in the leaderboard', 'wc-donation-platform')) . ':</strong> ';
        if ($checkbox_value === "yes") {
            $e .= __('Yes', 'wc-donation-platform');
        } else {
            $e .= __('No', 'wc-donation-platform');
        }
        $e .= '</p>';
        echo wp_kses_post($e) ;
    }
}
