<?php
/**
 * Settings MPGS Data
 *
 * @package Mpgs_Hosted_Session
 */

if (! defined('ABSPATH') ) {
	exit;
}
/**
 * MPGS_Gateway_Request_Capture class.
 */
class Mpgs_Gateway_Request_Capture {




	/**
	 * Config
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor
	 *
	 * @param MPGS_Config $config Config.
	 */
	public function __construct( Mpgs_Gateway_Config $config ) {
		$this->config = $config;
	}

	/**
	 * Builds Capture request
	 *
	 * @param  array $order_item Order Item.
	 * @return array
	 */
	public function build( $order_item ) {
		
		$trn = 'CPTranID' . rand(1, 1000000) . time();
		$data= array(
		'token'   => $this->config->getHeaderAuthorization(),
		'request' => array(
		'data' => array(
		'apiOperation' => 'CAPTURE',
		'transaction' => array(
		'currency' =>  $order_item->currency,
		'amount' => strval($order_item->amount),
					),
		),
		'method' => 'PUT',
		'uri' =>  $this->config->get_order_request_url($order_item->reference, $trn),
		),
		);    
		$log['path']            = __METHOD__;
		$log['capture_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;
	}

}
