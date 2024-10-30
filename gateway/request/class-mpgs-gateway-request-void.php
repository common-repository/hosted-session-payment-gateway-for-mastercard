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
 * MPGS Capture class.
 */
class Mpgs_Gateway_Request_Void {




	/**
	 * Config
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor
	 *
	 * @param Mpgs_Hosted_Sessions_Gateway_Config $config Config.
	 */
	public function __construct( Mpgs_Gateway_Config $config ) {
		$this->config = $config;
	}

	/**
	 * Builds Void request
	 *
	 * @param  array $order_item Order Item.
	 * @return array
	 */
	public function build( $order_item ) {
		$trn = 'VDTranid' . rand(1, 1000000) . time();
		$data                           = array(
		'token'   => $this->config->getHeaderAuthorization(),
		'request' => array(
		'data' => array(
		'apiOperation' => 'VOID',
		'transaction' => array(                        
		'targetTransactionId'=> $order_item->id_payment,
					),
					
		),
		'method' => 'PUT',
		'uri' =>  $this->config->get_order_request_url($order_item->reference, $trn),
		),
		);
		$log['path']            = __METHOD__;
		$log['void_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;
	}

}
