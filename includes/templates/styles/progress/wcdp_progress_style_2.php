<?php
if(!defined('ABSPATH')) exit;

$label = sprintf($label, '<span class="wcdp-emphasized">' . wc_price($revenue) . '</span>', '<span class="wcdp-normal">' . wc_price($atts['goal']) . '</span>');

?>
<style>
	:root{
	--wcdp-main: ' . sanitize_hex_color(get_option('wcdp_secondary_color', '#30bf76')) . ';
	--wcdp-main-2: '. sanitize_hex_color(get_option('wcdp_main_color', '#00753a')) . ';
	--label-text-checked: white;
	}
	.wcdp-fundraising-progress {
		margin-bottom: 1.5em;
	}
	.wcdp-emphasized {
		font-size: 1.7em;
		font-weight: bold;
	}
	.wcdp-thermometer {
		height: 1em;
		border-radius: 0.5em;
	}
	.wcdp-thermometer-bg {
		background-color: var(--wcdp-main);
		margin: 0;
		height: 1em;
	}
	.wcdp-progress > .wcdp-thermometer-fg {
		background-color: var(--wcdp-main-2);
		margin-top: -1em;
		animation: progress 1s ease-in;
	}
	@keyframes progress {
		0% {
			width: 0%;
		}
	}
	.wcdp-column {
		float: left;
		width: 50%;
	}
	.wcdp-column:nth-child(2) {
		text-align:right;
	}
	.wcdp-progress-row:after {
		content: "";
		display: table;
		clear: both;
	}
</style>

<div class="wcdp-fundraising-progress">
	<div class="wcdp-progress-row">
		<?php
		if ($goal_db != '' && $goal_db > 0) {
			?> <div class="wcdp-column"> <?php
			echo $label;
				?> </div> <?php
		}
		?>
		<div class="wcdp-column">
			<?php
			if ($end_date_db != '') {
				echo $this->get_human_time_diff($end_date_db);
			}
			?>
		</div>
	</div>
	<?php if ($goal_db != '' && $goal_db > 0) : ?>
		<div class="wcdp-progress">
			<div class="wcdp-thermometer wcdp-thermometer-bg"></div>
			<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo $width; ?>%"></div>
		</div>
	<?php endif; ?>
</div>

