<?php
/**
 * Progress bar template style 10
 * /includes/templates/styles/progress/wcdp_progress_style_10.php
 *
 * @var string $goal
 * @var float $revenue
 * @var int $percentage_decimals
 */

if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_10')) :
    define('WCDP_PROGRESS_10', 1);
    ?>
    .wcdp-progress-style-10 .wcdp-emphasized {
    font-size: 1.7em;
    font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-10">
    <span class="wcdp-emphasized">
        <?php
        if ($goal != 0) {
            $percentage = ($revenue * 100) / $goal;
        } else {
            $percentage = 0;
        }
        $percentage_formatted = wc_format_decimal($percentage, $percentage_decimals) . '%';

        echo wp_kses_post(apply_filters('wcdp_percentage_formatted', $percentage_formatted)); ?>
    </span>
</div>
