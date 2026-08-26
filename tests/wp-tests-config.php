<?php
/**
 * WordPress test-suite DB configuration for ASTREA Core's PHPUnit tests.
 *
 * Defaults match the DB credentials `@wordpress/env` (wp-env) uses for its
 * built-in "tests" environment (see `WORDPRESS_DB_*` inside the
 * `tests-cli` / `tests-wordpress` containers) so `composer test` works out
 * of the box when run via `wp-env run tests-cli -- vendor/bin/phpunit`
 * without any extra setup. Override via environment variables for CI or
 * any other setup.
 *
 * @package Astrea\Core
 */

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'tests-wordpress' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: 'password' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: 'tests-mysql' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'ASTREA Core Test Suite' );

define( 'WP_PHP_BINARY', 'php' );

/**
 * Path to an actual WordPress core checkout (wp-load.php, wp-settings.php,
 * wp-admin/, wp-includes/). wp-phpunit/wp-phpunit ships only the test
 * harness, not WordPress core itself. Inside wp-env's `tests-cli` /
 * `tests-wordpress` containers, a full WordPress core checkout already
 * lives at /var/www/html — reuse it rather than downloading a second copy.
 */
define( 'ABSPATH', rtrim( getenv( 'WP_TESTS_CORE_DIR' ) ?: '/var/www/html', '/' ) . '/' );
