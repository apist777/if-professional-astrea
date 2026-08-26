# CONSTRUCTION ORDER 003A — 代表者情報の正本変更 / Core無効時URL扱いの正式化 実施報告

- 実施日: 2026-08-26（JST）
- 担当: クロエ（Claude）
- 対象: 代表者情報のProfessional Profileへの正本移行、Schema Migration、Core無効時URL扱いの正式仕様化
- 基準文書: `docs/specifications/05_astrea_free_v1_construction_baseline.md`、Decision 001〜024
- 仕様変更: あり（Decision 023・024として正式反映）

---

## 1. 追加Decision

- **Decision 023** — 代表者情報の正本は Professional Profile
- **Decision 024** — Core無効時のCore所有URLに関する保証範囲

詳細は`docs/specifications/04_astrea_free_v1_preconstruction_decisions.md`を参照。

---

## 2. 代表者の保存方式

**Professional Profileにpostmeta `astrea_professional_is_representative`（boolean）を追加した。**

- `register_post_meta()`で`type: boolean`、`sanitize_callback: rest_sanitize_boolean`として登録。
- 管理画面（専門家プロフィール編集画面のMeta Box）にチェックボックス「代表者として表示する」を追加。具体的な肩書テキスト（代表社員・所長・代表税理士等）は、既存の「資格・肩書」項目（`astrea_professional_qualification`）を使い、新しいテキスト項目は追加していない。
- 公開API：`Astrea\Core\ProfessionalProfile\get_representatives(): array`を追加。`get_profiles()`のうち`is_representative === true`のものだけを、既存の確定順（menu_order→title→ID）のまま返す。
- **複数代表者への一意制約は実装していない。** 士業法人には複数の代表社員が存在しうるため、単一代表者に制限する明確な根拠が既存仕様にも実態にも見当たらなかった。この判断（本当に複数代表者を許容すべきか）自体は要確認事項として残している（セクション6参照）。

---

## 3. 旧`representative_name`の処理（Migration方式）

**自動でProfessional Profileの人物データを生成・統合することはしていない。**

検討した3つのケース（Professional Profile 0件／同名Professional存在時／異なるProfessional存在時）のすべてにおいて、**自動的な人物生成・自動flagづけは危険と判断し、行わないという方針で統一した**。理由：

- 0件の場合：バイタ文字列から実在する「人物」コンテンツ（将来的に写真・経歴等を持ちうる本格的なコンテンツオブジェクト）を無断で生成することは、ユーザーの意思を超えたコンテンツ生成にあたる。
- 同名Professionalが存在する場合：名前の文字列一致だけで「この人物が代表者である」と自動判定するのは、同姓同名の可能性・表記揺れ等を考慮すると不正確なデータを生む危険がある。
- 異なるProfessionalが存在する場合：どのProfessionalを代表者とすべきか判断する情報がなく、誤った自動選択はより悪い結果を招く。

### 採用したMigration方式

Office Profileのschema_versionを1→2へ引き上げるVersioned Migrationを実装した（`Astrea\Core\OfficeProfile\maybe_migrate()`、`plugins_loaded`フックで実行）。

- 旧`representative_name`の値が存在する場合、内部専用キー`legacy_representative_name`へそのまま保存し直す（データ損失なし）。
- 旧`representative_name`キー自体は削除する（以後、管理画面フォームにこのフィールドは存在しない）。
- **`legacy_representative_name`は`get_office_profile()`の一般公開契約に含めない**（Theme・PROが読むべき値ではなく、移行案内のためだけに存在する内部専用値と位置づけた）。
- Migrationは「一度だけ」実行される。すでにschema_versionが2以上のサイト、および一度も保存されたことのないサイト（真の新規インストール）では何も書き込まない（無用な書き込み・容量増加を避けるため）。

### 案内の仕組み（管理画面通知）

`legacy_representative_name`が空でなく、かつ`is_representative`が立っているProfessional Profileが1件も存在しない場合、ASTREA管理画面・専門家プロフィール一覧/編集画面に警告Noticeを表示する。

- 「以前入力されていた代表者名「○○」があります。専門家プロフィールを追加し、「代表者として表示」にチェックを入れてください。」という具体的な案内文（旧値をそのまま表示するため、ユーザーは再入力ではなくコピー＆ペーストで済む＝「一度入力した情報は可能な限り再利用する」の実践）。
- **Dismiss機構は実装していない。** Noticeの表示条件そのものが「未解決」を意味するため、代表者を1人でも指定すれば自動的にNoticeが消える。JS/AJAXによる明示的な閉じるボタンの実装を避け、独自Frameworkの増殖を回避した。

---

## 4. 影響範囲の確認

| 領域 | 影響 |
|---|---|
| Professional Profileの代表者識別 | 新規追加（`is_representative`、`get_representatives()`） |
| Office Profile `representative_name` | 廃止。管理画面フォームから削除。既存データは`legacy_representative_name`へMigration |
| 公開API | `get_office_profile()`の返り値から`representative_name`キーを削除。`legacy_representative_name`は内部専用（一般公開契約外）。`ProfessionalProfile\get_representatives()`を新設 |
| Theme表示 | **変更なし。** Theme側は元々`representative_name`を一切表示しておらず（Construction Order 002完了時点で未使用と確認済み）、Block Bindingsの`ALLOWED_KEYS`から`representative_name`を削除したのみ |
| Migration / Backward Compatibility | Schema v1→v2 Migrationを新規実装（Office Profile初のMigration実行コード） |
| Sanitization / Security | `is_representative`はチェックボックスとして、既存のNonce（`astrea_professional_meta_nonce`）・Capability（`current_user_can('edit_post', $post_id)`）保護の対象に含めた。既存のセキュリティ水準を変更していない |
| Core停止 / 復帰 | `is_representative`はpostmetaのため、Office Profile・他のProfessional Profile項目と同様にDecision 019の対象（無効化で削除しない）。実機確認済み（セクション5） |

---

## 5. Test / CI結果

### PHPUnit

`tests/OfficeProfileTest.php`に8件、`tests/ProfessionalProfileTest.php`に7件、計15件を追加。全体で**54 tests / 82 assertions、全PASS**。

新規テストの内訳：

- Migrationが実データを正しく変換すること（v1→v2、`legacy_representative_name`への保全、旧キー削除）
- Migrationが「一度も保存されていないサイト」に対してno-opであること（無用な書き込みをしないこと）
- Migrationが二重実行されないこと（一度手動でクリアした値をMigrationが復活させないこと）
- `sanitize()`がもはや`representative_name`を処理しないこと、`legacy_representative_name`を書き換えないこと
- 管理画面通知の表示条件（未解決時に表示、代表者flag設定後に自動的に消えること、`legacy_representative_name`が空なら最初から表示されないこと）
- `is_representative`の0人/1人/複数人、Nonce拒否、チェックボックスOFF時の解除

### 実機wp-env検証（すべて実際のHTTPリクエストで確認）

| # | 検証内容 | 結果 |
|---|---|---|
| — | v1形式のOffice Profile Optionを直接投入し、実際のページリクエストでMigrationが走ること | schema_version 1→2、`representative_name`削除、`legacy_representative_name`へ値保全、すべて実際のHTTPリクエスト経由で確認 |
| — | Office Profile管理画面から旧「代表者名」フィールドが消えていること | 確認済み（フォームに該当input要素が存在しない） |
| — | 未解決時に管理画面へ警告Noticeが表示されること | 実際にログインし、ASTREA管理画面をHTTP取得して確認 |
| — | Professional Profileの編集画面から実際に「代表者として表示」チェックボックスをNonce付きでPOSTし、保存されること | **実際のWordPress投稿編集フォームへの本物のHTTP POST**（ログイン→編集画面取得→Nonce抽出→POST）で確認。関数呼び出しのシミュレーションではない、真のエンドツーエンド検証 |
| — | 代表者指定後、管理画面のNoticeが自動的に消えること | 確認済み |
| — | 公開API（`get_representatives()`）が正しく反映すること | 確認済み |
| — | Core無効化・再有効化を経ても`is_representative`のデータが保持・復帰すること | DB直接クエリおよび公開APIの両方で確認済み |

`tools/ci/smoke-test.sh`にPart 4（T〜X）として自動化し、既存Part 1〜3（Construction 001/002/003）を無変更のままRegression PASSを維持。ローカルで2回連続実行して冪等性を確認済み。

### PHPCS

エラー・警告0件（実PHP 8.3環境）。

### GitHub Actions

初回pushでは`Theme / Core independence smoke test`ジョブがPart 4（U）で失敗した。原因調査の結果、**実装のバグではなく、smoke-test.shスクリプト自身に潜んでいたbashのパイプ処理バグ**と判明した。

**根本原因**：`echo "$ADMIN_HTML" | grep -qF "..."`という記述で、`grep -q`は最初の一致を見つけた時点で即座に終了する。しかし`$ADMIN_HTML`は約72KBあり、単一のpipe buffer（通常64KB）に収まらないため、`echo`側がまだ書き込み中に`grep`が読み取りを打ち切ると、`echo`がSIGPIPEを受けて異常終了する。スクリプト冒頭の`set -o pipefail`により、パイプ全体の終了ステータスが「`grep`は一致を見つけて成功したにもかかわらず、`echo`のSIGPIPE異常終了（141）」に置き換わり、`if`文が「一致しなかった」と誤判定していた。`grep -c`（全件カウント、早期終了しない）で同一データ・同一パターンを検証すると正しく1件ヒットすることを診断ログで確認し、この理論を実証した。

**修正**：該当箇所をすべて`echo "$VAR" | grep ...`からヒアストリング`grep ... <<< "$VAR"`へ変更した。ヒアストリングはシェルが一時ディスクリプタ経由でデータを渡すため、別プロセス間のパイプによるSIGPIPE競合が発生しない。

この過程で計6回のCI往復を要した（ログイン成功確認→バイト単位でのHTML内容確認→パターン再照合→根本原因特定の順で切り分けた）。最終的に修正pushで**3ジョブ全Green**（[run 32959489323](https://github.com/apist777/if-professional-astrea/actions/runs/32959489323)）。

なお、調査の過程で試みた「`LC_ALL=C.UTF-8`をスクリプト全体へグローバル設定する」という対症療法は、ローカル検証で`wp-env`自身のNode.js内部通信（`got`ライブラリ）を壊す副作用が判明したため撤回した。ロケール設定はプロセス全体に伝播するため、`wp-env`のようなNode.jsツールを内部で呼び出すスクリプトでは、こうしたグローバルな環境変数変更は避けるべきという教訓を得た。

---

## 6. Core無効時のURL扱い（Decision 024）の正式化

Construction Order 003で発見された「Core無効時に`/professionals/`がHTTP 200のFallbackになる」挙動について、以下を正式に確定した。

- FREE v1が保証する範囲は、Theme正常動作・Fatal/Warning/Notice無し・壊れたMarkup無し・古いデータ残留表示無し・再有効化後の正常復帰、の5点に限定する。
- HTTP Statusが常に正しい404を返すことは保証対象に**含めない**。
- この挙動を是正するためだけにThemeへCore所有CPT・URLの知識を持たせることはしない（Decision 021の原則を優先）。

製品コードの変更は行っていない（現状の挙動をそのまま正式仕様として承認した）。

---

## 7. 発見した仕様上の要確認事項（新規）

1. **複数のProfessional Profileを「代表者」として同時に指定できることの是非。** 一意制約を設けない実装方針は確定したが、これがFREE v1として正しい仕様かどうか（将来的に単一代表者への制限が必要と判明する可能性を含む）は、クロエの独自判断で確定していない。次回の仕様確認を推奨する。
2. 上記以外に、Baseline / Decision 001〜024との矛盾は発見していない。
