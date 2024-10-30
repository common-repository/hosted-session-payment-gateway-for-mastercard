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
 * Mpgs_Gateway_Request_Authorize class.
 */
class Mpgs_Gateway_Request_Authorize extends Mpgs_Gateway_Request_Abstract {


	/**
	 * Builds sale request array
	 *
	 * @param  array $order Order.
	 * @return array
	 */
	public function get_build_array( $order, $session ) {
		
		$data = array(
		'data' => array(
		'apiOperation' => 'AUTHORIZE',
				
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
		$log['auth_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;        
	}
}
