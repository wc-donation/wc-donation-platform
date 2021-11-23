<?php
/*
WCDP Shortcode Form Style 5: 3 steps with banner header
*/

if(!defined('ABSPATH')) exit;
?>

<div class="wcdp-steps-wrapper">
  <div class="wcdp-style5 wcdp-step wcdp-style5-active" id="wcdp-style5-step-1" step="1" value="1">
	  <span><?php esc_html_e('1. Amount', 'wc-donation-platform') ?></span>
  </div>
  <div class="wcdp-style5 wcdp-step" id="wcdp-style5-step-2" step="2" value="2">
	  <span><?php esc_html_e('2. Details', 'wc-donation-platform') ?></span>
  </div>
  <div class="wcdp-style5 wcdp-step" id="wcdp-style5-step-3" step="3" value="3">
	  <span><?php esc_html_e('3. Payment', 'wc-donation-platform') ?></span>
  </div>
</div>

<?php
include('wcdp_form_style_3.php');
