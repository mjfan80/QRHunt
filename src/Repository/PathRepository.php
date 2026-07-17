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
			$paths[] = $this->hydrate_path( $row );
		}

		return $paths;
	}

	/**
	 * Gets a Path by identifier.
	 *
	 * @param int $id Path identifier.
	 * @return Path|null
	 */
	public function find_by_id( int $id ): ?Path {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_paths suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, post_id, name, description, status, start_checkpoint_id, finish_checkpoint_id, opening_date, closing_date, created_at, updated_at FROM {$this->table_name} WHERE id = %d",
			$id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_path( $row );
	}

	/**
	 * Gets a Path by WordPress post identifier.
	 *
	 * @param int $post_id WordPress post identifier.
	 * @return Path|null
	 */
	public function find_by_post_id( int $post_id ): ?Path {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_paths suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, post_id, name, description, status, start_checkpoint_id, finish_checkpoint_id, opening_date, closing_date, created_at, updated_at FROM {$this->table_name} WHERE post_id = %d",
			$post_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_path( $row );
	}

	/**
	 * Counts Paths.
	 *
	 * @return int
	 */
	public function count_all(): int {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_paths suffix.
		$count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	public function save( Path $path ): void {
		$data = array(
			'post_id'              => $path->get_post_id(),
			'name'                 => $path->get_name(),
			'description'          => $path->get_description(),
			'status'               => $path->get_status(),
			'start_checkpoint_id'  => $path->get_start_checkpoint_id(),
			'finish_checkpoint_id' => $path->get_finish_checkpoint_id(),
		);

		if ( null !== $this->find_by_post_id( (int) $path->get_post_id() ) ) {
			$data['updated_at'] = current_time( 'mysql' );

			$this->wpdb->update(
				$this->table_name,
				$data,
				array( 'post_id' => $path->get_post_id() ),
				array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);

			return;
		}

		$this->wpdb->insert(
			$this->table_name,
			$data,
			array( '%d', '%s', '%s', '%s', '%d', '%d' )
		);
	}

	/**
	 * Hydrates a Path model from a database row.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return Path
	 */
	private function hydrate_path( array $row ): Path {
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

		return $path;
	}
}
