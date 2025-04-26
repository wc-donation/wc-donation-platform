<?php
/**
 * WCDP Shortcode Form
 *
 * @var string $form_id
 */

if (!defined('ABSPATH')) exit;

do_action('woocommerce_checkout_before_order_review_heading'); ?>
    <h3>
        <?php esc_html_e('Your Donation', 'wc-donation-platform'); ?>
    </h3>
<?php
do_action('woocommerce_checkout_before_order_review');
do_action('woocommerce_checkout_order_review'); ?>
    <ul class="woocommerce-info" role="alert" id="wcdp-invalid-fields">
        <li>
            <p class="wcdp-button" data-step="2">
                <?php esc_html_e('Please fill in all fields under Donor Details correctly before checking out.', 'wc-donation-platform'); ?>
                <button type="button"
                        class="button wcdp-left"><?php esc_html_e('Fix Invalid Fields', 'wc-donation-platform'); ?></button>
            </p>
        </li>
    </ul>
<?php do_action('woocommerce_checkout_after_order_review');
