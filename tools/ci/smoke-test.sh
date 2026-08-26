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
	local status

	status=$(curl -s -o "$BODY_FILE" -w "%{http_code}" "$SITE_URL/")

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
