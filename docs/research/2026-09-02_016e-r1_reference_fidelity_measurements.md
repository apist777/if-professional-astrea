# Construction 016E-R1 — Reference vs Actual(016E) Measurement Audit

Reference: `docs/research/references/visual-v3-owner-reference.png`（872×1804px）。016E時点のActual実測値と、修正後実測値を並記する。計測はすべて1920px viewport、Playwright実機（`getBoundingClientRect`/`getComputedStyle`）またはCanvas Pixel Sampling（Reference画像自体の実測、016D-R2/016Eから継続利用の手法）による。

## HERO

| 項目 | Reference | Actual(016E, before) | Actual(016E-R1, after) | 判定 |
|---|---|---|---|---|
| H1見出し行数 | 2行 | 5〜6行 | **3行** | Reference ±1行の目標を達成 |
| Text Plane box width | — | 58% | 68% | Priority 1(Text Plane width)を優先して拡大 |
| Kicker/H1 max-width | — | 420px | 760px | |
| Body paragraph max-width | — | 420px(共有) | 460px(専用) | |
| Buttons max-width | — | 480px | 480px(維持、margin計算のバグを別途修正) | |
| Diagonal top/bottom(絶対%) | 56%/42% | 56%/42%(不変) | 56%/42%(不変) | Diagonal自体はReference実測値のまま変更せず |
| 写真領域の平均輝度(RGB) | (130,147,159)≈輝度143.3 | (111,119,127)≈輝度117.6 | (143,152,158)≈輝度150.0 | Reference輝度に収束 |
| dimRatio(Fixture Cover Block) | — | 45% | 30% | |

**実装中に発見した実バグ**: WordPress Core自身の`.is-layout-constrained > *`ルールが`margin-left/right:auto`を`!important`で設定しており、Text Plane内の各要素（Kicker/H1/Paragraph/Buttons）を意図せず中央寄せしていた。Theme側の`margin:0`リセットに`!important`が無かったため負けており、Content列が見た目より右へシフトし対角線と衝突していた。全リセットへ`!important`を追加して解消。

## PROFESSIONAL

| 項目 | Reference(概算) | Actual(016E, before) | Actual(016E-R1, after) |
|---|---|---|---|
| Row height | — | 515.4px | **454.6px**（-12%） |
| Section total height(Heading含む) | — | 649.4px | **588.5px**（-9%） |
| Photo aspect ratio | ≈1.7:1 | ≈1.5:1 | **1.7:1**(aspect-ratio指定に変更) |
| Name font-size | — | clamp(2rem,3.4vw,2.7rem) | clamp(1.7rem,2.6vw,2.2rem) |
| Bio font-size | — | `--font-size--large`(最大26px) | `--font-size--medium`(16-18px) |
| Bio line-height | — | 1.8 | 1.6 |

## PRICE

| 項目 | Reference | Actual(016E, before) | Actual(016E-R1, after) |
|---|---|---|---|
| 税別注記の表示 | Priceの下に独立した行 | **Priceと同じProperty内で改行され「税」「別）」で分断** | Priceと視覚的に分離した独立行 |
| データ構造 | — | `astrea_price_amount`に金額+税別注記が1つの文字列として混在 | `astrea_price_amount`=金額のみ、`astrea_price_notes`=税別注記（既存の2つの別Fieldへ分離、新規Field追加なし） |

**根本原因**: `astrea_price_notes`という金額と独立した既存Core Fieldが元々存在していたが、Fixtureデータ側が誤って税別注記を`astrea_price_amount`へ結合していたため、CSSの`.wp-block-astrea-price-item-notes`スタイル（小さいFont、独立行）が一切適用されていなかった。Core側の新規Field追加は不要、既存Fixtureデータの補正のみで解決（Order §6の「Demo fixture copy調整」に該当、Theme/Core側の変更ではない）。

## FINAL CTA

| 項目 | Reference(概算、1920相当) | Actual(016E, before) | Actual(016E-R1, after) |
|---|---|---|---|
| Section height | ≈216px | 365.4px | **229.7px** |
| 見出しFont-size | ≈33px | 36.8px | 27.2px |
| 縦Padding | ≈54px(上下) | x-large×1.15(≈110px) | large(56px) |

## CASE

Order §8指示によりLOCK DIRECTION、変更なし。Scenario A(全3件写真)/B(Mixed)/C(2件無写真)/D(全0件)の4パターンを実機確認、いずれもGrid崩れ・Fatal無し・偽Placeholder無しを確認、最終的にOwner Baseline(Scenario A)へ復元済み。

## RESULTS / FAQ / VOICE / FLOW / Core / CPT / Contact / Setup / SEO / GA4

Order §1のScope Freezeに従い変更なし。
