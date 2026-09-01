# Construction 016E — Reference vs Actual Measurement Audit

Reference: `docs/research/references/visual-v3-owner-reference.png`（872×1804px）。Reference上の座標は画像内実測px、% は872px幅に対する比率として記録し、Target(1920px)はその比率を1920pxへ適用した値（実装時のCSS `%`/`clamp()`/`vw`の目安値であり、固定pxへハードコードする意味ではない）。Actual Beforeは016D-R2完了時点（commit `f86a708`）でのPlaywright実測（1920px viewport）。

計測手法: PIL/ImageMagickが環境に存在しないため、Playwright ChromiumのCanvas API（`drawImage`→`getImageData`）でReference画像へ50px間隔のグリッド線を焼き込み、実測画像を目視読み取りした（`docs/research`配下には保存せず、作業用Scratchpadのみに生成——最終成果物はこの計測表とスクリーンショット）。Actual側は`getBoundingClientRect()`/`getComputedStyle()`による実DOM計測。

## HEADER

| 要素 | Reference (px/872) | Reference % | Actual Before (1920px) | Target (1920px) | Delta |
|---|---|---|---|---|---|
| Logo left edge | 32 | 3.7% | 240px | ~71px | 現状は共有Wide Grid(12.5%)に揃えられておりReferenceよりかなり内側。HeaderはContent Sectionと異なる、より狭いInsetを使っている。 |
| Nav start X | 345 | 39.6% | 1021px | ~760px | 同上 |
| Contact CTA right edge | 850 | 97.5% | 1680px(=1920-240) | ~1871px(右余白49px) | 同上、右側も広げる必要 |
| Header height | ~57 | — | 96px | 現状維持（大きな乖離なし、Composition上大きな問題ではない） | — |

**結論**: HeaderはShared Wide Gridの12.5%Insetではなく、より狭い（左右とも約3-4%程度）独自のInsetを使う。ロゴ・Nav・CTAがViewport端により近づく。

## HERO

| 要素 | Reference | Reference % | Actual Before | Target | Delta |
|---|---|---|---|---|---|
| Text column right edge | x≈400 | 45.9% | Text Plane width 845px(44.0%) | ほぼ一致 | 既に近い、大きな変更不要 |
| 見出し(H1装飾/Kicker)開始 | x≈100 | 11.5% | 左Padding分(約Grid Gutter) | ほぼ一致 | — |
| Diagonal boundary top (y≈65) | x≈660 | 75.7% | (存在しない、垂直Split) | ~1454px | **新規**: 対角線境界導入 |
| Diagonal boundary bottom (y≈450) | x≈355 | 40.7% | (存在しない) | ~782px | **新規**: 対角線境界導入 |
| Headline font-size(視覚上の大きさ) | 相対的に非常に大きい | — | 64px(clamp上限到達) | ~76-84px程度まで拡大 | 見出しの迫力を強化 |
| 左端の縦書き装飾文字 | 存在("ASTREA PROFESSIONAL OFFICE"相当) | — | 存在しない | 追加(aria-hidden) | **新規** |
| Header/Hero一体感 | Header背景=白、Heroとシームレスに連続 | — | Header独立(border-bottom有) | 大きな乖離なし、優先度低 | — |

**結論**: Heroが本Orderで最大のFidelity Gap。テキスト列の位置・幅は既に近いが、(1) 直線分割→対角線分割、(2) 見出しTypographyの拡大、(3) 左端の縦書き装飾文字、の3点が主な再構築対象。

## SERVICE（Minor Pass）

| 要素 | Reference | Reference % | Actual Before | Target |
|---|---|---|---|---|
| Section left/right | 100/805 | 11.5%/92.3% | Shared Grid(12.5%) | ほぼ一致、変更不要 |
| 01/02/03番号とIconの関係 | 番号とIconが同じ行 | — | 要確認・近ければ微調整のみ | Minor |
| 列間の縦罫線 | あり | — | 016D確立、あり | 維持 |

## CASE

| 要素 | Reference | Reference % | Actual Before | Target |
|---|---|---|---|---|
| Section left/right | 100/805 | 11.5%/92.3% | Shared Grid(12.5%) | ほぼ一致 |
| 番号バッジ位置 | 写真の上に重ねたNavy Badge(左上) | — | 写真の外側・上に大きなGold数字 | **変更**: 写真内オーバーレイBadgeへ |
| カード画像アスペクト比 | 横長(約2:1〜2.2:1) | — | aspect-ratio:4/3(より正方形寄り) | **変更**: より横長に |
| カテゴリラベルの表示 | 写真下に無し(Titleへ直結) | — | 写真下にカテゴリラベル文字あり | **変更**: 非表示化 |
| 3列とも写真あり | Yes(Model House Reference) | — | 016D-R2でScenario A(全3件Photo)へ設定済み | 維持(既に一致) |

## PROFESSIONAL（Major Reconstruction）

| 要素 | Reference | Reference % | Actual Before | Target | Delta |
|---|---|---|---|---|---|
| Photo left edge | 0 | 0%(Viewport端までFull-bleed) | 240px(Grid Inset) | 0px | **重大**: 写真がGridの外、Viewport端までFull-bleedすべき |
| Photo width(Viewport比) | 405/872 | 46.4% | 662/1920=34.5%(Grid内46%相当) | Viewport全体の約46% (~883px) | **重大**: Grid内%ではなくViewport全体に対する%で再計算 |
| Heading行 | 写真の上、独立した行 | — | 既存Kicker H2で対応済み | ほぼ一致 |
| 名前末尾のGold罫線 | あり | — | 016D-R2で追加済み | 維持 |

## PRICE（Major Hierarchy Pass）

| 要素 | Reference | Reference % | Actual Before | Target |
|---|---|---|---|---|
| Section left/right | 100/805 | 11.5%/92.3% | Shared Grid(12.5%) | ほぼ一致 |
| Icon視覚サイズ | 中程度、Labelと同じ行 | — | 26px | 32px程度へ拡大 |
| 価格文字サイズ(視覚上の支配度) | Label/Iconよりはっきり大きい | — | 32px(clamp上限) | 40-44px程度へ拡大、Label/Iconとの対比を強化 |
| 列間の縦罫線 | あり(薄) | — | 016D-R2で復元済み | 維持 |
| Group/Categoryラベル | 非表示 | — | 016D-R2で非表示化済み | 維持(既に一致) |

## FINAL CTA

| 要素 | Reference | Reference % | Actual Before | Target |
|---|---|---|---|---|
| 背景帯の幅 | Full-bleed(0-872, 100%) | 100% | Full-bleed(align:full) | 既に一致 |
| 内側コンテンツ中央寄せ幅 | 見出し+ボタン2つ分、中央寄せ | — | contentSize:600px中央寄せ | 概ね一致、Padding量を強化 |
| 縦Padding(視覚的な帯の強さ) | 比較的大きい | — | x-large | 若干強化を検討 |

## 実装優先順位（Order §22準拠）

1. **Hero**（対角線Geometry、Typography拡大、縦書き装飾）— 最優先
2. **Professional**（写真Full-bleed化）
3. **Price**（Icon/価格の視覚的支配度強化）
4. **CASE**（写真内番号Badge、アスペクト比、カテゴリラベル非表示）
5. **Header**（Inset縮小）
6. **Final CTA**（Padding強化）
7. **Service**（Minor調整のみ、測定上大きな乖離なし）
