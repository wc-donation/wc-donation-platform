<?php
/**
 * This class displays a leaderboard of your donations
 *
 * * @since 1.3.0
 */

if (!defined('ABSPATH'))
    exit;

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

        //Delete cache on product analytics update
        add_action('woocommerce_analytics_update_product', array($this, 'delete_stale_orders_cache'), 10, 2);

        if (get_option('wcdp_enable_checkout_checkbox', 'no') === 'yes') {
            // Add checkbox to WooCommerce checkout
            $checkbox_location = apply_filters('anonymous_donation_checkbox_location', 'woocommerce_review_order_before_submit');
            add_action($checkbox_location, array($this, 'add_anonymous_donation_checkbox'));

            //Save the value of the WooCommerce checkout checkbox
            add_action('woocommerce_checkout_create_order', array($this, 'save_anonymous_donation_checkbox'));

            // Display the value of the WooCommerce checkout checkbox to the user
            add_action('woocommerce_order_details_after_customer_details', array($this, 'display_anonymous_donation_checkbox_in_account_order_details'), 20);
        }

        // Clear donable products cache when product meta is updated
        add_action('updated_post_meta', array($this, 'maybe_clear_donable_cache'), 10, 4);
        add_action('added_post_meta', array($this, 'maybe_clear_donable_cache'), 10, 4);
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
     * Clear the donable products cache (call when product donation status changes)
     * @return void
     */
    public static function clear_donable_products_cache()
    {
        $cache_key = 'wcdp_donable_products';
        delete_transient($cache_key);
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
            'limit' => 10,
            'id' => '-1',
            'orderby' => 'date',
            "style" => 1,
            "split" => -1,
            "button" => __("Show more", "wc-donation-platform"),
            "fallback" => __('No donation data to display.', 'wc-donation-platform'),
        ), $atts, 'wcdp_leaderboard');

        $atts['orderby'] = $atts['orderby'] === 'date' ? 'date' : 'total';

        if ($atts['id'] === 'current') {
            $atts['id'] = get_the_ID();
        }

        // Parse product IDs (support comma-separated values)
        $product_ids = $this->parse_product_ids($atts['id']);

        // Validate donation status for product IDs
        if ($atts['id'] !== '-1') {
            foreach ($product_ids as $product_id) {
                $is_donable = WCDP_Form::is_donable($product_id);
                if (!$is_donable) {
                    return '<p class="wcdp-error-message">' . esc_html__('Donations are not activated for this project.', 'wc-donation-platform') . '</p>';
                }
            }
        }

        $limit = intval($atts['limit']);
        $limit = max(1, min(1000, $limit)); // Enforce reasonable limits

        $orders = $this->get_orders($product_ids, $atts['orderby'], $limit);

        // Generate the HTML output
        return $this->generate_leaderboard($orders, (int) $atts['style'], (int) $atts['split'], $atts['button'], $atts['fallback']);
    }

    /**
     * Parse product IDs from string (supports comma-separated values)
     * @param string $ids_string
     * @return array
     */
    private function parse_product_ids(string $ids_string): array
    {
        // Sanitize input string
        $ids_string = sanitize_text_field($ids_string);

        // if ids contains -1, ignore all other ids
        if (str_contains($ids_string, '-1')) {
            return [-1];
        }

        // Split by comma and sanitize each ID
        $ids = array_map('trim', explode(',', $ids_string));
        $product_ids = array();

        foreach ($ids as $id) {
            // Validate and sanitize each ID
            if (!is_numeric($id)) {
                continue;
            }

            $product_id = absint($id);
            if ($product_id > 0 && $product_id <= PHP_INT_MAX) {
                $product_ids[] = $product_id;
            }
        }

        // Limit to reasonable number of product IDs to prevent performance issues
        if (count($product_ids) > 100) {
            $product_ids = array_slice($product_ids, 0, 100);
        }

        return empty($product_ids) ? [-1] : array_unique($product_ids);
    }

    /**
     * Return all the latest WooCommerce orders
     * @param array $product_ids
     * @param string $orderby date or total
     * @param int $limit
     * @return array
     */
    private function get_orders(array $product_ids, string $orderby, int $limit): array
    {
        $cache_key = $this->generate_cache_key($product_ids, $orderby, $limit);

        $orders = json_decode(get_transient($cache_key), true);

        if (empty($orders)) {
            if (in_array(-1, $product_ids)) {
                $orders = $this->get_orders_db_all($orderby, $limit);
            } else {
                $orders = $this->get_orders_db_by_product_ids($product_ids, $orderby, $limit);
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
     * Generate optimized cache key - only uses MD5 when necessary
     * @param array $product_ids
     * @param string $orderby
     * @param int $limit
     * @return string
     */
    private function generate_cache_key(array $product_ids, string $orderby, int $limit): string
    {
        $product_string = implode(',', $product_ids);
        $base_key = 'wcdp_orders_';

        // Check if we can use a simple concatenation (WordPress transient keys max length is ~172 chars)
        // Leave room for prefix, orderby, limit and separators
        $simple_key = $base_key . $product_string . '_' . $orderby . '_' . $limit;

        // Use simple key if it's short enough and contains only safe characters
        if (strlen($simple_key) <= 150 && ctype_alnum(str_replace([',', '_', '-'], '', $simple_key))) {
            return $simple_key;
        }

        // For longer or complex product ID lists, use MD5 hash
        return $base_key . 'h_' . md5($product_string) . '_' . $orderby . '_' . $limit;
    }

    /**
     * Determine if a cache key should be invalidated based on changed product IDs
     * @param string $cache_key
     * @param array $changed_product_ids
     * @return bool
     */
    private function should_invalidate_cache_key(string $cache_key, array $changed_product_ids): bool
    {
        // Always invalidate cache for "all donation products" (contains -1)
        if (strpos($cache_key, 'wcdp_orders_-1_') === 0 || strpos($cache_key, 'wcdp_orders_h_') === 0) {
            // For hashed keys, we need to be conservative and invalidate all
            // Check if -1 is in the simple key format
            return true;
        }

        // Extract the product IDs part from simple cache key format: wcdp_orders_ID1,ID2,ID3_orderby_limit
        $cache_key_parts = explode('_', $cache_key);
        if (count($cache_key_parts) >= 4) {
            $product_ids_part = $cache_key_parts[2]; // Should be the product IDs part
            $cached_product_ids = array_map('intval', explode(',', $product_ids_part));

            // Check if any changed product ID is in the cached product IDs
            foreach ($changed_product_ids as $changed_id) {
                if (in_array($changed_id, $cached_product_ids)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get an array with all WooCommerce orders containing donation products
     * @param string $orderby date or total
     * @param int $limit
     * @return array
     */
    private function get_orders_db_all(string $orderby, int $limit): array
    {
        // Get cached donable product IDs
        $donable_product_ids = $this->get_cached_donable_products();

        // If no donable products found, return empty array
        if (empty($donable_product_ids)) {
            return array();
        }

        // Use the consolidated function to get orders for these donable products
        return $this->get_orders_db_by_product_ids($donable_product_ids, $orderby, $limit);
    }

    /**
     * Get cached list of donable product IDs, with fallback to database query
     * @return array
     */
    private function get_cached_donable_products(): array
    {
        $cache_key = 'wcdp_donable_products';
        $cached_products = get_transient($cache_key);

        if ($cached_products !== false) {
            return $cached_products;
        }

        // Cache miss - query database
        global $wpdb;

        // Query only published products that are donable and available for purchase
        // Excludes trashed, draft, private products
        $donable_products = $wpdb->get_col("
            SELECT DISTINCT pm.post_id 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_donable' 
                AND pm.meta_value = 'yes'
                AND p.post_type = 'product'
                AND p.post_status = 'publish'
        ");

        // Handle database errors
        if ($wpdb->last_error) {
            return array();
        }

        // Convert to integers for security
        $donable_product_ids = array_map('intval', $donable_products);

        // Cache the results for 12 hours (products don't change donation status frequently)
        $cache_duration = apply_filters('wcdp_donable_products_cache_duration', 12 * HOUR_IN_SECONDS);
        set_transient($cache_key, $donable_product_ids, $cache_duration);

        return $donable_product_ids;
    }

    /**
     * Get all donation items with specified product IDs (shows each donation separately, not aggregated by order)
     * This function is quite slow we try to call it as little as possible
     * @param array $product_ids
     * @param string $orderby
     * @param int $limit
     * @return array
     */
    private function get_orders_db_by_product_ids(array $product_ids, string $orderby, int $limit): array
    {
        if (empty($product_ids)) {
            return array();
        }

        // Create placeholders for product IDs
        $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));

        global $wpdb;
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            if ($orderby === 'date') {
                $orderby_sql = 'o.date_created_gmt DESC, l.order_item_id';
            } else {
                $orderby_sql = 'l.product_gross_revenue DESC, o.date_created_gmt';
            }
            $query = "SELECT 
                    o.id as order_id,
                    l.order_item_id,
                    l.product_id,
                    l.product_gross_revenue as item_total,
                    o.date_created_gmt
                FROM 
                    {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON o.id = l.order_id
                WHERE 
                    o.status = 'wc-completed'
                    AND o.type = 'shop_order'
                    AND l.product_id IN ($placeholders)
                ORDER BY $orderby_sql
                LIMIT %d;";
        } else {
            if ($orderby === 'date') {
                $orderby_sql = 'o.post_date DESC, l.order_item_id';
            } else {
                $orderby_sql = 'l.product_gross_revenue DESC, o.post_date';
            }
            $query = "SELECT 
                    o.ID as order_id,
                    l.order_item_id,
                    l.product_id,
                    l.product_gross_revenue as item_total,
                    o.post_date as date_created
                FROM 
                    {$wpdb->prefix}posts o
                    INNER JOIN {$wpdb->prefix}wc_order_product_lookup l ON o.ID = l.order_id
                WHERE 
                    o.post_type = 'shop_order' 
                    AND o.post_status = 'wc-completed'
                    AND l.product_id IN ($placeholders)
                ORDER BY $orderby_sql
                LIMIT %d;";
        }

        // Prepare query with product IDs and limit
        $prepare_params = array_merge($product_ids, array($limit));

        $query = $wpdb->prepare($query, $prepare_params);
        $donation_data = $wpdb->get_results($query, ARRAY_A);

        // Handle database errors
        if ($wpdb->last_error) {
            return array();
        }

        $donations_clean = array();

        foreach ($donation_data as $donation_row) {
            $order_id = $donation_row['order_id'];
            $item_id = $donation_row['order_item_id'];
            $product_id = $donation_row['product_id'];
            $item_total = $donation_row['item_total'];

            $order = wc_get_order($order_id);

            if ($order) {
                $meta = $order->get_meta('wcdp_checkout_checkbox');
                $chk = ($meta === "yes") ? 1 : 0;

                // Get product name for additional context
                $product = wc_get_product($product_id);
                $product_name = $product ? $product->get_name() : 'Unknown Product';

                // Check if we should include transaction fee
                $final_amount = (double) $item_total;
                $transaction_fee = $this->get_transaction_fee_for_single_donation_order($order);
                if ($transaction_fee > 0) {
                    $final_amount += $transaction_fee;
                }

                $donation_clean = array(
                    'date' => $order->get_date_created()->getTimestamp(),
                    'first' => $order->get_billing_first_name(),
                    'last' => $order->get_billing_last_name(),
                    'co' => $order->get_billing_company(),
                    'city' => $order->get_billing_city(),
                    'country' => $order->get_billing_country(),
                    'zip' => $order->get_billing_postcode(),
                    'total' => $final_amount,
                    'cy' => $order->get_currency(),
                    'cmnt' => $order->get_customer_note(),
                    'chk' => $chk,
                    'product_name' => $product_name,
                );

                $donations_clean[] = $donation_clean;
            }
        }

        return $donations_clean;
    }

    /**
     * Generate the HTML output for the orders
     * @param array $orders
     * @param int $style
     * @param int $split
     * @param string $button
     * @param string $fallback
     * @return string
     */
    public function generate_leaderboard(array $orders, int $style, int $split, string $button, string $fallback): string
    {
        $checkout_checkbox_enabled = get_option("wcdp_enable_checkout_checkbox", "yes") === "yes";
        $title = sanitize_text_field(get_option("wcdp_lb_title", __('{firstname} donated {amount}', 'wc-donation-platform')));
        $subtitle = sanitize_text_field(get_option("wcdp_lb_subtitle", "{timediff}"));
        $title_checked = sanitize_text_field(get_option("wcdp_lb_title_checked", ""));
        $title_unchecked = sanitize_text_field(get_option("wcdp_lb_title_unchecked", ""));
        $subtitle_checked = sanitize_text_field(get_option("wcdp_lb_subtitle_checked", ""));
        $subtitle_unchecked = sanitize_text_field(get_option("wcdp_lb_subtitle_unchecked", ""));
        $id = wp_unique_id('wcdp_');

        if (sizeof($orders) === 0) {
            return esc_html($fallback);
        }

        //style tag will be closed in get_css_style_1 or get_css_style_2 functions
        $output = "<style>#" . $id . " .wcdp-leaderboard-hidden {
                    display: none;
                }";

        if ($style === 1) {
            $output .= $this->get_css_style_1($id);
        } else {
            $output .= $this->get_css_style_2($id);
        }
        $hideClass = '';
        $timezone = wp_timezone();
        foreach ($orders as $pos => $donation) {
            if ($pos === $split) {
                $hideClass = ' wcdp-leaderboard-hidden';
            }
            $datetime = (new DateTime())->setTimestamp($donation['date'])->setTimezone($timezone);

            $placeholders = array(
                '{firstname}' => "<span class='wcdp-leaderboard-firstname'>" . wp_strip_all_tags($donation['first']) . "</span>",
                '{firstname_initial}' => "<span class='wcdp-leaderboard-firstname_initial'>" . wp_strip_all_tags($this->get_initials($donation['first'])) . "</span>",
                '{lastname}' => "<span class='wcdp-leaderboard-lastname'>" . wp_strip_all_tags($donation['last']) . "</span>",
                '{lastname_initial}' => "<span class='wcdp-leaderboard-lastname_initial'>" . wp_strip_all_tags($this->get_initials($donation['last'])) . "</span>",
                '{company}' => "<span class='wcdp-leaderboard-company'>" . wp_strip_all_tags($donation['co']) . "</span>",
                '{company_or_name}' => "<span class='wcdp-leaderboard-company_or_name'>" . $this->get_company_or_name($donation['co'], $donation['first'], $donation['last']) . "</span>",
                '{amount}' => "<span class='wcdp-leaderboard-amount'>" . wc_price($donation['total'], array('currency' => $donation['cy'], )) . "</span>",
                '{timediff}' => "<span class='wcdp-leaderboard-timediff'>" . $this->get_human_time_diff($donation['date']) . "</span>",
                '{datetime}' => "<span class='wcdp-leaderboard-datetime'>" . $datetime->format(get_option('date_format') . ' ' . get_option('time_format')) . "</span>",
                '{date}' => "<span class='wcdp-leaderboard-date'>" . $datetime->format(get_option('date_format')) . "</span>",
                '{city}' => "<span class='wcdp-leaderboard-city'>" . wp_strip_all_tags($donation['city']) . "</span>",
                '{country}' => "<span class='wcdp-leaderboard-country'>" . WC()->countries->countries[esc_attr($donation['country'])] . "</span>",
                '{country_code}' => "<span class='wcdp-leaderboard-country_code'>" . wp_strip_all_tags($donation['country']) . "</span>",
                '{postcode}' => "<span class='wcdp-leaderboard-postcode'>" . wp_strip_all_tags($donation['zip']) . "</span>",
                '{currency}' => "<span class='wcdp-leaderboard-currency'>" . wp_strip_all_tags($donation['cy']) . "</span>",
                '{comment}' => "<span class='wcdp-leaderboard-comment'>" . wp_strip_all_tags($donation['cmnt']) . "</span>",
                '{anonymous}' => "<span class='wcdp-leaderboard-anonymous'>" . esc_html__("Anonymous donor", "wc-donation-platform") . "</span>",
                '{product_name}' => "<span class='wcdp-leaderboard-product'>" . wp_strip_all_tags(isset($donation['product_name']) ? $donation['product_name'] : '') . "</span>",
            );

            $output .= '<li class="wcdp-leaderboard-li' . $hideClass . '"><div>';
            if ($checkout_checkbox_enabled && $title_checked !== "" && $donation['chk'] == 1) {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-checked-title">' . strtr($title_checked, $placeholders) . '</span><br>';
            } else if ($checkout_checkbox_enabled && $title_unchecked !== "" && $donation['chk'] == 0) {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-unchecked-title">' . strtr($title_unchecked, $placeholders) . '</span><br>';
            } else if (!$checkout_checkbox_enabled && $title != "") {
                $output .= '<span class="wcdp-leaderboard-title wcdp-leaderboard-default-title">' . strtr($title, $placeholders) . '</span><br>';
            }

            if ($checkout_checkbox_enabled && $subtitle_checked !== "" && $donation['chk'] == 1) {
                $output .= '<span class="wcdp-leaderboard-subtitle wcdp-leaderboard-checked-subtitle">' . strtr($subtitle_checked, $placeholders) . '</span>';
            } else if ($checkout_checkbox_enabled && $subtitle_unchecked !== "" && $donation['chk'] == 0) {
                $output .= '<span class="wcdp-leaderboard-subtitle wcdp-leaderboard-unchecked-subtitle">' . strtr($subtitle_unchecked, $placeholders) . '</span>';
            } else if (!$checkout_checkbox_enabled && $subtitle != "") {
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
                            for (let i = 0; i < Math.min(" . (int) $split . ", elementsToToggle.length); i++) {
                              elementsToToggle[i].classList.remove('wcdp-leaderboard-hidden');
                            }
                            if (elementsToToggle.length <= " . (int) $split . ") {
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
                    --wcdp-label-inactive: lightgrey;
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
                  background-color: var(--wcdp-label-inactive);
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
     * Get transaction fee amount if order contains single donation and has transaction fee
     * @param WC_Order $order
     * @return float
     */
    private function get_transaction_fee_for_single_donation_order(WC_Order $order): float
    {
        $items = $order->get_items('line_item');

        // Only include transaction fee if there's exactly one donation item
        if (count($items) !== 1) {
            return 0.0;
        }

        // Look for transaction fee
        $transaction_costs_label = __('Transaction costs', 'wc-donation-platform');
        $fees = $order->get_items('fee');

        foreach ($fees as $fee) {
            if ($fee->get_name() === $transaction_costs_label) {
                $fee_amount = (float) $fee->get_total();
                return $fee_amount;
            }
        }

        return 0.0;
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
        if (!empty($company))
            return esc_html($company);

        return esc_html($first) . ' ' . esc_html($this->get_initials($last));
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
     * Hook that's triggered when product analytics are updated (order item added/updated)
     * Deletes cache when the order item's product is a donation product
     * @param $order_item_id - the WC_Order_Item id
     * @param $order_id - the order id
     * @return void
     */
    function delete_stale_orders_cache($order_item_id, $order_id): void
    {
        error_log('delete_stale_orders_cache ' . $order_item_id . ' ' . $order_id);

        $order_item = WC_Order_Factory::get_order_item($order_item_id);
        if (!$order_item || !method_exists($order_item, 'get_product_id')) {
            return;
        }

        $product_id = $order_item->get_product_id();
        if ($product_id <= 0) {
            return;
        }

        // Only invalidate cache if this is a donation product
        if (!WCDP_Form::is_donable($product_id)) {
            return;
        }

        // Retrieve cache keys from the 'wcdp_transient_cache_keys' option.
        $cache_keys_option = get_option('wcdp_transient_cache_keys', array());

        if (!is_array($cache_keys_option)) {
            return; // No cache keys found, nothing to delete
        }

        $product_ids = array($product_id);

        foreach ($cache_keys_option as $cache_key => $timestamp) {
            $should_delete = $this->should_invalidate_cache_key($cache_key, $product_ids);

            if ($should_delete) {
                delete_transient($cache_key);
                unset($cache_keys_option[$cache_key]);
            }
        }
        update_option('wcdp_transient_cache_keys', $cache_keys_option);
    }

    /**
     * Add an "anonymous donation" checkbox to the checkout
     * @return void
     */
    public function add_anonymous_donation_checkbox()
    {
        if (!WCDP_Form::cart_contains_donation()) {
            return;
        }
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
        if (!WCDP_Form::order_contains_donation($order)) {
            return;
        }
        if (isset($_POST['wcdp_checkout_checkbox']) && $_POST['wcdp_checkout_checkbox'] == 1) {
            $order->update_meta_data('wcdp_checkout_checkbox', 'yes');
        } else {
            $order->update_meta_data('wcdp_checkout_checkbox', 'no');
        }
    }

    /**
     * Clear donable products cache when _donable meta is updated
     * @param int $meta_id
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $meta_value
     * @return void
     */
    public function maybe_clear_donable_cache($meta_id, $post_id, $meta_key, $meta_value)
    {
        // Only clear cache if the _donable meta key was updated
        if ($meta_key === '_donable') {
            self::clear_donable_products_cache();
        }
    }


    /**
     * Display the value of the anonymous checkbox in My Account order details page
     * @param int $order_id
     * @since 1.3.5
     * @return void
     */
    public function display_anonymous_donation_checkbox_in_account_order_details($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order || !WCDP_Form::order_contains_donation($order)) {
            return;
        }

        $checkbox_value = $order->get_meta('wcdp_checkout_checkbox');

        if ($checkbox_value === null || $checkbox_value === '')
            return;

        echo wp_kses_post(sprintf(
            '<section class="woocommerce-order-leaderboard-preference"><h2 style="margin: 1em 0 0;">%s</h2><p><strong>%s:</strong> %s</p></section>',
            esc_html__('Leaderboard Preference', 'wc-donation-platform'),
            esc_html(get_option("wcdp_checkout_checkbox_text", __('Do not show my name in the leaderboard', 'wc-donation-platform'))),
            esc_html($checkbox_value === "yes" ? __('Yes', 'wc-donation-platform') : __('No', 'wc-donation-platform'))
        ));
    }
}
