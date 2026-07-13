<?php
namespace QRHunt\Controller;

use QRHunt\Model\Group;
use QRHunt\Service\GroupService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class GroupController {
	private $group_service;
	private $path_service;

	public function __construct( GroupService $group_service, PathService $path_service ) {
		$this->group_service = $group_service;
		$this->path_service  = $path_service;
	}

	public function register_page(): void {
		add_submenu_page( 'qrhunt', __( 'Groups', 'qrhunt' ), __( 'Groups', 'qrhunt' ), 'edit_posts', 'qrhunt-groups', array( $this, 'render_page' ) );
	}

	public function render_page(): void {
		$paths        = $this->path_service->get_paths();
		$groups       = $this->group_service->get_groups();
		$path_names   = $this->get_path_names( $paths );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query parameter used only to preload the selected Group into the admin edit form; this request does not change any data.
		$editing_id   = isset( $_GET['group_id'] ) ? absint( wp_unslash( $_GET['group_id'] ) ) : 0;
		$editing_group = 0 === $editing_id ? null : $this->group_service->get_group( $editing_id );
		$is_edit_mode = null !== $editing_group;
		$form_title   = $is_edit_mode ? __( 'Edit Group', 'qrhunt' ) : __( 'Add Group', 'qrhunt' );
		$button_label = $is_edit_mode ? __( 'Update Group', 'qrhunt' ) : __( 'Add Group', 'qrhunt' );
		$path_id      = $is_edit_mode ? (int) $editing_group->get_path_id() : 0;
		$name         = $is_edit_mode ? (string) $editing_group->get_name() : '';
		$description  = $is_edit_mode ? (string) $editing_group->get_description() : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Groups', 'qrhunt' ); ?></h1>

			<h2><?php echo esc_html( $form_title ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'qrhunt_save_group', 'qrhunt_group_nonce' ); ?>
				<input type="hidden" name="action" value="qrhunt_save_group" />
				<?php if ( $is_edit_mode ) : ?>
					<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) $editing_group->get_id() ); ?>" />
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="qrhunt-group-path"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
							</th>
							<td>
								<select id="qrhunt-group-path" name="path_id" required>
									<option value=""><?php esc_html_e( 'Select a Path', 'qrhunt' ); ?></option>
									<?php foreach ( $paths as $path ) : ?>
										<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>>
											<?php echo esc_html( $path->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="qrhunt-group-name"><?php esc_html_e( 'Name', 'qrhunt' ); ?></label>
							</th>
							<td>
								<input id="qrhunt-group-name" class="regular-text" name="name" type="text" value="<?php echo esc_attr( $name ); ?>" required />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="qrhunt-group-description"><?php esc_html_e( 'Description', 'qrhunt' ); ?></label>
							</th>
							<td>
								<textarea id="qrhunt-group-description" class="large-text" name="description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( $button_label ); ?>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Description', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Path', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'qrhunt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $groups as $group ) : ?>
						<tr>
							<td><?php echo esc_html( $group->get_name() ); ?></td>
							<td><?php echo esc_html( (string) $group->get_description() ); ?></td>
							<td><?php echo esc_html( $path_names[ $group->get_path_id() ] ?? '' ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=qrhunt-groups&group_id=' . $group->get_id() ) ); ?>">
									<?php esc_html_e( 'Edit', 'qrhunt' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'qrhunt_delete_group_' . $group->get_id(), 'qrhunt_group_nonce' ); ?>
									<input type="hidden" name="action" value="qrhunt_delete_group" />
									<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) $group->get_id() ); ?>" />
									<button type="submit" class="button-link-delete"><?php esc_html_e( 'Delete', 'qrhunt' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function save(): void {
		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_group_nonce'] ) ), 'qrhunt_save_group' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$group    = new Group();

		if ( 0 !== $group_id ) {
			$group->set_id( $group_id );
		}

		$group->set_path_id( absint( wp_unslash( $_POST['path_id'] ?? 0 ) ) );
		$group->set_name( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ) );
		$group->set_description( sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) );

		$this->group_service->save_group( $group );

		wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-groups' ) );
		exit;
	}

	public function delete(): void {
		$id = absint( wp_unslash( $_POST['group_id'] ?? 0 ) );

		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_group_nonce'] ) ), 'qrhunt_delete_group_' . $id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$this->group_service->delete_group( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-groups' ) );
		exit;
	}

	private function get_path_names( array $paths ): array {
		$path_names = array();

		foreach ( $paths as $path ) {
			$path_names[ $path->get_id() ] = $path->get_name();
		}

		return $path_names;
	}
}
