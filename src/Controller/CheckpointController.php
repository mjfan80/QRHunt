<?php
/**
 * Checkpoint controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\CheckpointPostType;
use QRHunt\Model\Checkpoint;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\GroupService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class CheckpointController {

	private $checkpoint_service;
	private $dependency_controller;
	private $group_service;
	private $path_service;

	public function __construct( CheckpointService $checkpoint_service, DependencyController $dependency_controller, GroupService $group_service, PathService $path_service ) {
		$this->checkpoint_service   = $checkpoint_service;
		$this->dependency_controller = $dependency_controller;
		$this->group_service        = $group_service;
		$this->path_service         = $path_service;
	}

	public function register_metabox(): void {
		add_meta_box( 'qrhunt-checkpoint-path', __( 'Path', 'qrhunt' ), array( $this, 'render_path_metabox' ), CheckpointPostType::POST_TYPE, 'side' );
	}

	public function render_path_metabox( \WP_Post $post ): void {
		$checkpoint          = $this->checkpoint_service->get_checkpoint( $post->ID );
		$path_id             = null === $checkpoint ? 0 : (int) $checkpoint->get_path_id();
		$group_id            = null === $checkpoint || null === $checkpoint->get_group_id() ? 0 : (int) $checkpoint->get_group_id();
		$paths               = $this->path_service->get_paths();

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
			<select id="qrhunt-group-id" name="qrhunt_group_id" data-selected-group-id="<?php echo esc_attr( (string) $group_id ); ?>">
				<option value="0"><?php esc_html_e( 'No Group', 'qrhunt' ); ?></option>
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
		<?php $this->dependency_controller->render_section( $post, $path_id ); ?>
		<script>
			(function() {
				const pathField = document.getElementById( 'qrhunt-path-id' );
				const groupField = document.getElementById( 'qrhunt-group-id' );
				const groups = <?php echo wp_json_encode( $this->get_group_options() ); ?>;

				if ( ! pathField || ! groupField ) {
					return;
				}

				const syncGroups = function() {
					const selectedPathId = pathField.value;
					const selectedGroupId = groupField.dataset.selectedGroupId || groupField.value;
					let hasSelectedGroup = false;

					groupField.innerHTML = '';
					groupField.appendChild( new Option( '<?php echo esc_js( __( 'No Group', 'qrhunt' ) ); ?>', '0' ) );

					groups.forEach( function( group ) {
						if ( String( group.path_id ) !== selectedPathId ) {
							return;
						}

						const option = new Option( group.name, String( group.id ) );

						if ( String( group.id ) === selectedGroupId ) {
							option.selected = true;
							hasSelectedGroup = true;
						}

						groupField.appendChild( option );
					} );

					if ( ! hasSelectedGroup ) {
						groupField.value = '0';
					}

					groupField.dataset.selectedGroupId = groupField.value;
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
		$this->dependency_controller->save( $post_id );
	}

	private function get_group_options(): array {
		$groups = array();

		foreach ( $this->group_service->get_groups() as $group ) {
			$groups[] = array(
				'id'      => $group->get_id(),
				'path_id' => $group->get_path_id(),
				'name'    => $group->get_name(),
			);
		}

		return $groups;
	}
}
