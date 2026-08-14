<?php
/**
 * Export service tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Service\ExportService;

/**
 * Verifies export datasets and filters against persisted QuestUno data.
 */
final class ExportServiceTest extends IntegrationTestCase {
	/**
	 * Exports filtered Event rows and Path statistics.
	 *
	 * @return void
	 */
	public function test_exports_events_and_statistics_for_selected_path(): void {
		$services      = $this->get_services();
		$path          = $this->create_path( array( 'name' => 'Selected Path' ) );
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$finish        = $this->create_checkpoint( (int) $path->get_id() );
		$user_id       = self::factory()->user->create( array( 'display_name' => 'Player' ) );
		$participation = $this->create_participation( $user_id, (int) $path->get_id() );
		$path->set_start_checkpoint_id( (int) $checkpoint->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$services['scan_service']->scan_checkpoint( $participation, $checkpoint );
		$export = new ExportService(
			$services['participation_service'],
			$services['event_service'],
			$services['path_service'],
			$services['checkpoint_service'],
			$services['progress_builder']
		);

		$events     = $export->get_event_export( (int) $path->get_id(), $user_id, (int) $checkpoint->get_post_id(), 'accepted', '', '' );
		$participations = $export->get_participation_export( (int) $path->get_id(), $user_id, 'in_progress' );
		$statistics = $export->get_path_statistics_export( (int) $path->get_id() );
		$path_statistics = $export->get_path_statistics( (int) $path->get_id() );

		self::assertSame( array( 'participation_id', 'user_id', 'user_display_name', 'user_email', 'path_id', 'path_name', 'status', 'started_at', 'finished_at', 'cancelled_at', 'created_at', 'updated_at', 'validated_checkpoints' ), $participations['headers'] );
		self::assertCount( 1, $participations['rows'] );
		self::assertSame( array( 'event_id', 'participation_id', 'user_id', 'user_display_name', 'user_email', 'path_id', 'path_name', 'checkpoint_id', 'checkpoint_title', 'event_type', 'result', 'ip_address', 'user_agent', 'created_at' ), $events['headers'] );
		self::assertCount( 1, $events['rows'] );
		self::assertSame( '', $events['rows'][0][11] );
		self::assertSame( '', $events['rows'][0][12] );
		self::assertSame( '1', $statistics['rows'][0][7] );
		self::assertSame( '1', $statistics['rows'][0][8] );
		self::assertNotNull( $path_statistics );
		self::assertSame( 1, $path_statistics['events_total'] );
		self::assertSame( 1, $path_statistics['events_accepted'] );
	}
}
