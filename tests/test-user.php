<?php
/**
 * Class UserTest
 *
 * @package Wp_Disable_Inactive_Users
 */

use WP_Error;

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
	 * Set up the test initially.
	 */
	public static function setUpBeforeClass() {
		self::$user_class = new \WPDIU\User();
		self::$diu_class  = new \WPDIU();
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
