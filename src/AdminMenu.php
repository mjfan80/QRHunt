<?php
/**
 * Plugin administration menu registration.
 *
 * @package QRHunt
 */

namespace QRHunt;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the QRHunt administration menu.
 */
final class AdminMenu {

	/** @var callable */
	private $dashboard_callback;

	/**
	 * Creates an admin menu registrar.
	 *
	 * @param callable $dashboard_callback Dashboard callback.
	 */
	public function __construct( callable $dashboard_callback ) {
		$this->dashboard_callback = $dashboard_callback;
	}

	/**
	 * Registers the QRHunt administration menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_menu_page(
			__( 'QRHunt', 'qrhunt' ),
			__( 'QRHunt', 'qrhunt' ),
			'edit_posts',
			'qrhunt',
			$this->dashboard_callback,
			'dashicons-location-alt',
			26
		);

		add_submenu_page(
			'qrhunt',
			__( 'Paths', 'qrhunt' ),
			__( 'Paths', 'qrhunt' ),
			'edit_posts',
			'edit.php?post_type=' . PathPostType::POST_TYPE
		);

		add_submenu_page(
			'qrhunt',
			__( 'Checkpoints', 'qrhunt' ),
			__( 'Checkpoints', 'qrhunt' ),
			'edit_posts',
			'edit.php?post_type=' . CheckpointPostType::POST_TYPE
		);
	}
}
