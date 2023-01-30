<?php
/**
 * This class displays a leaderboard of your donations
 *
 * * @since 1.3.0
 */

if(!defined('ABSPATH')) exit;

class WCDP_Leaderboard
{
    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public function __construct() {
        //Leaderboard shortcode
        add_shortcode( 'wcdp_leaderboard', array($this, 'wcdp_leaderboard'));
    }

    /**
     * Leaderboard Shortcode
     * @return string
     */
    function wcdp_leaderboard() {
        return 'Leaderboard';
    }
}
