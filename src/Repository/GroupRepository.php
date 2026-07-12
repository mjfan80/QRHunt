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
		$rows = $this->wpdb->get_results( "SELECT id, path_id, name, description FROM {$this->table_name} ORDER BY name ASC", ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$groups = array();
		foreach ( $rows as $row ) {
			$group = new Group();
			$group->set_id( (int) $row['id'] );
			$group->set_path_id( (int) $row['path_id'] );
			$group->set_name( (string) $row['name'] );
			$group->set_description( null === $row['description'] ? null : (string) $row['description'] );
			$groups[] = $group;
		}
		return $groups;
	}

	public function save( Group $group ): void {
		$data = array( 'path_id' => $group->get_path_id(), 'name' => $group->get_name(), 'description' => $group->get_description() );
		if ( null === $group->get_id() ) {
			$this->wpdb->insert( $this->table_name, $data, array( '%d', '%s', '%s' ) );
			return;
		}
		$this->wpdb->update( $this->table_name, $data, array( 'id' => $group->get_id() ), array( '%d', '%s', '%s' ), array( '%d' ) );
	}

	public function delete( int $id ): void { $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ); }
}
