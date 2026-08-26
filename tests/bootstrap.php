<?php
/**
 * PHPUnit bootstrap for ASTREA Core.
 *
 * Loads the WordPress core PHPUnit test suite (via the wp-phpunit/wp-phpunit
 * Composer package) and then loads ASTREA Core as a must-use-style plugin
 * before WordPress finishes booting, exactly like a real "Plugin activated"
 * state. The ASTREA Theme is intentionally NOT loaded here — Office
 * Profile is a Core-only concern; the Theme/Core connection itself is
 * covered separately by tools/ci/smoke-test.sh against a real wp-env site.
 *
 * @package Astrea\Core
 */

if ( ! getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php — is wp-phpunit/wp-phpunit installed via Composer?" . PHP_EOL;
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once $_tests_dir . '/includes/functions.php';

/**
 * Loads ASTREA Core before WordPress finishes setting itself up, so it
 * behaves like an activated plugin for every test.
 *
 * Resolved via ABSPATH + the plugin's mounted location
 * (wp-content/plugins/astrea-core, see .wp-env.json `mappings`) rather than
 * a path relative to this file, because only `tests/` and `vendor/` —not
 * the whole project root— are mapped into the wp-env containers this
 * bootstrap normally runs in. Override ASTREA_CORE_TEST_PLUGIN_FILE for any
 * other setup.
 *
 * @return void
 */
function _astrea_core_manually_load_plugin() {
	$plugin_file = getenv( 'ASTREA_CORE_TEST_PLUGIN_FILE' );

	if ( ! $plugin_file ) {
		$plugin_file = ABSPATH . 'wp-content/plugins/astrea-core/astrea-core.php';
	}

	require $plugin_file;
}
tests_add_filter( 'muplugins_loaded', '_astrea_core_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
