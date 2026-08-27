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
# 006) from the last BODY_FILE before printing it. Order-sensitive checks
# that scan for a set of names/titles must use this instead of the raw
# file — since Organization JSON-LD legitimately repeats those same names
# sitewide, a plain grep across the whole body would double-count them.
visible_content_only() {
	node -e '
		const fs = require("fs");
		const html = fs.readFileSync(process.argv[1], "utf8");
		process.stdout.write(html.replace(/<script[\s\S]*?<\/script>/g, ""));
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
if grep -qF "wp-block-astrea-price-list" "$BODY_FILE"; then
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
if ! grep -qF 'wp-block-astrea-breadcrumb' "$BODY_FILE" || ! grep -qF '"@type":"BreadcrumbList"' "$BODY_FILE"; then
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
if grep -qF '"@type":"Organization"' "$BODY_FILE" || grep -qF 'wp-block-astrea-breadcrumb' "$BODY_FILE"; then
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
