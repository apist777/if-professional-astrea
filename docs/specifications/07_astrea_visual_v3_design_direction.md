# 07. ASTREA Visual v3 — Design Direction (B+C)

- **Status**: PHASE 1 実装済み・REVISE対応済み（Header / Hero / First View、Construction 016B → 016B-R1でJapanese Typography問題を修正）。Phase 2（Services以降）はOwner Approval待ち、未実装。
- **Construction Order**: 016A → 016A-R1 → 016B（Phase 1実装）→ 016B-R1（Typography Revision）
- **Date**: 2026-08-29（016A/016A-R1）/ 2026-08-30（016B / 016B-R1）
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

## 15. 016B実装確定事項（Header / Hero / First View、Phase 1）

Construction Order 016BでHeader/Hero/First Viewのみを実際のASTREA FREE Theme（`theme/parts/header.html`・`theme/patterns/home-hero.php`・`theme.json`）へ実装した。以下は本書§4〜§12の設計意図に対する確定・divergenceの記録であり、Services/Results/Professional/CASE（Phase 2、本書§6〜9に設計済みだが未実装）には影響しない。

- **Header統合方針の変更（§4「Header透過統合」）**: 本書はOwner承認Mockupに基づき「Heroへ Header透過統合（写真の上にHeaderが重なる）」を候補としていたが、実装では**Headerを通常のDocument Flowに残し、Hero直上に隙間なく接続する透過・軽量なHeader**へ変更した。理由: Header（`theme/parts/header.html`）はHOME以外の全Template（Archive/Single/Office/Price/Contact/検索/404等、Hero自体が存在しないページ）で共有されるSite-wide Template Partであり、`position:absolute`やNegative Margin等のOverlap技法はHero非存在ページでのHeader崩壊リスクを伴う。Order §9自身が「Header透明化/Hero統合Header/軽量Header」を並列候補として許容していたため、Risk回避を優先しこの解釈を採用した。実機（`docs/research/screenshots/016b/home-upper-real-desktop-1440.png`）で見た目の一体感・軽量さは確保できていることを確認済み。将来、真の写真オーバーラップが必要と判断された場合はHOME専用Hero Patternの中にHeader相当のMarkupを取り込む設計変更が必要になる（既存Site-wide Header Template Partの流用では実現不可）。
- **Hero文字色の方針（§4・§12）**: 本書はMockup写真（明るい空領域）を前提にNavy文字＋最小限のScrimを想定していたが、出荷Default（`theme/patterns/home-hero.php`、写真未設定時）は**任意の将来の写真（明度・構図不明）に耐える必要がある**ため、Hero文字は常にWhite（`textColor:"base"`）＋`overlayColor:"contrast"`の暗いOverlayを標準とした（既存015G Hero・Closing CTAと同じ確立済み規約）。Owner Fixtureでは実写真（`hero-office-city.png`、明るいOffice/City背景）に対し`dimRatio:75`で実用コントラストを確保した。§11 No-Photo Resilienceの意図（写真無し=単色背景+同じEditorial Typography）はこの方針と完全に一致する。
- **Next Section Hint（§4）**: Mockupの「"01 SERVICES" + 罫線」ラベルは採用せず、**罫線のみ（Theme CSSの`::after`疑似要素、Markupなし）**とした。理由: `core/cover`は自身のInnerBlocks以外の追加Markup Siblingを許容せず（Gutenberg Block Validationのround-trip検証で不整合になる）、また出荷PatternはHeroの次に必ず"SERVICES"が来ることを前提にできない（既存installationは`setup-home.php`のPattern順序をOwnerが自由に並べ替えられる）。
- **H1のCopy契約（§4）**: MockupのH1文言「複雑な手続きを、／前へ進める力に。」はデザイン検証用のFixture Copyであり、出荷Pattern既定のH1は**Construction Order 011以来の契約通り事務所名（`office_name` Binding）を維持**した。Owner Fixtureの実機HOME（`http://localhost:8888/`）でも同様（H1=ASTREA行政書士事務所）。Mockupが検証したのはTypography Scale／Layout／Photography構成であり、H1の中身そのものの差し替えではない。
- **Mobile Hero（§5）**: 016A-R1承認構図（写真右上ブリード＋Textプレート・オーバーラップ、55pxオーバーラップ、`overflow:hidden`によるMargin Collapsing対策）をそのまま実装した。上記「Hero文字色の方針」により、プレートはR1 Mockupの白背景＋濃色文字ではなく**濃色（Contrastトークン）背景＋白文字**とした（写真の明度に依存しない配色）。実機確認: `docs/research/screenshots/016b/hero-real-mobile-375.png`・`hero-real-mobile-320.png`。Photography未設定時（デフォルトState）はプレート構図自体を使わず、コンパクトな単色Hero（写真スロット無し）へ自然にFallbackする（`:has(.wp-block-cover__image-background)`でScope）。
- **新規Design Token**: `accent`（Gold、Trust `#B99A5C` / Natural `#c2a46b` / Modern `#a8925c`）をpaletteへ追加（Icon Setの既存Gold `#B99A5C`との統一）。Kicker（016B時点ではEyebrowという名称、016B-R1で`astrea-hero-kicker`へ改称——§16参照）の装飾罫線のみに使用（WCAG計算の結果、明背景ではAA未達のためText用途には使わない）。`heroTitle`（`clamp(2.4rem, 4.6vw, 4rem)`）・`heroEyebrow`（`0.78rem`）を`settings.custom.typography`へ追加（既存`resultsNumber`と同じToken方式）。
- **実装ヒヤリハット**: Style Variation「選択済みSnapshot」（`wp_global_styles`投稿）が旧Token値のまま固定されており、theme.json/styles/*.jsonへの`accent`追加が実機に反映されない事象を発見（015B/015Cで既知のWordPress標準挙動と同種）。今回はPaletteが配列（`palette.theme`）として丸ごと上書き保持される一方、`custom`配下のObjectキー（例: `typography.heroTitle`）はTheme側の値とMergeされる、という**キーの型（Array vs Object）によって再同期の要否が異なる**ことを新たに確認した。該当Snapshot投稿のPalette配列へ`accent`を追記して復旧した。

Phase 2（Services/Results/Professional/CASE/Section Rhythm全体）は本Orderの範囲外であり、本書§6〜10の設計はOwner Approval後の次のConstruction Orderで実装する。

## 16. 016B-R1実装確定事項 — Japanese Typographyの恒久原則化

Construction Order 016B-R1（Owner Verdict: 016B REVISE）で、Owner実機確認により日本語Typographyの重大な問題（漢字の字形違和感、意味を無視した改行、H1=事務所名がVisual上の主役になっていた問題）が発見され、これを修正した。詳細は`docs/research/2026-08-30_construction_016b_r1_japanese_typography_report.md`参照。

**恒久原則（Visual v3のみならずASTREA全体に適用）**:

> **Japanese Typography is a first-class design requirement.**
> 日本語の字形（Glyph）・改行・文字組みは、Overflow/Validation等の機械的な合否基準と同格の「製品品質」として扱う。日本語ユーザーが「字形がおかしい」「改行が不自然」と一目で感じた時点で、その画面はどれだけデザインが洗練されていてもVisual Qualityとして不合格である。

この原則の直接の帰結として、以下を今後のConstruction Orderにも適用する:

- **新しいFont Stackを追加・変更する際は、必ず実在するJapanese Font名を、対象プラットフォーム（Windows/macOS/iOS/Android/Linux）ごとに列挙し、素の`serif`/`sans-serif`キーワード1つに依存しないこと。** Mac/Windows名前のFontだけを書いて安心しない——実際に稼働する環境（CI、実ユーザーのLinux/Android等）にそのFontが存在するかどうかは、`fc-list`等で機械的に確認できる場合は確認すること。
- **Chromium上で実際に選択されたFontを確認したい場合は、CSSの`computed font-family`ではなく、DevTools Protocolの`CSS.getPlatformFontsForNode`（実際に描画に使われた物理Font名を返す）を使うこと。** `getComputedStyle().fontFamily`はCSSで指定したFallback Chain の文字列をそのまま返すだけで、実際にどのFontが選ばれたかは分からない——本Orderの根本原因調査で確立した手法。
- **新しい見出し/コピー要素を追加する際は、`word-break:normal; line-break:strict; overflow-wrap:anywhere;`をText Wrapper（継承元）へ、`text-wrap:pretty`を各Text要素へ付与することを既定の作法とする**（016B-R1でHeroに適用したパターンをそのまま再利用可能）。
- **Semantic Heading LevelとVisual Sizeの分離は、この製品で繰り返し使われる正当な設計パターンである**（Construction 011のHero H1、016B-R1のKicker/Primary分離）。「見た目を大きくしたいから見出しレベルを上げる/下げる」という発想ではなく、Semantic構造（H1は1つ、文書の論理構造を表す）とVisual Style（CSSでいくらでも調整可能）を独立して設計すること。
- **完全な日本語の意味的改行（形態素単位のPhrase-wrap、例: BudouX）は、CSSのみ（`text-wrap:pretty`等）では実現できない既知の限界である。** 特定の複合語がViewport幅によって2行にまたがることは起こり得る（1文字だけが孤立するような最悪パターンは`text-wrap:pretty`で概ね回避できるが、意味単位の完全遵守は保証されない）。この限界を許容するか、JS製の形態素解析ライブラリ導入という別のConstruction Orderで対応するかは、都度Ownerの判断を仰ぐこと（新規JS依存の追加は本書の設計原則を超える意思決定であり、勝手に追加しない）。
