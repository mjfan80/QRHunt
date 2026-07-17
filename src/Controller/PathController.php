<?php
/**
 * Path controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\PathPostType;
use QRHunt\Model\Path;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class PathController {

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/**
	 * Creates a Path controller.
	 *
	 * @param PathService       $path_service       Path service.
	 * @param CheckpointService $checkpoint_service Checkpoint service.
	 */
	public function __construct( PathService $path_service, CheckpointService $checkpoint_service ) {
		$this->path_service       = $path_service;
		$this->checkpoint_service = $checkpoint_service;
	}

	/**
	 * Registers Path metaboxes.
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		add_meta_box(
			'qrhunt-path-checkpoints',
			__( 'Path Checkpoints', 'qrhunt' ),
			array( $this, 'render_checkpoints_metabox' ),
			PathPostType::POST_TYPE,
			'side'
		);
	}

	/**
	 * Renders the start and finish Checkpoint metabox.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return void
	 */
	public function render_checkpoints_metabox( \WP_Post $post ): void {
		$path = $this->path_service->get_path_by_post_id( $post->ID );
		$path_id = null === $path || null === $path->get_id()
			? 0
			: (int) $path->get_id();
		$start_checkpoint_id = null === $path || null === $path->get_start_checkpoint_id()
			? 0
			: (int) $path->get_start_checkpoint_id();
		$finish_checkpoint_id = null === $path || null === $path->get_finish_checkpoint_id()
			? 0
			: (int) $path->get_finish_checkpoint_id();
		$checkpoints = 0 === $path_id
			? array()
			: $this->checkpoint_service->get_checkpoints_by_path( $path_id );

		wp_nonce_field( 'qrhunt_path_checkpoints', 'qrhunt_path_checkpoints_nonce' );
		?>
		<p>
			<label for="qrhunt-start-checkpoint-id"><?php esc_html_e( 'Start Checkpoint', 'qrhunt' ); ?></label>
			<select id="qrhunt-start-checkpoint-id" name="qrhunt_start_checkpoint_id">
				<option value="0"><?php esc_html_e( 'Select a Checkpoint', 'qrhunt' ); ?></option>
				<?php foreach ( $checkpoints as $checkpoint ) : ?>
					<?php $checkpoint_id = (int) $checkpoint->get_post_id(); ?>
					<option value="<?php echo esc_attr( (string) $checkpoint_id ); ?>" <?php selected( $start_checkpoint_id, $checkpoint_id ); ?>>
						<?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( $checkpoint_id ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="qrhunt-finish-checkpoint-id"><?php esc_html_e( 'Finish Checkpoint', 'qrhunt' ); ?></label>
			<select id="qrhunt-finish-checkpoint-id" name="qrhunt_finish_checkpoint_id">
				<option value="0"><?php esc_html_e( 'Select a Checkpoint', 'qrhunt' ); ?></option>
				<?php foreach ( $checkpoints as $checkpoint ) : ?>
					<?php $checkpoint_id = (int) $checkpoint->get_post_id(); ?>
					<option value="<?php echo esc_attr( (string) $checkpoint_id ); ?>" <?php selected( $finish_checkpoint_id, $checkpoint_id ); ?>>
						<?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( $checkpoint_id ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php if ( empty( $checkpoints ) ) : ?>
			<p class="description">
				<?php esc_html_e( 'Save this Path and assign Checkpoints to it before selecting start and finish Checkpoints.', 'qrhunt' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Saves Path technical data after post save.
	 *
	 * @param int      $post_id Post identifier.
	 * @param \WP_Post $post    WordPress post object.
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$stored_path = $this->path_service->get_path_by_post_id( $post_id );
		$path = new Path();
		$path->set_post_id( $post_id );
		$path->set_name( $post->post_title );
		$path->set_description( $post->post_content );
		$path->set_status( $post->post_status );

		if ( null !== $stored_path ) {
			$path->set_start_checkpoint_id( $stored_path->get_start_checkpoint_id() );
			$path->set_finish_checkpoint_id( $stored_path->get_finish_checkpoint_id() );
		}

		$has_valid_checkpoints_nonce = isset( $_POST['qrhunt_path_checkpoints_nonce'] )
			&& wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['qrhunt_path_checkpoints_nonce'] ) ),
				'qrhunt_path_checkpoints'
			);

		if ( $has_valid_checkpoints_nonce && null !== $stored_path && null !== $stored_path->get_id() ) {
			$checkpoint_ids        = $this->get_checkpoint_ids_by_path( (int) $stored_path->get_id() );
			$start_checkpoint_id  = isset( $_POST['qrhunt_start_checkpoint_id'] ) ? absint( wp_unslash( $_POST['qrhunt_start_checkpoint_id'] ) ) : 0;
			$finish_checkpoint_id = isset( $_POST['qrhunt_finish_checkpoint_id'] ) ? absint( wp_unslash( $_POST['qrhunt_finish_checkpoint_id'] ) ) : 0;

			if ( $start_checkpoint_id === $finish_checkpoint_id ) {
				$finish_checkpoint_id = 0;
			}

			$path->set_start_checkpoint_id(
				isset( $checkpoint_ids[ $start_checkpoint_id ] ) ? $start_checkpoint_id : null
			);
			$path->set_finish_checkpoint_id(
				isset( $checkpoint_ids[ $finish_checkpoint_id ] ) ? $finish_checkpoint_id : null
			);
		}

		$this->path_service->save_path( $path );
	}

	/**
	 * Gets Checkpoint post identifiers belonging to a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return array<int, bool>
	 */
	private function get_checkpoint_ids_by_path( int $path_id ): array {
		$checkpoint_ids = array();

		foreach ( $this->checkpoint_service->get_checkpoints_by_path( $path_id ) as $checkpoint ) {
			$checkpoint_ids[ (int) $checkpoint->get_post_id() ] = true;
		}

		return $checkpoint_ids;
	}
}
