# ASTREA Starter Pipeline — Repeatability Test

Construction 028 hardening artifact. Verifies that running the Starter
Site reproducibility pipeline against the **same** environment multiple
times, without resetting the database, converges to the same semantic
state instead of duplicating content — the property Construction 028 was
commissioned to guarantee after the Construction 027 Results-background
double-nesting bug.

**This test verifies the pipeline. It is not a substitute for the Starter
Site source of truth** (`scripts/build-content.php` etc.) — never treat a
passing test run as license to skip reading the actual scripts.

## Procedure

1. Build a genuinely fresh, disposable WordPress Playground environment
   (`download-and-install`), with ASTREA Theme/Core live-mounted and
   `/images` mounted from `docs/demo-assets/astrea-starter-site/images`.
   Disable cron/auto-updates for deterministic runs:
   `--define-bool DISABLE_WP_CRON true --define-bool AUTOMATIC_UPDATER_DISABLED true`.
2. Run the theme/core activation step once (see
   `scripts/theme-core-activation-blueprint.json`).
3. Trigger the 9-step content pipeline, in order, against the **same**
   running environment, three times in a row, without any database reset
   between runs:
   `cleanup.php → build-content.php → polish.php → permalinks.php →
   integrate-final-images.php → integrate-025b2-hero-and-cases.php →
   make-results-web-jpeg.php → integrate-025e1-results-background.php →
   fix-internal-link-portability.php`.
   (`build-content.php` is expected to print an `ABORT:` message and do
   nothing from RUN #2 onward — see Construction 028 report §Phase 3. That
   is the correct, intended behavior, not a test failure.)
4. After each run, execute `scripts/dev-snapshot.php` against the same
   environment and save its `/snapshot/out.json` output aside (e.g.
   `run1.json`, `run2.json`, `run3.json`).
5. Compare the semantic fields of all three snapshots (NOT a raw byte
   diff — `post_modified` timestamps and revision post IDs are expected to
   differ and must be ignored). At minimum compare: `post_type_counts`,
   `pages`, `professionals`, `cpt_titles`, `attachment_count`,
   `attachment_filenames`, `duplicate_filenames`, `results_wrapper_count`,
   `results_wrapper_max_depth`, `hero_wrapper_count`,
   `hero_wrapper_max_depth`, `home_button_count`, `home_cover_count`,
   `navigation_count`, `navigation_items`,
   `navigation_duplicate_labels`, `generated_pages`,
   `office_profile_office_name`, `identity_checks`, `stale_url_scan`.

## Pass criteria

- All compared fields are identical across RUN #1, #2, and #3.
- `duplicate_filenames` and `navigation_duplicate_labels` are empty in
  every run.
- `results_wrapper_count == 1` and `results_wrapper_max_depth == 0` in
  every run (no double-nesting).
- `hero_wrapper_count == 1` and `hero_wrapper_max_depth == 0` in every
  run.
- `identity_checks.old_yamada_count == 0` and
  `identity_checks.old_ibu_ifuo_count == 0`.
- `stale_url_scan` contains no entry for a host/port other than the
  current environment's own (the current environment's own IP/port
  legitimately appearing is correct, not a failure — see Construction 028
  report §Phase 9 for why).

## How Construction 028 triggered each run

There is no CLI flag to execute an arbitrary mounted PHP file against an
already-running `wp-playground-cli server` instance, and `server` mode did
not persist its SQLite database to the host disk in the CLI version used
for this Construction (contrary to earlier, unrelated sessions'
experience — re-verify this before relying on it). Construction 028 used
a small **test-only** mu-plugin (never part of the ASTREA product) that
hooks `wp_loaded` (not `init` — see the report for why `init` + `exit()`
silently breaks Core's own post-type registration for that request) and,
given a secret query var, `include`s one of the mounted pipeline scripts
and prints its output. This keeps the same running server across all
three runs, avoiding both the "does `server` mode actually persist to
disk" question and any database-reset risk.

This mu-plugin is a disposable test-harness artifact (kept outside this
repository, under the session's scratch directory) — not part of the
Starter Site or the ASTREA product, and is not required to reproduce this
test: any equivalent mechanism that executes the 9 scripts against a
continuously-running WordPress instance (a real WP-CLI `eval-file` against
a real server, for example) works the same way, provided each script's
`require_once '/wordpress/wp-load.php';` line is removed for that
execution style (WordPress is already bootstrapped by the harness) and
`/images/*` paths are mounted or copied to wherever the harness expects
them.
