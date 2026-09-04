# Construction Order 016K — HOME LOWER SECTIONS VISUAL v3 COMPLETION 施工報告

## 0. Purpose

HOME上半分（Hero〜Professional）で確立したASTREA Visual v3のデザイン言語を、HOME後半（PRICE / FAQ / VOICE / FLOW / Closing CTA）まで一貫して完成させる。新機能追加ではなく、既存Block・既存意味構造を維持したままの視覚仕上げ。

## 1. Before Findings（Pre-Construction Audit）

Order本文は5 SectionそれぞれについてTarget Compositionを詳細に指定していたが、実際のコード（`theme/theme.json`のCSS、各Pattern）を先に精査し、1920px Desktopでのライブ確認を行った結果、**5 Sectionのうち4つ（PRICE / FAQ / VOICE / Closing CTA）は既にOrder本文が要求するEditorial Compositionをほぼ満たしていた**ことを確認した：

| Section | Order要求 | 実装状況（監査時点） |
|---|---|---|
| PRICE | Wide Grid・4項目横展開・大きな金額主役・Border/divider中心・Heavy shadow禁止 | `.wp-block-astrea-price-list--compact`が既にDesktop 4カラムGrid、`border-left`区切り、`clamp(1.9rem,2.6vw,2.6rem)`の大きな金額、shadowなし・radiusなしで実装済み |
| FAQ | 左Heading Plane：右Content PlaneのEditorial Split Layout、Q/Aラベル明確化 | `.wp-block-astrea-faq-list-wrapper`が`grid-template-columns:minmax(260px,380px) 1fr`で既にDesktop 2カラムSplit実装済み、Q/Aラベル(`aria-hidden`付き)も既存 |
| VOICE | Top divider・quote/body・attributionのEditorial testimonial、カード化しすぎない | 既にTop divider＋本文＋属性の3要素構成、Card風の枠・shadowなしで実装済み |
| Closing CTA | Dark Navy・Full width・generous spacing・電話/Contact優先順位 | 既に`align:full`Dark Navy帯、`padding-top/bottom:x-large`(96px)、Outline(電話)+Solid Gold(Contact)のボタン優先順位が確立済み（016D-R2で確立） |

これらは新規に作る必要がなく、既存実装を壊さないことが最優先と判断した。

**一方、実地監査により2件の genuine な問題を発見した：**

### 1.1 FAQ: Mobile（≤782px）でEditorial Split Layoutが正しく1カラムへ戻らない

`.wp-block-astrea-faq-list-wrapper`のMobile用メディアクエリは`grid-template-columns:1fr`のみを指定していたが、子要素（見出しh2に`grid-column:1`、FAQ本文に`grid-column:2`）のDesktop用Grid配置指定がリセットされていなかった。CSS Gridの仕様上、明示的な列（1fr、1列のみ）に対して子要素が`grid-column:2`を要求すると、暗黙列（implicit column、auto幅）が追加生成されてしまい、結果として375px幅で「よくあるご質問」の見出しが1〜2文字ずつ縦に折り返す極端に狭い列（実測27px幅）になり、その右にFAQ本文が別列で表示される、という壊れたレイアウトになっていた（Order §5の"Mobileでは自然な1-columnへ戻す"という明示要求に反する状態）。Order §10の"Safety widths"（375/320含む）確認でも同種の懸念が明記されていた。

`docs/research/screenshots/016j/09-faq-background.png`（016J時点のDesktop screenshot、正常）とは異なり、Mobile幅でこの問題は今回のOrder着手まで一度も検証されていなかった。

### 1.2 FLOW: 01/02/03の数字が「大きなVisual anchor」としてはやや控えめ

Order §7は"数字01/02/03を大きなVisual anchorとして使う"ことを明示的に要求していたが、既存の`font-size:1.8rem`はRESULTS section（`clamp(3.2rem,6vw,5.4rem)`という別の"統計数字"用Token）と比べても、また同じ3-4カラムの密なGrid内で使われるPRICEの金額（`clamp(1.9rem,2.6vw,2.6rem)`）と比べても、視覚的な主張力が控えめだった。

## 2. Design Decisions

- **PRICE / VOICE / Closing CTAは無変更**。既にOrderの要求するEditorial Compositionを満たしており、「同じものをもう一度作る」ことを避けた。Order §3の"個別に綺麗にする"ではなく"既に成立している要素は壊さない"という判断。
- **FAQ Mobile Grid修正**：`@media (max-width:782px)`内で、見出し・FAQ本文の両方に`grid-column:1`を明示的に指定し、暗黙列の生成を止めた。新規Selector・新規Tokenは使わず、既存のMobile用メディアクエリに2行追加するのみ。
- **FLOW数字の拡大**：`font-size:1.8rem`を、PRICEの金額表示で既に使われている実測済みの値`clamp(1.9rem,2.6vw,2.6rem)`へ変更（新規Token不使用、既存の"密なGrid内での大きな数字"という精確に類似したコンテキストの値を再利用）。数字が大きくなった分、Kicker/Heading衝突と同じ理由で本文テキストとの衝突を防ぐため、`li`の`padding-top`を`2.4rem`→`3.4rem`へ調整（016H/016I/016Jで確立した"絶対配置要素のサイズ変更時は必ずクリアランスを再計算する"という設計原則の再適用）。

## 3. 変更ファイル

`theme/theme.json`（CSSのみ、3箇所）：
1. `.wp-block-astrea-faq-list-wrapper h2,.wp-block-astrea-faq-list-wrapper .wp-block-astrea-faq-list{grid-column:1;}` をMobile用メディアクエリ内に追加
2. `.astrea-flow-steps li`の`padding-top`を`2.4rem`→`3.4rem`
3. `.astrea-flow-steps li::before`の`font-size`を`1.8rem`→`clamp(1.9rem,2.6vw,2.6rem)`

他のファイルへの変更なし（`git diff --stat`で`theme/theme.json | 2 +-`のみ確認済み）。PHPファイル変更0件。

## 4. PRICE 結果

無変更。既存の4カラムCompact Grid・Border-left区切り・大きな金額・アイコンによる階層が、"普通の料金表"ではなく"Professional Service Pricing"として成立していることをScreenshot（`03-price-1920.png`）で確認した。1024px 2x2 Grid・375px 1カラムでも金額の不自然な折返しは発生しない（`audit-price-1024.png`、`audit-price-375.png`）。

## 5. FAQ 結果

Desktop（1920/1440/1366/1024）はEditorial Split Layout（左：Heading Plane、右：Content Plane）が既存のまま正常動作（`04-faq-1920.png`）。**Mobile（782px以下）のGrid崩れを修正**し、375px（`audit-faq-375-after.png`）・320px（`audit-faq-320-after.png`）・768px（`audit-faq-768-after.png`）いずれも自然な1カラムへ戻ることを確認した。Before（`audit-faq-375.png`、壊れた2カラム状態）とAfter（`audit-faq-375-after.png`）を直接比較可能な形で保存した。

FAQ Archive page（`/faq/`、別のCSS Class `.astrea-faq-archive-listing`を使用）は本修正の対象外セレクタであり、元々この問題の影響を受けていなかったことを確認した（`faq-archive-375-check.png`）——無関係な回帰なし。

## 6. VOICE 結果

無変更。Top divider・本文・属性によるEditorial testimonial構成が既存のまま維持されていることを確認した（`05-voice-1920.png`）。引用符の巨大装飾は追加していない（Order §6の明示的な禁止事項を遵守）。016J（Colored Section Heading Spacing）で確立した「背景上端→56px→Kicker→32px→Heading」の関係も無変更で維持されている（§9参照）。

## 7. FLOW 結果

01/02/03の数字を`clamp(1.9rem,2.6vw,2.6rem)`へ拡大し、"大きなVisual anchor"としての役割を強化した（`06-flow-1920.png`）。カードUI化はしていない（border-top divider方式を維持）。矢印アイコンは追加していない。数字拡大に伴う本文テキストとの衝突は発生しないことを、Desktop（`flow-1920-after.png`）・Mobile 320px（`flow-320-after.png`）双方で確認した。

## 8. Closing CTA 結果

無変更。Dark Navy・Full width・`padding:x-large`(96px)による十分な余白・電話(Outline)/Contact(Solid Gold)の優先順位付けボタンが既存のまま維持されていることを確認した（`07-closing-cta-1920.png`）。新しい文言フィールドは追加していない。

Closing CTAはHOME内でPRICEの直後（016D-R2で確立済みの配置——"価格を見た直後の意思決定"という文脈上の終着点）に位置しており、FAQ/VOICE/FLOWがその後に続く構造は本Order着手前から変わっていない。Order §9自身が示すSection Rhythmリスト（"PRICE → CTA → FAQVOICE → FLOW → Footer"）とも一致しており、この配置を変更する必要はないと判断した。CTAとFooterは直接隣接していない（間にFAQ/VOICE/FLOWが挟まる）ため、Order §8が懸念する「Dark→Darkが潰れて一枚に見える」というリスクは現在の構造には該当しないことを確認した。

## 9. Section Rhythm（§9）確認

HOME全体を1920px（`01-home-full-1920.png`）でFull-Page確認した。White(Hero/Services)→White(CASE見出し)→Light(CASE Card)→Dark(RESULTS)→White(Professional)→White(PRICE)→Dark(Closing CTA)→White(FAQ)→Light(VOICE)→White(FLOW)→Dark(Footer)というリズムが、016H/016I/016Jで確立した規則性（Colored Section Top→Kicker=56px、Kicker→Heading=32px）を保ったまま、HOME全体を通して一貫していることを確認した。

## 10. Responsive 結果

- **Primary fidelity**: 1920px（`01`〜`07`）・1440px（`08-home-full-1440.png`）・1366px（`09-home-full-1366.png`）でWide Gridが揃うことを確認。
- **Safety widths**: 1024px・768px・375px（`10-home-lower-375.png`）・320px（`11-home-lower-320.png`）で Horizontal Overflow・不自然な折返し・数字とテキストの衝突・CTAボタンのOverflowがいずれも発生しないことを確認した。
- Horizontal Overflow: 7幅（1920/1440/1366/1024/768/375/320）× 10ページ = 70パターンを機械的に確認、超過0件。

## 11. Style Variations 結果

Trust（`14`相当、無変更確認）・Natural（`15-natural-lower-1440.png`、`15-natural-faq-375.png`）・Modern（`16-modern-lower-1440.png`、`16-modern-faq-375.png`）の3 Variationで、PRICE/FAQ Mobile修正後の状態を確認した。Naturalの暖色パレット・Modernのシャープなgeometryいずれでも、Variation固有のレイアウト崩れは発生しなかった（Variationは既存のColor Token差し替えのみで成立）。Trustへ復元後、差し替え前バックアップとPython `json.dumps(sort_keys=True)`によるdiffで完全一致を確認済み。

## 12. Accessibility 結果

- Heading hierarchy: 本Orderでは見出しタグ（h2/h3等）を一切変更していない（CSSのみの変更）。
- Q/A semantics: `aria-hidden="true"`付きのQ/Aラベル（`core/includes/faq-list-block.php`）は無変更のまま維持。
- Focus visible: 既存の`:focus`/`focus-visible`CSSは無変更のまま維持。
- CTA keyboard operation: `<a>`要素のまま、Markup変更なし。
- Contrast: 配色（Navy/Gold/White）は無変更、Variation差し替え時のContrastも既存のまま。
- Reduced motion依存: 本Orderで新規のAnimation/Transitionは追加していない。

## 13. Regression 結果

- 016H（HOME Kicker/H2衝突修正）：内部ページとは別Selector、無影響。
- 016I（内部ページKicker/H1衝突修正）：`regression-016i-service-single.png`でSelector・Spacingとも無変更を確認。
- 016J（Colored Section Heading Spacing）：VOICE/CASEの`padding-top:88px`・Kicker`top:56px`を`getComputedStyle()`で実測し、1px単位で無変更を確認。
- Archives/Singles/Search/404：本Orderで変更したSelector（`.wp-block-astrea-faq-list-wrapper`のMobile Grid、`.astrea-flow-steps`）はHOME専用のPattern（`home-faq.php`、`home-flow.php`）からのみ到達可能で、Archive/Single Templateには存在しないことをコード上確認済み。FAQ Archiveページ（`.astrea-faq-archive-listing`、別Selector）への影響がないことをScreenshotで実地確認した（§5）。
- Core OFF→ON：`wp plugin deactivate/activate astrea-core`後いずれもHOME HTTP 200、Fatal/Warning無し。

## 14. Technical Verification 結果

- **PHPUnit**: `npx wp-env run tests-cli "vendor/bin/phpunit"`で398/398実行。既知のPre-existingエラー3件（`wp-phpunit`のAttachment Factory起因）のみ、無変更。本Orderで変更したPHPファイルは**0件**。
- **PHPCS**: 対象PHPファイル0件のため実行対象なし。
- **Theme Check**（公式Plugin、検証専用に一時インストール→検証後アンインストール済み）: REQUIRED 0 / WARNING 0 / INFO 1（既存・無変更）。
- **Core OFF→ON**: HTTP 200・Fatal無し。

## 15. Known Findings

現時点で追加の未修正Visual不具合は確認されていない。PRICE/VOICE/Closing CTAは既存実装がOrderの要求を既に満たしていたため無変更とした——これは手抜きではなく、"既に成立している要素を壊さない"というOrder §3自身の精神（Visual v3の静かな迫力を維持し、装飾のための装飾をしない）に沿った判断である。

## 16. Owner Acceptance Criteria（§15）確認

| # | 基準 | 結果 |
|---|---|---|
| A | HOME最下部までVisual v3に見える | PASS — Full-Page Screenshot(§9)で確認 |
| B | Priceが「普通の料金表」に見えない | PASS（既存実装、§4） |
| C | FAQが「WordPressの文章一覧」に見えない | PASS — Desktop既存・Mobile今回修正（§5） |
| D | Voiceがカードテンプレ感を出さない | PASS（既存実装、§6） |
| E | Flowが業務システムStepperに見えない | PASS — 数字拡大後もCard化なし（§7） |
| F | Closing CTAがページの明確な終着点になる | PASS — Price直後の意思決定Sectionとして機能、位置は016D-R2から無変更（§8） |
| G | 016JのColored Section spacingが維持される | PASS — 実測値1px単位で確認（§9, §13） |
| H | 1920/1440/1366でWide Gridが揃う | PASS（§10） |
| I | 375/320で自然なMobile composition | PASS — FAQ修正含め確認（§10） |
| J | Demo-only implementation = 0 | PASS |
| K | New feature = 0 | PASS |
| L | Horizontal overflow = 0 | PASS — 70パターン確認（§10） |
| M | Core OFF safe | PASS（§13） |
| N | Theme Check REQUIRED/WARNING = 0 | PASS（§14） |
| O | CI Green | Push後 `gh run watch` で確認し、HISTORY.csv確認コミットで記録する |

## 17. 測定値・Commit

- Start: 2026-09-04（本Order着手）
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER HOME VISUAL v3 ACCEPTANCE**

Release Ready宣言は行っていない。次のConstruction Orderへは自律的に進まない。Tag・GitHub Release・Deploy・WordPress.org submissionのいずれも実施していない。Ownerがスクリーンショット・本Reportを確認し明示的に承認した後にのみ、次工程を決定する。
