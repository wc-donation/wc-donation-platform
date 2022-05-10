<?php
if(!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_1')) :
	define('WCDP_PROGRESS_1', 1);
	?>
	.wcdp-progress-style-1 .wcdp-thermometer {
		height: 2em;
		border-radius: 0.5em;
	}
	.wcdp-progress-style-1 .wcdp-thermometer-bg {
		background-color: var(--wcdp-main);
		margin: 0;
		height: 2em;
	}
	.wcdp-progress-style-1 .wcdp-progress > .wcdp-thermometer-fg {
		background-color: var(--wcdp-main-2);
		margin-top: -2em;
		animation: wcdp-progress 2s ease-in;
	}
	.wcdp-progress-style-1 .wcdp-thermometer > .wcdp-label, .wcdp-thermometer > .wcdp-label .woocommerce-Price {
		white-space: nowrap;
		color: var(--label-text-checked);
		text-align: right;
		padding: 0 1ch 0 1ch;
		font-size: 1em;
		line-height: 2em;
	}
<?php endif;
$label = sprintf($label, wc_price($revenue), wc_price($atts['goal']));
?>
</style>

<div class="wcdp-progress-style-1">
	<div class="wcdp-progress"><div class="wcdp-thermometer wcdp-thermometer-bg"></div>
		<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo esc_attr($width); ?>%">
			<div class="wcdp-label"><?php echo $label; ?></div>
		</div>
</div>
