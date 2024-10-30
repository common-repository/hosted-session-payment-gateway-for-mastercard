<?php
/**
 * Mpgs Gateway Report
 * 
 * @package Mpgs_Hosted_Session
 * @author  Abzer <ecomsupport@abzer.com>
 * @link    https://www.abzer.com
 */
if (! class_exists('WP_List_Table') ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Mpgs_Gateway_Report class.
 */
class Mpgs_Gateway_Report extends WP_List_Table {



	/**
	 * Data
	 *
	 * @var array data
	 */
	public $found_data = array();
	/**
	 * Order Status Variable
	 *
	 * @var string Order Status.
	 */
	protected $order_status;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->order_status = include dirname(__FILE__) . '/order-status-mpgs.php';
		parent::__construct(
			array(
			'singular' => __('MPGS Online Report', 'mpgs_table'),
			'plural'   => __('MPGS Online Report', 'mpgs_table'),
			'ajax'     => false,
			)
		);
		add_action('admin_head', array( &$this, 'admin_header' ));
	}
	/**
	 * Admin Header
	 *
	 * @return null
	 */
	public function admin_header() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		$page = isset($page) ? esc_attr($page) : '';
		if ('mpgs-report' !== $page ) {
			return;
		}
		echo '<style type="text/css">';
		echo '.wp-list-table .column-order_id { width: 7%; }';
		echo '.wp-list-table .column-amount { width: 7%; }';
		echo '.wp-list-table .column-reference { width: 12%; }';
		echo '.wp-list-table .column-action { width: 6%;}';

		echo '.wp-list-table .column-state { width: 8%; }';
		echo '.wp-list-table .column-status { width: 9%; }';
		echo '.wp-list-table .column-payment_id { width: 15%; }';
		echo '.wp-list-table .column-capture_id { width: 15%;}';

		echo '.wp-list-table .column-capture_amount { width: 8%; }';
		echo '.wp-list-table .column-created_at { width: 11%; }';
		echo '</style>';
	}

	/**
	 * Default Column
	 *
	 * @param  array  $item        Item.
	 * @param  string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name) {
		switch ( $column_name ) {
			case 'order_id':
			case 'amount':
			case 'reference':
			case 'action':
			case 'state':
			case 'status':
			case 'id_payment':
			case 'receipt':
			case 'captured_amt':
			case 'created_at':
				return $item[$column_name];
			default:
				return print_r($item, true);
		}
	}
	/**
	 * Sortable Columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
		'order_id'   => array( 'order_id', false ),
		'amount'     => array( 'amount', false ),
		'created_at' => array( 'created_at', false ),
		);
	}
	/**
	 * Columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
		'order_id'       => __('Order ID', 'mpgs-report'),
		'amount'         => __('Amount', 'mpgs-report'),
		'reference'      => __('Order Ref', 'mpgs-report'),
		'action'         => __('Action', 'mpgs-report'),
		'state'          => __('State', 'mpgs-report'),
		'status'         => __('Status', 'mpgs-report'),
		'id_payment'     => __('Payment ID', 'mpgs-report'),
		'receipt'        => __('Receipt', 'mpgs-report'),
		'capture_amount' => __('Capture Amount', 'mpgs-report'),
		'created_at'     => __('Created At', 'mpgs-report'),
		);
	}
	/**
	 * Sort - Reorder
	 *
	 * @param  array $a first item.
	 * @param  array $b second item.
	 * @return string
	 */
	public function usort_reorder( $a, $b) {
		$orderby = filter_input(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING);
		$order   = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING);
		$orderby = !empty($orderby) ? $orderby : 'order_id';
		$order   = !empty($order) ? $order : 'desc';
		$result  = strcmp($a[$orderby], $b[$orderby]);
		return ( 'asc' === $order ) ? $result : -$result;
	}
	/**
	 * Format Amount
	 *
	 * @param  array $item Item.
	 * @return string
	 */
	public function column_amount( $item) {
		return $item['currency'] . ' ' . number_format($item['amount'], 2);
	}
	/**
	 * Format Amount
	 *
	 * @param  array $item Item.
	 * @return string
	 */
	public function column_capture_amount( $item) {
		return $item['currency'] . ' ' . number_format($item['capture_amount'], 2);
	}
	/**
	 * Format Status
	 *
	 * @param  array $item Item.
	 * @return string
	 */
	public function column_status( $item) {
		$renderedValue = $item['status'];
		$status = array_column($this->order_status, 'label', 'status');
		return isset($status[$renderedValue]) ? $status[$renderedValue] : $renderedValue;
	}
	/**
	 * Prepare Items
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
		$orders                = $this->fetch_order();
		usort($orders, array( &$this, 'usort_reorder' ));
		$per_page         = $this->get_items_per_page('records_per_page', 10);
		$current_page     = $this->get_pagenum();
		$total_items      = count($orders);
		$this->found_data = array_slice($orders, ( $current_page - 1 ) * $per_page, $per_page);
		$this->set_pagination_args(
			array(
			'total_items' => $total_items, // WE have to calculate the total number of items.
			'per_page'    => $per_page,    // WE have to determine how many items to show on a page.
			)
		);
		$this->items = $this->found_data;
	}
	/**
	 * Fetch data
	 *
	 * @global object $wpdb
	 * @return array
	 */
	public function fetch_order() {
		global $wpdb;
		$val = filter_input(INPUT_POST, 's', FILTER_SANITIZE_STRING);
		if (isset($val) && !empty($val)) {    
			$wild = '%';
			$like = $wild . $wpdb->esc_like($val) . $wild;
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM ' . $wpdb->prefix . 'mpgs_payment WHERE `reference` 
			LIKE %s OR `created_at` LIKE %s OR `capture_amount` LIKE %s OR `id_payment` LIKE %s OR `receipt` LIKE %s OR `action` LIKE %s OR `order_id` LIKE %s OR `amount` LIKE %s OR `state` LIKE %s OR `status` LIKE %s',
					$like,
					$like,
					$like,
					$like,
					$like,
					$like,
					$like,
					$like,
					$like,
					$like
				),
				ARRAY_A
			); // db call ok; no-cache ok.            
		}
		return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'mpgs_payment', ARRAY_A); // db call ok; no-cache ok.
	}    
}
