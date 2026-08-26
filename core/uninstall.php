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
 * deleted here. A future Construction Order will add an explicit,
 * confirmed "delete all ASTREA Core data" admin action — this file will
 * call into it only when the user has affirmatively chosen full deletion,
 * not automatically on every plugin removal.
 *
 * @package Astrea\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally no-op: `astrea_core_office_profile` (and any future
// Core-owned option/post type) must survive a plain "Delete" from the
// Plugins screen. Only a future, explicit, user-confirmed deletion flow
// may remove it.
