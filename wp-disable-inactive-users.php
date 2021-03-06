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
	 * @var string
	 */
	public static $activation_date;

	/**
	 * The plugin basename.
	 *
	 * @var string
	 */
	public static $plugin_basename;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Overwrite the default $days_limit with the provided number if it is changed using the 'wpdiu_days_limit' filter.
		self::$days_limit = apply_filters( 'wpdiu_days_limit', self::$days_limit );

		self::$plugin_basename = plugin_basename( __FILE__ );
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

		// Validate the user before they log in to check if it should still be active.
		add_action( 'wp_authenticate_user', [ 'WPDIU\User', 'check_if_user_active' ], 10, 2 );

		// Update the last login meta when the user logs in.
		add_action( 'wp_login', [ 'WPDIU\User', 'update_last_login' ], 10, 2 );

		// Listen for scheduled notifications.
		$notification = new WPDIU\Notification();
		add_action( 'init', [ $notification, 'add_listeners' ] );

		// Plugin activation and deactivation functionality.
		register_activation_hook( __FILE__, [ $this, 'wpdiu_activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'wpdiu_deactivate' ] );

		// Initialize the plugin props.
		$this->set_plugin_props();
	}


	/**
	 * Initialize the plugin props with the settings values.
	 *
	 * @return void
	 */
	public function set_plugin_props() {
		$settings = \WPDIU\Settings::get_settings();
		if ( isset( $settings['days_limit'] ) ) {
			self::$days_limit = $settings['days_limit'];
		}
		if ( isset( $settings['activation_date'] ) ) {
			self::$activation_date = $settings['activation_date'];
		}
		\WPDIU\User::$disabled_users = get_option( 'wpdiu_disabled_users', array() );
	}

	/**
	 * Activation functionality
	 * - Sets the 'wpdiu_activation' option to the current time() when the plugin gets activated (if it doesn't exist already).
	 *
	 * @return void
	 */
	public function wpdiu_activate() {
		if ( false === self::$activation_date ) {
			$current_time               = current_time( 'Y-m-d' );
			$options                    = \WPDIU\Settings::get_settings();
			$options['activation_date'] = $current_time;
			update_option( 'wpdiu_settings', $current_time );
		}
	}

	/**
	 * Deactivation functionality
	 * - Unschedules all the plugin events (the ones that start with 'wpdiu_').
	 *
	 * @return void
	 */
	public function wpdiu_deactivate() {
		// Unschedule the events that start with 'wpdiu_'.
		\WPDIU\Event::unschedule_all_wpdiu_events();
	}

}

$wpdiu = new WPDIU();
$wpdiu->init();
