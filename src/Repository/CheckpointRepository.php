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

	public function find_by_post_id( int $post_id ): ?Checkpoint {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare( "SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} WHERE post_id = %d", $post_id );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$checkpoint = new Checkpoint();
		$checkpoint->set_post_id( (int) $row['post_id'] );
		$checkpoint->set_path_id( (int) $row['path_id'] );
		$checkpoint->set_group_id( null === $row['group_id'] ? null : (int) $row['group_id'] );
		$checkpoint->set_token( (string) $row['token'] );
		$checkpoint->set_created_at( (string) $row['created_at'] );
		$checkpoint->set_updated_at( (string) $row['updated_at'] );

		return $checkpoint;
	}

	public function save_path( Checkpoint $checkpoint ): void {
		if ( null !== $this->find_by_post_id( $checkpoint->get_post_id() ) ) {
			$this->wpdb->update(
				$this->table_name,
				array(
					'path_id'  => $checkpoint->get_path_id(),
					'group_id' => $checkpoint->get_group_id(),
				),
				array( 'post_id' => $checkpoint->get_post_id() ),
				array( '%d', '%d' ),
				array( '%d' )
			);

			return;
		}

		$this->wpdb->insert(
			$this->table_name,
			array(
				'post_id'  => $checkpoint->get_post_id(),
				'path_id'  => $checkpoint->get_path_id(),
				'group_id' => $checkpoint->get_group_id(),
				'token'    => $this->generate_token(),
			),
			array( '%d', '%d', '%d', '%s' )
		);
	}

	private function generate_token(): string {
		do {
			$token = wp_generate_password( 16, false, false );
		} while ( $this->token_exists( $token ) );

		return $token;
	}

	private function token_exists( string $token ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare( "SELECT 1 FROM {$this->table_name} WHERE token = %s", $token );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$exists = $this->wpdb->get_var( $sql );

		return null !== $exists;
	}
}
