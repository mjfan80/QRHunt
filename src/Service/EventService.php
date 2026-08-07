<?php
/**
 * Event service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Event;
use QRHunt\Repository\EventRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides access to Events.
 */
final class EventService {

	/** @var EventRepository */
	private $event_repository;

	/**
	 * Creates an Event service.
	 *
	 * @param EventRepository $event_repository Event repository.
	 */
	public function __construct( EventRepository $event_repository ) {
		$this->event_repository = $event_repository;
	}

	/**
	 * Saves an Event.
	 *
	 * @param Event $event Event to save.
	 * @return void
	 */
	public function save_event( Event $event ): void {
		$this->event_repository->save( $event );
	}

	/**
	 * Gets recent Events.
	 *
	 * @param int $limit Maximum number of events.
	 * @return array<int, Event>
	 */
	public function get_recent_events( int $limit ): array {
		return $this->event_repository->find_recent( $limit );
	}

	/**
	 * Gets Events for a Participation.
	 *
	 * @param int $participation_id Participation identifier.
	 * @return array<int, Event>
	 */
	public function get_events_by_participation( int $participation_id ): array {
		return $this->event_repository->find_by_participation( $participation_id );
	}

	/**
	 * Gets Events matching admin filters.
	 *
	 * @param int    $path_id       Path identifier, or 0 for all Paths.
	 * @param int    $user_id       User identifier, or 0 for all users.
	 * @param int    $checkpoint_id Checkpoint identifier, or 0 for all Checkpoints.
	 * @param string $result        Event result, or empty for all results.
	 * @param string $date_from     Start date in Y-m-d format, or empty.
	 * @param string $date_to       End date in Y-m-d format, or empty.
	 * @return array<int, Event>
	 */
	public function get_events_by_filters( int $path_id, int $user_id, int $checkpoint_id, string $result, string $date_from, string $date_to ): array {
		return $this->event_repository->find_by_filters( $path_id, $user_id, $checkpoint_id, $result, $date_from, $date_to );
	}
}
