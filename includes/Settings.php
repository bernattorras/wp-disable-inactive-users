<?php
/**
 * A class to create and handle the plugin admin options.
 *
 * @package WordPress
 */

namespace WPDIU;

/**
 * Admin Settings class
 */
class Settings {

	/**
	 * The Admin options
	 *
	 * @var array - The saved plugin options.
	 */
	private $wpdiu_options;

	/**
	 * The class constructor.
	 */
	public function __construct() {
		// Add the settings page ( Users > Disable Inactive Users ).
		add_action( 'admin_menu', [ $this, 'wpdiu_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'page_init' ] );
	}

	/**
	 * Adds the admin page ( Users > Disable Inactive Users ).
	 *
	 * @return void
	 */
	public function wpdiu_add_plugin_page() {
		add_users_page(
			'WP Disable Inactive Users',
			'Disable Inactive Users',
			'manage_options',
			'wp-disable-inactive-users',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Creates the admin page with its settings.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		$this->wpdiu_options = get_option( 'wpdiu_settings' ); ?>

		<div class="wrap">
			<h2>WP Disable Inactive Users</h2>
			<p>Here are the settings of the WP Disable Inactive Users plugin.</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'wpdiu_option_group' );
					do_settings_sections( 'wp-disable-inactive-users-admin' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the settings with its sections and fields.
	 *
	 * @return void
	 */
	public function page_init() {
		register_setting(
			'wpdiu_option_group',
			'wpdiu_settings',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'wpdiu_setting_section',
			'Settings',
			array( $this, 'wpdiu_section_info' ),
			'wp-disable-inactive-users-admin'
		);

		add_settings_field(
			'dont_disable_roles',
			'Dont\'t disable the users with the following roles',
			array( $this, 'dont_disable_roles_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);

		add_settings_field(
			'reminder_email',
			'Send a reminder email',
			array( $this, 'reminder_email_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);

		add_settings_field(
			'disabled_notification',
			'Send a notification email when a user is disabled',
			array( $this, 'disabled_notification_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);
	}

	/**
	 * Sanitize the options.
	 *
	 * @param array $input - The option values.
	 * @return array $sanitary_values - The sanitized option values.
	 */
	public function sanitize_options( $input ) {
		$sanitary_values = array();

		if ( isset( $input['dont_disable_roles'] ) ) {
			$sanitary_values['dont_disable_roles'] = $input['dont_disable_roles'];
		}

		if ( isset( $input['reminder_email'] ) ) {
			$sanitary_values['reminder_email'] = $input['reminder_email'];
		}

		if ( isset( $input['disabled_notification'] ) ) {
			$sanitary_values['disabled_notification'] = $input['disabled_notification'];
		}

		return $sanitary_values;
	}

	/**
	 * Settings section info.
	 *
	 * @return void
	 */
	public function wpdiu_section_info() {

	}

	/**
	 * "Don't disable users with the following roles" field.
	 *
	 * @return void
	 */
	public function dont_disable_roles_callback() {
		?>
		<select name="wpdiu_settings[dont_disable_roles][]" id="dont_disable_roles" multiple='multiple' style="width: 10em; padding-top: 6px;">
			<?php $selected = ( isset( $this->wpdiu_options['dont_disable_roles'] ) && in_array( 'administrator', $this->wpdiu_options['dont_disable_roles'], true ) ) ? 'selected' : ''; ?>
			<option value="administrator" <?php echo esc_attr( $selected ); ?>>Administrator</option>
			<?php $selected = ( isset( $this->wpdiu_options['dont_disable_roles'] ) && in_array( 'editor', $this->wpdiu_options['dont_disable_roles'], true ) ) ? 'selected' : ''; ?>
			<option value="editor" <?php echo esc_attr( $selected ); ?>>Editor</option>
			<?php $selected = ( isset( $this->wpdiu_options['dont_disable_roles'] ) && in_array( 'subscriber', $this->wpdiu_options['dont_disable_roles'], true ) ) ? 'selected' : ''; ?>
			<option value="subscriber" <?php echo esc_attr( $selected ); ?>>Subscriber</option>
		</select> 
		<p class="description">Select multiple roles with Ctrl-Click for Windows or Cmd-Click for Mac.</p>
		<?php
	}

	/**
	 * Reminder email field.
	 *
	 * @return void
	 */
	public function reminder_email_callback() {
		printf(
			'<input type="checkbox" name="wpdiu_settings[reminder_email]" id="reminder_email" value="reminder_email" %s> <label for="reminder_email">Check this option to send a reminder email to the customers 1 day before disabling their account.</label>',
			( isset( $this->wpdiu_options['reminder_email'] ) && 'reminder_email' === $this->wpdiu_options['reminder_email'] ) ? 'checked' : ''
		);
	}

	/**
	 * Disabled notifications field.
	 *
	 * @return void
	 */
	public function disabled_notification_callback() {
		?>
		<select name="wpdiu_settings[disabled_notification]" id="disabled_notification">
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'customer' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="customer" <?php echo esc_attr( $selected ); ?>>Send it to the user</option>
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'administrator' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="administrator" <?php echo esc_attr( $selected ); ?>>Send it to the administrator</option>
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'all' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="all" <?php echo esc_attr( $selected ); ?>>Send it to both</option>
		</select> 
		<?php
	}

}
