<?php
/**
 * Correios integration.
 *
 * @package WooCommerce_Correios/Classes/Integration
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Correios integration class.
 */
class WC_Correios_Integration extends WC_Integration {

	/**
	 * @var string
	 */
	public $api_login = null;

	/**
	 * @var string
	 */
	public $api_password = null;

	/**
	 * @var string
	 */
	public $api_postage_card = null;

	/**
	 * @var string
	 */
	public $api_debug = false;

	/**
	 * @var string
	 */
	public $tracking_enable = null;

	/**
	 * @var string
	 */
	public $tracking_login = null;

	/**
	 * @var string
	 */
	public $tracking_password = null;

	/**
	 * @var string
	 */
	public $tracking_debug = null;

	/**
	 * @var string
	 */
	public $autofill_enable = null;

	/**
	 * @var string
	 */
	public $autofill_validity = null;

	/**
	 * @var string
	 */
	public $autofill_force = null;

	/**
	 * @var string
	 */
	public $autofill_empty_database = null;

	/**
	 * @var string
	 */
	public $autofill_debug = null;

	/**
	 * Initialize integration actions.
	 */
	public function __construct() {
		$this->id           = 'correios-integration';
		$this->method_title = __( 'Correios', 'correios-for-woocommerce' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->api_debug                  = $this->get_option( 'api_debug' );
		// $this->tracking_enable            = $this->get_option( 'tracking_enable' );
		// $this->tracking_debug             = $this->get_option( 'tracking_debug' );
		$this->autofill_enable            = $this->get_option( 'autofill_enable' );
		$this->autofill_validity          = $this->get_option( 'autofill_validity' );
		$this->autofill_force             = $this->get_option( 'autofill_force' );
		$this->autofill_empty_database    = $this->get_option( 'autofill_empty_database' );
		$this->autofill_debug             = $this->get_option( 'autofill_debug' );

		// Admin Settings actions.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

		// API Credentials actions.
		add_filter( 'woocommerce_correios_webservice_credentials', array( $this, 'get_credentials' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WC_Correios::get_main_file() ), array( $this, 'add_plugin_action_links' ) );
		if ( 'correios-integration' !== wc_get_var( $_REQUEST['section'] ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_credentials' ), 5 );
		}

		// Tracking history actions.
		add_filter( 'woocommerce_correios_enable_tracking_history', array( $this, 'setup_tracking_history' ), 10 );
		add_filter( 'woocommerce_correios_tracking_user_data', array( $this, 'setup_tracking_user_data' ), 10 );
		add_filter( 'woocommerce_correios_enable_tracking_debug', array( $this, 'setup_tracking_debug' ), 10 );

		// Autofill address actions.
		add_filter( 'woocommerce_correios_enable_autofill_addresses', array( $this, 'setup_autofill_addresses' ), 10 );
		add_filter( 'woocommerce_correios_enable_autofill_addresses_debug', array( $this, 'setup_autofill_addresses_debug' ), 10 );
		add_filter( 'woocommerce_correios_autofill_addresses_validity_time', array( $this, 'setup_autofill_addresses_validity_time' ), 10 );
		add_filter( 'woocommerce_correios_autofill_addresses_force_autofill', array( $this, 'setup_autofill_addresses_force_autofill' ), 10 );
		add_action( 'wp_ajax_correios_autofill_addresses_empty_database', array( $this, 'ajax_empty_database' ) );
	}

	/**
	 * Add settings URL to the plugin action links
	 *
	 * @param array $actions
	 * @return array
	 */
	public function add_plugin_action_links ( $actions ) {
		$settings_url = $this->get_settings_link();
		return array_merge(
			array( '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'correios-for-woocommerce' ) .  "</a>" ),
			$actions
		);
	}

	protected function get_settings_link () {
		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=' . $this->id );
	}

	/**
	 * Get the Correios API credentials
	 *
	 * @param array $credentials
	 * @return array
	 */
	public function get_credentials ( $credentials = [] ) {
		$credentials = [
			'login' => $this->get_option( 'api_login' ),
			'password' => $this->get_option( 'api_password' ),
			'postage_card' => $this->get_option( 'api_postage_card' ),
		];
		return $credentials;
	}

	/**
	 * Get tracking log url.
	 *
	 * @return string
	 */
	protected function get_tracking_log_link() {
		return ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=correios-tracking-history-' . sanitize_file_name( wp_hash( 'correios-tracking-history' ) ) . '.log' ) ) . '">' . __( 'View logs.', 'correios-for-woocommerce' ) . '</a>';
	}

	/**
	 * Get API credentails log url.
	 *
	 * @return string
	 */
	protected function get_api_log_link() {
		return ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=correios-token-' . sanitize_file_name( wp_hash( 'correios-token' ) ) . '.log' ) ) . '">' . __( 'View logs.', 'correios-for-woocommerce' ) . '</a>';
	}

	/**
	 * Initialize integration settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'api'     => array(
				'title'       => __( 'API Credentials', 'correios-for-woocommerce' ),
				'type'        => 'title',
				'description' => sprintf(
					__( 'It is necessary to have a contract with Correios to have API credentials. Visit %s to learn more.', 'correios-for-woocommerce' ),
					'<a href="https://www.correios.com.br/correios-facil/correios-facil" target="_blank" rel="noopener">https://www.correios.com.br/correios-facil/correios-facil</a>'
				)
			),
			'api_login'          => array(
				'title'       => __( 'API Username', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Usually your CNPJ or CPF', 'correios-for-woocommerce' ),
			),
			'api_password'       => array(
				'title'       => __( 'API Password', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'description'     => sprintf(
					__( 'A 40-character password that is generated on %s', 'correios-for-woocommerce' ),
					'<a href="https://cws.correios.com.br/acesso-componentes" target="_blank" rel="noopener">https://cws.correios.com.br/acesso-componentes</a>'
				)
			),
			'api_postage_card'       => array(
				'title'       => __( 'Postage Card Number', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'A postage card is required to use the Correios API services', 'correios-for-woocommerce' )
			),
			'api_debug'          => array(
				'title'       => __( 'Debug Log', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging for API Credentials', 'correios-for-woocommerce' ),
				'default'     => 'no',
				/* translators: %s: log link */
				'description' => sprintf( __( 'Log %s events, such as WebServices requests.', 'correios-for-woocommerce' ), __( 'API Credentials', 'correios-for-woocommerce' ) ) . $this->get_api_log_link(),
			),

			'tracking'                => array(
				'title'       => __( 'Tracking History Table', 'correios-for-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Displays a table with informations about the shipping in My Account > View Order page. Required username and password that can be obtained with the Correios\' commercial area.', 'correios-for-woocommerce' ),
			),
			'tracking_enable'         => array(
				'title'   => __( 'Enable/Disable', 'correios-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Tracking History Table', 'correios-for-woocommerce' ),
				'default' => 'no',
			),
			'tracking_debug'          => array(
				'title'       => __( 'Debug Log', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging for Tracking History', 'correios-for-woocommerce' ),
				'default'     => 'no',
				/* translators: %s: log link */
				'description' => sprintf( __( 'Log %s events, such as WebServices requests.', 'correios-for-woocommerce' ), __( 'Tracking History Table', 'correios-for-woocommerce' ) ) . $this->get_tracking_log_link(),
			),

			'autofill_addresses'      => array(
				'title'       => __( 'Autofill Addresses', 'correios-for-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Displays a table with informations about the shipping in My Account > View Order page.', 'correios-for-woocommerce' ),
			),
			'autofill_enable'         => array(
				'title'   => __( 'Enable/Disable', 'correios-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Autofill Addresses', 'correios-for-woocommerce' ),
				'default' => 'yes',
			),
			'autofill_validity'       => array(
				'title'       => __( 'Postcodes Validity', 'correios-for-woocommerce' ),
				'type'        => 'select',
				'default'     => 'forever',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Defines how long a postcode will stay saved in the database before a new query.', 'correios-for-woocommerce' ),
				'options'     => array(
					'1'       => __( '1 month', 'correios-for-woocommerce' ),
					/* translators: %s number of months */
					'3'       => sprintf( __( '%d months', 'correios-for-woocommerce' ), 3 ),
					/* translators: %s number of months */
					'6'       => sprintf( __( '%d months', 'correios-for-woocommerce' ), 6 ),
					/* translators: %s number of months */
					'12'      => sprintf( __( '%d months', 'correios-for-woocommerce' ), 12 ),
					'forever' => __( 'Forever', 'correios-for-woocommerce' ),
				),
			),
			'autofill_force'          => array(
				'title'       => __( 'Force Autofill', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Force Autofill', 'correios-for-woocommerce' ),
				'description' => __( 'When enabled will autofill all addresses after the user finish to fill the postcode, even if the addresses are already filled.', 'correios-for-woocommerce' ),
				'default'     => 'no',
			),
			'autofill_empty_database' => array(
				'title'       => __( 'Empty Database', 'correios-for-woocommerce' ),
				'type'        => 'button',
				'label'       => __( 'Empty Database', 'correios-for-woocommerce' ),
				'description' => __( 'Delete all the saved postcodes in the database, use this option if you have issues with outdated postcodes.', 'correios-for-woocommerce' ),
			),
			'autofill_debug'          => array(
				'title'       => __( 'Debug Log', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging for Autofill Addresses', 'correios-for-woocommerce' ),
				/* translators: %s: log link */
				'description' => sprintf( __( 'Log %s events, such as WebServices requests.', 'correios-for-woocommerce' ), __( 'Autofill Addresses', 'correios-for-woocommerce' ) ) . $this->get_tracking_log_link(),
				'default'     => 'no',
			),
		);
	}

	public function check_api_credentials () {
		$api = new WC_Correios_Webservice( 'correios-token' );

		$api->set_debug( $this->api_debug );
		$token = $api->get_token( true );

		if ( null === $token ) {
			wc_correios_update_is_token_validated( false );
			include dirname( \WC_CORREIOS_PLUGIN_FILE ) . '/includes/admin/views/html-admin-error-credentials.php';
		} if ( ! empty( $token['token'] ) ) {
			wc_correios_update_is_token_validated( true );
			include dirname( \WC_CORREIOS_PLUGIN_FILE ) . '/includes/admin/views/html-admin-valid-credentials.php';
		} else {
			wc_correios_update_is_token_validated( false );
			$message = __( 'Erro ao verificar suas credenciais:  Confira seu usuário, sua senha e o número do seu cartão de postagem.', 'correios-for-woocommerce' );
			include dirname( \WC_CORREIOS_PLUGIN_FILE ) . '/includes/admin/views/html-admin-invalid-credentials.php';
		}
	}

	/**
	 * Correios options page.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->check_api_credentials();
		}

		echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';
		echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>'; // WPCS: XSS ok.

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( $this->id . '-admin', plugins_url( 'assets/js/admin/integration' . $suffix . '.js', WC_Correios::get_main_file() ), array( 'jquery', 'jquery-blockui' ), WC_CORREIOS_VERSION, true );
		wp_localize_script(
			$this->id . '-admin',
			'WCCorreiosIntegrationAdminParams',
			array(
				'i18n_confirm_message' => __( 'Are you sure you want to delete all postcodes from the database?', 'correios-for-woocommerce' ),
				'empty_database_nonce' => wp_create_nonce( 'woocommerce_correios_autofill_addresses_nonce' ),
			)
		);
	}

	/**
	 * Generate Button Input HTML.
	 *
	 * @param string $key  Input key.
	 * @param array  $data Input data.
	 * @return string
	 */
	public function generate_button_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'       => '',
			'label'       => '',
			'desc_tip'    => false,
			'description' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<button class="button-secondary" type="button" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['label'] ); ?></button>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Enable tracking history.
	 *
	 * @return bool
	 */
	public function setup_tracking_history() {
		return 'yes' === $this->tracking_enable;
	}

	/**
	 * Setup tracking user data.
	 *
	 * @param array $user_data User data.
	 * @return array
	 */
	public function setup_tracking_user_data( $user_data ) {
		if ( $this->tracking_login && $this->tracking_password ) {
			$user_data = array(
				'login'    => $this->tracking_login,
				'password' => $this->tracking_password,
			);
		}

		return $user_data;
	}

	/**
	 * Set up tracking debug.
	 *
	 * @return bool
	 */
	public function setup_tracking_debug() {
		return 'yes' === $this->tracking_debug;
	}

	/**
	 * Enable autofill addresses.
	 *
	 * @return bool
	 */
	public function setup_autofill_addresses() {
		return 'yes' === $this->autofill_enable;
	}

	/**
	 * Set up autofill addresses debug.
	 *
	 * @return bool
	 */
	public function setup_autofill_addresses_debug() {
		return 'yes' === $this->autofill_debug;
	}

	/**
	 * Set up autofill addresses validity time.
	 *
	 * @return string
	 */
	public function setup_autofill_addresses_validity_time() {
		return $this->autofill_validity;
	}

	/**
	 * Set up autofill addresses force autofill.
	 *
	 * @return string
	 */
	public function setup_autofill_addresses_force_autofill() {
		return $this->autofill_force;
	}

	/**
	 * Ajax empty database.
	 */
	public function ajax_empty_database() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) ) { // WPCS: input var okay, CSRF ok.
			wp_send_json_error( array( 'message' => __( 'Missing parameters!', 'correios-for-woocommerce' ) ) );
			exit;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'woocommerce_correios_autofill_addresses_nonce' ) ) { // WPCS: input var okay, CSRF ok.
			wp_send_json_error( array( 'message' => __( 'Invalid nonce!', 'correios-for-woocommerce' ) ) );
			exit;
		}

		$table_name = $wpdb->prefix . WC_Correios_Autofill_Addresses::$table;
		$wpdb->query( "DROP TABLE IF EXISTS $table_name;" ); // @codingStandardsIgnoreLine

		WC_Correios_Autofill_Addresses::create_database();

		wp_send_json_success( array( 'message' => __( 'Postcode database emptied successfully!', 'correios-for-woocommerce' ) ) );
	}

	/**
	 * @return void
	 */
	public function notice_missing_credentials () {
		if ( ! current_user_can( 'administrator' ) ) return;
		if ( wc_correios_get_is_token_validated() ) return;

		$settings_url = $this->get_settings_link();
		$settings_label = esc_html__( 'Settings', 'correios-for-woocommerce' );

		$message = sprintf(
			'You haven\'t configured the %s plugin yet. The new Correios API requires you to have a contract. Please, visit the %s page and enter your API access credentials.',
			'<strong>' . esc_html__( 'Correios for WooCommerce', 'correios-for-woocommerce' ) . '</strong>',
			'<a href="' . esc_url( $settings_url ) . '">' . $settings_label .  "</a>"
		);
		include WC_Correios::get_plugin_path() . 'includes/admin/views/html-admin-notice.php';
	}
}
