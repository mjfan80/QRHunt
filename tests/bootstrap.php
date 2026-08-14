<?php
/**
 * PHPUnit bootstrap for the WordPress integration suite.
 *
 * This file runs before WordPress defines ABSPATH. Restrict direct web requests
 * while allowing the PHPUnit CLI process to initialize the WordPress test suite.
 *
 * @package QuestUno
 */

if ( 'cli' !== PHP_SAPI ) {
	defined( 'ABSPATH' ) || exit;
}

$questuno_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $questuno_tests_dir || '' === $questuno_tests_dir ) {
	echo "WP_TESTS_DIR must point to the WordPress test library.\n";
	exit( 1 );
}

$questuno_tests_dir         = rtrim( $questuno_tests_dir, '\\/' );
$questuno_config_candidates = array(
	$questuno_tests_dir . '/wp-tests-config.php',
	dirname( $questuno_tests_dir, 2 ) . '/wp-tests-config.php',
);
$questuno_config_file       = '';

foreach ( $questuno_config_candidates as $questuno_config_candidate ) {
	if ( is_readable( $questuno_config_candidate ) ) {
		$questuno_config_file = $questuno_config_candidate;
		break;
	}
}

if ( '' === $questuno_config_file ) {
	echo "wp-tests-config.php was not found in WP_TESTS_DIR or its wordpress-develop root.\n";
	exit( 1 );
}

if ( '1' !== getenv( 'QUESTUNO_TESTS_ALLOW_DB_RESET' ) ) {
	echo "Set QUESTUNO_TESTS_ALLOW_DB_RESET=1 only for a dedicated test database.\n";
	exit( 1 );
}

require_once $questuno_config_file;

$questuno_expected_database = getenv( 'QUESTUNO_TEST_DB' );

if ( false === $questuno_expected_database || '' === $questuno_expected_database || ! defined( 'DB_NAME' ) || DB_NAME !== $questuno_expected_database ) {
	echo "QUESTUNO_TEST_DB must match the dedicated DB_NAME declared in wp-tests-config.php.\n";
	exit( 1 );
}

require_once $questuno_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function(): void {
		require dirname( __DIR__ ) . '/questuno.php';
	}
);

require $questuno_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/IntegrationTestCase.php';
