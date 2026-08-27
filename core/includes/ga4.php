<?php
/**
 * GA4 — basic measurement tag output (Construction Order 009).
 *
 * 02仕様書§18 ("GA4 / Search Console") requires GA4 to be settable from the
 * admin screen without asking the user to edit HTML/JavaScript, and to load
 * nothing when unused. Decision 009 narrowed *Search Console* specifically
 * (no API/OAuth/rank tracking) but never removed GA4 itself — the
 * Remaining Work Audit (2026-08-27) found this basic GA4 setting had never
 * actually been built. This file is the minimal implementation: a
 * Measurement ID setting (stored in `seo-settings.php`, alongside OGP/
 * Search Console) and a conditional `gtag.js` snippet, using Google's own
 * standard loader — no custom analytics client, no API, no OAuth, no
 * report/dashboard UI.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Ga4;

use function Astrea\Core\Seo\get_seo_settings;
use function Astrea\Core\Seo\is_known_seo_plugin_active;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Known Analytics Plugin basenames that already output their own GA4 tag,
 * checked the same way as `seo-plugin-detection.php`'s known-list (Decision
 * 018: a small, explicitly-verified, Update-extensible list — never a guess
 * at an unknown Plugin, never a giant compatibility table). Verified against
 * each Plugin's real main file (2026-08-27):
 * - Site Kit by Google: `google-site-kit/google-site-kit.php`
 * - MonsterInsights (Google Analytics for WordPress): `google-analytics-for-wordpress/googleanalytics.php`
 */
const KNOWN_ANALYTICS_PLUGIN_BASENAMES = array(
	'google-site-kit/google-site-kit.php',
	'google-analytics-for-wordpress/googleanalytics.php',
);

/**
 * Whether a known Analytics Plugin that already handles GA4 output is active.
 *
 * @return bool
 */
function is_known_analytics_plugin_active(): bool {
	$active = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	foreach ( KNOWN_ANALYTICS_PLUGIN_BASENAMES as $basename ) {
		if ( in_array( $basename, $active, true ) ) {
			return true;
		}
	}

	return false;
}

add_action( 'wp_head', __NAMESPACE__ . '\\output_ga4_tag' );

/**
 * Outputs Google's standard gtag.js loader when a Measurement ID is
 * configured. Suppressed when a known Analytics Plugin is active, to avoid
 * double-counting pageviews (same reasoning as SEO Plugin suppression,
 * Decision 018/026) — this only checks the small known list above; an
 * unrecognized Analytics Plugin is not detected, matching Decision 018's
 * "never guess at an unknown Plugin" principle.
 *
 * @return void
 */
function output_ga4_tag() {
	$measurement_id = get_seo_settings()['ga4_measurement_id'];

	if ( '' === $measurement_id ) {
		return;
	}

	if ( is_known_seo_plugin_active() || is_known_analytics_plugin_active() ) {
		return;
	}

	$id_json = wp_json_encode( $measurement_id );
	?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Google's own documented gtag.js bootstrap is always embedded inline with the dynamic Measurement ID; wp_enqueue_script() has no mechanism for this pattern. ?>
<script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- same reason: the inline bootstrap must run before/without an external file, per Google's own gtag.js setup instructions. ?>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', <?php echo $id_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() of a value already validated against /^G-[A-Z0-9]{4,}$/ by seo-settings.php's sanitizer; safe JS literal, not HTML. ?>);
</script>
	<?php
}
