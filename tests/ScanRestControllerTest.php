<?php
/**
 * Scan REST controller tests.
 *
 * @package QRHunt
 */

namespace QRHunt\Tests;

use QRHunt\Controller\ScanRestController;

/**
 * Verifies the REST scan response contract.
 */
final class ScanRestControllerTest extends IntegrationTestCase {
	/**
	 * Returns the structured result for a valid scan.
	 *
	 * @return void
	 */
	public function test_returns_validation_result_for_resolved_scan(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$checkpoint    = $this->create_checkpoint( (int) $path->get_id() );
		$participation = $this->create_participation( self::factory()->user->create(), (int) $path->get_id() );
		$controller    = new ScanRestController( $services['scan_service'] );
		$request       = new \WP_REST_Request( 'POST', '/qrhunt/v1/scan' );
		$request->set_param( 'token', $checkpoint->get_token() );
		$request->set_param( 'participation_id', $participation->get_id() );

		$response = $controller->scan( $request );

		self::assertInstanceOf( \WP_REST_Response::class, $response );
		self::assertTrue( $response->get_data()['valid'] );
		self::assertSame( array(), $response->get_data()['failed_dependencies'] );
	}

	/**
	 * Returns a not found error for unknown scan resources.
	 *
	 * @return void
	 */
	public function test_returns_not_found_for_unknown_participation(): void {
		$services   = $this->get_services();
		$controller = new ScanRestController( $services['scan_service'] );
		$request    = new \WP_REST_Request( 'POST', '/qrhunt/v1/scan' );
		$request->set_param( 'token', 'unknown-token' );
		$request->set_param( 'participation_id', 999999 );

		$response = $controller->scan( $request );

		self::assertInstanceOf( \WP_Error::class, $response );
		self::assertSame( 'qrhunt_scan_not_found', $response->get_error_code() );
	}
}
