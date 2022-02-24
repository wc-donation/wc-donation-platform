<?php
/**
WCDP Shortcode Form Style 3: steps with no header
*/

if (!defined('ABSPATH')) exit;
?>
<div class="wcdp-body">
	<div class="wcdp-tab" id="wcdp-step-1">
		<?php include( WCDP_DIR . 'includes/templates/wcdp_step_1.php' ); ?>
	</div>
	<?php /** @var TYPE_NAME $checkout */
	do_action('woocommerce_before_checkout_form', $checkout); ?>
	<form name="checkout" method="post" class="checkout woocommerce-checkout"
		  action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
		<div class="wcdp-tab" id="wcdp-step-2">
			<?php include( WCDP_DIR . 'includes/templates/wcdp_step_2.php' ); ?>
			<br>
			<button type="button" class="button wcdp-button wcdp-left" value="1">
				<div class="wcdp-arrow wcdp-left-arrow">&laquo;</div>&nbsp;<?php _e('Back', 'wc-donation-platform'); ?>
			</button>
			<button type="button" class="button wcdp-button wcdp-right"
					value="3"><?php _e('Next', 'wc-donation-platform'); ?>&nbsp;
				<div class="wcdp-arrow wcdp-right-arrow">&raquo;</div>
			</button>
		</div>
		<div class="wcdp-tab" id="wcdp-step-3">
			<?php include( WCDP_DIR . 'includes/templates/wcdp_step_3.php' ); ?>
			<br>
			<button type="button" class="button wcdp-button wcdp-left" value="2">
				<div class="wcdp-arrow wcdp-left-arrow">&laquo;</div>
				&nbsp;<?php _e('Back', 'wc-donation-platform'); ?></button>
		</div>
	</form>

	<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>
