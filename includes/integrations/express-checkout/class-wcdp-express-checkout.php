<?php
/**
 * This class integrates Stripe & PayPal Express Checkout with Donation Platform for WooCommerce
 */
if (!defined('ABSPATH'))
    exit;

class WCDP_Express_Checkout
{
    /**
     * Bootstraps the class and hooks required filters
     */
    public function __construct()
    {
        if (isset($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] == 'ppc-change-cart') {
            //Update PayPal Request
            add_filter('woocommerce_add_cart_item_data', array($this, 'paypal_modify_add_cart_data'), 10, 4);
        } else if (isset($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] == 'wc_stripe_add_to_cart') {
            //Update Stripe (Apple/Google Pay) add to cart Request
            add_filter('woocommerce_add_cart_item_data', array($this, 'stripe_modify_add_cart_data'), 10, 4);
        } else if (isset($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] == 'wc_stripe_get_selected_product_data') {
            //Update Stripe (Apple/Google Pay) wc_stripe_get_selected_product_data Request
            add_filter('woocommerce_product_variation_get_price', array($this, 'stripe_modify_get_selected_product_data'), 10, 2);
            add_filter('woocommerce_product_get_price', array($this, 'stripe_modify_get_selected_product_data'), 10, 2);
        }

        //Add Express Donation Heading
        add_action('wcdp_express_checkout_heading', array($this, 'express_donation_heading'));

        //Add hidden Amount Variation fields
        add_action('wcdp_express_checkout_amount_variation', array($this, 'express_checkout_amount_variation'));

        //Remove '(via WooCommerce)' suffix
        add_filter('wc_stripe_payment_request_total_label_suffix', '__return_empty_string');
    }

    function express_donation_heading()
    {
        echo '<p class="wcdp-express-heading" style="margin: 1em 0 7px 0; text-align: center;">&mdash; ' . esc_html__("Express Donation", "wc-donation-platform") . ' &mdash;</p>';
    }

    function express_checkout_amount_variation()
    {
        $min_donation_amount = get_option('wcdp_min_amount', 3);
        echo '<div class="variations_form" style="display:none !important;">
                <input class="wcdp-express-amount" style="display:none !important;" type="number" step="any" name="attribute_wcdp_donation_amount" value="1">
                <div class="variations" style="display:none !important;">
                    <select style="display:none !important;" name="attribute_wcdp_donation_amount">
                        <option value="' . esc_attr($min_donation_amount) . '" style="display:none !important;" class="wcdp-express-amount" selected></option>
                    </select>
                </div>
            </div>';
    }

    /**
     * Modify add_to_cart and set $cart_item_data['wcdp_donation_amount'] to donation amount
     * @param $cart_item_data
     * @param $product_id
     * @param $variation_id
     * @param $quantity
     * @return mixed
     */
    public function stripe_modify_add_cart_data($cart_item_data, $product_id, $variation_id, $quantity)
    {
        if (WCDP_Form::is_donable($product_id)) {
            $min_donation_amount = get_option('wcdp_min_amount', 3);
            $max_donation_amount = get_option('wcdp_max_amount', 50000);
            $amount = $this->stripe_extract_amount();
            if ($amount >= $min_donation_amount && $amount <= $max_donation_amount) {
                $cart_item_data['wcdp_donation_amount'] = $amount;
                return $cart_item_data;
            }
        }
        return $cart_item_data;
    }

    /**
     * Extract Donation Amount from request
     * @return int|string
     */
    function stripe_extract_amount()
    {
        if (isset($_REQUEST['attributes'])) {
            if (isset($_REQUEST['attributes']['attribute_wcdp_donation_amount'])) {
                return sanitize_text_field($_REQUEST['attributes']['attribute_wcdp_donation_amount']);
            }
        }
        return -1;
    }

    /**
     * Filter wc_stripe_get_selected_product_data product price
     * @param $value
     * @param $data
     * @return array|int|string|void
     */
    public function stripe_modify_get_selected_product_data($value, $data)
    {
        $min_donation_amount = get_option('wcdp_min_amount', 3);
        $max_donation_amount = get_option('wcdp_max_amount', 50000);
        $amount = $this->stripe_extract_amount();
        if ($amount >= $min_donation_amount && $amount <= $max_donation_amount) {
            return $amount;
        }
        return $value;
    }

    /**
     * Modify add_to_cart and set $cart_item_data['wcdp_donation_amount'] to donation amount
     * @param $cart_item_data
     * @param $product_id
     * @param $variation_id
     * @param $quantity
     * @return mixed
     */
    public function paypal_modify_add_cart_data($cart_item_data, $product_id, $variation_id, $quantity)
    {
        if (!WCDP_Form::is_donable($product_id)) {
            return $cart_item_data;
        }

        $stream = file_get_contents('php://input');
        if (!$stream) {
            return $cart_item_data;
        }

        $data = json_decode($stream);
        if (!is_object($data) || !isset($data->products) || !is_array($data->products)) {
            return $cart_item_data;
        }

        foreach ($data->products as $product) {

            if (!is_object($product) || !isset($product->id) || (int) $product->id !== $product_id) {
                continue;
            }

            $extra = $product->extra ?? null;
            if (!is_object($extra)) {
                continue;
            }

            $amount = $extra->{'wcdp-donation-amount'} ?? null;
            $nonce = $extra->{'security'} ?? null;

            if ($amount === null || $nonce === null) {
                continue;
            }

            if (
                WCDP_Form::check_donation_amount($amount, $product_id) &&
                wp_verify_nonce($nonce, 'wcdp_ajax_nonce' . $product_id)
            ) {
                $cart_item_data['wcdp_donation_amount'] = $amount;
                break; // Found the correct product, no need to keep looping
            }
        }

        return $cart_item_data;
    }
}
