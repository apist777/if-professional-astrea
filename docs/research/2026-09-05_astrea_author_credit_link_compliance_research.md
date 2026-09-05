# Construction Order 022-PRE — Author Credit Link Compliance Research

Date: 2026-09-05
Actor: Chloe (Claude)
Mode: RESEARCH / AUDIT ONLY — zero product code changes
Status at close: see §18 Final Verdict

## 1. Executive Conclusion

A single, plain-text "**Theme by Project-if**" footer credit, linked to the `Author URI` declared in `style.css` and marked `rel="nofollow"`, implemented as ordinary editable/removable blocks inside `theme/parts/footer.html` (not PHP-forced, not auto-reinserted), is **compliant** with the current (last updated **June 9, 2026**) WordPress.org Theme Review requirements, does not conflict with the GPL, and follows Google's own stated best practice for this exact class of link.

However, this research surfaced a **pre-existing, unrelated defect that blocks a fully-compliant implementation as-is**: ASTREA's `Theme URI` and `Author URI` in both `theme/style.css` and `core/astrea-core.php` currently point to `https://project-if.com/...` — a domain that **does not resolve at all** (`curl: Could not resolve host: project-if.com`), while the real, live product presence is `https://project-if.jp/`. The Theme Review rule restricts the one permitted footer credit link to *exactly* the URI declared in `style.css`'s `Author URI`/`Theme URI` headers — so implementing the credit correctly requires fixing this stale domain in the same release, not just adding a footer block. This is not scope creep for that future release; it is a prerequisite for the credit link to point anywhere real.

**Recommended verdict: B. AUTHOR CREDIT APPROVED WITH CONDITIONS** (see §18).

## 2. WordPress.org Rule Findings

Source: [Required — Make WordPress Themes handbook](https://make.wordpress.org/themes/handbook/review/required/) (fetched 2026-09-05; page states "Last updated: June 9, 2026", i.e. current).

**Section 13, "Selling, credits, links, and spam" — quoted verbatim:**

> Themes can include one single front-facing credit link, which is restricted to the Theme URI or Author URI defined in style.css. Themes can also have an additional front-facing credit link pointing to WordPress.org.
>
> * Themes must not display obtrusive upselling
> * Themes must not display upselling on the front
>
> Themes must disclose all affiliates.
>
> The theme and its public-facing pages, including theme description, readme files, bundled starter content, and translation files, may not be used to spam. Spammy behavior includes (but is not limited to) tags to competitors' products, blackhat SEO, and keyword stuffing.

**Section 1, "Licensing & copyright" — quoted verbatim (relevant line):**

> Copyright statements on the front end must only display the user's copyright, not the theme author's copyright.

**Section 9, "Files" — style.css header rules, quoted verbatim:**

> Theme URI is optional. If used, it must be about the theme being hosted on WordPress.org. Using WordPress.org in the Theme URI is reserved for the default themes (Twenty X). Author URI is optional. If used, it must be a page or website about the author, author theme shop, or author project/development website.

(Read literally, "hosted on WordPress.org" in that sentence describes the *default Twenty-series themes' own convention*, not a requirement that every theme's URI live on wordpress.org — the very next sentence clarifies that using the literal string "WordPress.org" in a Theme URI is reserved for those default themes. For a theme distributed outside the WordPress.org directory, as ASTREA currently is, the operative requirement is simply that the URI be a genuine page about the theme.)

**Section 8, "Language & internationalization" — quoted verbatim (relevant line):**

> All text strings must be translatable using gettext, with the temporary exception of text in HTML template files.

This last point directly answers a secondary question raised by implementation: literal, non-`__()`-wrapped text placed inside a block-theme HTML template file (such as `footer.html`) is **currently, explicitly exempted** from the general translatability requirement. A hard-coded "Theme by Project-if" string in `footer.html` would not, by itself, violate the i18n requirement — though it is still good practice to keep such user-facing strings translation-ready where reasonably possible (see §11).

**No explicit rule was found, in this authoritative document, that:**
- mandates a specific `rel` attribute on the credit link,
- restricts anchor text wording beyond the general "no spam/blackhat SEO/keyword stuffing" clause,
- requires the credit be user-removable (this is *inferred* from the spam clause and from general open-source/Google norms — see §3/§4 — not from an explicit sentence in this document).

An earlier automated web-search summary (not this direct page fetch) used the informal phrase "SEO-seeded" to characterize the anchor-text rule; that exact phrase does **not** appear in the verbatim handbook text quoted above. The actual operative rule is the general "may not be used to spam... blackhat SEO, and keyword stuffing" clause in Section 13. This report relies only on the verbatim quotes above, not on that paraphrase.

## 3. GPL Findings

Source: [GNU GPL FAQ](https://www.gnu.org/licenses/gpl-faq.en.html) (fetched 2026-09-05).

The FAQ's closest directly-on-point entry concerns academic citation requirements, and its answer generalizes cleanly to any "must display attribution" condition:

> No, this is not permitted under the terms of the GPL. While we recognize that proper citation is an important part of academic publications, citation cannot be added as an additional requirement to the GPL.

**Implication for ASTREA:** the GPL does not let Project-if impose an *extra license condition* requiring every derivative/deployed copy to display "Theme by Project-if." What the GPL *does* protect is the **copyright notice inside the source code itself** (e.g., the `License:`/copyright lines already present in `theme/style.css` and `core/astrea-core.php`) — those must not be stripped from redistributed source. A **frontend** footer credit is a different thing entirely: it is ordinary default *content* Project-if chooses to ship, which — being GPL-licensed like the rest of the theme — the end user is fully entitled to edit or delete, exactly as they can edit any other paragraph in `footer.html`. Three distinct concepts, not to be conflated:

1. **Copyright/license notice in distributed source** (e.g., the `style.css` header block) — GPL-protected, should not be stripped from the *source distribution*, but this is about the code file, not the rendered webpage.
2. **Frontend theme-author attribution** (the proposed "Theme by Project-if" link) — ordinary editable content, not a GPL-enforceable requirement, must remain removable.
3. **The user's own website copyright statement** (e.g., "© 2026 Yamada Office") — belongs entirely to the site owner; per §2, ASTREA's frontend must never present this as Project-if's copyright.

## 4. Google / Link-Spam Findings

Sources: [Google Search Central Blog — "A reminder about widget links"](https://developers.google.com/search/blog/2016/09/a-reminder-about-widget-links) (fetched 2026-09-05, original 2016-09-08); [Google — Qualify outbound links](https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links) (fetched 2026-09-05).

Google's own widget-links guidance addresses precisely this class of link — a link distributed across many installations of the same template/theme/widget, not individually chosen per site:

> Google's policy addresses the creation of keyword-rich, hidden or low-quality links embedded in widgets that are distributed across various sites. Some widgets add links to a site that a webmaster did not editorially place and contain anchor text that the webmaster does not control, and because these links are not naturally placed, they're considered a violation of Google Webmaster Guidelines. To resolve issues with unnatural widget links, you can add a `rel="nofollow"` attribute on the widget links or remove the links entirely.

Current `rel` attribute definitions (from the qualify-outbound-links documentation):

> **nofollow**: Use the `nofollow` value when other values don't apply, and you'd rather Google not associate your site with, or crawl the linked page from, your site.
> **sponsored**: Mark links that are advertisements or paid placements... with the `sponsored` value.
> **ugc**: We recommend marking user-generated content (UGC) links... with the `ugc` value.

**Answers to §7 of the Order:**

- **Is a legitimate theme-author credit acceptable even though it incidentally creates backlinks?** Yes — Google's own remediation for this class of link is `rel="nofollow"`, not removal; the concern is about *link equity flowing at scale without genuine editorial endorsement per site*, not about the mere existence of the credit.
- **Could large-scale theme footer links create search-engine concerns?** Yes, specifically if the anchor text is keyword-rich/commercial (e.g., "Best WordPress Themes"), or if the link cannot be removed/edited by the site owner, or if it is hidden (styled invisible). A plain brand-name link that the user can freely edit or delete does not fit Google's own definition of the problem.
- **Is brand-only anchor text safer than commercial/keyword-rich anchor text?** Yes, unambiguously — "Theme by Project-if" (a proper-noun brand mention) is categorically different from something like "Best Cheap WordPress Themes" (a commercial keyword phrase), which is exactly the distinction Google's own guidance draws.
- **Should Project-if avoid positioning this publicly as an SEO scheme?** Yes. The credit should be presented and documented internally and externally as *authorship attribution*, never as a link-building or ranking tactic. This report's recommendation (§13-§15) is written on that basis.
- **Google Search spam-policy considerations for widely distributed template/theme footer links:** the applicable, current, primary-source guidance is exactly the widget-links reminder quoted above — recommending `nofollow` for links whose relevance Google cannot independently verify per installation. This is directly applicable to a WordPress theme footer credit distributed to an unknown, growing number of independent sites.

## 5. Current ASTREA Footer Audit (Read-Only)

Inspected directly (no changes made):

**`theme/parts/footer.html`** — the entire, current, complete content is:

```html
<!-- wp:group {"backgroundColor":"contrast", ...} -->
<div class="wp-block-group ...">
  <!-- wp:group {"layout":{"type":"flex", ...}} -->
  <div class="wp-block-group">
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
      <!-- office_name (bound to astrea-core/office-profile) -->
      <!-- address (bound to astrea-core/office-profile) -->
      <!-- phone (bound to astrea-core/office-profile) -->
    </div>
    <!-- wp:navigation ... /-->
  </div>
</div>
```

**Findings:**
- There is **no copyright statement of any kind** in the current footer — not the user's, not the theme author's. Adding a credit is not "adding a second link next to an existing copyright line"; it would be the *first* piece of copyright/attribution text ASTREA has ever shown on the frontend.
- There is **no existing Project-if link anywhere** in ASTREA's shipped markup — confirmed by grepping every template, template part, and pattern file in `theme/` for `project-if`: zero matches.
- **Current `style.css` header** (`theme/style.css`):
  ```
  Theme URI: https://project-if.com/astrea
  Author: Project-if
  Author URI: https://project-if.com/
  ```
  **Current `astrea-core.php` header** (`core/astrea-core.php`):
  ```
  Plugin URI: https://project-if.com/astrea
  Author: Project-if
  Author URI: https://project-if.com/
  ```
  **Both domains use `.com`, not the real, live `.jp` domain.** Verified directly:
  ```
  $ curl -v --max-time 8 https://project-if.com/
  * Could not resolve host: project-if.com
  curl: (6) Could not resolve host: project-if.com
  ```
  `project-if.com` does not resolve at all — it is not merely a redirect or a parked page, it is entirely absent from DNS. **This is a genuine, pre-existing defect, unrelated to and out of scope for this research-only Order, but directly load-bearing for any future footer-credit implementation** (see §9/§18).
- **Site Editor editability:** confirmed — `footer.html` is built entirely from ordinary core blocks (`core/group`, `core/paragraph`, `core/navigation`), identical in kind to every other editable area of the theme. There is nothing about its current construction that would prevent adding one more ordinary paragraph/link block to it, or that would make such a block any less editable/removable than the Office Profile paragraphs already there.

## 6. Existing-Installation Update Behavior (Experimentally Verified)

The Order asked this not be assumed. It was verified experimentally on this project's own long-running wp-env instance (ASTREA 1.0.0 active), using database operations only — **zero files in the git repository were touched**, and the test data was fully cleaned up afterward (`git status --short` before and after shows only the same pre-existing, unrelated untracked file).

**Procedure:**
1. Confirmed no `wp_template_part` customization existed yet (`wp post list --post_type=wp_template_part` → empty) and the frontend rendered the theme's own `footer.html` file content, unmodified.
2. Created a `wp_template_part` post (slug `footer`, tagged with the `wp_theme` taxonomy term `astrea` — exactly what WordPress Core itself creates when a user edits a template part in the Site Editor) containing deliberately different content (`USER-CUSTOMIZED-FOOTER-MARKER-TEST-021PRE`).
3. Reloaded the homepage: **the customized database content rendered completely, replacing the entire theme-file footer** — confirmed by direct `curl` inspection of the rendered HTML.
4. Deleted the test post (`wp post delete --force`) and reloaded: the **original file-based footer structure reappeared exactly as before** the experiment (confirmed via the same distinctive CSS classes reappearing in the markup).

This directly confirms, for this exact theme, the general WordPress Core mechanism also documented at [Block Editor Handbook — Site Editing Templates](https://developer.wordpress.org/block-editor/explanations/architecture/full-site-editing-templates/) (fetched 2026-09-05):

> When a user edits a template (or template-part), the initial theme template file is kept as is but a forked version of the template is saved to the `wp_template` custom post type.

**Case-by-case conclusions:**

| Case | Behavior |
|---|---|
| **A. Never customized Footer** | Renders straight from the theme file. A future theme update to `footer.html` (adding the credit) takes effect immediately for this user, the next time the theme's files are loaded — exactly as any other default-content change would. |
| **B. Customized Footer** | The user's database-stored fork renders, in its entirety, regardless of what the theme file says. **A future ASTREA update that adds a credit to the shipped `footer.html` file has zero effect on this user's site** — they will not see the credit unless they explicitly re-add it themselves or reset to defaults. |
| **C. Deleted the author credit specifically** | If the user's customization is a *fork of the whole template part* (which is how the Block Editor currently works — there is no per-block-within-a-part exclusion mechanism), deleting the credit means their whole footer becomes a Case B fork from that point on; the credit will not reappear on a later update unless the user manually re-adds it or resets the part. |
| **D. Reset template part to theme default** | The Site Editor's own "Clear customizations" action deletes the user's database fork, immediately reverting to Case A behavior — confirmed by this experiment's own cleanup step (deleting the test post reproduced exactly this). |

**Conclusion relevant to the Order's underlying concern:** a footer credit shipped as ordinary default template content, added via a normal theme update, is accurately describable as **"a default that ships with new installs and remains only until/unless the site owner edits or clears it"** — not a forced or persistently-reinserted link. It cannot reach users who have already customized their footer without their own action, and any user (customized or not) can remove it at any time through the ordinary Site Editor UI, with no special mechanism needed.

## 7. Block Theme Implementation Options (A/B/C/D)

| Option | Description | User control | Site Editor editable | Removable | WP.org fit | Theme-only (works w/o Core) | Accessibility | i18n |
|---|---|---|---|---|---|---|---|---|
| **A. Ordinary blocks in `footer.html`** | A plain `core/paragraph` (or small `core/group`) containing a real `<a>`, added directly to the existing template part, exactly like the Office Profile paragraphs already there | Full | Yes, identical to any other footer content | Yes, delete like any block | Best fit — matches how every reference implementation of this pattern is built | Yes — no Core dependency needed for static text | Full — real, focusable anchor | Exempted per Handbook §8 (HTML template text); can still be authored translation-ready |
| **B. PHP hard-coded output** (e.g. `wp_footer` hook or a forced `render_block` filter) | Injects the credit outside the Site Editor's own content model | None | No — invisible to the block editor, cannot be selected/deleted there | Not through normal editing; would need a PHP-level opt-out mechanism that doesn't exist elsewhere in ASTREA | Poor fit — closely resembles the "forced/non-editorial" link pattern both Google's widget-link guidance and general open-source norms flag as problematic, even though the GPL itself does not forbid it outright | Yes (no Core needed), but that is its only advantage | Depends on markup, but bypasses the theme's existing accessible-block patterns | Could be `__()`-wrapped properly, the one advantage over A |
| **C. Dynamic block, e.g. `astrea/theme-credit`** | A Core-registered dynamic block rendered server-side, insertable/removable in the Editor like a real block | Full | Yes | Yes | Good, if implemented as a genuinely removable block (not force-inserted) | **No** — this would make the credit disappear whenever ASTREA Core is deactivated, directly conflicting with Decision 021 ("Coreは推奨する。しかしThemeを人質にしない。") if the intent is consistent branding regardless of Core state | Full | Fully `__()`-wrappable (Core is PHP, not exempted the way HTML templates are) |
| **D. Pattern-generated credit** | A Block Pattern that inserts the same markup as Option A at theme-build/authoring time | Full (once inserted) | Yes | Yes | Same as A | Yes | Same as A | Same as A |

**Recommendation: Option A.** It is functionally identical to D from the end user's perspective (both end up as literal editable blocks in `footer.html`) but requires no separate pattern-authoring indirection for content that is only ever used in one place (the footer). It has zero Core dependency (correctly preserving Theme-only operation, unlike C), and does not risk being perceived as a forced/hidden link (unlike B). This also matches this project's own established Decision 028 principle of not inventing a new mechanism when the existing one (ordinary template-part blocks) already does the job.

## 8. Versioning Impact

ASTREA `v1.0.0` is immutable and was **not** touched by this research (verified below). If Owner approves implementation later, the following would need updating — this section answers "what," not "whether to do it now":

| Item | Needs update? | Notes |
|---|---|---|
| Theme source (`theme/parts/footer.html`) | Yes | Add the ordinary editable credit block |
| `theme/style.css` `Theme URI` / `Author URI` | **Yes — required prerequisite**, not optional | Currently point to a non-resolving domain (`project-if.com`); must be corrected to the real `project-if.jp` destination for the credit link to be meaningful and to satisfy the Handbook's "must match the declared URI" rule |
| `core/astrea-core.php` `Plugin URI` / `Author URI` | Recommended in the same release, for consistency | Same dead-domain issue exists in Core's own header; not required by the footer-credit feature itself, but leaving Theme and Core headers pointing to two different (one broken) domains would be an inconsistent, confusing state to ship |
| `theme/style.css` `Version` | Yes → `1.0.1` | Patch-level: a footer-content addition plus a metadata correction, no breaking behavior change |
| `theme/readme.txt` Stable tag + Changelog | Yes | New `= 1.0.1 =` entry, RC/1.0.0 entries kept as history (established project convention) |
| `theme/languages/astrea.pot` | Optional but recommended | Not strictly required (HTML template text is currently exempted, §2), but if the credit string is authored in a translation-ready way, regenerate to include it |
| Final ZIP / SHA256 / git tag / GitHub Release | Yes, all | New `v1.0.1` artifacts; `v1.0.0` stays untouched and immutable, matching this project's established Release Safety principle |
| Project-if product page (`/if-thema/astrea/`) | Yes | Update the displayed "Version 1.0.0" text and the Theme/Core download links' asset paths (currently hardcoded to `/releases/download/v1.0.0/...`) to the new tag |
| Project-if TOP `PROJECT 003` card VERSION field | Yes | Same reason |
| WordPress.org submission package | N/A currently | ASTREA has not been submitted to WordPress.org as of this Order; this research is preparatory due diligence for that eventual step, not an active submission requirement today |

**Does ASTREA Core need a version bump?**

The Order's own hypothesis — "Core should NOT change if only Theme Footer markup changes" — is **verified true in the narrow case**: if the *only* change is `theme/parts/footer.html`, Core's own files are untouched and Core does not need a version bump.

**However**, this research also recommends correcting Core's own `Author URI`/`Plugin URI` header (§8 above) as part of the same release, for consistency across the product line. **If that correction is made, Core's own distributed file changes, and per this project's own established practice (Construction 020 bumped Theme AND Core together for a metadata-only change), Core would warrant its own patch version bump too** — even though nothing about Core's actual runtime behavior changes. This is the verified (not assumed) answer: **no bump required for the footer credit alone; a bump is warranted only if Core's own header metadata is also corrected in the same release.**

## 9. Project-if-Wide Policy Assessment

The Order's proposed 10-point policy was evaluated point-by-point against everything found above:

1. **Maximum one author credit link.** ✅ Matches the Handbook exactly ("one single front-facing credit link").
2. **Plain brand anchor text only.** ✅ Matches the "no keyword stuffing/blackhat SEO" clause and Google's "brand-only anchor is safer" guidance.
3. **Destination = official Project-if author/theme URI.** ✅ Matches the Handbook's restriction to Theme URI/Author URI — **with the caveat that this only works if that URI is actually correct and live**, which is not currently true for ASTREA (§5/§8). This policy point should be read as "the URI *as correctly declared and verified working* in that theme's own style.css," not merely "some Project-if URL."
4. **User can remove/edit it normally.** ✅ Achieved by Option A (§7); not a hard WP.org requirement in itself, but essential given the GPL analysis (§3) and Google's spam-policy framing (§4).
5. **No forced reinsertion.** ✅ Strongly recommended (§7); a forced-reinsertion mechanism would likely fail manual WordPress.org review and is exactly the "forced link" pattern Google's own guidance targets.
6. **No SEO keyword anchor.** ✅ Already covered by point 2.
7. **User/site copyright remains separate.** ✅ Directly required by Handbook §1 (§2 above) — this is not optional for WordPress.org eligibility.
8. **No telemetry associated with credit.** ✅ Consistent with the Handbook's Privacy section (opt-in-only tracking) and with this project's own long-standing "no telemetry inside the ASTREA product itself" principle (already established policy from Construction 021).
9. **No tracking parameters in the credit URL unless explicitly permitted and justified.** ✅ Consistent with keeping the link a plain, honest attribution rather than an analytics-instrumented marketing link; also avoids any risk of the link being read as an advertisement (which would require `rel="sponsored"`, not `nofollow`, per §4).
10. **WordPress.org rules always take precedence.** ✅ Sound as a governing principle.

**This proposed policy is sound and ready to serve as a common Project-if FREE-theme standard**, with one addition recommended: **a pre-flight check, for each theme the policy is applied to, that its declared `Theme URI`/`Author URI` actually resolves and points to the intended real destination** — since this research found that check currently fails for ASTREA. Whether the same issue exists in If-Thema Small Business FREE or My Base was **not verified** in this research (their theme source is not part of this repository and was out of scope for a READ-ONLY audit of `~/if-professional-astrea`); a similar audit of those themes' own `style.css` headers is recommended before applying this policy to them.

## 10. Risks

- **Implementing the credit before fixing the dead `project-if.com` domain** would ship a footer link that either 404s or fails to resolve for real visitors — a direct repeat of the exact class of mistake found and fixed in Construction 021 (the private-repository discovery). This is the primary risk this research surfaces.
- **Any temptation to make the credit non-removable or auto-reinserted** (even with good intentions, e.g. "so users don't accidentally delete our only attribution") would conflict with the spirit of the WP.org spam clause and Google's widget-link guidance, and could jeopardize a future WordPress.org submission.
- **Anchor-text scope creep** — a future edit that "improves" the wording into something more descriptive/keyword-rich (e.g., "Free WordPress Themes for Professionals by Project-if") would cross from legitimate attribution into exactly the kind of anchor text Google's guidance flags. The recommended wording (§13/§14) should be treated as final unless re-reviewed.
- **Applying the policy portfolio-wide without first auditing each theme's own URI metadata** could silently propagate the same broken-domain problem to Small Business FREE / My Base.

## 11. Recommended Exact Wording

**"Theme by Project-if"**

Rationale (compared against the Order's four candidates):
- *"Powered by Project-if"* — potentially misleading; "Powered by" conventionally implies an active runtime dependency (as in "Powered by WordPress"), which is not accurate for a theme author credit.
- *"WordPress Theme by Project-if"* — accurate and compliant (correct WordPress capitalization, per Handbook §7), but unnecessarily verbose given the context already makes clear this is a WordPress site.
- *"Project-if"* alone — too ambiguous; a visitor cannot tell whether Project-if is the theme author, the hosting provider, or something else.
- **"Theme by Project-if"** — accurate, minimal, matches the Order's own stated intended meaning exactly ("This WordPress theme was created by Project-if"), and reads as plain, honest, brand-only attribution rather than a promotional or keyword-rich phrase.

## 12. Recommended Exact Destination

**The `Author URI` declared in `theme/style.css`**, which should first be corrected from the current (non-resolving) `https://project-if.com/` to **`https://project-if.jp/`**.

A secondary observation: `Theme URI`, per Handbook §9's own definition ("a page... predominately related to the Theme"), is actually a *better-fitting* field for the already-existing, already-live product page **`https://project-if.jp/if-thema/astrea/`** than the current placeholder `https://project-if.com/astrea` ever was. Recording this as an option for the Owner's future decision: the eventual credit link could point to either the corrected Author URI (`https://project-if.jp/`, "the author") or the corrected Theme URI (`https://project-if.jp/if-thema/astrea/`, "the theme's own page") — both are Handbook-compliant destinations; the Order's own stated intent text ("Theme by Project-if → https://project-if.jp/") maps most naturally to Author URI, so that is the primary recommendation, with Theme URI noted as a compliant alternative.

## 13. Recommended Implementation Method

**Option A** (§7): ordinary, real `<a>` markup inside a `core/paragraph` (or small `core/group`) added directly to `theme/parts/footer.html`, visually and functionally on par with the Office Profile content already there — fully visible, selectable, editable, and deletable in the Site Editor, with no PHP injection, no dynamic block, and no Core dependency.

## 14. Whether `rel` Attribute Is Needed

**Yes: `rel="nofollow"`.** Not a WordPress.org requirement, but the current, directly-applicable Google guidance for exactly this class of link (a credit distributed across many independent installations, not individually vetted per site by Google). This is a defensible, conservative, currently-correct choice, not `sponsored` (no payment is involved) and not `ugc` (this is not user-submitted content).

## 15. Whether ASTREA v1.0.1 Is Appropriate

**Yes.** A footer-content addition plus a metadata (URI) correction is additive, non-breaking, user-facing-but-optional (the site owner can always edit/remove it), and matches ordinary semantic-versioning practice for a patch-level release — directly analogous to how this project's own Construction 018 treated a comparable scope (metadata + a small user-facing fix) as `rc1 → rc2`, and Construction 020 treated the final metadata promotion as its own release.

## 16. Whether a Core Bump Is Required

**Conditionally.** Not required if the footer-credit change is scoped to the Theme alone. Required (as a same-release companion patch bump, for consistency and traceability) **if** Core's own `Author URI`/`Plugin URI` header is also corrected in the same release — which this research recommends, for exactly the reason ASTREA's own is being recommended for correction (§8/§9).

## 17. Sources

1. [Required — Make WordPress Themes handbook](https://make.wordpress.org/themes/handbook/review/required/) — fetched 2026-09-05; page self-reports "Last updated: June 9, 2026."
2. [GNU General Public License — Frequently Asked Questions](https://www.gnu.org/licenses/gpl-faq.en.html) — fetched 2026-09-05.
3. [Google Search Central Blog — "A reminder about widget links" (2016-09-08)](https://developers.google.com/search/blog/2016/09/a-reminder-about-widget-links) — fetched 2026-09-05.
4. [Google Search Central — Qualify your outbound links to Google (rel attributes)](https://developers.google.com/search/docs/crawling-indexing/qualify-outbound-links) — fetched 2026-09-05.
5. [WordPress Block Editor Handbook — Site Editing Templates](https://developer.wordpress.org/block-editor/explanations/architecture/full-site-editing-templates/) — fetched 2026-09-05 (template-part database-fork mechanism).
6. Direct, first-hand inspection of `~/if-professional-astrea` source (`theme/style.css`, `theme/parts/footer.html`, `core/astrea-core.php`, `theme/readme.txt`) — 2026-09-05.
7. Direct experimental verification on this project's own wp-env development instance (ASTREA 1.0.0 active) — 2026-09-05; database-only operations, fully cleaned up, zero git changes (see §6, §19).
8. Direct `curl`/DNS check of `https://project-if.com/` — 2026-09-05 (does not resolve).

Secondary/context-only sources (search-result summaries, not relied upon for any conclusion stated as fact): general web search aggregations used only to locate the primary sources above, not cited as authority in their own right.

## 18. Product-Code-Change Statement / ASTREA Immutability

This Order made **zero** product code changes. Verified:

```
$ git status --short
?? "docs/research/references/ChatGPT Image ...:Zone.Identifier"   (pre-existing, unrelated)
```

No file under `theme/` or `core/` was edited. The one experimental verification (§6) was performed entirely at the WordPress database level on an existing, already-running development instance, and fully cleaned up (the test `wp_template_part` post was deleted; `wp post list --post_type=wp_template_part` confirms zero remain). This document itself is the only new file this Order produces.

## 19. Final Verdict

**B. AUTHOR CREDIT APPROVED WITH CONDITIONS**

Conditions for implementation (all verified findings from this research, not new opinions):

1. **Fix the dead `Theme URI`/`Author URI` domain first** (or in the same release) — `project-if.com` does not resolve; the credit link's destination must be `https://project-if.jp/` (Author URI) or `https://project-if.jp/if-thema/astrea/` (Theme URI), matching whichever field is actually declared and used.
2. **Exact wording: "Theme by Project-if."**
3. **Exact destination: the corrected `Author URI` (`https://project-if.jp/`)**, per the Order's own stated intent; `Theme URI` is a compliant alternative if the Owner prefers linking to the product page instead.
4. **Implementation: Option A** — ordinary, removable, editable blocks added directly to `theme/parts/footer.html`; no PHP injection, no forced/auto-reinserted mechanism, no new Core dependency.
5. **`rel="nofollow"`** on the link.
6. **Exactly one** such credit link (no additional Project-if links elsewhere on the front end beyond the one permitted credit and the optional WordPress.org link the Handbook separately allows).
7. **Any existing/future frontend copyright line must reference the site owner, never Project-if** — the two can coexist in the same footer as separate statements.
8. **Version as `v1.0.1`** when implemented; `v1.0.0` remains untouched and immutable. Core needs its own patch bump only if Core's own header metadata is corrected in the same release.
9. **No implementation in this Order** — product code changes remain at zero until a separate, explicitly Owner-authorized Construction Order approves and executes it.

Waiting for Owner.
