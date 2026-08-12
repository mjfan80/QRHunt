<?php
/**
 * Path archiving tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Controller\PathController;
use QRHunt\PathPostType;
use QRHunt\Service\ExportService;

/**
 * Verifies that Path archiving preserves QRHunt history.
 */
final class PathArchivingTest extends IntegrationTestCase {
	/**
	 * Registers the custom Path status used by these integration tests.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		( new PathPostType() )->register();
	}

	/**
	 * Archives and restores a Path without altering its historical data.
	 *
	 * @return void
	 */
	public function test_archiving_and_restoring_preserves_history(): void {
		$services      = $this->get_services();
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$path          = $this->create_path();
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$finish        = $this->create_checkpoint( (int) $path->get_id() );
		$user_id       = self::factory()->user->create();
		$participation = $this->create_participation( $user_id, (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $checkpoint->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$services['scan_service']->scan_checkpoint( $participation, $checkpoint );
		wp_set_current_user( $administrator );

		$controller = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$events_before   = $this->get_event_values( $services['event_service']->get_events_by_participation( (int) $participation->get_id() ) );
		$progress_before = $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids();

		self::assertSame( 'publish', get_post_status( (int) $path->get_post_id() ) );
		self::assertTrue( $controller->transition_post_status( (int) $path->get_post_id(), PathPostType::ARCHIVED_STATUS ) );
		self::assertSame( PathPostType::ARCHIVED_STATUS, get_post_status( (int) $path->get_post_id() ) );

		$archived_path = $services['path_service']->get_path( (int) $path->get_id() );
		self::assertSame( PathPostType::ARCHIVED_STATUS, $archived_path->get_status() );
		self::assertFalse( $services['path_service']->is_path_available_for_scan( $archived_path ) );
		self::assertSame( $events_before, $this->get_event_values( $services['event_service']->get_events_by_participation( (int) $participation->get_id() ) ) );
		self::assertSame( $progress_before, $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
		self::assertSame( 'in_progress', $services['participation_service']->get_participation( (int) $participation->get_id() )->get_status() );

		$export = new ExportService(
			$services['participation_service'],
			$services['event_service'],
			$services['path_service'],
			$services['checkpoint_service'],
			$services['progress_builder']
		);
		self::assertCount( 1, $export->get_event_export( (int) $path->get_id(), $user_id, 0, '', '', '' )['rows'] );

		self::assertTrue( $controller->transition_post_status( (int) $path->get_post_id(), 'draft' ) );
		self::assertSame( 'draft', get_post_status( (int) $path->get_post_id() ) );
		self::assertSame( 'draft', $services['path_service']->get_path( (int) $path->get_id() )->get_status() );
		self::assertSame( $events_before, $this->get_event_values( $services['event_service']->get_events_by_participation( (int) $participation->get_id() ) ) );
		self::assertSame( $progress_before, $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
	}

	/**
	 * Gets the persisted Event values relevant to historical integrity.
	 *
	 * @param array<int,\QRHunt\Model\Event> $events Events to project.
	 * @return array<int,array<string,int|string|null>>
	 */
	private function get_event_values( array $events ): array {
		$values = array();

		foreach ( $events as $event ) {
			$values[] = array(
				'id'               => $event->get_id(),
				'participation_id' => $event->get_participation_id(),
				'checkpoint_id'    => $event->get_checkpoint_id(),
				'event_type'       => $event->get_event_type(),
				'result'           => $event->get_result(),
				'created_at'       => $event->get_created_at(),
			);
		}

		return $values;
	}
}
