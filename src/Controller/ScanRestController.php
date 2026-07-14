<?php
/**
 * Scan REST controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Service\ScanService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the REST endpoint for QR scans.
 */
final class ScanRestController {

	/** @var string */
	private const REST_NAMESPACE = 'qrhunt/v1';

	/** @var string */
	private const REST_ROUTE = '/scan';

	/** @var ScanService */
	private $scan_service;

	/**
	 * Creates a scan REST controller.
	 *
	 * @param ScanService $scan_service Scan service.
	 */
	public function __construct( ScanService $scan_service ) {
		$this->scan_service = $scan_service;
	}

	/**
	 * Registers the REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'scan' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token'            => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'participation_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Handles a scan request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function scan( \WP_REST_Request $request ) {
		try {
			$validation_result = $this->scan_service->scan(
				(string) $request->get_param( 'token' ),
				(int) $request->get_param( 'participation_id' )
			);
		} catch ( \InvalidArgumentException $exception ) {
			return new \WP_Error(
				'qrhunt_scan_not_found',
				$exception->getMessage(),
				array( 'status' => 404 )
			);
		}

		$data = array(
			'valid'               => $validation_result->is_valid(),
			'failed_dependencies' => array(),
		);

		foreach ( $validation_result->get_failed_dependencies() as $violation ) {
			$data['failed_dependencies'][] = array(
				'type'         => $violation->get_type(),
				'target_type'  => $violation->get_target_type(),
				'target_id'    => $violation->get_target_id(),
				'display_name' => $violation->get_display_name(),
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}
}
