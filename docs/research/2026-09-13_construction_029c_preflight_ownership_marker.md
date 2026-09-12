# Construction 029-C — Preflight / Ownership Marker
ASTREA Starter Import Safety Gate / Ownership Foundation

- Start: 2026-09-13
- Modifier: Chloe
- Mode: **ローカル実装・テストのみ。commit / push / deployは一切実施していない。** Owner Review前提。

---

## PHASE 0 — PRECHECK結果

```
$ git rev-parse HEAD && git rev-parse origin/main
edaeb594110f36da991949623df400ec14068602（両者一致、分岐なし）
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`）は無変更のまま。

**029-B既存3 test errors baseline（作業開始前に確認）:**
```
Tests: 416, Assertions: 704, Errors: 3.
1) SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback
2) SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image
3) SetupTest::test_checklist_seo_og_image_item_reflects_setting
```

既存慣習を確認した：
- Capability: `manage_options`（`data-deletion.php`の既存の破壊的操作と同じ基準）。
- Nonce: `wp_nonce_field()`/`check_admin_referer()`パターン（`case-admin.php`等）。ただしAdmin UI/endpointが存在しない029-Cでは、nonce自体はこのConstructionの対象外（PHASE 16で明記）。
- Theme検出: 既存に専用の仕組みはなく、`get_stylesheet()`/`get_template()`を新たに採用。
- ブロック登録名: `astrea/office-summary`・`astrea/office-hours`・`astrea/office-sns`・`astrea/contact-form`・`astrea/price-list`（Starter Buildが実際に使う5ブロック）。

**重要な環境確認**: `tests/bootstrap.php`は「ASTREA Themeは意図的にロードしない」と明記されているが、実際にはテスト環境（wp-envのtest containers）にもTheme本体がファイルとして存在し、`switch_theme('astrea')`で有効化可能であることを確認した（`wp_get_themes()`で`astrea`が一覧に含まれ、切替後`get_template()`/`get_stylesheet()`とも`'astrea'`、バージョンも`1.0.3`と正しく読み取れる）。これにより、CHECK 06（ASTREA Theme）を含む「valid environment」シナリオのテストが可能になった。

---

## PHASE 1-2 — PREFLIGHT RESULT MODEL / CHECK MODEL

Construction 029-Bの`StateResult`と同じ設計思想（単なるbooleanではなく構造化された結果）を踏襲した。

```
CheckStatus enum: PASS / WARNING / BLOCK
PreflightCheck: { id, status, code, diagnostic, evidence }
PreflightResult: { status, can_start, checks[], warnings[], blockers[], site_state }
```

`PreflightResult::from_checks()`が、個々のcheckのstatusから集約結果（status/can_start/warnings/blockers）を決定論的に導出する——呼び出し側が自分で集計しないため、個別checkと集約結果の間にズレが生じない設計。

集約ルール：BLOCKが1件でもあればBLOCK（can_start=false）、無ければWARNINGが1件でもあればWARNING（can_start=true維持）、どちらも無ければPASS。

**ファイル構成（1ファイル1構造体、Construction 029-Bと同じ規約）:**
- `starter-import-preflight-result.php` — `CheckStatus` enum
- `starter-import-preflight-check.php` — `PreflightCheck` class
- `starter-import-preflight-aggregate.php` — `PreflightResult` class

---

## PHASE 3 — REQUIRED PREFLIGHT CHECKS（12項目、実装内容）

| # | id | 内容 | BLOCK条件 |
| --- | --- | --- | --- |
| 01 | `site_state` | Construction 029-Bの`StateResult`をそのまま利用、**Preflight側で再判定しない** | `allows_new_import()`がfalse |
| 02 | `wordpress_version` | `$wp_version` vs `MINIMUM_WP_VERSION`(7.0) | 下回る場合 |
| 03 | `php_version` | `PHP_VERSION` vs `MINIMUM_PHP_VERSION`(8.3) | 下回る場合 |
| 04 | `multisite` | `is_multisite()`の独立した明示的再確認（CHECK 01のUNKNOWN判定に加え） | true |
| 05 | `astrea_core` | Starter Importが依存する具体的Core API（`Setup\GENERATED_PAGES_OPTION`定数・`Setup\page_still_exists()`関数・`detect_state()`関数）の存在確認 | いずれか欠落 |
| 06 | `astrea_theme` | `get_template()`/`get_stylesheet()`をスラグで比較（表示名文字列不使用）。子テーマは無条件BLOCK。Theme version vs `MINIMUM_THEME_VERSION`(1.0.3) | Theme不一致／子テーマ／バージョン不足 |
| 07 | `required_cpt_and_blocks` | 7 ASTREA CPT + 5 Dynamic Block（Starter Buildが実際に使うもののみ）の登録確認 | いずれか未登録 |
| 08 | `capability` | `current_user_can('manage_options')` | false |
| 09 | `import_lifecycle_marker` | 029-Bの`read_import_status()`＋**生option値の追加確認**（後述、重要な設計判断） | not_started以外 |
| 10 | `concurrency_lock` | lock option読み取り | lock保持中（stale含む） |
| 11 | `upload_environment` | `wp_upload_dir()`の`error`確認（読み取りのみ） | error非空 |
| 12 | `storage_environment` | CHECK 01が既に`gather_evidence()`（複数option/post読み取り）を完走した事実自体をevidenceとする。**試し書きは一切行わない** | 到達しない（読み取り自体が失敗すればCHECK 01より前に例外） |

### CHECK 09の重要な設計判断（実装中に発見した課題への対応）

Construction 029-Bの`read_import_status()`は「不正な文字列は`null`（＝マーカーなしと同じ）に丸める」という設計（`ImportStatus::tryFrom()`の性質上、`site state`判定にとってはこれが正しい）。しかしPreflightの観点では、「マーカーが未設定」と「マーカーに解釈不能な値が書き込まれている」は全く別の状況——後者は明確な異常であり、区別せずBLOCKする必要がある（Order CASE P12: invalid lifecycle marker → BLOCK）。

**029-Bの`read_import_status()`自体は一切変更していない**（Construction 029-Bを改造しない原則）。代わりに、CHECK 09内で生のoption値を追加で読み取り、「生の値が存在するのにデコード結果がnull」というケースを`IMPORT_LIFECYCLE_INVALID`として明示的にBLOCKする実装にした。

---

## PHASE 4 — PREFLIGHT READ-ONLY GUARANTEE

`run_preflight()`は`wp_upload_dir()`（WordPress標準APIが通常動作の一環としてディレクトリ確認/作成を行う可能性があるが、これは意図的なtest writeではない）を除き、一切の書き込み系WordPress関数を呼ばない。CASE P15（100回繰り返し）で、post/page件数・option値（`IMPORT_STATUS_OPTION`・`LOCK_OPTION`・`IMPORT_GENERATED_OPTION`）が完全に一致することを確認した。

---

## PHASE 5 — START AUTHORIZATION CONTRACT

`begin_operation()`（`starter-import-operation.php`）が、Preflight結果を無視してImporter engineを直接起動できないようにする「認可された開始」の唯一の入口として設計されている。Preflightが`can_start=false`を返せば、`begin_operation()`はその時点で失敗を返し、Lock取得すら試みない。将来の029-D以降の実行エンジンも、この関数を経由しない限りmarker/lockの正当な状態遷移を得られない構造になっている（defense-in-depth: 029-D側の実行エンジンでも独自に状態確認することが推奨されるが、少なくともこの入口では確実にPreflightを強制する）。

---

## PHASE 6 — LIFECYCLE MARKER SCHEMA（正式FIX）

既存の029-B read contract（`IMPORT_STATUS_OPTION` = `astrea_starter_import_status`、`IMPORT_VERSION_OPTION` = `astrea_starter_import_version`）をそのまま踏襲し、新たに以下を追加した：

```
astrea_starter_import_operation （新規、OPERATION_OPTION定数）
{
  operation_id:    string,
  starter_version: string|null,
  started_at:      string (ISO8601 UTC),
  updated_at:      string,
  completed_at:    string|null,
  failed_step:     string|null,
  last_error_code: string|null,
}
```

PIIや巨大ログは含めない（`failed_step`/`last_error_code`は短い機械可読ラベルのみ、スタックトレース等は保存しない）。

---

## PHASE 7 — MARKER WRITE API（Transition Guard）

許可される遷移のみ実装（`starter-import-lifecycle.php`）：

```
not_started / (absent) -> running    (write_running_marker)
running                -> failed     (write_failed_marker)
running                -> completed  (write_completed_marker)
```

禁止（拒否）：`completed -> running`、`failed -> running`（029-Gまでretry未実装）、不正な現在値からの遷移全て。

**Operation ID一致チェック**: `write_failed_marker()`/`write_completed_marker()`は、渡された`operation_id`が現在の`OPERATION_OPTION`に記録されたものと一致する場合のみ許可する（`is_owning_operation()`）。これにより「別のoperationが他のoperationのmarkerを書き換える」ことを防ぐ——Lock所有者一致チェック（PHASE 9）と同じ思想。

---

## PHASE 8-9 — ATOMICITY / RACE SAFETY / CONCURRENCY LOCK

**Race safetyの核心**: `acquire_lock()`は`add_option()`を使用する。WordPressの`add_option()`は`wp_options.option_name`のUNIQUE制約に依存した実装であり、同時に2つのリクエストが呼んでも、DBレベルで片方だけが成功する——`get_option()`→`update_option()`という読み取り→書き込みの2ステップ（その間にrace windowが生じる）ではなく、単一のatomicな試行である点が重要（Order「単純なget_option()+update_optionだけでrace-safeと主張しない」に対応）。

Lock schema（`LOCK_OPTION` = `astrea_starter_import_lock`）：
```
{ operation_id: string, created_at: string, updated_at: string }
```

- `release_lock()`: 呼び出し元の`operation_id`が現在のlockと一致する場合のみ解放。他人のlockは解除できない。
- `is_lock_stale()`: `updated_at`から`LOCK_STALE_AFTER_SECONDS`(3600秒)経過していれば stale と判定するが、**自動解除は一切しない**（検出のみ、Recoveryは029-Gの責務として明確に切り分け）。

`begin_operation()`は、Preflight（時点のスナップショット）→ operation_id生成 → **lock取得**（真のrace-safeポイント）→ **lock取得後に再度site stateを確認**（Preflightとlock取得の間の race window を閉じる）→ marker書き込み、という順序で実装した。途中で失敗した場合、直前に取得したlockは確実に解放し、孤立したlockを残さない。

---

## PHASE 10 — OPERATION ID

`wp_generate_uuid4()`（WordPress標準API、WP 4.7+）を使用。セキュリティトークンとしては使わない（marker/lock/ownership registryの相関にのみ使用）ことを明記した。

---

## PHASE 11-13 — GENERATED OWNERSHIP SCHEMA / OBJECT-LEVEL OWNERSHIP / INVARIANTS

Construction 029-A/029-Bが定義した`IMPORT_GENERATED_OPTION`契約を正式スキーマ化した：

```
{
  schema_version:  1,
  starter_version: string|null,
  operation_id:    string|null,
  objects: {
    posts:       [int, ...],
    attachments: [int, ...],
    terms:       [int, ...],
    navigation:  [int, ...],
  },
  options: [string, ...],
}
```

- `register_generated_object(string $type, int $id)`: `$type`は`OWNERSHIP_OBJECT_TYPES`（posts/attachments/terms/navigation）のみ許可、それ以外は拒否（CASE O05）。同一IDの重複登録は増殖しない（CASE O03）。
- `register_generated_option(string $option_name)`: `OWNERSHIP_ALLOWED_OPTIONS`という明示的ホワイトリスト（既存の`astrea_core_generated_*`パターン + `build-content.php`が設定するidentity系option）に一致する場合のみ許可。任意のoption名注入は拒否（CASE O06）。
- レジストリはoperation完了後もクリアされない（将来のReset/Reinstallの対象として保持）。
- **Ownership ≠ Permission to overwrite**: このモジュールはレジストリへの登録のみを行い、後続の上書き・削除権限を一切付与しない（コード上、この登録APIを呼び出すだけでは何のmutation権限も生まれない）。

Object-level ownership meta（`_astrea_starter_generated`等）は、Order自体が「029-Dで生成時に実データを見て決定」としているため、**029-Cでは実装していない**（PHASE 14で詳述）。

---

## PHASE 14 — USER EDIT DETECTION CONTRACT（判断: B案採用）

Order提示の2択：
- A. 029-Cでfingerprint contractまで採用
- B. 029-Dでcontent生成時に実データを見て設計

**B案を採用した。** 理由：029-Cの時点ではまだ実際のStarter生成コンテンツ（029-Dで実装予定）が存在せず、fingerprintの正規化方式（WordPressのblock serializationやテーマ由来の自動整形によるfalse positiveの回避策）を実データなしに確定するのはリスクが高いと判断した。

**architecture debtとして残さないための明確なhandoff**: Ownership Registry（PHASE 11）のobjectレベルの構造は、将来`fingerprint`等の追加フィールドを個々のオブジェクトエントリに持たせられるよう、現状は単純なID配列（`array<int>`）だが、029-Dで「IDだけでなく生成時の状態を記録する必要がある」と判断された場合、スキーマの`schema_version`を上げて拡張することを前提とした設計にしてある（`get_ownership_registry()`は不明なキーを無視して安全にフォールバックする設計のため、将来のフィールド追加は後方互換的に行える）。029-Dは、実際にどのCPT/pageにfingerprintを持たせるべきかを、生成する実データを見た上で決定する。

---

## PHASE 15 — START TRANSACTION FOUNDATION

`begin_operation()`（`starter-import-operation.php`）として実装した。手順は PHASE 9 に記載の通り。**Starter Content（page/CPT/media/navigation）は一切生成しない**——テストでも実際のpost生成を伴わず、生成されるのはmarker/lock/registryのoption値のみであることを確認済み。

---

## PHASE 16 — SECURITY

- Capability: `manage_options`（CHECK 08）。
- Operation IDの性質を明記：セキュリティトークンではない（PHASE 10）。
- Option/postメタのサニタイズ: register系APIは全て許可リストによる入力検証を行い、任意のoption名・object typeを拒否する（CASE O05/O06）。
- Direct access prevention: 全ファイルに`if ( ! defined( 'ABSPATH' ) ) { exit; }`。
- **Nonce/CSRF/AJAX/RESTエンドポイントの検証は、この029-Cのdomain/service layerには一切実装していない**——Admin UI/エンドポイント自体が存在しないため、Order自身の指示通り「029-H/endpoint layerへ明確にhandoff」する。
- Remote request: 0件（grep確認済み）。
- Path traversal: ファイルパスを扱う箇所はなし（`wp_upload_dir()`の結果を読み取るのみ）。

---

## PHASE 17 — TEST MATRIX（実行結果）

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter StarterImportPreflightTest
PHPUnit 9.6.36
........................................                          40 / 40 (100%)
OK (40 tests, 105 assertions)
```

### PREFLIGHT（P01-P15）

| CASE | 内容 | 結果 |
| --- | --- | --- |
| P01 | FRESH + valid environment | PASS（can_start=true） |
| P02 | ASTREA_READY + valid environment | PASS（can_start=true） |
| P03 | EXISTING_CONTENT | BLOCK |
| P04 | COMPLETED | BLOCK |
| P05 | PARTIAL | BLOCK |
| P06 | UNKNOWN | BLOCK |
| P07 | PHP below minimum | **技術的制約**（後述） |
| P08 | WordPress below minimum | BLOCK（`$wp_version`グローバル変数を一時的に書き換えて検証） |
| P09 | ASTREA Theme inactive | BLOCK |
| P10 | insufficient capability | BLOCK（subscriberロールで検証） |
| P11 | active lock | BLOCK |
| P12 | invalid lifecycle marker | BLOCK（PHASE 3で詳述した設計判断の検証） |
| P13 | upload environment unusable | 実際にunusableな状態を試し書きなしで再現するのは困難なため、正常環境でPASSすることのみ確認（試し書き自体がOrderで禁止されているため、「壊す」テストが原理的に作れない） |
| P14 | Multisite | **技術的制約**（後述） |
| P15 | Preflight 100x | PASS（mutation 0） |

**CASE P07（PHP below minimum）の技術的制約**: `PHP_VERSION`はPHPのコンパイル時定数であり、テスト実行時に値を変更する手段がない。そのためend-to-endの`run_preflight()`シナリオとしては検証不能。代わりに、実check関数が内部で使う`version_compare()`という標準比較ロジック自体を仮想的な入力（`'8.2.0'`）で検証し、ロジックの正しさを担保した。

**CASE P14（Multisite）の技術的制約**: `WP_UnitTestCase`はsingle-site実行であり、`is_multisite()`を強制的にtrueにする手段がない（真のマルチサイトインストールが必要）。代わりに、`check_multisite()`自体が非マルチサイト環境で正しくPASSを返すことを確認し、実際のBLOCK判定ロジック自体はConstruction 029-Bの`test_multisite_is_always_blocked_regardless_of_content`（`Evidence`オブジェクトを直接構築してテスト）で既に検証済みであることを明記した。

### MARKER（M01-M06）

全6ケースPASS。`completed→running`・`failed→running`は拒否を確認、operation_id不一致による拒否も追加で検証した。

### LOCK（L01-L06）

全6ケースPASS。stale lock検出（L05）は「検出されるが自動解除されない」ことを確認、L06（同時begin）は`add_option()`の atomic性により後勝ちではなく**先勝ち**（最初に成功した方が所有者になる）ことを確認した。

### OWNERSHIP（O01-O07）

全7ケースPASS。O05/O06で不正な入力（未知のobject type・ホワイトリスト外のoption名）が確実に拒否されることを確認した。

### begin_operation() / Failure Injection（追加）

- 有効な環境でのbegin_operation成功（lock取得・marker書き込み・registry stampまで確認）。
- PreflightでBLOCKされる場合、lockもmarkerも一切作られないことを確認。
- Lock既取得状態でのbegin_operation失敗（この場合Preflight自体のCHECK 10で先にBLOCKされる設計になっているため、「lock取得だけ失敗する」独立したシナリオは通常操作では発生しない——Preflightがそもそも防ぐ）。
- race window shrink：Preflight後にunaccounted contentが追加された状況を模擬し、孤立したlock・偽のrunning markerが残らないことを確認。

---

## PHASE 18 — MUTATION BOUNDARY

| 種別 | 結果 |
| --- | --- |
| Preflight | read-only、mutation 0 |
| Ownership read | read-only、mutation 0 |
| Marker lifecycle tests | 意図的なtest mutation（テスト対象の性質上） |
| Lock tests | 意図的なtest mutation（同上） |
| Production | mutation 0（本番環境には一切接続していない） |
| Starter content generated | 0 |

---

## PHASE 19 — FAILURE INJECTION

`begin_operation()`の各境界で失敗を模擬し、以下を確認した：
- Double-running state: なし（PreflightのCHECK 09/10が事前にBLOCK）。
- Orphan lock: `begin_operation()`内の全失敗パスで、直前に取得したlockを確実に解放するよう実装・テストで確認。
- False completed: markerのtransition guardにより、running状態を経ずにcompletedへ到達する経路は存在しない。
- Import content generation: 0件（全テストを通じて実際のpost/page/attachment生成は一切発生していない）。

**029-Gへの申し送り**: 本Constructionのfailure injectionはPreflight/Lock/Marker/begin_operationの境界に限定されている。実際のコンテンツ生成中の部分的失敗（029-D以降が実装するStep単位の処理が途中で落ちるケース）からの回復戦略は、Construction 029-Bが既に記録した`generated`マーカーベースのretry設計（029-B報告書PHASE 12参照）を、029-Gで本格的に実装する必要がある。

---

## PHASE 20 — LIVE DEMO BOUNDARY

```
$ grep -rn "add-live-demo-disclosure|astrea-demo-disclosure|Live Demo|LiveDemo" core/includes/starter-import-*.php
（該当なし）
$ grep -rn "wp_remote_get|wp_remote_post|curl_exec" core/includes/starter-import-*.php
（該当なし）
```

Live Demo依存: 0。project-if.jpへのremote runtime依存: 0。

---

## PHASE 21 — COMPATIBILITY / QUALITY

`vendor/composer/platform_check.php`を一時バイパスして`vendor/bin/phpcs`を実行（確認後、元の内容へ復元済み）。

**最終結果**: 全9新規ファイル+`astrea-core.php`について、フォーマット系エラー・DocBlock不足を全て解消。既存の`astrea_generated_pages_consistent()`のような実装バグではなく、**1件の実装バグを本Constructionで発見・修正した**（後述PHASE 22参照）。最終的に残るのは、Construction 029-Bで既に確認・記録済みの以下1件のみ：

```
starter-import-state.php:90 | ERROR | "$this" can no longer be used ... (PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext)
```

これはConstruction 029-Bの`enum SiteState`メソッド内`$this`使用への既知のfalse positiveであり、本Constructionで新規に発生したものではない。029-C由来の新規コードにはこのfalse positiveを含め一切の残存issueがないことを確認した。この警告を隠すための無関係な改造は行っていない。

- namespace collision: なし（`Astrea\Core\StarterImport`は既存の名前空間の続き）。
- strict comparison: `===`/`!==`を徹底。
- fatal errors: なし（全テストPASS）。
- debug output: なし（デバッグ用に作成した一時テストファイル`ThemeAvailabilityTest.php`は確認後に削除済み）。
- local path leakage: なし。
- remote request: 0件。

---

## PHASE 22 — EXISTING CORE TEST BASELINE（比較）

```
$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 456, Assertions: 809, Errors: 3.
```

029-B baseline（416 total/413 PASS/3 errors）に対し、本Constructionで40件のテスト追加（456 total/453 PASS/3 errors）。**既存の3件のエラーは内容・件数とも完全に同一**（`SeoMetaTest`×2、`SetupTest`×1、いずれも`DIR_TESTDATA`実ファイル依存の既存環境課題）。

**029-C Regression: 0**

**実装中に発見・修正した実バグ**（Construction 029-Bの`astrea_generated_pages_consistent()`修正と同種の、テストで検出した設計上の不備）：
1. `check_astrea_core()`内で`\Astrea\Core\ASTREA_CORE_VERSION`（Astrea\Core名前空間の定数として参照）としていたが、`define()`で作成された定数は常にグローバル名前空間に置かれるため、正しくは`\ASTREA_CORE_VERSION`（先頭バックスラッシュ1つ）。CASE P01実行時に`Undefined constant`エラーで発覚し、即座に修正した。
2. CHECK 09（Import Lifecycle Marker）が当初、029-Bの`read_import_status()`の戻り値（decode結果）のみに依存しており、「不正な値」と「未設定」を区別できていなかった。CASE P12のテスト設計時に発覚し、生option値の追加確認ロジックを実装した（PHASE 3参照）。

---

## PHASE 23 — CORE VERSION POLICY

Core本体のコードを変更した（`astrea-core.php`へのrequire追加9行 + 新規9ファイル + 新規テスト1ファイル）ため、**Core Modification: CANDIDATE** として扱う。Core versionは**1.0.1のまま変更していない**（無断でversion bumpしていない）。

Theme（`theme/`）には一切触れていない。**Theme Modification: NONE、Version: 1.0.3のまま。**

---

## 未解決事項・029-D/029-G/029-Hへの申し送り

1. **029-D（Content Import Engine）へ**: `register_generated_object()`/`register_generated_option()`をStep単位のコンテンツ生成コードから呼び出す実統合、および実データを見た上でのUser Edit Detection fingerprint方式の設計（PHASE 14でarchitecture debtにしないための設計余地を用意済み）。
2. **029-G（Failure / Retry / Concurrency Safety）へ**: `failed`状態からの正式なretry許可ロジック（現状`write_running_marker()`はfailed状態からの遷移を一律拒否）、stale lockの安全な復旧フロー（`is_lock_stale()`は検出のみ、自動解除はしない設計を維持）。
3. **029-H（Admin UI）へ**: Nonce/CSRF/AJAX/RESTエンドポイントの実装（本Constructionのdomain/service layerには一切含めていない）、`PreflightResult`/`BeginOperationResult`をそのまま表示材料として利用できる構造（machine-readable code/diagnosticを日本語化するのは029-Hの責務）。
4. **子テーマ対応の再検討**: 現状、ASTREA子テーマは無条件BLOCK（曖昧な場合は安全側に倒す、というOrder方針に従った判断）。将来的に子テーマサポートが必要になった場合、どのような条件なら安全に許可できるかは別途検討が必要。
5. **CASE P07/P14のテスト環境上の制約**: PHP versionとMultisiteの実環境での強制切り替えができないため、完全なend-to-endテストではなく、ロジック単体検証・既存029-Bカバレッジへの委譲で対応した。実際のPHP 8.2環境やMultisite環境での動作確認は、本Constructionのローカル検証範囲を超える（将来的に必要であれば別途環境を用意して検証する）。

---

## Construction 029-C — Safety Review Ready

- Preflight Foundation: COMPLETE
- PASS/WARNING/BLOCK Model: COMPLETE
- Site State Integration: COMPLETE（029-Bを改造せず利用）
- Environment Checks: COMPLETE（12 CHECK実装）
- Capability Boundary: COMPLETE（`manage_options`、Site StateとPermissionを分離）
- Lifecycle Marker Schema: COMPLETE
- Marker Transition Guard: COMPLETE（許可遷移3種、禁止遷移を拒否）
- Ownership Registry Schema: COMPLETE
- Object Ownership Contract: COMPLETE（許可リスト方式）
- Concurrency Lock: COMPLETE（`add_option()`ベースのatomic取得）
- Race Safety Model: COMPLETE（Preflight→Lock→再確認→Marker書き込みの順序）
- Operation ID: COMPLETE（`wp_generate_uuid4()`）
- User Edit Detection Handoff: COMPLETE（B案採用、029-Dへ明確に申し送り）
- Preflight Read-only: PASS
- Preflight Repeat 100x: PASS
- Preflight Mutation: 0
- Starter Content Generated: 0
- Failure Injection: PASS
- Concurrent Begin: PASS（`add_option()`のatomic性で先勝ち）
- Live Demo Dependency: 0
- Remote Runtime Dependency: 0
- Existing Core Regression: 0（453/456 PASS、既存3件のエラーは内容・件数とも同一）
- Theme Modification: NONE
- Theme Version: 1.0.3
- Core Modification: CANDIDATE
- Core Version: 1.0.1 unchanged
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Safety Review: REQUIRED
- Next: Construction 029-D after Owner PASS

STOP

---

## Construction 029-C — FINALIZE / CLOSE

- Finalize Start: 2026-09-13（Owner Safety Review PASS受領後）
- Modifier: Chloe
- Owner Safety Review: **PASS**（Preflight Foundation / PASS-WARNING-BLOCK Model / Site State Integration / Environment Checks / Capability Boundary（manage_options）/ Lifecycle Marker Schema / Marker Transition Guard / Ownership Registry Schema / Object Ownership Contract / Concurrency Lock / Race Safety Model / Operation ID — 全てADOPTED。User Edit Detection Strategy: B案（029-Dへhandoff）ADOPTED。Child Theme Policy: FIRST RELEASE BLOCK。Additional Changes After Review: NONE — Owner確認済み）

### PHASE 1 FINAL DIFF AUDIT（実git diffベース）

```
$ git rev-parse HEAD && git rev-parse origin/main
edaeb594110f36da991949623df400ec14068602（両者一致、分岐なし）
$ git diff --stat -- core/ theme/ tests/
core/astrea-core.php | 9 +++++++++
1 file changed, 9 insertions(+)
```

実際のgit diffで確認したConstruction 029-C対象：
1. `core/astrea-core.php` — require文9行追加のみ。
2. `core/includes/starter-import-preflight-result.php`（新規、`CheckStatus` enum）
3. `core/includes/starter-import-preflight-check.php`（新規、`PreflightCheck` class）
4. `core/includes/starter-import-preflight-aggregate.php`（新規、`PreflightResult` class）
5. `core/includes/starter-import-lock.php`（新規）
6. `core/includes/starter-import-lifecycle.php`（新規）
7. `core/includes/starter-import-ownership.php`（新規）
8. `core/includes/starter-import-preflight.php`（新規）
9. `core/includes/starter-import-begin-result.php`（新規、`BeginOperationResult` class）
10. `core/includes/starter-import-operation.php`（新規）
11. `tests/StarterImportPreflightTest.php`（新規）
12. `docs/research/2026-09-13_construction_029c_preflight_ownership_marker.md`（本報告書、新規）

`theme/`への差分: 0件。`git diff --check`: 出力なし（空白関連エラー0件）。

### PHASE 3-8 FINAL PREFLIGHT / MARKER / LOCK AUDIT

実コードを再確認し、Owner Review時点と完全に同一であることを確認した：
- `run_preflight()`内の12 CHECK呼び出し（`check_site_state`〜`check_storage_environment`）: 追加0、削除0、順序変更0。
- `PreflightResult::from_checks()`の集約ルール（BLOCK優先→WARNING→PASS、未知はfail closed）: 変更なし。
- `write_running_marker()`/`write_failed_marker()`/`write_completed_marker()`の許可遷移3種・禁止遷移: 変更なし。`is_owning_operation()`によるoperation_id一致チェックも維持。
- `check_import_lifecycle_marker()`（CHECK 09）: 生option値+decode結果の二重確認ロジック、`IMPORT_LIFECYCLE_INVALID`判定とも維持。029-Bの`read_import_status()`自体は無変更のまま。
- `acquire_lock()`: `add_option()`ベースのatomic取得のまま。`get_option()`→`update_option()`のrace-proneな実装への後退なし。
- `release_lock()`: operation_id一致時のみ`delete_option()`。
- `is_lock_stale()`: 検出のみ、自動解除（`delete_option`呼び出し）なし。
- `OWNERSHIP_OBJECT_TYPES`（posts/attachments/terms/navigation）・`OWNERSHIP_ALLOWED_OPTIONS`（9件の既知option名）: 変更なし。

### PHASE 9-13 BEGIN OPERATION / OWNERSHIP FINAL AUDIT

`begin_operation()`の実行順序（Preflight → operation_id生成 → atomic lock取得 → 再確認 → running marker書き込み → ownership registry context stamp）を再確認し、Owner Review時点と同一であることを確認した。途中失敗時のlock解放ロジックも維持されている。Ownership registryの「生成物である証拠」であり「overwrite permissionではない」という原則も報告書内に維持されている。

### PHASE 14 TESTS（再実行結果）

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter StarterImportPreflightTest
PHPUnit 9.6.36
........................................                          40 / 40 (100%)
OK (40 tests, 105 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit --filter "StarterImportStateTest|StarterImportPreflightTest"
.........................................................         57 / 57 (100%)
OK (57 tests, 146 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 456, Assertions: 809, Errors: 3.
```

Preflight: 40/40 PASS（Owner Review時点と同数・同内容）。029-B+029-C: 57/57 PASS。既存Core test suite: **456 total / 453 PASS / 3 errors（Owner Review時点と件数・内容とも完全に同一）**：

1. `SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback`
2. `SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image`
3. `SetupTest::test_checklist_seo_og_image_item_reflects_setting`

いずれも`git diff --stat`で該当ファイル（`SeoMetaTest.php`/`SetupTest.php`/`seo-meta.php`/`setup-checklist.php`）への差分が0件であることを再確認し、**029-C Regression: 0** と判定した。「全テストPASS」への書き換えは行わず、Order指示通り事実をそのまま記録する。

### PHASE 15 KNOWN TEST LIMITATIONS（維持）

- CASE P07（PHP below minimum）: `PHP_VERSION`はruntime差し替え不可のため、`version_compare()`比較ロジック単体を仮想入力で検証。E2Eでの完全なBLOCK再現はできていないことを維持して記録する。
- CASE P14（Multisite）: `WP_UnitTestCase`はsingle-site実行のため`is_multisite()`を強制的にtrueにできず、`check_multisite()`が非マルチサイト環境で正しくPASSを返すことのみ確認。実際のBLOCK判定ロジックはConstruction 029-Bの`Evidence`ベースのテストで検証済みであることに委譲している。
- CASE P13（upload environment unusable）: Order禁止の「試し書き」でアップロード環境を破壊的にunusableにする手段がないため、正常時PASSのみ確認。

これらは「E2E完全PASS」ではなく、テスト環境の技術的制約として正確に記録されたままであることを確認した。

### PHASE 16 QUALITY（最終確認）

`vendor/composer/platform_check.php`を一時バイパスして`vendor/bin/phpcs`を再実行した（確認後、元の内容へ復元済み）。

```
$ vendor/bin/phpcs $(find core/includes -name "starter-import-*.php")
FILE: starter-import-state.php
FOUND 2 ERRORS AFFECTING 1 LINE
90 | ERROR | "$this" can no longer be used... (PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext)
```

Construction 029-Bで既に確認・記録済みの`enum` `$this`使用への既知false positiveのみが残存し、**029-C由来の新規issueは0件**であることを再確認した。この警告を隠すための無関係な改造は行っていない。Live Demo依存・remote request依存も再度grep確認し、いずれも0件。

### PHASE 17 VERSION POLICY

```
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Theme 1.0.3・Core 1.0.1とも無変更。Core version bumpは実施していない。

### PHASE 19 STAGE AUDIT

コミット対象を以下に確定した（除外: `HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群・他Construction報告書・stray files、いずれもConstruction 029-C以前から存在するOwner自身の作業または他Constructionのスコープであり、今回のcommitには一切含めない）：

**コミット対象:**
- `core/astrea-core.php`
- `core/includes/starter-import-preflight-result.php`
- `core/includes/starter-import-preflight-check.php`
- `core/includes/starter-import-preflight-aggregate.php`
- `core/includes/starter-import-lock.php`
- `core/includes/starter-import-lifecycle.php`
- `core/includes/starter-import-ownership.php`
- `core/includes/starter-import-preflight.php`
- `core/includes/starter-import-begin-result.php`
- `core/includes/starter-import-operation.php`
- `tests/StarterImportPreflightTest.php`
- `docs/research/2026-09-13_construction_029c_preflight_ownership_marker.md`

Theme変更: 0／Starter Site content変更: 0／Media変更: 0／Live Demo変更: 0／Owner unrelated changes: 0。

### PHASE 23 DEPLOY POLICY

**Deploy: NOT REQUIRED / NOT RUN。** Admin UI・Import endpoint・Starter Import実行・user-facing functionalityが一切存在しないため、production deployを実施しない。VPS操作は一切行っていない。

---

## Construction 029-C — CLOSED

- Owner Safety Review: PASS
- Preflight Foundation: ADOPTED
- PASS/WARNING/BLOCK Model: ADOPTED
- Site State Integration: ADOPTED
- Environment Checks: ADOPTED
- Capability Boundary: ADOPTED
- Capability: manage_options
- Lifecycle Marker Schema: ADOPTED
- Marker Transition Guard: ADOPTED
- Ownership Registry Schema: ADOPTED
- Object Ownership Contract: ADOPTED
- Concurrency Lock: ADOPTED
- Race Safety Model: ADOPTED
- Operation ID: ADOPTED
- User Edit Detection Strategy: B / 029-D handoff
- Child Theme Policy: BLOCK in first release
- Preflight Read-only: PASS
- Preflight Repeat: 100/100 PASS
- Preflight Mutation: 0
- Starter Content Generated: 0
- Failure Injection: PASS
- Concurrent Begin: PASS
- Preflight Tests: 40/40 PASS
- 029-B + 029-C Tests: 57/57 PASS
- Existing Core Tests: 453/456 PASS
- Existing Test Errors: 3（029-C以前から存在する既存環境依存エラー、内容・件数ともOwner Review時点と同一）
- 029-C Regression: 0
- Live Demo Dependency: 0
- Remote Runtime Dependency: 0
- Theme Modification: NONE
- Theme Version: 1.0.3
- Core Modification: ACCEPTED
- Core Version: 1.0.1
- Core Version Bump: NOT DONE
- Commit: `74ffded55ac62daf2168b156e53bd6a641721479`
- Follow-up Commit: 本行を含む報告書更新のみのフォローアップコミット（push確認後に作成。ハッシュは完了報告および`git log`参照）
- Push: PASS
- Deploy: NOT REQUIRED / NOT RUN
- Production: UNCHANGED
- Excluded Owner Changes: `HISTORY.csv`, `yamada-demo-export.wxr`（いずれも無変更のまま保持）、Owner削除済みscreenshot群（復元せず）、他Construction報告書（対象外）
- Next Recommended Construction: Construction 029-D — Content Import Engine / True Idempotency

STOP
