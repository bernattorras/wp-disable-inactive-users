<?php
/**
 * A class to handle User and login functionality.
 *
 * @package WordPress
 */

namespace WPDIU;

use WP_User;
use WP_Error;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * User class
 * This class handles all the user functionlity.
 */
class User {

	/**
	 * Adds/updates the 'last_login' user meta with the current time when the user logs in.
	 *
	 * @param string  $user_login - The user login name.
	 * @param WP_User $user - The user object.
	 * @return void
	 */
	public static function update_last_login( string $user_login, WP_User $user ) {
		update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
	}

	/**
	 * Checks if the user that is trying to log in is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @param string  $password - The password of the user.
	 * @return WP_User $user or WP_Error - The current user object if the user is active. A WP_Error otherwise.
	 */
	public static function check_if_user_active( WP_User $user, string $password ) {

		$status = get_user_meta( $user->ID, 'user_status' );

		if ( ! self::is_user_active( $user ) ) {
			return self::throw_inactive_error( $user );
		}

		return $user;
	}

	/**
	 * Checks if the provided user is active.
	 *
	 * @param \WP_User $user - The requested user.
	 * @return boolean - True if the user is active. False otherwise ($days_limit exceeded since last login).
	 */
	public static function is_user_active( WP_User $user ) {

		$days_limit = \WPDIU::$days_limit;
		$is_active  = true;
		$now        = current_time( 'mysql' );
		$now_date   = new DateTime( $now );

		// Check if user has last_login meta.
		$user_last_login = get_user_meta( $user->ID, 'last_login', true );

		if ( '' === $user_last_login ) {
			// The user doesn't have the 'last_login' meta yet.
			$activation      = \WPDIU::$activation_date;
			$activation_date = new DateTime( $activation );

			// Check if the plugin was enabled more than $days_limit days ago (they haven't logged in since the plugin was activated).
			$days_difference = self::get_days_between_dates( $activation_date, $now_date, false );

		} else {
			// The user has a 'last_login' meta.
			$user_last_login_date = new DateTime( $user_last_login );

			// Check if the last login date is older than the $days_limit.
			$days_difference = self::get_days_between_dates( $user_last_login_date, $now_date, false );
		}

		if ( $days_difference > $days_limit ) {
			// $days_limit Exceeded. The user needs to be disabled.
			$is_active = false;
		}

		return $is_active;
	}

	/**
	 * Returns a WP_Error saying that the user is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @return WP_Error - An error saying that the user is inactive.
	 */
	public static function throw_inactive_error( WP_User $user ) {

		$username = $user->user_login;
		$wpdiu    = new \WPDIU();

		return new WP_Error(
			'inactive_user',
			sprintf(
				/* translators: %1$s: User's username. %2$s: The days limit. */
				__( '<strong>Error</strong>: The username <strong>%1$s</strong> has been disabled because it has been inactive for %2$s days.', 'wp-disable-inactive-users' ),
				$username,
				$wpdiu::$days_limit
			)
		);
	}

	/**
	 * Returns the difference of days between two dates.
	 *
	 * @param DateTime $date1 - The starting date.
	 * @param DateTime $date2 - The ending date.
	 * @param boolean  $absolute - If it is set to false, the number of days will be negative if the $date1 is older than $date2.
	 * @return integer
	 */
	public static function get_days_between_dates( DateTime $date1, DateTime $date2, $absolute = true ) {
		$interval = $date1->diff( $date2 );
		// Return a negative number if $absolute is set to false and $date1 is older than $date2.
		return ( ! $absolute && $interval->invert ) ? - $interval->days : $interval->days;
	}
}
