<?php
/**
 * Privacy settings service.
 *
 * @package QuestUno
 */

namespace QuestUno\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the optional personal data recorded on new Events.
 */
final class PrivacyService {

	/** @var string */
	public const OPTION_NAME = 'questuno_privacy_settings';

	/**
	 * Determines whether IP address recording is enabled.
	 *
	 * @return bool
	 */
	public function records_ip_address(): bool {
		$settings = $this->get_settings();

		return ! empty( $settings['record_ip_address'] );
	}

	/**
	 * Determines whether User Agent recording is enabled.
	 *
	 * @return bool
	 */
	public function records_user_agent(): bool {
		$settings = $this->get_settings();

		return ! empty( $settings['record_user_agent'] );
	}

	/**
	 * Gets the request IP address when enabled and valid.
	 *
	 * @return string|null
	 */
	public function get_ip_address(): ?string {
		if ( ! $this->records_ip_address() || ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return null;
		}

		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

		return false === filter_var( $ip_address, FILTER_VALIDATE_IP ) ? null : $ip_address;
	}

	/**
	 * Gets the request User Agent when enabled.
	 *
	 * @return string|null
	 */
	public function get_user_agent(): ?string {
		if ( ! $this->records_user_agent() || ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return null;
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		return '' === $user_agent ? null : $user_agent;
	}

	/**
	 * Gets the stored settings with opt-in defaults.
	 *
	 * @return array<string, bool>
	 */
	private function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );

		return is_array( $settings ) ? $settings : array();
	}
}
