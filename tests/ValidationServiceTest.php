<?php
/**
 * Validation Engine tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationProgress;
use QRHunt\Model\ResolvedDependency;
use QRHunt\Service\ValidationService;

/**
 * Verifies Validation Engine rules without persistence.
 */
final class ValidationServiceTest extends \WP_UnitTestCase {
	/**
	 * Rejects a Checkpoint from another Path before checking Dependencies.
	 *
	 * @return void
	 */
	public function test_rejects_checkpoint_outside_participation_path(): void {
		$participation = new Participation();
		$participation->set_path_id( 10 );
		$checkpoint = $this->create_checkpoint( 11, 101 );
		$checkpoint->set_dependencies( array( new ResolvedDependency( DependencyType::AFTER, DependencyTargetType::CHECKPOINT, 999, 'Required' ) ) );

		$result = ( new ValidationService() )->validate( $participation, $checkpoint, new ParticipationProgress() );

		self::assertFalse( $result->is_valid() );
		self::assertSame( array(), $result->get_failed_dependencies() );
	}

	/**
	 * Rejects an already validated Checkpoint before checking Dependencies.
	 *
	 * @return void
	 */
	public function test_rejects_duplicate_checkpoint_before_dependencies(): void {
		$participation = new Participation();
		$participation->set_path_id( 10 );
		$checkpoint = $this->create_checkpoint( 10, 101 );
		$checkpoint->set_dependencies( array( new ResolvedDependency( DependencyType::AFTER, DependencyTargetType::CHECKPOINT, 999, 'Required' ) ) );

		$result = ( new ValidationService() )->validate( $participation, $checkpoint, new ParticipationProgress( array( 101 ) ) );

		self::assertFalse( $result->is_valid() );
		self::assertSame( array(), $result->get_failed_dependencies() );
	}

	/**
	 * Stops at unsatisfied AFTER Dependencies before evaluating BEFORE ones.
	 *
	 * @return void
	 */
	public function test_evaluates_after_dependencies_before_before_dependencies(): void {
		$participation = new Participation();
		$participation->set_path_id( 10 );
		$checkpoint = $this->create_checkpoint( 10, 101 );
		$checkpoint->set_dependencies(
			array(
				new ResolvedDependency( DependencyType::BEFORE, DependencyTargetType::CHECKPOINT, 201, 'Later' ),
				new ResolvedDependency( DependencyType::AFTER, DependencyTargetType::CHECKPOINT, 202, 'Earlier' ),
			)
		);

		$result = ( new ValidationService() )->validate( $participation, $checkpoint, new ParticipationProgress( array( 201 ) ) );

		self::assertFalse( $result->is_valid() );
		self::assertCount( 1, $result->get_failed_dependencies() );
		self::assertSame( DependencyType::AFTER, $result->get_failed_dependencies()[0]->get_type() );
	}

	/**
	 * Creates a minimal Checkpoint model.
	 *
	 * @param int $path_id Path identifier.
	 * @param int $post_id Checkpoint identifier.
	 * @return Checkpoint
	 */
	private function create_checkpoint( int $path_id, int $post_id ): Checkpoint {
		$checkpoint = new Checkpoint();
		$checkpoint->set_path_id( $path_id );
		$checkpoint->set_post_id( $post_id );

		return $checkpoint;
	}
}
