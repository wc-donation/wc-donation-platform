<?php
/**
 * Document structure
 * forked from https://github.com/wpovernight/woocommerce-pdf-invoices-packing-slips/
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly ?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo esc_html($this->get_shop_name()); ?></title>
    <style type="text/css">
        <?php $this->template_styles(); ?>
    </style>
    <style type="text/css">
        <?php do_action('wpo_wcpdf_custom_styles', $this->get_type(), $this); ?>
    </style>
</head>

<body class="<?php echo $this->get_type(); ?>">
    <?php echo $content; ?>
</body>

</html>