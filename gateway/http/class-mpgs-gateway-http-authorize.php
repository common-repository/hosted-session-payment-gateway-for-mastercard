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
 * Mpgs_Gateway_Http_Authorize class.
 */
class Mpgs_Gateway_Http_Authorize extends Mpgs_Gateway_Http_Abstract {





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
		$response = json_decode($response_enc);
		if (isset($response->response) ) {
			return $response_enc;
		} else {
			return false;
		}
	}
}
