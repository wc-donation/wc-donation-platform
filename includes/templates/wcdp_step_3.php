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
<?php do_action('woocommerce_checkout_after_order_review');

if ('yes' === get_option('wcdp_branding', 'no')): ?>
    <div class="wcdp-branding">
        <?php
        // translators: %s is a link to the plugin website
        echo wp_kses_post(
            sprintf(
                // translators: %s is a link to the plugin website
                esc_html__('Powered by %s', 'wc-donation-platform'),
                '<a href="https://www.wc-donation.com/?utm_source=poweredby" target="_blank">' . esc_html__('Donation Platform for WooCommerce', 'wc-donation-platform') . '</a>'
            )
        );
        ?>
    </div>
<?php endif;
