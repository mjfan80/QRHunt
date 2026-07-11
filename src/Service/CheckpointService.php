<?php
/**
 * Checkpoint service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Checkpoint;
use QRHunt\Repository\CheckpointRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Checkpoints.
 */
final class CheckpointService {

	/** @var CheckpointRepository */
	private $checkpoint_repository;

	/**
	 * Creates a Checkpoint service.
	 *
	 * @param CheckpointRepository $checkpoint_repository Checkpoint repository.
	 */
	public function __construct( CheckpointRepository $checkpoint_repository ) {
		$this->checkpoint_repository = $checkpoint_repository;
	}

	/**
	 * Gets all Checkpoints.
	 *
	 * @return array<int, Checkpoint>
	 */
	public function get_checkpoints(): array {
		return $this->checkpoint_repository->find_all();
	}
}
