<?php
/**
 * Plugin Name: Hosted Session Payment Gateway for Mastercard
 * Plugin URI: https://www.mastercard.us
 * Description: Hosted Session Payment Gateway for Mastercard Payment Gateway Services
 * Author: Abzer
 * Author URI: https://www.abzer.com
 * Version: 3.0.0
 *
 * @package Mpgs_Hosted_Session
 * @author  Abzer <ecomsupport@abzer.com>
 * @link    https://www.abzer.com
 */

/**
 * Function to register order statuses
 */
function register_mpgs_order_status() {
	$statuses = include 'gateway/order-status-mpgs.php';
	foreach ( $statuses as $status ) {
		$label = $status['label'];
		register_post_status(
			$status['status'],
			array(
			'label'                     => $label,
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: count */
			// 'label_count'               => _n_noop(
			//     $label . ' <span class="count">(%s)</span>', // NOSONAR.
			//     $label . ' <span class="count">(%s)</span>' // NOSONAR.
			// ),
			)
		);
	}
}

add_action('init', 'register_mpgs_order_status');

/**
 * Function to register woocommerce order statuses
 *
 * @param array $order_statuses Order Statuses.
 */
function mpgs_order_status( $order_statuses) {
	$statuses = include 'gateway/order-status-mpgs.php';
	$id       = get_the_ID();
	$action   = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	if ('shop_order' === get_post_type() && $id && isset($action) && 'edit' === $action ) {
		$order = wc_get_order($id);
		if ($order ) {
			$current_status = $order->get_status();
			foreach ( $statuses as $status ) {
				if ('wc-' . $current_status === $status['status'] ) {
					$order_statuses[$status['status']] = $status['label'];
				}
			}
		}
	} else {
		foreach ( $statuses as $status ) {
			$order_statuses[$status['status']] = $status['label'];
		}
	}
	return $order_statuses;
}

add_filter('wc_order_statuses', 'mpgs_order_status');

global $wpdb;
define('MPGS_TABLE', $wpdb->prefix . 'mpgs_payment');

/**
 * Function to create table while activate the plugin
 */
function mpgs_table_install() {
	$sql = 'CREATE TABLE IF NOT EXISTS `' . MPGS_TABLE . '` (
        `mid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT "MPGS Id",
        `order_id` varchar(55) NOT NULL COMMENT "Order Id",
        `amount` decimal(12,4) unsigned NOT NULL COMMENT "Amount",
        `currency` varchar(3) NOT NULL COMMENT "Currency",
        `reference` text NOT NULL COMMENT "Reference",
        `action` varchar(20) NOT NULL COMMENT "Action",
        `status` varchar(50) NOT NULL COMMENT "Status",
        `state` varchar(50) NOT NULL COMMENT "State",
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Created On",
        `id_payment` text NOT NULL COMMENT "Transaction ID",
        `receipt` text NOT NULL COMMENT "Receipt ID",
        `capture_amount` decimal(12,4) unsigned NOT NULL COMMENT "Capture Amount",
        PRIMARY KEY (`mid`),
        UNIQUE KEY `MPGS_ID_ORDER_ID` (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT="MPGS Online order table";';
	include_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}

register_activation_hook(__FILE__, 'mpgs_table_install');

/**
 * Function to add action links
 *
 * @param array $links Links.
 */
function mpgs_plugin_action_links( $links) {
	$plugin_links = array(
	'<a href="admin.php?page=wc-settings&tab=checkout&section=mpgs">' . esc_html__('Settings', 'woocommerce') . '</a>',
	'<a href="admin.php?page=mpgs-report">' . esc_html__('Report', 'woocommerce') . '</a>',
	);
	return array_merge($plugin_links, $links);
}
/**
 * Filter to add action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'mpgs_plugin_action_links');

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'mpgs_init_gateway_class');

/**
 * Function to register admin menu
 */
function register_mpgs_report_page() {
	$hook = add_submenu_page('woocommerce', 'MPGS Online Report', 'MPGS Online Report', 'manage_options', 'mpgs-report', 'mpgs_page_callback');
	add_action("load-$hook", 'mpgs_add_options');
}

/**
 * Function to add screen options
 */
function mpgs_add_options() {
	global $mpgs_table;
	$option = 'per_page';
	$args   = array(
	'label'   => 'No. of records',
	'default' => 10,
	'option'  => 'records_per_page',
	);
	add_screen_option($option, $args);
	include_once 'gateway/class-mpgs-gateway-report.php';
	$mpgs_table = new Mpgs_Gateway_Report();
}

add_action('admin_menu', 'register_mpgs_report_page');

/**
 * Function for search box
 */
function mpgs_page_callback() {
	global $mpgs_table;
	echo '</pre><div class="wrap"><h2>MPGS Online Report</h2>';
	$mpgs_table->prepare_items();
	?>
	<form method="post">
		<input type="hidden" name="page" value="mpgs_list_table">
	<?php
	$mpgs_table->search_box('search', 'mpgs_search_id');
	$mpgs_table->display();
	echo '</form></div>';
}

/**
 * Print admin errors
 */
function mpgs_print_errors() {
	settings_errors('mpgs_error');
}



/**
 * Initialise the gateway class
 */
function mpgs_init_gateway_class() {
	if (! class_exists('WC_Payment_Gateway') ) {
		return;
	}
	include_once 'gateway/class-mpgs-gateway.php';
	Mpgs_Gateway::get_instance()->init_hooks();
}

function my_enqueued_assets() {
	include_once dirname(__FILE__) . '/gateway/class-mpgs-gateway.php';
	$gateway  = new Mpgs_Gateway('mpgs');
	wp_enqueue_style('mpgs', plugin_dir_url(__FILE__) . '/gateway/assets/css/mpgs.css', array(), '1.0', 'all');
	
	// if(($gateway->environment)!='sandbox'){
		
	wp_enqueue_script('mpgs', $gateway->hostedSessionJs(), array(), '1.0'); // WPCS: EnqueuedResourceParameters ok.
	
	// }else if(($gateway->environment)=='sandbox'){
	//     wp_enqueue_script('mpgs', $gateway->hostedSessiontestJs(), array(), '1.0'); // WPCS: EnqueuedResourceParameters ok.    
	// }
}

add_action('wp_enqueue_scripts', 'my_enqueued_assets');


/**
 * Add to woocommorce gateway list
 *
 * @param array $gateways Gateways.
 */
function mpgs_add_gateway_class( $gateways) {
	$gateways[] = 'mpgs_gateway';
	return $gateways;
}

add_filter('woocommerce_payment_gateways', 'mpgs_add_gateway_class');

