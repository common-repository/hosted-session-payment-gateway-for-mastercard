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
 * Mpgs_Gateway_Config class.
 */
class Mpgs_Gateway_Config {




	/**
	 * Config tags
	 */

	//const SANDBOX_IDENTITY_URL  = 'https://test-network.mtf.gateway.mastercard.com';
	 
	//const LIVE_IDENTITY_URL     = 'https://na-gateway.mastercard.com';
	const ORDER_ENDPOINT        = '/api/rest/version/%s/merchant/%s/order/%s/transaction/%s';
	const THREEDS_ENDPOINT      = '/api/rest/version/%s/merchant/%s/3DSecureId/%s';
	const SANDBOX = 'sandbox';
	const LIVE    = 'live';
	 
	/**
	 * Pointer to gateway making the request.
	 *
	 * @var Mpgs_Gateway
	 */
	public $gateway;

	/**
	 * Token for gateway request
	 *
	 * @var string token
	 */
	private $token;

	/**
	 * Constructor.
	 *
	 * @param Mpgs_Gateway $gateway Mpgs Online gateway object.
	 */
	public function __construct( Mpgs_Gateway $gateway) {
		$this->gateway = $gateway;
	}

	/**
	 * Retrieve api password and merchant id empty or not
	 *
	 * @return bool
	 */
	public function is_complete() {
		if (! empty($this->get_api_password()) && ! empty($this->get_merchant_id()) ) {
			return true;
		}
		return false;
		 
	}

	/**
	 * Gets Identity Url.
	 *
	 * @return string
	 */
	public function get_identity_url() {
		switch ( $this->get_environment() ) {
			case 'sandbox':
				//$value = self::SANDBOX_IDENTITY_URL;
				$value = $this->get_merchant_url();
														 
				break;
			case 'live':
				//$value = self::LIVE_IDENTITY_URL;
				$value = $this->get_merchant_url();
				break;
			default:
				break;
		}
		return $value;
	}

	/**
	 * Gets Payment Action.
	 *
	 * @return string
	 */
	public function get_payment_action() {
		return $this->gateway->get_option('mpgs_payment_action');
	}

	/**
	 * Gets Environment.
	 *
	 * @return string
	 */
	public function get_environment() {
		return $this->gateway->get_option('mpgs_environment');
	}
	/**
	 * Gets Merchant URL.
	 *
	 * @return string
	 */
	public function get_merchant_url() {
		if (!empty($this->gateway->get_option('mpgs_merchant_url'))) {
			return $this->gateway->get_option('mpgs_merchant_url');        
		} else {
			return null;        
		}
	}
	/**
	 * Gets Api Version.
	 *
	 * @return string
	 */
	public function get_api_version() {
		return $this->gateway->get_option('mpgs_api_version');
	}
	  
	/**
	 * Gets merchant Id.
	 *
	 * @return string
	 */
	public function get_merchant_id() {
		return $this->gateway->get_option('mpgs_merchant_id');
	}

	/**
	 * Gets Api password.
	 *
	 * @return string
	 */
	public function get_api_password() {
		return $this->gateway->get_option('mpgs_api_password');
	}

	/**
	 * Get Expiry Month
	 *
	 * @return array
	 */
	public function getExpiryMonth() {
		return array(
		'01' => 'January',
		'02' => 'February',
		'03' => 'March',
		'04' => 'April',
		'05' => 'May',
		'06' => 'June',
		'07' => 'July',
		'08' => 'August',
		'09' => 'September',
		'10' => 'October',
		'11' => 'November',
		'12' => 'December',
		);
	}

	/**
	 * Get Expiry Year
	 *
	 * @return array
	 */
	public function getExpiryYear() {
		$year = gmdate('y');
		$expiryYear = array();
		for ($i = 0; $i < 15; $i++) {
			$expiryYear[] = $year + $i;
		}
		return $expiryYear;
	}

	/**
	 * Gets Header Authorization.
	 *
	 * @param  int|null $storeId
	 * @return string
	 */
	public function getHeaderAuthorization() {
		return 'merchant.' . $this->get_merchant_id() . ':' . $this->get_api_password();
	}

	/**
	 * Gets Order Request URL.
	 *
	 * @return string
	 */
	public function get_order_request_url( $orderId, $transactionId ) {
		 
		$endpoint = sprintf(self::ORDER_ENDPOINT, $this->get_api_version(), $this->get_merchant_id(), $orderId, $transactionId);
		 
		return $this->get_identity_url() . $endpoint;
	}

	/**
	 * Gets Order Request URL.
	 *
	 * @return string
	 */
	public function get_3ds_request_url( $secureid) {
		 
		$endpoint = sprintf(self::THREEDS_ENDPOINT, $this->get_api_version(), $this->get_merchant_id(), $secureid);
		return $this->get_identity_url() . $endpoint;
	}
}
