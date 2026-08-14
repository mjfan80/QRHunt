<?php
/**
 * Scan orchestration tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Model\Dependency;
use QuestUno\Model\DependencyTargetType;
use QuestUno\Model\DependencyType;
use QuestUno\Model\EventResult;
use QuestUno\Model\ParticipationStatus;

/**
 * Verifies persisted scan outcomes, Events and Participation status changes.
 */
final class ScanServiceTest extends IntegrationTestCase {
	/**
	 * Persists a Participation, its start Checkpoint and its accepted Event only
	 * after the first validation succeeds.
	 *
	 * @return void
	 */
	public function test_accepted_start_scan_creates_participation_progress_and_event(): void {
		$services  = $this->get_services();
		$path      = $this->create_path();
		$start     = $this->create_checkpoint( (int) $path->get_id() );
		$finish    = $this->create_checkpoint( (int) $path->get_id() );
		$user_id   = self::factory()->user->create();

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		$result        = $services['scan_service']->start_participation( $user_id, $start );
		$participation = $services['participation_service']->get_participation_by_user_and_path( $user_id, (int) $path->get_id() );

		self::assertTrue( $result->is_valid() );
		self::assertInstanceOf( \QuestUno\Model\Participation::class, $participation );
		self::assertSame( ParticipationStatus::IN_PROGRESS, $participation->get_status() );
		self::assertSame( array( (int) $start->get_post_id() ), $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
		self::assertSame( EventResult::ACCEPTED, $services['event_service']->get_events_by_participation( (int) $participation->get_id() )[0]->get_result() );
	}

	/**
	 * Does not persist a Participation or Event when the initial validation fails.
	 *
	 * @return void
	 */
	public function test_failed_start_scan_does_not_create_participation_or_event(): void {
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

		$result = $services['scan_service']->start_participation( $user_id, $start );

		self::assertFalse( $result->is_valid() );
		self::assertNull( $services['participation_service']->get_participation_by_user_and_path( $user_id, (int) $path->get_id() ) );
		self::assertCount( 0, $services['event_service']->get_recent_events( 10 ) );
	}

	/**
	 * Persists accepted scans and marks a fully visited Path as completed.
	 *
	 * @return void
	 */
	public function test_accepted_scan_updates_progress_event_and_completion(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );

		$path->set_finish_checkpoint_id( (int) $checkpoint->get_post_id() );
		$services['path_service']->save_path( $path );
		$result = $services['scan_service']->scan_checkpoint( $participation, $checkpoint );

		self::assertTrue( $result->is_valid() );
		self::assertSame( array( (int) $checkpoint->get_post_id() ), $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
		self::assertSame( ParticipationStatus::COMPLETED, $services['participation_service']->get_participation( (int) $participation->get_id() )->get_status() );
		self::assertSame( EventResult::ACCEPTED, $services['event_service']->get_events_by_participation( (int) $participation->get_id() )[0]->get_result() );
	}

	/**
	 * Records a duplicate Event without adding a second progress record.
	 *
	 * @return void
	 */
	public function test_duplicate_scan_does_not_change_progress(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$finish        = $this->create_checkpoint( (int) $path->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$path->set_start_checkpoint_id( (int) $checkpoint->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		$services['scan_service']->scan_checkpoint( $participation, $checkpoint );
		$result = $services['scan_service']->scan_checkpoint( $participation, $checkpoint );
		$events = $services['event_service']->get_events_by_participation( (int) $participation->get_id() );

		self::assertFalse( $result->is_valid() );
		self::assertCount( 1, $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
		self::assertSame( ParticipationStatus::IN_PROGRESS, $services['participation_service']->get_participation( (int) $participation->get_id() )->get_status() );
		self::assertCount( 2, $events );
		self::assertSame( EventResult::DUPLICATE, $events[0]->get_result() );
		self::assertSame( EventResult::ACCEPTED, $events[1]->get_result() );
	}

	/**
	 * Rejects an unsatisfied AFTER Dependency and records the matching Event result.
	 *
	 * @return void
	 */
	public function test_after_dependency_failure_does_not_update_progress(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$required      = $this->create_checkpoint( (int) $path->get_id() );
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$dependency    = new Dependency();
		$dependency->set_type( DependencyType::AFTER );
		$dependency->set_target_type( DependencyTargetType::CHECKPOINT );
		$dependency->set_target_id( (int) $required->get_post_id() );
		$services['dependency_repository']->save_for_checkpoint( (int) $checkpoint->get_post_id(), array( $dependency ) );
		$checkpoint = $services['checkpoint_service']->get_checkpoint_with_dependencies( (int) $checkpoint->get_post_id() );

		$result = $services['scan_service']->scan_checkpoint( $participation, $checkpoint );

		self::assertFalse( $result->is_valid() );
		self::assertSame( array(), $services['progress_builder']->build( $participation )->get_validated_checkpoint_ids() );
		self::assertSame( EventResult::AFTER_FAILED, $services['event_service']->get_events_by_participation( (int) $participation->get_id() )[0]->get_result() );
	}
}
