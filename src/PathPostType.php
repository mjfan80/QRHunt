<?php
/**
 * Path custom post type registration.
 *
 * @package QRHunt
 */

namespace QRHunt;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Path custom post type.
 */
final class PathPostType {

	public const POST_TYPE = 'qrhunt_path';

	public const ARCHIVED_STATUS = 'qrhunt_archived';

	/**
	 * Registers the Path custom post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_status(
			self::ARCHIVED_STATUS,
			array(
				'label'                     => _x( 'Archived', 'Path post status', 'qrhunt' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'label_count'               =>
					/* translators: %s: number of archived Paths. */
					_n_noop(
					'Archived <span class="count">(%s)</span>',
					'Archived <span class="count">(%s)</span>',
					'qrhunt'
				),
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $this->get_labels(),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_nav_menus'   => false,
				'supports'            => array( 'title', 'editor', 'revisions' ),
			)
		);
	}

	/**
	 * Gets the labels for the Path custom post type.
	 *
	 * @return array<string, string>
	 */
	private function get_labels(): array {
		return array(
			'name'                  => _x( 'Paths', 'Post type general name', 'qrhunt' ),
			'singular_name'         => _x( 'Path', 'Post type singular name', 'qrhunt' ),
			'menu_name'             => _x( 'Paths', 'Admin Menu text', 'qrhunt' ),
			'name_admin_bar'        => _x( 'Path', 'Add New on Toolbar', 'qrhunt' ),
			'add_new'               => __( 'Add New', 'qrhunt' ),
			'add_new_item'          => __( 'Add New Path', 'qrhunt' ),
			'new_item'              => __( 'New Path', 'qrhunt' ),
			'edit_item'             => __( 'Edit Path', 'qrhunt' ),
			'view_item'             => __( 'View Path', 'qrhunt' ),
			'all_items'             => __( 'All Paths', 'qrhunt' ),
			'search_items'          => __( 'Search Paths', 'qrhunt' ),
			'not_found'             => __( 'No paths found.', 'qrhunt' ),
			'not_found_in_trash'    => __( 'No paths found in Trash.', 'qrhunt' ),
			'filter_items_list'     => __( 'Filter paths list', 'qrhunt' ),
			'items_list_navigation' => __( 'Paths list navigation', 'qrhunt' ),
			'items_list'            => __( 'Paths list', 'qrhunt' ),
		);
	}
}
