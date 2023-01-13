<?php

if(!defined('ABSPATH')) exit;

/**
 * Class WCDP_Fee_Recovery
 */
class WCDP_Fee_Recovery
{
	public function __construct() {
		$fee_recovery_enabled = get_option('wcdp_fee_recovery', 'no');
		if ($fee_recovery_enabled == 'yes') {
			//Add Checkbox to WC checkout
			add_action( 'wcdp_fee_recovery', array($this, 'add_fee_recovery_checkbox') );

			//Add transaction fee to WC cart
			add_action( 'woocommerce_cart_calculate_fees', array($this, 'add_transaction_fee_cart'), 10, 1 );
		}
		//Add Fee recovery options table to general setting
		add_action( 'woocommerce_admin_field_wcdp_fee_recovery', array($this, 'settings_fee_recovery') );
	}

	/**
	 * Add a checkbox to the WooCommerce checkout form
	 * @return void
	 */
	function add_fee_recovery_checkbox() {
		if (WCDP_Form::cart_contains_donation()) {
			?>
				<tr class="wcdp-fee-recovery-row">
					<th colspan="2">
						<label class="wcdp-fee-recovery" for="wcdp_fee_recovery">
							<input type="checkbox" id="wcdp_fee_recovery" class="wcdp-fee-recovery__input" name="wcdp_fee_recovery" value="wcdp_fee_recovery" style="display:none;"
									<?php if(isset($_POST['post_data']) && strpos($_POST['post_data'], 'wcdp_fee_recovery=wcdp_fee_recovery')): ?>
										checked="checked"
									<?php endif; ?>
							>
							<span class="wcdp-fee-recovery__body">
								<span></span>
								<span class="wcdp-fee-recovery__body-header">
									<?php esc_html_e('Yes, I want to cover the transaction fee.', 'wc-donation-platform'); ?>
								</span>
								<span class="wcdp-fee-recovery__body-cover-checkbox">
									<svg class="wcdp-fee-recovery__body-cover-checkbox--svg" viewBox="0 0 12 10">
										<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
									</svg>
								</span>
							</span>
						</label>
					</th>
				</tr>
			<?php
		}
	}

    /**
     * Add the transaction fee to cart if wcdp_fee_recovery checked
     * @param $cart WC_Cart
     * @return void
     */
	function add_transaction_fee_cart(WC_Cart $cart ) {
		if (is_admin() && !defined('DOING_AJAX') || is_null(WC()->cart)) {
			return;
		}
        //determine currently selected payment method
		if (isset($_POST['post_data']) && strpos($_POST['post_data'], 'wcdp_fee_recovery=wcdp_fee_recovery')) {
			$post_data = $_POST['post_data'];
			$pos = strpos($post_data, 'payment_method=');
			if ($pos) {
				$payment_method = sanitize_key(strtok(substr($post_data, $pos+15), '&'));
			} else {
				$payment_method = '';
			}
		} else if (isset($_POST['wcdp_fee_recovery']) && isset($_POST['payment_method'])) {
			$payment_method = sanitize_key($_POST['payment_method']);
		} else {
			return;
		}
        //Get fees for all payment options
		$wcdp_fee_recovery_values = json_decode(get_option( 'wcdp_fee_recovery_values', '{}' ), true);

        //Get fees for selected payment method
		if (isset($wcdp_fee_recovery_values[$payment_method])) {
			$value_fixed = 0;
			$value_variable = 0;
			if (isset($wcdp_fee_recovery_values[$payment_method]['fixed']) &&
				$wcdp_fee_recovery_values[$payment_method]['fixed'] > 0) {
				$value_fixed = (float) $wcdp_fee_recovery_values[$payment_method]['fixed'];
			}
			if (isset($wcdp_fee_recovery_values[$payment_method]['variable']) &&
				$wcdp_fee_recovery_values[$payment_method]['variable'] > 0 &&
				$wcdp_fee_recovery_values[$payment_method]['variable'] <= 100
			) {
				$value_variable = (float) $wcdp_fee_recovery_values[$payment_method]['variable'];
			}

			$amount = $cart->get_cart_contents_total();
			$fee = $value_fixed + $value_variable/100 * $amount;
            $cart->add_fee(__('Transaction costs', 'wc-donation-platform'), $fee);
		}
	}

	/**
	 * Add Fee recovery options table to general setting
	 * @param $value
	 * @return void
	 */
	function settings_fee_recovery($value) {
		?>
		<script>
			(function($) {
				$(window).bind("load", function() {
					show_hide_fee_recovery();
				});

				$('#wcdp_fee_recovery').on('change', function(){
					show_hide_fee_recovery();
				});

				function show_hide_fee_recovery() {
					if ( $('#wcdp_fee_recovery').prop('checked') ) {
						$( '.show_if_fee_recovery' ).show();
					} else {
						$( '.show_if_fee_recovery' ).hide();
					}
				}
			})(jQuery);
		</script>
		<tr class="show_if_fee_recovery">
			<th scope="row" class="titledesc"></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$available_payment_methods = WC()->payment_gateways->get_available_payment_gateways();
					if (empty($available_payment_methods)):
						esc_html_e('No active payment methods found.', 'wc-donation-platform');
					else:
						esc_html_e('Enter the fixed transaction costs in the left field and the percentage transaction costs in the right field.', 'wc-donation-platform');
					?>
						<table>
							<?php
							$wcdp_fee_recovery_values = json_decode(get_option( 'wcdp_fee_recovery_values', '{}' ), true);
							foreach( $available_payment_methods as $method ) {
								$value_fixed = 0;
								$value_variable = 0;
								if (isset($wcdp_fee_recovery_values[$method->id])) {
									if (isset($wcdp_fee_recovery_values[$method->id]['fixed']) &&
											$wcdp_fee_recovery_values[$method->id]['fixed'] >= 0) {
										$value_fixed = $wcdp_fee_recovery_values[$method->id]['fixed'];
									}
									if (isset($wcdp_fee_recovery_values[$method->id]['variable']) &&
											$wcdp_fee_recovery_values[$method->id]['variable'] >= 0 &&
											$wcdp_fee_recovery_values[$method->id]['variable'] <= 100
									) {
										$value_variable = $wcdp_fee_recovery_values[$method->id]['variable'];
									}
								}
								?>
								<tr>
									<td>
										<label><?php echo esc_html($method->get_title()); ?></label>
									</td>
									<td>
										<?php echo get_woocommerce_currency_symbol(); ?>
										<input
												name="<?php echo esc_attr( 'wcdp_fixed_' . $method->id  ); ?>"
												id="<?php echo esc_attr( 'wcdp_fixed_' . $method->id ); ?>"
												type="number"
												style="width: 100px;"
												value="<?php echo esc_attr($value_fixed); ?>"
												placeholder="<?php esc_html_e( 'Fixed', 'wc-donation-platform' ); ?>"
												min="0"
												step="<?php echo esc_attr($value['step']); ?>"
										/> <?php esc_html_e('(fixed)', 'wc-donation-platform'); ?>&nbsp;&nbsp;&nbsp;
										<input
												name="<?php echo esc_attr( 'wcdp_variable_'. $method->id ); ?>"
												id="<?php echo esc_attr( 'wcdp_variable_' . $method->id ); ?>"
												type="number"
												style="width: 100px;"
												value="<?php echo esc_attr($value_variable); ?>"
												placeholder="<?php esc_html_e( 'Variable', 'wc-donation-platform' ); ?>"
												min="0"
												max="100"
												step="any"
										/>% <?php esc_html_e('(variable)', 'wc-donation-platform'); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
					<?php endif; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}
}
