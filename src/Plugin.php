<?php
/**
 * Plugin bootstrap.
 *
 * @package QRHunt
 */

namespace QRHunt;

use QRHunt\Controller\PathController;
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
		add_action( 'save_post_qrhunt_path', array( $this, 'synchronize_path' ), 10, 2 );
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

	public function synchronize_path( int $post_id, \WP_Post $post ): void {
		$this->get_path_controller()->save( $post_id, $post );
	}

	private function get_path_controller(): PathController {
		global $wpdb;

		$path_repository = new PathRepository( $wpdb );
		$path_service    = new PathService( $path_repository );

		return new PathController( $path_service );
	}
}
