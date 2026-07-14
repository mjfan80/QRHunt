<?php
namespace QRHunt\Model;

defined( 'ABSPATH' ) || exit;

final class Group {
	private $id;
	private $path_id;
	private $name;
	private $description;
	private $completion_mode;

	public function get_id(): ?int { return $this->id; }
	public function set_id( ?int $id ): void { $this->id = $id; }
	public function get_path_id(): ?int { return $this->path_id; }
	public function set_path_id( ?int $path_id ): void { $this->path_id = $path_id; }
	public function get_name(): ?string { return $this->name; }
	public function set_name( ?string $name ): void { $this->name = $name; }
	public function get_description(): ?string { return $this->description; }
	public function set_description( ?string $description ): void { $this->description = $description; }
	public function get_completion_mode(): ?string { return $this->completion_mode; }
	public function set_completion_mode( ?string $completion_mode ): void { $this->completion_mode = $completion_mode; }
}
