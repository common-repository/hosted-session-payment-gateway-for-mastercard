<?php
/**
 * Payment Gateway class for MPGS
 *
 * @package Mpgs_Hosted_Session
 */

if (! defined('ABSPATH') ) {
	exit;
}
/**
 * MPGS_Gateway_Http_Refund class.
 */
class Mpgs_Gateway_Http_Refund extends Mpgs_Gateway_Http_Abstract {




	/**
	 * Processing of API request body
	 *
	 * @param  array $data Data.
	 * @return string
	 */
	protected function pre_process( array $data) {
		 
		return wp_json_encode($data);
	}

	/**
	 * Processing of API response
	 *
	 * @param  array $response_enc Response.
	 * @return array|null
	 */
	protected function post_process( $response_enc) {
		//echo $response_enc;die;
		$response = json_decode($response_enc);
		if (isset($response->response) ) {
			return $response_enc;
		} else {
			return false;
		}
	}
}
