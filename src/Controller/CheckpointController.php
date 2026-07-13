<?php
/**
 * Checkpoint controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Checkpoint;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\GroupService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class CheckpointController {

	private $checkpoint_service;
	private $group_service;
	private $path_service;

	public function __construct( CheckpointService $checkpoint_service, GroupService $group_service, PathService $path_service ) {
		$this->checkpoint_service = $checkpoint_service;
		$this->group_service      = $group_service;
		$this->path_service       = $path_service;
	}

	public function register_metabox(): void {
		add_meta_box( 'qrhunt-checkpoint-path', __( 'Path', 'qrhunt' ), array( $this, 'render_path_metabox' ), 'qrhunt_checkpoint', 'side' );
	}

	public function render_path_metabox( \WP_Post $post ): void {
		$checkpoint          = $this->checkpoint_service->get_checkpoint( $post->ID );
		$path_id             = null === $checkpoint ? 0 : (int) $checkpoint->get_path_id();
		$group_id            = null === $checkpoint || null === $checkpoint->get_group_id() ? 0 : (int) $checkpoint->get_group_id();
		$paths               = $this->path_service->get_paths();
		$groups              = $this->group_service->get_groups();
		$groups_by_path      = $this->group_service->get_groups_by_path( $path_id );
		$available_group_ids = array();

		foreach ( $groups_by_path as $group ) {
			$available_group_ids[ $group->get_id() ] = true;
		}

		wp_nonce_field( 'qrhunt_checkpoint_path', 'qrhunt_checkpoint_path_nonce' );
		?>
		<label for="qrhunt-path-id"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
		<select id="qrhunt-path-id" name="qrhunt_path_id">
			<option value="0"><?php esc_html_e( 'Select a Path', 'qrhunt' ); ?></option>
			<?php foreach ( $paths as $path ) : ?>
				<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>><?php echo esc_html( $path->get_name() ); ?></option>
			<?php endforeach; ?>
		</select>
		<p>
			<label for="qrhunt-group-id"><?php esc_html_e( 'Group', 'qrhunt' ); ?></label>
			<select id="qrhunt-group-id" name="qrhunt_group_id">
				<option value="0"><?php esc_html_e( 'No Group', 'qrhunt' ); ?></option>
				<?php foreach ( $groups as $group ) : ?>
					<?php $is_available = isset( $available_group_ids[ $group->get_id() ] ); ?>
					<option
						value="<?php echo esc_attr( (string) $group->get_id() ); ?>"
						data-path-id="<?php echo esc_attr( (string) $group->get_path_id() ); ?>"
						<?php selected( $group_id, $group->get_id() ); ?>
						<?php disabled( ! $is_available ); ?>
						<?php echo $is_available ? '' : 'hidden'; ?>
					>
						<?php echo esc_html( $group->get_name() ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="qrhunt-checkpoint-token"><?php esc_html_e( 'Token', 'qrhunt' ); ?></label>
			<?php if ( null === $checkpoint ) : ?>
				<input id="qrhunt-checkpoint-token" type="text" value="" readonly="readonly" />
				<span class="description"><?php esc_html_e( 'Il token verrà generato al primo salvataggio.', 'qrhunt' ); ?></span>
			<?php else : ?>
				<input id="qrhunt-checkpoint-token" type="text" value="<?php echo esc_attr( $checkpoint->get_token() ); ?>" readonly="readonly" />
			<?php endif; ?>
		</p>
		<script>
			(function() {
				const pathField = document.getElementById( 'qrhunt-path-id' );
				const groupField = document.getElementById( 'qrhunt-group-id' );

				if ( ! pathField || ! groupField ) {
					return;
				}

				const syncGroups = function() {
					const selectedPathId = pathField.value;
					let hasSelectedGroup = false;

					Array.from( groupField.options ).forEach( function( option, index ) {
						if ( 0 === index ) {
							return;
						}

						const matchesPath = option.dataset.pathId === selectedPathId;

						option.hidden = ! matchesPath;
						option.disabled = ! matchesPath;

						if ( matchesPath && option.selected ) {
							hasSelectedGroup = true;
						}
					} );

					if ( ! hasSelectedGroup ) {
						groupField.value = '0';
					}
				};

				pathField.addEventListener( 'change', syncGroups );
				syncGroups();
			}() );
		</script>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['qrhunt_checkpoint_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_checkpoint_path_nonce'] ) ), 'qrhunt_checkpoint_path' ) || ! isset( $_POST['qrhunt_path_id'] ) ) {
			return;
		}

		$path_id    = absint( wp_unslash( $_POST['qrhunt_path_id'] ) );
		$group_id   = isset( $_POST['qrhunt_group_id'] ) ? absint( wp_unslash( $_POST['qrhunt_group_id'] ) ) : 0;
		$checkpoint = new Checkpoint();

		$checkpoint->set_post_id( $post_id );
		$checkpoint->set_path_id( $path_id );
		$checkpoint->set_group_id( 0 === $group_id ? null : $group_id );

		$this->checkpoint_service->save_path( $checkpoint );
	}
}
