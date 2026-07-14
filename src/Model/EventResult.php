<?php
/**
 * Event result values.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the supported Event results.
 */
final class EventResult {

	public const ACCEPTED                = 'accepted';
	public const DUPLICATE               = 'duplicate';
	public const BEFORE_FAILED           = 'before_failed';
	public const AFTER_FAILED            = 'after_failed';
	public const PATH_CLOSED             = 'path_closed';
	public const PARTICIPATION_CANCELLED = 'participation_cancelled';
}
