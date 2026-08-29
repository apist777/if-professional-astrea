#!/usr/bin/env bash
#
# ASTREA Theme / Core independence smoke test.
#
# Part 1 (A-D) automates Construction Order 001 §6 (Decision 021): Theme
# must run safely with Core absent, active, deactivated, and reactivated —
# with no PHP fatal, white screen, or non-200 response in any of the four
# states.
#
# Part 2 (E-I) automates Construction Order 002 §7: the Office Profile
# vertical slice (Core saves it, a Block Binding connects it, the Theme
# displays it) end to end, including its own Decision 021 behaviour
# (no stale leak while Core is inactive, data survives deactivation,
# display is restored on reactivation).
#
# Part 3 (J-S) automates Construction Order 003 §14: the Professional
# Profile vertical slice (CPT, deterministic ordering, featured image,
# Core public API, Theme Query Loop display) end to end.
#
# Part 4 (T-X) automates Construction Order 003A / Decision 023: a
# pre-existing (schema v1) Office Profile `representative_name` migrates
# to `legacy_representative_name` on a real request, the admin notice it
# drives appears/disappears correctly, and a Professional Profile's
# `is_representative` flag survives Core deactivate/reactivate.
#
# Part 5 (Y-AE) automates Construction Order 004: Service (CPT + Query
# Loop + individual URL), Price (non-viewable CPT + astrea/price-list
# Dynamic Block, deliberately no individual URL), and FAQ (CPT + Taxonomy
# archive + 関連Service/重要FAQ) end to end, including the Empty State
# guarantee (§8) and the same Core-inactive/deactivate/reactivate coverage
# as Parts 1-4.
#
# Part 6 (AF-AR) automates Construction Order 005: the Contact form's
# entire real HTTP lifecycle (astrea/contact-form Dynamic Block, CSRF,
# Honeypot, Rate Limit, validation-error value retention, admin read
# state, CSV Export + formula-injection neutralization, the notification-
# email confirmation Token flow including replay rejection), plus the same
# Core-inactive/deactivate/reactivate coverage as Parts 1-5.
#
# Part 7 (AS-BE) automates Construction Order 006 (SEO Foundation): meta
# description/OGP/Organization+Person+BreadcrumbList JSON-LD across
# Home/Service Archive/Service Single/Professional Archive/FAQ Archive,
# the Search Console verification meta (set/invalid-rejected), XSS/JSON-LD
# injection safety, real coexistence with an installed SEO Plugin (Yoast),
# and the same Core-inactive/deactivate/reactivate coverage as Parts 1-6.
#
#
# Part 12 (CN-CV) automates Construction Order 011 (Theme Display
# Completion / Security Hardening): the new single-astrea_professional.html
# Template and astrea/professional-field Dynamic Block (including the
# empty-meta "<p></p>" fix on the Professional Archive), the new
# astrea/office-hours and astrea/office-sns Dynamic Blocks, the new
# astrea/service-list Dynamic Block replacing home-services-teaser.php's
# old Query Loop (closing Decision 028's one remaining non-self-hiding HOME
# Teaser), the HOME H1 fix, the VOICE Archive heading-level fix, the
# astrea_price/astrea_result REST-exposure Security fix, the Contact Form
# submit button's Style Variation integration, and the same Core-inactive
# coverage as every earlier Part.
#
# Part 13 (CV-DA) automates Construction Order 013 (Release Quality
# Fixes): the Header office-name font-size reduction (Finding 1), the
# Navigation `ref` auto-connection into Header/Footer Template Parts —
# with idempotency and user-customization protection — and its permanent
# regression guard (Finding 2: a `wp_navigation` post existing must never
# again be mistaken for it actually being visible in Header/Footer, which
# is exactly the gap Construction 012 found), the Dynamic Block Editor
# script staying Editor-only (Finding 5), and the Site Title Setup
# Checklist item (Finding 3).
#
# Requires a running `wp-env` environment (see package.json `env:start`).

set -euo pipefail

SITE_URL="${WP_ENV_SITE_URL:-http://localhost:8888}"
BODY_FILE="$(mktemp)"
trap 'rm -f "$BODY_FILE"' EXIT

wp_cli() {
	npx wp-env run cli -- wp "$@"
}

check_no_fatal() {
	local label="$1"
	local path="${2:-/}"
	local status

	status=$(curl -s -o "$BODY_FILE" -w "%{http_code}" "$SITE_URL$path")

	if [ "$status" != "200" ]; then
		echo "FAIL [$label]: expected HTTP 200, got $status"
		exit 1
	fi

	if grep -qiE "fatal error|parse error|there has been a critical error" "$BODY_FILE"; then
		echo "FAIL [$label]: fatal/critical error detected in page output"
		exit 1
	fi

	echo "OK   [$label]: HTTP $status, no fatal error detected"
}

# Strips <script>...</script> blocks (notably JSON-LD, Construction Order
# 006) and .screen-reader-text spans (Construction Order 015D — WordPress
# core's own core/read-more block appends
# `<span class="screen-reader-text">: {title}</span>` to disambiguate
# repeated "詳しく見る" links for Accessible Name purposes) from the last
# BODY_FILE before printing it. Order-sensitive checks that scan for a set
# of names/titles must use this instead of the raw file — since both JSON-LD
# and this hidden accessible-name text legitimately repeat the same
# name/title a second time per item, and a plain grep across the raw body
# would double-count them without actually reflecting what a sighted visitor
# sees.
visible_content_only() {
	node -e '
		const fs = require("fs");
		const html = fs.readFileSync(process.argv[1], "utf8");
		process.stdout.write(
			html
				.replace(/<script[\s\S]*?<\/script>/g, "")
				.replace(/<span class="screen-reader-text">[\s\S]*?<\/span>/g, "")
		);
	' "$BODY_FILE"
}

# Fetches a page without requiring HTTP 200 or checking BODY_FILE afterwards
# (used where Core-inactive behaviour is a clean 404, not a broken response).
fetch_no_fatal_any_status() {
	local label="$1"
	local path="$2"
	local status

	status=$(curl -s -o "$BODY_FILE" -w "%{http_code}" "$SITE_URL$path")

	if grep -qiE "fatal error|parse error|there has been a critical error" "$BODY_FILE"; then
		echo "FAIL [$label]: fatal/critical error detected in page output (HTTP $status)"
		exit 1
	fi

	echo "OK   [$label]: HTTP $status, no fatal error detected"
}

echo "=== A. Theme only (ASTREA Core absent/inactive) ==="
wp_cli theme activate astrea
wp_cli plugin deactivate astrea-core --quiet || true
check_no_fatal "A: Theme only"

echo "=== B. Theme + Core (Core activated) ==="
wp_cli plugin activate astrea-core
check_no_fatal "B: Theme + Core"

echo "=== C. Core deactivated again ==="
wp_cli plugin deactivate astrea-core
check_no_fatal "C: Core deactivated"

echo "=== D. Core reactivated ==="
wp_cli plugin activate astrea-core
check_no_fatal "D: Core reactivated"

echo "All ASTREA Theme/Core independence checks passed."

OFFICE_NAME="スモークテスト事務所"

echo "=== E. Office Profile: sanitize() + save, then read back via the public API ==="
wp_cli eval '
$sanitized = \Astrea\Core\OfficeProfile\sanitize( array(
	"office_name" => "'"$OFFICE_NAME"'",
	"phone"       => "03-1234-5678",
) );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
'
SAVED_NAME=$(wp_cli eval 'echo \Astrea\Core\OfficeProfile\get_office_profile()["office_name"];')
if [ "$SAVED_NAME" != "$OFFICE_NAME" ]; then
	echo "FAIL [E: Office Profile save/read]: expected '$OFFICE_NAME', got '$SAVED_NAME'"
	exit 1
fi
echo "OK   [E: Office Profile save/read]"

echo "=== F. Theme displays the saved Office Profile value via Block Bindings ==="
check_no_fatal "F: Theme + Core, Office Profile set"
if ! grep -qF "$OFFICE_NAME" "$BODY_FILE"; then
	echo "FAIL [F: Theme display]: office_name not found in homepage output"
	exit 1
fi
echo "OK   [F: office_name rendered via Block Bindings]"

echo "=== G. Core deactivated: Theme still safe, no stale Office Profile leak ==="
wp_cli plugin deactivate astrea-core
check_no_fatal "G: Core deactivated (Office Profile)"
if grep -qF "$OFFICE_NAME" "$BODY_FILE"; then
	echo "FAIL [G]: stale Office Profile value leaked while Core is inactive"
	exit 1
fi
echo "OK   [G: no stale leak while Core inactive]"

echo "=== H. Office Profile data retained while Core is deactivated ==="
# `wp option get --format=json` escapes non-ASCII (\uXXXX), so check the
# phone number (ASCII) rather than the Japanese office_name here.
STORED=$(wp_cli option get astrea_core_office_profile --format=json)
if ! grep -qF "03-1234-5678" <<< "$STORED"; then
	echo "FAIL [H]: Office Profile option lost while Core was deactivated"
	exit 1
fi
echo "OK   [H: Office Profile data retained after deactivation]"

echo "=== I. Core reactivated: Office Profile display restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "I: Core reactivated (Office Profile)"
if ! grep -qF "$OFFICE_NAME" "$BODY_FILE"; then
	echo "FAIL [I]: Office Profile value not restored after reactivation"
	exit 1
fi
echo "OK   [I: display restored after reactivation]"

echo "All ASTREA Office Profile end-to-end checks passed."

# ---------------------------------------------------------------------------
# Part 3 (J-R): Construction Order 003 — Professional Profile end to end.
# CPT + native Query Loop + core/post-meta binding, multiple profiles,
# deterministic ordering, featured image (present/absent/broken), and the
# same Core-inactive/deactivate/reactivate behaviour as Parts 1-2.
# ---------------------------------------------------------------------------

echo "=== J. Professional Profile: create three professionals with a menu_order tie ==="
PROF_C=$(wp_cli post create --post_type=astrea_professional --post_title="Charlie Smoke" --post_status=publish --menu_order=1 --porcelain)
PROF_A=$(wp_cli post create --post_type=astrea_professional --post_title="Alpha Smoke" --post_status=publish --menu_order=0 --porcelain)
PROF_B=$(wp_cli post create --post_type=astrea_professional --post_title="Bravo Smoke" --post_status=publish --menu_order=0 --porcelain)
wp_cli post meta update "$PROF_A" astrea_professional_qualification "行政書士（スモークテスト）"
PROF_DRAFT=$(wp_cli post create --post_type=astrea_professional --post_title="Draft Smoke" --post_status=draft --porcelain)
echo "OK   [J: created Alpha($PROF_A) Bravo($PROF_B) Charlie($PROF_C) + draft($PROF_DRAFT)]"

echo "=== K. Archive shows all published professionals in deterministic order (menu_order, then title) ==="
check_no_fatal "K: Professional Profile archive" "/professionals/"
ORDER=$(visible_content_only | grep -oE "Alpha Smoke|Bravo Smoke|Charlie Smoke|Draft Smoke" | tr '\n' ',')
if [ "$ORDER" != "Alpha Smoke,Bravo Smoke,Charlie Smoke," ]; then
	echo "FAIL [K]: expected order 'Alpha Smoke,Bravo Smoke,Charlie Smoke,' (draft excluded), got '$ORDER'"
	exit 1
fi
echo "OK   [K: order is Alpha, Bravo (tie-break by title), Charlie; draft excluded]"

if ! grep -qF "行政書士（スモークテスト）" "$BODY_FILE"; then
	echo "FAIL [K]: qualification postmeta not rendered via core/post-meta Block Binding"
	exit 1
fi
echo "OK   [K: postmeta field rendered via core/post-meta binding]"

echo "=== L/M/N. Featured image: present, absent, and a broken attachment reference ==="
SCRATCH_PNG="$(mktemp --suffix=.png)"
# Minimal valid 1x1 PNG.
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=" | base64 -d > "$SCRATCH_PNG"
CLI_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'if-professional-astrea' | grep -- '-cli-1$' | grep -v tests)
docker cp "$SCRATCH_PNG" "$CLI_CONTAINER:/tmp/smoke-test-photo.png"
rm -f "$SCRATCH_PNG"
ATTACHMENT_ID=$(wp_cli media import /tmp/smoke-test-photo.png --post_id="$PROF_A" --title="Smoke Test Photo" --porcelain)
wp_cli post meta update "$PROF_A" _thumbnail_id "$ATTACHMENT_ID"
wp_cli post meta update "$PROF_B" _thumbnail_id 999999999

check_no_fatal "L/M/N: archive with photo present/absent/broken" "/professionals/"
IMG_COUNT=$(grep -oc '<img[^>]*wp-post-image' "$BODY_FILE" || true)
if [ "$IMG_COUNT" != "1" ]; then
	echo "FAIL [L/M/N]: expected exactly 1 rendered photo (Alpha only), found $IMG_COUNT"
	exit 1
fi
echo "OK   [L: photo present renders], [M: photo absent renders no broken img], [N: broken attachment reference does not fatal or render]"

echo "=== O/P. Core public API: single + list lookups ==="
API_NAME=$(wp_cli eval "echo \Astrea\Core\ProfessionalProfile\get_profile($PROF_A)['name'];")
if [ "$API_NAME" != "Alpha Smoke" ]; then
	echo "FAIL [O]: get_profile() expected 'Alpha Smoke', got '$API_NAME'"
	exit 1
fi
API_COUNT=$(wp_cli eval "echo count(\Astrea\Core\ProfessionalProfile\get_profiles());")
if [ "$API_COUNT" != "3" ]; then
	echo "FAIL [P]: get_profiles() expected 3 published profiles, got $API_COUNT"
	exit 1
fi
echo "OK   [O: get_profile() single lookup], [P: get_profiles() excludes the draft]"

echo "=== Q. Core deactivated: no Fatal, no stale Professional Profile leak ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "Q: homepage while Core inactive" "/"
fetch_no_fatal_any_status "Q: professionals archive while Core inactive" "/professionals/"
if grep -qE "Alpha Smoke|Bravo Smoke|Charlie Smoke" "$BODY_FILE"; then
	echo "FAIL [Q]: stale Professional Profile data leaked while Core is inactive"
	exit 1
fi
echo "OK   [Q: no stale Professional Profile leak while Core is inactive]"

echo "=== R. Professional Profile data retained while Core is deactivated ==="
DB_COUNT=$(wp_cli db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='astrea_professional'" --skip-column-names)
if [ "$DB_COUNT" -lt 4 ]; then
	echo "FAIL [R]: expected at least 4 astrea_professional rows (3 published + 1 draft) to survive deactivation, found $DB_COUNT"
	exit 1
fi
echo "OK   [R: Professional Profile data retained after deactivation ($DB_COUNT rows)]"

echo "=== S. Core reactivated: Professional Profile display restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "S: Professional Profile archive after reactivation" "/professionals/"
ORDER_AFTER=$(visible_content_only | grep -oE "Alpha Smoke|Bravo Smoke|Charlie Smoke" | tr '\n' ',')
if [ "$ORDER_AFTER" != "Alpha Smoke,Bravo Smoke,Charlie Smoke," ]; then
	echo "FAIL [S]: expected restored order 'Alpha Smoke,Bravo Smoke,Charlie Smoke,', got '$ORDER_AFTER'"
	exit 1
fi
echo "OK   [S: display and order restored after reactivation]"

echo "=== Cleanup: remove smoke-test Professional Profile fixtures ==="
wp_cli post delete "$PROF_A" "$PROF_B" "$PROF_C" "$PROF_DRAFT" "$ATTACHMENT_ID" --force > /dev/null

echo "All ASTREA Professional Profile end-to-end checks passed."

# ---------------------------------------------------------------------------
# Part 4 (T-X): Construction Order 003A / Decision 023 — representative
# migration and admin notice, end to end on a real site.
# ---------------------------------------------------------------------------

LEGACY_NAME="スモーク旧代表"

echo "=== T. Seed a v1-style Office Profile option, migrate it via a real request ==="
wp_cli eval '
update_option( "astrea_core_office_profile", array(
	"schema_version"      => 1,
	"office_name"         => "スモーク旧事務所",
	"representative_name" => "'"$LEGACY_NAME"'",
	"address"             => "",
	"phone"               => "",
) );
'
check_no_fatal "T: trigger v1 -> v2 migration via a real request"
MIGRATED=$(wp_cli option get astrea_core_office_profile --format=json)
if ! grep -qF '"schema_version":2' <<< "$MIGRATED"; then
	echo "FAIL [T]: migration did not run (schema_version is not 2)"
	exit 1
fi
HAS_OLD_KEY=$(echo "$MIGRATED" | node -e "let d=JSON.parse(require('fs').readFileSync(0,'utf8')); console.log(Object.prototype.hasOwnProperty.call(d,'representative_name') ? 'yes' : 'no');")
if [ "$HAS_OLD_KEY" != "no" ]; then
	echo "FAIL [T]: old representative_name key still present after migration"
	exit 1
fi
echo "OK   [T: v1 -> v2 migration ran on a real request, old key removed]"

echo "=== U. Legacy representative notice appears; deprecated field removed from the form ==="
COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
LOGIN_STATUS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null -w "%{http_code}")
if [ "$LOGIN_STATUS" != "302" ]; then
	echo "FAIL [U]: admin login did not redirect as expected (HTTP $LOGIN_STATUS) — cannot verify the admin notice"
	exit 1
fi
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if grep -q 'id="loginform"' <<< "$ADMIN_HTML"; then
	echo "FAIL [U]: admin session was not recognized (bounced back to the login form)"
	exit 1
fi
if ! grep -qF "$LEGACY_NAME" <<< "$ADMIN_HTML"; then
	echo "FAIL [U]: legacy representative notice did not appear on the ASTREA admin page"
	echo "--- diagnostics ---"
	echo "ADMIN_HTML length: $(echo "$ADMIN_HTML" | wc -c)"
	echo "Contains 'notice-warning': $(echo "$ADMIN_HTML" | grep -c 'notice-warning' || true)"
	echo "Contains page heading '事務所情報': $(echo "$ADMIN_HTML" | grep -c '事務所情報' || true)"
	echo "Raw bytes around 'notice-warning' (hex + text):"
	echo "$ADMIN_HTML" | grep -o '.\{0,40\}notice-warning.\{0,400\}' | head -1 | tee /tmp/notice_snippet.txt
	echo "$ADMIN_HTML" | grep -o '.\{0,40\}notice-warning.\{0,400\}' | head -1 | xxd | head -20
	echo "grep -F re-check against the snippet directly: $(grep -cF "$LEGACY_NAME" /tmp/notice_snippet.txt || true)"
	echo "grep -c (plain) against full ADMIN_HTML: $(echo "$ADMIN_HTML" | grep -c "$LEGACY_NAME" || true)"
	echo "LEGACY_NAME hex: $(printf '%s' "$LEGACY_NAME" | xxd)"
	echo "locale: $(locale 2>&1 | tr '\n' ' ')"
	echo "Ground truth via wp-cli:"
	wp_cli eval 'echo "legacy_representative_name=" . \Astrea\Core\OfficeProfile\get_office_profile()[ \Astrea\Core\OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY ] . "\n"; echo "representatives_count=" . count( \Astrea\Core\ProfessionalProfile\get_representatives() ) . "\n"; echo "current_screen_available=" . ( function_exists( "get_current_screen" ) ? "yes" : "no" ) . "\n";'
	echo "-------------------"
	exit 1
fi
if grep -qF 'name="astrea_core_office_profile[representative_name]"' <<< "$ADMIN_HTML"; then
	echo "FAIL [U]: deprecated representative_name field is still present in the Office Profile form"
	exit 1
fi
echo "OK   [U: notice shown, deprecated field removed from the admin form]"

echo "=== V. Flagging a Professional Profile as representative hides the notice ==="
REP_PROF=$(wp_cli post create --post_type=astrea_professional --post_title="Rep Smoke" --post_status=publish --porcelain)
wp_cli post meta update "$REP_PROF" astrea_professional_is_representative 1
ADMIN_HTML_AFTER=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if grep -qF "$LEGACY_NAME" <<< "$ADMIN_HTML_AFTER"; then
	echo "FAIL [V]: notice is still shown after a Professional Profile was flagged as representative"
	exit 1
fi
echo "OK   [V: notice disappears once a representative is flagged]"

echo "=== W. Public API reflects the representative ==="
REP_COUNT=$(wp_cli eval 'echo count( \Astrea\Core\ProfessionalProfile\get_representatives() );')
if [ "$REP_COUNT" != "1" ]; then
	echo "FAIL [W]: get_representatives() expected 1, got $REP_COUNT"
	exit 1
fi
echo "OK   [W: get_representatives() reflects the flagged Professional Profile]"

echo "=== X. Core deactivate/reactivate preserves the representative flag ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "X: homepage while Core inactive (representative)" "/"
DB_META=$(wp_cli db query "SELECT meta_value FROM wp_postmeta WHERE post_id=$REP_PROF AND meta_key='astrea_professional_is_representative'" --skip-column-names)
if [ "$DB_META" != "1" ]; then
	echo "FAIL [X]: representative flag lost while Core was deactivated"
	exit 1
fi
wp_cli plugin activate astrea-core
REP_COUNT_AFTER=$(wp_cli eval 'echo count( \Astrea\Core\ProfessionalProfile\get_representatives() );')
if [ "$REP_COUNT_AFTER" != "1" ]; then
	echo "FAIL [X]: representative not restored after reactivation, got $REP_COUNT_AFTER"
	exit 1
fi
echo "OK   [X: representative flag survives deactivate/reactivate]"

echo "=== Cleanup: remove Decision 023 smoke-test fixtures ==="
wp_cli post delete "$REP_PROF" --force > /dev/null
wp_cli eval 'delete_option( "astrea_core_office_profile" );' > /dev/null
rm -f "$COOKIE_JAR"

echo "All ASTREA representative migration/notice checks passed."

# ---------------------------------------------------------------------------
# Part 5 (Y-AF): Construction Order 004 — Service / Price / FAQ end to end.
# ---------------------------------------------------------------------------

echo "=== Y. Empty State: 0 Service/Price/FAQ renders cleanly (no fatal, no broken markup) ==="
check_no_fatal "Y: services archive, 0 items" "/services/"
if grep -qiE "wp-block-post-template" "$BODY_FILE" && grep -qE '<article' "$BODY_FILE"; then
	echo "FAIL [Y]: services archive rendered an article with 0 Service posts"
	exit 1
fi
check_no_fatal "Y: faq archive, 0 items" "/faq/"
PRICE_EMPTY_PAGE=$(wp_cli post create --post_type=page --post_status=publish --post_title="Smoke Price Page" --post_content='<!-- wp:astrea/price-list /-->' --porcelain)
PRICE_PAGE_PATH="/$(wp_cli post get "$PRICE_EMPTY_PAGE" --field=post_name)/"
check_no_fatal "Y: price-list Dynamic Block, 0 items" "$PRICE_PAGE_PATH"
# Match the actual rendered container tag, not a bare substring: Construction
# 015C added theme.json global CSS that legitimately mentions the
# "wp-block-astrea-price-list" class name in a shared selector (for margin
# rules applied when the block DOES render), which is present in every
# page's <style> block regardless of whether this page's block rendered
# anything — a plain `grep -qF` for the class name alone would false-positive
# on that unrelated CSS, not on real rendered content.
if grep -qE '<div class="wp-block-astrea-price-list"' "$BODY_FILE"; then
	echo "FAIL [Y]: astrea/price-list rendered a container with 0 Price posts (must render nothing per §8)"
	exit 1
fi
echo "OK   [Y: Service archive, FAQ archive, and Price Dynamic Block all render nothing/no-fatal with 0 items]"

echo "=== Z. Service: create three with a menu_order tie + a draft; verify archive order and individual URL ==="
SVC_C=$(wp_cli post create --post_type=astrea_service --post_title="Charlie Service" --post_status=publish --menu_order=1 --post_content="Charlie description" --porcelain)
SVC_A=$(wp_cli post create --post_type=astrea_service --post_title="Alpha Service" --post_status=publish --menu_order=0 --post_content="Alpha description" --porcelain)
SVC_B=$(wp_cli post create --post_type=astrea_service --post_title="Bravo Service" --post_status=publish --menu_order=0 --porcelain)
SVC_DRAFT=$(wp_cli post create --post_type=astrea_service --post_title="Draft Service" --post_status=draft --porcelain)

check_no_fatal "Z: Service archive" "/services/"
ORDER=$(visible_content_only | grep -oE "Alpha Service|Bravo Service|Charlie Service|Draft Service" | tr '\n' ',')
if [ "$ORDER" != "Alpha Service,Bravo Service,Charlie Service," ]; then
	echo "FAIL [Z]: expected order 'Alpha Service,Bravo Service,Charlie Service,' (draft excluded), got '$ORDER'"
	exit 1
fi
SVC_PERMALINK=$(wp_cli post get "$SVC_A" --field=url)
check_no_fatal "Z: Service single page" "${SVC_PERMALINK#$SITE_URL}"
if ! grep -qF "Alpha description" "$BODY_FILE"; then
	echo "FAIL [Z]: Service single page did not render post_content"
	exit 1
fi
echo "OK   [Z: Service archive order correct, draft excluded, individual URL (§7 個別Service) works]"

echo "=== AA. Price: deliberately not viewable — Dynamic Block renders, no individual URL exists ==="
PRICE_B=$(wp_cli post create --post_type=astrea_price --post_title="Bravo Price" --post_status=publish --menu_order=0 --porcelain)
PRICE_A=$(wp_cli post create --post_type=astrea_price --post_title="Alpha Price" --post_status=publish --menu_order=0 --porcelain)
wp_cli post meta update "$PRICE_A" astrea_price_amount "月額5,000円〜（スモーク）"
wp_cli post meta update "$PRICE_A" astrea_price_notes "実費は別途（スモーク）"

check_no_fatal "AA: price-list Dynamic Block" "$PRICE_PAGE_PATH"
if ! grep -qF "月額5,000円〜（スモーク）" "$BODY_FILE"; then
	echo "FAIL [AA]: Price amount not rendered via astrea/price-list Dynamic Block"
	exit 1
fi
ORDER=$(visible_content_only | grep -oE "Alpha Price|Bravo Price" | tr '\n' ',')
if [ "$ORDER" != "Alpha Price,Bravo Price," ]; then
	echo "FAIL [AA]: expected Price order 'Alpha Price,Bravo Price,' (menu_order tie -> title), got '$ORDER'"
	exit 1
fi
fetch_no_fatal_any_status "AA: direct Price permalink must not be a normal page" "/?p=$PRICE_A"
if [ "$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/?p=$PRICE_A")" = "200" ]; then
	echo "FAIL [AA]: Price post is reachable as a normal 200 page — §10 gives no basis for an individual Price URL"
	exit 1
fi
echo "OK   [AA: Price data + order rendered via Dynamic Block; no individual Price URL exists]"

echo "=== AB. FAQ: category taxonomy, 関連Service, 重要FAQ ==="
SVC_FOR_FAQ=$(wp_cli post create --post_type=astrea_service --post_title="FAQ関連Service" --post_status=publish --porcelain)
FAQ_1=$(wp_cli post create --post_type=astrea_faq --post_title="スモーク質問1" --post_content="スモーク回答1" --post_status=publish --menu_order=0 --porcelain)
FAQ_2=$(wp_cli post create --post_type=astrea_faq --post_title="スモーク質問2" --post_content="スモーク回答2" --post_status=publish --menu_order=1 --porcelain)
wp_cli post term add "$FAQ_1" astrea_faq_category "スモークカテゴリ"
wp_cli post meta update "$FAQ_1" astrea_faq_is_important 1
wp_cli post meta update "$FAQ_1" astrea_faq_related_services "[$SVC_FOR_FAQ]" --format=json

check_no_fatal "AB: FAQ archive" "/faq/"
if ! grep -qF "スモーク質問1" "$BODY_FILE" || ! grep -qF "スモーク回答1" "$BODY_FILE"; then
	echo "FAIL [AB]: FAQ question/answer not rendered on the FAQ archive"
	exit 1
fi
TERM_ID=$(wp_cli term list astrea_faq_category --field=term_id)
TERM_SLUG=$(wp_cli term list astrea_faq_category --field=slug)
check_no_fatal "AB: FAQ category taxonomy archive" "/faq-category/$TERM_SLUG/"
if ! grep -qF "スモーク質問1" "$BODY_FILE" || grep -qF "スモーク質問2" "$BODY_FILE"; then
	echo "FAIL [AB]: FAQ category archive did not correctly filter by term"
	exit 1
fi
IMPORTANT_COUNT=$(wp_cli eval "echo count(\Astrea\Core\Faq\get_important_faqs());")
if [ "$IMPORTANT_COUNT" != "1" ]; then
	echo "FAIL [AB]: get_important_faqs() expected 1, got $IMPORTANT_COUNT"
	exit 1
fi
RELATED_COUNT=$(wp_cli eval "echo count(\Astrea\Core\Faq\get_faqs_for_service($SVC_FOR_FAQ));")
if [ "$RELATED_COUNT" != "1" ]; then
	echo "FAIL [AB]: get_faqs_for_service() expected 1, got $RELATED_COUNT"
	exit 1
fi
echo "OK   [AB: FAQ category archive filters correctly; 重要FAQ and 関連Service public API both correct]"

echo "=== AC. Core deactivated: no Fatal, no stale Service/Price/FAQ leak ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "AC: services archive while Core inactive" "/services/"
if grep -qE "Alpha Service|Bravo Service|Charlie Service" "$BODY_FILE"; then
	echo "FAIL [AC]: stale Service data leaked while Core is inactive"
	exit 1
fi
fetch_no_fatal_any_status "AC: faq archive while Core inactive" "/faq/"
if grep -qE "スモーク質問1|スモーク質問2" "$BODY_FILE"; then
	echo "FAIL [AC]: stale FAQ data leaked while Core is inactive"
	exit 1
fi
fetch_no_fatal_any_status "AC: price page while Core inactive" "$PRICE_PAGE_PATH"
if grep -qF "月額5,000円〜（スモーク）" "$BODY_FILE"; then
	echo "FAIL [AC]: stale Price data leaked while Core is inactive"
	exit 1
fi
echo "OK   [AC: no stale Service/Price/FAQ leak while Core is inactive]"

echo "=== AD. Service/Price/FAQ data retained while Core is deactivated ==="
SVC_DB_COUNT=$(wp_cli db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='astrea_service'" --skip-column-names)
PRICE_DB_COUNT=$(wp_cli db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='astrea_price'" --skip-column-names)
FAQ_DB_COUNT=$(wp_cli db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='astrea_faq'" --skip-column-names)
if [ "$SVC_DB_COUNT" -lt 4 ] || [ "$PRICE_DB_COUNT" -lt 2 ] || [ "$FAQ_DB_COUNT" -lt 2 ]; then
	echo "FAIL [AD]: expected Service>=4, Price>=2, FAQ>=2 rows to survive deactivation; got Service=$SVC_DB_COUNT Price=$PRICE_DB_COUNT FAQ=$FAQ_DB_COUNT"
	exit 1
fi
echo "OK   [AD: Service/Price/FAQ data retained after deactivation]"

echo "=== AE. Core reactivated: Service/Price/FAQ display restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "AE: Service archive after reactivation" "/services/"
if ! grep -qF "Alpha Service" "$BODY_FILE"; then
	echo "FAIL [AE]: Service display not restored after reactivation"
	exit 1
fi
check_no_fatal "AE: Price Dynamic Block after reactivation" "$PRICE_PAGE_PATH"
if ! grep -qF "月額5,000円〜（スモーク）" "$BODY_FILE"; then
	echo "FAIL [AE]: Price display not restored after reactivation"
	exit 1
fi
check_no_fatal "AE: FAQ archive after reactivation" "/faq/"
if ! grep -qF "スモーク質問1" "$BODY_FILE"; then
	echo "FAIL [AE]: FAQ display not restored after reactivation"
	exit 1
fi
echo "OK   [AE: Service/Price/FAQ display restored after reactivation]"

echo "=== Cleanup: remove Construction Order 004 smoke-test fixtures ==="
wp_cli post delete "$SVC_A" "$SVC_B" "$SVC_C" "$SVC_DRAFT" "$SVC_FOR_FAQ" "$PRICE_A" "$PRICE_B" "$FAQ_1" "$FAQ_2" "$PRICE_EMPTY_PAGE" --force > /dev/null
wp_cli term delete astrea_faq_category "$TERM_ID" > /dev/null 2>&1 || true

echo "All ASTREA Service/Price/FAQ end-to-end checks passed."

# ---------------------------------------------------------------------------
# Part 6 (AF-AS): Construction Order 005 — Contact end to end.
# ---------------------------------------------------------------------------

# Every HTTP request in this script originates from the same apparent
# source IP (the host, as seen through wp-env's Docker port mapping), so
# any rate-limit state left over from manual testing/earlier runs must be
# cleared before Part 6's rate-limit-sensitive checks (AG/AJ/AK) run.
wp_cli db query "DELETE FROM wp_options WHERE option_name LIKE '\_transient\_astrea\_contact\_rl\_%' OR option_name LIKE '\_transient\_timeout\_astrea\_contact\_rl\_%'"

echo "=== AF. Contact form page renders with nonce + required fields, no fatal ==="
CONTACT_PAGE_ID=$(wp_cli post create --post_type=page --post_status=publish --post_title="Smoke Contact Page" --post_content='<!-- wp:astrea/contact-form /-->' --porcelain)
CONTACT_PAGE_PATH="/$(wp_cli post get "$CONTACT_PAGE_ID" --field=post_name)/"
check_no_fatal "AF: contact form page" "$CONTACT_PAGE_PATH"
if ! grep -qF 'name="astrea_contact_nonce"' "$BODY_FILE" || ! grep -qF 'name="message"' "$BODY_FILE"; then
	echo "FAIL [AF]: contact form did not render expected nonce/message field"
	exit 1
fi
NONCE=$(sed -n 's/.*name="astrea_contact_nonce" value="\([a-f0-9]*\)".*/\1/p' "$BODY_FILE" | head -1)
echo "OK   [AF: contact form renders with nonce ($NONCE) and required fields]"

echo "=== AG. Real submission: saved + notified + redirected to success ==="
SUBMIT_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -D "$BODY_FILE.headers" -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_submit_inquiry" \
	--data-urlencode "astrea_contact_redirect=$SITE_URL$CONTACT_PAGE_PATH" \
	--data-urlencode "astrea_contact_nonce=$NONCE" \
	--data-urlencode "name=スモーク太郎" \
	--data-urlencode "email=smoke@example.com" \
	--data-urlencode "subject=スモーク件名" \
	--data-urlencode "message=スモークテストの問い合わせです。" \
	--data-urlencode "privacy_consent=1")
if [ "$SUBMIT_STATUS" != "302" ] || ! grep -qF "astrea_contact_success=1" "$BODY_FILE.headers"; then
	echo "FAIL [AG]: expected a 302 redirect to the success state, got HTTP $SUBMIT_STATUS"
	cat "$BODY_FILE.headers"
	exit 1
fi
INQUIRY_ID=$(wp_cli post list --post_type=astrea_inquiry --orderby=ID --order=DESC --posts_per_page=1 --field=ID)
STORED_NAME=$(wp_cli post meta get "$INQUIRY_ID" astrea_inquiry_name)
if [ "$STORED_NAME" != "スモーク太郎" ]; then
	echo "FAIL [AG]: inquiry was not saved with the submitted name (got '$STORED_NAME')"
	exit 1
fi
echo "OK   [AG: real HTTP submission saved (post $INQUIRY_ID) and redirected to success]"

echo "=== AH. Validation error: values retained, error shown, nothing saved ==="
BEFORE_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
ERROR_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -D "$BODY_FILE.headers" -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_submit_inquiry" \
	--data-urlencode "astrea_contact_redirect=$SITE_URL$CONTACT_PAGE_PATH" \
	--data-urlencode "astrea_contact_nonce=$NONCE" \
	--data-urlencode "name=スモーク花子" \
	--data-urlencode "email=" \
	--data-urlencode "message=件名なしテスト" \
	--data-urlencode "privacy_consent=1")
AFTER_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
if [ "$ERROR_STATUS" != "302" ] || ! grep -qF "astrea_contact_error=1" "$BODY_FILE.headers"; then
	echo "FAIL [AH]: expected a 302 redirect to the error state, got HTTP $ERROR_STATUS"
	exit 1
fi
if [ "$AFTER_COUNT" != "$BEFORE_COUNT" ]; then
	echo "FAIL [AH]: an inquiry was saved despite a missing required field (email)"
	exit 1
fi
ERROR_REDIRECT=$(sed -n 's/^Location: \([^\r]*\)\r*$/\1/p' "$BODY_FILE.headers" | head -1)
check_no_fatal "AH: form re-render with retained values" "${ERROR_REDIRECT#$SITE_URL}"
if ! grep -qF "スモーク花子" "$BODY_FILE"; then
	echo "FAIL [AH]: submitted name was not retained in the re-rendered form"
	exit 1
fi
echo "OK   [AH: validation error retains values, shows an error, saves nothing]"

echo "=== AI. CSRF: invalid nonce is rejected, nothing saved ==="
BEFORE_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
curl -s -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_submit_inquiry" \
	--data-urlencode "astrea_contact_redirect=$SITE_URL$CONTACT_PAGE_PATH" \
	--data-urlencode "astrea_contact_nonce=not-a-real-nonce" \
	--data-urlencode "name=不正リクエスト" \
	--data-urlencode "email=csrf@example.com" \
	--data-urlencode "message=CSRFテスト"
AFTER_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
if [ "$AFTER_COUNT" != "$BEFORE_COUNT" ]; then
	echo "FAIL [AI]: an inquiry was saved despite an invalid nonce"
	exit 1
fi
echo "OK   [AI: invalid nonce rejected, nothing saved]"

echo "=== AJ. Honeypot: filled hidden field is silently dropped (success shown, nothing saved) ==="
BEFORE_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
HONEYPOT_STATUS=$(curl -s -o /dev/null -w '%{http_code}' -D "$BODY_FILE.headers" -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_submit_inquiry" \
	--data-urlencode "astrea_contact_redirect=$SITE_URL$CONTACT_PAGE_PATH" \
	--data-urlencode "astrea_contact_nonce=$NONCE" \
	--data-urlencode "astrea_contact_website=http://spam.example.com" \
	--data-urlencode "name=Bot" \
	--data-urlencode "email=bot@example.com" \
	--data-urlencode "message=Bot message" \
	--data-urlencode "privacy_consent=1")
AFTER_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
if [ "$HONEYPOT_STATUS" != "302" ] || ! grep -qF "astrea_contact_success=1" "$BODY_FILE.headers"; then
	echo "FAIL [AJ]: honeypot submission was not shown a success redirect"
	exit 1
fi
if [ "$AFTER_COUNT" != "$BEFORE_COUNT" ]; then
	echo "FAIL [AJ]: a honeypot-triggered submission was saved as a real inquiry"
	exit 1
fi
echo "OK   [AJ: honeypot hit shown success but silently dropped]"

echo "=== AK. Rate limit: rapid resubmission from the same client is rejected ==="
# The prior [AG] submission already primed the minimum-interval throttle for
# this client, so an immediate resubmission now must be rejected.
BEFORE_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
curl -s -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_submit_inquiry" \
	--data-urlencode "astrea_contact_redirect=$SITE_URL$CONTACT_PAGE_PATH" \
	--data-urlencode "astrea_contact_nonce=$NONCE" \
	--data-urlencode "name=連続送信" \
	--data-urlencode "email=rl@example.com" \
	--data-urlencode "message=Rate limit test" \
	--data-urlencode "privacy_consent=1"
AFTER_COUNT=$(wp_cli post list --post_type=astrea_inquiry --format=count)
if [ "$AFTER_COUNT" != "$BEFORE_COUNT" ]; then
	echo "FAIL [AK]: a rapid resubmission from the same client was not rate-limited"
	exit 1
fi
echo "OK   [AK: rapid resubmission rate-limited]"

echo "=== AL. Admin: unread count, mark-as-read toggle ==="
COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
LOGIN_STATUS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null -w "%{http_code}")
if [ "$LOGIN_STATUS" != "302" ]; then
	echo "FAIL [AL]: admin login did not redirect as expected (HTTP $LOGIN_STATUS)"
	exit 1
fi
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-contact")
if grep -q 'id="loginform"' <<< "$ADMIN_HTML"; then
	echo "FAIL [AL]: admin session was not recognized"
	exit 1
fi
if ! grep -qF "スモーク太郎" <<< "$ADMIN_HTML"; then
	echo "FAIL [AL]: the saved inquiry from [AG] did not appear on the admin screen"
	exit 1
fi
MARK_READ_NONCE=$(sed -n 's/.*name="astrea_contact_mark_read_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_contact_mark_read" \
	--data-urlencode "post_id=$INQUIRY_ID" \
	--data-urlencode "is_read=1" \
	--data-urlencode "astrea_contact_mark_read_nonce=$MARK_READ_NONCE"
IS_READ_AFTER=$(wp_cli post meta get "$INQUIRY_ID" astrea_inquiry_is_read)
if [ "$IS_READ_AFTER" != "1" ]; then
	echo "FAIL [AL]: marking the inquiry as read did not persist"
	exit 1
fi
echo "OK   [AL: admin screen shows the inquiry; mark-as-read toggle works]"

echo "=== AM. CSV Export: correct headers, content, and formula-injection neutralization ==="
CSV_INJECTION_ID=$(wp_cli post create --post_type=astrea_inquiry --post_status=private --post_title='=SUM(A1:A9)' --porcelain)
wp_cli post meta update "$CSV_INJECTION_ID" astrea_inquiry_name '=cmd|/c calc'
wp_cli post meta update "$CSV_INJECTION_ID" astrea_inquiry_email 'csv-injection@example.com'
EXPORT_NONCE=$(grep -oE 'action=astrea_export_inquiries&#0?38;_wpnonce=[a-f0-9]+' <<< "$ADMIN_HTML" | grep -oE '[a-f0-9]+$')
EXPORT_HEADERS=$(curl -s -b "$COOKIE_JAR" -D - -o "$BODY_FILE" "$SITE_URL/wp-admin/admin-post.php?action=astrea_export_inquiries&_wpnonce=$EXPORT_NONCE")
if ! grep -qiF "text/csv" <<< "$EXPORT_HEADERS"; then
	echo "FAIL [AM]: Export response was not served as text/csv"
	exit 1
fi
if ! grep -qF "スモーク太郎" "$BODY_FILE"; then
	echo "FAIL [AM]: CSV export did not include the saved inquiry"
	exit 1
fi
if grep -qE $'^=cmd|,=cmd' "$BODY_FILE"; then
	echo "FAIL [AM]: CSV formula injection was not neutralized"
	exit 1
fi
if ! grep -qF "'=cmd" "$BODY_FILE"; then
	echo "FAIL [AM]: expected the dangerous cell to be prefixed with a neutralizing quote"
	exit 1
fi
wp_cli post delete "$CSV_INJECTION_ID" --force > /dev/null
echo "OK   [AM: CSV export headers/content correct, formula injection neutralized]"

echo "=== AN. Notification email confirmation: request, confirm via real HTTP, Replay rejected ==="
wp_cli eval '
add_filter( "wp_mail", function ( $args ) {
	if ( preg_match( "/token=([^&\\s]+)/", $args["message"], $m ) ) {
		set_transient( "smoke_captured_confirm_token", $m[1], 300 );
	}
	return $args;
} );
\Astrea\Core\Inquiry\request_email_confirmation( "smoke-confirm@example.com" );
'
CONFIRM_TOKEN=$(wp_cli eval 'echo get_transient( "smoke_captured_confirm_token" );')
if [ -z "$CONFIRM_TOKEN" ]; then
	echo "FAIL [AN]: could not capture the confirmation token"
	exit 1
fi
fetch_no_fatal_any_status "AN: confirmation link (first use)" "/wp-admin/admin-post.php?action=astrea_confirm_contact_email&token=$CONFIRM_TOKEN"
CONFIRMED_EMAIL=$(wp_cli eval 'echo \Astrea\Core\Inquiry\get_contact_settings()["notification_email"];')
if [ "$CONFIRMED_EMAIL" != "smoke-confirm@example.com" ]; then
	echo "FAIL [AN]: notification_email was not confirmed via the real HTTP link (got '$CONFIRMED_EMAIL')"
	exit 1
fi
echo "OK   [AN: real HTTP confirmation link confirmed the notification email]"

echo "=== AO. Token Replay: reusing the same confirmation link fails ==="
wp_cli eval '\Astrea\Core\Inquiry\reschedule_digest_cron();' > /dev/null # no-op call to keep wp_cli warm; irrelevant to the assertion below
wp_cli option update astrea_core_contact_settings '{"notification_email":"placeholder@example.com"}' --format=json
fetch_no_fatal_any_status "AO: confirmation link (replay attempt)" "/wp-admin/admin-post.php?action=astrea_confirm_contact_email&token=$CONFIRM_TOKEN"
STILL_PLACEHOLDER=$(wp_cli eval 'echo \Astrea\Core\Inquiry\get_contact_settings()["notification_email"];')
if [ "$STILL_PLACEHOLDER" != "placeholder@example.com" ]; then
	echo "FAIL [AO]: a replayed confirmation token was accepted a second time"
	exit 1
fi
echo "OK   [AO: replayed confirmation token rejected]"
wp_cli eval 'delete_option( "astrea_core_contact_settings" );'
rm -f "$COOKIE_JAR"

echo "=== AP. Core deactivated: no Fatal, Contact data retained, Cron cleared ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "AP: contact page while Core inactive" "$CONTACT_PAGE_PATH"
DB_INQUIRY_COUNT=$(wp_cli db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='astrea_inquiry'" --skip-column-names)
if [ "$DB_INQUIRY_COUNT" -lt 1 ]; then
	echo "FAIL [AP]: inquiry data was lost while Core was deactivated"
	exit 1
fi
CRON_AFTER_DEACTIVATE=$(wp_cli cron event list --fields=hook --format=csv 2>/dev/null | grep -c astrea_core_contact || true)
if [ "$CRON_AFTER_DEACTIVATE" != "0" ]; then
	echo "FAIL [AP]: Contact Cron events were not cleared on deactivation"
	exit 1
fi
echo "OK   [AP: Contact data retained, Cron events cleared on deactivation]"

echo "=== AQ. Core reactivated: Cron rescheduled, catch-up cleanup ran ==="
wp_cli plugin activate astrea-core
check_no_fatal "AQ: contact page after reactivation" "$CONTACT_PAGE_PATH"
CRON_AFTER_ACTIVATE=$(wp_cli cron event list --fields=hook --format=csv 2>/dev/null | grep -c astrea_core_contact || true)
if [ "$CRON_AFTER_ACTIVATE" -lt 2 ]; then
	echo "FAIL [AQ]: Contact Cron events were not rescheduled on reactivation"
	exit 1
fi
echo "OK   [AQ: Cron rescheduled after reactivation]"

echo "=== AR. Retention Cleanup: an inquiry past its retention window is removed ==="
EXPIRED_ID=$(wp_cli post create --post_type=astrea_inquiry --post_status=private --post_title='Expired Smoke Inquiry' --post_date="$(date -u -d '100 days ago' '+%Y-%m-%d %H:%M:%S' 2>/dev/null || date -u -v-100d '+%Y-%m-%d %H:%M:%S')" --porcelain)
wp_cli eval '\Astrea\Core\Inquiry\cleanup_expired();'
if wp_cli post get "$EXPIRED_ID" --field=ID > /dev/null 2>&1; then
	echo "FAIL [AR]: an inquiry past its retention window was not cleaned up"
	exit 1
fi
echo "OK   [AR: Retention cleanup removed an expired inquiry]"

echo "=== Cleanup: remove Construction Order 005 smoke-test fixtures ==="
wp_cli post delete "$INQUIRY_ID" "$CONTACT_PAGE_ID" --force > /dev/null 2>&1 || true
wp_cli eval 'delete_option( "astrea_core_contact_settings" ); delete_transient( "astrea_core_contact_pending_email_confirm" ); delete_transient( "smoke_captured_confirm_token" );'

echo "All ASTREA Contact end-to-end checks passed."

# ---------------------------------------------------------------------------
# Part 7 (AS-BE): Construction Order 006 — SEO Foundation end to end.
# ---------------------------------------------------------------------------

# Extracts every <script type="application/ld+json"> block from the last
# BODY_FILE and asserts each one parses as valid JSON. Exits 1 on failure.
assert_all_json_ld_valid() {
	local label="$1"
	node -e '
		const fs = require("fs");
		const html = fs.readFileSync(process.argv[1], "utf8");
		const re = /<script type="application\/ld\+json">([\s\S]*?)<\/script>/g;
		let m, count = 0;
		while ((m = re.exec(html)) !== null) {
			count++;
			try { JSON.parse(m[1]); } catch (e) {
				console.error("Invalid JSON-LD block #" + count + ": " + e.message);
				process.exit(1);
			}
		}
		process.exit(0);
	' "$BODY_FILE"
	if [ $? -ne 0 ]; then
		echo "FAIL [$label]: malformed JSON-LD detected"
		exit 1
	fi
	echo "OK   [$label: all JSON-LD blocks on the page are valid JSON]"
}

echo "=== AS. Office/Professional fixtures for SEO checks ==="
wp_cli eval '
$sanitized = \Astrea\Core\OfficeProfile\sanitize( array(
	"office_name" => "スモークSEO事務所",
	"address"     => "東京都スモーク区1-1-1",
	"phone"       => "03-0000-0000",
) );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
'
SEO_PROF_ID=$(wp_cli post create --post_type=astrea_professional --post_title="スモーク代表" --post_status=publish --porcelain)
wp_cli post meta update "$SEO_PROF_ID" astrea_professional_qualification "弁護士（スモーク）"
SEO_SVC_ID=$(wp_cli post create --post_type=astrea_service --post_title="スモークSEO業務" --post_content="スモークSEO業務の説明です。" --post_status=publish --porcelain)
SEO_FAQ_ID=$(wp_cli post create --post_type=astrea_faq --post_title="スモークSEO質問" --post_content="スモークSEO回答です。" --post_status=publish --porcelain)
echo "OK   [AS: fixtures created]"

echo "=== AT. Home <head>: meta/OGP/Organization JSON-LD, no fatal, no duplicate meta ==="
check_no_fatal "AT: Home"
if [ "$(grep -c '<meta name="description"' "$BODY_FILE" || true)" -gt 1 ] || [ "$(grep -c '<meta property="og:title"' "$BODY_FILE" || true)" -gt 1 ]; then
	echo "FAIL [AT]: duplicate meta description/og:title detected on Home"
	exit 1
fi
if ! grep -qF '"@type":"Organization"' "$BODY_FILE"; then
	echo "FAIL [AT]: Organization JSON-LD not found on Home"
	exit 1
fi
if ! grep -qF 'スモークSEO事務所' "$BODY_FILE"; then
	echo "FAIL [AT]: Office Profile data not reflected in Home JSON-LD"
	exit 1
fi
assert_all_json_ld_valid "AT: Home JSON-LD"

echo "=== AU. Service Archive <head>: Breadcrumb + meta description ==="
check_no_fatal "AU: Service Archive" "/services/"
if ! grep -qF 'class="wp-block-astrea-breadcrumb"' "$BODY_FILE" || ! grep -qF '"@type":"BreadcrumbList"' "$BODY_FILE"; then
	echo "FAIL [AU]: Breadcrumb (visual or JSON-LD) missing on Service Archive"
	exit 1
fi
assert_all_json_ld_valid "AU: Service Archive JSON-LD"

echo "=== AV. Service Single <head>: 3-level Breadcrumb, meta description from content ==="
SEO_SVC_PATH="/services/$(wp_cli post get "$SEO_SVC_ID" --field=post_name)/"
check_no_fatal "AV: Service Single" "$SEO_SVC_PATH"
BREADCRUMB_ITEM_COUNT=$(grep -oE '<li>' "$BODY_FILE" | wc -l)
if [ "$BREADCRUMB_ITEM_COUNT" -lt 3 ]; then
	echo "FAIL [AV]: expected at least 3 Breadcrumb items on Service Single, found $BREADCRUMB_ITEM_COUNT"
	exit 1
fi
if ! grep -qF 'content="スモークSEO業務の説明です。"' "$BODY_FILE"; then
	echo "FAIL [AV]: meta description did not reflect Service content"
	exit 1
fi
assert_all_json_ld_valid "AV: Service Single JSON-LD"

echo "=== AW. Professional Archive <head>: Breadcrumb, Organization JSON-LD includes employee ==="
check_no_fatal "AW: Professional Archive" "/professionals/"
if ! grep -qF 'スモーク代表' "$BODY_FILE"; then
	echo "FAIL [AW]: Professional Profile not reflected in Organization JSON-LD employee list"
	exit 1
fi
assert_all_json_ld_valid "AW: Professional Archive JSON-LD"

echo "=== AX. FAQ Archive <head>: Breadcrumb + no FAQPage JSON-LD (Decision 026) ==="
check_no_fatal "AX: FAQ Archive" "/faq/"
if grep -qF '"@type":"FAQPage"' "$BODY_FILE"; then
	echo "FAIL [AX]: FAQPage JSON-LD must not be emitted (Decision 026)"
	exit 1
fi
if grep -qiE '"@type":"Offer"|PriceSpecification' "$BODY_FILE"; then
	echo "FAIL [AX]: Offer/PriceSpecification JSON-LD must not be emitted (Decision 026)"
	exit 1
fi
assert_all_json_ld_valid "AX: FAQ Archive JSON-LD"

echo "=== AY. Search Console verification: set via real admin form, output correctly, invalid rejected ==="
COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
LOGIN_STATUS=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null -w "%{http_code}")
if [ "$LOGIN_STATUS" != "302" ]; then
	echo "FAIL [AY]: admin login did not redirect as expected (HTTP $LOGIN_STATUS)"
	exit 1
fi
SEO_ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-seo")
if grep -q 'id="loginform"' <<< "$SEO_ADMIN_HTML"; then
	echo "FAIL [AY]: admin session was not recognized on the SEO settings screen"
	exit 1
fi
SEO_NONCE=$(sed -n 's/.*name="_wpnonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$SEO_ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/options.php" \
	--data-urlencode "option_page=astrea_core_seo_settings_group" \
	--data-urlencode "action=update" \
	--data-urlencode "_wpnonce=$SEO_NONCE" \
	--data-urlencode "_wp_http_referer=/wp-admin/admin.php?page=astrea-core-seo" \
	--data-urlencode "astrea_core_seo_settings[search_console_verification]=SmokeTestCode123-_"
check_no_fatal "AY: Home after setting verification code"
if ! grep -qF '<meta name="google-site-verification" content="SmokeTestCode123-_" />' "$BODY_FILE"; then
	echo "FAIL [AY]: Search Console verification meta not output after saving a valid code"
	exit 1
fi
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/options.php" \
	--data-urlencode "option_page=astrea_core_seo_settings_group" \
	--data-urlencode "action=update" \
	--data-urlencode "_wpnonce=$SEO_NONCE" \
	--data-urlencode "_wp_http_referer=/wp-admin/admin.php?page=astrea-core-seo" \
	--data-urlencode 'astrea_core_seo_settings[search_console_verification]=<script>alert(1)</script>'
check_no_fatal "AY: Home after attempting an invalid verification code"
if grep -qF 'google-site-verification' "$BODY_FILE"; then
	echo "FAIL [AY]: an invalid verification code was not rejected"
	exit 1
fi
echo "OK   [AY: Search Console verification set/output correctly, invalid input rejected]"

echo "=== AZ. XSS / JSON-LD injection: malicious Office Profile data cannot break out of <script> or leak raw HTML ==="
wp_cli eval '
$sanitized = \Astrea\Core\OfficeProfile\sanitize( array(
	"office_name" => "</script><script>alert(1)</script>スモーク",
	"address"     => "テスト住所",
) );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
'
check_no_fatal "AZ: Home with malicious Office Profile data"
if grep -qF '</script><script>alert' "$BODY_FILE"; then
	echo "FAIL [AZ]: JSON-LD script-closing injection was not neutralized"
	exit 1
fi
assert_all_json_ld_valid "AZ: Home JSON-LD with malicious input"
# Restore benign fixture data for the remaining checks.
wp_cli eval '
$sanitized = \Astrea\Core\OfficeProfile\sanitize( array(
	"office_name" => "スモークSEO事務所",
	"address"     => "東京都スモーク区1-1-1",
	"phone"       => "03-0000-0000",
) );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
'
echo "OK   [AZ: malicious input cannot break JSON-LD or leak a script tag]"

echo "=== BA. SEO Plugin coexistence: Yoast SEO suppresses ASTREA's own meta/OGP/structured data ==="
# AY's last step deliberately left the verification code rejected/empty; set
# a valid one again so this step can confirm it survives Plugin detection.
wp_cli eval 'update_option( \Astrea\Core\Seo\SETTINGS_OPTION, array_merge( \Astrea\Core\Seo\get_seo_settings(), array( "search_console_verification" => "BASmokeTestCode" ) ) );'
wp_cli plugin install wordpress-seo --activate > /dev/null 2>&1
check_no_fatal "BA: Home with Yoast SEO active"
ASTREA_OG_SITE_NAME_COUNT=$(grep -c '<meta property="og:site_name"' "$BODY_FILE" || true)
ASTREA_ORGANIZATION_COUNT=$(grep -c '"@type":"Organization"' "$BODY_FILE" || true)
if [ "$ASTREA_OG_SITE_NAME_COUNT" -gt 1 ] || [ "$ASTREA_ORGANIZATION_COUNT" -gt 0 ]; then
	echo "FAIL [BA]: ASTREA's own OGP/Organization JSON-LD was not suppressed while a known SEO Plugin is active"
	exit 1
fi
if ! grep -qF 'google-site-verification' "$BODY_FILE"; then
	echo "FAIL [BA]: Search Console verification meta must NOT be suppressed by SEO Plugin detection"
	exit 1
fi
wp_cli plugin deactivate wordpress-seo > /dev/null
wp_cli plugin uninstall wordpress-seo > /dev/null
echo "OK   [BA: known SEO Plugin coexistence — ASTREA's overlapping output suppressed, Search Console kept]"

echo "=== BB. Core deactivated: no Fatal, ASTREA SEO output removed ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "BB: Home while Core inactive" "/"
if grep -qF '"@type":"Organization"' "$BODY_FILE" || grep -qF 'class="wp-block-astrea-breadcrumb"' "$BODY_FILE"; then
	echo "FAIL [BB]: ASTREA SEO output leaked while Core is inactive"
	exit 1
fi
echo "OK   [BB: ASTREA SEO output cleanly absent while Core is inactive]"

echo "=== BC. Core reactivated: ASTREA SEO output restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "BC: Home after reactivation"
if ! grep -qF '"@type":"Organization"' "$BODY_FILE"; then
	echo "FAIL [BC]: Organization JSON-LD not restored after reactivation"
	exit 1
fi
echo "OK   [BC: ASTREA SEO output restored after reactivation]"

echo "=== Cleanup: remove Construction Order 006 smoke-test fixtures ==="
wp_cli post delete "$SEO_PROF_ID" "$SEO_SVC_ID" "$SEO_FAQ_ID" --force > /dev/null
wp_cli eval 'delete_option( \Astrea\Core\OfficeProfile\OPTION_NAME ); delete_option( \Astrea\Core\Seo\SETTINGS_OPTION );'
rm -f "$COOKIE_JAR"

echo "All ASTREA SEO Foundation end-to-end checks passed."

# Part 8 (BD-BM) automates Construction Order 007 (Setup / Onboarding):
# the setup checklist rendered on the ASTREA Office Profile page (derived
# from real data, no separate progress store), the "基本ページを作成する"
# / "基本メニューを作成する" admin-post actions (idempotent, never
# overwriting existing content, never generating a Navigation when one
# already exists), and the Theme-side Core-recommendation notice
# (Decision 021) with its per-user Dismiss.

COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null

echo "=== BD. Setup checklist renders on the ASTREA admin page, reflecting real (empty) state ==="
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if grep -q 'id="loginform"' <<< "$ADMIN_HTML"; then
	echo "FAIL [BD]: admin session was not recognized"
	exit 1
fi
if ! grep -qF 'セットアップ状況' <<< "$ADMIN_HTML"; then
	echo "FAIL [BD]: setup checklist heading did not render"
	exit 1
fi
if ! grep -qF '取扱業務を1件以上登録する' <<< "$ADMIN_HTML"; then
	echo "FAIL [BD]: Service checklist item did not render"
	exit 1
fi
echo "OK   [BD: setup checklist renders on the ASTREA admin page]"

echo "=== BE. 基本ページを作成する generates exactly 事務所概要/料金/お問い合わせ (draft), no Service/FAQ page ==="
PAGES_NONCE=$(sed -n 's/.*name="astrea_setup_generate_pages_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_pages&astrea_setup_generate_pages_nonce=$PAGES_NONCE"
GENERATED=$(wp_cli option get astrea_core_generated_pages --format=json)
GENERATED_KEYS=$(echo "$GENERATED" | node -e "console.log(Object.keys(JSON.parse(require('fs').readFileSync(0,'utf8'))).sort().join(','))")
if [ "$GENERATED_KEYS" != "about,contact,price" ]; then
	echo "FAIL [BE]: expected generated page keys 'about,contact,price', got '$GENERATED_KEYS'"
	exit 1
fi
ABOUT_ID=$(echo "$GENERATED" | node -e "console.log(JSON.parse(require('fs').readFileSync(0,'utf8')).about)")
PRICE_ID=$(echo "$GENERATED" | node -e "console.log(JSON.parse(require('fs').readFileSync(0,'utf8')).price)")
CONTACT_ID=$(echo "$GENERATED" | node -e "console.log(JSON.parse(require('fs').readFileSync(0,'utf8')).contact)")
for id in "$ABOUT_ID" "$PRICE_ID" "$CONTACT_ID"; do
	STATUS=$(wp_cli post get "$id" --field=post_status)
	if [ "$STATUS" != "draft" ]; then
		echo "FAIL [BE]: generated page $id expected status 'draft', got '$STATUS'"
		exit 1
	fi
done
echo "OK   [BE: generated exactly the 3 non-duplicate pages, all draft]"

echo "=== BF. Re-running 基本ページを作成する does not duplicate or overwrite existing content ==="
wp_cli post update "$ABOUT_ID" --post_content="スモークテストが書いた本文"
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_pages&astrea_setup_generate_pages_nonce=$PAGES_NONCE"
GENERATED_AFTER=$(wp_cli option get astrea_core_generated_pages --format=json)
ABOUT_ID_AFTER=$(echo "$GENERATED_AFTER" | node -e "console.log(JSON.parse(require('fs').readFileSync(0,'utf8')).about)")
if [ "$ABOUT_ID_AFTER" != "$ABOUT_ID" ]; then
	echo "FAIL [BF]: re-running generated a new page instead of recognizing the existing one"
	exit 1
fi
ABOUT_CONTENT=$(wp_cli post get "$ABOUT_ID" --field=post_content)
if [ "$ABOUT_CONTENT" != "スモークテストが書いた本文" ]; then
	echo "FAIL [BF]: re-running overwrote existing page content"
	exit 1
fi
echo "OK   [BF: re-running is idempotent and never overwrites existing content]"

echo "=== BG. Publishing the generated Contact page flips the checklist's Contact-reachable item to done ==="
wp_cli post update "$CONTACT_ID" --post_status=publish
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
# Done items render as plain text (no <a>); a not-done item has an <a> wrapping the label.
CONTACT_LINE=$(grep -oE '.{0,80}問い合わせフォームを設置したページを公開する.{0,10}' <<< "$ADMIN_HTML")
if grep -q '<a ' <<< "$CONTACT_LINE"; then
	echo "FAIL [BG]: Contact-reachable checklist item still shows as not-done after publishing the Contact page"
	exit 1
fi
echo "OK   [BG: Contact-reachable checklist item reflects the published Contact page]"

echo "=== BH. Confirmed notification email flips the checklist's notification item to done ==="
wp_cli eval 'update_option( \Astrea\Core\Inquiry\SETTINGS_OPTION, array_merge( \Astrea\Core\Inquiry\get_contact_settings(), array( "notification_email" => "smoke-007@example.com" ) ) );'
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
NOTIFY_LINE=$(grep -oE '.{0,80}問い合わせの通知先メールアドレスを確認する.{0,10}' <<< "$ADMIN_HTML")
if grep -q '<a ' <<< "$NOTIFY_LINE"; then
	echo "FAIL [BH]: notification-confirmed checklist item still shows as not-done after setting a confirmed address"
	exit 1
fi
echo "OK   [BH: notification-confirmed checklist item reflects the confirmed address]"

echo "=== BI. 基本メニューを作成する generates a draft Navigation with links to real content ==="
# Construction Order 008 gave the Header/Footer a bare core/navigation
# block; WordPress core itself (block_core_navigation_get_fallback_blocks(),
# WP_Navigation_Fallback) auto-creates and publishes a real wp_navigation
# post as a side effect of rendering it on any front-end page view once no
# Navigation exists yet — this is standard WordPress behavior on every
# block theme, not something ASTREA generates. Since Parts 1-8 have
# already rendered many pages by this point, that auto-fallback almost
# certainly exists now; clear it so this test can verify the "0
# Navigation -> button offered -> generates the real one" flow this step
# actually targets, without disturbing the has_any_navigation() guard
# itself (which correctly treats ANY existing Navigation, auto-created or
# not, as "one already exists" — see BJ below).
wp_cli post delete $(wp_cli post list --post_type=wp_navigation --field=ID) --force > /dev/null 2>&1 || true
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if ! grep -qF '基本メニューを作成する' <<< "$ADMIN_HTML"; then
	echo "FAIL [BI]: Navigation generation button did not render (no Navigation should exist yet)"
	exit 1
fi
NAV_NONCE=$(sed -n 's/.*name="astrea_setup_generate_navigation_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_navigation&astrea_setup_generate_navigation_nonce=$NAV_NONCE"
NAV_COUNT=$(wp_cli post list --post_type=wp_navigation --format=count)
if [ "$NAV_COUNT" != "1" ]; then
	echo "FAIL [BI]: expected exactly 1 generated wp_navigation post, found $NAV_COUNT"
	exit 1
fi
NAV_ID=$(wp_cli post list --post_type=wp_navigation --field=ID)
NAV_CONTENT=$(wp_cli post get "$NAV_ID" --field=post_content)
if ! grep -qF '料金' <<< "$NAV_CONTENT" || ! grep -qF 'お問い合わせ' <<< "$NAV_CONTENT"; then
	echo "FAIL [BI]: generated Navigation is missing expected links"
	exit 1
fi
echo "OK   [BI: generated a draft Navigation with links to the generated pages]"

echo "=== BJ. 基本メニューを作成する is not offered once a Navigation already exists ==="
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if grep -qF 'name="astrea_setup_generate_navigation_nonce"' <<< "$ADMIN_HTML"; then
	echo "FAIL [BJ]: Navigation generation form still rendered after a Navigation already exists"
	exit 1
fi
echo "OK   [BJ: Navigation generation is correctly withheld once a Navigation exists]"

echo "=== BK. Core-recommendation notice appears while Core is inactive, and can be dismissed per-user ==="
wp_cli plugin deactivate astrea-core
DASHBOARD_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/index.php")
if ! grep -qF 'ASTREA Coreを有効化すると' <<< "$DASHBOARD_HTML"; then
	echo "FAIL [BK]: Core-recommendation notice did not appear on the Dashboard while Core is inactive"
	exit 1
fi
DISMISS_URL=$(grep -oE "admin-post\.php\?action=astrea_dismiss_core_notice[^\"']*" <<< "$DASHBOARD_HTML" | sed 's/&#0\?38;/\&/g' | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/$DISMISS_URL"
DASHBOARD_HTML_AFTER=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/index.php")
if grep -qF 'ASTREA Coreを有効化すると' <<< "$DASHBOARD_HTML_AFTER"; then
	echo "FAIL [BK]: Core-recommendation notice still appeared after being dismissed"
	exit 1
fi
echo "OK   [BK: Core-recommendation notice appears while Core is inactive and is dismissible per-user]"

echo "=== BL. Core reactivated: notice stays gone (Core now active), Theme still safe throughout ==="
wp_cli plugin activate astrea-core
check_no_fatal "BL: Home after reactivation"
DASHBOARD_HTML_REACTIVATED=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/index.php")
if grep -qF 'ASTREA Coreを有効化すると' <<< "$DASHBOARD_HTML_REACTIVATED"; then
	echo "FAIL [BL]: Core-recommendation notice appeared while Core is active"
	exit 1
fi
echo "OK   [BL: no Fatal across deactivate/reactivate, notice correctly gone once Core is active]"

echo "=== Cleanup: remove Construction Order 007 smoke-test fixtures ==="
wp_cli post delete "$ABOUT_ID" "$PRICE_ID" "$CONTACT_ID" "$NAV_ID" --force > /dev/null
wp_cli eval 'delete_option( "astrea_core_generated_pages" ); delete_option( \Astrea\Core\Inquiry\SETTINGS_OPTION ); $u = get_user_by( "login", "admin" ); if ( $u ) { delete_user_meta( $u->ID, "astrea_core_notice_dismissed" ); }'
rm -f "$COOKIE_JAR"

echo "All ASTREA Setup / Onboarding end-to-end checks passed."

# Part 9 (BM-BY) automates Construction Order 008 (Design System / Theme
# 表示基盤, Decision 028): Style Variations are recognized by WordPress,
# the new front-page/home/page/single/search/404 templates and Header/
# Footer parts render without fatal, the phone_tel Block Bindings key,
# the astrea/faq-list and astrea/representative Dynamic Blocks' heading/
# emptyMessage/self-hide behaviour on a real page, core/query-no-results
# on a real 0-result archive, and the same Core-inactive/deactivate/
# reactivate coverage as Parts 1-8.

echo "=== BM. Style Variations (Trust/Natural/Modern) are recognized by WordPress ==="
VARIATION_TITLES=$(wp_cli eval 'echo implode(",", array_map(function($v){return $v["title"];}, WP_Theme_JSON_Resolver::get_style_variations()));')
for name in Trust Natural Modern; do
	if ! grep -qF "$name" <<< "$VARIATION_TITLES"; then
		echo "FAIL [BM]: Style Variation '$name' not recognized by WordPress (found: $VARIATION_TITLES)"
		exit 1
	fi
done
echo "OK   [BM: Trust/Natural/Modern Style Variations recognized ($VARIATION_TITLES)]"

echo "=== BN. New templates render without fatal: Home, Search, 404 ==="
check_no_fatal "BN: Home (front-page/home template)"
check_no_fatal "BN: Search results" "/?s=smoke-008"
fetch_no_fatal_any_status "BN: a nonexistent URL (404 template)" "/astrea-smoke-008-nonexistent-page/"
echo "OK   [BN: Home/Search/404 templates render without fatal]"

echo "=== BO. Header shows Office Profile data and a working tel: link ==="
wp_cli eval 'update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, \Astrea\Core\OfficeProfile\sanitize( array( "office_name" => "スモーク008事務所", "phone" => "03-1234-5678" ) ) );'
check_no_fatal "BO: Home with Office Profile data"
if ! grep -qF "スモーク008事務所" "$BODY_FILE"; then
	echo "FAIL [BO]: Header does not show the bound office_name"
	exit 1
fi
if ! grep -qF 'href="tel:03-1234-5678"' "$BODY_FILE"; then
	echo "FAIL [BO]: Header phone CTA is not a working tel: link"
	exit 1
fi
echo "OK   [BO: Header renders office_name and a tel: link CTA]"

echo "=== BP. Footer template part renders Office Profile data ==="
if ! grep -qF "<footer" "$BODY_FILE"; then
	echo "FAIL [BP]: Footer template part did not render"
	exit 1
fi
echo "OK   [BP: Footer template part renders]"

echo "=== BQ. astrea/faq-list: self-hides with zero important FAQs, shows heading+content once one exists ==="
FAQ_TEASER_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke008 FAQ Teaser" --post_status=publish --post_content='<!-- wp:astrea/faq-list {"mode":"important","limit":3,"heading":"よくあるご質問"} /-->' --porcelain)
FAQ_TEASER_PATH="/$(wp_cli post get "$FAQ_TEASER_PAGE" --field=post_name)/"
check_no_fatal "BQ: FAQ teaser page with zero important FAQs" "$FAQ_TEASER_PATH"
if grep -qF "よくあるご質問" "$BODY_FILE"; then
	echo "FAIL [BQ]: heading appeared even though there is no important FAQ (whole-section self-hide expected)"
	exit 1
fi
SMOKE_FAQ=$(wp_cli post create --post_type=astrea_faq --post_title="スモーク008重要FAQ" --post_status=publish --post_content="回答本文スモーク008" --porcelain)
wp_cli post meta update "$SMOKE_FAQ" astrea_faq_is_important 1
check_no_fatal "BQ: FAQ teaser page with one important FAQ" "$FAQ_TEASER_PATH"
if ! grep -qF "よくあるご質問" "$BODY_FILE" || ! grep -qF "スモーク008重要FAQ" "$BODY_FILE"; then
	echo "FAIL [BQ]: heading+important FAQ did not render once one exists"
	exit 1
fi
echo "OK   [BQ: astrea/faq-list self-hides at zero items, shows heading+content once populated]"

echo "=== BR. astrea/representative: self-hides with no flagged representative, shows once flagged ==="
REP_TEASER_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke008 Representative Teaser" --post_status=publish --post_content='<!-- wp:astrea/representative {"heading":"代表者紹介"} /-->' --porcelain)
REP_TEASER_PATH="/$(wp_cli post get "$REP_TEASER_PAGE" --field=post_name)/"
SMOKE_PROF=$(wp_cli post create --post_type=astrea_professional --post_title="スモーク008代表" --post_status=publish --porcelain)
check_no_fatal "BR: Representative teaser page with nobody flagged" "$REP_TEASER_PATH"
if grep -qF "代表者紹介" "$BODY_FILE"; then
	echo "FAIL [BR]: heading appeared even though nobody is flagged representative"
	exit 1
fi
wp_cli post meta update "$SMOKE_PROF" astrea_professional_is_representative 1
check_no_fatal "BR: Representative teaser page with one flagged representative" "$REP_TEASER_PATH"
if ! grep -qF "代表者紹介" "$BODY_FILE" || ! grep -qF "スモーク008代表" "$BODY_FILE"; then
	echo "FAIL [BR]: heading+representative did not render once one is flagged"
	exit 1
fi
echo "OK   [BR: astrea/representative self-hides with nobody flagged, shows once one is flagged]"

echo "=== BS. astrea/price-list dedicated-page mode: friendly message at zero items, list once populated ==="
PRICE_MSG_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke008 Price Message" --post_status=publish --post_content='<!-- wp:astrea/price-list {"emptyMessage":"現在、料金情報は準備中です。"} /-->' --porcelain)
PRICE_MSG_PATH="/$(wp_cli post get "$PRICE_MSG_PAGE" --field=post_name)/"
check_no_fatal "BS: Price message page with zero prices" "$PRICE_MSG_PATH"
if ! grep -qF "現在、料金情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [BS]: dedicated-page empty message did not render for zero Price entries"
	exit 1
fi
echo "OK   [BS: astrea/price-list shows a friendly message (not a blank page) at zero items in dedicated-page mode]"

echo "=== BT. Service archive: core/query-no-results shows a friendly message at zero Services ==="
check_no_fatal "BT: Service archive with zero Services" "/services/"
if ! grep -qF "現在、取扱業務の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [BT]: core/query-no-results message did not render on the empty Service archive"
	exit 1
fi
SMOKE_SVC=$(wp_cli post create --post_type=astrea_service --post_title="スモーク008業務" --post_status=publish --porcelain)
check_no_fatal "BT: Service archive with one Service" "/services/"
if grep -qF "現在、取扱業務の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [BT]: No Results message still shown even though a Service now exists"
	exit 1
fi
echo "OK   [BT: core/query-no-results correctly shown at zero items, hidden once a Service exists]"

echo "=== BU. Core deactivated: new Dynamic Blocks/Bindings degrade safely, no Fatal ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "BU: Home while Core inactive" "/"
fetch_no_fatal_any_status "BU: FAQ teaser page while Core inactive" "$FAQ_TEASER_PATH"
fetch_no_fatal_any_status "BU: Representative teaser page while Core inactive" "$REP_TEASER_PATH"
if grep -qF "スモーク008事務所" "$BODY_FILE"; then
	echo "FAIL [BU]: stale Office Profile data leaked while Core is inactive"
	exit 1
fi
echo "OK   [BU: Theme degrades safely with no Fatal while Core is inactive]"

echo "=== BV. Core reactivated: Design System output restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "BV: Home after reactivation"
if ! grep -qF "スモーク008事務所" "$BODY_FILE"; then
	echo "FAIL [BV]: Office Profile display not restored after reactivation"
	exit 1
fi
echo "OK   [BV: Design System output restored after Core reactivation]"

echo "=== Cleanup: remove Construction Order 008 smoke-test fixtures ==="
wp_cli post delete "$FAQ_TEASER_PAGE" "$REP_TEASER_PAGE" "$PRICE_MSG_PAGE" "$SMOKE_FAQ" "$SMOKE_PROF" "$SMOKE_SVC" --force > /dev/null
wp_cli eval 'delete_option( \Astrea\Core\OfficeProfile\OPTION_NAME );'

echo "All ASTREA Design System / Theme end-to-end checks passed."

# Part 10 (BW-CD) automates Construction Order 009: the Navigation
# checklist fix (ignoring WordPress's own Page List fallback), HOME
# assembly (generation, idempotency, existing-front-page protection), GA4
# (Measurement ID save/reject, tag output, known-Plugin suppression), and
# the explicit complete-data-deletion flow (wrong-phrase refusal, real
# deletion, generated-content/Media survival), plus the same Core-inactive/
# deactivate/reactivate coverage as Parts 1-9.

COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null

echo "=== BW. Setup checklist ignores WordPress's own Page List Navigation fallback ==="
# Construction Order 013: BI (above) already exercised the real
# "基本メニューを作成する" button, which now also binds Header/Footer to
# it via a `ref` on a Setup-owned wp_template_part (see
# includes/setup-navigation.php connect_navigation_to_template_part()).
# A Navigation block with a `ref` — even one pointing at a since-deleted
# post — renders empty rather than falling through to
# WP_Navigation_Fallback (confirmed by reading
# get_inner_blocks_from_navigation_post() in wp-includes/blocks/
# navigation.php: it returns an empty WP_Block_List as soon as `ref` is
# set at all, regardless of whether the referenced post still exists).
# This step must reset Header/Footer back to their original bare-block
# state first, or WordPress's own fallback-creation this test targets
# will never fire and this becomes a false failure, not a real one.
wp_cli eval 'delete_option( "astrea_core_generated_navigation" ); delete_option( "astrea_core_generated_template_parts" );'
BW_STALE_IDS=$(wp_cli post list --post_type=wp_navigation,wp_template_part --field=ID)
if [ -n "$BW_STALE_IDS" ]; then wp_cli post delete $BW_STALE_IDS --force > /dev/null 2>&1 || true; fi
check_no_fatal "BW: Home (triggers WordPress's Navigation fallback creation)"
FALLBACK_NAV_COUNT=$(wp_cli post list --post_type=wp_navigation --format=count)
if [ "$FALLBACK_NAV_COUNT" != "1" ]; then
	echo "FAIL [BW]: expected exactly 1 auto-created fallback Navigation after one page view, found $FALLBACK_NAV_COUNT"
	exit 1
fi
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if ! grep -qE '<a[^>]*>サイトのメニュー（Navigation）を作成する' <<< "$ADMIN_HTML"; then
	echo "FAIL [BW]: Navigation checklist item shows as done from WordPress's own fallback alone"
	exit 1
fi
if ! grep -qF '基本メニューを作成する' <<< "$ADMIN_HTML"; then
	echo "FAIL [BW]: Navigation generation button was hidden by the mere presence of WordPress's fallback"
	exit 1
fi
echo "OK   [BW: WordPress's own Page List fallback is correctly not counted as a meaningful Navigation]"

echo "=== BX. ホームページを作成する assembles HOME and sets it as the static front page ==="
wp_cli eval 'update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, \Astrea\Core\OfficeProfile\sanitize( array( "office_name" => "スモーク009事務所" ) ) );'
HOME_NONCE=$(sed -n 's/.*name="astrea_setup_generate_home_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_home&astrea_setup_generate_home_nonce=$HOME_NONCE"
SHOW_ON_FRONT=$(wp_cli option get show_on_front)
if [ "$SHOW_ON_FRONT" != "page" ]; then
	echo "FAIL [BX]: show_on_front was not set to 'page' after generating HOME"
	exit 1
fi
HOME_PAGE_ID=$(wp_cli option get page_on_front)
HOME_STATUS=$(wp_cli post get "$HOME_PAGE_ID" --field=post_status)
if [ "$HOME_STATUS" != "publish" ]; then
	echo "FAIL [BX]: generated HOME page is not published (status: $HOME_STATUS)"
	exit 1
fi
check_no_fatal "BX: Home after HOME assembly"
if ! grep -qF "スモーク009事務所" "$BODY_FILE"; then
	echo "FAIL [BX]: assembled HOME does not render Office Profile data (Hero Pattern)"
	exit 1
fi
echo "OK   [BX: HOME assembled, published, and set as the static front page]"

echo "=== BY. Re-running ホームページを作成する does not duplicate ==="
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_home&astrea_setup_generate_home_nonce=$HOME_NONCE"
HOME_PAGE_ID_AFTER=$(wp_cli option get page_on_front)
if [ "$HOME_PAGE_ID_AFTER" != "$HOME_PAGE_ID" ]; then
	echo "FAIL [BY]: re-running HOME generation replaced the existing front page instead of leaving it alone"
	exit 1
fi
echo "OK   [BY: HOME generation is idempotent]"

echo "=== BZ. ホームページを作成する refuses when a different static front page already exists ==="
wp_cli option update page_on_front 0
wp_cli eval 'delete_option( "astrea_core_generated_pages" );'
OTHER_FRONT_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke009 Other Front Page" --post_status=publish --porcelain)
wp_cli option update page_on_front "$OTHER_FRONT_PAGE"
wp_cli option update show_on_front page
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_home&astrea_setup_generate_home_nonce=$HOME_NONCE"
FRONT_PAGE_UNCHANGED=$(wp_cli option get page_on_front)
if [ "$FRONT_PAGE_UNCHANGED" != "$OTHER_FRONT_PAGE" ]; then
	echo "FAIL [BZ]: an existing user-set front page was overwritten by HOME generation"
	exit 1
fi
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core#astrea-setup-generate-home")
if ! grep -qF '既にホームページが設定されています' <<< "$ADMIN_HTML"; then
	echo "FAIL [BZ]: expected 'already configured' state was not shown after a refused HOME generation"
	exit 1
fi
echo "OK   [BZ: an existing static front page is never overwritten]"

echo "=== CA. GA4: valid Measurement ID saves and outputs the tag, invalid ID is rejected ==="
SEO_ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-seo")
SEO_NONCE=$(sed -n 's/.*name="_wpnonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$SEO_ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/options.php" \
	--data-urlencode "option_page=astrea_core_seo_settings_group" \
	--data-urlencode "action=update" \
	--data-urlencode "_wpnonce=$SEO_NONCE" \
	--data-urlencode "_wp_http_referer=/wp-admin/admin.php?page=astrea-core-seo" \
	--data-urlencode "astrea_core_seo_settings[ga4_measurement_id]=G-SMOKE12345"
check_no_fatal "CA: Home with a valid GA4 Measurement ID"
if ! grep -qF "G-SMOKE12345" "$BODY_FILE" || ! grep -qF "googletagmanager.com/gtag/js" "$BODY_FILE"; then
	echo "FAIL [CA]: GA4 tag was not output after saving a valid Measurement ID"
	exit 1
fi
SEO_ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-seo")
SEO_NONCE=$(sed -n 's/.*name="_wpnonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$SEO_ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/options.php" \
	--data-urlencode "option_page=astrea_core_seo_settings_group" \
	--data-urlencode "action=update" \
	--data-urlencode "_wpnonce=$SEO_NONCE" \
	--data-urlencode "_wp_http_referer=/wp-admin/admin.php?page=astrea-core-seo" \
	--data-urlencode "astrea_core_seo_settings[ga4_measurement_id]=<script>alert(1)</script>"
check_no_fatal "CA: Home after attempting an invalid GA4 Measurement ID"
if grep -qF "<script>alert(1)</script>" "$BODY_FILE"; then
	echo "FAIL [CA]: invalid GA4 Measurement ID was not rejected"
	exit 1
fi
GA4_STORED=$(wp_cli eval 'echo \Astrea\Core\Seo\get_seo_settings()["ga4_measurement_id"];')
if [ -n "$GA4_STORED" ]; then
	echo "FAIL [CA]: invalid GA4 Measurement ID was stored instead of being cleared ($GA4_STORED)"
	exit 1
fi
echo "OK   [CA: GA4 Measurement ID save/output/reject all behave correctly]"

echo "=== CB. GA4 output is suppressed while a known Analytics Plugin is active ==="
wp_cli eval 'update_option( \Astrea\Core\Seo\SETTINGS_OPTION, array_merge( \Astrea\Core\Seo\get_seo_settings(), array( "ga4_measurement_id" => "G-SMOKE12345" ) ) );'
# Append the known Analytics Plugin basename to the REAL active_plugins list
# rather than replacing it outright — replacing it would also deactivate
# astrea-core itself (it's an entry in that same option), breaking every
# check after this one.
wp_cli eval 'update_option( "active_plugins", array_merge( get_option( "active_plugins", array() ), array( "google-site-kit/google-site-kit.php" ) ) );'
check_no_fatal "CB: Home with a known Analytics Plugin 'active'"
if grep -qF "googletagmanager.com/gtag/js" "$BODY_FILE"; then
	echo "FAIL [CB]: ASTREA's own GA4 tag was not suppressed while a known Analytics Plugin is active"
	exit 1
fi
wp_cli eval 'update_option( "active_plugins", array_values( array_diff( get_option( "active_plugins", array() ), array( "google-site-kit/google-site-kit.php" ) ) ) );'
check_no_fatal "CB: Home after the known Analytics Plugin is no longer active"
if ! grep -qF "googletagmanager.com/gtag/js" "$BODY_FILE"; then
	echo "FAIL [CB]: GA4 tag did not resume once the known Analytics Plugin was no longer active"
	exit 1
fi
echo "OK   [CB: GA4 Plugin-coexistence suppression works and is reversible]"

echo "=== CC. Core complete data deletion: wrong phrase refuses, correct phrase deletes ==="
DELETE_FAQ=$(wp_cli post create --post_type=astrea_faq --post_title="削除確認用FAQ" --post_status=publish --porcelain)
DELETE_ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-data-deletion")
if grep -q '<a href="[^"]*loginform' <<< "$DELETE_ADMIN_HTML"; then
	echo "FAIL [CC]: could not reach the data-deletion screen"
	exit 1
fi
DELETE_NONCE=$(sed -n 's/.*name="astrea_delete_all_core_data_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$DELETE_ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_delete_all_core_data" \
	--data-urlencode "astrea_delete_all_core_data_nonce=$DELETE_NONCE" \
	--data-urlencode "confirm_understood=1" \
	--data-urlencode "confirm_phrase=まちがい"
FAQ_SURVIVES=$(wp_cli post get "$DELETE_FAQ" --field=post_status 2>/dev/null || echo "gone")
if [ "$FAQ_SURVIVES" = "gone" ]; then
	echo "FAIL [CC]: data was deleted despite an incorrect confirmation phrase"
	exit 1
fi
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_delete_all_core_data" \
	--data-urlencode "astrea_delete_all_core_data_nonce=$DELETE_NONCE" \
	--data-urlencode "confirm_understood=1" \
	--data-urlencode "confirm_phrase=削除する"
FAQ_DELETED=$(wp_cli post get "$DELETE_FAQ" --field=post_status 2>/dev/null || echo "gone")
if [ "$FAQ_DELETED" != "gone" ]; then
	echo "FAIL [CC]: FAQ was not deleted after a correct, confirmed deletion request"
	exit 1
fi
OFFICE_PROFILE_GONE=$(wp_cli option get astrea_core_office_profile 2>/dev/null || echo "gone")
if [ "$OFFICE_PROFILE_GONE" != "gone" ]; then
	echo "FAIL [CC]: Office Profile option survived complete data deletion"
	exit 1
fi
echo "OK   [CC: wrong confirmation phrase refuses deletion; correct phrase performs it]"

echo "=== CD. Complete data deletion never removes generated Pages, Navigation, or Media ==="
HOME_PAGE_SURVIVES=$(wp_cli post get "$HOME_PAGE_ID" --field=post_status 2>/dev/null || echo "gone")
if [ "$HOME_PAGE_SURVIVES" = "gone" ]; then
	echo "FAIL [CD]: the generated HOME page was deleted by the complete-data-deletion action"
	exit 1
fi
NAV_SURVIVES_COUNT=$(wp_cli post list --post_type=wp_navigation --format=count)
if [ "$NAV_SURVIVES_COUNT" = "0" ]; then
	echo "FAIL [CD]: Navigation post(s) were deleted by the complete-data-deletion action"
	exit 1
fi
echo "OK   [CD: generated Pages/Navigation/Media are never touched by complete data deletion]"

echo "=== CE. Core deactivated: HOME/GA4/data-deletion degrade safely, no Fatal ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "CE: Home while Core inactive" "/"
if grep -qF "googletagmanager.com/gtag/js" "$BODY_FILE"; then
	echo "FAIL [CE]: GA4 tag leaked while Core is inactive"
	exit 1
fi
fetch_no_fatal_any_status "CE: ASTREA admin page while Core inactive" "/wp-admin/admin.php?page=astrea-core"
wp_cli plugin activate astrea-core
check_no_fatal "CE: Home after reactivation"
echo "OK   [CE: Theme remains safe with Core inactive; ASTREA admin UI cleanly absent]"

echo "=== Cleanup: remove Construction Order 009 smoke-test fixtures ==="
wp_cli option update show_on_front posts
wp_cli option update page_on_front 0
wp_cli post delete "$OTHER_FRONT_PAGE" "$HOME_PAGE_ID" $(wp_cli post list --post_type=wp_navigation --field=ID) --force > /dev/null 2>&1 || true
rm -f "$COOKIE_JAR"

echo "All ASTREA HOME / GA4 / Core data-deletion end-to-end checks passed."

# Part 11 (CF-CO) automates Construction Order 010: CASE/RESULTS/VOICE
# Archive/Single display, the 3 new HOME Teaser Dynamic Blocks' 0-item
# self-hide and heading+content behaviour, CASE's related-Service admin
# save, Core deactivate/reactivate safety, and complete-data-deletion
# coverage for all three new post types (including Media survival).

COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null

echo "=== CF. CASE Archive: 0件でNo Results、投稿後にタイトル・抜粋・画像リンクが表示される ==="
check_no_fatal "CF: CASE archive, 0 items" "/cases/"
if ! grep -qF "現在、対応事例の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [CF]: core/query-no-results message did not render on the empty CASE archive"
	exit 1
fi
CASE_ATTACHMENT=$(wp_cli media import /var/www/html/wp-includes/images/w-logo-blue.png --porcelain)
CASE_A=$(wp_cli post create --post_type=astrea_case --post_title="スモーク010事例A" --post_status=publish --post_excerpt="事例Aの概要です。" --post_content="事例Aの本文です。" --porcelain)
wp_cli post meta update "$CASE_A" _thumbnail_id "$CASE_ATTACHMENT"
check_no_fatal "CF: CASE archive with 1 item" "/cases/"
if grep -qF "現在、対応事例の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [CF]: No Results message still shown even though a CASE now exists"
	exit 1
fi
if ! grep -qF "スモーク010事例A" "$BODY_FILE" || ! grep -qF "事例Aの概要です。" "$BODY_FILE"; then
	echo "FAIL [CF]: CASE archive did not render title/excerpt"
	exit 1
fi
echo "OK   [CF: CASE archive 0-item/1-item behaviour correct]"

echo "=== CG. CASE Single: タイトル・画像・本文が表示される ==="
CASE_A_PATH="/cases/$(wp_cli post get "$CASE_A" --field=post_name)/"
check_no_fatal "CG: CASE single" "$CASE_A_PATH"
if ! grep -qF "スモーク010事例A" "$BODY_FILE" || ! grep -qF "事例Aの本文です。" "$BODY_FILE"; then
	echo "FAIL [CG]: CASE single did not render title/content"
	exit 1
fi
echo "OK   [CG: CASE single renders correctly]"

echo "=== CH. VOICE Archive: 0件でNo Results、投稿後に表示名・本文が表示される ==="
check_no_fatal "CH: VOICE archive, 0 items" "/voices/"
if ! grep -qF "現在、お客様の声の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [CH]: core/query-no-results message did not render on the empty VOICE archive"
	exit 1
fi
VOICE_A=$(wp_cli post create --post_type=astrea_voice --post_title="40代・経営者様" --post_status=publish --post_content="大変助かりました。" --porcelain)
check_no_fatal "CH: VOICE archive with 1 item" "/voices/"
if grep -qF "現在、お客様の声の情報は準備中です。" "$BODY_FILE"; then
	echo "FAIL [CH]: No Results message still shown even though a VOICE now exists"
	exit 1
fi
if ! grep -qF "40代・経営者様" "$BODY_FILE" || ! grep -qF "大変助かりました。" "$BODY_FILE"; then
	echo "FAIL [CH]: VOICE archive did not render display name/content"
	exit 1
fi
echo "OK   [CH: VOICE archive 0-item/1-item behaviour correct]"

echo "=== CI. RESULTS: 非公開URL、Dynamic Block経由でのみ表示される ==="
RESULT_A=$(wp_cli post create --post_type=astrea_result --post_title="相談実績" --post_status=publish --porcelain)
wp_cli post meta update "$RESULT_A" astrea_result_value "1,000件以上"
if [ "$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/?p=$RESULT_A")" = "200" ]; then
	echo "FAIL [CI]: RESULTS post is reachable as a normal 200 page — no basis for an individual URL"
	exit 1
fi
echo "OK   [CI: RESULTS has no individual URL]"

echo "=== CJ. HOME Teaser Dynamic Blocks: 0件で見出し含め完全非表示、投稿後に見出し+内容が表示される ==="
CASE_TEASER_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke010 Case Teaser" --post_status=publish --post_content='<!-- wp:astrea/case-list {"limit":3,"heading":"対応事例"} /-->' --porcelain)
CASE_TEASER_PATH="/$(wp_cli post get "$CASE_TEASER_PAGE" --field=post_name)/"
check_no_fatal "CJ: CASE Teaser page (1 CASE already exists)" "$CASE_TEASER_PATH"
if ! grep -qF "対応事例" "$BODY_FILE" || ! grep -qF "スモーク010事例A" "$BODY_FILE"; then
	echo "FAIL [CJ]: CASE Teaser did not render heading+content with an existing CASE"
	exit 1
fi

RESULTS_TEASER_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke010 Results Teaser" --post_status=publish --post_content='<!-- wp:astrea/results-list {"heading":"実績"} /-->' --porcelain)
RESULTS_TEASER_PATH="/$(wp_cli post get "$RESULTS_TEASER_PAGE" --field=post_name)/"
check_no_fatal "CJ: RESULTS Teaser page (1 RESULTS already exists)" "$RESULTS_TEASER_PATH"
if ! grep -qF "実績" "$BODY_FILE" || ! grep -qF "1,000件以上" "$BODY_FILE"; then
	echo "FAIL [CJ]: RESULTS Teaser did not render heading+content with an existing RESULTS entry"
	exit 1
fi

VOICE_TEASER_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke010 Voice Teaser" --post_status=publish --post_content='<!-- wp:astrea/voice-list {"limit":3,"heading":"お客様の声"} /-->' --porcelain)
VOICE_TEASER_PATH="/$(wp_cli post get "$VOICE_TEASER_PAGE" --field=post_name)/"
check_no_fatal "CJ: VOICE Teaser page (1 VOICE already exists)" "$VOICE_TEASER_PATH"
if ! grep -qF "お客様の声" "$BODY_FILE" || ! grep -qF "大変助かりました。" "$BODY_FILE"; then
	echo "FAIL [CJ]: VOICE Teaser did not render heading+content with an existing VOICE"
	exit 1
fi

wp_cli post delete "$CASE_A" "$VOICE_A" "$RESULT_A" --force > /dev/null
check_no_fatal "CJ: CASE Teaser page with zero CASEs" "$CASE_TEASER_PATH"
if grep -qF "対応事例" "$BODY_FILE"; then
	echo "FAIL [CJ]: CASE Teaser heading appeared even though there are zero CASEs (whole-section self-hide expected)"
	exit 1
fi
check_no_fatal "CJ: RESULTS Teaser page with zero RESULTS" "$RESULTS_TEASER_PATH"
if grep -qF "実績" "$BODY_FILE"; then
	echo "FAIL [CJ]: RESULTS Teaser heading appeared even though there are zero RESULTS entries"
	exit 1
fi
check_no_fatal "CJ: VOICE Teaser page with zero VOICEs" "$VOICE_TEASER_PATH"
if grep -qF "お客様の声" "$BODY_FILE"; then
	echo "FAIL [CJ]: VOICE Teaser heading appeared even though there are zero VOICEs"
	exit 1
fi
echo "OK   [CJ: all 3 HOME Teaser Dynamic Blocks self-hide at zero items, show heading+content once populated]"

echo "=== CK. CASE admin: 編集画面にMeta Boxが正しく表示される ==="
# CASE's edit screen is the Block Editor (show_in_rest: true); simulating
# its hybrid REST+classic-meta-box save flow via raw curl (no real browser
# JS) would not reflect how it actually saves, so — consistent with how
# FAQ/Price/Professional's own meta box *saving* is verified via PHPUnit's
# direct save_meta() calls, not a simulated form POST — this step only
# confirms real-HTTP wiring: the screen loads without Fatal and renders
# the expected Service checkbox.
CASE_SVC=$(wp_cli post create --post_type=astrea_service --post_title="スモーク010業務" --post_status=publish --porcelain)
CASE_B=$(wp_cli post create --post_type=astrea_case --post_title="スモーク010事例B" --post_status=publish --porcelain)
# check_no_fatal() sends no cookies (it's meant for public pages); this is
# an authenticated wp-admin URL, so fetch it directly with the logged-in
# session instead — matching how every other admin-screen check in this
# file (ADMIN_HTML=$(curl ... -b "$COOKIE_JAR" ...)) already does it.
CASE_EDIT_STATUS=$(curl -s -b "$COOKIE_JAR" -o "$BODY_FILE" -w '%{http_code}' "$SITE_URL/wp-admin/post.php?post=$CASE_B&action=edit")
if [ "$CASE_EDIT_STATUS" != "200" ]; then
	echo "FAIL [CK]: expected HTTP 200 for the CASE edit screen, got $CASE_EDIT_STATUS"
	exit 1
fi
if ! grep -qF "astrea_case_meta_nonce" "$BODY_FILE"; then
	echo "FAIL [CK]: CASE meta box Nonce field not found on the edit screen"
	exit 1
fi
if ! grep -qF "スモーク010業務" "$BODY_FILE"; then
	echo "FAIL [CK]: CASE meta box did not list the available Service"
	exit 1
fi
echo "OK   [CK: CASE edit screen loads with the related-Service meta box wired up]"

echo "=== CL. Core deactivated: CASE/RESULTS/VOICE Dynamic display disappears, Theme stays safe ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "CL: CASE archive while Core inactive" "/cases/"
fetch_no_fatal_any_status "CL: VOICE archive while Core inactive" "/voices/"
fetch_no_fatal_any_status "CL: CASE Teaser page while Core inactive" "$CASE_TEASER_PATH"
if grep -qF "対応事例" "$BODY_FILE"; then
	echo "FAIL [CL]: CASE Teaser leaked content while Core is inactive"
	exit 1
fi
wp_cli plugin activate astrea-core
check_no_fatal "CL: CASE archive after reactivation"
echo "OK   [CL: Core deactivate/reactivate leaves Theme safe for CASE/RESULTS/VOICE]"

echo "=== CM. Core complete data deletion covers CASE/RESULTS/VOICE, Media survives ==="
CASE_C=$(wp_cli post create --post_type=astrea_case --post_title="削除確認用事例" --post_status=publish --porcelain)
CASE_C_ATTACHMENT=$(wp_cli media import /var/www/html/wp-includes/images/w-logo-blue.png --porcelain)
wp_cli post meta update "$CASE_C" _thumbnail_id "$CASE_C_ATTACHMENT"
RESULT_B=$(wp_cli post create --post_type=astrea_result --post_title="削除確認用実績" --post_status=publish --porcelain)
VOICE_B=$(wp_cli post create --post_type=astrea_voice --post_title="削除確認用声" --post_status=publish --porcelain)

DEL_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core-data-deletion")
DEL_NONCE=$(sed -n 's/.*name="astrea_delete_all_core_data_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$DEL_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null -X POST "$SITE_URL/wp-admin/admin-post.php" \
	--data-urlencode "action=astrea_delete_all_core_data" \
	--data-urlencode "astrea_delete_all_core_data_nonce=$DEL_NONCE" \
	--data-urlencode "confirm_understood=1" \
	--data-urlencode "confirm_phrase=削除する"

for id in "$CASE_C" "$RESULT_B" "$VOICE_B"; do
	STATUS=$(wp_cli post get "$id" --field=post_status 2>/dev/null || echo "gone")
	if [ "$STATUS" != "gone" ]; then
		echo "FAIL [CM]: post $id (CASE/RESULTS/VOICE) survived complete data deletion"
		exit 1
	fi
done
ATTACHMENT_STATUS=$(wp_cli post get "$CASE_C_ATTACHMENT" --field=post_status 2>/dev/null || echo "gone")
if [ "$ATTACHMENT_STATUS" = "gone" ]; then
	echo "FAIL [CM]: CASE Featured Image attachment was deleted by complete data deletion"
	exit 1
fi
echo "OK   [CM: complete data deletion removes CASE/RESULTS/VOICE, Media survives]"

echo "=== Cleanup: remove Construction Order 010 smoke-test fixtures ==="
wp_cli post delete "$CASE_B" "$CASE_SVC" "$CASE_TEASER_PAGE" "$RESULTS_TEASER_PAGE" "$VOICE_TEASER_PAGE" "$CASE_ATTACHMENT" "$CASE_C_ATTACHMENT" --force > /dev/null 2>&1 || true
rm -f "$COOKIE_JAR"

echo "All ASTREA CASE / RESULTS / VOICE end-to-end checks passed."

# Part 12 (CN-CU) automates Construction Order 011 — see the file docblock
# above for the full list of what this covers.

COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null

echo "=== CN. Professional Archive: 空metaで空<p></p>を残さず、値がある場合のみ表示される ==="
PROF_EMPTY=$(wp_cli post create --post_type=astrea_professional --post_title="スモーク011空専門家" --post_status=publish --porcelain)
PROF_FULL=$(wp_cli post create --post_type=astrea_professional --post_title="スモーク011専門家" --post_status=publish --porcelain)
wp_cli post meta update "$PROF_FULL" astrea_professional_qualification "行政書士"
wp_cli post meta update "$PROF_FULL" astrea_professional_career "10年の実務経験"
wp_cli post meta update "$PROF_FULL" astrea_professional_education "○○大学卒業"
wp_cli post meta update "$PROF_FULL" astrea_professional_affiliation "○○士会"
wp_cli post meta update "$PROF_FULL" astrea_professional_registration_info "登録番号：第1号"
check_no_fatal "CN: Professional archive with an empty-meta and a full-meta profile" "/professionals/"
if grep -qF "<p></p>" "$BODY_FILE"; then
	echo "FAIL [CN]: an empty <p></p> is still present on the Professional Archive"
	exit 1
fi
if ! grep -qF "行政書士" "$BODY_FILE"; then
	echo "FAIL [CN]: qualification did not render for the fully-populated profile"
	exit 1
fi
echo "OK   [CN: Professional Archive never leaves a blank <p></p>, shows qualification when present]"

echo "=== CO. Professional Single: 全項目が表示順どおり表示され、空項目のProfileは空見出しを残さない ==="
PROF_FULL_SLUG=$(wp_cli post get "$PROF_FULL" --field=post_name)
check_no_fatal "CO: Professional Single (full data)" "/professionals/$PROF_FULL_SLUG/"
for expected in "行政書士" "10年の実務経験" "○○大学卒業" "○○士会" "登録番号：第1号" "経歴" "学歴" "所属" "登録情報"; do
	if ! grep -qF "$expected" "$BODY_FILE"; then
		echo "FAIL [CO]: expected '$expected' on the full-data Professional Single"
		exit 1
	fi
done
PROF_EMPTY_SLUG=$(wp_cli post get "$PROF_EMPTY" --field=post_name)
check_no_fatal "CO: Professional Single (empty optional fields)" "/professionals/$PROF_EMPTY_SLUG/"
for absent in "経歴" "学歴" "所属" "登録情報"; do
	if grep -qF "$absent" "$BODY_FILE"; then
		echo "FAIL [CO]: '$absent' must not appear when the underlying field is empty (no label-only sections)"
		exit 1
	fi
done
# Matched as a rendered element's class attribute, not a bare substring:
# 015D added `.wp-block-astrea-professional-field` styling to theme.json's
# always-present global CSS (Theme-owned, unrelated to whether this
# specific field actually rendered), so a plain substring match here would
# false-positive on that CSS selector text exactly like Part AU/BB's
# Breadcrumb CSS did.
if grep -qF 'class="wp-block-astrea-professional-field"' "$BODY_FILE"; then
	echo "FAIL [CO]: 'wp-block-astrea-professional-field' must not appear when the underlying field is empty (no label-only sections)"
	exit 1
fi
echo "OK   [CO: Professional Single shows every populated field, never a blank labelled section]"

echo "=== CP. astrea/office-hours + astrea/office-sns: 0件で完全非表示、設定後に表示される ==="
OFFICE_BLOCKS_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke011 Office Blocks" --post_status=publish --post_content='<!-- wp:astrea/office-hours {"heading":"営業時間"} /--><!-- wp:astrea/office-sns {"heading":"SNS"} /-->' --porcelain)
OFFICE_BLOCKS_PATH="/$(wp_cli post get "$OFFICE_BLOCKS_PAGE" --field=post_name)/"
check_no_fatal "CP: Office Hours/SNS page with nothing configured" "$OFFICE_BLOCKS_PATH"
if grep -qF "営業時間" "$BODY_FILE" || grep -qF "SNS" "$BODY_FILE"; then
	echo "FAIL [CP]: heading appeared even though no business hours/SNS links are configured"
	exit 1
fi
wp_cli eval '
	$profile = \Astrea\Core\OfficeProfile\get_office_profile();
	$profile["business_hours"]["weekly"]["mon"] = array( "closed" => false, "open" => "09:00", "close" => "18:00" );
	$profile["sns_links"] = array( array( "label" => "X", "url" => "https://x.com/example" ) );
	update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $profile );
'
check_no_fatal "CP: Office Hours/SNS page once configured" "$OFFICE_BLOCKS_PATH"
if ! grep -qF "月曜日" "$BODY_FILE" || ! grep -qF "09:00" "$BODY_FILE"; then
	echo "FAIL [CP]: configured business hours did not render"
	exit 1
fi
if ! grep -qF 'href="https://x.com/example"' "$BODY_FILE"; then
	echo "FAIL [CP]: configured SNS link did not render"
	exit 1
fi
echo "OK   [CP: astrea/office-hours and astrea/office-sns self-hide at zero config, render once configured]"

echo "=== CQ. HOME: H1がちょうど1個、取扱業務Teaserが0/1件で正しく自己非表示・表示される ==="
wp_cli option update page_on_front 0
wp_cli option update show_on_front posts
wp_cli eval 'delete_option( "astrea_core_generated_pages" );'
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
HOME_NONCE=$(sed -n 's/.*name="astrea_setup_generate_home_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_home&astrea_setup_generate_home_nonce=$HOME_NONCE"
SMOKE011_HOME_PAGE=$(wp_cli option get page_on_front)
check_no_fatal "CQ: Home after HOME assembly (zero Services)"
H1_COUNT=$(grep -o "<h1[ >]" "$BODY_FILE" | wc -l)
if [ "$H1_COUNT" != "1" ]; then
	echo "FAIL [CQ]: expected exactly one <h1> on HOME, found $H1_COUNT"
	exit 1
fi
if grep -qF "取扱業務" "$BODY_FILE"; then
	echo "FAIL [CQ]: Services Teaser heading appeared on HOME with zero Services (Decision 028 whole-section self-hide)"
	exit 1
fi
SMOKE011_SVC=$(wp_cli post create --post_type=astrea_service --post_title="スモーク011業務" --post_status=publish --post_content="スモーク011業務の説明です。" --porcelain)
check_no_fatal "CQ: Home after HOME assembly (one Service)"
if ! grep -qF "取扱業務" "$BODY_FILE" || ! grep -qF "スモーク011業務" "$BODY_FILE"; then
	echo "FAIL [CQ]: Services Teaser did not render heading+content once a Service exists"
	exit 1
fi
echo "OK   [CQ: HOME has exactly one H1, Services Teaser fully self-hides at zero items and renders once populated]"

echo "=== CR. VOICE Archive: 項目見出しがH2で出力される（H1からのレベル飛びが無い） ==="
SMOKE011_VOICE=$(wp_cli post create --post_type=astrea_voice --post_title="スモーク011の声" --post_status=publish --post_content="大変助かりました。" --porcelain)
check_no_fatal "CR: VOICE archive with one item" "/voices/"
if ! grep -qF '<h2 class="wp-block-post-title">スモーク011の声</h2>' "$BODY_FILE"; then
	echo "FAIL [CR]: VOICE archive item title did not render as an H2"
	exit 1
fi
echo "OK   [CR: VOICE Archive item title is an H2, matching every other Archive]"

echo "=== CS. astrea_price / astrea_result: REST APIから匿名で読み取れない ==="
REST_PRICE_STATUS=$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/wp-json/wp/v2/astrea_price")
if [ "$REST_PRICE_STATUS" = "200" ]; then
	echo "FAIL [CS]: /wp-json/wp/v2/astrea_price is anonymously readable — Security Audit MEDIUM finding not fixed"
	exit 1
fi
REST_RESULT_STATUS=$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/wp-json/wp/v2/astrea_result")
if [ "$REST_RESULT_STATUS" = "200" ]; then
	echo "FAIL [CS]: /wp-json/wp/v2/astrea_result is anonymously readable — Security Audit MEDIUM finding not fixed"
	exit 1
fi
echo "OK   [CS: astrea_price/astrea_result are not exposed via the REST API]"

echo "=== CT. Contact Form: 送信ButtonがStyle Variationのボタン装飾を継承するclassを持つ ==="
CONTACT_PAGE=$(wp_cli post create --post_type=page --post_title="Smoke011 Contact" --post_status=publish --post_content='<!-- wp:astrea/contact-form /-->' --porcelain)
CONTACT_PATH="/$(wp_cli post get "$CONTACT_PAGE" --field=post_name)/"
check_no_fatal "CT: Contact Form page" "$CONTACT_PATH"
if ! grep -qE '<button type="submit" class="wp-element-button">' "$BODY_FILE"; then
	echo "FAIL [CT]: Contact Form submit button is missing the wp-element-button class"
	exit 1
fi
echo "OK   [CT: Contact Form submit button carries wp-element-button]"

echo "=== CU. Core deactivated: Professional Single / Office Blocks / Service List degrade safely ==="
wp_cli plugin deactivate astrea-core
fetch_no_fatal_any_status "CU: Professional Single while Core inactive" "/professionals/$PROF_FULL_SLUG/"
fetch_no_fatal_any_status "CU: Office Blocks page while Core inactive" "$OFFICE_BLOCKS_PATH"
fetch_no_fatal_any_status "CU: Home while Core inactive" "/"
wp_cli plugin activate astrea-core
check_no_fatal "CU: Professional Single after reactivation" "/professionals/$PROF_FULL_SLUG/"
if ! grep -qF "行政書士" "$BODY_FILE"; then
	echo "FAIL [CU]: Professional Single data not restored after Core reactivation"
	exit 1
fi
echo "OK   [CU: Core deactivate/reactivate leaves the new 011 features safe and restores data]"

echo "=== Cleanup: remove Construction Order 011 smoke-test fixtures ==="
wp_cli option update page_on_front 0
wp_cli option update show_on_front posts
wp_cli eval 'delete_option( "astrea_core_generated_pages" );'
STRAY_NAV_IDS=$(wp_cli post list --post_type=wp_navigation --field=ID)
wp_cli post delete "$PROF_EMPTY" "$PROF_FULL" "$OFFICE_BLOCKS_PAGE" "$SMOKE011_SVC" "$CONTACT_PAGE" "$SMOKE011_VOICE" "$SMOKE011_HOME_PAGE" $STRAY_NAV_IDS --force > /dev/null 2>&1 || true
rm -f "$COOKIE_JAR"

echo "All ASTREA Theme Display Completion / Security Hardening end-to-end checks passed."

# Part 13 (CV-DA) automates Construction Order 013 — see the file docblock
# above for the full list of what this covers.

COOKIE_JAR="$(mktemp)"
curl -s -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" > /dev/null
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$SITE_URL/wp-login.php" \
	--data-urlencode "log=admin" --data-urlencode "pwd=password" \
	--data-urlencode "wp-submit=Log In" --data-urlencode "redirect_to=$SITE_URL/wp-admin/" \
	-o /dev/null

echo "=== CV. Header: 事務所名Paragraphがmedium fontSizeへ縮小されている ==="
check_no_fatal "CV: Home (Header markup)"
if ! grep -qF 'has-medium-font-size' "$BODY_FILE"; then
	echo "FAIL [CV]: Header's office-name paragraph is not using the reduced medium fontSize"
	exit 1
fi
echo "OK   [CV: Header office-name uses medium fontSize]"

echo "=== CW. Navigation: 生成後、Frontend Header/FooterにPage List fallbackではなく実際のNavigation Linkが表示される ==="
wp_cli option update page_on_front 0
wp_cli option update show_on_front posts
wp_cli eval 'delete_option( "astrea_core_generated_pages" ); delete_option( "astrea_core_generated_navigation" ); delete_option( "astrea_core_generated_template_parts" );'
NAV_STRAY_IDS=$(wp_cli post list --post_type=wp_navigation,wp_template_part --field=ID)
if [ -n "$NAV_STRAY_IDS" ]; then wp_cli post delete $NAV_STRAY_IDS --force > /dev/null; fi

wp_cli eval 'update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, \Astrea\Core\OfficeProfile\sanitize( array( "office_name" => "スモーク013事務所" ) ) );'
CV_SVC=$(wp_cli post create --post_type=astrea_service --post_title="スモーク013業務" --post_status=publish --porcelain)
wp_cli eval '\Astrea\Core\Setup\generate_pages();'

ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
NAV_NONCE=$(sed -n 's/.*name="astrea_setup_generate_navigation_nonce" value="\([a-f0-9]*\)".*/\1/p' <<< "$ADMIN_HTML" | head -1)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_navigation&astrea_setup_generate_navigation_nonce=$NAV_NONCE"

check_no_fatal "CW: Home after Navigation generation"
if grep -qF 'wp-block-page-list' "$BODY_FILE"; then
	echo "FAIL [CW]: WordPress's own Page List fallback is still showing — 'a wp_navigation post was created' must never be mistaken for 'it is actually visible' (Construction 012 regression)"
	exit 1
fi
if ! grep -qF '取扱業務' "$BODY_FILE"; then
	echo "FAIL [CW]: Generated Navigation's real link did not appear on the front end Header/Footer"
	exit 1
fi
NAV_LINK_COUNT=$(grep -o '取扱業務' "$BODY_FILE" | wc -l)
if [ "$NAV_LINK_COUNT" -lt 2 ]; then
	echo "FAIL [CW]: expected the generated Navigation link in both Header and Footer, found $NAV_LINK_COUNT occurrence(s)"
	exit 1
fi
echo "OK   [CW: Generated Navigation is actually visible in both Header and Footer, no Page List fallback]"

echo "=== CX. Navigation: 再実行しても重複作成されない（冪等性） ==="
NAV_COUNT_BEFORE=$(wp_cli post list --post_type=wp_navigation --post_status=publish --format=count)
curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_navigation&astrea_setup_generate_navigation_nonce=$NAV_NONCE"
NAV_COUNT_AFTER=$(wp_cli post list --post_type=wp_navigation --post_status=publish --format=count)
if [ "$NAV_COUNT_BEFORE" != "$NAV_COUNT_AFTER" ]; then
	echo "FAIL [CX]: re-running Navigation generation changed the published wp_navigation count ($NAV_COUNT_BEFORE -> $NAV_COUNT_AFTER)"
	exit 1
fi
echo "OK   [CX: Navigation generation is idempotent]"

echo "=== CY. Navigation: ユーザーがカスタマイズ済みのHeaderは上書きされず、Footerのみ接続される ==="
wp_cli option update page_on_front 0
wp_cli option update show_on_front posts
wp_cli eval 'delete_option( "astrea_core_generated_pages" ); delete_option( "astrea_core_generated_navigation" ); delete_option( "astrea_core_generated_template_parts" );'
NAV_STRAY_IDS=$(wp_cli post list --post_type=wp_navigation,wp_template_part --field=ID)
if [ -n "$NAV_STRAY_IDS" ]; then wp_cli post delete $NAV_STRAY_IDS --force > /dev/null; fi

CUSTOM_HEADER_ID=$(wp_cli eval '
	// wp_insert_post()'"'"'s tax_input is gated by current_user_can( assign_terms ),
	// which is false in a bare wp-cli eval context (no logged-in user) —
	// impersonate the admin so this fixture ends up with a real wp_theme
	// term, exactly like the authenticated admin-post.php flow
	// connect_navigation_to_template_part() actually runs under in production.
	$admin = get_user_by( "login", "admin" );
	if ( $admin ) {
		wp_set_current_user( $admin->ID );
	}
	$id = wp_insert_post( array(
		"post_type"    => "wp_template_part",
		"post_status"  => "publish",
		"post_name"    => "header",
		"post_title"   => "Header",
		"post_content" => "<!-- wp:paragraph --><p>スモーク013カスタムヘッダー</p><!-- /wp:paragraph -->\n<!-- wp:navigation {\"overlayMenu\":\"mobile\"} /-->",
		"tax_input"    => array( "wp_theme" => "astrea", "wp_template_part_area" => "header" ),
		"meta_input"   => array( "origin" => "theme" ),
	), true );
	echo is_wp_error( $id ) ? $id->get_error_message() : $id;
')
wp_cli eval '\Astrea\Core\Setup\generate_pages();'

curl -s -b "$COOKIE_JAR" -o /dev/null "$SITE_URL/wp-admin/admin-post.php?action=astrea_setup_generate_navigation&astrea_setup_generate_navigation_nonce=$NAV_NONCE"

check_no_fatal "CY: Home after Navigation generation with a customized Header"
if ! grep -qF 'スモーク013カスタムヘッダー' "$BODY_FILE"; then
	echo "FAIL [CY]: the site owner's own customized Header content was lost — must never be overwritten"
	exit 1
fi
HEADER_CONTENT_AFTER=$(wp_cli post get "$CUSTOM_HEADER_ID" --field=post_content)
if ! grep -qF 'スモーク013カスタムヘッダー' <<< "$HEADER_CONTENT_AFTER" || grep -qF '"ref"' <<< "$HEADER_CONTENT_AFTER"; then
	echo "FAIL [CY]: the customized Header's stored content was modified (a ref must never be injected into it)"
	exit 1
fi
if ! grep -qF '取扱業務' "$BODY_FILE"; then
	echo "FAIL [CY]: Footer (still untouched) should still have been connected to the real Navigation"
	exit 1
fi
echo "OK   [CY: customized Header protected verbatim, untouched Footer still connected]"

echo "=== CZ. Dynamic Block Editor script: Editor画面でのみ読み込まれ、公開ページには出力されない ==="
check_no_fatal "CZ: Home (front end, no editor script)"
if grep -qF 'astrea-core-editor-blocks' "$BODY_FILE"; then
	echo "FAIL [CZ]: the Editor-only script was enqueued on the public front end"
	exit 1
fi
# page_on_front is 0 at this point (CY's cleanup reset it) — target a page
# known to exist and contain an astrea/* block instead: the "about" page
# generate_pages() created earlier in this Part.
CZ_ABOUT_ID=$(wp_cli eval 'echo (int) ( get_option( "astrea_core_generated_pages", array() )["about"] ?? 0 );')
if [ "$CZ_ABOUT_ID" = "0" ]; then
	echo "FAIL [CZ]: no generated 事務所概要 page found to check the Editor against"
	exit 1
fi
EDITOR_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/post.php?post=$CZ_ABOUT_ID&action=edit")
if ! grep -qF 'editor-blocks.js' <<< "$EDITOR_HTML"; then
	echo "FAIL [CZ]: the Editor-only script was NOT enqueued on the Block Editor screen"
	exit 1
fi
echo "OK   [CZ: Editor-only Dynamic Block script loads only in the Editor, never on the front end]"

echo "=== DA. Setup Checklist: 「サイトのタイトルを設定する」項目が表示される ==="
ADMIN_HTML=$(curl -s -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=astrea-core")
if ! grep -qF 'サイトのタイトルを設定する' <<< "$ADMIN_HTML"; then
	echo "FAIL [DA]: Setup Checklist is missing the Site Title item"
	exit 1
fi
echo "OK   [DA: Setup Checklist shows the Site Title guidance item]"

echo "=== Cleanup: remove Construction Order 013 smoke-test fixtures ==="
wp_cli option update page_on_front 0
wp_cli option update show_on_front posts
wp_cli eval 'delete_option( "astrea_core_generated_pages" ); delete_option( "astrea_core_generated_navigation" ); delete_option( "astrea_core_generated_template_parts" );'
NAV_CLEANUP_IDS=$(wp_cli post list --post_type=wp_navigation,wp_template_part --field=ID)
wp_cli post delete "$CV_SVC" $NAV_CLEANUP_IDS --force > /dev/null 2>&1 || true
rm -f "$COOKIE_JAR"

echo "All ASTREA Release Quality Fixes end-to-end checks passed."
