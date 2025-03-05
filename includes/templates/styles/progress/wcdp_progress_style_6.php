<?php
/**
 * Progress bar template style 6
 * /includes/templates/styles/progress/wcdp_progress_style_6.php
 *
 * @var string $goal_formatted
 */

if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_6')) :
    define('WCDP_PROGRESS_6', 1);
    ?>
    .wcdp-progress-style-6 .wcdp-emphasized {
        font-size: 1.7em;
        font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-6">
    <span class="wcdp-emphasized">
        <?php echo $goal_formatted; ?>
    </span>
</div>
