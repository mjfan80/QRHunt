<?php
/**
 * Participation progress model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the current validation state of a Participation.
 */
final class ParticipationProgress {

	/** @var array<int, int> */
	private $validated_checkpoint_ids;

	/** @var array<int, int> */
	private $completed_group_ids;

	/**
	 * Creates a Participation progress snapshot.
	 *
	 * @param array<int, int> $validated_checkpoint_ids Validated Checkpoint identifiers.
	 * @param array<int, int> $completed_group_ids      Completed Group identifiers.
	 */
	public function __construct( array $validated_checkpoint_ids = array(), array $completed_group_ids = array() ) {
		$this->validated_checkpoint_ids = array_values( array_map( 'absint', $validated_checkpoint_ids ) );
		$this->completed_group_ids      = array_values( array_map( 'absint', $completed_group_ids ) );
	}

	/**
	 * Gets the validated Checkpoint identifiers.
	 *
	 * @return array<int, int>
	 */
	public function get_validated_checkpoint_ids(): array {
		return $this->validated_checkpoint_ids;
	}

	/**
	 * Gets the completed Group identifiers.
	 *
	 * @return array<int, int>
	 */
	public function get_completed_group_ids(): array {
		return $this->completed_group_ids;
	}
}
