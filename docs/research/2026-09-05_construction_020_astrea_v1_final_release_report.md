# Construction Order 020 — ASTREA FREE v1.0.0 Final Release / Production Sealing

Date: 2026-09-05
Actor: Chloe (Claude)
Status at close: **A. ASTREA 1.0.0 RELEASED — STABLE GITHUB RELEASE VERIFIED**

## 1. Executive Result

ASTREA FREE v1.0.0 (Theme + Core) has been published as a stable, non-pre-release, non-draft GitHub Release, promoted from the Owner-accepted `v1.0.0-rc2` Release Candidate with **release-metadata-only changes**. Every quality gate passed with zero regression from the RC2 baseline. The exact final ZIPs were clean-install tested before upload, and the publicly re-downloaded assets were confirmed byte-for-byte identical to the inspected artifacts. No product behavior changed. Project-if deployment, WordPress.org submission, and Construction 021 were not performed, per this Order's explicit hard stop.

## 2. Accepted RC2 Baseline

- Tag: `v1.0.0-rc2`
- Target commit: `5a1f6b4` ("Prepare ASTREA 1.0.0-rc2 release candidate")
- Accepted via Construction 017 (inspection), 018 (release), 019 (real-user trial; verdict: RC2 OWNER ACCEPTED).

## 3. Pre-Flight State

```
git status --short   -> only the long-standing, pre-existing untracked
                         Zone.Identifier file (unrelated to this Order)
git log -10 --oneline -> ef7afe0 (HEAD), 8f737c3, 2d6dfe9, 5a1f6b4, ...
git tag --list        -> v1.0.0-rc2 (no v1.0.0 existed yet)
git branch --show-current -> main
main / origin sync    -> 0 0 (fully synced)
HEAD                   -> ef7afe034de6ef85327530c603471f2344481284
v1.0.0-rc2 commit      -> 5a1f6b40426c5ce4c8a16132ea8f6f979cc8c126
```

Version baseline confirmed exactly as the Order required before any change:

| | Theme | Core |
|---|---|---|
| Version / Stable tag | 1.0.0-rc2 | 1.0.0-rc2 |
| Requires at least | 7.0 | 7.0 |
| Tested up to | 7.1 | 7.1 |
| Requires PHP | 8.3 | 8.3 |

## 4. RC2 → Final Diff Classification (Baseline Divergence Check)

Before making any change, `git diff --stat 5a1f6b4 HEAD -- theme/ core/ tools/release/` was run and returned **empty** — zero product-code changes existed between the accepted RC2 commit and the pre-Finalization `HEAD` (`ef7afe0`). The commits in between (`2d6dfe9`, `8f737c3`, `ef7afe0`) were confirmed to be documentation/report/screenshot/HISTORY-only, matching Construction 018/019's own transparent record. **The accepted RC2 product baseline had not diverged** — Finalization was authorized to proceed.

## 5. Version Promotion

Authoritative version sources identified and bumped from `1.0.0-rc2` to `1.0.0`:

- `theme/style.css` — `Version:`
- `theme/readme.txt` — `Stable tag:`
- `core/astrea-core.php` — `Version:` header and `ASTREA_CORE_VERSION` constant
- `core/readme.txt` — `Stable tag:`
- `theme/languages/astrea.pot`, `core/languages/astrea-core.pot` — `Project-Id-Version` (via regeneration)

Remaining `1.0.0-rc2` matches after the bump, classified:

| Location | Classification | Correct to remain? |
|---|---|---|
| `HISTORY.csv` rows for 018/019 | Historical record | Yes |
| `docs/release/RC2_RELEASE_NOTES.md` | Historical release note, specifically about RC2 | Yes |
| `docs/research/2026-09-05_construction_018_*.md`, `..._019_*.md` | Historical Construction reports | Yes |
| `theme/readme.txt` / `core/readme.txt` `= 1.0.0-rc2 =` Changelog entries | Historical changelog entry, kept as history below the new `= 1.0.0 =` entry | Yes |

No blind replace was performed; each occurrence was inspected individually.

## 6. Changelog

New, concise, user-facing `= 1.0.0 =` Changelog entries were added above the existing `= 1.0.0-rc2 =` entries (which remain unmodified as history) in both `theme/readme.txt` and `core/readme.txt`. Entries state this is the initial stable release and summarize actually-delivered capabilities; no internal Construction numbering appears in either.

## 7. POT / i18n Final Sync

Regenerated via `wp i18n make-pot` inside the wp-env `cli` container, after the version bump.

| | Before (RC2) | After (Final) |
|---|---|---|
| Theme msgid count | 60 | 60 |
| Core msgid count | 294 | 294 |

Diff confirmed pure `Project-Id-Version` + `POT-Creation-Date` changes only — no translatable string added, removed, or altered. Core's regeneration reproduced one pre-existing, non-blocking wp-cli warning (a string reused across 3 files with different translator comments) — present in the source already, unrelated to this Order.

## 8. Source-Diff Sealing Check

`git diff` was run on every changed file (`theme/style.css`, `theme/readme.txt`, `core/astrea-core.php`, `core/readme.txt`) and inspected line-by-line. Every changed line is either:
- an authoritative version-metadata line (`Version:`, `Stable tag:`, `ASTREA_CORE_VERSION`), or
- a newly-added, purely additive Changelog entry.

**Answer to "What product behavior changed between accepted RC2 and Final?": NONE.**

## 9. Quality Gates

| Gate | Result | vs. RC2 Baseline |
|---|---|---|
| PHP syntax (`php -l`, all files, PHP 8.3) | Clean | — |
| PHPUnit | 399/399 executed, 3 known pre-existing environment errors (`wp-phpunit` attachment-factory `"file"` key) | Identical to 017/018 baseline — no regression |
| PHPCS (WordPress Coding Standards + PHP Compat, PHP 8.3) | 67 files, 0 violations | Identical |
| Theme Check | REQUIRED 0 / WARNING 0 / INFO 1 (Text Domain note, long-accepted) | Identical |
| Plugin Check (Core) | Same 3 known non-blocking items as RC2 baseline (`.gitkeep` hidden-file, `data-deletion.php` false-positive direct-access-check, `load_plugin_textdomain()` discouraged-since-4.6). **No new `stable_tag_mismatch`** — both readme.txt Stable tags were bumped consistently. | Identical |
| Secret scan (`theme/`, `core/`, `tools/`) | Clean | — |

No release-blocking finding. No regression from RC2.

## 10. Package Contents

Built via `tools/release/package.sh` (no manually-assembled ZIP):

```
Theme version: 1.0.0
Core version:  1.0.0
Built: dist/astrea-theme-1.0.0.zip
Built: dist/astrea-core-1.0.0.zip
Checksums written: dist/SHA256SUMS.txt
```

- Theme ZIP root: `astrea/` (63 files) — correct.
- Core ZIP root: `astrea-core/` (61 files) — correct.
- Contamination scan (`.git`, `.env`, secrets, credentials, tests, dev fixtures, screenshots, node_modules, OS/editor junk): **none found**. The only match against a "screenshot" pattern was `astrea/screenshot.png` — WordPress's own required theme-preview asset, not a development artifact.
- Version strings verified directly inside the ZIPs (`style.css`, `readme.txt` Stable tag ×2, `astrea-core.php` Version + constant, `readme.txt` Stable tag): all read `1.0.0`.

## 11. Artifact Filenames / Sizes

| File | Size (bytes) |
|---|---|
| `astrea-theme-1.0.0.zip` | 161,707 |
| `astrea-core-1.0.0.zip` | 159,972 |

## 12. SHA256

```
123a5ccffa8f0465883ef209489b1654c4abc9b6ed7230b9104369cbbc59bdb4  astrea-theme-1.0.0.zip
ecd34699fa3662e8b63d9517669d80c829dbb2d9979b71c41c292105c6d6cd10  astrea-core-1.0.0.zip
```

`sha256sum -c SHA256SUMS.txt` against both files: `OK` / `OK`. These exact ZIPs were never rebuilt after this point (Byte-Identity Discipline maintained — confirmed again immediately before tagging: `sha256sum -c` still reported `OK` on both).

## 13. Final Clean-Install Matrix (Exact Final ZIPs)

Environment: disposable `mysql:8.0` + `wordpress:php8.3-apache` containers on the existing wp-env docker network, WordPress 7.1 core, PHP 8.3.33 confirmed via `wp --info`. Never used for development.

| Item | Result |
|---|---|
| A. Theme ZIP install | PASS |
| B. Theme activation | PASS |
| C. Theme-only frontend | PASS — HOME HTTP 200, no Fatal |
| D. Core ZIP install | PASS |
| E. Core activation | PASS |
| F. Setup (Office Profile save) | PASS — via Core's own `OfficeProfile\get_office_profile()` API, `astrea_core_office_profile` option confirmed round-tripped correctly |
| G. Generated Pages | PASS — `Setup\generate_pages()` → About/Price/Contact created, no `WP_Error` |
| H. Navigation | PASS — `Setup\generate_navigation()` → menu created, no `WP_Error` |
| I. HOME | PASS — `Setup\generate_home_page()` → HOME created and auto-set as front page, no `WP_Error` |
| J. Representative dynamic block | PASS — FLOW block renders with the Construction 016K enlarged step-number treatment; zero-content sections (Service/CASE/Price/FAQ/Voice — none created in this lighter matrix) correctly self-hide per Decision 028, not a defect |
| K. Contact render | PASS — real form present and functional at the generated Contact page |
| L. Valid Contact submission | PASS — success message shown; inquiry saved as `private`-status `astrea_inquiry` post |
| M. Invalid nonce rejection | PASS — forged POST to `admin-post.php` with a fabricated `astrea_contact_nonce` redirected to `?astrea_contact_error=1`; no new inquiry post was created (verified: inquiry count unchanged) |
| N. Core OFF | PASS — HOME HTTP 200, no Fatal/critical error |
| O. Core ON | PASS — reactivated cleanly |
| P. Persisted data restored | PASS — Office Profile and the earlier inquiry both fully intact after the OFF/ON cycle |
| Q. Representative internal page (Service Single) | PASS — created a Service post, verified rendering and `body.single` class (016L's spacing fix scope) present |
| R. 375px sanity | PASS — 0px horizontal overflow (HOME + Price) |
| S. 320px sanity | PASS — 0px horizontal overflow (HOME + Price) |
| T. 1440px sanity | PASS — 0px horizontal overflow (HOME + Price) |
| U. 1920px sanity | PASS — 0px horizontal overflow (HOME + Price) |

Per the Order's own guidance, the full manual Construction 019 site-building exercise was not repeated — 019 already accepted the UX; this matrix proves the Final package still behaves like the accepted RC2.

## 14. RC2 / Final Behavioral Equivalence

Equivalence is established two ways:
1. **By construction** — §8/§9's line-by-line source diff proved zero product-code bytes differ between RC2 and Final beyond version metadata and changelog text, in `theme/`, `core/`, and `tools/release/`. Identical source renders identically for identical content/state.
2. **By direct observation** — HOME, Service Single (with the 016L header-spacing fix visibly correct), Price, Contact (render + valid submission + invalid-nonce rejection), Setup (page/navigation/home generation), and Core OFF/ON behavior were all directly exercised against the Final package in §13 and rendered/behaved exactly as expected from the established RC2 baseline. No unexplained visual or functional delta was observed.

## 15. Final Release Commit

```
commit c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
Prepare ASTREA 1.0.0 final release
```

`git diff --stat` for this commit: 7 files changed (`theme/style.css`, `theme/readme.txt`, `core/astrea-core.php`, `core/readme.txt`, both `.pot` files, plus the new `docs/release/FINAL_RELEASE_NOTES.md`) — matching exactly the scope authorized in §3 of the Order.

## 16. CI

CI run `33940899529` on commit `c6c2dde` — all 3 required jobs green:
- PHP syntax + Coding Standards — PASS
- PHPUnit (ASTREA Core) — PASS
- Theme / Core independence smoke test — PASS

Confirmed via `gh run watch 33940899529 --exit-status --interval 15`, exit code 0.

## 17. v1.0.0 Tag

```
$ git rev-parse v1.0.0^{commit}
c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
$ git rev-parse c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
c6c2dde84f49a3e4109ed245ea5066efd81a5a1e
```

Match confirmed exactly. Tag object `5f0e50d` (annotated) is distinct from `v1.0.0-rc2`'s tag object `7f19929`, and their target commits (`c6c2dde` vs `5a1f6b4`) are likewise distinct. Tag was created only after CI Green (§16), and pushed to origin (`git push origin v1.0.0` → `* [new tag] v1.0.0 -> v1.0.0`). No force-push was used; no existing tag was moved or overwritten.

## 18. GitHub Release

```
title:      ASTREA 1.0.0
tag:        v1.0.0
draft:      false
prerelease: false
url:        https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.0
assets:     astrea-theme-1.0.0.zip, astrea-core-1.0.0.zip, SHA256SUMS.txt
```

Created via `gh release create v1.0.0 <3 assets> --title "ASTREA 1.0.0" --notes-file docs/release/FINAL_RELEASE_NOTES.md` (no `--target` needed — the tag was already pushed and unambiguous). Body is `docs/release/FINAL_RELEASE_NOTES.md`, which explicitly notes that GitHub's auto-generated "Source code" archives are not the official installable packages.

## 19. Public Download Re-Verification

```
$ gh release download v1.0.0 --repo apist777/if-professional-astrea
astrea-theme-1.0.0.zip   161,707 bytes
astrea-core-1.0.0.zip    159,972 bytes
SHA256SUMS.txt

Downloaded Theme SHA:  123a5ccffa8f0465883ef209489b1654c4abc9b6ed7230b9104369cbbc59bdb4  -> matches inspected ZIP exactly
Downloaded Core SHA:   ecd34699fa3662e8b63d9517669d80c829dbb2d9979b71c41c292105c6d6cd10  -> matches inspected ZIP exactly
Downloaded SHA256SUMS.txt content: byte-identical to dist/SHA256SUMS.txt (diff: no output)
```

**Result: PASS.** No mismatch of any kind.

## 20. Post-Publication Install Sanity (Optional, Performed)

Using the re-downloaded public assets (not the local `dist/` copies), on a second fresh disposable WordPress 7.1 / PHP 8.3 environment:

- Theme install + activate: PASS, `wp theme get astrea --field=version` = `1.0.0`
- Core install + activate: PASS, `wp plugin get astrea-core --field=version` = `1.0.0`
- HOME HTTP 200 before and after Core activation
- No Fatal in `debug.log`

Disposable containers removed after use.

## 21. Product-Code Change Statement

Product-code changes made during this Order, beyond release metadata: **zero.** The only files touched were `theme/style.css`, `theme/readme.txt`, `core/astrea-core.php`, `core/readme.txt` (version/Stable-tag/Changelog only), both `.pot` files (version-metadata-only regeneration), and the new `docs/release/FINAL_RELEASE_NOTES.md`. No PHP logic, CSS, theme.json rule, JavaScript, template, pattern, block-rendering, CPT-registration, meta-field, Setup-behavior, Contact-behavior, SEO-behavior, Schema, Breadcrumb-behavior, Navigation-behavior, deletion/uninstall-behavior, Style Variation, demo/fixture content, or release-packaging-logic file was modified.

## 22. Known Deferred LOW / Post-v1 Items

Carried forward from Construction 019 without new evidence, not converted into a blocker:

- **Professional Profile field discoverability** — some Professional profile detail fields (including the "show as representative" flag) sit inside the Block Editor's standard, collapsible "Meta Boxes" panel — a general WordPress Core editing-screen pattern, not ASTREA-specific. Classification: LOW, documented in `FINAL_RELEASE_NOTES.md`.
- The three items already documented as non-blocking since Construction 017 (WP 7.1 `core/group`/`core/cover` editor validation warning; Professional Archive empty-excerpt graceful degradation; Price "Group" label as a per-entry tag rather than a sorted section) are likewise carried forward unchanged, also documented in `FINAL_RELEASE_NOTES.md`.

None were fixed during this Order, per its explicit prohibition on opportunistic changes.

## 23. Project-if Deployment Status

**Not deployed.** Not authorized by this Order.

## 24. WordPress.org Status

**Not submitted.** Not authorized by this Order.

## 25. git / main / origin State

- `main` and `origin/main` fully synchronized after pushing the Final Release Commit.
- Working tree at report time: only this Order's own new/modified files (report, HISTORY.csv update — committed separately, see below) plus the same long-standing, pre-existing, unrelated untracked `docs/research/references/ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier` file present since before this Order.
- `v1.0.0-rc2` tag and its GitHub Release: untouched throughout this Order.

## 26. Start / End / Duration

Start: 2026-09-05 11:05 JST (approximate — not separately logged at task receipt, immediately following Construction 019's report delivery at 10:56 JST)
End: 2026-09-05 12:16 JST
Duration: approx. 1h11m

## 27. Sequencing Note (HISTORY / Tag Relationship)

Consistent with the transparent model established in Construction 018: this report and the `HISTORY.csv` row for Order 020 are being committed in a commit **after** the Final Release Commit, Tag, and GitHub Release were already created — this documentation commit is **not** what `v1.0.0` points to. The tag points exclusively to the Final Release Commit `c6c2dde`, which is recorded as the `Commit` value in the HISTORY.csv row. This documentation commit makes no product-code, Release Metadata, or Artifact changes.

## 28. Completion Checklist

- [x] Accepted RC2 baseline proven unchanged functionally
- [x] Theme version = 1.0.0
- [x] Core version = 1.0.0
- [x] Stable tags = 1.0.0
- [x] Changelogs finalized
- [x] POT synchronized
- [x] RC2→Final behavioral diff = NONE
- [x] PHPUnit PASS (399/399, 3 known baseline errors)
- [x] PHPCS PASS (0 violations)
- [x] Theme Check REQUIRED 0 / WARNING 0
- [x] Plugin Check acceptable (same 3 known non-blocking items as RC2)
- [x] Secret scan PASS
- [x] Final `package.sh` PASS
- [x] Theme Final ZIP clean
- [x] Core Final ZIP clean
- [x] SHA256 generated
- [x] SHA256 locally verified
- [x] Exact Final ZIP clean-install PASS (A–U)
- [x] RC2/Final representative equivalence PASS
- [x] Final Release Commit created (`c6c2dde`)
- [x] Final Release Commit CI Green
- [x] v1.0.0 annotated tag created
- [x] Tag points exactly to Final Release Commit
- [x] Tag pushed
- [x] GitHub Release ASTREA 1.0.0 published
- [x] Release is NOT marked Pre-release
- [x] Correct 3 assets attached
- [x] Public Theme ZIP re-download SHA match
- [x] Public Core ZIP re-download SHA match
- [x] Public SHA256SUMS verified
- [x] Report complete
- [x] HISTORY complete
- [x] main/origin synchronized
- [x] No unintended working-tree changes
- [x] Project-if NOT deployed
- [x] WordPress.org NOT submitted

## 29. Final Verdict

**A. ASTREA 1.0.0 RELEASED — STABLE GITHUB RELEASE VERIFIED**

ASTREA FREE v1.0.0 is now a verified stable GitHub Release: https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.0

Per the Order's Absolute Stop Gate, this Order does not, and did not:
- start Construction 021
- deploy Project-if
- submit WordPress.org
- start ASTREA PRO implementation
- modify the `v1.0.0` tag
- replace the published Final artifacts

Waiting for Owner.
