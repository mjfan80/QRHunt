<?php
/**
 * Player Flow availability tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Controller\PlayerFlowController;
use QuestUno\Model\Dependency;
use QuestUno\Model\DependencyTargetType;
use QuestUno\Model\DependencyType;

/**
 * Verifies that unavailable Paths stop before scan orchestration.
 */
final class PlayerFlowControllerTest extends IntegrationTestCase {
	/**
	 * Does not persist a Participation or Event when the first checkpoint fails validation.
	 *
	 * @return void
	 */
	public function test_failed_initial_scan_stops_without_event_or_participation(): void {
		$services   = $this->get_services();
		$path       = $this->create_path();
		$required   = $this->create_checkpoint( (int) $path->get_id() );
		$start      = $this->create_checkpoint( (int) $path->get_id() );
		$finish     = $this->create_checkpoint( (int) $path->get_id() );
		$user_id    = self::factory()->user->create();
		$dependency = new Dependency();
		$dependency->set_type( DependencyType::AFTER );
		$dependency->set_target_type( DependencyTargetType::CHECKPOINT );
		$dependency->set_target_id( (int) $required->get_post_id() );
		$services['dependency_repository']->save_for_checkpoint( (int) $start->get_post_id(), array( $dependency ) );
		$start = $services['checkpoint_service']->get_checkpoint_with_dependencies( (int) $start->get_post_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		wp_set_current_user( $user_id );

		global $wp_query;
		$wp_query->query_vars[ PlayerFlowController::QUERY_VAR ] = $start->get_token();

		$controller = new PlayerFlowController(
			$services['checkpoint_service'],
			$services['participation_service'],
			$services['scan_service'],
			$services['path_service'],
			$services['progress_builder']
		);
		$controller->handle_request();
		$context = get_query_var( 'questuno_public_ui_context' );

		self::assertSame( 'Checkpoint could not be validated.', $context['message'] );
		self::assertNull( $services['participation_service']->get_participation_by_user_and_path( $user_id, (int) $path->get_id() ) );
		self::assertCount( 0, $services['event_service']->get_recent_events( 10 ) );
	}

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
		$participation_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}questuno_participations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The integration test asserts that the Player Flow does not persist a Participation.
		$event_count         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}questuno_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The integration test asserts that the Player Flow does not persist an Event.
		$wp_query->query_vars[ PlayerFlowController::QUERY_VAR ] = $checkpoint->get_token();

		$controller = new PlayerFlowController(
			$services['checkpoint_service'],
			$services['participation_service'],
			$services['scan_service'],
			$services['path_service'],
			$services['progress_builder']
		);
		$controller->handle_request();
		$context = get_query_var( 'questuno_public_ui_context' );

		self::assertSame( 'This Path is not available.', $context['message'] );
		self::assertSame( $participation_count, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}questuno_participations" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The integration test compares the persisted Participation count after the Player Flow.
		self::assertSame( $event_count, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}questuno_events" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The integration test compares the persisted Event count after the Player Flow.
	}
}
