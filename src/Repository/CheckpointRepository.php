<?php
/**
 * Checkpoint repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Checkpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Checkpoints from the database.
 */
final class CheckpointRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates a Checkpoint repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_checkpoints';
	}

	/**
	 * Gets all Checkpoints ordered by post identifier.
	 *
	 * @return array<int, Checkpoint>
	 */
	public function find_all(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$rows = $this->wpdb->get_results(
			"SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} ORDER BY post_id ASC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$checkpoints = array();

		foreach ( $rows as $row ) {
			$checkpoint = new Checkpoint();
			$checkpoint->set_post_id( (int) $row['post_id'] );
			$checkpoint->set_path_id( (int) $row['path_id'] );
			$checkpoint->set_group_id( null === $row['group_id'] ? null : (int) $row['group_id'] );
			$checkpoint->set_token( (string) $row['token'] );
			$checkpoint->set_created_at( (string) $row['created_at'] );
			$checkpoint->set_updated_at( (string) $row['updated_at'] );
			$checkpoints[] = $checkpoint;
		}

		return $checkpoints;
	}
}
