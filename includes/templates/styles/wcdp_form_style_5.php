<?php
/**
 * WCDP Shortcode Form Style 5: 3 steps with banner header
 * @var int $product_id
 * @var boolean $has_child
 * @var WC_Product $product
 * @var array $value
 * @var string $context
 * @var $checkout
 * @var string $form_id
 */

if (!defined('ABSPATH')) exit;
?>

    <div class="wcdp-steps-wrapper">
        <div class="wcdp-style5 wcdp-step wcdp-style5-active" id="wcdp-style5-step-1" data-step="1">
            <span><?php esc_html_e('1. Amount', 'wc-donation-platform') ?></span>
        </div>
        <div class="wcdp-style5 wcdp-step" id="wcdp-style5-step-2" data-step="2">
            <span><?php esc_html_e('2. Details', 'wc-donation-platform') ?></span>
        </div>
        <div class="wcdp-style5 wcdp-step" id="wcdp-style5-step-3" data-step="3">
            <span><?php esc_html_e('3. Payment', 'wc-donation-platform') ?></span>
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
