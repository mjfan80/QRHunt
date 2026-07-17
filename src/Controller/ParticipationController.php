<?php
/**
 * Participation controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationStatus;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\EventService;
use QRHunt\Service\ParticipationProgressBuilder;
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

	/** @var ParticipationProgressBuilder */
	private $participation_progress_builder;

	/** @var EventService */
	private $event_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/**
	 * Creates a Participation controller.
	 *
	 * @param ParticipationService         $participation_service          Participation service.
	 * @param PathService                  $path_service                   Path service.
	 * @param ParticipationProgressBuilder $participation_progress_builder Participation progress builder.
	 * @param EventService                 $event_service                  Event service.
	 * @param CheckpointService            $checkpoint_service             Checkpoint service.
	 */
	public function __construct(
		ParticipationService $participation_service,
		PathService $path_service,
		ParticipationProgressBuilder $participation_progress_builder,
		EventService $event_service,
		CheckpointService $checkpoint_service
	) {
		$this->participation_service          = $participation_service;
		$this->path_service                   = $path_service;
		$this->participation_progress_builder = $participation_progress_builder;
		$this->event_service                  = $event_service;
		$this->checkpoint_service             = $checkpoint_service;
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
		$paths       = $this->path_service->get_paths();
		$path_names  = $this->get_path_names( $paths );
		$users       = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		$user_labels = $this->get_user_labels( $users );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters used to render the admin listing; this request does not change data.
		$filter_path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$filter_user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;
		$filter_status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$detail_id      = isset( $_GET['participation_id'] ) ? absint( wp_unslash( $_GET['participation_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $this->participation_service->is_valid_status( $filter_status ) ) {
			$filter_status = '';
		}

		$participations = $this->participation_service->get_participations_by_filters( $filter_path_id, $filter_user_id, $filter_status );
		$detail         = 0 === $detail_id ? null : $this->participation_service->get_participation( $detail_id );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Participations', 'qrhunt' ); ?></h1>

			<form method="get">
				<input type="hidden" name="page" value="qrhunt-participations" />
				<label for="qrhunt-filter-path"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
				<select id="qrhunt-filter-path" name="path_id">
					<option value="0"><?php esc_html_e( 'All Paths', 'qrhunt' ); ?></option>
					<?php foreach ( $paths as $path ) : ?>
						<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $filter_path_id, $path->get_id() ); ?>>
							<?php echo esc_html( $path->get_name() ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="qrhunt-filter-user"><?php esc_html_e( 'User', 'qrhunt' ); ?></label>
				<select id="qrhunt-filter-user" name="user_id">
					<option value="0"><?php esc_html_e( 'All Users', 'qrhunt' ); ?></option>
					<?php foreach ( $users as $user ) : ?>
						<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $filter_user_id, $user->ID ); ?>>
							<?php echo esc_html( $user_labels[ $user->ID ] ?? (string) $user->ID ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="qrhunt-filter-status"><?php esc_html_e( 'Status', 'qrhunt' ); ?></label>
				<select id="qrhunt-filter-status" name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'qrhunt' ); ?></option>
					<?php foreach ( $this->get_status_labels() as $status => $label ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $filter_status, $status ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<?php submit_button( __( 'Filter', 'qrhunt' ), 'secondary', '', false ); ?>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'User', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Path', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Started at', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Finished at', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Cancelled at', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Validated Checkpoints', 'qrhunt' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'qrhunt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $participations as $participation ) : ?>
						<?php $progress = $this->participation_progress_builder->build( $participation ); ?>
						<tr>
							<td><?php echo esc_html( $user_labels[ $participation->get_user_id() ] ?? (string) $participation->get_user_id() ); ?></td>
							<td><?php echo esc_html( $path_names[ $participation->get_path_id() ] ?? '' ); ?></td>
							<td><?php echo esc_html( $this->get_status_labels()[ (string) $participation->get_status() ] ?? (string) $participation->get_status() ); ?></td>
							<td><?php echo esc_html( $this->format_datetime( $participation->get_started_at() ) ); ?></td>
							<td><?php echo esc_html( $this->format_datetime( $participation->get_finished_at() ) ); ?></td>
							<td><?php echo esc_html( $this->format_datetime( $participation->get_cancelled_at() ) ); ?></td>
							<td><?php echo esc_html( (string) count( $progress->get_validated_checkpoint_ids() ) ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=qrhunt-participations&participation_id=' . $participation->get_id() ) ); ?>">
									<?php esc_html_e( 'View', 'qrhunt' ); ?>
								</a>
								<?php if ( ParticipationStatus::CANCELLED !== $participation->get_status() ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qrhunt-inline-form">
										<?php wp_nonce_field( 'qrhunt_cancel_participation_' . $participation->get_id(), 'qrhunt_participation_nonce' ); ?>
										<input type="hidden" name="action" value="qrhunt_cancel_participation" />
										<input type="hidden" name="participation_id" value="<?php echo esc_attr( (string) $participation->get_id() ); ?>" />
										<button type="submit" class="button-link-delete"><?php esc_html_e( 'Cancel', 'qrhunt' ); ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $detail instanceof Participation ) : ?>
				<?php $this->render_detail( $detail, $user_labels, $path_names ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Cancels a Participation.
	 *
	 * @return void
	 */
	public function cancel(): void {
		$id = absint( wp_unslash( $_POST['participation_id'] ?? 0 ) );

		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_participation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_participation_nonce'] ) ), 'qrhunt_cancel_participation_' . $id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$this->participation_service->cancel_participation( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-participations&participation_id=' . $id ) );
		exit;
	}

	/**
	 * Renders Participation detail and history.
	 *
	 * @param Participation      $participation Participation.
	 * @param array<int,string>  $user_labels   User labels.
	 * @param array<int,string>  $path_names    Path names.
	 * @return void
	 */
	private function render_detail( Participation $participation, array $user_labels, array $path_names ): void {
		$events = null === $participation->get_id() ? array() : $this->event_service->get_events_by_participation( (int) $participation->get_id() );
		?>
		<h2><?php esc_html_e( 'Participation detail', 'qrhunt' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<tr><th scope="row"><?php esc_html_e( 'User', 'qrhunt' ); ?></th><td><?php echo esc_html( $user_labels[ $participation->get_user_id() ] ?? (string) $participation->get_user_id() ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Path', 'qrhunt' ); ?></th><td><?php echo esc_html( $path_names[ $participation->get_path_id() ] ?? '' ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Status', 'qrhunt' ); ?></th><td><?php echo esc_html( $this->get_status_labels()[ (string) $participation->get_status() ] ?? (string) $participation->get_status() ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Created at', 'qrhunt' ); ?></th><td><?php echo esc_html( $this->format_datetime( $participation->get_created_at() ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Started at', 'qrhunt' ); ?></th><td><?php echo esc_html( $this->format_datetime( $participation->get_started_at() ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Finished at', 'qrhunt' ); ?></th><td><?php echo esc_html( $this->format_datetime( $participation->get_finished_at() ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Cancelled at', 'qrhunt' ); ?></th><td><?php echo esc_html( $this->format_datetime( $participation->get_cancelled_at() ) ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'History', 'qrhunt' ); ?></h3>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Date', 'qrhunt' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Checkpoint', 'qrhunt' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'qrhunt' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Result', 'qrhunt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $events as $event ) : ?>
					<tr>
						<td><?php echo esc_html( $this->format_datetime( $event->get_created_at() ) ); ?></td>
						<td><?php echo esc_html( null === $event->get_checkpoint_id() ? '' : $this->checkpoint_service->get_checkpoint_title( (int) $event->get_checkpoint_id() ) ); ?></td>
						<td><?php echo esc_html( (string) $event->get_event_type() ); ?></td>
						<td><?php echo esc_html( (string) $event->get_result() ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
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

	/**
	 * Gets Participation status labels.
	 *
	 * @return array<string, string>
	 */
	private function get_status_labels(): array {
		return array(
			ParticipationStatus::IN_PROGRESS => __( 'In Progress', 'qrhunt' ),
			ParticipationStatus::FINISHED    => __( 'Finished', 'qrhunt' ),
			ParticipationStatus::COMPLETED   => __( 'Completed', 'qrhunt' ),
			ParticipationStatus::CANCELLED   => __( 'Cancelled', 'qrhunt' ),
		);
	}

	/**
	 * Formats a stored MySQL datetime for display.
	 *
	 * @param string|null $datetime Stored datetime.
	 * @return string
	 */
	private function format_datetime( ?string $datetime ): string {
		if ( null === $datetime || '' === $datetime ) {
			return '';
		}

		$timestamp = strtotime( $datetime );

		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
