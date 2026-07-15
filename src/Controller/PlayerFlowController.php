<?php
/**
 * Player flow controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Checkpoint;
use QRHunt\Model\DependencyTargetType;
use QRHunt\Model\DependencyType;
use QRHunt\Model\Participation;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\ParticipationService;
use QRHunt\Service\ScanService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the public player flow for Checkpoint URLs.
 */
final class PlayerFlowController {

	/** @var string */
	public const QUERY_VAR = 'qrhunt_checkpoint_token';

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var ParticipationService */
	private $participation_service;

	/** @var ScanService */
	private $scan_service;

	/**
	 * Creates a player flow controller.
	 *
	 * @param CheckpointService    $checkpoint_service    Checkpoint service.
	 * @param ParticipationService $participation_service Participation service.
	 * @param ScanService          $scan_service          Scan service.
	 */
	public function __construct( CheckpointService $checkpoint_service, ParticipationService $participation_service, ScanService $scan_service ) {
		$this->checkpoint_service    = $checkpoint_service;
		$this->participation_service = $participation_service;
		$this->scan_service          = $scan_service;
	}

	/**
	 * Registers the public rewrite rules.
	 *
	 * @return void
	 */
	public static function register_rewrite_rules(): void {
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([^&]+)' );
		add_rewrite_rule(
			'^qrhunt/checkpoint/([^/]+)/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Handles the public player request when the plugin token query var is present.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$token = get_query_var( self::QUERY_VAR, '' );

		if ( ! is_string( $token ) || '' === $token ) {
			return;
		}

		$checkpoint = $this->checkpoint_service->get_checkpoint_by_token_with_dependencies( sanitize_text_field( $token ) );

		if ( null === $checkpoint || null === $checkpoint->get_post_id() ) {
			$this->render_page(
				null,
				null,
				__( 'Validation failed', 'qrhunt' ),
				__( 'QR Code not valid.', 'qrhunt' ),
				array(),
				404
			);
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$participation = $this->participation_service->get_participation_for_scan(
			get_current_user_id(),
			$checkpoint
		);

		if ( null === $participation ) {
			$this->render_page(
				$checkpoint,
				null,
				__( 'Validation failed', 'qrhunt' ),
				__( 'No active Participation was found for this Path.', 'qrhunt' ),
				array(),
				200
			);
		}

		$validation_result = $this->scan_service->scan_checkpoint( $participation, $checkpoint );
		$participation     = $this->participation_service->get_participation( (int) $participation->get_id() );

		$this->render_page(
			$checkpoint,
			$participation,
			$validation_result->is_valid() ? __( 'Validation succeeded', 'qrhunt' ) : __( 'Validation failed', 'qrhunt' ),
			$validation_result->is_valid() ? __( 'Checkpoint registered successfully.', 'qrhunt' ) : __( 'Checkpoint could not be validated.', 'qrhunt' ),
			$this->build_violation_messages( $validation_result->get_failed_dependencies() ),
			200
		);
	}

	/**
	 * Builds user-facing validation messages.
	 *
	 * @param array<int, \QRHunt\Model\DependencyViolation> $violations Dependency violations.
	 * @return array<int, string>
	 */
	private function build_violation_messages( array $violations ): array {
		$messages = array();

		foreach ( $violations as $violation ) {
			if ( DependencyType::AFTER === $violation->get_type() ) {
				$messages[] = DependencyTargetType::GROUP === $violation->get_target_type()
					? sprintf(
						/* translators: %s: group name. */
						__( 'You must complete the group "%s" first.', 'qrhunt' ),
						$violation->get_display_name()
					)
					: sprintf(
						/* translators: %s: checkpoint name. */
						__( 'You must complete the checkpoint "%s" first.', 'qrhunt' ),
						$violation->get_display_name()
					);

				continue;
			}

			$messages[] = DependencyTargetType::GROUP === $violation->get_target_type()
				? sprintf(
					/* translators: %s: group name. */
					__( 'This checkpoint is no longer available because the group "%s" has already been completed.', 'qrhunt' ),
					$violation->get_display_name()
				)
				: sprintf(
					/* translators: %s: checkpoint name. */
					__( 'This checkpoint is no longer available because the checkpoint "%s" has already been completed.', 'qrhunt' ),
					$violation->get_display_name()
				);
		}

		return $messages;
	}

	/**
	 * Renders the minimal player page using the active theme.
	 *
	 * @param Checkpoint|null      $checkpoint          Checkpoint.
	 * @param Participation|null   $participation       Participation.
	 * @param string               $validation_outcome  Validation outcome label.
	 * @param string               $message             Main player message.
	 * @param array<int, string>   $violation_messages  Violation messages.
	 * @param int                  $status_code         HTTP status code.
	 * @return void
	 */
	private function render_page( ?Checkpoint $checkpoint, ?Participation $participation, string $validation_outcome, string $message, array $violation_messages, int $status_code ): void {
		global $wp_query;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = 404 === $status_code;
		}

		status_header( $status_code );
		nocache_headers();

		$post = null;

		if ( null !== $checkpoint && null !== $checkpoint->get_post_id() ) {
			$post = get_post( (int) $checkpoint->get_post_id() );
		}

		get_header();
		?>
		<main id="primary" class="site-main qrhunt-player-flow">
			<div class="qrhunt-player-flow__content" style="max-width: 720px; margin: 2rem auto; padding: 0 1rem;">
				<h1><?php echo esc_html( $post instanceof \WP_Post ? get_the_title( $post ) : __( 'Checkpoint', 'qrhunt' ) ); ?></h1>

				<p><strong><?php esc_html_e( 'Validation outcome:', 'qrhunt' ); ?></strong> <?php echo esc_html( $validation_outcome ); ?></p>
				<p><?php echo esc_html( $message ); ?></p>

				<p>
					<strong><?php esc_html_e( 'Participation status:', 'qrhunt' ); ?></strong>
					<?php echo esc_html( null === $participation ? __( 'Not started', 'qrhunt' ) : (string) $participation->get_status() ); ?>
				</p>

				<?php if ( ! empty( $violation_messages ) ) : ?>
					<section class="qrhunt-player-flow__violations">
						<h2><?php esc_html_e( 'Validation details', 'qrhunt' ); ?></h2>
						<ul>
							<?php foreach ( $violation_messages as $violation_message ) : ?>
								<li><?php echo esc_html( $violation_message ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $post instanceof \WP_Post ) : ?>
					<section class="qrhunt-player-flow__checkpoint-content">
						<?php echo wp_kses_post( $this->get_rendered_post_content( $post ) ); ?>
					</section>
				<?php endif; ?>
			</div>
		</main>
		<?php
		get_footer();
		exit;
	}

	/**
	 * Renders the Checkpoint content through the standard WordPress template API.
	 *
	 * @param \WP_Post $post Checkpoint post.
	 * @return string
	 */
	private function get_rendered_post_content( \WP_Post $post ): string {
		$original_post = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		ob_start();
		the_content();
		$content = (string) ob_get_clean();

		if ( $original_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $original_post;
			setup_postdata( $original_post );
		} else {
			wp_reset_postdata();
		}

		return $content;
	}
}
