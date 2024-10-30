<?php
/**
 * Settings MPGS Data
 *
 * @package Mpgs_Hosted_Session
 * @author  Abzer <ecomsupport@abzer.com>
 * @link    https://www.abzer.com
 */

defined('ABSPATH') || exit;
require_once 'class-mpgs-gateway-request-abstract.php';
/**
 * Mpgs_Gateway_Request_Sale class.
 */
class Mpgs_Gateway_Request_Sale extends Mpgs_Gateway_Request_Abstract {


	/**
	 * Builds sale request array
	 *
	 * @param  array $order Order.
	 * @return array
	 */
	public function get_build_array( $order, $session) {

		$sid = filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING);
		if (isset($_GET['secureId'])) {
			$secureid = $_GET['secureId'];			
		}
		$transactionid      = 'Txn' . rand(1, 1000000) . time();
		$data = array(
		'data' => array(
		'apiOperation' => 'PAY',
		'3DSecureId' => $secureid,
		'order' => array(
		'currency' =>  $order->currency,
		'amount' => strval($order->amount),
		'reference'=> $order->reference, 
		),
		'session' => array(
					'id' => $session,                     
		),
		'sourceOfFunds' => array(
					'type' => 'CARD',                     
		),                 
				 
		),
		'method' => 'PUT',
		'uri' =>  $this->config->get_order_request_url($order->reference, $order->id_payment),
		);      
		  
		$log['path']         = __METHOD__;
		$log['sale_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;        
	}
}
