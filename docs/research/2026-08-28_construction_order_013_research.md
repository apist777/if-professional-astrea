# CONSTRUCTION ORDER 013 — PRE-CONSTRUCTION RESEARCH — 着工前調査報告

**Status:** RESEARCH COMPLETE（製品コード変更なし。`docs/research/`・`HISTORY.csv`のみ更新）
**関連:** Decision 021/022/028/029、Construction 012 Integrated Release Quality Audit

---

## PART A — Finding 1（長い事務所名 / Header・Hero）

### A-1. 現状Markupの分解

`theme/parts/header.html`：外側Groupが`layout:{type:flex, justifyContent:space-between, flexWrap:wrap}`、`spacing.blockGap:small`。子要素3つ（すべて対等なflex item）：① 事務所名Paragraph（`fontSize:"large"`＝fluid 1.25rem〜1.625rem、`fontFamily:"heading"`＝Georgia/游明朝系のserif）、② 素の`wp:navigation`（`overlayMenu:"mobile"`）、③ 電話CTA Button。

`theme/patterns/home-hero.php`：事務所名は`wp:heading{level:1, fontSize:"xx-large"}`＝fluid 2rem〜3rem、単独`constrained` layout内に縦積み。

### A-2. 320px折返しの技術的原因

1. **事務所名Paragraph/Headingに`flex-basis`や`max-width`の明示的な制約が無い**ため、テキストは自然幅まで伸びようとし、他の2要素（Navigation・電話Button）と横並びを試みてから`flexWrap:wrap`で個別に折り返す。3要素が「1行に収まらなければそれぞれ独立した行になる」という不安定な挙動になっている。
2. **CJK（日本語）テキストの行送り自体は正常**：`word-break`のカスタム指定は一切なく、ブラウザ既定のUAX#14準拠の折返し（日本語は文字間、半角英数字は単語境界）が機能している——これは「壊れている」のではなく、長い文字列を大きいフォントサイズで表示すれば当然増える行数である。
3. **`fontSize`トークンのfluid最小値（large: 1.25rem、xx-large: 2rem）が、Header/Hero双方で"標準的な短い事務所名"を前提としたサイズ**になっており、Decision 022が正式対象とする「複数士業合同事務所名」のような長い文字列に対しては、ビューポート追従（fluid）だけでは不十分——fluidは"画面幅"には追従するが"テキストの長さ"には追従しない。

### A-3. 「正式名称を切らない」の確認

現状、`ellipsis`・`line-clamp`・`display:none`等による文字欠落は一切発生していない（全文表示されている）。今回の問題は「見た目の窮屈さ」であり「情報欠落」ではない。命令書の基本方針（情報を捨てずResponsive Typography/Layoutで吸収する）と現状の実装方針は矛盾していない——不足しているのは「長い文字列に対する追加の調整」のみ。

### A-4. Header/Hero個別評価

- **Header**：Navigation・電話CTAとの共存が必須。狭い画面では「事務所名は認識できれば十分、Navigation/CTAの操作性を最優先」という役割。
- **Hero**：正式名称そのものを主役として見せる場所。HeaderよりH1が長く折り返ることは許容できる（Hero以外に地の文が無いため、多少の行数増加はコンテンツとして自然）。

この非対称性から、**HeaderとHeroに同一のResponsive Ruleを課す必要はない**という命令書の前提は技術的にも妥当と確認した。

### A-5. 候補比較

| 案 | 内容 | Header適合 | Hero適合 | 備考 |
|---|---|---|---|---|
| A. fluid font-size中心 | `large`/`xx-large`のfluid最小値をさらに下げる、またはHeader専用の新しい小さめfontSizeトークンを追加 | ◎（実装コスト最小） | △（Heroの視覚的インパクトが薄れる） | 既存`theme.json`のfluid機構をそのまま拡張するだけで済む |
| B. Mobile Header Layout変更＋Typography調整 | Header専用に事務所名テキストへ小さめのfontSizeを指定しつつ、狭幅では事務所名を独立した行として扱う（Navigation/CTAとまとめて2行程度に収束させる） | ◎ | ○（Heroは現状維持のままで良い） | HeaderのみPattern/Template Part変更、Hero非改修で済む |
| C. line-clamp/ellipsis等 | CSSで行数上限＋省略表示 | △（技術的には収まるが） | ×（正式名称の欠落は命令書の基本方針に反する） | 比較のため記録するが**非推奨**として扱う |

### A-6. 推奨方針

**B（Aの要素を一部内包）を推奨する。** 具体的には、Header側の事務所名Paragraphのみ、既存の`fontSize:"large"`から、より小さい既存トークン（`medium`：fluid 1rem〜1.125rem）へ変更する、あるいはHeader専用の新しいfontSizeトークンをtheme.jsonへ追加する（後者は追加トークン1個で済み、Aの要素を含む）。Heroは`xx-large`のまま変更しない——Heroの多少の行数増加は許容範囲というのが命令書の前提とも一致する。

Header側のfont-size縮小だけで、STRESS Fixtureの55文字クラスの事務所名でも、7〜8行から概ね3〜4行程度への圧縮が見込める（同フォントファミリー・同カラム幅における文字数/行の単純比例から算出。実測での再検証はConstruction 013本施工時に必須）。それでも長さによっては複数行になり得るが、「Navigationを大幅に押し下げる」状態からは明確に改善される。

**新しい固定の「◯行以内」仕様は作らない**——命令書の指示どおり、Header側のfont-sizeを実用的な範囲まで縮小し、Navigation/CTAの操作性を最優先する、という原則にとどめる。

---

## PART B — Finding 2（Setup生成Navigation）

### B-1. Navigation Data Flow の完全追跡（決定的な根本原因を特定）

`core/includes/setup-navigation.php`の`generate_navigation()`は`wp_navigation`投稿を`post_status => 'draft'`で作成する。docblockには「Navigation only renders once referenced from a Template/Template Part in the Site Editor」と明記されており、Draftのまま作成する設計はConstruction 007時点で意図的だったことが確認できた。

問題は、`theme/parts/header.html`・`footer.html`の`<!-- wp:navigation {"overlayMenu":"mobile"} /-->`が**`ref`属性を持たない**こと。`ref`が無い場合、WordPress Core（`wp-includes/class-wp-navigation-fallback.php`）は`WP_Navigation_Fallback::get_fallback()`を呼び、内部で`get_most_recently_published_navigation()`を実行する。このクエリは：

```php
$parsed_args = array(
    'post_type'   => 'wp_navigation',
    'order'       => 'DESC',
    'orderby'     => 'date',
    'post_status' => 'publish',   // ← draftは対象外
    'posts_per_page' => 1,
);
```

**`post_status => 'publish'`のみを対象とする。** ASTREAが生成するNavigationは`draft`であるため、このクエリには**構造的に一切ヒットしない**。結果、WordPress Coreは「公開済みNavigationが存在しない」と判断し、`create_classic_menu_fallback()`（Classic Menuが無ければ失敗）→`create_default_fallback()`（`<!-- wp:page-list /-->`の汎用Navigationを新規作成）という既定の代替ロジックへ進む。これがフロントエンドで常にPage-List（Sample Page・Privacy Policy等の無関係なページを含む）が表示される直接の原因である。

**追加実験で確認した重要な事実：** 生成済みNavigationを手動で`publish`に変更しても、フロントエンドの表示は変わらなかった。原因は、`draft`の間にフロントエンドへ一度でもアクセスがあると、その時点でWordPress自身のPage-Listフォールバック（`post_status: publish`）が**既に**作成されてしまい、その後ASTREAのNavigationをpublishしても「最も新しく公開された方」の比較で（生成タイミングの前後関係により）Page-Listフォールバックの方が選ばれ得ることを実機で確認した。**つまり、単に`draft`を`publish`に変えるだけの修正では不十分**であり、`ref`による明示的な紐付けが必要という結論に至った。

### B-2. ユーザー編集保護 — Template Part判別の標準API

WordPress CoreにはTemplate Partが「Theme fileのまま（未編集）」か「DB上でユーザーカスタマイズ済み」かを判別する**公式・安定した**API `get_block_template( $id, 'wp_template_part' )`が存在する。戻り値`WP_Block_Template`オブジェクトの`->source`プロパティが`'theme'`（Theme file由来、未編集）か`'custom'`（`wp_template_part`投稿としてDB保存済み、ユーザー編集済み）かを明示する。これはSite Editor自体が「カスタマイズ済み」バッジ表示に使っている公開APIであり、内部実装への危険な依存には当たらない。

### B-3. Navigation修正案比較

| 案 | 内容 | 実現性 | ユーザー編集保護 | 備考 |
|---|---|---|---|---|
| A. 生成時にHeader/Footerへ`ref`を安全に割り当てる | `get_block_template()->source`が`'theme'`（未編集）の場合のみ、Header/Footer相当の内容で新しい`wp_template_part`投稿を作成し、Navigationブロックへ生成Navigationの`ref`を注入する。既にSetupが作成した`wp_template_part`が存在する場合はそれを冪等に更新する。ユーザーが既に編集済み（`source==='custom'`）の場合は一切触れず、案内のみ表示。 | 中（実装要） | ◎（標準APIで安全に判別可能） | 唯一「押したら実際に使われる」を実現する案 |
| B. Theme標準Navigationが自動でASTREA生成Navigationを参照する構造へ変更 | Header/Footer自体に何らかの「ASTREA生成分を優先」ロジックを埋め込む | 低 | △ | WordPress標準の`ref`解決の仕組みを迂回する独自ロジックになり、Theme更新耐性・WordPress標準準拠の観点で非推奨 |
| C. Navigation投稿のみ生成、Site Editorでの割当てをUIで明示案内 | 現状の生成ロジックを維持しつつ、成功メッセージに「サイトエディタでHeader/Footerに割り当ててください」という具体的な手順を追記する | 高（低コスト） | ◎（何も自動変更しない） | 最も安全だが「押したら使える」というUXは実現しない |

### B-4. 推奨方針

**A（未編集時は自動`ref`割当て）＋ C（編集済み時はガイダンスへ自動的にフォールバック）のハイブリッドを推奨する。** 具体的な安全設計：

1. Setup独自の追跡Option（例：`astrea_core_generated_template_parts`、既存の`GENERATED_PAGES_OPTION`と同一パターン）で、Setupが自分で作成したHeader/Footer用`wp_template_part`のIDを記録する。
2. `generate_navigation()`成功後、Header・Footerそれぞれについて：
   - 追跡済みのSetup生成`wp_template_part`が既に存在する→冪等に`ref`だけ更新（Setup自身の生成物なので安全に上書きしてよい）。
   - 追跡が無く、`get_block_template()->source === 'theme'`（未編集）→新規`wp_template_part`をTheme file相当の内容＋`ref`付きNavigationブロックで作成し、Option記録。
   - 追跡が無く、`source === 'custom'`（ユーザー編集済み）→**一切変更しない**。代わりに管理画面に「Header/Footerが既にカスタマイズされているため自動反映できませんでした。サイトエディタで手動割り当てが必要です」という具体的な案内を表示する（案Cへ自動フォールバック）。

これにより、Fresh installの大多数のケース（後述Scenario A/B/C）では「押したら実際に使われる」が実現し、既存編集を破壊するケース（Scenario E/F/G）では自動的に安全側（案C相当）へ倒れる。

### B-5. Navigation Scenario Matrix

| Scenario | 状態 | 分類 |
|---|---|---|
| A. Fresh install、Navigation 0件、Template Part未編集 | 生成→自動`ref`割当て可能 | **自動変更してよい** |
| B. WordPress fallback navigationのみ、Template Part未編集 | `has_meaningful_navigation()`が既にfallbackを除外して判定するため、Aと同様に新規生成→自動割当て可能 | **自動変更してよい** |
| C. ASTREA生成Navigationが既にある（再実行）、Template Part未編集 | `has_meaningful_navigation()`がtrueとなり新規生成自体スキップ（既存ガード）。すでに`ref`が割り当て済みなら何もしない、割当てがまだなら追跡情報を頼りに`ref`を割り当てる（1回のみ） | **自動変更してよい（冪等）** |
| D. ユーザー独自のNavigationあり | `has_meaningful_navigation()`が既にtrueと判定し、新規生成自体を行わない（既存ガード、013で変更しない） | **変更禁止（既存動作のまま）** |
| E. Headerのみユーザー編集済み | Header側は`source==='custom'`のため触れず案内のみ、Footer側は条件を満たせば自動割当て | **Header：案内のみ／Footer：自動変更してよい** |
| F. Footerのみユーザー編集済み | Eの逆 | **Footer：案内のみ／Header：自動変更してよい** |
| G. Header/Footer両方編集済み | 両方とも触れず、案内のみ | **変更禁止・案内のみ** |
| H. Core OFF→ON | `wp_navigation`・`wp_template_part`はいずれもWordPress標準CPTであり、Core非活性化時にも削除されない（Decision 019）。Core再有効化後も表示は継続する | **問題なし（既存Decision 019の保護がそのまま機能）** |

### B-6. Security確認（Navigation自動接続案）

- 全処理は既存の`handle_generate_navigation()`（`current_user_can('manage_options')`＋`check_admin_referer()`済み）の内部でのみ実行される——新しい入口は追加しない。
- `wp_template_part`へ書き込む内容は、Theme file自身の内容＋サーバー内部で生成した数値`ref`（`wp_insert_post()`の戻り値、攻撃者操作不可）のみで構成され、外部入力は一切混入しない。
- 上書き対象はTemplate Partの`source`判定で厳密にガードされ、ユーザー編集済みTemplate Partへの意図しない上書き（Template-part overwrite）は発生しない。
- ID改ざん・Stored Block Markup Injectionの新規経路は発見されなかった。

新しいSecurity Findingは無い。

---

## PART C — Finding 5（Dynamic Block Editor警告）

### C-1. 正確なBlock Inventory（現コードを正本として再カウント）

`grep -rn "register_block_type(" core/includes/*.php`の結果、**現在12種類**のDynamic Blockが存在する（Construction 012報告の「7種」は過少カウントだったため本調査で訂正する）：

`astrea/price-list`, `astrea/faq-list`, `astrea/representative`, `astrea/case-list`, `astrea/results-list`, `astrea/voice-list`, `astrea/service-list`, `astrea/professional-field`, `astrea/office-hours`, `astrea/office-sns`, `astrea/breadcrumb`, `astrea/contact-form`。

このうち7種（price-list/faq-list/representative/case-list/results-list/voice-list/service-list）はConstruction 012で実機Editor上の警告を直接確認済み。残る5種（professional-field/office-hours/office-sns/breadcrumb/contact-form）は同一の登録パターン（PHP側`register_block_type()`のみ、クライアント側JS登録なし）であるため、WordPress Coreの仕組み上、同一の警告が出ると推定されるが、**本調査ではこの5種について実機Editorでの再確認は行っていない（未検証）**——013本施工の検証範囲に含めるべき。

### C-2. 原因の技術的確認

WordPress Block Editor（Gutenberg）は投稿本文をブロックへパースする際、**クライアント側JSレジストリ**（`wp.blocks.getBlockType(name)`、ブラウザ内の`registerBlockType()`呼び出しで構築される）のみを参照する。PHP側の`register_block_type()`は**サーバーサイドレンダリング専用**（フロントエンド表示・REST APIのrender時）に効き、Editorのクライアント側認識には一切関与しない——これはDecision 013のCore/Theme責任分離とは無関係な、WordPress自体のEditor/Renderアーキテクチャの仕様である。

クライアント側に登録が無いブロック名がpost_content中に見つかると、Gutenberg内部の`core/missing`ブロックへフォールバックし、「このサイトは『X』ブロックに対応していません」（未登録）、および属性・innerContentの検証に失敗した場合は「想定されていないか無効なコンテンツが含まれています（復旧を試みる）」（Validation失敗）が表示される。

実機確認：`core/composer.json`・`block.json`はプロジェクト内に一切存在せず、`enqueue_block_editor_assets`・`wp_register_script`の呼び出しも現状ゼロ——クライアント側登録の仕組みは今回が初導入となる（Core内で初めてJSアセットを追加することになるが、Decision 013の「Core/Theme責任分離」自体には抵触しない。あくまでCoreが既に登録しているBlockのEditor体験を補うものである）。

### C-3. 修正候補比較

| 案 | 内容 | 評価 |
|---|---|---|
| A. block.json＋最小editorScript＋Server render | block.jsonファイルを新設し標準的なメタデータ駆動登録へ全面移行 | 構造としては理想だが、既存の12個すべてをblock.json形式へ書き換えるのは影響範囲が大きく「大規模Refactorを目的化しない」に抵触する可能性 |
| **B. PHP登録を維持しつつClient側で最小`registerBlockType()`を追加** | 既存の`register_block_type()`呼び出し（属性・render_callback）はそのまま、`editor_script_handles`引数を追加し、1つの小さなvanilla JSファイルで12ブロック分の最小Editor定義（`edit`はプレースホルダ表示のみ、`save`は`null`）を登録 | **推奨。** 既存コードへの変更が最小（各`register_block_type()`呼び出しへ1行追加＋JSファイル1本の新設のみ） |
| C. ServerSideRenderによるPreview | Editor内で実データをライブプレビュー | 明確に不要——命令書自身が「警告を消すためだけに複雑なReact UIを作るのは禁止」と明記。REST経由の追加リクエストも発生し、v1.0のスコープを超える |
| D. Editor Placeholderのみ | 名称のみ表示する最小限のプレースホルダ | Bに内包される（Bの`edit`実装そのものがD相当） |

**推奨：B（実質的にDを含む）。** ServerSideRenderのような高機能プレビューはv1.0には不要と判断する。

### C-4. 既存保存済みContentとの互換性

現在保存されている`<!-- wp:astrea/xxx {...attrs} /-->`はすべて**自己終了形式（innerContentなし）**である。B案の`save: () => null`は、Gutenbergの通常のシリアライズ規則により**まさに自己終了形式`<!-- wp:astrea/xxx {...attrs} /-->`を生成する**——つまり、既存の保存済みMarkupと完全に一致し、**Migrationは一切不要**。属性スキーマ（`heading`/`emptyMessage`/`limit`/`field`/`label`等）をPHP側の`register_block_type()`と1対1で一致させれば、Validation失敗（「復旧を試みる」警告）も同時に解消される。

### C-5. Editor UX最小仕様

各Blockについて、Inserter検索で表示されると却って混乱を招く（これらはすべて既存Patternへの埋め込み専用であり、単体挿入を想定した設計ではない）ため、**`title`・`icon`・`category`は設定しつつ`supports.inserter: false`**とし、Inserter検索結果には出さず、既存Pattern経由の挿入のみを維持する方針を推奨する。Description・Preview・Inspector Controlsは実装しない（原則最小）。

### C-6. Editor Save Testの設計

013施工時の必須検証手順として以下を提案する（実Browser、Playwright推奨——Construction 012で確立したPlaywright（公式Dockerイメージ、`--network host`）による検証パイプラインを流用可能）：

1. HOME（または事務所概要ページ）をBlock/Site Editorで開く。
2. 全セクションで「このサイトは対応していません」「想定されていないか無効なコンテンツ」の警告文字列が**出現しないこと**を確認。
3. 何らかの実編集（例：Heroのキャッチコピー文言変更）を行い保存。
4. フロントエンドを再取得し、Dynamic Block（Service/CASE/RESULTS/Professional/Price/FAQ/VOICE等）の出力が**保存前と一致すること**を確認（Diff比較）。
5. 同じページを再度Editorで開き、再度警告が**出現しないこと**を確認。

### C-7. Security確認（Editor Registration）

新設するJSファイルは以下を満たすことを設計方針とする（013実装時に遵守）：バンドル済み静的ファイルのみ（外部CDN・リモートスクリプト無し）、`eval`不使用、`dangerouslySetInnerHTML`等の不安全なHTML注入不使用、WordPress標準の`wp.element.createElement`のみ使用。外部依存パッケージの新規追加は不要（WordPressが既に提供する`wp-blocks`/`wp-element`/`wp-i18n`グローバルのみで実装可能）。

---

## PART D — Finding 3（Site Title / Office Name非同期）最終判断

### D-1. 責任整理

- `office_name`（Office Profile）：ASTREA Core正本、Header/Hero/Footer/Office Info Patternで使用（Block Bindings経由）。
- `blogname`（WordPress標準Option）：`<title>`タグ・パンくずHomeラベル・RSSフィード名等、WordPress Core自身が内部的に参照する汎用サイト名。ASTREA Coreが所有するデータではない。

### D-2. 比較

| 案 | 内容 | 評価 |
|---|---|---|
| A. Setup初回時のみユーザー確認の上でSite Titleへ反映 | 一度限りの同期＋確認ダイアログ | 実装コスト（確認UI）に対し効果が限定的、WordPress標準設定への介入という性質上、命令書の「強制同期は禁止候補」という基本方針にやや反する |
| **B. Setup Checklistに導線を追加** | 「サイトのタイトルを設定する」チェックリスト項目を追加し、`Settings > General`へのリンクを提示するのみ | **推奨。** 既存Setup Checklistの他項目（Navigation生成の案内等）と同一パターンで実装コストが低く、WordPress標準設定を尊重する（Decision 021の精神と一致） |
| C. Breadcrumbのみoffice_name優先、`<title>`はWordPress標準へ委譲 | 部分対応 | `<title>`タグはSEO上の影響が大きいため、これだけでは根本解決にならない |
| D. 現状維持＋Documentation | コード変更なし | 最も低コストだが、実際に多くのサイトで発生し得るギャップに対して弱い |

### D-3. 最終推奨

**B。013 RECOMMENDED（必須ではないが、013がSetup UI（`setup-admin.php`）に既に手を入れる予定であるため、同時に実施する価値がある）**という位置づけを提案する。Aのような自動上書きは行わない。

---

## PART E — Finding 4（日本語言語パック）最終判断

命令書の指示どおり、**原則014維持**を確認する。ASTREA Core自身が言語パックを自動インストール・自動有効化する設計は**推奨しない**——理由：(1) WordPressのlocaleは管理画面全体（投稿一覧・設定画面文言等）に影響する広範な設定であり、Office Profileのような限定的なCoreデータとは性質が異なる、(2) 言語パックの取得はwordpress.orgへの新規外部リクエストを伴い、Decision 021が想定する「Coreは推奨するがThemeを人質にしない」という受動的な設計方針から逸脱する、(3) 実運用では多くの場合、WordPressの5分間インストール時点で日本語が選択されており、本件は主に開発環境固有の見え方である可能性が高い。

Documentation（README・Release Note等）での言及のみに留め、コード対応は不要と判断する。**014送りを維持。**

---

## PART F — 013 Scope Classification（全8件）

| Finding | 分類 | 理由 |
|---|---|---|
| 1. 長い事務所名のHeader/Hero折返し | **013 REQUIRED** | Construction 012でHIGH判定。Decision 022対象セグメントへの直接影響 |
| 2. Setup生成Navigationが反映されない | **013 REQUIRED** | Construction 012でHIGH判定。Setup主要機能が実際には機能しないという深刻な運用ギャップ |
| 3. Site Title / Office Name非同期 | **013 RECOMMENDED** | 013がSetup UIに手を入れるため同時実施が効率的。単独では014でも可 |
| 4. 日本語言語パック | **014送り（維持）** | Documentation対応で足り、コード変更不要と判断 |
| 5. Dynamic Block Editor警告 | **013 RECOMMENDED**（ユーザー判断により013 REQUIREDへ格上げも妥当） | データ損失の実証的リスクは確認されていないが、12ブロック全てに及ぶ影響範囲・非技術者ユーザーの混乱リスクを踏まえると優先度は高い |
| 6. Professional Archive空Excerpt | Post v1 | 低頻度・低影響の視覚ノイズ |
| 7. 検索結果パンくずラベル | Post v1 | 軽微な表示上の問題 |
| 8. Price Group未表示 | Post v1 | Construction 012で最終判断済み（Release Blockingではない） |

---

## Security Review（総括）

Navigation自動接続案・Editor Registration案とも、既存の認可・Nonce機構の範囲内で完結し、新しい攻撃面は発見されなかった。全面的なSecurity Audit再実施は不要という命令書の前提どおり、対象を絞った確認のみで十分と判断する。

## FREE / PRO境界確認

013で予定するすべての修正はFREE v1の品質修正（表示崩れ修正、既存Setup機能の動作修正、Editor体験改善）であり、士業別Navigation自動設計・高度なSite Architecture生成等のPRO領域機能は一切含まれない。Setupが生成するNavigationは引き続き「ユーザー操作起点の職種非依存の基本メニュー」のままであり、Decision 029のFREE/PRO境界を変更しない。

## Test Strategy（013本施工用）

| Finding | PHPUnit | PHPCS | smoke-test.sh | Playwright（実Browser） |
|---|---|---|---|---|
| 1 | 対象外（CSS/Markupのみ） | Markup構文確認 | Header内fontSize属性値のgrep確認 | **必須**：320/375/768/1440での折返し行数・overflow再検証（Before/After比較） |
| 2 | `get_block_template()->source`判定ロジック・冪等性・Scenario A〜Hの主要ケースの単体テスト | 通常どおり | Navigation Scenario Matrixの主要ケース（A/C/E/G）を実際のwp-cli＋curlで検証——**フロントエンドに実際にNavigationリンクが出現すること**を確認する新Part追加（Construction 012が発見した「チェックリストは緑でも実際には反映されない」というギャップを二度と見逃さないための恒久的回帰テスト） | 補助的に目視確認 |
| 5 | 対象外（クライアントJSはPHPUnit対象外） | 通常どおり | 新規JSファイルが編集画面のみでエンキューされ、フロントエンドページには出現しないことを確認 | **必須**：Editor Save Test（§C-6）をHOME・事務所概要ページで実施 |
| 3 | Checklist項目追加のテスト | 通常どおり | Checklist表示確認 | 不要 |

## Screenshot Plan（013施工後比較用）

- **Finding 1 Before：** `docs/research/screenshots/012/stress_home_header_overflow_320w.png`（Construction 012で取得済み、再利用可）。
- **Finding 2 Before：** 本調査で確認した「`wp-block-page-list`が残存する」フロントエンドHTML（本文中に記録、Screenshotとしては未取得——013施工時に取得を推奨）。
- **Finding 5 Before：** `docs/research/screenshots/012/editor_unsupported_block_warnings.png`（Construction 012で取得済み、再利用可）。

013施工報告では、これら3点それぞれのAfter Screenshotを同一Viewport・同一Fixtureデータで取得し、Before/After比較として提示することを推奨する。

## 新規Decision要否

**不要。** Finding 1・2・3・5はいずれも既存Decision（021/022/028/029）および既存Architecture（Block Bindings、Setup冪等性ガード、Dynamic Block規約）の範囲内で解決可能な実装課題であり、新しい恒久的な設計判断を要するものではない。
