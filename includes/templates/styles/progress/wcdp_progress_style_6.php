<?php
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
        <?php echo wc_price($atts['goal']); ?>
    </span>
</div>
