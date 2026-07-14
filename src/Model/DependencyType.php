<?php
/**
 * Dependency type values.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the supported Dependency types.
 */
final class DependencyType {

	public const AFTER  = 'after';
	public const BEFORE = 'before';

	/**
	 * Gets the supported Dependency types.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::AFTER,
			self::BEFORE,
		);
	}
}
