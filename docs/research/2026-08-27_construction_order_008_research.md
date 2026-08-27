# CONSTRUCTION ORDER 008 — Design System / Theme表示基盤 — 事前調査

**種別:** PRE-CONSTRUCTION RESEARCH（調査のみ。製品コード変更なし）
**Status:** RESEARCH COMPLETE
**関連:** AGENTS.md §6, 00仕様書, 01仕様書§8, 02仕様書§5-14・19-26, 04文書 Decision 010・013・014・017・018・020・021, 05 Baseline §3・5・9・14・17

---

## 1. 現状棚卸し

### 1.1 Theme側実装（現状は骨格のみ）

```
theme/
  functions.php        — theme supports, Pattern category登録, Core検出, Core推奨Notice
  style.css             — メタ情報のみ。装飾CSSは意図的に置かない（コメントで明記済み）
  theme.json             — settings.appearanceTools + color paletteが base/contrast の2色のみ。
                            typography/spacing/layout等の設定は未整備。styles/ (Style Variation)ディレクトリ無し
  parts/header.html      — Group + office_nameバインドのParagraphのみ。Navigationブロック無し、Footer parts無し
  templates/
    index.html                     — Header + Breadcrumb + post-title + post-content（投稿/固定ページの汎用フォールバック）
    archive-astrea_service.html    — Header + Breadcrumb + Query Loop（Service）
    single-astrea_service.html     — （未読だが同系統と推定、要個別確認は不要——命名規則から自明）
    archive-astrea_professional.html
    archive-astrea_faq.html
    taxonomy-astrea_faq_category.html
  patterns/
    price-list.php         — astrea/price-list Dynamic Blockを1個埋め込むだけ
    contact-form.php       — astrea/contact-form Dynamic Blockを1個埋め込むだけ
```

**front-page.html、page.html、footer template part、Hero/Trust/CTA等のPatternは一切存在しない。** Design SystemはBaseline策定時点から「実装フェーズで設計する」と明記されたP0外事項であり（05 Baseline §17項目4）、Construction 002-007はいずれもCore側データ機能の実装が中心で、Theme側の見た目はほぼ手つかずのまま今日に至っている。008は事実上グリーンフィールドに近い。

### 1.2 Core → Theme 表示連携の現状

| 連携方式 | 実装済み箇所 | 対応データ |
|---|---|---|
| Block Bindings（`astrea-core/office-profile`） | `core/includes/block-bindings.php` | `office_name` / `address` / `phone`（スカラー値のみ。Decision 013どおり構造化データは対象外） |
| Dynamic Block | `astrea/price-list`, `astrea/contact-form`, `astrea/breadcrumb` | Price一覧、問い合わせフォーム、パンくず |
| Query Loop（`core/query` + `postType`） | 各Archiveテンプレート | Service, Professional Profile, FAQ（いずれも`publicly_queryable=true`のCPT） |
| JSON-LD（`wp_head`直接出力、表示連携ではない） | `core/includes/seo-structured-data.php` | Organization/Person/BreadcrumbList |

**現時点でTheme向けに用意されているBlock Bindingsは3キーのみ**（`office_name`/`address`/`phone`）。営業時間・SNSリンクは構造化データのためBindings非対応（Decision 013の想定どおり、Dynamic Block化が必要）。Professional Profile・Service・Price・FAQをHOME等の複数箇所で「一覧」として再利用する経路は、現状Query LoopまたはDynamic Blockのみで、単一項目の差し込み（例：代表者1名の写真のみをHeroに出す）用のBindings/Dynamic Blockはまだ無い。

### 1.3 Core側データ機能の実装状況（008のPattern設計が依拠できる範囲の確定）

02仕様書§6-14が挙げるHOME構成要素について、対応するCoreデータの実装状況を確認した。

| 02仕様書の要素 | Core実装状況 | 008で参照可能なデータ |
|---|---|---|
| Hero | 専用データ無し | Office Profile（事務所名・電話・住所）を流用 |
| Trust | 専用データ無し | Professional Profile（資格・肩書等）、Office Profileを流用 |
| Services | **実装済み**（`astrea_service` CPT, Construction 004） | `Service\get_services()` |
| Profile | **実装済み**（`astrea_professional` CPT, 0..N, Construction 003/003A） | `ProfessionalProfile\get_profiles()` |
| Flow | **未実装**（Core側にFlow専用データ機能が存在しない） | 無し |
| Price | **実装済み**（`astrea_price` CPT, Construction 004） | `Price\get_prices()` |
| FAQ | **実装済み**（`astrea_faq` CPT + Taxonomy, Construction 004） | `Faq\get_faqs()` 等 |
| CASE / RESULTS / VOICE | **未実装** | 無し |
| Access | 住所・営業時間・臨時休業は**実装済み**（Office Profile）。最寄駅・徒歩時間・駐車場等のACCESS固有情報は**未実装**（Decision 022で責任分離のみ確定、データ機能は未着手） | Office Profileの住所・営業時間のみ |
| CTA | 専用データ（相談方法等）は**未実装**（Decision 022で「別責任」と明記されたのみ） | Office Profileの電話番号、および問い合わせページへのリンク（Construction 007 Setupが生成/確認する到達可能な問い合わせページ） |

**これは008の施工範囲を直接左右する重要な発見である。** 「既存Coreデータをどう表示するか」という調査の問いに対し、Flow・CASE/RESULTS/VOICE・ACCESS固有情報・CTA固有データについては「既存Coreデータ」自体が存在しない。詳細は §4 と §14（要確認事項）で扱う。

---

## 2. Baseline上のDesign System / Responsive / Accessibility / Performance要件の整理

- **Design System（02§5）：** 「無料だから多少ダサくてもよい」を明確に禁止。Patternは数を目的にせず少数精鋭、採用するものは一級品にする。コンテンツ数をデザイン都合で強制しない（「3件登録しないと成立しない」等の禁止、02§5）。
- **Responsive（Baseline§9 / 02§23）：** PCデザインの縮小ではなく、画面サイズごとに情報優先順位・レイアウトを再設計する。端末固有CSSの増殖を避ける。
- **Accessibility（Baseline§9 / 02§24 / Decision 017）：** 追加オプションではなく標準品質。Keyboard、Focus表示、Semantic HTML、見出し階層、Form Label、alt、Contrast、Skip Link、適切なARIA、`prefers-reduced-motion`を品質要件とする。完全準拠の無条件保証はしないが品質を下げる口実にしない。責任分担：Theme＝表示・操作上のAccessibility、Core＝Form・管理画面のAccessibility（Decision 017）。
- **Performance（Baseline§9 / 02§25）：** 高速化Pluginを前提にせず、Theme+Core標準構成自体を軽量にする。未使用の外部サービス処理を読み込まない。Core Web Vitals（LCP/INP/CLS）を確認するが100点ゲームにしない。

008はこれら4つを「後から足すオプション」ではなく、Pattern設計・theme.json設計そのものに最初から組み込む必要がある。

---

## 3. HOMEをPatternの組み合わせとして成立させるArchitecture

02仕様書§6・Baseline§5が既に「固定テンプレートではなくPatternの組み合わせ」という方針を確定済みである。008の仕事はこの具体的な実装機構を設計することに限られる。

**推奨Architecture：**

- `front-page.html` テンプレートは、各Patternの**土台となる最小限の構造**（Header/Footer template part呼び出し + `<main>`）のみを持つ。中身はテンプレート自体にハードコードしない。
- HOMEの実際の構成は、ユーザーがSite Editor / Post Editorで**Patternを挿入して組み立てる**（WordPress標準の「Patternを挿入する」体験そのもの。ASTREA独自のPage Builder UIは作らない — 02§5・AGENTS.md全体の「独自Framework禁止」原則と直接一致）。
- Construction 007のSetup機能（基本ページ生成）は、事務所概要・料金・お問い合わせの3ページのみを生成し、HOME自体の内容は生成しない（Decision 027、007実装済み）。HOMEへのPattern構成はDesign System領域として008が担当する—ただし**008は「推奨されるPatternの組み合わせ例」をPatternカテゴリとして提供するに留め、HOME自体を自動組み立てはしない**（Decision 016「ページ等を勝手に大量生成しない」の精神と、02§6「すべてを使用する必要はない」というユーザー選択の原則に合致）。
- 各セクション用Pattern（Hero/Trust/Services/Profile/Flow/Price/FAQ/CTA等）は、それぞれ独立した`register_block_pattern()`として登録し、`astrea`カテゴリの中でも用途別にサブカテゴリ的な命名規則（例：`Title: HOME - Hero (Trust)`）を与え、Pattern一覧画面で見つけやすくする。

この設計は、Core側の既存原則「Patternは業務データの正本にしない」（Baseline§3, Decision 002）とも整合する：Pattern挿入後のBlock markupはページごとに複製されるため、Core側の値変更（例：Service名の変更）を自動反映できるのは、Pattern内部が引き続きQuery Loop / Dynamic Block / Block Bindingsを使っている場合のみである。008のPatternは「レイアウトの型」を提供し、値の同期は既存のCore連携機構（§1.2）に委ねる。

---

## 4. 各セクションの表示方式（Coreデータの有無別）

### 4.1 Coreデータが既に存在するセクション（Block Bindings / Dynamic Block / Query Loopで接続）

| セクション | 接続方式 | 備考 |
|---|---|---|
| Services（HOME内Teaser・専用Archive） | Query Loop（`postType: astrea_service`） | 既存Archiveと同じ機構。HOME用は`inherit:false`のQuery Loopで件数を絞る（例：新着3件） |
| Profile（HOME内Teaser・専用Archive） | Query Loop（`postType: astrea_professional`） | 代表者のみを1名Heroに出す用途には新しいDynamic Block or Block Bindingsの拡張が必要（§4.2） |
| Price | 既存の`astrea/price-list` Dynamic Block | そのままHOME/Priceページで再利用可能。変更不要 |
| FAQ | Query Loop、または既存の重要FAQ/関連Service向け関数（`get_important_faqs()`等）を使う新規Dynamic Block | 少数はMinimal表示、多数はカテゴリ表示という02§11の要求を満たすには、単純なQuery Loopでは「重要FAQのみ抜粋」ができないため、新規Dynamic Block（`astrea/faq-list`のような形）が必要になる可能性が高い。**008のスコープに含める候補**（後述§13） |
| Hero / CTA（電話番号・事務所名の差し込みのみ） | 既存Block Bindings（`office_name`/`address`/`phone`） | 新規Core実装不要。既存3キーで十分 |
| Header / Footer | Block Bindings（Office Profile） + Construction 007のNavigation生成機能 | Navigationは`wp_navigation`投稿として既にCore外（WordPress標準機構）で扱われている。Header/Footer Patternは`core/navigation`ブロックを配置するだけでよい |

### 4.2 新規Block Bindings拡張が必要になり得る箇所

- 「代表者1名の写真・氏名をHeroに出す」等、Professional Profileの**単一項目差し込み**は、現在の`get_representatives()`（配列を返す）をそのままBindingsのスカラー値取得に使えない。新しいBindings Source（例：`astrea-core/representative`、`args.key`に`name`/`photo_url`等）を追加するか、Dynamic Blockで対応するかは実装フェーズの選択（Decision 013の「具体的な使い分けの詳細は実装設計時に決定」という既存方針どおり）。
- これは**新しいCoreデータの追加ではなく、既存データへの新しい読み取り経路の追加**である。Core本体の情報構造・DBスキーマは変更しない。

### 4.3 Coreデータが存在しないセクション（要確認事項、§14参照）

- **Flow**：Core側に専用データ機能が無い。02§11「件数固定にはせず」という要求はあるが、これは必ずしも「CPTで構造化管理する」ことを意味しない——単なる編集可能な静的Pattern（例：番号付きステップのGroup Block、ユーザーが手動で行数を増減）でも02仕様書の要求（件数固定にしない）を満たせる。
- **CASE / RESULTS / VOICE**：Core側に専用データ機能が無い。VOICEは「掲載許可確認を支援するUI」（02§12）という要求があり、これは単純な静的Patternでは満たせない可能性がある（許可状態の管理はCoreデータが必要になりそうだが、これは008ではなく将来のCore機能追加Construction Orderの領域）。
- **ACCESS固有情報**（最寄駅・徒歩時間・駐車場・地図表示方式）：Decision 022で「ACCESSの責任」と分離済みだが、データ機能自体は未実装。地図表示（ページ内表示/Google Mapsで開く/地図なし）を選ぶUIもCore側の設定项目が必要になる。

---

## 5. Style Variation（Trust / Natural / Modern）の共有方式

WordPress標準の **Style Variations機構**（`developer.wordpress.org/themes/global-settings-and-styles/style-variations/`、2026-08-27確認）を採用する。技術的詳細：

- テーマルートの`theme.json`が**共通の基盤設定**（デフォルトのタイポグラフィスケール、スペーシングスケール、layout設定等、3案すべてに共通するトークン構造）を持つ。
- `theme/styles/`ディレクトリ配下に`trust.json`・`natural.json`・`modern.json`を配置する。WordPressはこのディレクトリをスキャンして自動的にStyle Variationとして認識し、Site Editorの「Appearance > Editor > Styles > Browse Styles」にユーザー選択肢として表示する（追加のPHP実装は一切不要）。
- 各Variationファイルは**差分のみ**を記述すればよい（Style Variationは完全なtheme.jsonの複製である必要がない——ベーステーマが10色定義していてVariationが2色しか変えない場合、その2色だけを書けば残りはベースから継承される）。これにより「Trust＝ネイビー・白の落ち着いた配色」「Natural＝柔らかな配色」「Modern＝モノトーンの現代的配色」（01仕様書§8）という**色・Typographyの差分**をVariationファイル側に閉じ込め、Templates/Template Parts/Patternsは**3案で完全共通**にできる。
- **共通部分（Theme本体が持つもの）：** Templates, Template Parts, Patterns, Block構造, Core連携ロジック（Block Bindings/Dynamic Block）。
- **Variation側が持つもの：** 配色パレット、Typographyのフォント・ウェイト・行間、（必要なら）角丸・シャドウ等の装飾トークン。**新しいTemplateやPatternをVariationごとに複製しない**（複製するとPattern更新のたびに3倍のメンテナンスコストが発生し、02§5「少数精鋭で一級品」という方針にも反する）。

この設計は、着工前調査で要求された「独自Framework禁止」「WordPress標準Blockで実現できる範囲の見極め」の両方を満たす——Style Variation切り替えは完全にWordPress core（Site Editor）の標準機能であり、ASTREA側のPHPコードは一切不要である。

---

## 6. 0件・1件・少数・多数でも破綻しない表示設計（Baseline§17項目5への回答案）

現状の実装を精査した結果、**既に事実上2つの異なる「0件時挙動」が併存している**ことが分かった。

- **Price（Dynamic Block）：** `render_price_list_block()`は0件のとき空文字列`''`を返す＝**見出しを含めてセクションごと完全に消える**（Construction 004で確立済み）。
- **Service / Professional / FAQ（Query Loop）：** 現状Archiveテンプレートは`core/query-no-results`ブロックを一切使用していない（確認済み）。WordPress標準のQuery Loop Blockは、この「0件時に表示するBlock」を子Block（`core/query-no-results`）として明示的にサポートしている（2026-08-27時点のBlock Editor Handbook確認）。未使用のままだと、0件時はQuery Loop内が単に空になり、見出し（`query-title`）だけが残る**中途半端な状態**になる。

**推奨する統一ルール（008着工時に正式化）：**

1. **Archive専用ページ（Service/Professional/FAQ/Price等の一覧が唯一の目的のページ）：** `core/query-no-results`（Query Loopベースのもの）または同等の分岐（Dynamic Blockベースのもの）を使い、**見出しは残したまま**、本文に「現在準備中です」等の前向きなメッセージ＋次の行動導線（例：お問い合わせへのリンク）を表示する。ページ自体の目的が一覧であるため、セクションごと消すと空白ページになってしまうため。
2. **HOME等に埋め込むTeaserセクション（そのセクションの有無をユーザーが選べる、page内の一部）：** Priceの既存実装と同じく、**見出しを含めてセクション全体を非表示**にする。中途半端な「準備中です」という一文だけがHOME上に浮かぶ状態を避けるため（02§6「空白や不自然な構成にならない」の趣旨に合致）。

この2ルールを新しいDecisionとして正式化することを008着工時に推奨する（本調査では確定させない——「HOMEをどう組むかはユーザー次第」という02§6の原則と整合させつつ、機械的な統一ルールを敷く必要があるため、正式なDecision化が妥当と判断する）。

---

## 7. Mobile設計・Navigation/Header/CTAの優先順位

02仕様書§14が既に「PC版の縮小ではない」「情報の優先順位を再設計する」「Mobile CTAは任意」と明記している。008の実装指針：

- Header PatternはStandard/Minimal/Center/Editorial/CTA強調等の少数バリエーションを用意する（02§14の候補どおり）。ただしBaseline§17項目4の精神（Pattern数を目的化しない）に従い、008では**Style Variationとは独立に、Headerパターンの型自体は1〜2種類程度に絞る**ことを推奨する（多数のHeaderパターンを最初から量産しない）。
- `core/navigation`ブロックの標準レスポンシブ機構（WP 6.3以降のOverlay Menu機構、WP 7.0で導入された`core/navigation-overlay-close`を含む可変Overlay Pattern、developer.wordpress.org確認）をそのまま利用する。独自JSハンバーガーメニュー実装は行わない。
- モバイルではCTA（電話・問い合わせ）を優先表示し、副次的なメニュー項目は折りたたむ、という優先順位付けをHeader Pattern側で表現する（`theme.json`のレスポンシブ設定・Group Blockのレイアウト設定の範囲で対応可能。カスタムCSSメディアクエリの多用は避ける）。

---

## 8. Core非活性時のFallback設計

既存のDecision 021・013の要件がそのまま008にも適用される。新しい方針は不要——確認事項：

- Block Bindingsが接続されたBlock（Hero等のoffice_name/phone等）は、Core非活性時WordPress標準機構により自動的にBlockの静的コンテンツへフォールバックする（`block-bindings.php`のdocblockに明記済みの既存確認済み挙動）。
- Dynamic Block（Price、Contact Form、将来のFAQ一覧等）は、Core非活性時にBlockそのものが未登録になるため、Site Editor上で当該Blockは「認識されないBlock」として警告なく空表示になる想定——**008では、この状態でもEditor側にBlock登録エラーが出ないことを実HTTPで確認するテストが必須**（Baseline§4の既存必須テスト観点をそのまま踏襲）。
- Query Loop（Service/Professional/FAQ Archive）は、Core非活性時にCPT自体が未登録になるため、当該Archive URLがWordPress標準のフォールバック（Decision 024：404を保証対象にしない）になる。これは既存Decisionで確定済みであり、008で新しい判断は不要。

---

## 9. Site EditorカスタマイズとTheme Updateの両立

Decision 014（02§26に反映済み）が既にこの点を確定している：Site EditorでユーザーがTemplate/Template Partを独自編集した場合、WordPress標準挙動（DB保存版がThemeファイルより優先される）を尊重し、強制上書きしない。ASTREA自身のBug/Security/互換性修正は最新版として提供する。

008の実装上の含意：

- Templateへ**ユーザーデータを直接埋め込みすぎない**（Decision 014の「Styles/Core/Binding/WordPress標準設定へ適切に分離する」方針どおり）。例えば固定の電話番号文字列をテンプレートに書かず、必ずBlock Bindings経由にする。
- Pattern自体は「挿入した瞬間にユーザーの投稿データの一部としてコピーされる」がWordPress標準Patternの仕様どおりの挙動であり、これはDecision 002「Patternは業務データの正本にしない」と整合済みの前提である。008で新しい懸念はない。

---

## 10. WordPress標準機能で実現できる範囲 vs 独自実装が必要な範囲

| 機能 | 標準機能で対応 | 独自実装が必要 |
|---|---|---|
| Style Variation切替 | ✅ `styles/*.json` + Site Editor UI | 無し |
| Header/Footerのレスポンシブメニュー | ✅ `core/navigation`のOverlay機構 | 無し |
| 0件時メッセージ（Archive） | ✅ `core/query-no-results` | 無し |
| 0件時メッセージ（Teaser全体非表示） | ❌ Query Loop単体では不可 | Dynamic Block（Price同様のパターンを他セクションにも展開） |
| 単一Professional（代表者）の差し込み | ❌ 現在のBindingsは配列非対応 | Block Bindings Source拡張、または新規Dynamic Block（§4.2） |
| FAQの「重要FAQのみ抜粋」表示 | ❌ Query Loopは`is_important`メタでの絞り込みUIを持たない | 新規Dynamic Block（`astrea/faq-list`相当） |
| Trust/Natural/Modernの配色・Typography切替 | ✅ Style Variations | 無し |
| Pattern一覧・カテゴリ整理 | ✅ `register_block_pattern_category()`（実装済み） | 無し |

---

## 11. WordPress.org Theme Review上の注意点（一次情報確認）

- WordPress.org公式ディレクトリ掲載（Decision 001）を目指す以上、Theme Review Team（`make.wordpress.org/themes/`）が維持するTheme Review Guidelines（`developer.wordpress.org/themes/releasing-your-theme/theme-review-guidelines/`）・WPThemeReview PHPCS Standard・Theme Checkの要求に適合する必要がある（2026-08-27確認）。
- 実務上の主な注意点：
  - テーマ本体に「デモデータ」「営業目的の広告的コンテンツ」を含めない——008のPatternはあくまで空のレイアウト骨格であり、架空の事務所名等をハードコードしないこと（Construction 007の§10 Sample Data方針と同じ原則をTheme側でも徹底する）。
  - `theme.json`のライセンス・バンドルフォント等はGPL互換であることを確認する（Google Fonts等の外部CDN直リンクはTheme Review上非推奨——セルフホストが標準的に要求される）。
  - Text Domainの一貫性（`astrea`）、`load_theme_textdomain()`の適切な呼び出し（既存`functions.php`で対応済み）。
  - Style Variationファイルも含め、テーマ全体のPHPCS（WPThemeReview相当）が引き続きグリーンであることを確認する。
- 詳細なチェックリスト化は008実装フェーズの最終ステップ（Release Checklist、Baseline§16）で行う。本調査では「存在する審査基準を把握し、008の設計がそれと矛盾しないことを確認した」に留める。

---

## 12. 推奨する008の施工範囲

**008に含める（Coreデータが既に存在し、Theme側実装だけで完結する）：**

1. `theme.json`基盤設計（Typography/Spacing/Layout設定の整備。現状はcolor paletteのみ）
2. `theme/styles/trust.json` / `natural.json` / `modern.json`（Style Variation 3種）
3. Header / Footer Template Part（Navigation Block、Office Profile Bindings、Construction 007のNavigation生成機能との接続）
4. `front-page.html` / `page.html` テンプレート（最小限の骨格のみ）
5. Hero / Trust / Services / Professional Profile（Teaser & 個別差し込み） / Price / FAQ / CTA 用Pattern一式
6. §6で提案した0件時表示の統一ルールの正式化（新規Decision）と実装（`core/query-no-results`の導入、Teaser用Dynamic Blockの必要な拡張）
7. Blog/Archive/Search/404テンプレートのDesign System適用
8. Accessibility（Skip Link、見出し階層、Focus表示等）をtheme.json/Template/Pattern全体に組み込む
9. WordPress.org Theme Review適合の実務チェック

**009以降へ分離することを推奨する（Core側のデータ機能が先に必要、または法務/UI設計の検討が別途必要）：**

1. Flow：Core側で構造化管理するか静的Patternに留めるかの判断（§14 要確認事項）を経てから
2. CASE / RESULTS / VOICE：Core側データ機能（掲載許可確認UIを含む）が存在しないため、008ではPattern化できない
3. ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図選択）：Core側データ機能が存在しないため同様
4. CTA固有の「相談方法」データ：Core側で未設計。008では電話番号・問い合わせページリンクの範囲に留める

---

## 13. Architecture案（まとめ）

```
theme/
  theme.json                 — 共通の基盤settings（Typography/Spacing/Layout scale等）
  styles/
    trust.json                — Trust配色・Typography差分
    natural.json               — Natural配色・Typography差分
    modern.json                — Modern配色・Typography差分
  parts/
    header.html                — core/navigation + Office Profile Bindings
    footer.html                — 事務所情報・CTA・簡易Navigation（新規）
  templates/
    front-page.html            — 最小骨格（Patternはユーザーが挿入）
    page.html                  — 最小骨格（新規、現状はindex.htmlへフォールバック中）
    index.html                 — 既存維持
    archive-astrea_service.html 等 — 既存 + core/query-no-results 追加
    404.html                    — 新規（Design Systemの一部として）
  patterns/
    home-hero-*.php             — Hero（Trust/一般の2バリエーション程度）
    home-services-teaser.php    — Service一覧（新着N件、0件で自己非表示）
    home-professional-teaser.php — 代表者差し込み（新規Bindings拡張 or Dynamic Block）
    home-price.php               — 既存price-list.phpを流用
    home-faq.php                 — 新規astrea/faq-list Dynamic Blockを使用
    home-cta.php                  — 電話番号Bindings + 問い合わせページへのリンク
    price-list.php（既存）
    contact-form.php（既存）
```

Core側の追加（008の対象。新規CPTやDBスキーマ変更ではなく、既存データへの新しい読み取り経路のみ）：
- `core/includes/faq-list-block.php`（新規Dynamic Block、重要FAQ抜粋・カテゴリ別表示）
- 代表者1名差し込み用のBlock Bindings拡張、またはDynamic Block（実装フェーズで選択）

---

## 14. 要確認事項（008着工前にユーザー判断が必要）

1. **Flow / CASE・RESULTS・VOICE / ACCESS固有情報のPattern化方針。** 本調査の推奨は「Core側データ機能が無いため008のスコープから除外し、009以降（該当Core機能の実装後）へ回す」。代替案として、Flowのみは構造化データ無しの**純粋な静的Pattern**（ユーザーがEditorで直接編集する番号付きステップ等）として008に含めることも可能——この場合Core変更は一切不要。どちらを採用するか着工前に確認したい。
2. **0件時表示の統一ルール（§6）をDecisionとして正式化することの承認。** 「Archive専用ページ＝`core/query-no-results`で前向きなメッセージ表示」「HOME Teaser＝見出し含め完全非表示」という2ルールを、次のConstruction Order番号のDecisionとして記録してよいか確認したい。
3. **FAQ「重要FAQ抜粋」表示のための新規Dynamic Block追加を008スコープに含めてよいか。** 新しいCPT/DBスキーマ変更は伴わないが、新しいBlock登録（`astrea/faq-list`）という「新しい実装」が発生するため、念のため確認事項として明示する。
4. **代表者1名差し込み用のBlock Bindings拡張 vs Dynamic Block、どちらの方式で実装するか。** Decision 013は「実装設計時に最適解を選択する」としているため新規Decision矛盾は無いが、実装方式の選択そのものは着工後の技術判断として進めてよいか、事前に一言確認したい（軽微な確認事項）。

上記1点目・2点目は「仕様・Decisionの実装解釈確定」に該当するため、Construction 007のDecision 027と同様の手続き（着工前確認→Decision化）を想定している。3点目・4点目は実装フェーズの技術判断で完結する程度の軽微な確認事項であり、着工そのものを妨げるものではない。

**本調査の範囲では、既存Decision・仕様と矛盾する判断は行っていない。** FREE/PRO境界の変更、新規DBテーブル、外部サービス、Telemetryのいずれも本調査は提案していない。

---

## 15. まとめ

- **推奨Architecture：** 共通Templates/Patterns + `theme/styles/*.json`によるStyle Variation 3種（Trust/Natural/Modern）。HOMEはユーザーがPatternを組み合わせて構築する非固定テンプレート。既存のBlock Bindings/Dynamic Block/Query Loop機構をそのまま拡張し、新しい独自Frameworkは導入しない。
- **008で実装すべき範囲：** Header/Footer/HOME関連Pattern一式、Style Variation 3種、theme.json整備、0件時表示の統一、Blog/Archive/Search/404のDesign System適用、Accessibility組み込み、Theme Review適合確認。
- **009以降へ：** Flow（要確認事項1の結果次第）、CASE/RESULTS/VOICE、ACCESS固有情報、CTA固有データ——いずれもCore側データ機能が先に必要。
- **要確認事項：** 上記§14の4点（うち2点は着工前確認・Decision化が必要、2点は軽微）。

いずれも本調査で与えられた9つの制約（見た目先行禁止、Core/Block/Theme/Pattern責任分離維持、Core密結合禁止、FREE単体運用思想維持、PRO専用デザイン先行禁止、既存Decisionとの矛盾禁止）に反する提案は行っていない。
