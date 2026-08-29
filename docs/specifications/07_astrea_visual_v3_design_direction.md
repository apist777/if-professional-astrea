# 07. ASTREA Visual v3 — Design Direction (B+C)

- **Status**: DESIGN BLUEPRINT（Owner Approval待ち、実装なし）
- **Construction Order**: 016A
- **Date**: 2026-08-29
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. 位置づけ

本書は`docs/specifications/06_astrea_visual_v2_design_system.md`（Visual v2、015B〜015Fで実装）を**置き換えない**。Visual v2で確立したToken体系（Color/Radius/Typography Scale/Spacing）・3 Style Variation構造・既存Semantic Dataは全て維持する。本書は、Owner Visual Acceptanceで不合格となったVisual v2 HOME上部（Hero/Section Rhythm/Photography/Professional/Results）の**次の方向性**を示す設計図であり、016B以降で実装が承認された場合にのみ、06番文書との統合・更新を行う。

## 1. なぜVisual v2は不合格だったか

015G Model Houseまで作り込んでも解消しなかった問題（Owner指摘）：

- First Viewのインパクト不足（HeroがSectionの1つに見える）
- Desktopで全体が細く小さく見える（Content幅・Photography幅がText Measureに閉じ込められている）
- Card依存（Services/CASE/VOICEが同じ箱の反復に見える）
- Section構成が均質（Centered H2 → 3 Cards の反復）
- Typographyの迫力不足
- Photographyの活用不足（Owner Fixtureの写真が小さいAvatar程度）
- Professional sectionが「社員紹介Card」的
- Resultsが小さい

これらは**Fixture文言の問題ではなく、Visual Structureの問題**と判断した。015GでCopyを磨いても、Card+Centered見出しという骨格自体を変えない限り解決しない。

## 2. B+C Design DNA

**B（Editorial Foundation）**: 大きな日本語Typography、明確な階層、Grid、Whitespace、Fine Rule、Numbering、Wide Composition。BだけでもDesignが成立することを必須条件とする（写真がない利用者のため）。

**C（Photography Impact）**: 大きな写真、非対称構成、Crop、Full/Wide Breakout。CはBを強化するが、Bに依存する（写真無し=崩壊、を禁止）。

3 Variationでの配分：

| Variation | 配分 | 方向性 |
|---|---|---|
| Trust | B + A（Editorial + 端正なProfessional Photography） | 王道、伝統と現代の中間 |
| Natural | B + softer C | 柔らかく人間的、写真も柔らかいTrim/Crop |
| Modern | B + Cを最も強く | Typography+非対称構成を最大化 |

Markup/Block構造は3 Variation共通。差はColor Token・Photography Treatment（Crop/Overlay濃度）・Typography Weightの範囲に留める（Order §20準拠）。

## 3. Asset Inventory（実在確認済み、016A時点）

`docs/research/visual-v3/assets/model-house/`（全8点、実在確認済み。画像内へのNavigation/Logo/H1/Button/電話番号/UI/説明文の焼き込みなし）:

| ファイル | 実寸 | 用途候補 | 解像度上の注意 |
|---|---|---|---|
| hero-office-city.png | 1536×494 | Hero背景（Wide Bleed） | Hero用として十分 |
| professional-sato-kenichi-wide.png | 1536×1024 | Professional Section（HOME第一候補） | 十分 |
| professional-sato-kenichi-portrait.png | 1024×1536 | Professional Single / Mobile代替Crop | 十分 |
| case-legal-documents.png | 512×260 | CASE二次項目・Thumbnail | **大きなFeature表示には解像度不足** |
| case-city-landscape.png | 528×260 | CASE二次項目・Thumbnail | 同上 |
| case-modern-office-building.png | 513×260 | CASE Feature候補だが実際は上記と同格 | **大きなFeature表示には解像度不足** |
| office-consultation-room.png | 484×260 | Section transition / Office Atmosphere | 帯状の控えめな使用に適する |
| accent-greenery.png | 484×260 | Accent / 余白装飾 | 同上 |

`docs/research/visual-v3/assets/icons/astrea/`（全10点、実在確認済み）: company / permit / inheritance / contract / result-company / result-check / result-consultation / price-yen / document / folder。Navy(#102A43) + Gold(#B99A5C) の線画、ViewBox 48×48。Windows由来の`*:Zone.Identifier`は本Order確認時点で0件（削除作業不要）。

**Finding（Asset）**: CASE用画像3点は全て同一の小さな実寸（500×260前後）で提供されており、うち1点を「大きなFeature Case」として扱う設計（§8参照）は、実装時に**目に見える解像度不足（ソフトフォーカス化）を招く**。次の対応が必要：(a) より高解像度のCASE用素材を追加調達する、(b) CASE Sectionは3点とも同格のThumbnail運用に留める、のいずれかをOwnerと合意する。本Mockupでは(a)を仮定した見え方を提示しているが、実データではないことをReportに明記する。

## 4. Hero v3

### 構成

`core/cover`相当（画像full-bleed）+ 左寄せ非対称Text Block。50/50 Columnには**しない**。

- Header: Hero上に統合、Transparent（Order §9候補「hero integrated header」を採用）。
- Eyebrow: 細いGold Rule + 事務所名（小さく、Letter-spacing広め）
- H1: 大きな明朝/Serif寄りWeight。「複雑な手続きを、／前へ進める力に。」（2行、Order指定文言）
- Supporting copy: 1〜3行
- Primary CTA（お問い合わせ）+ Secondary（電話番号、Icon付き）
- Editorial Metadata: 縦書きの補助ラベル（"ADMINISTRATIVE SCRIVENER OFFICE — SINCE 2011"相当、Fixture文言）
- Next section hint: "01 SERVICES" + 短い罫線

### Desktop寸法

- 高さ: 60–75vh目安（Mockupでは74vh、Contentの多さに応じ実装時調整）
- Text Column: 660px程度（8文字の日本語見出しが1行で収まる最小幅として実測確定。560pxでは「複雑な手続きを、」が2行に割れ、意図しない改行が発生することをMockup検証で確認した）
- H1 Font Size: `clamp(2.4rem, 4.6vw, 4rem)`

### 実装可能性の分類

| 要素 | 実現方法 |
|---|---|
| 背景画像Full Bleed | `core/cover`（`align:full`）+ Theme側でPage Templateのwide/full許可を確認（015Gで全幅化に制約があった実績あり、要再検証） |
| 非対称Text位置 | `core/cover`の`contentPosition`、または`core/group`のCustom Layout |
| 縦書きMetadata | Theme CSS（`writing-mode`）、新規Block不要 |
| Next Section Hint | Theme CSS + 既存Anchor |
| Header透過統合 | Theme CSS（Header Template Part側の背景/位置調整）、016B以降で要検証（Header/Hero一体化はTemplate構造に踏み込む可能性あり） |

### 016A-R1: Desktop Hero可読性改訂（Owner承認: Minor Revision）

H1サイズ・Hero高さ・全体構図は無変更のまま、Supporting CopyがPhotographyの建物領域と競合する問題を解消：`object-position`微調整（60/55→64/52%）、Supporting Copy `max-width`縮小（460→400px）、Text Column左側への薄い角度付きScrim（左端最大不透明度.40、Text Column終端手前で透明化）、Supporting Copy/Meta Labelへの白Text-shadow追加。強いGradient・白Box・H1縮小はいずれも行っていない。詳細は`docs/research/2026-08-29_astrea_visual_v3_016a_r1_report.md`§1参照。

## 5. Mobile Hero（375px基準、320pxまで成立）

**016A-R1でOwner承認の構図に更新（016A原本の「Photography上部背景＋Gradient Scrim＋Text重ね」案は不採用）。**

「写真右上ブリード＋Textプレート・オーバーラップ」構図：写真（画面右80%幅、高さ220–300pxClamp）をTop-right配置し、その直下にEyebrow/H1/Copy/CTAを収めた不透明な白背景Textプレートを、写真の下端へ55pxオーバーラップさせて重ねる。Textは写真の上ではなく不透明背景の上に乗るため、Scrim/Text-shadowなしで完全なコントラストを確保する。CTAは`order`でPrimary（お問い合わせ）→Secondary（電話）の優先順位を明示。

**実装上の既知の罠**: `.hero-content`に`margin-top`でオーバーラップ量を指定する際、親要素（`.hero`）が`overflow:visible`のままだとCSS Margin Collapsing（親子間の上マージン相殺）が発生し、Header・写真・Textが同一Y座標に重なって表示される。親へ`overflow:hidden`（Block Formatting Context確立）を指定して回避する。016B実装時に同種のOverlap構図を採用する場合は必ず同じ対策を行うこと。

Mockup実測で320px/375pxともHorizontal Overflow 0件、日本語H1「複雑な手続きを、／前へ進める力に。」は2行を維持（オーファン改行なし）。詳細は`docs/research/2026-08-29_astrea_visual_v3_016a_r1_report.md`§2参照。

## 6. Services — Editorial Numbered List

「01 / 02 / 03」の連番 + Icon（`assets/icons/astrea`使用、Trust想定はNavy stroke） + Title + Description + Link、を横並び1行のRowとして配置し、Row間をFine Ruleで区切る。Card（Border+Surface Box）を使わない。実装は既存の`astrea/service-list` Dynamic Blockの出力Markupへ、番号用の疑似要素またはData属性を追加する程度の変更で足りる可能性が高いが、確定は016B。

## 7. Results — 数字そのものをVisualにする

Full-width、濃色背景（Navy等）、3項目を縦Ruleで区切ったGrid。数字は`clamp(3.2rem, 6vw, 5.4rem)`程度、単位（社+/%/件+）はGold色で小さく添える。既存の`astrea/results-list` Dynamic Blockの出力へCSSのみで到達可能と見込む（015Cで既に`resultsNumber` Tokenが存在するため、Token値の見直しのみで大部分は再現できる可能性が高い）。

## 8. Professional — 大きな写真を主役に

Wide Photo（`professional-sato-kenichi-wide.png`、1536×1024）を画面の55–60%を占める非対称Blockとして配置し、右側にRole/Name（Serif、大きく）/Statement/Link。「名刺」「証明写真」に見えないよう、Cropは上半身が大きく写る構図を採用（提供素材がこの構図に合致することを確認済み）。既存の`astrea/representative` Dynamic BlockのMarkup変更が必要になる可能性が高く、016Bでの実装確認事項とする。

## 9. CASE — Photography-led Editorial（解像度制約あり、§3 Finding参照）

**016A-R1でOwner承認の構図に更新（016A原本の「Feature大画像＋Dark Gradient Overlay」案は解像度制約により不採用）。**

Feature/Secondaryの区別を「画像サイズの差」ではなく「Typography・Row Treatmentの差」で作る設計へ変更した。各CASEをNumber（Serif、Gold、Featureは2.3rem/Secondaryは1.7rem）／Body（Category・Serviceラベル＋Title＋Description＋Link）／Media（画像、原寸以下で表示——Featureは260×160px、Secondaryは190×120px、いずれもアップスケールなし）の3カラムRowとし、Row間を細い罫線で区切る。白背景・角丸・影のCard表現は使用しない（Servicesと共通する編集的リズム）。

**No-Photo Fallback**: 画像が無いCASEは`.case-media.is-empty`（Paper Warm背景＋Gold細線円のみ）へ自然に縮退し、Number/Rule/Label/Title/Description/Linkだけで完結する。Mockupでは意図的に1件（CASE 03）を無画像にして実演済み。

**High-Res Asset Specification**（将来より大きなFeature演出を行う場合のみ必要、現行R1では不要）: Feature用3:2または16:10・最小幅1600px、Secondary用2:1・最小幅800px。Text-overlay Safe Areaは現行R1設計（画像内へ文字を焼き込まない）では不要。詳細は`docs/research/2026-08-29_astrea_visual_v3_016a_r1_report.md`§3参照。

## 10. Section Rhythm

Hero（写真）→ Services（白、Editorial）→ Results（濃色Full Width）→ Professional（白系+写真）→ CASE（白、写真混在）→ ...（Price/FAQ/VOICE/CTAは016A範囲外、Order §16の例に準じ今後設計）。Order §17の例をそのままコピーせず、「Sectionごとに役割が視覚的に区別できること」を原則として維持する。

## 11. No-Photo Resilience（重要）

Photography要素（Hero背景・Professional写真・CASE画像）を全て仮に無い状態にしても、以下だけで成立する設計であることを確認する：

- Hero: 画像なし時はNavy/Surfaceの単色背景 + 同じEditorial Typography（Eyebrow/H1/Copy/CTA）。非対称Layoutは崩れるが、中央寄せまたは左寄せのText-onlyへ自然にFallbackできる。
- Services: 元々Photographyに依存しないSection。
- Results: 元々Photographyに依存しないSection。
- Professional: 写真なし時はName/Role/Statementのみのテキスト主体レイアウトへ縮退（015B/015Cで確立済みの「Placeholder禁止・非表示」方針を維持）。
- CASE: 元々画像任意。

目標スコア: Photographyあり=95、Photographyなし=85（Order §21）。本Mockupの段階では「Photography Impact」を優先的に検証しており、No-Photo Fallbackの実機検証は016B以降の実装時に行う。

## 12. Accessibility / Durability方針

- 画像への文字焼き込みなし（本文書§3で確認済み）。
- Alt Text: 内容説明型（Hero/Professional/CASEとも、装飾目的が強い場合はDecorative空Alt扱いも検討）。
- Contrast: Hero Text色（Navy `#102A43`）と背景（写真の明るい空領域）の組み合わせで実用上十分なコントラストになるよう、非対称構図で「写真の明るい部分にTextを置く」ことを設計原則にした（Overlay/Scrimへの依存を最小化）。Mobileでは写真が暗い/複雑な場合があるためGradient Scrimを併用。
- Motion: 新規Animation不要。Hover等は既存Button/Link相当のみ。
- 流行への耐性: Glassmorphism・過剰Shadow・Neumorphism等は使用していない。Editorial Typography + Photographyは意匠寿命が長いスタイルと判断。

## 13. Style Variation展開方針（016B以降で確定）

Hero/Results/Professionalの構造はVariation間で共通。差は:

- Overlay/Scrim濃度（Natural=薄め、Modern=Photographyをよりシャープにトリミング）
- Typography Weight（Modernはより太く/広く、Naturalはより軽く）
- Divider/Rule色（Trust=Navy、Natural=Warm Brown、Modern=Black）

完全な別Markupは作らない（Order §20準拠）。

## 14. 016B以降の実装候補ステップ（提案、未承認）

1. Hero（Header統合含む）
2. Results
3. Services（Editorial List化）
4. Professional
5. CASE
6. Section Rhythm全体の再調整（Price/FAQ/VOICE/CTA）
7. 3 Variation展開・Contrast再検証
8. No-Photo Fallback実機検証
9. Responsive全Breakpoint（320/375/768/1024/1440）実機検証
10. Accessibility Full Audit

いずれもOwner Approval後、個別Construction Orderとして分割実施することを推奨する（一括実装はRegression Risk過大と判断）。
