<?php
/**
 * Event model.
 *
 * @package QRHunt
 */

namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Represents an Event.
 */
final class Event {

	/** @var int|null */
	private $id;

	/** @var int|null */
	private $participation_id;

	/** @var int|null */
	private $checkpoint_id;

	/** @var string|null */
	private $event_type;

	/** @var string|null */
	private $result;

	/** @var string|null */
	private $ip_address;

	/** @var string|null */
	private $user_agent;

	/** @var string|null */
	private $created_at;

	public function get_id(): ?int {
		return $this->id;
	}

	public function set_id( ?int $id ): void {
		$this->id = $id;
	}

	public function get_participation_id(): ?int {
		return $this->participation_id;
	}

	public function set_participation_id( ?int $participation_id ): void {
		$this->participation_id = $participation_id;
	}

	public function get_checkpoint_id(): ?int {
		return $this->checkpoint_id;
	}

	public function set_checkpoint_id( ?int $checkpoint_id ): void {
		$this->checkpoint_id = $checkpoint_id;
	}

	public function get_event_type(): ?string {
		return $this->event_type;
	}

	public function set_event_type( ?string $event_type ): void {
		$this->event_type = $event_type;
	}

	public function get_result(): ?string {
		return $this->result;
	}

	public function set_result( ?string $result ): void {
		$this->result = $result;
	}

	public function get_ip_address(): ?string {
		return $this->ip_address;
	}

	public function set_ip_address( ?string $ip_address ): void {
		$this->ip_address = $ip_address;
	}

	public function get_user_agent(): ?string {
		return $this->user_agent;
	}

	public function set_user_agent( ?string $user_agent ): void {
		$this->user_agent = $user_agent;
	}

	public function get_created_at(): ?string {
		return $this->created_at;
	}

	public function set_created_at( ?string $created_at ): void {
		$this->created_at = $created_at;
	}
}
