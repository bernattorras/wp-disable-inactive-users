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
		add_action( 'admin_init', [ $this, 'set_default_opptions' ] );

		// Add the "Is disabled" column in the Users page.
		add_filter( 'manage_users_columns', [ $this, 'add_inactive_user_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'inactive_user_column_content' ], 10, 3 );
		add_action( 'admin_head-users.php', [ $this, 'users_page_custom_styles' ] );

		// Add the "Last login" column in the Users page.
		add_filter( 'manage_users_columns', [ $this, 'add_last_login_user_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'last_login_user_column_content' ], 10, 3 );

		// Add the "Reactivate" user row action and its functionality.
		add_filter( 'user_row_actions', [ $this, 'reactivate_user_link' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'add_admin_listeners' ] );
	}

	/**
	 * Retruns the plugin settings.
	 *
	 * @return array - The plugin settings.
	 */
	public static function get_settings() {
		return get_option( 'wpdiu_settings' );
	}

	/**
	 * Add the "Is inactive" column to the Users page.
	 *
	 * @param array $columns - The current User columns.
	 * @return array $columns - The current User columns.
	 */
	public function add_inactive_user_column( $columns ) {
		$columns['inactive_user'] = 'Disabled';
		return $columns;
	}

	/**
	 * Show if a user is inactive.
	 *
	 * @param string $value - Custom column output. Default empty.
	 * @param string $column_name - Column name.
	 * @param int    $user_id - ID of the currently-listed user.
	 * @return string $value - "Yes" if the user is disabled, "-" otherwise.
	 */
	public function inactive_user_column_content( $value, $column_name, $user_id ) {
		$disabled = get_user_meta( $user_id, 'wpdiu_disabled', true );
		if ( 'inactive_user' === $column_name ) {
			$value = '-';
			if ( $disabled ) {
				$value = '<div class="wpdiu_icon wpdiu_disabled"></div>';
			}
		}
		return $value;
	}

	/**
	 * Adds custom styles to the Users page to style the "Disabled" column.
	 *
	 * @return void
	 */
	public function users_page_custom_styles() {
		echo '<style>';
		echo '.wpdiu_icon:before { display: inline-block; -webkit-font-smoothing: antialiased; font: normal 24px/1 "dashicons"; vertical-align: bottom; position: relative; top: 2px;}';
		echo '.wpdiu_disabled:before, .wpdiu_login:before { content: "\f147"; }';
		echo '.wpdiu_blocked:before { content: "\f158"; }';
		echo '</style>';
	}

	/**
	 * Add the "Last logine" column to the Users page.
	 *
	 * @param array $columns - The current User columns.
	 * @return array $columns - The current User columns.
	 */
	public function add_last_login_user_column( $columns ) {
		$columns['wpdiu_last_login'] = 'Last Login';
		return $columns;
	}

	/**
	 * Show the last login date of the user (or the last login attempt if it was blocked)
	 *
	 * @param string $value - Custom column output. Default empty.
	 * @param string $column_name - Column name.
	 * @param int    $user_id - ID of the currently-listed user.
	 * @return string $value - "Yes" if the user is disabled, "-" otherwise.
	 */
	public function last_login_user_column_content( $value, $column_name, $user_id ) {
		$disabled           = get_user_meta( $user_id, 'wpdiu_disabled', true );
		$last_login         = get_user_meta( $user_id, 'wpdiu_last_login', true );
		$blocked_date       = get_user_meta( $user_id, 'wpdiu_date_blocked', true );
		$last_login_attempt = get_user_meta( $user_id, 'wpdiu_last_login_attempt', true );

		if ( 'wpdiu_last_login' === $column_name ) {
			if ( ! $last_login && ! $disabled ) {
				$value = '-';
			}

			if ( $disabled ) {
				$value = '<p><i class="wpdiu_icon wpdiu_blocked"></i>' . $blocked_date . '</p>';
			}

			if ( '' !== $last_login ) {
				$value .= '<p><i class="wpdiu_icon wpdiu_login"></i>' . $last_login . '</p>';
			}
		}
		return $value;
	}

	/**
	 * Adds the 'Reactivate' link to the user row actions.
	 *
	 * @param array   $actions - The user row actions.
	 * @param WP_User $user - The current user.
	 * @return array  $actions - The user row actions.
	 */
	public function reactivate_user_link( $actions, $user ) {
		$disabled = get_user_meta( $user->ID, 'wpdiu_disabled', true );
		if ( $disabled && current_user_can( 'manage_options' ) ) {
			$actions['wpdiu_reactivate'] = "<a class='wpdiu_reactivate' href='" . wp_nonce_url( "users.php?action=wpdiu_reactivate&amp;user=$user->ID", 'wpdiu-reactivate' ) . "'>" . esc_html__( 'Reactivate', 'wp-disable-inactive-users' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Add admin listeners for the 'Reactivation' user row action.
	 *
	 * @return void
	 */
	public function add_admin_listeners() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wpdiu-reactivate' ) || ( 'wpdiu_reactivate' !== $_GET['action'] ) || ! isset( $_GET['user'] ) ) {
				return;
		}

		/* Reactivate user */
		\WPDIU\User::reactivate_user( intval( $_GET['user'] ) );

		// Show reactivation admin notices.
		add_action( 'admin_notices', [ $this, 'reactivation_notice' ] );
		add_action( 'network_admin_notices', [ $this, 'reactivation_notice' ] );
	}

	/**
	 * Show a reactivation admin notice.
	 *
	 * @return void
	 */
	public function reactivation_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'User reactivated!', 'wp-disable-inactive-users' ); ?></p>
		</div>
		<?php
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
		$this->wpdiu_options = get_option( 'wpdiu_settings' );
		?>

		<div class="wrap">
			<h2><?php echo esc_html_e( 'WP Disable Inactive Users', 'wp-disable-inactive-users' ); ?></h2>
			<p><?php echo esc_html_e( 'Here are the settings of the WP Disable Inactive Users plugin.', 'wp-disable-inactive-users' ); ?></p>
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
			__( 'Dont\'t disable the users with the following roles', 'wp-disable-inactive-users' ),
			array( $this, 'dont_disable_roles_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);

		add_settings_field(
			'reminder_email',
			__( 'Send a reminder email', 'wp-disable-inactive-users' ),
			array( $this, 'reminder_email_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);

		add_settings_field(
			'disabled_notification',
			__( 'Send a notification email when a user is disabled', 'wp-disable-inactive-users' ),
			array( $this, 'disabled_notification_callback' ),
			'wp-disable-inactive-users-admin',
			'wpdiu_setting_section'
		);
	}

	/**
	 * Set de option defaults.
	 *
	 * @return void
	 */
	public function set_default_opptions() {
		$wpdiu_options = get_option( 'wpdiu_settings' );
		if ( false === $wpdiu_options ) {
			// By default, prevent administrators and editors to be disabled.
			$wpdiu_options = array(
				'dont_disable_roles'    => [ 'administrator', 'editor' ],
				'disabled_notification' => 'none',
			);
			update_option( 'wpdiu_settings', $wpdiu_options );
		}
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
		global $wp_roles;
		$all_roles      = $wp_roles->roles;
		$editable_roles = apply_filters( 'editable_roles', $all_roles );
		?>
		<select name="wpdiu_settings[dont_disable_roles][]" id="dont_disable_roles" multiple='multiple' style="min-width: 10em; padding-top: 6px;">
		<?php
		foreach ( $editable_roles as $role => $role_data ) {
			$selected = ( isset( $this->wpdiu_options['dont_disable_roles'] ) && in_array( $role, $this->wpdiu_options['dont_disable_roles'], true ) ) ? 'selected' : '';
			?>
			<option value="<?php echo esc_attr( $role ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $role_data['name'] ); ?></option>
			<?php
		}
		?>
		</select> 
		<p class="description"><?php echo esc_html_e( 'Select multiple roles with Ctrl-Click for Windows or Cmd-Click for Mac.', 'wp-disable-inactive-users' ); ?></p>
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
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'none' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="none" <?php echo esc_attr( $selected ); ?>><?php echo esc_html_e( 'Don\'t send any notification', 'wp-disable-inactive-users' ); ?></option>
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'customer' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="customer" <?php echo esc_attr( $selected ); ?>><?php echo esc_html_e( 'Send it to the user', 'wp-disable-inactive-users' ); ?></option>
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'administrator' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="administrator" <?php echo esc_attr( $selected ); ?>><?php echo esc_html_e( 'Send it to the administrator', 'wp-disable-inactive-users' ); ?></option>
			<?php $selected = ( isset( $this->wpdiu_options['disabled_notification'] ) && 'all' === $this->wpdiu_options['disabled_notification'] ) ? 'selected' : ''; ?>
			<option value="all" <?php echo esc_attr( $selected ); ?>><?php echo esc_html_e( 'Send it to both', 'wp-disable-inactive-users' ); ?></option>
		</select> 
		<?php
	}

}
