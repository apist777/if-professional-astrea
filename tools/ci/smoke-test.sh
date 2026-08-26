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
if ! grep -q "$OFFICE_NAME" "$BODY_FILE"; then
	echo "FAIL [F: Theme display]: office_name not found in homepage output"
	exit 1
fi
echo "OK   [F: office_name rendered via Block Bindings]"

echo "=== G. Core deactivated: Theme still safe, no stale Office Profile leak ==="
wp_cli plugin deactivate astrea-core
check_no_fatal "G: Core deactivated (Office Profile)"
if grep -q "$OFFICE_NAME" "$BODY_FILE"; then
	echo "FAIL [G]: stale Office Profile value leaked while Core is inactive"
	exit 1
fi
echo "OK   [G: no stale leak while Core inactive]"

echo "=== H. Office Profile data retained while Core is deactivated ==="
# `wp option get --format=json` escapes non-ASCII (\uXXXX), so check the
# phone number (ASCII) rather than the Japanese office_name here.
STORED=$(wp_cli option get astrea_core_office_profile --format=json)
if ! echo "$STORED" | grep -q "03-1234-5678"; then
	echo "FAIL [H]: Office Profile option lost while Core was deactivated"
	exit 1
fi
echo "OK   [H: Office Profile data retained after deactivation]"

echo "=== I. Core reactivated: Office Profile display restored ==="
wp_cli plugin activate astrea-core
check_no_fatal "I: Core reactivated (Office Profile)"
if ! grep -q "$OFFICE_NAME" "$BODY_FILE"; then
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
ORDER=$(grep -oE "Alpha Smoke|Bravo Smoke|Charlie Smoke|Draft Smoke" "$BODY_FILE" | tr '\n' ',')
if [ "$ORDER" != "Alpha Smoke,Bravo Smoke,Charlie Smoke," ]; then
	echo "FAIL [K]: expected order 'Alpha Smoke,Bravo Smoke,Charlie Smoke,' (draft excluded), got '$ORDER'"
	exit 1
fi
echo "OK   [K: order is Alpha, Bravo (tie-break by title), Charlie; draft excluded]"

if ! grep -q "行政書士（スモークテスト）" "$BODY_FILE"; then
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
ORDER_AFTER=$(grep -oE "Alpha Smoke|Bravo Smoke|Charlie Smoke" "$BODY_FILE" | tr '\n' ',')
if [ "$ORDER_AFTER" != "Alpha Smoke,Bravo Smoke,Charlie Smoke," ]; then
	echo "FAIL [S]: expected restored order 'Alpha Smoke,Bravo Smoke,Charlie Smoke,', got '$ORDER_AFTER'"
	exit 1
fi
echo "OK   [S: display and order restored after reactivation]"

echo "=== Cleanup: remove smoke-test Professional Profile fixtures ==="
wp_cli post delete "$PROF_A" "$PROF_B" "$PROF_C" "$PROF_DRAFT" "$ATTACHMENT_ID" --force > /dev/null

echo "All ASTREA Professional Profile end-to-end checks passed."
