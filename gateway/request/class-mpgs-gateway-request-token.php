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
 * Mpgs_Gateway_Request_Token class.
 */
class Mpgs_Gateway_Request_Token {




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
	 * Builds access token request
	 *
	 * @return string|null
	 */
	public function get_access_token() {
		
		$log['path'] = __METHOD__;
				 
				 
		try {
			$response              = wp_remote_post(
				$this->config->get_identity_url(),
				array(
				'method'      => 'POST',
				'httpversion' => '1.0',
				'timeout'     => 600,
				'headers'     => array(
				'Authorization' => 'Basic ' . $this->config->get_api_password(),
				'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'        => http_build_query(array( 'grant_type' => 'client_credentials' )),
				)
			);
			 
			 
			print_r($response);
			exit;
			$log['token_response'] = $response['body'];
			 
			if (is_wp_error($response) ) {
					echo esc_html($response->get_error_message(), 'woocommerce');
					die();
			} else {
				 $result = json_decode($response['body']);
				if (isset($result->access_token) ) {
					return $result->access_token;
				}
			}
		} catch ( Exception $e ) {
			$log['exception'] = $e->getMessage();
			echo esc_html($e->getMessage(), 'woocommerce');
			die();
		} finally {
			$this->config->gateway->debug($log);
		}
	}

}
