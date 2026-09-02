# Construction Order 016E-R3 実装報告 — HOME Section Composition Finalization

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-02 20:45 JST
- **End**: 2026-09-02 22:51 JST
- **Commit**: `0cf92ce`（CI green確認済み: PHPUnit / Theme-Core independence smoke test / PHP syntax+Coding Standards、全Job成功）
- **Measurement Audit**: `docs/research/2026-09-02_016e-r3_composition_measurements.md`

## 1. Root Causes

Owner指摘「見出し ↓ 大きな空白 ↓ コンテンツ、という構図がHOME全体で繰り返されている」を計測した結果、根本原因は**数値（margin）の問題ではなく、色（Composition）の問題**だった。

CASE/RESULTS/VOICEは、Section HeadingがSurface/Dark BandというBody自身の背景色を持たず「白背景」のまま配置されていたため、Heading→Body間のGap自体は他Sectionと同じ56pxでも、**色の境界線がHeadingとBodyの間に生まれ、視覚的に別Sectionとして分離して見えていた**。単純なmargin調整では解決できない構造的な問題であり、Order冒頭が明示した通り「Spatial Systemを作り直す」のではなく「HeadingをBodyと同じ視覚Panelに統合する」ことが必要だった。

FAQは、右半分の死角が016E-R2の720px→960px拡幅だけでは解消しきれておらず、真の2-column Editorial構成への変更が必要だった。

## 2. Composition Rules 新規/変更

- **統合Panel Rule**（CASE/RESULTS/VOICE）: Section Heading自身にBodyと同じ`background`を与え、Heading側の`margin-bottom`を0にし、代わりに`padding-top`/`padding-bottom`（塗りつぶされる領域）で内部の呼吸を確保する。これによりHeading要素とBody要素は物理的に別Boxのまま、視覚的には継ぎ目のない1枚のPanelとして成立する。
- **Heading-Gap Token調整**: `--astrea-v3-heading-gap`を`large`(56px)→`2.5rem`(40px)へ。個別Section毎の数値ではなく、Services/Professional/Price/Flowが共有する1つのTokenを調整した（Order §1が禁止する「個別margin施工」を回避）。
- **FAQ 2-Column Wrapper**: `astrea/faq-list` Dynamic Blockの`render_faq_list_block()`に、`heading`属性が設定されている場合（=HOME-teaser Context）にのみ、Heading+ListをラップするDivを追加（新規CSS Grid: 左260-380px=Kicker+Heading、右1fr=既存FAQ list）。既存のFAQ Archiveページ（`heading`未設定）は完全に無影響であることを実機確認した。

## 3. Section別変更理由

### SERVICE
Heading→Cards間のGapを56px→40pxへ（Token変更）。3-column構造・Wide Grid幅は無変更（LOCK）。

### CASE
Section Headingへ`background:surface`を付与し、Body（3-column Cards）と同じGray Surface Panelへ統合。3-column構造・Numbering・Image Fallback等は無変更（LOCK）。

### RESULTS
Section Headingへ`background:contrast`+`color:base`（白文字）を付与し、Dark Bandと統合。KickerはGoldのまま（Dark背景で十分なContrastを確認）、Trailing Ruleを`rgba(255,255,255,.35)`へ変更しDark背景での可読性を確保。Trust/Natural/Modern全てで実機確認済み。

### PROFESSIONAL
016E-R1で確立したPhoto+Bio構造は無変更。Heading-Gap Token調整の恩恵のみ受ける形でRESULTS→Professionalの遷移がより自然になったことを確認（RESULTS自体がPanel化されたことで「実績が終わり、新しい章が始まった」という認識がより明確になった）。

### PRICE
Heading→4-column間のGapを40pxへ。4-column構造・Price Typography・Tax-note分離（016E-R1で解決済み）は無変更。

### PRICE → CTA
実測24pxのGapは既に「Price直後にCTAが直結」して見える状態であり、追加調整は行っていない。

### FAQ
2-column Editorial構成（左:Kicker+Heading、右:既存FAQ list）へ変更。新規Accordion JS・新規CPT・Schema変更は一切行っていない。既存FAQ Archiveページは無影響。

### VOICE
CASE同様、Section Headingへ`background:surface`を付与しBodyと統合。3-column構造は無変更。

### FLOW
Section自身のPadding(`large`→`medium`)を縮小し、Voice→Flow→Footerの間で「孤立した巨大な白地」に見える度合いを緩和。Wide Grid使用（016E-R2で確立、Content幅93%以上）は維持。

## 4. Responsive結果（§16）

| 幅 | Horizontal Overflow |
|---|---|
| 1920 | 0px |
| 1440 | 0px |
| 1366 | 0px |
| 1024 | 0px |
| 768 | 0px |
| 375 | 0px（実装中に一時81pxのRegressionを発見・修正、詳細は§7参照） |
| 320 | 0px（同上、一時136px） |

## 5. Variation結果

Trust/Natural/Modern 3件とも、CASE/Results/VoiceのPanel統合・FAQ 2-column・Heading-Gap調整が正常動作することを実機確認、Trustへ復元済み。

## 6. Core OFF/ON結果

Core OFF→ON、HTTP 200・Fatal無しを確認。

## 7. 発見した実バグ（正直な報告）

FAQ 2-column Gridの`grid-template-columns:1fr`（782px以下向けMobile Override）を、既存の782pxメディアクエリブロックの先頭付近に追記した結果、同じ詳細度の無条件ベースルール（Stylesheet後方）に負け、375px幅で81px・320px幅で136pxの実横Overflow（FAQ itemが68px幅まで圧縮）が発生した。これは**Spec 07番§21に既に文書化されていた「新しいResponsive OverrideはStylesheet末尾に配置する」という恒久原則を、実装時に見落として再発させたもの**——同じ罠に2度目に落ちたことを正直に報告する。Stylesheet末尾へ移動し解消、7幅全てOverflow 0pxを再確認した。

## 8. Test結果

- PHPUnit: 397/397 Pass
- PHPCS: 変更2ファイル（`faq-list-block.php`/`home-flow.php`）0 Errors/0 Warnings
- Theme Check: 本Orderでは再実行せず（新規Enqueue/Text Domain等の変更なし、前回Baseline継続採用）
- Hero Collision: `hero-safety-verify.js`で18ケース全てSAFE（再検証済み、Heroは無変更）
- 長い事務所名（24文字超）: Header/Hero双方Overflow無し、7幅すべて確認
- CASE Scenario A（Owner Baseline）: 開始時・終了時とも維持を確認
- Professional Photo: 016E-R1確立の構造、無変更

## 9. Screenshot Evidence

`docs/research/screenshots/016e-r3/`に21枚（Desktop Primary 3幅+Responsive Safety 4幅+Section Close-up 7枚(1440px)+Before/After比較7枚）。

## 10. 変更ファイル

- `theme/theme.json`（CASE/Results/Voice Panel統合CSS、Heading-Gap Token調整、FAQ 2-column Grid CSS）
- `core/includes/faq-list-block.php`（heading設定時のみ適用されるWrapper div追加）
- `theme/patterns/home-flow.php`（Section Padding調整）

Fixtureデータ（Page ID 1914のFlow Pattern padding属性）はGit管理外のWordPress DB状態であり、Before/After比較用のFixture切替はすべて事前Backup→検証→Owner Baseline復元→復元確認の手順を踏んだ。

## 11. 未解決の視覚的所感（正直な報告）

- Flowの「孤立した白地」感は緩和したが、完全な解消（例: Surface背景の追加）はOrder §16「色を増やさない」制約により見送った。Section自体のPadding縮小のみで対応した。
- CASE→Resultsの境界（234px）は他境界(96px)より広いままだが、2つの強い色面（Gray Surface→Dark Contrast）が連続するため、視覚的な呼吸として妥当と判断し据え置いた。

## 12. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/Construction 016Fへの自動進行・新機能追加は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
