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
 * A future Construction Order will add an explicit, confirmed "delete all
 * ASTREA Core data" admin action — this file will call into it only when
 * the user has affirmatively chosen full deletion, not automatically on
 * every plugin removal.
 *
 * @package Astrea\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally no-op: `astrea_core_office_profile`, `astrea_professional`
// posts/postmeta, and their featured-image attachments must all survive a
// plain "Delete" from the Plugins screen. Only a future, explicit,
// user-confirmed deletion flow may remove the first two — and that flow
// must never delete Media Library attachments.
