<?php
/**
 * Path custom post type registration.
 *
 * @package QuestUno
 */

namespace QuestUno;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Path custom post type.
 */
final class PathPostType {

	public const POST_TYPE = 'questuno_path';

	public const ARCHIVED_STATUS = 'questuno_archived';

	/**
	 * Registers the Path custom post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_status(
			self::ARCHIVED_STATUS,
			array(
				'label'                     => _x( 'Archived', 'Path post status', 'questuno' ),
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
					'questuno'
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
			'name'                  => _x( 'Paths', 'Post type general name', 'questuno' ),
			'singular_name'         => _x( 'Path', 'Post type singular name', 'questuno' ),
			'menu_name'             => _x( 'Paths', 'Admin Menu text', 'questuno' ),
			'name_admin_bar'        => _x( 'Path', 'Add New on Toolbar', 'questuno' ),
			'add_new'               => __( 'Add New', 'questuno' ),
			'add_new_item'          => __( 'Add New Path', 'questuno' ),
			'new_item'              => __( 'New Path', 'questuno' ),
			'edit_item'             => __( 'Edit Path', 'questuno' ),
			'view_item'             => __( 'View Path', 'questuno' ),
			'all_items'             => __( 'All Paths', 'questuno' ),
			'search_items'          => __( 'Search Paths', 'questuno' ),
			'not_found'             => __( 'No paths found.', 'questuno' ),
			'not_found_in_trash'    => __( 'No paths found in Trash.', 'questuno' ),
			'filter_items_list'     => __( 'Filter paths list', 'questuno' ),
			'items_list_navigation' => __( 'Paths list navigation', 'questuno' ),
			'items_list'            => __( 'Paths list', 'questuno' ),
		);
	}
}
