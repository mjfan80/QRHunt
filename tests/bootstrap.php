<?php
/**
 * PHPUnit bootstrap for the WordPress integration suite.
 *
 * This file runs before WordPress defines ABSPATH. Restrict direct web requests
 * while allowing the PHPUnit CLI process to initialize the WordPress test suite.
 *
 * @package QRHunt
 */

if ( 'cli' !== PHP_SAPI ) {
	defined( 'ABSPATH' ) || exit;
}

$qrhunt_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $qrhunt_tests_dir || '' === $qrhunt_tests_dir ) {
	echo "WP_TESTS_DIR must point to the WordPress test library.\n";
	exit( 1 );
}

$qrhunt_tests_dir         = rtrim( $qrhunt_tests_dir, '\\/' );
$qrhunt_config_candidates = array(
	$qrhunt_tests_dir . '/wp-tests-config.php',
	dirname( $qrhunt_tests_dir, 2 ) . '/wp-tests-config.php',
);
$qrhunt_config_file       = '';

foreach ( $qrhunt_config_candidates as $qrhunt_config_candidate ) {
	if ( is_readable( $qrhunt_config_candidate ) ) {
		$qrhunt_config_file = $qrhunt_config_candidate;
		break;
	}
}

if ( '' === $qrhunt_config_file ) {
	echo "wp-tests-config.php was not found in WP_TESTS_DIR or its wordpress-develop root.\n";
	exit( 1 );
}

if ( '1' !== getenv( 'QRHUNT_TESTS_ALLOW_DB_RESET' ) ) {
	echo "Set QRHUNT_TESTS_ALLOW_DB_RESET=1 only for a dedicated test database.\n";
	exit( 1 );
}

require_once $qrhunt_config_file;

$qrhunt_expected_database = getenv( 'QRHUNT_TEST_DB' );

if ( false === $qrhunt_expected_database || '' === $qrhunt_expected_database || ! defined( 'DB_NAME' ) || DB_NAME !== $qrhunt_expected_database ) {
	echo "QRHUNT_TEST_DB must match the dedicated DB_NAME declared in wp-tests-config.php.\n";
	exit( 1 );
}

require_once $qrhunt_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function(): void {
		require dirname( __DIR__ ) . '/qrhunt.php';
	}
);

require $qrhunt_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/IntegrationTestCase.php';
