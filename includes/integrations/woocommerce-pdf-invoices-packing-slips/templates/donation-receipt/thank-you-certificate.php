<?php
/**
 *
 * Template of a Thank You Certificate
 * example 1: https://wcdp.jonh.eu/wp-content/uploads/example-charity_thank-you-certificate_382.pdf
 * example 2: https://wcdp.jonh.eu/wp-content/uploads/example-charity_thank-you-certificate_382_p.pdf
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$shop_name = esc_html($this->get_shop_name());

do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>
<?php
//filter: Order ID, donation amount
$url= apply_filters('wcdp_certificate_background_image', $this->get_setting('background', ''), $this->order->get_id(), $this->order->get_total());
if ($url) :?>
	<style>
		body {
			background-image: url("<?php echo esc_url( $url, array('http', 'https')); ?>");
			background-repeat: no-repeat;
			background-position: center;
			background-size: cover;
		}
	</style>
<?php endif; ?>
<div class="frame-banner"></div>
<div class="banner">
	<table class="table-banner">
		<tr>
			<th class="left-table">
				<h4><?php /*translators: Donation Number */
					printf( esc_html__( '#%s', 'wc-donation-platform' ), $order->get_id()); ?></h4>
				<p class="label">
					<?php esc_html_e("Donation Number", 'wc-donation-platform'); ?>
				</p>
			</th>
			<th class="center-table">
				<?php
				if( $this->has_header_logo() ) {
					$this->header_logo();
				}
				?>
				<h4><?php esc_html_e("Thank you", 'wc-donation-platform'); ?></h4>
				<h1><?php /*translators: 1. donor firstname, 2. donor second name */
					printf( esc_html__( '%1$s %2$s', 'wc-donation-platform' ), esc_html( $order->get_billing_first_name() ), esc_html($order->get_billing_last_name()) );?></h1>
				<h4><?php /*translators: 1. donation amount, 2. shop name */
					printf( esc_html__('for donating %1$s to %2$s.', 'wc-donation-platform'), $order->get_formatted_order_total(), $shop_name); ?></h4>
				<h4><?php esc_html_e("Your support helps us to realize our projects.", 'wc-donation-platform'); ?></h4>

				<?php $url_signature= esc_url($this->get_setting('signature', ''), array('http', 'https'));
				if ($url_signature) :?>
					<img src="<?php echo $url_signature; ?>" style="width: 5cm;"><br>
				<?php endif; ?>
				<p><?php printf( esc_html__( 'Your friends at %s', 'wc-donation-platform' ), $shop_name ); ?></p>
			</th>
			<th class="right-table">
				<h4><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></h4>
				<p class="label">
					<?php esc_html_e("Donation Date", 'wc-donation-platform'); ?>
				</p>
			</th>
		</tr>
	</table>
</div>
