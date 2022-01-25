<?php
if(!defined('ABSPATH')) exit;

$label = sprintf($label, wc_price($revenue), wc_price($atts['goal']));
?>
<style>
	:root{
	--wcdp-main: <?php echo sanitize_hex_color(get_option('wcdp_secondary_color', '#30bf76')) ?>;
	--wcdp-main-2: <?php echo sanitize_hex_color(get_option('wcdp_main_color', '#00753a')) ?>;
	--label-text-checked: white;
	}
	.wcdp-thermometer {
	height: 2em;
		border-radius: 0.5em;
	}
	.wcdp-thermometer-bg {
	background-color: var(--wcdp-main);
		margin: 0;
		height: 2em;
	}
	.wcdp-progress > .wcdp-thermometer-fg {
	background-color: var(--wcdp-main-2);
		margin-top: -2em;
		animation: progress 2s ease-in;
	}
	@keyframes progress {
	0% {
		width: 0%;
	}
	}
	.wcdp-thermometer > .wcdp-label, .wcdp-thermometer > .wcdp-label .woocommerce-Price {
	white-space: nowrap;
		color: var(--label-text-checked);
		text-align: right;
		padding: 0 1ch 0 1ch;
		font-size: 1em;
		line-height: 2em;
	}
</style>
<div class="wcdp-progress"><div class="wcdp-thermometer wcdp-thermometer-bg"></div>
<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo $width; ?>%">
	<div class="wcdp-label"><?php echo $label; ?></div>
</div>
