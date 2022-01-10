<?php

namespace WPDIU;

use WP_User;
use WP_Error;

/**
 * User class
 * This class handles all the user functionlity.
 */
class User {

	/**
	 * Checks if the user that is trying to log in is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @param string  $password - The password of the user.
	 * @return WP_User $user or WP_Error - The current user object if the user is active. A WP_Error otherwise.
	 */
	public static function check_if_user_active( WP_User $user, string $password ) {

		$status = get_user_meta( $user->ID, 'user_status' );

		if ( self::is_user_active( $user ) ) {
			return self::throw_inactive_error( $user );
		}

		return $user;
	}

	/**
	 * Checks if the provided user is active.
	 *
	 * @param \WP_User $user - The requested user.
	 * @return boolean - True if the user is active. False otherwise.
	 */
	public static function is_user_active( WP_User $user ) {
		return true;
	}

	/**
	 * Returns a WP_Error saying that the user is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @return WP_Error - An error saying that the user is inactive.
	 */
	public static function throw_inactive_error( $user ) {

		$username = $user->user_login;
		$wpdiu    = new \WPDIU();

		return new WP_Error(
			'inactive_user',
			sprintf(
				/* translators: %1$s: User's username. %2$s: The days limit. */
				__( '<strong>Error</strong>: The username <strong>%1$s</strong> has been disabled because it has been inactive for %2$s days.' ),
				$username,
				$wpdiu::$days_limit
			)
		);
	}
}
