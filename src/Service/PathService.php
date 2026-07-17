<?php
/**
 * Path service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Path;
use QRHunt\Repository\PathRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Paths.
 */
final class PathService {

	/** @var PathRepository */
	private $path_repository;

	/**
	 * Creates a Path service.
	 *
	 * @param PathRepository $path_repository Path repository.
	 */
	public function __construct( PathRepository $path_repository ) {
		$this->path_repository = $path_repository;
	}

	/**
	 * Gets all Paths.
	 *
	 * @return array<int, Path>
	 */
	public function get_paths(): array {
		return $this->path_repository->find_all();
	}

	/**
	 * Gets a Path by identifier.
	 *
	 * @param int $id Path identifier.
	 * @return Path|null
	 */
	public function get_path( int $id ): ?Path {
		return $this->path_repository->find_by_id( $id );
	}

	/**
	 * Gets a Path by WordPress post identifier.
	 *
	 * @param int $post_id WordPress post identifier.
	 * @return Path|null
	 */
	public function get_path_by_post_id( int $post_id ): ?Path {
		return $this->path_repository->find_by_post_id( $post_id );
	}

	/**
	 * Counts Paths.
	 *
	 * @return int
	 */
	public function count_paths(): int {
		return $this->path_repository->count_all();
	}

	public function save_path( Path $path ): void {
		$this->path_repository->save( $path );
	}
}
