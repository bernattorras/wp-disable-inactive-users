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

	/**
	 * Unschedules all the plugin events (the ones that start with 'wpdiu_').
	 *
	 * @return void
	 */
	public static function unschedule_all_wpdiu_events() {
		// Get all the 'wpdiu_' events.
		$wpdiu_events = self::get_wpdiu_events();

		// Get their name from their args and unschedule them.
		foreach ( $wpdiu_events as $key => $event ) {
			$event_name = key( $event );
			self::unschedule( $event_name );
		}
	}

	/**
	 * Returns all the events scheduled by this plugin (the ones that start with 'wpdiu_').
	 *
	 * @return array - An array containing the plguin scheduled events.
	 */
	public static function get_wpdiu_events() {
		// Get all the cron events from the 'cron' option.
		$cron_events = get_option( 'cron', array() );

		// Filter the array to get only the hooks that start with '_wpdiu'.
		$wpdiu_events = array_filter( $cron_events, [ __CLASS__, 'filter_for_wpdiu_events' ] );

		return $wpdiu_events;
	}

	/**
	 * Filters an array to return only the items that have an initial key that starts with 'wpdiu_'.
	 *
	 * @param array $event - A scheduled event.
	 * @return array the filtered array item.
	 */
	public function filter_for_wpdiu_events( $event ) {

		return ( is_array( $event ) && stripos( key( $event ), 'wpdiu_' ) === 0 );
	}

}
