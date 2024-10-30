<?php
/**
 * Settings MPGS Data
 *
 * @package Mpgs_Hosted_Session
 * @author  Abzer <ecomsupport@abzer.com>
 * @link    https://www.abzer.com
 */
defined('ABSPATH') || exit;

return array(
	'mpgs_enabled'        => array(
		'title'   => __('Enable/Disable', 'woocommerce'),
		'label'   => __('Enable MPGS Online Payment Gateway', 'woocommerce'),
		'type'    => 'checkbox',
		'default' => 'no',
	),
	'mpgs_title'          => array(
		'title'       => __('Title', 'woocommerce'),
		'type'        => 'text',
		'description' => __('The title which the user sees during checkout.', 'woocommerce'),
		'default'     => __('MPGS Online Payment Gateway', 'woocommerce'),
	),
	'mpgs_description'    => array(
		'title'       => __('Description', 'woocommerce'),
		'type'        => 'textarea',
		'css'         => 'width: 400px;height:60px;',
		'description' => __('The description which the user sees during checkout.', 'woocommerce'),
		'default'     => __('You will be redirected to payment gateway.', 'woocommerce'),
	),
	'mpgs_environment'    => array(
		'title'   => __('Environment', 'woocommerce'),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'options' => array(
			'sandbox' => __('Test Region', 'woocommerce'),
			'live'    => __('Live', 'woocommerce'),
		),
		'default' => 'sandbox',
	),
	 
	'mpgs_payment_action' => array(
		'title'   => __('Payment Action', 'woocommerce'),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'options' => array(
			'sale' => __('Purchase', 'woocommerce'),            
			'authorize' => __('Authorize+Capture', 'woocommerce'),
		),
		'default' => 'sale',
	),
	'mpgs_merchant_id'    => array(
		'title' => __('Merchant ID', 'woocommerce'),
		'type'  => 'text',
	),
	'mpgs_api_password'   => array(
		'title' => __('API Password', 'woocommerce'),
		'type'  => 'textarea',
		'css'   => 'width: 400px;height:50px;',
	),
	'mpgs_merchant_url'    => array(
		'title' => __('Merchant URL', 'woocommerce'),
		'type'  => 'text',
	),
	'mpgs_api_version'    => array(
		'title' => __('API version', 'woocommerce'),
		'type'  => 'text',
	),
	'mpgs_debug'          => array(
		'title'       => __('Debug Log', 'woocommerce'),
		'type'        => 'checkbox',
		'label'       => __('Enable logging', 'woocommerce'),
		/* translators: %s: file path */
		'description' => sprintf(__('Log file will be %s', 'woocommerce'), '<code>' . WC_Log_Handler_File::get_log_file_path('mpgs') . '</code>'),
		'default'     => 'no',
	),
);
