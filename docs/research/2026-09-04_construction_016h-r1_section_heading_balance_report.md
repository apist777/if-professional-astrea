# Construction Order 016H-R1 — HOME Section Heading Balance & Rhythm Calibration 施工報告

## 0. Purpose

Construction 016HでKicker/H2の衝突は解消されたが、Owner Visual ReviewによりHOME各SectionのSection Heading（Kicker→H2、H2→Content、前Section終端→次Heading）の視覚的間隔・密度が「統一されたデザインシステム」に見えないという指摘を受けた。本Orderは「衝突修正」ではなく、HOME全体のSection Headingを一つのDesign Systemとして再調整するCalibrationである。新機能開発ではない。

## 1. Phase 1 — 実測（1920px Desktop、CSS変更前）

CSS変更前に、`getBoundingClientRect()`により8 Section全ての実測を行った。

| Section | Kicker | padding-top（Kicker→H2内部余白） | H2 Box Height | Prev→Section Gap | Wrapper特性 |
|---|---|---|---|---|---|
| SERVICE | SERVICE | 32px | 82px | 96px | 通常（entry-content直下） |
| CASE | CASE | **56px** | 98px | 96px | Integrated Panel（padding-bottom:small override） |
| RESULTS | RESULTS | **56px** | 138px | 96px | Integrated Panel（dark、results-list自身がpadding-block:x-large） |
| ABOUT | ABOUT | 32px | 82px | 96px | 通常 |
| PRICE | PRICE | 32px | 82px | 96px | 通常 |
| FAQ | FAQ | 32px | 82px | 96px | 特殊2カラムレイアウト |
| VOICE | VOICE | **56px** | 138px | 96px | Integrated Panel |
| FLOW | FLOW | 32px | 82px | 24px | 独自`<section>`Wrapper（016H既修正） |

実測により、CASE/RESULTS/VOICEの3 SectionのみKicker→H2内部余白が56px、他5 Sectionは32pxという、**明確な2グループの不一致**が存在することを確認した（1920px幅のクロップScreenshotでCASE/SERVICE等を直接比較し、視覚的にも56pxグループの方が明らかに間延びして見えることを確認）。

## 2. Root Cause

56pxという値は、016H調査で判明した「016E-R3のIntegrated Panel技術がCASE/RESULTS/VOICEのH2にのみ`padding-top:var(--wp--preset--spacing--large)`を副産物的に付与していた」ことに由来する、Kicker Clearanceを目的として選ばれた値ではない（016H Reportでも明記済み）。016Hでは「衝突を解消する」ことのみが目的だったため、この既存の56pxにはあえて手を付けず、他5 Sectionにのみ32pxを追加する形で衝突を解消した。その結果、衝突は無くなったが、8 Section全体を通した「同じデザインシステムに見える」という一段上のRequirementは未達のまま残っていた。

## 3. 実施した修正

**単一の変更**：CASE/RESULTS/VOICEが共有する`h2:has(+ .wp-block-astrea-case-list),h2:has(+ .wp-block-astrea-results-list),h2:has(+ .wp-block-astrea-voice-list)`ルールの`padding-top`を、`var(--wp--preset--spacing--large)`（56px）から`var(--wp--preset--spacing--medium)`（32px、他5 Sectionと同一の既存Token）へ変更した。

新規Token・新規Selector・個別Section毎のMagic Number・Negative Margin・Fixed Heightはいずれも使用していない。CASE固有の`padding-bottom:small !important`（Case Cardsとの間隔用、Kicker Clearanceとは無関係）は無変更のまま維持した。

これにより：
- 8 Section全てのKicker→H2内部余白が**32px（medium Token）に統一**された。
- H2 Box自身の高さの由来も整理された：CASE=98px（padding-top32+line-height50+padding-bottom16）、RESULTS/VOICE=114px（32+50+32）、他5 Section=82px（32+50+0）——この差はKicker Clearanceの不一致ではなく、H2下端とその後のContentとの間隔（Panel継続 vs 通常）という**別の意味を持つ差**として整理された。
- Section間の大きなRhythm（96px、または密結合の24px）はいずれも無変更。

## 4. Before/After 視覚比較

`docs/research/screenshots/016h-r1/before-after-section-heading-comparison.png`（Owner一目確認用の統合比較資料）で、CASE・RESULTS・ABOUT・VOICEの4 SectionがBefore（間延びしたKicker→H2余白）からAfter（他Sectionと揃った余白）へ変化したことを、SERVICE・PRICE・FAQ・FLOW（016Hで既に正しい状態、無変更）と並べて確認できる。

個別のBefore/After（1920px、各Sectionのクロップ）: `docs/research/screenshots/016h-r1/before-1920-*.png`（Before）、`1920-*.png`（After）。

## 5. 320px / 2行H2ケースの安全性再確認

Kicker Clearanceを縮小したCASE/RESULTS/VOICEについて、320px幅で再度Collision Riskを確認した（`docs/research/screenshots/016h-r1/w320-{case,results,voice}.png`）。いずれも衝突無く、明確な間隔を保っていることを確認した。

## 6. Full-Page Visual Inspection

HOMEを7幅（1920/1440/1366/1024/768/375/320）でFull-Page Screenshot確認した（`docs/research/screenshots/016h-r1/06〜12-home-full-*.png`）。全幅でCollision=0、Horizontal Overflow=0pxを確認。1440px全景では、Kicker→H2の視覚的な「呼吸」がSERVICEからFLOWまで一貫しており、016H時点で見られたCASE/RESULTS/VOICEの間延びが解消されていることを確認した。

## 7. Style Variations

Trust（既定）/ Natural / Modernの3 Variationで1440px・375pxのFull-Page Screenshotを取得し、特に本Orderで変更したCASE・PRICEのCloseupを個別確認した（`docs/research/screenshots/016h-r1/{natural,modern}-{1440,375}.png`、`{natural,modern}-{case,price}-crop.png`）。Variation固有のFont Metrics差による衝突再発は無いことを確認した。Trustへ復元し、`wp_global_styles`（post ID 825）の内容が復元前と一致することをdiffで確認済み。

## 8. Internal Page Regression

以下10ページを1440pxでSpot Check（`docs/research/screenshots/016h-r1/freeze-*.png`）：Office / Service Archive+Single / Professional Archive+Single / CASE Archive+Single / FAQ Archive / Contact / 404。いずれも本Orderの変更（HOME専用の`h2:has(+ .wp-block-astrea-{case,results,voice}-list)`セレクタ、内部ページの`.astrea-archive-header h1`/`main.alignfull>h1`とは完全に異なるSelector）の影響を受けていないことを確認した。

## 9. Automated Tests

- PHPUnit: 398/398（本Orderで変更したのは`theme/theme.json`のCSS1箇所のみ、PHPファイル変更0件）。既知の3件のPre-existingエラー（`wp-phpunit`のattachment factory起因）は無変更。
- PHPCS: 対象PHPファイル0件のため実行対象なし。
- Theme Check（公式Plugin、検証専用に一時インストール→検証後アンインストール済み）: **"ASTREA passed the tests"**、5542 tests、REQUIRED 0/WARNING 0/INFO 1（無変更）。
- Horizontal Overflow: 既存の7幅×13ページチェックスクリプトを再実行、0px（全パターン）。
- Core OFF→ON: HOME・HTTP 200・Fatal/Warning/Notice無しを確認、再有効化後も正常表示。

## 10. Locked/Prohibited遵守確認

Hero/Header/Footer/Services構造/CASE構造/RESULTS Dark Band/Professional構成/Price Content/FAQ Content/Voice Content/Flow Content/CTAのいずれも再設計していない。HOME以外の内部ページ・Core・Content/Data/Fixture・新機能・Release作業のいずれにも触れていない。8 Sectionへの個別Magic Number追加・Negative Margin・場当たり的なViewport別px補正・Fixed Height・Absolute Positioningによる配置変更・Text画像化はいずれも行っていない。

## 11. 変更ファイル

`theme/theme.json`（CSSのみ、1箇所）：CASE/RESULTS/VOICEが共有するSelectorの`padding-top`を`large`（56px）から`medium`（32px、既存Token）へ変更。他のファイルは変更していない。

## 12. Known Remaining Findings

現時点で追加のVisual不具合は確認されていない。8 Section全てが単一のKicker Clearance Token（medium=32px）を共有する状態となり、「同じデザインシステムから作られた見出し」という本Orderの受入基準を満たしていると判断する。

## 13. 測定値・Commit

- Start: 2026-09-04 09:26 JST（Order着手、Docker Desktop起動待ちの中断を含む）
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER SECTION HEADING ACCEPTANCE**

016H-R1自身はRelease Readyを宣言しない。016Iへは進まない。tag・GitHub Release・deployのいずれも実施していない。OwnerがFull-Page Screenshot・比較資料を確認し明示的に承認した後にのみ、次工程を決定する。
