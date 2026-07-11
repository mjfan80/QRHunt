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
}
