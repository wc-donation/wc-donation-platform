<?php
/**
 * Deactivation Action.
 *
 * @since v1.2.7
 */

defined('ABSPATH') || exit;

class WCDP_Feedback
{
    private $deactivation_survey_options;
    private $feedback_survey_options;

    /**
     * WCDP_Feedback constructor
     *
     * @since v1.2.7
     */
    public function __construct()
    {
        //add feedback survey (css, js & html) to product page
        //add_action('wcdp_before_product_settings', array($this, 'wcdp_add_feedback_survey'));

        //add deactivation survey (css, js & html) to plugin.php page
        add_action('admin_footer', array($this, 'get_source_data_callback'));

        //send survey response to WCDP server
        add_action('wp_ajax_wcdp_feedback_survey', array($this, 'send_survey_data'));

        //do not show survey again after dismiss for several days
        //add_action('wp_ajax_wcdp_feedback_survey_dismiss', array($this, 'send_survey_data_dismiss'));

        // Ask users for reviews
        add_action( 'current_screen', array( $this, 'add_review_notice' ) );

        // Ask user to subscribe to the newsletter
        add_action( 'admin_notices', array( $this, 'add_newsletter_notice' ) );

        //options of the
        $this->deactivation_survey_options = array(
            array(
                'id' => 'no-need',
                'input' => false,
                'text' => __("I no longer need the plugin.", "wc-donation-platform"),
            ),
            array(
                'id' => 'better-plugin',
                'input' => true,
                'text' => __("I found a better plugin.", "wc-donation-platform"),
                'placeholder' => __("Please share which plugin.", "wc-donation-platform"),
            ),
            array(
                'id' => 'stop-working',
                'input' => true,
                'text' => __("The plugin suddenly stopped working.", "wc-donation-platform"),
                'placeholder' => __("Please share more details.", "wc-donation-platform"),
            ),
            array(
                'id' => 'not-working',
                'input' => true,
                'text' => __("I could not get the plugin to work.", "wc-donation-platform"),
                'placeholder' => __("Please share more details.", "wc-donation-platform"),
            ),
            array(
                'id' => 'temporary-deactivation',
                'input' => false,
                'text' => __("It's a temporary deactivation.", "wc-donation-platform"),
            ),
            array(
                'id' => 'other',
                'input' => true,
                'text' => __("Other", "wc-donation-platform"),
                'placeholder' => __("Please share the reason.", "wc-donation-platform"),
            ),
        );

        //options of the feedback survey
        $this->feedback_survey_options = array(
            array(
                'id' => 'wcdp-0',
                'text' => '🤬',
            ),
            array(
                'id' => 'wcdp-1',
                'text' => '😕',
            ),
            array(
                'id' => 'wcdp-2',
                'text' => '😬',
            ),
            array(
                'id' => 'wcdp-3',
                'text' => '🙂',
            ),
            array(
                'id' => 'wcdp-4',
                'text' => '😍',
            ),
        );
    }

    /**
     * Ask users for reviews on wordpress.org
     *
     * @return void
     * @since 1.3.2
     */
    public function add_review_notice() {
        $key = 'wcdp_review_notice';
        if (!class_exists('WC_Admin_Notices') || rand(0, 3) !== 0 || !$this->should_show_newsletter_notice() || (int) get_user_meta( get_current_user_id(), 'dismissed_wcdp_newsletter_notice_notice', true) !== 1 || (int) get_user_meta( get_current_user_id(), 'dismissed_' . $key . '_notice', true) === 1) return;
        $html = '<h3><a href="https://wordpress.org/support/plugin/wc-donation-platform/reviews/?filter=5#new-post" target="_blank">' . esc_html__('If you like Donation Platform for WooCommerce and want to support the further growth and development of the plugin, please consider a 5-star rating on wordpress.org.', 'wc-donation-platform') . '</a></h3>';
        WC_Admin_Notices::add_custom_notice($key, $html);
    }

    /**
     * Ask users to subscribe to the Newsletter
     *
     * @return void
     * @since 1.3.2
     */
    public function add_newsletter_notice() {
        $key = 'wcdp_newsletter_notice';
        if (!class_exists('WC_Admin_Notices') || !$this->should_show_newsletter_notice() || (int) get_user_meta( get_current_user_id(), 'dismissed_' . $key . '_notice', true) === 1) return;
        $current_user = wp_get_current_user();
        if (!$current_user) return;

        // Create the notice content
        echo '<div id="message" class="updated woocommerce-message">
                <a class="woocommerce-message-close notice-dismiss" href="' . esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', $key ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' ) ) . '">' . esc_html__( 'Dismiss', 'woocommerce' ) . '</a>
                <h2>' . esc_html__('Subscribe to the Donation Platform for WooCommerce Newsletter', 'wc-donation-platform') . '</h2>
                <style>
                    .form-container {
                        max-width: 500px;
                        margin-bottom: 18px;
                    }
                    .form-group {
                        margin-bottom: 15px;
                    }
                    .form-label {
                        display: block;
                        text-align: left;
                    }
                    .form-input {
                        width: 100%;
                        padding: 10px;
                        border: 1px solid #ccc;
                        border-radius: 5px;
                    }
                    .form-submit {
                        width: 100%;
                    }
                </style>
                <form method="post" action="https://listmonk.jonh.eu/subscription/form" class="form-container" target="_blank">
                    <input type="hidden" name="nonce">
                    <div class="form-group">
                        <label for="email" class="form-label">' . esc_html__('Email', 'wc-donation-platform') . '</label>
                        <input type="email" id="email" name="email" required="" class="form-input" value="' . esc_attr($current_user->user_email) . '">
                    </div>
                    <div class="form-group">
                        <label for="name" class="form-label">' . esc_html__('Name (optional)', 'wc-donation-platform') . '</label>
                        <input type="text" id="name" name="name" class="form-input" value="' . esc_attr($current_user->display_name) . '">
                    </div>
                    <p style="display:none;">
                        <input id="6f21f" type="checkbox" name="l" checked="" value="6f21f9d3-12e2-4da8-9f05-feb3fb62adfc">
                        <label for="6f21f">Donation Platform for WooCommerce</label>
                    </p>
                    <input type="submit" value="' . esc_attr__('Subscribe to the Donation Platform for WooCommerce Newsletter', 'wc-donation-platform') . '" class="form-submit button-primary">
                </form>
              </div>';
    }

    /**
     * Check if it should show the Newsletter Subscription / Review notice
     *
     * @return bool
     * @since 1.3.2
     */
    private function should_show_newsletter_notice(): bool
    {
        if (!current_user_can( 'edit_shop_orders' )) return false;
        return (isset($_GET['page']) && $_GET['page'] === 'wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit')
            || (isset($_GET['tab']) && $_GET['tab'] === 'wc-donation-platform')
            && !isset($_GET['wc-hide-notice']);
    }

    /**
     * Send survey result to wcdp server
     *
     * @return void
     * @since v1.2.7
     */
    public function send_survey_data()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wcdp_nonce') || !current_user_can('administrator')) wp_die();

        $data = $this->get_data();
        if ($data['action'] === 'wcdp_feedback_survey') {
            set_transient('wcdp_feedback_send', true, 31536000);
        }

        wp_remote_post('https://wcdp.jonh.eu/wp-admin/admin-ajax.php', array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'headers' => array(
                'user-agent' => 'PHP/WCDP',
                'Accept' => 'application/json',
            ),
            'blocking' => false,
            'httpversion' => '1.0',
            'body' => $data,
        ));
        wp_die();
    }

    /**
     * Get non-sensitive diagnostic Data
     *
     * @return ARRAY Data to send
     * @since v1.2.7
     */
    public function get_data(): array
    {
        global $wpdb;
        $plugins_data = $this->get_plugins();

        return array(
            'action' => isset($_POST['type']) ? esc_attr($_POST['type']) : '',
            'name' => get_bloginfo('name'),
            'home' => esc_url(home_url()),
            'wp_version' => get_bloginfo('version'),
            'memory_limit' => WP_MEMORY_LIMIT,
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'locale' => get_locale(),
            'multisite' => is_multisite(),

            'active_theme' => get_stylesheet(),
            'active_plugins' => $plugins_data['active'],
            'inactive_plugins' => $plugins_data['inactive'],
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_key($_SERVER['SERVER_SOFTWARE']) : '',

            'timezone' => date_default_timezone_get(),
            'php_curl' => function_exists('curl_init'),
            'php_version' => function_exists('phpversion') ? phpversion() : '',
            'upload_size' => size_format(wp_max_upload_size()),
            'mysql_version' => $wpdb->db_version(),
            'php_fsockopen' => function_exists('fsockopen'),

            'cause_id' => isset($_POST['cause_id']) ? esc_attr($_POST['cause_id']) : '',
            'cause_details' => isset($_POST['cause_details']) ? sanitize_text_field($_POST['cause_details']) : '',
        );
    }

    /**
     * Get All the Installed Plugin Data
     *
     * @return ARRAY Plugin Information
     * @since v1.2.7
     */
    public function get_plugins(): array
    {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $active = array();
        $inactive = array();
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, array_keys(get_site_option('active_sitewide_plugins', array())));
        }

        foreach ($all_plugins as $key => $plugin) {
            $e = $key . '/' . $plugin['Version'];

            if (in_array($key, $active_plugins)) {
                $active[] = $e;
            } else {
                $inactive[] = $e;
            }
        }

        return array('active' => $active, 'inactive' => $inactive);
    }

    /**
     * Set transient value to not show the survey to often
     *
     * @return void
     * @throws Exception
     * @since 1.2.9
     */
    function send_survey_data_dismiss()
    {
        set_transient('wcdp_feedback_send', true, 86400 * random_int(1, 7));
        wp_die();
    }

    /**
     * Add Feedback Survey HTML, JS & CSS
     *
     * @return void
     * @since v1.2.9
     */
    public function wcdp_add_feedback_survey()
    {
        $filename = 'index.php';
        if (!file_exists($filename) || time() - filemtime($filename) < 345600 ||
            !current_user_can('administrator') ||
            get_transient('wcdp_feedback_send')
        ) {
            return;
        }

        $this->feedback_modal_html();
        $this->modal_css();
        $this->feedback_js();
    }

    /**
     * echo html of survey modal
     *
     * @return void
     */
    public function feedback_modal_html()
    { ?>
        <div class="wcdp-modal" id="wcdp-feedback-modal">
            <div class="wcdp-modal-wrap">

                <div class="wcdp-modal-header">
                    <h2><?php esc_html_e("Can you please help me to improve Donation Platform for WooCommerce?", "wc-donation-platform"); ?></h2>
                    <button class="wcdp-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
                </div>

                <div class="wcdp-modal-body">
                    <h3><?php esc_html_e("If you have a moment, please let me know how I can improve Donation Platform for WooCommerce for you. How do you like the plugin?", "wc-donation-platform"); ?></h3>
                    <ul class="wcdp-modal-input wcdp-radio-none">
                        <?php foreach ($this->feedback_survey_options as $option) { ?>
                            <li>
                                <label>
                                    <input type="radio" id="<?php echo esc_attr($option['id']); ?>" class="wcdp-survey"
                                           name="wcdp-survey" value="<?php echo esc_attr($option['id']); ?>">
                                    <div class="wcdp-reason-text"><?php echo esc_html($option['text']); ?></div>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                    <label for="wcdp_feedback_comments" class="wcdp-label-strong">
                        <strong><?php esc_html_e("Anything else you want to tell me?", "wc-donation-platform"); ?></strong><br>
                    </label>
                    <textarea id="wcdp_feedback_comments" name="wcdp_feedback_comments"
                              placeholder="<?php esc_html_e("Comments & Suggestions for Improvement", "wc-donation-platform"); ?>"
                              class="wcdp-comments-input"></textarea>


                    <div class="wcdp-modal-footer">
                        <a class="wcdp-modal-submit"
                           href="#"><?php esc_html_e("Submit Feedback", "wc-donation-platform"); ?><span
                                    class="dashicons dashicons-update rotate"></span></a>
                        <span class="wcdp-modal-on-deactivation wcdp-modal-cancel"><?php esc_html_e("Not now", "wc-donation-platform"); ?></span>
                    </div>

                    <p class="wcdp-greyed"><?php esc_html_e("By submitting the survey, you agree that some non-sensitive technical diagnostic data will be send.", "wc-donation-platform"); ?></p>

                </div>
            </div>
        </div>
    <?php }

    /**
     * Deactivation Forms CSS File
     *
     * @return void
     * @since v1.2.7
     */
    public function modal_css()
    { ?>
        <style>
            .wcdp-modal {
                position: fixed;
                z-index: 99999;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: #0000007F;
                display: none;
                box-sizing: border-box;
                overflow: scroll;
            }

            .wcdp-modal * {
                box-sizing: border-box;
            }

            .wcdp-modal.modal-active {
                display: block;
            }

            .wcdp-modal-wrap {
                max-width: 870px;
                width: 100%;
                position: relative;
                margin: 10% auto;
                background: #fff;
            }

            .wcdp-modal-submit {
                color: white;
                background-color: #0c4a0d;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 3px;
            }

            .wcdp-modal-submit:hover {
                color: white;
                background-color: #0e8512;
            }

            .wcdp-reason-input {
                display: none;
            }

            .wcdp-reason-input.wcdp-active {
                display: block;
            }

            .rotate {
                animation: rotate 1.5s linear infinite;
            }

            @keyframes rotate {
                to {
                    transform: rotate(360deg);
                }
            }

            @keyframes popupRotate {
                to {
                    transform: rotate(360deg);
                }
            }

            #wcdp-feedback-modal {
                background: #000000E2;
                overflow: hidden;
            }

            #wcdp-feedback-modal .wcdp-modal-wrap {
                max-width: 570px;
                border-radius: 3px;
                margin: 5% auto;
                overflow: hidden
            }

            #wcdp-feedback-modal .wcdp-modal-header {
                padding: 17px 10px 17px 30px;
                border-bottom: 1px solid #eaeaea;
                display: flex;
                align-items: center;
                background: #f8f8f8;
                font-size: 1.3em;
            }

            #wcdp-feedback-modal .wcdp-modal-header .wcdp-modal-cancel {
                padding: 0;
                border-radius: 100px;
                border: 1px solid #0c4a0d;
                background: none;
                color: #0c4a0d;
                cursor: pointer;
                transition: 400ms;
            }

            #wcdp-feedback-modal .wcdp-modal-header .wcdp-modal-cancel:focus, #wcdp-feedback-modal .wcdp-modal-header .wcdp-modal-cancel:hover {
                color: #0e8512;
                border: 1px solid #0e8512;
                outline: 0;
            }

            #wcdp-feedback-modal .wcdp-modal-header h2 {
                margin: 0;
                padding: 0;
                flex: 1;
                line-height: 1;
            }

            #wcdp-feedback-modal .wcdp-modal-body {
                padding: 25px 30px;
            }

            #wcdp-feedback-modal .wcdp-modal-body h3, .wcdp-label-strong {
                padding: 0;
                margin: 0;
                line-height: 1.4;
                font-size: 15px;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul {
                margin: 25px 0 10px;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li {
                display: flex;
                margin-bottom: 10px;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li:last-child {
                margin-bottom: 0;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li label {
                align-items: center;
                width: 100%;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li label input {
                padding: 0 !important;
                margin: 0;
                display: inline-block;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li label textarea {
                margin-top: 8px;
                width: 350px;
            }

            #wcdp-feedback-modal .wcdp-modal-body ul li label .wcdp-reason-text {
                margin-left: 8px;
                display: inline-block;
            }

            #wcdp-feedback-modal .wcdp-modal-footer {
                padding-top: 15px;
                display: flex;
                align-items: center;
            }

            #wcdp-feedback-modal .wcdp-modal-footer .wcdp-modal-submit {
                display: flex;
                align-items: center;
            }

            #wcdp-feedback-modal .wcdp-modal-footer .wcdp-modal-submit span {
                margin-left: 4px;
                display: none;
            }

            #wcdp-feedback-modal .wcdp-modal-footer .wcdp-modal-submit.loading span {
                display: block;
            }

            #wcdp-feedback-modal .wcdp-modal-footer .wcdp-modal-on-deactivation {
                margin-left: auto;
                color: #939393;
                text-decoration: none;
            }

            .wcdp-greyed {
                color: #939393;
                font-size: 0.9em;
            }

            .wcdp-radio-none {
                display: flex;
            }

            #wcdp-feedback-modal .wcdp-modal-body .wcdp-radio-none input {
                display: none;
            }

            .wcdp-radio-none {
                height: 100px;
            }

            .wcdp-radio-none li {
                width: 90px;
            }

            .wcdp-radio-none .wcdp-reason-text {
                font-size: 70px;
                filter: grayscale(100);
                cursor: pointer;
                transition: 0.3s;
            }

            .wcdp-radio-none .wcdp-reason-text:hover {
                font-size: 75px;
            }

            .wcdp-radio-none .wcdp-survey:checked + .wcdp-reason-text {
                font-size: 80px;
                filter: grayscale(0);
            }

            #wcdp_feedback_comments {
                width: 100%;
                height: 5em;
            }
        </style>
    <?php }

    /**
     * Feedback Forms JS script
     *
     * @return void
     * @since v1.2.9
     */
    public function feedback_js()
    { ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                'use strict';

                // Modal Cancel Click Action
                $(document).on('click', '.wcdp-modal-cancel', function (e) {
                    $('#wcdp-feedback-modal').remove();
                    $.ajax({
                        url: '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wcdp_nonce'); ?>',
                        type: 'POST',
                        data: {
                            action: 'wcdp_feedback_survey_dismiss',
                        }
                    });
                });

                // Deactivate Button Click Action
                $(document).on('click', '[href="#wcdp_donation_form_data"]', function (e) {
                    e.preventDefault();
                    $('#wcdp-feedback-modal').addClass('modal-active');
                });

                // Submit to Server
                $(document).on('click', '.wcdp-modal-submit', function (e) {
                    e.preventDefault();

                    $(this).addClass('loading');

                    $.ajax({
                        url: '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wcdp_nonce'); ?>',
                        type: 'POST',
                        data: {
                            action: 'wcdp_feedback_survey',
                            cause_id: $('.wcdp-survey:checked').attr('id'),
                            cause_details: $('#wcdp_feedback_comments').val(),
                            type: 'wcdp_feedback_survey'
                        },
                        success: function () {
                            $('#wcdp-feedback-modal').remove();
                        },
                        error: function (xhr) {
                            console.log('WCDP: Error occurred. Please try again' + xhr.statusText + xhr.responseText);
                            $('#wcdp-feedback-modal').remove();
                        },
                    });
                });
            });
        </script>
    <?php }

    /**
     * Add Deactivation Survey HTML, JS & CSS
     *
     * @return void
     * @since v1.2.7
     */
    public function get_source_data_callback()
    {
        global $pagenow;
        if ($pagenow == 'plugins.php') {
            $this->on_deactivation_html();
            $this->modal_css();
            $this->on_deactivation_js();
        }
    }

    /**
     * echo html of survey modal
     *
     * @return void
     */
    public function on_deactivation_html()
    { ?>
        <div class="wcdp-modal" id="wcdp-feedback-modal">
            <div class="wcdp-modal-wrap">

                <div class="wcdp-modal-header">
                    <h2><?php esc_html_e("Can you please help me to improve the plugin?", "wc-donation-platform"); ?></h2>
                    <button class="wcdp-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
                </div>

                <div class="wcdp-modal-body">
                    <h3><?php esc_html_e("If you have a moment, please let me know why you are deactivating Donation Platform for WooCommerce.", "wc-donation-platform"); ?></h3>
                    <ul class="wcdp-modal-input">
                        <?php foreach ($this->deactivation_survey_options as $key => $option) { ?>
                            <li>
                                <label>
                                    <input type="radio" id="<?php echo esc_attr($option['id']); ?>" class="wcdp-survey"
                                           name="wcdp-survey" value="<?php echo esc_attr($option['text']); ?>">
                                    <div class="wcdp-reason-text"><?php echo esc_html($option['text']); ?></div>
                                    <?php if (isset($option['input']) && $option['input']) { ?>
                                        <textarea placeholder="<?php echo esc_attr($option['placeholder']); ?>"
                                                  class="wcdp-reason-input <?php echo $key == 0 ? 'wcdp-active' : ''; ?> <?php echo esc_html($option['id']); ?>"></textarea>
                                    <?php } ?>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>

                    <div class="wcdp-modal-footer">
                        <a class="wcdp-modal-submit"
                           href="#"><?php esc_html_e("Submit & Deactivate", "wc-donation-platform"); ?><span
                                    class="dashicons dashicons-update rotate"></span></a>
                        <a class="wcdp-modal-on-deactivation"
                           href="#"><?php esc_html_e("Skip & Deactivate", "wc-donation-platform"); ?></a>
                    </div>

                    <p class="wcdp-greyed"><?php esc_html_e("By submitting the survey, you agree that some non-sensitive technical diagnostic data will be send.", "wc-donation-platform"); ?></p>

                </div>
            </div>
        </div>
    <?php }

    /**
     * Deactivation Forms JS script
     *
     * @return void
     * @since v1.2.7
     */
    public function on_deactivation_js()
    { ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                'use strict';

                // Modal Radio Input Click Action
                $('.wcdp-modal-input input[type=radio]').on('change', function (e) {
                    $('.wcdp-reason-input').removeClass('wcdp-active');
                    $('.wcdp-modal-input').find('.' + $(this).attr('id')).addClass('wcdp-active');
                });

                // Modal Cancel Click Action
                $(document).on('click', '.wcdp-modal-cancel', function (e) {
                    $('#wcdp-feedback-modal').removeClass('modal-active');
                });

                // Deactivate Button Click Action
                $(document).on('click', '#deactivate-wc-donation-platform', function (e) {
                    e.preventDefault();
                    $('#wcdp-feedback-modal').addClass('modal-active');
                    $('.wcdp-modal-on-deactivation').attr('href', $(this).attr('href'));
                    $('.wcdp-modal-submit').attr('href', $(this).attr('href'));
                });

                // Submit to Server
                $(document).on('click', '.wcdp-modal-submit', function (e) {
                    e.preventDefault();

                    $(this).addClass('loading');
                    const url = $(this).attr('href')

                    $.ajax({
                        url: '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wcdp_nonce'); ?>',
                        type: 'POST',
                        data: {
                            action: 'wcdp_feedback_survey',
                            cause_id: $('input[type=radio]:checked').attr('id'),
                            cause_details: $('.wcdp-reason-input.wcdp-active').val(),
                            type: 'wcdp_deactivation_survey'
                        },
                        success: function () {
                            $('#wcdp-feedback-modal').removeClass('modal-active');
                            window.location.href = url;
                        },
                        error: function (xhr) {
                            console.log('WCDP: Error occurred. Please try again' + xhr.statusText + xhr.responseText);
                        },
                    });
                });
            });
        </script>
    <?php }
}
