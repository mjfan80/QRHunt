<?php
/**
 * Dashboard service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates data for the admin dashboard.
 */
final class DashboardService {

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var GroupService */
	private $group_service;

	/** @var ParticipationService */
	private $participation_service;

	/** @var EventService */
	private $event_service;

	/**
	 * Creates a dashboard service.
	 *
	 * @param PathService          $path_service          Path service.
	 * @param CheckpointService    $checkpoint_service    Checkpoint service.
	 * @param GroupService         $group_service         Group service.
	 * @param ParticipationService $participation_service Participation service.
	 * @param EventService         $event_service         Event service.
	 */
	public function __construct(
		PathService $path_service,
		CheckpointService $checkpoint_service,
		GroupService $group_service,
		ParticipationService $participation_service,
		EventService $event_service
	) {
		$this->path_service          = $path_service;
		$this->checkpoint_service    = $checkpoint_service;
		$this->group_service         = $group_service;
		$this->participation_service = $participation_service;
		$this->event_service         = $event_service;
	}

	/**
	 * Gets dashboard summary rows.
	 *
	 * @return array<int, array{label:string,value:int}>
	 */
	public function get_summary(): array {
		return array(
			array(
				'label' => __( 'Paths', 'qrhunt' ),
				'value' => $this->path_service->count_paths(),
			),
			array(
				'label' => __( 'Checkpoints', 'qrhunt' ),
				'value' => $this->checkpoint_service->count_checkpoints(),
			),
			array(
				'label' => __( 'Groups', 'qrhunt' ),
				'value' => $this->group_service->count_groups(),
			),
			array(
				'label' => __( 'Participations', 'qrhunt' ),
				'value' => $this->participation_service->count_participations(),
			),
		);
	}

	/**
	 * Gets recent events enriched for dashboard display.
	 *
	 * @param int $limit Maximum number of events.
	 * @return array<int, array{created_at:string,path_name:string,checkpoint_name:string,result:string}>
	 */
	public function get_recent_events( int $limit ): array {
		$events = array();

		foreach ( $this->event_service->get_recent_events( $limit ) as $event ) {
			$events[] = array(
				'created_at'      => $this->format_event_date( $event ),
				'path_name'       => $this->resolve_path_name( $event ),
				'checkpoint_name' => $this->resolve_checkpoint_name( $event ),
				'result'          => (string) $event->get_result(),
			);
		}

		return $events;
	}

	/**
	 * Formats the event date using WordPress settings.
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	private function format_event_date( Event $event ): string {
		$created_at = $event->get_created_at();

		if ( null === $created_at ) {
			return '';
		}

		$timestamp = strtotime( $created_at );

		if ( false === $timestamp ) {
			return $created_at;
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Resolves the path name for an event.
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	private function resolve_path_name( Event $event ): string {
		$participation_id = $event->get_participation_id();

		if ( null === $participation_id ) {
			return '';
		}

		$participation = $this->participation_service->get_participation( $participation_id );

		if ( null === $participation || null === $participation->get_path_id() ) {
			return '';
		}

		$path = $this->path_service->get_path( (int) $participation->get_path_id() );

		return null === $path ? '' : (string) $path->get_name();
	}

	/**
	 * Resolves the checkpoint name for an event.
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	private function resolve_checkpoint_name( Event $event ): string {
		$checkpoint_id = $event->get_checkpoint_id();

		if ( null === $checkpoint_id ) {
			return '';
		}

		return $this->checkpoint_service->get_checkpoint_title( $checkpoint_id );
	}
}
