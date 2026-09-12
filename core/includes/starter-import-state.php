<?php
/**
 * ASTREA Starter Import — canonical site states (Construction 029-B).
 *
 * Domain foundation only. This file defines WHAT the possible states are
 * and what each one means; it performs no detection and touches no
 * database — see starter-import-detection.php for that. Following
 * Construction 029-A's Architecture Decision Record, Starter Import lives
 * as a namespace-isolated submodule inside ASTREA Core (not the Theme, not
 * a separate plugin for the first release).
 *
 * PHP 8.3 is Core's own minimum requirement (see astrea-core.php's own
 * header), so backed enums (PHP 8.1+) are used here rather than the
 * `final class` + `const` pattern the rest of Core uses for CPT/option
 * name constants — those are simple string identifiers, not a closed set
 * of mutually exclusive states with behavior attached to each member,
 * which is exactly what enums exist for.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Canonical site states for Starter Import eligibility (Construction 029-A
 * Phase 3 / 029-B Phase 2).
 *
 * These are the ONLY states a site can be classified into. A detector that
 * cannot confidently place a site into one of FRESH/ASTREA_READY/COMPLETED/
 * EXISTING_CONTENT/PARTIAL must return UNKNOWN — never invent a seventh
 * state and never guess (Phase 9: fail closed).
 */
enum SiteState: string {

	/**
	 * WordPress itself is close to its just-installed state: no meaningful
	 * user content, and ASTREA's own generated content does not exist yet
	 * either. Starter Import candidate.
	 */
	case FRESH = 'fresh';

	/**
	 * Only known ASTREA Theme/Core-generated content exists (Setup's
	 * generated pages/navigation, or nothing beyond Theme/Core activation
	 * itself) — no meaningful user content. Starter Import candidate.
	 */
	case ASTREA_READY = 'astrea_ready';

	/**
	 * A Starter Import has already completed on this site (ownership
	 * marker present and internally consistent). Re-import is BLOCKed —
	 * see Construction 029-A Phase 10.
	 */
	case COMPLETED = 'completed';

	/**
	 * Content judged to be user-created (not attributable to WordPress's
	 * own untouched initial content, nor to ASTREA's own tracked
	 * generated content) is present. First-release Starter Import is
	 * BLOCKed — see Construction 029-A Phase 11.
	 */
	case EXISTING_CONTENT = 'existing_content';

	/**
	 * A Starter Import was started (or previously failed) and did not
	 * reach COMPLETED. Not eligible for a fresh import; only a future
	 * retry flow (Construction 029-G) may act on this state.
	 */
	case PARTIAL = 'partial';

	/**
	 * Evidence is contradictory, or does not safely fit any of the above
	 * states (including environments this module does not support yet,
	 * such as Multisite — Construction 029-B Phase 13). Always BLOCKed.
	 */
	case UNKNOWN = 'unknown';

	/**
	 * Whether this state permits starting a NEW Starter Import.
	 *
	 * Deliberately only FRESH and ASTREA_READY return true — every other
	 * state is a hard BLOCK for this Construction's scope (retry/reset
	 * flows are future Constructions, not this one).
	 */
	public function allows_new_import(): bool {
		return self::FRESH === $this || self::ASTREA_READY === $this;
	}
}
