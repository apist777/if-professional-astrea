<?php
/**
 * ASTREA Starter Import — evidence and result value objects
 * (Construction 029-B Phase 3).
 *
 * `Evidence` is the raw, structured facts gathered from the current site
 * (Construction 029-B Phase 10: read-only). `StateResult` is the outcome
 * of classifying that Evidence (Construction 029-B Phase 3/9): never a
 * bare string, always state + import_allowed + a machine-readable reason
 * code + a short human-readable diagnostic + the evidence it was based on
 * + any warnings. Both are immutable (readonly) — a result is a snapshot
 * of one point in time, never mutated after the fact.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Raw facts gathered from the current WordPress site. Nothing in this
 * class interprets what the facts MEAN — that is Domain\classify()'s job
 * exclusively (Construction 029-B Phase 1: keep Detection and Domain
 * separate). Every property is data already read; constructing this
 * object performs no further queries.
 */
final class Evidence {

	/**
	 * Constructs an immutable snapshot of gathered facts.
	 *
	 * @param bool                                                                        $is_multisite               Whether this is a Multisite install (Phase 13 — unsupported for now).
	 * @param array{count:int, untouched:bool}                                            $wp_default_post   The single default "Hello world!" post, if it still exists in recognizable, untouched form.
	 * @param array<string, array{expected_id:int|null, exists:bool, title_matches:bool}> $wp_default_pages Known default WordPress pages (Sample Page, Privacy Policy) keyed by a stable internal key, each with whether an untouched instance was found.
	 * @param array<string, array{recorded_id:int|null, exists:bool}>                     $astrea_generated_pages Core's own `Setup\GENERATED_PAGES_OPTION` map, cross-checked against reality.
	 * @param bool                                                                        $astrea_generated_navigation_exists Whether Core's own tracked generated Navigation post still exists.
	 * @param array<string, int>                                                          $astrea_cpt_counts      Post counts (any status) per ASTREA CPT (astrea_professional, astrea_service, astrea_case, astrea_result, astrea_price, astrea_faq, astrea_voice).
	 * @param int                                                                         $unaccounted_page_count      Pages that are neither the recognized WordPress defaults nor a Core-tracked generated page.
	 * @param int                                                                         $unaccounted_post_count      Posts (post_type=post) that are not the untouched default "Hello world!" post.
	 * @param int                                                                         $attachment_count            Media Library attachments of any kind.
	 * @param ImportStatus|null                                                           $import_status           Value of `IMPORT_STATUS_OPTION`, decoded — null if the option does not exist or holds an unrecognized value.
	 * @param string|null                                                                 $import_version              Value of `IMPORT_VERSION_OPTION`, if present.
	 * @param bool                                                                        $import_generated_marker_present Whether `IMPORT_GENERATED_OPTION` exists and is a non-empty array.
	 */
	public function __construct(
		public readonly bool $is_multisite,
		public readonly array $wp_default_post,
		public readonly array $wp_default_pages,
		public readonly array $astrea_generated_pages,
		public readonly bool $astrea_generated_navigation_exists,
		public readonly array $astrea_cpt_counts,
		public readonly int $unaccounted_page_count,
		public readonly int $unaccounted_post_count,
		public readonly int $attachment_count,
		public readonly ?ImportStatus $import_status,
		public readonly ?string $import_version,
		public readonly bool $import_generated_marker_present
	) {
	}

	/** Total across all ASTREA CPTs — a single "is there any Starter-shaped content at all" figure. */
	public function total_astrea_cpt_count(): int {
		return array_sum( $this->astrea_cpt_counts );
	}

	/**
	 * Whether every entry Core itself recorded as generated actually still
	 * exists as recorded. An entry with no recorded ID at all (that page
	 * simply hasn't been generated yet) is NOT a consistency problem —
	 * only a recorded ID that no longer resolves to a real page is.
	 */
	public function astrea_generated_pages_consistent(): bool {
		foreach ( $this->astrea_generated_pages as $entry ) {
			if ( null !== $entry['recorded_id'] && ! $entry['exists'] ) {
				return false;
			}
		}
		return true;
	}

	/** Whether ASTREA has generated anything at all yet (pages or navigation). */
	public function has_any_astrea_generated_content(): bool {
		foreach ( $this->astrea_generated_pages as $entry ) {
			if ( $entry['exists'] ) {
				return true;
			}
		}
		return $this->astrea_generated_navigation_exists;
	}
}
