<?php
/**
 * Construction 025-L1 — Internal Link Portability Fix.
 *
 * Makes every Yamada Demo *internal* link follow whatever local URL / port
 * the environment is currently served at, so a rebuild (or a clone served
 * on a different port, or a WXR import into a subdirectory install such as
 * demo.project-if.jp/astrea/) never leaves a dead `http://127.0.0.1:88xx/…`
 * link behind.
 *
 * Three mechanisms, all WordPress-native and all re-run-safe:
 *
 *  1. The generated `wp_navigation` menu (Header + Footer both render it)
 *     is rebuilt so each `wp:navigation-link` is a *reference*, not a
 *     frozen URL:
 *       - a page target  -> {"kind":"post-type","type":"page","id":<id>}
 *       - a CPT archive   -> {"kind":"post-type-archive","type":"<cpt>"}
 *     WordPress then calls get_permalink()/get_post_type_archive_link() at
 *     render time, so the href is always built from the *current*
 *     home_url() (subpath included). `Astrea\Core\Setup\generate_navigation()`
 *     still emits `kind:"custom"` frozen URLs — this script converts its
 *     output; Core is not modified.
 *
 *  2. Construction 025-CLOSEOUT — the two placeholder Contact CTA buttons
 *     that `theme/patterns/home-hero.php` and `theme/patterns/home-cta.php`
 *     legitimately ship with `href="#"` (a generic Theme pattern cannot
 *     know any specific site's Contact page at authoring time — this is
 *     correct product design, the same reason a real site owner picks the
 *     target page themselves via the Button block's Link UI) are wired up
 *     to *this demo's own* generated Contact page, exactly the way a site
 *     owner would: by setting the button's `url` attribute to
 *     get_permalink( $contact_id ). Demo-content-level, zero Theme/Core
 *     change. The Hero phone button ("お電話でのご相談") already carries a
 *     `metadata.bindings.url` (Office Profile phone_tel) and is explicitly
 *     never touched here.
 *
 *  3. A serialization-aware search-replace over post_content / postmeta /
 *     options rewrites any *other* absolute internal URL whose host:port
 *     differs from the current one (stale media URLs in old revisions,
 *     cloned-DB leftovers, etc.) to the current home_url(). External URLs
 *     (project-if.jp, googletagmanager.com, …) are never touched.
 *
 * Idempotent: on an already-portable environment it finds nothing to
 * change. No hardcoded host, port, IP or production URL anywhere —
 * everything is derived from home_url() / get_permalink() / get_option()
 * at run time.
 *
 * Intended as the LAST step of the reproducibility pipeline (after every
 * build-content / integrate-* script), and also safe to run standalone
 * against an existing local demo.
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-includes/blocks.php';

function line( $m ) {
	echo $m . "\n";
	// Optional: also append to a mounted log dir when present (test harness).
	if ( is_dir( '/l1log' ) && is_writable( '/l1log' ) ) {
		file_put_contents( '/l1log/out.txt', $m . "\n", FILE_APPEND );
	}
}

$current_home = untrailingslashit( home_url() );
$current_host = wp_parse_url( $current_home, PHP_URL_HOST );
$current_port = wp_parse_url( $current_home, PHP_URL_PORT );
line( 'Current home_url(): ' . $current_home . '  (host=' . $current_host . ' port=' . ( $current_port ?: '(default)' ) . ')' );

// ---------------------------------------------------------------------
// 1. Rebuild the generated Navigation with reference-based links.
// ---------------------------------------------------------------------

/**
 * Locate the wp_navigation post Setup generated. Prefer the tracked
 * option; fall back to the newest wp_navigation whose content carries
 * ASTREA's own labels.
 */
function astrea_l1_find_generated_nav(): int {
	$tracked = (int) get_option( 'astrea_core_generated_navigation', 0 );
	if ( $tracked && get_post( $tracked ) instanceof WP_Post && 'wp_navigation' === get_post_type( $tracked ) ) {
		return $tracked;
	}
	$navs = get_posts( array(
		'post_type'      => 'wp_navigation',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'orderby'        => 'ID',
		'order'          => 'DESC',
	) );
	foreach ( $navs as $nav ) {
		if ( false !== strpos( $nav->post_content, 'navigation-link' )
			&& ( false !== strpos( $nav->post_content, '事務所概要' ) || false !== strpos( $nav->post_content, 'お問い合わせ' ) ) ) {
			return (int) $nav->ID;
		}
	}
	return 0;
}

/**
 * Build the same curated link set as Core's navigation_links(), but as
 * portable reference blocks. Returns the wp:navigation-link markup.
 */
function astrea_l1_build_portable_nav_markup(): string {
	$generated_pages = get_option( 'astrea_core_generated_pages', array() );
	$blocks          = array();

	$page_link = static function ( $label, $page_id ) {
		$page_id = (int) $page_id;
		if ( $page_id <= 0 ) {
			return null;
		}
		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post || 'trash' === $post->post_status ) {
			return null;
		}
		return sprintf(
			'<!-- wp:navigation-link %s /-->',
			wp_json_encode(
				array(
					'label' => $label,
					'type'  => 'page',
					'id'    => $page_id,
					'url'   => get_permalink( $page_id ), // hint only; WP re-resolves for kind=post-type
					'kind'  => 'post-type',
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	};

	$archive_link = static function ( $label, $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return null;
		}
		$count = (int) wp_count_posts( $post_type )->publish;
		if ( $count < 1 ) {
			return null;
		}
		$url = get_post_type_archive_link( $post_type );
		if ( ! $url ) {
			return null;
		}
		return sprintf(
			'<!-- wp:navigation-link %s /-->',
			wp_json_encode(
				array(
					'label' => $label,
					'type'  => $post_type,
					'url'   => $url, // hint only; WP re-resolves for kind=post-type-archive
					'kind'  => 'post-type-archive',
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	};

	// Same order / same conditions as Astrea\Core\Setup\navigation_links().
	$candidates = array(
		$page_link( __( '事務所概要', 'astrea-core' ), $generated_pages['about'] ?? 0 ),
		$archive_link( __( '取扱業務', 'astrea-core' ), 'astrea_service' ),
		$archive_link( __( '専門家紹介', 'astrea-core' ), 'astrea_professional' ),
		$page_link( __( '料金', 'astrea-core' ), $generated_pages['price'] ?? 0 ),
		$archive_link( __( 'FAQ', 'astrea-core' ), 'astrea_faq' ),
		$page_link( __( 'お問い合わせ', 'astrea-core' ), $generated_pages['contact'] ?? 0 ),
	);

	foreach ( $candidates as $block ) {
		if ( null !== $block ) {
			$blocks[] = $block;
		}
	}

	return implode( "\n", $blocks ) . "\n";
}

$nav_id = astrea_l1_find_generated_nav();
if ( $nav_id ) {
	$new_markup = astrea_l1_build_portable_nav_markup();
	$existing   = get_post( $nav_id )->post_content;
	if ( trim( $existing ) === trim( $new_markup ) ) {
		line( "Navigation #$nav_id already portable — no change." );
	} else {
		wp_update_post( array(
			'ID'           => $nav_id,
			'post_content' => $new_markup,
		) );
		line( "Navigation #$nav_id rebuilt with reference-based links:" );
		line( $new_markup );
	}
} else {
	line( 'WARNING: no generated wp_navigation post found — skipping nav rebuild.' );
}

// ---------------------------------------------------------------------
// 2. Connect the two placeholder Contact CTA buttons (Construction
//    025-CLOSEOUT) to this demo's own generated Contact page.
// ---------------------------------------------------------------------

/**
 * Resolve the Contact page Setup generated for this demo. Prefers the
 * tracked option (same one Astrea\Core\Setup\navigation_links() reads);
 * falls back to a title match so this still works if that option is ever
 * missing.
 */
function astrea_co_find_contact_page_id(): int {
	$pages = get_option( 'astrea_core_generated_pages', array() );
	$id    = isset( $pages['contact'] ) ? (int) $pages['contact'] : 0;
	if ( $id > 0 && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	$found = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'title'          => 'お問い合わせ',
	) );
	return $found ? (int) $found[0]->ID : 0;
}

/**
 * Rebuilds one `core/button` block's saved markup with a new `url`,
 * changing only the anchor's `href` — every existing class, color and
 * label stays byte-identical, matching exactly what WordPress Core's own
 * `core/button` save() produces when a user picks a link via the Button
 * block's standard Link UI (verified against the Block Editor's own
 * validation: no new warning after this rewrite).
 *
 * @param array  $block Parsed `core/button` block.
 * @param string $url   Destination URL (already resolved from get_permalink()).
 * @return array
 */
function astrea_co_rebuild_button_href( array $block, string $url ): array {
	$block['attrs']['url'] = $url;

	$new_html = preg_replace( '/href="[^"]*"/', 'href="' . esc_attr( $url ) . '"', $block['innerHTML'], 1 );

	$block['innerHTML']    = $new_html;
	$block['innerContent'] = array( $new_html );
	return $block;
}

/**
 * Walks a parsed block tree and connects any `core/button` whose visible
 * label matches one of the target Contact CTA labels — skipping any
 * button that already carries a `metadata.bindings.url` (the phone CTA)
 * — to $contact_url. Recurses into innerBlocks.
 *
 * @param array    $blocks       Parsed blocks (by reference).
 * @param string   $contact_url  Current environment's Contact page permalink.
 * @param string[] $target_labels Exact label strings to match.
 * @param int      $count        Running count of buttons actually changed (by reference).
 */
function astrea_co_connect_contact_ctas( array &$blocks, string $contact_url, array $target_labels, int &$count ) {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['blockName'] ) && 'core/button' === $block['blockName'] ) {
			$has_phone_binding = isset( $block['attrs']['metadata']['bindings']['url'] );
			$label_match        = false;
			foreach ( $target_labels as $label ) {
				if ( isset( $block['innerHTML'] ) && false !== strpos( $block['innerHTML'], $label ) ) {
					$label_match = true;
					break;
				}
			}
			if ( $label_match && ! $has_phone_binding ) {
				$current_url = $block['attrs']['url'] ?? null;
				if ( $current_url !== $contact_url ) {
					$block = astrea_co_rebuild_button_href( $block, $contact_url );
					++$count;
				}
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			astrea_co_connect_contact_ctas( $block['innerBlocks'], $contact_url, $target_labels, $count );
		}
	}
}

$contact_id = astrea_co_find_contact_page_id();
if ( $contact_id > 0 ) {
	$contact_url   = get_permalink( $contact_id );
	$target_labels = array( 'お問い合わせはこちら', 'お問い合わせフォームへ' );

	// Any published page could in principle carry these CTA patterns; scan
	// all of them rather than assuming only the front page does.
	$candidate_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page'
			 AND ( post_content LIKE %s OR post_content LIKE %s )",
			'%' . $wpdb->esc_like( $target_labels[0] ) . '%',
			'%' . $wpdb->esc_like( $target_labels[1] ) . '%'
		)
	);

	$n_ctas = 0;
	foreach ( array_unique( array_map( 'intval', $candidate_ids ) ) as $pid ) {
		$post   = get_post( $pid );
		$blocks = parse_blocks( $post->post_content );
		$count  = 0;
		astrea_co_connect_contact_ctas( $blocks, $contact_url, $target_labels, $count );
		if ( $count > 0 ) {
			wp_update_post( array( 'ID' => $pid, 'post_content' => serialize_blocks( $blocks ) ) );
			$n_ctas += $count;
			line( "Connected $count Contact CTA button(s) on post #$pid -> $contact_url" );
		}
	}
	if ( 0 === $n_ctas ) {
		line( 'Contact CTA buttons already connected to the current Contact page — no change.' );
	}
} else {
	line( 'WARNING: no published Contact page found — Contact CTA buttons left as href="#".' );
}

// ---------------------------------------------------------------------
// 3. Serialization-aware internal-URL search-replace.
// ---------------------------------------------------------------------

/**
 * Collect the stale internal base URLs present anywhere in the DB that
 * are NOT the current one. Only loopback / private-LAN / bare-hostname
 * hosts are considered "internal"; real domains are left alone.
 */
function astrea_l1_stale_internal_bases( string $current_home ): array {
	global $wpdb;
	$found = array();

	$samples = array();
	$samples[] = (string) get_option( 'home' );
	$samples[] = (string) get_option( 'siteurl' );

	$hits = $wpdb->get_col(
		"SELECT DISTINCT post_content FROM {$wpdb->posts}
		 WHERE post_content REGEXP 'https?://(127\\.0\\.0\\.1|localhost|10\\.|172\\.(1[6-9]|2[0-9]|3[01])\\.|192\\.168\\.)'
		 LIMIT 200"
	);
	$samples = array_merge( $samples, (array) $hits );

	foreach ( $samples as $blob ) {
		if ( ! is_string( $blob ) || '' === $blob ) {
			continue;
		}
		if ( preg_match_all( '#https?://(?:127\.0\.0\.1|localhost|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(?:1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3})(?::\d+)?#i', $blob, $m ) ) {
			foreach ( $m[0] as $base ) {
				$base = untrailingslashit( $base );
				if ( strtolower( $base ) !== strtolower( $current_home ) ) {
					$found[ $base ] = $base;
				}
			}
		}
	}
	return array_values( $found );
}

/**
 * Recursively replace $from with $to inside a value, unserializing and
 * re-serializing as needed (the WP-CLI search-replace approach — never a
 * blind string replace on serialized blobs).
 */
function astrea_l1_deep_replace( $value, array $from_list, string $to ) {
	if ( is_string( $value ) ) {
		$unser = @unserialize( $value ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $unser || 'b:0;' === $value ) {
			return serialize( astrea_l1_deep_replace( $unser, $from_list, $to ) );
		}
		$out = $value;
		foreach ( $from_list as $from ) {
			$out = str_replace( $from, $to, $out );
		}
		return $out;
	}
	if ( is_array( $value ) ) {
		$new = array();
		foreach ( $value as $k => $v ) {
			$new[ $k ] = astrea_l1_deep_replace( $v, $from_list, $to );
		}
		return $new;
	}
	if ( is_object( $value ) ) {
		$new = clone $value;
		foreach ( get_object_vars( $new ) as $k => $v ) {
			$new->$k = astrea_l1_deep_replace( $v, $from_list, $to );
		}
		return $new;
	}
	return $value;
}

global $wpdb;
$stale_bases = astrea_l1_stale_internal_bases( $current_home );

if ( empty( $stale_bases ) ) {
	line( 'No stale internal base URLs found in the DB — nothing to search-replace.' );
} else {
	line( 'Stale internal base URLs to rewrite -> ' . $current_home . ' :' );
	foreach ( $stale_bases as $b ) {
		line( '  - ' . $b );
	}

	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE " . implode(
				' OR ',
				array_fill( 0, count( $stale_bases ), 'post_content LIKE %s OR post_excerpt LIKE %s' )
			),
			call_user_func_array(
				'array_merge',
				array_map(
					static function ( $b ) use ( $wpdb ) {
						$like = '%' . $wpdb->esc_like( $b ) . '%';
						return array( $like, $like );
					},
					$stale_bases
				)
			)
		)
	);
	$n_posts = 0;
	foreach ( array_unique( $post_ids ) as $pid ) {
		$post = get_post( $pid );
		if ( ! $post ) {
			continue;
		}
		$new_content = astrea_l1_deep_replace( $post->post_content, $stale_bases, $current_home );
		$new_excerpt = astrea_l1_deep_replace( $post->post_excerpt, $stale_bases, $current_home );
		if ( $new_content !== $post->post_content || $new_excerpt !== $post->post_excerpt ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $new_content, 'post_excerpt' => $new_excerpt ),
				array( 'ID' => $pid )
			);
			clean_post_cache( $pid );
			++$n_posts;
		}
	}
	line( "post_content/excerpt rewritten in $n_posts post(s)." );

	$meta_rows = $wpdb->get_results(
		"SELECT meta_id, meta_value FROM {$wpdb->postmeta}
		 WHERE meta_value REGEXP 'https?://(127\\.0\\.0\\.1|localhost|10\\.|172\\.(1[6-9]|2[0-9]|3[01])\\.|192\\.168\\.)'"
	);
	$n_meta = 0;
	foreach ( $meta_rows as $row ) {
		$new = astrea_l1_deep_replace( $row->meta_value, $stale_bases, $current_home );
		if ( $new !== $row->meta_value ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new ), array( 'meta_id' => $row->meta_id ) );
			++$n_meta;
		}
	}
	line( "postmeta rewritten in $n_meta row(s)." );

	// Options: only ones that legitimately hold internal URLs.
	$opt_names = $wpdb->get_col(
		"SELECT option_name FROM {$wpdb->options}
		 WHERE option_value REGEXP 'https?://(127\\.0\\.0\\.1|localhost|10\\.|172\\.(1[6-9]|2[0-9]|3[01])\\.|192\\.168\\.)'
		 AND option_name NOT IN ('cron')"
	);
	$n_opt = 0;
	foreach ( $opt_names as $oname ) {
		$val = get_option( $oname );
		$new = astrea_l1_deep_replace( $val, $stale_bases, $current_home );
		if ( $new !== $val ) {
			update_option( $oname, $new );
			++$n_opt;
			line( "  option '$oname' rewritten." );
		}
	}
	line( "options rewritten: $n_opt." );
}

// ---------------------------------------------------------------------
// 4. Self-heal the stored home/siteurl rows and flush rewrite rules.
//
// A local dev server (e.g. WordPress Playground `--site-url`) usually
// serves the site through a WP_HOME/WP_SITEURL constant or an
// `option_home` filter, so get_option()/home_url() report the *served*
// URL while the actual `wp_options` row can still hold a stale value from
// whatever environment first built (or cloned) the DB. Write the row
// directly so the DB is self-consistent if it is ever served without the
// override (WXR import into a real install already drops these rows, so
// this only helps the raw-DB-copy case — but it costs nothing).
// ---------------------------------------------------------------------

foreach ( array( 'home', 'siteurl' ) as $oname ) {
	$db_val = $wpdb->get_var(
		$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $oname )
	);
	if ( null !== $db_val && untrailingslashit( (string) $db_val ) !== $current_home ) {
		$wpdb->update( $wpdb->options, array( 'option_value' => $current_home ), array( 'option_name' => $oname ) );
		wp_cache_delete( $oname, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		line( "stored option '$oname' row: '$db_val' -> '$current_home'." );
	} else {
		line( "stored option '$oname' row already '$current_home' (or absent)." );
	}
}

flush_rewrite_rules( false );
line( 'Rewrite rules flushed. DONE.' );
