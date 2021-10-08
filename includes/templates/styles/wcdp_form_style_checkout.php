<?php
/*
WCDP Shortcode Form Style 4: Redirect to checkout
*/

if(!defined('ABSPATH')) exit;

$page_id = wc_get_page_id( 'checkout' );
if (!($page_id && is_page( $page_id ))) {
	return;
}
?>
    <div class="wcdp-body">
        <?php include(WCDP_DIR . 'includes/templates/wcdp_step_1.php'); ?>
        <svg class="wcdp-divider-arrow" width="90%" height="100%" viewBox="0 0 113 4" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><rect id="export" x="0" y="0" width="112.8" height="3.84" style="fill:none;"></rect><clipPath id="_clip1"><rect x="0" y="0" width="112.8" height="3.84"></rect></clipPath><g clip-path="url(#_clip1)"><path d="M56.485,-4.458l-4.243,4.243l4.073,4.073l4.243,-4.243l-4.073,-4.073Zm-0,0.863l3.21,3.21c0,0 -3.38,3.38 -3.38,3.38c0,0 -3.21,-3.21 -3.21,-3.21l3.38,-3.38Z" fill="var(--controls)"></path><rect x="-0" y="-0" width="53.28" height="0.6" style="fill:url(#_Linear2);"></rect><rect x="59.52" y="-0" width="53.28" height="0.6" style="fill:url(#_Linear3);"></rect></g><defs><linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(53.28,0,0,0.6,-1.15769e-13,0.3)"><stop offset="0" style="stop-color:transparent;stop-opacity:1"></stop><stop offset="0.16" style="stop-color: var(--controls);stop-opacity:1"></stop><stop offset="0.35" style="stop-color: var(--controls);stop-opacity:1"></stop><stop offset="1" style="stop-color: var(--controls);stop-opacity:1"></stop></linearGradient><linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(-53.28,-6.52492e-15,7.34788e-17,-0.6,112.8,0.3)"><stop offset="0" style="stop-color:transparent;stop-opacity:1"></stop><stop offset="0.16" style="stop-color: var(--controls);stop-opacity:1"></stop><stop offset="0.35" style="stop-color: var(--controls);stop-opacity:1"></stop><stop offset="1" style="stop-color: var(--controls);stop-opacity:1"></stop></linearGradient></defs></svg>
    </div>
