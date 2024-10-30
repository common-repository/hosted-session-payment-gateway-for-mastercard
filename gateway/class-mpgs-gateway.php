<?php
/**
 * Mastercard Payment Gateway Services: Hosted Session.
 *
 * @package Mpgs_Hosted_Session
 */

if (! defined('ABSPATH') ) {
	exit;
}

require_once dirname(__FILE__) . '/config/class-mpgs-gateway-config.php';
require_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-token.php';
require_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-transfer.php';
require_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-abstract.php';

/**
 * Mpgs_Gateway class.
 */
class Mpgs_Gateway extends WC_Payment_Gateway {


	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * Singleton instance
	 *
	 * @var Mpgs_Gateway
	 */
	private static $instance;

	/**
	 * Notice variable
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Order Status Variable
	 *
	 * @var string Order Status.
	 */
	protected $order_status;

	/**
	 * Status
	 *
	 * @var string status
	 */
	protected $status_set_to;

	/**
	 * Get instance of Mpgs_Gateway
	 *
	 * Returns a new instance of self, if it does not already exist.
	 *
	 * @static
	 * @return Mpgs_Gateway
	 */
	public static function get_instance() {
		if (! isset(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->id   = 'mpgs';
		$this->icon = ''; // URL of the icon that will be
		// displayed on checkout page near your gateway name.
		$this->has_fields         = false; // in case you need a custom credit card form.
		$this->method_title       = 'MPGS Online Payment Gateway';
		$this->method_description = 'Hosted Session Payment Gateway from Mastercard';
		// will be displayed on the options page
		// gateways can support subscriptions, refunds, saved payment methods.
		$this->supports = array(
		'products',
		'refunds',
		);

		// Method with all the options fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		$this->order_status    = include dirname(__FILE__) . '/order-status-mpgs.php';
		$this->title          = $this->get_option('mpgs_title');
		$this->description    = $this->get_option('mpgs_description');
		$this->enabled        = $this->get_option('mpgs_enabled');
		$this->environment    = $this->get_option('mpgs_environment');
		$this->payment_action = $this->get_option('mpgs_payment_action');
		$this->merchant_id    = $this->get_option('mpgs_merchant_id');
		$this->merchant_url    = $this->get_option('mpgs_merchant_url');
		$this->api_version    = $this->get_option('mpgs_api_version');
		$this->api_password   = $this->get_option('mpgs_api_password');
		$this->debug          = 'yes' === $this->get_option('mpgs_debug', 'no');
		self::$log_enabled    = $this->debug;
	}

	/**
	 * Plug-in options
	 */
	public function init_form_fields() {
		$this->form_fields = include 'settings-mpgs.php';
	}

	

	/**
	 * Initilize module hooks
	 */
	public function init_hooks() {
		add_action('woocommerce_receipt_mpgs', array( $this, 'receipt_page' ));
		add_action('woocommerce_api_mpgsonline', array( $this, 'update_mpgs_response' ));
		add_action('woocommerce_api_mpgsredirect', array( $this, 'mpgs_redirect' ));
		add_action('woocommerce_api_mpgssecurepayment', array( $this, 'update_mpgssecurepayment'));
		if (is_admin() ) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
			add_action('add_meta_boxes', array($this, 'mpgs_online_meta_boxes'));
			add_action('save_post', array($this, 'mpgs_online_actions'));
		}
	}

	/**
	 * Add notice query variable
	 *
	 * @param  string $location Location.
	 * @return string
	 */
	public function add_notice_query_var( $location ) {
		remove_filter('redirect_post_location', array( $this, 'add_notice_query_var' ), 99);
		return add_query_arg(array( 'message' => false ), $location);
	}

	/**
	 * Processing order
	 *
	 * @global object $woocommerce
	 * @param  int $order_id Order ID.
	 * @return array|null
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order($order_id);
		$pay_url = add_query_arg(
			array(
			'key'           => $order->get_order_key(),
			'pay_for_order' => false,
			), $order->get_checkout_payment_url() 
		);

		return array(
		'result'    => 'success',
		'redirect'    => $pay_url
		);
	}

	/**
	 * Show hosted session page 
	 *
	 * @global object $woocommerce
	 * @param  int $order_id Order ID.
	 * @return array|null
	 */
	public function receipt_page( $order_id ) {
		$config  = new Mpgs_Gateway_Config($this);
		$order = wc_get_order($order_id);
		?>         
		<style id="antiClickjack">body{display:none !important;}
			iframe {
				max-width: 800px;
				min-width: 600px;
				}
		</style>
		<div><iframe id="3ds" class="3ds"   style="position: absolute"  width="800"  height="400" frameborder="0" scrolling="no"></iframe></div> 
		<div id="mpgs-payment">
			<div id="payment-section">
				<div class="payment-heading">    
					<h3>MPGS Hosted Session</h3>
				</div>
				<div class="col-md-12">
					<label class="form-control-label mod='mpgs'">Card Number:</label>
					<input type="text" id="card-number" class="form-control input-text" title="card number" aria-label="enter your card number" value="" disabled tabindex="1" readonly>
					<span id="card-error" class="error"></span>
				</div>
				<div class="col-md-6" style="padding-right:10px;">
					<label class="form-control-label mod='mpgs'">Expiry Month: </label>
					<select id="expiry-month" class="form-control input-text" required="" readonly style="width: 100%;">                          
						<option value="">Select Month</option>
		  <?php foreach ($config->getExpiryMonth() as $key => $value) { ?>
												<option value="<?php echo esc_html($key); ?>"> <?php echo esc_html($value); ?></option> 
		  <?php } ?>             
					</select>
					<span id="month-error" class="error"></span>
				</div>
				<div class="col-md-6" style="padding-left:10px;">
					<label class="form-control-label mod='mpgs'">Expiry Year: </label>
					<select id="expiry-year" class="form-control input-text" required="" readonly style="width: 100%;">
						<option value="">Select Year</option>
		<?php foreach ($config->getExpiryYear() as $key => $value) { ?>
												<option > <?php echo esc_html($value); ?></option> 
		<?php } ?>  
					</select>
					<span id="year-error" class="error"></span>
				</div>
				<div class="col-md-12">
					<label class="form-control-label mod='mpgs'">Security Code:</label>
					<input type="text" id="security-code" class="input-field form-control" title="security code" aria-label="three digit CCV security code" value="" tabindex="4" readonly>
					<span id="cvv-error" class="error"></span>
				</div>             

				<div class="col-md-12">
					<label class="form-control-label mod='mpgs'">Cardholder Name:</label>
					<input type="text" id="cardholder-name" class="input-field form-control" title="cardholder name" aria-label="enter name on card" value="" tabindex="3" >
					<span id="name-error" class="error"></span>
				</div>
				<div id="error"></div><br/>
				  
				<div class="col-md-12 form-footer clearfix"><button id="payButton" class="btn-primary float-xs-right" disabled onclick="pay();">Pay Now</button></div> 

			</div>            
			<div id="loading" style="display:none; width: 500px"> 
				<img src="<?php echo esc_html(plugin_dir_url(dirname(__FILE__))) . 'gateway/assets/images/loading.gif'; ?>"><br/><br/>
				<span class="pro-text">Please do not refresh the page and wait while we are processing your payment!.</span>
			</div>             
		</div>        
	   
		<script type="text/javascript">
		 document.getElementById("3ds").style.display = "none";
			if (self === top) {
				var antiClickjack = document.getElementById("antiClickjack");
				antiClickjack.parentNode.removeChild(antiClickjack);
			} else {
				top.location = self.location;
			} 
			var sessions = [];
			var order_id = <?php echo esc_html($order_id); ?>;
			PaymentSession.configure({
				fields: {
					// ATTACH HOSTED FIELDS TO YOUR PAYMENT PAGE FOR A CREDIT CARD
					card: {
						number: "#card-number",
						securityCode: "#security-code",
						expiryMonth: "#expiry-month",
						expiryYear: "#expiry-year",
						nameOnCard: "#cardholder-name"
					}
				},   
				//SPECIFY YOUR MITIGATION OPTION HERE
				frameEmbeddingMitigation: ["javascript"],
				callbacks: {
					initialized: function(response) {
						if ("ok" == response.status) {
							 
							document.getElementById("card-number").disabled = false;
							document.getElementById("payButton").disabled = false;
						} else {
							document.getElementById("error").innerHTML = 'Invalid configuration';
						}
						  
						// HANDLE INITIALIZATION RESPONSE

					},
				formSessionUpdate: function(response) {
					// HANDLE RESPONSE FOR UPDATE SESSION
					if (response.status) {
						if ("ok" == response.status) {                      
							document.getElementById("card-error").innerHTML = '';
							document.getElementById("year-error").innerHTML = '';
							document.getElementById("month-error").innerHTML = '';
							 
							//check if the security code was provided by the user
							if (response.sourceOfFunds.provided.card.securityCode) {
								document.getElementById("cvv-error").innerHTML = "";
								// ajax call
								jQuery.ajax({
									  type:'POST',
									  data:{sid:response.session.id, order_id: order_id},
									  url: "<?php echo esc_html(site_url()) . '/wc-api/mpgsonline'; ?>",
									  beforeSend: function() {
										document.getElementById("3ds").style.display = "block";
										document.getElementById("payment-section").style.display = "none";
										document.getElementById("loading").style.display = "block";
										 
									},
									  success: function(result) {                                           
										  document.getElementById("3ds").srcdoc = result;
									  }
								});  
							} else {
								document.getElementById("cvv-error").innerHTML = "Security code invalid!.";
							}
						} else if ("fields_in_error" == response.status) {                        
							if (response.errors.cardNumber) {
								document.getElementById("card-error").innerHTML = 'Card number invalid or missing!.';
							} else {
								document.getElementById("card-error").innerHTML = '';
							}

							if (response.errors.expiryYear) {
								document.getElementById("year-error").innerHTML = "Expiry year invalid or missing!.";
							} else {
								document.getElementById("year-error").innerHTML = '';
							}

							if (response.errors.expiryMonth) {
								document.getElementById("month-error").innerHTML = "Expiry month invalid or missing!.";
							} else {
								document.getElementById("month-error").innerHTML = '';
							}
							if (response.errors.securityCode) {
								document.getElementById("cvv-error").innerHTML = "Security code invalid!.";
							}
						} else if ("request_timeout" == response.status)  {
							document.getElementById("error").innerHTML = "Session update failed with request timeout: " + response.errors.message;
						} else if ("system_error" == response.status)  { 
							document.getElementById("error").innerHTML = "Session update failed with system error: " + response.errors.message;
						}
					} else {
						document.getElementById("error").innerHTML = "Session update failed: " + response;
					}
					  
				}

			},
			interaction: {
				displayControl: {
					formatCard: "EMBOSSED",
					invalidFieldCharacters: "REJECT"
					 }
				}
			});
			function pay() {
				// UPDATE THE SESSION WITH THE INPUT FROM HOSTED FIELDS
				PaymentSession.updateSessionFromForm('card');
			}
		</script>

		<?php 

		 
	}

	/**
	 * Ajx call
	 */
	public function update_mpgs_response() {
		 
		$sid = filter_input(INPUT_POST, 'sid', FILTER_SANITIZE_STRING);
		$order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_STRING); 
		 
		 
		if (isset($sid) && !empty($sid) && isset($order_id) && !empty($order_id) ) {            
			echo esc_html($this->process_order($order_id, $sid));
			exit;
		}
	}
	/**
	 * Catch response from mpgs redirect
	 */
	public function mpgs_redirect() {
		 
		global $woocommerce; 
		$log['path'] = __METHOD__;
		$redirect_url = wc_get_checkout_url();
		$ref = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_STRING);         
		if (isset($ref) && !empty($ref) ) {             
			$mpgsOrder  = $this->fetch_order_by_reference($ref);             
			$order = wc_get_order($order_id);
			if (!empty($mpgsOrder) && !empty($order) && 'wc-mpgs-complete' ==  $mpgsOrder->status) {
				$woocommerce->cart->empty_cart();
				$redirect_url = $order->get_checkout_order_received_url();
			}
		} else {
			wc_add_notice('Invalid Order Reference ID', 'error');
		}
		$this->debug($log);
		wp_safe_redirect($redirect_url);
		exit();
	}

	public function update_mpgssecurepayment() {
		 
		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-sale.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-sale.php';
		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-authorize.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-authorize.php'; 
		global $woocommerce;
		$log['path']          = __METHOD__;
		$log['is_configured'] = false; 
		$orderid   = filter_input(INPUT_GET, 'orderId', FILTER_SANITIZE_STRING);
		$sessionid = filter_input(INPUT_GET, 'sessionId', FILTER_SANITIZE_STRING);
		$secureid  = filter_input(INPUT_GET, 'secureId', FILTER_SANITIZE_STRING);
		$config               = new Mpgs_Gateway_Config($this);
		switch ($config->get_payment_action() ) { 
			 
			case 'sale':
				$request_class = new Mpgs_Gateway_Request_Sale($config);                                    
				$request_http  = new Mpgs_Gateway_Http_Sale();                
				break;
			case 'authorize':
				$request_class = new Mpgs_Gateway_Request_Authorize($config);
				$request_http  = new Mpgs_Gateway_Http_Authorize();                                                    
				break;    
			default:
				break;
		}
		$mpgsOrder  = $this->fetch_order_by_reference($orderid);
		$transfer_class = new Mpgs_Gateway_Http_Transfer(); 
		$response =  $request_http->place_request($transfer_class->create($request_class->build($mpgsOrder, $sessionid))); 
		if ($response) {
			return $this->process_payment_responce($response);
		} else {
			wc_add_notice('Error! Invalid configuration or Currency is not mapped', 'error');
			return false;
		}
		
	}

	/**
	 * Process order
	 */
	public function process_order( $order_id, $session_id) {
		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-sale.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-sale.php';

		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-authorize.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-authorize.php';

		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-3DSecure.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-3DSecure.php';
		 
		global $woocommerce;
		$log['path']          = __METHOD__;
		$log['is_configured'] = false;            
		$order                = wc_get_order($order_id);
		$config               = new Mpgs_Gateway_Config($this);          
		if ($config->is_complete() ) {
			$log['is_configured'] = true;
			$this->debug($log);    
			$request_class = new Mpgs_Gateway_Request_3DSecure($config);
			$request_http  = new Mpgs_Gateway_Http_3DSecure();
			$mpgsOrder = $this->buildMpgsOrder($order);                    
			$this->saveMpgsPaymentData($mpgsOrder, $order->get_id());
			
			$transfer_class = new Mpgs_Gateway_Http_Transfer();
			$response = $request_http->place_request($transfer_class->create($request_class->build($mpgsOrder, $session_id)));
			if ($response) {
				$this->save_data($order);                
				$log['action'] = 'Redirecting to payment gateway...';
				$this->debug($log);                
			}			
			$respons = json_decode($response, true);            
			if (isset($respons['3DSecure']['authenticationRedirect']['simple']['htmlBodyContent'])) {
				echo $respons['3DSecure']['authenticationRedirect']['simple']['htmlBodyContent'];
			}                     
		}
	}

	/**
	 * Process payment responce
	 */
	public function process_payment_responce( $responseEnc ) {
		$response = json_decode($responseEnc);        
		if (isset($response->result)) { 
			$mpgsOrder  = $this->fetch_order_by_reference($response->order->id);
			$order = wc_get_order($mpgsOrder->order_id);
			$status = $this->responce_status($response);                        
			$data = array();
			$data['reference'] = $response->order->id;
			$data['state'] = $response->order->status;
			$data['status'] = $status;
			$data['receipt'] = $response->transaction->receipt;    
			if (isset($response->totalCapturedAmount)) {         
				$data['capture_amount'] =is_null($response->totalCapturedAmount) ? '0' : $response->totalCapturedAmount;
				 
			} else {
				$data['capture_amount'] =is_null($response->order->totalCapturedAmount) ? '0' : $response->order->totalCapturedAmount;    
				 
			}  
			$this->update_table($data, $mpgsOrder->mid);
			if ('wc-mpgs-complete' == $status) {
				
				$order->payment_complete();                             
				$order->update_status($status);               
				$message = 'Captured Amount: ' . $order->get_formatted_order_total() . ' | Transaction Id: ' . $mpgsOrder->id_payment;                
				$order->add_order_note($message);                
				$emailer = new WC_Emails();
				$emailer->customer_invoice($order);
				$this->message = 'Success! ' . $message . ' of an order #' . $order->id;
				WC_Admin_Notices::add_custom_notice('mpgs', $this->message);
			}
			
			if ('wc-mpgs-authorised' == $status) {   
				$message = 'Authorized Amount: ' . $order->get_formatted_order_total();              
				$order->payment_complete();
				$order->update_status($status);
				$order->add_order_note($message);   
					
			}
			if ('wc-mpgs-failed' == $status) { 
				$message = 'Transaction Failed.' ;
				$order->payment_complete();
				$order->update_status($status);
				$order->add_order_note($message);
			}
		}     
		$redirect_url = $order->get_checkout_order_received_url(); 		     
		echo "<script>window.top.location.href = '" . esc_html($redirect_url) . "';</script>";		
		die;        
		return false;    
	}
	/**
	 * Update Table.
	 *
	 * @param  array $data Data.
	 * @param  int   $mid  MID.
	 * @return bool  true
	 */
	public function update_table( array $data, $mid ) {
		global $wpdb;
		return $wpdb->update(MPGS_TABLE, $data, array( 'mid' => $mid )); // db call ok; no-cache ok.
	}

	public function update_captable( array $data, $mid ) {
		global $wpdb;
		return $wpdb->update(MPGS_TABLE, $data, array( 'reference' => $mid )); // db call ok; no-cache ok.
	}
	/**
	 * Responce status
	 */
	public function responce_status( $response ) {
		 
		switch ($response->result) {
			case 'SUCCESS':
				if ( 'AUTHORIZED'==$response->order->status) {
					 $status = $this->order_status[3]['status'];
				} else {
					$status = $this->order_status[2]['status']; 
				}
				break;
			case 'FAILURE':
				$status = $this->order_status[1]['status'];
				break;
			default:
				$status = $this->order_status[0]['status'];
				break;
		}
		return $status;
	}

	/**
	 * Fetch Order details.
	 *
	 * @param  string $reference Order reference.
	 * @return object
	 */
	public function fetch_order_by_reference( $reference ) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'mpgs_payment WHERE `reference`=%s ORDER BY `mid` DESC', $reference)); // db call ok; no-cache ok.
	}
	/**
	 * Build MPGS Order
	 */
	public function buildMpgsOrder( $order ) {
		return array(
		'apiOperation' => null,
		'order' => array(
		'currency' =>  $order->get_currency(),
		'amount' => strval($order->get_total()),
		),
		'orderId' => 'MPGS_' . $order->get_id(),
		'transactionId' => 'TranId' . rand(1, 1000000) . time(),
		'emailAddress' => $order->get_billing_email(),
		'method' => null,
		'uri' => null
		);
	}

	/**
	 * Build MPGS order.
	 *
	 * @param  array $order
	 * @return bool
	 */
	public function saveMpgsPaymentData( $mpgsOrder, $order_id) {
		$config               = new Mpgs_Gateway_Config($this);            
		$data = array();
		$data['status']     = 'wc-mpgs-pending';
		$data['state']      = 'STARTED';
		$data['order_id']   = $order_id;
		$data['amount']     = $mpgsOrder['order']['amount'];
		$data['currency']   = $mpgsOrder['order']['currency'];
		$data['reference']  = $mpgsOrder['orderId'];
		$data['action']     =  ( $config->get_payment_action() =='sale' ) ?'SALE' : 'AUTHORIZE';        
		$data['id_payment']     = $mpgsOrder['transactionId'];
		if ($this->save_data($data)) {
			return true;
		}
		return false;
	}
	/**
	 * Save data
	 *
	 * @global object $wpdb
	 * @global object $wp_session
	 * @param  object $order Order.
	 */
	public function save_data( $data ) {
		global $wpdb;
		$wpdb->replace(
			MPGS_TABLE,
			$data
		); // db call ok; no-cache ok.
	}

	/**
	 * Update data
	 *
	 * @global object $wpdb
	 * @param  array $data  Data.
	 * @param  array $where Where condition.
	 */
	public function update_data( array $data, array $where ) {
		global $wpdb;
		$wpdb->update(MPGS_TABLE, $data, $where); // db call ok; no-cache ok.
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level   Optional. Default 'info'. Possible values:
	 *                        emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'debug' ) {
		if (self::$log_enabled ) {
			if (empty(self::$log) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log($level, $message . "\r\n", array( 'source' => 'mpgs' ));
		}
	}

	/**
	 * Debug method.
	 *
	 * @param array $message Log message.
	 */
	public function debug( array $message ) {
		self::log(wp_json_encode($message), 'debug');
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the error field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();
		  
		if ('yes' !== $this->get_option('mpgs_debug', 'no') ) {
			if (empty(self::$log) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear('mpgs');
		}
		return $saved;
	}

	/**
	 * Fetch Order details.
	 *
	 * @param  int $order_id Order ID.
	 * @return object
	 */
	public function fetch_order( $order_id ) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'mpgs_payment WHERE `order_id`=%d', $order_id)); // db call ok; no-cache ok.
	}

	/**
	 * MPGS Online Meta Boxes
	 */
	public function mpgs_online_meta_boxes() {
		global $post;
		$order_id = $post->ID;
		$payment_method = get_post_meta($order_id, '_payment_method', true);
		if ($this->id === $payment_method) {
			add_meta_box(
				'mpgs-payment-actions', __('MPGS Online Payment', 'woocommerce'), array($this, 'mpgs_online_meta_box_payment'), 'shop_order', 'side', 'high'
			);
		}
	}

	/**
	 * Generate the MPGS Online payment meta box and echos the HTML
	 */
	public function mpgs_online_meta_box_payment() {
		global $post;
		$order_id = $post->ID;
		$order = wc_get_order($order_id);        
		if (!empty($order)) {
			$order_item = $this->fetch_order($order_id);
			try {
				$curency_code = $order_item->currency . ' ';

				echo '<table border="0" cellspacing="10">';
				echo '<tr>';
				echo '<td>' . esc_html('State:', 'woocommerce') . '</td>';
				echo '<td>' . esc_html($order_item->state, 'woocommerce') . '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>' . esc_html('Payment Id:', 'woocommerce') . '</td>';
				echo '<td>' . esc_html($order_item->id_payment, 'woocommerce') . '</td>';
				echo '</tr>';
				echo '<tr>';                
				echo '<tr>';
				echo '<td>' . esc_html('Authorised:', 'woocommerce') . '</td>';
				echo '<td>' . esc_html($curency_code . number_format($order_item->amount, 2), 'woocommerce') . '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>' . esc_html('Captured:', 'woocommerce') . '</td>';
				echo '<td>' . esc_html($curency_code . number_format($order_item->capture_amount, 2), 'woocommerce') . '</td>';
				echo '</tr>';
				$refunded = 0;
				if ('wc-mpgs-full-ref' === $order_item->status ) {
					$refunded = $order_item->amount;
				} elseif ('wc-mpgs-part-ref' === $order_item->status ) {
					$refunded = $order_item->amount - $order_item->capture_amount;
				}
				echo '<tr>';
				echo '<td>' . esc_html('Refunded:', 'woocommerce') . '</td>';
				echo '<td>' . esc_html($curency_code . number_format($refunded, 2), 'woocommerce') . '</td>';
				echo '</tr>';
				if ('wc-mpgs-authorised' === $order_item->status) {
					echo '<tr><td>';
					echo '<input id="mpgs_void_submit" class="button void" name="mpgs_void" type="submit" value="' . esc_html('Void', 'woocommerce') . '" /></td>';
					echo '<td><input id="mpgs_capture_submit" class="button button-primary" name="mpgs_capture" type="submit" value="' . esc_html('Capture', 'woocommerce') . '" /></td>';
					echo '</tr>';
				}
				echo '</table>';
			} catch (Exception $e) {
				echo esc_html($e->getMessage(), 'woocommerce');
			}
		}
	}

	/**
	 * Handle actions on order page
	 *
	 * @param  int $post_id Post ID.
	 * @return null
	 */
	public function mpgs_online_actions( $post_id) {
		$this->message = '';
		WC_Admin_Notices::remove_all_notices();
		$order_item = $this->fetch_order($post_id);
		$order      = wc_get_order($post_id);        
		if ($order && $order_item ) {
			if (isset($_POST['mpgs_capture']) ) {    
				 $this->process_actions('Capture', $order, $order_item);
			} else if (isset($_POST['mpgs_void']) ) { 
				 $this->process_actions('Void', $order, $order_item);
			}
		} 
		$order = wc_get_order($post_id);
		add_filter('redirect_post_location', array($this, 'add_notice_query_var'), 99);
		return true;
	}
	/**
	 * Process capture or void action
	 *
	 * @param string   $action     Action.
	 * @param WC_Order $order      Order.
	 * @param object   $order_item Order Item.
	 */

	public function process_actions( $action, $order, $order_item ) {
		 
		$config      = new Mpgs_Gateway_Config($this);        
		if ($config->is_complete($order->get_currency()) ) {
			if ('Capture' === $action ) {                     
				$this->mpgs_capture($order, $config, $order_item);
			} else if ('Void' === $action ) {                     
				$this->mpgs_void($order, $config, $order_item);
			} 
		}
		 
	}

	/**
	 * Process Capture
	 *
	 * @param WC_Order            $order      Order.
	 * @param MPGS_Gateway_Config $config     Config.
	 * @param object              $order_item Order Item.
	 */
	public function mpgs_capture( $order, $config, $order_item ) {
		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-capture.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-capture.php';
		 
		$request_class  = new Mpgs_Gateway_Request_Capture($config);         
		$request_http   = new Mpgs_Gateway_Http_Capture();
		$transfer_class = new Mpgs_Gateway_Http_Transfer();
		$response = $request_http->place_request($transfer_class->create($request_class->build($order_item)));
		if ($response) {
			return $this->process_capture_responce($response, $order);
		}    
		return false; 
	}
	/**
	 * Process Authorization Reversal
	 *
	 * @param WC_Order            $order      Order.
	 * @param MPGS_Gateway_Config $config     Config.
	 * @param object              $order_item Order Item.
	 */

	public function mpgs_void( $order, $config, $order_item ) {
		include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-void.php';
		include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-void.php';
		 
		$request_class  = new Mpgs_Gateway_Request_Void($config);         
		$request_http   = new Mpgs_Gateway_Http_Void();        
		$transfer_class = new Mpgs_Gateway_Http_Transfer(); 
		$response = $request_http->place_request($transfer_class->create($request_class->build($order_item)));        
		if ($response) {
			return $this->process_void_responce($response, $order);            
		}             
		return false; 
	}


	public function process_capture_responce( $responseEnc, $order ) {
		$response = json_decode($responseEnc);        
		if (isset($response->result)) {
			$config               = new Mpgs_Gateway_Config($this);
			$data = array();
			$data['status']     = $this->order_status[4]['status'];   
			$data['state']      =  'CAPTURED';           
			$data['id_payment'] =$response->transaction->id; 
			$data['capture_amount'] = $response->transaction->amount;
			$mid = $response->order->id;
			$this->update_captable($data, $mid);    
			$status =$this->order_status[4]['status'];
			$order->update_status($status); 
			$emailer = new WC_Emails();
			$emailer->customer_invoice($order);
			$text          = 'Captured an amount ' . $order->get_formatted_order_total(); 
			$order->add_order_note($text);                                        
			$this->message = 'Success! ' . $text . ' of an order #' . $order->id;
			//$text         .= '. Transaction ID: ' . $response->transaction->id;            
			WC_Admin_Notices::add_custom_notice('mpgs', $this->message);
		}
	}    
	public function process_void_responce( $responseEnc, $order ) {
		$response = json_decode($responseEnc);
						
		if (isset($response->result)) {
			$config               = new Mpgs_Gateway_Config($this);
			$data = array();
			$data['status']     =  $this->order_status[6]['status'];   
			$data['state']      = $response->order->status;
			$data['capture_amount']  = $response->order->totalCapturedAmount;
			$mid = $response->order->id;
			$this->update_captable($data, $mid);
			$status = $this->order_status[6]['status'];            
			$order->update_status($status);             
			$text = 'The void transaction was successful.';            
			$order->add_order_note($text);
			WC_Admin_Notices::add_custom_notice('mpgs', $text);                     
		}
	}
	/**
	 * Process Refund
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount   Amount.
	 * @param  string     $reason   Reason.
	 * @return bool
	 */

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$this->message = ''; 
		if (isset($amount) && $amount > 0 ) {
			include_once dirname(__FILE__) . '/request/class-mpgs-gateway-request-refund.php';
			include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-refund.php';            
			include_once dirname(__FILE__) . '/http/class-mpgs-gateway-http-transfer.php';             
			$order_item = $this->fetch_order($order_id);
			$order      = wc_get_order($order_id);
			if ($amount <= $order_item->capture_amount) {    
				$config      = new Mpgs_Gateway_Config($this);                   
				if ($config->is_complete($order->get_currency()) ) {                    
					$request_class  = new Mpgs_Gateway_Request_Refund($config);
					$request_http   = new Mpgs_Gateway_Http_Refund();
					$transfer_class = new Mpgs_Gateway_Http_Transfer();                                            
					$result = $request_http->place_request($transfer_class->create($request_class->build($order_item, $amount))); 
					return $this->refund_action($order, $result, $order_item);
				}
			}
		} else {
			throw new Exception('Invalid amount');
		}
		return false;
	}
	/**
	 * Action after refund process
	 *
	 * @param WC_Order $order      Order.
	 * @param array    $result     Result.
	 * @param object   $order_item Order Item.
	 */

	public function refund_action( $order, $result, $order_item ) {
		 
		$response = json_decode($result);                
		if ($response ) {
			$data                 = array();
			if ('PARTIALLY_REFUNDED' === $response->order->status) {
				$data['status']       = $this->order_status[5]['status']; 
			} else if ('REFUNDED' === $response->order->status) {
				$data['status']      = $this->order_status[7]['status'];
			} else {
				$data['status']      = $this->order_status[1]['status'];    
			}           
			$data['state']        =  $response->order->status;
			$data['capture_amount'] = ( ( $response->order->totalCapturedAmount )-( $response->order->totalRefundedAmount ) );          
			$this->update_data($data, array('mid' => $order_item->mid )); 
			$text          = 'Refunded an amount ' . number_format($response->transaction->amount, 2) . ' ' . $response->transaction->currency;
			$this->message = 'Success! ' . $text . ' of an order #' . $order_item->order_id;
			$text         .= '. Transaction ID: ' . $response->transaction->id;
			
			if ('PARTIALLY_REFUNDED' === $response->order->status) {    
				$status     = $this->order_status[5]['status'];             
				$order->update_status($status);
			}
			$order->add_order_note($text);
			WC_Admin_Notices::add_custom_notice('mpgs', $this->message);
			return true;
		}
	}
	/**
	 * Hosted Session Js
	 *
	 * @return string
	 */
	public function hostedSessionJs() {
		$config  = new Mpgs_Gateway_Config($this);
		return $config->get_identity_url() . '/form/version/' . $config->get_api_version() . '/merchant/' . $config->get_merchant_id() . '/session.js';
		
	}
	
}
