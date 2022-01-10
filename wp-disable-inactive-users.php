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

/**
 * TODO:
 * - Guardar la data actual com a user_meta al fer login (last_login)
 * - Revisar la data al fer login. Retornar error si fa mes de $90 dies de l'ultim login.
 * -
 * EXTRA FEATURES
 * - Opció per no desactivar admins
 * - Opció per no desactivar usuaris seleccionats (user meta o selector d'usuaris?)
 * - Llistat d'usuaris deshabilitats
 * - Enviar un email a l'admin quan es desactivi un usuari
 * - Enviar un email recordatori X dies abans de desactivar un usuari
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
