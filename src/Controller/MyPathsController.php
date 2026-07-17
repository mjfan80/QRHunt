<?php
/**
 * Public My Paths controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Participation;
use QRHunt\Model\ParticipationStatus;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\ParticipationProgressBuilder;
use QRHunt\Service\ParticipationService;
use QRHunt\Service\PathService;
use QRHunt\Service\QrCodeService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the public My Paths page.
 */
final class MyPathsController {

	/** @var string */
	public const QUERY_VAR = 'qrhunt_my_paths';

	/** @var ParticipationService */
	private $participation_service;

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var ParticipationProgressBuilder */
	private $participation_progress_builder;

	/** @var QrCodeService */
	private $qr_code_service;

	/** @var array<string, mixed>|null */
	private $current_view_context;

	/**
	 * Creates a My Paths controller.
	 *
	 * @param ParticipationService         $participation_service          Participation service.
	 * @param PathService                  $path_service                   Path service.
	 * @param CheckpointService            $checkpoint_service             Checkpoint service.
	 * @param ParticipationProgressBuilder $participation_progress_builder Participation progress builder.
	 * @param QrCodeService                $qr_code_service                QR code service.
	 */
	public function __construct(
		ParticipationService $participation_service,
		PathService $path_service,
		CheckpointService $checkpoint_service,
		ParticipationProgressBuilder $participation_progress_builder,
		QrCodeService $qr_code_service
	) {
		$this->participation_service          = $participation_service;
		$this->path_service                   = $path_service;
		$this->checkpoint_service             = $checkpoint_service;
		$this->participation_progress_builder = $participation_progress_builder;
		$this->qr_code_service                = $qr_code_service;
	}

	/**
	 * Registers the public rewrite rules.
	 *
	 * @return void
	 */
	public static function register_rewrite_rules(): void {
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([01])' );
		add_rewrite_rule(
			'^qrhunt/my-paths/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/**
	 * Handles the public My Paths request.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$is_my_paths_request = get_query_var( self::QUERY_VAR, '' );

		if ( '1' !== (string) $is_my_paths_request ) {
			return;
		}

		add_filter( 'redirect_canonical', array( $this, 'disable_canonical_redirect' ), 10, 2 );

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$this->prepare_template_response(
			array(
				'page_title' => __( 'My paths', 'qrhunt' ),
				'items'      => $this->build_items( get_current_user_id() ),
			)
		);
	}

	/**
	 * Disables canonical redirects only for the QRHunt My Paths route.
	 *
	 * @param string|false $redirect_url  Redirect destination.
	 * @param string       $requested_url Requested URL.
	 * @return string|false
	 */
	public function disable_canonical_redirect( $redirect_url, string $requested_url ) {
		unset( $requested_url );

		if ( '1' === (string) get_query_var( self::QUERY_VAR, '' ) ) {
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
		$classes[] = 'qrhunt-public-ui--my-paths';

		return array_values( array_unique( $classes ) );
	}

	/**
	 * Forces the plugin template only for the active QRHunt My Paths request.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function filter_template_include( string $template ): string {
		if ( null === $this->current_view_context ) {
			return $template;
		}

		return dirname( __DIR__, 2 ) . '/templates/public-my-paths.php';
	}

	/**
	 * Prepares the plugin template response for the current QRHunt public request.
	 *
	 * @param array<string, mixed> $view_context Prepared template context.
	 * @return void
	 */
	private function prepare_template_response( array $view_context ): void {
		global $wp_query;

		$this->current_view_context = $view_context;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}

		status_header( 200 );
		nocache_headers();

		set_query_var( 'qrhunt_public_my_paths_context', $view_context );

		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
		add_filter( 'template_include', array( $this, 'filter_template_include' ), 99 );
	}

	/**
	 * Builds the list items rendered by the My Paths template.
	 *
	 * @param int $user_id User identifier.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_items( int $user_id ): array {
		$items = array();

		foreach ( $this->participation_service->get_participations_by_user( $user_id ) as $participation ) {
			$item = $this->build_item( $participation );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Builds one My Paths item.
	 *
	 * @param Participation $participation Participation.
	 * @return array<string, mixed>|null
	 */
	private function build_item( Participation $participation ): ?array {
		$path_id = $participation->get_path_id();

		if ( null === $path_id ) {
			return null;
		}

		$path = $this->path_service->get_path( (int) $path_id );

		if ( null === $path ) {
			return null;
		}

		$checkpoints          = $this->checkpoint_service->get_checkpoints_by_path( (int) $path_id );
		$total_checkpoints   = count( $checkpoints );
		$progress            = $this->participation_progress_builder->build( $participation );
		$visited_checkpoints = count( $progress->get_validated_checkpoint_ids() );

		return array(
			'path_name'      => (string) $path->get_name(),
			'status'         => (string) $participation->get_status(),
			'progress_label' => 0 === $total_checkpoints
				? __( 'No checkpoints', 'qrhunt' )
				: sprintf(
					/* translators: 1: visited checkpoints, 2: total checkpoints. */
					__( '%1$d / %2$d checkpoints', 'qrhunt' ),
					$visited_checkpoints,
					$total_checkpoints
				),
			'action_url'     => $this->get_action_url( $participation ),
		);
	}

	/**
	 * Gets the public URL used to open a Path from the list.
	 *
	 * The current domain model does not define a "next Checkpoint" resolver yet,
	 * so this points to the Path start or finish Checkpoint rather than promising
	 * a resume-from-current-position behavior.
	 *
	 * @param Participation $participation Participation.
	 * @return string
	 */
	private function get_action_url( Participation $participation ): string {
		$path_id = $participation->get_path_id();

		if ( null === $path_id ) {
			return '';
		}

		$path = $this->path_service->get_path( (int) $path_id );

		if ( null === $path ) {
			return '';
		}

		$checkpoint_id = ParticipationStatus::COMPLETED === $participation->get_status()
			? $path->get_finish_checkpoint_id()
			: $path->get_start_checkpoint_id();

		if ( null === $checkpoint_id ) {
			return '';
		}

		$checkpoint = $this->checkpoint_service->get_checkpoint( (int) $checkpoint_id );

		if ( null === $checkpoint || null === $checkpoint->get_token() ) {
			return '';
		}

		return $this->qr_code_service->build_public_url( (string) $checkpoint->get_token() );
	}
}
