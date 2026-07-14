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

	public function get_checkpoint( int $post_id ): ?Checkpoint {
		return $this->checkpoint_repository->find_by_post_id( $post_id );
	}

	/**
	 * Gets a Checkpoint with its Dependencies.
	 *
	 * @param int $post_id Checkpoint post identifier.
	 * @return Checkpoint|null
	 */
	public function get_checkpoint_with_dependencies( int $post_id ): ?Checkpoint {
		return $this->checkpoint_repository->find_by_post_id_with_dependencies( $post_id );
	}

	/**
	 * Gets Checkpoints for a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return array<int, Checkpoint>
	 */
	public function get_checkpoints_by_path( int $path_id ): array {
		return $this->checkpoint_repository->find_by_path( $path_id );
	}

	public function save_path( Checkpoint $checkpoint ): void {
		$this->checkpoint_repository->save_path( $checkpoint );
	}
}
