<?php
/**
 * Participation checkpoint repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Participation checkpoint state from the database.
 */
final class ParticipationCheckpointRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates a Participation checkpoint repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_participation_checkpoints';
	}

	/**
	 * Gets the validated Checkpoint identifiers for a Participation.
	 *
	 * @param int $participation_id Participation identifier.
	 * @return array<int, int>
	 */
	public function find_validated_checkpoint_ids_by_participation( int $participation_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_participation_checkpoints suffix.
		$sql = $this->wpdb->prepare(
			"SELECT checkpoint_id FROM {$this->table_name} WHERE participation_id = %d ORDER BY checkpoint_id ASC",
			$participation_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_col( $sql );

		return array_map( 'absint', $rows );
	}

	/**
	 * Saves a validated Checkpoint for a Participation.
	 *
	 * @param int $participation_id Participation identifier.
	 * @param int $checkpoint_id    Checkpoint identifier.
	 * @return void
	 */
	public function save( int $participation_id, int $checkpoint_id ): void {
		$this->wpdb->insert(
			$this->table_name,
			array(
				'participation_id' => $participation_id,
				'checkpoint_id'    => $checkpoint_id,
			),
			array( '%d', '%d' )
		);
	}
}
