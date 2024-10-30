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
 * Mpgs_Gateway_Request_Abstract class.
 */
abstract class Mpgs_Gateway_Request_Abstract {



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
	public function __construct( Mpgs_Gateway_Config $config) {
		$this->config = $config;
	}

	/**
	 * Builds request array
	 *
	 * @param  array $order Order.
	 * @return array
	 */
	public function build( $order, $session) {
		return array(
		'token'   => $this->config->getHeaderAuthorization(),
		'request' => $this->get_build_array($order, $session),
		);
	}

	/**
	 * Builds abstract request array
	 *
	 * @param  array $order Order.
	 * @return array
	 */
	abstract public function get_build_array( $order, $session);
}
