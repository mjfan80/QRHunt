<?php
/**
 * QR code controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Checkpoint;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\PathService;
use QRHunt\Service\QrCodeService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles QR code administration.
 */
final class QrCodeController {

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var PathService */
	private $path_service;

	/** @var QrCodeService */
	private $qr_code_service;

	/**
	 * Creates a QR code controller.
	 *
	 * @param CheckpointService $checkpoint_service Checkpoint service.
	 * @param PathService       $path_service       Path service.
	 * @param QrCodeService     $qr_code_service    QR code service.
	 */
	public function __construct( CheckpointService $checkpoint_service, PathService $path_service, QrCodeService $qr_code_service ) {
		$this->checkpoint_service = $checkpoint_service;
		$this->path_service       = $path_service;
		$this->qr_code_service    = $qr_code_service;
	}

	/**
	 * Registers the QR Codes admin page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'qrhunt',
			__( 'QR Codes', 'qrhunt' ),
			__( 'QR Codes', 'qrhunt' ),
			'edit_posts',
			'qrhunt-qr-codes',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the QR Codes admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$paths = $this->path_service->get_paths();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query parameter used only to filter the admin listing by Path; this request does not change any data.
		$selected_path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$selected_path    = 0 === $selected_path_id ? null : $this->path_service->get_path( $selected_path_id );
		$checkpoints      = null === $selected_path ? array() : $this->checkpoint_service->get_checkpoints_by_path( $selected_path_id );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'QR Codes', 'qrhunt' ); ?></h1>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="qrhunt-qr-codes" />
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="qrhunt-qr-path"><?php esc_html_e( 'Path', 'qrhunt' ); ?></label>
							</th>
							<td>
								<select id="qrhunt-qr-path" name="path_id">
									<option value="0"><?php esc_html_e( 'Select a Path', 'qrhunt' ); ?></option>
									<?php foreach ( $paths as $path ) : ?>
										<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $selected_path_id, $path->get_id() ); ?>>
											<?php echo esc_html( $path->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php submit_button( __( 'Load Checkpoints', 'qrhunt' ), 'secondary', '', false ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>

			<?php if ( null !== $selected_path ) : ?>
				<p>
					<a class="button button-secondary" href="<?php echo esc_url( $this->get_print_url( $selected_path_id ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Print Path', 'qrhunt' ); ?>
					</a>
				</p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'qrhunt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Token', 'qrhunt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'qrhunt' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $checkpoints ) ) : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'No Checkpoints found for the selected Path.', 'qrhunt' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $checkpoints as $checkpoint ) : ?>
								<tr>
									<td><?php echo esc_html( get_the_title( (int) $checkpoint->get_post_id() ) ); ?></td>
									<td><code><?php echo esc_html( (string) $checkpoint->get_token() ); ?></code></td>
									<td>
										<a class="button" href="<?php echo esc_url( $this->get_download_url( (int) $checkpoint->get_post_id(), 'png' ) ); ?>">
											<?php esc_html_e( 'Download PNG', 'qrhunt' ); ?>
										</a>
										<a class="button" href="<?php echo esc_url( $this->get_download_url( (int) $checkpoint->get_post_id(), 'svg' ) ); ?>">
											<?php esc_html_e( 'Download SVG', 'qrhunt' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Downloads a QR code asset.
	 *
	 * @return void
	 */
	public function download(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$checkpoint_id = isset( $_GET['checkpoint_id'] ) ? absint( wp_unslash( $_GET['checkpoint_id'] ) ) : 0;
		$format        = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : '';

		if ( ! in_array( $format, array( 'png', 'svg' ), true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'qrhunt_download_qr_code_' . $checkpoint_id . '_' . $format ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$checkpoint = $this->checkpoint_service->get_checkpoint( $checkpoint_id );

		if ( null === $checkpoint ) {
			wp_die( esc_html__( 'Checkpoint not found.', 'qrhunt' ) );
		}

		if ( 'png' === $format ) {
			$this->output_file(
				$this->build_checkpoint_filename( $checkpoint, 'png' ),
				'image/png',
				$this->qr_code_service->generate_checkpoint_png( $checkpoint )
			);
		}

		$this->output_file(
			$this->build_checkpoint_filename( $checkpoint, 'svg' ),
			'image/svg+xml',
			$this->qr_code_service->generate_checkpoint_svg( $checkpoint )
		);
	}

	/**
	 * Renders the printable QR code page for a Path.
	 *
	 * @return void
	 */
	public function print_path(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'qrhunt_print_path_qr_codes_' . $path_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) );
		}

		$path = $this->path_service->get_path( $path_id );

		if ( null === $path ) {
			wp_die( esc_html__( 'Path not found.', 'qrhunt' ) );
		}

		$checkpoints = $this->checkpoint_service->get_checkpoints_by_path( $path_id );

		$this->render_print_document( (string) $path->get_name(), $checkpoints );
	}

	/**
	 * Builds the download URL for a QR code asset.
	 *
	 * @param int    $checkpoint_id Checkpoint post identifier.
	 * @param string $format        Asset format.
	 * @return string
	 */
	private function get_download_url( int $checkpoint_id, string $format ): string {
		return wp_nonce_url(
			admin_url(
				'admin-post.php?action=qrhunt_download_qr_code&checkpoint_id=' . $checkpoint_id . '&format=' . rawurlencode( $format )
			),
			'qrhunt_download_qr_code_' . $checkpoint_id . '_' . $format
		);
	}

	/**
	 * Builds the print URL for a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return string
	 */
	private function get_print_url( int $path_id ): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=qrhunt_print_path_qr_codes&path_id=' . $path_id ),
			'qrhunt_print_path_qr_codes_' . $path_id
		);
	}

	/**
	 * Outputs a downloadable file and terminates execution.
	 *
	 * @param string $filename  File name.
	 * @param string $mime_type MIME type.
	 * @param string $content   File content.
	 * @return void
	 */
	private function output_file( string $filename, string $mime_type, string $content ): void {
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary/image response generated internally by QrCodeService.
		exit;
	}

	/**
	 * Builds a download filename for a Checkpoint QR code.
	 *
	 * @param Checkpoint $checkpoint Checkpoint.
	 * @param string     $extension  File extension.
	 * @return string
	 */
	private function build_checkpoint_filename( Checkpoint $checkpoint, string $extension ): string {
		$title = sanitize_title( get_the_title( (int) $checkpoint->get_post_id() ) );

		if ( '' === $title ) {
			$title = 'checkpoint-' . (int) $checkpoint->get_post_id();
		}

		return $title . '.' . $extension;
	}

	/**
	 * Renders the printable HTML document.
	 *
	 * @param string                  $path_name   Path name.
	 * @param array<int, Checkpoint> $checkpoints Checkpoints.
	 * @return void
	 */
	private function render_print_document( string $path_name, array $checkpoints ): void {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<title><?php echo esc_html( $path_name ); ?></title>
			<style>
				@page { size: A4 portrait; margin: 12mm; }
				body { font-family: Arial, sans-serif; color: #111; margin: 0; }
				.qrhunt-print-wrap { padding: 0; }
				.qrhunt-print-title { font-size: 24px; margin: 0 0 10mm; text-align: center; }
				.qrhunt-print-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10mm; }
				.qrhunt-print-card { break-inside: avoid; page-break-inside: avoid; border: 1px solid #dcdcde; padding: 8mm; text-align: center; }
				.qrhunt-print-card svg { display: block; width: 100%; height: auto; margin: 0 auto 6mm; }
				.qrhunt-print-name { font-size: 16px; font-weight: 600; }
				@media screen {
					body { background: #f0f0f1; padding: 20px; }
					.qrhunt-print-wrap { background: #fff; margin: 0 auto; max-width: 210mm; min-height: 297mm; padding: 12mm; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
				}
			</style>
		</head>
		<body>
			<div class="qrhunt-print-wrap">
				<h1 class="qrhunt-print-title"><?php echo esc_html( $path_name ); ?></h1>
				<div class="qrhunt-print-grid">
					<?php foreach ( $checkpoints as $checkpoint ) : ?>
						<div class="qrhunt-print-card">
							<?php echo $this->qr_code_service->generate_checkpoint_svg( $checkpoint ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is generated internally by QrCodeService from trusted Checkpoint tokens. ?>
							<div class="qrhunt-print-name"><?php echo esc_html( get_the_title( (int) $checkpoint->get_post_id() ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script>
				window.print();
			</script>
		</body>
		</html>
		<?php
		exit;
	}
}
