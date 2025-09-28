<?php
/**
 * Progress bar template style 4
 * /includes/templates/styles/progress/wcdp_progress_style_4.php
 *
 * @var string $revenue_formatted
 * @var string $goal_formatted
 * @var string $end_date_db
 * @var float $width
 * @var float $goal
 * @var string $percentage_formatted
 * @var float $revenue
 */

$aria_label = esc_attr(wp_kses(
        sprintf(
        /* translators: 1: percentage raised formatted */
                __('%1$s raised', 'wc-donation-platform'),
                $percentage_formatted
        ), []));

if (!defined('ABSPATH'))
    exit;

if (!defined('WCDP_PROGRESS_2')):
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
        <div class="wcdp-column">
            <span class="wcdp-emphasized">
                <?php echo wp_kses_post($percentage_formatted); ?>
            </span>
        </div>
        <div class="wcdp-column">
            <?php
            if ($end_date_db != '') {
                echo WCDP_Progress::get_human_time_diff($end_date_db);
            }
            ?>
        </div>
    </div>
    <?php if ($goal != '' && $goal > 0): ?>
        <div class="wcdp-progress" role="progressbar" aria-valuenow="<?php echo esc_attr($width); ?>" aria-valuemin="0"
            aria-valuemax="100" aria-label="<?php echo $aria_label; ?>">
            <div class="wcdp-thermometer wcdp-thermometer-bg"></div>
            <div class="wcdp-thermometer wcdp-thermometer-fg" style="width: <?php echo esc_attr($width); ?>%"></div>
        </div>
    <?php endif; ?>
</div>