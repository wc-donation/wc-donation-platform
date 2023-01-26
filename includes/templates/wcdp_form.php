<?php
/**
 * WCDP Donation Form Template
 */

if(!defined('ABSPATH')) exit;
?>

<noscript>
    <ul class="woocommerce-error" role="alert">
        <li><?php esc_html_e('Please activate JavaScript to continue with your donation.', 'wc-donation-platform') ?></li>
    </ul>
</noscript>

<?php
//Encapsulate donation form in a popup?
if ($value['popup']) :
?>
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
            //Style 2: one-page form
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
		case '5':
			//Just first step, only on Product page
			include_once 'styles/wcdp_form_style_5.php';
			break;
        /** @noinspection PhpMissingBreakStatementInspection */
        case 'checkout':
            if ($is_internal) {
                //Just first step, only on Checkout page
                include_once 'styles/wcdp_form_style_checkout.php';
                break;
            }
			//no break here
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
