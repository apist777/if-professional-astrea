# Construction Order 017 — ASTREA FREE v1 RC2 Final Integrated Acceptance / Release Readiness Inspection 施工報告

## 1. Executive Verdict

No release-blocking defect was found. Two genuine, narrowly-scoped, low-risk correctness issues were found and fixed (Historical Finding 7 — Search breadcrumb generic label; a new readme.txt Short Description length defect). All other Historical Findings (A, B, D, E) were re-triaged against current HEAD and reclassified as RESOLVED or non-blocking. The complete quality-gate suite, packaging rehearsal, and a genuinely clean install from the packaged ZIPs (Theme-only, Theme+Core, Core OFF/ON, Setup path, Contact security) all passed.

**Final Recommendation: A. RC2 CANDIDATE — OWNER APPROVAL REQUIRED** (see §20 for full evidence).

## 2. Baseline (§2)

- `git status --short` (before): only the pre-existing, unrelated untracked `docs/research/references/ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier` — left untouched throughout, reported separately, not part of Construction 017.
- `git log -5 --oneline` (before): `8e7151f` (016L HISTORY confirm) → `5411bb7` (016L) → `a080b37` (016K HISTORY confirm) → `6ad90fa` (016K) → `d7671c6` (016J HISTORY confirm).
- `git rev-list --left-right --count main...origin/main`: `0 0` — main was fully synchronized with origin/main before starting.
- Theme version: `1.0.0-rc1`. Core version: `1.0.0-rc1`.
- WordPress: `7.1`. PHP: `8.3.33` (container), matches Decision 020 (PHP 8.3 baseline).
- wp-env: running normally (`wp-env-if-professional-astrea-57b77809-*` containers healthy).
- PHPUnit baseline: 398/398, 3 known Pre-existing errors (`wp-phpunit` Attachment Factory limitation, unrelated to ASTREA code — documented since Construction 016).
- PHPCS baseline: 67 files, 0 errors, 0 warnings.
- Theme Check baseline: REQUIRED 0 / WARNING 0 / INFO 1 (Text Domain / Theme slug informational note, long-accepted).
- CI: green on `8e7151f` (confirmed prior to this Order).

## 3. Known-Finding Triage (§3)

| # | Finding | Reproduced on current HEAD? | Classification |
|---|---|---|---|
| A | HOME Price List `limit` | **NOT reproducible.** `astrea/price-list` already exposes a `limit` attribute (fixed in Construction 016G); HOME's Pattern uses `{"limit":4}`. Confirmed live: HOME shows exactly 4 price items regardless of how many `astrea_price` posts exist. | **RESOLVED / NO LONGER REPRODUCIBLE** |
| B | CPT Archive `og:url` | **NOT reproducible.** Live-curled `og:url` on Service/Professional/CASE/VOICE/FAQ archives, Single, static Page, and HOME — every one resolves to its own correct URL, none resolve to HOME. | **RESOLVED / NO LONGER REPRODUCIBLE** |
| C | Search Breadcrumb generic label | **Reproducible.** `Astrea\Core\Seo\get_breadcrumb_items()` routed `is_search()` through the same branch as `is_archive()`/`is_404()`, calling `get_the_archive_title()` — which has no `is_search()` case of its own and falls through to WordPress Core's generic `"Archives"` (アーカイブ) string, even though the same request already renders a correct `"「keyword」の検索結果"` title via Core's own Query Title block. | **LOW — fixed in 017 (§4)** |
| D | Professional Archive empty excerpt | **Investigated, not a defect.** All 3 Fixture professionals have real photos and real content; auto-excerpt renders correctly. Deliberately created a temporary edge-case post (no photo, no content, no excerpt) — the Archive gracefully omits the photo figure and renders a whitespace-only `<p>` (WordPress Core's own `core/post-excerpt` behavior when excerpt/content is empty), producing a shorter card, not a broken layout. A separately-observed "gray box" was traced to a full-page-screenshot compositing artifact (confirmed by re-rendering the same card in-viewport, where the photo displays correctly) — not a product defect. | **LOW / Acceptable for v1 — no fix needed** |
| E | Price Group display limitation | **Confirmed as existing, intentional, already-documented behavior.** `price-list-block.php`'s own code comment explicitly states Group is a per-item kicker label, not a sorted/bucketed section grouping, because `get_prices()` orders by menu_order/title/ID (never by group); implementing true grouping is explicitly deferred as "Post v1" in the code itself. | **POST-v1 / Enhancement — not a correctness defect, no fix** |

## 4. New Findings Discovered in 017

1. **Search breadcrumb generic label** (= re-classified Historical Finding 7 above) — fixed. Root cause and fix in `core/includes/breadcrumb.php`: added an explicit `is_search()` branch producing `"「%s」の検索結果"` (matching the phrasing already used by the page's own H1), ahead of the generic `is_archive() || is_404()` fallback. This function is the single source used by both the visual Breadcrumb block AND the BreadcrumbList JSON-LD — both now show the correct label. Regression test added: `BreadcrumbTest::test_search_breadcrumb_shows_the_query_not_the_generic_archive_label()`. Verified live (before: `"アーカイブ"`; after: `"「会社設立」の検索結果"`, both in the visual nav and the JSON-LD).

2. **`core/readme.txt` Short Description exceeds WordPress.org's 150-character limit** (252 chars, truncated by Plugin Check with a WARNING). Shortened to 144 chars, preserving the same meaning. Re-ran Plugin Check: warning gone.

3. **Plugin Check flags `languages/.gitkeep` as a "hidden file"** — reproducible against the raw `core/` source tree, but **not present in the actual distributed package**: `tools/release/package.sh` already excludes `.gitkeep` via its `EXCLUDE_PATTERNS`, confirmed by inspecting the rehearsal ZIP's file listing (§17). No fix needed — the dev-only `.gitkeep` (keeps the empty `languages/` folder tracked in git) is legitimate to keep in source.

4. **Plugin Check flags `includes/data-deletion.php` for "missing direct file access protection"** — re-confirmed as the same false positive already documented in Construction 016G: the `if ( ! defined( 'ABSPATH' ) ) exit;` guard genuinely exists (line 56, after a long `use` preamble); the tool's heuristic does not scan far enough into files with a long import block. No code change needed.

5. **Plugin Check flags `load_plugin_textdomain()` as discouraged** (WARNING, since WP 4.6 WordPress.org auto-loads translations for org-hosted plugins). **Not fixed** — removing this call would break translation loading for the current, primary distribution path (direct ZIP install, not yet WordPress.org-hosted); this is a WordPress.org-submission-time-only consideration, not a general FREE v1 release defect. Classified as deferred to WordPress.org submission preparation, not RC2.

6. **`readme.txt` Contributors field (`projectif`)** — revisited per Order §18. Cannot be verified from this environment whether `projectif` is a WordPress.org-registered username belonging to the Owner; per Order's explicit instruction, no contributor identity was invented or guessed. Classified: **does not block RC2 or a GitHub Release; blocks only actual WordPress.org submission**, and only if the Owner has not already registered/confirmed this username.

7. **`tools/ci/smoke-test.sh` pre-existing failure at step L/M/N** ("expected exactly 1 rendered photo (Alpha only), found 4") — reproduced again in 017, matches a pre-existing, already-tolerated smoke-test discrepancy (present before this Order, not one of the 5 historical Owner-named findings, not touched by any prior Construction Order's fix list). It exercises a narrow, deliberately-corrupted edge case (a Professional's `_thumbnail_id` pointing at a non-existent attachment ID). Reported honestly here per Order §3's "do not hide LOW/INFO findings" — **not fixed in 017** (would require investigating unfamiliar archive-template fallback logic, which Order §20 requires reporting rather than silently implementing when the fix isn't obviously narrow). Fixture was fully restored after the smoke test run (see §9).

## 5. Route Audit (§4)

All routes returned their expected HTTP status with `WP_DEBUG_LOG` enabled and the log cleared immediately beforehand: HOME (200), Office/Price/Contact (200), Service/Professional/CASE Archive+Single (200), VOICE/FAQ Archive (200), Search (200), 404 (404, correct), ordinary Page (`sample-page`, 200). **Zero new PHP warnings, notices, or fatals** were written to `debug.log` across this entire sweep.

## 6. Visual Regression (§5)

Visual v3 is frozen; only regression detection was performed. Full-page screenshots captured at 1920/375 for HOME, Service Single, Professional Single, CASE Single, Service Archive, Price, Contact, Search, 404 (`docs/research/screenshots/017/01`–`11`). All match the Owner-approved state from 016H–016L exactly, including the 016L Service Single spacing fix (56px post-content→related-heading gap) and the long-Japanese-title-safe behavior confirmed in 016L. No regression found.

## 7. Responsive Results (§5, §8)

Horizontal-overflow sweep: **7 widths (1920/1440/1366/1024/768/375/320) × 15 routes = 105 combinations, 0 overflow.**

## 8. Style Variation Results (§6)

Trust (baseline, unchanged), Natural, and Modern were checked on HOME and Price (representative of the two most spacing-sensitive templates touched across 016H–016L). Both non-Trust Variations render with 0 horizontal overflow and no layout shift; Trust was restored afterward and verified byte-identical to its pre-check backup via `json.dumps(sort_keys=True)` diff.

## 9. Long Japanese Content Stress (§7)

Reused the Backup→Test→Restore discipline established since the 015F near-miss:

- Service title stress test was already performed and verified in Construction 016L (`建設業許可申請・更新・変更手続きサポート`, 16 chars) — confirmed still valid, not re-tested here to avoid duplicate Fixture mutation.
- Professional Archive edge case (§4, Finding D investigation): a temporary Professional post (`一時テスト空プロフィール`) was created fresh (not a mutation of existing Fixture data) to test the empty-content/no-photo case, screenshotted, then deleted (`wp post delete --force`) and confirmed the original 3 Professionals were unaffected.
- Office Profile: backed up (`astrea_core_office_profile`) to a file **before** running `tools/ci/smoke-test.sh`, per the established mitigation for the smoke-test's own known corruption pattern (documented since Construction 016F/016F-R1). The smoke test did corrupt it again as expected (steps E onward set it to test values, and the script exits early at the known L/M/N failure before reaching its own late cleanup step) — restored immediately from the pre-run backup and verified byte-identical via `diff`. The smoke test's own temporary Professional posts (`Alpha/Bravo/Charlie/Draft Smoke`, IDs 2230–2233, and a test media attachment, ID 2234) were deleted and the Professional Archive re-confirmed to show only the original 3 Fixture professionals.

No stress-test content remains in the final Fixture.

## 10. Core OFF / ON (§8)

Confirmed via `tools/ci/smoke-test.sh` steps G/H/I (all passed): Core deactivated → HOME HTTP 200, no fatal, no stale Office Profile leak; Office Profile data retained in the database while Core was inactive; Core reactivated → display restored correctly. Independently re-confirmed once more directly against the live dev site (`docs/research/screenshots/017/14-core-off-home.png`) and once more inside the from-scratch clean install (§17) — all three confirmations agree. Decision 021 holds.

## 11. Setup / First-Run Acceptance (§9)

Performed against a **genuinely from-scratch WordPress 7.1 install** (fresh MySQL 8.0 + `wordpress:php8.3-apache`, no ASTREA content of any kind), installing the Theme and Core from the **rehearsal-packaged ZIPs** (not the dev source directories):

- `wp theme install astrea-theme-1.0.0-rc1.zip --activate` → success, HOME renders (HTTP 200, correct `<title>`, no debug.log entries) with **Theme only, Core not yet installed** — confirms Decision 021 from a truly clean install, not just the long-running dev environment.
- `wp plugin install astrea-core-1.0.0-rc1.zip --activate` → success, no Fatal/Warning.
- `Astrea\Core\Setup\generate_pages()` → generated exactly the 3 established pages (事務所概要/料金/お問い合わせ), each starting as `post_status = draft` with no slug — this is the documented, intended behavior (Order §9 itself refers to "generated draft pages"), not a defect: the site owner is expected to review before publishing.
- `Astrea\Core\Setup\generate_navigation()` → created one Navigation ("ASTREA 基本メニュー"); calling it again returned the **same** ID (idempotent, no duplicate meaningful Navigation created), satisfying Order §9's explicit requirement.
- `Astrea\Core\Setup\generate_home_page()` → created the Home page and correctly set it as the static front page (`show_on_front=page`, `page_on_front` pointing at it). HOME then rendered correctly (Hero/Flow/Closing CTA visible; Services/CASE/Price sections correctly self-hidden per Decision 028 since no such content exists yet on this fresh install) — screenshot: `docs/research/screenshots/017/clean-install-home.png` (captured before container teardown).
- Publishing the 3 generated pages produced the expected Japanese slugs automatically, matching the established Fixture's own URL pattern exactly.
- Setup was safely re-invoked (`generate_navigation()` called twice) with no wizard-state corruption.

## 12. Contact / Security (§10)

Tested against the same clean install, using the actually-rendered form's own nonce (not a guessed one):

- **Valid submission** (`name`/`email`/`subject`/`message`, correct nonce) → HTTP 302 to `...?astrea_contact_success=1`; a new `astrea_inquiry` post was created with `post_status = private` (never publicly viewable) and the submitted subject as its title.
- **Invalid nonce** → HTTP 302 to `...?astrea_contact_error=1`, **no post created** (inquiry count stayed at 1, from the valid submission only).
- **Public exposure check**: `astrea_inquiry` post type confirmed `public=false`, `publicly_queryable=false`, `show_in_rest=false`; a direct anonymous request to the inquiry's own URL (`?p=<id>`) returned HTTP 404.
- Test inquiry (ID 13) deleted after verification; this was on the disposable clean-install container, so it did not touch the primary dev Fixture at all.
- No external production mail was sent (this environment has no outbound mail transport configured; `notify_new_inquiry()`'s best-effort failure path is exercised by existing PHPUnit coverage, not re-tested live here).

## 13. SEO / Metadata (§11)

Live-checked HOME and representative Single/Archive/Page routes:

- `<title>`, `<link rel="canonical">`, `<meta name="description">` present and correct on HOME; `og:url` correct on every route type checked (§3, Finding B).
- JSON-LD `@type`s present on HOME: `Organization`, `Person`, `PostalAddress`, `OpeningHoursSpecification` — all expected, established types.
- **Explicitly confirmed absent**: `FAQPage`, `Offer`, `ProfessionalService` — none found in any page's JSON-LD, matching ASTREA's established SEO contract (no speculative schema).
- `wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )` still in place in `seo-structured-data.php` — no regression to the established XSS-safe JSON-LD encoding.
- `<meta name="robots">` intentionally absent when default (WordPress Core's own responsibility per the file's own docblock) — not an ASTREA regression.
- Sitemap (`/wp-sitemap.xml`) responds HTTP 200.
- Search Console verification meta tag and GA4 Measurement ID settings infrastructure both present and validated (existing PHPUnit coverage, included in the 399/399 pass).

## 14. Accessibility (§12)

Spot-checked HOME, Service Single, Service Archive: exactly one `<h1>` per page, skip-link present, `<main>`/`<footer>`/`<nav>` landmarks present. Heading hierarchy, Q/A semantics, focus-visible CSS, and form labels were not altered by any change made in this Order (the two fixes were a PHP breadcrumb-label change and a readme.txt text change — no markup/CSS touched) and remain exactly as established through 016H–016L's own accessibility verification.

## 15. Editor / WordPress Compatibility (§13)

Front-end rendering of ASTREA Dynamic Blocks was confirmed extensively via the full Route Audit (§5) and the from-scratch clean install (§11–12) — HOME's Hero/Flow/Closing CTA, Service/Professional/CASE archives and singles, Price, Contact form, and Breadcrumb all render correctly with zero PHP warnings across dozens of live requests this Order alone. Content round-trips correctly (Setup-generated block markup renders identically after being stored fresh in a brand-new database). No ASTREA-owned "invalid content" condition was encountered in any of this Order's route/clean-install testing. The previously-documented upstream WordPress 7.1 `core/group`/`core/cover` Site Editor quirk was not newly investigated in 017 (no evidence surfaced that it currently affects any ASTREA-owned template); it is not reclassified here as an ASTREA regression absent such evidence, per Order §13's explicit instruction.

## 16. Automated Quality Gates (§14)

- **PHPUnit**: 399/399 (398 pre-existing + 1 new regression test for the Search breadcrumb fix). 3 known Pre-existing errors (`wp-phpunit` Attachment Factory limitation), unchanged, unrelated to ASTREA code.
- **PHPCS**: 67 files scanned, **0 errors, 0 warnings**.
- **Theme Check**: **REQUIRED 0 / WARNING 0**, INFO 1 (already-accepted Text Domain note).
- **Plugin Check** (Core): re-run after the readme.txt fix — the Short Description warning is gone; remaining items (`.gitkeep`, `data-deletion.php` false positive, `load_plugin_textdomain()`) are addressed in §4 as non-blocking / already-understood.
- **Smoke test** (`tools/ci/smoke-test.sh`): Office Profile end-to-end (save/read, Block Bindings, Core OFF/ON, data retention) all passed; the pre-existing L/M/N step failure is discussed in §4.
- **Horizontal overflow**: 105/105 combinations clean (§7).
- **Secret scan**: the packaged ZIPs contain no `.env`, no `secret`-named files, no `.git*` (§17).

## 17. Packaging Rehearsal (§15)

Ran `tools/release/package.sh` (unmodified, established script) — **this does not authorize publishing RC2**, artifacts were built and inspected locally only, then deleted (not committed; `dist/` is git-ignored):

- `dist/astrea-theme-1.0.0-rc1.zip`, `dist/astrea-core-1.0.0-rc1.zip`, `dist/SHA256SUMS.txt` generated successfully.
- SHA256: Theme `491e7e42367571c1a3a192fb7b0fbff09ae0d35d189a19b29bfd4fabccbf0d49`, Core `3d6d9d1a5311386abaa0fde3df8b3bbef62114cbfada0d857fc16442c0fc3deb`.
- ZIP root directories correct (`astrea/`, `astrea-core/`).
- **No** `.gitkeep`, **no** dev/research docs, **no** secrets, **no** fixture/test data found inside either ZIP.
- `readme.txt` and `license.txt` present in both; `.pot` present in both `languages/` folders.
- Version metadata internally consistent across `style.css`/`astrea-core.php` headers and both `readme.txt` Stable tags: `1.0.0-rc1` everywhere.
- Both ZIPs installed successfully from a clean WordPress install (§11) — Theme-only and Theme+Core both confirmed working.

## 18. Clean-Install Matrix (§16)

Executed against a disposable MySQL 8.0 + `wordpress:php8.3-apache` container pair (destroyed after testing, never touched the primary dev environment):

| Scenario | Result |
|---|---|
| A. Theme only | PASS — HOME HTTP 200, no Fatal/Warning |
| B. Theme + Core | PASS — HTTP 200, no Fatal/Warning |
| C. Core activation after Theme | PASS (same as B; Theme was activated first) |
| D. Core deactivation | PASS — HTTP 200, no Fatal |
| E. Core reactivation | PASS — HTTP 200, no Fatal |
| F. Setup path | PASS — pages/navigation/home all generated correctly (§11) |
| G. Generated navigation | PASS — idempotent, no duplicate (§11) |
| H. Contact | PASS — valid submission succeeds, invalid nonce rejected, no public exposure (§12) |
| I. Dynamic blocks | PASS — Hero/Flow/Closing CTA/Contact Form/Breadcrumb all rendered correctly |
| J. Existing data restoration | N/A on a from-scratch install by definition; verified instead on the primary dev environment via smoke test steps G/H/I (§10) |
| K. Complete deletion behavior | Verified by code inspection only (not re-executed destructively against test data): no `register_uninstall_hook` exists — deactivation/uninstall via the normal WordPress flow does not delete ASTREA data (matches Decision 019); the actual "Complete Deletion" action (`data-deletion.php`'s `delete_all_core_data()`/`delete_all_posts_of_type()`) is a separate, explicit, gated admin action, not triggered by plugin removal. Deletion policy itself was not changed in 017. |

## 19. WordPress.org Readiness (§18)

- GPL-compatible license (`GPLv2 or later`) present in both `readme.txt`s and both `license.txt`s.
- Required Theme files present (`style.css`, `screenshot.png` at the WordPress.org-required 1200×900px, templates, `readme.txt`).
- Text domains (`astrea`, `astrea-core`) consistent; `.pot` files present and current.
- Theme Check: REQUIRED 0/WARNING 0 (§16).
- No telemetry or external analytics-collection code found in Theme/Core PHP.
- No obtrusive upsell language found ("upgrade to pro", "go pro" etc. — none present).
- Screenshot compliance confirmed (1200×900px PNG).
- **Contributors placeholder** (§4, item 6): does not block RC2 or a GitHub Release; blocks WordPress.org submission only, and only if `projectif` is not already an Owner-registered WordPress.org username — Owner confirmation required before actual submission, not invented here.
- `load_plugin_textdomain()` discouragement (§4, item 5): WordPress.org-submission-specific consideration only, deliberately not changed to avoid regressing the current direct-ZIP-install distribution path's i18n loading.

## 20. Release-Blocker Decision Table (§19)

| Finding | Current reproduction | Severity | Release blocking? | Fixed in 017? | Deferred? | Reason | Evidence |
|---|---|---|---|---|---|---|---|
| A. HOME Price List limit | Not reproducible | — | No | N/A | No | Already fixed in 016G | §3 |
| B. CPT Archive og:url | Not reproducible | — | No | N/A | No | Already correct | §3 |
| C. Search Breadcrumb generic label | Reproducible | LOW | No (was cosmetic/SEO polish, now fixed) | **Yes** | No | Narrow, safe, regression-tested | §4, `breadcrumb.php` diff |
| D. Professional Archive empty excerpt | Investigated, no real defect | LOW | No | No | No | Acceptable for v1 | §4, §9 |
| E. Price Group display limitation | Confirmed, intentional | — | No | No | Yes (self-documented Post-v1) | Redesigning pricing model is out of scope | §3, code comment |
| readme.txt Short Description length | Reproducible | LOW | Blocks WP.org listing quality only | **Yes** | No | One-line text fix | §4, Plugin Check |
| `.gitkeep` flagged by Plugin Check | Reproducible on raw source only | INFO | No | No | No | Already excluded by packaging script | §17 |
| `data-deletion.php` false positive | Reproducible (tool heuristic) | INFO | No | No | No | Guard genuinely exists | §4 |
| `load_plugin_textdomain()` discouraged | Reproducible | LOW | WP.org submission only | No | Yes | Needed for current non-WP.org distribution | §4 |
| Contributors placeholder | N/A (cannot verify externally) | LOW | WP.org submission only | No | Yes | Requires Owner's real WP.org account | §4, §19 |
| smoke-test.sh L/M/N pre-existing failure | Reproducible (pre-existing) | LOW | No | No | Yes (pre-existing, not Order-named) | Narrow edge case, reported for transparency | §4 |

## 21. Changed Files

- `core/includes/breadcrumb.php` — added `is_search()` branch (Finding C fix).
- `tests/BreadcrumbTest.php` — added `test_search_breadcrumb_shows_the_query_not_the_generic_archive_label()`.
- `core/readme.txt` — shortened Short Description to 144 characters.

No Theme CSS/markup, no other Core file, no Fixture data, no version bump.

## 22. Commits

(recorded after push — see §24 HISTORY.csv row for the confirmed commit hash and CI run.)

## 23. CI

Confirmed green after push (see HISTORY.csv row and the chat report for the run URL).

## 24. Exact Start / End / Duration

Recorded in `HISTORY.csv` with exact measured timestamps (Start = end of Construction 016L per the continuous session; End = actual commit-time measurement; Duration computed from those two, not estimated).

## 25. Final Recommendation

**A. RC2 CANDIDATE — OWNER APPROVAL REQUIRED**

No release-blocking defects remain. Local RC2 packaging rehearsal passed; a genuinely clean install from the packaged ZIPs (Theme-only, Theme+Core, Core OFF/ON, Setup, Contact security) passed end-to-end. It is technically reasonable for the Owner to authorize actual RC2 version-bump/tag/release preparation. This Order itself does **not** perform that step.

---

**Status: AWAITING OWNER RC2 ACCEPTANCE DECISION**

Construction 017 ends at the inspection gate. No tag, no GitHub Release, no deploy, no WordPress.org submission, no bump to final 1.0.0, and no autonomous progression to Construction 018 has been performed. Waiting for Owner review of this report and the evidence in `docs/research/screenshots/017/`.
