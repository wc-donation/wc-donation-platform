<?php

if(!defined('ABSPATH')) exit;

/**
 * Class WCDP_Form
 */
class WCDP_Form
{
	public function __construct() {
		//Donation Form Shortcode
		add_shortcode( 'wcdp_donation_form', 'WCDP_Form::wcdp_donation_form_shortcode' );

		//Register & Enqueue CSS & JS files
		add_action( 'wp_enqueue_scripts', array($this, 'wcdp_enqueue_scripts'), 15 );

		//Register Gutenberg Block
		add_action( 'init',  array($this, 'wcdp_block_init') );

		//Handle AJAx requests to put Donation Product in cart
		add_action( 'wp_ajax_wcdp_ajax_donation_calculation', array($this, 'wcdp_ajax_donation_calculation') );
		add_action( 'wp_ajax_nopriv_wcdp_ajax_donation_calculation', array($this, 'wcdp_ajax_donation_calculation') );
	}

    /**
     * Register & Enqueue CSS & JS Files
     */
    public function wcdp_enqueue_scripts() {
        //Dependencies
        $cssdeps = array('select2',);
        $jsdeps = array('jquery',
            'selectWoo',
            'wc-checkout',
            'wc-password-strength-meter',
            'select2',
            'wc-cart',);

        //Register CSS & JS
        wp_register_style( 'wc-donation-platform', WCDP_DIR_URL . 'assets/css/wcdp.min.css', $cssdeps, WCDP_VERSION );
        wp_register_script( 'wc-donation-platform', WCDP_DIR_URL . 'assets/js/wcdp.min.js', $jsdeps, WCDP_VERSION );

        //Only enqueue if needed
        if($this->wcdp_has_donation_form()) {
            wp_enqueue_style( 'wc-donation-platform');
            wp_enqueue_script( 'wc-donation-platform');
        }
    }

    /**
     * Return html of Donation Form
     * @param string $atts
     * @return false|string|void
     */
    public static function wcdp_donation_form_shortcode($atts = array()){
        return WCDP_Form::wcdp_donation_form($atts, false);
    }

	/**
	 * Return html of Donation Form
	 *
	 * @param $value donation form attributes
	 * @param $is_internal
	 * @return string html of donation form
	 */
    public static function wcdp_donation_form($value, $is_internal): string
	{
        //Only one donation form per page
        static $no_donation_form_yet = true;
        if ( !$no_donation_form_yet ) {
            return '<p class="wcdp-error-message">' . esc_html__('Only one donation form per page allowed','wc-donation-platform' ) . '</p>';
        }
        $no_donation_form_yet = false;

        $value = shortcode_atts( array(
            'id'			    => 0,
            'style'			    => 1,
            'popup'             => 0,
            'button'    		=> 1,
            'title'             => 0,
            'description'       => 0,
            'short_description' => 0,
            'image'             => 0,
			'className'			=> ''
        ), $value );

        if (!$value['id']) {
            return '<p class="wcdp-error-message">' . esc_html__('id is a required attribute', 'wc-donation-platform' ) . '</p>';
        }

        $checkout = WC()->checkout();
        $id = intval($value['id']);

        ob_start();

        // If checkout registration is disabled and user id not logged in, the user cannot donate.
        if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
            echo '<ul class="woocommerce-info" role="info"><li>';
            esc_html_e( 'Please log in to donate.', 'wc-donation-platform' );
            echo '</li></ul>';
            wc_get_template('templates/form-login.php');
        } else {
            $product = wc_get_product($id);
            if (!isset(WC()->cart)) {
				WCDP_Form::form_error_message('In the current view, the donation form is not available.');
			} else if(!WCDP_Form::is_donable($id)) {
				WCDP_Form::form_error_message('Donations are not activated for this project.');
			} else if(!$product) {
				WCDP_Form::form_error_message('Invalid project ID: This project is unknown.');
			} else if(!is_a( $product, 'WC_Product_Grouped' ) && !$product->is_purchasable()) {
				WCDP_Form::form_error_message('Currently you can not donate to this project.');
			} else if(!$product->is_in_stock()) {
				WCDP_Form::form_error_message('This project is currently not available.');
			} else {
				WC()->cart->empty_cart();
                $has_child = is_a( $product, 'WC_Product_Variable' ) && $product->has_child();

                //enqueue woocommerce variation js
                if ($has_child) {
                    wp_enqueue_script('wc-add-to-cart-variation');
                }

                require_once 'templates/wcdp_form.php';
            }
        }

        $r = ob_get_contents();
        ob_end_clean();
        return $r;
    }

    private static function form_error_message($message) {
		echo '<ul class="woocommerce-error wcdp-error-message" id="wcdp-ajax-error" role="alert"><li>';
		esc_html_e($message, 'wc-donation-platform' );
		echo '</li></ul>';
	}

    /**
     * return true if there is a donation form on the site
     * @return bool
     */
    private static function wcdp_has_donation_form(): bool
    {
        if (defined('WCDP_FORM')) {
            return true;
        }

        global $post;
        if (is_product() || is_checkout()
			|| has_block( 'wc-donation-platform/wcdp' )
            || (is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wcdp_donation_form' ))){
            define('WCDP_FORM', 1);
            return true;
        }
        return false;
    }

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 */
	public function wcdp_block_init() {
		register_block_type_from_metadata( WCDP_DIR, array(
			'render_callback'  => 'WCDP_Form::wcdp_donation_form_shortcode',
			'attributes' => array(
				'id'    => array(
					'type'    => 'number',
					'default' => 0,
				),
				'style'    => array(
					'type'    => 'number',
					'default' => 1,
					'enum' => array( 1, 2, 3, 4 ),
				),
				'popup'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'button'    => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'title'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'description'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'short_description'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'image'    => array(
					'type'    => 'boolean',
					'default' => false,
				)
			),
		) );
	}

	/**
	 * Handle wcdp_ajax_donation_calculation AJAX request
	 */
	public function wcdp_ajax_donation_calculation() {
		if (!isset($_REQUEST['postid'])){
			$message = esc_html__('Invalid Request: postid missing. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform');
		} else if (false === check_ajax_referer( 'wcdp_ajax_nonce' . sanitize_key($_REQUEST['postid']), 'security', false )){
			$message = esc_html__('Error: invalid nonce. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform');
		} else {
			wp_send_json($this->wcdp_add_to_cart());
			return;
		}

		wp_send_json(array(
			'success'	=> false,
			'message'	=> $message,
			'recurring'	=> false,
			'reload'	=> true,
		));
	}

	/**
	 * Put Donation in Cart and return JSON response
	 */
	public function wcdp_add_to_cart(): array
	{
		$response = array(
			'success'	=> false,
			'message'	=> '',
			'recurring'	=> false,
			'reload'	=> true,
		);

		if (isset($_REQUEST['postid']) && isset($_REQUEST['wcdp-donation-amount'])) {
			$product_id = absint($_REQUEST['postid']);
			$product = wc_get_product($product_id);

			if (!$product) {
				$response['message'] = esc_html__('Invalid postid. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform');
			}

			$product_choices = $product->get_children( 'edit' );

			if (isset($_REQUEST['wcdp_products']) && is_a( $product, 'WC_Product_Variable' )) {
				$product_choice = absint($_REQUEST['wcdp_products']);
				if (in_array($product_choice, $product_choices)) {
					$product_id = $product_choice;
				}
			}

			if (WCDP_Form::is_donable( $product_id )){
				$variation_id      = 0;
				$variation         = array();
				if (isset($_REQUEST['variation_id'])) {
					$variation_id = absint($_REQUEST['variation_id']);
					foreach ($_REQUEST as $key => $value) {
						if (substr( $key, 0, 10 ) === 'attribute_') {
							$variation[sanitize_text_field($key)] = sanitize_text_field($value);
						}
					}
				}
				$min_donation_amount = get_option('wcdp_min_amount') ?? 3;
				$min_donation_amount = is_numeric($min_donation_amount) ? $min_donation_amount : 3;
				$max_donation_amount = get_option('wcdp_max_amount') ?? 5000;
				$max_donation_amount = is_numeric($max_donation_amount) ? $max_donation_amount : 5000;

				$wcdp_donation_amount = sanitize_text_field($_REQUEST['wcdp-donation-amount']);
				if (is_numeric($min_donation_amount) && $wcdp_donation_amount >= $min_donation_amount
					&& $wcdp_donation_amount < $max_donation_amount && isset(WC()->cart)){
					if (!WC()->cart->is_empty()){
						WC()->cart->empty_cart();
					}

					if (false !== WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation, array('wcdp_donation_amount' => $wcdp_donation_amount) )) {
						$response['success'] = true;
						$response['recurring'] = WCDP_Subscriptions::wcdp_contains_subscription();
						$response['reload'] = false;
					} else {
						$response['message'] = esc_html__('Could not add donation to cart.', 'wc-donation-platform');
					}
				} else {
					$response['message'] = esc_html__('Invalid donation amount. Please enter a different donation amount.', 'wc-donation-platform');
					$response['reload'] = false;
				}
			} else {
				$response['message'] = esc_html__('Invalid donation product status. Please contact our support team.', 'wc-donation-platform');
			}
		} else {
			$response['message'] = esc_html__('Invalid request. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform');
		}
		return $response;
	}

	public static function is_donable($id) {
		return apply_filters('wcdp_is_donable', get_post_meta( $id, '_donable', true) == 'yes');
	}

	public static function wcdp_generate_fieldset($args = array()) {
		$args = wp_parse_args(
			apply_filters( 'wcdp_generate_fieldset_args', $args ),
			array(
				'ul-id'				=> '',
				'ul-class'        	=> 'wcdp_options',
				'name'				=> '',
				'options'			=> array(
					'field'		=> array(
						'input-id' 			=> '',
						'input-class' 		=> '',
						'input-name'		=> '',
						'input-value'		=> '',
						'input-checked'		=> '',
						'label-class'		=> 'wcdp-button-label',
						'label-id'			=> '',
						'label-text'		=> ''
					)
				)
			)
		);

		$allowed_html = array(
			'span' => array(
				'class' => array(),
				'id' => array(),
			),
			'div' => array(
				'id' => array(),
				'class' => array(),
			),
			'input' => array(
				'id' => array(),
				'class' => array(),
				'type' => array(),
				'name' => array(),
				'step' => array(),
				'min' => array(),
				'max' => array(),
				'required' => array(),
				'value' => array(),
			),
		);

		$options = array();
		foreach ($args['options'] as $option) {
			array_push($options, wp_parse_args($option, array(
				'input-id' 			=> '',
				'input-class' 		=> '',
				'input-value'		=> '',
				'input-checked'		=> '0',
				'label-class'		=> 'wcdp-button-label',
				'label-id'			=> '',
				'label-text'		=> ''
			)));
		}

		$html = '<ul id="' . esc_attr($args['ul-id']) . '" class="' . esc_attr($args['ul-class']) . '" wcdp-name="' . esc_attr($args['name']) . '"> ';
		foreach ($options as $option) {
			$html .= '<li><input type="radio" id="' . esc_attr($option['input-id']) . '" name="' . esc_attr($args['name']) . '" class="' . esc_attr($option['input-class']) . '" value="' . esc_attr($option['input-value']) . '"';
			if ($option['input-checked'] || (isset($_REQUEST[esc_attr($args['name'])]) && $_REQUEST[esc_attr($args['name'])] == esc_attr($option['input-value']))) {
				$html .= '" checked="checked"';
			}
			$html .= ' required>';
			$html .= '<label id="' . esc_attr($option['label-id']) . '" class="' . esc_attr($option['label-class']) . '" for="' . esc_attr($option['input-id']) . '">';
			$html .=  wp_kses(apply_filters('wcdp_label_' . esc_attr($option['input-value']), $option['label-text'], $args), $allowed_html);
			$html .= '</label></li>';
		}
		$html .= '</ul>';
		return apply_filters( 'wcdp_generate_fieldset_args_html', $html, $args );
	}
}



