<?php
/*
WCDP Shortcode Form Style 6
*/

if (!defined('ABSPATH')) exit;

//insert css variables style block
WCDP_FORM::define_ccs_variables();
?>

<div class="wcdp-body">
    <?php wc_get_template('wcdp_step_1.php',
        array(
            'product_id'=> $product_id,
            'has_child' => $has_child,
            'product' => $product,
            'value' => $value,
            'context' => $context,
        ), '', WCDP_DIR . 'includes/templates/'); ?>
</div>
