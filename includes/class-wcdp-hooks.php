<?php
/**
 * This class adds WooCommerce Hooks
 *
 * @since 1.0.0
 */

if(!defined('ABSPATH')) exit;

class WCDP_Hooks
{
    public function __construct() {
        //Change some WC templates to WCDP templates
        add_filter( 'wc_get_template', array( $this, 'wcdp_modify_template'), 11, 5 );

        //A page with a WCDP form is a checkout page
        add_filter( 'woocommerce_is_checkout', array( $this, 'wcdp_set_is_checkout' ) );

        //Rename Account Order Columns
        add_filter( 'woocommerce_account_orders_columns', array( $this, 'wcdp_account_orders_columns'), 10 );

        //Rename Account Menu "Orders" item to "Donations"
        add_filter( 'woocommerce_account_menu_items', array( $this, 'wcdp_account_menu_items'), 10, 1 );

        //Rename Title in orders page
        add_filter( 'woocommerce_endpoint_orders_title', array( $this, 'wcdp_endpoint_orders_title'), 10, 1 );

        //Hide Hide Place Order button when cart is empty
        add_filter('woocommerce_order_button_html', array( $this, 'wcdp_order_button_html'), 10, 1);

        //Rename Place Order button
        add_filter('woocommerce_order_button_text', array( $this, 'wcdp_order_button_text'), 10 );

        //Allow checkout page with empty cart
        add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false' );

        //Allow checkout page with expired update order review
        add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false', 15 );

        //Rename Order notes to Donation notes
        add_filter( 'woocommerce_checkout_fields', array( $this, 'woocommerce_checkout_fields'), 10 );

        //Disable Order Again Button on my account page
        add_filter( 'woocommerce_valid_order_statuses_for_order_again', '__return_empty_array' );

        //Change "Add to Cart" Button
        add_filter('woocommerce_loop_add_to_cart_link', array($this, 'wcdp_loop_add_to_cart_link'), 10, 3);

        //Change "Add to Cart" Button
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'product_add_to_cart_text') );

		//Remove x 1 after a product name
		add_filter('woocommerce_order_item_quantity_html', '__return_empty_string' );

        //Set Price of Donation
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'wcdp_set_donation_price'), 99 );

        //Add donation selection form on checkout page
        add_action( 'woocommerce_before_checkout_form', array( $this, 'wcdp_before_checkout_form') );

        //Ensure there is a WooCommerce session so that nonces are not invalidated by new session created on AJAX request
        add_action( 'wp', array( $this, 'ensure_session' ) );

        //Add donation selection form on checkout page
        add_action( 'woocommerce_product_related_products_heading', array( $this, 'wcdp_product_related_products_heading') );

        //Disable Order notes if checked in settings
        if (get_option('wcdp_disable_order_notes', 'no') == 'yes') {
            add_filter('woocommerce_enable_order_notes_field', '__return_false');
        }

		//donations do not need admin processing
		add_filter('woocommerce_order_item_needs_processing', array( $this, 'wcdp_autocomplete_order'), 10, 3);
    }

    /**
     * Filter specific WC templates to WCDP templates
     *
     * @param string $template
     * @param string $template_name
     * @param string $args
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public function wcdp_modify_template(string $template, string $template_name, $args, string $template_path, string $default_path ): string
    {
        //Return if the template has been overwritten in yourtheme/woocommerce/XXX
        if ($template[strlen($template) - strlen($template_name) - 2] === 'e') {
            return $template;
        }

        $path = WCDP_DIR . 'includes/wc-templates/';

        switch ($template_name) {

            case 'checkout/review-order.php':
            case 'checkout/form-login.php':
            case 'checkout/thankyou.php':
            case 'checkout/cart-errors.php':
            case 'checkout/form-checkout.php':
            case 'checkout/order-receipt.php':
            case 'checkout/payment.php':
            case 'checkout/form-billing.php':

            case 'myaccount/dashboard.php':
            case 'myaccount/view-order.php':
            case 'myaccount/my-address.php':
            case 'myaccount/orders.php':
            case 'myaccount/downloads.php':

            case 'order/order-details.php':

            case 'emails/email-order-details.php' :
            case 'emails/email-customer-details.php' :
            case 'emails/email-addresses.php' :
            case 'emails/customer-refunded-order.php' :
            case 'emails/customer-processing-order.php' :
            case 'emails/email.php' :
            case 'emails/customer-on-hold-order.php' :
            case 'emails/customer-note.php' :
            case 'emails/customer-new-account.php' :
            case 'emails/customer-invoice.php' :
            case 'emails/customer-completed-order.php' :
            case 'emails/admin-new-order.php' :
            case 'emails/admin-failed-order.php' :
            case 'emails/admin-cancelled-order.php' :
            case 'emails/plain/email-order-details.php' :
            case 'emails/plain/email.php' :
            case 'emails/plain/email-customer-details.php' :
            case 'emails/plain/email-addresses.php' :
            case 'emails/plain/customer-refunded-order.php' :
            case 'emails/plain/customer-processing-order.php' :
            case 'emails/plain/customer-on-hold-order.php' :
            case 'emails/plain/customer-note.php' :
            case 'emails/plain/customer-invoice.php' :
            case 'emails/plain/customer-completed-order.php' :
            case 'emails/plain/admin-new-order.php' :
            case 'emails/plain/admin-failed-order.php' :
            case 'emails/plain/admin-cancelled-order.php' :

            case 'loop/no-products-found.php':

                $template = $path . $template_name;
                break;

			case 'loop/price.php':
				global $product;
				if(!is_null($product) && WCDP_Form::is_donable($product->get_id())) {
					$template = $path . $template_name;
				}
				break;

			case 'single-product/price.php':
			case 'single-product/add-to-cart/variation-add-to-cart-button.php' :
				if(WCDP_Form::is_donable(get_queried_object_id())) {
					$template = $path . $template_name;
				}
				break;

            case 'single-product/add-to-cart/simple.php' :
            case 'single-product/add-to-cart/variable.php' :
			case 'single-product/add-to-cart/grouped.php' :
            	if(WCDP_Form::is_donable(get_queried_object_id())) {
					$template = $path . 'single-product/add-to-cart/product.php';
				}
                break;

            default:
                break;
        }
        return apply_filters( 'wcdp_get_template', $template, $template_name, $args, $template_path, $default_path );
    }

    /**
     * Return true if page contains a WCDP form
     * @param $is_checkout
     * @return bool
     */
    public function wcdp_set_is_checkout( $is_checkout ): bool
    {
		if ($is_checkout) {
			return true;
		}
        if (defined('WCDP_FORM')) {
            return WCDP_FORM;
        }
		global $post;
		if (has_block( 'wc-donation-platform/wcdp' )
            || (!is_null($post) && (has_shortcode( $post->post_content, 'wcdp_donation_form') || has_shortcode( $post->post_content, 'product_page')))
        ){
            define('WCDP_FORM', true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rename Account Order Columns
     *
     * @param array $columns
     * @return array filtered $columns
     */
    public function wcdp_account_orders_columns(array $columns): array
    {
        $columns['order-number'] = __( 'Donation', 'wc-donation-platform' );
        $columns['order-total'] = __( 'Total', 'wc-donation-platform' );

        return $columns;
    }

    /**
     * Rename Account Menu "Orders" item to "Donations"
     *
     * @param array $items
     * @return array
     */
    public function wcdp_account_menu_items(array $items): array
    {
        return array_merge( $items, array(
            'orders' => __( 'Donations', 'wc-donation-platform' ),
            )
        );
    }

    /**
     * Rename Title in orders page
     *
     * @param string $title
     * @return string
     */
    public function wcdp_endpoint_orders_title(string $title): string
    {
        global $wp;
        if ( ! empty( $wp->query_vars['orders'] ) ) {
            /* translators: %s: page */
            $title = sprintf( __( 'Donations (page %d)', 'wc-donation-platform' ), intval( $wp->query_vars['orders'] ) );
        } else {
            $title = __( 'Donations', 'wc-donation-platform' );
        }
        return $title;
    }

    /**
     * Hide Place Order button when cart is empty
     *
     * @param $html
     * @return string|string[]
     */
    public function wcdp_order_button_html($html) {
        if (WC()->cart->is_empty()) {
            return substr_replace($html, 'style="display:none" ', strpos($html, 'id="place_order"'), 0);
        }
        return $html;
    }

    /**
     * Rename Place Order button
     *
     * @return string
     */
    public function wcdp_order_button_text(): string
    {
        return __('Donate now', 'wc-donation-platform');
    }

    /**
     * Recalculate item price to the amount specified by user
     */
    public function wcdp_set_donation_price( $cart_object ) {
        foreach ($cart_object->cart_contents as $value ) {
            if( isset( $value["wcdp_donation_amount"] ) && WCDP_Form::check_donation_amount($value["wcdp_donation_amount"], (int) $value["product_id"])) {
                $value['data']->set_price($value["wcdp_donation_amount"]);
            }
        }
    }

    /**
     * Add donation selection on checkout page
     */
    public function wcdp_before_checkout_form() {
		//insert css variables style block
		WCDP_FORM::define_ccs_variables();

		//add donation form
        if (isset($_REQUEST['postid']) && is_checkout()) {
            $id = intval($_REQUEST['postid']);

            echo WCDP_Form::wcdp_donation_form(array(
                'id' => $id,
                'style' => 'checkout',
            ), true);
        }
    }

    /**
     * Ensure there is a WooCommerce session so that nonces are not invalidated by new session created on AJAX request
     */
    public function ensure_session() {
        if ( ! empty( WC()->session ) && ! WC()->session->has_session() ) {
            WC()->session->set_customer_session_cookie( true );
        }
    }

    /**
     * Change "Related Products Heading"
     */
    public function wcdp_product_related_products_heading() {
        return __( 'Related projects', 'wc-donation-platform' );
    }

    /**
     * Rename order notes to donation notes
     * @param array $fields
     * @return array
     */
    public function woocommerce_checkout_fields(array $fields): array
    {
        if (isset($fields['order']) && isset($fields['order']['order_comments'])) {
            $fields['order']['order_comments']['label'] = __( 'Donation notes', 'wc-donation-platform');
            $fields['order']['order_comments']['placeholder'] = esc_attr__( 'Notes about your donation', 'wc-donation-platform');
        }
        return $fields;
    }

    /**
     * Turn Add to cart text into learn more
     * @return string
     */
    public function product_add_to_cart_text(): string
    {
        return __( 'Learn more', 'wc-donation-platform');
    }

    /**
     * Turn Add to cart button into a "learn more" button
     * Filters the Add to Cart button
     *
     * @param $html
     * @param $product
     * @param array $args
     * @return string
     */
    public function wcdp_loop_add_to_cart_link($html, $product, array $args = array()): string
    {
        return sprintf(
            '<a href="%s" class="button" %s>%s</a>',
            esc_url( $product->get_permalink()),
            isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
            esc_html( $product->add_to_cart_text() )
        );
    }

	/**
	 * Autocomplete donations
	 * (donation products do not need processing)
	 * @param $product WC_Product
	 * @param $order_id int
	 * @return bool if order needs processing
	 */
	public function wcdp_autocomplete_order($needs_processing, WC_Product $product, int $order_id): bool
	{
		if ($needs_processing && $product->is_virtual()) {
			if (! WCDP_Form::is_donable($product->get_id()) && ! WCDP_Form::is_donable($product->get_parent_id())) {
				return true;
			}
		}
		return false;
	}
}
