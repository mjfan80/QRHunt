<?php
/**
 * Path controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Path;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class PathController {

	/** @var PathService */
	private $path_service;

	public function __construct( PathService $path_service ) {
		$this->path_service = $path_service;
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$path = new Path();
		$path->set_post_id( $post_id );
		$path->set_name( $post->post_title );
		$path->set_description( $post->post_content );
		$path->set_status( $post->post_status );
		$this->path_service->save_path( $path );
	}
}
