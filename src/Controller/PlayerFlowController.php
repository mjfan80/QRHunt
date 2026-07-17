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
use QRHunt\Model\ParticipationProgress;
use QRHunt\Model\ParticipationStatus;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\ParticipationProgressBuilder;
use QRHunt\Service\ParticipationService;
use QRHunt\Service\PathService;
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

	/** @var PathService */
	private $path_service;

	/** @var ParticipationProgressBuilder */
	private $participation_progress_builder;

	/** @var array<string, mixed>|null */
	private $current_view_context;

	/**
	 * Creates a player flow controller.
	 *
	 * @param CheckpointService            $checkpoint_service             Checkpoint service.
	 * @param ParticipationService         $participation_service          Participation service.
	 * @param ScanService                  $scan_service                   Scan service.
	 * @param PathService                  $path_service                   Path service.
	 * @param ParticipationProgressBuilder $participation_progress_builder Participation progress builder.
	 */
	public function __construct(
		CheckpointService $checkpoint_service,
		ParticipationService $participation_service,
		ScanService $scan_service,
		PathService $path_service,
		ParticipationProgressBuilder $participation_progress_builder
	) {
		$this->checkpoint_service             = $checkpoint_service;
		$this->participation_service          = $participation_service;
		$this->scan_service                   = $scan_service;
		$this->path_service                   = $path_service;
		$this->participation_progress_builder = $participation_progress_builder;
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

		add_filter( 'redirect_canonical', array( $this, 'disable_canonical_redirect' ), 10, 2 );

		$checkpoint = $this->checkpoint_service->get_checkpoint_by_token_with_dependencies( sanitize_text_field( $token ) );

		if ( null === $checkpoint || null === $checkpoint->get_post_id() ) {
			$this->prepare_template_response(
				$this->build_error_view_context(
					null,
					null,
					__( 'Validation failed', 'qrhunt' ),
					__( 'QR Code not valid.', 'qrhunt' ),
					array(),
					false,
					false
				),
				404
			);

			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$participation = $this->participation_service->get_participation_for_scan(
			get_current_user_id(),
			$checkpoint
		);

		if ( null === $participation ) {
			$this->prepare_template_response(
				$this->build_error_view_context(
					$checkpoint,
					null,
					__( 'Validation failed', 'qrhunt' ),
					__( 'No active Participation was found for this Path.', 'qrhunt' ),
					array(),
					false,
					true
				),
				200
			);

			return;
		}

		$progress_before      = $this->participation_progress_builder->build( $participation );
		$validation_result    = $this->scan_service->scan_checkpoint( $participation, $checkpoint );
		$stored_participation = $this->participation_service->get_participation( (int) $participation->get_id() );
		$participation        = $stored_participation instanceof Participation ? $stored_participation : $participation;

		if ( $validation_result->is_valid() ) {
			$this->prepare_template_response(
				$this->build_success_view_context( $checkpoint, $participation, $progress_before ),
				200
			);

			return;
		}

		$this->prepare_template_response(
			$this->build_error_view_context(
				$checkpoint,
				$participation,
				__( 'Validation failed', 'qrhunt' ),
				__( 'Checkpoint could not be validated.', 'qrhunt' ),
				$this->build_violation_messages( $validation_result->get_failed_dependencies() ),
				false,
				true
			),
			200
		);
	}

	/**
	 * Disables canonical redirects only for the QRHunt public token route.
	 *
	 * @param string|false $redirect_url Redirect destination.
	 * @param string       $requested_url Requested URL.
	 * @return string|false
	 */
	public function disable_canonical_redirect( $redirect_url, string $requested_url ) {
		unset( $requested_url );

		$token = get_query_var( self::QUERY_VAR, '' );

		if ( is_string( $token ) && '' !== $token ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Adds QRHunt classes to the body tag of rendered public requests.
	 *
	 * @param array<int, string> $classes Existing classes.
	 * @return array<int, string>
	 */
	public function filter_body_class( array $classes ): array {
		if ( null === $this->current_view_context ) {
			return $classes;
		}

		$classes[] = 'qrhunt-public-ui';
		$classes[] = 'qrhunt-public-ui--checkpoint';

		if ( '' !== $this->current_view_context['banner_modifier'] ) {
			$classes[] = 'qrhunt-public-ui--' . sanitize_html_class( $this->current_view_context['banner_modifier'] );
		}

		if ( ! empty( $this->current_view_context['is_valid_checkpoint'] ) ) {
			$classes[] = 'qrhunt-public-ui--valid';
		} else {
			$classes[] = 'qrhunt-public-ui--invalid';
		}

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Forces the plugin template only for the active QRHunt public request.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function filter_template_include( string $template ): string {
		if ( null === $this->current_view_context ) {
			return $template;
		}

		return dirname( __DIR__, 2 ) . '/templates/public-checkpoint.php';
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
	 * Prepares the plugin template response for the current QRHunt public request.
	 *
	 * @param array<string, mixed> $view_context Prepared template context.
	 * @param int                  $status_code  HTTP status code.
	 * @return void
	 */
	private function prepare_template_response( array $view_context, int $status_code ): void {
		global $wp_query;

		$this->current_view_context = $view_context;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = ! empty( $view_context['is_404'] );
		}

		status_header( $status_code );
		nocache_headers();

		set_query_var( 'qrhunt_public_ui_context', $view_context );

		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
		add_filter( 'template_include', array( $this, 'filter_template_include' ), 99 );
	}

	/**
	 * Builds the success context rendered by the plugin public template.
	 *
	 * @param Checkpoint            $checkpoint      Checkpoint being shown.
	 * @param Participation         $participation   Participation after validation.
	 * @param ParticipationProgress $progress_before Progress before validation.
	 * @return array<string, mixed>
	 */
	private function build_success_view_context( Checkpoint $checkpoint, Participation $participation, ParticipationProgress $progress_before ): array {
		$post               = get_post( (int) $checkpoint->get_post_id() );
		$page_title         = $post instanceof \WP_Post ? get_the_title( $post ) : __( 'Checkpoint', 'qrhunt' );
		$path_id            = $checkpoint->get_path_id();
		$path               = null === $path_id ? null : $this->path_service->get_path( (int) $path_id );
		$progress_after     = $this->participation_progress_builder->build( $participation );
		$post_id            = (int) $checkpoint->get_post_id();
		$was_visited        = in_array( $post_id, $progress_before->get_validated_checkpoint_ids(), true );
		$was_completed      = ParticipationStatus::COMPLETED === $participation->get_status()
			&& count( $progress_before->get_validated_checkpoint_ids() ) === count( $progress_after->get_validated_checkpoint_ids() );
		$banner             = $this->resolve_banner_state( $participation, $was_visited, $was_completed );
		$total_checkpoints  = null === $path_id ? 0 : count( $this->checkpoint_service->get_checkpoints_by_path( (int) $path_id ) );
		$visited_checkpoints = count( $progress_after->get_validated_checkpoint_ids() );

		return array(
			'page_title'          => $page_title,
			'validation_outcome'  => __( 'Validation succeeded', 'qrhunt' ),
			'message'             => '',
			'participation'       => $participation,
			'violation_messages'  => array(),
			'render_content'      => true,
			'banner_message'      => $banner['message'],
			'banner_modifier'     => $banner['modifier'],
			'path_name'           => null === $path ? __( 'Path', 'qrhunt' ) : (string) $path->get_name(),
			'progress_label'      => 0 === $total_checkpoints
				? ''
				: sprintf(
					/* translators: 1: visited checkpoints, 2: total checkpoints. */
					__( '%1$d / %2$d checkpoints', 'qrhunt' ),
					$visited_checkpoints,
					$total_checkpoints
				),
			'my_paths_url'        => $this->get_my_paths_url(),
			'render_navigation'   => true,
			'checkpoint_content'  => $post instanceof \WP_Post ? $this->get_rendered_post_content( $post ) : '',
			'is_valid_checkpoint' => true,
			'is_404'              => false,
		);
	}

	/**
	 * Builds the error context rendered by the plugin public template.
	 *
	 * @param Checkpoint|null    $checkpoint         Checkpoint, if available.
	 * @param Participation|null $participation      Participation, if available.
	 * @param string             $validation_outcome Validation outcome label.
	 * @param string             $message            Main player message.
	 * @param array<int, string> $violation_messages Violation messages.
	 * @param bool               $show_content       Whether the Checkpoint content should be rendered.
	 * @param bool               $is_valid_checkpoint Whether the Checkpoint exists and can provide content.
	 * @return array<string, mixed>
	 */
	private function build_error_view_context( ?Checkpoint $checkpoint, ?Participation $participation, string $validation_outcome, string $message, array $violation_messages, bool $show_content, bool $is_valid_checkpoint ): array {
		$post = null;

		if ( null !== $checkpoint && null !== $checkpoint->get_post_id() ) {
			$post = get_post( (int) $checkpoint->get_post_id() );
		}

		return array(
			'page_title'          => $post instanceof \WP_Post ? get_the_title( $post ) : __( 'Checkpoint', 'qrhunt' ),
			'validation_outcome'  => $validation_outcome,
			'message'             => $message,
			'participation'       => $participation,
			'violation_messages'  => $violation_messages,
			'render_content'      => $show_content,
			'banner_message'      => '',
			'banner_modifier'     => '',
			'path_name'           => '',
			'progress_label'      => '',
			'my_paths_url'        => '',
			'render_navigation'   => false,
			'checkpoint_content'  => $show_content && $post instanceof \WP_Post ? $this->get_rendered_post_content( $post ) : '',
			'is_valid_checkpoint' => $is_valid_checkpoint,
			'is_404'              => ! $is_valid_checkpoint,
		);
	}

	/**
	 * Resolves the dynamic banner state for the rendered page.
	 *
	 * @param Participation $participation Participation after validation.
	 * @param bool          $was_visited   Whether the Checkpoint was already visited before this scan.
	 * @param bool          $was_completed Whether the Path was already completed before this scan.
	 * @return array{modifier: string, message: string}
	 */
	private function resolve_banner_state( Participation $participation, bool $was_visited, bool $was_completed ): array {
		if ( $was_completed ) {
			return array(
				'modifier' => 'path-already-completed',
				'message'  => __( 'This Path was already completed.', 'qrhunt' ),
			);
		}

		if ( ParticipationStatus::COMPLETED === $participation->get_status() ) {
			return array(
				'modifier' => 'path-completed',
				'message'  => __( 'Path completed.', 'qrhunt' ),
			);
		}

		if ( $was_visited ) {
			return array(
				'modifier' => 'checkpoint-already-visited',
				'message'  => __( 'Checkpoint already visited.', 'qrhunt' ),
			);
		}

		return array(
			'modifier' => 'checkpoint-registered',
			'message'  => __( 'Checkpoint registered.', 'qrhunt' ),
		);
	}

	/**
	 * Gets the public URL for the My Paths page.
	 *
	 * @return string
	 */
	private function get_my_paths_url(): string {
		$url = home_url( '/qrhunt/my-paths/' );

		/**
		 * Filters the My Paths public URL.
		 *
		 * @param string $url Default URL.
		 */
		return (string) apply_filters( 'qrhunt_public_my_paths_url', $url );
	}

	/**
	 * Renders the Checkpoint content through the standard WordPress content API.
	 *
	 * @param \WP_Post $post Checkpoint post.
	 * @return string
	 */
	private function get_rendered_post_content( \WP_Post $post ): string {
		$original_post   = $GLOBALS['post'] ?? null;
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
