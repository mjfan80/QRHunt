<?php
/**
 * Path model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a Path.
 */
final class Path {

	/** @var int|null */
	private $id;

	/** @var int|null */
	private $post_id;

	/** @var string|null */
	private $name;

	/** @var string|null */
	private $description;

	/** @var string|null */
	private $status;

	/** @var int|null */
	private $start_checkpoint_id;

	/** @var int|null */
	private $finish_checkpoint_id;

	/** @var string|null */
	private $opening_date;

	/** @var string|null */
	private $closing_date;

	/** @var string|null */
	private $created_at;

	/** @var string|null */
	private $updated_at;

	/**
	 * Gets the Path identifier.
	 *
	 * @return int|null
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Sets the Path identifier.
	 *
	 * @param int|null $id Path identifier.
	 * @return void
	 */
	public function set_id( ?int $id ): void {
		$this->id = $id;
	}

	public function get_post_id(): ?int {
		return $this->post_id;
	}

	public function set_post_id( ?int $post_id ): void {
		$this->post_id = $post_id;
	}

	/**
	 * Gets the Path name.
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->name;
	}

	/**
	 * Sets the Path name.
	 *
	 * @param string|null $name Path name.
	 * @return void
	 */
	public function set_name( ?string $name ): void {
		$this->name = $name;
	}

	/**
	 * Gets the Path description.
	 *
	 * @return string|null
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * Sets the Path description.
	 *
	 * @param string|null $description Path description.
	 * @return void
	 */
	public function set_description( ?string $description ): void {
		$this->description = $description;
	}

	/**
	 * Gets the Path status.
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Sets the Path status.
	 *
	 * @param string|null $status Path status.
	 * @return void
	 */
	public function set_status( ?string $status ): void {
		$this->status = $status;
	}

	/**
	 * Gets the start Checkpoint identifier.
	 *
	 * @return int|null
	 */
	public function get_start_checkpoint_id(): ?int {
		return $this->start_checkpoint_id;
	}

	/**
	 * Sets the start Checkpoint identifier.
	 *
	 * @param int|null $start_checkpoint_id Start Checkpoint identifier.
	 * @return void
	 */
	public function set_start_checkpoint_id( ?int $start_checkpoint_id ): void {
		$this->start_checkpoint_id = $start_checkpoint_id;
	}

	/**
	 * Gets the finish Checkpoint identifier.
	 *
	 * @return int|null
	 */
	public function get_finish_checkpoint_id(): ?int {
		return $this->finish_checkpoint_id;
	}

	/**
	 * Sets the finish Checkpoint identifier.
	 *
	 * @param int|null $finish_checkpoint_id Finish Checkpoint identifier.
	 * @return void
	 */
	public function set_finish_checkpoint_id( ?int $finish_checkpoint_id ): void {
		$this->finish_checkpoint_id = $finish_checkpoint_id;
	}

	/**
	 * Gets the Path opening date.
	 *
	 * @return string|null
	 */
	public function get_opening_date(): ?string {
		return $this->opening_date;
	}

	/**
	 * Sets the Path opening date.
	 *
	 * @param string|null $opening_date Path opening date.
	 * @return void
	 */
	public function set_opening_date( ?string $opening_date ): void {
		$this->opening_date = $opening_date;
	}

	/**
	 * Gets the Path closing date.
	 *
	 * @return string|null
	 */
	public function get_closing_date(): ?string {
		return $this->closing_date;
	}

	/**
	 * Sets the Path closing date.
	 *
	 * @param string|null $closing_date Path closing date.
	 * @return void
	 */
	public function set_closing_date( ?string $closing_date ): void {
		$this->closing_date = $closing_date;
	}

	/**
	 * Gets the creation date.
	 *
	 * @return string|null
	 */
	public function get_created_at(): ?string {
		return $this->created_at;
	}

	/**
	 * Sets the creation date.
	 *
	 * @param string|null $created_at Creation date.
	 * @return void
	 */
	public function set_created_at( ?string $created_at ): void {
		$this->created_at = $created_at;
	}

	/**
	 * Gets the last update date.
	 *
	 * @return string|null
	 */
	public function get_updated_at(): ?string {
		return $this->updated_at;
	}

	/**
	 * Sets the last update date.
	 *
	 * @param string|null $updated_at Last update date.
	 * @return void
	 */
	public function set_updated_at( ?string $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}
