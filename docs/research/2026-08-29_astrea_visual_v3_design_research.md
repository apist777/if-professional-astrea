# Construction Order 016A — Visual v3 B+C Design Blueprint / Owner Approval — Research Report

- **Status**: DESIGN RESEARCH COMPLETE — RELEASE HOLD維持、Owner Approval待ちでSTOP
- **Date**: 2026-08-29
- **担当**: クロエ (Chloe)
- **Product Code Diff**: **ゼロ**（`theme/`・`core/`・`functions.php`・`theme.json`・`styles/*.json`・`patterns/*.php`・`templates/*.html`・`parts/*.html` 全て無変更）

## 1. Preflight（施工前確認）

```
git status --porcelain   → ?? docs/research/visual-v3/（未コミットの既存Asset一式のみ）
git branch                → * main
git log -5 --oneline      → da97c00, ac41418, 2e827a2, f145a9e, 1e60605（015F/015G完了状態）
```

`docs/research/visual-v3/`配下は本Order開始前から実在しており（Owner側で事前配置済み）、Git未追跡だった。既存ファイルは一切破壊せず、追加のみ行った。

## 2. Asset実在確認

### Model House Assets（`docs/research/visual-v3/assets/model-house/`）

指示書記載の8ファイル、**全て実在を確認**（過不足なし）。画像内へのNavigation/Logo/H1/Button/電話番号/UI/説明文の焼き込みは無いことを目視確認した。

| ファイル | 実寸 |
|---|---|
| hero-office-city.png | 1536×494 |
| professional-sato-kenichi-wide.png | 1536×1024 |
| professional-sato-kenichi-portrait.png | 1024×1536 |
| case-legal-documents.png | 512×260 |
| office-consultation-room.png | 484×260 |
| accent-greenery.png | 484×260 |
| case-city-landscape.png | 528×260 |
| case-modern-office-building.png | 513×260 |

### Icon Assets（`docs/research/visual-v3/assets/icons/astrea/`）

指示書記載の10候補、**全て実在を確認**：company / permit / inheritance / contract / result-company / result-check / result-consultation / price-yen / document / folder。Navy(#102A43)+Gold(#B99A5C)の線画、ViewBox 48×48、`README.md`に仕様記載あり。

`*:Zone.Identifier`ファイルは`find`で検索し**0件**（削除作業は不要だった）。

## 3. Current Visual Diagnosis（015G不合格の理由）

015Gまでの到達点と、Owner指摘事項の対応関係：

| Owner指摘 | 015Gでの状態 | 根本原因 |
|---|---|---|
| First Viewインパクト不足 | Hero改善はしたが、Surface Box内にCover画像を収めた「1 Section」構成のまま | HeroをPage内の一部品として設計していた |
| Desktopで細く見える | Content幅が終始`contentSize:720px`前後に収まる | Photography/Visual要素までText Measureへ閉じ込めていた |
| Card依存 | Services/CASE/VOICEは全てBorder+Surfaceの均質なBox | Section差別化をContent量のみで行っていた |
| Section構成が均質 | Centered H2 → Card群、の反復 | Editorial Hierarchy（番号・非対称・罫線）が無かった |
| Professionalが社員紹介Card的 | 96px/160pxの小さな円形Avatar | 写真をUI部品として扱っていた |
| Resultsが小さい | 3列Grid、`clamp(1.75rem,3.5vw,2.5rem)` | 数字を主役にする設計になっていなかった |

## 4. B+C Design Blueprint

詳細仕様は`docs/specifications/07_astrea_visual_v3_design_direction.md`に保存。要旨：

- **B（Editorial Foundation）**: 大きな日本語Typography・Grid・Whitespace・Fine Rule・Numbering・Wide Composition。写真が無くても成立する土台。
- **C（Photography Impact）**: 大きな写真・非対称構成・Full/Wide Breakout。Bを強化するが依存させない。
- Trust=B+A（端正な写真）、Natural=B+softer C、Modern=B+C最強、という配分方針（Order §20準拠、Markup共通・Token差のみ）。

## 5. Hero設計

Header統合・Transparent、`core/cover`相当のFull Bleed写真 + 左寄せ非対称Text（Text Column 660px確定——560pxでは指定H1文言「複雑な手続きを、」が2行に予期せず割れることをMockup実測で発見し、幅を拡大して解消した）。詳細は仕様書§4。

## 6. Mobile Hero設計

Desktop縮小ではなく、Photography（上部・高さ抑制）→ Gradient Scrim → Eyebrow → H1 → Copy → CTA（縦積み）の階層で再設計。320px/375pxともHorizontal Overflow 0件を実測確認。詳細は仕様書§5。

## 7. Header関係性

Hero上へ統合しTransparent化する方向をMockupで提案。Navigation可読性・電話番号・Contact CTA・長い事務所名への耐性は016Bでの実装検証事項とし、016Aでは視覚提案に留めた（Order §9準拠）。

## 8. Width System

1440px Viewportで「Desktopなのに細い」印象を解消するため、Hero写真・Professional写真・CASE Featureは`content-w`（1200px相当）を超えてFull/Wide Bleedさせる設計とした。本文（Description等）はText Measureを維持し、Photography/Visual要素のみ幅を解放する、という使い分けを明文化した（仕様書§4/§8相当）。

## 9. Typography

Hero H1・Professional名・Results数字にGeorgia/Mincho系Serifを使用し、既存Trust Variationの`heading`Font Family方針と一貫させた。Scaleは全て`clamp()`で指定し、320px〜1440pxまでの実装耐性を確認済み（Mockup実測）。

## 10. Services / Results / Professional / CASE

Card依存を避けたEditorial表現をそれぞれMockup化。実装可能性の分類（WordPress Core Block／ASTREA Block／theme.json／Theme CSSのどれで再現するか）は仕様書§4・§6・§7・§8・§9に記載。

## 11. Section Rhythm

Hero（写真）→ Services（白・Editorial）→ Results（濃色Full Width）→ Professional（写真＋白）→ CASE（写真混在）、という役割分担をMockupで実証。Order §17の例をそのまま固定仕様にはしていない。

## 12. Style Variations

Markup共通・Token差という制約の中でTrust/Natural/Modernを描き分ける方針を明文化（仕様書§13）。016Aでは3 Variation分のMockup実装までは行っていない（Trust想定の1系統のみ実装、Owner承認後にVariation展開へ進む）。

## 13. No-Photo Fallback

写真が使えない利用者でも成立する設計原則を明文化（仕様書§11）。実機検証（画像を外した状態のMockup）は016Aでは実施していない——016Bでの実装確認事項とした。

## 14. Accessibility / Maintainability

画像への文字焼き込みなし・Alt方針・Contrast方針・Motion方針を明文化（仕様書§12）。Glassmorphism・過剰Shadow・Neumorphism等の禁止事項は使用していないことを確認済み。

## 15. Implementation Feasibility（要旨）

| 要素 | 実現レイヤー |
|---|---|
| Hero Full Bleed写真 | `core/cover`＋Page Template側のWide/Full許可（015Gで制約が発見された実績があり要再検証） |
| Hero縦書きMetadata | Theme CSS |
| Services番号+罫線 | 既存`astrea/service-list`のMarkup拡張＋Theme CSS |
| Results巨大数字 | 既存`astrea/results-list`＋Theme CSS（Token値見直し中心） |
| Professional大型写真 | 既存`astrea/representative`のMarkup拡張が必要な可能性が高い |
| CASE Editorial Grid | 既存`astrea/case-list`のMarkup拡張＋Theme CSS、**ただし提供素材の解像度制約あり（§16 Finding）** |

## 16. Product/Asset Findings（修正なし、記録のみ）

- **CASE用画像3点（512×260前後）は大きなFeature表示に解像度不足**。本Mockupでは仮に大きく表示しているが、実装時は追加素材調達か、3点同格Thumbnail運用への変更が必要（詳細: 仕様書§3）。
- 015D〜015Gで既に記録済みの Post v1 Backlog（`astrea/price-list`のlimit属性欠如、CPT Archive og:url、Professional Archive空Excerpt、Search Breadcrumb汎用ラベル、Price Group構造）は本Orderでも**引き続き対応せず**、維持する（Order §27準拠）。

## 17. 015G Comparison

| 評価項目 | 015G | Visual v3 Target |
|---|---|---|
| First View Impact | 中（Surface Box内のCover画像） | 高（Full Bleed写真＋非対称Text、Header統合） |
| Typography | 中（既存Token範囲内） | 高（Editorial Scale、Serif見出し、Numbering） |
| Desktop Scale | 低〜中（Content幅に閉じ込め） | 高（Photography/VisualはWide/Full解放） |
| Photography | 低（96px Avatar中心） | 高（大型Wide Photo、Professionalが主役） |
| Section Rhythm | 中（Surface/Contrast 4箇所） | 高（役割ごとに異なるVisual Role） |
| Professional Presentation | 低（社員紹介Card的） | 高（写真が主役の非対称Block） |
| Results Presentation | 低（3列Grid、控えめな数字） | 高（Full Width濃色、巨大数字） |
| Card Dependence | 高（Services/CASE/VOICE全てCard） | 低（Editorial List/Number中心） |
| Mobile | 中（Desktop相当の構成を縮小） | 高（Mobile専用階層で再設計） |
| Trust（既存Design Systemとの一貫性） | 高 | 中〜高（Token/Font Family方針は踏襲、Markupは変更が必要） |

Screenshot比較用に、015Gの`docs/research/screenshots/015g-model-house/{before,after}-home-desktop.png`をVisual v3 Mockupと並べて確認できる状態にしてある（本ReportおよびChat報告で提示）。

## 18. 016B以降の施工案（提案、未承認）

仕様書§14に記載の10ステップ（Hero→Results→Services→Professional→CASE→Section Rhythm全体→3 Variation展開→No-Photo実機検証→Responsive実機検証→Accessibility Full Audit）を、個別Construction Orderへ分割することを推奨する。一括実装はRegression Riskが大きいと判断する。

## 19. 成果物

- Mockup: `docs/research/visual-v3/mockups/visual-v3-home-desktop.html`（Desktop/Mobile共通、Media Queryで分岐する単一ファイル。Real Assetを使用、Placeholder Gray Boxは不使用）
- Screenshot: `docs/research/visual-v3/screenshots/`
  - `hero-desktop-1440.png`
  - `hero-mobile-375.png`
  - `home-upper-desktop-1440.png`（Hero/Services/Results/Professional/CASEまで収録）
  - `home-upper-mobile-375.png`（同上）
- Specification: `docs/specifications/07_astrea_visual_v3_design_direction.md`
- 本Report: `docs/research/2026-08-29_astrea_visual_v3_design_research.md`

## 20. Completion Conditions

- [x] Repository preflight完了
- [x] Model House assets実在確認（8/8）
- [x] Icon research assets確認（10/10、Zone.Identifier 0件）
- [x] B+C Visual v3 Design Blueprint完成
- [x] Hero Desktop Mockup完成
- [x] Hero Mobile Mockup完成
- [x] HOME Upper Desktop完成（Hero/Services/Results/Professional/CASE）
- [x] HOME Upper Mobile完成
- [x] 015G comparison完成
- [x] No-photo fallback方針完成（設計原則のみ、実機検証は016B）
- [x] Implementation feasibility確認（分類表作成、確定は016B）
- [x] Research report保存
- [x] Specification保存
- [x] Product code diff = 0
- [ ] HISTORY.csv更新（本Report確定後に実施）
- [ ] Commit / Push / CI Green（同上）
- [x] RELEASE HOLD継続
- [x] Owner Approval待ちでSTOP
