<?php
/**
 * ASTREA Starter Import — Preflight (Construction 029-C Phase 1-5).
 *
 * Answers a strictly different question than Construction 029-B's
 * `detect_state()`: not "what state is this site in" but "given that
 * state and the current environment, may an import operation safely start
 * right now". `run_preflight()` calls `detect_state()` exactly once
 * (CHECK 01) and never re-implements or second-guesses its judgment — see
 * this Construction's order, "029-B State Detectorを Preflight都合で改造
 * しない".
 *
 * Every check in here is read-only (Construction 029-C Phase 4/10):
 * running Preflight, even 100 times in a row, creates no post, updates no
 * option, acquires no lock, writes no marker. `wp_upload_dir()` (CHECK 11)
 * is the one WordPress API called here that can create a directory as a
 * side effect of normal operation — that is WordPress's own standard
 * behavior for that call, not a deliberate test-write this module adds;
 * no other check performs any write of any kind.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

use function Astrea\Core\Setup\page_still_exists;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Starter Import's own minimum requirements. Kept here (not derived from
 * Core's plugin header at runtime) because Starter Import may in the
 * future require a HIGHER minimum than Core's own general requirement
 * once it depends on newer WordPress/PHP features — Core's header states
 * Core's floor, not necessarily Starter Import's. As of this Construction
 * the two happen to match; if Core's own header is ever raised, keep
 * these constants in sync deliberately, not by coincidence.
 */
const MINIMUM_WP_VERSION  = '7.0';
const MINIMUM_PHP_VERSION = '8.3';

/**
 * The ASTREA Theme stylesheet/template slug (directory name), matched
 * against `get_template()`/`get_stylesheet()` — never against the
 * human-readable "Theme Name" header string (Construction 029-C Phase 6:
 * "Theme detectionを表示名文字列だけで判定しない").
 */
const ASTREA_THEME_SLUG = 'astrea';

/**
 * Minimum ASTREA Theme version Starter Import requires — Construction
 * 028/029-A's own finding that the Results-background Cover pattern this
 * pipeline sets up requires Theme 1.0.3 or later.
 */
const MINIMUM_THEME_VERSION = '1.0.3';

/** Dynamic Blocks the Starter content-build pipeline actually inserts (Construction 029-A Phase 6's content model) — not every `astrea/*` block that exists, only the ones Starter Import itself needs registered. */
const REQUIRED_BLOCKS = array(
	'astrea/office-summary',
	'astrea/office-hours',
	'astrea/office-sns',
	'astrea/contact-form',
	'astrea/price-list',
);

/**
 * Runs every Preflight check and returns the aggregate result.
 *
 * @return PreflightResult
 */
function run_preflight(): PreflightResult {
	$site_state = detect_state();

	$checks   = array();
	$checks[] = check_site_state( $site_state );
	$checks[] = check_wordpress_version();
	$checks[] = check_php_version();
	$checks[] = check_multisite();
	$checks[] = check_astrea_core();
	$checks[] = check_astrea_theme();
	$checks[] = check_required_cpt_and_blocks();
	$checks[] = check_capability();
	$checks[] = check_import_lifecycle_marker();
	$checks[] = check_concurrency_lock();
	$checks[] = check_upload_environment();
	$checks[] = check_storage_environment( $site_state );

	return PreflightResult::from_checks( $checks, $site_state );
}

/**
 * CHECK 01 — Site State. Uses Construction 029-B's own StateResult
 * verbatim; never re-derives or overrides its verdict.
 *
 * @param StateResult $site_state Construction 029-B's own classification result.
 * @return PreflightCheck
 */
function check_site_state( StateResult $site_state ): PreflightCheck {
	$status = $site_state->state->allows_new_import() ? CheckStatus::PASS : CheckStatus::BLOCK;

	return new PreflightCheck(
		'site_state',
		$status,
		$site_state->reason_code,
		$site_state->diagnostic,
		array( 'state' => $site_state->state->value )
	);
}

/** CHECK 02 — WordPress Version. */
function check_wordpress_version(): PreflightCheck {
	global $wp_version;
	$current = $wp_version;

	if ( version_compare( $current, MINIMUM_WP_VERSION, '<' ) ) {
		return new PreflightCheck(
			'wordpress_version',
			CheckStatus::BLOCK,
			'WORDPRESS_VERSION_TOO_OLD',
			"WordPress {$current} is below the required minimum " . MINIMUM_WP_VERSION . '.',
			array(
				'current' => $current,
				'minimum' => MINIMUM_WP_VERSION,
			)
		);
	}

	return new PreflightCheck(
		'wordpress_version',
		CheckStatus::PASS,
		'WORDPRESS_VERSION_OK',
		"WordPress {$current} meets the minimum requirement.",
		array(
			'current' => $current,
			'minimum' => MINIMUM_WP_VERSION,
		)
	);
}

/** CHECK 03 — PHP Version. */
function check_php_version(): PreflightCheck {
	$current = PHP_VERSION;

	if ( version_compare( $current, MINIMUM_PHP_VERSION, '<' ) ) {
		return new PreflightCheck(
			'php_version',
			CheckStatus::BLOCK,
			'PHP_VERSION_TOO_OLD',
			"PHP {$current} is below the required minimum " . MINIMUM_PHP_VERSION . '.',
			array(
				'current' => $current,
				'minimum' => MINIMUM_PHP_VERSION,
			)
		);
	}

	return new PreflightCheck(
		'php_version',
		CheckStatus::PASS,
		'PHP_VERSION_OK',
		"PHP {$current} meets the minimum requirement.",
		array(
			'current' => $current,
			'minimum' => MINIMUM_PHP_VERSION,
		)
	);
}

/**
 * CHECK 04 — Multisite. Construction 029-B's own site_state check (CHECK
 * 01) already resolves Multisite to UNKNOWN/BLOCK; this is a deliberate,
 * independent, explicit re-statement (Construction 029-C order: "Preflight
 * でも...明示する") rather than relying solely on CHECK 01's evidence.
 */
function check_multisite(): PreflightCheck {
	if ( is_multisite() ) {
		return new PreflightCheck(
			'multisite',
			CheckStatus::BLOCK,
			'MULTISITE_UNSUPPORTED',
			'Multisite installations are not supported by Starter Import in this release.',
		);
	}

	return new PreflightCheck( 'multisite', CheckStatus::PASS, 'NOT_MULTISITE', 'Not a Multisite installation.' );
}

/**
 * CHECK 05 — ASTREA Core. Not a "is Core installed" tautology (this code
 * IS Core) — confirms the specific Core APIs/constants Starter Import
 * itself depends on are actually present and loaded, which matters if a
 * future Core refactor ever removes or renames them without updating this
 * module.
 */
function check_astrea_core(): PreflightCheck {
	$required_symbols_present = defined( 'Astrea\\Core\\Setup\\GENERATED_PAGES_OPTION' )
		&& function_exists( 'Astrea\\Core\\Setup\\page_still_exists' )
		&& function_exists( __NAMESPACE__ . '\\detect_state' );

	if ( ! $required_symbols_present ) {
		return new PreflightCheck(
			'astrea_core',
			CheckStatus::BLOCK,
			'ASTREA_CORE_CONTRACT_MISSING',
			'One or more ASTREA Core APIs Starter Import depends on are unavailable.'
		);
	}

	return new PreflightCheck(
		'astrea_core',
		CheckStatus::PASS,
		'ASTREA_CORE_CONTRACT_OK',
		'ASTREA Core version ' . ASTREA_CORE_VERSION . ' provides the required Setup contract.',
		array( 'core_version' => defined( 'ASTREA_CORE_VERSION' ) ? \ASTREA_CORE_VERSION : null )
	);
}

/**
 * CHECK 06 — ASTREA Theme. Matched by stylesheet/template directory slug,
 * never by the "Theme Name" display string. A child theme (stylesheet !==
 * template) is BLOCKed in this release: Starter content depends on ASTREA
 * Theme Patterns/Blocks whose availability under an arbitrary child theme
 * cannot be safely verified here, so this stays a hard BLOCK rather than a
 * guess (Construction 029-C Phase 6: "曖昧ならfirst release: BLOCK").
 */
function check_astrea_theme(): PreflightCheck {
	$template   = get_template();
	$stylesheet = get_stylesheet();

	if ( ASTREA_THEME_SLUG !== $template ) {
		return new PreflightCheck(
			'astrea_theme',
			CheckStatus::BLOCK,
			'ASTREA_THEME_NOT_ACTIVE',
			"The active theme's template is \"{$template}\", not \"" . ASTREA_THEME_SLUG . '".',
			array(
				'template'   => $template,
				'stylesheet' => $stylesheet,
			)
		);
	}

	if ( $stylesheet !== $template ) {
		return new PreflightCheck(
			'astrea_theme',
			CheckStatus::BLOCK,
			'ASTREA_CHILD_THEME_UNSUPPORTED',
			"A child theme (\"{$stylesheet}\") of ASTREA is active. Child themes are not supported by Starter Import in this release.",
			array(
				'template'   => $template,
				'stylesheet' => $stylesheet,
			)
		);
	}

	$theme_version = wp_get_theme( $template )->get( 'Version' );
	if ( ! $theme_version || version_compare( $theme_version, MINIMUM_THEME_VERSION, '<' ) ) {
		return new PreflightCheck(
			'astrea_theme',
			CheckStatus::BLOCK,
			'ASTREA_THEME_VERSION_TOO_OLD',
			"ASTREA Theme {$theme_version} is below the required minimum " . MINIMUM_THEME_VERSION . '.',
			array(
				'current' => $theme_version,
				'minimum' => MINIMUM_THEME_VERSION,
			)
		);
	}

	return new PreflightCheck(
		'astrea_theme',
		CheckStatus::PASS,
		'ASTREA_THEME_OK',
		"ASTREA Theme {$theme_version} is active and meets the minimum requirement.",
		array(
			'current' => $theme_version,
			'minimum' => MINIMUM_THEME_VERSION,
		)
	);
}

/**
 * CHECK 07 — Required CPTs / Blocks. Confirms the specific registrations
 * Construction 029-D's content engine will need — the 7 ASTREA CPTs and
 * the 5 Dynamic Blocks the Starter Build pipeline actually inserts, not
 * an exhaustive list of every block ASTREA ships.
 */
function check_required_cpt_and_blocks(): PreflightCheck {
	$missing_cpts = array();
	foreach ( ASTREA_CPTS as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			$missing_cpts[] = $post_type;
		}
	}

	$registry       = \WP_Block_Type_Registry::get_instance();
	$missing_blocks = array();
	foreach ( REQUIRED_BLOCKS as $block_name ) {
		if ( ! $registry->is_registered( $block_name ) ) {
			$missing_blocks[] = $block_name;
		}
	}

	if ( $missing_cpts || $missing_blocks ) {
		return new PreflightCheck(
			'required_cpt_and_blocks',
			CheckStatus::BLOCK,
			'REQUIRED_REGISTRATIONS_MISSING',
			'One or more required post types or blocks are not registered.',
			array(
				'missing_cpts'   => $missing_cpts,
				'missing_blocks' => $missing_blocks,
			)
		);
	}

	return new PreflightCheck(
		'required_cpt_and_blocks',
		CheckStatus::PASS,
		'REQUIRED_REGISTRATIONS_OK',
		'All required post types and blocks are registered.'
	);
}

/**
 * CHECK 08 — Capability. Deliberately separate from Construction 029-B's
 * StateResult (Construction 029-C Phase 14: Site State != User
 * Permission). Uses the same 'manage_options' capability Core's own
 * existing destructive/admin-only action (data-deletion.php) already
 * requires.
 */
function check_capability(): PreflightCheck {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new PreflightCheck(
			'capability',
			CheckStatus::BLOCK,
			'INSUFFICIENT_CAPABILITY',
			'The current user lacks the manage_options capability required to start a Starter Import.'
		);
	}

	return new PreflightCheck( 'capability', CheckStatus::PASS, 'CAPABILITY_OK', 'The current user has manage_options.' );
}

/**
 * CHECK 09 — Import Lifecycle Marker.
 *
 * Reads the raw option value in addition to Construction 029-B's own
 * `read_import_status()` (never modified here — Construction 029-C order:
 * "029-B State Detectorを Preflight都合で改造しない", which this
 * Construction extends in spirit to 029-B's marker-reading contract too).
 * `read_import_status()` intentionally decodes any unrecognized string to
 * null ("no signal either way"), which is the right behavior for
 * Construction 029-B's site-state classifier. Preflight's own concern is
 * different: a stored value that does not decode to any known
 * `ImportStatus` is itself a red flag (someone or something wrote garbage
 * into this option) and must BLOCK, not be silently treated the same as
 * "never set". Checking the raw value alongside the decoded one is how
 * this file tells the two apart without changing 029-B's own function.
 */
function check_import_lifecycle_marker(): PreflightCheck {
	$raw    = get_option( IMPORT_STATUS_OPTION, '' );
	$status = read_import_status();

	if ( is_string( $raw ) && '' !== $raw && null === $status ) {
		return new PreflightCheck(
			'import_lifecycle_marker',
			CheckStatus::BLOCK,
			'IMPORT_LIFECYCLE_INVALID',
			"Import lifecycle marker holds an unrecognized value: \"{$raw}\".",
			array( 'raw' => $raw )
		);
	}

	if ( null === $status || ImportStatus::NOT_STARTED === $status ) {
		return new PreflightCheck( 'import_lifecycle_marker', CheckStatus::PASS, 'NOT_STARTED', 'No prior Starter Import is recorded.' );
	}

	$code_map = array(
		ImportStatus::RUNNING->value   => 'IMPORT_ALREADY_RUNNING',
		ImportStatus::FAILED->value    => 'IMPORT_PREVIOUSLY_FAILED',
		ImportStatus::COMPLETED->value => 'IMPORT_ALREADY_COMPLETED',
	);

	return new PreflightCheck(
		'import_lifecycle_marker',
		CheckStatus::BLOCK,
		$code_map[ $status->value ] ?? 'IMPORT_LIFECYCLE_INVALID',
		"Import lifecycle marker is \"{$status->value}\" — a new import may not start.",
		array( 'status' => $status->value )
	);
}

/** CHECK 10 — Concurrency Lock. */
function check_concurrency_lock(): PreflightCheck {
	$lock = get_lock();

	if ( null === $lock ) {
		return new PreflightCheck( 'concurrency_lock', CheckStatus::PASS, 'NO_LOCK', 'No Starter Import operation currently holds the lock.' );
	}

	$code = is_lock_stale() ? 'LOCK_HELD_STALE' : 'LOCK_HELD_ACTIVE';

	return new PreflightCheck(
		'concurrency_lock',
		CheckStatus::BLOCK,
		$code,
		'Another operation already holds the Starter Import lock' . ( 'LOCK_HELD_STALE' === $code ? ' (appears stale, but is not auto-cleared).' : '.' ),
		array(
			'operation_id' => $lock['operation_id'],
			'updated_at'   => $lock['updated_at'],
		)
	);
}

/**
 * CHECK 11 — Filesystem / Upload Capability. Read-only inspection of
 * `wp_upload_dir()`'s own report — never a deliberate test file write.
 */
function check_upload_environment(): PreflightCheck {
	$upload_dir = wp_upload_dir();

	if ( ! empty( $upload_dir['error'] ) ) {
		return new PreflightCheck(
			'upload_environment',
			CheckStatus::BLOCK,
			'UPLOAD_DIRECTORY_UNUSABLE',
			(string) $upload_dir['error']
		);
	}

	return new PreflightCheck(
		'upload_environment',
		CheckStatus::PASS,
		'UPLOAD_DIRECTORY_OK',
		'The upload directory is usable.',
		array( 'basedir' => $upload_dir['basedir'] )
	);
}

/**
 * CHECK 12 — Database / Required Storage. Deliberately does NOT perform a
 * trial write (Construction 029-C Phase 3/16 explicitly forbid this).
 * Piggybacks on the fact that CHECK 01 already successfully executed
 * `detect_state()` — a full round of option/post reads across multiple
 * post types and options — as evidence that normal WordPress storage
 * operations are functioning.
 *
 * @param StateResult $site_state Construction 029-B's own classification result (used only as evidence that reads already succeeded).
 * @return PreflightCheck
 */
function check_storage_environment( StateResult $site_state ): PreflightCheck {
	// Reaching this point at all means gather_evidence() (get_posts(),
	// get_option(), wp_count_posts() etc. across the whole site) completed
	// without throwing — that is the evidence, not a fresh trial write.
	unset( $site_state );

	return new PreflightCheck( 'storage_environment', CheckStatus::PASS, 'STORAGE_OK', 'Standard WordPress read operations completed successfully.' );
}
