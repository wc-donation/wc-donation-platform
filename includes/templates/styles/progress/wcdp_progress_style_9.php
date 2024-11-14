<?php
if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_9')) :
    define('WCDP_PROGRESS_9', 1);
    ?>
    .wcdp-progress-style-9 .wcdp-emphasized {
    font-size: 1.7em;
    font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-9">
    <span class="wcdp-emphasized">
        <?php echo $revenue_formatted; ?>
    </span>
</div>
