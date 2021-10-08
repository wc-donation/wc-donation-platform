<?php
/**
 * WCDP Donation Form Template
 */

if(!defined('ABSPATH')) exit;
    $wcdp_main_color = get_option('wcdp_secondary_color', '#30bf76');
	$wcdp_main_color_2 = get_option('wcdp_main_color', '#00753a');
	$wcdp_main_color_3 = get_option('wcdp_error_color', '#de0000');
    ?>
    <style id="wcdp-css">
        :root{
            --wcdp-main: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
            --wcdp-main-2: <?php echo sanitize_hex_color($wcdp_main_color_2); ?>;
            --wcdp-main-3: <?php echo sanitize_hex_color($wcdp_main_color_3); ?>;
            --wcdp-step-2: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
            --wcdp-step-3: <?php echo sanitize_hex_color($wcdp_main_color); ?>;
            --label-inactive: LightGray;
            --label-inactive-hover: #b5b5b5;
            --label-text: black;
            --label-text-checked: white;
            --background-color: white;
			--overlay-color: rgba(0, 0, 0, 0.8);
            --controls: black;
        }
    </style>

<noscript>
    <ul class="woocommerce-error" role="alert">
        <li><?php esc_html_e('Please activate JavaScript to continue with your donation.', 'wc-donation-platform') ?></li>
    </ul>
</noscript>

<?php
    //Encapsulate donation form in a popup?
    if ($value['popup']) :
?>
        <?php
            //Show a button to open the donation form?
            if ($value['button']) :
        ?>
			<p>
                <a href="#wcdp-form">
                    <button id="wcdp-button" type="button" class="button wcdp-modal-open">
                        <?php esc_html_e( 'Donate now!', 'wc-donation-platform' ); ?>
                    </button>
                </a>
			</p>
        <?php endif; ?>

        <div id="wcdp-form" class="wcdp-overlay">
            <div id="wcdp-cancel" class="wcdp-modal-close"></div>
            <div id="wcdp-popup">
                <div aria-label="<?php esc_html_e('Close', 'wc-donation-platform'); ?>" class="wcdp-close wcdp-modal-close">
                    <svg viewbox="0 0 40 40">
                        <path class="close-x" d="M 10,10 L 30,30 M 30,10 L 10,30" />
                    </svg>
                </div>
                <div class="wcdp">
<?php endif; ?>

<div class="wc-donation-platform woocommerce <?php esc_attr_e($value['className']);?>" id="wcdp" style="visibility:hidden">
    <div class="lds-ellipsis wcdp-loader"><div></div><div></div><div></div><div></div></div>
    <?php

    //Display title of product
    if ($value['image']) {
        echo $product->get_image('large' );
    }

    switch($value['style']) {
        case 2:
            //Style 2: one page form
            include_once 'styles/wcdp_form_style_2.php';
            break;
        case 3:
            //Style 3: 3 steps, without header
            include_once 'styles/wcdp_form_style_3.php';
            break;
        case '4':
            //Just first step, only on Product page
            include_once 'styles/wcdp_form_style_4.php';
            break;
        case 'checkout':
            if ($is_internal) {
                //Just first step, only on Checkout page
                include_once 'styles/wcdp_form_style_checkout.php';
                break;
            }
        default:
            //Default Style: 3 steps, with progress bar
            include_once 'styles/wcdp_form_style_1.php';
            break;
    }

    ?>
</div>

<?php
//Closing divs of popup
if ($value['popup']) :
    ?>
                </div>
            </div>
        </div>
<?php endif; ?>
