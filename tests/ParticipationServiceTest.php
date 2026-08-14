<?php
/**
 * Participation lifecycle tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Model\ParticipationStatus;

/**
 * Verifies creation and allowed state transitions.
 */
final class ParticipationServiceTest extends IntegrationTestCase {
	/**
	 * Retrieves an existing Participation without persisting one during lookup.
	 *
	 * @return void
	 */
	public function test_gets_existing_participation_without_creating_one(): void {
		$services    = $this->get_services();
		$path        = $this->create_path();
		$start       = $this->create_checkpoint( (int) $path->get_id() );
		$other       = $this->create_checkpoint( (int) $path->get_id() );
		$user_id     = self::factory()->user->create();

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$services['path_service']->save_path( $path );

		self::assertNull( $services['participation_service']->get_participation_for_scan( $user_id, $start ) );
		self::assertNull( $services['participation_service']->get_participation_for_scan( $user_id, $other ) );

		$participation = $this->create_participation( $user_id, (int) $path->get_id() );

		self::assertSame( ParticipationStatus::IN_PROGRESS, $participation->get_status() );
		self::assertSame( $participation->get_id(), $services['participation_service']->get_participation_for_scan( $user_id, $other )->get_id() );
	}

	/**
	 * Accepts only documented lifecycle transitions and always allows cancellation.
	 *
	 * @return void
	 */
	public function test_enforces_participation_lifecycle(): void {
		$services      = $this->get_services();
		$participation = $this->create_participation( self::factory()->user->create(), (int) $this->create_path()->get_id() );

		$participation->set_status( ParticipationStatus::FINISHED );
		$services['participation_service']->save_participation( $participation );
		$participation->set_status( ParticipationStatus::IN_PROGRESS );

		$this->expectException( \InvalidArgumentException::class );
		$services['participation_service']->save_participation( $participation );
	}

	/**
	 * Keeps the Participation record while marking it cancelled.
	 *
	 * @return void
	 */
	public function test_cancellation_preserves_participation_history(): void {
		$services      = $this->get_services();
		$participation = $this->create_participation( self::factory()->user->create(), (int) $this->create_path()->get_id() );

		$services['participation_service']->cancel_participation( (int) $participation->get_id() );
		$stored = $services['participation_service']->get_participation( (int) $participation->get_id() );

		self::assertNotNull( $stored );
		self::assertSame( ParticipationStatus::CANCELLED, $stored->get_status() );
		self::assertNotNull( $stored->get_cancelled_at() );
	}
}
