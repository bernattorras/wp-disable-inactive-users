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
	 * The Admin options
	 *
	 * @var array - The saved plugin options.
	 */
	public $headers;

	public $body;

	public $subject;

	public $admin_email;

	/**
	 * The class constructor.
	 */
	public function __construct() {
		$this->admin_email = get_option( 'admin_email', true );

		$this->headers = apply_filters(
			'wpdiu_notification_headers',
			array(
				'Content-Type: text/html;',
			)
		);

		$this->body = apply_filters(
			'wpdiu_notification_body',
			array(
				'customer' => 'Your account at XXX has been disabled because you didn\'t log in for 90 days. Plese get in touch with the site administrator if you want to reactivate it.',
				'administrator'    => 'A new user account has been disabled. User: ZZZ. Last login: XXX. [Manage users].',
			)
		);

		$this->subject = apply_filters(
			'wpdiu_notification_subject',
			array(
				'customer' => 'Your account at XXX has been disabled',
				'administrator'    => 'A new user account has been disabled',
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

	public function get_notification_params( $send_to, $user_id ) {

		$params = array();

		$user_info   = get_userdata( $user_id );
		$user_name   = $user_info->display_name;
		$user_email  = $user_info->user_email;

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
					'body'    => $this->body[ $send_to ],
					'headers' => $this->headers,
				);
				break;
			case 'all':
				$params['customer'] = array(
					'to'      => $user_email,
					'subject' => $this->subject['customer'],
					'body'    => $this->body['customer'],
					'headers' => $this->headers,
				);
				$params['administrator'] = array(
					'to'      => $this->admin_email,
					'subject' => $this->subject['administrator'],
					'body'    => $this->body['administrator'],
					'headers' => $this->headers,
				);
				break;
		}
		return $params;
	}

}
