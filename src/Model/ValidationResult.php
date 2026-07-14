<?php
/**
 * Validation result model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the outcome of a validation attempt.
 */
final class ValidationResult {

	/** @var bool */
	private $valid;

	/** @var array<int, DependencyViolation> */
	private $failed_dependencies;

	/**
	 * Creates a Validation result.
	 *
	 * @param bool                           $valid               Whether the validation succeeded.
	 * @param array<int, DependencyViolation> $failed_dependencies Failed Dependencies.
	 */
	public function __construct( bool $valid, array $failed_dependencies = array() ) {
		$this->valid               = $valid;
		$this->failed_dependencies = $failed_dependencies;
	}

	/**
	 * Creates a successful Validation result.
	 *
	 * @return self
	 */
	public static function create_valid(): self {
		return new self( true );
	}

	/**
	 * Creates a failed Validation result.
	 *
	 * @param array<int, DependencyViolation> $failed_dependencies Failed Dependencies.
	 * @return self
	 */
	public static function create_invalid( array $failed_dependencies ): self {
		return new self( false, $failed_dependencies );
	}

	/**
	 * Indicates whether the validation succeeded.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * Gets the failed Dependencies.
	 *
	 * @return array<int, DependencyViolation>
	 */
	public function get_failed_dependencies(): array {
		return $this->failed_dependencies;
	}
}
