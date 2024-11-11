<?php
/**
 * Thank You Certificate Class
 * Forked from https://github.com/wpovernight/woocommerce-pdf-invoices-packing-slips/tree/8418b8caddfdc58b3effa6e17a6e386de14658e2/includes/documents
 */

namespace WPO\IPS\Documents;

use WC_Order;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WCDP_Thank_You_Certificate')) :

    /**
     * Thank You Certificate
     *
     * @class       WCDP_Thank_You_Certificate
     */
    class WCDP_Thank_You_Certificate extends OrderDocumentMethods
    {
        protected static $_instance = null;

        /**
         * Create only one instance of WCDP_Thank_You_Certificate
         * @return WCDP_Thank_You_Certificate|null
         */
        public static function instance(): ?WCDP_Thank_You_Certificate
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Init/load the order object.
         *
         * @param int|object|WC_Order $order Order to init.
         */
        public function __construct($order = 0)
        {
            parent::__construct( $order );

            // set properties
            $this->type = $this->slug = 'thank-you-certificate';
            $this->title = esc_html__('Thank You Certificate', 'wc-donation-platform');
            $this->icon = "";

            if (is_numeric($order) && $order > 0) {
                $this->order_id = $order;
                $this->order = wc_get_order($this->order_id);
            } elseif ($order instanceof WC_Order || is_subclass_of($order, '\WC_Abstract_Order')) {
                $this->order_id = $order->get_id();
                $this->order = $order;
            }
            // load data
            if ($this->order) {
                $this->read_data($this->order);
            }

            $this->settings = $this->get_settings();
            $this->latest_settings = $this->get_settings(true);
            $this->enabled = $this->get_setting('enabled', false);
            $this->output_formats = array('pdf');

            //Setting of thank you certificate orientation
            add_filter('wpo_wcpdf_paper_orientation', array($this, 'paper_orientation'), 10, 2);

            add_filter('wpo_wcpdf_attach_documents', array($this, 'attach_certificate'));

            //Add My Account Download Button
            add_filter('wpo_wcpdf_myaccount_actions', function ($actions, $order) {
                $certificate = wcpdf_get_document('thank-you-certificate', $order);
                if ($certificate && $certificate->is_enabled()) {
                    $pdf_url = wp_nonce_url(admin_url('admin-ajax.php?action=generate_wpo_wcpdf&document_type=thank-you-certificate&order_ids=' . $order->get_id() . '&my-account'), 'generate_wpo_wcpdf');

                    // check my account button settings
                    $button_setting = $certificate->get_setting('my_account_buttons', 'available');
                    $certificate_allowed = false;
                    switch ($button_setting) {
                        case 'available':
                            $certificate_allowed = $certificate->exists();
                            break;
                        case 'always':
                            $certificate_allowed = true;
                            break;
                        case 'never':
                            break;
                        case 'custom':
                            $allowed_statuses = $certificate->get_setting('my_account_restrict', array());
                            if (!empty($allowed_statuses) && in_array($order->get_status($order), array_keys($allowed_statuses))) {
                                $certificate_allowed = true;

                            }
                            break;
                    }

                    // Check if invoice has been created already or if status allows download (filter your own array of allowed statuses)
                    if ($certificate_allowed) {
                        $actions['thank-you-certificate'] = array(
                            'url' => $pdf_url,
                            'name' => $this->get_title(),
                        );
                    }
                }

                return $actions;
            }, 10, 2);
        }

        /**
         * Filename of the downloaded document
         *
         * @param string $context
         * @param array $args
         *
         * @return string
         */
        public function get_filename($context = 'download', $args = array()): string
        {
            $order_ids = $args['order_ids'] ?? array($this->order_id);
            $filename = get_bloginfo('name') . '_' . implode('-', $order_ids);
            return sanitize_title(apply_filters('wpo_wcpdf_filename', $filename, 'thank-you-certificate', $order_ids, $context )) . '.pdf';
        }

        public function init_settings()
        {
            // Register settings.
            $page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_thank-you-certificate';

            $settings_fields = array(
                array(
                    'type' => 'section',
                    'id' => 'thank-you-certificate',
                    'title' => '',
                    'callback' => 'section',
                ),
                array(
                    'type' => 'setting',
                    'id' => 'enabled',
                    'title' => __('Enable', 'wc-donation-platform'),
                    'callback' => 'checkbox',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'enabled',
                    ),
                ),
                array(
                    'type' => 'setting',
                    'id' => 'attach_to_email_ids',
                    'title' => __('Attach to:', 'wc-donation-platform'),
                    'callback' => 'multiple_checkboxes',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'attach_to_email_ids',
                        'fields' => $this->get_wc_emails(),
                        /* translators: directory path */
                        'description' => !wp_is_writable(WPO_WCPDF()->main->get_tmp_path('attachments')) ? '<span class="wpo-warning">' . sprintf(__('It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'wc-donation-platform'), WPO_WCPDF()->main->get_tmp_path('attachments')) . '</span>' : '',
                    ),
                ),
                array(
                    'type' => 'setting',
                    'id' => 'certificate_orientation',
                    'title' => __('Orientation of the Certificate', 'wc-donation-platform'),
                    'callback' => 'select',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'certificate_orientation',
                        'options' => array(
                            'portrait' => __('Portrait', 'wc-donation-platform'),
                            'landscape' => __('Landscape', 'wc-donation-platform'),
                        ),
                    ),
                ),
                array(
                    'type' => 'setting',
                    'id' => 'my_account_buttons',
                    'title' => __('Allow My Account thank you certificate download', 'wc-donation-platform'),
                    'callback' => 'select',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'my_account_buttons',
                        'options' => array(
                            'available' => __('Only when a thank you certificate is already created/emailed', 'wc-donation-platform'),
                            'custom' => __('Only for specific order statuses (define below)', 'wc-donation-platform'),
                            'always' => __('Always', 'wc-donation-platform'),
                            'never' => __('Never', 'wc-donation-platform'),
                        ),
                        'custom' => array(
                            'type' => 'multiple_checkboxes',
                            'args' => array(
                                'option_name' => $option_name,
                                'id' => 'my_account_restrict',
                                'fields' => $this->get_wc_order_status_list(),
                            ),
                        ),
                    ),
                ),
                array(
                    'type' => 'string',
                    'id' => 'background',
                    'title' => __('URL of background image', 'wc-donation-platform'),
                    'callback' => 'text_input',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'background',
                        'size' => '60',
                    ),
                ),
                array(
                    'type' => 'string',
                    'id' => 'signature',
                    'title' => __('URL to image of signature', 'wc-donation-platform'),
                    'callback' => 'text_input',
                    'section' => 'thank-you-certificate',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'signature',
                        'size' => '60',
                    ),
                ),
            );

            // allow plugins to alter settings fields
            $settings_fields = apply_filters('wcdp_settings_fields_documents_thank-you-certificate', $settings_fields, $page, $option_group, $option_name);
            WPO_WCPDF()->settings->add_settings_fields($settings_fields, $page, $option_group, $option_name);
        }

        /**
         * Change paper orientation
         *
         * @param $orientation
         * @param $type
         *
         * @return string
         */
        public function paper_orientation($orientation, $type): string
        {
            if ($type == 'thank-you-certificate' && 'landscape' == $this->get_setting('certificate_orientation', 'landscape')) {
                return 'landscape';
            } else {
                return $orientation;
            }
        }

        /**
         * Since free version of PDF Invoices & Packing Slips for WooCommerce only allows adding invoice as attachment
         * we need this workaround and have to add it manually
         * @param array $attach_documents
         * @return array
         */
        public function attach_certificate(array $attach_documents): array
        {
            $is_enabled = $this->get_setting('enabled', false);
            if (!$is_enabled) return $attach_documents;

            $attach_documents[ 'pdf' ][ 'thank-you-certificate' ] = $this->get_attach_to_email_ids();
            return $attach_documents;
        }
    }

endif; // class_exists
