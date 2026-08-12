<?php
/**
 * Path configuration validation tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Model\Dependency;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;

/**
 * Verifies structural Path publication requirements.
 */
final class PathConfigurationValidatorTest extends IntegrationTestCase {
	/**
	 * Accepts a Path with distinct start and finish Checkpoints in the Path.
	 *
	 * @return void
	 */
	public function test_accepts_a_structurally_valid_path(): void {
		$services = $this->get_services();
		$path     = $this->create_path();
		$start    = $this->create_checkpoint( (int) $path->get_id() );
		$finish   = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );

		self::assertSame( array(), $services['path_configuration_validator']->validate( $path ) );
	}

	/**
	 * Rejects a dependency cycle that makes the required order impossible.
	 *
	 * @return void
	 */
	public function test_rejects_cyclic_checkpoint_dependencies(): void {
		$services = $this->get_services();
		$path     = $this->create_path();
		$start    = $this->create_checkpoint( (int) $path->get_id() );
		$finish   = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$services['dependency_repository']->save_for_checkpoint( (int) $start->get_post_id(), array( $this->create_after_dependency( (int) $finish->get_post_id() ) ) );
		$services['dependency_repository']->save_for_checkpoint( (int) $finish->get_post_id(), array( $this->create_after_dependency( (int) $start->get_post_id() ) ) );
		$start_dependencies  = $services['dependency_repository']->find_by_checkpoint( (int) $start->get_post_id() );
		$finish_dependencies = $services['dependency_repository']->find_by_checkpoint( (int) $finish->get_post_id() );

		self::assertCount( 1, $start_dependencies );
		self::assertSame( DependencyType::AFTER, $start_dependencies[0]->get_type() );
		self::assertSame( DependencyTargetType::CHECKPOINT, $start_dependencies[0]->get_target_type() );
		self::assertSame( $finish->get_post_id(), $start_dependencies[0]->get_target_id() );
		self::assertCount( 1, $finish_dependencies );
		self::assertSame( DependencyType::AFTER, $finish_dependencies[0]->get_type() );
		self::assertSame( DependencyTargetType::CHECKPOINT, $finish_dependencies[0]->get_target_type() );
		self::assertSame( $start->get_post_id(), $finish_dependencies[0]->get_target_id() );

		self::assertContains( 'The Path dependencies contain a cycle.', $services['path_configuration_validator']->validate( $path ) );
	}

	/**
	 * Warns when an ordinary Checkpoint has no own dependency rules.
	 *
	 * @return void
	 */
	public function test_warns_for_an_ordinary_checkpoint_without_own_dependencies(): void {
		$services = $this->get_services();
		$path     = $this->create_path();
		$start    = $this->create_checkpoint( (int) $path->get_id() );
		$first    = $this->create_checkpoint( (int) $path->get_id() );
		$second   = $this->create_checkpoint( (int) $path->get_id() );
		$finish   = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$services['dependency_repository']->save_for_checkpoint( (int) $second->get_post_id(), array( $this->create_after_dependency( (int) $first->get_post_id() ) ) );

		$diagnostics = $services['path_configuration_validator']->get_diagnostics( $path );

		self::assertTrue( $diagnostics['publishable'] );
		self::assertSame( array(), $diagnostics['errors'] );
		self::assertCount( 1, $diagnostics['warnings'] );
		self::assertStringContainsString( $services['checkpoint_service']->get_checkpoint_title( (int) $first->get_post_id() ), $diagnostics['warnings'][0] );
	}

	/**
	 * Creates an AFTER dependency targeting a Checkpoint.
	 *
	 * @param int $target_id Target Checkpoint post identifier.
	 * @return Dependency
	 */
	private function create_after_dependency( int $target_id ): Dependency {
		$dependency = new Dependency();
		$dependency->set_type( DependencyType::AFTER );
		$dependency->set_target_type( DependencyTargetType::CHECKPOINT );
		$dependency->set_target_id( $target_id );

		return $dependency;
	}
}
