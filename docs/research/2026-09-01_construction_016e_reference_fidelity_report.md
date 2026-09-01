# Construction Order 016E 実装報告 — Owner Reference Fidelity Final Pass

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-01 16:40 JST
- **End**: 2026-09-01 23:55 JST
- **Commit**: `8980591`（CI green確認済み: PHPUnit / Theme-Core independence smoke test / PHP syntax+Coding Standards、全Job成功）
- **Measurement Audit**: `docs/research/2026-09-01_016e_reference_measurements.md`（本Order §2の要求通り、CSS変更前に作成・使用）
- **Owner Directive**: 「Referenceは模範であり、翻訳・再解釈の対象ではない」——本Orderでは既存実装の合理性・従来仕様との整合性を理由にReferenceから離れることを避け、Referenceに写っている箇所は一方向でReference→Implementationへ合わせた。

## 0. アプローチ

016D-R2完了時点でOwnerから「まだ正本とのDesign Fidelity差が大きい、特にHero/CASE/Professional/Price/Final CTAはComposition/Geometry/Visual Hierarchy自体に差がある」との評価を受け、本OrderはCSS変更前にReference実測（§2 Measurement Audit）を必須化した上で、Composition優先（Order §22の優先順位: Composition > Width/position > Image proportion > Typography hierarchy > Spacing > Color/border details）で再構築した。

PIL/ImageMagickが引き続き環境に存在しないため、016D-R2で確立したPlaywright Canvas Pixel Samplingに加え、50px間隔のグリッド線をReference画像へ焼き込んだ上での目視読み取りを併用し、対角線境界のような単純な閾値検出では拾えない構図情報を実測した。

## 1. HERO（最優先・Major Reconstruction）

**Reference geometry**: 左Text Plane（白背景）と右Photo Plane（写真）が垂直ではなく対角線で分割され、写真が上部で狭く（Hero幅の約56%）・下部で広く（約42%）三角形状に食い込む構図。左端に小さな縦書き装飾文字。見出しTypographyはKicker/Bodyに比べて非常に大きく強い。

**Before geometry**: Text Plane 44% / Photo Plane 56%の単純な垂直flex分割。対角線なし。縦書き装飾なし。見出しは`clamp(2.4rem,4.6vw,4rem)`（実測64px、clampのCap到達）。

**After geometry**: `.astrea-hero-textplane`を`position:relative`・58%幅の矩形Boxとし、`clip-path:polygon(0 0,96.6% 0,72.4% 100%,0 100%)`で対角線境界を実現（Reference実測の上部56%/下部42%に相当）。`.astrea-hero-photoplane`を`position:absolute;inset:0`のFull-bleed背景層へ変更。実データ（Office Profile `office_name`）を再利用した`aria-hidden`の縦書き装飾要素(`.astrea-hero-vertical`)を追加（新規Data発明ではなく、H1が既に読み上げている情報の非音声的な意匠的重複）。見出しTypography Tokenを`clamp(2.6rem,5vw,4.6rem)`へ拡大。Mobile（≤600px）は対角線・縦書き装飾ともに解除し単純Stackへ戻す（Order §14「Hero diagonal may simplify on mobile if necessary」）。

**実装中に発見・修正した実バグ**: 初期実装（Content max-width 520px）は対角線の最も狭い箇所で本文Paragraphの実テキストが物理的にクリップされる不具合を実機Screenshotで発見、Content max-widthを420px・Buttons行のみ480pxへ調整して解消。縦書き装飾のCSS `rotate(180deg)`が不要な二重回転で文字が読みにくい向きになっていた不具合も発見・修正。Tablet幅（601-1180px）では対角線Boxの余白が不足しBoxボタンがClipに接近するリスクを確認したため、専用のTablet-safety Media Queryを追加（Content/Buttons max-widthをさらに縮小）。長い事務所名（24文字超）での実機Stress Testでも、Header/Hero双方でOverflow・クリップが発生しないことを確認。

**Classification**: **MATCH**（対角線Geometry・Typography・縦書き装飾いずれもReferenceの構図原理を再現。見出しCopy自体はFixture固有の実データであり意図的に無変更）。

## 2. HEADER

**Reference geometry**: Logo/Nav/CTAがViewport端に近い狭いInset（実測 左3.7%/右2.5%）。

**Before geometry**: 共有Wide Gridと同じ12.5%Inset。

**After geometry**: Header専用の`padding-inline:clamp(20px,3.5vw,70px)`へ変更（共有Grid Formulaを使わない、Headerは通常のContent Sectionではないという判断）。

**意図的な未対応**: Reference Headerには電話番号の下に営業時間サブテキスト（「平日9:00-18:00」）が見える。Office Profileには実際に`business_hours`という構造化データ（曜日別の時刻・例外日を含む）が存在するが、これを1行の要約文へ変換するロジックは新規のPresentation機能に相当し、曜日ごとに時刻が異なる・休業日があるケースを誤って要約するリスクがあるため、本Orderでは実装しなかった（Order §5「Do not invent new data」の精神を、複雑な実データを単純化する新規ロジックにも慎重に適用した判断）。

**Classification**: **ACCEPTABLE DIFFERENCE**（Inset幅はMATCH、営業時間サブテキストはOWNER DECISION REQUIREDとして次点候補に残す）。

## 3. SERVICE（Minor Fidelity Pass）

**Reference**: 01/02/03番号がIconと同じ行に太字で表示。

**Before**: 番号はItem右上の薄いグレー装飾数字、Iconとは別行。

**After**: 番号をIcon右横（同じ行）へ再配置、色をBorder(薄灰)→Contrast(濃紺)・太字へ変更。列間の縦罫線・3カラム構成は変更なし（Order §6「Do NOT redesign from scratch」）。

**Classification**: **MATCH**（Minor Passの範囲内で番号配置・強調を修正）。

## 4. CASE（Reference Wins）

**Reference**: 番号が写真の上に重ねた濃紺Badge（左上）。カテゴリラベル非表示。画像アスペクト比は横長（約2:1）。3件とも写真あり（Model House）。

**Before**: 番号はItem外側の大きなGold数字。カテゴリラベル表示あり。Aspect-ratio 4:3。CASE 1件のみ写真ありの状態が残っていた（後述の実データ不整合）。

**After**: 番号を`.wp-block-astrea-case-item-media::before`（`position:absolute;top:0;left:0`）へ移動し写真上のBadge化、Aspect-ratioを2:1へ変更、カテゴリラベルを`display:none`化。写真無し項目（Product要件として引き続き必須）は、Badge化できないため番号をH3直前の小さなGold数字として表示するFallbackを`:has()`で実装（新規データ・Markup変更なし）。

**実装中に発見・修正した実データ不整合（正直な報告）**: CASE item「建設業許可を初回申請で取得」（Post ID 2040）の`_thumbnail_id`が、016D-R2のCASE写真バリエーション検証（Scenario C: 全項目no-photo）作業中に削除されたまま復元されておらず、016D-R2完了時点から本Order着手まで、Model Houseの当該項目が実際には無写真状態のまま公開されていたことを本Orderの実機Screenshot確認中に発見した。添付ファイル自体（ID 2125）は削除されておらず、`_thumbnail_id`を再設定して復旧した。既存投稿データの削除は伴っておらず、Coreコード自体にも問題はない（Fixture操作ミスの見落とし）。

**検証**: Scenario A（全3件写真あり、最終Model House状態）・Scenario B（Mixed、1件のみ写真無し）いずれも実機Screenshotで確認、レイアウト崩れ・Fatal無し。

**Classification**: **MATCH**（写真ありBadge配置・Aspect比・カテゴリ非表示いずれもReference通り。No-photo Fallbackは独自設計だがOrder §7の「Product must still work with all/mixed/zero photos」要件を満たす）。

## 5. RESULTS（Preserve）

Measurement Audit時点で大きな乖離は見つからず、Order §8「Do not redesign」の指示通り無変更とした。

**Classification**: **MATCH**（無変更維持）。

## 6. PROFESSIONAL（Major Reconstruction）

**Reference geometry**: 写真がViewport左端までFull-bleed（Grid Insetの外側）、写真幅はViewport全体の約46.4%。

**Before geometry**: 写真は共有Grid内側（1920px幅で左端240px）に収まっており、Grid内46%（Viewport全体では約34.5%）に過ぎなかった。

**After geometry**: Row Container自体のPadding-leftを0にし、写真がViewportの真の左端までFull-bleedするよう変更（Order §3「Editorial media may intentionally extend beyond content grid when Reference does so」の明示的な適用）。Bodyコラムは独自のPadding-rightで共有Gridへ揃える。No-photo Fallbackは独自にPadding-leftを持たせ、テキストがViewport端に接触しないようにした。

**実装中に発見・修正した実バグ2件**: (1) 782px以下のMobile Media Queryに、016D-R2以前から存在した別系統の`text-align:center`という重複Selector定義（同じ`.wp-block-astrea-representative`セレクタが離れた2箇所で定義され、後方のRuleがCascadeで勝っていた——Price Group Labelバグ（016D-R2で発見・Spec化）と同じ再発パターン）が、写真Full-bleed化の実機確認中に見つかり、削除して解消した。(2) 写真のResponsive `height`調整（`height:100%`）が、Mobile幅でParentの高さが不定なため画像が高さ0に潰れる不具合を実機確認で発見、`height:auto`のMedia Query上書きを追加して解消。

**Classification**: **MATCH**（写真Full-bleed比率・位置ともにReference実測値に収束）。

## 7. PRICE（Major Hierarchy Pass）

**Reference**: Icon・Labelに対し価格文字が明確に支配的な大きさ、税別注記は小さく従属的。

**Before**: Icon 26px、価格`clamp(1.6rem,2.1vw,2rem)`（実測32px）——016D-R2からの差分。

**After**: Icon 34px、価格`clamp(1.9rem,2.6vw,2.6rem)`・font-weight 600へ拡大。列間の薄い縦罫線・Icon+Name横並び・Group Label非表示は016D-R2の実装を維持（Reference再確認の結果、既にMATCHしていたため無変更）。

**Classification**: **MATCH**。

## 8. FINAL CTA

**Reference**: Full-bleed濃紺帯、見出し中央、ボタン2つ、十分な縦Padding。

**Before**: 016D-R2でPrice直後へ配置済み、Full-bleed済みだが視覚的な迫力がReferenceよりやや弱かった。

**After**: 縦Paddingを`x-large`の1.15倍へ、見出しFont-sizeを拡大、ボタンPaddingを拡大。

**Classification**: **MATCH**。

## 9. FAQ / VOICE / FLOW

Reference画像に該当箇所が写っていないため、本Orderでは016D-R2確立のEditorial言語（Wide Grid・Kicker+罫線・控えめなSurface）をそのまま維持し、新規デザインは行っていない（Order §12「Do NOT over-design them during 016E」に従う）。

**Classification**: 対象外（Referenceに存在しないため評価不能、016D-R2の実装を継続）。

## 10. 測定結果サマリ（1920px幅、実機Playwright計測）

| 要素 | 016D-R2実測 | 016E実測 | Reference目標 |
|---|---|---|---|
| Header Logo left | 240px(12.5%) | ~67-70px(3.5%) | 3.7% |
| Professional Photo left | 240px(Grid Inset内) | 0px(Full-bleed) | 0% |
| Hero Text/Photo境界 | 垂直(845px固定) | 対角線(56%→42%) | 対角線(56%→42%) |
| Price Icon | 26px | 34px | Referenceより明確に大きい |
| Price Amount | 32px(clamp Cap) | 最大41.6px(2.6rem) | Referenceで支配的な大きさ |

## 11. Model House Photography

CASE 3件すべてに既存Media Library内の写真（Hotlink無し、外部著作権リスク無し）を設定、Professionalは既存承認済みFictional Portrait Fixtureを継続使用、Heroは既存承認済みFixture画像を継続使用。いずれもDemo/Fixture専用の変更であり、Product Codeの動作（0/mixed/full写真いずれでも正常動作）には依存させていない。

## 12. テスト結果

- **PHPUnit**: 397/397 Pass, 661 assertions（本Orderでは Core PHP 無変更のため既存件数のまま）。
- **PHPCS**: 変更した唯一のPHPファイル（`theme/patterns/home-hero.php`）0 Errors/0 Warnings。
- **Theme Check**: 本Orderでは未再実行（Enqueue/Text Domain/File Header等の変更なし、前回Baseline継続採用）。
- **Horizontal Overflow**: 1920/1440/1366/1024/768/375/320の7幅すべて0px（Playwright機械計測）。
- **Trust/Natural/Modern**: 3 Variationとも新Hero対角線・Header Inset・Professional Full-bleed・Price強化・CASE Badge・CTA強化のすべてが正常動作を実機確認、Trustへ復元済み。
- **Core OFF→ON**: Fatal無し、Deactivate/Reactivate双方でHTTP 200・Warning無しを確認。
- **長い事務所名ストレステスト**（24文字超の実データで実機確認）: Header 2行折返し・Hero縦書き装飾伸長いずれもOverflow・クリップ無しで収まることを確認、正常復元済み。
- **CASE写真バリエーション**: Scenario A（全3件写真あり、最終Model House状態）・Scenario B（Mixed）を実機確認。

## 13. 既知の・意図的な乖離（正直な報告）

- **Header営業時間サブテキスト**: 実データは存在するが、複雑な曜日別スケジュールを安全に1行要約する新規ロジックの実装リスクを理由に本Orderでは見送った（OWNER DECISION REQUIRED）。
- **CASE「事例をもっと見る」リンク**: Referenceのセクション見出し行右側に見える別要素だが、Order §7の具体的な要求リストに含まれておらず、新規Feature追加のリスクを避けるため本Orderでは追加していない（OWNER DECISION REQUIRED、必要であれば別Orderでの明示的な指示を推奨）。
- **FAQ/Voice/Flow**: Referenceに写っていないため、016D-R2の実装をそのまま維持（評価対象外）。

## 14. 変更ファイル

- `theme/patterns/home-hero.php`（縦書き装飾要素の追加のみ、既存Binding構造は維持）
- `theme/theme.json`（Hero/Header/Service/CASE/Professional/Price/Final CTA CSS変更、`heroTitle` Token拡大）
- `docs/research/2026-09-01_016e_reference_measurements.md`（新規）
- `docs/research/2026-09-01_construction_016e_reference_fidelity_report.md`（本書）

Fixtureデータ（CASE Post 2040の`_thumbnail_id`復旧、Office Profile一時変更→復元）はGit管理外のWordPress DB状態であり、リポジトリのDiffには含まれない。

## 15. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/v1.0.0-final/Construction 016Fへの自動進行は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
