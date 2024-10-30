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
class Mpgs_Gateway_Request_3DSecure extends Mpgs_Gateway_Request_Abstract {


	/**
	 * Builds 3D check request array
	 *
	 * @param  array $order Order.
	 * @return array
	 */
	public function get_build_array( $order, $session ) {
		
		$secureid = time() . rand(10, 1000);        
		$data = array(
		'data' => array(
		'apiOperation' => 'CHECK_3DS_ENROLLMENT',
		'order' => array(
		'currency' =>  $order['order']['currency'],
		'amount' => strval($order['order']['amount']),                    
		),
		'session' => array(
					'id' => $session,                    
		),
		'3DSecure' => array(
					'authenticationRedirect' => array(
						'responseUrl' => site_url() . '/wc-api/mpgssecurepayment?sessionId=' . $session . '&orderId=' . $order['orderId'] . '&secureId=' . $secureid,
						
		),
		),
		),
		'method' => 'PUT',            
		'uri' =>  $this->config->get_3ds_request_url($secureid),
		);
		$log['path']         = __METHOD__;
		$log['3Dsecure_request'] = $data;
		$this->config->gateway->debug($log);
		return $data;        
	}
}
