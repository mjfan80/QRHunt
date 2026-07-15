<?php
/**
 * Plugin deactivation handler.
 *
 * @package QRHunt
 */

namespace QRHunt;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation.
 */
final class Deactivation {

	/**
	 * Performs deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
