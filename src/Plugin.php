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
	}

	/**
	 * Initializes plugin components.
	 *
	 * @return void
	 */
	public function initialize(): void {
	}
}
