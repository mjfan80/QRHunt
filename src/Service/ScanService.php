<?php
/**
 * Scan service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Participation;
use QRHunt\Model\ValidationResult;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the scan validation workflow.
 */
final class ScanService {

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var ParticipationProgressBuilder */
	private $participation_progress_builder;

	/** @var ValidationService */
	private $validation_service;

	/**
	 * Creates a scan service.
	 *
	 * @param CheckpointService            $checkpoint_service             Checkpoint service.
	 * @param ParticipationProgressBuilder $participation_progress_builder Participation progress builder.
	 * @param ValidationService            $validation_service             Validation service.
	 */
	public function __construct(
		CheckpointService $checkpoint_service,
		ParticipationProgressBuilder $participation_progress_builder,
		ValidationService $validation_service
	) {
		$this->checkpoint_service             = $checkpoint_service;
		$this->participation_progress_builder = $participation_progress_builder;
		$this->validation_service             = $validation_service;
	}

	/**
	 * Validates a Checkpoint scan for a Participation.
	 *
	 * @param Participation $participation      Participation being validated.
	 * @param int           $checkpoint_post_id Checkpoint post identifier.
	 * @return ValidationResult
	 */
	public function validate( Participation $participation, int $checkpoint_post_id ): ValidationResult {
		$checkpoint = $this->checkpoint_service->get_checkpoint_with_dependencies( $checkpoint_post_id );

		if ( null === $checkpoint ) {
			throw new \InvalidArgumentException( 'Checkpoint not found.' );
		}

		$participation_progress = $this->participation_progress_builder->build( $participation );

		return $this->validation_service->validate( $participation, $checkpoint, $participation_progress );
	}
}
