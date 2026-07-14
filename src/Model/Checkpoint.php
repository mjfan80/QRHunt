<?php
/**
 * Checkpoint model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a Checkpoint.
 */
final class Checkpoint {

	/** @var int|null */
	private $post_id;

	/** @var int|null */
	private $path_id;

	/** @var int|null */
	private $group_id;

	/** @var string|null */
	private $token;

	/** @var string|null */
	private $created_at;

	/** @var string|null */
	private $updated_at;

	/** @var array<int, ResolvedDependency> */
	private $dependencies = array();

	/**
	 * Gets the post identifier.
	 *
	 * @return int|null
	 */
	public function get_post_id(): ?int {
		return $this->post_id;
	}

	/**
	 * Sets the post identifier.
	 *
	 * @param int|null $post_id Post identifier.
	 * @return void
	 */
	public function set_post_id( ?int $post_id ): void {
		$this->post_id = $post_id;
	}

	/**
	 * Gets the Path identifier.
	 *
	 * @return int|null
	 */
	public function get_path_id(): ?int {
		return $this->path_id;
	}

	/**
	 * Sets the Path identifier.
	 *
	 * @param int|null $path_id Path identifier.
	 * @return void
	 */
	public function set_path_id( ?int $path_id ): void {
		$this->path_id = $path_id;
	}

	/**
	 * Gets the group identifier.
	 *
	 * @return int|null
	 */
	public function get_group_id(): ?int {
		return $this->group_id;
	}

	/**
	 * Sets the group identifier.
	 *
	 * @param int|null $group_id Group identifier.
	 * @return void
	 */
	public function set_group_id( ?int $group_id ): void {
		$this->group_id = $group_id;
	}

	/**
	 * Gets the public token.
	 *
	 * @return string|null
	 */
	public function get_token(): ?string {
		return $this->token;
	}

	/**
	 * Sets the public token.
	 *
	 * @param string|null $token Public token.
	 * @return void
	 */
	public function set_token( ?string $token ): void {
		$this->token = $token;
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

	/**
	 * Gets the Dependencies associated with the Checkpoint.
	 *
	 * @return array<int, ResolvedDependency>
	 */
	public function get_dependencies(): array {
		return $this->dependencies;
	}

	/**
	 * Sets the Dependencies associated with the Checkpoint.
	 *
	 * @param array<int, ResolvedDependency> $dependencies Checkpoint Dependencies.
	 * @return void
	 */
	public function set_dependencies( array $dependencies ): void {
		$this->dependencies = $dependencies;
	}
}
