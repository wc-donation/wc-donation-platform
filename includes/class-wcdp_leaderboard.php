<?php
/**
 * This class displays a leaderboard of your donations
 *
 * * @since 1.3.0
 */

if (!defined('ABSPATH')) exit;

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

    /**<s<
     * get an array with all WooCommerce orders
     * @param string $orderby date or total
     * @return array
     */
    private function get_orders_db(string $orderby): array
    {
        $args = array(
            'limit' => apply_filters("wcdp_max_order_cache", 1000),
            'status' => 'completed',
            'order'   => 'DESC',
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
            $product_ids = array();
            foreach ($order->get_items() as $item) {
                $product_ids[] = $item->get_product_id();
            }
            $meta = $order->get_meta('wcdp_checkout_checkbox');
            if ($meta === "yes") {
                $chk = 1;
            } else  {
                $chk = 0;
            }

            $orders_clean[] = array(
                'date' => $order->get_date_created()->getTimestamp(),
                'first' => $order->get_billing_first_name(),
                'last' =>  $order->get_billing_last_name(),
                'co' => $order->get_billing_company(),
                'city' => $order->get_billing_city(),
                'country' => $order->get_billing_country(),
                'zip' => $order->get_billing_postcode(),
                'total' => $order->get_total(),
                'cy' => $order->get_currency(),
                'ids' => $product_ids,
                'cmnt' => $order->get_customer_note(),
                'chk' => $chk,
            );
        }
        return $orders_clean;
    }

    /**
     * Return all the latest WooCommerce orders
     * @param string $orderby date or total
     * @return array
     */
    private function get_orders(string $orderby) : array {
        $cache_key = 'wcdp_orders_' . $orderby;
        $all_orders = json_decode(get_transient($cache_key), true);

        if (empty($all_orders)) {
            $all_orders = $this->get_orders_db($orderby);
            set_transient($cache_key, json_encode($all_orders), apply_filters("wcdp_cache_expiration", 6 * HOUR_IN_SECONDS));
        }
        return $all_orders;
    }

    /**
     * Return orders that included at least one of the specified ids
     * @param $limit
     * @param $ids
     * @param string $orderby date or total
     * @return array
     */
    private function wcdp_get_orders($limit, $ids, string $orderby): array
    {
        $all_orders = $this->get_orders($orderby);
        if ($ids === ['-1']) {
            if ($limit === -1) return $all_orders;
            return array_slice($all_orders, 0, $limit);
        }

        $filtered_orders = array_filter($all_orders, function ($order) use ($ids) {
            return !empty(array_intersect($order['ids'], $ids));
        });
        if ($limit === -1) return $filtered_orders;
        return array_slice($filtered_orders, 0, $limit);
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
            $output .= '<button class="button wcdp-button" type="button" id="' . $id . '-button">' . esc_html($button) . "</button>
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
            'limit'     => 10,
            'ids'       => '-1',
            'orderby'   => 'date',
            "style"     => 1,
            "split"     => -1,
            "button"    => __("Show more", "wc-donation-platform"),
        ), $atts, 'latest_orders');

        $atts['orderby'] = $atts['orderby'] === 'date' ? 'date' : 'total';

        $limit = intval($atts['limit']);
        $ids = explode(',', $atts['ids']);;

        // Get the latest orders
        $orders = $this->wcdp_get_orders($limit, $ids, $atts['orderby']);

        // Generate the HTML output
        return $this->generate_leaderboard($orders, (int) $atts['style'], (int) $atts['split'], $atts['button'] );
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

        foreach (['date', 'total'] as $orderby) {
            $cache_key = 'wcdp_orders_' . $orderby;
            $timeout = get_option('_transient_timeout_' . $cache_key);

            if (($timeout && time() + apply_filters("wcdp_cache_expiration", 6 * HOUR_IN_SECONDS) - $timeout > 90) || $order_id < 10000) {
                delete_transient($cache_key);
                delete_transient($cache_key . '_timestamp');
            }
        }

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
            $initials .= strtoupper(substr($part, 0, 1))  . '.';
        }
        return $initials;
    }

    /**
     * Returns human time diff. Expects $timestamp to be in the past
     *
     * @param $timestamp int UNIX timestamp
     * @return string
     */
    private function get_human_time_diff(int $timestamp ): string {
        $human_diff = '<span class="wcdp-emphasized">' . human_time_diff( $timestamp ) . '</span>';
        return sprintf( esc_html__( '%s ago', 'wc-donation-platform' ), $human_diff );
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
     * @param string $id  leaderboard id
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
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-li div {
                  display: inline-block;
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-li::before {
                      content: "";
                      background-image: url(' . WCDP_DIR_URL . 'assets/svg/donation.svg);
                      background-size: auto;
                      width: 1.39em;
                      height: 1em;
                      margin-right: 5px;
                      display: inline-block;
                }
                .wcdp-leaderboard-s2 .wcdp-leaderboard-title, .wcdp-leaderboard-s2 .woocommerce-Price-amount, .wcdp-leaderboard-s2 .wcdp-emphasized {
                  font-weight: bold;
                }
            </style>
            <ul class="wcdp-leaderboard-s2 wcdp-leaderboard" id="' . $id . '">';
    }

    /**
     * Add a "anonymous donation" checkbox to the checkout
     * @return void
     */
    public function add_anonymous_donation_checkbox() {
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
    public function save_anonymous_donation_checkbox($order) {
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
    public function display_anonymous_donation_checkbox_in_order_details($order) {
        $checkbox_value = $order->get_meta('wcdp_checkout_checkbox');
        $e = '<p><strong>' . get_option("wcdp_checkout_checkbox_text", __('Do not show my name in the leaderboard', 'wc-donation-platform')) . ':</strong> ';
        if ($checkbox_value === "yes") {
            $e .= __('Yes', 'wc-donation-platform');
        } else {
            $e .= __('No', 'wc-donation-platform');
        }
        echo $e . '</p>';
    }

    /**
     * Clear the entire cache of the leaderboard
     * @return void
     */
    public static function delete_cached_leaderboard_total() {
        foreach (['date', 'total'] as $orderby) {
            $cache_key = 'wcdp_orders_' . $orderby;
            delete_transient($cache_key);
            delete_transient($cache_key . '_timestamp');
        }
    }
}
