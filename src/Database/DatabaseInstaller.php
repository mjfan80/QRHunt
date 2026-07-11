<?php
/**
 * Database installation handler.
 *
 * @package QRHunt
 */

namespace QRHunt\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and updates the plugin database tables.
 */
final class DatabaseInstaller {

	/**
	 * Creates or updates the plugin database tables.
	 *
	 * @return void
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$paths_table     = $wpdb->prefix . 'qrhunt_paths';
		$checkpoints_table = $wpdb->prefix . 'qrhunt_checkpoints';
		$groups_table      = $wpdb->prefix . 'qrhunt_checkpoint_groups';
		$dependencies_table = $wpdb->prefix . 'qrhunt_dependencies';
		$participations_table = $wpdb->prefix . 'qrhunt_participations';
		$events_table        = $wpdb->prefix . 'qrhunt_events';

		$paths_sql = "CREATE TABLE $paths_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			start_checkpoint_id bigint(20) unsigned DEFAULT NULL,
			finish_checkpoint_id bigint(20) unsigned DEFAULT NULL,
			opening_date datetime DEFAULT NULL,
			closing_date datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY opening_date (opening_date),
			KEY closing_date (closing_date)
		) $charset_collate;";

		$checkpoints_sql = "CREATE TABLE $checkpoints_table (
			post_id bigint(20) unsigned NOT NULL,
			path_id bigint(20) unsigned NOT NULL,
			group_id bigint(20) unsigned DEFAULT NULL,
			token char(16) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (post_id),
			KEY path_id (path_id),
			KEY group_id (group_id),
			UNIQUE KEY token (token)
		) $charset_collate;";

		$groups_sql = "CREATE TABLE $groups_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			path_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY path_id (path_id)
		) $charset_collate;";

		$dependencies_sql = "CREATE TABLE $dependencies_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			checkpoint_id bigint(20) unsigned NOT NULL,
			type varchar(20) NOT NULL,
			target_type varchar(20) NOT NULL,
			target_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY checkpoint_id (checkpoint_id),
			KEY target_type_target_id (target_type, target_id),
			KEY type (type)
		) $charset_collate;";

		$participations_sql = "CREATE TABLE $participations_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			path_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'in_progress',
			started_at datetime DEFAULT NULL,
			finished_at datetime DEFAULT NULL,
			cancelled_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id_path_id (user_id, path_id),
			KEY path_id (path_id),
			KEY status (status)
		) $charset_collate;";

		$events_sql = "CREATE TABLE $events_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			participation_id bigint(20) unsigned NOT NULL,
			checkpoint_id bigint(20) unsigned NOT NULL,
			event_type varchar(30) NOT NULL,
			result varchar(30) NOT NULL,
			message_key varchar(50) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY participation_id (participation_id),
			KEY checkpoint_id (checkpoint_id),
			KEY created_at (created_at),
			KEY event_type (event_type),
			KEY result (result)
		) $charset_collate;";

		dbDelta( $paths_sql );
		dbDelta( $checkpoints_sql );
		dbDelta( $groups_sql );
		dbDelta( $dependencies_sql );
		dbDelta( $participations_sql );
		dbDelta( $events_sql );
	}
}
