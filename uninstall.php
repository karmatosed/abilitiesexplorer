<?php
/**
 * Uninstall Ability Explorer
 *
 * Fired when the plugin is uninstalled.
 *
 * @package AbilityExplorer
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'ability_explorer_demo_site_health' );
