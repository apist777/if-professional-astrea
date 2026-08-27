<?php
/**
 * Uninstall handler for ASTREA Core.
 *
 * Per Decision 019 (docs/specifications/04_astrea_free_v1_preconstruction_decisions.md),
 * complete deletion of Core-owned data must only happen through the
 * plugin's own explicit, user-confirmed deletion flow — never merely
 * because the plugin was deleted from the Plugins screen. Plain WordPress
 * "Delete" must keep behaving as a safe default that preserves data.
 *
 * As of Construction Order 002, Office Profile is the first concrete
 * example of such data: it is stored under the option name
 * `Astrea\Core\OfficeProfile\OPTION_NAME` ('astrea_core_office_profile',
 * see includes/office-profile.php). That option is intentionally NOT
 * deleted here.
 *
 * As of Construction Order 003, Professional Profile is the second: every
 * `astrea_professional` post (see includes/professional-profile.php) and
 * its postmeta (astrea_professional_*) is Core-owned data under the same
 * policy — intentionally NOT deleted here either.
 *
 * Professional Profile photos are a special case: they are ordinary
 * Media Library attachments the user uploaded (WordPress's own asset, not
 * something ASTREA copies into its own storage — see
 * includes/professional-profile.php's featured-image approach). Even a
 * future confirmed "delete all ASTREA Core data" action must NOT delete
 * these attachments — only the astrea_professional post that references
 * one as its featured image. The attachment itself remains the user's
 * asset in the Media Library, exactly as WordPress's own "delete post"
 * behavior already treats featured images (it never deletes the
 * attachment either).
 *
 * As of Construction Order 004, Service (`astrea_service`), Price
 * (`astrea_price`) and FAQ (`astrea_faq`, plus its `astrea_faq_category`
 * taxonomy terms) posts/postmeta are Core-owned data under the same
 * policy — intentionally NOT deleted here either.
 *
 * As of Construction Order 005, `astrea_inquiry` posts/postmeta (Contact
 * submissions) and the `astrea_core_contact_settings` option are
 * Core-owned data under the same policy — intentionally NOT deleted here.
 * Per Decision 019, this is a SEPARATE deletion path from the time-based
 * Retention auto-delete (Decision 004): Retention removes individual
 * inquiries once they age past the configured period regardless of
 * plugin activation state; plain Uninstall must not additionally wipe
 * whatever inquiries still happen to be within their Retention window.
 *
 * As of Construction Order 006, the `astrea_core_seo_settings` option
 * (site-wide OGP fallback image reference, Search Console verification
 * code) is Core-owned data under the same policy — intentionally NOT
 * deleted here. The OGP fallback image itself is, like Professional
 * Profile photos, an ordinary Media Library attachment the user selected —
 * this option only stores its ID, never a copy of the file.
 *
 * As of Construction Order 007, `astrea_core_generated_pages` (an index of
 * which Page ID was generated for the Setup "基本ページを作成する" action)
 * is Core-owned data under the same policy — intentionally NOT deleted
 * here. The generated Pages themselves (and any Setup-generated
 * `wp_navigation` post) are ordinary WordPress content the site owner can
 * freely edit or delete, exactly like Decision 016 requires; only this
 * index option is Core's own bookkeeping.
 *
 * As of Construction Order 009, the explicit, confirmed "delete all ASTREA
 * Core data" admin action anticipated above has been implemented — see
 * includes/data-deletion.php ("データ削除" under the ASTREA admin menu).
 * This file remains a permanent no-op regardless: that action is reached
 * only through its own deliberate confirmation flow (capability, Nonce,
 * a checkbox, and an exact typed confirmation phrase), never merely by
 * deleting the plugin from the Plugins screen. Its own deletion inventory
 * mirrors this file's history exactly, and likewise never touches
 * Media Library attachments or Setup-generated Pages/Navigation.
 *
 * @package Astrea\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally no-op: `astrea_core_office_profile`, `astrea_professional`,
// `astrea_service`, `astrea_price`, `astrea_faq`, `astrea_inquiry`
// posts/postmeta/taxonomy terms, `astrea_core_contact_settings`,
// `astrea_core_seo_settings`, `astrea_core_generated_pages`, and their
// featured-image / OGP-image attachments must all survive a plain
// "Delete" from the Plugins screen. Only includes/data-deletion.php's own
// explicit, user-confirmed flow may remove them — and that flow must
// never delete Media Library attachments or Setup-generated Pages/
// Navigation (those are user content per Decision 016).
