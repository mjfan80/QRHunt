<?php
/**
 * Dependency model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a Dependency.
 */
final class Dependency {

	/** @var int|null */
	private $id;

	/** @var int|null */
	private $checkpoint_id;

	/** @var string|null */
	private $type;

	/** @var string|null */
	private $target_type;

	/** @var int|null */
	private $target_id;

	/** @var string|null */
	private $created_at;

	/** @var string|null */
	private $updated_at;

	public function get_id(): ?int {
		return $this->id;
	}

	public function set_id( ?int $id ): void {
		$this->id = $id;
	}

	public function get_checkpoint_id(): ?int {
		return $this->checkpoint_id;
	}

	public function set_checkpoint_id( ?int $checkpoint_id ): void {
		$this->checkpoint_id = $checkpoint_id;
	}

	public function get_type(): ?string {
		return $this->type;
	}

	public function set_type( ?string $type ): void {
		$this->type = $type;
	}

	public function get_target_type(): ?string {
		return $this->target_type;
	}

	public function set_target_type( ?string $target_type ): void {
		$this->target_type = $target_type;
	}

	public function get_target_id(): ?int {
		return $this->target_id;
	}

	public function set_target_id( ?int $target_id ): void {
		$this->target_id = $target_id;
	}

	public function get_created_at(): ?string {
		return $this->created_at;
	}

	public function set_created_at( ?string $created_at ): void {
		$this->created_at = $created_at;
	}

	public function get_updated_at(): ?string {
		return $this->updated_at;
	}

	public function set_updated_at( ?string $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
