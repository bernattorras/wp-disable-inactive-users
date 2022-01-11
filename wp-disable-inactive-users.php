<?php
/**
 * Plugin Name:     Wp Disable Inactive Users
 * Plugin URI:      https://github.com/bernattorras/wp-disable-inactive-users
 * Description:     A WordPress plugin that disables inactive users automatically.
 * Author:          Bernat Torras
 * Text Domain:     wp-disable-inactive-users
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Disable_Inactive_Users
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPDIU class
 */
class WPDIU {

	/**
	 * Contains errors and messages as admin notices.
	 *
	 * @var array
	 */
	public static $days_limit = 90;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Overwrite the default $days_limit with the provided number if it is changed using the 'wpdiu_days_limit' filter.
		self::$days_limit = apply_filters( 'wpdiu_days_limit', self::$days_limit );
	}

	/**
	 * WPDIU Init
	 */
	public function init() {

		// Composer autoload.
		require_once __DIR__ . '/vendor/autoload.php';

		// Validate the user to check if it's active.
		add_action( 'wp_authenticate_user', [ 'WPDIU\User', 'check_if_user_active' ], 10, 2 );

		register_activation_hook( __FILE__, [ $this, 'wpdiu_activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'wpdiu_deactivate' ] );

	}


	/**
	 * Activation functionality
	 * - Sets the 'wpdiu_activation' option to the current time() when the plugin gets activated (if it doesn't exist already).
	 *
	 * @return void
	 */
	public function wpdiu_activate() {
		if ( false === get_option( 'wpdiu_activation' ) ) {
			add_option( 'wpdiu_activation', time() );
		}
	}

	/**
	 * Deactivation functionality
	 * - Deletes the 'wpdiu_activation' option.
	 *
	 * @return void
	 */
	public function wpdiu_deactivate() {
		if ( false !== get_option( 'wpdiu_activation' ) ) {
			delete_option( 'wpdiu_activation' );
		}
	}

}
$wpdiu = new WPDIU();
$wpdiu->init();
