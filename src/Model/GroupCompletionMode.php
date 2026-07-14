<?php
/**
 * Group completion mode values.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the supported Group completion modes.
 */
final class GroupCompletionMode {

	public const ALL = 'ALL';
	public const ANY = 'ANY';

	/**
	 * Gets the supported Group completion modes.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::ALL,
			self::ANY,
		);
	}
}
