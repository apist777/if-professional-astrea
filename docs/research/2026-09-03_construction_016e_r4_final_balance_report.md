# Construction Order 016E-R4 実装報告 — HOME Visual v3 Final Balance & Owner Acceptance Candidate

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-02 22:55 JST
- **End**: 2026-09-03 06:40 JST（見込み、git push/CI確認後に確定）

## 0. 総括

「もっと良くする」ための全面改修ではなく、局所施工（Finding A/B/Cの3点のみ）としてOwner Acceptance Candidateまで持っていくことが目的だった。Locked Areas（Header/Hero/RESULTS/Price→CTA/FAQ/VOICE/FLOW/Footer）は一切変更していない。

## 1. Finding A — Professional Balance（最優先）

**問題**: Photo(46%)+Body(max-width:520px固定)の構成により、Bodyの右側に大きな未使用の白地が残り、「大きな空白の中に小さなプロフィールがある」ように見えていた。

**対応**:
1. Body列の`max-width`を520px→640pxへ拡大。
2. Name/Qualification/Bio Typography（016E-R1で密度優先のため縮小していたもの）を一段階引き上げ: Name `clamp(1.7rem,2.6vw,2.2rem)`→`clamp(2rem,3vw,2.6rem)`、Qualification `--small`→`--medium`、Bio `--medium`→`--large`(line-height 1.6→1.7)。既存情報のみ使用、新規データ項目は追加していない。

**根本的な学び**: 単にコンテナのmax-widthを広げるだけでは、短い左寄せテキストは「container幅を使い切らない」ため視覚的な空白は解消しない。実際に効果があったのは、既存テキスト自体のTypographyを引き上げ視覚重量を増すことだった。1440/1366（Primary Width）で明確なバランス改善を確認。1920では引き続きある程度の余白が残るが、これはOrder自身が禁じる「固定50%を目的にする」ことを避け、Wide Viewportでの自然な余白として許容範囲と判断した（正直な報告、§9参照）。

**No-photo Fallback**: 画像なし時、画像前提の空白カラムは残らないことを実機確認済み（既存の`.no-photo`用CSSで対応済み、変更なし）。

## 2. Finding B — CASE Internal Density

**問題**: 016E-R3のPanel統合自体は成立していたが、Panel内部のHeading→Cards間の距離（H2 padding-bottom:medium(32px) + List padding-top相当:x-large(96px) = 128px）がまだ緩く感じられた。

**対応**: CASE専用のOverride（RESULTS/VOICEは本Order Locked、変更せず）を追加。
- H2の`padding-bottom`: medium(32px)→small(16px)
- List自身の`padding-block`(top/bottom共通96px)を`padding-top:large(56px)`/`padding-bottom:x-large(96px)`に分離、Topのみ縮小

合計Gap: 128px→72px（-44%）。Panel全体の外部Spacing（Services→CASE、CASE→Results）、3-column構造、Card Geometry、Surface Colorはすべて無変更。既存Spatial Token（small/large/x-large）のみ使用、新規Magic Numberは追加していない。

## 3. Finding C — Service Icon/Number Collision

**問題**: 「01/02/03」の連番が`position:absolute;left:72px`という固定値で配置されていたが、Item 2/3は`:not(:first-child)`セレクタにより追加で`padding-left:medium(32px)`（区切り線用）を持つため、実際のIcon右端（32+52=84px）が番号の開始位置（72px）より右にあり、Icon 2/3で明確な物理的重なりが発生していた（Item 1は追加Paddingが無いため問題なし——これがItem間で症状が非対称に見えていた理由）。

**対応**: `.wp-block-astrea-service-item:not(:first-child)::after{left:calc(var(--wp--preset--spacing--medium) + 72px)}`を追加し、Item 2/3の追加Paddingを番号位置の計算に反映。実測でItem 1/2/3すべて約19-20pxの一貫した安全マージンを確認（自動計測: iconRight vs afterLeftの実座標比較）。320pxでも衝突が無いことをZoom Screenshotで確認。既存3種のIconはそのまま維持、新Icon Systemの開発は行っていない。

## 4. Locked Areas確認

Header/Hero/RESULTS/Price→CTA/FAQ/VOICE/FLOW/Footerについて、margin/padding/typography/width/background/markup/responsiveのいずれも変更していない。変更ファイルは`theme/theme.json`のCSSのみで、Professional/CASE/Service関連のSelectorに限定されている。

## 5. Responsive結果

| 幅 | Horizontal Overflow |
|---|---|
| 1920 | 0px |
| 1440 | 0px |
| 1366 | 0px |
| 1024 | 0px |
| 768 | 0px |
| 375 | 0px |
| 320 | 0px |

## 6. Variation結果

Trust/Natural/Modern 3件とも、Professional Balance改善・CASE内部密度調整・Service Icon/Number修正が正常動作することを実機確認、Trustへ復元済み。

## 7. Core OFF/ON結果

Core OFF→ON、HTTP 200・Fatal無しを確認。

## 8. Test結果

- PHPUnit: 397/397 Pass
- PHPCS: 本Orderでは変更PHPファイル無し（theme.jsonのCSSのみ変更）
- Theme Check: 本Orderでは再実行せず（Enqueue/Text Domain等の変更なし、前回Baseline継続採用）
- 長い事務所名（24文字超）: Header/Hero双方Overflow無し、7幅すべて確認
- CASE Scenario A（Owner Baseline）: 開始時・終了時とも維持を確認
- Professional Photo/No-photo: 両方正常動作を確認（画像削除→確認→復元）

## 9. 正直な報告（未解決の視覚的所感）

- Professionalの1920px幅での右側余白は、640px拡幅+Typography引き上げ後も1440/1366ほど引き締まっては見えない。これはOrder §2が明示的に禁じる「固定50%を目的にする」ことや、Locked Areaではない範囲でのさらなる構造変更（例: ROW自体のmax-width導入）を避けた結果であり、Wide Viewportでの自然な余白として許容範囲と判断した。Owner確認時にさらなる調整が必要と判断された場合は、次Orderでの対応を推奨する。
- CASE→Resultsの外部境界（234px、016E-R3で既に確認済み）は本Orderでは変更していない（Order §1の適用外、RESULTS Locked）。

## 10. HOME全景での自己評価（§10）

- Heroだけ豪華で下が弱く見えるか: 016E-R1〜R3の一連の施工により、Hero/Service/CASE/Results/Professional/Priceが同じEditorial言語で統一されており、極端な段差は見られない。
- Service→CASE→Resultsの密度: Service Icon/Number修正・CASE内部密度調整により、より自然な連続性を確認。
- Results→Professionalの急な間延び: 016E-R3で解消済み（96pxの一貫したSection Gap）、本Orderでは変更なし。
- Professional→Priceの接続: 016E-R3の統一Token（40px heading-gap）のまま、Professional Body拡幅・Typography変更は接続自体に影響しないことを確認。
- Price→CTA→FAQ→VOICE→FLOW→Footerの後半リズム: Locked、無変更。
- ページ全体の一貫性: 実機Full Page Screenshot（1920/1440/1366/1024/375/320）で確認、同じDesign Systemで作られたページとして成立していると判断。

## 11. Screenshot Evidence

`docs/research/screenshots/016e-r4/`に10枚（Desktop Primary 2幅+Responsive Safety 4幅+Before/After比較4枚(Professional/CASE/Service/Full Home)）。

## 12. 変更ファイル

- `theme/theme.json`（Professional Body max-width・Typography、CASE内部Spacing、Service Icon/Number位置のみ）

Fixtureデータ（Professional No-photo検証時の一時的な_thumbnail_id削除・復元、Before/After比較用のCSS一時ロールバック）はすべて事前Backup→検証→Owner Baseline復元→復元確認の手順を踏んだ。Git管理外のWordPress DB状態であり、リポジトリのDiffには含まれない。

## 13. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/final v1.0.0/Construction 016Fへの自動進行は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
