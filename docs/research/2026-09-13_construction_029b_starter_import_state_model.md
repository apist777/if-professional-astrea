# Construction 029-B — Starter Import State Model / Domain Foundation

- Start: 2026-09-13
- Modifier: Chloe
- Mode: **ローカル実装・テストのみ。commit / push / deployは一切実施していない。** Owner Review前提。

---

## PHASE 0 — PRECHECK結果

```
$ git rev-parse HEAD && git rev-parse origin/main
af93cb36a24582449012b32b750dd5ec4560cda9（両者一致、分岐なし）
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
$ grep -n "Requires PHP\|Requires at least" core/astrea-core.php
Requires at least: 7.0 / Requires PHP: 8.3
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`）は無変更のまま。既存Core architectureを読み込み、以下を確認した：

- Coreは`namespace Astrea\Core\XXX;`単位でincludes/配下にフラットにファイルを配置し、`astrea-core.php`から`require`で直接読み込む（PSR-4オートロードなし）。
- **既存のownership pattern**（Construction 028/029-Aで既に発見済み）: `Setup\GENERATED_PAGES_OPTION`（`astrea_core_generated_pages`）・`Setup\GENERATED_NAVIGATION_OPTION`（`astrea_core_generated_navigation`）・`Setup\GENERATED_TEMPLATE_PARTS_OPTION`が、Core自身の生成物を「記録されたIDが実在するかどうか」で追跡する既存の仕組みとして確立している。029-Bはこれを独自frameworkで置き換えず、そのまま再利用した。
- `data-deletion.php`のdocblockに「ASTREA Setup生成ページも、一度生成されればユーザーコンテンツと区別できない（Decision 016/019）」という既存原則があり、029-Bのstate detectionはこの前提（=option trackingが失われれば区別不能になる）を踏まえて設計した。
- 既存PHPUnitインフラ（`wp-phpunit`経由の実DB統合テスト、`tests/*.php`、`composer.json`の`phpunit/phpunit ^9.6`）を確認し、同じパターン（`WP_UnitTestCase`、`self::factory()->post->create()`、`tear_down()`でoption掃除）でテストを書いた。

---

## PHASE 1 — MODULE BOUNDARY（実装したモジュール構成）

Construction 029-A ADRの「Core内サブモジュール」決定に従い、`Astrea\Core\StarterImport`という単一の名前空間の下に、責務ごとに6ファイルへ分割した（Coreの既存規約＝1機能=1namespace・複数ファイルに合わせ、深いネストのサブnamespaceは作らなかった）。

| ファイル | 責務 | 主な内容 |
| --- | --- | --- |
| `starter-import-state.php` | State | `SiteState` enum（6状態）+ `allows_new_import()` |
| `starter-import-marker.php` | State（marker契約） | `ImportStatus` enum + marker option名3定数（read-only契約） |
| `starter-import-evidence.php` | Domain（データ） | `Evidence` 値オブジェクト（immutable, readonly properties） |
| `starter-import-result.php` | Domain（データ） | `StateResult` 値オブジェクト + reason code定数群 |
| `starter-import-domain.php` | Domain（ロジック） | `classify( Evidence ): StateResult`（唯一の純粋関数） |
| `starter-import-detection.php` | Detection | `gather_evidence()`（DB読み取り）・`detect_state()`（公開エントリポイント） |

`astrea-core.php`に6行のrequireを追加（既存の`data-deletion.php`の直後）。「巨大な1個のStarterImporterクラスへ全部詰め込む」ことはせず、Detection（DB読み取り）とDomain（判定ロジック）を完全に分離した——`classify()`はDB接続を一切持たず、`Evidence`オブジェクトを直接構築すればユニットテストできる。

---

## PHASE 2 — CANONICAL SITE STATES

Construction 029-Aで定義された6状態を、PHP 8.3（backed enum、PHP 8.1+）で実装した。既存Coreの`final class + const`パターンではなくenumを採用した理由：この6状態は「相互排他的な閉じた集合」であり、各状態に振る舞い（`allows_new_import()`）を持たせる必要があるため——これはまさにenumの用途であり、単純な文字列識別子（CPT名やoption名）とは性質が異なると判断した。

| State | 意味 | `allows_new_import()` |
| --- | --- | --- |
| `FRESH` | WordPress自体がほぼ初期状態、meaningful contentなし、ASTREA生成物もなし | true |
| `ASTREA_READY` | ASTREA既知の生成物のみ存在、meaningful user contentなし | true |
| `COMPLETED` | Starter Import完了marker、内部整合性あり | false |
| `EXISTING_CONTENT` | ユーザー作成と判断されるcontentが存在 | false |
| `PARTIAL` | Import開始/失敗markerまたは矛盾するownership evidence | false |
| `UNKNOWN` | 証拠矛盾、または既知stateへ安全に分類不能（Multisite含む） | false |

---

## PHASE 3 — STATE RESULT MODEL

`StateResult`は単なるstringではなく、以下を必ず保持する immutable オブジェクト：

```php
final class StateResult {
    public function __construct(
        public readonly SiteState $state,
        public readonly bool $import_allowed,
        public readonly string $reason_code,   // machine-readable, REASON_* 定数
        public readonly string $diagnostic,    // 開発者向け短い英語診断（Admin翻訳文はここに置かない）
        public readonly Evidence $evidence,    // 判定根拠となったEvidence
        public readonly array $warnings = array()
    ) {}
}
```

`reason_code`は12種類のREASON_*定数（`starter-import-result.php`）として実装し、enumではなくplain string constantsとした——029-C以降でreason codeが増える度にenum全体を更新する必要を避けるため。

---

## PHASE 4 — MEANINGFUL CONTENT DEFINITION

**`post count == 0`のような単純判定は一切使用していない。** `detect_wp_default_post()`/`detect_wp_default_pages()`は、タイトル一致だけでなく以下を複合的に確認する：

- `post_modified_gmt === post_date_gmt`（一度も編集されていない）
- `comment_count === 0`（コメントが付いていない＝人が触れた形跡がない）

この2条件と「タイトル一致」を **AND** で組み合わせ、初めて「WordPress標準の未編集初期コンテンツ」と判定する（`is_untouched()`関数）。タイトルだけでの判定は明示的に避けた（Phase 13、ローカライズ・バージョン差異への配慮）。

「ASTREA生成物」と「ユーザーコンテンツ」の区別は、Core既存の`GENERATED_PAGES_OPTION`/`GENERATED_NAVIGATION_OPTION`に記録されたIDとの照合で行う（PHASE 5参照）。

**Pageのunaccounted判定ロジック**（`count_unaccounted_pages()`）：
1. 生存中（trash以外）の全pageを取得。
2. 「認識済みWordPress初期ページ（未編集のSample Page/Privacy Policy）」と「Core記録済みの生成ページ（実在するもの）」のIDを`accounted_ids`として集める。
3. 全page IDから`accounted_ids`を差し引いた残りの件数を`unaccounted_page_count`とする。

1件でもunaccountedなpageがあれば`EXISTING_CONTENT`としてBLOCKする（Phase 12: false positive最小化のため、量ではなく有無で判定）。

---

## PHASE 5 — ASTREA GENERATED CONTENT

既存の`Setup\GENERATED_PAGES_OPTION`（`about`/`price`/`contact`/`home`の4キー）・`Setup\GENERATED_NAVIGATION_OPTION`をそのまま読み取り、「記録されたIDが実在し、かつtrashでないか」を`page_still_exists()`（setup-pages.php既存関数を再利用）で確認する。

**重要な修正済みバグ**: 実装当初、`astrea_generated_pages_consistent()`が「まだ生成されていないキー（`recorded_id === null`）」を「矛盾」として誤判定していた（CASE 02のテストで発覚）。正しくは「記録されているのに実在しない」場合のみ矛盾とすべきで、「まだ記録されていない」ことは矛盾ではない（Starter Buildの途中段階、あるいは一部のみ生成済みのASTREA_READY状態として正常）。テストで検出し、修正済み（詳細はPHASE 11参照）。

`ASTREA_READY`は、既知の生成物（1件でも）が存在し、かつ矛盾がない場合に判定される——**ASTREA生成済みページがあるだけでEXISTING_CONTENTにはしない**という要求を満たす。

---

## PHASE 6 — USER EDIT DETECTION BOUNDARY

Order指示通り、**完全なcontent diff engineは実装していない**。029-Bで検出できる範囲は「ASTREA生成ページが記録通りに実在するか」までであり、「その中身がユーザーによって編集済みか」は判定していない（判定不能な場合は無理に推測せず、現状のEvidenceモデルには含めなかった）。

**029-C以降への提案**: ASTREA生成ページが編集されたかどうかを検出する必要が将来生じた場合（例: Reset/Reinstall機能の安全性確保）、`post_modified_gmt !== post_date_gmt`程度の粗い指標か、より確実にはpost_content のfingerprint（hash）をCore生成時に`GENERATED_PAGES_OPTION`と併せて記録する「ownership fingerprint」の導入を検討する必要がある。029-Bでは範囲外として実装しなかった。

---

## PHASE 7 — STARTER MARKERS（Read Contract）

`starter-import-marker.php`で以下を定義した。**029-Bはこれらのoptionに一切書き込まない**（`update_option`/`add_option`/`delete_option`の呼び出しが0件であることをgrepで確認済み、PHASE 16/17参照）。

```
astrea_starter_import_status   — ImportStatus enum: not_started/running/failed/completed
astrea_starter_import_version  — string（Starter Packageバージョン）
astrea_starter_import_generated — 生成物ID map（029-C以降が書き込む。029-Bは「存在し非空か」のみ読む）
```

---

## PHASE 8 — STATE TRANSITION MODEL（仕様のみ、実行は未実装）

```
FRESH            → import start → PARTIAL(running) → COMPLETED
ASTREA_READY     → import start → PARTIAL(running) → COMPLETED
PARTIAL(failed)  → future retry → PARTIAL(running) → COMPLETED
COMPLETED        → re-import    → BLOCK
EXISTING_CONTENT → import       → BLOCK
UNKNOWN          → import       → BLOCK
```

上記は仕様として記録するのみで、実際の遷移実行（marker書き込み等）は一切実装していない——029-Bは`classify()`という一方向の判定関数のみを持つ。

---

## PHASE 9 — FAIL CLOSED

`classify()`の判定順序は「一致が確認できない限りBLOCK側へ倒す」ことを徹底した：

1. Multisiteは無条件UNKNOWN（BLOCK）。
2. `COMPLETED`マーカーは、実際に生成物の痕跡（`generated`マーカー非空 or ASTREA CPT件数>0）と整合する場合のみ信頼する。矛盾すればUNKNOWN（CASE 09）。
3. `RUNNING`/`FAILED`マーカーはそのままPARTIAL（BLOCK）。
4. マーカーが`NOT_STARTED`/未設定の場合のみ、コンテンツ形状から推論する。この段階でも、Core自身のgenerated-page trackingに矛盾があればUNKNOWN、その後は「ページ/投稿/添付ファイル/CPTのいずれか1件でもunaccounted」なら即BLOCK（重み付けをせず、有無だけで判定）。

`test_fail_closed_every_state_except_fresh_and_astrea_ready_blocks_import`テストで、`SiteState`の全6ケースを網羅的に検証し、`FRESH`と`ASTREA_READY`以外は必ず`allows_new_import() === false`であることを確認した。

---

## PHASE 10 — PURE DETECTION（副作用ゼロの確認）

`gather_evidence()`/`detect_state()`は、`get_posts()`/`get_post()`/`get_option()`/`wp_count_posts()`のみを呼び出し、書き込み系関数（`wp_insert_post`/`update_option`/`wp_delete_post`等）は一切呼び出していない（grep確認済み、ヒットはdocblock内のコメント記述のみ）。CASE 11テストで、`detect_state()`を100回連続呼び出した前後でpost/page/attachment件数と関連option値が完全に一致することを確認した。

---

## PHASE 11 — TEST MATRIX（実行結果）

実行環境: `wp-env`（Docker、WordPress test site）+ `wp-phpunit` + `WP_UnitTestCase`。

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter StarterImportStateTest
PHPUnit 9.6.36
.................                                                 17 / 17 (100%)
OK (17 tests, 41 assertions)
```

| CASE | 内容 | 結果 |
| --- | --- | --- |
| 01 | Fresh WordPress | PASS（FRESH / allowed） |
| 02 | ASTREA known generated pages only | PASS（ASTREA_READY / allowed）※実装中にバグ発見・修正（下記参照） |
| 03 | Meaningful custom page exists | PASS（EXISTING_CONTENT / blocked） |
| 04 | Meaningful blog post exists | PASS（EXISTING_CONTENT / blocked） |
| 05 | User attachment exists | PASS（EXISTING_CONTENT / blocked、reason=UNACCOUNTED_ATTACHMENT。理由：genuinely freshなWordPressはMedia Libraryが常に0件のため、1件でもあれば安全側でblockする設計） |
| 06 | Starter status = running | PASS（PARTIAL / blocked） |
| 07 | Starter status = failed | PASS（PARTIAL / blocked） |
| 08 | Starter status = completed（整合あり） | PASS（COMPLETED / blocked） |
| 09 | Conflicting completed marker + inconsistent ownership | PASS（UNKNOWN / blocked、reason=INCONSISTENT_COMPLETED_MARKER） |
| 10 | Unknown/unexpected relevant CPT content | PASS（EXISTING_CONTENT / blocked、reason=UNEXPECTED_CPT_CONTENT） |
| 11 | Detector repeated 100 times | PASS（mutation 0、post/page/attachment件数・option値とも完全一致） |
| 12 | ASTREA generated page + unrelated user content | PASS（EXISTING_CONTENT / blocked） |

追加テスト（Order必須項目以外に、Phase 9/13/Evidence値オブジェクト自体の健全性を確認するため実装）：
- `test_multisite_is_always_blocked_regardless_of_content`（Phase 13）
- `test_fail_closed_every_state_except_fresh_and_astrea_ready_blocks_import`（Phase 9網羅監査）
- `test_evidence_total_astrea_cpt_count_sums_all_types` / `test_evidence_generated_pages_consistent_true_when_unrecorded_entries_are_simply_not_generated_yet` / `test_evidence_generated_pages_consistent_false_when_recorded_id_no_longer_exists`（Evidence値オブジェクト単体テスト、DB不使用）

**実装中に発見・修正した実バグ**: `Evidence::astrea_generated_pages_consistent()`が、「まだ生成されていないページ（recorded_id=null）」を「矛盾」と誤判定しており、CASE 02（aboutページのみ生成済み、price/contact/homeは未生成）が誤ってUNKNOWN判定になっていた。CASE 02のテスト失敗により発覚し、「recorded_idが記録されているのに実在しない場合のみ矛盾とする」よう修正した。修正前は一部生成のASTREA_READY状態を正しく認識できず、false negativeを生んでいた（false positiveではなくfalse negative側だったため、Order Phase 12の優先順位＝false positive最小化には反しない性質のバグだったが、機能として誤りだったため修正した）。

**既存Coreテストスイート全体への影響確認**:
```
$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 416, Assertions: 704, Errors: 3.
```
416件中413件PASS。残る3件（`SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback`ほか）は`DIR_TESTDATA . '/images/test-image.jpg'`という実ファイルを使う`create_upload_object()`呼び出しに起因する既存の環境依存エラーで、`git diff --stat`で該当ファイル（`SeoMetaTest.php`/`SetupTest.php`/`seo-meta.php`/`setup-checklist.php`）への差分が0件であることを確認し、**Construction 029-Bの変更とは無関係**と判断した。

---

## PHASE 12 — FALSE POSITIVE / FALSE NEGATIVE AUDIT

- **False Positive（最重要事故＝Freshと誤判定）を防ぐ設計**: PHASE 9記載の通り、1件でもunaccounted contentがあれば無条件BLOCK。CASE 05（attachment 1件）のような「量としては軽微」なケースでも保守的にBLOCKする方針を採用した。
- **False Negative（不便だが安全）は許容**: 例えば「WordPress標準ページが正規に未編集で残っているだけ」のような紛らわしいケースで、判定に迷えばBLOCK側へ倒れる設計自体がFalse Negativeを積極的に許容している。
- 唯一実装中に発見した実バグ（PHASE 11のCASE 02）はFalse Negative方向のバグ（本来ASTREA_READYであるべきものをUNKNOWNとして誤ってBLOCKしていた）であり、False Positiveではなかった。修正はしたが、これはOrderの優先順位（False Positive最小化）に照らして「安全側の誤り」だったことを記録する。

---

## PHASE 13 — WORDPRESS INITIAL CONTENT / MULTISITE

- タイトル文字列だけに依存しない判定（PHASE 4参照）。
- **Multisiteは初版で正式対応せず、`is_multisite()`が真であれば無条件で`UNKNOWN`（BLOCK）とする。** ネットワーク全体 vs サイト単位の状態判定は本Constructionのスコープ外。

---

## PHASE 14 — CAPABILITY BOUNDARY

`starter-import-*.php`のいずれにも`current_user_can()`等のcapabilityチェックは一切実装していない。Site State（このサイトの中身がどうなっているか）とCurrent User Permission（今操作しているユーザーに何が許されるか）を意図的に分離し、後者は029-C（Preflight）の責務として残した。

---

## PHASE 15/16 — NO UI / NO STARTER IMPORT（確認済み）

```
$ grep -n "add_action( 'admin_menu'\|register_rest_route\|wp_ajax_\|add_menu_page\|add_submenu_page" core/includes/starter-import-*.php
（該当なし）
$ grep -n "wp_insert_post\|wp_update_post\|wp_insert_attachment\|update_option\|add_option\|delete_option\|wp_delete_post" core/includes/starter-import-*.php
（実際の呼び出しは0件、ヒットは全てdocblock内のコメント記述）
```

Admin画面・ボタン・進捗バー・通知・REST/AJAXエンドポイントは一切実装していない。Starter content（page/CPT/media/navigation）も1件も生成していない。

---

## PHASE 17 — LIVE DEMO BOUNDARY

```
$ grep -rn "add-live-demo-disclosure|astrea-demo-disclosure|Live Demo|LiveDemo" core/includes/starter-import-*.php
（該当なし）
```

Starter Import State ModelはLive Demoの存在自体を一切知らない設計になっている（Construction 028-Fで確立した境界を維持）。

---

## PHASE 18 — CORE VERSION POLICY

Core本体のコードを変更した（`astrea-core.php`へのrequire追加6行 + 新規6ファイル + 新規テスト1ファイル）ため、**Core Modification: CANDIDATE** として扱う。Core versionは**1.0.1のまま変更していない**（無断でversion bumpしていない）。029系全体をどの単位でリリースし、いつversionを上げるかはOwner判断後に別途確定する。

Theme（`theme/`）には一切触れていない。**Theme Modification: NONE、Version: 1.0.3のまま。**

---

## PHASE 19 — CODE QUALITY

`vendor/bin/phpcs`（`phpcompatibility/phpcompatibility-wp`含む既存ASTREAルールセット）を実行した。実行にあたり、システムのデフォルトPHPが8.1（Core最低要件8.3未満）のため、`vendor/composer/platform_check.php`（gitignore対象、composerが自動生成する非product code）を一時的にバイパスし、確認後に元の内容へ復元した。

**確認結果**: 6新規ファイル+`astrea-core.php`について、フォーマット系エラー33件を`phpcbf`で自動修正、DocBlock不足を手動で補完した。最終的に残るのは以下1件のみ：

```
starter-import-state.php:90 | ERROR | "$this" can no longer be used in a plain function or method since PHP 7.1.
(PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext)
```

これは`enum SiteState`の`allows_new_import()`メソッド内の`self::FRESH === $this`という正当な参照に対する**既知のfalse positive**であることを確認した——`phpcompatibility/phpcompatibility-wp ^2.1`がPHP 8.1のenum構文（enumメソッド内の`$this`は正当）を正しく解析できていない。実行時には問題なく動作することを直接検証済み：

```
$ php -r 'define("ABSPATH","/tmp/"); require "core/includes/starter-import-state.php"; ...'
bool(true)
bool(false)
```

コード自体は変更せず、既知の制約として本報告書に記録するに留めた（Coreのphpcs.xml自体を無断で変更・除外設定追加することはしていない——Owner判断が必要な場合は029-C以降で検討）。

- escaping/sanitization: 該当なし（DB書き込みを一切行わないread-onlyモジュールのため）。
- strict comparison: `===`/`!==`を徹底使用。
- namespace collision: `Astrea\Core\StarterImport`は既存のいかなる名前空間とも衝突しない新規追加。
- direct access prevention: 全ファイルに`if ( ! defined( 'ABSPATH' ) ) { exit; }`を実装。
- no global pollution: 全関数・定数・enumは`Astrea\Core\StarterImport`名前空間内。
- no fatal on unsupported PHP: enum/readonly propertiesはPHP 8.1+、Core最低要件8.3を満たす環境では問題なし。
- no debug output / no development path leakage: デバッグ用に一時作成した`tests/DebugStarterImportTest.php`は診断完了後に削除済み。

---

## PHASE 20 — COMPATIBILITY

Core既存の最低要件（PHP 8.3、WordPress 7.0以上）をそのまま踏襲し、それを超える新しい言語機能は導入していない（backed enum・readonly propertiesはいずれもPHP 8.1で導入済みの機能であり、PHP 8.3という現行最低要件の範囲内）。

---

## 未解決事項・029-Cへの申し送り

1. **User Edit Detection（PHASE 6）**: ASTREA生成ページがユーザーに編集済みかどうかを検出する仕組みは未実装。将来Reset/Reinstall機能等で必要になった場合、ownership fingerprint（生成時のcontent hashを記録）の導入を検討する必要がある。
2. **Marker書き込み・ライフサイクル**: `astrea_starter_import_status`等の実際の書き込み（`not_started`→`running`→`completed`/`failed`の遷移実行）は029-Cの責務として未実装のまま。
3. **`astrea_starter_import_generated`の正式スキーマ**: 029-Bは「存在し非空か」のみを読む契約とし、CPTごとの生成物ID mapの正式な形（Construction 029-A Phase 5で概案を提示済み）は029-Cで正式に確定する必要がある。
4. **Capability check**: `current_user_can()`等の権限チェックは意図的に含めていない（PHASE 14、Site StateとUser Permissionの分離）。029-C Preflightで実装する。
5. **PHPCompatibility sniffの既知のfalse positive**（PHASE 19）: `enum`メソッド内`$this`使用への誤検知。今後Core内で他にenumを使う箇所が増えた場合も同様の警告が出ることが予想される。Composer依存のバージョンアップまたはphpcs.xmlでの明示的除外をOwnerに提案するかどうかは、本Constructionでは判断せず未確定のまま残す。

---

## Construction 029-B — Domain Review Ready

- Module Foundation: COMPLETE（`Astrea\Core\StarterImport`、6ファイル）
- Canonical States: COMPLETE（FRESH/ASTREA_READY/COMPLETED/EXISTING_CONTENT/PARTIAL/UNKNOWN）
- State Detector: COMPLETE（`gather_evidence()`/`detect_state()`、read-only）
- Evidence Model: COMPLETE（`Evidence`/`StateResult`、immutable）
- Meaningful Content Policy: COMPLETE（post count==0に依存しない複合判定）
- ASTREA Generated Content Handling: COMPLETE（既存`Setup\GENERATED_*_OPTION`パターン再利用）
- Marker Read Contract: COMPLETE（`astrea_starter_import_*`、read-only、write実装なし）
- Transition Model: COMPLETE（仕様のみ、実行は未実装）
- Fail Closed: PASS（網羅テストで確認）
- Read-only Detection: PASS（100回繰り返しでmutation 0）
- State Test Matrix: PASS（CASE 01-12、17/17）
- Repeat Detection 100x: PASS
- Mutation: 0
- Live Demo Dependency: 0
- Theme Modification: NONE
- Theme Version: 1.0.3
- Core Modification: CANDIDATE
- Core Version: 1.0.1 unchanged
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Domain Review: REQUIRED
- Next: Construction 029-C after Owner PASS

STOP

---

## Construction 029-B — FINALIZE / CLOSE

- Finalize Start: 2026-09-13（Owner Domain Review PASS受領後）
- Modifier: Chloe
- Owner Domain Review: **PASS**（Module Foundation / Canonical States / State Detector / Evidence Model / Meaningful Content Policy / ASTREA Generated Content Handling / Fail Closed Policy / Read-only Detection — 全てADOPTED。Core Candidate Modification: ACCEPTED。Theme Modification: NONE。追加修正要求: NONE — Owner確認済み）

### PHASE 1 FINAL DIFF AUDIT（実git diffベース）

```
$ git rev-parse HEAD && git rev-parse origin/main
af93cb36a24582449012b32b750dd5ec4560cda9（両者一致、分岐なし）
$ git diff --stat -- core/ theme/ tests/
core/astrea-core.php | 6 ++++++
1 file changed, 6 insertions(+)
```

実際のgit diffで確認したConstruction 029-B対象：
1. `core/astrea-core.php` — require文6行追加のみ（内容確認済み、他の変更なし）。
2. `core/includes/starter-import-state.php`（新規）
3. `core/includes/starter-import-marker.php`（新規）
4. `core/includes/starter-import-evidence.php`（新規）
5. `core/includes/starter-import-result.php`（新規）
6. `core/includes/starter-import-domain.php`（新規）
7. `core/includes/starter-import-detection.php`（新規）
8. `tests/StarterImportStateTest.php`（新規）
9. `docs/research/2026-09-13_construction_029b_starter_import_state_model.md`（本報告書、新規）

`theme/`への差分: 0件。`git diff --check`: 出力なし（空白関連エラー0件）。

### PHASE 3-4 FINAL DOMAIN VERIFICATION / FRESH・ASTREA_READY SAFETY

実コードを再確認し、Owner Review時点と完全に同一であることを確認した：
- `SiteState` enum: FRESH / ASTREA_READY / COMPLETED / EXISTING_CONTENT / PARTIAL / UNKNOWN の6ケース、変更なし。
- `allows_new_import()`: `self::FRESH === $this || self::ASTREA_READY === $this` のまま、変更なし。
- `Evidence::astrea_generated_pages_consistent()`: Review中に修正した「`recorded_id`が記録されているのに実在しない場合のみ矛盾とする」ロジックのまま——**後退していないことを確認**（未生成キー=`recorded_id === null`を矛盾として誤判定する旧バグには戻っていない）。

### PHASE 5 FAIL-CLOSED FINAL CHECK / PHASE 6 READ-ONLY GUARANTEE / PHASE 7 TESTS

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter StarterImportStateTest
PHPUnit 9.6.36
.................                                                 17 / 17 (100%)
OK (17 tests, 41 assertions)
```

17/17 PASS（Owner Review時点と同数・同内容）。CASE 03/04/05/06/07/08/09/10/12の全BLOCK系ケース、CASE 11の100回繰り返し（mutation 0）を再確認した。

**既存Core test suite再実行結果:**
```
$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 416, Assertions: 704, Errors: 3.
```
Owner Review時点と**完全に同一**（416 total / 413 PASS / 3 errors）。エラー内容も同一3件（`SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback`、`SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image`、`SetupTest::test_checklist_seo_og_image_item_reflects_setting`）——いずれも`git diff --stat`で該当ファイル（`SeoMetaTest.php`/`SetupTest.php`/`seo-meta.php`/`setup-checklist.php`）への差分が0件であることを再確認し、**029-B Regression: 0** と判定した。件数・内容に変化がないため、PASSへの書き換えは行わず、Order指示通り事実をそのまま記録する。

### PHASE 8 CODE QUALITY（最終確認）

`vendor/composer/platform_check.php`を一時バイパスして`vendor/bin/phpcs`を再実行した（確認後、元の内容へ復元済み）。

- Core対象6ファイル+`astrea-core.php`: 既知の`enum` `$this`false positive（`starter-import-state.php:90`、`PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext`）以外に新規issueは0件。実行時の正常動作は前回Constructionで検証済み（`php -r`による直接実行で`true`/`false`を正しく返すことを確認済み）。この警告を隠すための無関係なコード変更は一切行っていない。
- `phpcs.xml`の`<file>`要素が`./theme`と`./core`のみを対象とし、`./tests`はプロジェクトの既存スコープ外であることを確認した（既存の`SetupTest.php`等、他のテストファイルも同水準のDocBlock省略等が多数存在することを確認済み）。この事実に基づき、`tests/StarterImportStateTest.php`へのPHPCS指摘（DocBlock省略等）は既存プロジェクト慣習の範囲内と判断し、追加修正は行わなかった。
- Live Demo依存・書き込み系関数呼び出し・UI関連コードが0件であることを再度grep確認した（PHASE 15/16/17と同一結果）。

### PHASE 9 VERSION POLICY

```
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Theme 1.0.3・Core 1.0.1とも無変更。Core version bumpは実施していない。

### PHASE 12 STAGE AUDIT

コミット対象を以下に確定した（除外: `HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群・他Construction報告書・stray files、いずれもConstruction 029-B以前から存在するOwner自身の作業または他Constructionのスコープであり、今回のcommitには一切含めない）：

**コミット対象:**
- `core/astrea-core.php`
- `core/includes/starter-import-state.php`
- `core/includes/starter-import-marker.php`
- `core/includes/starter-import-evidence.php`
- `core/includes/starter-import-result.php`
- `core/includes/starter-import-domain.php`
- `core/includes/starter-import-detection.php`
- `tests/StarterImportStateTest.php`
- `docs/research/2026-09-13_construction_029b_starter_import_state_model.md`

Theme変更: 0／Starter Site content変更: 0／Media変更: 0／Live Demo変更: 0／Owner unrelated changes: 0。

### PHASE 16 DEPLOY POLICY

**Deploy: NOT REQUIRED / NOT RUN。** 今回のCore product codeはStarter Import基礎domainのみであり、UI・endpoint・Import実行・user-facing functionalityが一切存在しないため、production deployを実施しない。VPS操作は一切行っていない。

---

## Construction 029-B — CLOSED

- Owner Domain Review: PASS
- Module Foundation: ADOPTED
- Canonical States: ADOPTED
- Allowed States: FRESH / ASTREA_READY ONLY
- State Detector: ADOPTED
- Evidence Model: ADOPTED
- Meaningful Content Policy: ADOPTED
- ASTREA Generated Content Handling: ADOPTED
- Marker Read Contract: ADOPTED
- Transition Model: ADOPTED
- Fail Closed: PASS
- Read-only Detection: PASS
- State Tests: 17/17 PASS
- Repeat Detection: 100/100 PASS
- Mutation: 0
- Existing Core Tests: 413/416 PASS
- Existing Test Errors: 3（029-B以前から存在する既存環境依存エラー、内容・件数ともOwner Review時点と同一）
- 029-B Regression: 0
- Live Demo Dependency: 0
- Theme Modification: NONE
- Theme Version: 1.0.3
- Core Modification: ACCEPTED
- Core Version: 1.0.1
- Core Version Bump: NOT DONE
- Commit: `<COMMIT_HASH_PENDING_FOLLOWUP>`
- Follow-up Commit: `<COMMIT_HASH_PENDING_FOLLOWUP>`
- Push: PASS（予定）
- Deploy: NOT REQUIRED / NOT RUN
- Production: UNCHANGED
- Excluded Owner Changes: `HISTORY.csv`, `yamada-demo-export.wxr`（いずれも無変更のまま保持）、Owner削除済みscreenshot群（復元せず）、他Construction報告書（対象外）
- Next Recommended Construction: Construction 029-C — Preflight / Ownership Marker

STOP
