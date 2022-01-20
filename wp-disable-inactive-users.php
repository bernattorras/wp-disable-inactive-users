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
	 * Plugin activation date
	 *
	 * @var [time]
	 */
	public static $activation_date;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Overwrite the default $days_limit with the provided number if it is changed using the 'wpdiu_days_limit' filter.
		self::$days_limit = apply_filters( 'wpdiu_days_limit', self::$days_limit );

		// Overwrite the default $days_limit with the provided number if it is changed using the 'wpdiu_days_limit' filter.
		self::$activation_date = apply_filters( 'wpdiu_activation_date', get_option( 'wpdiu_activation' ) );
	}

	/**
	 * WPDIU Init
	 */
	public function init() {

		// Composer autoload.
		require_once __DIR__ . '/vendor/autoload.php';

		if ( is_admin() ) {
			$settings = new WPDIU\Settings();
		}

		add_action( 'init', [ 'WPDIU\User', 'init' ], 10, 2 );

		// Validate the user to check if it's active.
		add_action( 'wp_authenticate_user', [ 'WPDIU\User', 'check_if_user_active' ], 10, 2 );

		// Update the last login meta when the user logs in.
		add_action( 'wp_login', [ 'WPDIU\User', 'update_last_login' ], 10, 2 );

		// Listen for scheduled notifications.
		$notification = new WPDIU\Notification();
		add_action( 'init', [ $notification, 'add_listeners' ] );

		// Plugin activation and deactivation functionality.
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
			$current_time = current_time( 'mysql' );
			add_option( 'wpdiu_activation', $current_time );
		}
	}

	/**
	 * Deactivation functionality
	 * - Deletes the 'wpdiu_activation' option.
	 * - Unschedules the recurring 'wpdiu_disable_users_automatically' event.
	 *
	 * @return void
	 */
	public function wpdiu_deactivate() {

		// Delete the 'wpdiu_activation' option.
		if ( false !== get_option( 'wpdiu_activation' ) ) {
			delete_option( 'wpdiu_activation' );
		}

		// Unschedule the recurring 'wpdiu_disable_users_automatically' event.
		\WPDIU\Event::unschedule( 'wpdiu_disable_users_automatically' );

		// TODO: Unchedule single disabled notification hooks.
		// - get the 'cron' option.
		// - filter the array to get only the hooks that start with '_wpdiu'.
		// - Get their args and unschedule them.
	}

}
$wpdiu = new WPDIU();
$wpdiu->init();
