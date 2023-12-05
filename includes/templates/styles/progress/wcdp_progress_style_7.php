<?php
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
        <?php echo wc_price($atts['goal'] - $revenue); ?>
    </span>
</div>
