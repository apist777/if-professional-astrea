# Construction 029-CG — ASTREA Office Information Admin Foundation

- Start: 2026-09-13
- Modifier: Chloe
- Mode: **ローカル実装・テストのみ。commit / push / deployは一切実施していない。** Owner Admin Review前提。

---

## PHASE 0 — PRECHECK結果

```
$ git rev-parse HEAD && git rev-parse origin/main
4501067063411ac18d836c9fc6f4e9dddb016ebe（029-CF safety commit、両者一致、分岐なし）
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Owner既存差分（`HISTORY.csv`、`yamada-demo-export.wxr`）・Owner削除済み旧screenshots 235件・無関係な未追跡docsファイル数件を確認し、いずれも今回一切変更していない。

---

## PHASE 1 — 現行Admin構成調査、および重大な事前発見

実装着手前に既存ASTREA Core Admin構成を調査した結果、**「事務所情報」に相当する機能は既に実装済み**であることが判明した。

- ASTREAトップレベルメニュー自体（`core/includes/office-profile-admin.php`の`add_menu_page()`、slug `astrea-core`）が、事務所名・住所・電話番号・営業時間（曜日別open/close + 休業exceptions repeater）・SNSリンクを保持する画面であり、画面のH1見出しは既に「事務所情報」。
- データは単一option `astrea_core_office_profile`（`core/includes/office-profile.php`、Settings API経由）に保存。
- 代表者情報（氏名・肩書・資格・プロフィール・写真）は既にDecision 023により専門家プロフィール（`Astrea\Core\ProfessionalProfile`、CPT `astrea_professional`）側へ分離済みで、Office Profileには一切含まれない。
- 初回セットアップ（`setup-admin.php`のチェックリスト）も、この**同一画面**の先頭に`astrea_core_office_profile_page_top`アクションで描画されており、初回入力と事後編集は元から同一画面・同一optionに統合済み。
- ただしサイドバーの表示ラベルは「ASTREA」のみ（`add_menu_page`のmenu_titleが"ASTREA"）で、「事務所情報」という文字列は画面を開いた後のH1にしか出ない。

この発見をOwnerへ報告し、**新しい別サブメニュー＋別optionの新設は行わず、既存Office Profileを拡張する**方針の確認を得た（Ownerの明示的な選択：「既存Office Profileを拡張する（推奨）」）。これにより「事務所固有情報のSingle Source of Truth」を1つに保つ。

### frontendへの影響確認（既存`address`フィールドを変更しない理由）

```
$ grep -rn "profile\['address'\]" theme/ core/
core/includes/office-summary-block.php:73:	$address = trim( (string) $profile['address'] );
```
`office-summary-block.php`（フロント表示）と`seo-structured-data.php`（構造化データ）が既存の`address`を直接参照しているため、この既存フィールドは変更せず、新規フィールドは**追加**する方針を確定した。

---

## PHASE 2 — メニュー設計（実装内容）

`office-profile-admin.php`の`add_menu()`に、同一slug (`astrea-core`) を指す`add_submenu_page()`を追加し、サブメニューのラベルだけを明示的に「事務所情報」へ上書きした（WordPress標準の手法。ページ・option・コールバックは一切複製していない）。

```php
add_submenu_page(
    PAGE_SLUG,
    __( '事務所情報', 'astrea-core' ),
    __( '事務所情報', 'astrea-core' ),
    'manage_options',
    PAGE_SLUG,
    __NAMESPACE__ . '\\render_page'
);
```

**Menu Position: ASTREA配下、先頭（既存の専門家プロフィール一覧等より上）**

（当初「トップレベル自身の既定サブメニュー項目のため自動的に先頭表示される」と想定していたが、これは誤りだった。詳細と修正内容はFINALIZEセクション参照。）

---

## PHASE 3-7 — フィールド追加・住所モデル・営業時間方針・データモデル・サニタイズ

既存の単一option `astrea_core_office_profile` へ、以下6フィールドを**追加**（既存フィールドは無変更）：

| フィールド | キー | サニタイズ | 備考 |
| --- | --- | --- | --- |
| 郵便番号 | `postal_code` | `sanitize_text_field`（形式強制なし） | |
| 都道府県 | `prefecture` | `sanitize_text_field` | |
| 市区町村・番地 | `address_line` | `sanitize_text_field` | |
| 建物名・部屋番号 | `building` | `sanitize_text_field` | 任意 |
| FAX番号 | `fax` | 電話番号と同じ許容パターン（数字・全角数字・ハイフン・カッコ・+・スペース） | 任意。不正時は前回値へロールバック |
| 対応エリア | `service_area` | `sanitize_text_field` | 任意 |

**住所モデル**: 郵便番号/都道府県/市区町村番地/建物名を分離して保存（PHASE 4要求どおり）。ただし既存の単一`address`フィールドは、フロント表示・構造化データが依存しているため**そのまま維持**し、新フィールドは追加のみ。管理画面に「住所は現在サイトの表示に使われている項目です。...現時点ではまだサイト表示には反映されません。」という説明文を明記し、混乱を防止。

**営業時間・定休日**: 既存の曜日別`business_hours`（定休日チェック＋開始/終了時刻＋休業exceptions repeater）は自由入力テキストより高度に「営業時間」「定休日」の両方を扱っているため、**自由入力の重複フィールドは追加していない**（Owner承認済みの決定）。既存データ構造・既存テストは無変更。

**電話番号のサニタイズ共通化**: `sanitize_phone()`を`sanitize_phone_like(array $input, array $existing, string $field, string $label)`へ一般化し、`phone`と`fax`の両方で再利用。ロールバック挙動・エラーメッセージの意味は既存の電話番号バリデーションと同一。

**代表者情報の非重複**: 郵便番号等の追加時、代表者名・肩書・資格・プロフィール・写真に相当するフィールドは一切追加していない（`sanitize()`はこれらのキーが送信されても出力に含めない。テストで確認済み）。

---

## PHASE 8-9 — セキュリティ・Admin UI

- **Capability**: 既存の`manage_options`をそのまま踏襲（新規フィールドも同一フォーム・同一Settings APIグループ内）。
- **Nonce/CSRF**: 既存どおり`settings_fields(SETTINGS_GROUP)`が`options.php`経由で自動的に処理（新規フィールドも同一フォーム内のため、追加の実装不要）。
- **Sanitization**: 全新規フィールドに`sanitize_text_field(wp_unslash(...))`または電話番号共通パターンを適用。
- **Escaping**: 全出力を`esc_attr()`/`esc_html()`/`esc_url()`で処理（既存規約と同一）。
- **Admin UI**: セクションを「基本情報」「所在地」「連絡先」「営業情報」「SNS」に再編成（PHASE 9の想定構成に合わせた）。奇抜な独自JS/SPAは追加していない（既存の「過剰なJavaScript UIを導入しない」方針を踏襲）。

---

## PHASE 10 — Responsibility Notice

フォーム下部に、専門家プロフィール一覧へのリンク付き案内文を追加：

> 「代表者名・肩書・所有資格・プロフィール・代表者写真は専門家プロフィールから編集できます。」（`edit.php?post_type=astrea_professional`へのリンク）

---

## PHASE 11 — Save UX（実機検証）

wp-envのdevelopment環境（`http://localhost:8888/wp-admin/`）に対しPlaywrightで実際にログイン・入力・保存・再読込を検証した。

**検証中に発見した問題（テストスクリプト側のミス、実装のバグではない）**: 初回テストスクリプトが誤って`#submit`セレクタを使用したため、同一ページ上に存在する**別の**フォーム（setup-admin.phpの「基本ページを作成する」ボタン、これも`id="submit"`）を誤ってクリックしてしまい、重複下書きページ（ID 2249-2251）と`astrea_core_generated_pages`オプションが意図せず作成された。これはOffice Profile実装の不具合ではなく、同一ページ上のsubmitボタンIDが偶然重複していたことによるテストスクリプトの誤操作であり、実際のOwner操作（画面上の「事務所情報を保存」ボタンをクリック）では発生しない。発生後、直ちに調査の上、作成された3件の重複下書きページと当該optionを削除し、環境を実行前の状態へ復元した（`astrea_core_office_profile`本体は無傷だったことも確認済み）。

修正後、正しいセレクタ（`input[value="事務所情報を保存"]`）で再検証：

- 郵便番号・都道府県・市区町村番地・建物名・FAX番号・対応エリアを入力し保存 → 保存成功。
- 別リクエストでの再読込（フレッシュなGETナビゲーション）後も、全フィールドの値が保持されていることを確認。
- 空欄のoptionalフィールド（建物名等）も正常に空文字として保存・復元されることを確認。

**Save: PASS / Reload Persistence: PASS**

---

## PHASE 12 — Starter Import互換性

Starter Import Engine（Construction 029-D以降）へは一切接続していない。Import lifecycle marker・Ownership markerは無変更（`core/includes/starter-import-*.php`は今回のdiffに含まれない）。

---

## PHASE 13-14 — Frontend Binding境界 / 後方互換性

- 今回の変更後もHomeページのフロント表示は既存の`address`（単一フィールド）のみを表示し、新規保存した`postal_code`等の値は一切フロントに現れないことをHTTP応答で直接確認した。
- Office Profile optionが未設定のサイトでも、`merge_with_defaults()`が新フィールドを含む全キーを自動的に埋めるため（`array_merge($defaults, $stored)`）、エラーは発生しない。既存のマイグレーション機構（schema_version）は変更していない。

```
$ curl -s http://localhost:8888/ | grep -o "ASTREA行政書士事務所\|東京都千代田区丸の内一丁目1番1号 丸の内センタービル8F\|100-0001\|東京都・神奈川県・埼玉県・千葉県"
ASTREA行政書士事務所
東京都千代田区丸の内一丁目1番1号 丸の内センタービル8F
```
（新規保存した`100-0001`・対応エリア文字列はフロントに一切出現しない = Frontend全面連動なし、確認済み）

**Existing Frontend Changed: NO**

---

## PHASE 15 — データ削除

`core/includes/data-deletion.php`は`astrea_core_office_profile`オプション全体を`delete_option()`で削除する設計であり（フィールド単位ではなくoption単位）、新option化していないため**変更不要**。既存の`DataDeletionTest.php`はそのままPASSすることを確認済み。

---

## PHASE 16 — Tests

`tests/OfficeProfileTest.php`へ9件のテストを追加（Construction 029-CGセクションとして明記）：

1. `test_sanitize_accepts_new_location_and_contact_fields` — 新規フィールドの正常受理
2. `test_sanitize_new_fields_default_to_empty_string_when_omitted` — 未送信時の空文字デフォルト
3. `test_sanitize_rejects_invalid_fax_and_keeps_previous_value` — FAX不正値のロールバック
4. `test_sanitize_strips_tags_from_new_text_fields` — タグ除去
5. `test_sanitize_does_not_introduce_any_representative_fields` — 代表者情報の非重複（責務境界）
6. `test_new_fields_persist_across_save_and_reload` — 保存→再取得の永続化
7. `test_admin_page_renders_new_construction_029cg_fields` — 新フィールドのHTML出力確認
8. `test_admin_page_links_to_professional_profile_for_representative_fields` — 専門家プロフィールへのリンク存在確認
9. `test_office_information_submenu_is_registered_with_explicit_label` — サブメニュー登録・ラベル確認

capability/nonce拒否については、既存の`test_admin_page_denies_non_admin_users`（capability）と、Settings APIの`options.php`が提供する標準nonce機構（既存の`OfficeProfileTest.php`の慣習どおりPHPUnitでは直接検証せず、`tools/ci/smoke-test.sh`が実HTTPレベルで検証する既存の切り分け方針を踏襲）で担保。

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter "OfficeProfileTest|DataDeletionTest"
OK (64 tests, 131 assertions)
```

---

## PHASE 17 — Existing Test Baseline

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter "StarterImportStateTest|StarterImportPreflightTest"
OK (57 tests, 146 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 465, Assertions: 843, Errors: 3.
1) SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback
2) SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image
3) SetupTest::test_checklist_seo_og_image_item_reflects_setting
```
既知の3件のみ（内容・件数とも029-CF baselineと同一）。新規テスト9件を含め総数456→465に増加。**新規failure/errorは0件。**

### PHPCS

```
$ vendor/bin/phpcs   （theme/ core/ のみ、phpcs.xmlの既定スコープ）
core/includes/starter-import-state.php の既知のenum $this false positive（Construction 029-Bで文書化済み、未修正・許容）以外、エラー0件・警告0件。
```
（`tests/`はphpcs.xmlの対象外 — Construction 029-Cで確認済みの既存方針どおり）

---

## PHASE 18 — Local Manual Test（実機検証）

`http://localhost:8888/wp-admin/` で実施:

- ASTREAサブメニューに「事務所情報」が表示されることを確認（スクリーンショット参照）。
- 郵便番号・都道府県・市区町村番地・建物名・FAX番号・対応エリアにテスト値を入力し保存。
- 別ナビゲーションでの再読込後も同一値が保持されることを確認。
- 保存後もHomeページ（`http://localhost:8888/`）の表示が変化していないことを確認（既存`address`のみ表示継続）。

スクリーンショット（`docs/research/screenshots/029-CG/`）:
- `029cg-office-information-before.png` — 入力前
- `029cg-office-information-after-save.png` — 保存直後
- `029cg-office-information-after-reload.png` — 再読込後（永続化確認）

---

## PHASE 19 — Version Policy

- Theme: 変更なし。1.0.3のまま。
- Core: ソースコード変更あり（`core/includes/office-profile.php`、`office-profile-admin.php`）。ただしOwner Review前のためversion bumpは行っていない。1.0.1のまま維持し、bump要否は次工程でOwnerと判断する。

---

## PHASE 20 — Git Policy

Owner Review Gate前のため、commit / push / deployは一切実施していない。`git add .`も使用していない。

---

## 変更ファイル一覧

**Gitで追跡される変更（3ファイル）:**
- `core/includes/office-profile.php` — 新規フィールド6件のデフォルト値・サニタイズ追加、電話番号サニタイズの共通化
- `core/includes/office-profile-admin.php` — サブメニューラベル追加、セクション再編成、新規フィールドUI、専門家プロフィールへの案内リンク追加
- `tests/OfficeProfileTest.php` — 新規テスト9件追加

**新規追加（未追跡）:**
- `docs/research/2026-09-13_construction_029cg_office_information_admin_foundation.md`（本レポート）
- `docs/research/screenshots/029-CG/`（4枚。メニュー順序修正後の最終状態を追加）

**変更していないもの:**
- `core/includes/data-deletion.php`（既存の設計で新フィールドを自動的にカバーするため変更不要）
- Starter Import関連ファイル一式
- Theme（`theme/`配下は無変更）

---

## Construction 029-CG — Admin Review Ready

```
Office Information Menu: PASS
Menu Position: ASTREAサブメニュー先頭（専門家プロフィール一覧等より上）
Fields: 10（事務所名・郵便番号・都道府県・市区町村番地・建物名部屋番号・電話番号・FAX番号・営業時間/定休日[既存の曜日別構造]・臨時休業[既存exceptions]・対応エリア）
Representative/Profile Duplication: 0
Save: PASS
Reload Persistence: PASS
Sanitization: PASS
Nonce Protection: PASS（Settings API標準機構、既存挙動を継承）
Capability Protection: PASS
Existing Frontend Changed: NO
Starter Import Regression: 0
029-B/C Tests: 57/57 PASS
Core Tests: 462/465 PASS（既知エラー3件、内容・件数unchanged）
Theme Modification: NONE
Theme Version: 1.0.3
Core Modification: YES
Core Version: 1.0.1（Review用に据え置き、bump未実施）
Local Admin URL: http://localhost:8888/wp-admin/
Commit: NOT DONE
Push: NOT DONE
Deploy: NOT DONE
Production: UNCHANGED
Owner Admin Review: REQUIRED
```

Ownerに「ASTREA → 事務所情報」を実際に開いていただき、ご確認をお願いします。

**STOP。Owner Admin Reviewを受けるまでCLOSEしない。**

---

## FINALIZE / CLOSE（Owner Admin Review: PASS）

- Owner Admin Review: **PASS**（Owner確認結果:「修正できるようになった」）
- Construction Status: **CLOSED**

### PHASE 1 — Precommit Check（再確認）

```
$ git rev-parse HEAD && git rev-parse origin/main
4501067063411ac18d836c9fc6f4e9dddb016ebe（029-CF safety commit、両者一致、分岐なし）
```
Owner差分（`HISTORY.csv`、`yamada-demo-export.wxr`）・削除済み旧screenshots 235件・無関係untracked 8件を再確認し、いずれも今回のcommit対象から除外した。

### PHASE 2 — Final Admin Verification中に発見した不具合と修正

Owner Admin Review PASSを受けての最終確認中、**「事務所情報」サブメニューが要求どおりの先頭位置に表示されていない**ことを発見した。

**原因**: WordPress core は `専門家プロフィール一覧`等7件のCPT一覧サブメニューを、`admin_menu`アクションが発火する**前**に`$submenu['astrea-core']`へ追加している。そのため、`admin_menu`アクション内で呼ばれる`add_submenu_page()`（今回追加した「事務所情報」エントリ）は、フック優先度に関わらず必ずそれら7件より後ろに追加される。当初の「トップレベル自身の既定サブメニューだから自動的に先頭に来る」という前提は誤りであり、実機確認（Playwright）で以下の並びになっていることを確認した：

```
専門家プロフィール一覧, 取扱業務一覧, 料金一覧, FAQ一覧, 対応事例一覧, 実績一覧, お客様の声一覧, 事務所情報, 問い合わせ, SEO, データ削除
```

**修正**: `office-profile-admin.php`の`add_menu()`内で`add_submenu_page()`実行後、新規関数`move_office_information_to_top()`を呼び出し、`$submenu['astrea-core']`配列を直接操作して「事務所情報」エントリ（`menu_slug === PAGE_SLUG`で識別）を先頭へ移動する処理を追加した（WordPressにはサブメニュー順序を制御する公式APIが存在しないため、直接の配列操作は多くのプラグインで採用される標準的な手法）。

修正後、実機で確認した並び：
```
事務所情報, 専門家プロフィール一覧, 取扱業務一覧, 料金一覧, FAQ一覧, 対応事例一覧, 実績一覧, お客様の声一覧, 問い合わせ, SEO, データ削除
```

再発防止のため、CPT一覧サブメニューが既に存在する状態を模したテスト`test_office_information_submenu_is_positioned_first_even_when_cpt_submenus_already_exist`を追加した。

**PHPCS**: `$submenu[PAGE_SLUG] = array_values(...)`の再インデックス代入に対し`WordPress.WP.GlobalVariablesOverride.Prohibited`が検出されたため、理由を説明するコメントとともに`phpcs:ignore`を付与（WordPress自身のグローバルを並べ替える正当な操作であり、無関係なデータで置き換えるものではないことを明記）。

### 再検証結果（修正後、FINALIZE時点）

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter "OfficeProfileTest|DataDeletionTest"
OK (65 tests, 135 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit --filter "StarterImportStateTest|StarterImportPreflightTest"
OK (57 tests, 146 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 466, Assertions: 847, Errors: 3.
1) SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback
2) SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image
3) SetupTest::test_checklist_seo_og_image_item_reflects_setting
```
既知の3件のみ（内容・件数とも不変）。新規テスト1件を追加し総数465→466。**新規failure/errorは0件。**

```
$ vendor/bin/phpcs（theme/ core/、phpcs.xml既定スコープ）
core/includes/starter-import-state.php の既知enum $this false positive以外、エラー0件・警告0件。
```

### PHASE 3 — Data Model Final Check

```
$ npx wp-env run cli wp option get astrea_core_office_profile --format=json
```
単一option `astrea_core_office_profile` に全フィールドが格納されていることを確認。新規重複option: **0**。代表者関連フィールドの重複: **0**（`title`/`qualification`/`profile`/`photo`等は`sanitize()`の出力に一切含まれないことをテストで保証）。既存フィールド（`office_name`/`address`/`phone`/`business_hours`/`sns_links`）は無変更で共存。

**Single Source of Truth: astrea_core_office_profile（確定）**

### PHASE 4 — Frontend Boundary（再確認）

```
$ curl -s http://localhost:8888/ | grep -o "ASTREA行政書士事務所\|東京都千代田区丸の内一丁目1番1号 丸の内センタービル8F\|100-0001"
ASTREA行政書士事務所
東京都千代田区丸の内一丁目1番1号 丸の内センタービル8F
```
新規保存したpostal_code等の値はフロントに一切出現しない。**Frontend regression: 0**

### PHASE 10 — Post-Push Check（事前確認）

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/
200
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/wp-admin/
302（ログイン画面への正常リダイレクト）
```

### 変更ファイル一覧（最終・FINALIZE時点）

**Gitで追跡される変更（3ファイル、PHASE 2修正を含む最終版）:**
- `core/includes/office-profile.php`
- `core/includes/office-profile-admin.php`（メニュー先頭移動処理を含む）
- `tests/OfficeProfileTest.php`（メニュー位置の回帰テストを含む、計10件追加）

**新規追加:**
- `docs/research/2026-09-13_construction_029cg_office_information_admin_foundation.md`
- `docs/research/screenshots/029-CG/`（4枚: before, after-save, after-reload, final-menu-order）

### Construction 029-CG — CLOSED

```
Owner Admin Review: PASS
Office Information Admin: PASS
Fields: 10
Single Source of Truth: astrea_core_office_profile
Representative/Profile Duplication: 0
Save / Reload: PASS
029-B/C Tests: 57/57 PASS
Core Tests: 463/466 PASS
Known Errors: 3 unchanged
Theme: 1.0.3
Core: 1.0.1
Local Preview: PASS
Production: UNCHANGED
Deploy: NOT RUN
```

次工程: Office Information → Frontend Binding（まず事務所名の利用箇所棚卸しから）
