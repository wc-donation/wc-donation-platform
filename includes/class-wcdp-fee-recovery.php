<?php

if (!defined('ABSPATH')) exit;

/**
 * Class WCDP_Fee_Recovery
 */
class WCDP_Fee_Recovery
{
    public function __construct()
    {
        if (get_option('wcdp_fee_recovery', 'no') !== 'yes') {
            return;
        }

        $action = get_option('wcdp_compatibility_mode', 'no') === 'no'
            ? 'wcdp_fee_recovery'
            : 'woocommerce_review_order_after_cart_contents';

        add_action($action, [$this, 'add_fee_recovery_checkbox']);
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_transaction_fee_cart'], 10, 1);
        add_action('woocommerce_admin_field_wcdp_fee_recovery', [$this, 'settings_fee_recovery']);
    }

    /**
     * Add a checkbox to the WooCommerce checkout form
     */
    public function add_fee_recovery_checkbox(): void
    {
        if (!WCDP_Form::cart_contains_donation()) {
            return;
        }
        ?>
        <tr class="wcdp-fee-recovery-row">
            <th colspan="2">
                <label class="wcdp-fee-recovery" for="wcdp_fee_recovery">
                    <input type="checkbox" id="wcdp_fee_recovery" class="wcdp-fee-recovery__input"
                           name="wcdp_fee_recovery" value="wcdp_fee_recovery" style="display:none;"
                        <?php checked($this->is_fee_recovery_checked()); ?>
                    >
                    <span class="wcdp-fee-recovery__body">
                        <span></span>
                        <span class="wcdp-fee-recovery__body-header">
                            <?php esc_html_e('Yes, I want to cover the transaction fee.', 'wc-donation-platform'); ?>
                        </span>
                        <span class="wcdp-fee-recovery__body-cover-checkbox">
                            <svg class="wcdp-fee-recovery__body-cover-checkbox--svg" viewBox="0 0 12 10">
                                <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                            </svg>
                        </span>
                    </span>
                </label>
            </th>
        </tr>
        <?php
    }

    /**
     * Add the transaction fee to the cart if the checkbox is checked
     */
    public function add_transaction_fee_cart(WC_Cart $cart): void
    {
        if (is_admin() && !defined('DOING_AJAX') || is_null(WC()->cart)) {
            return;
        }

        $payment_method = $this->get_selected_payment_method();
        if (!$payment_method) {
            return;
        }

        $fees = json_decode(get_option('wcdp_fee_recovery_values', '{}'), true);
        if (!isset($fees[$payment_method])) {
            return;
        }

        $value_fixed = isset($fees[$payment_method]['fixed']) ? max(0, (float)$fees[$payment_method]['fixed']) : 0;
        $value_variable = isset($fees[$payment_method]['variable'])
            ? min(100, max(0, (float)$fees[$payment_method]['variable']))
            : 0;

        $amount = $cart->get_cart_contents_total();
        $fee = $value_fixed + ($value_variable / 100 * $amount);
        $fee = apply_filters('wcdp_fee_amount', $fee, $payment_method, $cart);

        $cart->add_fee(__('Transaction costs', 'wc-donation-platform'), $fee);
    }

    /**
     * Add Fee recovery options table to general settings
     */
    public function settings_fee_recovery($value): void
    {
        ?>
        <script>
            (function ($) {
                function toggleFeeRecovery() {
                    $('.show_if_fee_recovery').toggle($('#wcdp_fee_recovery').prop('checked'));
                }
                $(document).ready(toggleFeeRecovery);
                $('#wcdp_fee_recovery').on('change', toggleFeeRecovery);
            })(jQuery);
        </script>
        <tr class="show_if_fee_recovery">
            <th scope="row" class="titledesc"></th>
            <td class="forminp forminp-checkbox">
                <fieldset>
                    <?php
                    $payment_methods = WC()->payment_gateways->get_available_payment_gateways();
                    if (!$payment_methods) {
                        esc_html_e('No active payment methods found.', 'wc-donation-platform');
                        return;
                    }

                    esc_html_e('Enter the fixed transaction costs in the left field and the percentage transaction costs in the right field.', 'wc-donation-platform');
                    $fees = json_decode(get_option('wcdp_fee_recovery_values', '{}'), true);
                    ?>
                    <table>
                        <?php foreach ($payment_methods as $method):
                            $fee_data = $fees[$method->id] ?? [];
                            $value_fixed = max(0, (float)($fee_data['fixed'] ?? 0));
                            $value_variable = min(100, max(0, (float)($fee_data['variable'] ?? 0)));
                            ?>
                            <tr>
                                <td><label><?php echo esc_html($method->get_title()); ?></label></td>
                                <td>
                                    <?php echo get_woocommerce_currency_symbol(); ?>
                                    <input name="wcdp_fixed_<?php echo esc_attr($method->id); ?>"
                                           id="wcdp_fixed_<?php echo esc_attr($method->id); ?>"
                                           type="number" style="width: 100px;"
                                           value="<?php echo esc_attr($value_fixed); ?>"
                                           placeholder="<?php esc_html_e('Fixed', 'wc-donation-platform'); ?>"
                                           min="0" step="<?php echo esc_attr($value['step']); ?>" />
                                    <?php esc_html_e('(fixed)', 'wc-donation-platform'); ?>&nbsp;&nbsp;&nbsp;
                                    <input name="wcdp_variable_<?php echo esc_attr($method->id); ?>"
                                           id="wcdp_variable_<?php echo esc_attr($method->id); ?>"
                                           type="number" style="width: 100px;"
                                           value="<?php echo esc_attr($value_variable); ?>"
                                           placeholder="<?php esc_html_e('Variable', 'wc-donation-platform'); ?>"
                                           min="0" max="100" step="any" />
                                    % <?php esc_html_e('(variable)', 'wc-donation-platform'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </fieldset>
            </td>
        </tr>
        <?php
    }

    /**
     * Check if the fee recovery checkbox was checked
     */
    private function is_fee_recovery_checked(): bool
    {
        return isset($_POST['post_data']) && strpos($_POST['post_data'], 'wcdp_fee_recovery=wcdp_fee_recovery') !== false;
    }

    /**
     * Get the selected payment method from request data
     */
    private function get_selected_payment_method(): ?string
    {
        if ($this->is_fee_recovery_checked()) {
            preg_match('/payment_method=([\w-]+)/', $_POST['post_data'], $matches);
            return isset($matches[1]) ? sanitize_key($matches[1]) : null;
        }

        return isset($_POST['wcdp_fee_recovery'], $_POST['payment_method']) ? sanitize_key($_POST['payment_method']) : null;
    }
}
