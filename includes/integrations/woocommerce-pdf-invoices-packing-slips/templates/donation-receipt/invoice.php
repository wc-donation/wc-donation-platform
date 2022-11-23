<?php
/**
 * Template of a tax deductible receipt
 * Please adapt this template according to your local legal requirements!
 *
 * forked from https://github.com/wpovernight/woocommerce-pdf-invoices-packing-slips/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="invoice-page">
	<?php
	$shop_name = esc_html($this->get_shop_name());

	do_action( 'wpo_wcpdf_before_document', $this->type, $this->order );
	?>

		<table class="head container">
			<tr>
				<td class="donor-info">
					<div class="divider-top"></div>
					<div class="sender">
						<?php
						//Translators: %$1s blog name, %2$s shop base address, %3$s shop base postal code, %4$s %2$s shop base city
						printf( esc_html__( '%1$s, %2$s, %3$s %4$s', 'wc-donation-platform' ), $shop_name, esc_html(WC()->countries->get_base_address()), esc_html( WC()->countries->get_base_postcode() ), esc_html( WC()->countries->get_base_city() ) );
						?>
					</div>
					<div class="divider"></div>
					<?php do_action( 'wpo_wcpdf_before_billing_address', $this->type, $this->order ); ?>
					<?php $this->billing_address(); ?>
					<?php do_action( 'wpo_wcpdf_after_billing_address', $this->type, $this->order ); ?>
					<?php if ( isset($this->settings['display_email']) ) { ?>
						<div class="billing-email"><?php $this->billing_email(); ?></div>
					<?php } ?>
					<?php if ( isset($this->settings['display_phone']) ) { ?>
						<div class="billing-phone"><?php $this->billing_phone(); ?></div>
					<?php } ?>
				</td>
				<td class="shop-info">
					<?php
					if( $this->has_header_logo() ) {
						$this->header_logo();
					}
					?>
					<?php do_action( 'wpo_wcpdf_before_shop_name', $this->type, $this->order ); ?>
					<div class="shop-name"><h3><?php echo $shop_name; ?></h3></div>
					<?php do_action( 'wpo_wcpdf_after_shop_name', $this->type, $this->order ); ?>
					<?php do_action( 'wpo_wcpdf_before_shop_address', $this->type, $this->order ); ?>
					<div class="shop-address"><?php $this->shop_address(); ?></div>
					<?php do_action( 'wpo_wcpdf_after_shop_address', $this->type, $this->order ); ?>
				</td>
			</tr>
		</table>

	<?php do_action( 'wpo_wcpdf_after_document_label', $this->type, $this->order ); ?>

		<h3 class="heading">
			<?php esc_html_e('Tax Deductible Receipt', 'wc-donation-platform'); ?>
		</h3>
		<div class="divider"></div>
		<div class="divider"></div>

	<p class="right">
		<?php
		//Translators: %$1s shop city, %$2 date of receipt creation
		printf( esc_html__( '%1$s, %2$s', 'wc-donation-platform' ), esc_html(WC()->countries->get_base_city()), esc_html( $this->get_invoice_date() ) );
		?>
	</p>
	<p>
		<?php
			//Translators: %$1s firstname, %$2s lastname
			printf( esc_html__( 'Dear %1$s %2$s,', 'wc-donation-platform' ), esc_html( $this->order->get_billing_first_name() ), esc_html($this->order->get_billing_last_name()) );
		?>
	</p>
	<p>
		<?php
			//translators: %s Shop Name
			printf( esc_html__( 'Here is the receipt for your generous donation to %s. Thank you so much for donating.', 'wc-donation-platform' ), $shop_name );
		?>
	</p>
		<div class="divider"></div>
	<table>
		<tr>
			<th class="order-number">
				<?php
				//translators: %s Donation number
				printf( esc_html__( 'Summary of your donation #%s', 'wc-donation-platform' ), intval($this->order->get_id()) );
				?>
			</th>
		</tr>
	</table>
		<table>
			<tr class="organization">
				<td>
					<?php
					//translators: %s Shop Name
					esc_html_e( 'Organization: ', 'wc-donation-platform' );
					?>
				</td>
				<td>
					<?php echo $shop_name; ?>
				</td>
			</tr>
			<tr class="amount">
				<td>
					<?php
					esc_html_e( 'Amount:', 'wc-donation-platform' );
                    echo ' ';
					?>
				</td>
				<td>
					<?php echo $this->order->get_formatted_order_total() ?>
				</td>
			</tr>
			<tr class="date">
				<td>
					<?php
                    esc_html_e( 'Donation Date:', 'wc-donation-platform' );
                    echo ' ';
                    ?>
				</td>
				<td>
					<?php $this->order_date() ?>
				</td>
			</tr>
			<tr class="donor">
				<td>
					<?php
                    esc_html_e( 'Donor:', 'wc-donation-platform' );
                    echo ' ';
                    ?>
				</td>
				<td>
					<?php echo wp_kses($this->get_billing_address(), array('br'=>array())); ?>
				</td>
			</tr>
			<tr class="payment_method">
				<td>
					<?php
                    esc_html_e( 'Payment Method:', 'wc-donation-platform' );
                    echo ' ';
                    ?>
				</td>
				<td>
					<?php echo esc_html($this->get_payment_method()); ?>
				</td>
			</tr>
		</table>
	<div class="divider"></div>
	<p>
		<?php //Translators: %$1s, %$2s shop name
		printf( esc_html__( '%1$s is a registered non-profit organization. Your donation is tax deductible to the extent allowable by law. No goods or service were provided by %2$s in return for this donation.', 'wc-donation-platform' ), $shop_name, $shop_name ); ?>
	</p>
	<div class="divider"></div>
	<p>
		<?php esc_html_e( 'Best regards,', 'wc-donation-platform' ); ?><br>
		<?php //Translators: %$1s shop name
		printf( esc_html__( 'Your friends at %s', 'wc-donation-platform' ), $shop_name ); ?>
	</p>


		<div class="bottom-spacer"></div>

	<?php do_action( 'wpo_wcpdf_after_order_details', $this->type, $this->order ); ?>

			<?php if ( $this->get_footer() ): ?>
		<div id="footer">
					<?php $this->footer(); ?>
		</div><!-- #letter-footer -->
			<?php endif; ?>
	<?php do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>
</div>
