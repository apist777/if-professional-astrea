# CONSTRUCTION ORDER 013 — RELEASE QUALITY FIXES — 施工報告

**Status:** COMPLETE
**関連:** Construction 012 Integrated Release Quality Audit、Construction 013着工前調査（2026-08-28）

---

## 1. Finding 1 — 長い事務所名のHeader表示改善

`theme/parts/header.html`の事務所名Paragraphのみ`fontSize`を`large`（fluid 1.25rem〜1.625rem）から`medium`（fluid 1rem〜1.125rem）へ縮小した。Heroは`xx-large`のまま無変更——正式名称は一切省略・切断していない（ellipsis/line-clamp/display:noneは不使用）。

Construction 012のSTRESS Fixture（55文字クラスの複数士業合同事務所名）を再投入し、320/375/768/1440pxで実機Browser（Playwright、公式Dockerイメージ経由）検証した。

- Header内の事務所名折返し行数：**8行→4行**に半減（`docWidth === clientWidth`、Horizontal Overflow無し、全Viewportで確認）。
- Hero側は変更なし（引き続き全文表示、視覚的な主役としての役割を維持）。
- Navigation・電話CTAの操作性は改善（Header全体高さが320pxで約400px超→206pxへ縮小）。
- H1はHOME全体でちょうど1個（Hero側のH1のみ）を維持。

Evidence: `docs/research/screenshots/013/finding1_after_home_{320w,375w,768w,1440w}.png`、`finding1_after_header_only_320w.png`。Before: `docs/research/screenshots/012/stress_home_header_overflow_320w.png`。

## 2. Finding 2 — Setup生成NavigationのFrontend反映

### 根本原因の確定

`core/includes/setup-navigation.php`の`generate_navigation()`は従来`post_status => 'draft'`で保存していたが、WordPress Core（`wp-includes/blocks/navigation.php`の`render_block_core_navigation()`）は`ref`が明示的に設定されていても`'publish' === $navigation_post->post_status`を要求することを、実際のコードを読んで確認した——ドラフトのNavigationは`ref`の有無に関わらず絶対にレンダリングされない。

### 実装したHybrid方式

1. `generate_navigation()`の保存Statusを`publish`へ変更（理由は上記、docblockに記録）。
2. 新規`GENERATED_NAVIGATION_OPTION`で生成したNavigation IDを追跡し、再実行時は同じIDを再利用（冪等）。
3. 新規`connect_navigation_to_template_part()`が、WordPress標準API `get_block_template( ..., 'wp_template_part' )`の`->source`プロパティ（`'theme'`=未編集／`'custom'`=編集済み）を用いて、Header/Footerそれぞれ独立に安全性を判定：
   - Setup自身が過去に生成したTemplate Part（`GENERATED_TEMPLATE_PARTS_OPTION`で追跡）が存在すれば、`ref`を冪等に更新。
   - 未追跡かつ`source === 'theme'`（未編集）なら、WordPress標準の`WP_REST_Templates_Controller::prepare_item_for_database()`と同じ構造（`post_type`/`post_name`/`tax_input(wp_theme, wp_template_part_area)`/`meta_input(origin)`）で新規`wp_template_part`を作成し、`parse_blocks()`/`serialize_blocks()`（WordPress標準のBlock Parser）で`core/navigation`ブロックへ`ref`を注入。
   - `source === 'custom'`（ユーザー編集済み）なら**一切変更せず**、案内メッセージのみ表示。
4. `handle_generate_navigation()`を拡張し、Header/Footerそれぞれの接続結果（`connected`/`skipped_custom`）に応じた具体的な管理画面メッセージを表示（ユーザーの編集を「エラー」と表現しない文言）。

### Navigation成功判定（実機確認）

「wp_navigation投稿が作られた」だけでPASSにせず、**Frontend Header/Footerに実際のNavigation Linkが表示されること**を実機HTTPで確認した：

- Fresh Install相当：Header/Footerとも自動接続、`wp-block-page-list`のフォールバックが消え、実際の"取扱業務"等のリンクが両方に表示。
- 冪等性：再実行してもwp_navigation/wp_template_part件数は増えない。
- ユーザー編集保護：Header側を事前に`source: 'custom'`のTemplate Partとして用意した状態で実行 → Header側の内容は**バイト単位で無変更**（`ref`も注入されない）、Footer側（未編集）のみ正しく接続、管理画面に「ヘッダーは既にカスタマイズされているため、自動では反映しませんでした」という案内を確認。
- Core OFF→ON：Fatal無し、再有効化後もNavigation表示は復元（`wp_navigation`/`wp_template_part`はいずれもWordPress標準CPTでありDecision 019の保護対象）。

### Navigation Scenario Matrix結論

| Scenario | 結果 |
|---|---|
| A. Fresh install | 自動接続（実機確認済み） |
| B. WP fallbackのみ | `has_meaningful_navigation()`が除外するため実質Aと同じ扱い（既存ロジック無変更） |
| C. ASTREA生成済み／再実行 | 冪等に同じIDを再利用、`ref`のみ再確認（実機確認済み） |
| D. ユーザー独自Navigationあり | `has_meaningful_navigation()`により新規生成自体を行わない（既存ガード、無変更） |
| E/F. Header または Footerのみ編集済み | 編集側は案内のみ・未編集側は自動接続（実機確認済み、Header側でテスト） |
| G. 両方編集済み | 両方とも変更せず案内のみ（ロジック上E/Fの単純な組み合わせで担保、個別の実機再現は省略） |
| H. Core OFF→ON | Fatal無し、表示復元（実機確認済み） |

## 3. Finding 5 — Dynamic Block Editor未対応警告の解消

### 再Inventory結果

現コードを正本として数え直した結果、**12種類**（Construction 012報告の「7種」から訂正）：`astrea/price-list`, `astrea/faq-list`, `astrea/representative`, `astrea/case-list`, `astrea/results-list`, `astrea/voice-list`, `astrea/service-list`, `astrea/professional-field`, `astrea/office-hours`, `astrea/office-sns`, `astrea/breadcrumb`, `astrea/contact-form`。

### 実装

新規`core/assets/js/editor-blocks.js`（1ファイル、WordPress標準の`wp.blocks`/`wp.element`/`wp.i18n`グローバルのみ使用、ビルド不要のVanilla JS、外部依存・外部CDN・eval・dangerouslySetInnerHTML・Remote Script一切無し）が、12ブロック全てについて最小限のClient側登録を行う：

- `edit`：静的Placeholder（Block名称＋「ASTREA Coreが公開ページで実際のデータを表示します」の説明文のみ、ライブPreview・Inspector Controls・ServerSideRenderは実装していない）。
- `save`：常に`null`を返す——既存の全保存済みMarkupが自己終了形式（`<!-- wp:astrea/xxx {...} /-->`、innerContentなし）であるため、この`save`は既存Markupと完全に一致する形でシリアライズされ、**Migrationは一切発生しない**。
- `supports.inserter: false`：全12Blockとも、既存どおりPattern経由でのみ挿入される設計を維持し、Block Inserterへの単体公開は行わない。

新規`core/includes/editor-blocks.php`が`wp_register_script()`でこのJSを登録し、既存の12個の`register_block_type()`呼び出しそれぞれへ`editor_script_handles`引数（WordPress標準、WP_Block_Type既存プロパティ）を追加した。PHP側の`render_callback`・Attributeスキーマは無変更。

### Editor Save Test（実機確認）

- 全12Blockについて、対象コンテンツ（HOME＝7種、事務所概要ページ＝office-hours/office-sns、独立テストページ＝breadcrumb/professional-field/contact-form）を実際にBlock Editorで開き、**astrea/\*ブロックに対する「未対応ブロック」「復旧を試みる」警告が0件**であることを確認した——Construction 013着工前調査時点で未検証だった`professional-field`/`office-hours`/`office-sns`/`breadcrumb`/`contact-form`の5種も含め、12種全てで確認済み。
- 保存の安全性：WordPress標準のPHP `parse_blocks()`/`serialize_blocks()`によるラウンドトリップテストで、実際に生成されたHOMEページの全内容がバイト単位で一致することを確認した（Gutenbergのクライアント側パーサー/シリアライザはPHP側と一致するよう設計されている、というWordPress Core自身の設計方針に基づく）。実際のBlock Editor UIでの対話的な「編集→保存→再読み込み」操作は、本セッションのヘッドレスChromium自動化における入力フォーカスの制約により完全な自動化検証には至らなかったが、上記の静的検証と「開いた時点で警告が0件」という実機確認を組み合わせることで、実用上十分な確証を得たと判断する。

### 新規発見事項（範囲外、修正せず報告のみ）

`core/group`（Hero・Flow・CTA・Trustの各Pattern、`tagName:"section"`を使うGroup Block）についても、Editor上で同種の「想定されていないか無効なコンテンツ」警告が表示されることを発見した。これはASTREA独自のDynamic Blockではなく、WordPress Core自身の`core/group`ブロックに関する別の問題であり、**Finding 5の承認Scope（ASTREA製Dynamic Block 12種）の対象外**のため、今回は一切変更していない。014または別Constructionでの追加調査を推奨する。

## 4. Finding 3 — Site Title Setup Checklist導線

`core/includes/setup-checklist.php`に新規チェックリスト項目「サイトのタイトルを設定する」（`site_title`、`optional`）を追加し、`Settings > General`（`options-general.php`）への導線を提供した。`done`判定は`'' !== trim(get_bloginfo('name'))`——「明確で安全な既存WordPress状態（空か否か）」のみを使用し、値の中身が「汎用的か」を推測する判定は行っていない。

Office Profileの`office_name`とWordPress標準の`blogname`は**引き続き完全に独立**しており、ASTREAはどちらの方向にも自動コピー・自動上書きを一切行わない（PHPUnitテストで明示的に確認：チェックリスト読み取りが`blogname`を変更しないことをアサート）。

## 5. Core OFF確認

Finding 1〜3・5のいずれの変更についても、Core無効化状態でHOME・各Archive・Search・404にFatal/Critical Errorが発生しないこと、Core再有効化後に全データ（Navigation・Template Part含む）が復元されることを実機確認した（`wp_navigation`/`wp_template_part`はいずれもWordPress標準投稿タイプであり、Decision 019によりCore非活性化時にも削除されない）。

## 6. Security

着工前調査で洗い出した懸念事項を実装レビューで再確認した：

- Navigation自動接続：全処理は既存の`handle_generate_navigation()`（`current_user_can('manage_options')`＋Nonce検証済み）の内部でのみ実行。新しい外部Entry Pointは追加していない。`wp_template_part`への書き込み内容はTheme file自身の内容＋サーバー内部生成の数値`ref`のみで構成され、外部入力は混入しない。Template Part上書きはWordPress標準の`source`判定で厳密にガードされている。
- Editor JS：外部入力をHTMLとして描画する経路は無い（静的テキストのみ）。外部CDN・eval・dangerouslySetInnerHTML・Remote Scriptは一切使用していない。
- Site Title：自動Mutationは一切行わない（PHPUnitで明示的に確認済み）。

新しいAttack Surfaceは発見されなかった。

## 7. Test結果

- **PHPUnit：359 tests / 574 assertions、全PASS**（Construction 012時点の348から+11件：Navigation生成のpublish化・冪等性・`navigation_still_exists()`・`inject_navigation_ref()`の純粋関数テスト・`connect_navigation_to_template_part()`のグレースフル失敗テスト・Site Titleチェックリスト項目の2テスト）。
- **PHPCS：`core/`・`theme/`全体で0エラー**（63ファイル、新規`editor-blocks.php`含む）。
- **PHP構文チェック：全変更ファイルでエラーなし。**
- **実HTTP smoke-test（`tools/ci/smoke-test.sh`）：Part 13（CV-DA）を新規追加。** 実装過程で、Construction 009由来の既存Part 8（BW）が、Construction 013の新機能（Header/FooterへのNavigation `ref`自動接続）との相互作用により誤ってFAILする状態を発見・修正した——`ref`が（たとえ削除済みの投稿を指していても）設定されている限りWordPress CoreはPage-Listフォールバックを作成しない、という実際のWordPress Core挙動（`get_inner_blocks_from_navigation_post()`を読んで確認）に起因する、正当なConstruction間相互作用であり、製品コードの不具合ではない。BW側にHeader/Footer・追跡Optionのリセット処理を追加して解消した。また、Part 13自身の実装過程で2件のテストスクリプト固有の誤り（Service個別タイトルではなくArchive汎用ラベルを検証すべきだった点、`wp eval`のCLIコンテキストには認証ユーザーが無く`tax_input`のTaxonomy割当てが暗黙にスキップされる点）を発見・修正した——いずれも製品コード（`connect_navigation_to_template_part()`自体は実際のHTTPフロー経由で正しく動作することを確認済み）には影響しない。

## 8. Migration

DB Schema Migrationは発生していない。新設した2つのOption（`GENERATED_NAVIGATION_OPTION`、`GENERATED_TEMPLATE_PARTS_OPTION`）は通常のOptions APIとして扱っており、既存データへの変換処理は不要。

## 9. まとめ

Construction 012で発見されたHIGH 2件（Finding 1・2）・013 REQUIREDへ格上げされたFinding 5・INCLUDEDのFinding 3を全て実装した。Finding 4（日本語言語パック）は既存分類どおり014維持、Finding 6/7/8（Professional Archive空Excerpt・検索結果パンくずラベル・Price Group表示）も既存分類どおりPost v1維持とし、いずれも変更していない。新規Decisionは追加していない——全て既存Decision・既存Architectureの範囲内で解決した実装課題である。
