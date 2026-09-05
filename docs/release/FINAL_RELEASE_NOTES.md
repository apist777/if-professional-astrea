# ASTREA 1.0.0 — Final Release Notes

**Status: Stable / Final.** This is the first stable release of ASTREA FREE v1.

**Release date:** 2026-09-05

## Overview

ASTREA is a WordPress Block Theme for Japanese professional-service providers (士業・専門家) — administrative scriveners, judicial scriveners, tax accountants, and similar practices. This 1.0.0 release promotes the previously-published 1.0.0-rc2 Release Candidate to final stable status. Its behavior is unchanged from RC2; only version identity and release metadata have changed. RC2 was independently verified through a full pre-release inspection, a public release-artifact integrity check, and a real-user acceptance trial performed against the actual published Release download.

## Requirements

- WordPress 7.0 or later (tested up to 7.1).
- PHP 8.3 or later (8.4+ recommended).
- ASTREA Core (plugin) is optional but recommended — see below.

## Theme / Core Relationship

**ASTREA** (the Theme) works safely on its own — no fatal errors, no broken page shell — whether ASTREA Core is installed, not installed, or deactivated. **ASTREA Core** (the plugin) is the official recommended companion: it owns office information that should survive a theme change, and the site-wide dynamic content (services, professional profiles, case studies, results, testimonials, pricing, FAQ, the contact form). The guiding principle: Core is recommended, but the Theme is never held hostage by it.

## Installation Outline

1. Install and activate the ASTREA Theme.
2. (Recommended) Install and activate ASTREA Core.
3. Visit the Office Profile admin screen — a setup checklist there offers to generate the standard starter pages (Office/Price/Contact), a basic Navigation menu, and a HOME page, all as editable drafts/content you can review before publishing.
4. Fill in your office information, services, professional profiles, pricing, and FAQ as needed.

## Major Capabilities

- Responsive Visual Design (v3) across HOME and all internal pages, verified from 320px to 1920px with zero horizontal overflow.
- Office Profile, Professional profiles, Services, Case studies, Results, Pricing, FAQ, and Customer Testimonials — each with dedicated display blocks and templates.
- Setup support: one-time, idempotent generation of starter pages, Navigation, and a HOME page, reviewable before publishing.
- Contact workflow with server-side validation, nonce protection, non-public inquiry storage, and admin-side inquiry management (list, CSV export, retention setting).
- SEO metadata integration (meta description, OGP, structured data), with automatic hand-off to Yoast SEO / All in One SEO / Rank Math / SEOPress when one of those is active.
- Three built-in Style Variations (Trust / Natural / Modern), each a complete, ready-to-use design.
- Accessibility and responsive-layout refinements validated via keyboard navigation (skip link, heading hierarchy) and mobile viewport testing.

## Known Post-v1 Items

None of the following are release-blocking. They are documented for transparency and are candidates for a future release, not defects in 1.0.0:

- **WordPress 7.1 `core/group`/`core/cover` Block Editor validation warning**: in some cases WordPress's own Site Editor may show a "Block contains unexpected or invalid content" notice on certain Group/Cover blocks. This is a WordPress Core behavior, not an ASTREA-specific defect — no data loss occurs. If you see it, avoid using "Attempt recovery" unless you recognize the content being recovered.
- **Professional Archive excerpt display**: when a Professional profile has no content and no excerpt, its Archive card shows a shorter card without a placeholder image or excerpt line — expected graceful degradation, not a defect.
- **Price "Group" label**: each Price entry's optional Group is shown as a small label on that entry, not as a sorted/bucketed section heading. True grouped sections are a candidate for a future release.
- **Professional Profile field discoverability**: some Professional profile detail fields (including the "show as representative" flag) live inside the Block Editor's standard, collapsible "Meta Boxes" panel near the bottom of the screen — a general WordPress Core editing-screen pattern, not something ASTREA changes. New users may need to scroll down and expand this panel to find them.

## Checks Performed

- Full automated suite: PHPUnit, PHPCS, official WordPress.org Theme Check and Plugin Check — all clean, no regressions from the Release Candidate baseline.
- A complete, explicit comparison between the accepted Release Candidate's source and this final release's source, confirming the only differences are version metadata and changelog text — no functional, visual, or template code changed.
- A genuinely clean WordPress install using the exact packaged Release ZIPs, in a disposable environment never used for development: Theme-only install, Theme+Core install, Setup, Contact form (valid submission and invalid-nonce rejection), Core deactivation/reactivation with full data persistence, and responsive layout sanity from 320px to 1920px.
- A prior, independent real-user acceptance trial against the actual published Release Candidate download (fresh SHA256-verified download, disposable environment, full real-UI Setup-through-Publish workflow).

## Support / Project

Project homepage: https://project-if.com/astrea

## Artifacts

- `astrea-theme-1.0.0.zip` (ASTREA Theme)
- `astrea-core-1.0.0.zip` (ASTREA Core)
- `SHA256SUMS.txt` (checksums for both)

SHA256:

```
123a5ccffa8f0465883ef209489b1654c4abc9b6ed7230b9104369cbbc59bdb4  astrea-theme-1.0.0.zip
ecd34699fa3662e8b63d9517669d80c829dbb2d9979b71c41c292105c6d6cd10  astrea-core-1.0.0.zip
```

Note: the "Source code" archives GitHub generates automatically for this tag are a raw copy of the repository, not the packaged, installable ASTREA Theme/Core ZIPs above. Please install using `astrea-theme-1.0.0.zip` and `astrea-core-1.0.0.zip`.
