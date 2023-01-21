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
		add_action( 'wp_enqueue_scripts', array($this, 'wcdp_register_scripts'), 15 );

		//Register Gutenberg Block
		add_action( 'init',  array($this, 'wcdp_block_init') );

		//Handle AJAx requests to put Donation Product in cart
		add_action( 'wp_ajax_wcdp_ajax_donation_calculation', array($this, 'wcdp_ajax_donation_calculation') );
		add_action( 'wp_ajax_nopriv_wcdp_ajax_donation_calculation', array($this, 'wcdp_ajax_donation_calculation') );
	}

    /**
     * Register CSS & JS Files
     */
    public function wcdp_register_scripts() {
        //JS Dependencies
        $jsdeps = array(
            'jquery',
            'selectWoo',
            'wc-checkout',
            'select2',
            'wc-cart',
        );
        //Require wc-password-strength-meter when necessary
        if ( 'yes' === get_option('woocommerce_enable_signup_and_login_from_checkout') && 'no' === get_option( 'woocommerce_registration_generate_password' ) && ! is_user_logged_in() ) {
            $jsdeps[] = 'wc-password-strength-meter';
        }

        wp_register_style( 'wc-donation-platform',
            WCDP_DIR_URL . 'assets/css/wcdp.min.css',
            array('select2',),
            WCDP_VERSION,
        );
        wp_register_script( 'wc-donation-platform',
            WCDP_DIR_URL . 'assets/js/wcdp.min.js',
            $jsdeps,
            WCDP_VERSION
        );

        //Only enqueue if needed
        if($this->wcdp_has_donation_form()) {
            $this->wcdp_enqueue_scripts();
        }
    }

    /**
     * Enqueue CSS & JS Files
     * @return void
     */
    private static function wcdp_enqueue_scripts( ) {
        //Dependencies
        $cssdeps = array(
                'select2',
                'wc-donation-platform',
            );
        foreach ($cssdeps as $cssdep) {
            wp_enqueue_style( $cssdep);
        }

        $jsdeps = array(
            'wc-donation-platform',
            'jquery',
            'selectWoo',
            'wc-checkout',
            'select2',
            'wc-cart',
        );
        //Require wc-password-strength-meter when necessary
        if ( 'yes' === get_option('woocommerce_enable_signup_and_login_from_checkout') && 'no' === get_option( 'woocommerce_registration_generate_password' ) && ! is_user_logged_in() ) {
            $jsdeps[] = 'wc-password-strength-meter';
        }
        foreach ($jsdeps as $jsdep) {
            wp_enqueue_script( $jsdep);
        }
    }

    /**
     * Return html of Donation Form
     * @param string $atts
     * @return string
     */
    public static function wcdp_donation_form_shortcode($atts = array()): string
    {
        return WCDP_Form::wcdp_donation_form($atts, false);
    }

	/**
	 * Return html of Donation Form
	 *
	 * @param $value array form attributes
	 * @param $is_internal bool
	 * @return string html of donation form
	 */
    public static function wcdp_donation_form(array $value, bool $is_internal): string
	{
        //Only one donation form per page
        static $no_donation_form_yet = true;
        if ( !$no_donation_form_yet || (!$is_internal && is_product())) {
            return '<p class="wcdp-error-message">' . esc_html__('Only one donation form per page allowed','wc-donation-platform' ) . '</p>';
        }
        $no_donation_form_yet = false;
        if (!defined('WCDP_FORM')) {
            define('WCDP_FORM', true);
        }

        $value = shortcode_atts( array(
            'id'			    => 0,
            'style'			    => 1,
            'popup'             => 0,
            'button'    		=> 1,
            'title'             => 0,
            'description'       => 0,
            'short_description' => 0,
            'image'             => 0,
			'className'			=> '',
            'label'             => __("Donate now!", "wc-donation-platform"),
        ), $value );
        $product_id = $value['id'];

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
			global $product;
			if (is_null($product)) {
				$product = wc_get_product($id);
			}

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
				//WC()->cart->empty_cart();
                $has_child = is_a( $product, 'WC_Product_Variable' ) && $product->has_child();

                //enqueue woocommerce variation js
                if ($has_child) {
                    wp_enqueue_script('wc-add-to-cart-variation');
                }

                WCDP_Form::wcdp_enqueue_scripts();
                require_once 'templates/wcdp_form.php';
            }
        }

        $r = ob_get_contents();
        ob_end_clean();

        if ($value['popup']) {
            add_action( 'wp_footer', function() use( $r ) {
                echo $r;
            } );
            if ($value['button']) {
                return '<p>
                    <a href="#wcdp-form">
                        <button id="wcdp-button" type="button" class="button wcdp-modal-open">'
                            . esc_html($value['label']) .
                        '</button>
                    </a>
                </p>';
            }
            return '';
        } else {
            return $r;
        }
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
    public static function wcdp_has_donation_form(): bool
    {
        if (defined('WCDP_FORM')) {
            return WCDP_FORM;
        }

        global $post;
        if (is_product() || is_checkout()
			|| has_block( 'wc-donation-platform/wcdp' )
            || (!is_null($post)
				&& (has_shortcode( $post->post_content, 'wcdp_donation_form') || has_shortcode( $post->post_content, 'product_page'))
			)
		){
            define('WCDP_FORM', true);
            return true;
        }
        if (!is_null($post)) {
            define('WCDP_FORM', false);
        }
        return false;
    }

	/**
	 * Return true if the cart contains a donation product
	 * @return bool
	 */
	public static function cart_contains_donation(): bool
	{
		if ( ! empty( WC()->cart->get_cart_contents() ) ) {
			foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
				if ( isset($cart_item['product_id']) && WCDP_Form::is_donable( $cart_item['product_id'] ) ) {
					return true;
				}
			}
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
					'enum' => array( 1, 2, 3, 4, 5 ),
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

			//Grouped Product
			$product_choices = $product->get_children();
			if (isset($_REQUEST['wcdp_products']) && is_a( $product, 'WC_Product_Grouped' )) {
				$product_choice = absint($_REQUEST['wcdp_products']);
				if (in_array($product_choice, $product_choices)) {
					$product_id = $product_choice;
				}
			}

			if (WCDP_Form::is_donable( $product_id )){
				//Variable Product
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

				$wcdp_donation_amount = sanitize_text_field($_REQUEST['wcdp-donation-amount']);
				if ($this->check_donation_amount($wcdp_donation_amount, $product_id) && isset(WC()->cart)){
					$this->maybe_empty_cart($product_id, $product_choices);
					if (false !== WC()->cart->add_to_cart($product_id, 1, $variation_id, $variation, array('wcdp_donation_amount' => $wcdp_donation_amount) )) {
						$response['success'] = true;
						$response['recurring'] = WCDP_Integrator::wcdp_contains_subscription($product);
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

	/**
	 * Remove other cart contents if wcdp_multiple_in_cart is enabled
	 * if the donation product is already in cart, remove it
	 * @param int $product_id
	 * @param array $product_choices
	 * @return void
	 */
	private function maybe_empty_cart(int $product_id, array $product_choices) {
		if (!WC()->cart->is_empty()){
			if (get_option('wcdp_multiple_in_cart', 'no') === 'yes') {
				$cart_contents = WC()->cart->get_cart_contents();
				foreach ($cart_contents as $cart_content) {
					if ($cart_content['product_id'] == $product_id || in_array($cart_content['product_id'], $product_choices)) {
						WC()->cart->remove_cart_item($cart_content['key']);
					}
				}
			} else {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Check if specified donation amount is valid
	 * @param $donation_amount
     * @param $product_id int
	 * @return bool
	 */
	public static function check_donation_amount($donation_amount, int $product_id = 0): bool
	{
		$min_donation_amount = (float) apply_filters('wcdp_min_amount', get_option('wcdp_min_amount', 3), $product_id);
		$max_donation_amount = (float) apply_filters('wcdp_max_amount', get_option('wcdp_max_amount', 50000), $product_id);
		return $donation_amount >= $min_donation_amount && $donation_amount <= $max_donation_amount;
	}

	public static function is_donable($id) {
		return apply_filters('wcdp_is_donable', get_post_meta( $id, '_donable', true) == 'yes');
	}

	public static function wcdp_generate_fieldset($args = array(), $product = null) {
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
			$options[] = wp_parse_args($option, array(
				'input-id' => '',
				'input-class' => '',
				'input-value' => '',
				'input-checked' => '0',
				'label-class' => 'wcdp-button-label',
				'label-id' => '',
				'label-text' => ''
			));
		}

		$html = '<ul id="' . esc_attr($args['ul-id']) . '" class="' . esc_attr($args['ul-class']) . '" wcdp-name="' . esc_attr($args['name']) . '"> ';
		foreach ($options as $option) {
			$html .= '<li><input type="radio" id="' . esc_attr($option['input-id']) . '" name="' . esc_attr($args['name']) . '" class="' . esc_attr($option['input-class']) . '" value="' . esc_attr($option['input-value']) . '"';
			if ($option['input-checked']
				|| (isset($_REQUEST[esc_attr($args['name'])]) && $_REQUEST[esc_attr($args['name'])] == esc_attr($option['input-value']))
				|| (!isset($_REQUEST[esc_attr($args['name'])]) && !is_null($product) && $product->get_variation_default_attribute(esc_attr($args['name'])) == esc_attr($option['input-value']))
			) {
				$html .= ' checked="checked"';
			}
			$html .= ' required>';
			$html .= '<label id="' . esc_attr($option['label-id']) . '" class="' . esc_attr($option['label-class']) . '" for="' . esc_attr($option['input-id']) . '">';
			$html .=  wp_kses(apply_filters('wcdp_label_' . esc_attr($option['input-value']), $option['label-text'], $args), $allowed_html);
			$html .= '</label></li>';
		}
		$html .= '</ul>';
		return apply_filters( 'wcdp_generate_fieldset_args_html', $html, $args );
	}

	/**
	 * echo css block with wcdp color variables
	 * @return void
	 */
	public static function define_ccs_variables() {
		$wcdp_main_color = get_option('wcdp_secondary_color', '#30bf76');
		$wcdp_main_color_2 = get_option('wcdp_main_color', '#00753a');
		$wcdp_main_color_3 = get_option('wcdp_error_color', '#de0000');
		?>
		<style id="wcdp-css">
			:root{
				--wcdp-main: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
				--wcdp-main-2: <?php echo sanitize_hex_color($wcdp_main_color_2); ?>;
				--wcdp-main-3: <?php echo sanitize_hex_color($wcdp_main_color_3); ?>;
				--wcdp-step-2: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
				--wcdp-step-3: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
				--label-inactive: LightGray;
				--label-inactive-hover: #b5b5b5;
				--label-text: black;
				--label-text-checked: white;
				--background-color: white;
				--overlay-color: rgba(0, 0, 0, 0.8);
				--controls: black;
			}
		</style>
		<?php
	}
}



