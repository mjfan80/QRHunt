<?php
/**
 * Plugin bootstrap.
 *
 * @package QRHunt
 */

namespace QRHunt;

use QRHunt\Controller\CheckpointController;
use QRHunt\Controller\DependencyController;
use QRHunt\Controller\GroupController;
use QRHunt\Controller\ParticipationController;
use QRHunt\Controller\PathController;
use QRHunt\Controller\ScanRestController;
use QRHunt\Repository\CheckpointRepository;
use QRHunt\Repository\DependencyRepository;
use QRHunt\Repository\EventRepository;
use QRHunt\Repository\GroupRepository;
use QRHunt\Repository\ParticipationCheckpointRepository;
use QRHunt\Repository\ParticipationRepository;
use QRHunt\Repository\PathRepository;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\DependencyService;
use QRHunt\Service\EventService;
use QRHunt\Service\GroupService;
use QRHunt\Service\ParticipationCheckpointService;
use QRHunt\Service\ParticipationProgressBuilder;
use QRHunt\Service\ParticipationService;
use QRHunt\Service\PathService;
use QRHunt\Service\ScanService;
use QRHunt\Service\ValidationService;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes the plugin integration with WordPress.
 */
final class Plugin {

	/** @var GroupController|null */
	private $group_controller;

	/** @var ParticipationController|null */
	private $participation_controller;

	/** @var PathController|null */
	private $path_controller;

	/** @var CheckpointController|null */
	private $checkpoint_controller;

	/** @var ScanRestController|null */
	private $scan_rest_controller;

	/** @var ScanService|null */
	private $scan_service;

	/** @var CheckpointService|null */
	private $checkpoint_service;

	/** @var DependencyService|null */
	private $dependency_service;

	/** @var GroupService|null */
	private $group_service;

	/** @var PathService|null */
	private $path_service;

	/** @var ParticipationService|null */
	private $participation_service;

	/** @var ParticipationCheckpointService|null */
	private $participation_checkpoint_service;

	/** @var ParticipationProgressBuilder|null */
	private $participation_progress_builder;

	/** @var EventService|null */
	private $event_service;

	/** @var ValidationService|null */
	private $validation_service;

	/** @var CheckpointRepository|null */
	private $checkpoint_repository;

	/** @var DependencyRepository|null */
	private $dependency_repository;

	/** @var GroupRepository|null */
	private $group_repository;

	/** @var ParticipationCheckpointRepository|null */
	private $participation_checkpoint_repository;

	/** @var ParticipationRepository|null */
	private $participation_repository;

	/** @var PathRepository|null */
	private $path_repository;

	/** @var EventRepository|null */
	private $event_repository;

	/**
	 * Registers WordPress hooks for the plugin.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'initialize' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'register_groups_page' ) );
		add_action( 'admin_menu', array( $this, 'register_participations_page' ) );
		add_action( 'admin_post_qrhunt_save_group', array( $this, 'save_group' ) );
		add_action( 'admin_post_qrhunt_delete_group', array( $this, 'delete_group' ) );
		add_action( 'admin_post_qrhunt_save_participation', array( $this, 'save_participation' ) );
		add_action( 'admin_post_qrhunt_delete_participation', array( $this, 'delete_participation' ) );
		add_action( 'save_post_qrhunt_path', array( $this, 'synchronize_path' ), 10, 2 );
		add_action( 'add_meta_boxes_qrhunt_checkpoint', array( $this, 'register_checkpoint_metabox' ) );
		add_action( 'save_post_qrhunt_checkpoint', array( $this, 'save_checkpoint_path' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Initializes plugin components.
	 *
	 * @return void
	 */
	public function initialize(): void {
	}

	/**
	 * Registers the plugin custom post types.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		$checkpoint_post_type = new CheckpointPostType();
		$checkpoint_post_type->register();

		$path_post_type = new PathPostType();
		$path_post_type->register();
	}

	/**
	 * Registers the plugin administration menu.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$admin_menu = new AdminMenu();
		$admin_menu->register();
	}

	/**
	 * Registers the Groups admin page.
	 *
	 * @return void
	 */
	public function register_groups_page(): void {
		$this->get_group_controller()->register_page();
	}

	/**
	 * Saves a Group.
	 *
	 * @return void
	 */
	public function save_group(): void {
		$this->get_group_controller()->save();
	}

	/**
	 * Deletes a Group.
	 *
	 * @return void
	 */
	public function delete_group(): void {
		$this->get_group_controller()->delete();
	}

	/**
	 * Registers the Participations admin page.
	 *
	 * @return void
	 */
	public function register_participations_page(): void {
		$this->get_participation_controller()->register_page();
	}

	/**
	 * Saves a Participation.
	 *
	 * @return void
	 */
	public function save_participation(): void {
		$this->get_participation_controller()->save();
	}

	/**
	 * Deletes a Participation.
	 *
	 * @return void
	 */
	public function delete_participation(): void {
		$this->get_participation_controller()->delete();
	}

	/**
	 * Synchronizes a Path after post save.
	 *
	 * @param int      $post_id Post identifier.
	 * @param \WP_Post $post    WordPress post object.
	 * @return void
	 */
	public function synchronize_path( int $post_id, \WP_Post $post ): void {
		$this->get_path_controller()->save( $post_id, $post );
	}

	/**
	 * Registers the Checkpoint metabox.
	 *
	 * @return void
	 */
	public function register_checkpoint_metabox(): void {
		$this->get_checkpoint_controller()->register_metabox();
	}

	/**
	 * Saves Checkpoint technical data after post save.
	 *
	 * @param int      $post_id Post identifier.
	 * @param \WP_Post $post    WordPress post object.
	 * @return void
	 */
	public function save_checkpoint_path( int $post_id, \WP_Post $post ): void {
		$this->get_checkpoint_controller()->save( $post_id, $post );
	}

	/**
	 * Registers plugin REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->get_scan_rest_controller()->register_routes();
	}

	/**
	 * Creates the Group controller.
	 *
	 * @return GroupController
	 */
	private function get_group_controller(): GroupController {
		if ( null === $this->group_controller ) {
			$this->group_controller = new GroupController( $this->get_group_service(), $this->get_path_service() );
		}

		return $this->group_controller;
	}

	/**
	 * Creates the Participation controller.
	 *
	 * @return ParticipationController
	 */
	private function get_participation_controller(): ParticipationController {
		if ( null === $this->participation_controller ) {
			$this->participation_controller = new ParticipationController( $this->get_participation_service(), $this->get_path_service() );
		}

		return $this->participation_controller;
	}

	/**
	 * Creates the Path controller.
	 *
	 * @return PathController
	 */
	private function get_path_controller(): PathController {
		if ( null === $this->path_controller ) {
			$this->path_controller = new PathController( $this->get_path_service() );
		}

		return $this->path_controller;
	}

	/**
	 * Creates the Checkpoint controller.
	 *
	 * @return CheckpointController
	 */
	private function get_checkpoint_controller(): CheckpointController {
		if ( null === $this->checkpoint_controller ) {
			$checkpoint_service    = $this->get_checkpoint_service();
			$group_service         = $this->get_group_service();
			$dependency_controller = new DependencyController( $this->get_dependency_service(), $checkpoint_service, $group_service );

			$this->checkpoint_controller = new CheckpointController( $checkpoint_service, $dependency_controller, $group_service, $this->get_path_service() );
		}

		return $this->checkpoint_controller;
	}

	/**
	 * Creates the scan REST controller.
	 *
	 * @return ScanRestController
	 */
	private function get_scan_rest_controller(): ScanRestController {
		if ( null === $this->scan_rest_controller ) {
			$this->scan_rest_controller = new ScanRestController( $this->get_scan_service() );
		}

		return $this->scan_rest_controller;
	}

	/**
	 * Creates the scan service.
	 *
	 * @return ScanService
	 */
	private function get_scan_service(): ScanService {
		if ( null === $this->scan_service ) {
			$this->scan_service = new ScanService(
				$this->get_checkpoint_service(),
				$this->get_participation_progress_builder(),
				$this->get_validation_service(),
				$this->get_participation_checkpoint_service(),
				$this->get_event_service(),
				$this->get_path_service(),
				$this->get_participation_service()
			);
		}

		return $this->scan_service;
	}

	/**
	 * Creates the Checkpoint service.
	 *
	 * @return CheckpointService
	 */
	private function get_checkpoint_service(): CheckpointService {
		if ( null === $this->checkpoint_service ) {
			$this->checkpoint_service = new CheckpointService( $this->get_checkpoint_repository() );
		}

		return $this->checkpoint_service;
	}

	/**
	 * Creates the Dependency service.
	 *
	 * @return DependencyService
	 */
	private function get_dependency_service(): DependencyService {
		if ( null === $this->dependency_service ) {
			$this->dependency_service = new DependencyService( $this->get_dependency_repository() );
		}

		return $this->dependency_service;
	}

	/**
	 * Creates the Group service.
	 *
	 * @return GroupService
	 */
	private function get_group_service(): GroupService {
		if ( null === $this->group_service ) {
			$this->group_service = new GroupService( $this->get_group_repository() );
		}

		return $this->group_service;
	}

	/**
	 * Creates the Path service.
	 *
	 * @return PathService
	 */
	private function get_path_service(): PathService {
		if ( null === $this->path_service ) {
			$this->path_service = new PathService( $this->get_path_repository() );
		}

		return $this->path_service;
	}

	/**
	 * Creates the Participation service.
	 *
	 * @return ParticipationService
	 */
	private function get_participation_service(): ParticipationService {
		if ( null === $this->participation_service ) {
			$this->participation_service = new ParticipationService( $this->get_participation_repository() );
		}

		return $this->participation_service;
	}

	/**
	 * Creates the Participation checkpoint service.
	 *
	 * @return ParticipationCheckpointService
	 */
	private function get_participation_checkpoint_service(): ParticipationCheckpointService {
		if ( null === $this->participation_checkpoint_service ) {
			$this->participation_checkpoint_service = new ParticipationCheckpointService( $this->get_participation_checkpoint_repository() );
		}

		return $this->participation_checkpoint_service;
	}

	/**
	 * Creates the Participation progress builder.
	 *
	 * @return ParticipationProgressBuilder
	 */
	private function get_participation_progress_builder(): ParticipationProgressBuilder {
		if ( null === $this->participation_progress_builder ) {
			$this->participation_progress_builder = new ParticipationProgressBuilder(
				$this->get_participation_checkpoint_repository(),
				$this->get_checkpoint_repository(),
				$this->get_group_repository()
			);
		}

		return $this->participation_progress_builder;
	}

	/**
	 * Creates the Event service.
	 *
	 * @return EventService
	 */
	private function get_event_service(): EventService {
		if ( null === $this->event_service ) {
			$this->event_service = new EventService( $this->get_event_repository() );
		}

		return $this->event_service;
	}

	/**
	 * Creates the Validation service.
	 *
	 * @return ValidationService
	 */
	private function get_validation_service(): ValidationService {
		if ( null === $this->validation_service ) {
			$this->validation_service = new ValidationService();
		}

		return $this->validation_service;
	}

	/**
	 * Creates the Checkpoint repository.
	 *
	 * @return CheckpointRepository
	 */
	private function get_checkpoint_repository(): CheckpointRepository {
		if ( null === $this->checkpoint_repository ) {
			global $wpdb;

			$this->checkpoint_repository = new CheckpointRepository( $wpdb, $this->get_dependency_repository(), $this->get_group_repository() );
		}

		return $this->checkpoint_repository;
	}

	/**
	 * Creates the Dependency repository.
	 *
	 * @return DependencyRepository
	 */
	private function get_dependency_repository(): DependencyRepository {
		if ( null === $this->dependency_repository ) {
			global $wpdb;

			$this->dependency_repository = new DependencyRepository( $wpdb );
		}

		return $this->dependency_repository;
	}

	/**
	 * Creates the Group repository.
	 *
	 * @return GroupRepository
	 */
	private function get_group_repository(): GroupRepository {
		if ( null === $this->group_repository ) {
			global $wpdb;

			$this->group_repository = new GroupRepository( $wpdb );
		}

		return $this->group_repository;
	}

	/**
	 * Creates the Participation checkpoint repository.
	 *
	 * @return ParticipationCheckpointRepository
	 */
	private function get_participation_checkpoint_repository(): ParticipationCheckpointRepository {
		if ( null === $this->participation_checkpoint_repository ) {
			global $wpdb;

			$this->participation_checkpoint_repository = new ParticipationCheckpointRepository( $wpdb );
		}

		return $this->participation_checkpoint_repository;
	}

	/**
	 * Creates the Participation repository.
	 *
	 * @return ParticipationRepository
	 */
	private function get_participation_repository(): ParticipationRepository {
		if ( null === $this->participation_repository ) {
			global $wpdb;

			$this->participation_repository = new ParticipationRepository( $wpdb );
		}

		return $this->participation_repository;
	}

	/**
	 * Creates the Path repository.
	 *
	 * @return PathRepository
	 */
	private function get_path_repository(): PathRepository {
		if ( null === $this->path_repository ) {
			global $wpdb;

			$this->path_repository = new PathRepository( $wpdb );
		}

		return $this->path_repository;
	}

	/**
	 * Creates the Event repository.
	 *
	 * @return EventRepository
	 */
	private function get_event_repository(): EventRepository {
		if ( null === $this->event_repository ) {
			global $wpdb;

			$this->event_repository = new EventRepository( $wpdb );
		}

		return $this->event_repository;
	}
}
