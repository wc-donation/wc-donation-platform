<?php
/**
 * WCDP Shortcode Form
 * @var float $min_donation_amount
 * @var float $max_donation_amount
 * @var int $product_id
 * @var WC_Product $product
 * @var array $value
 * @var string $form_id
 */
if (!defined('ABSPATH'))
    exit;

$amount_layout = get_post_meta($product_id, 'wcdp-settings[0]', true);

//Donation Amount field
$wcdp_price_decimals = apply_filters('wcdp_donation_amount_decimals', pow(10, wc_get_price_decimals() * (-1)));
$max_range = (float) get_option('wcdp_max_range', 500);
$value_donation_amount = "";
$currency_symbol = get_woocommerce_currency_symbol();

//Preselected donation amount
if (isset($_REQUEST["wcdp-donation-amount"])) {
    $value_donation_amount = floatval($_REQUEST["wcdp-donation-amount"]);
} else {
    $value_donation_amount = apply_filters('wcdp_default_amount', WCDP_Form::get_default_price($product), $product);
    if (!WCDP_Form::check_donation_amount($value_donation_amount, $product_id)) {
        $value_donation_amount = '';
    }
}

$wcdp_price_field = sprintf(get_woocommerce_price_format(), '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span>', '<input type="number" class="wcdp-input-field wcdp_donation_amount_field wcdp-donation-amount validate-required %s" data-formid="' . $form_id . '" id="wcdp-donation-amount" name="wcdp-donation-amount" step="%s" min="%s" max="%s" value="%s" required>');
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
        do_action('wcdp_custom_html_amount_' . $value['id']);
    } else if ($amount_layout == 2) { //Input box with range slider ?>
            <div class="wcdp-amount">
                <label for="wcdp-donation-amount">
                    <?php
                    $title = get_option('wcdp_contribution_title', __('Your Contribution', 'wc-donation-platform'));
                    echo esc_html($title);
                    ?>
                    <abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
                </label>
                <br>
                <?php
                $wcdp_price_field = sprintf($wcdp_price_field, 'wcdp-amount-range-field');
                echo $wcdp_price_field
                    ?>
                <input id="<?php echo $form_id ?>wcdp-range" name="wcdp-range" class="wcdp-range" aria-hidden="true"
                    type="range" step="<?php echo (float) apply_filters('wcdp_range_slider_steps', 1); ?>"
                    min="<?php echo $min_donation_amount ?>" max="<?php echo $max_range ?>">
            </div> <?php
    } else if ($amount_layout == 1) { //Radio/Button choices
        $suggestions = json_decode(get_post_meta($value['id'], 'wcdp-settings[1]', true));
        $price_format = get_woocommerce_price_format();

        $args = array(
            'ul-id' => 'wcdp_amount',
            'ul-class' => 'wcdp_options wcdp_amount',
            'name' => 'donation-amount',
            'options' => array()
        );

        $option_already_checked = false;

        if (!is_null($suggestions)) {
            foreach ($suggestions as $suggestion) {
                $suggestion = apply_filters('wcdp_suggestion', $suggestion, $product);
                if (is_numeric($suggestion) && $suggestion > 0 && $suggestion >= $min_donation_amount && $suggestion <= $max_donation_amount) {
                    $decimals = (floor($suggestion) == $suggestion) ? 0 : wc_get_price_decimals();
                    $option = array(
                        'input-id' => 'amount_' . str_replace('.', '-', $suggestion),
                        'input-value' => $suggestion,
                        'input-class' => 'wcdp_amount_suggestion wcdp_amount_' . str_replace('.', '-', $suggestion),
                        'label-text' => wc_price($suggestion, ['decimals' => $decimals]),
                    );
                    if ($suggestion == $value_donation_amount) {
                        $option['input-checked'] = true;
                        $option_already_checked = true;
                    }
                    $args['options'][] = $option;
                }
            }
        }
        $wcdp_price_field = sprintf($wcdp_price_field, '');
        $option = array(
            'input-id' => 'wcdp_value_other',
            'input-value' => 'other',
            'input-class' => 'wcdp_value_other',
            'label-id' => 'label_custom_amount',
            'label-class' => 'wcdp_label_custom_amount',
            'label-text' => '<div id="wcdp_other" class="wcdp_other">' . apply_filters('wcdp_other_label', esc_html__('Other', 'wc-donation-platform')) . '</div><div class="wcdp_cu_field">' . $wcdp_price_field . '</div>',
        );
        if (!$option_already_checked && $value_donation_amount) {
            $option['input-checked'] = true;
        }
        $args['options'][] = $option; ?>
                <label class="wcdp-variation-heading" for="donation-amount">
                <?php
                $title = get_option('wcdp_choose_amount_title', __('Choose an amount', 'wc-donation-platform'));
                echo esc_html($title);
                ?>
                    <abbr class="required" title="<?php esc_html_e('required', 'wc-donation-platform'); ?>">*</abbr>
                </label> <?php
                echo WCDP_Form::wcdp_generate_fieldset($args, null, $form_id);
    } else { //Default: just input box ?>
                <div class="wcdp-amount">
                    <label for="wcdp-donation-amount">
                    <?php
                    $title = get_option('wcdp_contribution_title', __('Your Contribution', 'wc-donation-platform'));
                    echo esc_html($title);
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
