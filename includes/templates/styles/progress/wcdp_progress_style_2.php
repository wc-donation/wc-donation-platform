<?php
if(!defined('ABSPATH')) exit;

$label = sprintf($label, '<span class="wcdp-emphasized">' . wc_price($revenue) . '</span>', '<span class="wcdp-normal">' . wc_price($atts['goal']) . '</span>');

if (!defined('WCDP_PROGRESS_2')) :
define('WCDP_PROGRESS_2', 1);
?>
	.wcdp-progress-style-2 {
		margin-bottom: 1em;
	}
	.wcdp-progress-style-2 .wcdp-emphasized {
		font-size: 1.7em;
		font-weight: bold;
	}
	.wcdp-progress-style-2 .wcdp-thermometer {
		height: 1em;
		border-radius: 0.5em;
	}
	.wcdp-progress-style-2 .wcdp-thermometer-bg {
		background-color: var(--wcdp-main);
		margin: 0;
		height: 1em;
	}
	.wcdp-progress-style-2 .wcdp-progress > .wcdp-thermometer-fg {
		background-color: var(--wcdp-main-2);
		margin-top: -1em;
		animation: wcdp-progress 1s ease-in;
	}
	.wcdp-progress-style-2 .wcdp-column {
		float: left;
		width: 50%;
	}
	.wcdp-progress-style-2 .wcdp-column:nth-child(2) {
		text-align:right;
	}
	.wcdp-progress-style-2 .wcdp-progress-row:after {
		content: "";
		display: table;
		clear: both;
	}
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-2">
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
			<div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo esc_attr($width); ?>%"></div>
		</div>
	<?php endif; ?>
</div>

