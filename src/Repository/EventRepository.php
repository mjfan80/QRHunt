<?php
/**
 * Event repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Stores Events in the database.
 */
final class EventRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/**
	 * Creates an Event repository.
	 *
	 * @param \wpdb $wpdb WordPress database access object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_events';
	}

	/**
	 * Saves an Event.
	 *
	 * @param Event $event Event to save.
	 * @return void
	 */
	public function save( Event $event ): void {
		$this->wpdb->insert(
			$this->table_name,
			array(
				'participation_id' => $event->get_participation_id(),
				'checkpoint_id'    => $event->get_checkpoint_id(),
				'event_type'       => $event->get_event_type(),
				'result'           => $event->get_result(),
				'ip_address'       => $event->get_ip_address(),
				'user_agent'       => $event->get_user_agent(),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Gets recent Events.
	 *
	 * @param int $limit Maximum number of events.
	 * @return array<int, Event>
	 */
	public function find_recent( int $limit ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_events suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, participation_id, checkpoint_id, event_type, result, ip_address, user_agent, created_at FROM {$this->table_name} ORDER BY created_at DESC, id DESC LIMIT %d",
			$limit
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_events( $rows );
	}

	/**
	 * Gets Events for a Participation.
	 *
	 * @param int $participation_id Participation identifier.
	 * @return array<int, Event>
	 */
	public function find_by_participation( int $participation_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_events suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, participation_id, checkpoint_id, event_type, result, ip_address, user_agent, created_at FROM {$this->table_name} WHERE participation_id = %d ORDER BY created_at DESC, id DESC",
			$participation_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_events( $rows );
	}

	/**
	 * Gets Events matching optional admin filters.
	 *
	 * @param int    $path_id       Path identifier, or 0 for all Paths.
	 * @param int    $user_id       User identifier, or 0 for all users.
	 * @param int    $checkpoint_id Checkpoint identifier, or 0 for all Checkpoints.
	 * @param string $result        Event result, or empty for all results.
	 * @param string $date_from     Start date in Y-m-d format, or empty.
	 * @param string $date_to       End date in Y-m-d format, or empty.
	 * @return array<int, Event>
	 */
	public function find_by_filters( int $path_id, int $user_id, int $checkpoint_id, string $result, string $date_from, string $date_to ): array {
		$where  = array();
		$values = array();

		if ( 0 !== $path_id ) {
			$where[]  = 'p.path_id = %d';
			$values[] = $path_id;
		}

		if ( 0 !== $user_id ) {
			$where[]  = 'p.user_id = %d';
			$values[] = $user_id;
		}

		if ( 0 !== $checkpoint_id ) {
			$where[]  = 'e.checkpoint_id = %d';
			$values[] = $checkpoint_id;
		}

		if ( '' !== $result ) {
			$where[]  = 'e.result = %s';
			$values[] = $result;
		}

		if ( '' !== $date_from ) {
			$where[]  = 'e.created_at >= %s';
			$values[] = $date_from . ' 00:00:00';
		}

		if ( '' !== $date_to ) {
			$where[]  = 'e.created_at <= %s';
			$values[] = $date_to . ' 23:59:59';
		}

		$where_sql = empty( $where ) ? '' : ' WHERE ' . implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names contain only the WordPress database prefix and fixed QRHunt suffixes; WHERE fragments are fixed strings selected above.
		$sql = "SELECT e.id, e.participation_id, e.checkpoint_id, e.event_type, e.result, e.ip_address, e.user_agent, e.created_at FROM {$this->table_name} e INNER JOIN {$this->wpdb->prefix}qrhunt_participations p ON p.id = e.participation_id{$where_sql} ORDER BY e.created_at DESC, e.id DESC";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql contains only fixed SQL fragments and is prepared with the collected values.
			$sql = $this->wpdb->prepare( $sql, $values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is either a fixed query or prepared immediately above.
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_events( $rows );
	}

	/**
	 * Hydrates Event models.
	 *
	 * @param array<int, array<string, mixed>> $rows Database rows.
	 * @return array<int, Event>
	 */
	private function hydrate_events( array $rows ): array {
		$events = array();

		foreach ( $rows as $row ) {
			$event = new Event();
			$event->set_id( (int) $row['id'] );
			$event->set_participation_id( (int) $row['participation_id'] );
			$event->set_checkpoint_id( (int) $row['checkpoint_id'] );
			$event->set_event_type( (string) $row['event_type'] );
			$event->set_result( (string) $row['result'] );
			$event->set_ip_address( null === $row['ip_address'] ? null : (string) $row['ip_address'] );
			$event->set_user_agent( null === $row['user_agent'] ? null : (string) $row['user_agent'] );
			$event->set_created_at( (string) $row['created_at'] );
			$events[] = $event;
		}

		return $events;
	}
}
