<?php
/**
 * Participation controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Participation;
use QRHunt\Service\ParticipationService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Participation administration.
 */
final class ParticipationController {

	/** @var ParticipationService */
	private $participation_service;

	/** @var PathService */
	private $path_service;

	/**
	 * Creates a Participation controller.
	 *
	 * @param ParticipationService $participation_service Participation service.
	 * @param PathService          $path_service          Path service.
	 */
	public function __construct( ParticipationService $participation_service, PathService $path_service ) {
		$this->participation_service = $participation_service;
		$this->path_service          = $path_service;
	}

	/**
	 * Registers the Participation admin page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'qrhunt',
			__( 'Participations', 'qrhunt' ),
			__( 'Participations', 'qrhunt' ),
			'edit_posts',
			'qrhunt-participations',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the Participation admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$paths               = $this->path_service->get_paths();
		$participations      = $this->participation_service->get_participations();
		$path_names          = $this->get_path_names( $paths );
		$users               = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		$user_labels         = $this->get_user_labels( $users );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query parameter used only to preload the selected Participation into the admin edit form; this request does not change any data.
		$editing_id          = isset( $_GET['participation_id'] ) ? absint( wp_unslash( $_GET['participation_id'] ) ) : 0;
		$editing_participation = 0 === $editing_id ? null : $this->participation_service->get_participation( $editing_id );
		$is_edit_mode        = null !== $editing_participation;
		$form_title          = $is_edit_mode ? __( 'Edit Participation', 'qrhunt' ) : __( 'Add Participation', 'qrhunt' );
		$button_label        = $is_edit_mode ? __( 'Update Participation', 'qrhunt' ) : __( 'Add Participation', 'qrhunt' );
		$user_id             = $is_edit_mode ? (int) $editing_participation->get_user_id() : 0;
		$path_id             = $is_edit_mode ? (int) $editing_participation->get_path_id() : 0;
		$status              = $is_edit_mode ? (string) $editing_participation->get_status() : 'in_progress';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Participations', 'qrhunt' ); ?></h1>

			<h2><?php echo esc_html( $form_title ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'qrhunt_save_participation', 'qrhunt_participation_nonce' ); ?>
				<input type="hidden" name="action" value="qrhunt_save_participation" />
				<?php if ( $is_edit_mode ) : ?>
					<input type="hidden" name="participation_id" value="<?php echo esc_attr( (string) $editing_participation->get_id() ); ?>" />
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="qrhunt-participation-user"><?php esc_html_e( 'User', 'qrhunt' ); ?></label>
							</th>
							<td>
								<select id="qrhunt-participation-user" name="user_id" required>
									<option value=""><?php esc_html_e( 'Select a User', 'qrhunt' ); ?></option>
									<?php foreach ( $users as $user ) : ?>
										<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $user_id, $user->ID ); ?>>
											<?php echo esc_html( $user_labels[ $user->ID ] ?? (string) $user->ID ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="qrhunt-participation-path"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
							</th>
							<td>
								<select id="qrhunt-participation-path" name="path_id" required>
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
								<label for="qrhunt-participation-status"><?php esc_html_e( 'Status', 'qrhunt' ); ?></label>
							</th>
							<td>
								<select id="qrhunt-participation-status" name="status" required>
									<option value="in_progress" <?php selected( $status, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'qrhunt' ); ?></option>
									<option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'qrhunt' ); ?></option>
									<option value="finished" <?php selected( $status, 'finished' ); ?>><?php esc_html_e( 'Finished', 'qrhunt' ); ?></option>
									<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'qrhunt' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( $button_label ); ?>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'User', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Path', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'qrhunt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $participations as $participation ) : ?>
						<tr>
							<td><?php echo esc_html( $user_labels[ $participation->get_user_id() ] ?? (string) $participation->get_user_id() ); ?></td>
							<td><?php echo esc_html( $path_names[ $participation->get_path_id() ] ?? '' ); ?></td>
							<td><?php echo esc_html( (string) $participation->get_status() ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=qrhunt-participations&participation_id=' . $participation->get_id() ) ); ?>">
									<?php esc_html_e( 'Edit', 'qrhunt' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'qrhunt_delete_participation_' . $participation->get_id(), 'qrhunt_participation_nonce' ); ?>
									<input type="hidden" name="action" value="qrhunt_delete_participation" />
									<input type="hidden" name="participation_id" value="<?php echo esc_attr( (string) $participation->get_id() ); ?>" />
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

	/**
	 * Saves a Participation.
	 *
	 * @return void
	 */
	public function save(): void {
		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_participation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_participation_nonce'] ) ), 'qrhunt_save_participation' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$participation_id = isset( $_POST['participation_id'] ) ? absint( wp_unslash( $_POST['participation_id'] ) ) : 0;
		$participation    = new Participation();

		if ( 0 !== $participation_id ) {
			$participation->set_id( $participation_id );
		}

		$participation->set_user_id( absint( wp_unslash( $_POST['user_id'] ?? 0 ) ) );
		$participation->set_path_id( absint( wp_unslash( $_POST['path_id'] ?? 0 ) ) );
		$participation->set_status( sanitize_key( wp_unslash( $_POST['status'] ?? 'in_progress' ) ) );

		$this->participation_service->save_participation( $participation );

		wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-participations' ) );
		exit;
	}

	/**
	 * Deletes a Participation.
	 *
	 * @return void
	 */
	public function delete(): void {
		$id = absint( wp_unslash( $_POST['participation_id'] ?? 0 ) );

		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_participation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_participation_nonce'] ) ), 'qrhunt_delete_participation_' . $id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$this->participation_service->delete_participation( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-participations' ) );
		exit;
	}

	/**
	 * Builds Path names indexed by identifier.
	 *
	 * @param array<int, \QRHunt\Model\Path> $paths Paths.
	 * @return array<int, string>
	 */
	private function get_path_names( array $paths ): array {
		$path_names = array();

		foreach ( $paths as $path ) {
			$path_names[ $path->get_id() ] = $path->get_name();
		}

		return $path_names;
	}

	/**
	 * Builds user labels indexed by user ID.
	 *
	 * @param array<int, \WP_User> $users Users.
	 * @return array<int, string>
	 */
	private function get_user_labels( array $users ): array {
		$user_labels = array();

		foreach ( $users as $user ) {
			$user_labels[ $user->ID ] = sprintf(
				'%1$s (%2$s)',
				$user->display_name,
				$user->user_email
			);
		}

		return $user_labels;
	}
}
