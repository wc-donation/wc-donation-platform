<?php
/**
 * Progress bar template style 10
 * /includes/templates/styles/progress/wcdp_progress_style_10.php
 *
 * @var string $percentage_formatted
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

        echo wp_kses_post($percentage_formatted); ?>
    </span>
</div>
