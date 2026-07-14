<?php
/**
 * Checkpoint repository.
 *
 * @package QRHunt
 */

namespace QRHunt\Repository;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\ResolvedDependency;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves Checkpoints from the database.
 */
final class CheckpointRepository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table_name;

	/** @var DependencyRepository */
	private $dependency_repository;

	/** @var GroupRepository */
	private $group_repository;

	/**
	 * Creates a Checkpoint repository.
	 *
	 * @param \wpdb                $wpdb                  WordPress database access object.
	 * @param DependencyRepository $dependency_repository Dependency repository.
	 * @param GroupRepository      $group_repository      Group repository.
	 */
	public function __construct( \wpdb $wpdb, DependencyRepository $dependency_repository, GroupRepository $group_repository ) {
		$this->wpdb                  = $wpdb;
		$this->table_name            = $wpdb->prefix . 'qrhunt_checkpoints';
		$this->dependency_repository = $dependency_repository;
		$this->group_repository      = $group_repository;
	}

	/**
	 * Gets all Checkpoints ordered by post identifier.
	 *
	 * @return array<int, Checkpoint>
	 */
	public function find_all(): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$rows = $this->wpdb->get_results(
			"SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} ORDER BY post_id ASC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$checkpoints = array();

		foreach ( $rows as $row ) {
			$checkpoints[] = $this->hydrate_checkpoint( $row );
		}

		return $checkpoints;
	}

	public function find_by_post_id( int $post_id ): ?Checkpoint {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare( "SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} WHERE post_id = %d", $post_id );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_checkpoint( $row );
	}

	/**
	 * Gets a Checkpoint by token.
	 *
	 * @param string $token Checkpoint token.
	 * @return Checkpoint|null
	 */
	public function find_by_token( string $token ): ?Checkpoint {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare( "SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} WHERE token = %s", $token );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_checkpoint( $row );
	}

	/**
	 * Gets all Checkpoints for a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return array<int, Checkpoint>
	 */
	public function find_by_path( int $path_id ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare(
			"SELECT post_id, path_id, group_id, token, created_at, updated_at FROM {$this->table_name} WHERE path_id = %d ORDER BY post_id ASC",
			$path_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$rows = $this->wpdb->get_results( $sql, ARRAY_A );

		$checkpoints = array();

		foreach ( $rows as $row ) {
			$checkpoints[] = $this->hydrate_checkpoint( $row );
		}

		return $checkpoints;
	}

	/**
	 * Gets a Checkpoint with its Dependencies.
	 *
	 * @param int $post_id Checkpoint post identifier.
	 * @return Checkpoint|null
	 */
	public function find_by_post_id_with_dependencies( int $post_id ): ?Checkpoint {
		$checkpoint = $this->find_by_post_id( $post_id );

		if ( null === $checkpoint ) {
			return null;
		}

		$checkpoint->set_dependencies( $this->resolve_dependencies( $post_id ) );

		return $checkpoint;
	}

	/**
	 * Gets a Checkpoint by token with its Dependencies.
	 *
	 * @param string $token Checkpoint token.
	 * @return Checkpoint|null
	 */
	public function find_by_token_with_dependencies( string $token ): ?Checkpoint {
		$checkpoint = $this->find_by_token( $token );

		if ( null === $checkpoint ) {
			return null;
		}

		$checkpoint->set_dependencies( $this->resolve_dependencies( (int) $checkpoint->get_post_id() ) );

		return $checkpoint;
	}

	public function save_path( Checkpoint $checkpoint ): void {
		if ( null !== $this->find_by_post_id( $checkpoint->get_post_id() ) ) {
			$this->wpdb->update(
				$this->table_name,
				array(
					'path_id'  => $checkpoint->get_path_id(),
					'group_id' => $checkpoint->get_group_id(),
				),
				array( 'post_id' => $checkpoint->get_post_id() ),
				array( '%d', '%d' ),
				array( '%d' )
			);

			return;
		}

		$this->wpdb->insert(
			$this->table_name,
			array(
				'post_id'  => $checkpoint->get_post_id(),
				'path_id'  => $checkpoint->get_path_id(),
				'group_id' => $checkpoint->get_group_id(),
				'token'    => $this->generate_token(),
			),
			array( '%d', '%d', '%d', '%s' )
		);
	}

	private function generate_token(): string {
		do {
			$token = wp_generate_password( 16, false, false );
		} while ( $this->token_exists( $token ) );

		return $token;
	}

	private function token_exists( string $token ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name contains only the WordPress database prefix and the fixed qrhunt_checkpoints suffix.
		$sql = $this->wpdb->prepare( "SELECT 1 FROM {$this->table_name} WHERE token = %s", $token );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above with $wpdb->prepare().
		$exists = $this->wpdb->get_var( $sql );

		return null !== $exists;
	}

	/**
	 * Hydrates a Checkpoint model without loading its Dependencies.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return Checkpoint
	 */
	private function hydrate_checkpoint( array $row ): Checkpoint {
		$checkpoint = new Checkpoint();
		$checkpoint->set_post_id( (int) $row['post_id'] );
		$checkpoint->set_path_id( (int) $row['path_id'] );
		$checkpoint->set_group_id( null === $row['group_id'] ? null : (int) $row['group_id'] );
		$checkpoint->set_token( (string) $row['token'] );
		$checkpoint->set_created_at( (string) $row['created_at'] );
		$checkpoint->set_updated_at( (string) $row['updated_at'] );

		return $checkpoint;
	}

	/**
	 * Resolves the Dependencies associated with a Checkpoint.
	 *
	 * @param int $post_id Checkpoint post identifier.
	 * @return array<int, ResolvedDependency>
	 */
	private function resolve_dependencies( int $post_id ): array {
		$resolved_dependencies = array();

		foreach ( $this->dependency_repository->find_by_checkpoint( $post_id ) as $dependency ) {
			$resolved_dependencies[] = new ResolvedDependency(
				(string) $dependency->get_type(),
				(string) $dependency->get_target_type(),
				(int) $dependency->get_target_id(),
				$this->resolve_dependency_display_name( (string) $dependency->get_target_type(), (int) $dependency->get_target_id() )
			);
		}

		return $resolved_dependencies;
	}

	/**
	 * Resolves the display name of a Dependency target.
	 *
	 * @param string $target_type Dependency target type.
	 * @param int    $target_id   Dependency target identifier.
	 * @return string
	 */
	private function resolve_dependency_display_name( string $target_type, int $target_id ): string {
		if ( DependencyTargetType::GROUP === $target_type ) {
			$group = $this->group_repository->find_by_id( $target_id );

			return null === $group ? '' : (string) $group->get_name();
		}

		return (string) get_the_title( $target_id );
	}
}
