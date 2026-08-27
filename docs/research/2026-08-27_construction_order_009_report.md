# CONSTRUCTION ORDER 009 — HOME組み立て支援 / GA4 / Core完全削除UI — 施工報告

**Status:** COMPLETE
**関連:** Decision 029（Remaining Work Auditに基づく5件の確定）, Decision 019（Core Uninstall）, Decision 016（初期セットアップ）, Decision 021（Core任意・公式推奨）, Decision 028（0件時表示統一）, Construction 007（Setup）, Construction 008（Design System）

---

## 1. 追加Decision

**Decision 029** を追加した（`docs/specifications/04_astrea_free_v1_preconstruction_decisions.md`）。Remaining Work Audit（2026-08-27）で提起された5件（Navigation自動構築とFREE/PRO境界、CASE/RESULTS/VOICEの扱い、ACCESS固有情報の扱い、WordPress.org提出タイミング、Release Candidate方針）を正式にFIXした。05 Baseline §27へ追記済み。

---

## 2. HOME組み立て方式

`core/includes/setup-home.php`（新規）。「ホームページを作成する」というSetup Action（Office Profileページの「ホームページの作成」セクション）を1回クリックすることで、以下を1操作で行う。

1. Construction 008のHOME用Pattern 7種（`astrea/home-hero`, `astrea/home-services-teaser`, `astrea/home-professional-teaser`, `astrea/home-price`, `astrea/home-faq`, `astrea/home-flow`, `astrea/home-cta`）の**現在の登録内容**を`WP_Block_Patterns_Registry`から直接取得し、連結して新規固定ページの本文とする（Pattern内容をCore側へ複製・二重管理しない）。
2. 新規固定ページを`publish`状態で作成する。
3. `show_on_front=page` / `page_on_front=<新規ページID>`を設定する。

**Trust Pattern（`astrea/home-trust`）は既定の組み立て対象から除外した。** 「ここに信頼要素の説明を入力してください」という明示的な空欄補充指示を含み、Construction 007の事務所概要ページ同様「未編集のプレースホルダーを公開しない」という既存方針に反するため。Trust Pattern自体は削除しておらず、手動挿入用として引き続き利用可能。

---

## 3. 既存HOME保護方式

`generate_home_page()`が以下を実装。

- **冪等性：** `astrea_core_generated_pages`オプション（Construction 007由来）に`home`キーを追加し、生成済みページIDを記録。生成済みかつ現存する場合は`astrea_home_exists`エラーで即座に拒否（重複生成しない）。
- **既存Front Page保護：** `show_on_front=page`かつ`page_on_front`が現存するページを指す場合、それが自Trackingページでなければ`astrea_home_front_page_exists`エラーで拒否し、一切変更しない。
- **Blog-as-home：** `show_on_front=posts`（既定値）はブロッキング対象外——本機能自体が「明示操作」であるため、クリックした時点でユーザーの意思とみなす。
- **編集後の再実行：** Trackingされたページが現存する限り、内容がどう編集されていても再生成しない。
- **孤立参照：** 生成済みTrackingページ、または`page_on_front`参照先がゴミ箱に移動されていた場合は「存在しない」扱いとし、再生成を妨げない。

実機検証（A〜E全シナリオ）は§9参照。

---

## 4. Setup / Navigationとの統合

- Setup Checklistへ「ホームページを公開する」項目を追加（`is_home_configured()`：進捗DBではなく`show_on_front`/`page_on_front`の実際の状態から動的判定、ASTREA生成か否かを問わない）。
- **Navigationチェックリスト項目の誤判定を修正した。** Construction 008で発見されていた「Header/FooterのNavigationブロックにより、WordPress自身が空のPage Listフォールバックを自動作成する」という挙動により、`has_any_navigation()`（旧実装）は初回ページ表示だけで常に「完了」と誤判定していた。`is_wordpress_fallback_navigation()`（`post_name==='navigation' && post_content==='<!-- wp:page-list /-->'`——`WP_Navigation_Fallback::create_default_fallback()`のWordPressコア実装から正確に特定）を追加し、この自動フォールバックのみを除外する`has_meaningful_navigation()`へ置き換えた。実機検証で、1回のホームページ閲覧だけでフォールバックが作成されること、それでもチェックリストが「未完了」のまま・生成ボタンも表示されたままであることを確認した。
- HOME生成→Navigation生成、Navigation生成→HOME生成のどちらの順序でも、双方の冪等性・保護ロジックは独立しており相互に影響しないことを実機確認した。

---

## 5. GA4実装方式

`core/includes/ga4.php`（新規）＋`seo-settings.php`への`ga4_measurement_id`フィールド追加（既存のOGP/Search Console設定と同じOptions APIレコードに統合）。

- 管理画面（SEO設定画面）にGA4測定ID入力欄を追加。`^G-[A-Z0-9]{4,}$`形式のみ許可、不正値は保存拒否（`add_settings_error`）。
- 設定時のみ`wp_head`でGoogleの標準`gtag.js`ローダーを出力。空欄なら一切出力しない。
- 測定IDは`wp_json_encode()`で埋め込み、XSS対策済み（サニタイズ済みの`G-`形式のみ通過するため実質的に二重の防御）。
- 外部通信（Googleへの送信）が発生する旨を設定画面に明記した。
- Admin画面・REST・Feed等では出力されない（`wp_head`フックの性質上、これらのコンテキストでは呼ばれない）。

実装しなかったもの：GA4 API、Analyticsダッシュボード、自動レポート、Conversion分析、Search Console連携、OAuth、独自Cookie Consentプラットフォーム——指示どおり。

---

## 6. GA4 Plugin共存方針

Decision 018のSEO Plugin検出（既知リスト方式）と同じ設計を採用した。**採用方式：** `active_plugins`オプションを既知のAnalytics Plugin basenameと照合する小さな既知リスト（Site Kit by Google `google-site-kit/google-site-kit.php`、MonsterInsights `google-analytics-for-wordpress/googleanalytics.php`——2026-08-27時点で実際のPlugin本体ファイルを確認済み）のみを対象とし、検出時はASTREA自身のGA4タグ出力を抑制する。

**採用理由：** 既存のSEO Plugin検出と同じ枠組みを再利用することで実装・保守コストを最小化しつつ、二重計測という実害のあるリスクに対応できる。巨大な互換表・未知Pluginへの推測は行わない（Decision 018の運用方針を継承）。管理画面での注意表示（代替案として提示されていた）は、既に「既知SEO Plugin検出時のnotice」と同様の枠組みが整っているため、今回は静かな抑制のみとし、追加のUI要素は設けなかった（過剰な互換表・UIを避ける指示に従った）。

---

## 7. Core完全削除方式

`core/includes/data-deletion.php`（新規）。ASTREA管理メニュー内に「データ削除」という独立したサブメニューを新設し（通常設定画面から明確に分離）、以下の確認フローを実装した。

1. 削除対象・非削除対象を明記した警告表示（`notice-warning`）。
2. チェックボックス「上記の内容を理解し、元に戻せないことに同意します」。
3. テキスト入力で確認文字列「削除する」の完全一致を要求（大文字小文字・部分一致は不可）。
4. Capability（`manage_options`）＋Nonce＋POST限定。JavaScriptによる「入力したらボタンが有効化される」演出は採用せず（過剰な独自UI回避の方針、AGENTS.md/office-profile-admin.phpの既存方針を継承）、サーバー側で確認内容を再検証する方式とした（クライアント側の見た目に依存しない、本物のSecurity Control）。
5. 成功・失敗いずれもWordPress標準の`settings_errors`transientパターン（`options.php`が内部で使う方式と同一）で結果を表示。

---

## 8. 完全削除対象 / 保持対象

**削除する（Core所有データ）：**

- `astrea_core_office_profile`オプション
- Professional Profile（`astrea_professional`）全投稿＋postmeta
- Service（`astrea_service`）全投稿＋postmeta
- Price（`astrea_price`）全投稿＋postmeta
- FAQ（`astrea_faq`）全投稿＋postmeta、および`astrea_faq_category`タクソノミーの全ターム
- 問い合わせ（`astrea_inquiry`）全投稿＋postmeta
- `astrea_core_contact_settings`オプション
- `astrea_core_seo_settings`オプション（GA4測定IDを含む）
- `astrea_core_generated_pages`オプション（Setup生成ページの索引のみ）
- Cronスケジュール2件（Retention cleanup、digest通知）
- Transient3件（Contact到達可能性キャッシュ、保留中メール確認、Retention catch-up最終実行記録）

**保持する（Core所有データではない、通常のWordPressコンテンツ）：**

- Setupが生成した固定ページ（事務所概要・料金・お問い合わせ・ホーム）——Decision 016によりユーザーコンテンツ扱い
- 生成されたNavigation（`wp_navigation`投稿）
- メディアライブラリの添付ファイル（専門家の写真、OGP画像）——Decision 019の既存方針を継承

すべて`wp_delete_post($id, true)`（ゴミ箱を経由しない完全削除）を使用し、投稿削除に伴うMedia参照の解除（サムネイル参照の削除であり添付ファイル自体の削除ではない）はWordPress標準の`wp_delete_post()`の挙動に委ねた（Professional Profileの写真等が誤って削除されないことをTest済み）。

---

## 9. Transaction Safety

WordPressにはDB Transaction保証が無いことを踏まえ、`delete_all_core_data()`は各投稿タイプ・各オプションについて**現在存在するものを都度クエリして削除する**設計とした（あらかじめ計算した固定リストに対して操作するのではない）。これにより：

- 冪等：再実行しても削除するものが無ければ何も起きない（PHPUnitで確認済み）。
- 再実行可能：PHPタイムアウト等で処理が中断しても、次回実行時に残っているものから再開できる。
- 存在しないデータの削除試行でFatalしない：`get_posts()`が空配列を返すケース、`delete_option()`が偽を返すケース（既に存在しない）のいずれも正常に処理される。

---

## 10. Security

| 項目 | HOME | GA4 | 完全削除 |
|---|---|---|---|
| Capability | `manage_options` | `manage_options`（既存SEO画面） | `manage_options` |
| Nonce | `astrea_setup_generate_home` | 既存の`astrea_core_seo_settings_group`（Settings API標準） | `astrea_delete_all_core_data` |
| Sanitization/XSS | 該当なし（既存Pattern内容の転記のみ） | 正規表現`^G-[A-Z0-9]{4,}$`、不正値は保存拒否、実HTTPでスクリプトインジェクション試行が保存されないことを確認 | 確認文字列は`sanitize_text_field()`、厳密一致比較 |
| 既存HOME保護/冪等性 | PHPUnit＋実HTTPでA〜E全シナリオ確認 | — | 冪等性PHPUnitで確認 |
| 確認不一致拒否/CSRF/二重送信 | — | — | 誤った確認文字列でのPOSTがデータを一切変更しないことを実HTTPで確認。Nonce必須のためCSRF耐性あり。処理自体が冪等なため二重送信も安全 |
| Media非削除 | — | — | PHPUnit＋実HTTPで添付ファイル・生成済みPage・Navigationの生存を確認 |

---

## 11. Core非活性 / Theme単体

- Core無しTheme：既存のFatal無し保証は変更なし（本Construction ではTheme側のコード変更はゼロ）。
- Core非活性化：GA4タグの出力が停止すること、ASTREA管理UI（データ削除画面含む）が消滅すること、Home等のTheme表示は正常に継続することを実HTTPで確認した。
- Core再有効化：Office Profile等の保持データの表示が復帰することを確認した。

---

## 12. Test / CI結果

- PHPUnit：253 tests / 411 assertions、全PASS（新規：`SetupHomeTest.php`11件、`DataDeletionTest.php`9件、`SeoMetaTest.php`へのGA4関連7件追加、`SetupTest.php`のNavigation関連2件追加）。
- PHPCS：`core/`・`theme/`全体で0エラー。
- 実HTTP smoke-test（`tools/ci/smoke-test.sh`）：Part 10（BW-CE）を新規追加。**ローカルサンドボックスで`npx wp-env run cli`ラッパーの断続的なネットワーク不安定性（本セッションで複数回確認済みの既知事象）により複数回中断したが、製品コードの不具合は0件。** 中断のたびに`docker exec`による直接実機検証で個別の新機能をすべて確認し、その過程で以下3件の**smoke-test.sh自体の記述ミス**（製品コードではない）を発見・修正した。
  1. Navigationチェックリスト項目の検証で、リンクURLの長さを考慮しない狭すぎるgrep窓（80文字）を使っていたため、実際には正しく動作している「未完了」状態を誤って「完了」と誤検知していた。
  2. GA4 Plugin共存テストで`update_option("active_plugins", array("google-site-kit/..."))`と**置き換え**てしまい、`astrea-core`自身がこのオプションのリストに含まれていることを考慮せず、結果的にCore自体を無効化してしまっていた（`array_merge`/`array_diff`による追加・除去に修正）。
  3. `$ADMIN_HTML`の内容を`grep`へ誤ってファイル名として渡していた（`<<<`によるパイプ漏れ）。
  4. `fetch_no_fatal_any_status`のPath引数を省略した呼び出し（同関数はデフォルト値を持たないため`set -u`でクラッシュ）。

  これらすべてを修正した後、**smoke-test.sh Part 1-10の完全な連続PASSをローカルで確認した**（EXIT 0、149件のOK）。その後GitHub Actions CI（クリーンな別環境）でも最終確認する。

---

## 13. まとめ

HOME組み立て支援・GA4基本設定・Core完全削除UIの3領域すべてを実装し、Decision 029で正式化した5件のFIXを反映した。実機検証を通じて、Construction 008由来のNavigation自動フォールバック問題（チェックリスト誤判定）を正しく解消した。

**発見した要確認事項：** なし。本Construction Orderで新たに発見された不整合は、上記§12に記載したsmoke-test.sh自体の記述ミス（製品コードではない）のみであり、いずれもその場で修正済み。既存Decision・仕様との矛盾、Security問題、Data loss risk、FREE/PRO境界問題は発見されなかった。
