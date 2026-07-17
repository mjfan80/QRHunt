<?php
/**
 * Scan service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\DependencyType;
use QRHunt\Model\Event;
use QRHunt\Model\EventResult;
use QRHunt\Model\EventType;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationProgress;
use QRHunt\Model\ParticipationStatus;
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

	/** @var PathService */
	private $path_service;

	/** @var ParticipationService */
	private $participation_service;

	/**
	 * Creates a scan service.
	 *
	 * @param CheckpointService              $checkpoint_service               Checkpoint service.
	 * @param ParticipationProgressBuilder   $participation_progress_builder   Participation progress builder.
	 * @param ValidationService              $validation_service               Validation service.
	 * @param ParticipationCheckpointService $participation_checkpoint_service Participation checkpoint service.
	 * @param EventService                   $event_service                    Event service.
	 * @param PathService                    $path_service                     Path service.
	 * @param ParticipationService           $participation_service            Participation service.
	 */
	public function __construct(
		CheckpointService $checkpoint_service,
		ParticipationProgressBuilder $participation_progress_builder,
		ValidationService $validation_service,
		ParticipationCheckpointService $participation_checkpoint_service,
		EventService $event_service,
		PathService $path_service,
		ParticipationService $participation_service
	) {
		$this->checkpoint_service               = $checkpoint_service;
		$this->participation_progress_builder   = $participation_progress_builder;
		$this->validation_service               = $validation_service;
		$this->participation_checkpoint_service = $participation_checkpoint_service;
		$this->event_service                    = $event_service;
		$this->path_service                     = $path_service;
		$this->participation_service            = $participation_service;
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

		return $this->process_scan( $participation, $checkpoint );
	}

	/**
	 * Handles a scan for a resolved Checkpoint and Participation.
	 *
	 * @param Participation $participation Participation being validated.
	 * @param Checkpoint    $checkpoint    Resolved Checkpoint.
	 * @return ValidationResult
	 */
	public function scan_checkpoint( Participation $participation, Checkpoint $checkpoint ): ValidationResult {
		if ( null === $checkpoint->get_post_id() ) {
			throw new \InvalidArgumentException( 'Checkpoint not found.' );
		}

		return $this->process_scan( $participation, $checkpoint );
	}

	/**
	 * Processes a scan for a resolved Checkpoint and Participation.
	 *
	 * @param Participation $participation Participation being validated.
	 * @param Checkpoint    $checkpoint    Resolved Checkpoint.
	 * @return ValidationResult
	 */
	private function process_scan( Participation $participation, Checkpoint $checkpoint ): ValidationResult {
		if (
			in_array(
				$participation->get_status(),
				array( ParticipationStatus::CANCELLED, ParticipationStatus::FINISHED, ParticipationStatus::COMPLETED ),
				true
			)
		) {
			return ValidationResult::create_invalid( array() );
		}

		$checkpoint_post_id      = (int) $checkpoint->get_post_id();
		$participation_progress = $this->participation_progress_builder->build( $participation );
		$validation_result      = $this->validation_service->validate( $participation, $checkpoint, $participation_progress );

		$this->event_service->save_event( $this->build_scan_event( $participation, $checkpoint_post_id, $validation_result ) );

		if ( $validation_result->is_valid() ) {
			$this->participation_checkpoint_service->save_validated_checkpoint( (int) $participation->get_id(), $checkpoint_post_id );
			$this->update_participation_status( $participation, $checkpoint_post_id, $participation_progress );
		}

		return $validation_result;
	}

	/**
	 * Handles a scan identified by token and Participation identifier.
	 *
	 * @param string $token            Checkpoint token.
	 * @param int    $participation_id Participation identifier.
	 * @return ValidationResult
	 */
	public function scan( string $token, int $participation_id ): ValidationResult {
		$participation = $this->participation_service->get_participation( $participation_id );

		if ( null === $participation ) {
			throw new \InvalidArgumentException( 'Participation not found.' );
		}

		$checkpoint = $this->checkpoint_service->get_checkpoint_by_token_with_dependencies( $token );

		if ( null === $checkpoint || null === $checkpoint->get_post_id() ) {
			throw new \InvalidArgumentException( 'Checkpoint not found.' );
		}

		return $this->scan_checkpoint( $participation, $checkpoint );
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

	/**
	 * Updates the Participation status after a successful validation.
	 *
	 * @param Participation         $participation          Participation being updated.
	 * @param int                   $checkpoint_post_id     Validated Checkpoint post identifier.
	 * @param ParticipationProgress $participation_progress Participation progress before the current validation.
	 * @return void
	 */
	private function update_participation_status( Participation $participation, int $checkpoint_post_id, ParticipationProgress $participation_progress ): void {
		$path_id = $participation->get_path_id();

		if ( null === $path_id ) {
			return;
		}

		$path = $this->path_service->get_path( $path_id );

		if ( null === $path ) {
			return;
		}

		$validated_checkpoint_ids   = $participation_progress->get_validated_checkpoint_ids();
		$validated_checkpoint_ids[] = $checkpoint_post_id;
		$validated_checkpoint_ids   = array_values( array_unique( array_map( 'absint', $validated_checkpoint_ids ) ) );

		$all_checkpoint_ids = array();

		foreach ( $this->checkpoint_service->get_checkpoints_by_path( $path_id ) as $path_checkpoint ) {
			$all_checkpoint_ids[] = (int) $path_checkpoint->get_post_id();
		}

		if ( ! empty( $all_checkpoint_ids ) && ! array_diff( $all_checkpoint_ids, $validated_checkpoint_ids ) ) {
			$participation->set_status( ParticipationStatus::COMPLETED );
			$this->participation_service->save_participation( $participation );
			return;
		}

		if (
			$checkpoint_post_id === $path->get_finish_checkpoint_id() &&
			ParticipationStatus::FINISHED !== $participation->get_status() &&
			ParticipationStatus::COMPLETED !== $participation->get_status()
		) {
			$participation->set_status( ParticipationStatus::FINISHED );
			$this->participation_service->save_participation( $participation );
		}
	}
}
