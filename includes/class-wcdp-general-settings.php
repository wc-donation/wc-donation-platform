<?php
if (!defined('ABSPATH')) exit;

class WCDP_General_Settings
{

    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_wc-donation-platform', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_wc-donation-platform', array($this, 'update_settings'));

        if (function_exists('wp_add_privacy_policy_content')) {
            add_action( 'admin_init', array($this, 'suggest_privacy_policy_content') );
        }
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab(array $settings_tabs): array
    {
        $settings_tabs['wc-donation-platform'] = __('Donations', 'wc-donation-platform');
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public function settings_tab()
    {
        woocommerce_admin_fields($this->get_settings());
    }

    /**
     * Get all the settings for this plugin for @return array Array of settings for @see woocommerce_admin_fields() function.
     * @see woocommerce_admin_fields() function.
     *
     */
    public function get_settings(): array
    {
        if (get_option("wcdp_clear_cache") === "yes") {
            $this->clear_cached_data();
            update_option("wcdp_clear_cache", "no");
            $desc_tip = __('Cached data cleared successfully.', 'wc-donation-platform');
        } else {
            $desc_tip = "";
        }
        $decimals = pow(10, wc_get_price_decimals() * (-1));
        $settings = array(
            array(
                'title' => __('General Options', 'wc-donation-platform'),
                'type' => 'title',
                'id' => 'wcdp_settings_general',
            ),
            array(
                'title' => __('Allow more than one product in cart', 'wc-donation-platform'),
                'desc' => __('Should it be possible to have more than one donation product in the cart at the same time?', 'wc-donation-platform'),
                'id' => 'wcdp_multiple_in_cart',
                'default' => 'no',
                'type' => 'checkbox',
            ),
            array(
                'title' => __('"Your Contribution" Title Text', 'wc-donation-platform'),
                'id' => 'wcdp_contribution_title',
                'type' => 'text',
                'default' => __('Your Contribution', 'wc-donation-platform'),
            ),
            array(
                'title' => __('"Choose an amount" Title Text', 'wc-donation-platform'),
                'id' => 'wcdp_choose_amount_title',
                'type' => 'text',
                'default' => __('Choose an amount', 'wc-donation-platform'),
            ),
            array(
                'title' => __('Minimum Donation amount', 'wc-donation-platform'),
                'id' => 'wcdp_min_amount',
                'type' => 'number',
                'default' => '3',
                'custom_attributes' => array(
                    'min' => $decimals,
                    'step' => $decimals,
                ),
            ),
            array(
                'title' => __('Maximum Donation amount', 'wc-donation-platform'),
                'id' => 'wcdp_max_amount',
                'type' => 'number',
                'default' => '50000',
                'custom_attributes' => array(
                    'min' => $decimals,
                    'step' => $decimals,
                ),
            ),
            array(
                'title' => __('Maximum Amount for range input', 'wc-donation-platform'),
                'id' => 'wcdp_max_range',
                'type' => 'number',
                'default' => '500',
                'custom_attributes' => array(
                    'min' => $decimals,
                    'step' => $decimals,
                ),
            ),
            array(
                'title' => __('Disable Order / Donation notes', 'wc-donation-platform'),
                'desc' => __('Enable to disable notes on checkout', 'wc-donation-platform'),
                'id' => 'wcdp_disable_order_notes',
                'default' => 'no',
                'type' => 'checkbox',
            ),
            array(
                'title' => __('Fee Recovery', 'wc-donation-platform'),
                'desc' => __('Ask donors to cover transaction fees.', 'wc-donation-platform'),
                'id' => 'wcdp_fee_recovery',
                'default' => 'no',
                'type' => 'checkbox',
            ),
            array(
                'type' => 'wcdp_fee_recovery',
                'step' => $decimals,
            ),
            array(
                'id' => 'wcdp_fee_recovery_values',
            ),
            array(
                'title' => __('Enable compatibility mode', 'wc-donation-platform'),
                'desc' => __('Disable wording changes to run donation platform and webshop simultaneously', 'wc-donation-platform'),
                'id' => 'wcdp_compatibility_mode',
                'default' => 'no',
                'type' => 'checkbox',
                'desc_tip' => __('Some features of Donation Platform for WooCommerce will be disabled so that WooCommerce can be used as a donation platform and webshop at the same time.', 'wc-donation-platform'),
            ),
            array(
                'title' => __('Clear Cached Data', 'wc-donation-platform'),
                'type' => 'checkbox',
                'default' => 'no',
                'desc' => __('Clear cached progress bar & leaderboard data.', 'wc-donation-platform'),
                'id' => 'wcdp_clear_cache',
                'desc_tip' => $desc_tip,
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wcdp_section_general',
            ),
            array(
                'name' => __('Leaderborard Options', 'wc-donation-platform'),
                'type' => 'title',
                'desc' => '<a href="https://wcdp.jonh.eu/documentation/usage/donation-leaderboard/" target="_blank">' . esc_html__('Detailed leaderboard documentation', 'wc-donation-platform') . '</a>',
                'id' => 'wcdp_leaderboard_options',
            ),
            array(
                'title' => __('Enable anonymous / public checkbox', 'wc-donation-platform'),
                'desc' => __('Add a checkbox to the checkout allowing users to specify how their donation is displayed in the leaderboard', 'wc-donation-platform'),
                'id' => 'wcdp_enable_checkout_checkbox',
                'default' => 'no',
                'type' => 'checkbox',
                'desc_tip' => __('The checkbox allows you to display anonymous and public donations in your leaderboards.', 'wc-donation-platform'),
            ),
            array(
                'title' => __('Text of anonymous / public checkbox', 'wc-donation-platform'),
                'desc' => __('Checkbox label', 'wc-donation-platform'),
                'id' => 'wcdp_checkout_checkbox_text',
                'default' => __('Do not show my name in the leaderboard', 'wc-donation-platform'),
                'type' => 'text',
                'desc_tip' => __('The text of the optional checkbox on checkout. It allows you to display anonymous and public donations in your leaderboards.', 'wc-donation-platform'),
            ),
            array(
                'title' => __('Leaderboard item heading', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title',
                'default' => __('{firstname} donated {amount}', 'wc-donation-platform'),
                'type' => 'text',
            ),
            array(
                'title' => __('Leaderboard item description', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle',
                'default' => __('{timediff}', 'wc-donation-platform'),
                'type' => 'text',
            ),
            array(
                'title' => __('Leaderboard item heading (Checkout checkbox checked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title_checked',
                'default' => "",
                'type' => 'text',
            ),
            array(
                'title' => __('Leaderboard item heading (Checkout checkbox unchecked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title_unchecked',
                'default' => "",
                'type' => 'text',
            ),
            array(
                'title' => __('Leaderboard item description (Checkout checkbox checked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle_checked',
                'default' => "",
                'type' => 'text',
            ),
            array(
                'title' => __('Leaderboard item description (Checkout checkbox unchecked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle_unchecked',
                'default' => "",
                'type' => 'text',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wcdp_section_leaderboard',
            ),
            array(
                'title' => __('Design Options', 'wc-donation-platform'),
                'type' => 'title',
                'id' => 'wcdp_leaderboard_options',
            ),
            array(
                'title' => __('Main Color', 'wc-donation-platform'),
                /* translators: %s: default color */
                'desc' => sprintf(__('Primary Color used in the frontend. Default: %s.', 'wc-donation-platform'), '<code>#00753a</code>'),
                'id' => 'wcdp_main_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => '#00753a',
                'autoload' => false,
                'desc_tip' => true,
            ),

            array(
                'title' => __('Secondary Color', 'wc-donation-platform'),
                /* translators: %s: default color */
                'desc' => sprintf(__('Secondary Color used in the frontend. Default: %s.', 'wc-donation-platform'), '<code>#30bf76</code>'),
                'id' => 'wcdp_secondary_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => '#30bf76',
                'autoload' => false,
                'desc_tip' => true,
            ),

            array(
                'title' => __('Error Color', 'wc-donation-platform'),
                // translators: %s: default color //
                'desc' => sprintf(__('Error Color used in the frontend. Default: %s.', 'wc-donation-platform'), '<code>#de0000</code>'),
                'id' => 'wcdp_error_color',
                'type' => 'color',
                'css' => 'width:6em;',
                'default' => '#de0000',
                'autoload' => false,
                'desc_tip' => true,
            ),

            array(
                'type' => 'sectionend',
                'id' => 'wcdp_section_design',
            ),
            array(
                'title' => __('Support', 'wc-donation-platform'),
                'desc' => '<a href="https://wordpress.org/support/plugin/wc-donation-platform/reviews/?filter=5#new-post" target="_blank">' . esc_html__('If you like Donation Platform for WooCommerce and want to support the further growth and development of the plugin, please consider a 5-star rating on wordpress.org.', 'wc-donation-platform') . '</a>',
                'type' => 'title',
                'id' => 'wcdp_settings_support',
            ),
        );

        return apply_filters('wcdp-general-settings', $settings);
    }

    private function clear_cached_data()
    {
        WCDP_Progress::delete_total_revenue_meta_for_all_products();
        WCDP_Leaderboard::delete_cached_leaderboard_total();
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public function update_settings()
    {
        $wcdp_fee_recovery_values = array();
        $available_payment_methods = WC()->payment_gateways->get_available_payment_gateways();
        foreach ($available_payment_methods as $method) {
            if (isset($_POST['wcdp_fixed_' . $method->id]) && $_POST['wcdp_fixed_' . $method->id] >= 0) {
                $fixed = (float)$_POST['wcdp_fixed_' . $method->id];
            } else {
                $fixed = 0;
            }
            if (isset($_POST['wcdp_variable_' . $method->id]) &&
                (float)$_POST['wcdp_variable_' . $method->id] >= 0 &&
                (float)$_POST['wcdp_variable_' . $method->id] <= 100) {
                $variable = (float)$_POST['wcdp_variable_' . $method->id];
            } else {
                $variable = 0;
            }

            $wcdp_fee_recovery_values[$method->id] = array(
                'fixed' => $fixed,
                'variable' => $variable,
            );
        }
        update_option('wcdp_fee_recovery_values', wp_json_encode($wcdp_fee_recovery_values), 'yes');

        woocommerce_update_options(self::get_settings());
    }

    /**
     * Adds privacy policy content for the Donation Platform for WooCommerce plugin.
     */
    public function suggest_privacy_policy_content() {
        $content = sprintf(
            '<div class="wp-suggested-text">' .
            '<p class="privacy-policy-tutorial">' .
                __( 'This sample language includes the basics around what personal data Donation Platform for WooCommerce may be collecting, storing and sharing. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'wc-donation-platform' ) .
            '</p>' .
            // Translators: %1$s & %2$s: link tags
            '<p>' . __('This website utilizes %1$sDonation Platform for WooCommerce%2$s, a plugin designed to facilitate donations.', 'wc-donation-platform') . '</p>' .
            '<p>' . __('By making a donation through our website, you acknowledge and agree to the following:', 'wc-donation-platform') . '</p>' .
            '<ul>' .
                '<li>' . __('Any information provided during the donation process, such as name, email address, and donation amount, may be securely stored in our website’s database.', 'wc-donation-platform') . '</li>' .
                '<li>' . __('Donation Platform for WooCommerce provides an anonymized donor wall / Leaderboard feature to showcase donations. You have the option to specify how you would like your donation to be listed during the checkout process.', 'wc-donation-platform') . '</li>' .
                '<li>' . __('We may use the provided information to:', 'wc-donation-platform') . '</li>' .
                    '<ul>' .
                        '<li>' . __('Thank you for your donation via email or other communication methods.', 'wc-donation-platform') . '</li>' .
                        '<li>' . __('Issue receipts for your donations.', 'wc-donation-platform') . '</li>' .
                        '<li>' . __('Comply with any legal obligations regarding donation records and reporting.', 'wc-donation-platform') . '</li>' .
                        '<li>' . __('Provide you with a donation thank you certificate.', 'wc-donation-platform') . '</li>' .
                    '</ul>' .
                '<li>' . __('Your information will not be disclosed to third parties except when required by law, with your explicit consent, when necessary for providing our services such as payment processing, and in cases where it is in our legitimate interest, such as fraud prevention.', 'wc-donation-platform') . '</li>' .
            '</ul>' .
            '</div>',
            '<a href="https://wcdp.jonh.eu/" target="_blank">',
            '</a>'
        );

        $content = apply_filters( 'wcdp_privacy_policy_content', $content );

        wp_add_privacy_policy_content('Donation Platform for WooCommerce', wp_kses_post($content));
    }
}

$wc_settings = new WCDP_General_Settings();
