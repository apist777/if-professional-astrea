# Construction Order 016E-R1 実装報告 — ASTREA Visual v3 Reference Fidelity Final Pass

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-02 00:05 JST
- **End**: 2026-09-02 09:00 JST
- **Commit**: `0d060b5`（CI green確認済み: PHPUnit / Theme-Core independence smoke test / PHP syntax+Coding Standards、全Job成功）
- **Measurement Audit**: `docs/research/2026-09-02_016e-r1_reference_fidelity_measurements.md`
- **Owner Directive**: 016E REVISE。「さらに格好よくする」ではなく、016E Actualに残っているComposition/Scale/Density/Typography/Section HeightのReference差を計測し収束させる。「良く見えると思ったので」は理由にしない。

## 0. 総括

016Eは技術基盤（Shared Wide Grid、Hero対角線split、CASE 3-column media cards、Section Heading Kicker等）を正しく確立したが、実際の運用結果は「HeroのH1が細いText Planeへ押し込まれ5〜6行に分裂」「Professionalが縦に過大化」「Final CTAが明確に過大」「Priceの税別注記が改行途中で分断」という、Composition/Density面での明確な後退を伴っていた。本OrderはこれをすべてReference実測値へ収束させる「Fidelity Final Pass」である。

## 1. HERO（P0）

**問題**: H1が5〜6行に分裂、Referenceの「TEXT|diagonal|PHOTO」が一体化した構図に見えず「細いTEXT|巨大PHOTO」に見えていた。

**対応（Order優先順位: Text Plane width→Diagonal→max-width→font-size→line-heightの順）**:
1. Text Plane box自体を58%→68%へ拡大（Diagonalの絶対位置(56%/42%)は不変のまま、Box自体を広げて内部の安全余白を増やした）。
2. Kicker/H1のmax-widthを420px→760pxへ拡大（Diagonalの最も広い上部近くに位置するため安全マージンが大きい）。
3. Body Paragraphは460px、Buttonsは480pxという、より下方（Diagonalが狭くなる位置）にある要素ほど保守的なmax-widthへ個別調整。
4. font-size/line-heightは変更していない（Widthの拡大のみでReference ±1行の目標(2行→3行)を達成したため、Priority上位の対応で足りた）。

**実装中に発見した実バグ**: WordPress Core自身の`.is-layout-constrained > *`ルールが`margin-left/right:auto`を`!important`で強制しており、Text Plane内の各要素を意図せず中央寄せしていた。Theme側の`margin:0`リセットに`!important`が付いていなかったため負けており、実際のContent列がPadding基準より右へシフトし、対角線と衝突してテキストが物理的にクリップされる実害を実機Screenshotで発見した。全リセットへ`!important`を追加して解消。

**写真の暗さ**: Reference/ActualそれぞれのHero写真領域をCanvas Pixel Samplingで平均RGB実測し、Actualが明らかに暗い（輝度117.6 vs Reference 143.3）ことを確認、Fixtureの`dimRatio`を45%→30%へ調整し輝度150.0まで改善（Reference比で許容範囲）。写真自体のOverlay/Opacity設定はFixtureデータ（Cover Blockの属性）であり、Theme CSSの変更ではない。

**Copy長との関係（正直な報告）**: FixtureのH1本文（「許認可・会社設立・相続手続きを、わかりやすく、確実に。」27文字）はReferenceのCopy（「複雑な手続きを、前へ進める力に。」16文字）よりかなり長く、同じ行数に収めるには本質的な限界がある。今回はTheme側のTypography修正（Width拡大）のみで対応し、Fixture Copyの短縮は行っていない（意味変更を避けるOrder指示に従う）。

## 2. PROFESSIONAL（P0）

**問題**: Heading→巨大Portrait→巨大Profileという縦の積み重なりに見え、Referenceの「一体化した横長Editorial Composition」から乖離していた。

**対応**:
- Photo Container/Imgに`aspect-ratio:1.7`を指定（旧`min-height:420px`を廃止）、Reference実測の横長比率(≈1.7:1)へ収束。Row Height 515.4px→454.6px（-12%）、Section総高さ649.4px→588.5px（-9%）。
- Name font-sizeをclamp(2rem,3.4vw,2.7rem)→clamp(1.7rem,2.6vw,2.2rem)、Bio font-sizeを`--large`(最大26px)→`--medium`(16-18px)、line-height 1.8→1.6、各種margin縮小により密度を向上。
- Photo Full-bleed(016Eで確立、Viewport左端まで)自体は維持（Order §5「Full-bleedにする技術自体は維持可能だが、Full-bleedだから巨大にする必要はない」に従い、視覚重量のみ調整）。

**Tablet確認**: 1024px幅で2-column horizontal（十分な横幅があるため）、768px幅では既存の782px breakpointにより縦Stack（Photo/Profileの自然なEditorial stack）、いずれも「巨大写真→巨大文字」にはならず適切な比率であることを実機確認した。

## 3. FINAL CTA（P0）

**問題**: 016Eで意図的に強化した結果、Reference比で明確に過大（実測365.4px、Reference相当の216px比で+69%）になっていた。

**対応**: `padding-block`を`x-large×1.15`(≈110px)→`large`(56px)へ、見出しfont-sizeをclamp(1.7rem,2.6vw,2.3rem)(実測36.8px)→clamp(1.4rem,1.8vw,1.7rem)(実測27.2px)へ、ボタンPaddingも縮小。結果、Section height 365.4px→229.7px（Reference相当216pxに近似）。Heading/Buttons/配置構成自体は無変更。

## 4. PRICE（P1）

**問題**: 「55,000円〜（税別）」が改行され「55,000円〜（税」「別）」と不自然に分断されていた。

**根本原因（Fixtureデータの誤り、Theme/Core側の不具合ではない）**: Core側には元々`astrea_price_amount`（金額）と`astrea_price_notes`（補足）という独立した2つのField（`core/includes/price.php`で定義済み）が存在し、Theme CSS側にも`.wp-block-astrea-price-item-amount`（大きく強調）/`.wp-block-astrea-price-item-notes`（小さく従属）という明確に異なるスタイルが既に定義されていた。しかしFixtureデータ4件すべてで、税別注記「（税別）」が誤って`astrea_price_amount`の中に金額と結合して保存されており、`astrea_price_notes`は未使用のまま空だった。これにより本来2階層に分離されるべき表示が1つの大きなParagraphとして描画され、幅が足りず改行時に不自然な分断が生じていた。

**対応**: Price投稿4件（会社設立パック/建設業許可申請/相続手続きサポート/顧問契約）すべてで、`astrea_price_amount`から「（税別）」を除去し、既存の`astrea_price_notes` Fieldへ移動（新規Field追加なし、Core/Theme側のコード変更なし、Fixtureデータの補正のみ）。Order §6が「Demo fixture copy調整」と「Theme側のTypography修正」を区別するよう求めていた通り、本件は前者に分類し、STOPしてOwner Decisionへ上げる必要がある「データ構造変更」には該当しないと判断した（既存構造を正しく使うだけであるため）。

**副次確認**: Icon/Label/Price/Tax-noteの4階層自体は016Eで既に構築済みであり、本Orderでは追加のCSS変更は行っていない（データ修正だけで階層が正しく機能することを確認）。

## 5. CASE（P1、LOCK DIRECTION）

Order §8の明示的指示により変更なし。Scenario A(全3件写真、Owner Baseline)/B(Mixed)/C(1件写真+2件無写真)/D(全0件)の4パターンをすべて実機確認、Grid崩れ・Fatal・偽Placeholder無し。検証後Scenario Aへ復元済み。

## 6. Header Owner Decision Items（変更なし、Order §10通り）

016Eで保留した「営業時間サブテキスト」「CASE『事例をもっと見る』」は、本Orderでも新規機能追加を避けるため実装していない。

## 7. Responsive / Regression

- **Horizontal Overflow**: 1920/1440/1366/1024/768/375/320の7幅すべて0px（Playwright機械計測）。
- **Trust/Natural/Modern**: 3 Variationとも新Hero/Professional/Price/CTA CSSが正常動作、Trustへ復元済み。
- **Core OFF→ON**: Fatal無し、HTTP 200を確認。
- **PHPUnit**: 397/397 Pass（本Orderでは Core PHP無変更）。
- **PHPCS**: 変更ファイルはtheme.json（CSS）のみ、PHP変更なし。
- **長い事務所名(24文字超)ストレステスト**: Header/Hero双方でOverflow・クリップ無しを確認、正常復元済み。
- **Fixture Backup/Restore**: CASE写真Scenario試験・Price Fixtureデータ修正・Office Profile長文名試験、いずれも実施前にBackupを取得し、試験後Owner Baselineへ復元したことを確認（前回016D-R1のtests/誤削除ヒヤリハットを踏まえ、対象を都度確認）。

## 8. Screenshot Evidence

`docs/research/screenshots/016e-r1/`に15枚を格納（Reference/Actual比較5枚、7幅のFull Page、Professional Tablet、CASE写真バリエーション2枚）。

## 9. 変更ファイル

- `theme/theme.json`（Hero/Professional/Final CTA CSS変更のみ、CASE/Price CSSは無変更）
- `docs/research/2026-09-02_016e-r1_reference_fidelity_measurements.md`（新規）
- `docs/research/2026-09-02_construction_016e_r1_reference_fidelity_report.md`（本書）

Fixtureデータ変更（Price 4件のamount/notes分離、CASE Scenario試験と復元、Hero dimRatio調整、Office Profile一時変更と復元）はGit管理外のWordPress DB状態であり、リポジトリのDiffには含まれない。

## 10. Acceptance Criteria 照合（Order §15）

- [x] Hero H1が細い縦長ブロックになっていない（3行、Reference比+1行）
- [x] Hero diagonal compositionがReference比へ接近
- [x] Hero写真が過度に暗くない（輝度150.0、Reference輝度143.3に近似）
- [x] Professionalが巨大Section化していない（Section高さ-9%、Row高さ-12%）
- [x] Professionalが横長Editorial composition（Photo aspect 1.7:1）
- [x] Price desktop 4-column（変更なし、維持）
- [x] Priceの税別が不自然に途中改行しない（Fixtureデータ修正で解消）
- [x] Final CTAが巨大Hero化していない（Section高さ-37%）
- [x] CASE all-photoは現在の良い方向を維持（LOCK、無変更）
- [x] 320px overflow 0
- [x] 3 Variations safe
- [x] Core OFF safe
- [x] No Demo Fraud（実在するAssetのみ使用、新規Hotlink無し）

## 11. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/v1.0.0-final/Construction 016Fへの自動進行・Price-list limit修正・Archive og:url修正・新機能追加は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
