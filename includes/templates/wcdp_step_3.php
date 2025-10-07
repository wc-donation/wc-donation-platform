<?php
/**
 * WCDP Shortcode Form
 *
 * @var string $form_id
 */

if (!defined('ABSPATH'))
    exit;

do_action('woocommerce_checkout_before_order_review_heading'); ?>
<h3>
    <?php esc_html_e('Your Donation', 'wc-donation-platform'); ?>
</h3>
<?php
do_action('woocommerce_checkout_before_order_review');
do_action('woocommerce_checkout_order_review'); ?>
<ul class="woocommerce-info" role="alert" id="wcdp-invalid-fields">
    <li>
        <?php esc_html_e('Please fill in all fields under Donor Details correctly before checking out.', 'wc-donation-platform'); ?>
        <button type="button" class="button wcdp-button wcdp-left"
            data-step="2"><?php esc_html_e('Fix Invalid Fields', 'wc-donation-platform'); ?></button>
    </li>
</ul>
<?php do_action('woocommerce_checkout_after_order_review');

if ('yes' === get_option('wcdp_branding', 'no')): ?>
    <div class="wcdp-branding">
        <?php
        // translators: %s is a link to the plugin website
        echo wp_kses_post(
            sprintf(
                esc_html__('Powered by %s', 'wc-donation-platform'),
                '<a href="https://www.wc-donation.com/" target="_blank">' . esc_html__('Donation Platform for WooCommerce', 'wc-donation-platform') . '</a>'
            )
        );
        ?>
    </div>
<?php endif;
