<?php
/**
 * Dependency repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Dependency;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Dependencies from the database.
 */
final class DependencyRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates a Dependency repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_dependencies';
	}

	/**
	 * Gets all Dependencies for a Checkpoint.
	 *
	 * @param int $checkpoint_id Checkpoint identifier.
	 * @return array<int, Dependency>
	 */
	public function find_by_checkpoint( int $checkpoint_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_dependencies suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, checkpoint_id, type, target_type, target_id, created_at, updated_at FROM {$this->table_name} WHERE checkpoint_id = %d ORDER BY id ASC",
			$checkpoint_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_dependencies( $rows );
	}

	/**
	 * Replaces all Dependencies for a Checkpoint.
	 *
	 * @param int                    $checkpoint_id Checkpoint identifier.
	 * @param array<int, Dependency> $dependencies  Dependencies to save.
	 * @return void
	 */
	public function save_for_checkpoint( int $checkpoint_id, array $dependencies ): void {
		$this->delete_by_checkpoint( $checkpoint_id );

		foreach ( $dependencies as $dependency ) {
			$this->wpdb->insert(
				$this->table_name,
				array(
					'checkpoint_id' => $checkpoint_id,
					'type'          => $dependency->get_type(),
					'target_type'   => $dependency->get_target_type(),
					'target_id'     => $dependency->get_target_id(),
				),
				array( '%d', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Deletes all Dependencies for a Checkpoint.
	 *
	 * @param int $checkpoint_id Checkpoint identifier.
	 * @return void
	 */
	public function delete_by_checkpoint( int $checkpoint_id ): void {
		$this->wpdb->delete(
			$this->table_name,
			array( 'checkpoint_id' => $checkpoint_id ),
			array( '%d' )
		);
	}

	/**
	 * Hydrates Dependency models.
	 *
	 * @param array<int, array<string, mixed>> $rows Database rows.
	 * @return array<int, Dependency>
	 */
	private function hydrate_dependencies( array $rows ): array {
		$dependencies = array();

		foreach ( $rows as $row ) {
			$dependency = new Dependency();
			$dependency->set_id( (int) $row['id'] );
			$dependency->set_checkpoint_id( (int) $row['checkpoint_id'] );
			$dependency->set_type( (string) $row['type'] );
			$dependency->set_target_type( (string) $row['target_type'] );
			$dependency->set_target_id( (int) $row['target_id'] );
			$dependency->set_created_at( (string) $row['created_at'] );
			$dependency->set_updated_at( (string) $row['updated_at'] );
			$dependencies[] = $dependency;
		}

		return $dependencies;
	}
}
