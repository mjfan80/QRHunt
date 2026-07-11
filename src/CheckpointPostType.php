<?php
/**
 * Checkpoint custom post type registration.
 *
 * @package QRHunt
 */

namespace QRHunt;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Checkpoint custom post type.
 */
final class CheckpointPostType {

	/**
	 * Registers the Checkpoint custom post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_type(
			'qrhunt_checkpoint',
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
			'name'                  => _x( 'Checkpoints', 'Post type general name', 'qrhunt' ),
			'singular_name'         => _x( 'Checkpoint', 'Post type singular name', 'qrhunt' ),
			'menu_name'             => _x( 'Checkpoints', 'Admin Menu text', 'qrhunt' ),
			'name_admin_bar'        => _x( 'Checkpoint', 'Add New on Toolbar', 'qrhunt' ),
			'add_new'               => __( 'Add New', 'qrhunt' ),
			'add_new_item'          => __( 'Add New Checkpoint', 'qrhunt' ),
			'new_item'              => __( 'New Checkpoint', 'qrhunt' ),
			'edit_item'             => __( 'Edit Checkpoint', 'qrhunt' ),
			'view_item'             => __( 'View Checkpoint', 'qrhunt' ),
			'all_items'             => __( 'All Checkpoints', 'qrhunt' ),
			'search_items'          => __( 'Search Checkpoints', 'qrhunt' ),
			'not_found'             => __( 'No checkpoints found.', 'qrhunt' ),
			'not_found_in_trash'    => __( 'No checkpoints found in Trash.', 'qrhunt' ),
			'featured_image'        => _x( 'Featured image', 'Overrides the “Featured Image” phrase', 'qrhunt' ),
			'set_featured_image'    => _x( 'Set featured image', 'Overrides the “Set featured image” phrase', 'qrhunt' ),
			'remove_featured_image' => _x( 'Remove featured image', 'Overrides the “Remove featured image” phrase', 'qrhunt' ),
			'use_featured_image'    => _x( 'Use as featured image', 'Overrides the “Use as featured image” phrase', 'qrhunt' ),
			'filter_items_list'     => __( 'Filter checkpoints list', 'qrhunt' ),
			'items_list_navigation' => __( 'Checkpoints list navigation', 'qrhunt' ),
			'items_list'            => __( 'Checkpoints list', 'qrhunt' ),
		);
	}
}
