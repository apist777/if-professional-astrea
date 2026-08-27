<?php
/**
 * SEO — known SEO Plugin detection (Construction Order 006, Decision 018/026).
 *
 * Detection uses only the WordPress-standard `active_plugins` option (the
 * same data `is_plugin_active()` reads) — never a Plugin's own internal
 * classes/constants/APIs. This keeps detection resilient to internal
 * refactors in those Plugins and avoids the "巨大な互換表" the order
 * explicitly warns against: the list below is a handful of entries and is
 * designed to grow via ordinary Update, not a spec change (Decision 018).
 *
 * Per Decision 026, detecting one of these Plugins suppresses ASTREA's own
 * meta description / OGP / Organization+Person JSON-LD / BreadcrumbList
 * JSON-LD (all of which these Plugins already provide as core features).
 * It does NOT suppress the Search Console verification meta tag (harmless
 * to have alongside a Plugin's own, unrelated verification field — each
 * service only checks for its own specific code) nor the visual Breadcrumb
 * block (Theme-placed content, not `wp_head` output, so it does not
 * duplicate anything the Plugin injects into `<head>`).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Known SEO Plugin main-file basenames (as used by WordPress's own
 * `active_plugins` option / `is_plugin_active()`). Confirmed against each
 * Plugin's published source (docs/research/2026-08-27_construction_order_006_research.md).
 */
const KNOWN_PLUGIN_BASENAMES = array(
	'wordpress-seo/wp-seo.php',                    // Yoast SEO.
	'all-in-one-seo-pack/all_in_one_seo_pack.php', // All in One SEO.
	'seo-by-rank-math/rank-math.php',              // Rank Math.
	'wp-seopress/seopress.php',                    // SEOPress.
);

/**
 * Whether a known SEO Plugin is currently active (single-site or
 * network-activated). Unknown Plugins are never guessed at (Decision 018).
 *
 * @return bool
 */
function is_known_seo_plugin_active(): bool {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	foreach ( KNOWN_PLUGIN_BASENAMES as $basename ) {
		if ( in_array( $basename, $active_plugins, true ) ) {
			return true;
		}
	}

	if ( is_multisite() ) {
		$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );

		foreach ( KNOWN_PLUGIN_BASENAMES as $basename ) {
			if ( isset( $network_active[ $basename ] ) ) {
				return true;
			}
		}
	}

	return false;
}
