<?php
/**
 * Plugin activation handler.
 *
 * @package QuestUno
 */

namespace QuestUno;

use QuestUno\Controller\PlayerFlowController;
use QuestUno\Database\DatabaseInstaller;

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
		PlayerFlowController::register_rewrite_rules();
		flush_rewrite_rules();
	}
}
