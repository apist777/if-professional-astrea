# CONSTRUCTION ORDER 002 — ASTREA Core基礎データ層 / Office Profile 実施報告

- 実施日: 2026-08-26（JST）
- 担当: クロエ（Claude）
- 対象: Office Profileデータ層、管理UI、Core公開取得境界、Block Bindings接続、Theme最小表示、Test/CI拡張
- 基準文書: `docs/specifications/05_astrea_free_v1_construction_baseline.md`、Decision 001〜021
- 仕様変更: なし

---

## 0. GitHub Actions CI 最終結果

3コミット目で全ジョブGreenを確認した（[run 32944574074](https://github.com/apist777/if-professional-astrea/actions/runs/32944574074)）。

- `PHP syntax + Coding Standards`：PASS
- `Theme / Core independence smoke test`（Construction 001のA〜D + 本Orderで追加したE〜I）：PASS
- `PHPUnit (ASTREA Core)`：PASS（20 tests, 40 assertions）

### 発見・修正したCI Bug（Baseline/仕様変更を伴わない実装Bug）

**`composer.lock`がこのセッションのホストPHP（8.1、プロジェクトの要求バージョン8.3未満）で`--ignore-platform-req=php`を使って生成されていたため、`doctrine/instantiator 2.1.0`（実際にはPHP 8.4以上が必要）がlockされてしまい、CIの実PHP 8.3環境では解決不能だった。**

`--ignore-platform-req=php`はPHPのバージョンチェック全体を無視するため、ローカルでは矛盾に気づけなかった。修正は、wp-envの`tests-cli`コンテナ（実際のPHP 8.3.33を内蔵）へ`composer.json`/`composer.lock`/`vendor`をマウントし、そのコンテナの本物のPHP 8.3で`composer update`を実行して`composer.lock`を再生成すること。Ignore flagなしで`doctrine/instantiator`が自動的に2.0.0（PHP 8.3対応）へダウングレードされ、以後はホストのPHPバージョンに依存せず正しいlockを保証できるようになった。

この経緯から、`.wp-env.json`の`mappings`へ`composer.json`と`composer.lock`も追加し、今後のComposer操作は「ホストの（プロジェクト要求より古い）PHPで無理に実行する」のではなく「wp-envコンテナの実PHP 8.3で実行する」ことを標準手順とした。

---

## 1. Office Profileの項目とスコープ判断

正式仕様（01仕様書§11「事務所基本情報」、02仕様書§4、AGENTS.md §6の「Office Profile」列挙）から、以下を今回のOffice Profileデータとして実装した。

- 事務所名（office_name）
- 代表者名（representative_name）
- 所在地（address）
- 電話番号（phone）
- 営業時間（business_hours：週次の定休日／開始・終了時刻＋臨時休業・年末年始・夏季休業等の期間指定リスト）
- SNSリンク（sns_links：名称＋URLの繰り返し項目）

### 意図的にスコープ外とした項目

AGENTS.md §6はCoreの責任範囲として「Office Profile」と「専門家情報」を**別項目**として列挙している。この区別に基づき、以下は今回のOffice Profileへ含めなかった。

- **資格・所属、経歴、写真、紹介文等**（02仕様書§8 PROFILE）: 個人の信用情報としての性質が事務所基本情報と異なり、「すべて任意、埋めなくてもデザインが成立する」という別種の要件を持つ。将来のConstruction Orderで扱う。
- **CTA・相談方法**: AGENTS.mdでCoreの別責任項目として列挙されている。
- **最寄駅・徒歩時間・駐車場・地図表示方式**（02仕様書§13 ACCESS）: ACCESSページ固有の補足情報であり、今回の「事務所基本情報」の中核集合には含めなかった。

これらを含めるべきかどうかは仕様上ボーダーラインであり、クロエの独自判断で追加していない。次回の仕様確認時に、スコープ区分の妥当性を確認することを推奨する。

**【2026-08-26 追記：CLOSED】** 上記の要確認事項は、同日中にDecision 022（士業法人・複数専門家対応、`04_astrea_free_v1_preconstruction_decisions.md`）として正式にFIXされた。

- 資格・所属、経歴、写真、紹介文等 → **Professional Profile**（Office Profileとは別責任、0〜複数人対応。未実装）
- CTA・相談方法 → **CTA / Consultation**側の責任（未実装）
- 最寄駅・徒歩時間・駐車場・地図関連 → **ACCESS**側の責任（未実装）。所在地そのものはOffice Profileを正本として再利用する。

本Construction Order 002で実装したOffice Profileの構造・スコープは、この正式FIXの結果とも一致しており、作り直しは不要と確認された。

---

## 2. データ設計

### 保存方式：WordPress Options API（単一の構造化Option）

Option名：`astrea_core_office_profile`（Decision 012のprefix規約に準拠）

比較検討した選択肢：

| 方式 | 判断 |
|---|---|
| 単一の構造化Option（採用） | Header/Footer等で一括参照する用途に対し1回の読み取りで完結。`schema_version`をOption内に同梱でき、将来のMigrationの起点にしやすい |
| フィールドごとに個別Option | 個別取得は容易だが、Migration対象キーの把握・Settings APIとの結線が煩雑になり、今回の規模（6項目程度）には過剰 |
| 独自DBテーブル | 検討したが、AGENTS.md §7（不必要な独自実装の回避）、および本件がリレーショナルな大量データではなく単一サイト設定であることから不採用。独自Tableが必要になるとしたら、それはService/FAQ等の複数件データが対象になった時点であり、Office Profileはその対象ではない |

独自DB Tableは作成していない。

### Schema Version / Migration

Option内に`schema_version`（現在1）を保持する。**Migration実行機構（バージョン差分適用のRunner）は今回実装していない。**

理由：これがOffice Profileの初回スキーマであり、移行元となる旧データが存在しないため。

必要になる時点：将来`schema_version`を2以上へ上げるようなデータ構造変更（例：営業時間のフィールド追加、SNSリンクの正規化変更等）が発生した時点で、`admin_init`等のタイミングで保存済み`schema_version`とコード側の期待値を比較し、差分に応じた変換関数を順に適用するMigration Runnerを追加する。それまでは、「Optionの中にバージョン番号を持たせておく」という最小限の備えに留める。

---

## 3. Sanitization / Validation / Security

- 保存経路はWordPress標準の**Settings API**（`register_setting()` + `settings_fields()` + `options.php`）を採用。これにより、Nonce/CSRF対策・Capability Check・保存処理をWordPress標準機構へ委譲した（独自の非標準フォーム処理を書いていない）。
- Sanitize Callback（`Astrea\Core\OfficeProfile\sanitize()`）で全フィールドを検証する。不正な値（不正な電話番号形式、不正な時刻形式、開始日>終了日、http/https以外のURLスキーム等）は**個別に**元の値へロールバックし、`add_settings_error()`で具体的な日本語エラーメッセージを表示する（一括リジェクトはしない）。
- 管理画面は`current_user_can( 'manage_options' )`を明示チェックし、権限のないユーザーには`wp_die()`する（Settings API自体の保護に加えた多層防御）。
- 実際にHTTPレベルで検証した内容はセクション5参照。

---

## 4. Core → Theme 公開境界

Construction Baseline §14「Coreが覚える、Blockがつなぐ、Themeが見せる」に従い、以下の二層構造とした。

1. **関数レベルの公開API**：`Astrea\Core\OfficeProfile\get_office_profile(): array`。Theme・将来のPRO・将来のDynamic Blockが読む唯一の窓口。内部のOption名・保存構造には一切依存させない。
2. **Block Bindings Source**：`astrea-core/office-profile`（`register_block_bindings_source()`、WordPress標準API）。単純なスカラー値（office_name, representative_name, address, phone）のみを公開する。この文字列（source名＋`args.key`）自体が、ThemeとCoreの間の技術的な契約であり、Theme側はCoreのPHP関数を一切呼び出さずに接続できる。

構造化データ（business_hours, sns_links）はBlock Bindingsの対象外（Block Bindingsはスカラーなブロック属性しか扱えない）。これらをTheme側で表示する場合は、将来のConstruction OrderでDynamic Blockを追加する。

---

## 5. Theme最小表示接続とDecision 021の実証（実機検証済み）

`theme/parts/header.html`に、`astrea-core/office-profile`（key: office_name）へBindingsで接続した`core/paragraph`を1つ配置し、`templates/index.html`から読み込ませた。

WordPress実機（wp-env）上で以下を**実際にHTTPリクエストで検証した**（すべてPASS）。

| # | 検証内容 | 結果 |
|---|---|---|
| A | Nonce不正なPOSTを`options.php`へ送信 | **HTTP 403で拒否**（保存されない） |
| B | 正しいNonce・正しい値でフォーム送信 | **HTTP 302（成功）、Option正しく保存** |
| C | 不正な電話番号（`<script>`混入含む）・不正な時刻（`25:99`）で送信 | **各フィールド個別に元の値を保持、日本語エラーメッセージ表示、他の正常フィールドは保存継続** |
| D | 保存したoffice_nameがHomeページのBindings接続段落に表示されるか | **表示される（Block Bindings経由）** |
| E | Core無効化 | **HTTP 200維持、office_nameは表示されない（静的Fallbackへ安全に劣化、Fatal/Warning/Noticeなし）** |
| F | Core無効化中もOption自体は保持されているか | **保持されている（Decision 019準拠）** |
| G | Core再有効化 | **office_nameの表示が復元される** |
| H | 権限のないユーザー（subscriber）による管理画面アクセス | **`wp_die()`で拒否される（管理者では正常表示）** |

`wp-content/debug.log`（`WP_DEBUG_LOG`有効）はこの一連の検証を通じて**一度も生成されず**、PHP Notice/Warningは検出されなかった。

---

## 6. Uninstallとの整合（Decision 019）

`core/uninstall.php`は引き続きno-opのまま維持し、Office Profile用のOption（`astrea_core_office_profile`）を**削除する処理は追加していない**。コメントで当該Option名を明示し、「将来の明示的な完全削除フローが対象とすべきデータ」として識別可能にした。

`deactivate()`（Plugin無効化フック）も引き続きno-op。PHPUnitテストで「無効化してもOffice Profileデータが消えないこと」を直接検証済み（`test_deactivate_does_not_delete_office_profile_data`）。

---

## 7. Test / CI

### 7.1 PHPUnit（`tests/OfficeProfileTest.php`、20件・40assertions、全PASS）

WP_UnitTestCaseを用い、ASTREA Coreのみを読み込んだ状態（Themeは読み込まない）でテストした。カバー範囲：

- 未保存時のデフォルト値
- 各スカラーフィールドのSanitization（タグ除去等）
- 電話番号・営業時間・臨時休業・SNSリンクの正常系／異常系（ロールバック・行スキップ・拒否）
- 管理画面のCapability Check（一般ユーザーは`WPDieException`、管理者は正常描画）
- Block Bindingsの`get_bound_value()`（許可キー／未許可キー／未設定値のnullフォールバック）
- 無効化してもデータが消えないこと

**意図的にPHPUnitで検証していない事項とその理由**：Nonce拒否の実地検証はWordPress core自身の`options.php`が担う領域であり、自作コードの再テストになるためPHPUnitでは行わず、セクション5に記載の**実際のHTTPリクエストによる手動検証**で確認した（403応答を実際に確認済み）。Theme側の表示接続（Block Bindings経由の実描画、Core活性状態の遷移）もPHPUnit単体では検証が難しい領域のため、`tools/ci/smoke-test.sh`（実際のwp-env上のHTTPレスポンス検証）で担保する设计とした。

### 7.2 環境構築上の技術的判断（Composer/PHPUnitとwp-envの統合）

- `wp-phpunit/wp-phpunit` 6.9系は**PHPUnit 11と非互換**（`PHPUnit\Util\Test::parseTestMethodAnnotations()`がPHPUnit 11で削除されているため）だったので、実績のある**PHPUnit 9.6**へ切り替えた。将来wp-phpunitがPHPUnit 11以降に正式対応した時点で見直す。
- `.wp-env.json`の`mappings`に`vendor`と`tests`を追加し、`wp-env`の`tests-cli`コンテナ（WordPress 7.x + PHP 8.3 + 実DB）を再利用してPHPUnitを実行する方式とした。標準の「別途MySQLサービスコンテナを立てる」方式ではなく、Decision 020で確定済みの「WSL2 + Docker + wp-env」という単一の標準環境をそのまま流用する形にしている。
- `tests/wp-tests-config.php`でABSPATHを`/var/www/html`（wp-envのWordPressコア実体）へ向けている。プロジェクトルート全体をマウントする案は`.git`や`node_modules`等が意図せずコンテナへ露出する衛生上の懸念があったため採用しなかった。

### 7.3 `tools/ci/smoke-test.sh` の拡張（既存A〜Dは無変更、E〜Iを追加）

既存の`A. Theme only` `B. Theme + Core` `C. Core deactivated` `D. Core reactivated`（Construction Order 001）はコードを変更せずそのまま維持し、その後段に以下を追加した。

- E. `sanitize()` + `update_option()`による保存、公開APIでの読み取り確認
- F. Homeページの実際のHTTPレスポンスにOffice Profile値がBlock Bindings経由で出力されることを確認
- G. Core無効化時に古い値が残留表示されないことを確認
- H. Core無効化中もOptionデータが保持されていることを確認
- I. Core再有効化で表示が復元されることを確認

ローカルで全項目PASSを確認済み（詳細はHISTORY.md参照）。

---

## 8. 発見した仕様上の要確認事項

1. ~~Office Profileの正確な項目範囲（セクション1参照）。「資格・所属」「相談方法」「ACCESS固有項目」を含めるかどうかは、02仕様書§4の記述だけでは断定できず、クロエの判断で除外した。~~ **CLOSED（2026-08-26 Decision 022により確定）。** 詳細はセクション1追記を参照。
2. 上記以外に、Baselineとの矛盾は発見していない。
