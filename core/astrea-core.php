<?php
/**
 * Plugin Name: ASTREA Core
 * Plugin URI: https://project-if.com/astrea
 * Description: If Professional ASTREA — テーマを変更しても保持すべき情報とサイト共通機能を担当する、ASTREA Themeの公式推奨Plugin（任意）。「Coreは推奨する。しかしThemeを人質にしない。」（Decision 021）。
 * Version: 0.12.0
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

define( 'ASTREA_CORE_VERSION', '0.12.0' );
define( 'ASTREA_CORE_FILE', __FILE__ );
define( 'ASTREA_CORE_DIR', plugin_dir_path( __FILE__ ) );

require ASTREA_CORE_DIR . 'includes/shared.php';
require ASTREA_CORE_DIR . 'includes/editor-blocks.php';
require ASTREA_CORE_DIR . 'includes/office-profile.php';
require ASTREA_CORE_DIR . 'includes/office-profile-admin.php';
require ASTREA_CORE_DIR . 'includes/office-hours-block.php';
require ASTREA_CORE_DIR . 'includes/office-sns-block.php';
require ASTREA_CORE_DIR . 'includes/block-bindings.php';
require ASTREA_CORE_DIR . 'includes/professional-profile.php';
require ASTREA_CORE_DIR . 'includes/professional-profile-admin.php';
require ASTREA_CORE_DIR . 'includes/professional-profile-block.php';
require ASTREA_CORE_DIR . 'includes/professional-field-block.php';
require ASTREA_CORE_DIR . 'includes/service.php';
require ASTREA_CORE_DIR . 'includes/service-list-block.php';
require ASTREA_CORE_DIR . 'includes/price.php';
require ASTREA_CORE_DIR . 'includes/price-admin.php';
require ASTREA_CORE_DIR . 'includes/price-list-block.php';
require ASTREA_CORE_DIR . 'includes/faq.php';
require ASTREA_CORE_DIR . 'includes/faq-admin.php';
require ASTREA_CORE_DIR . 'includes/faq-list-block.php';
require ASTREA_CORE_DIR . 'includes/case.php';
require ASTREA_CORE_DIR . 'includes/case-admin.php';
require ASTREA_CORE_DIR . 'includes/case-list-block.php';
require ASTREA_CORE_DIR . 'includes/result.php';
require ASTREA_CORE_DIR . 'includes/result-admin.php';
require ASTREA_CORE_DIR . 'includes/results-list-block.php';
require ASTREA_CORE_DIR . 'includes/voice.php';
require ASTREA_CORE_DIR . 'includes/voice-list-block.php';
require ASTREA_CORE_DIR . 'includes/inquiry.php';
require ASTREA_CORE_DIR . 'includes/inquiry-email-confirmation.php';
require ASTREA_CORE_DIR . 'includes/inquiry-notifications.php';
require ASTREA_CORE_DIR . 'includes/inquiry-admin.php';
require ASTREA_CORE_DIR . 'includes/contact-form-block.php';
require ASTREA_CORE_DIR . 'includes/seo-plugin-detection.php';
require ASTREA_CORE_DIR . 'includes/seo-settings.php';
require ASTREA_CORE_DIR . 'includes/seo-admin.php';
require ASTREA_CORE_DIR . 'includes/breadcrumb.php';
require ASTREA_CORE_DIR . 'includes/seo-meta.php';
require ASTREA_CORE_DIR . 'includes/seo-structured-data.php';
require ASTREA_CORE_DIR . 'includes/ga4.php';
require ASTREA_CORE_DIR . 'includes/setup-checklist.php';
require ASTREA_CORE_DIR . 'includes/setup-pages.php';
require ASTREA_CORE_DIR . 'includes/setup-home.php';
require ASTREA_CORE_DIR . 'includes/setup-navigation.php';
require ASTREA_CORE_DIR . 'includes/setup-admin.php';
require ASTREA_CORE_DIR . 'includes/data-deletion.php';

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
 * Office Profile has nothing that needs seeding at activation time —
 * get_office_profile() already returns a well-formed default shape even
 * before the option has ever been saved.
 *
 * Professional Profile, Service and FAQ each register a post type with
 * its own rewrite rules (the `professionals` / `services` / `faq`
 * archives), so activation must register them and then flush rewrite
 * rules — otherwise the archive URLs 404 until WordPress happens to flush
 * rules for an unrelated reason. This does not create or modify any
 * content; it only makes routing aware of the post types.
 *
 * Construction Order 010 adds CASE (`cases` archive) and VOICE (`voices`
 * archive) to this same list for the same reason. RESULTS has no rewrite
 * rule (non-public, like Price) but is registered here too for
 * consistency with Price's own inclusion.
 *
 * Inquiry (Contact, Construction Order 005) schedules two daily Cron
 * events (Retention cleanup, digest notification) on activation/
 * reactivation, and runs one Retention cleanup pass immediately — this
 * catches up on anything that should have expired while Core was
 * inactive (its Cron events don't fire while deactivated).
 *
 * @return void
 */
function activate() {
	ProfessionalProfile\register_post_type_and_meta();
	Service\register_post_type_and_meta();
	Price\register_post_type_and_meta();
	Faq\register_post_type_and_meta();
	CaseStudy\register_post_type_and_meta();
	Result\register_post_type_and_meta();
	Voice\register_post_type();
	flush_rewrite_rules();

	Inquiry\schedule_cleanup_cron();
	Inquiry\reschedule_digest_cron();
	Inquiry\cleanup_expired();
}

register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Deactivation hook.
 *
 * Per Decision 019, deactivation must never delete Core-owned data
 * (including the Office Profile option and all astrea_professional posts
 * / postmeta / featured images). Only rewrite rules are flushed, so a
 * deactivated Core doesn't leave a dangling `professionals` rewrite rule
 * pointing at a post type that is no longer registered.
 *
 * Inquiry's Cron events are unscheduled (not the data itself — see
 * Decision 019) so a deactivated Core doesn't leave dangling scheduled
 * events calling into code that is no longer loaded.
 *
 * @return void
 */
function deactivate() {
	flush_rewrite_rules();

	Inquiry\clear_cleanup_cron();
	Inquiry\clear_digest_cron();
}
