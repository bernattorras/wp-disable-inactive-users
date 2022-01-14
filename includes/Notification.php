<?php
/**
 * A class to handle the plugin notifications.
 *
 * @package WordPress
 */

namespace WPDIU;

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
				'customer'      => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s has been disabled because you didn\'t log in for 90 days. Plese get in touch with the site administrator if you want to reactivate it.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
				/* translators: %1$s: The username of the disabled user. %3$s: A links to the Users page. */
				'administrator' => __( '<p>A new user account has been disabled.</p><p><strong>User:</strong> %1$s.</p><p><a href="%2$s">Manage users</a></p>', 'wp-disable-inactive-users' ),
			)
		);

		$this->subject = apply_filters(
			'wpdiu_notification_subject',
			array(
				'customer'      => sprintf(
					/* translators: %s: The site name. */
					__( 'Your account at %s has been disabled.', 'wp-disable-inactive-users' ),
					$this->site_name
				),
				'administrator' => __( 'A new user account has been disabled', 'wp-disable-inactive-users' ),
			)
		);

	}

	/**
	 * Adds listeners for the hooks that send the scheduled notifications.
	 *
	 * @return void
	 */
	public function add_listeners() {
		add_action( 'wpdiu_send_reminder_notifications', [ $this, 'send_reminder_notifications' ], 10, 2 );
		add_action( 'wpdiu_send_disabled_notifications', [ $this, 'send_disabled_notifications' ], 10, 2 );
	}

	/**
	 * Schedule a recurring or single notification.
	 *
	 * @param int    $timestamp - Unix timestamp (UTC) for when to next run the event.
	 * @param string $recurrence - How often the event should subsequently recur. See wp_get_schedules() for accepted values.
	 * @param string $hook_type - 'reminder' or 'disabled' depending on which kind of notification needs to be sent.
	 * @param array  $args - Array containing arguments to pass to the hook's callback function.
	 * @return void
	 */
	public function schedule( $timestamp, $recurrence, $hook_type, $args = array() ) {

		$hook_name = 'wpdiu_send_' . $hook_type . '_notifications';

		if ( 'single' === $recurrence ) {
			if ( ! wp_next_scheduled( $hook_type ) ) {
				wp_schedule_single_event( time(), $hook_name, $args );
			}
		} else {
			if ( ! wp_next_scheduled( $hook_type ) ) {
				wp_schedule_event( $timestamp, $recurrence, $hook_name, $args );
			}
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
	 * Get the parameters of each notification.
	 *
	 * @param string $send_to - Determines to whom should be sent the notification (set in the plugin options).
	 * @param int    $user_id - The Id of the disabled user.
	 * @return array $params - The mail parmaters of the requested notification.
	 */
	public function get_notification_params( $send_to, $user_id ) {

		$params = array();

		$user_info  = get_userdata( $user_id );
		$user_name  = $user_info->display_name;
		$user_email = $user_info->user_email;

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
		}
		return $params;
	}

}
