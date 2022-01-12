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
	 * @var [\WPDIU\Use]
	 */
	public static $user_class;

	/**
	 * A new test user created for this test case.
	 *
	 * @var [WP_User]
	 */
	public static $test_user;


	/**
	 * Set up the test case initially.
	 */
	public static function setUpBeforeClass() {
		self::$user_class = new \WPDIU\User();
		self::$diu_class  = new \WPDIU();

		self::create_test_user();
	}

	/**
	 * Clear the temporary data when the test case finishes.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass() {
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

		if ( ! $user_id && false == email_exists( $user_email ) ) {
			$random_password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $user_name, $random_password, $user_email );
			
			self::$test_user = get_user_by( 'id', $user_id );

		} else {
			fwrite(STDERR, __( 'User already exists.', 'wp-disable-inactive-users' ) );
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

		$user_class = new \WPDIU\User();
		$user       = get_user_by( 'id', 1 );

		$result = self::$user_class::throw_inactive_error( $user );

		$this->assertWPError( $result );
		$this->assertSame( $result->get_error_code(), 'inactive_user' );
		$this->assertSame( $result->get_error_message(), '<strong>Error</strong>: The username <strong>admin</strong> has been disabled because it has been inactive for ' . self::$diu_class::$days_limit . ' days.' );
	}
}
