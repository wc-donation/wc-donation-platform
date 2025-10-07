<?php
/**
 * WCDP Donation Form Template
 *
 * @var array $value
 * @var WC_Product $product
 * @var string $context
 * @var int $product_id,
 * @var bool $has_child,
 * @var WC_Checkout $checkout,
 */

if (!defined('ABSPATH'))
    exit;
?>

<noscript>
    <ul class="woocommerce-error" role="alert">
        <li><?php esc_html_e('Please activate JavaScript to continue with your donation.', 'wc-donation-platform') ?>
        </li>
    </ul>
</noscript>

<?php
$form_id = wp_unique_id('wcdp_') . '_';

//Encapsulate donation form in a popup?
if ($value['popup']):
    ?>
    <div id="wcdp-form" class="wcdp-overlay">
        <div id="wcdp-cancel" class="wcdp-modal-close"></div>
        <div id="wcdp-popup" class="wcdp-popup">
            <div aria-label="<?php esc_html_e('Close', 'wc-donation-platform'); ?>" class="wcdp-close wcdp-modal-close">
                <svg viewbox="0 0 40 40">
                    <path class="close-x" d="M 10,10 L 30,30 M 30,10 L 10,30" />
                </svg>
            </div>
            <div class="wcdp">
            <?php endif; ?>

            <div class="wc-donation-platform woocommerce wcdp-form <?php echo esc_attr($value['className']); ?>"
                id="wcdp" style="visibility:hidden" data-formid="<?php echo $form_id; ?>">
                <div class="lds-ellipsis wcdp-loader">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                <?php

                //Display title of product
                if ($value['image']) {
                    echo $product->get_image('large');
                }

                switch ($value['style']) {
                    case 2:
                        //Style 2: one-page form
                        wc_get_template(
                            'styles/wcdp_form_style_2.php',
                            array(
                                'value' => $value,
                                'product' => $product,
                                'context' => $context,
                                'product_id' => $product_id,
                                'has_child' => $has_child,
                                'checkout' => $checkout,
                                'form_id' => $form_id,
                            ),
                            '',
                            WCDP_DIR . 'includes/templates/'
                        );
                        break;
                    case 3:
                        //Style 3: 3 steps, without header
                        wc_get_template(
                            'styles/wcdp_form_style_3.php',
                            array(
                                'value' => $value,
                                'product' => $product,
                                'context' => $context,
                                'product_id' => $product_id,
                                'has_child' => $has_child,
                                'checkout' => $checkout,
                                'form_id' => $form_id,
                            ),
                            '',
                            WCDP_DIR . 'includes/templates/'
                        );
                        break;
                    case '4':
                        //Just first step, only on Product page
                        wc_get_template(
                            'styles/wcdp_form_style_4.php',
                            array(
                                'value' => $value,
                                'product' => $product,
                                'context' => $context,
                                'product_id' => $product_id,
                                'has_child' => $has_child,
                                'checkout' => $checkout,
                                'form_id' => $form_id,
                            ),
                            '',
                            WCDP_DIR . 'includes/templates/'
                        );
                        break;
                    case '5':
                        //Just first step, only on Product page
                        wc_get_template(
                            'styles/wcdp_form_style_5.php',
                            array(
                                'value' => $value,
                                'product' => $product,
                                'context' => $context,
                                'product_id' => $product_id,
                                'has_child' => $has_child,
                                'checkout' => $checkout,
                                'form_id' => $form_id,
                            ),
                            '',
                            WCDP_DIR . 'includes/templates/'
                        );

                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'checkout':
                        if ($context == 'checkout') {
                            //Just first step, only on Checkout page
                            wc_get_template(
                                'styles/wcdp_form_style_checkout.php',
                                array(
                                    'value' => $value,
                                    'product' => $product,
                                    'context' => $context,
                                    'product_id' => $product_id,
                                    'has_child' => $has_child,
                                    'checkout' => $checkout,
                                    'form_id' => $form_id,
                                ),
                                '',
                                WCDP_DIR . 'includes/templates/'
                            );

                            break;
                        }
                    //no break here
                    default:
                        //Default Style: 3 steps, with progress bar
                        wc_get_template(
                            'styles/wcdp_form_style_1.php',
                            array(
                                'value' => $value,
                                'product' => $product,
                                'context' => $context,
                                'product_id' => $product_id,
                                'has_child' => $has_child,
                                'checkout' => $checkout,
                                'form_id' => $form_id,
                            ),
                            '',
                            WCDP_DIR . 'includes/templates/'
                        );

                        break;
                }

                ?>
            </div>

            <?php
            //Closing divs of popup
            if ($value['popup']):
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>