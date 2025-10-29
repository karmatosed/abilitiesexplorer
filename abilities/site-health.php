<?php
/**
 * Site Health Demo Ability
 *
 * This file demonstrates how to register an ability using the WordPress Abilities API.
 * It creates a "Get Site Health Status" ability that returns information about your
 * WordPress installation's health.
 *
 * @package AbilityExplorer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Site Health ability category
 */
$register_category = function() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return false;
	}

	wp_register_ability_category( 'diagnostics', array(
		'label'       => 'Diagnostics',
		'description' => 'Abilities for checking system status and health',
	) );

	return true;
};

// Register category first - categories must be registered before abilities
add_action( 'wp_abilities_api_categories_init', $register_category, 10 );

/**
 * Register the Site Health ability
 *
 * This ability uses WordPress's built-in WP_Site_Health class to
 * return information about the site's health status.
 *
 * IMPORTANT: Abilities MUST be registered on the 'wp_abilities_api_init' hook.
 * WordPress 6.9 enforces this and will reject registrations on other hooks.
 */
$register_ability = function() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return false;
	}

	wp_register_ability( 'ability-explorer/get-site-health', array(
		// Basic Information
		'label'       => 'Get Site Health Status',
		'description' => 'Returns WordPress site health status using the built-in WP_Site_Health API. Shows overall status, test results, and critical issues.',
		'category'    => 'diagnostics',

		// Input Schema (JSON Schema format)
		// Defines what data this ability accepts
		'input_schema' => array(
			'type'       => 'object',
			'properties' => array(
				'include_details' => array(
					'type'        => 'boolean',
					'description' => 'Include detailed test results',
					'default'     => false,
				),
			),
		),

		// Output Schema (JSON Schema format)
		// Defines what data this ability returns
		'output_schema' => array(
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'type'        => 'string',
					'description' => 'Overall site health status (good, recommended, critical)',
				),
				'score' => array(
					'type'        => 'number',
					'description' => 'Site health score (0-100)',
				),
				'counts' => array(
					'type'        => 'object',
					'description' => 'Counts of tests by status',
					'properties'  => array(
						'good'        => array( 'type' => 'integer' ),
						'recommended' => array( 'type' => 'integer' ),
						'critical'    => array( 'type' => 'integer' ),
					),
				),
				'critical_issues' => array(
					'type'        => 'array',
					'description' => 'List of critical issues',
				),
			),
		),

		// Execute Callback
		// This function runs when the ability is invoked
		'execute_callback' => function( $input ) {
			// Load WP_Site_Health if not already loaded
			if ( ! class_exists( 'WP_Site_Health' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
			}

			// Get Site Health instance
			$site_health = WP_Site_Health::get_instance();

			// Get test results
			$tests = $site_health->get_tests();

			// Run tests and collect results
			$test_results = array(
				'good'        => 0,
				'recommended' => 0,
				'critical'    => 0,
			);
			$critical_issues = array();

			// Process direct tests
			if ( isset( $tests['direct'] ) ) {
				foreach ( $tests['direct'] as $test ) {
					if ( is_callable( $test['test'] ) ) {
						$result = call_user_func( $test['test'] );
						if ( isset( $result['status'] ) ) {
							if ( $result['status'] === 'good' ) {
								++$test_results['good'];
							} elseif ( $result['status'] === 'recommended' ) {
								++$test_results['recommended'];
							} elseif ( $result['status'] === 'critical' ) {
								++$test_results['critical'];
								$critical_issues[] = array(
									'label'       => $result['label'] ?? 'Unknown Issue',
									'description' => $result['description'] ?? '',
								);
							}
						}
					}
				}
			}

			// Calculate overall score and status
			$total_tests = $test_results['good'] + $test_results['recommended'] + $test_results['critical'];
			$score       = $total_tests > 0 ? round( ( $test_results['good'] / $total_tests ) * 100 ) : 0;

			if ( $test_results['critical'] > 0 ) {
				$status = 'critical';
			} elseif ( $test_results['recommended'] > 0 ) {
				$status = 'recommended';
			} else {
				$status = 'good';
			}

			// Build response
			$response = array(
				'status'          => $status,
				'score'           => $score,
				'counts'          => $test_results,
				'critical_issues' => $critical_issues,
			);

			// Include detailed test results if requested
			$include_details = isset( $input['include_details'] ) ? (bool) $input['include_details'] : false;
			if ( $include_details ) {
				$response['tests'] = $tests;
			}

			return $response;
		},

		// Permission Callback
		// Determines who can execute this ability
		'permission_callback' => function() {
			// Only allow users who can manage options (administrators)
			return current_user_can( 'manage_options' );
		},
	) );

	return true;
};

// Register on the correct hook - this is enforced by WordPress 6.9
add_action( 'wp_abilities_api_init', $register_ability, 10 );
