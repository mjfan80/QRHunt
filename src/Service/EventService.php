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
}
