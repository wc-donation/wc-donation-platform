<?php
if(!defined('ABSPATH')) exit;

$label = sprintf($label, '<span class="wcdp-emphasized">' . wc_price($revenue) . '</span>', '<span class="wcdp-normal">' . wc_price($atts['goal']) . '</span>');

if (!defined('WCDP_PROGRESS_3')) :
	define('WCDP_PROGRESS_3', 1);
	?>
		:root{
			--wcdp-main: <?php echo sanitize_hex_color(get_option('wcdp_secondary_color', '#30bf76')) ?>;
			--wcdp-main-2: <?php echo sanitize_hex_color(get_option('wcdp_main_color', '#00753a')) ?>;
			--label-text-checked: white;
		}
		.wcdp-progress-style-3 {
			margin-bottom: 0.5em;
		}
		.wcdp-progress-style-3 .wcdp-emphasized {
			font-weight: bold;
		}
		.wcdp-progress-style-3 .wcdp-thermometer {
			height: 0.5em;
			border-radius: 0.5em;
		}
		.wcdp-progress-style-3 .wcdp-thermometer-bg {
			background-color: var(--wcdp-main);
			margin: 0;
			height: 0.5em;
		}
		.wcdp-progress-style-3 .wcdp-progress > .wcdp-thermometer-fg {
			background-color: var(--wcdp-main-2);
			margin-top: -0.5em;
			animation: wcdp-progress 1s ease-in;
		}
	<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-3">
	<?php if ($goal_db != '' && $goal_db > 0) : ?>
		<div class="wcdp-column">
			<?php echo $label; ?>
		</div>
		<div class="wcdp-progress">
			<div class="wcdp-thermometer wcdp-thermometer-bg"></div>
			<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo esc_attr($width); ?>%"></div>
		</div>
	<?php endif; ?>
	<?php
	if ($end_date_db != '') {
		echo $this->get_human_time_diff($end_date_db);
	}
	?>
</div>

