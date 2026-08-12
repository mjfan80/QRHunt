<?php
/**
 * Path configuration validator.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;
use QRHunt\Model\Path;

defined( 'ABSPATH' ) || exit;

/**
 * Validates the structural requirements of a publishable Path.
 */
final class PathConfigurationValidator {

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var DependencyService */
	private $dependency_service;

	/** @var GroupService */
	private $group_service;

	/**
	 * Creates a Path configuration validator.
	 *
	 * @param CheckpointService $checkpoint_service Checkpoint service.
	 * @param DependencyService $dependency_service Dependency service.
	 * @param GroupService      $group_service      Group service.
	 */
	public function __construct( CheckpointService $checkpoint_service, DependencyService $dependency_service, GroupService $group_service ) {
		$this->checkpoint_service = $checkpoint_service;
		$this->dependency_service = $dependency_service;
		$this->group_service      = $group_service;
	}

	/**
	 * Gets the structural configuration errors for a Path.
	 *
	 * @param Path $path Path being validated.
	 * @return array<int, string>
	 */
	public function validate( Path $path ): array {
		return $this->get_diagnostics( $path )['errors'];
	}

	/**
	 * Gets the publishability diagnostics for a Path.
	 *
	 * @param Path $path Path being diagnosed.
	 * @return array{checks:array<int,string>,errors:array<int,string>,warnings:array<int,string>,publishable:bool}
	 */
	public function get_diagnostics( Path $path ): array {
		$path_id = $path->get_id();

		if ( null === $path_id ) {
			return array(
				'checks'      => array(),
				'errors'      => array( __( 'The Path must be saved before it can be published.', 'qrhunt' ) ),
				'warnings'    => array(),
				'publishable' => false,
			);
		}

		$checkpoints    = $this->checkpoint_service->get_checkpoints_by_path( $path_id );
		$checkpoint_ids = array();
		$errors         = array();
		$warnings       = array();
		$checks         = array();
		$dependencies   = array();

		foreach ( $checkpoints as $checkpoint ) {
			if ( null !== $checkpoint->get_post_id() ) {
				$checkpoint_ids[ (int) $checkpoint->get_post_id() ] = true;
			}
		}

		$start_checkpoint_id  = $path->get_start_checkpoint_id();
		$finish_checkpoint_id = $path->get_finish_checkpoint_id();

		$has_valid_start = null !== $start_checkpoint_id && isset( $checkpoint_ids[ $start_checkpoint_id ] );
		$has_valid_finish = null !== $finish_checkpoint_id && isset( $checkpoint_ids[ $finish_checkpoint_id ] );

		if ( ! $has_valid_start ) {
			$errors[] = __( 'A Path must have a start Checkpoint belonging to the Path.', 'qrhunt' );
		} else {
			$checks[] = __( 'The start Checkpoint belongs to this Path.', 'qrhunt' );
		}

		if ( ! $has_valid_finish ) {
			$errors[] = __( 'A Path must have a finish Checkpoint belonging to the Path.', 'qrhunt' );
		} else {
			$checks[] = __( 'The finish Checkpoint belongs to this Path.', 'qrhunt' );
		}

		if ( null !== $start_checkpoint_id && $start_checkpoint_id === $finish_checkpoint_id ) {
			$errors[] = __( 'The start and finish Checkpoints must be different.', 'qrhunt' );
		} elseif ( $has_valid_start && $has_valid_finish ) {
			$checks[] = __( 'The start and finish Checkpoints are different.', 'qrhunt' );
		}

		$precedence_graph = array();

		foreach ( array_keys( $checkpoint_ids ) as $checkpoint_id ) {
			$precedence_graph[ $checkpoint_id ] = array();
		}

		$error_count_before_dependencies = count( $errors );

		foreach ( array_keys( $checkpoint_ids ) as $checkpoint_id ) {
			$dependencies[ $checkpoint_id ] = $this->dependency_service->get_dependencies_by_checkpoint( $checkpoint_id );

			foreach ( $dependencies[ $checkpoint_id ] as $dependency ) {
				$target_ids = $this->get_target_checkpoint_ids( $dependency->get_target_type(), $dependency->get_target_id(), $path_id, $checkpoint_ids, $errors );

				foreach ( $target_ids as $target_id ) {
					if ( DependencyType::AFTER === $dependency->get_type() ) {
						$precedence_graph[ $target_id ][] = $checkpoint_id;
					} elseif ( DependencyType::BEFORE === $dependency->get_type() ) {
						$precedence_graph[ $checkpoint_id ][] = $target_id;
					}
				}
			}
		}

		$has_dependency_errors = count( $errors ) !== $error_count_before_dependencies;

		if ( ! $has_dependency_errors ) {
			$checks[] = __( 'All dependency targets and Groups belong to this Path.', 'qrhunt' );
		}

		if ( $this->has_cycle( $precedence_graph ) ) {
			$errors[] = __( 'The Path dependencies contain a cycle.', 'qrhunt' );
		} else {
			$checks[] = __( 'The Path dependencies do not contain cycles.', 'qrhunt' );
		}

		foreach ( $checkpoints as $checkpoint ) {
			$checkpoint_id = $checkpoint->get_post_id();

			if (
				null === $checkpoint_id
				|| $checkpoint_id === $start_checkpoint_id
				|| $checkpoint_id === $finish_checkpoint_id
				|| ! empty( $dependencies[ $checkpoint_id ] )
			) {
				continue;
			}

			$warnings[] = sprintf(
				/* translators: %s: Checkpoint title. */
				__( 'The Checkpoint "%s" has no dependencies and can be validated independently.', 'qrhunt' ),
				$this->checkpoint_service->get_checkpoint_title( $checkpoint_id )
			);
		}

		$errors = array_values( array_unique( $errors ) );

		return array(
			'checks'      => array_values( array_unique( $checks ) ),
			'errors'      => $errors,
			'warnings'    => array_values( array_unique( $warnings ) ),
			'publishable' => empty( $errors ),
		);
	}

	/**
	 * Resolves a dependency target to Checkpoint post identifiers.
	 *
	 * @param string|null     $target_type    Dependency target type.
	 * @param int|null        $target_id      Dependency target identifier.
	 * @param int             $path_id        Path identifier.
	 * @param array<int, bool> $checkpoint_ids Checkpoints belonging to the Path.
	 * @param array<int, string> $errors      Validation errors.
	 * @return array<int, int>
	 */
	private function get_target_checkpoint_ids( ?string $target_type, ?int $target_id, int $path_id, array $checkpoint_ids, array &$errors ): array {
		if ( null === $target_id || $target_id <= 0 ) {
			$errors[] = __( 'A dependency has an invalid target.', 'qrhunt' );
			return array();
		}

		if ( DependencyTargetType::CHECKPOINT === $target_type ) {
			if ( ! isset( $checkpoint_ids[ $target_id ] ) ) {
				$errors[] = __( 'A dependency references a Checkpoint that does not belong to this Path.', 'qrhunt' );
				return array();
			}

			return array( $target_id );
		}

		if ( DependencyTargetType::GROUP !== $target_type ) {
			$errors[] = __( 'A dependency has an invalid target type.', 'qrhunt' );
			return array();
		}

		$group = $this->group_service->get_group( $target_id );

		if ( null === $group || $path_id !== $group->get_path_id() ) {
			$errors[] = __( 'A dependency references a Group that does not belong to this Path.', 'qrhunt' );
			return array();
		}

		$target_checkpoint_ids = array();

		foreach ( $this->checkpoint_service->get_checkpoints_by_path( $path_id ) as $checkpoint ) {
			if ( $target_id === $checkpoint->get_group_id() && null !== $checkpoint->get_post_id() ) {
				$target_checkpoint_ids[] = (int) $checkpoint->get_post_id();
			}
		}

		return $target_checkpoint_ids;
	}

	/**
	 * Determines whether a directed graph contains a cycle.
	 *
	 * @param array<int, array<int, int>> $graph Directed graph.
	 * @return bool
	 */
	private function has_cycle( array $graph ): bool {
		$states = array();

		foreach ( array_keys( $graph ) as $node_id ) {
			if ( $this->visit_node( $node_id, $graph, $states ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Visits a graph node using depth-first search.
	 *
	 * @param int                       $node_id Node identifier.
	 * @param array<int, array<int, int>> $graph Graph.
	 * @param array<int, int>           $states Visit states.
	 * @return bool
	 */
	private function visit_node( int $node_id, array $graph, array &$states ): bool {
		if ( 1 === ( $states[ $node_id ] ?? 0 ) ) {
			return true;
		}

		if ( 2 === ( $states[ $node_id ] ?? 0 ) ) {
			return false;
		}

		$states[ $node_id ] = 1;

		foreach ( $graph[ $node_id ] ?? array() as $target_id ) {
			if ( $this->visit_node( $target_id, $graph, $states ) ) {
				return true;
			}
		}

		$states[ $node_id ] = 2;

		return false;
	}
}
