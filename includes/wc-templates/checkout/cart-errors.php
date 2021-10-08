<?php
/**
 * Cart errors page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/cart-errors.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * forked from WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;
?>

<p><?php esc_html_e( 'There are some issues with your donation session. Please reload the page and try again.', 'wc-donation-platform' ); ?></p>

<?php do_action( 'woocommerce_cart_has_errors' ); ?>

<p><a class="button wc-backward" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Resolve issues', 'wc-donation-platform' ); ?></a></p>
