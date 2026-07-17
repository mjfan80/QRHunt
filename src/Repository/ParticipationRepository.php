<?php
/**
 * Participation repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Participations from the database.
 */
final class ParticipationRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates a Participation repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_participations';
	}

	/**
	 * Gets all Participations.
	 *
	 * @return array<int, Participation>
	 */
	public function find_all(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		$rows = $this->wpdb->get_results(
			"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} ORDER BY id ASC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $this->hydrate_participations( $rows );
	}

	/**
	 * Finds Participations matching optional admin filters.
	 *
	 * @param int    $path_id Path identifier, or 0 for all Paths.
	 * @param int    $user_id User identifier, or 0 for all users.
	 * @param string $status  Participation status, or empty for all statuses.
	 * @return array<int, Participation>
	 */
	public function find_by_filters( int $path_id, int $user_id, string $status ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		if ( 0 !== $path_id && 0 !== $user_id && '' !== $status ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE path_id = %d AND user_id = %d AND status = %s ORDER BY updated_at DESC, id DESC",
				$path_id,
				$user_id,
				$status
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( 0 !== $path_id && 0 !== $user_id ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE path_id = %d AND user_id = %d ORDER BY updated_at DESC, id DESC",
				$path_id,
				$user_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( 0 !== $path_id && '' !== $status ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE path_id = %d AND status = %s ORDER BY updated_at DESC, id DESC",
				$path_id,
				$status
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( 0 !== $user_id && '' !== $status ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE user_id = %d AND status = %s ORDER BY updated_at DESC, id DESC",
				$user_id,
				$status
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( 0 !== $path_id ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE path_id = %d ORDER BY updated_at DESC, id DESC",
				$path_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( 0 !== $user_id ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE user_id = %d ORDER BY updated_at DESC, id DESC",
				$user_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		if ( '' !== $status ) {
			$sql  = $this->wpdb->prepare(
				"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE status = %s ORDER BY updated_at DESC, id DESC",
				$status
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared immediately above.
			$rows = $this->wpdb->get_results( $sql, ARRAY_A );

			return $this->hydrate_participations( $rows );
		}

		$rows = $this->wpdb->get_results(
			"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} ORDER BY updated_at DESC, id DESC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $this->hydrate_participations( $rows );
	}

	/**
	 * Gets a Participation by identifier.
	 *
	 * @param int $id Participation identifier.
	 * @return Participation|null
	 */
	public function find_by_id( int $id ): ?Participation {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE id = %d",
			$id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$participations = $this->hydrate_participations( array( $row ) );

		return $participations[0];
	}

	/**
	 * Gets a Participation by user and Path.
	 *
	 * @param int $user_id User identifier.
	 * @param int $path_id Path identifier.
	 * @return Participation|null
	 */
	public function find_by_user_and_path( int $user_id, int $path_id ): ?Participation {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE user_id = %d AND path_id = %d",
			$user_id,
			$path_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$participations = $this->hydrate_participations( array( $row ) );

		return $participations[0];
	}

	/**
	 * Gets Participations by user.
	 *
	 * @param int $user_id User identifier.
	 * @return array<int, Participation>
	 */
	public function find_by_user( int $user_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, user_id, path_id, status, started_at, finished_at, cancelled_at, created_at, updated_at FROM {$this->table_name} WHERE user_id = %d ORDER BY updated_at DESC, id DESC",
			$user_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_participations( $rows );
	}

	/**
	 * Counts Participations.
	 *
	 * @return int
	 */
	public function count_all(): int {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_participations suffix.
		$count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	/**
	 * Saves a Participation.
	 *
	 * @param Participation $participation Participation to save.
	 * @return void
	 */
	public function save( Participation $participation ): void {
		$status = $participation->get_status();
		$data   = array(
			'user_id'      => $participation->get_user_id(),
			'path_id'      => $participation->get_path_id(),
			'status'       => $status,
			'started_at'   => $participation->get_started_at(),
			'finished_at'  => $participation->get_finished_at(),
			'cancelled_at' => $participation->get_cancelled_at(),
			'updated_at'   => current_time( 'mysql' ),
		);

		if ( null === $participation->get_started_at() && ParticipationStatus::IN_PROGRESS === $status ) {
			$data['started_at'] = current_time( 'mysql' );
		}

		if (
			null === $participation->get_finished_at()
			&& in_array( $status, array( ParticipationStatus::FINISHED, ParticipationStatus::COMPLETED ), true )
		) {
			$data['finished_at'] = current_time( 'mysql' );
		}

		if ( null === $participation->get_cancelled_at() && ParticipationStatus::CANCELLED === $status ) {
			$data['cancelled_at'] = current_time( 'mysql' );
		}

		if ( null === $participation->get_id() ) {
			$this->wpdb->insert(
				$this->table_name,
				$data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$participation->set_id( 0 === (int) $this->wpdb->insert_id ? null : (int) $this->wpdb->insert_id );

			return;
		}

		$this->wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $participation->get_id() ),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Hydrates Participation models.
	 *
	 * @param array<int, array<string, mixed>> $rows Database rows.
	 * @return array<int, Participation>
	 */
	private function hydrate_participations( array $rows ): array {
		$participations = array();

		foreach ( $rows as $row ) {
			$participation = new Participation();
			$participation->set_id( (int) $row['id'] );
			$participation->set_user_id( (int) $row['user_id'] );
			$participation->set_path_id( (int) $row['path_id'] );
			$participation->set_status( (string) $row['status'] );
			$participation->set_started_at( null === $row['started_at'] ? null : (string) $row['started_at'] );
			$participation->set_finished_at( null === $row['finished_at'] ? null : (string) $row['finished_at'] );
			$participation->set_cancelled_at( null === $row['cancelled_at'] ? null : (string) $row['cancelled_at'] );
			$participation->set_created_at( (string) $row['created_at'] );
			$participation->set_updated_at( (string) $row['updated_at'] );
			$participations[] = $participation;
		}

		return $participations;
	}
}
