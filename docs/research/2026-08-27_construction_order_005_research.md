# CONSTRUCTION ORDER 005 — Contact / 問い合わせ基盤 着工前調査・設計

- 実施日: 2026-08-26〜27（JST、夜間施工）
- 担当: クロエ（Claude）
- 対象: Contact（問い合わせ）機能のCore実装
- 基準文書: `05_astrea_free_v1_construction_baseline.md`、Decision 001〜025、`02_astrea_free_v1_specification.md` §15

---

## 1. 正式仕様からの要件抽出

### 1.1 フォーム項目（02仕様書§15）

> 名前、メール、電話、件名、問い合わせ内容、Privacy Policy同意 等の基本項目を中心とする。使用・必須・任意程度の設定は可能とする。

- 「使用・必須・任意程度の設定は可能とする」は明文であり、**電話・件名について「使用する/しない」「必須/任意」を管理画面で設定可能にする**（実装範囲）。
- 名前・メール・問い合わせ内容は、問い合わせという機能そのものが成立するための最低限の項目であり、常時使用・必須とする（これを無効化できると「返信先のない問い合わせ」等、機能自体が壊れるため）。
- Privacy Policy同意は、WordPress標準のPrivacy Policyページ機構（`get_privacy_policy_url()`）と連動させる：サイトにPrivacy Policyページが設定されている場合のみ同意チェックボックスを表示・必須とし、設定されていない場合は表示しない。独自の「有効/無効」トグルを追加で作らず、WordPress標準の仕組みをそのまま利用した（Decision 016「WordPress標準Privacy機能を活用した設定・編集を案内する」に整合）。

### 1.2 保存・Retention（Decision 004）

> 問い合わせは送信時点でCoreへ即時保存する（メール配送の成否に関わらず保存する）。保存期間は10 / 30 / 60 / 90日から選択可能とし、初期値は30日とする。保存期間経過後は自動削除する。

- 実装順序は必ず「Validation/Security → Core保存 → 通知処理」。メール送信の成否は保存の可否に一切影響させない。
- Retentionは10/30/60/90日の4択、初期値30日。

### 1.3 管理画面・通知タイミング（Decision 005）

> 管理画面に問い合わせ一覧、未確認状態、未確認件数を表示する。通知タイミングは即時通知と指定時刻のまとめ通知から選択可能とし、初期値は即時通知とする。

- 「未確認」は既読/未読の軽量な状態管理（postmeta boolean）。
- 通知タイミングは即時 or 「指定時刻のまとめ通知」（digest）の2択、初期値は即時。

### 1.4 CRM化しない境界・CSV Export（Decision 006）

> FREE v1では問い合わせ機能をCRM化しない。高度な検索、顧客管理、案件管理等は作らない。適切な管理権限を持つユーザーが、現在保存されている問い合わせをCSVで一括Exportできるようにする。CSVには保存期間内のデータのみを含め、削除済みデータは対象外とする。

- **検索機能は追加しない**（明文の除外事項）。
- Export形式はCSVで明確に確定済み（他形式を追加しない）。
- Export時は保存期間内のデータのみを対象とする（生成時点で再度Retention判定を行う。物理削除がまだ走っていない期限超過データを誤ってExportに含めない）。

### 1.5 Spam/Bot対策（Decision 007）

> 外部CAPTCHAを必須にせず、WordPress標準のSecurity機構、Nonce、Validation / Sanitization、Honeypot、連続送信抑制等の低負担な基本対策を標準搭載する。曖昧なSpam判定のみを理由に正規の問い合わせを無断削除しない。

- Nonce、Honeypot、連続送信抑制（Rate Limit）を実装。外部CAPTCHAは追加しない。
- 「曖昧なSpam判定のみを理由に正規の問い合わせを無断削除しない」は、**すでに保存された問い合わせを事後的にあいまいな基準で削除する**ことを禁じる規定であり、Honeypot（隠しフィールドへの入力=ほぼ確実にBot）による**送信時点での保存前ブロック**とは性質が異なる。保存前ブロックは「曖昧な判定」ではなく二値的なシグナルであるため、この制限には抵触しないと判断した。

### 1.6 メールインフラ（Decision 003）

> 問い合わせ機能はWordPress標準のメール送信機構（`wp_mail()`等）を利用する。Project-if独自のメール配送サーバーを必須としない。通知先メールアドレスの登録・変更時は、確認メールと一回限り有効なTokenを含む確認URLにより、そのアドレスが実際に受信可能であることを確認する仕組みを設ける。

- `wp_mail()`のみを使用。独自SMTP Clientは実装しない。
- Token確認の具体的な有効期限（時間数）・Rate Limitの具体的な閾値（秒数・件数）は仕様に明記がないため、実装判断として以下を採用し本書に明記する（仕様変更ではなく実装パラメータの決定）：
  - Token有効期限：**24時間**
  - Rate Limit：同一送信元（ハッシュ化IPで識別、後述）につき **20秒に1回、かつ1時間あたり5回まで**

### 1.7 Privacy（AGENTS.md / Decision全体の精神）

- 本文・メールアドレス等をdebug logへ出さない。
- Tokenをログへ出さない。
- 公開REST APIへ問い合わせデータを一切露出しない（`show_in_rest => false`）。
- IPアドレスは**恒久保存しない**。Rate Limit実装のためだけにハッシュ化した形で数十秒〜数時間の短命Transientとして一時的に利用するのみで、問い合わせレコード自体には一切含めない（スパム対策に便利という理由だけでの個人情報追加保存を禁じる指示に従い、恒久フィールド化はしない）。

---

## 2. 保存方式の比較

Contactは「訪問者から送信される一時的なデータ」であり、Office/Service/Price/FAQのような「事務所が公開するコンテンツ」とは性質が異なる。しかし0..N件・個別に削除可能・日付ベースでの絞り込みが必要という点は共通する。

| 観点 | CPT + Post Meta | Options API | 独自DB Table |
|---|---|---|---|
| 0..N件の個別レコード | ◎ 標準の投稿ストレージ | ✕ 配列1レコードでは大量データに不向き | ◎ だが独自スキーマ管理が必要 |
| 送信日時での絞り込み（Retention） | ◎ `post_date_gmt`を使った標準`WP_Query`の日付クエリ | ✕ | △ 独自SQL |
| 個別削除（Retention/将来のUninstall） | ◎ `wp_delete_post()` | ✕ | △ 独自DELETE文 |
| 検索機能を作らない（意図的抑制） | ◎ 独自の一覧画面を自作すればWordPress標準検索UIを混入させずに済む | - | ◎ |
| 公開REST非露出 | ◎ `show_in_rest => false`で標準的に遮断 | ◎ | ◎（独自実装次第） |
| Migration/Schema管理の複雑さ | 低い（WordPress標準機構に委任） | 低い | 高い（独自Migration機構が必要） |

**独自DB Tableは不要と判断した。** CPT（`astrea_inquiry`）で全要件（個別削除・日付絞り込み・非公開・非検索）を標準機構だけで満たせるため、Construction Order 002〜004で確立した「独自DB Tableは避け、WordPress標準機構を優先する」方針をそのまま踏襲する。未承認の独自DB Table採用は発生していないため、停止・報告事由には該当しない。

### CPT設計の要点

- `public => false`, `publicly_queryable => false`, `show_in_rest => false`, `exclude_from_search => true`：フロント公開・REST公開を遮断。
- `show_ui => false`, `show_in_menu => false`：WordPress標準の投稿編集画面（`edit.php`／`post.php`）を一切生成しない。理由：問い合わせは訪問者が送信した**改変されるべきでない記録**であり、通常の投稿編集UI（本文編集・タイトル編集）を露出すると、管理者が送信内容を書き換えられるように見えてしまう。そのため、CPTは「WordPress標準のストレージ機構」としてのみ利用し、閲覧・既読管理・Export専用の**独自の読み取り専用管理画面**（`core/includes/inquiry-admin.php`）を用意した。検索機能を作らないというDecision 006の明文とも整合する（標準の投稿一覧を使わないため、標準検索ボックスが混入する余地もない）。
- `post_status => 'private'`：WordPress標準の「非公開」ステータスをそのまま利用（`read_private_posts`権限を持つユーザーのみ閲覧対象という标準の意味論に自然に乗る）。
- `post_title` = 件名（未入力時は内部識別用に「(件名なし)」を補う。表示は管理画面のみ）。
- `post_content` = 問い合わせ内容（`sanitize_textarea_field()`でプレーンテキスト化、HTML/Script混入を最初から排除）。
- `post_date` / `post_date_gmt` = 受信日時（WordPress標準フィールドをそのまま「保存日時」として利用）。
- postmeta：`astrea_inquiry_name`、`astrea_inquiry_email`、`astrea_inquiry_phone`、`astrea_inquiry_is_read`（bool）、`astrea_inquiry_notified`（bool、まとめ通知の既送フラグ）、`astrea_inquiry_privacy_consent`（bool、送信時点での同意有無の記録）。

---

## 3. Token確認方式（通知先メールアドレス確認）

- 状態は**単一のTransient**（キー固定：`astrea_core_contact_pending_email_confirm`）に集約する。値は`{ email, token_hash, requested_at }`。TransientのTTLをToken有効期限（24時間）と一致させることで、**期限切れの自動失効をWordPress標準のTransient APIに完全に委任**し、独自の期限チェック処理を重複して持たない。
- Tokenは`wp_generate_password(43, false)`で生成する非推測な乱数文字列。**生Token自体は保存せず**、`hash('sha256', $token)`のみをTransientへ保存する（生Tokenはメール本文の確認URLにのみ含まれる）。
- 照合は`hash_equals()`を用いる（タイミング攻撃耐性）。
- 確認成功時：`notification_email`（確定値、Options内）を新アドレスへ更新し、Pending Transientを削除する（**削除により同一リンクの再利用＝Replayを防止**）。
- 確認前：`notification_email`は**旧アドレスのまま**とし、本番通知は旧アドレスへ送られ続ける（新アドレスへ切り替わるのは確認完了時のみ）。初回設定（旧アドレスが存在しない）の場合は、確認完了までいかなる通知先も存在しない状態となる——この間、通知は送信されない（データ保存自体には一切影響しない）。
- 再送：管理画面から「確認メールを再送」を押すと、同じ固定Transientキーへ新しいToken・新しい`requested_at`で**上書き**する。これにより古いTokenは即座に無効化される（Replay対策を兼ねる）。
- 確認画面（リンク先）は`admin-post.php`の`astrea_confirm_contact_email`アクションとして実装し、`admin_post_astrea_confirm_contact_email`と`admin_post_nopriv_astrea_confirm_contact_email`の両方にフックする（管理者がメールのリンクをクリックする時点でwp-adminにログイン済みとは限らないため）。

---

## 4. 通知（即時 / まとめ通知）と障害時挙動

- `astrea_core_contact_settings`オプションに`notification_timing`（`immediate` | `digest`、初期値`immediate`）と`digest_time`（`HH:MM`、まとめ通知時刻）を持たせる。
- **即時**：保存直後、`notification_email`が確定していれば`wp_mail()`を呼ぶ。戻り値が`false`でも、**すでに保存済みの問い合わせ本体には一切影響しない**（設計順序：保存→通知、の直接的な帰結）。
- **まとめ通知**：`wp_schedule_event()`で1日1回（`digest_time`ちょうどの時刻を狙って毎日0時に再計算しつつ実行時刻をチェックする方式ではなく、設定変更時に該当時刻ぴったりのUNIXタイムスタンプで`daily`イベントを再スケジュールする）、未通知（`astrea_inquiry_notified = false`）の問い合わせをまとめて1通のメールで通知し、送信後にまとめて`notified = true`を立てる。
- **通知失敗の可視化**：正式仕様（Decision 005/006）に「通知失敗を管理画面へ表示する」という明文の要求は見当たらなかった。本工程の指示文自身が「存在しない場合は勝手に高度なQueue等を追加しないこと」と明記しているため、**通知失敗の可視化機能は実装しない**。問い合わせデータ自体は通知の成否と無関係に必ず保存されるため、実務上の「相談を取りこぼす」リスクはすでに解消されている。

---

## 5. Retention自動削除とCleanupの二重化

- 主経路：`wp_schedule_event()`による1日1回のCronイベント（`astrea_core_contact_cleanup`）。Core有効化時（`activate()`）に未スケジュールなら登録し、無効化時（`deactivate()`）に解除する（Decision 019：無効化はデータを削除しないが、無効化中のPluginに対して延々とCronイベントを空撃ちさせ続ける状態は避ける、という一般的なWordPressプラグインの作法に従った）。
- 補完経路：WP-Cronはアクセスドリブンであり、指定時刻に必ず実行される保証がない（低トラフィックサイトでは特に遅延しうる）。指示文はこの点を明示的に考慮するよう求めているため、**`admin_init`にフックした軽量な補完チェック**を追加した：Transient（`astrea_core_contact_cleanup_last_run`、TTL 24時間）が存在しなければCleanup関数を実行し、Transientをセットする。これにより「管理者が管理画面を1日1回以上開く」という穏当な前提のもとで、Cronが遅延・失敗してもRetention超過データが長期間残り続けることを防ぐ。独自のDaemonやSchedulerライブラリは一切追加していない。
- **Core再有効化時のCleanup**：`activate()`内でCronの再登録に加えて、Cleanup関数を同期的に1回即時実行する。Core無効化中はCronが解除されているため、その間に保存期限を超えたデータが再有効化まで残る可能性があるが、再有効化の瞬間に必ず1回分のCleanupが走ることで速やかに解消される。

---

## 6. Core無効化 / Uninstallとの整合性（Decision 019との関係確認）

指示文は「Core削除時は保持、しかしContactはRetention対象」という一見した緊張関係の確認を求めているが、これは**Decision 019自身がすでに明文で解決済み**である：

> Decision 004の「保存期間経過後の自動削除」（時間ベース）と、本Decisionの「Uninstall時の完全削除」（ユーザー操作ベース）は別の削除経路であることを、実装・ドキュメント双方で区別する。

したがって、Retentionによる時間ベースの自動削除と、Core削除時にデータを保持するというUninstall方針は、矛盾ではなく最初から独立した別経路として設計されている。本実装もこの区別をそのまま踏襲し、新たな仕様判断は発生していない。

- Core無効化：問い合わせデータ（`astrea_inquiry`投稿・postmeta）を削除しない。Cronは解除するが、データそのものは保持。
- Core再有効化：保存期間内のデータへ再アクセス可能。§5のCleanupにより期限超過データのみ整理される。
- Core削除（Uninstall）：`uninstall.php`は他のCore所有データと同様、`astrea_inquiry`投稿群・`astrea_core_contact_settings`オプション・Pending Email確認Transientを削除しない（既存方針を継続）。

---

## 7. Theme接続方式

Decision 013「Coreが覚える、Blockがつなぐ、Themeが見せる、Patternが並べる」を継続する。

- Contactフォームは、入力→Validation→保存→通知→成功/エラー表示という**一連の処理そのもの**であり、単純な値の埋め込み（Block Bindings向き）ではなく、Priceで採用したのと同種の「一覧・条件分岐」に近い性質を持つ。よって**Dynamic Block（`astrea/contact-form`）**として実装し、Core自身がフォームHTML・Validationエラー表示・送信成功表示のすべてをサーバーサイドでレンダリングする。
- ThemeはこのDynamic Blockを配置する`theme/patterns/contact-form.php`という薄いPatternのみを持ち、フォーム処理ロジックを一切持たない（指示文§3の明文要求に直接対応）。
- Core無効化・非導入時：Block自体が未登録のため、WordPressの標準挙動により該当箇所は何も出力されない（Fatalにならない）。Construction Order 004のPrice Dynamic Blockと同じ安全性を、実機で同様に確認する。

---

## 8. Security設計の要点

- **CSRF**：`wp_nonce_field()` + `check_admin_referer()`（送信・確認・Export・既読切替のすべてのPOST/GETアクションに適用）。
- **Capability**：管理画面（設定・一覧・既読切替・Export・確認再送）はすべて`current_user_can('manage_options')`（Office Profile設定画面と同水準）。
- **Sanitization**：氏名・件名は`sanitize_text_field()`、電話は既存Office Profileと同じ電話用サニタイザ、メールは`sanitize_email()`、本文は`sanitize_textarea_field()`。
- **Output Escape**：管理画面表示は全項目`esc_html()`。CSV出力はCSV Injection対策（`=`, `+`, `-`, `@`で始まるセルの先頭にシングルクォートを付与して数式解釈を無効化）を実施。
- **Stored/Reflected XSS対策**：本文・件名等はプレーンテキストとして保存・エスケープ出力のみで、HTMLをそのまま解釈させる経路が存在しない。
- **不正ID参照**：既読切替・Export等はすべて内部で`get_post_type() === POST_TYPE`を確認してから操作する。
- **公開REST露出防止**：`show_in_rest => false`。
- **Token推測耐性・Replay・Expiry**：§3参照。
- **二重送信**：Post/Redirect/Getパターンにより通常のブラウザ操作（リロード等）での二重POSTを構造的に防止。同一Nonceの意図的な再送（ツールによるリプレイ）はWordPress標準Nonceの仕様上完全には防げないが、これはWordPress標準Nonceの一般的な性質であり、Contact固有の欠陥ではないと判断し、追加の独自Token機構は設けない。
- **異常入力サイズ**：各`sanitize_*`関数に加え、本文の最大長（実装judgment：5,000文字）を超える入力は送信エラーとする。

---

## 9-1. Test / CI — 実機検証で発見した2件の実バグと修正

推測で実装を進めず、実機wp-envへの本物のHTTPリクエストで検証する方針を貫いた結果、PHPUnitだけでは検出できない実バグを2件発見し、その場で根本原因を特定して修正した。

### バグ1：CSRF Tokenリダイレクト先URLの`\r`混入によるcurl「malformed URL」

`tools/ci/smoke-test.sh`内で、フォームのValidationエラー時にリダイレクトされるURLをHTTPレスポンスヘッダ（`Location:`）から抽出する際、`sed -n 's/^Location: \(.*\)\r*$/\1/p'`という正規表現を使用していた。HTTPヘッダは`\r\n`で終端されるが、GNU sedは行分割を`\n`のみで行うため、`\r`は行内の通常の1文字としてマッチ対象に残る。ここで貪欲な`.*`が末尾の`\r`まで飲み込んでしまい、後続の`\r*`（0回以上の`\r`）は何もマッチしないため、抽出結果の末尾に`\r`が混入していた。その結果、後続の`curl`呼び出しに渡るURLが「有効なURLの末尾に不可視の`\r`が付いた文字列」となり、curlが`CURLE_URL_MALFORMAT`（終了コード3）で失敗していた。

診断は、`printf '%q'`で変数をエスケープ表示することで初めて`\r`の存在を可視化できた（通常の`echo`表示では見えない）。**修正**：正規表現を`\([^\r]*\)\r*$`とし、キャプチャグループ自体が`\r`を含み得ないようにした。

この不具合は製品コード（Core）ではなく、smoke-test.sh自身のシェルスクリプトのバグである。Construction Order 003Aで発見したpipefail/SIGPIPEバグと同種の「シェルスクリプトが生のHTTPヘッダを文字列処理する際の落とし穴」であり、今後同種のヘッダ抽出処理を書く際はこのパターン（`\r`を明示的にキャプチャ対象から除外する）を踏襲する。

### バグ2：`register_setting()`の`sanitize_option`フィルタが内部の信頼された`update_option()`呼び出しまで横取りしていた

`Astrea\Core\Inquiry\Admin\sanitize_settings_and_reschedule()`は`register_setting()`経由で`sanitize_option_astrea_core_contact_settings`フィルタとして登録される。このフィルタは、WordPressの仕様上、**設定画面フォームからの保存だけでなく、`admin_init`が発火した後のリクエスト内で行われるあらゆる`update_option('astrea_core_contact_settings', ...)`呼び出しに対して無条件に適用される**。`admin-post.php`（Token確認リンクの遷移先）も内部で`admin_init`を発火させるため、`confirm_pending_email()`が確認済みメールアドレスを設定しようとして呼ぶ`update_option()`も、このフィルタを経由してしまっていた。

`sanitize_settings()`は「フォーム経由での`notification_email`の直接書き換えを許さない」という意図的な多層防御として、常に既存値へ差し戻す実装になっていた（Decision 003：確認済みTokenフロー以外でnotification_emailを変更させない）。ところがこの防御ロジックは、確認画面フォームからの入力だけでなく、Token確認成功後の**正規の内部書き込み**まで無差別に打ち消してしまい、「確認リンクをクリックすると`confirmed=1`にリダイレクトされるにもかかわらず、実際には`notification_email`が更新されない」という状態を引き起こしていた。

PHPUnitの単体テスト（`EmailConfirmationTest`）ではこの不具合を検出できなかった。理由は、`WP_UnitTestCase`のテスト実行コンテキストでは`admin_init`が発火せず、`register_setting()`が一度も呼ばれないため、問題のフィルタ自体が登録されない状態でテストが通っていたため。**実機wp-envへの本物のHTTP GETリクエスト（Token確認リンクの実クリック相当）で初めて検出できた**。

**修正**：`confirm_pending_email()`内の`update_option()`呼び出しの前後で、当該フィルタを明示的に`remove_filter()` / `add_filter()`することで、この1箇所の信頼された内部書き込みだけをフィルタの対象から除外した。

この発見は、「Settings APIの`sanitize_callback`はフォーム経由の保存だけに効くと思い込まない」という一般的なWordPress実装上の教訓であり、将来Core側で他の設定にも同様の「Token確認後の内部更新」パターンを実装する際は、同じ落とし穴を踏まないよう本書を参照すること。

---

## 9-2. Test / CI 結果サマリー

- **PHPUnit**：新規47件追加（`InquiryTest`・`ContactFormTest`・`EmailConfirmationTest`）、既存100件と合わせて**合計147 tests / 229 assertions、全PASS**。
- **PHPCS**：エラー・警告0件（実PHP 8.3環境、`WordPress` + `PHPCompatibilityWP` 標準）。
- **smoke-test.sh**：Part 1〜6（A〜AS）すべてPASS。Part 6（Contact、AF〜AS）は実機wp-envへの本物のHTTPリクエストで、フォーム送信成功/Validationエラー/CSRF拒否/Honeypot/Rate Limit/管理画面既読切替/CSV Export（Formula Injection対策含む）/Token確認（Replay拒否含む）/Core無効化・データ保持/Core再有効化/Retention Cleanupを検証。ローカルで2回連続実行し冪等性を確認済み。
- **GitHub Actions**：push後に確認（本報告の最終セクション参照）。

---

## 9. 発見した仕様上の要確認事項

1. Token有効期限（24時間）、Rate Limit閾値（20秒/1回、5回/時間）は正式仕様に具体的な数値の定めがないため、実装判断として本書に明記した数値を採用した。将来より厳密な運用要件が判明した場合は再確認を推奨する。
2. 通知失敗の可視化は明文の要求がないため実装していない。将来「メール到達性の可視化」が必要と判断された場合は、改めて仕様判断を仰ぐ。
3. 上記以外に、Baseline / Decision 001〜025との矛盾は発見していない。
