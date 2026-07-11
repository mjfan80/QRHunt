<?php
/**
 * Path repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Path;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Paths from the database.
 */
final class PathRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates a Path repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_paths';
	}

	/**
	 * Gets all Paths ordered by name.
	 *
	 * @return array<int, Path>
	 */
	public function find_all(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_paths suffix.
		$rows = $this->wpdb->get_results(
			"SELECT id, post_id, name, description, status, start_checkpoint_id, finish_checkpoint_id, opening_date, closing_date, created_at, updated_at FROM {$this->table_name} ORDER BY name ASC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$paths = array();

		foreach ( $rows as $row ) {
			$path = new Path();
			$path->set_id( (int) $row['id'] );
			$path->set_post_id( (int) $row['post_id'] );
			$path->set_name( (string) $row['name'] );
			$path->set_description( null === $row['description'] ? null : (string) $row['description'] );
			$path->set_status( (string) $row['status'] );
			$path->set_start_checkpoint_id( null === $row['start_checkpoint_id'] ? null : (int) $row['start_checkpoint_id'] );
			$path->set_finish_checkpoint_id( null === $row['finish_checkpoint_id'] ? null : (int) $row['finish_checkpoint_id'] );
			$path->set_opening_date( null === $row['opening_date'] ? null : (string) $row['opening_date'] );
			$path->set_closing_date( null === $row['closing_date'] ? null : (string) $row['closing_date'] );
			$path->set_created_at( (string) $row['created_at'] );
			$path->set_updated_at( (string) $row['updated_at'] );
			$paths[] = $path;
		}

		return $paths;
	}

	public function save( Path $path ): void {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_paths suffix.
		$sql = $this->wpdb->prepare( "INSERT INTO {$this->table_name} (post_id, name, description, status) VALUES (%d, %s, %s, %s) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), status = VALUES(status), updated_at = CURRENT_TIMESTAMP", $path->get_post_id(), $path->get_name(), $path->get_description(), $path->get_status() );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$this->wpdb->query( $sql );
	}
}
