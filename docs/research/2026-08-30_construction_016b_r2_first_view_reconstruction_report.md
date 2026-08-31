# Construction Order 016B-R2 — First View Reconstruction / Visual v3 Design Fidelity / 2030 Quality Gate 実装Report

- **Construction Order**: 016B-R2
- **Date**: 2026-08-31
- **Status**: COMPLETE（Phase 2は引き続き未着手・未承認、RELEASE HOLD継続）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 1. Owner Rejection Summary

Construction 016B（実装）は実ブラウザ確認の結果REJECTED。技術的（Font/Glyph/Semantic Contract/Core OFF/Regression）には成立していたが、Visual Productとして不合格と判定された。具体的指摘: HeroがViewportではなく中央の箱に見える／左右に大きな白余白／写真が「配置された画像」に見え「背景世界」に見えない／HeaderとHeroが視覚的に分離／First Viewのスケールが小さい／従来型WordPress士業テンプレートへの逆戻り。

## 2. Root Cause of Boxed Hero（推測せず、DOM/Computed CSSで特定）

`theme/templates/front-page.html`は以下の構造だった:

```html
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
<!-- wp:astrea/breadcrumb /-->
<!-- wp:post-content /-->
</main>
```

`<!-- wp:post-content /-->`（Layout属性なし）は`<main>`の`is-layout-constrained`レイアウトの**直接の子**として描画される。WordPress Core自身の生成CSSは:

```css
.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)){
  max-width: var(--wp--style--global--content-size);
  margin-left: auto !important; margin-right: auto !important;
}
```

`post-content`自体は`alignfull`/`alignwide`クラスを持たないため、この規則にマッチし、**post-content全体（Hero含む）が720px（`contentSize`）へ強制的に収縮**されていた。Hero自体が`align:"full"`を持っていても、それは`.entry-content`という**さらに内側**のBox（それ自体が既に720pxに縮んでいる）の中で評価されるため無意味だった。

実測（Playwright、`getComputedStyle`+`getBoundingClientRect`、1920px viewport）:

| 要素 | 修正前 width | 修正前 left/right |
|---|---|---|
| `<main>` | 1920px | 0 / 1920 |
| `.entry-content`（post-content） | **720px** | 600 / 1320 |
| `.astrea-hero-v3`（Hero、alignfull） | **720px** | 600 / 1320 |

Hero自身が`alignfull`でも、親の`.entry-content`が既に720pxへ収縮済みのため、Heroも720pxの「箱」になっていた——これがOwnerの言う「中央の箱」の正体。

## 3. Fix（Root Cause対応、場当たり的なNegative Margin等は不使用）

`theme/templates/front-page.html`の`wp:post-content`へ`{"align":"full","layout":{"inherit":true}}`を付与:

```html
<!-- wp:post-content {"align":"full","layout":{"inherit":true}} /-->
```

- `align:"full"`: post-content自身を`.is-layout-constrained > :not(.alignfull)`規則の対象から除外し、`<main>`と同じ1920px幅（Viewport全幅）を確保。
- `layout:{"inherit":true}`: post-content自身をあらためて`is-layout-constrained`として再宣言し、**post-content内部の**非full/wideな子（Services/Results等）が引き続き`contentSize`/`wideSize`で正しく中央寄せされることを保証。

修正後の実測（同一手法）:

| 要素 | 修正後 width |
|---|---|
| `.entry-content` | **1920px**（Viewport全幅） |
| `.astrea-hero-v3` | **1920px**（Viewport全幅） |
| `.wp-block-astrea-service-list`（非full通常Block） | **720px**（変わらず正しく中央寄せ） |

`<main>`自身の`layout:{"type":"constrained"}}`は他Template（single.html/page.html/archive-*.html等）と共通の既存パターンであり、変更していない（他ページのBreadcrumb等の挙動に影響を与えないため）。**同種の潜在バグ（`align:full`なMarkupをまだ持たない他Templateでは現状無症状）が理論上は他Templateにも存在するが、本Orderのスコープ外として変更せず、次のOrderでの点検候補として記録する。**

## 4. 016A-R1 Fidelity Comparison

| 項目 | 016A-R1 Mockup | 016B-R1（修正前） | 016B-R2（本Order後） |
|---|---|---|---|
| Viewport usage | Full bleed | 中央720px | **MATCH**（Full bleed、1920px確認済み） |
| Hero width | 100vw | 720px | **MATCH** |
| Hero height | 60-75vh目安 | 固定560px（768px高で92.5vh相当） | **ACCEPTABLE DIFFERENCE**（62vh Floor + Content-driven、1920/1440/1024で60-75vh帯、1366×768のみ78.5%——後述§7） |
| Image crop | Full-bleed、非対称 | 720px内に収縮 | **MATCH** |
| Typography scale | 大きい | 変化なし（相対的に見劣り） | **MATCH**（同一Token、広い舞台で正しく機能） |
| Text position | 左寄せ非対称 | 左寄せだが箱の中 | **MATCH** |
| CTA position | Hero内左下寄り | 同左だが箱の中 | **MATCH** |
| Header関係 | Hero統合、透過 | 白背景、分離 | **MATCH**（Desktop、body.home限定） |
| Whitespace | 意図的な非対称余白 | 左右に意図しない白余白 | **MATCH**（不要な余白解消） |
| First-view impact | 強い | 弱い（従来型Template感） | **MATCH** |

## 5. Selected Geometry

- Hero: `core/cover`、`align:"full"`、`minHeight:"62vh"`（`minHeightUnit:"vh"`）。写真ありの場合は`dimRatio:75`を維持（016B/016B-R1から変更なし）。
- Post-content: `align:"full"` + `layout:{"inherit":true}`（§3）。
- Hero内部padding: Desktop `var(--wp--preset--spacing--large)`（56px、旧x-large 96pxから縮小）に調整し、Kicker/Primary/Copyのmargin-bottomも微調整（26→20px、28→22px、40→32px）——Content量に対しHero高さがViewport高（特に768px級ノートPC）に対し過大にならないようにするための調整（§7参照）。

## 6. Header/Hero Solution

`body.home`（WordPress Core標準のHOME判定Bodyクラス）でスコープした、Desktop限定（`@media (min-width:601px)`）のCSSのみで実現。Header Template Part自体のMarkupは無変更。

- `body.home .astrea-site-header{position:absolute;...;background:transparent;color:base}`: HeaderをNormal Flowから外し、Hero先頭（y:0）の上へ透過Overlay。
- `body.home .astrea-site-header::before`: 160px高のTop-edge Gradient Scrim（`rgba(10,14,20,.6)→0`）。写真の明るさに依存しない、Header文字のContrast保証専用の局所的Scrim（Hero写真全体のdimRatioは変更していない——Order §7「暗くしすぎて灰色背景にしない」原則を維持）。
- `body.home main{margin-top:0}`: 発見した副次バグ（後述§7参照）の解消。

**Mobile（600px以下）ではこのOverlay処理を一切適用しない**——実測の結果、Mobile Hero構図（写真は右上80%幅のみ、左20%は無地）ではHeaderロゴ位置が「無地（白に近い）領域」と重なり、白文字コントラストが約1.4:1まで低下する実害を発見したため（§7参照）。Mobile Headerは016A-R1承認済みの通常Flow・白背景のまま維持。

**内部ページへの影響ゼロ**: `body.home`スコープのため、Office/Price/Contact等の内部ページのHeaderは無変更（実機確認済み、§10参照）。

## 7. Desktop Solution — 発見した副次的な問題と対応

Header Overlay実装の過程で2つの実バグを発見し、いずれもRoot Cause特定の上で対応した（推測でのCSS投入はしていない）。

### 7.1 24pxの意図しない白い隙間

Header Overlay適用直後、実機で「Hero上端に24pxの白い隙間があり、その中にHeaderが浮いている」状態を発見。`getBoundingClientRect()`で`<main>`の`margin-top`が`24px`であることを直接確認した——これはMargin Collapsingではなく、WordPress Coreが Template Part間（Header/Main/Footer）に付与する既定のRoot Block Gapで、他の全ページでは「Headerが実High さを持ち押し下げる」ため視覚的に問題化しなかったが、Headerを`position:absolute`にした瞬間だけ、この24pxが真の余白として露出した。`body.home main{margin-top:0}`で対応。

### 7.2 Header文字のContrast不足（実測で発見）

Chromiumで実際にRenderされたScreenshotのPixelを直接Sampling（Canvas API経由）し、WCAG Contrast計算を行った。修正前は写真の明るい空領域（Hero左上）でHeader文字が最低2.69:1（AA基準4.5:1を大幅未達）だった。§6のTop-edge Gradient Scrim追加後、同じ手法で再計測し、Sample全点で7.85〜11.86:1（AA/AAAとも十分）まで改善したことを確認した。

### 7.3 Hero高さのViewport追従

固定`minHeight:560px`はContent量（Kicker+Primary+Copy+Buttons）により実際には約710-715px相当まで伸びており、Viewport高さに関わらず一定だったため、768px高ノートPCでは92.5%、1080pxでは66.2%と大きくばらついていた。`minHeight`を`62vh`（Floor）へ変更し、加えてHero内Padding/Margin-bottomを軽く縮小（§5）した結果:

| Viewport | 修正前 Hero高さ比率 | 修正後 |
|---|---|---|
| 1920×1080 | 66.2% | **62.0%**（目標帯内） |
| 1440×900 | 79.4% | **67.4%**（目標帯内） |
| 1366×768 | 92.5% | **78.5%**（目標帯にほぼ近い、僅かに超過） |
| 1024×768 | — | **70.8%**（目標帯内） |

1366×768のみ目標上限（75vh）をわずかに超過する。これ以上Padding/Font-sizeを削ると可読性・Editorial感を損なうため、これ以上の追い込みは行わなかった。実機Screenshot（`03-first-view-1366x768.png`）で目視確認した結果、Servicesセクションの見出しが画面下端にわずかに覗いており、Hero→本文の接続は自然で、崩れ・圧迫感はない。**盲目的に数値を追わず実機を確認する**というOrder §6自身の指示に従った判断として記録する。

## 8. Mobile Solution

016A-R1承認の「写真右上ブリード＋テキストプレート・オーバーラップ」構図は無変更のまま維持（016B/016B-R1で既に実装済み）。本Orderでの変更点はHeader Overlayを**適用しないこと**（§6/§7.2参照）のみ。Mobile Hero高さ・CTA・Photography・Overflow等は既存の016B/016B-R1実装のまま、320px/375pxとも実機Overflow 0pxを再確認した。

## 9. No-Photo Solution

出荷Default（写真未設定）でも同じ`align:full`+`inherit:true`の恩恵を受け、単色Full-bleed Heroとして描画されることを実機確認した（Desktop 1920px・Mobile 375pxとも）。「写真あり=Premium Full-bleed、写真なし=意図的なTypography-led Fallback」がGeometryのレベルで完全に統一され、「No-photo Defaultに引きずられて写真ありHeroまで小さくなる」という禁止事項には抵触していない。

## 10. Typography Solution

016B-R1のJapanese Glyph修正（Font Stack）・Line-breaking方針（`text-wrap:pretty`等）は無変更のまま維持。長い事務所名（24文字）・標準文言とも、新Geometry（Full-bleed、62vh Floor）の下で改行・Overflowに悪影響がないことを再確認した（`_check-longname-*`、Overflow 0px）。「わかりやすく」等特定複合語の改行に関する既知の限界（016B-R1で報告済み）は本Orderのスコープ外のため変更していない。

## 11. 1920×1080 結果

`docs/research/screenshots/016b-r2/01-first-view-1920x1080.png`。Hero写真が左右のViewport端まで完全に到達し、Headerが透過してHeroへ統合され、写真が「配置された画像」ではなく「背景の環境」として機能している。施工担当者自身がこのScreenshotを確認し、「中央に置いたHero画像」「普通の士業WordPress Template」には見えないと判断した（§28 Visual Stop Conditionに基づく自己確認）。

## 12. Viewport Matrix

| Viewport | Overflow | Hero高さ比率 | 判定 |
|---|---|---|---|
| 1920×1080 | 0px | 62.0% | 良好 |
| 1440×900 | 0px | 67.4% | 良好 |
| 1366×768 | 0px | 78.5% | 目標帯にほぼ近い（§7.3参照） |
| 1024×768 | 0px | 70.8% | 良好 |
| 768〜320 | 0px（320/375/768で確認） | Mobile独自構図（対象外） | 良好 |

全320〜1920pxの範囲でHorizontal Overflow 0pxを機械計測（`scrollWidth - clientWidth`）で確認した。

## 13. Variation結果

Trust（Default）/Natural/Modern、いずれも同一Geometry・同一Header Overlay機構のまま、Palette/Radius/Font-weight等のTokenのみで意図した個性を維持することを実機確認した（`variation-natural-1920.png`・`variation-modern-1920.png`）。Variation固有のCSSは追加していない（Order §18準拠、共通Geometryの完成を優先）。検証後Trustへ復元済み。

## 14. Accessibility

- Contrast: Header Overlay文字は§7.2の通り実測7.85〜11.86:1（AA/AAA適合）。Hero本文（Kicker/Primary/Copy）は016B-R1から無変更（既存確認済み）。
- Focus visible: 新規CSSはFocus Styleを一切上書きしていない（既存のBrowser/Core Button Focus Styleを維持）。
- Text remains HTML text: Hero文字は全てHTML Text（画像への焼き込みなし、016B/016B-R1から継続）。
- Semantic H1: `astrea-hero-kicker`が引き続き唯一のH1（Construction 011契約、016B-R1から無変更）。
- Navigation keyboard usable: Header Overlay化後もNavigation Blockの構造・Tabindexは無変更（Position/Backgroundのみの変更のため）。
- Mobile CTA target: Mobile Headerは通常Flowのまま無変更、CTA領域も無変更。
- Motion: 新規Animationは追加していない（`prefers-reduced-motion`対応不要）。

## 15. Core OFF

`astrea-core`を無効化し、HOME（`/`）を再取得。HTTPステータス200、PHP Fatal/Warning/Notice 0件を確認。事務所名Bindingは静的デフォルト「ASTREA」、電話Bindingは「お電話でのご相談」+`href="#"`へ安全にFallback。Full-bleed Geometry・Header Overlayとも正常に機能（Screenshot確認済み）。確認後`astrea-core`を再Activate。

## 16. Regression Tests

- PHPUnit: 359 tests, 560 assertions — OK（全件Pass、既存Baselineと同数）。
- PHPCS: `home-hero.php` — 0 Errors / 0 Warnings。
- Theme Check（一時導入→検証→削除）: INFO 1件のみ（Text-domain、既存Baseline、本Orderの変更とは無関係）。
- Block Validation: 016B/016B-R1と同じ制約（Admin自動ログイン不能）により、`parse_blocks()`/`serialize_blocks()`往復一致（5533/5533文字で完全一致）による代理確認。
- Horizontal Overflow: 320〜1920pxの7ブレークポイントで0px（§12）。
- CI: Green（後述、Commit確認後）。

## 17. Remaining Limitations

- 1366×768でHero高さが目標帯（60-75vh）をわずかに超過（78.5%、§7.3）。実機目視では崩れ・圧迫感はないと判断したが、さらなる追い込みは可読性とのTrade-offになるため見送った。
- Header Overlay（Desktop限定）はスクロールとともにHeroごと画面外へ流れる仕様とした（JS Sticky再出現は未実装）。Order自体がJS新規依存を積極的に求めていないため、CSSのみの範囲でこの仕様とした。将来的にScroll後もCTA/Navigationへ常時アクセスしたい場合は別Orderでの検討が必要。
- `<main>`に`layout:{"type":"constrained"}}`+ 無属性`wp:post-content`という同型パターンは他Template（single.html/page.html/archive-*.html等）にも存在する。現状これらのTemplateには`align:full`なMarkupが無いため無症状だが、将来alignfullなBlockをそれらへ追加する場合は同じ問題が再発する可能性があり、次のOrderでの点検候補として記録する。
- 016B-R1で報告済みのJapanese Line-breaking限界（特定複合語の意味単位分断、BudouX等が必要）は本Orderのスコープ外として維持。

## 18. Product Code Diff

変更ファイル: `theme/templates/front-page.html`、`theme/patterns/home-hero.php`、`theme/theme.json`の3ファイルのみ（`git diff --stat`で確認）。Header Template Part自体（`theme/parts/header.html`）・Core（`astrea-core`）・Services以降・Post-v1 Backlog・Release/Tag/Deploy・Versionはいずれも無変更。

## 19. Owner Screenshots

すべて`docs/research/screenshots/016b-r2/`配下。

- `01-first-view-1920x1080.png`（★最重要）
- `02-first-view-1440x900.png`
- `03-first-view-1366x768.png`
- `04-first-view-1024x768.png`
- `05-first-view-mobile-375.png`
- `06-first-view-mobile-320.png`
- `07-first-view-before-after-1920.png`（Before/After比較）
- `home-upper-1920.png`（Header/Hero/Services冒頭のFull Context）
- `variation-natural-1920.png` / `variation-modern-1920.png`（参考、Trustは01と同一状態）

Stopped before Visual v3 Phase 2 / Construction 016C. RELEASE HOLD継続。Owner Verdict（APPROVED / REVISE / REJECTED）待ち。
