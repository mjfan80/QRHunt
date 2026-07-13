<?php
/**
 * Participation service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Participation;
use QRHunt\Repository\ParticipationRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Participations.
 */
final class ParticipationService {

	/** @var ParticipationRepository */
	private $participation_repository;

	/**
	 * Creates a Participation service.
	 *
	 * @param ParticipationRepository $participation_repository Participation repository.
	 */
	public function __construct( ParticipationRepository $participation_repository ) {
		$this->participation_repository = $participation_repository;
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
	 * Gets a Participation by identifier.
	 *
	 * @param int $id Participation identifier.
	 * @return Participation|null
	 */
	public function get_participation( int $id ): ?Participation {
		return $this->participation_repository->find_by_id( $id );
	}

	/**
	 * Saves a Participation.
	 *
	 * @param Participation $participation Participation to save.
	 * @return void
	 */
	public function save_participation( Participation $participation ): void {
		$this->participation_repository->save( $participation );
	}

	/**
	 * Deletes a Participation.
	 *
	 * @param int $id Participation identifier.
	 * @return void
	 */
	public function delete_participation( int $id ): void {
		$this->participation_repository->delete( $id );
	}
}
