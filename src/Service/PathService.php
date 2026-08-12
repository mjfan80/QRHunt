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
	 * Determines whether a Path can be started or scanned at the current time.
	 *
	 * @param Path $path Path to evaluate.
	 * @return bool
	 */
	public function is_path_available_for_scan( Path $path ): bool {
		if ( ! in_array( $path->get_status(), array( 'publish', 'published' ), true ) ) {
			return false;
		}

		$current_time = current_time( 'mysql' );
		$opening_date = $path->get_opening_date();
		$closing_date = $path->get_closing_date();

		if ( null !== $opening_date && '' !== $opening_date && $current_time < $opening_date ) {
			return false;
		}

		if ( null !== $closing_date && '' !== $closing_date && $current_time > $closing_date ) {
			return false;
		}

		return true;
	}

	/**
	 * Counts Paths.
	 *
	 * @return int
	 */
	public function count_paths(): int {
		return $this->path_repository->count_all();
	}

	/**
	 * Counts Paths currently available for scans.
	 *
	 * @return int
	 */
	public function count_active_paths(): int {
		$count = 0;

		foreach ( $this->get_paths() as $path ) {
			if ( $this->is_path_available_for_scan( $path ) ) {
				++$count;
			}
		}

		return $count;
	}

	public function save_path( Path $path ): void {
		$this->path_repository->save( $path );
	}
}
