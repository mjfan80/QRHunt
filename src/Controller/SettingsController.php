<?php
/**
 * Settings controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Service\PrivacyService;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and registers QRHunt settings.
 */
final class SettingsController {

	/** @var string */
	public const PAGE_SLUG = 'qrhunt-settings';

	/** @var string */
	public const CSV_SEPARATOR_OPTION_NAME = 'qrhunt_csv_separator';

	/**
	 * Registers the settings page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'qrhunt',
			__( 'Settings', 'qrhunt' ),
			__( 'Settings', 'qrhunt' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers the privacy option.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'qrhunt_settings',
			self::CSV_SEPARATOR_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_csv_separator' ),
				'default'           => ',',
			)
		);

		register_setting(
			'qrhunt_settings',
			PrivacyService::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_privacy_settings' ),
				'default'           => array(
					'record_ip_address' => false,
					'record_user_agent' => false,
				),
			)
		);
	}

	/**
	 * Sanitizes the CSV separator setting.
	 *
	 * @param mixed $separator Submitted separator.
	 * @return string
	 */
	public function sanitize_csv_separator( $separator ): string {
		$separator = is_string( $separator ) ? $separator : ',';

		return in_array( $separator, array( ',', ';', "\t" ), true ) ? $separator : ',';
	}

	/**
	 * Sanitizes the privacy settings received from the WordPress Settings API.
	 *
	 * @param mixed $settings Submitted setting value.
	 * @return array<string, bool>
	 */
	public function sanitize_privacy_settings( $settings ): array {
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'record_ip_address' => ! empty( $settings['record_ip_address'] ),
			'record_user_agent' => ! empty( $settings['record_user_agent'] ),
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$settings = get_option( PrivacyService::OPTION_NAME, array() );
		$settings = is_array( $settings ) ? $settings : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'QRHunt Settings', 'qrhunt' ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'qrhunt_settings' ); ?>
				<h2><?php esc_html_e( 'Privacy', 'qrhunt' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Event data', 'qrhunt' ); ?></th>
						<td>
							<label>
								<input name="<?php echo esc_attr( PrivacyService::OPTION_NAME ); ?>[record_ip_address]" type="checkbox" value="1" <?php checked( ! empty( $settings['record_ip_address'] ) ); ?> />
								<?php esc_html_e( 'Record IP address for new Events', 'qrhunt' ); ?>
							</label>
							<br />
							<label>
								<input name="<?php echo esc_attr( PrivacyService::OPTION_NAME ); ?>[record_user_agent]" type="checkbox" value="1" <?php checked( ! empty( $settings['record_user_agent'] ) ); ?> />
								<?php esc_html_e( 'Record User Agent for new Events', 'qrhunt' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Exports', 'qrhunt' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qrhunt-csv-separator"><?php esc_html_e( 'CSV separator', 'qrhunt' ); ?></label></th>
						<td>
							<select id="qrhunt-csv-separator" name="<?php echo esc_attr( self::CSV_SEPARATOR_OPTION_NAME ); ?>">
								<option value="," <?php selected( ',', get_option( self::CSV_SEPARATOR_OPTION_NAME, ',' ) ); ?>><?php esc_html_e( 'Comma (,)', 'qrhunt' ); ?></option>
								<option value=";" <?php selected( ';', get_option( self::CSV_SEPARATOR_OPTION_NAME, ',' ) ); ?>><?php esc_html_e( 'Semicolon (;)', 'qrhunt' ); ?></option>
								<option value="&#9;" <?php selected( "\t", get_option( self::CSV_SEPARATOR_OPTION_NAME, ',' ) ); ?>><?php esc_html_e( 'Tab', 'qrhunt' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'CSV exports are always encoded in UTF-8.', 'qrhunt' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
