<?php
/**
 * WCDP Addon Hook Tests
 *
 * Outputs visible markers at each wcdp_* hook injection point.
 * Only active when WCDP_ADDON_HOOK_DEBUG is defined and true in wp-config.php.
 *
 * Usage: define( 'WCDP_ADDON_HOOK_DEBUG', true );
 *
 * @package WC_Donation_Platform
 */

if (!defined('ABSPATH'))
    exit;

if (!defined('WCDP_ADDON_HOOK_DEBUG') || !WCDP_ADDON_HOOK_DEBUG)
    return;

class WCDP_Hook_Tests
{
    public function __construct()
    {
        $hooks = array(
            'wcdp_before_donation_form',
            'wcdp_after_donation_form',
            'wcdp_before_step_amount',
            'wcdp_after_step_amount',
            'wcdp_before_donor_details',
            'wcdp_after_donor_details',
        );
        foreach ($hooks as $hook) {
            add_action($hook, array($this, 'marker'), 10, 2);
        }
    }

    public function marker(int $product_id, string $context): void
    {
        printf(
            '<div style="background:#fef9c3;padding:6px 12px;margin:4px 0;font-family:monospace;font-size:12px;">'
            . '<strong>%1$s</strong> &nbsp;|&nbsp; product_id=%2$d &nbsp;|&nbsp; context=%3$s'
            . '</div>',
            esc_html(current_action()),
            $product_id,
            esc_html($context)
        );
    }
}
