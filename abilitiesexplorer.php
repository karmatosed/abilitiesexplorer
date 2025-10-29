<?php
/**
 * Plugin Name: Ability Explorer
 * Plugin URI: https://github.com/yourusername/ability-explorer
 * Description: Discover, inspect, test, and document all abilities registered via the WordPress 6.9+ Abilities API from core, plugins, and themes.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: abilitiesexplorer
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ABILITY_EXPLORER_VERSION', '1.0.0' );
define( 'ABILITY_EXPLORER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABILITY_EXPLORER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ABILITY_EXPLORER_MIN_WP_VERSION', '6.9' );

/**
 * Main Ability Explorer class
 */
class Ability_Explorer {

	/**
	 * Single instance of the class
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Check WordPress version
		if ( ! $this->check_wp_version() ) {
			add_action( 'admin_notices', array( $this, 'version_notice' ) );
			return;
		}

		// Check if Abilities API is available
		if ( ! $this->check_abilities_api() ) {
			add_action( 'admin_notices', array( $this, 'abilities_api_notice' ) );
			return;
		}

		// Load plugin files
		$this->load_dependencies();

		// Initialize admin
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Load text domain
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Load demo abilities
		$this->load_demo_abilities();
	}

	/**
	 * Load demo abilities
	 */
	private function load_demo_abilities() {
		// Load Site Health ability (only if enabled)
		if ( get_option( 'ability_explorer_demo_site_health', false ) ) {
			require_once ABILITY_EXPLORER_PLUGIN_DIR . 'abilities/site-health.php';
		}
	}

	/**
	 * Check WordPress version
	 */
	private function check_wp_version() {
		global $wp_version;

		// Strip beta/RC/alpha suffixes for version comparison
		// This allows the plugin to run on 6.9-beta1, 6.9-RC1, etc.
		$clean_version = preg_replace( '/-(?:beta|rc|alpha).*$/i', '', $wp_version );

		return version_compare( $clean_version, ABILITY_EXPLORER_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if Abilities API is available
	 */
	private function check_abilities_api() {
		// Allow bypassing the check for development/testing
		// Add this to wp-config.php: define( 'ABILITY_EXPLORER_SKIP_API_CHECK', true );
		if ( defined( 'ABILITY_EXPLORER_SKIP_API_CHECK' ) && ABILITY_EXPLORER_SKIP_API_CHECK ) {
			return true;
		}

		// Check for WP_Ability class (the official way per docs)
		if ( class_exists( 'WP_Ability' ) ) {
			return true;
		}

		// Check for the main API functions
		if ( function_exists( 'wp_get_abilities' ) && function_exists( 'wp_register_ability' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Display version notice
	 */
	public function version_notice() {
		$message = sprintf(
			/* translators: 1: Required WordPress version, 2: Current WordPress version */
			__( 'Ability Explorer requires WordPress %1$s or higher. You are running version %2$s.', 'abilitiesexplorer' ),
			ABILITY_EXPLORER_MIN_WP_VERSION,
			$GLOBALS['wp_version']
		);

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Display Abilities API notice
	 */
	public function abilities_api_notice() {
		global $wp_version;

		$message = __(
			'Ability Explorer requires the Abilities API which is not available.',
			'abilitiesexplorer'
		);

		// Add debug information
		$debug_info = array();
		$debug_info[] = sprintf( 'WordPress Version: %s', $wp_version );
		$debug_info[] = '';
		$debug_info[] = '=== Required Components ===';
		$debug_info[] = sprintf( 'WP_Ability class: %s', class_exists( 'WP_Ability' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = sprintf( 'WP_Ability_Category class: %s', class_exists( 'WP_Ability_Category' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = sprintf( 'WP_Abilities_Registry class: %s', class_exists( 'WP_Abilities_Registry' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = '';
		$debug_info[] = '=== Required Functions ===';
		$debug_info[] = sprintf( 'wp_register_ability(): %s', function_exists( 'wp_register_ability' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = sprintf( 'wp_get_abilities(): %s', function_exists( 'wp_get_abilities' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = sprintf( 'wp_get_ability(): %s', function_exists( 'wp_get_ability' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );
		$debug_info[] = sprintf( 'wp_has_ability(): %s', function_exists( 'wp_has_ability' ) ? 'EXISTS ✓' : 'NOT FOUND ✗' );

		// Check for API version
		if ( defined( 'WP_ABILITIES_API_VERSION' ) ) {
			$debug_info[] = '';
			$debug_info[] = sprintf( 'API Version: %s', WP_ABILITIES_API_VERSION );
		}

		// Check if the plugin is installed but not activated
		$debug_info[] = '';
		$debug_info[] = '=== Installation Status ===';
		$plugins = get_plugins();
		$found_plugin = false;
		foreach ( $plugins as $plugin_file => $plugin_data ) {
			if ( stripos( $plugin_data['Name'], 'abilit' ) !== false ) {
				$is_active = is_plugin_active( $plugin_file );
				$debug_info[] = sprintf(
					'Plugin: %s (%s)',
					$plugin_data['Name'],
					$is_active ? 'ACTIVE ✓' : 'INACTIVE ✗'
				);
				$found_plugin = true;
			}
		}
		if ( ! $found_plugin ) {
			$debug_info[] = 'No Abilities API plugin found in /wp-content/plugins/';
		}

		$help_text = '<strong>' . esc_html__( 'The Abilities API is not available in your WordPress installation.', 'abilitiesexplorer' ) . '</strong><br><br>';
		$help_text .= esc_html__( 'The Abilities API is included in WordPress 6.9 and higher. To use this plugin:', 'abilitiesexplorer' ) . '<br><br>';
		$help_text .= '<strong>1. Upgrade to WordPress 6.9+</strong><br>';
		$help_text .= esc_html__( 'Make sure you are running WordPress 6.9 or higher. Check your WordPress version in Dashboard → Updates.', 'abilitiesexplorer' ) . '<br><br>';
		$help_text .= '<strong>2. Verify Installation</strong><br>';
		$help_text .= esc_html__( 'After upgrading, return to this page. The plugin will automatically detect the Abilities API.', 'abilitiesexplorer' ) . '<br><br>';
		$help_text .= '<em>' . sprintf(
			/* translators: %s: constant name */
			esc_html__( 'For development/testing: add %s to wp-config.php to bypass this check', 'abilitiesexplorer' ),
			"<code>define( 'ABILITY_EXPLORER_SKIP_API_CHECK', true );</code>"
		) . '</em>';

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p><p>%s</p><details style="margin-top: 10px;"><summary style="cursor: pointer;">%s</summary><pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; overflow-x: auto;">%s</pre></details></div>',
			esc_html( $message ),
			$help_text,
			esc_html__( 'Debug Information', 'abilitiesexplorer' ),
			esc_html( implode( "\n", $debug_info ) )
		);
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once ABILITY_EXPLORER_PLUGIN_DIR . 'includes/class-ability-handler.php';
		require_once ABILITY_EXPLORER_PLUGIN_DIR . 'includes/class-ability-table.php';
	}

	/**
	 * Initialize admin functionality
	 */
	private function init_admin() {
		require_once ABILITY_EXPLORER_PLUGIN_DIR . 'admin/class-admin-page.php';

		$admin_page = new Ability_Explorer_Admin_Page();
		$admin_page->init();
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'abilitiesexplorer',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
}

// Initialize the plugin
Ability_Explorer::get_instance();
