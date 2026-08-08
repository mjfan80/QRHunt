<?php
/**
 * Validation service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;
use QRHunt\Model\DependencyViolation;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationProgress;
use QRHunt\Model\ResolvedDependency;
use QRHunt\Model\ValidationResult;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the public API of the Validation Engine.
 */
final class ValidationService {

	/**
	 * Evaluates whether a Checkpoint can be validated for a Participation.
	 *
	 * @param Participation        $participation         Participation being validated.
	 * @param Checkpoint            $checkpoint           Checkpoint to validate.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @return ValidationResult
	 */
	public function validate( Participation $participation, Checkpoint $checkpoint, ParticipationProgress $participation_state ): ValidationResult {
		if ( $participation->get_path_id() !== $checkpoint->get_path_id() ) {
			return ValidationResult::create_invalid( array() );
		}

		if ( in_array( $checkpoint->get_post_id(), $participation_state->get_validated_checkpoint_ids(), true ) ) {
			return ValidationResult::create_invalid( array() );
		}

		$failed_dependencies = $this->get_failed_dependencies_by_type(
			$checkpoint,
			$participation_state,
			DependencyType::AFTER
		);

		if ( ! empty( $failed_dependencies ) ) {
			return ValidationResult::create_invalid( $failed_dependencies );
		}

		$failed_dependencies = $this->get_failed_dependencies_by_type(
			$checkpoint,
			$participation_state,
			DependencyType::BEFORE
		);

		return empty( $failed_dependencies )
			? ValidationResult::create_valid()
			: ValidationResult::create_invalid( $failed_dependencies );
	}

	/**
	 * Gets unsatisfied Dependencies of one type.
	 *
	 * @param Checkpoint            $checkpoint          Checkpoint being validated.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @param string                $type                Dependency type.
	 * @return array<int, DependencyViolation>
	 */
	private function get_failed_dependencies_by_type( Checkpoint $checkpoint, ParticipationProgress $participation_state, string $type ): array {

		$failed_dependencies = array();

		foreach ( $checkpoint->get_dependencies() as $dependency ) {
			if ( $type !== $dependency->get_type() ) {
				continue;
			}

			if ( $this->is_dependency_satisfied( $dependency, $participation_state ) ) {
				continue;
			}

			$failed_dependencies[] = new DependencyViolation(
				$dependency->get_type(),
				$dependency->get_target_type(),
				$dependency->get_target_id(),
				$dependency->get_display_name()
			);
		}

		return $failed_dependencies;
	}

	/**
	 * Determines whether a resolved Dependency is satisfied.
	 *
	 * @param ResolvedDependency    $dependency          Dependency to evaluate.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @return bool
	 */
	private function is_dependency_satisfied( ResolvedDependency $dependency, ParticipationProgress $participation_state ): bool {
		if ( DependencyTargetType::GROUP === $dependency->get_target_type() ) {
			return $this->is_group_dependency_satisfied( $dependency, $participation_state );
		}

		return $this->is_checkpoint_dependency_satisfied( $dependency, $participation_state );
	}

	/**
	 * Determines whether a Checkpoint Dependency is satisfied.
	 *
	 * @param ResolvedDependency    $dependency          Dependency to evaluate.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @return bool
	 */
	private function is_checkpoint_dependency_satisfied( ResolvedDependency $dependency, ParticipationProgress $participation_state ): bool {
		$is_validated = in_array( $dependency->get_target_id(), $participation_state->get_validated_checkpoint_ids(), true );

		if ( DependencyType::AFTER === $dependency->get_type() ) {
			return $is_validated;
		}

		return ! $is_validated;
	}

	/**
	 * Determines whether a Group Dependency is satisfied.
	 *
	 * @param ResolvedDependency    $dependency          Dependency to evaluate.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @return bool
	 */
	private function is_group_dependency_satisfied( ResolvedDependency $dependency, ParticipationProgress $participation_state ): bool {
		$is_completed = in_array( $dependency->get_target_id(), $participation_state->get_completed_group_ids(), true );

		if ( DependencyType::AFTER === $dependency->get_type() ) {
			return $is_completed;
		}

		return ! $is_completed;
	}
}
