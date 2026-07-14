<?php
/**
 * Participation status values.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the supported Participation statuses.
 */
final class ParticipationStatus {

	public const IN_PROGRESS = 'in_progress';
	public const COMPLETED   = 'completed';
	public const FINISHED    = 'finished';
	public const CANCELLED   = 'cancelled';
}
