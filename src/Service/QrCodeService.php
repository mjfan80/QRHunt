<?php
/**
 * QR code service.
 *
 * @package QRHunt
 */

namespace QRHunt\Service;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use QRHunt\Model\Checkpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Generates QR code assets for Checkpoints.
 */
final class QrCodeService {

	/**
	 * Builds the public URL for a Checkpoint token.
	 *
	 * @param string $token Checkpoint token.
	 * @return string
	 */
	public function build_public_url( string $token ): string {
		return home_url( '/qrhunt/checkpoint/' . rawurlencode( $token ) );
	}

	/**
	 * Generates a PNG QR code for a Checkpoint.
	 *
	 * @param Checkpoint $checkpoint Checkpoint.
	 * @return string
	 */
	public function generate_checkpoint_png( Checkpoint $checkpoint ): string {
		$writer = new PngWriter();
		$result = $writer->write( $this->create_qr_code( (string) $checkpoint->get_token() ) );

		return $result->getString();
	}

	/**
	 * Generates an SVG QR code for a Checkpoint.
	 *
	 * @param Checkpoint $checkpoint Checkpoint.
	 * @return string
	 */
	public function generate_checkpoint_svg( Checkpoint $checkpoint ): string {
		$writer = new SvgWriter();
		$result = $writer->write( $this->create_qr_code( (string) $checkpoint->get_token() ) );

		return $result->getString();
	}

	/**
	 * Creates a QR code instance for the given token.
	 *
	 * @param string $token Checkpoint token.
	 * @return QrCode
	 */
	private function create_qr_code( string $token ): QrCode {
		return new QrCode(
			data: $this->build_public_url( $token ),
			encoding: new Encoding( 'UTF-8' ),
			errorCorrectionLevel: ErrorCorrectionLevel::High,
			size: 600,
			margin: 10,
			roundBlockSizeMode: RoundBlockSizeMode::Margin
		);
	}
}
