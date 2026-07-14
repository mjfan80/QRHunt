<?php
/**
 * Scan service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\DependencyType;
use QRHunt\Model\Event;
use QRHunt\Model\EventResult;
use QRHunt\Model\EventType;
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

	/** @var ParticipationCheckpointService */
	private $participation_checkpoint_service;

	/** @var EventService */
	private $event_service;

	/**
	 * Creates a scan service.
	 *
	 * @param CheckpointService              $checkpoint_service               Checkpoint service.
	 * @param ParticipationProgressBuilder   $participation_progress_builder   Participation progress builder.
	 * @param ValidationService              $validation_service               Validation service.
	 * @param ParticipationCheckpointService $participation_checkpoint_service Participation checkpoint service.
	 * @param EventService                   $event_service                    Event service.
	 */
	public function __construct(
		CheckpointService $checkpoint_service,
		ParticipationProgressBuilder $participation_progress_builder,
		ValidationService $validation_service,
		ParticipationCheckpointService $participation_checkpoint_service,
		EventService $event_service
	) {
		$this->checkpoint_service               = $checkpoint_service;
		$this->participation_progress_builder   = $participation_progress_builder;
		$this->validation_service               = $validation_service;
		$this->participation_checkpoint_service = $participation_checkpoint_service;
		$this->event_service                    = $event_service;
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
		$validation_result      = $this->validation_service->validate( $participation, $checkpoint, $participation_progress );

		$this->event_service->save_event( $this->build_scan_event( $participation, $checkpoint_post_id, $validation_result ) );

		if ( $validation_result->is_valid() ) {
			$this->participation_checkpoint_service->save_validated_checkpoint( (int) $participation->get_id(), $checkpoint_post_id );
		}

		return $validation_result;
	}

	/**
	 * Builds the Event associated with a scan attempt.
	 *
	 * @param Participation    $participation      Participation being validated.
	 * @param int              $checkpoint_post_id Checkpoint post identifier.
	 * @param ValidationResult $validation_result  Validation result.
	 * @return Event
	 */
	private function build_scan_event( Participation $participation, int $checkpoint_post_id, ValidationResult $validation_result ): Event {
		$event = new Event();
		$event->set_participation_id( $participation->get_id() );
		$event->set_checkpoint_id( $checkpoint_post_id );
		$event->set_event_type( EventType::QR_SCAN );
		$event->set_result( $this->resolve_event_result( $validation_result ) );
		$event->set_ip_address( null );
		$event->set_user_agent( null );

		return $event;
	}

	/**
	 * Resolves the Event result from a Validation result.
	 *
	 * @param ValidationResult $validation_result Validation result.
	 * @return string
	 */
	private function resolve_event_result( ValidationResult $validation_result ): string {
		if ( $validation_result->is_valid() ) {
			return EventResult::ACCEPTED;
		}

		foreach ( $validation_result->get_failed_dependencies() as $violation ) {
			if ( DependencyType::AFTER === $violation->get_type() ) {
				return EventResult::AFTER_FAILED;
			}
		}

		return EventResult::BEFORE_FAILED;
	}
}
