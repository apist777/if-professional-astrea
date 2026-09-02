# Construction Order 016E-R2 実装報告 — HOME Spatial System / Full-Width Composition Convergence

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-02 09:05 JST
- **End**: 2026-09-02 18:45 JST（見込み、git push/CI確認後に確定）
- **Measurement Audit**: `docs/research/2026-09-02_016e-r2_spatial_system_measurements.md`

## 1. Root Cause Summary

Owner実機確認で指摘された8項目（Hero衝突、左右バランス、Heading-Content距離不統一、Section間Rhythm不統一、RESULTS→Professional近接、CTA孤立、Flow過度な余白、Full/Wide/Reading使い分けの不統一）を計測した結果、根本原因は主に3種類に整理できた。

1. **Hero H1衝突（P0）**: 016E-R1で導入した固定px単位のmax-width（760/460/480px）がViewport幅に比例して縮まらず、1440/1366/1024px幅で実際に見出し・ボタンが対角線と衝突していた（負のMargin=物理的重なりを実測で確認）。
2. **Section Rhythm不統一（P0）**: Heading→Content間隔とSection→Section間隔が同じCSSプロパティ（H2のmargin + List要素の別のmargin）に無秩序に依存しており、特にResults→Professional/Professional→Price/CTA→FAQの3境界は実測0pxだった。
3. **CTA/Flowの「Full Width」失敗（P0）**: `.astrea-final-cta`・`.astrea-home-flow`いずれも外側Groupに`align:full`が無く、Page Template自身の`post-content`（`layout:inherit`）がSection要素自体（背景を含む）をTheme既定のcontentSize(720px)へ強制収縮していた。子要素CSSの`max-width:none`をいくら強めても、親のBox自体が既に狭ければ意味がないという、016E-R1のHero collision修正とは一段階レベルの異なるバグだった。

## 2. Spatial System定義（§3）

`:root`に3つの新規Custom Propertyを追加し、これ以降のHOME実装がすべて参照する共通語彙とした。

```css
--astrea-v3-grid-max: 1600px;      /* Plane B: Wide Content Grid（旧1440pxから拡幅） */
--astrea-v3-heading-gap: 56px;      /* Heading→Content（large token） */
--astrea-v3-section-gap: 96px;      /* Section→Section（x-large token） */
--astrea-v3-reading-max: 640px;     /* Plane C: Reading Width（新規、将来のParagraph-heavy Content用） */
```

Plane A（Full Width背景）/Plane B（Wide Content Grid）/Plane C（Reading Width）の3階層は既存のBackground-vs-Content分離原則（016D確立）をそのまま踏襲し、新たに`--astrea-v3-reading-max`をPlane C用Tokenとして明文化した。

## 3. Hero Collision Resolution（§4）

`clamp()`によるViewport追従のResponsive max-widthへ変更（Kicker/H1: `clamp(420px,36vw,680px)`、Body: `clamp(300px,30vw,460px)`、Buttons: `clamp(300px,30vw,480px)`）。診断用に`hero-safety-verify.js`（対角線の実際のクリップ位置を各要素の実座標から逆算し、Margin>0を機械的に検証するScript）を新規作成し、1920×1200/1920×900/1440×900/1366×768/1024×768/1024×600の6構成×3要素=18ケース全てでSAFEを確認した。長い事務所名（24文字超）ストレステストでも安全マージンを維持することを確認した。

行数は3〜4行（Viewport幅により変動、Referenceの2行に対し+1〜2行）となり、016E-R1の3行より若干増えたが、Order §4の明示的な優先順位「行数よりcomposition safetyを優先する」に従い、実際の衝突を解消することを優先した。

## 4. Section Rhythm System実装（§5）

共有H2 Selector（`h2:has(+ .wp-block-astrea-X-list)`、Kicker System）の`padding-top:28px`を`margin-top:var(--astrea-v3-section-gap)`へ、`margin:0 0 large`を`margin:var(--astrea-v3-section-gap) 0 var(--astrea-v3-heading-gap)`へ変更。あわせて、List要素側の重複した`margin-top/bottom:x-large`ルール（H2のmarginと不規則に積み重なり0〜230pxのブレを生んでいた）を削除し、Heading→Content・Section→Sectionの両方をH2側の1箇所からのみ制御する設計に統一した。

## 5. RESULTS→Professional Correction（§6）

上記Section Rhythm統一の直接的な結果として、Results→Professionalのgapは0px→96pxへ改善した。RESULTS自体の強い暗色帯とProfessionalのEditorial Compositionの間に、Owner表現の「実績が終わり、新しい代表者紹介の章が始まった」と認識できる呼吸を確保した。個別の特殊対応（巨大な追加余白等）は行わず、共通Rhythm Systemの一環として自然に解決した。

## 6. CTA Full Width Band実装（§7）

`theme/patterns/home-cta.php`の外側Groupへ`align:full`を追加（`layout:constrained`の`contentSize`は600px→720pxへ微増）。これにより暗色背景がViewport全幅（1920px時、比率1.0）になり、内側の見出し+ボタンは適度な幅（720px）で中央寄せされたまま、Reference同様「Sectionそのものがfull-width dark band」という構成を実現した。高さ・Padding・Typography（016E-R1で確立した密度）は変更していない——「カードを横に大きくしただけ」にせず、Full-bleed背景+適切なCentered Inner Containerという構造にした。

## 7. Flow Wide Implementation（§8）

`theme/patterns/home-flow.php`の外側Groupへ`align:full`を追加（layoutは`constrained`→`flow`へ変更、Core自身の子要素幅強制ロジックを完全に回避）。CTAと全く同じ根本原因だったため、同じ手法で解決。Content width比率は0.326〜0.458（Viewport非依存の固定625px）から0.930〜0.951（Viewport追従）へ改善し、01/02/03の3 StepがDesktop上で堂々と横展開される構成になった。新しい装飾機能・アイコンは追加していない（既存のNumber/Title/Descriptionヒエラルキーを維持）。

## 8. その他のP1対応

- **FAQ（§9）**: Reading Column（720px→960px）を左寄せのまま適度に拡幅し、「右半分が完全に死んで見える」状態を緩和した。文章をViewport端まで伸ばすことはしていない。
- **Services/CASE/Price/Voice/Professional**: Visual Directionは無変更（LOCK）。Section Rhythm System適用による間隔変化のみ。

## 9. Responsive結果（§12）

| 幅 | Horizontal Overflow |
|---|---|
| 1920 | 0px |
| 1440 | 0px |
| 1366 | 0px |
| 1024 | 0px |
| 768 | 0px |
| 375 | 0px |
| 320 | 0px |

375/320含め、Desktop改善によるMobile破壊は無し（Flow/CTAは`align:full`化しても既存のMobile Media Queryがそのまま機能することを確認）。

## 10. Variations結果（§13）

Trust/Natural/Modern 3件とも新しいSpatial System（Wide Grid拡幅・Section Rhythm統一・CTA/Flow Full Width化・Hero衝突修正）が正常動作することを実機確認、Trustへ復元済み。

## 11. Core OFF/ON結果

Core OFF→ON、HTTP 200・Fatal無しを確認。

## 12. Test結果

- PHPUnit: 397/397 Pass（Core PHP無変更のため既存件数）
- PHPCS: 変更2ファイル（`home-flow.php`/`home-cta.php`）0 Errors/0 Warnings
- CASE写真バリエーション: Scenario A（Owner Baseline、全3件写真あり）が本Order開始時・終了時とも維持されていることを確認（新規の追加検証は本Orderでは実施せず、016E-R1で既に4パターン確認済みのため）

## 13. Screenshot Evidence

`docs/research/screenshots/016e-r2/`に12枚（Desktop Primary 3幅+Responsive Safety 4幅+Reference-vs-Actual比較2枚(Hero/CTA)+Before/After比較3枚(Services/Results-Professional/Flow)）。

## 14. 変更ファイル

- `theme/theme.json`（Spatial System Token新規、Section Rhythm統一、Hero Responsive max-width、FAQ拡幅）
- `theme/patterns/home-cta.php`（`align:full`追加、contentSize微増）
- `theme/patterns/home-flow.php`（`align:full`追加、layout変更）

Fixtureデータ（Page ID 1914のpost_content、CASE写真状態）はGit管理外のWordPress DB状態であり、リポジトリのDiffには含まれない。施工中のBefore/After比較用Fixture切替はすべて事前Backup→検証→Owner Baseline復元→復元確認の手順を踏んだ。

## 15. 発見した実バグ（正直な報告）

1. **Hero collision実クリップ**: 016E-R1の固定px max-widthが1440/1366/1024幅で実際に対角線とマイナスマージン（物理的重なり）を起こしていた。Owner報告の「まだ衝突している」は正確だった。
2. **CTA/Flowの`align:full`欠落**: Sectionの子要素CSSをいくら調整しても、親Group自身がPage Templateのconstrained post-contentに捕捉されていては無意味という、一段深い構造上の見落とし。CTA/Flow両方に共通する同一原因だった。

## 16. 未解決のOwner Decision事項

- Price→CTA間のgap（24px、意図的にPrice→Actionの直結演出として維持）——「Section Rhythmの完全な均一化」よりも「016E-R1で確立したPrice直後CTAの演出」を優先した判断であり、Owner確認を推奨する。
- FAQ右半分の死角は「緩和」であり「完全解消」ではない（960px幅拡張のみ、2カラム化等の構造変更は新機能追加リスクを避けるため見送った）。

## 17. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/Construction 016Fへの自動進行・新機能追加は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
