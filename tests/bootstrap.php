<?php
/**
 * PHPUnit bootstrap for the WordPress integration suite.
 *
 * @package QRHunt
 */

$tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $tests_dir || '' === $tests_dir ) {
	fwrite( STDERR, "WP_TESTS_DIR must point to the WordPress test library.\n" );
	exit( 1 );
}

$config_file = rtrim( $tests_dir, '\\/' ) . '/wp-tests-config.php';

if ( ! is_readable( $config_file ) ) {
	fwrite( STDERR, "wp-tests-config.php was not found in WP_TESTS_DIR.\n" );
	exit( 1 );
}

if ( '1' !== getenv( 'QRHUNT_TESTS_ALLOW_DB_RESET' ) ) {
	fwrite( STDERR, "Set QRHUNT_TESTS_ALLOW_DB_RESET=1 only for a dedicated test database.\n" );
	exit( 1 );
}

require_once $config_file;

$expected_database = getenv( 'QRHUNT_TEST_DB' );

if ( false === $expected_database || '' === $expected_database || ! defined( 'DB_NAME' ) || DB_NAME !== $expected_database ) {
	fwrite( STDERR, "QRHUNT_TEST_DB must match the dedicated DB_NAME declared in wp-tests-config.php.\n" );
	exit( 1 );
}

require_once rtrim( $tests_dir, '\\/' ) . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function(): void {
		require dirname( __DIR__ ) . '/qrhunt.php';
	}
);

require rtrim( $tests_dir, '\\/' ) . '/includes/bootstrap.php';

require_once __DIR__ . '/IntegrationTestCase.php';
