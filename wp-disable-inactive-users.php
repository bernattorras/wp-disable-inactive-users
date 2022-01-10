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
	 * WPDIU Init
	 */
	public static function init() {

		// Composer autoload.
		require_once __DIR__ . '/vendor/autoload.php';

		// Validate the user to check if it's active.
		add_action( 'wp_authenticate_user', [ 'WPDIU\User', 'check_if_user_active' ], 10, 2 );
	}

}

WPDIU::init();
