<?php
/**
 * Export controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\EventResult;
use QRHunt\Model\ParticipationStatus;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\ExportService;
use QRHunt\Service\PathService;
use QRHunt\Service\ParticipationService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles CSV exports in the administration area.
 */
final class ExportController {

	/** @var ExportService */
	private $export_service;

	/** @var PathService */
	private $path_service;

	/** @var ParticipationService */
	private $participation_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/**
	 * Creates an export controller.
	 *
	 * @param ExportService        $export_service        Export service.
	 * @param PathService          $path_service          Path service.
	 * @param ParticipationService $participation_service Participation service.
	 * @param CheckpointService    $checkpoint_service    Checkpoint service.
	 */
	public function __construct(
		ExportService $export_service,
		PathService $path_service,
		ParticipationService $participation_service,
		CheckpointService $checkpoint_service
	) {
		$this->export_service        = $export_service;
		$this->path_service          = $path_service;
		$this->participation_service = $participation_service;
		$this->checkpoint_service    = $checkpoint_service;
	}

	/**
	 * Registers the Exports admin page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'qrhunt',
			__( 'Exports', 'qrhunt' ),
			__( 'Exports', 'qrhunt' ),
			'edit_posts',
			'qrhunt-exports',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the Exports admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$paths       = $this->path_service->get_paths();
		$users       = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		$checkpoints = $this->checkpoint_service->get_checkpoints();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Exports', 'qrhunt' ); ?></h1>

			<h2><?php esc_html_e( 'Participations', 'qrhunt' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="qrhunt_export_csv" />
				<input type="hidden" name="export_type" value="participations" />
				<?php wp_nonce_field( 'qrhunt_export_participations', '_wpnonce', false ); ?>
				<?php $this->render_path_select( $paths, 'qrhunt-participations-path', 0 ); ?>
				<?php $this->render_user_select( $users, 'qrhunt-participations-user' ); ?>
				<label for="qrhunt-participations-status"><?php esc_html_e( 'Status', 'qrhunt' ); ?></label>
				<select id="qrhunt-participations-status" name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'qrhunt' ); ?></option>
					<?php foreach ( $this->get_status_labels() as $status => $label ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Export Participations CSV', 'qrhunt' ), 'secondary', '', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Events', 'qrhunt' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="qrhunt_export_csv" />
				<input type="hidden" name="export_type" value="events" />
				<?php wp_nonce_field( 'qrhunt_export_events', '_wpnonce', false ); ?>
				<?php $this->render_path_select( $paths, 'qrhunt-events-path', 0 ); ?>
				<?php $this->render_user_select( $users, 'qrhunt-events-user' ); ?>
				<label for="qrhunt-events-checkpoint"><?php esc_html_e( 'Checkpoint', 'qrhunt' ); ?></label>
				<select id="qrhunt-events-checkpoint" name="checkpoint_id">
					<option value="0"><?php esc_html_e( 'All Checkpoints', 'qrhunt' ); ?></option>
					<?php foreach ( $checkpoints as $checkpoint ) : ?>
						<option value="<?php echo esc_attr( (string) $checkpoint->get_post_id() ); ?>">
							<?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( (int) $checkpoint->get_post_id() ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<label for="qrhunt-events-result"><?php esc_html_e( 'Result', 'qrhunt' ); ?></label>
				<select id="qrhunt-events-result" name="result">
					<option value=""><?php esc_html_e( 'All Results', 'qrhunt' ); ?></option>
					<?php foreach ( $this->get_result_labels() as $result => $label ) : ?>
						<option value="<?php echo esc_attr( $result ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="qrhunt-events-date-from"><?php esc_html_e( 'From', 'qrhunt' ); ?></label>
				<input id="qrhunt-events-date-from" type="date" name="date_from" />
				<label for="qrhunt-events-date-to"><?php esc_html_e( 'To', 'qrhunt' ); ?></label>
				<input id="qrhunt-events-date-to" type="date" name="date_to" />
				<?php submit_button( __( 'Export Events CSV', 'qrhunt' ), 'secondary', '', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Path Statistics', 'qrhunt' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="qrhunt_export_csv" />
				<input type="hidden" name="export_type" value="path_statistics" />
				<?php wp_nonce_field( 'qrhunt_export_path_statistics', '_wpnonce', false ); ?>
				<?php $this->render_path_select( $paths, 'qrhunt-statistics-path', 0 ); ?>
				<?php submit_button( __( 'Export Path Statistics CSV', 'qrhunt' ), 'secondary', '', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Downloads a CSV export.
	 *
	 * @return void
	 */
	public function download(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$export_type = isset( $_GET['export_type'] ) ? sanitize_key( wp_unslash( $_GET['export_type'] ) ) : '';

		check_admin_referer( 'qrhunt_export_' . $export_type );

		if ( 'participations' === $export_type ) {
			$path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
			$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;
			$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

			if ( ! $this->participation_service->is_valid_status( $status ) ) {
				$status = '';
			}

			$this->output_csv( 'qrhunt-participations.csv', $this->export_service->get_participation_export( $path_id, $user_id, $status ) );
		}

		if ( 'events' === $export_type ) {
			$result = isset( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';

			if ( ! array_key_exists( $result, $this->get_result_labels() ) ) {
				$result = '';
			}

			$this->output_csv(
				'qrhunt-events.csv',
				$this->export_service->get_event_export(
					isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0,
					isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0,
					isset( $_GET['checkpoint_id'] ) ? absint( wp_unslash( $_GET['checkpoint_id'] ) ) : 0,
					$result,
					isset( $_GET['date_from'] ) ? $this->validate_date( sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) ) : '',
					isset( $_GET['date_to'] ) ? $this->validate_date( sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) ) : ''
				)
			);
		}

		if ( 'path_statistics' === $export_type ) {
			$path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;

			$this->output_csv( 'qrhunt-path-statistics.csv', $this->export_service->get_path_statistics_export( $path_id ) );
		}

		wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
	}

	/**
	 * Renders a Path select.
	 *
	 * @param array<int,\QRHunt\Model\Path> $paths    Paths.
	 * @param string                        $field_id Field identifier.
	 * @param int                           $selected Selected Path identifier.
	 * @return void
	 */
	private function render_path_select( array $paths, string $field_id, int $selected ): void {
		?>
		<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
		<select id="<?php echo esc_attr( $field_id ); ?>" name="path_id">
			<option value="0"><?php esc_html_e( 'All Paths', 'qrhunt' ); ?></option>
			<?php foreach ( $paths as $path ) : ?>
				<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $selected, $path->get_id() ); ?>>
					<?php echo esc_html( $path->get_name() ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Renders a User select.
	 *
	 * @param array<int,\WP_User> $users    Users.
	 * @param string              $field_id Field identifier.
	 * @return void
	 */
	private function render_user_select( array $users, string $field_id ): void {
		?>
		<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'User', 'qrhunt' ); ?></label>
		<select id="<?php echo esc_attr( $field_id ); ?>" name="user_id">
			<option value="0"><?php esc_html_e( 'All Users', 'qrhunt' ); ?></option>
			<?php foreach ( $users as $user ) : ?>
				<option value="<?php echo esc_attr( (string) $user->ID ); ?>">
					<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Gets Participation status labels.
	 *
	 * @return array<string,string>
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
	 * Gets Event result labels.
	 *
	 * @return array<string,string>
	 */
	private function get_result_labels(): array {
		return array(
			EventResult::ACCEPTED                => __( 'Accepted', 'qrhunt' ),
			EventResult::DUPLICATE               => __( 'Duplicate', 'qrhunt' ),
			EventResult::BEFORE_FAILED           => __( 'Before Failed', 'qrhunt' ),
			EventResult::AFTER_FAILED            => __( 'After Failed', 'qrhunt' ),
			EventResult::PATH_CLOSED             => __( 'Path Closed', 'qrhunt' ),
			EventResult::PARTICIPATION_CANCELLED => __( 'Participation Cancelled', 'qrhunt' ),
		);
	}

	/**
	 * Validates a date filter.
	 *
	 * @param string $date Date value.
	 * @return string
	 */
	private function validate_date( string $date ): string {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
	}

	/**
	 * Outputs a CSV response and terminates execution.
	 *
	 * @param string                                           $filename Filename.
	 * @param array{headers:array<int,string>,rows:array<int,array<int,string>>} $dataset  Dataset.
	 * @return void
	 */
	private function output_csv( string $filename, array $dataset ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$separator = $this->get_csv_separator();

		echo $this->build_csv_line( $dataset['headers'], $separator ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV response content is escaped for CSV format by build_csv_line().
		foreach ( $dataset['rows'] as $row ) {
			echo $this->build_csv_line( $row, $separator ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV response content is escaped for CSV format by build_csv_line().
		}

		exit;
	}

	/**
	 * Builds one CSV line.
	 *
	 * @param array<int,string> $fields    Fields.
	 * @param string            $separator CSV separator.
	 * @return string
	 */
	private function build_csv_line( array $fields, string $separator ): string {
		$escaped_fields = array();

		foreach ( $fields as $field ) {
			$field = str_replace( '"', '""', $field );

			if ( str_contains( $field, $separator ) || str_contains( $field, '"' ) || str_contains( $field, "\r" ) || str_contains( $field, "\n" ) ) {
				$field = '"' . $field . '"';
			}

			$escaped_fields[] = $field;
		}

		return implode( $separator, $escaped_fields ) . "\r\n";
	}

	/**
	 * Gets the configured CSV separator.
	 *
	 * @return string
	 */
	private function get_csv_separator(): string {
		$separator = (string) get_option( SettingsController::CSV_SEPARATOR_OPTION_NAME, ',' );

		return in_array( $separator, array( ',', ';', "\t" ), true ) ? $separator : ',';
	}
}
