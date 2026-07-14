<?php
/**
 * Validation service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationProgress;
use QRHunt\Model\ValidationResult;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the public API of the Validation Engine.
 */
final class ValidationService {

	/**
	 * Evaluates whether a Checkpoint can be validated for a Participation.
	 *
	 * This initial milestone defines only the public API of the Validation Engine.
	 * The validation logic will be introduced in a subsequent implementation step.
	 *
	 * @param Participation        $participation         Participation being validated.
	 * @param Checkpoint           $checkpoint            Checkpoint to validate.
	 * @param ParticipationProgress $participation_state Current Participation state.
	 * @return ValidationResult
	 */
	public function validate( Participation $participation, Checkpoint $checkpoint, ParticipationProgress $participation_state ): ValidationResult {
		unset( $participation, $checkpoint, $participation_state );

		return ValidationResult::create_valid();
	}
}
