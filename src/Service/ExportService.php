<?php
/**
 * Export service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use QRHunt\Model\EventResult;
use QRHunt\Model\ParticipationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Builds export datasets from domain services.
 */
final class ExportService {

	/** @var ParticipationService */
	private $participation_service;

	/** @var EventService */
	private $event_service;

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var ParticipationProgressBuilder */
	private $participation_progress_builder;

	/**
	 * Creates an export service.
	 *
	 * @param ParticipationService         $participation_service          Participation service.
	 * @param EventService                 $event_service                  Event service.
	 * @param PathService                  $path_service                   Path service.
	 * @param CheckpointService            $checkpoint_service             Checkpoint service.
	 * @param ParticipationProgressBuilder $participation_progress_builder Participation progress builder.
	 */
	public function __construct(
		ParticipationService $participation_service,
		EventService $event_service,
		PathService $path_service,
		CheckpointService $checkpoint_service,
		ParticipationProgressBuilder $participation_progress_builder
	) {
		$this->participation_service          = $participation_service;
		$this->event_service                  = $event_service;
		$this->path_service                   = $path_service;
		$this->checkpoint_service             = $checkpoint_service;
		$this->participation_progress_builder = $participation_progress_builder;
	}

	/**
	 * Gets Participation export rows.
	 *
	 * @param int    $path_id Path identifier, or 0 for all Paths.
	 * @param int    $user_id User identifier, or 0 for all users.
	 * @param string $status  Participation status, or empty for all statuses.
	 * @return array{headers:array<int,string>,rows:array<int,array<int,string>>}
	 */
	public function get_participation_export( int $path_id, int $user_id, string $status ): array {
		$paths          = $this->get_path_names();
		$participations = $this->participation_service->get_participations_by_filters( $path_id, $user_id, $status );
		$rows           = array();

		foreach ( $participations as $participation ) {
			$user     = get_userdata( (int) $participation->get_user_id() );
			$progress = $this->participation_progress_builder->build( $participation );

			$rows[] = array(
				(string) $participation->get_id(),
				(string) $participation->get_user_id(),
				false === $user ? '' : (string) $user->display_name,
				false === $user ? '' : (string) $user->user_email,
				(string) $participation->get_path_id(),
				$paths[ (int) $participation->get_path_id() ] ?? '',
				(string) $participation->get_status(),
				$this->format_export_datetime( $participation->get_started_at() ),
				$this->format_export_datetime( $participation->get_finished_at() ),
				$this->format_export_datetime( $participation->get_cancelled_at() ),
				$this->format_export_datetime( $participation->get_created_at() ),
				$this->format_export_datetime( $participation->get_updated_at() ),
				(string) count( $progress->get_validated_checkpoint_ids() ),
			);
		}

		return array(
			'headers' => array(
				'participation_id',
				'user_id',
				'user_display_name',
				'user_email',
				'path_id',
				'path_name',
				'status',
				'started_at',
				'finished_at',
				'cancelled_at',
				'created_at',
				'updated_at',
				'validated_checkpoints',
			),
			'rows'    => $rows,
		);
	}

	/**
	 * Gets Event export rows.
	 *
	 * @param int    $path_id       Path identifier, or 0 for all Paths.
	 * @param int    $user_id       User identifier, or 0 for all users.
	 * @param int    $checkpoint_id Checkpoint identifier, or 0 for all Checkpoints.
	 * @param string $result        Event result, or empty for all results.
	 * @param string $date_from     Start date in Y-m-d format, or empty.
	 * @param string $date_to       End date in Y-m-d format, or empty.
	 * @return array{headers:array<int,string>,rows:array<int,array<int,string>>}
	 */
	public function get_event_export( int $path_id, int $user_id, int $checkpoint_id, string $result, string $date_from, string $date_to ): array {
		$paths          = $this->get_path_names();
		$participations = $this->get_participations_by_id();
		$events         = $this->event_service->get_events_by_filters( $path_id, $user_id, $checkpoint_id, $result, $date_from, $date_to );
		$rows           = array();

		foreach ( $events as $event ) {
			$participation = $participations[ (int) $event->get_participation_id() ] ?? null;
			$user          = null === $participation ? false : get_userdata( (int) $participation->get_user_id() );
			$event_path_id = null === $participation ? 0 : (int) $participation->get_path_id();
			$checkpoint    = null === $event->get_checkpoint_id() ? 0 : (int) $event->get_checkpoint_id();

			$rows[] = array(
				(string) $event->get_id(),
				(string) $event->get_participation_id(),
				null === $participation ? '' : (string) $participation->get_user_id(),
				false === $user ? '' : (string) $user->display_name,
				false === $user ? '' : (string) $user->user_email,
				0 === $event_path_id ? '' : (string) $event_path_id,
				0 === $event_path_id ? '' : ( $paths[ $event_path_id ] ?? '' ),
				0 === $checkpoint ? '' : (string) $checkpoint,
				0 === $checkpoint ? '' : $this->checkpoint_service->get_checkpoint_title( $checkpoint ),
				(string) $event->get_event_type(),
				(string) $event->get_result(),
				(string) $event->get_ip_address(),
				(string) $event->get_user_agent(),
				$this->format_export_datetime( $event->get_created_at() ),
			);
		}

		return array(
			'headers' => array(
				'event_id',
				'participation_id',
				'user_id',
				'user_display_name',
				'user_email',
				'path_id',
				'path_name',
				'checkpoint_id',
				'checkpoint_title',
				'event_type',
				'result',
				'ip_address',
				'user_agent',
				'created_at',
			),
			'rows'    => $rows,
		);
	}

	/**
	 * Gets Path statistics export rows.
	 *
	 * @param int $path_id Path identifier, or 0 for all Paths.
	 * @return array{headers:array<int,string>,rows:array<int,array<int,string>>}
	 */
	public function get_path_statistics_export( int $path_id ): array {
		$paths = 0 === $path_id ? $this->path_service->get_paths() : array_filter(
			array( $this->path_service->get_path( $path_id ) )
		);
		$rows  = array();

		foreach ( $paths as $path ) {
			$current_path_id = (int) $path->get_id();
			$participations  = $this->participation_service->get_participations_by_filters( $current_path_id, 0, '' );
			$events          = $this->event_service->get_events_by_filters( $current_path_id, 0, 0, '', '', '' );
			$event_counts    = $this->count_events_by_result( $events );

			$rows[] = array(
				(string) $current_path_id,
				(string) $path->get_name(),
				(string) count( $participations ),
				(string) $this->count_participations_by_status( $participations, ParticipationStatus::IN_PROGRESS ),
				(string) $this->count_participations_by_status( $participations, ParticipationStatus::FINISHED ),
				(string) $this->count_participations_by_status( $participations, ParticipationStatus::COMPLETED ),
				(string) $this->count_participations_by_status( $participations, ParticipationStatus::CANCELLED ),
				(string) count( $events ),
				(string) ( $event_counts[ EventResult::ACCEPTED ] ?? 0 ),
				(string) $this->count_invalid_events( $event_counts ),
				(string) ( $event_counts[ EventResult::DUPLICATE ] ?? 0 ),
			);
		}

		return array(
			'headers' => array(
				'path_id',
				'path_name',
				'participations_total',
				'participations_in_progress',
				'participations_finished',
				'participations_completed',
				'participations_cancelled',
				'events_total',
				'events_accepted',
				'events_invalid',
				'events_duplicate',
			),
			'rows'    => $rows,
		);
	}

	/**
	 * Gets path names indexed by identifier.
	 *
	 * @return array<int,string>
	 */
	private function get_path_names(): array {
		$names = array();

		foreach ( $this->path_service->get_paths() as $path ) {
			$names[ (int) $path->get_id() ] = (string) $path->get_name();
		}

		return $names;
	}

	/**
	 * Gets Participations indexed by identifier.
	 *
	 * @return array<int,\QRHunt\Model\Participation>
	 */
	private function get_participations_by_id(): array {
		$participations_by_id = array();

		foreach ( $this->participation_service->get_participations() as $participation ) {
			$participations_by_id[ (int) $participation->get_id() ] = $participation;
		}

		return $participations_by_id;
	}

	/**
	 * Counts Participations with a specific status.
	 *
	 * @param array<int,\QRHunt\Model\Participation> $participations Participations.
	 * @param string                                 $status         Status.
	 * @return int
	 */
	private function count_participations_by_status( array $participations, string $status ): int {
		$count = 0;

		foreach ( $participations as $participation ) {
			if ( $status === $participation->get_status() ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Counts Events by result.
	 *
	 * @param array<int,\QRHunt\Model\Event> $events Events.
	 * @return array<string,int>
	 */
	private function count_events_by_result( array $events ): array {
		$counts = array();

		foreach ( $events as $event ) {
			$result            = (string) $event->get_result();
			$counts[ $result ] = ( $counts[ $result ] ?? 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * Counts invalid Events, excluding accepted and duplicate scans.
	 *
	 * @param array<string,int> $event_counts Event counts indexed by result.
	 * @return int
	 */
	private function count_invalid_events( array $event_counts ): int {
		$count = 0;

		foreach ( $event_counts as $result => $result_count ) {
			if ( ! in_array( $result, array( EventResult::ACCEPTED, EventResult::DUPLICATE ), true ) ) {
				$count += $result_count;
			}
		}

		return $count;
	}

	/**
	 * Formats a stored datetime for CSV export.
	 *
	 * @param string|null $datetime Stored datetime.
	 * @return string
	 */
	private function format_export_datetime( ?string $datetime ): string {
		return null === $datetime ? '' : $datetime;
	}
}
