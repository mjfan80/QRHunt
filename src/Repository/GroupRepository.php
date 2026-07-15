<?php
namespace QRHunt\Repository;

use QRHunt\Model\Group;

defined( 'ABSPATH' ) || exit;

final class GroupRepository {
	private $wpdb;
	private $table_name;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . 'qrhunt_checkpoint_groups';
	}

	public function find_all(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_checkpoint_groups suffix.
		$rows = $this->wpdb->get_results( "SELECT id, path_id, name, description, completion_mode FROM {$this->table_name} ORDER BY name ASC", ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $this->hydrate_groups( $rows );
	}

	public function find_by_path( int $path_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_checkpoint_groups suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, path_id, name, description, completion_mode FROM {$this->table_name} WHERE path_id = %d ORDER BY name ASC",
			$path_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->hydrate_groups( $rows );
	}

	public function find_by_id( int $id ): ?Group {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_checkpoint_groups suffix.
		$sql = $this->wpdb->prepare(
			"SELECT id, path_id, name, description, completion_mode FROM {$this->table_name} WHERE id = %d",
			$id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$groups = $this->hydrate_groups( array( $row ) );

		return $groups[0];
	}

	public function count_all(): int {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and fixed qrhunt_checkpoint_groups suffix.
		$count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	public function save( Group $group ): void {
		$data = array(
			'path_id'         => $group->get_path_id(),
			'name'            => $group->get_name(),
			'description'     => $group->get_description(),
			'completion_mode' => $group->get_completion_mode(),
		);
		if ( null === $group->get_id() ) {
			$this->wpdb->insert( $this->table_name, $data, array( '%d', '%s', '%s', '%s' ) );
			return;
		}
		$this->wpdb->update( $this->table_name, $data, array( 'id' => $group->get_id() ), array( '%d', '%s', '%s', '%s' ), array( '%d' ) );
	}

	public function delete( int $id ): void { $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ); }

	private function hydrate_groups( array $rows ): array {
		$groups = array();

		foreach ( $rows as $row ) {
			$group = new Group();
			$group->set_id( (int) $row['id'] );
			$group->set_path_id( (int) $row['path_id'] );
			$group->set_name( (string) $row['name'] );
			$group->set_description( null === $row['description'] ? null : (string) $row['description'] );
			$group->set_completion_mode( (string) $row['completion_mode'] );
			$groups[] = $group;
		}

		return $groups;
	}
}
