# Construction Order 016H — HOME Final Visual Integrity Audit & Repair 施工報告

## 0. Purpose

Construction 016Gの「FINAL RC READY WITH DEFERRED LOW」は技術監査としては有効だが、Owner実機確認によりHOMEに明確なVisual不具合（Section kickerと日本語H2の重なり、Section間の縦方向余白の不統一、VOICE→FLOW間の過大な空白）が見つかったため、016H として**竣工補修**を実施した。新機能・新CPT・新Dynamic Block・Coreデータ構造変更等はいずれも行っていない。

**AUTOMATED PASS != VISUAL PASS** — 016Gで確認したOverflow=0px/Theme Check PASS/PHPUnit PASSはいずれも引き続き有効だが、それらだけではVisual Acceptanceを証明できないというOrderの前提に従い、本Orderは全面的にFULL-PAGE screenshot目視検証を軸に進めた。

## 1. Owner-Observed Blockers の再現確認

### A. Heading collision（実機確認・全数調査）

HOME全9見出し（H2）のKicker（英字ラベル）とH2本文の視覚的距離を実機で全数確認した結果：

| 見出し | Kicker | 016G時点の状態 |
|---|---|---|
| SERVICE / 取扱業務 | SERVICE | **衝突**（Kickerと「取」がほぼ接触） |
| CASE / 対応事例 | CASE | 正常（明確な間隔あり） |
| RESULTS / 実績 | RESULTS | 正常（明確な間隔あり） |
| ABOUT / 代表者紹介 | ABOUT | **衝突**（Kickerと「代」がほぼ接触） |
| PRICE / 料金 | PRICE | **重度の衝突**（KickerテキストがH2グリフに直接重なり判読困難） |
| FAQ / よくあるご質問 | FAQ | **衝突**（Kickerと「よ」が重なる） |
| VOICE / お客様の声 | VOICE | 正常（明確な間隔あり） |
| FLOW / ご相談の流れ | FLOW | **衝突**（Kickerと「ご」が重なる） |

「たまたま重なっていない箇所がある」のではなく、明確に2グループに分かれていた。

### B/C. Vertical Rhythm / VOICE→FLOW過大な空白

実測の結果、VOICE quote一覧の終端からFLOW見出しの可視テキストまでの合計距離は**145px**（他のSection境界の96-128px前後という基準から見て明確に逸脱）であることを確認した。

## 2. Root Cause 調査

### 2.1 Heading Collision の根本原因

全Section見出しは共通の`h2:has(+ .wp-block-astrea-X-list)`セレクタ群を共有し、Kicker（`::before`、`position:absolute;top:0`）とH2本文の間隔は、**H2要素自身の`padding-top`の有無**によって決まる（`top:0`はH2のPadding Boxの最上端を基準とするため、H2自身にPadding-topが無ければKickerとH2本文の間に実質的な余白が生まれない）。

Construction 016E-R3の「Integrated Panel」技術（CASE/RESULTS/VOICEのH2に`padding-top:var(--wp--preset--spacing--large)`=56pxを適用、Heading自身に背景色と同じBackgroundを持たせるための施工）が、**副産物として**この3見出しにのみKicker用の十分なClearanceを与えていた。SERVICE/ABOUT/PRICE/FAQ/FLOWの5見出しは共通の基本Kickerシステム（016D-R2確立）からPadding-topを一度も付与されておらず、Marginのみに依存していたため、Kickerの絶対位置指定がH2本文と接触していた。

これは「たまたま3箇所だけ直っていた」のであり、8見出し全体を貫く統一ルールとして設計されたことは一度も無かったことが判明した。

### 2.2 VOICE→FLOW過大な空白の根本原因

FLOWは`align:full`+独自の`<section>`Wrapper（Construction 016E-R2で、`is-layout-constrained`親要素によるContentSize強制収縮を回避するために導入、`docs/research/2026-09-02_construction_016e_r2_spatial_system_report.md`参照）を持つ、HOME内で唯一の構造を持つSectionである。この`<section>`自身が`padding-top:var(--wp--preset--spacing--medium)`(32px)を独自に持つ一方、内部のH2見出しは他の全見出しと共通の`margin-top:var(--astrea-v3-section-gap)`(96px)をそのまま適用されていたため、

```
Section自身のpadding-top(32px) + H2のmargin-top(96px) = 128px
```

が**二重に積み上がって**いた。他の見出しは`entry-content`の直接の子として存在し、Section Wrapperという中間層を持たないため、単一の96pxのみで済んでいる。FLOWだけがこの構造上の違いにより、実質的に他のSectionの約1.5倍の「見出し前の空白」を持っていたことが根本原因であると判明した。

## 3. 実施した修正（いずれも共通Selector／既存Tokenへの単一修正、個別Section毎のパッチではない）

### 3.1 Heading Kicker Clearanceの統一

`h2:has(+ .wp-block-astrea-X-list)`等、全見出しが共有する基本Kickerルールに`padding-top:var(--wp--preset--spacing--medium)`（32px、既存トークンを再利用、新規Token追加なし）を追加。CASE/RESULTS/VOICEは既存の`padding-top:var(--wp--preset--spacing--large)`（56px）の個別Overrideを引き続き保持しており、この基本ルールで上書きされない（Source-Order／同一詳細度のため後方のOverrideがそのまま優先される、CSSの通常の挙動）。

これにより：
- 「Compact」グループ（SERVICE/ABOUT/PRICE/FAQ/FLOW）: Kicker Clearance 32px
- 「Panel」グループ（CASE/RESULTS/VOICE、Integrated Panel技術使用）: Kicker Clearance 56px（無変更）

という、意図的な2段階のRhythmとして整理された（Order §5が求める「Large/Medium/Compact」の少数の意図的なRhythmに合致する）。

### 3.2 FLOW見出しの二重積み上げ解消

`h2.astrea-flow-heading`（既存の固有Selector、Kickerシステムで元々「FLOW」という文言を出し分けるために使われている）に対し、`margin-top:var(--wp--preset--spacing--small)`（16px、既存トークン）のOverrideを追加。Section自身の`padding-top`（32px）と統合して、他のSectionと近い合計値になるよう調整した。`margin-top:-XXpx`のような負のMarginやHardcoded Heightは一切使用していない。

## 4. Before/After 実測

| 項目 | Before | After | Verdict |
|---|---|---|---|
| SERVICE kicker→H2 padding-top | 0px（衝突） | 32px | PASS |
| CASE kicker→H2 padding-top | 56px | 56px（無変更） | PASS |
| RESULTS kicker→H2 padding-top | 56px | 56px（無変更） | PASS |
| ABOUT kicker→H2 padding-top | 0px（衝突） | 32px | PASS |
| PRICE kicker→H2 padding-top | 0px（重度衝突） | 32px | PASS |
| FAQ kicker→H2 padding-top | 0px（衝突） | 32px | PASS |
| VOICE kicker→H2 padding-top | 56px | 56px（無変更） | PASS |
| FLOW kicker→H2 padding-top | 0px（衝突） | 32px | PASS |

Section Boundary（1440px、prev content end → next kicker start）:

| Boundary | Gap |
|---|---|
| Hero → SERVICE | 96px |
| SERVICE-list → CASE | 96px |
| CASE-list → RESULTS | 96px |
| RESULTS-list → ABOUT | 96px |
| Professional → PRICE | 96px |
| PRICE-list → CTA | 24px（意図的、密結合） |
| CTA → FAQ | 96px |
| FAQ-wrapper → VOICE | 96px |
| **VOICE-list → FLOW（見出しテキストまでの合計）** | **Before 145px → After 65px** |

VOICE→FLOWの合計距離は他のSection境界（96-128px前後）に近い範囲へ収まり、「何も表示されていないのでは」と感じさせる過大な空白ではなくなったことを実機Screenshotで確認した。

## 5. Full-Page Visual Inspection

HOMEをHeaderからFooterまでFULL-PAGE Screenshotで7幅すべて確認した：1920 / 1440 / 1366 / 1024 / 768 / 375 / 320。全幅でKicker衝突ゼロ、Horizontal Overflow 0pxを確認（`docs/research/screenshots/016h/after-full-home-*.png`、Before/After比較用に`before-full-home-*.png`も保存）。

Section境界の接続を跨ぐBoundary Screenshot 01〜09（`docs/research/screenshots/016h/boundary-*.png`）を取得し、Hero→Services→Case→Results→Professional→Price→CTA→FAQ→Voice→Flow→Footerが分断されず連続したページとして成立していることを目視確認した。

## 6. 320px / 2行H2ケースの安全性確認

320px幅ではFLOW見出し「ご相談の流れ」が2行に折り返る（`docs/research/screenshots/016h/w320-flow-full.png`）。Kicker・H2本文・Trailing Ruleいずれも衝突せず、Trailing Ruleは2行目の下に自然に折り返して表示されることを確認した（`display:flex;flex-wrap:wrap`による既存の設計どおりの挙動）。1行H2/2行H2いずれのケースでも安全であることを実機確認した。

## 7. Three Variations

Trust（既定）/ Natural / Modernそれぞれで1440px・375pxのFULL-PAGE Screenshotを取得し、特に最も重度だったPRICE見出しのCloseupを3 Variationすべてで確認した（`docs/research/screenshots/016h/{trust,natural,modern}-{1440,375}.png`、`modern-price-crop.png`等）。Variation固有のFont Metrics差によるKicker衝突の再発は無いことを確認した。Trustへ復元し、`wp_global_styles`（post ID 825）の内容が復元前と一致することをdiffで確認済み。

## 8. Regression

- PHPUnit: 398/398（本Orderで変更したのは`theme/theme.json`のCSSのみ、PHPファイル変更0件のため398件は016G終了時点と同数）。既知の3件のPre-existingエラー（`wp-phpunit`のattachment factory起因）は無変更。
- PHPCS: 対象PHPファイル0件のため実行対象なし。
- Horizontal Overflow: 既存の7幅チェックスクリプトを再実行、0px（全パターン）。
- Core OFF→ON: HOME・HTTP 200・Fatal/Warning/Notice無しを確認、再有効化後も正常表示。
- H1数: 1（無変更）。Skip Link（`#wp--skip-link--target`）とその実Target実装の存在を確認（無変更、既存実装）。
- 承認済み内部ページのFreeze確認: Professional Single / Service Archive / CASE Single / FAQ Archive / VOICE Archive / Search / 404 / Office / Price / Contactの計10ページを1440pxで実機確認し、いずれも本Orderの変更（HOME固有の`h2:has(+ .wp-block-astrea-X-list)`Selector・`h2.astrea-flow-heading`Selector）の影響を受けていないことを確認した（内部ページは別の`.astrea-archive-header h1`/`main.alignfull>h1`という異なるKicker機構を使用しており、Selectorの対象範囲が完全に分離されている）。

## 9. 変更ファイル

- `theme/theme.json`（CSSのみ、2箇所）:
  1. 共有Kicker基本ルールへ`padding-top:var(--wp--preset--spacing--medium)`を追加
  2. `h2.astrea-flow-heading`へ`margin-top:var(--wp--preset--spacing--small) !important`のOverrideを追加

他のファイルは変更していない。新規Token・新規Selector・Hardcoded Height・負のMargin・Fixture専用CSS・JS補正のいずれも使用していない。

## 10. Known Remaining Findings

現時点で追加のVisual不具合は確認されていない。Section Rhythmは「Compact(32px Kicker Clearance)」「Panel(56px Kicker Clearance)」の2段階に整理され、FLOWの二重積み上げ問題も解消された。今後同種の問題（新しいSectionを追加する際、Kickerシステムの基本ルールにPadding-topが含まれていることを忘れる、または独自の`<section>`Wrapperを追加する際にMarginとPaddingの二重計上に気づかない）を防ぐため、`docs/specifications/07_astrea_visual_v3_design_direction.md`への追記を検討したが、016Hは「Visual補修」であり新たな設計原則の追加はOrderの主眼ではないため、次回のConstruction Orderで正式に検討することとし、今回は見送った。

## 11. Locked/Prohibited遵守確認

Hero Geometry・Services 3-column構造・CASE 3-column Visual・RESULTS Dark Band・Professional構成・Price Content構造・FAQ Content・Voice Content・Flow Content・CTA・Footer・Headerのいずれも再設計していない。新CPT・新Dynamic Block・新管理画面・Coreデータ構造変更・SEO仕様変更・Contact仕様変更・Setup仕様変更・PRO機能・Demo専用CSS・Fixture専用CSS・特定文言依存CSS・JavaScript補正・Hardcoded Height・空のSpacer Block・場当たり的なNegative Marginのいずれも使用していない。

## 12. 測定値・Commit

- Start: 2026-09-04 03:44 JST
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER HOME VISUAL ACCEPTANCE**

016H自身は「Release Ready」を宣言しない。Owner がFULL-PAGE Screenshotを確認し明示的に承認した後にのみ、次工程（016I / Release Order）へ進む。Construction 016G時点のTechnical Readiness（Theme Check PASS、Security PASS、Clean Install確認等）はいずれも保持されているが、Visual Release Gateは再びOPEN状態であり、Version変更・Tag・GitHub Release・Deploy・WordPress.org Submissionのいずれも実施していない。
