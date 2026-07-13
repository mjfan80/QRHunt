<?php
/**
 * Participation model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a Participation.
 */
final class Participation {

	/** @var int|null */
	private $id;

	/** @var int|null */
	private $user_id;

	/** @var int|null */
	private $path_id;

	/** @var string|null */
	private $status;

	/** @var string|null */
	private $started_at;

	/** @var string|null */
	private $finished_at;

	/** @var string|null */
	private $cancelled_at;

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

	public function get_user_id(): ?int {
		return $this->user_id;
	}

	public function set_user_id( ?int $user_id ): void {
		$this->user_id = $user_id;
	}

	public function get_path_id(): ?int {
		return $this->path_id;
	}

	public function set_path_id( ?int $path_id ): void {
		$this->path_id = $path_id;
	}

	public function get_status(): ?string {
		return $this->status;
	}

	public function set_status( ?string $status ): void {
		$this->status = $status;
	}

	public function get_started_at(): ?string {
		return $this->started_at;
	}

	public function set_started_at( ?string $started_at ): void {
		$this->started_at = $started_at;
	}

	public function get_finished_at(): ?string {
		return $this->finished_at;
	}

	public function set_finished_at( ?string $finished_at ): void {
		$this->finished_at = $finished_at;
	}

	public function get_cancelled_at(): ?string {
		return $this->cancelled_at;
	}

	public function set_cancelled_at( ?string $cancelled_at ): void {
		$this->cancelled_at = $cancelled_at;
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
