<?php
/**
 * Plugin Name: Be API - Recovery Mode Email
 * Description: Give the possibility to choose another recipient for WordPress recovery emails (WP 5.2 +)
 * Version: 0.1
 * Author: BE API Technical team
 * Author URI: https://www.beapi.fr
 * Text domain: recovery-mode-email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * nothing after this happens if version is not equal or higher
 *
 */
if ( ! version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) ) {
	return;
}

/**
 * Recovery_Mode_Email plugin class.
 */
class Recovery_Mode_Email {

	/**
	 * Plugin instance.
	 *
	 * @var
	 */
	public static $instance = null;

	protected $default_email = null;

	/**
	 * Plugin instance creator.
	 *
	 * @return Recovery_Mode_Email
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Register plugin's hooks
	 */
	public function hooks() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_init', [ $this, 'declare_error_email_setting' ] );
		add_filter( 'recovery_mode_email', [ $this, 'bypass_error_recipient' ] );
	}

	/**
	 * Load Text domain
	 *
	 * @author Romain DORR
	 *
	 */
	public function init() {
		load_muplugin_textdomain( 'recovery-mode-email', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( defined( 'RECOVERY_MODE_EMAIL' ) && is_email( constant( 'RECOVERY_MODE_EMAIL' ) ) ) {
			$this->default_email = RECOVERY_MODE_EMAIL;
		}
	}

	/**
	 * Declare setting
	 *
	 * @return void
	 * @author François de Cambourg
	 */
	public function declare_error_email_setting() {
		register_setting(
			'general',
			'beapi_error_email_options',
			[ $this, 'validate_options' ]
		);

		add_settings_field(
			'beapi_error_email_handler_field',
			esc_html__( 'WordPress Fatal Error email handler', 'recovery-mode-email' ),
			[ $this, 'error_email_setting_input' ],
			'general',
			'default'
		);
	}

	/**
	 * Echo new field markup, callback param 3 of add_settings_field below
	 *
	 * @return void
	 * @see beapi_declare_error_email_setting()
	 *
	 * @author François de Cambourg
	 *
	 */
	public function error_email_setting_input() {
		$error_email_array = get_option( 'beapi_error_email_options' );
		if ( ! empty( $error_email_array ) ) {
			return;
		}

		$error_email = $error_email_array['error_handling_email'];
		if ( empty( $error_email ) || ! is_email( $error_email ) ) {
			return;
		}

		?>
		<input id="error_handling_email" name="beapi_error_email_options[error_handling_email]" type="email"
			   value="<?php echo esc_attr( $error_email ); ?>"/>
		<?php esc_html_e( 'Fatal Error emails goes to this adress instead of default administrator roles.', 'recovery-mode-email' );
	}

	/**
	 * Validate email address
	 *
	 * @param array $input
	 *
	 * @return array
	 *
	 * @see https://kellenmace.com/wordpress-hook-options-page-save/
	 *
	 * @author François de Cambourg
	 */
	public function validate_options( array $input ) {
		$valid                         = array();
		$valid['error_handling_email'] = sanitize_email( $input['error_handling_email'] );

		// Notice if address is not good
		if ( $valid['error_handling_email'] !== $input['error_handling_email'] ) {
			add_settings_error(
				'beapi_error_handling_email',
				'beapi_texterror',
				esc_html_e( 'Invalid email', 'recovery-mode-email' ),
				'error'
			);
		}

		return $valid;
	}

	/**
	 * Bypass email address for recovery_mode_email core hook
	 *
	 * @param array $email
	 *
	 * @return array
	 *
	 * @author François de Cambourg
	 */
	public function bypass_error_recipient( $email ) {
		$error_email_array = get_option( 'beapi_error_email_options' );
		if ( empty( $error_email_array ) || ! is_array( $error_email_array ) ) {
			return $email;
		}

		$error_email = $error_email_array['error_handling_email'];
		if ( empty( $error_email ) || ! is_email( $error_email ) ) {
			return $email;
		}

		if ( ! $error_email ) {
			return $email;
		}

		$email['to'] = $error_email;

		return $email;
	}
}

Recovery_Mode_Email::instance();
