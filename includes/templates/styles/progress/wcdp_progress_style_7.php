<?php
/**
 * Progress bar template style 7
 * /includes/templates/styles/progress/wcdp_progress_style_7.php
 *
 * @var string $label
 * @var string $revenue_formatted
 * @var string $goal_formatted
 * @var float $goal_db
 * @var string $end_date_db
 * @var float $width
 * @var float $goal
 * @var float $revenue
 */

if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_7')) :
    define('WCDP_PROGRESS_7', 1);
    ?>
    .wcdp-progress-style-7 .wcdp-emphasized {
        font-size: 1.7em;
        font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-7">
    <span class="wcdp-emphasized">
        <?php echo apply_filters('wcdp_progress_remaining', wc_price($goal - $revenue)); ?>
    </span>
</div>
