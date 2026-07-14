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
}
