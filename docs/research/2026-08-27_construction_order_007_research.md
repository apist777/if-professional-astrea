# CONSTRUCTION ORDER 007 — Setup / Onboarding / Initial UX — 事前調査

**種別:** PRE-CONSTRUCTION RESEARCH（調査・設計のみ。製品コード変更なし）
**対象:** `theme/`, `core/` は本タスクでは未変更。
**Status:** RESEARCH COMPLETE
**関連:** Decision 016（初期セットアップ）, Decision 017（Accessibility）, Decision 018（第三者Plugin共存）, Decision 019（Uninstall）, Decision 021（Core任意・公式推奨）, Decision 022（Office/Professional責任境界）, Decision 026（SEO Foundation）, Construction 002-006実装

---

## 1. 前提として確認した既存仕様・Decision・実装状態

### 1.1 02仕様書 §21（初回セットアップ）原文

> テーマ有効化直後に設定地獄へ放り込まない。初回案内はスキップ可能とする。基本フローは、基本情報→問い合わせ方法→デザイン→推奨サイト構成→準備完了程度に留める。FREEではページを自動生成するのではなく、ユーザーへ「次に何をすればいいか」を案内する。既存資料で定義された、インストールから公開までの一本道とも整合させる。

### 1.2 02仕様書 §22（ASTREA Dashboard）原文

> WordPress標準機能を隠すのではなく、ユーザーが迷わない入口を提供する。Dashboardから、事務所情報、専門家情報、営業時間、問い合わせ、SEO・計測、デザイン、ページ制作等へ移動できる。設定状況は点数化せず、完了/推奨/任意程度のチェックリストで案内する。「全部埋めないと完成ではない」というUXにはしない。

この2節はすでに「何を作るべきか」に対する明確な方針を持っている。本調査は、この方針を**現在の実装（Construction 002-006完了後）に対して具体化する**ことが主目的であり、新しい方針を発明する仕事ではない。

### 1.3 Decision 016（初期セットアップ）確認事項

- Theme/Core有効化だけでページを勝手に大量生成しない。**ユーザーの明示操作**でのみ基本ページ一括生成を可能にする。
- 対象ページ例：ホーム・事務所概要・取扱業務・料金・FAQ・問い合わせ（Decision策定時点＝2026-08-25の例示）。
- 再実行しても重複生成・既存ページ上書きをしない。
- セットアップは何度でも再度開ける。途中終了可能。不足分だけ後から追加可能。**全項目完了を公開条件にしない。**
- 生成ページは通常のWordPress固定ページとして保存し、Core Uninstallの対象に含めない（ユーザーコンテンツ扱い）。

### 1.4 Decision 021（Core任意・公式推奨）確認事項

- 「Coreは推奨する。しかしThemeを人質にしない。」
- **「初回有効化時、Core未導入であれば『Coreを推奨』する案内を提示してよいが、案内をスキップしてもTheme自体は機能停止しない。」**→ Core推奨案内は既にDecisionレベルで許可済みの機能であり、新規Decisionを要しない。

### 1.5 Decision 022（Office/Professional責任境界）確認事項

- Office Profile＝事務所全体情報（事務所名・所在地・電話・営業時間・休業情報・SNS）。
- Professional Profile＝所属専門家個人情報（0..N）。
- **ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図）はOffice Profileに含めず、ACCESSという別責任として扱う。** → ACCESS機能はまだ実装されていない（Core側にACCESS用のデータもUIも存在しない）。これは本調査の「ページ生成」項で重要な制約になる（§8参照）。

### 1.6 現在の実装状態（コード確認済み）

`grep -rn "add_menu_page\|add_submenu_page" core/includes/*.php` の結果、現在ASTREA管理画面は以下の構造で**既に存在している**：

```
ASTREA（トップレベルメニュー = Office Profile設定画面そのもの）
├─ 専門家プロフィール（Professional Profile CPT、標準WP一覧/編集画面）
├─ 料金（Price CPT、標準WP一覧/編集画面、公開URLなし）
├─ 取扱業務（Service CPT、標準WP一覧/編集画面、+ 公開Archive URL `/services/` あり）
├─ FAQ（FAQ CPT、標準WP一覧/編集画面、+ 公開Archive URL `/faq/` あり）+ FAQカテゴリ（Taxonomy管理）
├─ 問い合わせ（Contact、独自の読み取り専用管理画面）
└─ SEO（独自の設定画面：OGP画像・Search Console verification）
```

これは02仕様書§22が要求する「事務所情報、専門家情報、…問い合わせ、SEO・計測…へ移動できる」入口の**大部分が既にWordPress標準のCRUD画面として存在する**ことを意味する。つまり007の仕事は「入口を新設する」ことではなく、**既存の入口への案内と状態の可視化**が中心になる。

追加確認事項：
- `theme/functions.php` に `is_core_active(): bool` が定義済みだが、**現在どこからも呼ばれていない**（Core推奨案内・Theme側の分岐は未実装のまま）。Decision 021が許可した「Core未導入時の推奨案内」は、まだ実装されていない未着手機能である。
- `theme/parts/header.html` は Office Profile の `office_name` バインディングのみを持ち、**`core/navigation` Blockは一切存在しない**（Navigation自体が未着手）。
- `theme/templates/` に `page.html` は存在しない（固定Pageは `index.html` へフォールバックする）。
- `theme/patterns/` には `price-list.php`（Dynamic Block埋め込み）と `contact-form.php`（Dynamic Block埋め込み）の2件のみ存在する。ホーム・事務所概要等のPatternは存在しない。
- Service/Professional/FAQは全て「0件でも安全に動作する」というEmpty State保証が Construction 002-006 を通じて既に確立している（Query Loop / Dynamic Blockいずれも0件時に破綻しない設計が実装・テスト済み）。これは「何を必須にすべきか」の判断において極めて重要な既存事実である。

---

## 2. User Journey（初回インストールから公開まで）と 必須/推奨/任意 分類

現在の実際の実装を基準に、Theme有効化から「専門家サイトとして意味のある形で公開する」までの流れを整理する。

| # | ステップ | 現在の実装上の入口 | 分類 | 理由 |
|---|---|---|---|---|
| 1 | Theme有効化 | WordPress標準（外観 > テーマ） | 必須（前提） | Themeなしでは何も始まらない |
| 2 | Core有効化 | WordPress標準（Plugin > 有効化） | 推奨 | Decision 021によりTheme単体でも安全に動作するため技術的必須ではない。ただしOffice Profile以下の全機能はCoreに依存する |
| 3 | 事務所情報（Office Profile）入力 | 「ASTREA」トップページ | 推奨（強く） | 未入力でもTheme/Coreは破綻しないが、事務所名すら無い状態は「公開可能な士業サイト」として意味を持たない |
| 4 | 専門家プロフィール登録（0..N） | 「専門家プロフィール」 | 任意 | Decision 022により0人でも正式に許容される構造。個人事務所では省略される場合がある |
| 5 | 取扱業務（Service）登録（最低1件推奨） | 「取扱業務」 | 推奨（強く） | 0件でもArchiveは破綻しないが、「何を相談できるか」が無いと集客サイトとして機能しない |
| 6 | 料金（Price）登録 | 「料金」 | 任意 | 02仕様書自体、料金非公開の事務所を排除しない設計（0件で正式に許容） |
| 7 | FAQ登録 | 「FAQ」 | 任意 | 0件で正式に許容 |
| 8 | 問い合わせフォーム設置 | Contact Form Dynamic Block（Patternで固定ページに埋め込み） | 推奨（強く） | フォーム自体はCore有効化のみで動作し「保存」は即座に機能する。ただし埋め込み先ページが無いと到達不能 |
| 9 | 通知先メールアドレスの確認（Token確認） | 「問い合わせ」画面 | 推奨（強く、ただし公開条件にはしない） | 未確認でもフォームは保存機能として正常動作するが、事務所側が新着問い合わせに気付けないリスクがある（Construction 005で確立済みの挙動） |
| 10 | SEO設定（OGP画像・Search Console） | 「SEO」画面 | 任意 | Construction 006で「無くても`wp_head`は安全にフォールバックする」設計を確立済み |
| 11 | 固定ページ作成（ホーム・事務所概要・料金ページ・お問い合わせページ等） | WordPress標準（固定ページ）+ 将来のASTREA一括生成機能（§8参照） | 推奨（強く、Price/Contactに限り実質必須） | Price・ContactはCPT自体に個別URLが無いため、**埋め込み先の固定ページが無いと事務所側からも訪問者からも到達不能**。Service/Professional/FAQは既にArchive URLを持つため固定ページなしでも到達可能 |
| 12 | Navigation（メニュー）設定 | WordPress標準（外観 > ナビゲーション or サイトエディタ） | 任意（ただし強く推奨） | 無くてもURLへの直接アクセスは可能。ユーザビリティ上は重要 |
| 13 | サイトロゴ/アイコン設定 | WordPress標準（外観 > サイトエディタ / 一般設定） | 任意 | WordPress標準機能、ASTREA固有の作業ではない |
| 14 | 公開確認 | WordPress標準（サイトの公開性設定） | 必須（前提） | WordPress自体の設定 |

**結論（重複を避けるため§3で詳述）：** 技術的に「必須」なのは Theme有効化 と WordPress自体の公開設定のみである。ASTREA固有の意味で「これが無いと専門家サイトとして機能しない」ものは Office Profile（事務所名）・Service最低1件・Priceまたは Contactの到達可能性、の3点に絞られる。

---

## 3. 現在の実装からの「最小公開ステップ」の算出

02仕様書のEmpty State方針（Construction 002-006で一貫して実装・テスト済み）により、**技術的にはASTREA Theme+Coreは0件データの状態でも一切のFatal/白画面を起こさず「公開可能」**である。これはこの3セッションかけて意図的に作り込まれた設計保証であり、007のUXが「これを埋めないと使えない」という体験を作ってはならないことの技術的裏付けでもある（02§22「全部埋めないと完成ではないというUXにはしない」と直接一致）。

したがって：

- **システムとして公開をブロックする必須項目は0件。**
- **「専門家サイトとして意味を持つ」ための実務上の最小ラインは以下の3点：**
  1. 事務所名（Office Profile最小限の入力）
  2. 最低1件のService（何を相談できるかの提示）
  3. 到達可能な問い合わせ手段（Contact Formを埋め込んだ固定ページが最低1つ存在すること）

この3点は「必須」ではなく「Setupチェックリストが強く推奨する」項目として設計する（§9のチェックリスト設計を参照）。これにより、02§21の「基本情報→問い合わせ方法→デザイン→推奨サイト構成→準備完了」という基本フローの前半2ステップと一致する。

---

## 4. Core案内方式（Core非導入時のガイダンス設計）

Decision 021は既にCore推奨案内を許可している。新規Decisionは不要。設計方針：

- **自動インストールは行わない。** WordPressのベストプラクティスとして、Pluginを他のPluginやThemeから無断で自動有効化することは推奨されない（後述§16のWeb調査結果）。ASTREA CoreがまだWordPress.org公式ディレクトリに公開されていない現段階では、検索クエリ付きの直接リンク（`plugin-install.php?s=astrea-core&tab=search`）が正しく解決する保証もない。案内文言は「ASTREA Coreを有効化してください」とし、リンク先はPlugin画面のトップ（`plugins.php`）に留める。将来公式ディレクトリに公開された時点で検索プリフィル付きリンクへ強化することを申し送り事項とする。
- **表示条件：** Theme有効かつ Core非活性（`is_core_active() === false`）の場合のみ、管理画面（ダッシュボード および ASTREAトップメニュー相当）に表示する。フロント側には一切表示しない。
- **非強制・Dismiss可能：** WordPress標準の `admin_notices` フック + `is-dismissible` クラスに加え、永続的なDismissには `update_user_meta()` によるユーザー単位の既読フラグを用いる（§16のWeb調査で確認した標準パターン）。強制リダイレクトは行わない（activation hookから直接`admin_notices`を出すのではなく、`admin_init`でのフラグ確認を用いる設計とする——activation直後のリダイレクトに巻き込まれる既知の落とし穴を回避するため）。
- **Fatal/Warning厳禁：** Decision 021の実装時遵守事項に従い、Core非活性時のTheme側の全処理は空欄・プレースホルダーへの安全なフォールバックを維持する（Construction 002-006で既に確立済みのパターンをそのまま踏襲するのみ）。

---

## 5. Welcome / Setup UI アーキテクチャ比較（6方式 × 10評価軸）

### 比較対象の6方式

- **A. 独立したフルスクリーンSetup Wizard**（プラグイン有効化後に専用画面へ強制的に案内し、ステップを順番に進めるモーダル/専用ページ形式。例：多くのECプラグインに見られる形式）
- **B. ダッシュボード常駐チェックリストWidget**（WordPress標準ダッシュボードに「セットアップ状況」ウィジェットを追加し、完了/推奨/任意を表示）
- **C. ASTREA専用「セットアップ」ページ + チェックリスト**（独立した管理画面ページを1つ新設し、そこにチェックリストと各機能への導線を集約）
- **D. 既存トップページ（Office Profile画面）拡張型**（新しい画面を作らず、既存の「ASTREA」トップメニュー画面の冒頭にチェックリストセクションを追加する）
- **E. 既存各設定画面へのリンクのみ**（チェックリストや状態表示すら作らず、WordPress標準の管理画面メニュー構造だけで案内を代替する）
- **F. インラインAdmin Notice方式**（チェックリストや専用画面を作らず、各管理画面の上部に「次にやるべきこと」を控えめな通知として出し続ける）

### 評価軸（10軸）と評価

| 評価軸 | A: 独立Wizard | B: Dashboard Widget | C: 専用Setupページ | D: 既存トップページ拡張 | E: リンクのみ | F: Admin Notice |
|---|---|---|---|---|---|---|
| 1. 60点公開思想との整合（§21「設定地獄へ放り込まない」） | 低（強制的・段階的・重い） | 中 | 中 | **高** | 高 | 中 |
| 2. 実装コスト（新規UI量） | 非常に高い | 中 | 高（新画面+状態管理） | **低（既存画面へ追記のみ）** | 最低 | 低 |
| 3. 状態管理の複雑さ（現在ステップ・スキップ状態の保持） | 高（専用の進捗保存が必要） | 低 | 中 | **低（実データから都度算出可能）** | なし | 低 |
| 4. スキップ可能性（§21「初回案内はスキップ可能」） | 実装が面倒（スキップ後の再開導線が必要） | 容易（自然に無視できる） | 容易 | **最も自然（見るだけで強制力なし）** | 容易 | 容易 |
| 5. 何度でも再度開ける（Decision 016） | 要「再度開く」導線の別実装 | 容易（常時表示） | 容易（URLに再訪するだけ） | **最も容易（既存トップページを開くだけ）** | 該当なし | 該当なし（常時表示のため） |
| 6. 「全部埋めないと完成でない」感の回避（§22） | 高リスク（ステップ形式は完了圧を生みやすい） | 低リスク | 中リスク | **低リスク（完了/推奨/任意の併記がしやすい）** | 該当なし | 低リスク |
| 7. WordPress標準機能を隠さない（§22） | リスクあり（独自画面に閉じ込めがち） | 低リスク | 中リスク（新画面が入口を分散させうる） | **最良（既存WP標準CRUD画面への外部リンク集約）** | 最良 | 良好 |
| 8. Accessibility実装コスト（Focus管理・Step復帰） | 高（複数画面間の遷移・状態保持が必要） | 低 | 中 | **低（単一静的ページ内のセクション）** | 最低 | 低 |
| 9. 将来のPRO拡張性（チェック項目の追加しやすさ） | 中（Step順序の再設計が必要になりがち） | 高 | 高 | **高（リスト項目の追加のみ）** | 低（構造自体がない） | 低 |
| 10. Core非活性時の劣化の綺麗さ | 要個別対応 | 容易 | 容易 | **容易（Core関連項目だけ「Core推奨」表示に切替）** | 該当なし | 容易 |

### 推奨：D（既存トップページ拡張型）＋ 軽量なF（Core未導入時のみAdmin Notice）の併用

理由：
- 現在の実装は既に「ASTREA」という単一の管理画面ハブ（トップレベルメニュー）を持っており、そこから各機能（専門家プロフィール・取扱業務・料金・FAQ・問い合わせ・SEO）へのサブメニューが揃っている。**02§22が求める「入口」は実質的にすでに存在している。** 007が新規に作るべきは「状態の可視化（完了/推奨/任意チェックリスト）」だけであり、新しい画面(C)やWizard(A)を作ることは、02§21が明示的に警戒する「設定地獄」化・過剰実装のリスクを生む。
- 独立Wizard（A）は、Decision 016の「何度でも再度開ける」「途中終了可能」「全項目完了を公開条件にしない」という要件を満たすために、進捗保存・スキップ状態・再開位置といった**新しい状態管理の仕組み**を要求する。これは「Setup状態は実データから判定する」という本調査の推奨方針（§6）と真っ向から矛盾し、余分な複雑性を生む。
- D方式に、Core非活性時専用の軽量Admin Notice（§4で設計）を組み合わせることで、「Core自体が無いユーザー」にも自然に気付きの導線を提供できる。

**却下したものの理由：**
- A（独立Wizard）：過剰実装・状態管理の複雑化・スキップ後の再開導線が別途必要になるため不採用。ただし「将来的にPROがより手厚いオンボーディングを提供する」余地としては選択肢に残しておく。
- C（専用Setupページ）：既存の「ASTREA」トップページと機能が重複し、入口が2つに分散する。「WordPress標準機能を隠さない」という§22の精神にもやや反する。
- E（リンクのみ）：状態の可視化が一切ないため、「完了/推奨/任意のチェックリストで案内する」という§22の明文要求を満たせない。

---

## 6. Setup状態判定方式（実データからの導出）

新しい進捗DB・専用オプションでの進捗フラグ管理は行わない。各チェック項目は、既存の公開APIから都度動的に判定する。

| チェック項目 | 判定方法（既存データソース） | 状態 |
|---|---|---|
| Core有効化 | `Astrea\Theme\is_core_active()` | 完了/推奨 |
| 事務所情報 | `Astrea\Core\OfficeProfile\get_office_profile()`（想定）の `office_name` が空でないか | 完了/推奨 |
| 専門家プロフィール | `Astrea\Core\Professional\get_professionals()`（想定）の件数 > 0 | 完了/任意 |
| 取扱業務 | `Astrea\Core\Service\get_services()` の件数 > 0 | 完了/推奨 |
| 料金 | `Astrea\Core\Price\get_prices()` の件数 > 0 | 完了/任意 |
| FAQ | `Astrea\Core\Faq\get_faqs()` の件数 > 0 | 完了/任意 |
| 問い合わせ到達可能性 | `astrea/contact-form` Blockを含む公開済み固定ページが1件以上存在するか（`has_block()` を全公開Pageに対して確認、または将来のページ生成機能が記録するID） | 完了/推奨 |
| 通知先メール確認 | Contact設定の `notification_email` が確認済み状態か（Construction 005の既存関数を再利用） | 完了/推奨 |
| SEO（OGP画像） | SEO設定の `og_image_id` が設定済みか | 完了/任意 |
| Navigation | `wp_navigation` 投稿タイプの投稿が1件以上存在するか | 完了/任意 |

この方式のメリット：
- 新しいオプション・テーブルを一切追加しない（着工前研究の明示指示「新しいDB Table不要」と自然に合致）。
- ユーザーが管理画面を経由せず直接データを追加・削除しても、チェックリストは常に実態と一致する（進捗DBのズレというクラスの不具合が原理的に発生しない）。
- 「全部埋めないと完成でない」UXを技術的に回避しやすい（各項目は独立した実データ判定であり、順序も強制しない）。

---

## 7. 既存サイト保護（新規/既存/大量投稿/既存ページ/既存Navigation/テーマ切替）

チェックリスト方式（自動生成を伴わない可視化のみ）を採用する限り、既存サイトへの破壊的影響は原理的に発生しない（表示のみで書き込みを行わないため）。破壊リスクが生じるのは §8 のページ一括生成機能に限られるため、そちらで個別に安全策を設計する。

- **新規サイト：** チェックリストは「未完了」項目を素直に表示する。
- **既存サイト（Astrea Theme以外からの乗り換え等）：** 既存の固定ページ・投稿・Navigationを一切変更しないため、チェックリストの「未完了」表示以外に影響はない。ただし「問い合わせ到達可能性」判定（`has_block()` による走査）は、既存の大量投稿があるサイトでは走査コストに配慮し、判定結果を短時間（例：1時間程度）の Transient にキャッシュすることを実装時の申し送り事項とする。
- **既存Navigationがあるサイト：** Navigation生成機能（§9）は「既存Navigationが1件も無い場合のみ」提案し、既存Navigationがあれば一切触れない設計とする。
- **テーマ切替時：** Core側のデータ（Office Profile等）はTheme非依存で保持される（Decision 013/021の既存設計）。チェックリストUI自体はTheme側管理画面拡張として実装するため、他Themeへ切り替えればチェックリストは表示されなくなるが、データは失われない。

---

## 8. Page生成の必要性・責任境界（Decision 016 との整合確認 — 要確認事項あり）

Decision 016（2026-08-25策定）は「ホーム・事務所概要・取扱業務・料金・FAQ・問い合わせ等の基本ページ」を一括生成候補として例示している。しかし、この例示はConstruction 003/004でService・Professional・FAQが**独自のCPT Archive URL**（`/services/`, `/professionals/`, `/faq/`）を持つ設計になる**前**に書かれたものである。現在の実装を踏まえると：

| Decision 016例示のページ | 現状のURL到達可能性 | 固定ページ生成の要否 |
|---|---|---|
| ホーム | WordPress標準の投稿一覧またはフロントページ設定で代替可能 | 任意（Design System領域、007では扱わない） |
| 事務所概要 | 到達可能なURLが存在しない（Office Profile情報を表示する固定ページが無い） | **必要**（ただし本文はユーザー自身が書く前提の空Page骨格のみ生成し、偽コンテンツは入れない） |
| 取扱業務 | **既に`/services/`のArchiveが存在する** | 不要（重複を避けるため、生成するなら「別ページ」ではなくArchiveへのリンクで足りる） |
| 料金 | 到達可能なURLが存在しない（Price CPTは意図的に個別URLを持たない設計） | **必要**（既存の`price-list.php` Patternをそのまま埋め込む） |
| FAQ | **既に`/faq/`のArchiveが存在する** | 不要 |
| 問い合わせ | 到達可能なURLが存在しない（Contact Form Dynamic Blockは埋め込み先ページが無いと到達不能） | **必要**（既存の`contact-form.php` Patternをそのまま埋め込む） |

**この表自体が「Decision 016の例示リストと現在のCPT Archive設計の間に、Decision策定後の実装進展によるズレがある」ことを示している。** これは既存Decisionと矛盾する新事実の発見に該当するため、本調査ではこの表を**提案**として提示するに留め、007着工前に正式な確認・合意を得ることを推奨する（詳細は§10「要確認事項」）。

生成する場合の実装方針（提案）：
- 生成ページは通常のWordPress固定ページとして保存する（Decision 016・019の要件どおり、Core Uninstallの対象外＝ユーザーコンテンツ扱い）。
- 重複防止は、生成時に固定ページIDをCore側オプション（例：`astrea_core_generated_pages`）に記録し、再実行時はそのIDが実在し削除されていないかを確認する方式とする（Decision 016「安全に判定できる仕組み」の具体化）。ユーザーが生成後にページを削除した場合は「再生成可能」として扱ってよい（強制復元はしない）。
- 生成は**明示的なユーザー操作（ボタンクリック）でのみ**発動し、Theme/Core有効化やチェックリスト表示のタイミングでは一切自動発動しない（Decision 016の核心要件）。
- 事務所概要ページの本文は、偽のダミー文章ではなく、空の状態（見出しのみ、または「ここに事務所の紹介を書きましょう」という明確にプレースホルダーと分かる案内文）で生成する（§10 Sample Data方針と一貫）。

---

## 9. Navigation自動生成の必要性

現状 `theme/parts/header.html` に `core/navigation` Blockが一切存在しない。WordPress公式ドキュメント（§16参照、2026-07-17更新のBlock Editor Handbookで確認）によれば、Navigation Blockの中身は隠し投稿タイプ `wp_navigation` として保存される。

- **自動生成は行わない（デフォルト）。** 既存サイトが独自にNavigationを組んでいる可能性を尊重し、Themeテンプレート側で強制的にNavigation Blockを追加することはしない（テーマ更新で既存サイトの見た目を勝手に変えないという02§26 Updateの原則とも整合）。
- **ユーザー明示操作による「基本メニュー生成」提案は可能。** §8のページ生成と同様、「事務所概要・取扱業務・料金・FAQ・お問い合わせへのリンクを含むNavigationを作成しますか？」という明示的なボタン操作を用意し、**既存の`wp_navigation`投稿が1件も無い場合にのみ**提示する（§7の既存サイト保護方針と一致）。生成されるリンク先は§8の判定結果（Archiveがあればそちらへ、無ければ生成した固定ページへ）を再利用する。
- 実装自体はWordPress標準の投稿作成API（`wp_insert_post` で `wp_navigation` タイプ）で完結し、新しいDB Tableや外部ライブラリを必要としない。

---

## 10. Sample / Demo Data の必要性 — 明確に非推奨

- **一括のサンプルコンテンツ投入（架空の事務所名・架空の専門家・架空の料金表等）は行わない。** 理由は本調査の指示どおり「架空データが誤って本番公開されるリスク」が現実的であるため。ASTREAは特定の海外テーマに見られる「デモデータImport」思想を明示的に採らない（Decision 016本文が既にこの思想を否定している：「海外デモデータのImportという思想ではなく」）。
- 空データ状態のUXは既にConstruction 002-006のEmpty State設計で「破綻しない」ことが保証されているため、サンプルデータで見た目を取り繕う技術的必要性もない。
- §8のページ生成において、本文が必要な箇所（事務所概要ページ等）は、架空の文章ではなく「プレースホルダーと明確に分かる案内文」に留める（例：`<!-- wp:paragraph --><p>ここに事務所の紹介文を入力してください。</p><!-- /wp:paragraph -->`）。この文言はSchema.org出力等の構造化データには一切影響しない（Construction 006のOrganization JSON-LDはOffice Profileの実データのみを参照し、固定ページ本文を参照しないため）。

---

## 11. Setup / Design System 境界

- 007は「使い始め方の案内」に責任を持ち、視覚的な完成度（配色・レイアウトテンプレートの充実・Hero Pattern等）には踏み込まない。
- **Design Systemへの申し送り事項：**
  1. 現状 `theme/templates/page.html` が存在しない。固定ページは `index.html` にフォールバックしている。事務所概要・料金・お問い合わせ用の固定ページを美しく見せるには、将来的に `page.html` 専用テンプレート、または各用途向けのPage Pattern（Hero、区切りセクション等）が必要になる。007では手を付けず、Design System側の課題として記録する。
  2. §8で生成する固定ページは、現時点では既存Pattern（`price-list.php`, `contact-form.php`）をそのまま埋め込むだけの最小構成となる。より洗練された「事務所概要ページ用Pattern」等はDesign Systemの領域とする。
  3. Navigation Block自体の見た目（ハンバーガーメニュー、オーバーレイ等）はDesign Systemが決定する。WP 7.0で導入された `core/navigation-overlay-close` 等の新しいブロック（§16参照）を採用するかどうかもDesign System側の判断とする。

---

## 12. Setup / SEO 境界（Construction 006参照）

- SEOに関して、Setupチェックリストに表示するのは「OGP画像（サイト全体のフォールバック画像）」の設定状況のみとする。Search Console verificationは**必須条件にも「推奨」チェック項目にも含めない**（本調査指示の明示どおり、非本質的な項目をSetup完了の条件にしない）。SEO専用の「SEO」画面へのリンクを1本、チェックリストの「任意」枠に置くだけで十分である。
- Organization/Person/BreadcrumbList JSON-LD（Construction 006実装済み）はOffice Profile・Professional Profileの実データから自動生成されるため、Setup側で何かを追加設定する必要はない。事務所名を入力した時点で自動的にOrganization JSON-LDが出力され始める（既存動作）。

---

## 13. Setup / Contact 境界（Construction 005参照）

- Contact Formの「保存」機能自体はCore有効化のみで動作する（設定不要）。Setupチェックリストが扱うべきなのは以下の2点のみ：
  1. Contact Formを埋め込んだ固定ページが到達可能な状態にあるか（§6の判定方法）。
  2. 通知先メールアドレスが確認済みかどうか（未確認のままだと事務所側が新着問い合わせに気付けない実務リスクがあるため「推奨」項目とする）。
- 「未確認のまま公開する」ことをブロックはしない（Decision 016の「全項目完了を公開条件にしない」と一致）。ただし、通知未確認の状態でContact Formページを公開しようとしていることが分かった場合、Setupチェックリスト上で視覚的に目立たせる（例：他の「任意」項目より優先度の高い表示色・アイコン）程度の配慮は行う。強制ブロックや確認ダイアログは行わない。

---

## 14. Security / Privacy（Setup UI）

- 新設するUI（チェックリスト、Core推奨Notice、ページ/Navigation一括生成ボタン）はいずれも管理画面限定・`manage_options` 相当のCapability チェックを要求する（既存のOffice Profile/SEO admin画面と同じ方針）。
- 一括生成ボタンの実行は `admin-post.php` 経由 + Nonce検証（既存のInquiry/SEO admin実装と同一パターンを再利用）。
- 出力するリンクURL（各サブメニューへのリンク、生成したページの編集リンク等）は全て `admin_url()` / `get_edit_post_link()` 等のWordPress標準APIから構築し、`$_GET`/`$_POST` 由来の値をURLとして直接出力しない（Open Redirect対策）。
- チェックリストが表示する状態情報（件数・確認状態等）は全て `esc_html()` で出力する。
- **Telemetryは一切実装しない。** 本調査の明示指示どおり、ASTREA FREE v1はSetup進捗・利用状況・機能利用有無等のいかなる情報も外部（Project-if含む）へ送信しない。将来そのような機能を検討する場合は、ユーザーの明示的な同意（デフォルトOFFのOpt-in）を要する別Decisionが必要であり、007はその前提を一切作らない。

---

## 15. Accessibility（Setup / Checklist UI）

- チェックリストは `<ul>`/`<li>` 等のSemantic HTMLで構成し、完了/推奨/任意の状態は色だけで示さない（アイコン+テキストラベルの併記、例：「✓ 完了」「推奨」「任意」）。
- 見出し階層（`<h2>`/`<h3>`）を適切に用い、Landmarkとして管理画面の既存構造（`.wrap`等）に自然に統合する。
- Core推奨NoticeはWordPress標準の `notice` クラス構造（既に `office-profile-admin.php` の既存Notice実装で確立済みのパターン）をそのまま踏襲し、Screen Reader向けの状態通知（`role="status"` 等、WordPress標準Notice構造が既に提供する範囲）を壊さない。
- D方式（既存トップページ拡張）を採用したことにより、複数画面間のFocus管理・Step復帰といった複雑なAccessibility要件（Wizard方式Aで懸念された論点）はそもそも発生しない。

---

## 16. Web調査（一次情報・日付付き）

| 項目 | 出典 | 確認日 | 要点 |
|---|---|---|---|
| Plugin有効化Hookからの案内表示 | [admin_notices – Hook - WordPress Developer Resources](https://developer.wordpress.org/reference/hooks/admin_notices/) / [register_activation_hook() – Function](https://developer.wordpress.org/reference/functions/register_activation_hook/) | 2026-08-27 | activation hook直後に`admin_notices`を直接発火させず、Transient経由で表示するのが定石（リダイレクトに巻き込まれるため）。他Pluginの自動有効化は行わず、明確なNoticeで案内するのがベストプラクティス |
| Navigation Block / `wp_navigation` 投稿タイプ | [Navigation – Block Editor Handbook](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/core-block-navigation/)（2026-07-17更新確認）/ [Site Editor navigation – Documentation](https://wordpress.org/documentation/article/site-editor-navigation/) | 2026-08-27 | NavigationはWP 5.9以降、隠し投稿タイプ`wp_navigation`としてBlock Markupを保持。WP 6.3でサイトエディタから直接編集可能に。WP 7.0でモバイルオーバーレイが`core/navigation-overlay-close`を含む通常のBlock Patternとして編集可能になった（Design System申し送り事項として記録） |
| Dismissible Admin Noticeの永続化 | [admin_notices – Hook](https://developer.wordpress.org/reference/hooks/admin_notices/) / WP 6.4 "Introducing admin notice functions" (Make WordPress Core, 2023-10-16) | 2026-08-27 | `is-dismissible`クラスは画面リロードで復活するため、永続化には`update_user_meta()`によるユーザー単位フラグが標準パターン |
| Plugin依存関係の表明 | `Requires Plugins`ヘッダー（WP 6.5, 2024-04導入） | 2026-08-27 | 他Plugin依存を宣言する標準ヘッダーだが、**必須依存を強制（活性化ブロック）する用途**であり、「任意だが推奨」というASTREA Coreの位置付け（Decision 021）には不適合。採用を見送り、独自の非強制Admin Noticeとする根拠として記録 |

WordPressバージョンの前提：本調査は2026年8月時点の最新安定版（WP 7.x系列、Navigation Overlay機能を含む）を前提として実施した。旧Classic Theme時代の知見（`wp_nav_menu()`のウィジェット的Menu管理等）はASTREAがBlock Theme専業である以上、意図的に参照していない。

---

## 17. Test Strategy（実装フェーズ向け設計。今回は実装しない）

実装される機能の範囲に対応してのみテストを設計する（存在しない機能へのテストは作らない）。

- **チェックリスト状態判定：** 各項目（Core活性/Office Profile/Service件数/Price件数/FAQ件数/Contact到達可能性/通知確認/OGP画像/Navigation有無）が、実データの有無に応じて正しく完了/未完了を返すことをPHPUnitで検証。
- **Core非活性時：** チェックリストUI自体がFatalを起こさず、Core関連項目が適切に「Core推奨」表示へフォールバックすることを確認（既存のCore非活性テストパターンを踏襲）。
- **Core未導入Notice：** 表示条件（Theme活性+Core非活性時のみ）とDismiss後の非再表示（usermeta確認）をPHPUnit + 実HTTPで検証。
- **ページ一括生成（実装される場合）：** (a) 初回生成が正しいPatternを埋め込んだPageを作成すること、(b) 再実行しても重複生成しないこと（Decision 016要件の直接検証）、(c) 生成後にユーザーがページを削除した場合、再生成が可能になること、(d) 生成ページがCore Uninstallで削除されないこと（Decision 019との整合）、を実HTTP（wp-env）で検証。
- **Navigation生成（実装される場合）：** 既存Navigationが存在する場合は提案自体を表示しないこと、生成後のリンク先URLが正しいこと（Archive優先・無ければ生成ページ）を検証。
- **Accessibility：** チェックリストの状態表示が色のみに依存しないこと（テキストラベルの存在をDOM検証）、見出し階層の妥当性。
- **Regression：** Construction 001-006の既存smoke-test（Part 1-7）が全て継続して緑であることを実装フェーズの必須条件とする。

---

## 18. CONSTRUCTION ORDER 007 実装提案（次工程への提案。今回は未実装）

1. **管理画面：** 新規ページを作らず、既存「ASTREA」トップページ（Office Profile画面）の冒頭に「セットアップ状況」セクションを追加する（§5 D方式）。
2. **チェックリスト内容：** §6の10項目を、完了/推奨/任意のラベル付きリストとして表示。各項目は対応する既存サブメニュー（専門家プロフィール/取扱業務/料金/FAQ/問い合わせ/SEO）または将来のPage/Navigation生成ボタンへリンクする。
3. **状態判定：** §6の実データ判定方式をそのまま採用。新規オプション・DBテーブルは作らない。
4. **Core案内：** §4のTransient+Admin Notice+usermeta Dismiss方式を実装する。
5. **機能への導線：** 既存の各サブメニューURLを`admin_url('...')`で構築し、チェックリストの各行からリンクする。
6. **ページ生成：** §8で提案した「事務所概要・料金・お問い合わせ」の3ページ生成機能を実装するかどうかは、**§10の要確認事項（Decision 016例示リストとCPT Archiveの重複整理）の回答を得てから**判断する（本調査では設計のみ提示し、実装着手はブロックしない範囲で提案に留める）。
7. **Navigation生成：** §9の「既存Navigation0件時のみ提案」方式を、ページ生成機能と同時に実装する（依存関係があるため）。
8. **Sample Data：** 実装しない（§10）。
9. **Setup完了の定義：** 単一の「完了」状態は定義しない。各項目が独立して完了/推奨/任意を保持するのみとし、02§22「全部埋めないと完成でないというUXにはしない」を実装レベルで徹底する。
10. **再表示/Dismiss：** チェックリスト自体は常時閲覧可能（Decision 016「何度でも再度開ける」）とし、個別のDismiss機構は持たない（実データに基づき自然に「完了」表示へ遷移するため、Dismiss概念自体が不要）。Core推奨Noticeのみ、usermeta Dismissを持つ。
11. **Security/Accessibility/Migration/Uninstall：** §14/§15の方針をそのまま実装要件とする。ページ生成機能を実装する場合、生成ページIDを記録するオプションはCore Uninstallの対象に含める一方、生成された固定ページ自体はUninstall対象に含めない（Decision 016/019）。

---

## 19. まとめ表（報告用サマリの根拠）

- 推奨Setup Architecture：D（既存トップページ拡張型チェックリスト）+ Core非活性時のみの軽量Admin Notice。独立Wizardは不採用。
- 最小公開ステップ：システム上の必須は0件（Empty State保証により技術的にはTheme有効化のみで公開可能）。実務上の推奨最小ラインは「事務所名」「Service最低1件」「到達可能なContact Form」の3点。
- Core案内：Decision 021が既に許可済み。新規Decision不要。Transient+usermeta Dismissパターンで実装。
- Setup状態判定：新規DB/オプション不要、既存の公開APIから都度算出。
- Page/Navigation生成：Decision 016は既に許可済みだが、**対象ページの具体的なリストがCPT Archive導入後の現状と食い違っている**ため、§10の要確認事項として次工程着手前の確認を推奨。
- Sample Data：明確に不採用。

---

## 20. 発見した要確認事項（着工を阻害するものではないが、実装着手前に確認を推奨）

1. **Decision 016のページ生成対象リストの更新要否：** Decision 016策定時（2026-08-25）の例示「ホーム・事務所概要・取扱業務・料金・FAQ・問い合わせ」は、その後のConstruction 003/004でService・FAQ・ProfessionalがCPT Archive URLを獲得したことで一部重複している。本調査は「取扱業務・FAQは既存Archiveがあるため固定ページ生成は不要、事務所概要・料金・お問い合わせのみ生成する」という具体化案を提示したが、これはDecision 016本文の文言そのものを変更するかどうかに関わるため、正式な着工前確認を推奨する（新規Decisionの追加ではなく、既存Decision 016への実装解釈の確定として処理できると考えられるが、判断は着工権者に委ねる）。
2. **Office Profile画面のチェックリスト表示位置の細部：** 02§22は「営業時間」を「事務所情報」と並列の入口として明示しているが、現在の実装ではOffice Profile単一画面の中に統合されている。チェックリスト上で2行に分けて表示するか、1行にまとめるかは実装時の些末な判断で足りると考えられるが、念のため記録する。

いずれも本調査の9つの停止条件（既存Decisionとの矛盾／Core必須化／FREE-PRO境界変更／既存ユーザーデータの自動改変／新規DBテーブル／外部サービス／Telemetry／Design System仕様確定の必要／重大Security-Privacy判断）には該当しないと判断し、RESEARCH COMPLETEとして報告する。

---

## 21. 着工承認・要確認事項の解決（2026-08-27追記）

CONSTRUCTION 007 作戦承認を受け、§20-1の要確認事項について着工前確認を実施した。ユーザーは本調査の推奨案（重複を避けた3ページのみ生成）を選択した。この判断を**Decision 027**として正式に記録した（`docs/specifications/04_astrea_free_v1_preconstruction_decisions.md`）。§20-2（営業時間チェックリスト表示位置）は実装時の些末な判断として、事務所情報と統合した1項目として扱うことにした（新規Decision不要）。

## 22. 実装報告（2026-08-27追記）

§18の実装提案どおり、以下を実装した。

**追加ファイル（Core）:**
- `core/includes/setup-checklist.php` — チェックリスト状態判定（既存データAPIからの動的算出、進捗DB無し）。
- `core/includes/setup-pages.php` — 事務所概要/料金/お問い合わせページの一括生成（Decision 016/027準拠、下書き作成、重複防止、上書き無し）。
- `core/includes/setup-navigation.php` — 基本メニュー（`wp_navigation`）生成（既存Navigationが無い場合のみ）。
- `core/includes/setup-admin.php` — 上記のチェックリストUIと2つの生成ボタンを、既存の「ASTREA」Office Profile画面へ`astrea_core_office_profile_page_top`アクション経由で追加描画。

**変更ファイル（Core）:**
- `core/includes/office-profile-admin.php` — チェックリスト差し込み用のアクションフックを1箇所追加。
- `core/astrea-core.php` — 新規4ファイルのrequire追加。Version 0.6.0 → 0.7.0。
- `core/uninstall.php` — `astrea_core_generated_pages`オプションをCore所有データとして明記（削除しない）。

**変更ファイル（Theme）:**
- `theme/functions.php` — Core未導入時の推奨Notice（Decision 021が既に許可済み）。Transient不要でuser meta Dismissのみのシンプルな実装とした（activation hookからの直接表示ではなく`admin_notices`自体で毎回`is_core_active()`を判定するため、当初検討したTransient経由の設計は不要と判断——Pluginのアクティベーション直後のリダイレクト問題を回避する目的のTransientは、ASTREA Coreというサイト全体の状態を毎回判定する常設Noticeには当てはまらないため）。

**Security/Accessibility：** 全アクション（ページ生成・メニュー生成・Notice Dismiss）は`current_user_can()`+Nonce（`check_admin_referer`）で保護。出力は`esc_html()`/`esc_url()`/`esc_attr()`で徹底。チェックリストは完了/推奨/任意をテキストラベル併記（色のみに依存しない）。

**Core非活性時の挙動：** チェックリストはCore画面自体の一部のため、Core非活性時は表示されない（Core画面自体が存在しないため、既存のCore非活性時の一般的挙動と同じ）。Theme側のCore推奨Noticeは、Core非活性時にのみ表示され、Fatal/Warning無しで動作することを実HTTPで確認した。

**Test/CI結果：**
- 新規PHPUnit：`tests/SetupTest.php`（18 tests / 44 assertions）追加。
- Regression含む全体：205 tests / 340 assertions、PASS。
- PHPCS：新規・変更ファイル、および`core/`/`theme/`全体で0エラー。
- 実HTTP smoke-test（`tools/ci/smoke-test.sh`）：Part 8（BD-BL）を新規追加し、チェックリスト描画・ページ生成の冪等性・Navigation生成のガード・Core推奨Notice表示/Dismiss・Core非活性化/再有効化時のFatal無しを実機確認。Part 1-7の既存Regressionも含め全項目PASS（2回目の実行で成功。1回目は`npx wp-env run cli`のETIMEDOUT——本セッションで既知のサンドボックス側ネットワーク不安定性——により中断、製品コードの不具合ではない）。

**発見した追加の要確認事項：** なし。§20で提起した2件のうち、§20-1はDecision 027で解決済み、§20-2は実装レベルの軽微な判断として処理した。
