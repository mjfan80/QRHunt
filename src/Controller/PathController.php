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
use QRHunt\Service\PathConfigurationValidator;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class PathController {

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var PathConfigurationValidator */
	private $configuration_validator;

	/** @var bool */
	private $is_correcting_post_status = false;

	/** @var bool */
	private $is_transitioning_post_status = false;

	/**
	 * Creates a Path controller.
	 *
	 * @param PathService                $path_service             Path service.
	 * @param CheckpointService          $checkpoint_service       Checkpoint service.
	 * @param PathConfigurationValidator $configuration_validator  Path configuration validator.
	 */
	public function __construct( PathService $path_service, CheckpointService $checkpoint_service, PathConfigurationValidator $configuration_validator ) {
		$this->path_service             = $path_service;
		$this->checkpoint_service       = $checkpoint_service;
		$this->configuration_validator  = $configuration_validator;
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
		$opening_date = null === $path ? '' : (string) $path->get_opening_date();
		$closing_date = null === $path ? '' : (string) $path->get_closing_date();
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
			<label for="qrhunt-opening-date"><?php esc_html_e( 'Opening date', 'qrhunt' ); ?></label>
			<input id="qrhunt-opening-date" name="qrhunt_opening_date" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $opening_date ) ); ?>" />
		</p>
		<p>
			<label for="qrhunt-closing-date"><?php esc_html_e( 'Closing date', 'qrhunt' ); ?></label>
			<input id="qrhunt-closing-date" name="qrhunt_closing_date" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $closing_date ) ); ?>" />
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
		if ( $this->is_transitioning_post_status || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
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
			$path->set_opening_date( $stored_path->get_opening_date() );
			$path->set_closing_date( $stored_path->get_closing_date() );
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
			$path->set_opening_date( $this->get_datetime_input( 'qrhunt_opening_date' ) );
			$path->set_closing_date( $this->get_datetime_input( 'qrhunt_closing_date' ) );
		}

		$this->path_service->save_path( $path );

		if ( 'publish' === $post->post_status && ! $this->is_correcting_post_status ) {
			$errors = $this->configuration_validator->validate( $path );

			if ( ! empty( $errors ) ) {
				$this->is_correcting_post_status = true;
				set_transient( 'qrhunt_path_configuration_errors_' . $post_id, $errors, MINUTE_IN_SECONDS );
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'draft',
					)
				);
				$this->is_correcting_post_status = false;
			}
		}
	}

	/**
	 * Adds archive actions to Path rows in the WordPress administration list.
	 *
	 * @param array<string,string> $actions Row actions.
	 * @param \WP_Post             $post    Current post.
	 * @return array<string,string>
	 */
	public function add_row_actions( array $actions, \WP_Post $post ): array {
		if ( PathPostType::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		if ( 'publish' === $post->post_status ) {
			$actions['qrhunt_archive'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qrhunt_archive_path&post_id=' . $post->ID ), 'qrhunt_archive_path_' . $post->ID ) ),
				esc_html__( 'Archive', 'qrhunt' )
			);
		}

		if ( PathPostType::ARCHIVED_STATUS === $post->post_status ) {
			$actions['qrhunt_restore'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qrhunt_restore_path&post_id=' . $post->ID ), 'qrhunt_restore_path_' . $post->ID ) ),
				esc_html__( 'Restore to Draft', 'qrhunt' )
			);
		}

		return $actions;
	}

	/**
	 * Archives a published Path.
	 *
	 * @return void
	 */
	public function archive(): void {
		$this->handle_status_transition( PathPostType::ARCHIVED_STATUS, 'qrhunt_archive_path' );
	}

	/**
	 * Restores an archived Path as a draft.
	 *
	 * @return void
	 */
	public function restore(): void {
		$this->handle_status_transition( 'draft', 'qrhunt_restore_path' );
	}

	/**
	 * Transitions a Path to the requested status from an admin action.
	 *
	 * @param string $status Target status.
	 * @param string $action Action nonce prefix.
	 * @return void
	 */
	private function handle_status_transition( string $status, string $action ): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;

		if ( 0 === $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		check_admin_referer( $action . '_' . $post_id );

		if ( ! $this->transition_post_status( $post_id, $status ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . PathPostType::POST_TYPE . '&post_status=' . $status ) );
		exit;
	}

	/**
	 * Changes a Path status without altering its associated QRHunt data.
	 *
	 * @param int    $post_id Path post identifier.
	 * @param string $status  Target post status.
	 * @return bool
	 */
	public function transition_post_status( int $post_id, string $status ): bool {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || PathPostType::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( PathPostType::ARCHIVED_STATUS === $status && 'publish' !== $post->post_status ) {
			return false;
		}

		if ( 'draft' === $status && PathPostType::ARCHIVED_STATUS !== $post->post_status ) {
			return false;
		}

		$this->is_transitioning_post_status = true;

		try {
			$updated_post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => $status,
				),
				true
			);
		} finally {
			$this->is_transitioning_post_status = false;
		}

		if ( is_wp_error( $updated_post_id ) || 0 === $updated_post_id ) {
			return false;
		}

		$updated_post = get_post( $post_id );

		if ( ! $updated_post instanceof \WP_Post ) {
			return false;
		}

		$this->save( $post_id, $updated_post );

		return true;
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

	private function format_datetime_input( string $value ): string {
		$timestamp = strtotime( $value );

		return false === $timestamp ? '' : wp_date( 'Y-m-d\\TH:i', $timestamp );
	}

	private function get_datetime_input( string $key ): ?string {
		if ( ! isset( $_POST[ $key ] ) ) {
			return null;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

		if ( '' === $value ) {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', $value, wp_timezone() );

		return $date instanceof \DateTimeImmutable && $date->format( 'Y-m-d\\TH:i' ) === $value ? $date->format( 'Y-m-d H:i:s' ) : null;
	}

	/**
	 * Renders configuration errors after a prevented publication.
	 *
	 * @return void
	 */
	public function render_configuration_errors(): void {
		$post_id = get_the_ID();
		$errors  = false === $post_id ? false : get_transient( 'qrhunt_path_configuration_errors_' . $post_id );

		if ( ! is_array( $errors ) ) {
			return;
		}

		delete_transient( 'qrhunt_path_configuration_errors_' . $post_id );
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'The Path was saved as a draft because its configuration is not publishable.', 'qrhunt' ); ?></strong></p>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
