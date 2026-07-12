<?php
/**
 * Checkpoint controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Checkpoint;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class CheckpointController {

	private $checkpoint_service;
	private $path_service;

	public function __construct( CheckpointService $checkpoint_service, PathService $path_service ) {
		$this->checkpoint_service = $checkpoint_service;
		$this->path_service       = $path_service;
	}

	public function register_metabox(): void {
		add_meta_box( 'qrhunt-checkpoint-path', __( 'Path', 'qrhunt' ), array( $this, 'render_path_metabox' ), 'qrhunt_checkpoint', 'side' );
	}

	public function render_path_metabox( \WP_Post $post ): void {
		$checkpoint = $this->checkpoint_service->get_checkpoint( $post->ID );
		$path_id    = null === $checkpoint ? 0 : $checkpoint->get_path_id();

		wp_nonce_field( 'qrhunt_checkpoint_path', 'qrhunt_checkpoint_path_nonce' );
		?>
		<label for="qrhunt-path-id"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
		<select id="qrhunt-path-id" name="qrhunt_path_id">
			<option value="0"><?php esc_html_e( 'Select a Path', 'qrhunt' ); ?></option>
			<?php foreach ( $this->path_service->get_paths() as $path ) : ?>
				<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>><?php echo esc_html( $path->get_name() ); ?></option>
			<?php endforeach; ?>
		</select>
		<p>
			<label for="qrhunt-checkpoint-token"><?php esc_html_e( 'Token', 'qrhunt' ); ?></label>
			<?php if ( null === $checkpoint ) : ?>
				<input id="qrhunt-checkpoint-token" type="text" value="" readonly="readonly" />
				<span class="description"><?php esc_html_e( 'Il token verrà generato al primo salvataggio.', 'qrhunt' ); ?></span>
			<?php else : ?>
				<input id="qrhunt-checkpoint-token" type="text" value="<?php echo esc_attr( $checkpoint->get_token() ); ?>" readonly="readonly" />
			<?php endif; ?>
		</p>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['qrhunt_checkpoint_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_checkpoint_path_nonce'] ) ), 'qrhunt_checkpoint_path' ) || ! isset( $_POST['qrhunt_path_id'] ) ) {
			return;
		}

		$checkpoint = new Checkpoint();
		$checkpoint->set_post_id( $post_id );
		$checkpoint->set_path_id( absint( wp_unslash( $_POST['qrhunt_path_id'] ) ) );
		$this->checkpoint_service->save_path( $checkpoint );
	}
}
