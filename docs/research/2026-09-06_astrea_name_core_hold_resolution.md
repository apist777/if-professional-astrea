# Construction Order 022B — ASTREA WordPress.org Hold Resolution (GATE 1 Only)

Date: 2026-09-06
Actor: Chloe (Claude)
Mode: Two-gate research/resolution Order
Status at close: **GATE 1 STOPPED — GATE 2 NOT EXECUTED**

## 1. Executive Result

GATE 1 (name-risk research) was completed in full. Based on that research, this Order's own rules require stopping before GATE 2: the evidence supports a **HIGH** name-risk rating (medium confidence), which per this Order's own §36 Stop Conditions ("STOP immediately after GATE 1 if: name risk = HIGH or UNACCEPTABLE... evidence is materially ambiguous and Owner judgment is needed") mandates ending the Order here. **GATE 2 (Core recommendation compliance) was not started.** No product code was touched, no version was bumped, no release was created.

## 2. Baseline

```
git status --short   -> only the long-standing, pre-existing untracked
                         Zone.Identifier file (unrelated)
branch                -> main
HEAD                   -> 3416a085165b63379dd4bf96dd480007fa12ead4
main / origin sync    -> 0 0
Tags                   -> v1.0.0-rc2 (5a1f6b4), v1.0.0 (c6c2dde),
                           v1.0.1 (8a9f8e4), v1.0.2 (64f870c) —
                           all confirmed pointing to their known commits
Stable GitHub Release  -> v1.0.2, draft=false, prerelease=false
Theme version           -> 1.0.2
Core version            -> 1.0.1
```

Prior evidence read: `docs/research/2026-09-06_astrea_wordpress_org_submission_preflight.md` (Construction 022, this Order's direct prerequisite) — its WPORG-001 (name similarity) and WPORG-002 (Core recommendation) findings were treated as the starting point for this Order's own independent re-research, not assumed correct without re-verification, per the Order's own §2 instruction.

## 3. Current Name Rules (re-fetched 2026-09-06)

Source: [Required — Make WordPress Themes handbook](https://make.wordpress.org/themes/handbook/review/required/), page self-reports "Last updated: June 9, 2026" (i.e., current, unchanged since Construction 022's own fetch of the same page one day earlier).

**Section 7, "Naming, spelling, and trademarks" — quoted verbatim:**

> The themes team can decline themes based on the name and request that the name is changed if they decide that the name is inappropriate or **too similar to an existing theme or brand.**
>
> * Theme names must not use: WordPress, Theme, Twenty*
> * Spell "WordPress" correctly in all public-facing text: all one word, with both an uppercase W and P
> * No violation of trademarks
> * The theme logo and banner image must not include the WordPress logo

**Section 14, "Theme author and theme upload restrictions"** — no additional naming-collision language beyond what's already covered in Section 7; this section concerns account/upload-frequency restrictions, not naming.

**A secondary, unverified claim was also investigated**: a community source (not make.wordpress.org itself) states that WordPress.org's upload-time name check tests "against the entire WordPress ecosystem," and a name used elsewhere (GitHub, ThemeForest, etc.) with **over 50 active installations becomes unavailable**. This was **not corroborated by any primary WordPress.org source** during this research (the historical 2013 "Clarifying Guidelines for Theme Name" post, fetched directly, discusses only prohibited generic/reserved terms and says nothing about this threshold). It is recorded here as a plausible but **unconfirmed** additional risk factor, not as established policy — consistent with this Order's own instruction to prioritize primary sources.

**Implication for ASTREA**: the current, primary-source rule is unambiguous — the Theme Review Team retains **explicit, named discretion** to reject a name for being "too similar to an existing theme or brand," independent of exact-slug availability.

## 4. Astra — Factual Baseline (fetched directly from wordpress.org, 2026-09-06)

```
Theme Name:            Astra
Slug:                  astra
Author:                Brainstorm Force
Active Installations:  1+ million (shown directly on the WordPress.org listing)
Rating:                4.9 / 5 (6,280 five-star reviews)
Last Updated:          August 19, 2026 (actively, currently maintained)
Stated audience:       "professional web designers, solopreneurs, small
                        businesses, eCommerce, membership sites and any
                        type of website" (Astra's own tagline)
```

Astra is not merely "a theme with a similar name" — it is one of the most widely recognized, actively maintained, top-rated themes in the entire WordPress.org directory, officially displayed with its 1M+ install count directly on its own listing page.

## 5. Directory Similarity — Practice Research

A genuine, good-faith search was made for existing precedent of a name this close to a dominant brand successfully coexisting in the directory (per this Order's own instruction not to cherry-pick only favorable examples):

| Existing Theme | Comparison Theme | Slugs | Relationship | Evidence | Relevance |
|---|---|---|---|---|---|
| Astra (astra) | AstroStar (astrostar) | `astra` / `astrostar` | Shares the "Astr-" root, both live in the directory | Both confirmed live via direct fetch | **Weak precedent** — "AstroStar" is a longer, structurally distinct compound word ("Astro" + "Star"), not a near-homophone single word the way "Astrea" is to "Astra" |
| Astra (astra) | "Astera" (searched) | `astera` | — | **HTTP 404** — no theme currently exists at this slug | Confirms the directory is not saturated with every possible "Astr-" variant, but does not establish that a near-homophone would be *accepted* if submitted |

**No example was found** of a theme name that is a near-homophone / near-anagram of an existing, dominant (1M+ install) theme's name successfully coexisting in the current WordPress.org Theme Directory. This absence, after a genuine search, is itself weak-to-moderate evidence against assuming safety — not proof of rejection, but the complete lack of a reassuring precedent.

## 6. ASTREA vs. Astra — Structured Comparison

| Axis | Astra | ASTREA |
|---|---|---|
| Orthographic | A-s-t-r-a (5 letters) | A-s-t-r-e-a (6 letters) — Astra with "e" inserted before the final "a" |
| Phonetic (English) | /ˈæstrə/ | /əˈstriːə/ or /æsˈtreɪə/ — shares the identical "Astr-" onset; the two are easily mis-heard/mis-typed for each other, especially in casual speech or when read aloud |
| Phonetic (Japanese, ASTREA's actual market) | アストラ (Asutora) | アストレア (Asutorea) — differs by inserting only "レ" (re); the two katakana renderings are visually and aurally close |
| Visual (as plain text, no logo comparison performed) | ASTRA | ASTREA — a 4-letter shared prefix "ASTR" out of 5-6 total letters |
| Product category | WordPress theme | WordPress theme (identical category) |
| Branding | "Astra" (single word, no qualifier in the WP.org listing itself) | "If Professional ASTREA" (full brand includes a distinguishing qualifier not present in the bare slug/Theme Name "ASTREA") |
| Slug | `astra` | `astrea` (confirmed unregistered, §7 of Construction 022) |
| Author | Brainstorm Force | Project-if |
| Positioning | Broad/horizontal: "solopreneurs, small businesses, eCommerce, membership sites, **professional web designers**, any type of website" | Narrow/vertical: Japanese licensed professional-service providers (行政書士, 社会保険労務士, 税理士, etc.) specifically |

**Genuine differentiators** (not to be dismissed): the full public brand "If Professional ASTREA" carries a qualifier Astra's own branding lacks; the product's actual positioning is meaningfully narrower and more vertical than Astra's broad general-purpose one; the author names are unrelated. **Genuine similarity** (not to be minimized): the bare Theme Name/slug that WordPress.org itself would display and index — "ASTREA" / `astrea` — has no qualifier at that level, and is orthographically/phonetically a near-superset of "Astra" (adding one letter). Astra's own stated audience explicitly includes "professional web designers" — a phrase that overlaps, at least linguistically, with ASTREA's own "professional" framing.

## 7. Public Search / Confusion Evidence

A direct web search for `"If Professional ASTREA" wordpress theme` (2026-09-06) returned **zero results about ASTREA itself** — every single result returned concerned "Astra" (Brainstorm Force's theme): review sites, the official wpastra.com site, WordPress.com's own theme listing for Astra, etc. This is concrete, current evidence that:

- ASTREA currently has no independent search presence of its own (expected — the product is brand new and has not yet been indexed anywhere close to Astra's scale).
- **The practical effect for any near-term searcher is total confusion-adjacent dominance by Astra** — a person searching for information about "ASTREA" today would, in practice, encounter only Astra-related content.

This is not proof that a WordPress.org reviewer would reject the name, but it is direct, current, reproducible evidence that the name-similarity concern is not merely theoretical — it has an immediate, observable real-world effect.

## 8. Trademark / Brand Signal — Limited

A targeted search for a registered "Astra" trademark held by Brainstorm Force (the actual maker of the Astra theme) found **no confirmed match**. The USPTO trademark records surfaced for "ASTRA" belong to unrelated entities (Astra S.r.l., Astra Pharmaceutical Products Inc., TradeSun Inc.) — none is Brainstorm Force. Brainstorm Force does hold a confirmed registered trademark for a **different** product name ("SPECTRA", per USPTO records), which at least establishes the company does pursue trademark registration for its brands generally, but no equivalent record for "Astra" itself was found in this research.

**Classification: NO CLEAR SIGNAL FOUND** (for a formal registered trademark specifically covering "Astra" the WordPress theme name). This research does not constitute formal legal trademark clearance, per this Order's own explicit scope limitation (§8 of the Order). The absence of a found registration does **not** neutralize the separate, independent WordPress.org policy risk (§3) — that risk exists under WordPress.org's own review discretion regardless of formal trademark status.

## 9. Name Strategy Options (analysis only — no option is adopted or discarded on the Owner's behalf)

**N1 — Submit as-is**: "If Professional ASTREA", slug `astrea`. Preserves brand continuity entirely. Carries the full risk documented above.

**N2 — Retain the public brand "If Professional ASTREA" but use a more distinctive Theme Directory name/slug.** This is a real, commonly-used pattern in the WordPress ecosystem (a product's marketed/public brand name and its literal WordPress.org Theme Name/slug are not always identical — some agencies register a more distinctive or prefixed directory name while marketing under a separate, flashier brand elsewhere). No specific alternative slug/name is proposed or invented here, per the Order's own explicit instruction ("Do not invent or adopt a new slug without evidence") — this option is recorded as a viable middle path for the Owner's own consideration, not as a recommendation of a concrete replacement name.

**N3 — Rename the Theme/product before submission.** Explicitly a last resort requiring direct Owner approval; no rebranding plan was generated, per the Order's own instruction not to do so unless N3 becomes necessary and is separately authorized.

## 10. Name Risk Rating

**Risk: HIGH**
**Confidence: MEDIUM**

Rationale: the current, primary-source Handbook rule (§3) grants explicit, named discretion to reject names "too similar to an existing theme or brand"; Astra is not an obscure theme but one of the most dominant, actively-maintained, prominently-displayed themes in the entire directory (§4); a genuine search for coexistence precedent found none favorable and no reassuring example (§5); the structural/phonetic/orthographic comparison shows real, substantial closeness at the bare Theme Name/slug level, with Astra's own stated positioning language ("professional web designers") overlapping ASTREA's own framing (§6); and a direct, current, reproducible search shows total confusion-adjacent dominance by Astra for any ASTREA-related query today (§7). Confidence is rated MEDIUM rather than HIGH because no directly documented case of an actual past WordPress.org rejection for this specific pattern (a near-homophone of a dominant brand) was found to point to as conclusive proof — the rating rests on strong, converging circumstantial and policy-text evidence rather than a matching precedent case.

## 11. GATE 1 Decision

**N-C. ASTREA NAME HOLD — OWNER DECISION REQUIRED**

Per this Order's own explicit rule (§11 of the Order): *"If N-C or N-D: STOP ENTIRE ORDER. Do not modify product code. Do not modify Theme name. Do not touch Core recommendation. Do not create a release. Return findings to Owner."*

This rating was not manufactured to be favorable or unfavorable — it reflects a genuine, converging body of current, primary-source-grounded evidence that this is a real, material, and — critically — **fundamentally subjective, reviewer-discretion-dependent** risk. Only the Owner can weigh brand-continuity value (ASTREA is an established Project-if product, and this Order's own OWNER INTENT section correctly cautions against casual renaming) against the material risk of investing further effort into a submission that a reviewer could reject or force a late rename on, potentially after a lengthy review-queue wait.

## 12. GATE 2 — Not Executed

Per the Order's own explicit STOP instruction triggered by the GATE 1 result above, **GATE 2 (Core recommendation compliance resolution) was not started.** No research was performed on current plugin-recommendation rules beyond what Construction 022 already established (WPORG-002, unchanged and still open). No Theme-side code was inspected, modified, or tested for this Order. No `v1.0.3` was prepared. No release was created.

## 13. Product-Code Change Statement

**Zero.** Confirmed:

```
$ git status --short
?? "docs/research/references/ChatGPT Image ...:Zone.Identifier"   (pre-existing, unrelated)
```

This document is the only new file this Order produces. No file under `theme/` or `core/` was touched. `v1.0.0`, `v1.0.0-rc2`, `v1.0.1`, and `v1.0.2` all remain exactly as they were before this Order began.

## 14. Deferred Items

- The two-part decision the Owner now faces:
  1. Whether to accept the documented HIGH/MEDIUM-confidence naming risk and proceed with GATE 2 + eventual submission under the current name (N1),
  2. or explore N2 (distinct Directory slug/name, same public brand) or N3 (rename), each requiring its own follow-up Construction Order once the Owner has decided a direction.
- **WPORG-002 (Core recommendation compliance)** remains open, entirely untouched by this Order, and will require its own GATE-2-equivalent resolution once GATE 1 is cleared by the Owner.
- The unverified "50-install ecosystem-wide name check" claim (§3) is recorded for awareness but was not treated as confirmed policy; if the Owner wants this specifically verified before deciding, an actual test submission (which this Order does not perform) may be the only way to know for certain — WordPress.org does not appear to publish this exact mechanism in its own primary documentation.

## 15. Start / End / Duration

Start: 2026-09-06 21:35 JST (approximate — this Order's work began immediately following the prior turn's HISTORY.csv schema commit, no separately logged start timestamp exists)
End: see HISTORY.csv (this report's own commit)
Duration: -

## 16. Final Verdict

**C. ASTREA WORDPRESS.ORG HOLD — OWNER DECISION REQUIRED**

Per the Order's Absolute Stop Gate (§40), this applies regardless of which verdict resulted: this Order does not, and did not, begin Construction 023, upload the Theme, submit Core, rename ASTREA, or touch the Core recommendation notice. Waiting for Owner.
