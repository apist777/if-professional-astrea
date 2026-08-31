# Construction Order 016D — Visual Geometry / Grid Fidelity Reconstruction + ASTREA Icon System Integration 実装Report

- **Construction Order**: 016D
- **Date**: 2026-09-01
- **Status**: **AWAITING OWNER VISUAL ACCEPTANCE**（RELEASE HOLD継続）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. Owner Verdict / Reference Imageの状況

Owner指摘: 「見本と結構左右の空白とか違い多いよね。ヘッダーの文字の位置とか、これじゃぁ目指しているものと結構ちがくない？」を受け、Header/Hero/Services/CASE/RESULTS/Professionalが「別々の座標系」に見える問題を再施工する、というOrderを受領した。

Order当初は`docs/research/references/visual-v3-owner-reference.png`をVisual Geometry Source of Truthとする指示だったが、**このファイルは実際には配置されず**（フォルダ自体は作成されたがファイルは存在しないままだった）、Owner自身が後続の指示で「Do not block the entire 016D construction solely because [the reference image] does not exist」「Where the written 016D order conflicts with an unavailable external reference image, do not invent pixel measurements from that missing image」と明示的に方針転換した。

本Reportは、この方針転換に従い、**画像リファレンスからのピクセル測定は一切行っていない**（存在しないため測定不可能）。かわりに、Owner本人が直接文章で示した「Owner's key geometry corrections」（本文書§2参照）と、既存の承認済みSpecification（`docs/specifications/07_astrea_visual_v3_design_direction.md`）・Ownerが配置済みのASTREA Icon System Asset（`theme/assets/icons/`）を根拠として実装した。**「Pixel-perfect / Reference-measured」を主張できるのはOwner自身の実機（現在の実装）に対する自己測定分のみであり、外部見本画像との比較は行っていない**ことを明記する。

## 1. Root Cause Analysis（推測せず、実測で特定）

CSS変更前に、現在実装のGeometryを実機（Playwright、1920px viewport）で直接測定した。

| 要素 | 測定値（修正前） |
|---|---|
| Header Logo (`.astrea-header-identity`) | left: **0px**（Viewport端に密着） |
| Header Actions (`.astrea-header-actions`) | right: **1920px**（Viewport端に密着） |
| Hero Text (`.astrea-hero-text`) | left: **52px**（Cover内包の実質flush left） |
| Hero Kicker (`.astrea-hero-kicker`) | left: **630px**（Hero Text内部で`layout:constrained,contentSize:660px`により自己中央寄せ） |
| Services (`.wp-block-astrea-service-list`) | left: **600px** / right: **1320px**（既定の`contentSize:720px`中央寄せ） |
| CASE (`.wp-block-astrea-case-list`) | left: **600px** / right: **1320px**（同上） |
| Results (`.wp-block-astrea-results-list`) | left: **0px** / right: **1920px**（Full-bleed、内部Padding基準なし） |
| Professional (`.wp-block-astrea-representative`) | left: **0px** / right: **1920px**（Padding無し） |

**これが「別々の座標系に見える」の実体**: Header(0px始まり) / Hero(52px→630px、写真領域内で独自中央寄せ) / Services・CASE(600px、既定のcontentSize基準) / Results・Professional(0px、Padding無し) が全て異なる基準で配置されていた。Header自体は`theme/parts/header.html`のBlock属性で`padding:{"left":"var:preset|spacing|medium",...}`を宣言していたが、Template Part経由のレンダリングでこの値がインラインStyleとして一切出力されていないことも実機確認した（Gutenbergの通常のPost Content経由Renderとは異なる挙動——原因の完全特定より先に、theme.json側の明示CSSで確実に制御する方針へ切り替えた。他のPatternで`.wp-block-astrea-closing-cta`等の重要なSpacingが全てtheme.json内の明示CSSクラスで実装されている既存の設計判断と一貫させた）。

## 2. Shared Wide Grid 設計

```css
:root{
  --astrea-v3-grid-max: 1200px;
  --astrea-v3-grid-gutter: var(--wp--preset--spacing--medium); /* 2rem = 32px */
}
```

- **magic-number不使用**: `1200px`は新規の値ではなく、既存の`theme.json` `settings.layout.wideSize`（Visual v2から既に存在するDesign Token）をそのまま再利用した。`32px`ゲート幅も既存の`medium` Spacing Presetをそのまま再利用した。
- **`:root`スコープ**（`body.home`限定ではない）: Headerの「Viewport端に密着する」問題は内部ページ（Archive/Single/Office等）でも同一の欠陥であり、実機確認の結果、内部ページのHeaderも同様に0px始まりだった。Order §5「Header内部の左右paddingをviewport基準ではなくGrid基準へ」は文脈上HOME限定の指示ではなく、Header自体がSite-wide Template Partである以上、一貫して直すのが正しいと判断した。Hero/Services/CASE/Results/Professionalは元々HOME以外に存在しないBlockのため、スコープの実害はない。
- **適用パターン**: `padding-inline:max(var(--astrea-v3-grid-gutter),calc((100% - var(--astrea-v3-grid-max)) / 2))`。狭い画面では最小32pxゲートを確保し、広い画面では1200px中央寄せへ収束する、CSS標準のContainer Queryパターン（新規JS依存なし）。
- **背景とContentの分離**（Order §4）: `.astrea-site-header`・`.wp-block-astrea-results-list`はいずれも要素自体は引き続き`width:100%`（Full bleed、背景・写真・濃色帯はViewport端まで到達）で、上記Padding Formulaにより**内部Content（Logo/Nav/数字等）のみ**が共有Gridへ inset される。

## 3. Header Reconstruction

- `.astrea-site-header`へ`padding-inline`（共有Grid）・`padding-block`（既存`small`token）を追加。
- 副作用として発見・修正した実バグ: 長い事務所名（24文字）で`.astrea-header-actions`（Nav/CTA）が2段目へwrapし、`position:absolute`のHeaderがHero Kickerと重なる事象を実機で発見した（§9参照）。`.astrea-header-identity`へ`min-width:0;max-width:min(480px,45%)`（Desktop・HOME限定）を追加し、Logo文字列自体が列内で複数行に折り返すことでNav/CTAを同じ行に維持する形で解決（文字を隠す/省略記号にする対応は不採用——016B-R1で確立した「事務所名の一部を隠さない」原則を維持）。

## 4. Hero Reconstruction

- `home-hero.php`: `.astrea-hero-text`の`layout:{"type":"constrained","contentSize":"660px"}`を削除し、CSS側で`max-width:660px`（`margin:auto`なし）のみを付与——中央寄せをやめ、親（Grid-paddedされた`.wp-block-cover__inner-container`）の左端にそのまま接する構成へ変更した。
- `.wp-block-cover__inner-container`のPaddingを、既存の`var(--wp--preset--spacing--medium)`固定値から共有Grid Formulaへ変更。
- 結果、Hero Kicker/H1/Copy/CTAの左端が**Header LogoおよびServices/CASE/Results/Professionalと完全に同じx座標（1920px幅で360px）**になったことを実測確認した（§1のBefore表と対比）。
- Photographyは元々`core/cover`のFull-bleed写真のまま変更していない——「非対称：左Editorial Copy／右Photography」という構図自体は既に016B-R2で成立しており、今回のGeometry修正はTextの水平位置のみを対象とした。
- Mobile: Order §6の指示通り、016A-R1/016B-R2で確立済みのMobile Hero構成（写真右上ブリード＋Textプレート）は一切変更していない（Mobile専用の`@media (max-width:600px)`ブロックは無変更）。

## 5. Services Geometry

- `.wp-block-astrea-service-list`へ共有Grid Padding、`max-width:none;width:100%`を追加し、既定の`contentSize:720px`центраlize（600–1320px幅）から共有Grid幅（1920px viewportで360–1560px、実質1200px幅）へ拡大した。
- 列間に縦罫線（`border-left`、2列目以降）を追加（新規指示、Resultsの既存罫線と同じVisual Languageに統一）。

## 6. CASE / RESULTS / Professional Alignment

- **CASE**: Servicesと同じ共有Grid Paddingを追加。**016C確立のEditorial Row構成（Number/Body/Media）はそのまま維持した**——理由は§10参照。
- **RESULTS**: 既存の16B〜016Cで確立したFull-bleed濃色帯構造は維持しつつ、内部Paddingを固定`medium`値から共有Grid Formulaへ変更。ダーク背景自体はViewport端まで変わらずFull-bleed。
- **Professional**: `.wp-block-astrea-representative`へ共有Grid Paddingを新規追加（元々Padding自体が存在しなかった）。加えて、Order §11「巨大な写真+小さなプロフィール、ではなく人物と専門性の両方が主役に」を受け、写真幅を56%から**46%**へ縮小し、Info側（氏名・資格・Bio）のFont SizeとMax-widthを拡大、写真とInfoのバランスを再構築した。

## 7. ASTREA Icon System Integration

`theme/assets/icons/`配下の10ファイルを監査した（Order §8）。

| 監査項目 | 結果 |
|---|---|
| SVG validity | 全10件、有効なXML/SVG |
| viewBox | 全て`0 0 48 48`で統一 |
| Hard-coded寸法 | `width`/`height`属性なし（CSSで自由にScale可能） |
| Fill/Stroke | Main: `#102A43`固定、Accent: `#B99A5C`固定（`currentColor`不使用——外部`<img>`として使う場合はNavy/Gold固定表示になる設計。Variation追従にはInline化+`currentColor`置換が必要、既存の`docs/research/visual-v3/assets/icons/astrea/README.md`の推奨手法と一致） |
| Accessibility | 全て`role="img" aria-hidden="true" focusable="false"`（装飾用、内部`<title>`なし——ラベルの二重化なし） |
| 外部参照/Script | なし |
| 出所 | Owner-created（`docs/research/visual-v3/assets/icons/astrea/`の既存Research Assetとバイト単位で同一内容——`diff`で確認済み。Theme正式Assetとしての再配置と理解） |

**Git追跡**: 配置済みだった10ファイルはこれまでGit追跡対象外だった（`git ls-files`で確認）。本Orderでコミット対象へ追加した。**配置場所は変更していない**（Order §「技術的理由が無ければ現状の場所を維持」に従う——`theme/assets/icons/{price,results,services}/`のディレクトリ構造はOwnerの配置のまま）。**Packaging**: `tools/release/package.sh`は`theme/`ディレクトリを丸ごとrsyncするのみで、Assetの除外パターンは存在しないことを確認済み——追加の対応不要。

### 7-A. Services Icons

`services/folder.svg`のみ、Serviceごとに共通の汎用アイコンとして016Cから継続使用（Inline化・`currentColor`変換版、内容はバイト単位で実ファイルと一致することを再確認済み）。**個別Service向けのIcon選択機能は実装していない**——`astrea_service`投稿タイプにIcon選択Fieldが存在せず、新設は本Order（Visual Geometry施工）のScope外とOwnerが明示。日本語Fixtureタイトルからの推測Mappingも明示的に禁止されているため、この設計判断を報告のみに留める（§11参照）。

### 7-B. Results Icons

`results/result-check.svg`を全RESULT項目共通のアイコンとして新規導入（Inline化・`currentColor`変換）。Servicesと全く同じ理由（`astrea_result`にもIcon選択Fieldが無く、Label文字列からの推測は禁止）で、個別選択ではなく統一アイコンとした。ICON/BIG NUMBER/LABELの3階層（Order §8-B）が実現した。

### 7-C. Price Icon

`price/price-yen.svg`は監査のみ実施し、**実装への組み込みは行っていない**。Order自身が「Price下半分の全面Visual v3 redesignはGeometry approval後の次工程でもよい」と明示しており、Price Section自体のGeometry修正が今回のOwner指摘リストに含まれていなかったため、Asset監査（§7表）を完了した段階で留めた。

## 8. Responsive結果

| 幅 | Horizontal Overflow |
|---|---|
| 320px | 0px |
| 375px | 0px |
| 768px | 0px |
| 1024px | 0px |
| 1366px | 0px |
| 1440px | 0px |
| 1920px | 0px |

全幅、Playwrightで`document.documentElement.scrollWidth - clientWidth`を機械計測（目視だけでなく数値でも確認）。Mobile（≤600px）はDesktop Asymmetric Layoutの縮小版ではなく、016A-R1/016B-R2で確立済みの独立したMobile Compositionをそのまま維持——変更していない。

## 9. 実装中に発見・修正した実バグ（推測せず、実測で特定）

### 9.1 長い事務所名でHeader Nav/CTAが2段目へWrapし、Hero Kickerと重なる

1920px viewportで24文字の事務所名（東京都〇〇許認可・法人設立・相続手続総合行政書士事務所）を設定して実機確認したところ、共有Grid導入によりHeader全体の横幅が1920pxから1200px相当へ縮小した結果、Logo(573px)+Actions(659px)が1200px幅に収まらず、Flexboxの`flex-wrap:wrap`によりActionsが2段目へ落ち、`position:absolute`のHeader（016B-R2、Hero上へ透過Overlay）がHero Kickerと視覚的に重なる事象を発見した。`.astrea-header-identity`へ`min-width:0;max-width:min(480px,45%)`（Desktop・HOME限定）を追加し、Logo文字列自体を列内で複数行折り返しさせることでActionsを同じ行に保つ形で解決した。

### 9.2 上記修正の副作用（Mobile破壊）を自己発見・修正

上記の修正を`@media`スコープなしで適用した結果、Mobile（320px）で標準長の事務所名（11文字）まで3行に折り返される新規Regressionを自己発見した——Mobileでは元々Nav/CTAがHamburger Menuに格納されHeaderの横幅を圧迫しないため、この制約自体が不要だった。修正を`@media (min-width:601px)`かつ`body.home`スコープ内へ限定し、Mobile/内部ページには影響しないことを実機再確認した。

## 10. CASE Geometry — "3-column" 指示について（未対応、理由を明記）

Order §9は「Owner提示見本が3-columnであれば採用する」という条件付き指示だったが、参照画像が実際には提供されなかったため、この条件は発動していない。§0の方針に従い、**外部見本からの推測は行わず**、既に承認済みのSpecification 07番§9・016A-R1で承認されたMockup（`visual-v3-home-desktop-r1.html`の`case-editorial`構造）・016Cで実装済みのEditorial Row構成をそのまま維持した。3-column化がOwnerの意図と一致するかどうかは、実際の見本画像が提供された時点で改めて判断する必要がある——本Orderでは断定的な変更を行わず、この論点を未解決のまま報告する。

## 11. Accessibility

- H1は引き続き1個のみ（`astrea-hero-kicker`、Construction 011契約無変更）。
- Landmark（header/nav/main）・Skip Link・Focus Visible・Keyboard Navigationは今回のCSS変更（Padding/Max-width/Flex制御）の影響を受けない要素のみを変更しており、無変更。
- 新規Icon（Services/Results）はいずれも`aria-hidden="true" focusable="false"`の装飾要素として実装、可視ラベル（Service名/Result Label）と重複する内部Titleは追加していない。
- Contrast: 新規CSSは色を追加していない（既存Token参照のみ）ため、既存のWCAG確認結果（016B-R1/016B-R2）から変化なし。

## 12. Core OFF

`astrea-core`を無効化し、HOME（`/`）を再取得。HTTPステータス200、PHP Fatal/Warning/Notice 0件を確認。共有Grid CSSはtheme.json由来のためCore状態に依存せず正常に機能。確認後`astrea-core`を再Activate。

## 13. Variation Tests

Trust（Default）/Natural/Modern、いずれも同一Markup・同一Grid CSSのまま、Palette/Radius/Typography Tokenのみで意図した個性を維持し、共有Gridの位置（1920px幅で360/1560px境界）が3 Variationとも完全に一致することを実機確認した。検証後Trustへ復元済み。

## 14. Changed Files

```
core/includes/results-list-block.php     (Results Icon統合)
theme/patterns/home-hero.php             (Hero Text構造変更)
theme/theme.json                         (共有Grid CSS、全Section適用)
theme/assets/icons/                      (10 SVGファイル、新規Git追跡)
```

`core/includes/service-list-block.php`・`core/includes/professional-profile-block.php`・`core/includes/case-list-block.php`は016Cから無変更（今回はCSS/Grid GeometryとResults Icon統合のみ）。Header Template Part自体（`theme/parts/header.html`）のMarkupも無変更（すべてCSS側で対応）。

## 15. Test Results

- PHPUnit: 359 tests, 560 assertions — OK。
- PHPCS: `results-list-block.php` — 0 Errors / 0 Warnings。
- Theme Check（一時導入→検証→削除）: INFO 1件のみ（既存Baseline、本Orderと無関係）。
- Archive Page（Service）: Card型Designを完全維持していることを実機確認（Header Padding修正の恩恵のみ受け、他は無変更）。
- `tools/ci/smoke-test.sh`はOwner Fixture保護のためローカル未実行。

## 16. Known Remaining Visual Differences

- §10の通り、CASE 3-column化はOwner提示見本が無いため未実施。
- Results数値（`clamp(3.2rem,6vw,5.4rem)`、016Cで導入）は共有Grid幅（1200px、3列）内で「200社以上」のような4〜5文字の値が2行に折り返す場合がある（Overflowではない、意図的な大型数字の副作用）。
- 1366×768での Hero高さが目標比率をわずかに超過する件（016B-R2で既知・容認済み）は本Orderでは対応していない。
- Price/FAQ/VOICE/Flow/Closing CTAのVisual v3全面再設計は、Order §13の通り本Orderの主目的外として未着手。

## 17. Screenshots

`docs/research/screenshots/016d/`配下:

- `02-before-after-1920.png`（016C→016D比較、Header/Hero/Sectionsの座標統一を一目で確認可能）
- `05-home-1920-full.png` / `06-home-1440-full.png` / `07-home-1366-full.png`（Desktop Primary widths、Full Page）
- `08-home-1024.png` / `09-home-768.png` / `10-home-375.png` / `11-home-320.png`（Responsive Safety widths）
- `12-crop-header-hero.png` / `13-crop-services.png` / `14-crop-case.png` / `15-crop-results.png` / `16-crop-professional.png`（各Section拡大）

**Owner提示見本とのSide-by-side比較画像（`01/03/04-reference-vs-*`）は、参照画像が提供されなかったため作成していない**——実機測定に基づく実装結果のみを報告する。

## 18. Exact Start / End / Duration

- Start: 2026-08-31 17:57 JST（前回Order 016C完了コミット時刻、以降継続的にこのOrderへ従事）
- End: 2026-09-01 01:58 JST
- Duration: 約8時間01分

**内訳の注記**（推測ではなく事実として記録）: この時間には、当初指定された`docs/research/references/visual-v3-owner-reference.png`の到着待ち（複数回の確認・Owner側の方針転換連絡を含む往復）が含まれる。実際のGeometry実装作業（測定・CSS実装・テスト・スクリーンショット取得）はOwnerによる最終Clarification（既存theme/assets/icons/の使用指示、見本非依存での実装継続指示）の受領後に集中して行った——正確な着手時刻の証跡（Fileタイムスタンプ等）が残っていないため、Durationは「Order受領からComplete報告までの継続した対応期間」として記録する。

## 19. Commit Hashes

- `db92883` — Construction 016D実装コミット（CI green確認済み）
- `<HISTORY確認コミット>` — HISTORY.csv更新（次コミット、CI green確認後に本文書へ追記）

## 20. HISTORY.csv Update

別途HISTORY.csvへ本Orderの行を追加する。

---

**Status: AWAITING OWNER VISUAL ACCEPTANCE**

Construction 016E / Release作業には進みません。Owner確認後、APPROVED / REVISE / FAILのいずれかのご判断をお待ちしています。
