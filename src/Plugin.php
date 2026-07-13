<?php
/**
 * Plugin bootstrap.
 *
 * @package QRHunt
 */

namespace QRHunt;

use QRHunt\Controller\DependencyController;
use QRHunt\Controller\ParticipationController;
use QRHunt\Controller\PathController;
use QRHunt\Controller\GroupController;
use QRHunt\Repository\DependencyRepository;
use QRHunt\Repository\GroupRepository;
use QRHunt\Repository\ParticipationRepository;
use QRHunt\Service\DependencyService;
use QRHunt\Service\GroupService;
use QRHunt\Service\ParticipationService;
use QRHunt\Controller\CheckpointController;
use QRHunt\Repository\CheckpointRepository;
use QRHunt\Repository\PathRepository;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes the plugin integration with WordPress.
 */
final class Plugin {

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

	public function register_groups_page(): void { $this->get_group_controller()->register_page(); }
	public function save_group(): void { $this->get_group_controller()->save(); }
	public function delete_group(): void { $this->get_group_controller()->delete(); }
	public function register_participations_page(): void { $this->get_participation_controller()->register_page(); }
	public function save_participation(): void { $this->get_participation_controller()->save(); }
	public function delete_participation(): void { $this->get_participation_controller()->delete(); }

	private function get_group_controller(): GroupController {
		global $wpdb;
		$group_repository = new GroupRepository( $wpdb );
		$group_service = new GroupService( $group_repository );
		$path_repository = new PathRepository( $wpdb );
		$path_service = new PathService( $path_repository );
		return new GroupController( $group_service, $path_service );
	}

	private function get_participation_controller(): ParticipationController {
		global $wpdb;
		$participation_repository = new ParticipationRepository( $wpdb );
		$participation_service    = new ParticipationService( $participation_repository );
		$path_repository          = new PathRepository( $wpdb );
		$path_service             = new PathService( $path_repository );
		return new ParticipationController( $participation_service, $path_service );
	}

	public function synchronize_path( int $post_id, \WP_Post $post ): void {
		$this->get_path_controller()->save( $post_id, $post );
	}

	private function get_path_controller(): PathController {
		global $wpdb;

		$path_repository = new PathRepository( $wpdb );
		$path_service    = new PathService( $path_repository );

		return new PathController( $path_service );
	}

	public function register_checkpoint_metabox(): void {
		$this->get_checkpoint_controller()->register_metabox();
	}

	public function save_checkpoint_path( int $post_id, \WP_Post $post ): void {
		$this->get_checkpoint_controller()->save( $post_id, $post );
	}

	private function get_checkpoint_controller(): CheckpointController {
		global $wpdb;

		$checkpoint_repository = new CheckpointRepository( $wpdb );
		$checkpoint_service    = new \QRHunt\Service\CheckpointService( $checkpoint_repository );
		$dependency_repository = new DependencyRepository( $wpdb );
		$dependency_service    = new DependencyService( $dependency_repository );
		$group_repository      = new GroupRepository( $wpdb );
		$group_service         = new GroupService( $group_repository );
		$path_repository       = new PathRepository( $wpdb );
		$path_service          = new PathService( $path_repository );
		$dependency_controller = new DependencyController( $dependency_service, $checkpoint_service, $group_service );

		return new CheckpointController( $checkpoint_service, $dependency_controller, $group_service, $path_service );
	}
}
