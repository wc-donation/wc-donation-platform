<?php

if (!defined('ABSPATH'))
    exit;

/**
 * Class WCDP_Donation_Upsells
 */
class WCDP_Donation_Upsells
{
    const OPTION_ENABLED = 'wcdp_donation_upsells';
    const OPTION_VALUES = 'wcdp_donation_upsell_values';

    public function __construct()
    {
        add_action('woocommerce_admin_field_wcdp_donation_upsells', array($this, 'settings_donation_upsells'));
        add_action('woocommerce_update_options_wc-donation-platform', array($this, 'save_settings'), 5);

        if (!$this->is_enabled()) {
            return;
        }

        $checkout_hook = apply_filters(
            'wcdp_donation_upsell_checkout_hook',
            'woocommerce_review_order_before_order_total'
        );

        add_action($checkout_hook, array($this, 'render_checkout_upsells'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_donation_fees'), 20, 1);
    }

    /**
     * Return the supported donation upsell types.
     *
     * @return array
     */
    public static function get_supported_types(): array
    {
        $decimals = pow(10, wc_get_price_decimals() * (-1));

        $types = array(
            'round_up' => array(
                'title' => __('Round up', 'wc-donation-platform'),
                'description' => __('Round the current cart value up to the next full currency unit.', 'wc-donation-platform'),
                'calculator' => 'calculate_round_up_amount',
                'defaults' => array(
                    'enabled' => 'no',
                    'label' => __('Yes, round up my order and add {amount} as a donation.', 'wc-donation-platform'),
                    'fee_name' => __('Round up donation', 'wc-donation-platform'),
                ),
                'fields' => array(),
            ),
            'fixed_percentage' => array(
                'title' => __('Add percentage + fixed donation amount', 'wc-donation-platform'),
                'description' => __('Add a fixed donation plus a percentage of the cart value.', 'wc-donation-platform'),
                'calculator' => 'calculate_fixed_percentage_amount',
                'defaults' => array(
                    'enabled' => 'no',
                    'label' => __('Yes, add {amount} as a donation.', 'wc-donation-platform'),
                    'fee_name' => __('Donation', 'wc-donation-platform'),
                    'fixed_amount' => '1',
                    'percentage' => '5',
                ),
                'fields' => array(
                    'fixed_amount' => array(
                        'label' => __('Fixed donation amount', 'wc-donation-platform'),
                        'type' => 'number',
                        'min' => 0,
                        'step' => $decimals,
                    ),
                    'percentage' => array(
                        'label' => __('Percentage of cart value', 'wc-donation-platform'),
                        'type' => 'number',
                        'min' => 0,
                        'max' => 100,
                        'step' => 'any',
                    ),
                ),
            ),
        );

        return apply_filters('wcdp_donation_upsell_types', $types);
    }

    /**
     * Render donation upsell settings in WooCommerce > Settings > Donations.
     *
     * @param array $value
     * @return void
     */
    public function settings_donation_upsells($value): void
    {
        $types = self::get_supported_types();
        ?>
        <script>
            (function ($) {
                function toggleDonationUpsells() {
                    $('.show_if_wcdp_donation_upsells').toggle($('#wcdp_donation_upsells').prop('checked'));
                }

                $(document).ready(toggleDonationUpsells);
                $('#wcdp_donation_upsells').on('change', toggleDonationUpsells);
            })(jQuery);
        </script>
        <tr class="show_if_wcdp_donation_upsells">
            <th scope="row" class="titledesc"></th>
            <td class="forminp">
                <fieldset>
                    <p>
                        <?php esc_html_e('Configure one or more optional checkout donations. Use {amount} in the checkbox wording to show the calculated donation amount to the customer.', 'wc-donation-platform'); ?>
                    </p>
                    <table class="widefat striped" style="max-width: 980px;">
                        <tbody>
                            <?php foreach ($types as $type_id => $type):
                                $settings = $this->get_type_settings($type_id);
                                ?>
                                <tr>
                                    <td style="width: 240px; vertical-align: top;">
                                        <strong><?php echo esc_html($type['title']); ?></strong>
                                        <p class="description"><?php echo esc_html($type['description']); ?></p>
                                    </td>
                                    <td>
                                        <p>
                                            <label>
                                                <input type="checkbox"
                                                    name="wcdp_donation_upsell_enabled_<?php echo esc_attr($type_id); ?>"
                                                    value="yes" <?php checked($settings['enabled'], 'yes'); ?>>
                                                <?php esc_html_e('Enable this upsell type', 'wc-donation-platform'); ?>
                                            </label>
                                        </p>

                                        <p>
                                            <label for="wcdp_donation_upsell_label_<?php echo esc_attr($type_id); ?>">
                                                <?php esc_html_e('Checkout checkbox wording', 'wc-donation-platform'); ?>
                                            </label>
                                            <br>
                                            <input type="text" class="regular-input"
                                                id="wcdp_donation_upsell_label_<?php echo esc_attr($type_id); ?>"
                                                name="wcdp_donation_upsell_label_<?php echo esc_attr($type_id); ?>"
                                                value="<?php echo esc_attr($settings['label']); ?>">
                                        </p>

                                        <p>
                                            <label for="wcdp_donation_upsell_fee_name_<?php echo esc_attr($type_id); ?>">
                                                <?php esc_html_e('Fee name', 'wc-donation-platform'); ?>
                                            </label>
                                            <br>
                                            <input type="text" class="regular-input"
                                                id="wcdp_donation_upsell_fee_name_<?php echo esc_attr($type_id); ?>"
                                                name="wcdp_donation_upsell_fee_name_<?php echo esc_attr($type_id); ?>"
                                                value="<?php echo esc_attr($settings['fee_name']); ?>">
                                        </p>

                                        <?php foreach ($type['fields'] as $field_key => $field):
                                            $field_name = 'wcdp_donation_upsell_' . $field_key . '_' . $type_id;
                                            ?>
                                            <p>
                                                <label for="<?php echo esc_attr($field_name); ?>">
                                                    <?php echo esc_html($field['label']); ?>
                                                </label>
                                                <br>
                                                <?php if ($field_key === 'fixed_amount') {
                                                    echo esc_html(get_woocommerce_currency_symbol()) . ' ';
                                                } ?>
                                                <input type="<?php echo esc_attr($field['type']); ?>"
                                                    style="width: 140px;"
                                                    id="<?php echo esc_attr($field_name); ?>"
                                                    name="<?php echo esc_attr($field_name); ?>"
                                                    min="<?php echo esc_attr($field['min']); ?>"
                                                    <?php if (isset($field['max'])): ?>
                                                        max="<?php echo esc_attr($field['max']); ?>"
                                                    <?php endif; ?>
                                                    step="<?php echo esc_attr($field['step']); ?>"
                                                    value="<?php echo esc_attr($settings[$field_key]); ?>">
                                                <?php if ($field_key === 'percentage') {
                                                    esc_html_e('% of cart value', 'wc-donation-platform');
                                                } ?>
                                            </p>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </fieldset>
            </td>
        </tr>
        <?php
    }

    /**
     * Save donation upsell settings.
     *
     * @return void
     */
    public function save_settings(): void
    {
        $values = array();

        foreach (self::get_supported_types() as $type_id => $type) {
            $defaults = $type['defaults'];
            $values[$type_id] = array(
                'enabled' => isset($_POST['wcdp_donation_upsell_enabled_' . $type_id]) ? 'yes' : 'no',
                'label' => isset($_POST['wcdp_donation_upsell_label_' . $type_id])
                    ? sanitize_text_field(wp_unslash($_POST['wcdp_donation_upsell_label_' . $type_id]))
                    : $defaults['label'],
                'fee_name' => isset($_POST['wcdp_donation_upsell_fee_name_' . $type_id])
                    ? sanitize_text_field(wp_unslash($_POST['wcdp_donation_upsell_fee_name_' . $type_id]))
                    : $defaults['fee_name'],
            );

            foreach ($type['fields'] as $field_key => $field) {
                $post_key = 'wcdp_donation_upsell_' . $field_key . '_' . $type_id;
                $raw_value = isset($_POST[$post_key]) ? wp_unslash($_POST[$post_key]) : $defaults[$field_key];
                $numeric_value = (float) $raw_value;

                if (isset($field['min'])) {
                    $numeric_value = max((float) $field['min'], $numeric_value);
                }
                if (isset($field['max'])) {
                    $numeric_value = min((float) $field['max'], $numeric_value);
                }

                $values[$type_id][$field_key] = (string) $numeric_value;
            }
        }

        update_option(self::OPTION_VALUES, wp_json_encode($values), 'yes');
    }

    /**
     * Render the enabled donation upsells on checkout.
     *
     * @return void
     */
    public function render_checkout_upsells(): void
    {
        if (!$this->should_render_checkout_upsells()) {
            return;
        }

        foreach ($this->get_enabled_types() as $type_id => $type) {
            $amount = $this->get_amount_for_type($type_id, WC()->cart);

            if ($amount <= 0) {
                continue;
            }

            $settings = $this->get_type_settings($type_id);
            $checkbox_id = 'wcdp_donation_upsell_' . $type_id;
            ?>
            <tr class="wcdp-donation-upsell-row wcdp-donation-upsell-row--<?php echo esc_attr($type_id); ?>">
                <th colspan="2">
                    <label class="wcdp-fee-recovery wcdp-donation-upsell" for="<?php echo esc_attr($checkbox_id); ?>">
                        <input type="checkbox"
                            id="<?php echo esc_attr($checkbox_id); ?>"
                            class="wcdp-fee-recovery__input wcdp-donation-upsell__input"
                            name="<?php echo esc_attr($checkbox_id); ?>"
                            value="1" <?php checked($this->is_type_checked($type_id)); ?>>
                        <span class="wcdp-fee-recovery__body">
                            <span></span>
                            <span class="wcdp-fee-recovery__body-header">
                                <?php echo esc_html($this->replace_label_placeholders($settings['label'], $amount)); ?>
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
    }

    /**
     * Add donation fee rows to the WooCommerce cart.
     *
     * @param WC_Cart $cart
     * @return void
     */
    public function add_donation_fees(WC_Cart $cart): void
    {
        if ((is_admin() && !defined('DOING_AJAX')) || is_null(WC()->cart) || $cart->is_empty()) {
            return;
        }

        foreach ($this->get_enabled_types() as $type_id => $type) {
            if (!$this->is_type_checked($type_id)) {
                continue;
            }

            $amount = $this->get_amount_for_type($type_id, $cart);

            if ($amount <= 0) {
                continue;
            }

            $settings = $this->get_type_settings($type_id);
            $fee_name = apply_filters(
                'wcdp_donation_upsell_fee_name',
                $settings['fee_name'],
                $type_id,
                $amount,
                $cart,
                $settings
            );

            $cart->add_fee($fee_name, $amount, false);
        }
    }

    /**
     * Check whether donation upsells are globally enabled.
     *
     * @return bool
     */
    private function is_enabled(): bool
    {
        return get_option(self::OPTION_ENABLED, 'no') === 'yes';
    }

    /**
     * Return stored settings for a specific upsell type merged with defaults.
     *
     * @param string $type_id
     * @return array
     */
    private function get_type_settings(string $type_id): array
    {
        $types = self::get_supported_types();
        $defaults = isset($types[$type_id]['defaults']) ? $types[$type_id]['defaults'] : array();
        $saved_settings = $this->get_saved_settings();
        $saved_type_settings = isset($saved_settings[$type_id]) && is_array($saved_settings[$type_id])
            ? $saved_settings[$type_id]
            : array();

        return wp_parse_args($saved_type_settings, $defaults);
    }

    /**
     * Return all enabled upsell types.
     *
     * @return array
     */
    private function get_enabled_types(): array
    {
        $enabled_types = array();

        foreach (self::get_supported_types() as $type_id => $type) {
            $settings = $this->get_type_settings($type_id);

            if (($settings['enabled'] ?? 'no') === 'yes') {
                $enabled_types[$type_id] = $type;
            }
        }

        return $enabled_types;
    }

    /**
     * Return whether checkout upsells should be rendered.
     *
     * @return bool
     */
    private function should_render_checkout_upsells(): bool
    {
        if (is_admin() || is_null(WC()->cart) || WC()->cart->is_empty()) {
            return false;
        }

        if (WCDP_Form::cart_contains_donation()) {
            return false;
        }

        return !empty($this->get_enabled_types());
    }

    /**
     * Get the current request data including WooCommerce post_data payload.
     *
     * @return array
     */
    private function get_request_data(): array
    {
        if (isset($_POST['post_data'])) {
            $request_data = array();
            parse_str(wp_unslash($_POST['post_data']), $request_data);
            return is_array($request_data) ? $request_data : array();
        }

        return is_array($_POST) ? wp_unslash($_POST) : array();
    }

    /**
     * Check if a specific upsell checkbox is selected.
     *
     * @param string $type_id
     * @return bool
     */
    private function is_type_checked(string $type_id): bool
    {
        $request_data = $this->get_request_data();
        $key = 'wcdp_donation_upsell_' . $type_id;

        return !empty($request_data[$key]);
    }

    /**
     * Calculate the upsell amount for a specific type.
     *
     * @param string $type_id
     * @param WC_Cart $cart
     * @return float
     */
    private function get_amount_for_type(string $type_id, WC_Cart $cart): float
    {
        $types = self::get_supported_types();

        if (!isset($types[$type_id]['calculator']) || !method_exists($this, $types[$type_id]['calculator'])) {
            return 0.0;
        }

        $settings = $this->get_type_settings($type_id);
        $calculator = $types[$type_id]['calculator'];
        $amount = (float) $this->$calculator($cart, $settings);
        $amount = apply_filters('wcdp_donation_upsell_amount', $amount, $type_id, $cart, $settings);
        $amount = apply_filters('wcdp_donation_upsell_amount_' . $type_id, $amount, $cart, $settings);

        return max(0, round($amount, wc_get_price_decimals()));
    }

    /**
     * Calculate round-up amount.
     *
     * @param WC_Cart $cart
     * @param array $settings
     * @return float
     */
    private function calculate_round_up_amount(WC_Cart $cart, array $settings): float
    {
        $base_amount = $this->get_base_amount($cart);

        if ($base_amount <= 0) {
            return 0.0;
        }

        $next_whole = ceil($base_amount);
        $amount = round($next_whole - $base_amount, wc_get_price_decimals());

        if ($amount <= 0) {
            return 0.0;
        }

        return $amount;
    }

    /**
     * Calculate fixed + percentage amount.
     *
     * @param WC_Cart $cart
     * @param array $settings
     * @return float
     */
    private function calculate_fixed_percentage_amount(WC_Cart $cart, array $settings): float
    {
        $base_amount = $this->get_base_amount($cart);
        $fixed_amount = max(0, (float) ($settings['fixed_amount'] ?? 0));
        $percentage = min(100, max(0, (float) ($settings['percentage'] ?? 0)));

        if ($base_amount <= 0 || ($fixed_amount <= 0 && $percentage <= 0)) {
            return 0.0;
        }

        return $fixed_amount + ($base_amount * ($percentage / 100));
    }

    /**
     * Return the amount used as calculation basis.
     *
     * @param WC_Cart $cart
     * @return float
     */
    private function get_base_amount(WC_Cart $cart): float
    {
        $amount = max(0, (float) $cart->get_cart_contents_total());

        return (float) apply_filters('wcdp_donation_upsell_base_amount', $amount, $cart);
    }

    /**
     * Replace supported placeholders in checkout labels.
     *
     * @param string $label
     * @param float $amount
     * @return string
     */
    private function replace_label_placeholders(string $label, float $amount): string
    {
        return str_replace('{amount}', wp_strip_all_tags(wc_price($amount)), $label);
    }

    /**
     * Get saved upsell settings.
     *
     * @return array
     */
    private function get_saved_settings(): array
    {
        $saved_settings = json_decode(get_option(self::OPTION_VALUES, '{}'), true);

        return is_array($saved_settings) ? $saved_settings : array();
    }
}
