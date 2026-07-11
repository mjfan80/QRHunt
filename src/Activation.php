<?php
/**
 * Plugin activation handler.
 *
 * @package QRHunt
 */

namespace QRHunt;

use QRHunt\Database\DatabaseInstaller;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation.
 */
final class Activation {

	/**
	 * Performs activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$database_installer = new DatabaseInstaller();
		$database_installer->install();
	}
}
