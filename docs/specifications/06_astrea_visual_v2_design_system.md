# 06 — ASTREA Visual v2 Design System

Construction Order 015A（RESEARCH / DESIGN SPECIFICATION ONLY）の成果物。ASTREA FREE v1のRC1 Functional Baselineを一切変更せず、Visual v2として実装する際の正本となる設計仕様。本書自体はProduct Codeではない。実装（015B以降）は本書と、対をなす調査Report（`docs/research/2026-08-29_astrea_visual_v2_design_research.md`）を参照して行う。

## 0. 適用範囲

士業・専門家（行政書士・税理士・社会保険労務士・司法書士・コンサルタント等）全般で成立する、汎用的なProfessional Visual Systemとして設計する。特定士業専用のデザインにはしない。

RC1のCPT Architecture・Core Data Model・Setup Architecture・SEO Architecture・Contact Architecture・Navigation Architecture・Core OFF方針・Deletion方針・FREE/PRO境界は一切変更しない。新しいSemantic Data（Postmeta等）の追加は行わない。既存データのみで成立させる。

## 1. Design Principles

1. **Trust before Decoration（装飾より信頼）** — 士業・専門家サイトの主目的は「安心して相談できそうか」を伝えること。装飾的な効果より、正確な情報提示・明確な階層・読みやすさを優先する。
2. **Content has Hierarchy（コンテンツには階層がある）** — 全ての情報を同じ強さで見せない。見出し・本文・補足・CTAの視覚的な強弱を常に区別する。
3. **Every Section has a Purpose（全SectionにVisual Roleを与える）** — HOME上の各Sectionは「並べただけ」にせず、Card・Metric・Testimonial等、内容の性質に合ったVisual Roleを持たせる。
4. **Professional but Approachable（専門的だが、親しみやすい）** — 硬すぎる権威主義的デザインでも、カジュアルすぎるデザインでもない、中間の「信頼できる専門家」の距離感を保つ。
5. **Japanese Text First（日本語前提で設計する）** — 欧文書体を基準に設計し、日本語を後から流し込むと破綻する（行間・文字間・折返し）。長い日本語文字列（長い事務所名、長い業務説明）を前提にレイアウトを検証する。
6. **Mobile is not a Reduced Desktop（Mobileは縮小版Desktopではない）** — Mobile Layoutは、Desktop Layoutの要素を単に縦に潰したものではなく、Mobile自体の情報設計として成立させる。
7. **Data Deserves Presentation（データはデータらしく見せる）** — RESULTSの数値、PRICEの金額、VOICEの声は、それぞれの情報種別にふさわしいVisual Componentで提示する（全て同じParagraphスタイルにしない）。
8. **No Empty Endings（余白で終わらせない）** — Single Page・Section末尾を「情報が尽きたので終わる」ままにせず、次の行動（CTA・関連情報）へ必ずつなげる。

## 2. Visual Direction

### 採用する方向性

- Strong Typography（明確なFont Size / Weightの差）
- Controlled Whitespace（余白は意図を持って配置する。空いているだけの余白にしない）
- Clear Information Hierarchy
- Grid / Card Layout（Archive・Section内の複数項目はCard化する）
- Strong Numeric Presentation（RESULTS等の数値は大きく見せる）
- Professional Photography Support（画像が入る前提のSlotを用意するが、画像必須にはしない）
- Restrained Border / Surface（薄い境界線・淡いSurface色で構造を示す。過剰な装飾はしない）
- Clear CTA（各Section・各Single Pageの終わりに明確な行動導線を置く）
- Mobile-first Structure
- Accessibility（コントラスト・Focus・見出し階層を犠牲にしない）
- Long Japanese Text Durability（長い日本語文字列で崩れないことを設計時点で検証する）

### 避ける方向性

- 過剰なGlassmorphism（半透明・ぼかし多用）
- 過剰なGradient
- 過剰なShadow（多重・濃いShadow）
- Animation依存（Animationが無いと成立しないUI）
- SaaS Dashboard風の見た目（士業サイトはSaaS Productではない）
- 角丸だらけ（Border Radiusの濫用）
- 装飾目的だけのIcon乱用
- Trendだけを追ったDesign（2024〜2026の一時的な流行に依存し、数年で陳腐化する表現）

2026〜2030にかけて比較的陳腐化しにくい、Typography・階層・余白중심のDesignを採用する。

## 3. Design Tokens

既存`theme/theme.json`（`version: 3`、`appearanceTools: true`）の構造・命名規約と互換性を持たせ、**破壊的な置き換えではなく拡張**として設計する。

### 3.1 Typography

既存の5段階Font Size（small/medium/large/x-large/xx-large）は維持し、Component側で組み合わせて使う。新規Font Sizeの追加は必要最小限とする。

| 用途 | 既存Token | 備考 |
|---|---|---|
| Hero見出し | `xx-large`（既存） | 変更なし |
| Section見出し（H2） | `x-large`（既存） | 現状より明確に本文と差をつける |
| Cardタイトル（H3） | `large`（既存） | |
| 本文 | `medium`（既存） | |
| 補足・Meta情報 | `small`（既存） | |
| **新規**：RESULTS等の大数字 | `results-number`（新規、目安 `clamp(2.5rem, 5vw, 4rem)`） | 既存`xx-large`よりさらに大きい、数値専用Token。既存Font Sizeスケールとは別枠の「表示用」Tokenとして追加する（本文用スケールを汚さない） |

Line Height：本文`1.6`（既存維持）、見出し`1.3`（既存維持）、Card内の短い見出しは`1.4`程度を許容。

Letter Spacing：日本語本文への負のLetter Spacingは基本的に付与しない（可読性低下のリスク）。英数字混じりの大数字（RESULTS等）にのみ、必要に応じて僅かなLetter Spacing調整を許容する。

Font Family：既存の`heading`（Georgia等の欧文Serif＋日本語Mincho）／`body`（System UI＋日本語Gothic）の2系統を維持する。Variationごとの差別化はFont Familyの選定自体（Trust/Natural/Modernで既に異なるFont Familyを持つ）に委ね、v2 Component側で新たなFont Familyは追加しない。

### 3.2 Spacing / Section Spacing / Component Gap

既存の4段階Spacing（small 1rem / medium 2rem / large 3.5rem / x-large 6rem）を維持しつつ、**Section間の使い分けルール**を明文化する（新規Token追加は最小限）。

| 用途 | Token | 備考 |
|---|---|---|
| Section間の余白（HOME上の大Section区切り） | `x-large`（既存） | 現状も使われているが、Sectionごとの背景差（後述Surface）と併用して階層を強める |
| Section内、見出しとCard Gridの間 | `medium`（既存） | |
| Card同士のGap（Grid Gap） | `medium`（既存） | |
| Cardの内側Padding | `medium`（既存） | |
| Single Page内、Block同士の間 | `medium`〜`large`（既存） | |

新規追加候補：`component-gap-small`（目安 `0.5rem`）— Card内のLabelと値の間等、既存`small`（1rem）よりさらに狭い間隔が必要な箇所向け。既存4段階に対する下位追加として、既存Tokenへの影響を与えない形で追加する。

### 3.3 Content Width / Wide Width

既存`contentSize: 720px` / `wideSize: 1200px`は維持する。Card Grid等、複数列Layoutは`wideSize`（1200px）を基準に組み立てる。

### 3.4 Border / Surface / Border Radius

現状、`surface`色（`#f2f4f7`相当、Variationごとに異なる値）は存在するが、Border Tokenは未定義。以下を新規に定義する。

| Token | 値の目安 | 用途 |
|---|---|---|
| `border-color`（新規、Style Variationごとに定義） | Primaryより薄い、Secondaryに近いTone | Card境界線、区切り線 |
| `border-width` | `1px`固定 | 太い枠線は使わない（Restrained Border方針） |
| `border-radius-sm`（新規） | `4px` | Input、小さいBadge |
| `border-radius-md`（新規、既存Buttonの`2px`から見直し） | `6px` | Card、Button |
| Surfaceの用途拡張 | 既存`surface`色を、Card背景・Section背景の切り替えに活用 | 「角丸だらけ」を避けつつ、色面の切り替えでSection階層を作る |

既存Buttonの`border.radius: 2px`は、v2で`border-radius-md`（6px程度）へ引き上げる方向を推奨するが、「角丸だらけ」を避ける方針と矛盾しないよう、Card等も含め統一的に控えめな角丸に留める。

### 3.5 Shadow Policy

**原則Shadowを使わない。** 階層表現はSurface色の切り替えとBorderで行う。やむを得ずCard等に立体感が必要な場合も、`0 1px 2px rgba(0,0,0,0.05)`程度の極めて薄いShadowに限定し、複数レイヤーのShadowやHover時の派手なShadow拡大は行わない（過剰なShadow回避の方針と一致させる）。

### 3.6 Button

既存Buttonの色・Textカラーは維持しつつ、以下を明確化する。

- Primary Button（既存の電話・CTA相当）：塗りつぶし、`border-radius-md`
- Secondary Button（既存の「お問い合わせはこちら」相当）：Outline形式、既存のまま維持
- Button内Padding：現状より横方向にやや余裕を持たせる（既存`appearanceTools`のButton Padding設定を活用、新規Tokenは不要）

### 3.7 Focus

既存のBrowser標準Focus Outlineを尊重する（Construction 012/RC1で確認済みのAccessibility要件を後退させない）。Focus Ring色をCustomizeする場合も、コントラスト比を維持し、`outline: none`による抑制は行わない。

## 4. Header v2 Specification

### Desktop

Office Identity（事務所名）／Primary Navigation／Primary CTA（電話・お問い合わせ）を、**単一のFlex行に並べるだけでなく、視覚的にグループ化された1つのHeaderとして成立させる**。

- Office Identity領域とNavigation+CTA領域の間に、明確な区切り（Padding差、または薄いBorder）を設ける。
- CTA（電話番号ボタン）は、Navigationと同じ高さ・同じ視覚的重みの列に配置し、「左下に単独で浮いている」ような構造を避ける（既存Markupは既にFlexで同一行にあるため、根本原因はVisual Weightの均一さにある。Weight差・グルーピングの視覚処理で解決する）。
- 長い事務所名（Construction 013 Finding 1で確認したSTRESS Fixture相当）でも、Navigation・CTAを圧迫しないよう、Office Identity領域に適切なMax WidthまたはFont Size調整を許容する。

### Mobile

Office Name／Menu（Overlay Menuトグル）／Primary Action（電話等）が、崩れず1行〜2行で成立すること。既存の`core/navigation`の`overlayMenu: mobile`設定を維持しつつ、CTAとMenuトグルの視覚的優先順位を明確にする。

### 実装上の制約

Markup構造自体（`core/navigation`、Block Bindings経由のOffice Profile参照）は変更しない。Visual v2は主にStyle（theme.json Styles、Block単位のStyle属性）と、必要最小限のLayout属性調整（`layout.type`、`justifyContent`等）で実現する。

## 5. HOME v2 Specification

情報の順序（Hero → Services → CASE → RESULTS → Professional → Price → FAQ → VOICE → Flow → CTA）は維持する。各Sectionに以下のVisual Roleを与える。

| Section | 現状 | v2 Visual Role |
|---|---|---|
| Hero | 単一Group、テキスト+CTA2つ | 維持（既に一定の階層あり）。長い事務所名時のCTA圧迫を防ぐLayout調整のみ |
| Services（取扱業務） | 縦積みのTitle+説明 | **Card Grid**（2〜3列、Desktop） |
| CASE（対応事例） | 縦積み | **Case Study Card**（Title、要約、Single詳細へのリンクを明示） |
| RESULTS（実績） | 縦積みTextのみ | **Large Numeric Metrics**（3.4節の`results-number` Tokenを使用した数字強調Layout） |
| Professional（専門家紹介） | 縦積みName+説明 | **Photo + Profile**（Featured Image Slot、無ければIniital/Placeholder） |
| Price（料金） | 縦積みTitle+金額 | **Price Card / Structured List**（金額を視覚的に強調） |
| FAQ | 縦積みQ&A | Accordion化は新規JS依存となるため、v2では「Q/Aの視覚的差別化（QにBadge等）」に留める案を基本とし、Accordion化は別途Regression Risk（後述）を評価した上で判断する |
| VOICE（お客様の声） | 縦積みBlockquote | **Testimonial Card**（引用符・Attribution・Card境界） |
| Flow（ご相談の流れ） | 既存の番号付きList | 維持、Numbered Stepsとして強化（既に近い形） |
| CTA | 既存の高コントラストSection | 維持、強化不要（既に目的に合っている） |

## 6. Archive v2 Specification

Archiveを「投稿一覧」ではなく「Professional SiteのListing Page」として設計する。

| Archive | v2 Listing形式 |
|---|---|
| Service | Service Cards（Grid） |
| Professional | Professional Cards（Photo+Name+Title） |
| CASE | Case Study Cards |
| VOICE | Testimonial-style Listing |
| FAQ | FAQ-specific Listing（Q/Aの視覚差別化） |

### "Archives:" の除去について

WordPress標準の`get_the_archive_title()`は、投稿タイプArchiveに対して既定で「Archive: %s」（Localeにより「アーカイブ: %s」等）という接頭辞を付与する。この接頭辞はWordPress Core自身の挙動であり、ASTREA固有の実装ではない。

安全な除去方法として、`get_the_archive_title` Filter Hookを使い、投稿タイプArchiveの場合のみ接頭辞を除いたTitle（`post_type_archive_title()`相当）を返すよう調整する案を提案する。この方法は：

- WordPress Core・Gutenbergの動作を変更しない（標準Filter APIの使用）
- `<title>`タグ生成（`wp_get_document_title()`等、SEO関連）には影響を与えない設計にする（Filterは`the_archive_title()`が呼ばれるTemplate側の見出し表示にのみ適用し、`document_title_parts`等のSEO関連Filterとは分離する）
- Theme Check等のWordPress.org Guidelineに抵触しない（Filter APIの正規利用）

具体的な実装（Filter追加箇所）は015B以降のConstruction Orderで確定する。本書では「安全な方法が存在する」ことの確認に留める。

## 7. Single v2 Specification

Service / Professional / CASE等のSingle Templateに、以下の構造を設計する。

1. **Header Area**：Breadcrumb、Title（既存維持）
2. **Content Area**：既存のPost Content（既存データのみ）
3. **Related / Next Action**：同じPost Typeの他の項目への導線（例：他の取扱業務、他の専門家）。新しいDataは追加せず、既存の`get_published_posts()`相当のQueryを活用したBlock構成に留める
4. **Closing CTA**：お問い合わせへの明確な導線（既存のContact Page / Contact Form Blockへのリンク・Buttonを配置）

現状、Service Single等はTitle+本文のみで終わっており、Footerとの間に意味のない余白が生じている（Construction 015A監査で実機確認）。Closing CTAの追加により、この余白を意味のあるSectionへ転換する。

新しいSemantic Data（Postmeta等）は追加しない。既存のPost Type・Taxonomy・既存Fieldのみで構成する。

## 8. Professional v2 Specification

Featured Imageがある場合：Photo・Name・Qualification・Title・Biography・Career/Education/Affiliation・CTAが自然に構成されるLayoutを設計する（既存のProfessional Profile Fieldのみを使用、新規Field追加は無し）。

Featured Imageが無い場合でも破綻しないよう、Photo Slotには：

- Placeholder（Initial文字によるAvatar等、装飾目的のIcon乱用にはならない範囲）
- またはPhoto無し用のText中心Layoutへの自動切り替え

のいずれかを採用する。具体的な実装方式（CSSのみでの切り替えか、Templateレベルでの条件分岐か）は015D以降で確定する。

## 9. Results v2 Specification

既存Result Data（Title/Content、例：「相談実績500件以上」）を、大きな数字として視覚化するComponentを設計する。

- 数値部分（「500+」「98%」等）と、説明文（「相談実績」「建設業許可取得率」等）を、Design Tokenの`results-number`（3.1節）で分離して強調する。
- **データの意味を勝手に推測しない**：現在のResult Dataは自由入力のTitle/Content形式であり、「数値」と「説明」を機械的に分離するParsingロジックは追加しない（Post v1 Backlog化されている構造変更は行わない）。v2では、既存のTitle全体を大きく見せるVisual Treatment（Title自体を大きなFont Sizeで表示する）に留める案を基本とする。

## 10. Price v2 Specification

既存Price情報（Title、`astrea_price_amount`、`astrea_price_group`）のみを使用し、Price CardまたはStructured Listを設計する。

- `astrea_price_group`によるSection Grouping（既存Meta Fieldの活用、新規Field追加なし）
- Offer Schema（JSON-LD）の追加は禁止（Decision 026のFAQPage/Offer/ProfessionalService自動生成禁止方針を維持）
- Price GroupのPost v1 Backlog（Finding 8）自体の実装着手はv2 Visual Designの対象外。v2では「見た目の改善」のみを行い、「Groupごとの構造的な表示切り替え機能」は着手しない

## 11. Voice v2 Specification

VoiceをQuote（引用符付きBlockquote）・Attribution（`display_name`）・適切なReadable Spacingを持つTestimonial Componentとして設計する。

架空のRating／Star評価等、既存Dataに存在しない情報の追加は行わない。

## 12. Office Hours v2 Specification

営業時間（曜日・時間・休業）を、視覚的に読めるTable形式またはDefinition List形式へ改善する案を作る。

- Desktop：曜日と時間を横並びのTable、または`dl/dt/dd`によるDefinition Layout
- Mobile：横幅不足時は縦積みへ自然に切り替わるResponsive設計とし、Table自体がHorizontal Scrollを必要とする構造は避ける
- 休業日の表現（現状「休業」というText表示）は維持しつつ、視覚的にグレーアウト等で区別する

## 13. Contact Form v2 Specification

既存Field構成（お名前・メールアドレス・電話番号・件名・お問い合わせ内容）は変更しない。改善対象はVisual Styleのみ。

- Input Width：Formの最大幅を適切に制限し、Desktopで不自然に細い/広いInputを避ける
- Input Height / Spacing：Padding・Line Heightを調整し、タップ領域を確保する
- Label：Requiredな項目に明確な視覚的指標（既存の`*`表示を、Badge等より視認性の高い形へ改善する余地あり）
- Textarea：適切な初期高さ
- Focus / Error：既存のAccessibility要件（Focus可視性）を維持しながら、Formとして違和感のないStyleを適用
- Button：3.6節のButton Tokenに準拠
- Form Container：Card状のContainer（Border、Surface背景）でForm全体をひとまとまりに見せる

## 14. Footer v2 Specification

Footer自体を派手にしない。Office Identity・Address・Phone・Navigationの情報構造は維持しつつ、余白・Typographyの微調整でMain ContentとのVisual Balanceを取る。Main Content側（特にSingle Page）のVisual Densityが低いことがFooterとの不均衡の主因であるため（7節参照）、Footer自体を強調するより、Main Content側の改善を優先する。

## 15. Style Variation Compatibility

Trust／Natural／Modernの3 Variationは、Markupを完全に共通のまま維持する。Visual v2の全Component（Header、Card、Testimonial、Results Metric等）は、Style（Color、Font、Border、Radius等のToken値のみ）で3 Variationの個性を出し、Variationごとの別Template・別Markupは作らない。

これは、RC1で確認済みの「3 VariationがMarkup共通で成立する」という既存Architecture上の制約と一致する。

## 16. Photography Policy

標準Theme（画像無し状態）でも完成して見えることを最優先とする。画像が追加された場合に、ProfessionalなWeb Siteへ進化できるImage Slotを用意する。

| 箇所 | 画像方針 |
|---|---|
| Hero | 画像無し版を標準とする（現状維持）。将来的に背景画像Slotを追加する余地はあるが、v2では必須としない |
| Professional | Featured Image Slotを用意（8節）。正方形または縦長の一定Aspect Ratioを推奨し、画像の縦横比バラつきによるGrid崩れを防ぐ |
| CASE | 任意の画像Slot（Featured Image）を許容するが、無くても成立するCard Designを基本とする |

Demoでは高品質な写真を使用する想定だが、Demo専用のArchitecture（Demo限定のBlock、Demo限定のTemplate）は作らない。標準Theme ArchitectureのままDemoも構築する。

## 17. FREE / PRO Boundary

FREEの標準Theme自体を意図的に見劣りさせてPROへ誘導する設計は禁止する。FREEのみでも「完成したサイト」に見えることを目標とする（Install直後80点、Setup後90点、本格Demo構築時95点、という本Orderの目標値を参照）。

PROは将来、以下で価値を出す方向とする（v2 Visual Designの対象外、あくまで境界の確認）。

- Profession-specific Starter（業種別の初期Fixture・Copy）
- Setup Automation（本Order Part 20のDemo Strategyとも関連）
- Advanced Layout（FREEのCard/Grid Systemを拡張した高度なLayout Option）

## 18. Regression Risk Summary

詳細は調査Report（`docs/research/2026-08-29_astrea_visual_v2_design_research.md`）の該当節を参照。本書では設計仕様として、各v2 Componentが以下を壊さないことを実装条件とする。

- Block Validation（新規Block Markup構造がGutenbergの検証を通ること）
- Dynamic Block（`astrea/*` BlockのServer-side Render・Editor Placeholderの互換性）
- Core OFF（Visual v2 StyleはTheme側のみで完結させ、Core非活性時にStyleが破綻しないこと）
- Responsive／Accessibility（既存確認済み水準を後退させない）
- Theme Check／WordPress.org Rules
- 既存ユーザーContent・既存Setup生成HOMEの上書き禁止（Migrationによる強制上書きは行わない）
