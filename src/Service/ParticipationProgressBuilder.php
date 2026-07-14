<?php
/**
 * Participation progress builder.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\Group;
use QRHunt\Model\GroupCompletionMode;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationProgress;
use QRHunt\Repository\CheckpointRepository;
use QRHunt\Repository\GroupRepository;
use QRHunt\Repository\ParticipationCheckpointRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the current progress snapshot for a Participation.
 */
final class ParticipationProgressBuilder {

	/** @var ParticipationCheckpointRepository */
	private $participation_checkpoint_repository;

	/** @var CheckpointRepository */
	private $checkpoint_repository;

	/** @var GroupRepository */
	private $group_repository;

	/**
	 * Creates a Participation progress builder.
	 *
	 * @param ParticipationCheckpointRepository $participation_checkpoint_repository Participation checkpoint repository.
	 * @param CheckpointRepository              $checkpoint_repository                Checkpoint repository.
	 * @param GroupRepository                   $group_repository                     Group repository.
	 */
	public function __construct(
		ParticipationCheckpointRepository $participation_checkpoint_repository,
		CheckpointRepository $checkpoint_repository,
		GroupRepository $group_repository
	) {
		$this->participation_checkpoint_repository = $participation_checkpoint_repository;
		$this->checkpoint_repository               = $checkpoint_repository;
		$this->group_repository                    = $group_repository;
	}

	/**
	 * Builds the current progress snapshot for a Participation.
	 *
	 * @param Participation $participation Participation whose state must be built.
	 * @return ParticipationProgress
	 */
	public function build( Participation $participation ): ParticipationProgress {
		$participation_id = $participation->get_id();
		$path_id          = $participation->get_path_id();

		if ( null === $participation_id || null === $path_id ) {
			return new ParticipationProgress();
		}

		$validated_checkpoint_ids = $this->participation_checkpoint_repository->find_validated_checkpoint_ids_by_participation( $participation_id );
		$checkpoints              = $this->checkpoint_repository->find_by_path( $path_id );
		$groups                   = $this->group_repository->find_by_path( $path_id );
		$completed_group_ids      = $this->build_completed_group_ids( $groups, $checkpoints, $validated_checkpoint_ids );

		return new ParticipationProgress( $validated_checkpoint_ids, $completed_group_ids );
	}

	/**
	 * Builds the completed Group identifiers for a Participation.
	 *
	 * @param array<int, Group>      $groups                   Groups belonging to the Path.
	 * @param array<int, Checkpoint> $checkpoints              Checkpoints belonging to the Path.
	 * @param array<int, int>        $validated_checkpoint_ids Validated Checkpoint identifiers.
	 * @return array<int, int>
	 */
	private function build_completed_group_ids( array $groups, array $checkpoints, array $validated_checkpoint_ids ): array {
		$completed_group_ids      = array();
		$validated_checkpoint_map = array_fill_keys( $validated_checkpoint_ids, true );

		foreach ( $groups as $group ) {
			$group_checkpoint_ids = $this->get_group_checkpoint_ids( $group, $checkpoints );

			if ( empty( $group_checkpoint_ids ) ) {
				continue;
			}

			if ( $this->is_group_completed( $group, $group_checkpoint_ids, $validated_checkpoint_map ) ) {
				$completed_group_ids[] = (int) $group->get_id();
			}
		}

		return $completed_group_ids;
	}

	/**
	 * Gets the Checkpoint identifiers belonging to a Group.
	 *
	 * @param Group                  $group       Group to inspect.
	 * @param array<int, Checkpoint> $checkpoints Available Checkpoints.
	 * @return array<int, int>
	 */
	private function get_group_checkpoint_ids( Group $group, array $checkpoints ): array {
		$group_checkpoint_ids = array();
		$group_id             = $group->get_id();

		foreach ( $checkpoints as $checkpoint ) {
			if ( $group_id !== $checkpoint->get_group_id() ) {
				continue;
			}

			$group_checkpoint_ids[] = (int) $checkpoint->get_post_id();
		}

		return $group_checkpoint_ids;
	}

	/**
	 * Determines whether a Group is completed.
	 *
	 * @param Group             $group                    Group to evaluate.
	 * @param array<int, int>   $group_checkpoint_ids     Group Checkpoint identifiers.
	 * @param array<int, true>  $validated_checkpoint_map Validated Checkpoint lookup map.
	 * @return bool
	 */
	private function is_group_completed( Group $group, array $group_checkpoint_ids, array $validated_checkpoint_map ): bool {
		if ( GroupCompletionMode::ANY === $group->get_completion_mode() ) {
			foreach ( $group_checkpoint_ids as $checkpoint_id ) {
				if ( isset( $validated_checkpoint_map[ $checkpoint_id ] ) ) {
					return true;
				}
			}

			return false;
		}

		foreach ( $group_checkpoint_ids as $checkpoint_id ) {
			if ( ! isset( $validated_checkpoint_map[ $checkpoint_id ] ) ) {
				return false;
			}
		}

		return true;
	}
}
