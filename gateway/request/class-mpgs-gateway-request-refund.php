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
 * MPGS_Request_Refund class.
 */
class Mpgs_Gateway_Request_Refund {




	/**
	 * Config
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor
	 *
	 * @param Mpgs_Gateway_Config $config Config.
	 */
	public function __construct( Mpgs_Gateway_Config $config ) {
		$this->config = $config;
	}

	/**
	 * Builds ENV refund request
	 *
	 * @param  object $order_item Order Item.
	 * @param  float  $amount     Amount.
	 * @return array|null
	 */
	public function build( $order_item, $amount ) {
		
		$transactionid      = 'Txn' . rand(1, 1000000) . time();
		$data                           = array(
		'token'   => $this->config->getHeaderAuthorization(),
		'request' => array(
		'data' => array(
		'apiOperation' => 'REFUND',
		'transaction' => array(                        
		'targetTransactionId'=> $order_item->id_payment,
		'currency' =>  $order_item->currency,
		'amount' =>$amount ,
					),                    
		),
		'method' => 'PUT',
		'uri' =>  $this->config->get_order_request_url($order_item->reference, $transactionid),
		),
		);
		
		$log['path']            = __METHOD__;
		$log['refund_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;
	}

}
