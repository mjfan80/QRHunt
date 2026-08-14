<?php
/**
 * Plugin deactivation handler.
 *
 * @package QuestUno
 */

namespace QuestUno;

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
