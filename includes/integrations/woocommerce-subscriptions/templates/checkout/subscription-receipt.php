<?php
/**
 * Change Subscription's Payment method Page
 *
 * forked from WooCommerce_Subscription\Templates
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<ul class="order_details">
    <li class="order">
        <?php
        /* translators: %s recurring donation number*/
        echo wp_kses(sprintf(esc_html__('Recurring Donation Number: %s', 'wc-donation-platform'), '<strong>' . esc_html($subscription->get_order_number()) . '</strong>'), array('strong' => true));
        ?>
    </li>
    <li class="date">
        <?php
        // translators: placeholder is the subscription's next payment date (either human readable or normal date) wrapped in <strong> tags
        echo wp_kses(sprintf(esc_html__('Next Payment Date: %s', 'woocommerce-subscriptions'), '<strong>' . esc_html($subscription->get_date_to_display('next_payment')) . '</strong>'), array('strong' => true));
        ?>
    </li>
    <li class="total">
        <?php
        // translators: placeholder is the formatted total to be paid for the subscription wrapped in <strong> tags
        echo wp_kses_post(sprintf(esc_html__('Total: %s', 'woocommerce-subscriptions'), '<strong>' . $subscription->get_formatted_order_total() . '</strong>'));
        ?>
    </li>
    <?php if ($subscription->get_payment_method_title()): ?>
        <li class="method">
            <?php
            // translators: placeholder is the display name of the payment method
            echo wp_kses(sprintf(esc_html__('Payment Method: %s', 'woocommerce-subscriptions'), '<strong>' . esc_html($subscription->get_payment_method_to_display()) . '</strong>'), array('strong' => true));
            ?>
        </li>
    <?php endif; ?>
</ul>

<?php do_action('woocommerce_receipt_' . $subscription->get_payment_method(), $subscription->get_id()); ?>

<div class="clear"></div>