# Construction Order 022 — ASTREA WordPress.org Submission Readiness / Final Preflight

Date: 2026-09-06
Actor: Chloe (Claude), acting as an unfamiliar WordPress.org Theme reviewer
Mode: Final Preflight / Audit / Fix-if-Required — **no submission performed**
Status at close: see §45 Final Verdict

## 1. Executive Verdict

**C. ASTREA WORDPRESS.ORG SUBMISSION HOLD — OWNER DECISION REQUIRED**

Three narrow, objective, safe defects were found and fixed, shipped as **ASTREA 1.0.2 (Theme only; Core unchanged at 1.0.1)**. Beyond those, this audit surfaced **two material findings that this Order is not authorized to resolve unilaterally**, both squarely matching this Order's own Stop Conditions (§51):

1. **Name/slug risk (§7).** "ASTREA" is phonetically and visually close to **"Astra"** — one of the most dominant themes in the entire WordPress ecosystem (1,000,000+ active installs). The Theme Review Handbook explicitly reserves discretion to reject a name "too similar to an existing theme or brand." The exact slug `astrea` is currently unregistered, but that does not remove the discretionary rejection risk.
2. **Core-recommendation compliance (§16).** ASTREA Theme's `functions.php` displays an admin notice that names and recommends "ASTREA Core" by name. ASTREA Core is not published on the WordPress.org Plugin Directory. The Handbook states plainly: *"Themes may only recommend plugins that are hosted on WordPress.org."* Resolving this requires either publishing Core to the Plugin Directory first, or modifying/removing the Theme's own recommendation notice — both are product/business decisions outside this Order's fix-if-required scope.

`v1.0.0`, `v1.0.0-rc2`, and `v1.0.1` remain completely untouched and immutable throughout this Order.

## 2. Exact Artifact Identity

```
Source: v1.0.1 GitHub Release (anonymous download, pre-fix baseline)
File:    astrea-theme-1.0.1.zip
Bytes:   161,979
SHA256:  f5b89df96fb73f714ee91de13beee0365e075c7e4773b8d3fe28367aa7ba91be
Release: https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.1
State:   draft=false, prerelease=false, tag target=main
Public anonymous accessibility: confirmed (curl, no gh auth) — HTTP 200
```

The theme ZIP was downloaded anonymously (not via authenticated `gh`), its SHA256 checked against the published `SHA256SUMS.txt`, and extracted to a clean temporary directory for the entire audit before any fix was made.

## 3. Current WordPress.org Sources (fetched 2026-09-06)

| Source | URL | Relevant to |
|---|---|---|
| Theme Review Handbook — Required | https://make.wordpress.org/themes/handbook/review/required/ (self-reports "Last updated: June 9, 2026") | Licensing, privacy, accessibility, code/prefix, plugin territory, files/screenshot/style.css headers, block themes, admin notices, selling/credits/links/spam, theme author restrictions |
| GNU GPL FAQ | https://www.gnu.org/licenses/gpl-faq.en.html | Attribution vs. license-notice distinction |
| Google Search Central — widget links | https://developers.google.com/search/blog/2016/09/a-reminder-about-widget-links | Author-credit link `rel` treatment (context, not a WP.org rule) |
| Learn WordPress — Submitting your theme | https://learn.wordpress.org/lesson/submitting-your-theme-to-wordpress-org/ | Submission logistics, slug derivation, review process |
| WordPress.org theme directory (direct check) | https://wordpress.org/themes/astrea/ | Slug availability (404 = unregistered) |
| Web search corroboration | "Astra" theme popularity (WP Tavern, 1M+ installs) | Name-similarity risk assessment |

This audit deliberately re-fetched current pages rather than relying on the equivalent research already performed in Construction 022-PRE, per this Order's own instruction (§2: "Do not rely solely on previous ASTREA research"). The Handbook content matched what 022-PRE found, confirming no rule change occurred between 2026-09-05 and 2026-09-06.

## 4. Requirements Matrix

| Requirement | Source | ASTREA Evidence | Status | Severity | Action |
|---|---|---|---|---|---|
| One ZIP root, correct structure | Handbook §11 | `astrea/` single root, 63 files, `templates/index.html` present | PASS | — | — |
| No contamination (`.git`, dev files, etc.) | Handbook §9 | Full file inventory (§5) shows none | PASS | — | — |
| style.css required headers present & valid | Handbook §9 | All present; URIs live (§6) | PASS | — | — |
| Theme URI / Author URI resolve, not dead domain | Handbook §9 | Both `https://project-if.jp/...`, HTTP 200 (verified in 022A, unaffected here) | PASS | — | — |
| Screenshot ≤1200×900, 4:3 | Handbook §9 | Exactly 1200×900 | PASS | — | — |
| readme.txt no placeholders | Handbook (general) | No TODO/TBD/CHANGEME/example.com found | PASS | — | — |
| Front-end copyright = site owner only | Handbook §1 | ASTREA displays no copyright statement at all (nothing to misattribute) | PASS | — | — |
| One front-facing credit link, Theme/Author URI only | Handbook §13 | Exactly 1, links to Author URI | PASS | — | — |
| No plugin-territory code in Theme | Handbook §5 | Zero CPT/taxonomy/shortcode/DB-table/REST registration in `theme/` | PASS | — | — |
| Theme works with Core absent | Handbook §5 (implicit) | Fresh install, Core absent: HTTP 200, zero PHP notices/warnings/fatals across HOME/page/search/404 | PASS | — | — |
| No prohibited remote requests/telemetry | Handbook §2 | Zero `wp_remote_*`/`curl`/remote-font calls found in Theme source | PASS | — | — |
| No bundled minified/generated assets requiring source disclosure | Handbook §9 | No JS at all; CSS only via theme.json/Style Variations | N/A | — | — |
| PHP/JS errors, warnings, notices | Handbook §4 | Zero across all tested page types (WP_DEBUG on) | PASS | — | — |
| Unique 4+ letter prefix | Handbook §4 | `Astrea\Theme` namespace throughout | PASS | — | — |
| Admin notices dismissible, not obtrusive | Handbook §12 | Dismissible via per-user meta + nonce-verified action, shown only on Dashboard/Plugins | PASS | — | — |
| No obtrusive upselling / stale PRO copy | Handbook §13 | None found | PASS | — | — |
| Theme Check REQUIRED/WARNING | Handbook (tooling) | REQUIRED 0, WARNING 0, INFO 1 (accepted baseline) | PASS | — | — |
| PHPCS (WPCS) | Project baseline | 67 files, 0 violations | PASS | — | — |
| PHPUnit | Project baseline | 399/399 (3 known env errors, unchanged) | PASS | — | — |
| Valid theme.json (schema v3, no experimental settings) | Handbook §11 | Confirmed via direct JSON parse | PASS | — | — |
| Tags currently valid | Handbook (tooling) | `full-site-editing`, `translation-ready` both confirmed valid | PASS | — | — |
| Theme name not confusingly similar to existing brand | Handbook §7 | "ASTREA" vs. "Astra" (1M+ installs) — material risk | **OWNER DECISION** | **HIGH** | See §7 |
| Themes may only recommend WordPress.org-hosted plugins | Handbook §5/§14 | Admin notice recommends "ASTREA Core" by name; Core not on WordPress.org | **OWNER DECISION** | **HIGH** | See §16 |
| Site Editor block-validation clean | Handbook (implicit code-quality expectation) | Found and fixed (front-page.html); verified zero warnings post-fix | PASS (after fix) | was MEDIUM | Fixed, shipped v1.0.2 |
| No dead/unused code, no internal-doc leakage in public metadata | General quality | Found and fixed (dead constant, Description text) | PASS (after fix) | was LOW | Fixed, shipped v1.0.2 |

## 5. ZIP Structure

Full file inventory of `astrea-theme-1.0.1.zip` (pre-fix baseline; the fixes below did not add/remove any files):

```
astrea/assets/icons/price/price-yen.svg
astrea/assets/icons/results/result-check.svg
astrea/assets/icons/results/result-company.svg
astrea/assets/icons/results/result-consultation.svg
astrea/assets/icons/services/{company,contract,document,folder,inheritance,permit}.svg
astrea/functions.php
astrea/languages/astrea.pot
astrea/license.txt
astrea/parts/{footer,header}.html
astrea/patterns/*.php (14 files)
astrea/readme.txt
astrea/screenshot.png
astrea/style.css
astrea/styles/{modern,natural,trust}.json
astrea/templates/*.html (17 files)
astrea/theme.json
```

63 files total, single root `astrea/`, no double nesting. `templates/index.html`, `style.css`, `theme.json`, `readme.txt`, `screenshot.png` all present at required locations. **No contamination found**: zero `.git`, `.github`, `node_modules`, tests, `docs/`, research files, IDE files, backups, temp files, or OS junk anywhere in the archive.

## 6. style.css Header Audit

```
Theme Name: ASTREA
Theme URI: https://project-if.jp/if-thema/astrea/   (HTTP 200, live product page)
Author: Project-if
Author URI: https://project-if.jp/                   (HTTP 200, live)
Description: If Professional ASTREA — ... (fixed in v1.0.2, see §41)
Version: 1.0.1 → 1.0.2 (this Order)
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: astrea
Domain Path: /languages
Tags: full-site-editing, translation-ready
```

All fields present and correctly formatted. Both URIs independently re-verified as live (HTTP 200), no redirect abuse, no tracking parameters, no unrelated commercial landing page — they resolve to the theme's own actual product page and the author's own actual homepage, both already publicly confirmed in Construction 021/021A.

## 7. Theme Name / Slug — Material Finding

Direct check: `https://wordpress.org/themes/astrea/` → **HTTP 404** (no theme currently registered at that exact slug). Per the submission-process documentation (§39), the slug is derived automatically from the ZIP's top-level folder name (`astrea`), so submitting today would very likely be assigned that exact slug with no manual naming step.

**However**, a broader search surfaced a serious, credible risk the exact-slug check alone does not capture: **"Astra"** is one of the most widely-installed WordPress themes in existence (over 1,000,000 active installs, extensively covered in WordPress press). "ASTREA" and "Astra" are a five/six-letter near-anagram of each other, pronounced almost identically in casual speech, and visually similar at a glance in a theme-directory listing. The Handbook's own Naming section (§7, quoted in full during this audit) states:

> The themes team can decline themes based on the name and request that the name is changed if they decide that the name is inappropriate **or too similar to an existing theme or brand.**

This is an explicit, discretionary rejection ground — not a mechanical slug check. A reviewer encountering "ASTREA" immediately after (or in the context of) the ecosystem's own dominant "Astra" brand has a credible basis to invoke this clause. This is **not** a trademark/legal clearance exercise (correctly out of this Order's scope per §6 of the Order) — it is a direct reading of WordPress.org's own stated review discretion.

**This Order does not rename ASTREA, does not change its slug, and does not attempt to preempt this risk by any code change** — per §29's explicit prohibition ("renaming ASTREA", "slug change" both require Owner decision). It is reported here as the primary basis for this Order's verdict.

## 8. readme.txt — Full Review

Full content read (both before and after the v1.0.2 patch touched only the Changelog section). Findings:

- `Contributors: projectif` — present, not a placeholder. **Not independently verifiable** from this audit whether `projectif` is Project-if's actual, currently-active WordPress.org.org username (this can only be confirmed by the Owner attempting to log in as that user, or during the actual submission flow) — flagged as a pre-submission action item (§39 checklist), not a defect.
- `Requires at least`, `Tested up to`, `Requires PHP`, `Stable tag`, `License`, `License URI` all present and consistent with style.css.
- No placeholder text of any kind (`TODO`, `TBD`, `CHANGEME`, `example.com`, `your-name`, `username`) found anywhere in the file.
- No `project-if.com` (the dead domain) anywhere in the file.
- Description, Installation, FAQ (18 questions), Changelog, and Known Issues sections are all complete, specific, and factually accurate to the current product — no unimplemented feature is claimed.
- **Observation (not a defect)**: a large portion of the FAQ describes ASTREA **Core's** plugin-only features in detail. This is factually accurate and transparently labeled as Core-only throughout (no claim that the Theme alone provides these), so it does not violate any rule — but a Theme-directory reviewer unfamiliar with the product might find it unusual that a Theme's own readme spends this much space on a companion plugin. Classified **INFO** — no change recommended, since trimming it risks reducing the transparency that keeps ASTREA compliant with the Theme/Core boundary in the first place.

## 9. screenshot.png Audit

```
Format:     PNG, 1200×900, 8-bit RGB, non-interlaced
Ratio:      4:3 exactly
Size:       109,173 bytes
Content:    A real render of ASTREA's own HOME output, using a fictional
            office ("あおば行政書士事務所") and an obviously-placeholder
            phone number (03-1234-5678)
Provenance: Self-created — a genuine screenshot of the theme's own actual
            rendered output (not a mockup, not third-party stock imagery)
```

No third-party trademarks, no real personal identity, no excessive promotional text, no misleading claims about content shown. Safe for submission as-is.

## 10. Bundled Asset / License Inventory

| Asset type | Count | Source/Provenance | License |
|---|---|---|---|
| SVG icons (`assets/icons/`) | 10 | Self-created — hand-authored, simple line-drawing style, using the theme's own design-system color tokens (`#102A43`, `#B99A5C` matching Trust's palette), no generator metadata (no Figma/FontAwesome/Iconify export artifacts) found in any file | Self-created / Project-if, distributed under the theme's own GPLv2-or-later |
| screenshot.png | 1 | Self-created (§9) | Self-created / Project-if |
| Fonts | 0 (none bundled) | — | N/A |
| JS libraries | 0 (none bundled) | — | N/A |
| CSS libraries | 0 (none bundled; styling is entirely `theme.json`/Style Variations) | — | N/A |

**No unverified or third-party asset found anywhere in the package.** license.txt contains the full, correct GPLv2 text with an accurate `Copyright (C) 2026 Project-if` notice (a source-code copyright notice, which GPL requires and which does not conflict with the separate frontend-display rule in §1/§13 — the two are different concepts, per the GPL-vs-attribution distinction already established in Construction 022-PRE).

## 11. Fonts

No `@font-face` declarations, no `fonts.googleapis.com`/`fonts.gstatic.com` references, and no bundled font files were found anywhere in the Theme package (confirmed via direct grep across the full extracted archive). Typography in `theme.json` uses only system/web-safe font stacks (e.g., `-apple-system, BlinkMacSystemFont, "Hiragino Kaku Gothic ProN", ... sans-serif`). **No remote font dependency exists.**

## 12. External Requests / Privacy

Full-package grep for `wp_remote_`, `curl_init`, `file_get_contents(...http`, `fopen(...http`, and any `fonts.googleapis`/`fonts.gstatic` reference: **zero matches** anywhere in `theme/`. The Theme makes no outbound network requests of any kind and has no dependency on Project-if (or anything else) being online to render normally — confirmed both statically and by the fact that every fresh-install test in this Order (and prior orders) ran in fully network-isolated Docker containers with no issue.

## 13. Author Credit Recheck

Re-audited fresh, as if no prior work existed:

- Exactly **one** frontend Project-if credit link on any given page (`grep -c` = 1 on HOME, confirmed on both the exact v1.0.1 baseline and the v1.0.2 fix build).
- Destination: `https://project-if.jp/`, matching the (now also independently re-verified live) `Author URI`.
- Anchor text: the single word "Project-if" — brand-only, not keyword-rich.
- `rel="nofollow"` present in rendered HTML, confirmed via direct `curl` inspection on a fresh install both before and after this Order's fixes.
- Site Editor editable/removable: re-confirmed via the same database-fork technique used in 022A (create a customized `wp_template_part` without the credit block → credit disappears and does not return on reload; delete the customization → theme-file default, including the credit, reappears).
- No second Project-if link anywhere else in the package — confirmed via a full-package `grep` for `href="http`/`src="http` across every template, part, and pattern file: the credit link is the **only** external link found anywhere in the shipped Theme.
- Front-end copyright: ASTREA displays no copyright statement of any kind (unchanged since 022A), so there is nothing for Project-if to misattribute as its own.

No change from Construction 022A's own findings; independently re-confirmed rather than assumed.

## 14. Plugin Territory Audit

Full-package `grep` for `register_post_type`, `register_taxonomy`, `add_shortcode`, `CREATE TABLE`, `$wpdb->query`, `register_rest_route` across every `.php` file in the Theme (main file + all 14 pattern files): **zero matches**. ASTREA's Theme code registers no custom post type, taxonomy, shortcode, database table, or REST endpoint of any kind — all such functionality correctly lives in ASTREA Core (out of this audit's primary scope, per the Order's own framing), confirming the Theme stays cleanly on the presentation side of the plugin-territory line.

The one piece of Theme-side PHP logic beyond template registration is the Core-detection/admin-notice mechanism in `functions.php` (§16) — a legitimate, narrowly-scoped, security-conscious (nonce-verified, capability-checked) piece of code, not itself "plugin functionality" in the sense the Handbook restricts (it stores one boolean-ish usermeta flag; it does not implement any of the enumerated forbidden categories: analytics, SEO options, contact forms, non-design meta boxes, caching, social buttons, session tampering).

## 15. Theme-Only Behavior

Fresh WordPress 7.1 / PHP 8.3 install, Core never installed:

| Check | Result |
|---|---|
| Install | PASS |
| Activation | PASS |
| HOME | HTTP 200 |
| Page (`?page_id=`) | 301 (canonical redirect — normal WordPress behavior, not an error) |
| Single (`?p=`) | 301 (same) |
| Search (`?s=`) | HTTP 200 |
| 404 (nonexistent path) | HTTP 404 (correct) |
| PHP notices/warnings/fatals (`WP_DEBUG` on, `debug.log` inspected) | **None**, across every page type tested |
| Dashboard admin notice | Present, informative, dismissible (screenshot captured) |
| Site Editor | Loads correctly (`Front Page ‹ Template ‹ ... ‹ Editor`) |
| Author credit | Present (Theme-only — no Core dependency; confirmed identical link count with Core OFF) |

Theme-only users receive: full HOME/page/archive/search/404 rendering using theme.json defaults and static pattern content (Office name/address/phone fields render empty, since those specific bindings come from Core — this is documented, expected, graceful degradation, not a defect), all three Style Variations, the Site Editor, and one gentle, dismissible admin notice explaining that Core adds further functionality. No fatal error, no white screen, no broken layout.

## 16. Core Recommendation / Distribution — Material Finding

`functions.php`'s `maybe_render_core_recommendation_notice()` function:

- Fires only on the Dashboard and Plugins admin screens (not globally).
- Is fully dismissible per-user, permanently, via a nonce-verified `admin-post` action.
- Does **not** auto-install, auto-download, or auto-activate anything.
- Its only actionable link opens `admin_url('plugins.php')` — WordPress Core's own standard Installed Plugins screen — not a direct GitHub download, not `plugin-install.php`'s search screen.

This is a well-built, non-forceful, non-obtrusive notice by the standards of admin-notice UX generally. **The issue is not how it behaves, but what it recommends.** Its visible text explicitly names and recommends "ASTREA Core" and explains its benefits. The Handbook's plugin-territory section (§5, quoted verbatim during this audit) states without qualification:

> Themes may only recommend plugins that are hosted on WordPress.org.

ASTREA Core is not currently listed on the WordPress.org Plugin Directory (this project's own repository history confirms it is distributed exclusively via GitHub/Project-if). Naming and recommending it by name in an admin notice, even without any install/download automation, plainly reads as exactly the kind of recommendation this rule restricts.

**Per this Order's own decision framework (§39), the outcomes are:**

- **A** (Theme independently compliant, Core merely external/optional) — **not fully accurate**, because the Theme actively recommends Core by name via a dedicated notice, not merely mentions it in documentation.
- **B** (Theme must not auto-install/update for non-directory Core) — ASTREA already satisfies this narrower requirement, but satisfying it alone does not resolve the "may only recommend directory-hosted plugins" issue.
- **C** (Core should be submitted to the WordPress.org Plugin Directory before Theme submission) — the most straightforwardly rule-compliant path, but a substantial, separate undertaking (a full Plugin Directory submission and review process for Core) explicitly not authorized in this Order.
- **D** (current relationship blocks submission) — **effectively true today**, unless the notice's wording/mechanism changes or Core is separately published first.

**This Order does not modify `functions.php`'s notice, does not submit Core anywhere, and does not weaken Decision 021's "Core is officially recommended" product principle unilaterally.** Any resolution — publishing Core first, or changing how/whether the Theme names Core in that notice — is a genuine product-strategy decision requiring the Owner, exactly matching this Order's own Stop Condition ("Core distribution model conflicts with Theme requirements") and Fix-If-Required exclusion ("Theme/Core strategic restructuring").

## 17. Admin Notices / Upsell Audit

Only one admin-facing notice exists in the Theme (the Core-recommendation notice, §16). No welcome/onboarding screen, no donation link, no PRO promotion, no dismissal-defeating persistence, no dashboard-hijacking, no unrelated advertising. Frequency/persistence is correctly minimal (shown only on 2 screens, permanently dismissible). Aside from the naming-of-a-non-directory-plugin issue already covered in §16, this notice fully satisfies the Handbook's admin-notice requirements (§12: uses `admin_notices`, is dismissible, follows core UI conventions).

## 18. Setup / Starter Content Audit (Theme side)

Confirmed via full-package `grep`: the Theme itself registers no `wp_insert_post`/`wp_update_post` calls, no page-creation logic, and no navigation-menu-creation logic anywhere in `theme/*.php` or `theme/patterns/*.php`. All of the Setup behavior described in readme.txt (creating draft pages, menus, HOME) is exclusively implemented in ASTREA Core (out of the Theme's own submission scope) and is **inert with Core absent** — confirmed experimentally in §15 (no such content was created on the Theme-only fresh install). Theme activation itself does not alter any existing content or settings — confirmed by the Theme-only fresh-install test showing a completely standard, unmodified default WordPress state aside from the active theme itself.

## 19. Block Pattern Audit

All 14 pattern files (`theme/patterns/*.php`) inspected. Every one uses the standard WordPress pattern-file header format (Title/Slug/Categories/Description) and contains only valid block markup plus, in a few cases, PHP conditionals gating Core-bound content — no business logic, no database access, no remote calls. Full-package link scan (§20) confirms **zero** hardcoded external URLs anywhere in any pattern file except the one permitted author-credit link (which lives in `parts/footer.html`, not a pattern). No dead links, no localhost/dev URLs, no `project-if.com`, no private URLs, no real-person PII, no unauthorized trademarks, no misleading external CTAs. Demo/placeholder copy throughout (e.g., "会社設立", "契約書作成") is generic, professionally worded, and suitable for public redistribution.

**Observation (INFO, not a defect):** several pattern files (most notably `home-hero.php`, 114 lines with 6 internal-reference comment lines) carry substantial internal "Construction Order" development-history commentary in code comments, including references to non-distributed internal research documents (`docs/research/2026-08-30_construction_016b_r2_...md` etc.). These are PHP comments, never rendered or displayed to any end user, and this pattern is common in real-world WordPress themes that retain development history as comments — not a rule violation. Not fixed in this Order (would be a large, purely cosmetic content-editing exercise across many files, explicitly outside the "objective blocker" scope this Order targets, and risks introducing errors for no compliance benefit).

## 20. Templates / Template Parts Audit

All 17 templates and 2 template parts inspected. `templates/index.html` (the required fallback) is present. No hardcoded private content, no development URLs, no broken references were found. **One genuine defect was found and fixed** — see §21.

**Recheck of the documented WordPress 7.1 `core/group`/`core/cover` Editor warning** (readme.txt's own Known Issues entry): this audit specifically re-tested this exact class of warning and found **two distinct things being conflated by that Known Issues entry**:

1. A genuine, general WordPress 7.1 Core behavior around `core/group`/`core/cover` block validation, which is **not** ASTREA-caused and was correctly left unmodified (working around a WordPress Core quirk is explicitly discouraged by this Order's own §20 instruction, and doing so would risk introducing a different problem).
2. A **separate, ASTREA-caused** instance of superficially the same symptom ("Block contains unexpected or invalid content" / a console "Block validation failed" error) on the Front Page template specifically — which this audit traced to a genuine ASTREA authoring mistake (§21), not the WordPress Core quirk, and which **was** fixed.

This distinction matters: the readme.txt's Known Issues entry, written before this discovery, may have been *silently including* symptoms of ASTREA's own bug alongside the genuine WordPress Core quirk. The Known Issues wording itself was not changed in this Order (it remains accurate for the WordPress Core quirk it was written to describe), but the Owner should be aware that some historical reports of "Block contains unexpected or invalid content" on the Front Page/HOME template specifically may have actually been this now-fixed ASTREA defect, not the Core quirk.

## 21. Block-Validation Defect — Found and Fixed

**Root cause, precisely identified:** `theme/templates/front-page.html` (pre-fix) placed a long, multi-line HTML comment **between two of a `core/group` block's own child-block delimiters** (between `<!-- wp:astrea/breadcrumb /-->` and `<!-- wp:post-content .../-->`, both inside that group's `<!-- wp:group -->...<!-- /wp:group -->` boundary). WordPress's Block Parser treats any comment in that position as unexpected inner content belonging to the group block itself; since a bare children-only group's `save()` output contains no such static content, this produces a genuine, deterministic mismatch — reproduced live in the Site Editor as a `Block validation failed` console error and the visible "Block contains unexpected or invalid content" warning, **every single time** that template is opened.

**Proof this is ASTREA-caused, not a WordPress Core quirk:** `theme/templates/page.html` carries an analogous, similarly-long internal comment, but placed **before** its own `<!-- wp:group -->` opening delimiter (i.e., between two top-level/sibling blocks, not inside one). Opening the `page` template in the Site Editor produces **zero** validation warnings. Moving `front-page.html`'s comment to the same safe position (before the group's own opening delimiter) **eliminated the warning entirely** — verified via direct browser automation, before the fix (warning reproduced) and after (zero errors/warnings), including on the **exact packaged v1.0.2 ZIP** in a fresh install.

**Fix applied:** relocated the comment (content byte-for-byte preserved, plus one added sentence noting why it moved) to sit before the `core/group` block's own opening delimiter — the identical safe pattern `page.html` already used. No visual or functional change; confirmed via responsive smoke test (§37) and a full re-run of all quality gates.

A systematic scan for the same anti-pattern across every other template and part file in the package found **no other occurrence** — this was an isolated, single-file defect.

## 22. theme.json Audit

```
$schema: https://schemas.wp.org/trunk/theme.json
version: 3
top-level keys: settings, styles, templateParts, customTemplates
```

Valid, current schema version. No `experimental`/`__unstable` settings anywhere in `theme.json` or any of the three Style Variation files (`styles/trust.json`, `styles/natural.json`, `styles/modern.json`) — confirmed via direct grep. All three Style Variations were re-verified to render correctly, including the Construction 022A Footer-credit contrast fix (link color `rgb(255,255,255)` on Trust, `rgb(253,250,245)` on Natural, `rgb(255,255,255)` on Modern — all high-contrast against their respective dark Footer backgrounds), confirming that fix remains intact and was not affected by this Order's own changes.

## 23. Accessibility

- Skip link: automatically provided by WordPress Core for block themes (Handbook §3: "In block themes, skip links are added automatically to the `<main>` tag") — no custom ASTREA skip-link markup exists to conflict with or duplicate it.
- Keyboard navigation: the one interactive element this Order specifically re-tested (the author-credit link) is a real, keyboard-focusable `<a>` element (confirmed via `element.focus()` → `document.activeElement` match, consistent with every prior accessibility check across this whole project).
- Contrast: the one contrast defect present in the codebase (the Footer-credit link under Modern) was already found and fixed in Construction 022A and re-confirmed intact in this Order (§22).
- No `accessibility-ready` tag is used in `style.css`'s Tags field, so no additional substantiation obligation applies, and no overclaiming exists.
- This audit does not claim full WCAG certification, consistent with the Order's own instruction not to overclaim.

## 24. Internationalization

- `Text Domain: astrea` in `style.css` matches the theme slug exactly.
- `load_theme_textdomain('astrea', ...)` in `functions.php` uses the identical string.
- All Theme-owned PHP-rendered user-facing strings use `esc_html_e()`/`__()` with the correct `'astrea'` text domain (confirmed by direct inspection of `functions.php`, the only Theme PHP file with translatable output).
- POT regeneration (both before this Order's product fixes and after, for the version bump) shows a **stable 60 msgid count** both times — no string was lost or gained by any change in this Order.
- Per the Handbook's own explicit "temporary exception" for text inside HTML template files (§8, quoted verbatim in Construction 022-PRE and reconfirmed current in this Order), the literal "Theme by Project-if" credit text and other static pattern/template copy are not required to be gettext-wrapped — this is not a defect, it is the Handbook's own current stated policy.
- Extensive Japanese hard-coded content exists throughout patterns and templates (by design — ASTREA is built for the Japanese market), but this is presentational/demo content, not a translatable-string defect; the theme's actual UI chrome strings (admin notices, dismiss links, etc.) are fully translatable.

## 25. Escaping / Sanitization / Security

`functions.php` (the only Theme PHP file with meaningful logic) was read in full: `esc_html_e()` and `esc_url()` used correctly on all dynamic/user-facing output; `check_admin_referer()` (nonce verification) and `current_user_can('activate_plugins')` (capability check) both correctly gate the one state-changing action (dismissing the notice); `wp_safe_redirect()` used for the post-action redirect (prevents open-redirect); direct-file-access guard (`if ( ! defined( 'ABSPATH' ) ) exit;`) present. No `$wpdb` direct queries, no `unserialize()`, no arbitrary file operations, no SQL string concatenation, no obvious XSS/CSRF surface anywhere in the Theme's PHP.

## 26. WordPress API / Code Quality

No deprecated WordPress API usage found. No direct database access outside WordPress's own APIs. No hardcoded `wp-content` path assumptions. `wp_enqueue_style`/`add_theme_support` used conventionally. No deregistration of Core assets, no disabling of admin functionality, no output-buffering hacks, no PHP sessions, no custom update mechanism, no remotely-fetched executable code (§12 already confirms zero outbound requests of any kind).

## 27. PHP 8.3 / WordPress 7.0–7.1 Requirements

Per this Order's explicit instruction not to lower the PHP requirement merely to widen the install base: **retained as declared** (`Requires PHP: 8.3`). This audit found no current WordPress.org tooling/policy rejecting a theme that declares a modern minimum PHP version — Theme Check itself accepted `Requires PHP: 8.3` cleanly (REQUIRED 0, no complaint about the PHP floor). Actual testing was performed at the declared boundary: every fresh-install test in this Order and its prerequisite orders used **WordPress 7.1 + PHP 8.3.33** (Docker `wordpress:php8.3-apache`), matching both ends of the declared support range (`Requires at least: 7.0`, `Tested up to: 7.1`, `Requires PHP: 8.3`). As of this Order's execution date (2026-09-06), WordPress 7.1 remains the latest stable release referenced throughout this project's history; no newer stable version was found to require updating `Tested up to`.

## 28. Tags

`full-site-editing`, `translation-ready` — both independently re-confirmed as currently valid, accurately descriptive tags (§27 research). No obsolete, promotional, or keyword-stuffed tags present. No change made.

## 29. GPL / License Audit

`style.css`: `License: GNU General Public License v2 or later`, `License URI: https://www.gnu.org/licenses/gpl-2.0.html` — both present and standard. `license.txt`: full, correct GPLv2 text with an accurate source-code copyright notice (`Copyright (C) 2026 Project-if`). All bundled assets (§10) are self-created and distributed under the same license — no third-party GPL-incompatible code, font, image, or icon found anywhere in the package. The source-copyright-notice-vs-frontend-attribution distinction (Construction 022-PRE's own finding) was reconfirmed: GPL protects the copyright notice in `license.txt`/`style.css`, which is unrelated to and does not conflict with the frontend author-credit rule.

## 30. Source / Minified Asset Audit

**No minified or generated CSS/JS assets exist in the ASTREA Theme package.** Confirmed by the complete file inventory (§5): zero `.js` files of any kind, and all styling is expressed declaratively through `theme.json`/Style Variation JSON files (no compiled/bundled CSS output). No build system was introduced to manufacture a false appearance of "source" — there is genuinely nothing to disclose source for.

## 31. Secret / Residue Scan

Full-package, case-insensitive search for `project-if\.com`, `localhost`, `127\.0\.0\.1`, `192\.168\.`, `TODO`, `FIXME`, `CHANGEME`, `password\s*=`, `secret`, `api_key`, private-key markers, and test-credential patterns: **zero matches** anywhere in the distributed ZIP, both before and after this Order's fixes. (The two internal-reference issues found and fixed — §21's misplaced comment and §41's Description text — were not secrets, but confusing/unprofessional internal references; they are addressed separately from this pure secret/residue check, which was already clean.)

## 32. Theme Check

Run against the exact v1.0.2 fix build on a fresh WordPress 7.1/PHP 8.3 install:

```
Version:     1.0.2
Author URI:  https://project-if.jp/           (live)
Theme URI:   https://project-if.jp/if-thema/astrea/  (live)
Result:      ASTREA passed the tests
REQUIRED:    0
WARNING:     0
INFO:        1 (Text Domain / theme-slug match note — long-accepted, informational only)
```

No finding was suppressed to produce this result; the single INFO item is the same one carried since Construction 017's original baseline.

## 33. PHPCS

```
$ php vendor/bin/phpcs   (PHP 8.3, project's own phpcs.xml ruleset)
67 files, 0 errors, 0 warnings
```

Re-run after each of this Order's own source edits (front-page.html, functions.php, style.css) — no regression.

## 34. Automated Tests

```
$ npm run test:php
Tests: 399, Assertions: 663, Errors: 3 (all 3 pre-existing, environment-only —
  wp-phpunit's own attachment-factory helper raising "Undefined array key
  \"file\"" — unrelated to any ASTREA code, unchanged baseline since
  Construction 017)
```

399/399 tests executed, identical error set to the established baseline both before and after this Order's fixes — no regression.

## 35. Exact-ZIP Fresh Install (Core Absent)

Performed on the pre-fix `astrea-theme-1.0.1.zip` (baseline, to reproduce the defect) and again on the post-fix `astrea-theme-1.0.2.zip` (to confirm the fix), both on fresh WordPress 7.1/PHP 8.3 Docker environments, Core never installed:

| Check | Pre-fix (1.0.1) | Post-fix (1.0.2) |
|---|---|---|
| Install / Activate | PASS | PASS |
| HOME / Page / Single / Archive / Search / 404 | PASS (200/301/301/200/404 as appropriate) | PASS |
| Site Editor loads | PASS | PASS |
| Front Page template — Block validation | **1 error + 3 warnings reproduced** | **Zero** |
| Author credit present, exactly 1, `rel="nofollow"` | PASS | PASS |
| Fatal/warning/notice (`debug.log`) | None | None |

## 36. Theme + Core Compatibility

Core (`astrea-core-1.0.1.zip`, unchanged, downloaded fresh from the public v1.0.1 Release) installed alongside the fixed Theme on the same fresh environment: activation successful, HOME HTTP 200, no fatal. This confirms the public Project-if-recommended pairing remains healthy — **not**, per the Order's own instruction, evidence that Core belongs inside the Theme Directory submission.

## 37. Responsive / Visual Smoke

HOME tested at 320/375/768/1024/1440/1920px on the exact v1.0.2 fix build: **0px horizontal overflow at every width.** No visual regression introduced by Construction 022A's Footer-credit addition or by this Order's own fixes (front-page.html's comment relocation and the two metadata edits touch no CSS/layout at all). Trust/Natural/Modern all re-confirmed rendering correctly with the credit link at proper contrast (§22).

## 38. Exact Proposed Submission ZIP

**`astrea-theme-1.0.2.zip`** (Theme ZIP only).

Do **not** include `astrea-core-1.0.1.zip`, `SHA256SUMS.txt`, `docs/`, the release-notes bundle, or any Project-if file — none of these belong in a WordPress.org Theme Directory submission. Per the submission-process research (§43), WordPress.org derives the theme's slug automatically from the ZIP's own top-level folder name (`astrea/`), so no separate slug-naming step exists at upload time — the slug question (§7) is answered entirely by what folder name is inside the ZIP, which is already `astrea`.

## 39. ASTREA Core — Distribution Decision

Per this Order's own required outcomes (§39 of the Order):

**Current state most closely matches Outcome D** ("current Theme/Core relationship blocks Theme submission") **as long as the Theme's admin notice continues to recommend Core by name while Core remains undirected-listed** — not because of any technical/install-automation problem (ASTREA already avoids all of those correctly), but because of the plain-text Handbook rule restricting *recommendation* itself to directory-hosted plugins (§16).

**Outcome C** (submit Core to the Plugin Directory first) would fully resolve this without touching the Theme's existing UX at all, but is a separate, substantial undertaking not authorized by this Order.

Neither outcome is something this Order can select on the Owner's behalf — this is exactly the kind of business/architecture decision reserved for the Owner throughout this entire project's discipline.

## 40. Fix-If-Required Scope — What Was Fixed, What Wasn't

**Fixed (safe, mechanical, zero behavior change):**
1. Block-validation defect in `front-page.html` (§21).
2. Unused, stale `const VERSION = '0.1.0'` in `functions.php`.
3. Internal, non-distributed document-path reference removed from `style.css`'s public `Description` field.

**Found but deliberately NOT fixed, per this Order's own scope boundaries:**
- Name/slug similarity to "Astra" (§7) — would require renaming, explicitly prohibited without Owner decision.
- Core-recommendation notice (§16) — would require either a separate Plugin Directory submission (out of scope) or a product-behavior change to the notice (a strategic decision, not a mechanical fix).
- Extensive internal-development commentary in pattern files (§19) — cosmetic, non-blocking, large-surface-area edit with no compliance benefit; left as-is.
- readme.txt's heavy Core-feature description (§8) — factually accurate and transparent, not a rule violation; left as-is.

## 41. Version / Release Impact

**ASTREA 1.0.2 (Theme only) was prepared and released** in this Order, containing exactly the three fixes in §40. This was judged in-scope because each fix is narrow, objective, mechanical, safe, and independently justified regardless of how the Owner eventually resolves the two open questions in §7/§16 — matching this Order's own versioning policy (§41 of the Order): *"If purely mechanical submission-readiness fix: v1.0.2 MAY be prepared and released only after all gates pass, with the reason documented."* All gates passed (§32–§37) before release.

`v1.0.0`, `v1.0.0-rc2`, and `v1.0.1` were **not** modified, rewritten, or had their assets replaced at any point in this Order — independently re-confirmed via `gh release view` (both `isDraft`/`isPrerelease` unchanged) and `git rev-parse` against each tag's target commit (§46).

ASTREA Core was **not** touched, not rebuilt, and not re-released — it remains at 1.0.1, its own asset in the v1.0.2 GitHub Release being byte-identical (same SHA256) to the one already published with v1.0.1.

**Project-if impact: none.** Since no Owner-facing "current version" claim needs updating for a Theme-only patch this narrow (the product pages already correctly say "1.0.1" for the last user-visible functional milestone, and this Order's fixes are invisible/non-functional), and since the Order's own §42 only requires a Project-if update "if v1.0.2 is legitimately required and released" for version/download-surface accuracy — this is flagged as a **deferred, low-priority housekeeping item**: Project-if's product page currently displays "Version 1.0.1" and links to the `v1.0.1` release assets, which still work (v1.0.1 is not deleted or modified) but are one patch behind the latest. Recommended for the Owner's future consideration, not actioned in this Order (a Project-if-side edit was judged non-essential to this Order's own core mission of establishing submission readiness).

## 42. Deferred / Non-Blocking Findings

- Small Business FREE / My Base URI audit remains as documented in Construction 022A (§25 of that report) — Small Business has no Author/Theme URI fields at all; My Base already correctly uses `project-if.jp`. Neither was touched in this Order (out of scope; ASTREA-only per this Order's own §0).
- `Contributors: projectif` in readme.txt could not be independently verified as an active WordPress.org.org username from this environment — a pre-submission action item for Construction 023, not a defect found in this audit.
- Internal-development commentary in pattern files (§19) and readme.txt's Core-feature-heavy FAQ (§8) — both INFO-level, no action recommended.

## 43. WordPress.org Submission Logistics — 023 Checklist

Researched (not performed) for a future, separately-authorized Construction 023:

- [ ] Confirm a WordPress.org.org account exists for the "projectif" username (or the account intended to own the submission) and that its credentials are available to the Owner.
- [ ] Confirm the resolution of §7 (name-similarity risk) and §16 (Core-recommendation policy) — both should be settled **before** initiating 023, since either could force a resubmission cycle if discovered mid-review.
- [ ] Prepare exactly `astrea-theme-1.0.2.zip` (§38) — no other file.
- [ ] Upload at `https://wordpress.org/themes/upload/` — this single action creates the theme's WordPress.org URL and SVN repository and triggers automated Theme Check-style checks immediately.
- [ ] After automated checks pass, the theme enters a **human review queue** managed via Trac; a reviewer downloads and evaluates it independently of anything in this report.
- [ ] The submitter (Owner or delegated account holder) must monitor email for Trac ticket updates and respond to reviewer feedback — inactivity for 7 days risks the ticket being closed; no response within the reviewer's own first 48 hours entitles the submitter to request the theme be returned to the queue.
- [ ] No GitHub URL or separate "support URL" field is required by the upload form itself; `Theme URI`/`Author URI` in `style.css` are what WordPress.org surfaces publicly.
- [ ] SVN is used only for *updating* an already-accepted theme (via `svn commit`, with a separate SVN password from the main account) or as an alternative to ZIP re-upload — not required for the initial submission itself.
- [ ] Confirm no code in the submitted ZIP claims or implies WordPress.org availability prematurely (Project-if's own copy must not say "Available on WordPress.org" until it actually is).

## 44. Product-Code Diff Classification

Every line changed in this Order's Release Commit (`64f870c`) is one of exactly: (1) the block-validation comment relocation (zero rendered-output change), (2) removal of one dead, unreferenced constant, (3) a Description-field text edit (metadata only), (4) the corresponding version-number bump, or (5) POT regeneration reflecting only that version bump and the Description edit. No PHP logic, CSS, template *behavior*, pattern content, CPT registration, meta field, Setup behavior, Contact behavior, SEO behavior, Schema, Breadcrumb behavior, Navigation behavior, deletion/uninstall behavior, or Style Variation was functionally altered. `core/` was not touched at all.

## 45. Git Discipline

```
Before this Order:
  git status --short -> only the long-standing, pre-existing untracked
                         Zone.Identifier file (unrelated)

After this Order:
  git status --short -> same single pre-existing untracked file, unchanged

Product diff (Release Commit 64f870c): 5 files (theme/functions.php,
  theme/languages/astrea.pot, theme/readme.txt, theme/style.css,
  theme/templates/front-page.html) + 1 new file
  (docs/release/1.0.2_RELEASE_NOTES.md)

Report/HISTORY commit: this report + HISTORY.csv row (separate commit,
  after the tag — see below)

Commits: 64f870c (Release Commit, CI green) -> tag v1.0.2 -> GitHub
  Release -> this report's own commit (pending, see §48)

main / origin sync: 0 0 throughout
```

No unrelated existing change was absorbed into any commit, deployed, or otherwise touched.

## 46. Immutability Confirmation

```
$ gh release view v1.0.0 --json isDraft,isPrerelease
{"isDraft":false,"isPrerelease":false}   (unchanged)
$ gh release view v1.0.1 --json isDraft,isPrerelease
{"isDraft":false,"isPrerelease":false}   (unchanged)
$ git rev-parse v1.0.0^{commit} v1.0.0-rc2^{commit} v1.0.1^{commit}
c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
5a1f6b40426c5ce4c8a16132ea8f6f979cc8c126
8a9f8e47b1079c09c65dad5b36eb4c2b0d1e3882
```

All three prior tags point to exactly the same commits they did before this Order began. `v1.0.2` (this Order's own tag) points to `64f870c2bc1b0b44d6e3b076e40739b9ff09e5d9`, distinct from all three.

## 47. CI / Tag / Release / Public Verification Summary

```
Release Commit:      64f870c
CI run:               34023530976 — all 3 jobs green (exit code 0)
Tag v1.0.2 target:    64f870c (git rev-parse match confirmed)
GitHub Release:       "ASTREA 1.0.2", draft=false, prerelease=false
Assets:                astrea-theme-1.0.2.zip, astrea-core-1.0.1.zip, SHA256SUMS.txt
Anonymous curl checks: all 4 URLs (release page + 3 assets) -> HTTP 200
Anonymous SHA256:      astrea-theme-1.0.2.zip OK, astrea-core-1.0.1.zip OK
SHA256SUMS.txt:        byte-identical between anonymous download and local dist/
```

## 48. Findings Register

| ID | Severity | Requirement | Evidence | Affected file | Proposed resolution | Fixed? | Retest result |
|---|---|---|---|---|---|---|---|
| WPORG-001 | **HIGH** | Handbook §7 — name not "too similar to an existing theme or brand" | "ASTREA" vs. "Astra" (1M+ install dominant theme) | Theme Name (style.css), product branding | Owner decision: accept risk, or consider a name adjustment before submission | No (requires Owner decision; renaming prohibited without one) | N/A |
| WPORG-002 | **HIGH** | Handbook §5/§14 — "Themes can only recommend plugins hosted on WordPress.org" | Admin notice in `functions.php` names/recommends ASTREA Core (not on WordPress.org) | `theme/functions.php` | Owner decision: submit Core to Plugin Directory first, or revise/remove the notice | No (strategic decision required) | N/A |
| WPORG-003 | MEDIUM (now fixed) | Editor content-integrity / genuine Block Parser behavior | Reproducible "Block validation failed" on Front Page template, traced to a misplaced internal comment inside a `core/group` block boundary | `theme/templates/front-page.html` | Relocate comment outside the block boundary (matches `page.html`'s own safe pattern) | **Yes** | Zero errors/warnings on exact v1.0.2 ZIP, confirmed via live Editor test |
| WPORG-004 | LOW (now fixed) | Code cleanliness / no dead code | Unused `const VERSION = '0.1.0'`, never referenced anywhere | `theme/functions.php` | Remove | **Yes** | PHPCS/PHPUnit clean, no functional change |
| WPORG-005 | LOW (now fixed) | Public metadata accuracy | style.css `Description` referenced a non-distributed internal spec file path | `theme/style.css` | Remove the path reference, keep the descriptive text | **Yes** | Theme Check re-run clean; Description reads naturally |
| WPORG-006 | INFO | readme.txt content balance | Extensive Core-feature description inside the Theme's own readme | `theme/readme.txt` | None required — factually accurate and transparent | No (not required) | — |
| WPORG-007 | INFO | Code comment hygiene | Internal "Construction Order" development narrative embedded in several pattern files, referencing non-distributed research docs | `theme/patterns/*.php` | None required — comments only, never rendered | No (not required) | — |
| WPORG-008 | INFO | Pre-submission verification, not a defect | `Contributors: projectif` cannot be independently confirmed as an active WordPress.org username from this environment | `theme/readme.txt` | Owner/submitter to confirm during Construction 023 | N/A | — |

## 49. HISTORY

Recorded in `HISTORY.csv` (see the git diff of this commit) with factual, measured `Start`/`End`/`Duration` and `Commit` fields.

## 50. Final Verdict

**C. ASTREA WORDPRESS.ORG SUBMISSION HOLD — OWNER DECISION REQUIRED**

Meaning: a material product/architecture/business decision (WPORG-001, WPORG-002) is required before Construction 023 could be responsibly authorized. Three narrow, objective, safe defects (WPORG-003/004/005) were found, fixed, and released as **ASTREA 1.0.2** — this alone does not change the overall verdict, since the two remaining open items are unrelated to and independent of those fixes.

Per the Order's Absolute Stop Gate (§54), even though narrow fixes were made and released, this Order does not, and did not: begin WordPress.org submission, create a Theme Directory entry, submit Core anywhere, contact Theme Review, start Construction 023, or claim WordPress.org approval/availability anywhere. `v1.0.0`, `v1.0.0-rc2`, and `v1.0.1` remain completely untouched and immutable. Waiting for Owner.
