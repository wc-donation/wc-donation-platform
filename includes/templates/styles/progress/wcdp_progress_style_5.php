<?php
if (!defined('ABSPATH')) exit;

if (!defined('WCDP_PROGRESS_5')) :
    define('WCDP_PROGRESS_5', 1);
    ?>
    .wcdp-progress-style-5 .wcdp-emphasized {
        font-size: 1.7em;
        font-weight: bold;
    }
<?php endif; ?>
</style>

<div class="wcdp-fundraising-progress wcdp-progress-style-5">
    <?php echo $this->get_human_time_diff($end_date_db); ?>
</div>
