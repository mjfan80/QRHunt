<?php
/**
 * Checkpoint custom post type registration.
 *
 * @package QuestUno
 */

namespace QuestUno;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Checkpoint custom post type.
 */
final class CheckpointPostType {

	public const POST_TYPE = 'questuno_checkpoint';

	/**
	 * Registers the Checkpoint custom post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $this->get_labels(),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_nav_menus'   => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			)
		);
	}

	/**
	 * Gets the labels for the Checkpoint custom post type.
	 *
	 * @return array<string, string>
	 */
	private function get_labels(): array {
		return array(
			'name'                  => _x( 'Checkpoints', 'Post type general name', 'questuno' ),
			'singular_name'         => _x( 'Checkpoint', 'Post type singular name', 'questuno' ),
			'menu_name'             => _x( 'Checkpoints', 'Admin Menu text', 'questuno' ),
			'name_admin_bar'        => _x( 'Checkpoint', 'Add New on Toolbar', 'questuno' ),
			'add_new'               => __( 'Add New', 'questuno' ),
			'add_new_item'          => __( 'Add New Checkpoint', 'questuno' ),
			'new_item'              => __( 'New Checkpoint', 'questuno' ),
			'edit_item'             => __( 'Edit Checkpoint', 'questuno' ),
			'view_item'             => __( 'View Checkpoint', 'questuno' ),
			'all_items'             => __( 'All Checkpoints', 'questuno' ),
			'search_items'          => __( 'Search Checkpoints', 'questuno' ),
			'not_found'             => __( 'No checkpoints found.', 'questuno' ),
			'not_found_in_trash'    => __( 'No checkpoints found in Trash.', 'questuno' ),
			'featured_image'        => _x( 'Featured image', 'Overrides the “Featured Image” phrase', 'questuno' ),
			'set_featured_image'    => _x( 'Set featured image', 'Overrides the “Set featured image” phrase', 'questuno' ),
			'remove_featured_image' => _x( 'Remove featured image', 'Overrides the “Remove featured image” phrase', 'questuno' ),
			'use_featured_image'    => _x( 'Use as featured image', 'Overrides the “Use as featured image” phrase', 'questuno' ),
			'filter_items_list'     => __( 'Filter checkpoints list', 'questuno' ),
			'items_list_navigation' => __( 'Checkpoints list navigation', 'questuno' ),
			'items_list'            => __( 'Checkpoints list', 'questuno' ),
		);
	}
}
