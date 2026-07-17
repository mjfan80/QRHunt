<?php
/**
 * Participation service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationStatus;
use QRHunt\Repository\ParticipationRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Participations.
 */
final class ParticipationService {

	/** @var ParticipationRepository */
	private $participation_repository;

	/** @var PathService */
	private $path_service;

	/**
	 * Creates a Participation service.
	 *
	 * @param ParticipationRepository $participation_repository Participation repository.
	 * @param PathService             $path_service             Path service.
	 */
	public function __construct( ParticipationRepository $participation_repository, PathService $path_service ) {
		$this->participation_repository = $participation_repository;
		$this->path_service             = $path_service;
	}

	/**
	 * Gets all Participations.
	 *
	 * @return array<int, Participation>
	 */
	public function get_participations(): array {
		return $this->participation_repository->find_all();
	}

	/**
	 * Gets Participations matching admin filters.
	 *
	 * @param int    $path_id Path identifier, or 0 for all Paths.
	 * @param int    $user_id User identifier, or 0 for all users.
	 * @param string $status  Participation status, or empty for all statuses.
	 * @return array<int, Participation>
	 */
	public function get_participations_by_filters( int $path_id, int $user_id, string $status ): array {
		$status = $this->is_valid_status( $status ) ? $status : '';

		return $this->participation_repository->find_by_filters( $path_id, $user_id, $status );
	}

	/**
	 * Gets a Participation by identifier.
	 *
	 * @param int $id Participation identifier.
	 * @return Participation|null
	 */
	public function get_participation( int $id ): ?Participation {
		return $this->participation_repository->find_by_id( $id );
	}

	/**
	 * Gets a Participation by user and Path.
	 *
	 * @param int $user_id User identifier.
	 * @param int $path_id Path identifier.
	 * @return Participation|null
	 */
	public function get_participation_by_user_and_path( int $user_id, int $path_id ): ?Participation {
		return $this->participation_repository->find_by_user_and_path( $user_id, $path_id );
	}

	/**
	 * Gets Participations by user.
	 *
	 * @param int $user_id User identifier.
	 * @return array<int, Participation>
	 */
	public function get_participations_by_user( int $user_id ): array {
		return $this->participation_repository->find_by_user( $user_id );
	}

	/**
	 * Counts Participations.
	 *
	 * @return int
	 */
	public function count_participations(): int {
		return $this->participation_repository->count_all();
	}

	/**
	 * Gets or creates the Participation for a Checkpoint scan.
	 *
	 * A Participation is created only when the scanned Checkpoint is the
	 * start Checkpoint of the Path and no valid Participation already exists.
	 *
	 * @param int        $user_id    User identifier.
	 * @param Checkpoint $checkpoint Scanned Checkpoint.
	 * @return Participation|null
	 */
	public function get_participation_for_scan( int $user_id, Checkpoint $checkpoint ): ?Participation {
		$path_id = $checkpoint->get_path_id();

		if ( null === $path_id || null === $checkpoint->get_post_id() ) {
			return null;
		}

		$participation = $this->get_participation_by_user_and_path( $user_id, (int) $path_id );

		if ( null !== $participation ) {
			return $participation;
		}

		$path = $this->path_service->get_path( (int) $path_id );

		if ( null === $path || (int) $checkpoint->get_post_id() !== (int) $path->get_start_checkpoint_id() ) {
			return null;
		}

		return $this->create_participation( $user_id, (int) $path_id );
	}

	/**
	 * Saves a Participation.
	 *
	 * @param Participation $participation Participation to save.
	 * @return void
	 */
	public function save_participation( Participation $participation ): void {
		$status = (string) $participation->get_status();

		if ( ! $this->is_valid_status( $status ) ) {
			throw new \InvalidArgumentException( 'Invalid Participation status.' );
		}

		if ( null === $participation->get_id() ) {
			if ( ParticipationStatus::IN_PROGRESS !== $status ) {
				throw new \InvalidArgumentException( 'Invalid Participation transition.' );
			}

			$this->participation_repository->save( $participation );
			return;
		}

		$current_participation = $this->get_participation( (int) $participation->get_id() );

		if (
			null !== $current_participation
			&& ! $this->can_transition_status( (string) $current_participation->get_status(), $status )
		) {
			throw new \InvalidArgumentException( 'Invalid Participation transition.' );
		}

		$this->participation_repository->save( $participation );
	}

	/**
	 * Creates a Participation for a user and Path.
	 *
	 * @param int $user_id User identifier.
	 * @param int $path_id Path identifier.
	 * @return Participation
	 */
	public function create_participation( int $user_id, int $path_id ): Participation {
		$participation = new Participation();
		$participation->set_user_id( $user_id );
		$participation->set_path_id( $path_id );
		$participation->set_status( ParticipationStatus::IN_PROGRESS );

		$this->save_participation( $participation );

		if ( null !== $participation->get_id() ) {
			return $participation;
		}

		$stored_participation = $this->participation_repository->find_by_user_and_path( $user_id, $path_id );

		if ( null !== $stored_participation ) {
			return $stored_participation;
		}

		return $participation;
	}

	/**
	 * Cancels a Participation without deleting historical data.
	 *
	 * @param int $id Participation identifier.
	 * @return void
	 */
	public function cancel_participation( int $id ): void {
		$participation = $this->get_participation( $id );

		if ( null === $participation || ParticipationStatus::CANCELLED === $participation->get_status() ) {
			return;
		}

		$participation->set_status( ParticipationStatus::CANCELLED );
		$this->save_participation( $participation );
	}

	/**
	 * Checks if a status is supported.
	 *
	 * @param string $status Participation status.
	 * @return bool
	 */
	public function is_valid_status( string $status ): bool {
		return in_array(
			$status,
			array(
				ParticipationStatus::IN_PROGRESS,
				ParticipationStatus::FINISHED,
				ParticipationStatus::COMPLETED,
				ParticipationStatus::CANCELLED,
			),
			true
		);
	}

	/**
	 * Checks if a status transition is allowed by the Participation lifecycle.
	 *
	 * @param string $current_status Current stored status.
	 * @param string $next_status    Requested next status.
	 * @return bool
	 */
	private function can_transition_status( string $current_status, string $next_status ): bool {
		if ( $current_status === $next_status ) {
			return true;
		}

		if ( ParticipationStatus::CANCELLED === $next_status ) {
			return true;
		}

		if ( ParticipationStatus::IN_PROGRESS !== $current_status ) {
			return false;
		}

		return in_array(
			$next_status,
			array( ParticipationStatus::FINISHED, ParticipationStatus::COMPLETED ),
			true
		);
	}
}
