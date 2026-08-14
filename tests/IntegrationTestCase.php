<?php
/**
 * Shared WordPress integration test support.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Database\DatabaseInstaller;
use QuestUno\Model\Checkpoint;
use QuestUno\Model\Participation;
use QuestUno\Model\Path;
use QuestUno\PathPostType;
use QuestUno\CheckpointPostType;
use QuestUno\Repository\CheckpointRepository;
use QuestUno\Repository\DependencyRepository;
use QuestUno\Repository\EventRepository;
use QuestUno\Repository\GroupRepository;
use QuestUno\Repository\ParticipationCheckpointRepository;
use QuestUno\Repository\ParticipationRepository;
use QuestUno\Repository\PathRepository;
use QuestUno\Service\CheckpointService;
use QuestUno\Service\EventService;
use QuestUno\Service\GroupService;
use QuestUno\Service\ParticipationCheckpointService;
use QuestUno\Service\ParticipationProgressBuilder;
use QuestUno\Service\ParticipationService;
use QuestUno\Service\PathService;
use QuestUno\Service\PathConfigurationValidator;
use QuestUno\Service\PrivacyService;
use QuestUno\Service\ValidationService;
use QuestUno\Service\ScanService;

/**
 * Provides a real WordPress database and QuestUno service graph for integration tests.
 */
abstract class IntegrationTestCase extends \WP_UnitTestCase {
	/**
	 * Creates the QuestUno tables in the dedicated test database.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		( new DatabaseInstaller() )->install();
	}

	/**
	 * Clears QuestUno state from the dedicated test database between tests.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $wpdb;
		$tables = array(
			'questuno_events',
			'questuno_participation_checkpoints',
			'questuno_participations',
			'questuno_dependencies',
			'questuno_checkpoints',
			'questuno_checkpoint_groups',
			'questuno_paths',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- This integration-test teardown clears only the fixed QuestUno tables in the dedicated test database.
		}
	}

	/**
	 * Creates the service graph used by the production plugin.
	 *
	 * @return array<string, object>
	 */
	protected function get_services(): array {
		global $wpdb;

		$dependency_repository             = new DependencyRepository( $wpdb );
		$group_repository                  = new GroupRepository( $wpdb );
		$checkpoint_repository             = new CheckpointRepository( $wpdb, $dependency_repository, $group_repository );
		$participation_checkpoint_repository = new ParticipationCheckpointRepository( $wpdb );
		$participation_repository          = new ParticipationRepository( $wpdb );
		$path_repository                   = new PathRepository( $wpdb );
		$event_repository                  = new EventRepository( $wpdb );
		$path_service                      = new PathService( $path_repository );
		$checkpoint_service                = new CheckpointService( $checkpoint_repository );
		$participation_service             = new ParticipationService( $participation_repository, $path_service );
		$progress_builder                  = new ParticipationProgressBuilder( $participation_checkpoint_repository, $checkpoint_repository, $group_repository );
		$event_service                     = new EventService( $event_repository );

		return array(
			'dependency_repository'       => $dependency_repository,
			'group_service'                => new GroupService( $group_repository ),
			'checkpoint_service'           => $checkpoint_service,
			'participation_service'        => $participation_service,
			'participation_checkpoint_service' => new ParticipationCheckpointService( $participation_checkpoint_repository ),
			'progress_builder'             => $progress_builder,
			'event_service'                => $event_service,
			'path_service'                 => $path_service,
			'path_configuration_validator' => new PathConfigurationValidator( $checkpoint_service, new \QuestUno\Service\DependencyService( $dependency_repository ), new GroupService( $group_repository ) ),
			'scan_service'                 => new ScanService( $checkpoint_service, $progress_builder, new ValidationService(), new ParticipationCheckpointService( $participation_checkpoint_repository ), $event_service, $path_service, $participation_service, new PrivacyService() ),
		);
	}

	/**
	 * Creates a Path and returns its persisted model.
	 *
	 * @param array<string, string|null> $values Path values.
	 * @return Path
	 */
	protected function create_path( array $values = array() ): Path {
		$services = $this->get_services();
		$post_id  = self::factory()->post->create(
			array(
				'post_type'   => PathPostType::POST_TYPE,
				'post_status' => $values['status'] ?? 'publish',
				'post_title'  => $values['name'] ?? 'Path',
			)
		);
		$path = new Path();
		$path->set_post_id( $post_id );
		$path->set_name( $values['name'] ?? 'Path' );
		$path->set_description( '' );
		$path->set_status( $values['status'] ?? 'publish' );
		$path->set_opening_date( $values['opening_date'] ?? null );
		$path->set_closing_date( $values['closing_date'] ?? null );
		$services['path_service']->save_path( $path );

		return $services['path_service']->get_path_by_post_id( $post_id );
	}

	/**
	 * Creates a Checkpoint for a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return Checkpoint
	 */
	protected function create_checkpoint( int $path_id ): Checkpoint {
		$services = $this->get_services();
		$post_id  = self::factory()->post->create(
			array(
				'post_type'   => CheckpointPostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		$checkpoint = new Checkpoint();
		$checkpoint->set_post_id( $post_id );
		$checkpoint->set_path_id( $path_id );
		$services['checkpoint_service']->save_path( $checkpoint );

		return $services['checkpoint_service']->get_checkpoint( $post_id );
	}

	/**
	 * Persists a Participation for a user and Path.
	 *
	 * @param int $user_id User identifier.
	 * @param int $path_id Path identifier.
	 * @return Participation
	 */
	protected function create_participation( int $user_id, int $path_id ): Participation {
		$services      = $this->get_services();
		$participation = new Participation();
		$participation->set_user_id( $user_id );
		$participation->set_path_id( $path_id );
		$participation->set_status( 'in_progress' );
		$services['participation_service']->save_participation( $participation );

		return $participation;
	}
}
