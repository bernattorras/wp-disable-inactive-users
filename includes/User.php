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
use WP_User_Query;

defined( 'ABSPATH' ) || exit;

/**
 * User class
 * This class handles all the user functionlity.
 */
class User {

	/**
	 * A list of the disabled users
	 *
	 * @var array - An array containing the IDs of the disabled users.
	 */
	public static $disabled_users = array();

	/**
	 * User init method.
	 *
	 * @return void
	 */
	public static function init() {
		$options            = \WPDIU\Settings::get_settings();
		$disabled_users_ids = get_option( 'wpdiu_disabled_users' );

		// The callback for the 'wpdiu_bulk_disable_users' event.
		add_action( 'wpdiu_get_disabled_users', [ __CLASS__, 'get_disabled_users_ids' ] );

		// Schedule a single event to get the IDs of the disabled users and store it to the 'wpdiu_disabled_users' option (if it doesn't exist already).
		if ( ! $disabled_users_ids ) {
			\WPDIU\Event::schedule(
				time(),
				'single',
				'wpdiu_get_disabled_users'
			);
		}

		if ( isset( $options['disable_automatically'] ) && 'on' === $options['disable_automatically'] ) {

			// The callback for the 'wpdiu_bulk_disable_users' event.
			add_action( 'wpdiu_disable_users_automatically', [ __CLASS__, 'bulk_disable_users' ] );

			// Schedule the daily event to disable users automatically.
			\WPDIU\Event::schedule(
				time() + ( 60 * 60 ),
				'daily',
				'wpdiu_disable_users_automatically'
			);
		} else {
			\WPDIU\Event::unschedule( 'wpdiu_disable_users_automatically' );
		}

	}

	/**
	 * Adds/updates the 'last_login' user meta with the current time when the user logs in.
	 *
	 * @param string  $user_login - The user login name.
	 * @param WP_User $user - The user object.
	 * @return void
	 */
	public static function update_last_login( string $user_login, WP_User $user ) {
		update_user_meta( $user->ID, 'wpdiu_last_login', current_time( 'mysql' ) );
	}

	/**
	 * Checks if the user that is trying to log in is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @param string  $password - The password of the user.
	 * @return WP_User $user or WP_Error - The current user object if the user is active. A WP_Error otherwise.
	 */
	public static function check_if_user_active( WP_User $user, string $password ) {

		$options            = \WPDIU\Settings::get_settings();
		$dont_disable_roles = $options['dont_disable_roles'];
		$has_role           = self::user_has_role( $user->ID, $dont_disable_roles );
		$disabled           = get_user_meta( $user->ID, 'wpdiu_disabled', true );

		// Skip the validation if the user has one of the roles specified in the "Don't disable users with the following roles" option.
		if ( $has_role ) {
			return $user;
		}

		if ( $disabled || ! self::is_user_active( $user ) ) {
			self::disable_user( $user, false );
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
		$user_last_login = get_user_meta( $user->ID, 'wpdiu_last_login', true );
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
	 * Disable a user.
	 *
	 * @param WP_User $user - A user to disable.
	 * @param bool    $is_bulk - True if the disable request comes from a bulk deactivation (no notification will be sent and no 'wpdiu_last_login_attempt' meta will be updated).
	 * @return void
	 */
	public static function disable_user( $user, $is_bulk = false ) {

		$is_already_disabled = get_user_meta( $user->ID, 'wpdiu_disabled', true );

		if ( ! $is_bulk ) {
			update_user_meta( $user->ID, 'wpdiu_last_login_attempt', current_time( 'mysql' ) );
		}

		// The user is disabled for the first time.
		if ( ! $is_already_disabled ) {

			// Set disabled and blocked date metas.
			update_user_meta( $user->ID, 'wpdiu_disabled', true );
			update_user_meta( $user->ID, 'wpdiu_date_blocked', current_time( 'mysql' ) );

			// Send the blocked notifiction.
			$settings = \WPDIU\Settings::get_settings();
			$send_to  = $settings['disabled_notification'];

			// Don't send any notification if the user has selected 'none' in the "Send a notification email when a user is disabled" options field.
			if ( 'none' === $send_to ) {
				return;
			}

			if ( ! $is_bulk ) {
				// Send the notification only if it isn't coming from a bulk disable event.
				\WPDIU\Event::schedule(
					time(),
					'single',
					'wpdiu_send_disabled_notifications',
					$args = array(
						'user_id' => $user->ID,
						'send_to' => $send_to,
					)
				);
			}
		}
	}

	/**
	 * Function to bulk disable users automatically.
	 *
	 * @return void
	 */
	public static function bulk_disable_users() {
		$options            = \WPDIU\Settings::get_settings();
		$dont_disable_roles = $options['dont_disable_roles'];
		$send_to            = $options['disabled_notification'];
		$disabled_users_ids = get_option( 'wpdiu_disabled_users' );
		$new_disabled_users = array();

		// Get a list of all the active users that aren't already disabled and that don't have the roles specified in the plugin settings.
		$active_users_query = new WP_User_Query(
			array(
				'exclude'      => $disabled_users_ids,
				'role__not_in' => $dont_disable_roles,
			)
		);

		$active_users = $active_users_query->get_results();

		foreach ( $active_users as $user ) {
			// Check if each user should still be active. If not, disable it.
			$is_active = self::is_user_active( $user );
			if ( ! $is_active ) {

				// Disable the user.
				self::disable_user( $user, true );

				// Schedule the customer notification (doing it from here instead of from the 'disable_user' function).
				\WPDIU\Event::schedule(
					time(),
					'single',
					'wpdiu_send_disabled_notifications',
					$args = array(
						'user_id' => $user->ID,
						'send_to' => 'customer',
					)
				);

				// Add the user ID to the disabled users list.
				$new_disabled_users[] = $user->ID;

			}
		}

		if ( ( 'administrator' === $send_to || 'all' === $send_to ) && ! empty( $new_disabled_users ) ) {
			// Schedule the admin notification.
			\WPDIU\Event::schedule(
				time(),
				'single',
				'wpdiu_send_bulk_disabled_notifications',
				$args = array(
					'user_ids' => $new_disabled_users,
					'send_to'  => 'bulk_administrator',
				)
			);
		}

		// Update the disabled users option with all the recently disabled users.
		$disabled_users_ids = array_merge( $disabled_users_ids, $new_disabled_users );
		update_option( 'wpdiu_disabled_users', $disabled_users_ids );
	}

	/**
	 * Gets a list of the IDs of the disabled users.
	 *
	 * @return array - An array containing the IDs of the disabled users.
	 */
	public static function get_disabled_users_ids() {
		$disabled_users_ids = get_option( 'wpdiu_disabled_users' );

		// If there's no 'wpdiu_disabled_users' option, get the users from the database (performing a slower WP_User_Query).
		if ( ! $disabled_users_ids ) {
			$disabled_users_ids = array();
			$disabled_users     = self::get_disabled_users();

			foreach ( $disabled_users as $user ) {
				$disabled_users_ids[] = $user->ID;
			}

			// Save the disabled users in the 'wpdiu_disabled_users' option to avoid future user queries.
			self::set_disabled_users_option( $disabled_users_ids );
		}

		return $disabled_users_ids;
	}

	/**
	 * Get the disabled users by performing a WP_User_Query.
	 *
	 * @return object - The results of the WP_User_Query containing the disabled users.
	 */
	public static function get_disabled_users() {

		$disabled_users_query = new WP_User_Query(
			array(
				'meta_key'   => 'wpdiu_disabled',
				'meta_value' => true,
			)
		);

		$disabled_users = $disabled_users_query->get_results();

		return $disabled_users;
	}

	/**
	 * Saves a list of the disabled users ID's to the database.
	 *
	 * @param array $disabled_users_ids - The IDs of the disabled users.
	 * @return void
	 */
	public static function set_disabled_users_option( array $disabled_users_ids ) {
		// Save the disabled users in the 'wpdiu_disabled_users' option.
		update_option( 'wpdiu_disabled_users', $disabled_users_ids );
	}

	/**
	 * Returns a WP_Error saying that the user is inactive.
	 *
	 * @param WP_User $user - The current user object.
	 * @return WP_Error - An error saying that the user is inactive.
	 */
	public static function throw_inactive_error( WP_User $user ) {

		$username = $user->user_login;

		return new WP_Error(
			'inactive_user',
			sprintf(
				/* translators: %1$s: User's username. %2$s: The days limit. */
				__( '<strong>Error</strong>: The username <strong>%1$s</strong> has been disabled because it has been inactive for %2$s days.', 'wp-disable-inactive-users' ),
				$username,
				\WPDIU::$days_limit
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

	/**
	 * Reactivates a user.
	 *
	 * @param string $user_id - The user ID.
	 * @return void
	 */
	public static function reactivate_user( $user_id ) {
		delete_user_meta( $user_id, 'wpdiu_last_login' );
		delete_user_meta( $user_id, 'wpdiu_disabled' );

		// Remove the user from the 'wpdiu_disabled_users' option.
		$disabled_users = get_option( 'wpdiu_disabled_users' );
		$key            = array_search( $user_id, $disabled_users, true );
		if ( false !== $key ) {
			// The user is in the 'wpdiu_disabled_users' option. Remove it and update the option.
			unset( $disabled_users[ $key ] );
			update_option( 'wpdiu_disabled_users', $disabled_users );
		}
	}

	/**
	 * Reactivate all users automatically.
	 *
	 * @return void
	 */
	public static function reactivate_all_users() {
		$disabled_users = self::$disabled_users;
		foreach ( $disabled_users as $user_id ) {
			self::reactivate_user( $user_id );
		}
	}

	/**
	 * Check if the user has one of the provided roles.
	 *
	 * @param int   $user_id - The user ID.
	 * @param array $roles - An array containing the roles to check against.
	 * @return boolean - True if the user has one of these roles. False otherwise.
	 */
	public static function user_has_role( $user_id, $roles ) {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		foreach ( $user_roles as $key => $role ) {
			if ( in_array( $role, $roles, true ) ) {
				return true;
			}
		}
		return false;
	}

}
