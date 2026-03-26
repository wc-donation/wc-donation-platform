<?php
/**
 * WCDP Shortcode Form Style 6: steps with no header,
 * top back arrow and next buttons only at the bottom.
 *
 * @var int $product_id
 * @var boolean $has_child
 * @var WC_Product $product
 * @var array $value
 * @var string $context
 * @var string $form_id
 * @var mixed $checkout
 */

if (!defined('ABSPATH'))
    exit;
?>
<div class="wcdp-body wcdp-style-6-body">
    <div class="wcdp-tab" id="wcdp-step-1">
        <?php wc_get_template(
            'wcdp_step_1.php',
            array(
                'product_id' => $product_id,
                'has_child' => $has_child,
                'product' => $product,
                'value' => $value,
                'context' => $context,
                'form_id' => $form_id,
            ),
            '',
            WCDP_DIR . 'includes/templates/'
        ); ?>
    </div>
    <?php do_action('woocommerce_before_checkout_form', $checkout); ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout"
        action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
        <div class="wcdp-tab" id="wcdp-step-2">
            <div class="wcdp-style-6-back-wrap">
                <button type="button" class="wcdp-step wcdp-style-6-back" data-step="1"
                    aria-label="<?php esc_attr_e('Back', 'wc-donation-platform'); ?>">
                    <img src="<?php echo esc_url(WCDP_DIR_URL . 'assets/svg/chevron-left.svg'); ?>"
                        class="wcdp-style-6-back-icon" alt="" aria-hidden="true" />
                    <span class="screen-reader-text"><?php esc_html_e('Back', 'wc-donation-platform'); ?></span>
                </button>
            </div>
            <?php wc_get_template(
                'wcdp_step_2.php',
                array(
                    'value' => $value,
                    'context' => $context,
                ),
                '',
                WCDP_DIR . 'includes/templates/'
            ); ?>
            <br>
            <div class="button-row">
                <button type="button" class="button wcdp-button wcdp-right" data-step="3">
                    <?php echo apply_filters('wcdp_next_button', esc_html__('Next', 'wc-donation-platform'), $value['id'], 2); ?>
                    &nbsp;
                    <div class="wcdp-arrow wcdp-right-arrow">&raquo;</div>
                </button>
                <div></div>
            </div>
        </div>
        <div class="wcdp-tab" id="wcdp-step-3">
            <div class="wcdp-style-6-back-wrap">
                <button type="button" class="wcdp-step wcdp-style-6-back" data-step="2"
                    aria-label="<?php esc_attr_e('Go to previous step', 'wc-donation-platform'); ?>">
                    <img src="<?php echo esc_url(WCDP_DIR_URL . 'assets/svg/chevron-left.svg'); ?>"
                        class="wcdp-style-6-back-icon" alt="" aria-hidden="true" />
                    <span class="screen-reader-text"><?php esc_html_e('Go to previous step', 'wc-donation-platform'); ?></span>
                </button>
            </div>
            <?php wc_get_template('wcdp_step_3.php', ['form_id' => $form_id], '', WCDP_DIR . 'includes/templates/'); ?>
        </div>
    </form>

    <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>