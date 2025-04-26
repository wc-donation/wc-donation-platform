<?php
/**
 * WCDP Donation Form
 * @var int $product_id
 * @var boolean $has_child
 * @var WC_Product $product
 * @var array $value
 * @var string $context
 * @var string $form_id
 * @var $checkout
*/

if (!defined('ABSPATH')) exit;
?>

    <div class="wcdp-header">
        <div id="wcdp-progress-bar-background" class="wcdp-progress-bar-background"></div>
        <div id="wcdp-progress-bar" class="wcdp-progress-bar"></div>
        <div class="wcdp-step wcdp-header-step-1" id="wcdp-header-step-1" data-step="1">
            <?php esc_html_e('Amount', 'wc-donation-platform'); ?>
        </div>
        <div class="wcdp-step wcdp-header-step-2" id="wcdp-header-step-2" data-step="2">
            <?php esc_html_e('Details', 'wc-donation-platform'); ?>
        </div>
        <div class="wcdp-step wcdp-header-step-3" id="wcdp-header-step-3" data-step="3">
            <?php esc_html_e('Payment', 'wc-donation-platform'); ?>
        </div>
    </div>

<?php
wc_get_template('wcdp_form_style_3.php',
    array(
        'product_id'=> $product_id,
        'has_child' => $has_child,
        'product' => $product,
        'value' => $value,
        'context' => $context,
        'checkout' => $checkout,
        'form_id' => $form_id,
    ), '', WCDP_DIR . 'includes/templates/styles/');
