# Construction Order 022A — ASTREA 1.0.1 Author Credit / URI Correction Patch Release

Date: 2026-09-06
Actor: Chloe (Claude)
Status at close: **A. ASTREA 1.0.1 RELEASED — AUTHOR CREDIT / URI PATCH VERIFIED**

## 1. Executive Result

ASTREA 1.0.1 (Theme + Core) is now a stable, published GitHub Release. It is a narrow patch on top of 1.0.0, implementing exactly the conditions approved in Construction 022-PRE's compliance research: (1) corrected `Theme URI`/`Author URI`/`Plugin URI` metadata (previously pointing to a non-resolving domain), and (2) an optional, fully editable/removable "Theme by Project-if" author credit added to the default Footer. A genuine contrast/accessibility bug discovered during implementation (the credit link was invisible against the dark Footer background under the Modern Style Variation) was found and fixed before release. A real `v1.0.0 → v1.0.1` upgrade was tested end-to-end using the actual published `v1.0.0` Release assets, confirming a customized Footer survives the update untouched and the new credit is never force-inserted into it. `v1.0.0` remains completely untouched and immutable.

## 2. Construction 022-PRE Prerequisite

Independently re-confirmed before starting: 022-PRE's verdict was **B. AUTHOR CREDIT APPROVED WITH CONDITIONS**, and its report (`docs/research/2026-09-05_astrea_author_credit_link_compliance_research.md`) was treated as the binding design constraint for every implementation decision in this Order (exact wording, exact destination, implementation method, `rel` attribute, and the requirement to fix the dead domain first).

## 3. v1.0.0 Baseline

Verified directly before any change:

```
git status --short   -> only the long-standing, pre-existing untracked
                         Zone.Identifier file (unrelated)
main / origin sync    -> 0 0
HEAD                   -> 3881711109d25085c93d96a3487c67a887627467
v1.0.0 tag commit      -> c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
v1.0.1 tag             -> did not exist yet
GitHub Release v1.0.0  -> tag v1.0.0, draft: false, prerelease: false,
                          assets: astrea-theme-1.0.0.zip,
                          astrea-core-1.0.0.zip, SHA256SUMS.txt
Theme version           -> 1.0.0   Core version -> 1.0.0
Theme URI               -> https://project-if.com/astrea
Author URI (Theme)      -> https://project-if.com/
Plugin URI (Core)       -> https://project-if.com/astrea
Author URI (Core)       -> https://project-if.com/
```

## 4. URI Audit

Full repository search for `project-if.com` classified every occurrence:

**Authoritative — corrected in this Order:**
- `theme/style.css` — `Theme URI`, `Author URI`
- `core/astrea-core.php` — `Plugin URI`, `Author URI`
- `theme/languages/astrea.pot`, `core/languages/astrea-core.pot` — the corresponding auto-extracted `msgid` strings (corrected via regeneration, not manual edit)

**Historical — deliberately left unchanged** (per the Order's own explicit instruction not to blind-replace historical text): `HISTORY.csv`'s 022-PRE row, the 022-PRE research report itself, and `docs/release/FINAL_RELEASE_NOTES.md` (the v1.0.0 GitHub Release's own already-published notes — a frozen historical record of what v1.0.0 actually said, analogous to how RC2's own release notes were left untouched when 1.0.0 shipped).

After the fix, a repeat search confirms `project-if.com` no longer appears in any current/authoritative product metadata — only inside the historical documents listed above.

## 5. Changed Authoritative Metadata

| File | Field | Before | After |
|---|---|---|---|
| `theme/style.css` | Theme URI | `https://project-if.com/astrea` | `https://project-if.jp/if-thema/astrea/` |
| `theme/style.css` | Author URI | `https://project-if.com/` | `https://project-if.jp/` |
| `theme/style.css` | Version | `1.0.0` | `1.0.1` |
| `core/astrea-core.php` | Plugin URI | `https://project-if.com/astrea` | `https://project-if.jp/if-thema/astrea/` |
| `core/astrea-core.php` | Author URI | `https://project-if.com/` | `https://project-if.jp/` |
| `core/astrea-core.php` | Version / `ASTREA_CORE_VERSION` | `1.0.0` | `1.0.1` |
| `theme/readme.txt`, `core/readme.txt` | Stable tag | `1.0.0` | `1.0.1` |

Rationale for Theme URI/Plugin URI landing on `https://project-if.jp/if-thema/astrea/` rather than just the bare domain: per the Theme Review Handbook §9 ("Author URI...must be a page or website about the author... Theme URI...predominately related to the Theme"), and 022-PRE's own §12 recommendation, the Theme/Plugin URI is best pointed at the page that is actually *about the theme* — which already exists and is live (built in Construction 021) — while Author URI points at the organization's own homepage.

## 6. Footer Before / After

**Before** (`theme/parts/footer.html`, unchanged portion): Office Profile identity (office name / address / phone, all Core-bound) + navigation. No copyright line, no Project-if link of any kind existed anywhere in the theme's frontend markup (confirmed by 022-PRE's own audit and reconfirmed here).

**After**: the same content, plus one new block added at the end, inside the same outer Footer group:

```html
<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--medium)">

<!-- wp:paragraph {"textColor":"base","fontSize":"small"} -->
<p class="has-base-color has-text-color has-small-font-size">Theme by <a href="https://project-if.jp/" rel="nofollow" style="color:var(--wp--preset--color--base)" class="has-base-color has-inline-color">Project-if</a></p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

No site copyright line was added or changed — ASTREA had none before this patch and still has none; only the theme-author credit exists, so no misattribution of the user's own site copyright can occur (§4 of the Order's requirement is trivially satisfied, since no copyright statement is displayed by ASTREA at all).

## 7. Exact Author Credit Markup

As above. Design choices, each traced to a specific 022-PRE condition or Order instruction:
- Only the word **"Project-if"** is the link, not the whole phrase (per Order §5).
- **Exactly one** credit link (confirmed on a live clean install: `grep -c 'https://project-if.jp/"'` on the rendered HOME page returns `1`).
- Plain `core/paragraph` + `core/group` blocks — no PHP output, no dynamic block, no JS, no activation hook, no save-time reinsertion logic of any kind.
- `rel="nofollow"` present in the raw markup and confirmed to survive rendering (§8).

## 8. Rendered HTML / `rel` Verification

Direct inspection of the rendered frontend (`curl`, both on the long-running dev instance and on a fresh clean-install environment):

```html
<p class="has-small-font-size wp-block-paragraph has-base-color has-text-color">Theme by <a href="https://project-if.jp/" rel="nofollow" style="color:var(--wp--preset--color--base)" class="has-base-color has-inline-color">Project-if</a></p>
```

`rel="nofollow"` is present exactly as authored, unaltered by WordPress's block rendering pipeline.

**Contrast bug found and fixed during implementation:** the first version of the markup (without the inline `style="color:..."` override on the `<a>` itself) rendered with `getComputedStyle(link).color` = `rgb(17, 17, 17)` against a Footer background of the same `rgb(17, 17, 17)` under the **Modern** Style Variation — the link was completely invisible. Root cause: `theme.json`'s global `styles.elements.link.color` is `var(--wp--preset--color--primary)`, and Modern's `primary` token is a near-black monotone color intended for light-background contexts; a plain paragraph's *text* correctly inherits the Footer's explicit `textColor: base`, but WordPress's link-element style rule takes precedence over that inheritance for the `<a>` itself. Fixed by explicitly setting `textColor: "base"` on the paragraph **and** an inline `style="color:var(--wp--preset--color--base)"` directly on the anchor (matching the serialization WordPress's own Link UI "force a text color" produces). Re-verified: `rgb(255, 255, 255)` (Trust), `rgb(253, 250, 245)` (Natural), `rgb(255, 255, 255)` (Modern) — all high-contrast against their respective dark Footer backgrounds.

## 9. Site Editor Removability

Verified two ways:

1. **Structural**: the credit is built from the same ordinary core blocks (`core/group`, `core/paragraph`) as every other piece of Footer content in this theme — nothing distinguishes it as special or protected.
2. **Behavioral (database-level, matching exactly what the Site Editor's own "Save" does internally)**: created a `wp_template_part` post (tagged with the `wp_theme` taxonomy term `astrea`) containing the Footer with the credit paragraph removed. The frontend correctly stopped showing the credit; reloading again confirmed it did not reappear (no forced reinsertion); deleting that customization post reverted to the theme-file default, which correctly showed the credit again.

## 10. Existing-User / Existing-Installation Behavior

Re-confirms 022-PRE's own experimental finding, with the actual 1.0.1 source this time: a `wp_template_part` database fork completely and permanently overrides the theme's file-based `footer.html`, regardless of what that file contains — meaning a future theme file change cannot reach a site that has already forked its Footer, until/unless the site owner resets it. This was the basis for the mandatory upgrade test in §11.

## 11. Upgrade Scenarios A–D (Mandatory, §19 of the Order)

All four verified experimentally, using the **actual published `v1.0.0` GitHub Release assets** (freshly downloaded, SHA256-confirmed to match the known v1.0.0 values before use) as the starting point, and the newly-built `v1.0.1` ZIPs as the upgrade target, via `wp theme install --force` / `wp plugin install --force` (the same mechanism WordPress's own "Update now" button uses):

| Scenario | Setup | Action | Result |
|---|---|---|---|
| **A — untouched Footer** | Fresh v1.0.0 install, Footer never customized (confirmed: zero `wp_template_part` posts existed) | Upgrade Theme to v1.0.1 | The new default Footer, **including the credit**, appeared immediately after upgrade. HOME HTTP 200, no fatal. **PASS.** |
| **B — customized Footer** | Fresh v1.0.0 install with real Setup-generated content (Office Profile "アップグレードテスト事務所", pages, navigation, HOME), then Footer customized via a `wp_template_part` database fork (simulating a real Site-Editor save) to distinct, non-default content | Upgrade Theme **and** Core to v1.0.1 | The customized Footer content rendered **exactly unchanged** after upgrade; the new credit link was **not** present anywhere on the page (count 0); all site content (office name, HOME) remained intact, HTTP 200. **PASS — this is the decisive proof that the credit is a default, not a forced insertion.** |
| **C — remove credit after v1.0.1** | Exact-ZIP clean install of v1.0.1 (default Footer, credit present) | Create a `wp_template_part` fork identical to the default Footer minus the credit paragraph (simulating deleting the block in the Site Editor and saving) | Credit disappeared; reloading a second time confirmed it stayed gone (no automatic reinsertion). **PASS.** |
| **D — reset to default** | Continuing from Scenario C's state | Delete the customization post (equivalent to the Site Editor's "Clear customizations") | The v1.0.1 default Footer, **with the credit**, reappeared immediately. **PASS.** |

## 12. i18n / POT

The credit text ("Theme by Project-if") is static content inside an HTML block template file. Per the Theme Review Handbook §8 (quoted in 022-PRE): *"All text strings must be translatable using gettext, with the temporary exception of text in HTML template files."* This string is therefore currently exempt from the gettext requirement, and — consistent with that same exemption already governing every other piece of static text already present in `theme/parts/*.html` and `theme/templates/*.html` — was authored the same way, without inventing new translation infrastructure for one string (which would have meant a dynamic PHP-rendered block, rejected in 022-PRE §7 for breaking Theme-only operation).

POT regeneration confirmed a purely metadata-level diff:

| | Theme (`astrea.pot`) | Core (`astrea-core.pot`) |
|---|---|---|
| msgid count before | 60 | 294 |
| msgid count after | 60 | 294 |

The only content changes in either `.pot` file are the `Project-Id-Version` line and the two corrected URI `msgid` strings (auto-extracted from the `style.css`/`astrea-core.php` header comments) — no string was gained or lost, confirming the Footer credit text did not (and, per the exemption, was not expected to) appear in either file.

## 13. WordPress.org Compliance Recheck

Re-run in full, per Order §14:

- **Maximum permitted frontend author credit links**: confirmed exactly **one** on a live rendered page (`grep -c` = 1).
- **Credit destination matches permitted Theme URI/Author URI**: confirmed — the credit links to `https://project-if.jp/`, which is exactly the (now-corrected) `Author URI` declared in `theme/style.css`.
- **Copyright remains site-owner copyright**: no copyright statement of any kind is displayed by ASTREA (unchanged from before this patch) — nothing to misattribute.
- **Credit removable**: confirmed experimentally (§9, §11 Scenario C).
- **No forced reinsertion**: confirmed experimentally (§11 Scenario C, second reload).
- **No SEO keyword anchor**: anchor text is the single word "Project-if" — a brand name, not a keyword phrase.
- **Theme URI / Author URI valid, no dead domain**: confirmed via Theme Check itself, which resolved and displayed both corrected URIs as live links (`https://project-if.jp/`, `https://project-if.jp/if-thema/astrea/`) — see §14.
- **No prohibited upsell behavior**: unchanged from baseline; this patch adds no admin notices, no upsell UI.

No contradiction between current WordPress.org rules and the 022-PRE-approved approach was found; release proceeded.

## 14. Quality Gates

| Gate | Result | vs. 1.0.0 Baseline |
|---|---|---|
| PHP syntax (`php -l`, all files, PHP 8.3) | Clean | — |
| PHPUnit | 399/399 executed, 3 known pre-existing environment errors (unchanged, matches every prior order's baseline) | No regression |
| PHPCS (WordPress Coding Standards + PHP Compat, PHP 8.3) | 67 files, 0 violations | Identical |
| Theme Check | REQUIRED 0 / WARNING 0 / INFO 1 (unchanged Text Domain note); Version correctly shown as 1.0.1; Author URI/Theme URI correctly shown as the corrected, live `project-if.jp` links | No regression, URIs now correct |
| Plugin Check (Core) | Same 3 known non-blocking items as baseline (`.gitkeep`, `data-deletion.php` false-positive, `load_plugin_textdomain()` discouraged) — no new `stable_tag_mismatch` | Identical |
| Secret scan (`theme/`, `core/`, `tools/`) | Clean | — |

## 15. Package

Built via `tools/release/package.sh` (no manual ZIP assembly):

```
Theme version: 1.0.1
Core version:  1.0.1
Built: dist/astrea-theme-1.0.1.zip
Built: dist/astrea-core-1.0.1.zip
```

- Theme ZIP root `astrea/`, 63 files (matches 1.0.0's file count exactly — no files added/removed, only content edits).
- Core ZIP root `astrea-core/`, 61 files (same).
- Contamination scan: none found (no `.git`, `.env`, secrets, dev fixtures, test files, or OS/editor junk).
- Version strings verified directly inside both ZIPs: `Version: 1.0.1`, `Stable tag: 1.0.1`, `ASTREA_CORE_VERSION` = `1.0.1`, and both corrected URIs, in every relevant file.

## 16. Clean Install (Exact v1.0.1 ZIPs)

Fresh disposable WordPress 7.1 / PHP 8.3 environment, never used for development:

| Item | Result |
|---|---|
| Theme only | PASS — install + activate, HOME HTTP 200 |
| Theme + Core | PASS |
| Setup (Office Profile, Pages, Navigation, HOME) | PASS — no `WP_Error` |
| HOME | PASS — office name rendered correctly |
| Footer | PASS |
| Author credit | PASS — present, correct text/href |
| Project-if link | PASS — exactly 1 occurrence on the page |
| `rel` | PASS — `nofollow` present in rendered HTML |
| Site Editor removal (simulated) | PASS — see §9 |
| Core OFF | PASS — HOME HTTP 200, credit link still present (count still 1 — confirms the credit is Theme-only, no Core dependency) |
| Core ON | PASS |
| Responsive | PASS — see §16 below (measured on the long-running dev instance; spot-confirmed on the clean-install instance too) |

## 17. Artifact Filenames / Sizes / SHA256

```
astrea-theme-1.0.1.zip   161,979 bytes   f5b89df96fb73f714ee91de13beee0365e075c7e4773b8d3fe28367aa7ba91be
astrea-core-1.0.1.zip    160,065 bytes   88a526527ebbfdbe85aacc3c4311b35ef3c622df1e6bb1292a86cc0028839531
```

`sha256sum -c SHA256SUMS.txt` against both local files: `OK` / `OK`. These exact bytes were never rebuilt after this point.

## 18. Release Commit

```
commit 8a9f8e47b1079c09c65dad5b36eb4c2b0d1e3882
Prepare ASTREA 1.0.1 patch release
```

`git diff --stat` for this commit: 8 files (`theme/style.css`, `theme/readme.txt`, `core/astrea-core.php`, `core/readme.txt`, both `.pot` files, `theme/parts/footer.html`, plus the new `docs/release/1.0.1_RELEASE_NOTES.md`) — exactly the scope authorized by this Order, nothing else.

## 19. CI

CI run `34011340410` on commit `8a9f8e4` — all 3 required jobs (PHP syntax + Coding Standards, PHPUnit, Theme/Core independence smoke test) green. Confirmed via `gh run watch --exit-status`, exit code 0.

## 20. v1.0.1 Tag

```
$ git rev-parse v1.0.1^{commit}
8a9f8e47b1079c09c65dad5b36eb4c2b0d1e3882
$ git rev-parse v1.0.0^{commit}
c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
$ git rev-parse v1.0.0-rc2^{commit}
5a1f6b40426c5ce4c8a16132ea8f6f979cc8c126
```

`v1.0.1` points exactly to the Release Commit; all three tags are confirmed distinct. Created only after CI Green (§19); pushed to origin; never force-pushed; `v1.0.0` and `v1.0.0-rc2` were not moved or modified.

## 21. GitHub Release

```
title:      ASTREA 1.0.1
tag:        v1.0.1
draft:      false
prerelease: false
url:        https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.1
assets:     astrea-theme-1.0.1.zip, astrea-core-1.0.1.zip, SHA256SUMS.txt
```

Body is `docs/release/1.0.1_RELEASE_NOTES.md`.

## 22. Anonymous Public Download Verification

Because Construction 021 changed this repository's visibility from private to public, this Order deliberately verified publication using **plain, unauthenticated `curl`** rather than `gh` (which uses the Owner's own credentials and would not have caught a repeat of that earlier private-repository mistake):

```
$ curl -sIL https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.1        -> 200
$ curl -sIL .../releases/download/v1.0.1/astrea-theme-1.0.1.zip                            -> 200
$ curl -sIL .../releases/download/v1.0.1/astrea-core-1.0.1.zip                             -> 200
$ curl -sIL .../releases/download/v1.0.1/SHA256SUMS.txt                                    -> 200

$ sha256sum -c SHA256SUMS.txt   (downloaded anonymously)
astrea-theme-1.0.1.zip: OK
astrea-core-1.0.1.zip: OK
```

The anonymously-downloaded `SHA256SUMS.txt` is byte-identical (`diff` — no output) to the locally-inspected `dist/SHA256SUMS.txt`.

## 23. Project-if Update

Confirmed the ASTREA download links on Project-if are **pinned to a specific tag** (`/releases/download/v1.0.0/...`), not `releases/latest` — so they required explicit updating. Updated, in the `~/project-if` repository:

- `if-thema/astrea/index.html`, `en/if-thema/astrea/index.html`: every `1.0.0` occurrence (hero/meta version display, both Theme and Core download `href`s and `data-download-version` attributes, the Release-page link, the SHA256SUMS link, and the final-CTA section) updated to `1.0.1`. Each updated GitHub asset URL was verified reachable via anonymous `curl` *before* committing.
- `index.html`, `en/index.html`: **PROJECT 003** (ASTREA)'s `VERSION` fact and description text updated to `1.0.1`. **PROJECT 002** (If-Thema Small Business FREE, genuinely still `1.0.0`) was correctly left untouched.

**Incidental finding**: at commit time, an unrelated, pre-existing, uncommitted local modification to `terms/index.html` (predating this session, not part of this Order's scope) was found in the working tree. It was excluded from the commit. Because `scripts/deploy.sh` syncs the actual filesystem (not git state), that pending file would otherwise also have been deployed; it was safely set aside with `git stash` for the duration of the deploy and restored immediately afterward — confirmed via `git diff`/`git status` to be back exactly as found, untouched, uncommitted, unaffected by this Order.

Commits: `9ea6254` (product page + PROJECT 003 update), `4d2e310` (history.csv). Project-if has no CI; its own `deploy.sh` dry-run/post-deploy check served as the gate.

## 24. Production Verification

`scripts/deploy.sh`'s own 40-path post-deploy check: all `PASS`. Beyond that, direct Playwright verification against the live `https://project-if.jp/` confirmed: JA/EN ASTREA pages show `1.0.1` and no stale `1.0.0`; JA/EN TOP pages correctly show both `1.0.1` (ASTREA) and `1.0.0` (Small Business, genuinely unchanged); no `localhost` string anywhere; GA `gtag` still loaded on all four pages; the live Theme/Core download button `href`s read directly from the production DOM point to the `v1.0.1` GitHub asset URLs. Those exact URLs were then downloaded fresh (not reusing any earlier download) and their SHA256 matched the recorded values exactly — closing the full verification loop from the live Project-if page, through the live GitHub Release, to the exact bytes inspected during packaging.

## 25. Other Project-if Theme URI Findings (Read-Only)

Per Order §25, inspected (read-only, no modification) the distributed ZIPs of the other two Project-if FREE themes for the same class of defect:

- **If-Thema Small Business FREE (1.0.0)**: its `style.css` header has **no `Author`, `Author URI`, or `Theme URI` field at all** — a different situation from ASTREA's (not a broken domain, but no eligible field for a future credit link under the Handbook's "restricted to Theme URI or Author URI" rule). Noted as a follow-up recommendation, not a defect requiring immediate action.
- **If-Thema My Base (1.0.1)**: already correctly declares `Author URI: https://project-if.jp/` — **no defect found**; this theme does not have the stale-domain problem.

Neither theme was modified in any way during this Order, consistent with §29's explicit prohibition.

## 26. Product-Code Diff Classification

Every changed line in the Release Commit (`8a9f8e4`) is one of exactly three kinds: (1) a corrected URI value, (2) a version-metadata bump, or (3) the new, purely additive Footer credit block. No other PHP logic, CSS, template, pattern, CPT registration, meta field, Setup behavior, Contact behavior, SEO behavior, Schema, Breadcrumb behavior, Navigation behavior, deletion/uninstall behavior, or Style Variation file was touched.

## 27. Known Deferred Findings

- If-Thema Small Business FREE's missing Author/Theme URI fields (§25) — a candidate for a future, separately-authorized Project-if-wide policy rollout, not actioned here.
- The three long-standing, already-accepted non-blocking Plugin Check items (`.gitkeep`, `data-deletion.php` false positive, `load_plugin_textdomain()`) — unchanged, still deferred, not re-litigated by this patch.
- The pre-existing, unrelated `terms/index.html` local modification in `~/project-if` (§23) — left exactly as found; not this Order's to resolve.

## 28. Start / End / Duration

Start: 2026-09-06 12:30 JST (approximate — this Order's work began mid-session, continuing from Construction 022-PRE without a separately logged start timestamp)
End: 2026-09-06 15:00 JST
Duration: approx. 2h30m

## 29. Final Verdict

**A. ASTREA 1.0.1 RELEASED — AUTHOR CREDIT / URI PATCH VERIFIED**

`v1.0.0` remains completely untouched and immutable throughout this Order (`git rev-parse v1.0.0^{commit}` unchanged; no GitHub Release asset for `v1.0.0` was modified or re-verified-and-found-altered). Per the Order's Absolute Stop Gate (§32), this Order does not, and did not, begin WordPress.org submission, Construction 022 (the parent Order) or 023, any further modification to `v1.0.1`, `v1.0.2` development, ASTREA PRO work, or any modification to Small Business/My Base. Waiting for Owner.
