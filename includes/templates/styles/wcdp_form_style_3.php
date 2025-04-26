<?php
/**
 * WCDP Shortcode Form Style 3: steps with no header
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
<div class="wcdp-body">
    <div class="wcdp-tab" id="wcdp-step-1">
        <?php wc_get_template('wcdp_step_1.php',
            array(
                'product_id'=> $product_id,
                'has_child' => $has_child,
                'product' => $product,
                'value' => $value,
                'context' => $context,
                'form_id' => $form_id,
            ), '', WCDP_DIR . 'includes/templates/'); ?>
    </div>
    <?php /** @var TYPE_NAME $checkout */
    do_action('woocommerce_before_checkout_form', $checkout); ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout"
          action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
        <div class="wcdp-tab" id="wcdp-step-2">
            <?php wc_get_template('wcdp_step_2.php',
                array(
                    'value' => $value,
                    'context' => $context,
                ), '', WCDP_DIR . 'includes/templates/');
            ?>
            <br>
            <button type="button" class="button wcdp-button wcdp-left" data-step="1">
                <div class="wcdp-arrow wcdp-left-arrow">&laquo;</div>&nbsp;<?php esc_html_e('Back', 'wc-donation-platform'); ?>
            </button>
            <button type="button" class="button wcdp-button wcdp-right" data-step="3">
                <?php echo apply_filters('wcdp_next_button', esc_html__('Next', 'wc-donation-platform'), $value['id'], 2); ?>
                &nbsp;
                <div class="wcdp-arrow wcdp-right-arrow">&raquo;</div>
            </button>
        </div>
        <div class="wcdp-tab" id="wcdp-step-3">
            <?php wc_get_template('wcdp_step_3.php', ['form_id' => $form_id,], '', WCDP_DIR . 'includes/templates/'); ?>
            <br>
            <button type="button" class="button wcdp-button wcdp-left" data-step="2">
                <div class="wcdp-arrow wcdp-left-arrow">&laquo;</div>
                &nbsp;<?php esc_html_e('Back', 'wc-donation-platform'); ?></button>
        </div>
    </form>

    <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>
