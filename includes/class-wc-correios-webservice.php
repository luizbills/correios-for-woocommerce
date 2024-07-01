<?php
/**
 * Correios Webservice.
 *
 * @package WooCommerce_Correios/Classes/Webservice
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Correios Webservice integration class.
 */
class WC_Correios_Webservice {

	/**
	 * Webservice URL.
	 *
	 * @var string
	 */
	private $webservice_url = 'https://api.correios.com.br/';

	/**
	 * Shipping method ID.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Shipping zone instance ID.
	 *
	 * @var int
	 */
	protected $instance_id = 0;

	/**
	 * ID from Correios service.
	 *
	 * @var string
	 */
	protected $service = '';

	/**
	 * WooCommerce package containing the products.
	 *
	 * @var array
	 */
	protected $package = null;

	/**
	 * Origin postcode.
	 *
	 * @var string
	 */
	protected $origin_postcode = '';

	/**
	 * Destination postcode.
	 *
	 * @var string
	 */
	protected $destination_postcode = '';

	/**
	 * Package height.
	 *
	 * @var float
	 */
	protected $height = 0;

	/**
	 * Package width.
	 *
	 * @var float
	 */
	protected $width = 0;

	/**
	 * Package diameter.
	 *
	 * @var float
	 */
	protected $diameter = 0;

	/**
	 * Package length.
	 *
	 * @var float
	 */
	protected $length = 0;

	/**
	 * Package weight.
	 *
	 * @var float
	 */
	protected $weight = 0;

	/**
	 * Minimum height.
	 *
	 * @var float
	 */
	protected $minimum_height = 2;

	/**
	 * Minimum width.
	 *
	 * @var float
	 */
	protected $minimum_width = 11;

	/**
	 * Minimum length.
	 *
	 * @var float
	 */
	protected $minimum_length = 16;

	/**
	 * Extra weight.
	 *
	 * @var float
	 */
	protected $extra_weight = 0;

	/**
	 * Declared value.
	 *
	 * @var string
	 */
	protected $declared_value = '';

	/**
	 * Declared value service code.
	 *
	 * @var string
	 */
	protected $declared_value_code = '';

	/**
	 * Own hands.
	 *
	 * @var bool
	 */
	protected $own_hands = false;

	/**
	 * Receipt notice.
	 *
	 * @var bool
	 */
	protected $receipt_notice = false;

	/**
	 * Package format.
	 *
	 * 1 – envelope
	 * 2 – package
	 * 3 - roll
	 *
	 * @var string
	 */
	protected $format = 2;

	/**
	 * Debug mode.
	 *
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * Maximum package weight (kg)
	 *
	 * @var float
	 */
	protected $maximum_weight = 30;

	/**
	 * Initialize webservice.
	 *
	 * @param string $id Method ID.
	 * @param int    $instance_id Instance ID.
	 */
	public function __construct( $id = 'correios', $instance_id = 0 ) {
		$this->id           = $id;
		$this->instance_id  = $instance_id;
	}

	/**
	 * Set the service
	 *
	 * @param string $service Service.
	 */
	public function set_service( $service ) {
		$this->service = wc_correios_sanitize_numberic( $service );
	}

	/**
	 * Set shipping package.
	 *
	 * @param array $package Shipping package.
	 */
	public function set_package( $package = array() ) {
		$this->package = $package;
	}

	/**
	 * Set the Weight (g) and dimensions (cm) based in the package.
	 *
	 * @return void
	 */
	public function prepare_package () {
		if ( ! $this->package ) {
			$this->log( 'No packages have been assigned', 'error' );
			return;
		}

		$correios_package = new WC_Correios_Package( $this->package );
		$data = $correios_package->get_data();

		$this->set_height( $data['height'] );
		$this->set_width( $data['width'] );
		$this->set_length( $data['length'] );
		$this->set_weight( $data['weight'] );

		if ( $this->debug ) {
			$log_data = array(
				'weight' => $this->get_weight(),
				'height' => $this->get_height(),
				'width'  => $this->get_width(),
				'length' => $this->get_length(),
			);
			$this->log( 'Weight (g) and dimensions (cm) of the package: ' . wp_json_encode( $log_data ) );
		}
	}

	/**
	 * Set origin postcode.
	 *
	 * @param string $postcode Origin postcode.
	 */
	public function set_origin_postcode( $postcode = '' ) {
		$this->origin_postcode = $postcode;
	}

	/**
	 * Set destination postcode.
	 *
	 * @param string $postcode Destination postcode.
	 */
	public function set_destination_postcode( $postcode = '' ) {
		$this->destination_postcode = $postcode;
	}

	/**
	 * Set shipping package height.
	 *
	 * @param float $height Package height.
	 */
	public function set_height( $height = 0 ) {
		$this->height = (float) $height;
	}

	/**
	 * Set shipping package width.
	 *
	 * @param float $width Package width.
	 */
	public function set_width( $width = 0 ) {
		$this->width = (float) $width;
	}

	/**
	 * Set shipping package diameter.
	 *
	 * @param float $diameter Package diameter.
	 */
	public function set_diameter( $diameter = 0 ) {
		$this->diameter = (float) $diameter;
	}

	/**
	 * Set shipping package length.
	 *
	 * @param float $length Package length.
	 */
	public function set_length( $length = 0 ) {
		$this->length = (float) $length;
	}

	/**
	 * Set shipping package weight (kg).
	 *
	 * @param float $weight Package weight.
	 */
	public function set_weight( $weight = 0 ) {
		$this->weight = (float) $weight;
	}

	/**
	 * Set minimum height.
	 *
	 * @param float $minimum_height Package minimum height.
	 */
	public function set_minimum_height( $minimum_height = 2 ) {
		$this->minimum_height = 2 <= $minimum_height ? $minimum_height : 2;
	}

	/**
	 * Set minimum width.
	 *
	 * @param float $minimum_width Package minimum width.
	 */
	public function set_minimum_width( $minimum_width = 11 ) {
		$this->minimum_width = 11 <= $minimum_width ? $minimum_width : 11;
	}

	/**
	 * Set minimum length.
	 *
	 * @param float $minimum_length Package minimum length.
	 */
	public function set_minimum_length( $minimum_length = 16 ) {
		$this->minimum_length = 16 <= $minimum_length ? $minimum_length : 16;
	}

	/**
	 * Set extra weight (kg).
	 *
	 * @param float $extra_weight Package extra weight.
	 */
	public function set_extra_weight( $extra_weight = 0 ) {
		$this->extra_weight = (float) $extra_weight;
	}

	/**
	 * Set declared value.
	 *
	 * @param float $declared_value Declared value.
	 */
	public function set_declared_value( $declared_value = 0 ) {
		$this->declared_value = $declared_value;
	}

	/**
	 * Set declared value service code.
	 *
	 * @param string $code
	 */
	public function set_declared_value_code( $code = '' ) {
		$this->declared_value_code = $code;
	}

	/**
	 * Set own hands.
	 *
	 * @param bool $own_hands
	 */
	public function set_own_hands( $own_hands ) {
		$this->own_hands = $own_hands;
	}

	/**
	 * Set receipt notice.
	 *
	 * @param bool $receipt_notice
	 */
	public function set_receipt_notice( $receipt_notice ) {
		$this->receipt_notice = $receipt_notice;
	}

	/**
	 * Set the debug mode.
	 *
	 * @param bool $debug
	 */
	public function set_debug( $debug = false ) {
		$this->debug = (bool) $debug;
	}

	/**
	 * Get webservice URL.
	 *
	 * @param string $endpoint
	 * @return string
	 */
	public function get_webservice_url( $endpoint = null ) {
		$webservice_url = $this->webservice_url;
		if ( defined( 'WC_CORREIOS_HOMOLOG_API' ) && \WC_CORREIOS_HOMOLOG_API ) {
			$webservice_url = 'https://apihom.correios.com.br/';
		}
		$webservice_url = apply_filters(
			'woocommerce_correios_webservice_url',
			$webservice_url,
			$this->id, $this->instance_id, $this->package
		);

		if ( ! $endpoint ) return $webservice_url;

		$endpoint_url = apply_filters(
			'woocommerce_correios_webservice_endpoint_url',
			rtrim( trim( $webservice_url ), '/' ) . '/' . trim( trim( $endpoint ), '/' ),
			$this->id, $this->instance_id, $this->package
		);

		return $endpoint_url;
	}

	/**
	 * Get origin postcode.
	 *
	 * @return string
	 */
	public function get_origin_postcode() {
		return wc_correios_sanitize_postcode(
			apply_filters( 'woocommerce_correios_origin_postcode', $this->origin_postcode, $this->id, $this->instance_id, $this->package )
		);
	}

	/**
	 * Get height (cm).
	 *
	 * @return float
	 */
	public function get_height() {
		return $this->height > $this->minimum_height ? $this->height : $this->minimum_height;
	}

	/**
	 * Get width  (cm).
	 *
	 * @return float
	 */
	public function get_width() {
		return $this->width > $this->minimum_width ? $this->width : $this->minimum_width;
	}

	/**
	 * Get diameter (cm).
	 *
	 * @return float
	 */
	public function get_diameter() {
		return $this->diameter;
	}

	/**
	 * Get length (cm).
	 *
	 * @return float
	 */
	public function get_length() {
		return $this->length > $this->minimum_length ? $this->length : $this->minimum_length;
	}

	/**
	 * Get weight (kg).
	 *
	 * @return float
	 */
	public function get_weight() {
		$weight = ( $this->weight + $this->extra_weight );
		return $weight > 0 ? $weight : 0.1;
	}

	/**
	 * Get package maximum weight (g).
	 *
	 * @return float
	 */
	public function get_maximum_weight () {
		return $this->maximum_weight;
	}

	/**
	 * Set package maximum weight (kg).
	 *
	 * @return float
	 */
	public function set_maximum_weight ( $value = 0) {
		$this->maximum_weight = (float) $value;
	}

	/**
	 * Check if is available.
	 *
	 * @return bool
	 */
	protected function is_available() {
		$origin_postcode = $this->get_origin_postcode();
		$package = $this->package;

		return ! empty( $this->service )
			|| ! empty( $this->destination_postcode )
			|| ! empty( $origin_postcode )
			|| 'BR' !== $package['destination']['country'];
	}

	/**
	 * Get the API credentials to generate authorization tokens
	 *
	 * @return array
	 */
	public function get_credentials () {
		return apply_filters(
			'woocommerce_correios_webservice_credentials',
			[
				'login' => '',
				'password' => '',
				'postage_card' => ''
			],
			$this->id,
			$this->instance_id
		);
	}

	/**
	 * get shipping estimated delivery time
	 *
	 * @return \WP_Error|null|array
	 */
	public function request_shipping_delivery_time() {
		// Checks if service and postcode are empty.
		if ( ! $this->is_available() ) {
			return null;
		}

		$token_data = $this->get_token();
		$token = null;
		if ( ! empty( $token_data ) ) {
			$token = $token_data['token'];
		}

		if ( ! $token ) {
			$this->log( 'Missing autorization token', 'error' );
			$this->log( 'Aborted.' );
			return null;
		}

		$this->log( 'Initiating request to calculate shipping delivery time...' );

		$args = apply_filters( 'woocommerce_correios_shipping_args', array(
			'cepOrigem' => wc_correios_sanitize_postcode( $this->get_origin_postcode() ),
			'cepDestino' => wc_correios_sanitize_postcode( $this->destination_postcode )
		), $this->id, $this->instance_id, $this->package );

		$args = apply_filters( 'woocommerce_correios_shipping_args', $args, $this->id, $this->instance_id, $this->package );

		$endpoint = '/prazo/v1/nacional/' . wc_correios_sanitize_numberic( $this->service );
		$request_args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept' => 'application/json'
			)
		);

		// Gets the WebService response.
		$response = $this->do_request(
			$endpoint,
			$args,
			$request_args
		);

		$result = null;

		if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
			$this->log( 'Response body: ' . wp_json_encode( $response['body'] ) );
			$result = $response['body'];
		}

		return $result;
	}

	/**
	 * Get shipping cost
	 *
	 * @return \WP_Error|null|array
	 */
	public function request_shipping_cost() {
		// Checks if service and postcode are empty.
		if ( ! $this->is_available() ) {
			return null;
		}

		$token_data = $this->get_token();
		$token = null;
		if ( ! empty( $token_data ) ) {
			$token = $token_data['token'];
		}

		if ( ! $token ) {
			$this->log( 'Missing autorization token', 'error' );
			$this->log( 'Aborted.' );
			return null;
		}

		$this->log( 'Initiating request to calculate shipping cost...' );

		$this->prepare_package();

		$max_weight = $this->get_maximum_weight();
		if ( $this->get_weight() > $max_weight ) {
			$this->log( 'The package weight exceeds the limit accepted of ' . round( $max_weight, 2 ) . 'kg', 'error' );

			$error_message = __( 'The weight of your order exceeds the limit accepted by the carrier nationwide', 'correios-for-woocommerce' );

			return new \WP_Error( 'correios-error', $error_message );
		}

		$args = array(
			'cepOrigem' => wc_correios_sanitize_postcode( $this->get_origin_postcode() ),
			'cepDestino' => wc_correios_sanitize_postcode( $this->destination_postcode ),
			'psObjeto' => round( $this->get_weight() * 1000, 1 ),
			'tpObjeto' => 2,
			'comprimento' => $this->get_length(),
			'largura' => $this->get_width(),
			'altura' => $this->get_height(),
			'servicosAdicionais' => [],
		);

		if ( ! empty( $token_data['cartaoPostagem']['contrato'] ) ) {
			$args['nuContrato'] = wc_correios_sanitize_numberic(
				$token_data['cartaoPostagem']['contrato']
			);
		}

		if ( ! empty( $token_data['cartaoPostagem']['dr'] ) ) {
			$args['nuDR'] = wc_correios_sanitize_numberic(
				$token_data['cartaoPostagem']['dr']
			);
		}

		if ( $this->receipt_notice ) {
			$args['servicosAdicionais'][] = '001';
		}

		if ( $this->own_hands ) {
			$args['servicosAdicionais'][] = '002';
		}

		if ( $this->declared_value && $this->declared_value_code ) {
			$args['servicosAdicionais'][] = $this->declared_value_code;
			$args['vlDeclarado'] = $this->declared_value;
		}

		$args = apply_filters( 'woocommerce_correios_shipping_args', $args, $this->id, $this->instance_id, $this->package );

		$endpoint = '/preco/v1/nacional/' . wc_correios_sanitize_numberic( $this->service );
		$request_args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept' => 'application/json'
			)
		);

		// Gets the WebService response.
		$response = $this->do_request(
			$endpoint,
			$args,
			$request_args
		);

		$result = null;

		if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
			$this->log( 'Response body: ' . wp_json_encode( $response['body'] ) );
			$result = $response['body'];
		}

		return $result;
	}

	/**
	 * Get an address
	 *
	 * @param string $postcode
	 * @return \WP_Error|null|array
	 */
	public function request_address( $postcode ) {
		$token_data = $this->get_token();
		$token = null;
		if ( ! empty( $token_data ) ) {
			$token = $token_data['token'];
		}
		if ( ! $token ) {
			$this->log( 'Missing autorization token', 'error' );
			$this->log( 'Aborted.' );
			return null;
		}

		$this->log( 'Initiating request to get an address...' );

		$address_postcode = wc_correios_sanitize_postcode( $postcode );

		if ( ! $address_postcode ) {
			$this->log( 'Postcode "' . $postcode . '" is invalid', 'error' );
			$error_message = __( 'Invalid zip code', 'correios-for-woocommerce' );
			return new \WP_Error( 'correios-error', $error_message );
		}

		$endpoint = '/cep/v1/enderecos/' . $address_postcode;
		$request_args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept' => 'application/json'
			)
		);

		// Gets the WebService response.
		$response = $this->do_request( $endpoint, null, $request_args );
		$result = null;

		if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
			$result = $response['body'];
			$this->log( 'Response body: ' . wp_json_encode( $response['body'] ) );
		}

		return $result;
	}

	/**
	 * Get tracking history.
	 *
	 * @param string[] $object_codes
	 * @return \WP_Error|null|array
	 */
	public function request_tracking_history( $tracking_codes, $search_type = 'T' ) {
		$token_data = $this->get_token();
		$token = null;
		if ( ! empty( $token_data ) ) {
			$token = $token_data['token'];
		}
		if ( ! $token ) {
			$this->log( 'Missing autorization token', 'error' );
			$this->log( 'Aborted.' );
			return null;
		}

		$this->log( 'Initiating request to get an tracking history...' );

		if ( ! in_array( $search_type, array( 'T', 'U', 'P' ) ) ) {
			$search_type = 'T';
		}

		$endpoint = '/srorastro/v1/objetos';
		$args = [
			'codigosObjetos' => array_map( 'strval', $tracking_codes ),
			'resultado' => $search_type,
		];
		$request_args = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept' => 'application/json'
			)
		);

		// Gets the WebService response.
		$response = $this->do_request( $endpoint, $args, $request_args );
		$result = null;

		if ( $response['status_code'] >= 200 && $response['status_code'] < 300 ) {
			$result = $response['body'];
			$this->log( 'Response body: ' . wp_json_encode( $response['body'] ) );
		}

		return $result;
	}

	/**
	 * Request a Auth Token
	 *
	 * @return array|false
	 */
	public function request_token () {
		$endpoint = '/token/v1/autentica/cartaopostagem';
		$credentials = $this->get_credentials();
		$login = $credentials['login'];
		$password = $credentials['password'];
		$request_args = array(
			'method' => 'POST',
			'body' => json_encode( [
				"numero" => $credentials['postage_card']
			] ),
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $login . ':' . $password ),
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			)
		);

		$response = $this->do_request(
			$endpoint,
			null,
			$request_args
		);

		return $response;
	}

	/**
	 * @param boolean $ignore_cache
	 * @return array|null
	 */
	public function get_token ( $ignore_cache = false ) {
		$token = null;

		if ( ! $ignore_cache ) {
			$token = wc_correios_get_token();
			if ( is_array( $token ) ) {
				$this->log( 'Retrieving authorization token from database' );
				return $token;
			} else {
				$this->log( 'Not found authorization token in database or maybe it is expired', 'warning' );
			}
		}

		$this->log( 'Initiating request to get authorization token by Correios webservice...' );
		$response = $this->request_token();
		if ( is_null( $response ) ) {
			return null;
		}

		$status = $response['status_code'];
		$token_data  = $response['body'];

		if ( $status >= 200 && $status < 300 ) {
			wc_correios_update_token( $token_data );
			$this->log( 'Authorization token saved in database' );
			return $token_data;
		} else {
			wc_correios_delete_token();
			$this->log( 'Authorization token deleted from database due to request error', 'warning' );
			if ( $status < 500 ) {
				return [];
			} else {
				$this->log( 'Correios webservice returned an unexpected response code: ' . $status, 'error' );
			}
		}

		return null;
	}

	/**
	 * Process an HTTP request
	 *
	 * @param string $url
	 * @param array $query_args
	 * @param array $request_args
	 * @return null|array
	 */
	protected function do_request ( $endpoint, $query_args = [], $request_args = [] ) {
		$url = $this->get_webservice_url( $endpoint );
		$request_url = $query_args ? add_query_arg( $query_args, $url ) : $url;
		$request_args = wp_parse_args( $request_args, [
			'method' => 'GET',
			'timeout' => apply_filters( 'woocommerce_correios_request_timeout', 60 )
		] );

		$this->log( "Requesting: {$request_args['method']} $request_url" );

		$request = wp_remote_request(
			esc_url_raw( $request_url ),
			$request_args
		);

		if ( is_wp_error( $request ) ) {
			$this->log( 'WP_Error: ' . $request->get_error_message(), 'error' );
			return null;
		}

		$raw_body = $request['body'];
		$json_body = json_decode( $raw_body, true );

		$response = [
			'status_code' => $request['response']['code'],
			'body' => is_array( $json_body ) ? $json_body : []
		];

		$this->log( 'Response status: ' . wp_json_encode( $request['response'] ) );

		if ( $response['status_code'] >= 300 ) {
			$this->log( 'Response body: ' . $raw_body );
		}

		return $response;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $message
	 * @param string $level
	 * @return void
	 */
	public function log ( $message, $level = 'info' ) {
		if ( $this->debug ) {
			$logger = wc_get_logger();
			$logger->log( $level, $message, array(
				'source'  => $this->id,
			) );
		}
	}
}
