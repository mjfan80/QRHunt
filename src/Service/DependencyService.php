<?php
/**
 * Dependency service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Dependency;
use QRHunt\Repository\DependencyRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Dependencies.
 */
final class DependencyService {

	/** @var DependencyRepository */
	private $dependency_repository;

	/**
	 * Creates a Dependency service.
	 *
	 * @param DependencyRepository $dependency_repository Dependency repository.
	 */
	public function __construct( DependencyRepository $dependency_repository ) {
		$this->dependency_repository = $dependency_repository;
	}

	/**
	 * Gets Dependencies for a Checkpoint.
	 *
	 * @param int $checkpoint_id Checkpoint identifier.
	 * @return array<int, Dependency>
	 */
	public function get_dependencies_by_checkpoint( int $checkpoint_id ): array {
		return $this->dependency_repository->find_by_checkpoint( $checkpoint_id );
	}

	/**
	 * Saves Dependencies for a Checkpoint.
	 *
	 * @param int                    $checkpoint_id Checkpoint identifier.
	 * @param array<int, Dependency> $dependencies  Dependencies to save.
	 * @return void
	 */
	public function save_dependencies( int $checkpoint_id, array $dependencies ): void {
		$this->dependency_repository->save_for_checkpoint( $checkpoint_id, $dependencies );
	}
}
