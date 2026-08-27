<?php
/**
 * SEO — structured data (JSON-LD): Organization/Person and BreadcrumbList
 * (Construction Order 006, Decision 026).
 *
 * Only the data types Decision 026 explicitly approved are emitted:
 * Organization (Office Profile), Person within Organization.employee
 * (Professional Profile), and BreadcrumbList (Decision 010). Price/Offer
 * and FAQ/FAQPage are deliberately NOT generated here — see Decision 026
 * and docs/research/2026-08-27_construction_order_006_research.md §5-6.
 *
 * All JSON output goes through wp_json_encode() with JSON_HEX_TAG so a
 * value containing a literal "</script>" cannot break out of the
 * surrounding <script> tag (Security requirement: script closing
 * injection). No manual string concatenation is used to build JSON.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Day keys (office-profile.php's WEEKDAYS) mapped to schema.org's dayOfWeek URIs. */
const SCHEMA_DAY_OF_WEEK = array(
	'mon' => 'https://schema.org/Monday',
	'tue' => 'https://schema.org/Tuesday',
	'wed' => 'https://schema.org/Wednesday',
	'thu' => 'https://schema.org/Thursday',
	'fri' => 'https://schema.org/Friday',
	'sat' => 'https://schema.org/Saturday',
	'sun' => 'https://schema.org/Sunday',
);

add_action( 'wp_head', __NAMESPACE__ . '\\output_structured_data' );

/**
 * Outputs Organization+Person and BreadcrumbList JSON-LD, unless a known
 * SEO Plugin is already handling structured data (Decision 026/018).
 *
 * @return void
 */
function output_structured_data() {
	if ( is_known_seo_plugin_active() ) {
		return;
	}

	$organization = build_organization_json_ld();
	if ( null !== $organization ) {
		print_json_ld( $organization );
	}

	$breadcrumb = build_breadcrumb_json_ld();
	if ( null !== $breadcrumb ) {
		print_json_ld( $breadcrumb );
	}
}

/**
 * Builds the Organization JSON-LD from Office Profile + published
 * Professional Profiles. Returns null when there is nothing meaningful to
 * say (no office name set) — an empty/placeholder Organization is not
 * output.
 *
 * @return array|null
 */
function build_organization_json_ld(): ?array {
	$office = \Astrea\Core\OfficeProfile\get_office_profile();

	if ( '' === trim( $office['office_name'] ) ) {
		return null;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => $office['office_name'],
		'url'      => home_url( '/' ),
	);

	if ( '' !== trim( $office['address'] ) ) {
		$data['address'] = array(
			'@type'         => 'PostalAddress',
			'streetAddress' => $office['address'],
		);
	}

	if ( '' !== trim( $office['phone'] ) ) {
		$data['telephone'] = $office['phone'];
	}

	$hours = build_opening_hours_specification( $office['business_hours']['weekly'] ?? array() );
	if ( ! empty( $hours ) ) {
		$data['openingHoursSpecification'] = $hours;
	}

	$employees = build_employee_list();
	if ( ! empty( $employees ) ) {
		$data['employee'] = $employees;
	}

	return $data;
}

/**
 * Converts Office Profile's weekly business hours into
 * schema.org OpeningHoursSpecification entries. Only the regular weekly
 * schedule is mapped (Decision 026 FIX 2) — exceptions/closures are
 * intentionally excluded, since mixing them into a weekly-recurrence
 * schema would misrepresent temporary closures as permanent ones.
 *
 * @param array $weekly Office Profile's business_hours.weekly.
 * @return array[]
 */
function build_opening_hours_specification( array $weekly ): array {
	$specs = array();

	foreach ( SCHEMA_DAY_OF_WEEK as $day_key => $schema_day ) {
		$row = $weekly[ $day_key ] ?? null;

		if ( ! is_array( $row ) || ! empty( $row['closed'] ) ) {
			continue;
		}

		$opens  = $row['open'] ?? '';
		$closes = $row['close'] ?? '';

		if ( '' === $opens || '' === $closes ) {
			continue; // Incomplete data: don't guess a time.
		}

		$specs[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $schema_day,
			'opens'     => $opens,
			'closes'    => $closes,
		);
	}

	return $specs;
}

/**
 * Builds the `employee` (Person[]) list from published Professional
 * Profiles, in the same deterministic order used everywhere else
 * (menu_order -> title -> ID). The `is_representative` flag is
 * deliberately NOT translated into any JSON-LD property — schema.org has
 * no standard "representative" concept, and Decision 026 explicitly
 * forbids inventing one.
 *
 * @return array[]
 */
function build_employee_list(): array {
	if ( ! function_exists( '\Astrea\Core\ProfessionalProfile\get_profiles' ) ) {
		return array();
	}

	$employees = array();

	foreach ( \Astrea\Core\ProfessionalProfile\get_profiles() as $profile ) {
		if ( '' === trim( $profile['name'] ) ) {
			continue;
		}

		$person = array(
			'@type' => 'Person',
			'name'  => $profile['name'],
		);

		if ( '' !== trim( $profile['qualification'] ) ) {
			$person['jobTitle'] = $profile['qualification'];
		}

		if ( '' !== trim( wp_strip_all_tags( $profile['bio'] ) ) ) {
			$person['description'] = wp_strip_all_tags( $profile['bio'] );
		}

		if ( ! empty( $profile['photo_id'] ) ) {
			$image_url = wp_get_attachment_image_url( $profile['photo_id'], 'full' );
			if ( $image_url ) {
				$person['image'] = $image_url;
			}
		}

		$employees[] = $person;
	}

	return $employees;
}

/**
 * Builds the BreadcrumbList JSON-LD from the same get_breadcrumb_items()
 * used by the visual Breadcrumb block, so the two can never diverge.
 *
 * @return array|null
 */
function build_breadcrumb_json_ld(): ?array {
	$items = get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return null;
	}

	$list_items = array();

	foreach ( $items as $index => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $item['label'],
		);

		if ( ! empty( $item['url'] ) ) {
			$entry['item'] = $item['url'];
		}

		$list_items[] = $entry;
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list_items,
	);
}

/**
 * Prints one JSON-LD <script> block. JSON_HEX_TAG escapes `<`/`>` into
 * unicode escapes so a value containing "</script>" cannot terminate the
 * tag early; default slash-escaping is left in place as a second layer.
 *
 * @param array $data Structured data.
 * @return void
 */
function print_json_ld( array $data ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() with JSON_HEX_TAG is the escaping mechanism here; there is no HTML to further esc_html().
}
