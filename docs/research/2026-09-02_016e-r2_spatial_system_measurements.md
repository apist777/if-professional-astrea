# Construction 016E-R2 — HOME Spatial System Measurement Audit (Before/After)

1920/1440/1366px、Playwright実機（`getBoundingClientRect`/`getComputedStyle`）による計測。CSS変更前に実測してから施工した（§2）。

## Section Heading → Content Gap（8 Section共通化）

| Section | Before | After |
|---|---|---|
| Services | 96px | 56px |
| CASE | 96px | 56px |
| Professional | 56px | 56px |
| Price | 96px | 56px |
| FAQ | 96px | 56px |
| Voice | 96px | 56px |
| Flow | 56px | 56px |

`--astrea-v3-heading-gap`（56px、large token）に統一。以前はList要素側の重複`margin-top:x-large`(96px)がH2のmargin-bottomと予測不能に積み重なっていた。

## Section → Section Gap（章の呼吸）

| 境界 | Before | After |
|---|---|---|
| Services→CASE | 96px | 96px |
| CASE→Results | 230px | 202px |
| **Results→Professional** | **0px** | **96px** |
| **Professional→Price** | **0px** | **96px** |
| Price→CTA | 96px | 24px（意図的、§後述） |
| **CTA→FAQ** | **0px** | **96px** |
| FAQ→Voice | 96px | 96px |
| Voice→Flow | 121px | 145px |

`--astrea-v3-section-gap`（96px、x-large token）をH2のmargin-topとして統一適用。特にResults→Professional/Professional→Price/CTA→FAQが0pxだった実害（Owner指摘の「実績の続きに代表者がくっついている」の直接原因）を解消した。Price→CTAは016E-R1で確立した「Price→Action」の直結演出を維持するため意図的に据え置いた（Reportに明記）。CASE→Resultsの202pxは、CASE自身のSurface背景のpadding-bottomとResults H2のmargin-topが積み重なった結果で、2つの強い色面の間の余裕として妥当と判断した。

## Wide Grid（Plane B）

| 項目 | Before | After |
|---|---|---|
| `--astrea-v3-grid-max` | 1440px | 1600px |
| 1920px幅での左右余白 | 240px（12.5%） | 160px（8.3%） |

## Final CTA（§7 Full Width Band）

| 項目 | Before | After |
|---|---|---|
| Section width | 720px（固定、中央寄せ） | 1920px（Viewport全幅、Full-bleed背景） |
| Width/Viewport比率 | 0.375〜0.527（幅により変動） | 1.0（全幅） |
| 内側コンテンツ幅 | 600px | 720px |

**根本原因**: `.astrea-final-cta`の外側Groupに`align:full`が無く、Page Template自身の`is-layout-constrained`な`post-content`によりSection自体（背景含む）がTheme既定のcontentSize(720px)へ強制収縮されていた——CSSでの個別幅指定の問題ではなく、Block属性レベルの問題だった。

## Flow（§8 Wide Composition）

| 項目 | Before | After |
|---|---|---|
| Content width | 625px（固定、Viewport非依存） | 1825px(1920時)/1345px(1440時)/1271px(1366時)、Viewport追従 |
| Width/Viewport比率 | 0.326〜0.458 | 0.930〜0.951 |

**根本原因**: `.astrea-flow-steps`自体のCSS修正（`!important`追加）だけでは解決しなかった——一段上の`.astrea-home-flow`Group自体に`align:full`が無く、CTAと全く同じ原因（Page Templateのconstrained post-contentによるSection自体の強制収縮）で捕捉されていた。CTA/Flow両方とも、子要素CSSではなく親Group自身のBlock属性(`align:full`)を修正することで解決した。

## Hero Collision（§4、P0）

| Viewport | 要素 | Before margin | After margin |
|---|---|---|---|
| 1920×1200 | Primary見出し | +6.6px（危険なほど僅差） | +86px |
| 1440×900 | Primary見出し | **-96.6px（実クリップ）** | +145px |
| 1366×768 | Primary見出し | **-130.4px（実クリップ）** | +142px |
| 1024×768 | Primary見出し | **-129.6px（実クリップ）** | +57px |
| 1024×768 | Body Paragraph | +131px | +131px |
| 1024×768 | Buttons | -70px（同じ理由で実クリップ） | +102px |

**根本原因**: 016E-R1で導入した固定`max-width:760px`（Kicker/H1）・`460px`（Body）・`480px`（Buttons）はいずれも「Viewportが狭まってもText Plane自体の絶対px幅は比例して縮まない」ことを考慮しておらず、1440/1366/1024幅で実際に見出し・ボタンが対角線と衝突（負のMargin=物理的な重なり）していた。`clamp()`によるViewport追従のResponsive値へ変更し、全計測ポイントで安全マージンを確保した（自動検証Script: `hero-safety-verify.js`、全6構成×3要素=18ケース全てSAFE）。

## FAQ（§9、右半分の死角是正）

| 項目 | Before | After |
|---|---|---|
| Reading Column max-width | 720px | 960px |
| Wide Grid frame内の占有率（1920時） | 約45% | 約60% |

テキストをViewport端まで伸ばすことはせず、Heading(左寄せ)との一貫性を保つため左寄せのまま、適度に拡幅した。
