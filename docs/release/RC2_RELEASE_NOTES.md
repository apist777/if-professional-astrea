# ASTREA FREE v1 — 1.0.0-rc2 Release Notes

**This is a pre-release (Release Candidate), not a final release.**

## Position of this Release Candidate

RC2 supersedes RC1 (which was prepared and inspected but never tagged or published as a GitHub Release). Since RC1's metadata was written, the project completed the remaining Visual v3 design work across HOME and every internal page, then ran a final integrated acceptance inspection (Construction Order 017) before sealing this candidate.

## Main Improvements Since RC1

- Visual design and responsive layout refinements across HOME and internal pages.
- Improved internal page (Single/Archive) heading and spacing consistency.
- Improved HOME lower-section content presentation (Price, FAQ, Voice, Flow, Closing CTA).
- Improved search-result breadcrumb labeling — it now reflects the searched keyword instead of a generic "Archives" label (this also corrects the equivalent BreadcrumbList structured-data entry, since both share the same resolver).
- Improved release/package metadata (version/readme/POT synchronization).
- General stability, accessibility, and compatibility refinements.

## Requirements

- WordPress 7.0 or later (tested up to 7.1).
- PHP 8.3 or later (8.4+ recommended).
- ASTREA Core (plugin) is optional but recommended — see below.

## Theme / Core Relationship

**ASTREA** (the Theme) works safely on its own — no fatal errors, no broken page shell — whether ASTREA Core is installed, not installed, or deactivated. **ASTREA Core** (the plugin) is the official recommended companion: it owns office information that should survive a theme change, and the site-wide dynamic content (services, professional profiles, case studies, results, testimonials, pricing, FAQ, the contact form). The guiding principle: "Core is recommended — but the Theme is never held hostage by it."

## Installation Outline

1. Install and activate the ASTREA Theme.
2. (Recommended) Install and activate ASTREA Core.
3. Visit the Office Profile admin screen — a setup checklist there offers to generate the standard starter pages (Office/Price/Contact), a basic Navigation menu, and a HOME page, all as editable drafts/content you can review before publishing.
4. Fill in your office information, services, professional profiles, pricing, and FAQ as needed.

## Known Issues

- **WordPress 7.1 `core/group`/`core/cover` Block Editor validation warning**: in some cases WordPress's own Site Editor may show a "Block contains unexpected or invalid content" notice on certain Group/Cover blocks. This is a WordPress Core behavior, not an ASTREA-specific defect — no data loss occurs. If you see it, avoid using "Attempt recovery" unless you recognize the content being recovered.
- **Professional Archive excerpt display**: when a Professional profile has no content and no excerpt, its Archive card shows a shorter card without a placeholder image or excerpt line, rather than a defect — this is expected graceful degradation, not release-blocking.
- **Price "Group" label**: each Price entry's optional Group is shown as a small label on that entry, not as a sorted/bucketed section heading. True grouped sections are a candidate for a future release, not part of FREE v1.

None of the above are classified as release-blocking; all were re-confirmed during the Construction Order 017 final inspection.

## Checks Performed Before Sealing This Candidate

- Full automated suite: PHPUnit, PHPCS, official WordPress.org Theme Check and Plugin Check.
- Full route audit (HOME, static pages, all CPT archives/singles, Search, 404).
- Visual regression across 1920/1440/1366/1024/768/375/320px, on all three Style Variations (Trust/Natural/Modern), with zero horizontal overflow.
- Long-Japanese-content stress testing (with Fixture backup/restore discipline).
- Core OFF/ON safety (Decision 021).
- Setup / first-run acceptance from a clean install (idempotent page/navigation/home generation).
- Contact form security (valid submission, invalid-nonce rejection, non-public inquiry storage).
- A genuinely clean WordPress install using the exact packaged Release ZIPs, in a disposable environment never used for development.

## Artifacts

- `astrea-theme-1.0.0-rc2.zip` (ASTREA Theme)
- `astrea-core-1.0.0-rc2.zip` (ASTREA Core)
- `SHA256SUMS.txt` (checksums for both, recorded in the Construction 018 report)

SHA256 values are recorded in `docs/research/2026-09-05_construction_018_rc2_release_preparation_report.md` and in this release's GitHub Release description.
