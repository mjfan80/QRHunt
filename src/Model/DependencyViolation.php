<?php
/**
 * Dependency violation model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a failed Dependency.
 */
final class DependencyViolation {

	/** @var string */
	private $type;

	/** @var string */
	private $target_type;

	/** @var int */
	private $target_id;

	/** @var string */
	private $display_name;

	/**
	 * Creates a Dependency violation.
	 *
	 * @param string $type         Dependency type.
	 * @param string $target_type  Dependency target type.
	 * @param int    $target_id    Dependency target identifier.
	 * @param string $display_name Dependency display name.
	 */
	public function __construct( string $type, string $target_type, int $target_id, string $display_name ) {
		$this->type         = $type;
		$this->target_type  = $target_type;
		$this->target_id    = $target_id;
		$this->display_name = $display_name;
	}

	/**
	 * Gets the Dependency type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Gets the Dependency target type.
	 *
	 * @return string
	 */
	public function get_target_type(): string {
		return $this->target_type;
	}

	/**
	 * Gets the Dependency target identifier.
	 *
	 * @return int
	 */
	public function get_target_id(): int {
		return $this->target_id;
	}

	/**
	 * Gets the Dependency display name.
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->display_name;
	}
}
