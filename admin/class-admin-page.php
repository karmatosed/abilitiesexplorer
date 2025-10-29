<?php
/**
 * Admin Page Class
 *
 * Handles admin menu, pages, and UI rendering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ability_Explorer_Admin_Page {

	/**
	 * Initialize admin functionality
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_ability_explorer_invoke', array( $this, 'ajax_invoke_ability' ) );
		add_action( 'wp_ajax_ability_explorer_toggle_demo', array( $this, 'ajax_toggle_demo_ability' ) );
	}

	/**
	 * Add admin menu item
	 */
	public function add_admin_menu() {
		// Add top-level menu
		add_menu_page(
			__( 'Abilities', 'abilitiesexplorer' ),
			__( 'Abilities', 'abilitiesexplorer' ),
			'manage_options',
			'abilitiesexplorer',
			array( $this, 'render_page' ),
			'dashicons-superhero',
			30
		);

		// Add Explorer submenu (the main list)
		add_submenu_page(
			'abilitiesexplorer',
			__( 'Ability Explorer', 'abilitiesexplorer' ),
			__( 'Explorer', 'abilitiesexplorer' ),
			'manage_options',
			'abilitiesexplorer',
			array( $this, 'render_page' )
		);

		// Add Demo Abilities submenu
		add_submenu_page(
			'abilitiesexplorer',
			__( 'Demo Abilities', 'abilitiesexplorer' ),
			__( 'Demo Abilities', 'abilitiesexplorer' ),
			'manage_options',
			'abilitiesexplorer-demo',
			array( $this, 'render_demo_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_assets( $hook ) {
		// Load on both Explorer and Demo Abilities pages
		$allowed_hooks = array(
			'toplevel_page_abilitiesexplorer',     // Main Explorer page
			'abilities_page_abilitiesexplorer-demo', // Demo Abilities page
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		// Enqueue styles
		wp_enqueue_style(
			'ability-explorer-admin',
			ABILITY_EXPLORER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ABILITY_EXPLORER_VERSION
		);

		// Enqueue scripts
		wp_enqueue_script(
			'ability-explorer-admin',
			ABILITY_EXPLORER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ABILITY_EXPLORER_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'ability-explorer-admin',
			'abilityExplorer',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ability_explorer_invoke' ),
				'strings' => array(
					'invoking'       => __( 'Invoking ability...', 'abilitiesexplorer' ),
					'success'        => __( 'Success!', 'abilitiesexplorer' ),
					'error'          => __( 'Error', 'abilitiesexplorer' ),
					'invalidJson'    => __( 'Invalid JSON input', 'abilitiesexplorer' ),
					'confirmInvoke'  => __( 'Are you sure you want to invoke this ability?', 'abilitiesexplorer' ),
					'copySuccess'    => __( 'Copied to clipboard!', 'abilitiesexplorer' ),
					'copyError'      => __( 'Failed to copy', 'abilitiesexplorer' ),
				),
			)
		);
	}

	/**
	 * Render the main page
	 */
	public function render_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'abilitiesexplorer' ) );
		}

		// Get current action
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		echo '<div class="wrap ability-explorer-wrap">';
		echo '<h1>' . esc_html__( 'Ability Explorer', 'abilitiesexplorer' ) . '</h1>';

		// Display statistics dashboard
		if ( 'list' === $action ) {
			$this->render_statistics();
		}

		// Render appropriate view based on action
		switch ( $action ) {
			case 'view':
				$this->render_detail_view();
				break;

			case 'test':
				$this->render_test_runner();
				break;

			case 'list':
			default:
				$this->render_list_view();
				break;
		}

		echo '</div>';
	}

	/**
	 * Render statistics dashboard
	 */
	private function render_statistics() {
		$stats = Ability_Explorer_Handler::get_statistics();

		?>
		<div class="ability-explorer-stats">
			<div class="ability-stat-card">
				<div class="ability-stat-number"><?php echo esc_html( $stats['total'] ); ?></div>
				<div class="ability-stat-label"><?php esc_html_e( 'Total Abilities', 'abilitiesexplorer' ); ?></div>
			</div>

			<div class="ability-stat-card">
				<div class="ability-stat-number"><?php echo esc_html( $stats['by_provider']['Core'] ); ?></div>
				<div class="ability-stat-label"><?php esc_html_e( 'Core', 'abilitiesexplorer' ); ?></div>
			</div>

			<div class="ability-stat-card">
				<div class="ability-stat-number"><?php echo esc_html( $stats['by_provider']['Plugin'] ); ?></div>
				<div class="ability-stat-label"><?php esc_html_e( 'Plugins', 'abilitiesexplorer' ); ?></div>
			</div>

			<div class="ability-stat-card">
				<div class="ability-stat-number"><?php echo esc_html( $stats['by_provider']['Theme'] ); ?></div>
				<div class="ability-stat-label"><?php esc_html_e( 'Theme', 'abilitiesexplorer' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render list view
	 */
	private function render_list_view() {
		$table = new Ability_Explorer_Table();
		$table->prepare_items();

		?>
		<form method="get">
			<input type="hidden" name="page" value="ability-explorer" />
			<?php
			$table->search_box( __( 'Search Abilities', 'abilitiesexplorer' ), 'ability' );
			$table->display();
			?>
		</form>
		<?php
	}

	/**
	 * Render detail view
	 */
	private function render_detail_view() {
		$ability_slug = isset( $_GET['ability'] ) ? sanitize_text_field( wp_unslash( $_GET['ability'] ) ) : '';

		if ( empty( $ability_slug ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'No ability specified.', 'abilitiesexplorer' ) . '</p></div>';
			return;
		}

		$ability = Ability_Explorer_Handler::get_ability( $ability_slug );

		if ( ! $ability ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Ability not found.', 'abilitiesexplorer' ) . '</p></div>';
			return;
		}

		$back_url = admin_url( 'admin.php?page=abilitiesexplorer' );
		$test_url = add_query_arg(
			array(
				'page'    => 'abilitiesexplorer',
				'action'  => 'test',
				'ability' => $ability_slug,
			),
			admin_url( 'admin.php' )
		);

		?>
		<div class="ability-explorer-detail">
			<div class="ability-detail-header">
				<a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Back to List', 'abilitiesexplorer' ); ?></a>
				<a href="<?php echo esc_url( $test_url ); ?>" class="button button-primary"><?php esc_html_e( 'Test Ability', 'abilitiesexplorer' ); ?></a>
			</div>

			<h2><?php echo esc_html( $ability['name'] ); ?></h2>
			<p class="ability-detail-slug"><code><?php echo esc_html( $ability['slug'] ); ?></code></p>

			<?php if ( ! empty( $ability['description'] ) ) : ?>
				<div class="ability-detail-section">
					<h3><?php esc_html_e( 'Description', 'abilitiesexplorer' ); ?></h3>
					<p><?php echo esc_html( $ability['description'] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="ability-detail-section">
				<h3><?php esc_html_e( 'Details', 'abilitiesexplorer' ); ?></h3>
				<table class="ability-detail-table">
					<tr>
						<th><?php esc_html_e( 'Provider', 'abilitiesexplorer' ); ?></th>
						<td><span class="ability-provider ability-provider-<?php echo esc_attr( strtolower( $ability['provider'] ) ); ?>"><?php echo esc_html( $ability['provider'] ); ?></span></td>
					</tr>
				</table>
			</div>

			<?php if ( ! empty( $ability['input_schema'] ) ) : ?>
				<div class="ability-detail-section">
					<h3><?php esc_html_e( 'Input Schema', 'abilitiesexplorer' ); ?></h3>
					<button type="button" class="button button-small ability-copy-btn" data-copy="input-schema"><?php esc_html_e( 'Copy', 'abilitiesexplorer' ); ?></button>
					<pre class="ability-schema-display" id="input-schema"><?php echo esc_html( wp_json_encode( $ability['input_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $ability['output_schema'] ) ) : ?>
				<div class="ability-detail-section">
					<h3><?php esc_html_e( 'Output Schema', 'abilitiesexplorer' ); ?></h3>
					<button type="button" class="button button-small ability-copy-btn" data-copy="output-schema"><?php esc_html_e( 'Copy', 'abilitiesexplorer' ); ?></button>
					<pre class="ability-schema-display" id="output-schema"><?php echo esc_html( wp_json_encode( $ability['output_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
				</div>
			<?php endif; ?>

			<div class="ability-detail-section">
				<h3><?php esc_html_e( 'Raw Data', 'abilitiesexplorer' ); ?></h3>
				<button type="button" class="button button-small ability-copy-btn" data-copy="raw-data"><?php esc_html_e( 'Copy', 'abilitiesexplorer' ); ?></button>
				<pre class="ability-schema-display" id="raw-data"><?php echo esc_html( wp_json_encode( $ability['raw_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			</div>
		</div>
		<?php
	}

	/**
	 * Render test runner
	 */
	private function render_test_runner() {
		$ability_slug = isset( $_GET['ability'] ) ? sanitize_text_field( wp_unslash( $_GET['ability'] ) ) : '';

		if ( empty( $ability_slug ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'No ability specified.', 'abilitiesexplorer' ) . '</p></div>';
			return;
		}

		$ability = Ability_Explorer_Handler::get_ability( $ability_slug );

		if ( ! $ability ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Ability not found.', 'abilitiesexplorer' ) . '</p></div>';
			return;
		}

		$back_url   = admin_url( 'admin.php?page=abilitiesexplorer' );
		$detail_url = add_query_arg(
			array(
				'page'    => 'abilitiesexplorer',
				'action'  => 'view',
				'ability' => $ability_slug,
			),
			admin_url( 'admin.php' )
		);

		// Generate example input from input schema
		$example_input = $this->generate_example_input( $ability['input_schema'] );

		?>
		<div class="ability-explorer-test-runner">
			<div class="ability-detail-header">
				<a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Back to List', 'abilitiesexplorer' ); ?></a>
				<a href="<?php echo esc_url( $detail_url ); ?>" class="button"><?php esc_html_e( 'View Details', 'abilitiesexplorer' ); ?></a>
			</div>

			<h2><?php esc_html_e( 'Test Ability:', 'abilitiesexplorer' ); ?> <?php echo esc_html( $ability['name'] ); ?></h2>
			<p class="ability-detail-slug"><code><?php echo esc_html( $ability['slug'] ); ?></code></p>

			<?php if ( ! empty( $ability['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $ability['description'] ); ?></p>
			<?php endif; ?>

			<div class="ability-test-editor">
				<h3><?php esc_html_e( 'Input Data', 'abilitiesexplorer' ); ?></h3>
				<?php if ( empty( $ability['input_schema'] ) ) : ?>
					<div class="notice notice-warning inline" style="margin: 10px 0;">
						<p>
							<strong><?php esc_html_e( 'No Input Required', 'abilitiesexplorer' ); ?></strong><br>
							<?php esc_html_e( 'This ability does not accept any input parameters. Simply click "Invoke Ability" to execute it.', 'abilitiesexplorer' ); ?>
						</p>
					</div>
				<?php else : ?>
					<p class="description">
						<?php
						esc_html_e( 'Edit the JSON input below to test the ability. The input will be validated against the input schema if available.', 'abilitiesexplorer' );
						?>
					</p>
					<div class="notice notice-info inline" style="margin: 10px 0;">
						<p>
							<strong><?php esc_html_e( 'How to test:', 'abilitiesexplorer' ); ?></strong><br>
							1. <?php esc_html_e( 'Edit the JSON input below with your test data', 'abilitiesexplorer' ); ?><br>
							2. <?php esc_html_e( 'Click "Validate Input" to check your JSON is correct', 'abilitiesexplorer' ); ?><br>
							3. <?php esc_html_e( 'Click "Invoke Ability" to execute the ability with your input', 'abilitiesexplorer' ); ?><br>
							4. <?php esc_html_e( 'View the results below', 'abilitiesexplorer' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<textarea id="ability-test-payload" rows="12"><?php echo esc_textarea( wp_json_encode( $example_input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>

				<div class="ability-test-actions">
					<button type="button" id="ability-test-invoke" class="button button-primary" data-ability="<?php echo esc_attr( $ability_slug ); ?>">
						<?php esc_html_e( 'Invoke Ability', 'abilitiesexplorer' ); ?>
					</button>
					<button type="button" id="ability-test-validate" class="button">
						<?php esc_html_e( 'Validate Input', 'abilitiesexplorer' ); ?>
					</button>
					<button type="button" id="ability-test-clear" class="button">
						<?php esc_html_e( 'Clear Result', 'abilitiesexplorer' ); ?>
					</button>
				</div>

				<div id="ability-test-validation" class="ability-test-validation" style="display: none;"></div>
			</div>

			<div class="ability-test-result-container" id="ability-test-result-container" style="display: none;">
				<h3><?php esc_html_e( 'Result', 'abilitiesexplorer' ); ?></h3>
				<div id="ability-test-result"></div>
			</div>

			<?php if ( ! empty( $ability['input_schema'] ) ) : ?>
				<div class="ability-test-schema">
					<h3><?php esc_html_e( 'Input Schema Reference', 'abilitiesexplorer' ); ?></h3>
					<pre><?php echo esc_html( wp_json_encode( $ability['input_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
				</div>
			<?php endif; ?>
		</div>

		<script type="application/json" id="ability-input-schema">
			<?php echo wp_json_encode( $ability['input_schema'] ); ?>
		</script>
		<?php
	}

	/**
	 * Generate example input from input schema
	 *
	 * @param array $schema Input schema
	 * @return array Example input
	 */
	private function generate_example_input( $schema ) {
		if ( empty( $schema ) || ! isset( $schema['properties'] ) ) {
			return array();
		}

		$input = array();

		foreach ( $schema['properties'] as $prop_name => $prop_schema ) {
			$input[ $prop_name ] = $this->get_example_value( $prop_schema );
		}

		return $input;
	}

	/**
	 * Get example value for a schema property
	 *
	 * @param array $prop_schema Property schema
	 * @return mixed Example value
	 */
	private function get_example_value( $prop_schema ) {
		if ( isset( $prop_schema['default'] ) ) {
			return $prop_schema['default'];
		}

		if ( isset( $prop_schema['example'] ) ) {
			return $prop_schema['example'];
		}

		$type = $prop_schema['type'] ?? 'string';

		switch ( $type ) {
			case 'string':
				return '';
			case 'number':
			case 'integer':
				return 0;
			case 'boolean':
				return false;
			case 'array':
				return array();
			case 'object':
				return new stdClass();
			default:
				return null;
		}
	}

	/**
	 * AJAX handler for invoking abilities
	 */
	public function ajax_invoke_ability() {
		// Verify nonce
		check_ajax_referer( 'ability_explorer_invoke', 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Insufficient permissions.', 'abilitiesexplorer' ),
				)
			);
		}

		// Get parameters
		$ability_slug = isset( $_POST['ability'] ) ? sanitize_text_field( wp_unslash( $_POST['ability'] ) ) : '';
		$input        = isset( $_POST['input'] ) ? json_decode( wp_unslash( $_POST['input'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $ability_slug ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Ability slug is required.', 'abilitiesexplorer' ),
				)
			);
		}

		// Get ability to validate
		$ability = Ability_Explorer_Handler::get_ability( $ability_slug );

		if ( ! $ability ) {
			wp_send_json_error(
				array(
					'message' => __( 'Ability not found.', 'abilitiesexplorer' ),
				)
			);
		}

		// Validate input
		if ( ! empty( $ability['input_schema'] ) ) {
			$validation = Ability_Explorer_Handler::validate_input( $ability['input_schema'], $input );

			if ( ! $validation['valid'] ) {
				wp_send_json_error(
					array(
						'message' => __( 'Input validation failed.', 'abilitiesexplorer' ),
						'errors'  => $validation['errors'],
					)
				);
			}
		}

		// Invoke the ability
		$result = Ability_Explorer_Handler::invoke_ability( $ability_slug, $input );

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message' => __( 'Ability invoked successfully.', 'abilitiesexplorer' ),
					'data'    => $result['data'],
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => isset( $result['error'] ) ? $result['error'] : __( 'Unknown error occurred.', 'abilitiesexplorer' ),
					'trace'   => isset( $result['trace'] ) ? $result['trace'] : null,
				)
			);
		}
	}

	/**
	 * Render demo abilities page
	 */
	public function render_demo_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'abilitiesexplorer' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Demo Abilities', 'abilitiesexplorer' ) . '</h1>';
		echo '<p>' . esc_html__( 'Example abilities that demonstrate the WordPress Abilities API. Enable them to see how abilities work.', 'abilitiesexplorer' ) . '</p>';

		$this->render_demo_abilities_list();

		echo '</div>';
	}

	/**
	 * Render list of demo abilities
	 */
	private function render_demo_abilities_list() {
		// Define available demo abilities
		$demo_abilities = array(
			'site-health' => array(
				'name'        => 'Get Site Health Status',
				'slug'        => 'ability-explorer/get-site-health',
				'description' => 'Returns WordPress site health status using the built-in WP_Site_Health API. Shows overall score, number of passed/failed tests, and critical issues.',
				'enabled'     => get_option( 'ability_explorer_demo_site_health', false ),
				'safe'        => true,
			),
		);

		?>
		<div class="ability-demo-list" style="max-width: 1200px;">
			<?php foreach ( $demo_abilities as $key => $ability ) : ?>
				<div class="ability-demo-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
					<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
						<div style="flex: 1;">
							<h2 style="margin: 0 0 8px 0; font-size: 18px;">
								<?php echo esc_html( $ability['name'] ); ?>
								<?php if ( $ability['safe'] ) : ?>
									<span style="display: inline-block; background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; margin-left: 8px;">READ-ONLY</span>
								<?php endif; ?>
							</h2>
							<p style="margin: 0 0 12px 0; color: #646970;">
								<strong><?php esc_html_e( 'Ability Name:', 'abilitiesexplorer' ); ?></strong> <code><?php echo esc_html( $ability['slug'] ); ?></code>
							</p>
							<p style="margin: 0;">
								<?php echo esc_html( $ability['description'] ); ?>
							</p>
						</div>
						<div style="flex-shrink: 0;">
							<button type="button"
								class="button button-<?php echo $ability['enabled'] ? 'secondary' : 'primary'; ?> ability-demo-toggle"
								data-ability="<?php echo esc_attr( $key ); ?>"
								data-enabled="<?php echo esc_attr( $ability['enabled'] ? '1' : '0' ); ?>"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'ability_explorer_toggle_demo_' . $key ) ); ?>">
								<?php echo $ability['enabled'] ? esc_html__( 'Disable', 'abilitiesexplorer' ) : esc_html__( 'Enable', 'abilitiesexplorer' ); ?>
							</button>
							<?php if ( $ability['enabled'] ) : ?>
								<p style="margin: 10px 0 0; color: #00a32a; font-size: 13px;">
									✓ <?php esc_html_e( 'Active', 'abilitiesexplorer' ); ?>
								</p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=abilitiesexplorer' ) ); ?>" class="button button-small" style="margin-top: 8px;">
									<?php esc_html_e( 'View in Explorer', 'abilitiesexplorer' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler for toggling demo abilities
	 */
	public function ajax_toggle_demo_ability() {
		// Get ability key
		$ability_key = isset( $_POST['ability'] ) ? sanitize_text_field( wp_unslash( $_POST['ability'] ) ) : '';

		if ( empty( $ability_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ability.', 'abilitiesexplorer' ) ) );
		}

		// Verify nonce
		check_ajax_referer( 'ability_explorer_toggle_demo_' . $ability_key, 'nonce' );

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'abilitiesexplorer' ) ) );
		}

		// Map ability keys to option names
		$option_map = array(
			'site-health' => 'ability_explorer_demo_site_health',
		);

		if ( ! isset( $option_map[ $ability_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown ability.', 'abilitiesexplorer' ) ) );
		}

		$option_name = $option_map[ $ability_key ];

		// Get current state and toggle
		$is_enabled = get_option( $option_name, false );
		$new_state  = ! $is_enabled;
		update_option( $option_name, $new_state );

		wp_send_json_success(
			array(
				'enabled' => $new_state,
				'message' => $new_state
					? __( 'Ability enabled. Check the Explorer to see it.', 'abilitiesexplorer' )
					: __( 'Ability disabled.', 'abilitiesexplorer' ),
			)
		);
	}
}
