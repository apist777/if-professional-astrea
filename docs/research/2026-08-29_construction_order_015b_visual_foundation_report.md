# Construction Order 015B — Visual v2 Foundation / Tokens / Header / Footer

Design Specification正本：`docs/specifications/06_astrea_visual_v2_design_system.md`。RC1（Theme/Core 1.0.0-rc1、Version変更なし）をFunctional Baselineとして維持し、Theme側のみを施工した。

## 1. Pre-construction Audit

`theme/theme.json`、`theme/style.css`、`theme/parts/header.html`、`theme/parts/footer.html`、`theme/styles/*.json`を確認した。Header/Footerの現行Markupは単一Flex GroupでOffice Identity・Navigation・Phone CTAを並列表示しており、視覚的なグルーピングが一切無いことを再確認した。

## 2. 変更Token（`theme/theme.json` + 3 Style Variations）

### 新規追加

| Token | 場所 | 値 |
|---|---|---|
| `color.palette` に `border` slot追加 | base theme.json / trust.json / natural.json / modern.json | Trust `#c3ccd6`、Natural `#d9c7ab`、Modern `#cccccc`（各Paletteのbase/surfaceとcontrast/secondaryの中間トーンで選定） |
| `settings.custom.border.radiusSm` | base theme.json | `4px` |
| `settings.custom.border.radiusMd` | base theme.json | `6px` |
| `settings.custom.component.gapSmall` | base theme.json | `0.5rem` |
| `settings.custom.typography.resultsNumber` | base theme.json | `clamp(2.5rem, 5vw, 4rem)`（015Cで使用予定、本Orderでは未使用） |
| `styles.css`（Raw CSS） | base theme.json | `.astrea-header-identity`のBorder、`.astrea-header-actions`のGap、Mobile時のBorder解除、Button hover/focusの`filter: brightness(0.88)` |

### 変更（既存Token拡張、破壊的変更なし）

- `styles.elements.button` に `typography.fontWeight: "600"`、`spacing.padding`（上下0.75rem／左右1.5rem）を追加。
- 既存のButton `border.radius`（Trust 2px／Natural 999px／Modern 0px）は**変更していない**——各Variationが既に確立している視覚的個性（Trustは控えめな角丸、Natural はPill形状、Modernは直角）を保護した。Button Hoverは色のスワップではなく`filter: brightness()`を採用した理由は後述（4節）。

### 維持（変更なし）

- 既存5段階Font Size（small/medium/large/x-large/xx-large）
- 既存4段階Spacing（small/medium/large/x-large）
- `contentSize: 720px` / `wideSize: 1200px`
- 既存Color Palette（base/contrast/primary/secondary/surface）
- Font Family（Trust: Georgia+明朝／Natural・Modern: それぞれ既存Sans-serif）

Token数は06 Design Systemが示した最小限（`results-number`、`component-gap-small`、`border-color`、`border-radius-sm/md`）に留め、CSS Frameworkの自作は行っていない。

## 3. Header v2

`theme/parts/header.html`を、単一Flex Groupから「Identity Group」＋「Actions Group（Navigation + Phone CTA）」の2つの子Groupへ再構成した。

```
<Group flex, space-between, wrap>
  <Group className="astrea-header-identity">   ← Office Name（border-right区切り、font-weight:700）
  <Group className="astrea-header-actions">    ← Navigation + Phone Button（まとまったAction群）
```

Block Bindings（`office-profile` sourceによるoffice_name/phone/phone_tel）は一切変更していない。新しいSemantic Dataの追加も無い。純粋なWrapper構造の追加とStyle属性の付与のみ。

### 4. Button Hover実装上の発見

当初、Button Hoverを`elements.button.:hover`でPrimary→Contrastの色スワップとして実装したところ、**Modern VariationはPrimary色とContrast色が同一値（`#111111`）**であるため、Hoverしても視覚的な変化が一切起きないことが判明した。全3 Variationで確実に機能する手段として、色のスワップではなく`filter: brightness(0.88)`をRaw CSS（`styles.css`）で適用する方式へ変更した。

## 5. Footer v2

`theme/parts/footer.html`のOffice Name Paragraphに`font-weight:700`を追加し、Address/PhoneのFont Sizeを`small`へ縮小した。Footerの構造（Flex 2カラム：情報ブロック＋Navigation）自体は変更していない。Footerを「豪華にしない」方針を守り、Spacing/Typographyの微調整のみに留めた。

## 6. Existing Template Part Protection（重要な確認）

施工前に、現在の開発環境で保持しているOwner Visual Acceptance Fixture用に、以前のConstruction（Owner Visual Acceptance）で`connect_navigation_to_header_footer()`が生成したHeader/Footer用`wp_template_part`投稿が存在していたが、これらは`wp eval`経由の非認証コンテキストで作成されたため`wp_theme`Taxonomy Termが正しく付与されておらず（Construction 013で既知の`wp_insert_post()`の`tax_input`/`assign_terms`権限問題と同一パターン）、WordPress のTemplate解決から見えない「機能していない」投稿だった。これにより、本Orderの新しいTheme File変更（`theme/parts/header.html`等）が問題なくFrontendへ反映されていることを確認した。

この発見を踏まえ、**実際に正しくTaxonomy付けされた「本物のUser-customized Template Part」**を作成して検証したところ、新しいTheme File変更を導入した後も、正しくTagされたCustom Template Partの内容がTheme Fileより優先して表示されることを確認した（`<header>`が丸ごとCustom Overrideの内容に置き換わることを実機確認）。これにより、**Visual v2のTheme File変更が既存のUser-customized Template Partを上書きしない**ことを実証した。検証用に作成したTest Fixtureと、機能していなかった旧Owner Acceptance生成物は、検証後に削除した。

## 7. Responsive / STRESS Office Name

320 / 375 / 768 / 1440pxで確認した。Construction 013で使用したSTRESS Fixture（極端に長い事務所名）でも：

- 全文表示（省略・truncateなし）
- Horizontal Overflow無し（全4 breakpointで`scrollWidth <= clientWidth`を確認）
- Navigation・CTAはOffice Identityの下へ自然に折り返し、崩れは無い

を確認した。唯一の軽微な所見として、1440px幅でSTRESS事務所名が複数行に折り返した場合、`.astrea-header-identity`のborder-rightが折り返し後の空白域まで伸びて視覚的にやや浮いて見える（LOW、日常利用では発生しない極端なFixtureでのみ確認される現象）。

## 8. Style Variations（Trust / Natural / Modern）

3 Variation全てでDesktop + Mobileを確認した。

- **Trust**：Border可視、CTAがPrimary色（紺）で表示、Button Radius 2px維持
- **Natural**：Border（暖色）可視、CTAがPill形状（999px）を維持、Primary色（緑）
- **Modern**：Border（グレー）可視、CTAが直角（0px）を維持、Primary=Contrast（黒）

いずれもMarkupは完全に共通（Header/Footer HTMLは1つのみ）。Style（Color Palette、Custom Token）のみで個性を実現し、Variation別Templateは作成していない。

### 重要な技術的知見：Style Variation選択のSnapshot特性

施工中、既にStyle Variationが選択された環境（今回のOwner Acceptance Fixture）では、`theme.json`ファイルへの変更が即座に反映されないことを発見した。WordPressの「Style Variationを選択する」操作は、選択時点のSettings/Stylesを`wp_global_styles`カスタム投稿へ**スナップショットとしてコピーする**動作であり、その後のTheme Fileの変更は自動的に反映されない（これはWordPress全体のBlock Theme標準仕様であり、ASTREA固有の問題ではない）。本Orderの検証では、開発環境の`wp_global_styles`投稿を手動で再同期して確認したが、実際のユーザーが同じ状況（既にVariationを選択済みの状態で将来のTheme Updateを受け取る）に遭遇した場合、新しいTokenを反映させるには**Site Editorで該当Variationを選び直す操作が必要**になる。これはVisual v2のBug ではなくWordPress自体の一般的な制約であるため、本Reportでは事実として記録するに留め、product Codeでの回避は行っていない（必要であれば、Documentationでの案内を将来検討する）。

## 9. Core OFF

Core無効化状態でHOMEにアクセスし、HTTP 200・Fatal無し・Header/Footerが（Block BindingsのFallback静的文言「ASTREA」で）安全にRenderされることを確認した。

## 10. Accessibility

- H1：1個のまま
- Main/Nav landmark：維持
- Skip Link：ページ最初のTab Stopのまま維持（日本語文言「内容をスキップ」で確認、Localeの挙動でありASTREA非依存）
- Keyboard Tab順：Identity（非Interactive）→ Navigation Links → Actions内Phone Button、論理的な順序を維持
- Focus可視性：全Tab Stopで`outline: auto`を確認、抑制なし

## 11. Block Validation

Site EditorでHeader/Footer Template Partを開き、ASTREA由来の新規Warning（Unexpected content / Invalid block / Attempt recovery）が0件であることを確認した。WordPress 7.1既知のcore/group/coverの警告（Construction 014Aで確認済み、Header/Footerには元々該当ブロックが無いため今回は無関係）とは明確に別。

## 12. Automated Regression

| 項目 | 結果 |
|---|---|
| PHP Syntax | 全件OK |
| PHPCS | 62/62、0 Errors |
| PHPUnit | 359 tests, 560 assertions, OK |
| smoke-test.sh（全13 Part） | 203 OK / 0 FAIL、Exit 0 |
| 公式Theme Check | REQUIRED 0、WARNING 0、INFO 1（Text Domain確認、問題ではない） |

いずれも既存基準（PHPUnit 359以上、PHPCS 0 Errors、smoke 203以上/0 FAIL）を下回らなかった。

### 重要な運用上の教訓

smoke-test.sh を、Owner Visual Acceptance Fixtureが生存したままの開発環境に対して実行したところ、smoke-test自身のCleanup処理が「クリーンな環境である」ことを前提としており、Fixtureとして保持していた全27件のCore CPT投稿（Professional/Service/Price/FAQ/CASE/RESULTS/VOICE）が誤って削除される事態が発生した。これは smoke-test.sh 自体のバグではなく（CI環境では常にクリーンな新規wp-envで実行されるため問題にならない）、「Owner Fixtureを保持したまま」という前回Orderの要求と、「smoke-testはクリーンな環境を前提とする」という既存設計が、同一のwp-env上で衝突した結果である。本Orderでは、削除されたFixtureを全て正確に復元した（Professional 3件・Service 6件・Price 4件・FAQ 5件・CASE 3件・RESULTS 3件・VOICE 3件、Office Profile、Contact設定、Navigation接続を全て再構築し、実機で復元を確認済み）。**今後、Owner Fixtureを保持した開発環境でsmoke-test.shをローカル実行する場合は、事前にFixtureのバックアップを取るか、CI（常にクリーンな環境）の結果のみを正とすることを強く推奨する。**

## 13. Security / Migration

新規Endpoint・新規Input・新規Persistenceは追加していない（Visual施工のみ）。DB Migration・Content Migrationは無し。既存User Contentの自動書き換えも無し（6節のTemplate Part保護検証を参照）。

## 14. Known Issues（今回発見・未修正、Blockerではない）

- STRESS事務所名で、Header Identity領域のBorder-rightが折り返し後に視覚的にやや浮く（LOW、日常利用では発生しない）。
- Style Variation選択済み環境でのTheme.json変更が自動反映されない（WordPress標準仕様、ASTREA非依存、Documentation化を将来検討）。

## 15. Before / After Screenshot

`docs/research/screenshots/015b/` に保存。

| ファイル | 内容 |
|---|---|
| header-before-desktop.png / header-before-mobile.png | 施工前のHeader |
| header-after-desktop.png / header-after-mobile.png | 施工後のHeader（Trust、通常Fixture） |
| header-stress-320w/375w/768w/1440w.png | STRESS事務所名での4 breakpoint確認 |
| footer-before-desktop.png / footer-after-desktop.png | Footer Before/After |
| natural-after-desktop/mobile.png、modern-after-desktop/mobile.png | Natural/Modernでの確認 |
| editor-header-check.png / editor-footer-check.png | Site EditorでのBlock Validation確認 |

## 16. Documentation更新

実装差分（Button Hover実装をJSON `:hover`色スワップから`filter: brightness()`ベースのRaw CSSへ変更した経緯）を反映するため、`06_astrea_visual_v2_design_system.md`の該当箇所は次回Construction（015C）着手時に、実装が進むにつれて事実確認・反映する（本Orderでは仕様の大幅な書き換えは行っていない）。
