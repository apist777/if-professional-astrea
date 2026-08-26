<?php
/**
 * Plugin Name: ASTREA Core
 * Plugin URI: https://project-if.com/astrea
 * Description: If Professional ASTREA — テーマを変更しても保持すべき情報とサイト共通機能を担当する、ASTREA Themeの公式推奨Plugin（任意）。「Coreは推奨する。しかしThemeを人質にしない。」（Decision 021）。
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Author: Project-if
 * Author URI: https://project-if.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: astrea-core
 * Domain Path: /languages
 *
 * @package Astrea\Core
 */

namespace Astrea\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

define( 'ASTREA_CORE_VERSION', '0.1.0' );
define( 'ASTREA_CORE_FILE', __FILE__ );
define( 'ASTREA_CORE_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_textdomain' );

/**
 * Loads the plugin text domain.
 *
 * @return void
 */
function load_textdomain() {
	load_plugin_textdomain( 'astrea-core', false, dirname( plugin_basename( ASTREA_CORE_FILE ) ) . '/languages' );
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Activation hook.
 *
 * Intentionally a no-op in this construction-phase skeleton: no data model
 * exists yet. Future feature Phases (Office Profile, Service, Price, FAQ,
 * Contact, SEO/OGP, Search Console, Setup) will add versioned setup here.
 *
 * @return void
 */
function activate() {
	// Intentionally no-op for the minimal skeleton.
}

register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Deactivation hook.
 *
 * Per Decision 019, deactivation must never delete Core-owned data. This
 * skeleton stores no data yet, so this remains a no-op by design.
 *
 * @return void
 */
function deactivate() {
	// Intentionally no-op: deactivation must never delete Core data (Decision 019).
}
