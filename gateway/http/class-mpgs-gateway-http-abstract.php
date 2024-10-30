<?php
/**
 * Mastercard Payment Gateway Services: Hosted Session.
 *
 * @package Mpgs_Hosted_Session
 */

if (! defined('ABSPATH') ) {
	exit;
}

/**
 * Mpgs_Gateway_Http_Abstract class.
 */
abstract class Mpgs_Gateway_Http_Abstract {





	/**
	 * MPGS Order status.
	 *
	 * @var array $order_status
	 */
	protected $order_status;

	/**
	 * Gateway Object
	 *
	 * @var MPGS $gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->order_status    = include dirname(__FILE__) . '/../order-status-mpgs.php';
		$this->gateway = Mpgs_Gateway::get_instance();
	}

	/**
	 * Places request to gateway.
	 *
	 * @param  TransferInterface $transfer_object Transafer Factory.
	 * @return array|null
	 * @throws Exception Exception.
	 */
	public function place_request( $transfer_object ) {
		  
		$this->order_status  = include dirname(__FILE__) . '/../order-status-mpgs.php';
		$data = $this->pre_process($transfer_object->get_body());
		$log['path'] = __METHOD__;

		try {
			$response  = wp_remote_post(
				$transfer_object->get_uri(),
				array(
				'method'      => $transfer_object->get_method(),
				'httpversion' => '1.0',
				'timeout'     => 30,
				'headers'     => $transfer_object->get_headers(),
				'body'        => $data,
				)
			);
			 
			$log['response'] = $response;
			if (in_array($response['response']['code'], array( 200, 201 ), true) ) {
					  $result = $this->post_process($response['body']);
			} else {
				$error_obj = json_decode($response['body']);
				if (isset($error_obj->error) ) {
					$message = 'Error! #' . $error_obj->error->cause . '|' . $error_obj->error->explanation;
					wc_add_notice($message, 'error');
				}
				$result = false;
			}
			return $result;
		} catch ( Exception $e ) {
			return new WP_Error('error', $e->getMessage());
		} finally {
			$this->gateway->debug($log);
		}
	}

	/**
	 * Processing of API request body
	 *
	 * @param  array $data Data.
	 * @return string|array
	 */
	abstract protected function pre_process( array $data);

	/**
	 * Processing of API response
	 *
	 * @param  array $response Response.
	 * @return array|null
	 */
	abstract protected function post_process( $response);
}
