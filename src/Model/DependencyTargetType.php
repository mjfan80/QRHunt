<?php
/**
 * Dependency target type values.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the supported Dependency target types.
 */
final class DependencyTargetType {

	public const CHECKPOINT = 'checkpoint';
	public const GROUP      = 'group';

	/**
	 * Gets the supported Dependency target types.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::CHECKPOINT,
			self::GROUP,
		);
	}
}
