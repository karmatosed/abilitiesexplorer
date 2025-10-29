<?php
/**
 * Ability Table Class
 *
 * Extends WP_List_Table to display abilities in a searchable, filterable table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ability_Explorer_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'ability',
				'plural'   => 'abilities',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get table columns
	 *
	 * @return array Column definitions
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'abilitiesexplorer' ),
			'slug'       => __( 'Slug', 'abilitiesexplorer' ),
			'provider'   => __( 'Provider', 'abilitiesexplorer' ),
			'actions'    => __( 'Actions', 'abilitiesexplorer' ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array Sortable column definitions
	 */
	public function get_sortable_columns() {
		return array(
			'name'     => array( 'name', false ),
			'slug'     => array( 'slug', false ),
			'provider' => array( 'provider', false ),
		);
	}

	/**
	 * Prepare items for display
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get abilities
		$abilities = Ability_Explorer_Handler::get_all_abilities();

		// Apply search filter
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( ! empty( $search ) ) {
			$abilities = array_filter(
				$abilities,
				function( $ability ) use ( $search ) {
					return stripos( $ability['name'], $search ) !== false
						|| stripos( $ability['slug'], $search ) !== false
						|| stripos( $ability['description'], $search ) !== false;
				}
			);
		}

		// Apply provider filter
		$provider_filter = isset( $_REQUEST['provider'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['provider'] ) ) : '';
		if ( ! empty( $provider_filter ) && 'all' !== $provider_filter ) {
			$abilities = array_filter(
				$abilities,
				function( $ability ) use ( $provider_filter ) {
					return $ability['provider'] === $provider_filter;
				}
			);
		}

		// Apply sorting
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'name';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

		usort(
			$abilities,
			function( $a, $b ) use ( $orderby, $order ) {
				$result = 0;

				if ( isset( $a[ $orderby ] ) && isset( $b[ $orderby ] ) ) {
					$result = strcasecmp( $a[ $orderby ], $b[ $orderby ] );
				}

				return ( 'asc' === $order ) ? $result : -$result;
			}
		);

		// Pagination
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count( $abilities );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->items = array_slice( $abilities, ( ( $current_page - 1 ) * $per_page ), $per_page );
	}

	/**
	 * Default column output
	 *
	 * @param array $item Item data
	 * @param string $column_name Column name
	 * @return string Column output
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '—';
	}

	/**
	 * Checkbox column
	 *
	 * @param array $item Item data
	 * @return string Checkbox HTML
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="abilities[]" value="%s" />',
			esc_attr( $item['slug'] )
		);
	}

	/**
	 * Name column
	 *
	 * @param array $item Item data
	 * @return string Name column HTML
	 */
	public function column_name( $item ) {
		$detail_url = add_query_arg(
			array(
				'page'    => 'abilitiesexplorer',
				'action'  => 'view',
				'ability' => $item['slug'],
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong><br /><small>%s</small>',
			esc_url( $detail_url ),
			esc_html( $item['name'] ),
			esc_html( wp_trim_words( $item['description'], 15 ) )
		);
	}

	/**
	 * Slug column
	 *
	 * @param array $item Item data
	 * @return string Slug column HTML
	 */
	public function column_slug( $item ) {
		return sprintf(
			'<code>%s</code>',
			esc_html( $item['slug'] )
		);
	}

	/**
	 * Provider column
	 *
	 * @param array $item Item data
	 * @return string Provider column HTML
	 */
	public function column_provider( $item ) {
		$provider = $item['provider'];
		$class    = 'ability-provider ability-provider-' . strtolower( $provider );

		return sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $class ),
			esc_html( $provider )
		);
	}

	/**
	 * Actions column
	 *
	 * @param array $item Item data
	 * @return string Actions column HTML
	 */
	public function column_actions( $item ) {
		$detail_url = add_query_arg(
			array(
				'page'    => 'abilitiesexplorer',
				'action'  => 'view',
				'ability' => $item['slug'],
			),
			admin_url( 'admin.php' )
		);

		$test_url = add_query_arg(
			array(
				'page'    => 'abilitiesexplorer',
				'action'  => 'test',
				'ability' => $item['slug'],
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<a href="%s" class="button button-small">%s</a> <a href="%s" class="button button-small button-primary">%s</a>',
			esc_url( $detail_url ),
			esc_html__( 'View', 'abilitiesexplorer' ),
			esc_url( $test_url ),
			esc_html__( 'Test', 'abilitiesexplorer' )
		);
	}

	/**
	 * Display filter controls
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$provider_filter = isset( $_REQUEST['provider'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['provider'] ) ) : 'all';

		?>
		<div class="alignleft actions">
			<select name="provider" id="filter-by-provider">
				<option value="all" <?php selected( $provider_filter, 'all' ); ?>><?php esc_html_e( 'All Providers', 'abilitiesexplorer' ); ?></option>
				<option value="Core" <?php selected( $provider_filter, 'Core' ); ?>><?php esc_html_e( 'Core', 'abilitiesexplorer' ); ?></option>
				<option value="Plugin" <?php selected( $provider_filter, 'Plugin' ); ?>><?php esc_html_e( 'Plugins', 'abilitiesexplorer' ); ?></option>
				<option value="Theme" <?php selected( $provider_filter, 'Theme' ); ?>><?php esc_html_e( 'Theme', 'abilitiesexplorer' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'abilitiesexplorer' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
