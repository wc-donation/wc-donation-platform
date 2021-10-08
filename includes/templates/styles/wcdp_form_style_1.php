<?php
/*
WCDP Donation Form
*/

if(!defined('ABSPATH')) exit;
?>

  <div class="wcdp-header">
    <div id="wcdp-progress-bar-background"></div>
    <div id="wcdp-progress-bar"></div>
    <div class="wcdp-step" id="wcdp-header-step-1" step="1" value="1"><?php _e( 'Amount', 'wc-donation-platform' ); ?></div>
    <div class="wcdp-step" id="wcdp-header-step-2" step="2" value="2"><?php _e( 'Details', 'wc-donation-platform' ); ?></div>
    <div class="wcdp-step" id="wcdp-header-step-3" step="3" value="3"><?php _e( 'Payment', 'wc-donation-platform' ); ?></div>
  </div>

<?php
	include('wcdp_form_style_3.php');
