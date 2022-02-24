<?php
/**
 * WCDP Shortcode Form
 * @var float $min_donation_amount
*/
if(!defined('ABSPATH')) exit;

$amount_layout = get_post_meta( $value['id'], 'wcdp-settings[0]', true );

//Donation Amount field
$wcdp_price_decimals = pow(10, wc_get_price_decimals() * (-1));
$max_range = (float) get_option('wcdp_max_range', 500);
$value_donation_amount = "";
$currency_symbol = get_woocommerce_currency_symbol();

if (isset($_REQUEST["wcdp-donation-amount"])) {
    $value_donation_amount = floatval($_REQUEST["wcdp-donation-amount"]);
}

$wcdp_price_field = sprintf( get_woocommerce_price_format(), '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span>', '<input type="number" class="wcdf-input-field validate-required %s" id="wcdp-donation-amount" name="wcdp-donation-amount" step="%s" min="%s" max="%s" value="%s" required>' );
/** @var float $max_donation_amount */
$wcdp_price_field = sprintf($wcdp_price_field, '%s %s', $wcdp_price_decimals, $min_donation_amount, $max_donation_amount, $value_donation_amount);

if ($value['style'] != 3 && $value['style'] != 4) {
	$wcdp_price_field = sprintf($wcdp_price_field, '', '%s');
} else {
	$wcdp_price_field = sprintf($wcdp_price_field, 'wcdp-input-style-3', '%s');
}

?>
<div id="wcdp_va_amount" class="wcdp_variation wcdp-row">
	<?php
	if ($amount_layout == 3) { //Expert design - action wcdp_custom_html_amount
		do_action('wcdp_custom_html_amount');
	} else if ($amount_layout == 2) { //Input box with range slider ?>
		<div class="wcdp-amount">
			<label for="wcdp-donation-amount">
				<?php
					$title = get_option('wcdp_contribution_title', __( 'Your Contribution', 'wc-donation-platform' ));
					echo esc_html( $title );
				?>
				<abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
			</label>
			<br>
			<?php
				$wcdp_price_field = sprintf($wcdp_price_field, 'wcdp-amount-range-field');
				echo $wcdp_price_field
			?>
			<input id="wcdp-range" type="range" step="1" min="<?php echo $min_donation_amount ?>" max="<?php echo $max_range ?>">
		</div> <?php
	} else if ($amount_layout == 1) { //Radio/Button choices
		$suggestions = json_decode( get_post_meta( $value['id'], 'wcdp-settings[1]', true ) );
		$price_format = get_woocommerce_price_format();

		$args = array(
				'ul-id'				=> 'wcdp_amount',//'wcdp_suggestions',
				'ul-class'			=> 'wcdp_options',
				'name'				=> 'donation-amount',
				'options'			=> array()
		);

		if (!is_null($suggestions)) {
			foreach ($suggestions as $suggestion){
				if (is_numeric($suggestion) && $suggestion > 0) {
					$args['options'][] = array(
							'input-id' => 'wcdp_amount_' . str_replace('.', '-', $suggestion),//'wcdp_value_' . str_replace ('.', '-', $suggestion),
							'input-value' => $suggestion,
							'label-text' => sprintf($price_format, '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span>', $suggestion)
					);
				}
			}
		}
		$wcdp_price_field = sprintf($wcdp_price_field, '');
		$args['options'][] = array(
				'input-id' => 'wcdp_value_other',
				'input-value' => '',
				'label-id' => 'wcdp_label_custom_amount',
				'label-text' => '<div id="wcdp_other">' . esc_html__('Other', 'wc-donation-platform') . '</div><div class="wcdp_cu_field">' . $wcdp_price_field . '</div>',
		); ?>
		<label class="wcdp-variation-heading" for="donation-amount">
			<?php
				$title = get_option('wcdp_choose_amount_title', __( 'Choose an amount', 'wc-donation-platform' ));
				echo esc_html( $title );
			?>
			<abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
		</label> <?php
		echo WCDP_Form::wcdp_generate_fieldset($args);
	} else { //Default: just input box ?>
		<div class="wcdp-amount">
			<label for="wcdp-donation-amount">
				<?php
				$title = get_option('wcdp_contribution_title', __( 'Your Contribution', 'wc-donation-platform' ));
				echo esc_html( $title );
				?>
				<abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
			</label>
			<br>
			<?php
				$wcdp_price_field = sprintf($wcdp_price_field, '');
				echo $wcdp_price_field;
			?>
		</div> <?php
	} ?>
</div>
<?php
