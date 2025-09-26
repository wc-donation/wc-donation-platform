<?php
if (!defined('ABSPATH'))
    exit;

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
        add_action('woocommerce_update_options_advanced', array($this, 'disable_new_product_editor'));
        add_action('woocommerce_admin_field_wcdp_clear_cache_button', [$this, 'js_clear_cache']);
        add_action('wp_ajax_wcdp_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('woocommerce_admin_field_wcdp_leaderboard_js', [$this, 'leaderboard_js']);

        if (function_exists('wp_add_privacy_policy_content')) {
            add_action('admin_init', array($this, 'suggest_privacy_policy_content'));
        }

        add_action('upgrader_process_complete', array($this, 'on_plugin_update'), 10, 2);
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
     * Disables the new product block editor since WCDP is not compatible
     * @return void
     */
    public function disable_new_product_editor()
    {
        update_option('woocommerce_feature_product_block_editor_enabled', 'no');
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
        $decimals = pow(10, wc_get_price_decimals() * (-1));
        $settings = array(
            array(
                'title' => __('General Options', 'wc-donation-platform'),
                'type' => 'title',
                'id' => 'wcdp_section_general',
            ),
            array(
                'title' => __('Allow more than one product in cart', 'wc-donation-platform'),
                'desc' => __('Should it be possible to have more than one donation product in the cart at the same time?', 'wc-donation-platform'),
                'id' => 'wcdp_multiple_in_cart',
                'default' => 'no',
                'type' => 'checkbox',
            ),
            array(
                'title' => __('Redirect to Cart Instead of Checkout', 'wc-donation-platform'),
                'desc' => __('Would you like to redirect users to the cart when they proceed to the next step in Style 4 or from product pages?', 'wc-donation-platform'),
                'id' => 'wcdp_redirect_to_cart',
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
                'type' => 'wcdp_clear_cache_button',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wcdp_section_general',
            ),
            array(
                'name' => __('Leaderborard Options', 'wc-donation-platform'),
                'type' => 'title',
                'desc' => '<a href="https://www.wc-donation.com/documentation/usage/donation-leaderboard/" target="_blank">' . esc_html__('Detailed leaderboard documentation', 'wc-donation-platform') . '</a>',
                'id' => 'wcdp_section_leaderboard',
            ),
            array(
                'title' => __('Leaderboard item heading', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title',
                'default' => __('{firstname} donated {amount}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_default',
            ),
            array(
                'title' => __('Leaderboard item description', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle',
                'default' => __('{timediff}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_default',
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
                'desc' => __('Checkout checkbox label', 'wc-donation-platform'),
                'id' => 'wcdp_checkout_checkbox_text',
                'default' => __('Do not show my name in the leaderboard', 'wc-donation-platform'),
                'type' => 'text',
                'desc_tip' => __('The text of the optional checkbox on checkout. It allows you to display anonymous and public donations in your leaderboards.', 'wc-donation-platform'),
                'class' => 'wcdp_leaderboard_optout_checkbox',
            ),
            array(
                'title' => __('Leaderboard item heading (Checkout checkbox checked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title_checked',
                'default' => __('Anonymous donor donated {amount}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_optout_checkbox',
            ),
            array(
                'title' => __('Leaderboard item heading (Checkout checkbox unchecked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_title_unchecked',
                'default' => __('{firstname} donated {amount}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_optout_checkbox',
            ),
            array(
                'title' => __('Leaderboard item description (Checkout checkbox checked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle_checked',
                'default' => __('{timediff}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_optout_checkbox',
            ),
            array(
                'title' => __('Leaderboard item description (Checkout checkbox unchecked)', 'wc-donation-platform'),
                'id' => 'wcdp_lb_subtitle_unchecked',
                'default' => __('{timediff}', 'wc-donation-platform'),
                'type' => 'text',
                'class' => 'wcdp_leaderboard_optout_checkbox',
            ),
            array(
                'type' => 'wcdp_leaderboard_js',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wcdp_section_leaderboard',
            ),
            array(
                'title' => __('Design Options', 'wc-donation-platform'),
                'type' => 'title',
                'id' => 'wcdp_section_design',
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
                // translators: %1$s & %3$s: opening links, %2$s & %4$s closing links
                'desc' => sprintf(esc_html__('Having issues? First, %1$scheck the documentation%2$s. If that doesn’t solve your problem, feel free to %3$sopen an issue on WordPress.org%4$s!', 'wc-donation-platform'), '<a href="https://www.wc-donation.com/documentation/" target="_blank">', '</a>', '<a href="https://wordpress.org/support/plugin/wc-donation-platform/#new-topic-0" target="_blank">', '</a>')
                    . '<br><br><a href="https://wordpress.org/support/plugin/wc-donation-platform/reviews/?filter=5#new-post" target="_blank">'
                    . esc_html__('If you like Donation Platform for WooCommerce and want to support the further growth and development of the plugin, please consider a 5-star rating on wordpress.org.', 'wc-donation-platform')
                    . '</a>',
                'type' => 'title',
                'id' => 'wcdp_settings_support',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wcdp_settings_support',
            ),
        );

        return apply_filters('wcdp-general-settings', $settings);
    }

    public static function clear_cached_data()
    {
        WCDP_Progress::delete_total_revenue_meta_for_all_products();
        WCDP_Leaderboard::delete_cached_leaderboard_total();
        WCDP_Leaderboard::clear_donable_products_cache();
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
                $fixed = (float) $_POST['wcdp_fixed_' . $method->id];
            } else {
                $fixed = 0;
            }
            if (
                isset($_POST['wcdp_variable_' . $method->id]) &&
                (float) $_POST['wcdp_variable_' . $method->id] >= 0 &&
                (float) $_POST['wcdp_variable_' . $method->id] <= 100
            ) {
                $variable = (float) $_POST['wcdp_variable_' . $method->id];
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

        // Make sure to disable Product block editor
        update_option('woocommerce_feature_product_block_editor_enabled', 'no');
    }

    /**
     * Adds privacy policy content for the Donation Platform for WooCommerce plugin.
     */
    public function suggest_privacy_policy_content()
    {
        $content = sprintf(
            '<div class="wp-suggested-text">' .
            '<p class="privacy-policy-tutorial">' .
            __('This sample language includes the basics around what personal data Donation Platform for WooCommerce may be collecting, storing and sharing. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'wc-donation-platform') .
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
            '<a href="https://www.wc-donation.com/" target="_blank">',
            '</a>'
        );

        $content = apply_filters('wcdp_privacy_policy_content', $content);

        wp_add_privacy_policy_content('Donation Platform for WooCommerce', wp_kses_post($content));
    }

    public function on_plugin_update($upgrader_object, $options)
    {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            if (array_key_exists('plugins', $options) && in_array('woocommerce/woocommerce.php', $options['plugins'], true)) {
                if (get_option('woocommerce_email_footer_text') === '{site_title} &mdash; Built with {WooCommerce}') {
                    update_option('woocommerce_email_footer_text', '{site_title} &mdash; Built with {WooCommerce} and <a href="https://www.wc-donation.com/">Donation Platform for WooCommerce</a>');
                }
            }
        }
    }

    /**
     * Render a "Clear Cache" button with AJAX submit
     * @return void
     */
    public function js_clear_cache()
    {

        ?>
        <tr class="">
            <th scope="row" class="titledesc"><?php esc_html_e('Clear Cached Data', 'wc-donation-platform'); ?></th>
            <td class="forminp forminp-checkbox ">
                <fieldset>
                    <button type="button" class="button-secondary" id="wcdp-clear-cache-btn">
                        <?php esc_html_e('Clear Cache', 'wc-donation-platform'); ?>
                    </button>
                    <p class="description ">
                        <?php esc_html_e('Clear cached progress bar & leaderboard data.', 'wc-donation-platform'); ?></p>
            </td>
        </tr>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#wcdp-clear-cache-btn').on('click', function () {
                    const $button = $(this);
                    $button.prop('disabled', true).text('<?php echo esc_js(__('Clearing...', 'wc-donation-platform')); ?>');

                    $.post(ajaxurl, {
                        action: 'wcdp_clear_cache',
                        nonce: '<?php echo wp_create_nonce('wcdp_clear_cache'); ?>',
                    }).done(function (response) {
                        alert(response.success ? '<?php echo esc_js(__('Cached data cleared successfully.', 'wc-donation-platform')); ?>' : response.data);
                    }).always(function () {
                        $button.prop('disabled', false).text('<?php echo esc_js(__('Clear Cache', 'wc-donation-platform')); ?>');
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Handle "Clear Cache" button click via AJAX
     *
     * @return void
     */
    public function ajax_clear_cache()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'wc-donation-platform'));
        }

        if (!check_ajax_referer('wcdp_clear_cache', 'nonce', false)) {
            wp_send_json_error(__('Invalid request.', 'wc-donation-platform'));
        }

        wcdp_clear_cache();
        wp_send_json_success();
    }

    /**
     * Show/Hide Leaderboard input fields depending on settings
     * @return void
     */
    public function leaderboard_js()
    {

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                function toggleLeaderboardOptions() {
                    const isChecked = $('#wcdp_enable_checkout_checkbox').is(':checked');

                    $('.wcdp_leaderboard_optout_checkbox')
                        .each(function () {
                            $(this).parent().parent().toggle(isChecked);
                        });
                    $('.wcdp_leaderboard_default')
                        .each(function () {
                            $(this).parent().parent().toggle(!isChecked);
                        });
                }

                // Run on page load
                toggleLeaderboardOptions();

                // Bind change event
                $('#wcdp_enable_checkout_checkbox').on('change', toggleLeaderboardOptions);
            });
        </script>
        <?php
    }

}

$wc_settings = new WCDP_General_Settings();
