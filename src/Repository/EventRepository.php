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
