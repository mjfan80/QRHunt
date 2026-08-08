<?php
/**
 * Player Flow availability tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Controller\PlayerFlowController;

/**
 * Verifies that unavailable Paths stop before scan orchestration.
 */
final class PlayerFlowControllerTest extends IntegrationTestCase {
	/**
	 * Stops a scan before the Path opening date without creating data.
	 *
	 * @return void
	 */
	public function test_path_not_yet_open_stops_without_event_or_participation(): void {
		$this->assert_unavailable_path_stops_before_scan( wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + DAY_IN_SECONDS ) );
	}

	/**
	 * Stops a scan after the Path closing date without creating data.
	 *
	 * @return void
	 */
	public function test_closed_path_stops_without_event_or_participation(): void {
		$this->assert_unavailable_path_stops_before_scan( null, wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS ) );
	}

	/**
	 * Executes the Player Flow for an unavailable Path and checks that storage is unchanged.
	 *
	 * @param string|null $opening_date Opening date.
	 * @param string|null $closing_date Closing date.
	 * @return void
	 */
	private function assert_unavailable_path_stops_before_scan( ?string $opening_date = null, ?string $closing_date = null ): void {
		$services   = $this->get_services();
		$path       = $this->create_path(
			array(
				'opening_date' => $opening_date,
				'closing_date' => $closing_date,
			)
		);
		$checkpoint = $this->create_checkpoint( (int) $path->get_id() );
		$user_id    = self::factory()->user->create();
		wp_set_current_user( $user_id );

		global $wpdb, $wp_query;
		$participation_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}qrhunt_participations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name contains only the WordPress prefix and fixed QRHunt suffix.
		$event_count         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}qrhunt_events" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name contains only the WordPress prefix and fixed QRHunt suffix.
		$wp_query->query_vars[ PlayerFlowController::QUERY_VAR ] = $checkpoint->get_token();

		$controller = new PlayerFlowController(
			$services['checkpoint_service'],
			$services['participation_service'],
			$services['scan_service'],
			$services['path_service'],
			$services['progress_builder']
		);
		$controller->handle_request();
		$context = get_query_var( 'qrhunt_public_ui_context' );

		self::assertSame( 'This Path is not available.', $context['message'] );
		self::assertSame( $participation_count, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}qrhunt_participations" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name contains only the WordPress prefix and fixed QRHunt suffix.
		self::assertSame( $event_count, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}qrhunt_events" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name contains only the WordPress prefix and fixed QRHunt suffix.
	}
}
