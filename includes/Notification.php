<?php
/**
 * A class to handle the plugin notifications.
 *
 * @package WordPress
 */

namespace WPDIU;

use DateTime;

/**
 * Admin Notifications class
 */
class Notification {

	/**
	 * The notification headers.
	 *
	 * @var array - Each notification headers.
	 */
	public $headers;

	/**
	 * The notification body.
	 *
	 * @var array - Each notification body.
	 */
	public $body;

	/**
	 * The notification subject.
	 *
	 * @var array - Each notification subject.
	 */
	public $subject;

	/**
	 * The admin email
	 *
	 * @var string - The site administrator's email.
	 */
	public $admin_email;

	/**
	 * Site name.
	 *
	 * @var string - The name of the site.
	 */
	public $site_name;

	/**
	 * The class constructor.
	 */
	public function __construct() {

		$this->admin_email = get_option( 'admin_email', true );

		$this->site_name = get_option( 'blogname', true );

		$this->headers = apply_filters(
			'wpdiu_notification_headers',
			array(
				'Content-Type: text/html',
			)
		);

		$this->body = apply_filters(
			'wpdiu_notification_body',
			array(
				'customer'           => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s has been disabled because you didn\'t log in for 90 days. Please get in touch with the site administrator if you want to reactivate it.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
				/* translators: %1$s: The username of the disabled user. %3$s: A link to the Users page. */
				'administrator'      => __( '<p>A new user account has been disabled.</p><p><strong>User:</strong> %1$s.</p><p><a href="%2$s">Manage users</a></p>', 'wp-disable-inactive-users' ),
				/* translators: %1$s: An HTML list of the disabled users. %3$s: A link to the Users page. */
				'bulk_administrator' => __( '<p>The following accounts have been disabled:</p>%1$s<p><a href="%2$s">Manage users</a></p>', 'wp-disable-inactive-users' ),
				'reminder'           => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s will be disabled tomorrow if you don\'t log in in the next hours. Please remember to log in today if you want to keep your account active.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
			)
		);

		$this->subject = apply_filters(
			'wpdiu_notification_subject',
			array(
				'customer'           => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s has been disabled.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
				'administrator'      => __( 'A new user account has been disabled', 'wp-disable-inactive-users' ),
				'bulk_administrator' => __( 'New user accounts have been disabled', 'wp-disable-inactive-users' ),
				'reminder'           => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s will be disabled tomorrow.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
			)
		);

	}

	/**
	 * Adds listeners for the hooks that send the scheduled notifications.
	 *
	 * @return void
	 */
	public function add_listeners() {
		add_action( 'wpdiu_send_disabled_notifications', [ $this, 'send_disabled_notifications' ], 10, 2 );
		add_action( 'wpdiu_send_bulk_disabled_notifications', [ $this, 'send_bulk_disabled_notifications' ], 10, 2 );
		add_action( 'wpdiu_send_reminder_notification', [ $this, 'send_reminder_notification' ], 10, 2 );
	}

	/**
	 * Send a notification after bulk disabling mutliple users.
	 *
	 * @param array  $user_ids - The disabled users.
	 * @param string $send_to - To whom the notification should be sent (by default: 'bulk_administrator').
	 * @return void
	 */
	public function send_bulk_disabled_notifications( $user_ids, $send_to = 'bulk_administrator' ) {
		// Get an HTML formatted list of the disabled users.
		foreach ( $user_ids as $user_id ) {
			$user_info         = get_userdata( $user_id );
			$user_name         = $user_info->display_name;
			$user_email        = $user_info->user_email;
			$disabled_emails[] = "ID: $user_id ($user_email)";
		}
		$users_list = '<ul><li>' . implode( '</li><li>', $disabled_emails ) . '</li></ul>';
		$params     = $this->get_notification_params( $send_to, $users_list );

		foreach ( $params as $email => $email_params ) {
			wp_mail( $email_params['to'], $email_params['subject'], $email_params['body'], $email_params['headers'] );
		}
	}
	/**
	 * Send the account disabled notification.
	 *
	 * @param integer $user_id - The ID of the user we're sending the disabled notification.
	 * @param string  $send_to - To whom should the notification be sent.
	 * @return void
	 */
	public function send_disabled_notifications( $user_id, $send_to = 'customer' ) {

		$params = $this->get_notification_params( $send_to, $user_id );

		foreach ( $params as $email => $email_params ) {
			wp_mail( $email_params['to'], $email_params['subject'], $email_params['body'], $email_params['headers'] );
		}

	}

	/**
	 * Send a reminder deactivation email to the customer.
	 *
	 * @param int $user_id - The user's ID.
	 * @return void
	 */
	public function send_reminder_notification( $user_id ) {
		$params = $this->get_notification_params( 'reminder', $user_id );
		foreach ( $params as $email => $email_params ) {
			wp_mail( $email_params['to'], $email_params['subject'], $email_params['body'], $email_params['headers'] );
		}
	}

	/**
	 * Get the parameters of each notification.
	 *
	 * @param string $send_to - Determines to whom should be sent the notification (set in the plugin options).
	 * @param int    $user_id - The Id of the disabled user.
	 * @return array $params - The mail parmaters of the requested notification.
	 */
	public function get_notification_params( $send_to, $user_id ) {

		$params = array();

		if ( is_int( $user_id ) ) {
			$user_info  = get_userdata( $user_id );
			$user_name  = $user_info->display_name;
			$user_email = $user_info->user_email;
		}

		switch ( $send_to ) {
			case 'customer':
				$params['customer'] = array(
					'to'      => $user_email,
					'subject' => $this->subject[ $send_to ],
					'body'    => $this->body[ $send_to ],
					'headers' => $this->headers,
				);
				break;
			case 'administrator':
				$params['administrator'] = array(
					'to'      => $this->admin_email,
					'subject' => $this->subject[ $send_to ],
					'body'    => sprintf(
						$this->body[ $send_to ],
						$user_email,
						admin_url( 'users.php' )
					),
					'headers' => $this->headers,
				);
				break;
			case 'bulk_administrator':
				$params['bulk_administrator'] = array(
					'to'      => $this->admin_email,
					'subject' => $this->subject[ $send_to ],
					'body'    => sprintf(
						$this->body[ $send_to ],
						$user_id,
						admin_url( 'users.php' )
					),
					'headers' => $this->headers,
				);
				break;
			case 'all':
				$params['customer']      = array(
					'to'      => $user_email,
					'subject' => $this->subject['customer'],
					'body'    => $this->body['customer'],
					'headers' => $this->headers,
				);
				$params['administrator'] = array(
					'to'      => $this->admin_email,
					'subject' => $this->subject['administrator'],
					'body'    => sprintf(
						$this->body['administrator'],
						$user_email,
						admin_url( 'users.php' )
					),
					'headers' => $this->headers,
				);
				break;
			case 'reminder':
				$params['reminder'] = array(
					'to'      => $user_email,
					'subject' => $this->subject['reminder'],
					'body'    => $this->body['reminder'],
					'headers' => $this->headers,
				);
				break;
		}

		return $params;
	}

}
