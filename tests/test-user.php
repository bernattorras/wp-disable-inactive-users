<?php
/**
 * Class UserTest
 *
 * @package Wp_Disable_Inactive_Users
 */

/**
 * User test case.
 */
class UserTest extends WP_UnitTestCase {

	/**
	 * Instance of the \WPDIU class.
	 *
	 * @var [\WPDIU]
	 */
	public static $diu_class;

	/**
	 * Instance of the \WPDIU\User class.
	 *
	 * @var [\WPDIU\User]
	 */
	public static $user_class;

	/**
	 * Instance of the \WPDIU\Settings class.
	 *
	 * @var [\WPDIU\Settings]
	 */
	public static $settings_class;

	/**
	 * A new test user created for this test case.
	 *
	 * @var [WP_User]
	 */
	public static $test_user;

	/**
	 * Set up the test case initially.
	 */
	public static function set_up_before_class() {
		self::$user_class     = new \WPDIU\User();
		self::$diu_class      = new \WPDIU();
		self::$settings_class = new \WPDIU\Settings();

		self::$settings_class->set_default_opptions();

		self::create_test_user();
	}

	/**
	 * Clear the temporary data when the test case finishes.
	 *
	 * @return void
	 */
	public static function tear_down_after_class() {
		self::delete_test_user();
	}

	/**
	 * Creates a test user.
	 *
	 * @return void
	 */
	public static function create_test_user() {

		$user_email = 'unit_test_user@example.com';
		$user_name  = 'Unit Test User';
		$user_id    = username_exists( 'unit_test_user' );

		if ( ! $user_id && false === email_exists( $user_email ) ) {
			$random_password = wp_generate_password( 12, false );
			$user_id         = wp_create_user( $user_name, $random_password, $user_email );

			self::$test_user = get_user_by( 'id', $user_id );

		} else {
			fwrite( STDERR, __( 'User already exists.', 'wp-disable-inactive-users' ) );
		}
	}

	/**
	 * Deletes the test user.
	 *
	 * @return void
	 */
	public static function delete_test_user() {
		wp_delete_user( self::$test_user->ID );
	}

	/**
	 * Check the validation error.
	 */
	public function test_dissabled_message() {

		$user   = get_user_by( 'id', 1 );
		$result = self::$user_class::throw_inactive_error( $user );

		$this->assertWPError( $result );
		$this->assertSame( $result->get_error_code(), 'inactive_user' );
		$this->assertSame( $result->get_error_message(), '<strong>Error</strong>: The username <strong>admin</strong> has been disabled because it has been inactive for ' . self::$diu_class::$days_limit . ' days.' );
	}

	/**
	 * Check if the update_last_login function is storing the current time in mysql format in the 'wpdiu_last_login' user meta.
	 *
	 * @return void
	 */
	public function test_last_login_meta() {
		self::$user_class::update_last_login( self::$test_user->user_login, self::$test_user );
		$last_login = get_user_meta( self::$test_user->ID, 'wpdiu_last_login', true );

		$this->assertSame( $last_login, current_time( 'mysql' ) );
	}

	/**
	 * Confirms that the user (with an updated 'wpdiu_last_login' date) is considered active.
	 *
	 * @return void
	 */
	public function test_check_if_user_is_active() {
		$user = self::$user_class::check_if_user_active( self::$test_user, self::$test_user->user_pass );

		// No error should be returned if the user is active.
		$this->assertNotWPError( $user );

		// The function should return the same user object.
		$this->assertEquals( $user, self::$test_user );
	}

	/**
	 * Confirms that a user with an old 'wpdiu_last_login' date is disabled.
	 *
	 * @return void
	 */
	public function test_user_is_disabled() {

		$days_limit   = \WPDIU::$days_limit;
		$current_time = new DateTime( current_time( 'mysql' ) );

		// Add 1 day to the current date + the days limit to make sure that the date exceeds the limit to log in.
		$older_date = $current_time->modify( '-' . ( $days_limit + 1 ) . ' day' )->format( 'Y-m-d H:i:s' );

		update_user_meta( self::$test_user->ID, 'wpdiu_last_login', $older_date );

		$result = self::$user_class::check_if_user_active( self::$test_user, self::$test_user->user_pass );

		// An error should be returned if the user's 'wpdiu_last_login' date exceeds the days limit.
		$this->assertWPError( $result );
		$this->assertSame( $result->get_error_code(), 'inactive_user' );
		$this->assertSame(
			$result->get_error_message(),
			sprintf(
				/* translators: %1$s: User's username. %2$s: The days limit. */
				__( '<strong>Error</strong>: The username <strong>%1$s</strong> has been disabled because it has been inactive for %2$s days.', 'wp-disable-inactive-users' ),
				self::$test_user->user_login,
				self::$diu_class::$days_limit
			)
		);
	}

	/**
	 * Confirms that a user with an old 'wpdiu_last_login' date remains active if it has one of the roles that are selected in the 'dont_disable_roles' option.
	 *
	 * @return void
	 */
	public function test_user_with_role_isnt_disabled() {
		$days_limit   = \WPDIU::$days_limit;
		$current_time = new DateTime( current_time( 'mysql' ) );

		// Add 1 day to the current date + the days limit to make sure that the date exceeds the limit to log in.
		$older_date = $current_time->modify( '-' . ( $days_limit + 1 ) . ' day' )->format( 'Y-m-d H:i:s' );

		update_user_meta( self::$test_user->ID, 'wpdiu_last_login', $older_date );

		// Set the user role to one of the roles selected in the 'dont_disable_roles' option.
		$options            = self::$settings_class::get_settings();
		$dont_disable_roles = $options['dont_disable_roles'];
		self::$test_user->add_role( $dont_disable_roles[0] );

		$result = self::$user_class::check_if_user_active( self::$test_user, self::$test_user->user_pass );

		// No error should be returned if the user is active.
		$this->assertNotWPError( $result );

		// The function should return the same user object.
		$this->assertEquals( $result, self::$test_user );
	}

	/**
	 * Tests if the get_days_between_dates returns the days correcty and if it returns a negative day if the first date is more recent than the second one.
	 *
	 * @return void
	 */
	public function test_days_between_dates() {
		$now        = new DateTime( current_time( 'mysql' ) );
		$older_date = new DateTime( current_time( 'mysql' ) );
		$older_date = $older_date->modify( '-1 day' );

		// It should return a postive number (1).
		$days = self::$user_class::get_days_between_dates( $now, $older_date, true );
		$this->assertSame( $days, 1 );

		// It should return a negative number (-1).
		$days = self::$user_class::get_days_between_dates( $now, $older_date, false );
		$this->assertSame( $days, -1 );
	}
}
