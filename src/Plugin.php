<?php
/**
 * Plugin bootstrap.
 *
 * @package QRHunt
 */

namespace QRHunt;

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
	}
}
