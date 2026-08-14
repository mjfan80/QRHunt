<?php
/**
 * Participation progress tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Model\Group;
use QuestUno\Model\GroupCompletionMode;

/**
 * Verifies Group progress derived from persisted state.
 */
final class ParticipationProgressBuilderTest extends IntegrationTestCase {
	/**
	 * Computes ALL and ANY Group completion while excluding empty Groups.
	 *
	 * @return void
	 */
	public function test_builds_completed_groups_from_validated_checkpoints(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$first         = $this->create_checkpoint( (int) $path->get_id() );
		$second        = $this->create_checkpoint( (int) $path->get_id() );
		$all_group     = $this->create_group( (int) $path->get_id(), GroupCompletionMode::ALL, 'All Group' );
		$any_group     = $this->create_group( (int) $path->get_id(), GroupCompletionMode::ANY, 'Any Group' );
		$empty_group   = $this->create_group( (int) $path->get_id(), GroupCompletionMode::ALL, 'Empty Group' );

		$this->assign_group( $first, (int) $all_group->get_id() );
		$this->assign_group( $second, (int) $any_group->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$services['participation_checkpoint_service']->save_validated_checkpoint( (int) $participation->get_id(), (int) $first->get_post_id() );
		$services['participation_checkpoint_service']->save_validated_checkpoint( (int) $participation->get_id(), (int) $second->get_post_id() );

		$progress = $services['progress_builder']->build( $participation );

		self::assertContains( (int) $all_group->get_id(), $progress->get_completed_group_ids() );
		self::assertContains( (int) $any_group->get_id(), $progress->get_completed_group_ids() );
		self::assertNotContains( (int) $empty_group->get_id(), $progress->get_completed_group_ids() );
	}

	/**
	 * Creates a persisted Group.
	 *
	 * @param int    $path_id Path identifier.
	 * @param string $mode    Completion mode.
	 * @param string $name    Group name.
	 * @return Group
	 */
	private function create_group( int $path_id, string $mode, string $name ): Group {
		$services = $this->get_services();
		$group    = new Group();
		$group->set_path_id( $path_id );
		$group->set_name( $name );
		$group->set_completion_mode( $mode );
		$services['group_service']->save_group( $group );
		$groups = $services['group_service']->get_groups_by_path( $path_id );

		foreach ( $groups as $stored_group ) {
			if ( $name === $stored_group->get_name() ) {
				return $stored_group;
			}
		}

		self::fail( 'Group was not persisted.' );
	}

	/**
	 * Assigns a Checkpoint to a Group.
	 *
	 * @param \QuestUno\Model\Checkpoint $checkpoint Checkpoint to update.
	 * @param int                         $group_id   Group identifier.
	 * @return void
	 */
	private function assign_group( $checkpoint, int $group_id ): void {
		$services = $this->get_services();
		$checkpoint->set_group_id( $group_id );
		$services['checkpoint_service']->save_path( $checkpoint );
	}
}
