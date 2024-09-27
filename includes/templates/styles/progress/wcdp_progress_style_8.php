<?php
if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_8')) :
    define('WCDP_PROGRESS_8', 1);
    ?>
    .wcdp-progress-style-8 .wcdp-emphasized {
        font-size: 1.7em;
        font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-8">
    <?php echo sprintf($label, '<span class="wcdp-emphasized">' . $revenue_formatted . '</span>', '<span class="wcdp-normal">' . $goal_formatted . '</span>'); ?>
</div>
