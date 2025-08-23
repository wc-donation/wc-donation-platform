<?php
/**
 * Template for the "Donation Form" tab for editing products
 */
if (!defined('ABSPATH'))
    exit;

global $post;

$wcdp_min_amount = (float) get_option('wcdp_min_amount', 3);
$wcdp_max_amount = (float) get_option('wcdp_max_amount', 50000);

do_action('wcdp_before_product_settings');
?>

<div id="wcdp_donation_form_data" class="panel woocommerce_options_panel hidden">
    <div class="options_group">
        <p class="wcdp_shortcode form-field">
            <label for="wcdp_shortcode"><?php esc_html_e('Shortcode', 'wc-donation-platform'); ?></label>
            <span class="wrap">
                <input type="text" id="wcdp_shortcode" readonly="readonly" onclick="this.select()"
                    value="[wcdp_donation_form id=&quot;<?php echo $post->ID; ?>&quot;]">
                <?php
                /* translators: %s & %s: link html (not visible) */
                echo wc_help_tip(__('Add this shortcode where you want to display the donation form.', 'wc-donation-platform') . '<a href="https://www.wc-donation.com/documentation/getting-started/shortcode/" target="_blank" rel="noopener">' . __('Shortcode Documentation', 'wc-donation-platform') . '</a>'); ?>
            </span>
        </p>
        <p class="wcdp_direct_link form-field">
            <label><?php esc_html_e('Direct Link', 'wc-donation-platform'); ?></label>
            <span class="wrap">
                <a href="<?php echo esc_url(wc_get_checkout_url() . '?postid=' . $post->ID); ?>"
                    target="_blank"><?php echo esc_url(wc_get_checkout_url() . '?postid=' . $post->ID); ?></a>
            </span>
        </p>
        <p class="wcdp_donation_amounts form-field">
            <label><?php esc_html_e('Donation range', 'wc-donation-platform'); ?></label>
            <span class="wrap">
                <?php
                // Translators: %1$d minimum donation amount, %2$d maximum donation amount
                printf(esc_html__('Min: %1$s, Max: %2$s', 'wc-donation-platform'), wc_price($wcdp_min_amount), wc_price($wcdp_max_amount)); ?>&nbsp;
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=wc-donation-platform'); ?>"
                    target="_blank"><?php esc_html_e('Edit', 'wc-donation-platform'); ?></a>
            </span>
        </p>
    </div>

    <div class="options_group">
        <?php
        woocommerce_wp_text_input(
            array(
                'id' => "wcdp_fundraising_goal",
                'name' => "wcdp_fundraising_goal",
                'value' => get_post_meta($post->ID, 'wcdp-settings[wcdp_fundraising_goal]', true),
                'label' => __('Fundraising Goal', 'wc-donation-platform'),
                'wrapper_class' => 'form-field',
                'placeholder' => __('Enter 0 to deactivate', 'wc-donation-platform'),
                'data_type' => 'price',
                'desc_tip' => true,
                'description' => __('The amount you want to collect with this campaign. Leave the field empty to hide the goal. Please note: You can still donate to the project after reaching the goal.', 'wc-donation-platform'),
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => "wcdp_fundraising_end_date",
                'name' => "wcdp_fundraising_end_date",
                'type' => 'date',
                'value' => get_post_meta($post->ID, 'wcdp-settings[wcdp_fundraising_end_date]', true),
                'label' => __('End Date', 'wc-donation-platform'),
                'wrapper_class' => 'form-field',
            )
        );
        do_action('wcdp_product_settings_fundraising', $post->ID);
        ?>
        <p><?php esc_html_e('Please note: You can still donate to the project after reaching the fundraising goal or end date.', 'wc-donation-platform'); ?>
        </p>
    </div>

    <div class="options_group">
        <?php
        $product = wc_get_product($post->ID);
        woocommerce_wp_select(
            array(
                'wrapper_class' => '',
                'id' => 'wcdp-amount-layout',
                'class' => 'select short wcdp-selection',
                'label' => __('Layout of Amount suggestions', 'wc-donation-platform'),
                'options' => array(
                    '0' => __('Just input box', 'wc-donation-platform'),
                    '1' => __('Radio/Button selection', 'wc-donation-platform'),
                    '2' => __('Input box + Range slider', 'wc-donation-platform'),
                    //'3'	=> __( 'Expert: custom code with action: wcdp_custom_html_amount', 'wc-donation-platform' ),
                ),
                'description' => __('Design of the amount field.', 'wc-donation-platform'),
                'desc_tip' => 'true',
                'name' => 'wcdp-amount-layout',
                'value' => get_post_meta($post->ID, 'wcdp-settings[0]', true),
            )
        );

        $values = json_decode(get_post_meta($post->ID, 'wcdp-settings[1]', true));
        $default_amounts = '5, 15, 25, 75, 100';

        // Convert stored values array to comma-separated string
        $amount_suggestions_value = '';
        if (is_array($values) && !empty($values)) {
            $amount_suggestions_value = implode(', ', $values);
        } else {
            $amount_suggestions_value = $default_amounts;
        }

        woocommerce_wp_text_input(
            array(
                'wrapper_class' => get_post_meta($post->ID, 'wcdp-settings[0]', true) == 2 ? '' : 'hidden',
                'id' => 'wcdp-settings',
                'label' => __('Amount Suggestions', 'wc-donation-platform'),
                'placeholder' => $default_amounts,
                'description' => __('Enter donation amounts separated by commas (e.g. 5, 15, 25, 75, 100). Values must be between the minimum and maximum donation amounts.', 'wc-donation-platform'),
                'desc_tip' => 'true',
                'name' => 'wcdp-settings',
                'value' => $amount_suggestions_value,
            )
        );

        if (is_a($product, 'WC_Product_Variable')) {
            $attributes = $product->get_variation_attributes();
            foreach ($attributes as $attribute => $options) {
                $attribute_name = esc_attr(sanitize_title($attribute));

                woocommerce_wp_select(
                    array(
                        'wrapper_class' => 'show_if_variable',
                        'id' => 'wcdp-attr-' . $attribute_name,
                        'class' => 'select short wcdp-selection',
                        'label' => __('Layout of ', 'wc-donation-platform') . wc_attribute_label($attribute_name),
                        'options' => array(
                            '0' => __('Default layout', 'wc-donation-platform'),
                            '1' => __('Radio/Button selection', 'wc-donation-platform'),
                            '2' => __('Expert: custom code with action wcdp_custom_html_', 'wc-donation-platform') . esc_attr($attribute_name) . '_' . esc_attr($product->get_id()),
                        ),
                        'description' => __('Design of the variation selection. Only for variable products.', 'wc-donation-platform'),
                        'desc_tip' => 'true',
                        'name' => 'wcdp-attr-' . $attribute_name,
                        'value' => get_post_meta($post->ID, 'wcdp-settings[wcdp-attr-' . $attribute_name . ']', true),
                    )
                );
            }
        }

        ?>

        <script>
            (function ($) {
                $(window).bind("load", function () {
                    show_hide_donable_panel();

                    const price = $('#_regular_price');
                    if (price.val() === '') {
                        price.val(0);
                    }
                });

                $('#wcdp-amount-layout,input#_donable').on('change', function () {
                    show_hide_donable_panel();
                });

                function show_hide_donable_panel() {
                    const is_donable = $('input#_donable:checked').length;
                    if (is_donable) {
                        $('.show_if_donable').show();
                    } else {
                        $('.show_if_donable').hide();
                    }

                    if ($('#wcdp-amount-layout').val() == 1) {
                        $('.wcdp-settings_field').show();
                    } else {
                        $('.wcdp-settings_field').hide();
                    }
                }
            })(jQuery);
        </script>
    </div>

    <div class="options_group">
        <p class="wcdp_ask_review">
            <a href="https://wordpress.org/support/plugin/wc-donation-platform/reviews/?filter=5#new-post"
                target="_blank"><?php esc_html_e('If you like Donation Platform for WooCommerce and want to support the further growth and development of the plugin, please consider a 5-star rating on wordpress.org.', 'wc-donation-platform'); ?></a>
        </p>
    </div>

</div>