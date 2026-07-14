<?php
/**
 * Participation checkpoint service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Repository\ParticipationCheckpointRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Participation checkpoint state.
 */
final class ParticipationCheckpointService {

	/** @var ParticipationCheckpointRepository */
	private $participation_checkpoint_repository;

	/**
	 * Creates a Participation checkpoint service.
	 *
	 * @param ParticipationCheckpointRepository $participation_checkpoint_repository Participation checkpoint repository.
	 */
	public function __construct( ParticipationCheckpointRepository $participation_checkpoint_repository ) {
		$this->participation_checkpoint_repository = $participation_checkpoint_repository;
	}

	/**
	 * Registers a validated Checkpoint for a Participation.
	 *
	 * @param int $participation_id Participation identifier.
	 * @param int $checkpoint_id    Checkpoint identifier.
	 * @return void
	 */
	public function save_validated_checkpoint( int $participation_id, int $checkpoint_id ): void {
		$this->participation_checkpoint_repository->save( $participation_id, $checkpoint_id );
	}
}
