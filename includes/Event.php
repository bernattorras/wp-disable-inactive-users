<?php
/**
 * A class to handle the plugin events.
 *
 * @package WordPress
 */

namespace WPDIU;

/**
 * Admin Event class
 */
class Event {

	/**
	 * Schedule a recurring or single notification.
	 *
	 * @param int    $timestamp - Unix timestamp (UTC) for when to next run the event.
	 * @param string $recurrence - How often the event should subsequently recur. See wp_get_schedules() for accepted values.
	 * @param string $hook_name - The name of the hook that needs to be scheduled.
	 * @param array  $args - Array containing arguments to pass to the hook's callback function.
	 * @return void
	 */
	public static function schedule( $timestamp, $recurrence, $hook_name, $args = array() ) {

		if ( 'single' === $recurrence ) {
			if ( ! wp_next_scheduled( $hook_name, $args ) ) {
				wp_schedule_single_event( time(), $hook_name, $args );
			}
		} else {
			if ( ! wp_next_scheduled( $hook_name, $args ) ) {
				wp_schedule_event( $timestamp, $recurrence, $hook_name );
			}
		}

	}

	/**
	 * Unschedule a scheduled hook.
	 *
	 * @param string $hook - The name of the scheduled hook.
	 * @return void
	 */
	public static function unschedule( $hook ) {
		// If the hook isn't scheduled, return.
		if ( ! wp_next_scheduled( $hook ) ) {
			return;
		}
		wp_clear_scheduled_hook( $hook );
	}

}
