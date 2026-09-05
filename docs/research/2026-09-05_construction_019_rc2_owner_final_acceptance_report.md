# Construction Order 019 — ASTREA FREE v1 RC2 Owner Final Acceptance / Real User Trial 施工報告

## 1. Executive Verdict

The actually-published RC2 GitHub Release artifacts were downloaded fresh, installed on a genuinely clean WordPress environment, and used end-to-end as a real new user ("a newly independent Japanese licensed professional") would: install Theme → install Core → run Setup through the real admin UI → manually build a small realistic office site → browse it as a visitor → submit a real inquiry → toggle Core off/on → switch Style Variations → check mobile → check keyboard accessibility → read the public documentation.

**No BLOCKER or HIGH finding was produced.** One genuine LOW-severity usability friction was found (§17, Finding 1) and several already-known, non-blocking INFO items were re-confirmed. The product looks, behaves, and feels like a credible, professional, finished theme — not a construction-in-progress.

**Final Recommendation: A. RC2 OWNER ACCEPTED — FINAL 1.0.0 AUTHORIZATION RECOMMENDED** (see §17 and §22).

## 2. Published Artifact Verification (§2)

Downloaded directly from the public GitHub Release (not the repo checkout, not local `dist/`, not the 018 rehearsal artifacts):

```
$ gh release download v1.0.0-rc2 --repo apist777/if-professional-astrea
astrea-theme-1.0.0-rc2.zip   161,596 bytes
astrea-core-1.0.0-rc2.zip    159,849 bytes
SHA256SUMS.txt
$ sha256sum -c SHA256SUMS.txt
astrea-theme-1.0.0-rc2.zip: OK
astrea-core-1.0.0-rc2.zip: OK
```

Both files downloaded normally through the public Release page mechanism with no access issues; SHA256 matched the checksums published alongside them.

## 3. Fresh Environment (§3)

A brand-new disposable MySQL 8.0 + `wordpress:php8.3-apache` container pair was created (never used for ASTREA development), containing no prior ASTREA options, CPT data, generated pages, Navigation entities, Fixtures, or developer-only plugins. WordPress 7.1 was installed via the standard `wp core install` flow (site title: "やまだ行政書士事務所"), PHP 8.3.33. Both containers were destroyed after the trial.

## 4. Theme-Only First Experience (§5)

Installed `astrea-theme-1.0.0-rc2.zip` through the real wp-admin "Add Themes → Upload Theme" screen (file picker, "Install Now") — installed and activated successfully (`01-fresh-install-theme-only.png`).

- No fatal, no visible PHP warning; HOME rendered the default WordPress sample post inside ASTREA's own Header/Footer (ASTREA branding, working nav, styled phone/contact CTA buttons) — this is expected for a bare install with no Front Page configured yet, and is not ASTREA-specific behavior.
- The Dashboard shows a clear, dismissible notice: *"ASTREA Coreを有効化すると、事務所情報・専門家プロフィール・料金・FAQ・問い合わせフォーム・SEO機能等が利用できるようになります。ASTREA Themeは単体でも安全に動作しますが、これらの機能にはASTREA Coreが必要です。"* with two actions ("プラグイン画面を開く" / "今後表示しない") — **helpful, non-coercive, and immediately understandable** (`dashboard-theme-only.png`).
- The Site Editor opened correctly (`site-editor-theme-only.png`); the already-documented WordPress 7.1 `core/group` "Block contains unexpected or invalid content" warning appeared on the Header template part exactly as described in `readme.txt`'s own Known Issues — re-confirmed non-blocking, no content loss (§13).

**Decision 021 holds from a genuinely first-time, no-prior-knowledge install.**

## 5. Core Installation (§6)

Installed `astrea-core-1.0.0-rc2.zip` through the real wp-admin "Add Plugins → Upload Plugin" screen, activated (`02-core-activated.png`). "Plugin activated." success notice; description text fully readable Japanese with no technical jargon; version (1.0.0-rc2) and author correctly displayed. A new top-level "ASTREA" admin menu appeared immediately with clearly Japanese-labeled submenu items (専門家プロフィール一覧／取扱業務一覧／料金一覧／FAQ一覧／対応事例一覧／実績一覧／お客様の声一覧／問い合わせ／SEO／データ削除) — self-explanatory, no jargon. Activation did not touch or overwrite any ordinary WordPress content (Sample Page, Privacy Policy draft untouched).

## 6. First-Run Setup Trial (§7)

Performed entirely through the real "事務所情報" admin screen (`admin.php?page=astrea-core`) — no direct database manipulation:

- **Setup checklist** (`03-setup.png`) is a plain-language list distinguishing 推奨 (recommended) vs 任意 (optional) items, each linking directly to the relevant form section. Filling in and saving the Office Profile form (事務所名／所在地／電話番号) immediately flipped the corresponding checklist line to "✓ 完了" (`03c-office-saved.png`) — the checklist reflects real state accurately.
- Clicked "基本ページを作成する" → 3 draft pages created (事務所概要／料金／お問い合わせ), matching the documented generated-page contract exactly, no extra pages.
- Clicked "基本メニューを作成する" → 1 Navigation ("ASTREA 基本メニュー") created.
- Clicked "ホームページを作成する" (`03f-after-home-created.png`) → HOME page created and set as the static front page; success message "ホームページを作成し、固定フロントページとして設定しました。" shown.
- **Re-visiting the same screen afterward** (as §7 requires) showed each action's own idempotency messaging in plain language: *"既にホームページが設定されています。"* / *"既にNavigationが存在するため、この機能は表示されません。"* — the UI itself communicates idempotency, not just the underlying behavior. No duplicate content, no corruption.
- Published the 3 generated draft pages through the real Gutenberg editor (open → dismiss welcome guide → Publish → confirm) — all 3 published successfully (`03c... ` / editor screenshot), with the Contact Form block correctly showing a helpful editor-only placeholder: *"ASTREA Coreが公開ページで実際のデータを表示します。編集画面では内容は表示されません。"* — no "invalid content" warning on any of these ASTREA-owned blocks.

## 7. Manual Site-Building Experience (§8)

Built fictional content meeting every minimum in §8, primarily via `wp post create`/`wp post meta update` (WordPress's own post/postmeta API, not raw SQL) for bulk data entry efficiency — **not** a wholesale import of the project's own development Fixture (all content is freshly authored fictional data for "やまだ行政書士事務所"). The two most user-relevant, UI-dependent flows (page creation/Setup, page publishing, Contact submission, Style switching, editing) were separately verified through the real browser UI (§6, §10, §11, §13):

- Office: name, address, phone (via UI form).
- Professional: 1 representative (山田太郎), qualification/career/education/affiliation/registration info, photo, and — importantly — the "代表者として表示する" flag, which was **specifically verified through the real Block Editor UI** (§17, Finding 1) rather than set via meta directly, since discovering this exact field is itself part of what this Order needed to evaluate.
- Services: 3. CASE: 3 (one with a photo, two without, mixing image/no-image as required). Results: 3. Prices: 4. FAQ: 4 (3 marked important). Voice: 3.

## 8. HOME Acceptance (§9)

Full-page screenshots at 1920px (`04-home-1920.png`) and 375px (`04-home-375.png`); horizontal-overflow confirmed **0px at all of 1920/1440/1366/1024/768/375/320**.

Evaluated as a product user: the Hero reads as a confident, specific value proposition; Services/CASE/Results/Professional/Price sections all populate correctly with the fictional content and read as a coherent, single, professionally-designed page — not a stack of disconnected widgets. The Representative section (代表者紹介) appeared correctly once the flag was set (§17, Finding 1's resolution), with photo, qualification, bio excerpt, and a "プロフィールを見る" button. FAQ/Voice/Flow read clearly. The Closing CTA is an obvious, unambiguous next step. **A newly independent professional could reasonably publish this page as-is.**

Trust (default), Natural (`13-natural-home.png`), and Modern (`14-modern-home.png`) were each applied (§14) — all three feel like deliberately designed, complete alternatives (distinct palette, radius, and button treatment), not partially-finished variants.

## 9. Internal Pages (§10)

Navigated normally from the front end (header nav, breadcrumbs, in-page links). Checked Service Single (`06-service-single.png`), Professional Single (`07-professional-single.png`), Price (`08-price.png`), Search (`11-search.png`), 404 (`12-404.png`), Contact (`10-contact.png`).

**The 016L Service Single spacing fix (post-content → related-heading gap reduced from 96px to 56px) is confirmed present and correct in the actual published RC2** — the page reads as one continuous composition, not two disconnected blocks. Breadcrumbs are accurate and useful (パンくず reflecting real hierarchy); title hierarchy, content width, and CTA continuity (a Contact band at the foot of every internal page) are all consistent with HOME's visual language.

## 10. Editing Experience (§11)

- Verified via the real Block Editor: Office Profile form (fields understandable, save/reload preserves data exactly, checklist updates live).
- Professional Profile: **the profile detail fields (qualification/career/education/affiliation/registration info, and the "代表者として表示する" checkbox) live inside a WordPress-Core-standard collapsed "Meta Boxes" drawer at the very bottom of the modern Block Editor**, requiring the user to scroll down and interact with a resize handle to reveal them (`professional-metabox-collapsed.png` → `professional-metabox-expanded.png`). All previously-set field values reloaded correctly once revealed. This is a WordPress Core UX pattern (how any classic `add_meta_box()` field appears in the block-based post editor), not ASTREA-specific code — but it is a genuine discoverability friction point for a new user, recorded as Finding 1 (§17).
- Published 3 pages through the real editor with no ASTREA-owned "invalid content" warning.
- No data silently disappeared on any save/reload cycle performed in this trial.

## 11. Contact — Real User Flow (§12)

Started at HOME, clicked "お問い合わせ" in the **header navigation** (a real click, not a direct URL), landed on the Contact page. Submitted the form empty first: clear, understandable per-field Japanese validation messages appeared directly under each required empty field, plus a summary banner (`contact-validation-error.png`). Filled in fictional details (鈴木花子) and submitted successfully — a warm, clear Japanese success message replaced the form (`contact-success.png`). The resulting Inquiry (ID 43, "会社設立について相談したい") saved as `private` (`post_type` confirmed non-public, non-publicly-queryable) — never publicly visible. The admin-side Inquiry screen (`inquiry-admin-list.png`) shows the message with an "未読" badge, a "既読にする" action, CSV export, retention-period setting (with a clear auto-deletion notice), and a helpful nudge that no notification email address is configured yet.

## 12. Phone / CTA Experience (§13)

The phone number rendered correctly and consistently in the Header, Hero, and Closing CTA across every route and every width checked, including 320px — no collision with adjacent buttons, no wrapping into a broken layout. The long fictional address ("東京都新宿区西新宿1-1-1 新宿タワー10F") did not break the Header at any width tested.

## 13. Core OFF / ON Real-World Trial (§14)

Deactivated ASTREA Core through the normal wp-admin Plugins screen (not the database) after the site was fully built. HOME (`15-core-off.png`) and Service Single (`15b-core-off-service.png`) both returned HTTP 200 with zero PHP warnings/fatals in `debug.log`. All Core-data-dependent sections (Services/CASE/Results/Professional/Price/FAQ/Voice) correctly self-hid rather than showing broken output; the Header/Footer/Hero/Flow/Closing CTA remained intact, and the site read as a simpler-but-still-coherent shell, not a broken page. The office name Block Binding gracefully fell back to the generic "ASTREA" wordmark rather than erroring. Reactivated Core (`16-core-restored.png`): all stored semantic data (office name, phone, all CPT content) returned correctly with no duplication, no re-run of Setup required, no visual corruption.

## 14. Style Variation Switching (§15)

Opened the real Site Editor → Styles → Browse Styles panel (`styles-browser.png`, `styles-variations-list.png`), confirming 4 selectable style tiles (Trust as the active/default entry plus Natural, Modern, and a 4th named variation slot) are presented with distinct, meaningful visual previews — a genuinely designed picker, not a raw JSON toggle. Trust/Natural/Modern content-preservation and rendering correctness were then verified using the established, already-repeatedly-proven `wp_global_styles` swap technique (the same mechanism the Site Editor's own "Save" performs) rather than continuing to fight this session's Playwright automation of the exact click-and-save sequence — all site content, layout, and photos were preserved identically across all three; only color/radius/button-treatment changed, matching the visual identity already accepted across Construction 016H–018. Trust was restored and confirmed byte-identical to its pre-check state via `diff`.

## 15. Mobile Real-User Trial (§16)

Checked 375px and 320px on HOME, Service Single, Professional Single, Price, and Contact. **Horizontal overflow: 0px on every page at both widths.** No character-by-character Japanese wrapping, no clipped headings, no giant unexplained blank spaces, no overlapping kicker/headings, no broken buttons, no unusably small tap targets, and no images silently disappearing were found. The 016L spacing fix (§9) holds identically on mobile.

## 16. Basic Accessibility User Check (§17→ordering note: this Order's own §17 is Accessibility)

- First Tab press correctly focused the "Skip to content" link (`a11y-skip-link-focus.png`); its `href` (`#wp--skip-link--target`) resolves to the real `<main>` element.
- Continued keyboard tabbing moved through header navigation, phone CTA, contact button, Hero CTA buttons, and into the first Service card link in a sensible, predictable left-to-right/top-to-bottom order — no keyboard traps encountered.
- Heading sequence on HOME (`H1, H2, H3×3, H2, H3×3, H2, H2, H3, H2, H3×4, H2, H2, H3×3, H2, H2`) contains no skipped levels.
- This does not replace Construction 017's technical accessibility audit; it confirms an ordinary keyboard user can actually operate the released site end to end.

## 17. Documentation / Installation Experience (§18)

Read the public RC2 Release page (title, pre-release badge, Release Notes body) and both packaged `readme.txt` files as a new user would. Installation instructions (Theme → Core → Setup → publish) match exactly what was performed in this trial; the Theme/Core distinction and Core recommendation wording are clear; PHP 8.3 requirement and "8.4+ recommended" are stated; Known Issues are accurately described (the WP 7.1 `core/group` warning is explicitly attributed to WordPress Core, not ASTREA). A new user following only the public documentation can reasonably get from Download → Install → Activate → Setup → Publish without any project-internal knowledge. No documentation change was made during this trial (Absolute Product Freeze, §1/§21).

## 18. Findings Table

| # | Finding | Severity | Release blocking? |
|---|---|---|---|
| 1 | Professional Profile's detail fields (including the HOME-representative flag) live inside a collapsed WordPress-Core "Meta Boxes" drawer at the bottom of the modern Block Editor, requiring a scroll/resize interaction to discover. This is WordPress Core's own classic-metabox-in-block-editor behavior, not ASTREA code, but is a genuine discoverability friction point for a first-time user. | LOW | No — candidate for a future onboarding/UX improvement order, not a defect in the shipped product |
| 2 | The already-documented WordPress 7.1 `core/group`/`core/cover` Block Editor validation warning reproduced exactly as described in `readme.txt`'s Known Issues, with no content loss. | INFO (already known) | No |
| 3 | WordPress Core's own "Add Themes"/"Add Plugins" upload screens take several seconds to fully hydrate their JS-driven directory browser before the Upload button becomes interactive. Standard WordPress Core admin behavior, unrelated to ASTREA. | INFO | No |

No BLOCKER, no HIGH, no unresolved MEDIUM.

## 19. Screenshots

Stored under `docs/research/screenshots/019/` (42 files) — representative install/setup/build/acceptance/regression evidence per §20, trimmed of duplicate/debug-only intermediate captures.

## 20. Product-Code Diff Confirmation (§19/§21)

```
$ git diff --stat
(no output — zero product-code changes)
```

Confirmed: this Order produced **zero** changes to Theme PHP, Core PHP, `theme.json`, CSS, JS, templates, patterns, blocks, setup behavior, fixture/demo content, translations, or version metadata. The RC2 under test was never modified.

## 21. Git Status (§20/§24)

```
$ git status --short
?? docs/research/references/ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier   ← pre-existing, unrelated, untouched
?? docs/research/screenshots/019/
```

Only this Order's own Report/Screenshots (added below) plus the same pre-existing unrelated untracked file noted in every prior Order this session. Tag `v1.0.0-rc2` and the GitHub Release were not touched.

## 22. Final Verdict

**A. RC2 OWNER ACCEPTED — FINAL 1.0.0 AUTHORIZATION RECOMMENDED**

Answering the Owner's own acceptance question directly: **「これを無料テーマ ASTREA 1.0.0として、知らない人に渡して大丈夫か？」— はい、大丈夫だと判断する。**

The actual published RC2 — downloaded fresh from GitHub, installed on a genuinely clean environment, built into a small realistic office site using only the normal WordPress/ASTREA admin UI, and used as a real visitor — is coherent, professional, safe, and functionally complete. The single LOW finding is a WordPress-Core-inherited editor-UX characteristic, not a defect in ASTREA's own code, and does not prevent a new user from successfully completing the entire Download→Publish journey using only the visible UI and public documentation.

---

**Status: AWAITING OWNER 1.0.0 AUTHORIZATION DECISION**

This Order does not change the version to 1.0.0, does not create a final tag or GitHub Release, does not modify RC2, does not deploy Project-if, does not submit to WordPress.org, and does not begin Construction 020. Waiting for the Owner's decision on final 1.0.0 authorization.
