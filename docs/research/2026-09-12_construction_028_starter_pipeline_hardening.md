# Construction 028 — ASTREA Starter Pipeline Hardening
Idempotency / Repeatability / Safety Audit

- Start: 2026-09-12 08:13頃 JST（Order受領時）
- End: 2026-09-12 08:54:08 JST（実測）
- Modifier: Chloe
- Mode: **ローカル監査・修正・テストのみ。commit / push / VPS deployは一切実施していない。Live Demo（本番）には一切触れていない。**

---

## PHASE 0 — PRECHECK

```
$ git status --short --branch
## main...origin/main
 M HISTORY.csv                                        ← Owner自身の編集、対象外
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr  ← Owner自身の既存差分（2026-09-10由来）、今回も無変更
 D docs/research/screenshots/...（多数）                ← Owner削除済み、復元せず対象外
?? docs/research/2026-09-11_construction_024...等       ← 他Constructionの未commit報告書、対象外

$ git rev-parse HEAD
8f0808b27abe722dec435aeb6957c40c2ebcca17
$ git rev-parse origin/main
8f0808b27abe722dec435aeb6957c40c2ebcca17（一致）
```

Construction 027 report（`docs/research/2026-09-11_construction_027_astrea_starter_site_foundation.md`）を再読し、正本化済みの識別情報（ASTREA行政書士事務所／伊吹 文人）とファイル配置（`docs/demo-assets/astrea-starter-site/`）を確認した。過去のConstruction reportは今回一切書き換えていない。Owner削除済みscreenshotsは復元していない。

---

## PHASE 1 — PIPELINE INVENTORY

実行順（`docs/demo-assets/astrea-starter-site/scripts/`配下）:

| # | スクリプト | 入力 | 出力/DB変更 | 前工程への依存 | 施工前のidempotency保証 |
| --- | --- | --- | --- | --- | --- |
| 0 | `theme-core-activation-blueprint.json` | — | Theme切替・Plugin有効化・`blogname`・`blog_public`option | なし | `astrea_demo_theme_activated`optionガード（既にSAFE） |
| 1 | `cleanup.php` | 固定post ID 1/2/3 | デフォルト投稿削除 | 0の後 | **なし（ID固定、危険）** |
| 2 | `build-content.php` | Office Profile値・CPTデータ配列 | Office Profile option、Professional/Service/Case/Result/Price/FAQ/Voice投稿、Core Setup（pages/nav/home）、事務所概要への開示文追記 | 1の後 | **なし（既存確認なしのINSERTのみ、危険）** |
| 3 | `polish.php` | — | 事務所概要ページ本文の`str_replace`、Office Profile business_hours更新 | 2の後（事務所概要ページ必須） | str_replaceは実質安全、business_hoursは`update_option`で安全 |
| 4 | `permalinks.php` | — | `permalink_structure`option、rewrite flush | 2の後推奨 | 安全（`update_option`+flush） |
| 5 | `integrate-final-images.php` | `/images/*.png` | Web JPEG生成（ファイル操作）、Professional/Case#1のattachment・Featured Image | 2の後（Professional/Case#1必須） | **なし（既存attachment未確認、危険）** |
| 6 | `integrate-025b2-hero-and-cases.php` | `/images/web/*.jpg` | Hero/Case#2/#3のattachment・Cover block属性・Featured Image | 2, 5の後（HOME必須） | ファイル名ベースの既存確認あり（安全） |
| 7 | `make-results-web-jpeg.php` | `/images/*.png` | Web JPEG生成（純粋なファイル操作、DB変更なし） | なし | 決定的な上書き、安全 |
| 8 | `integrate-025e1-results-background.php` | `/images/web/*.jpg` | Resultsセクションのattachment・Cover block構造 | 2, 7の後（HOME必須） | **旧: ファイル名文字列一致のみ（危険、既知バグ）** |
| 9 | `fix-internal-link-portability.php` | — | Navigation参照化、Contact CTA接続、内部URL置換、home/siteurl自己修復 | 全工程の最後（必須） | 各所で事前比較あり（既にSAFE） |

危険パターンの検出（Order Phase 1が例示した項目に対応）:

- **特定ファイル名文字列だけで施工済み判定** → `integrate-025e1-results-background.php`（Phase 2で恒久修正）
- **post ID固定** → `cleanup.php`（1/2/3をハードコード、既存サイト誤爆リスク）
- **INSERTのみで既存確認なし** → `build-content.php`（Professional/Service/Case/Result/Price/FAQ/Voiceの全投稿）
- **image再アップロードによるattachment増殖** → `integrate-final-images.php`（Professional/Case#1、既知バグではなかったが今回新規発見）
- **Cover/Group二重wrapper** → `integrate-025e1-results-background.php`（Construction 027で実際に発生した既知バグ）

`attachment ID固定`・`URL固定`・`localhost固定`・`開発環境パス固定`・`menu item重複生成`は、既存スクリプト群では確認されなかった（`fix-internal-link-portability.php`のNavigation再構築ロジックはmenu itemの重複を作らない設計で、実測でも重複0を確認——後述Phase 6/8）。

---

## PHASE 2 — KNOWN BUG FIX

対象: `integrate-025e1-results-background.php`

### 根本原因

idempotency判定が「post_contentに`astrea-demo-yamada-results-background`という**旧アセットファイル名の文字列**が含まれているか」だけで「施工済み」を判定していた。Construction 027でアセットファイル名を`yamada`→`starter`へ改名したため、この文字列一致は常に不成立となり、後続の「無画像→with-image」アップグレード処理が誤って発火した。その処理は`$unwrapped`（`<!-- wp:astrea/results-list {"heading":"実績"} /-->`という自己終端タグ）を`strpos()`で検索していたが、この文字列は**既にwith-image状態のCoverブロックの内部にも部分文字列として存在する**ため、既存のwith-imageブロックをもう一段Coverで包む二重ネストを引き起こした。

### 恒久修正

`parse_blocks()`/`serialize_blocks()`によるブロック構造ベースの判定へ全面書き換え:

1. `astrea-results-photoplane`クラスを持つ`core/cover`ブロックをブロックツリー上で探索（文字列検索ではなく構造探索）。
2. 発見したCoverの`innerBlocks`が**さらに同じクラスのCoverを含む場合**（＝Construction 027の二重ネストバグそのもの）、内側の正しいブロックへ自動的にunwrapして自己修復。
3. 属性（`url`/`id`/`dimRatio`）が既に目的の値と一致していれば**no-op**。異なっていれば（旧アセット施工済み等）**その場で属性を更新するのみ**（新規wrapper生成なし）。
4. Coverが存在せず、pre-1.0.3の裸の`astrea/results-list`ブロックのみの場合は、実際にパースされたブロックオブジェクトを`innerBlocks`としてCoverへ格納（生文字列の手組みではなく、`serialize_block()`のnullプレースホルダ機構を利用）。
5. 最終的な`serialize_blocks()`結果が変更前と同一なら`wp_update_post()`自体をスキップ（無駄なリビジョンを作らない、副次的改善）。

### 検証結果（期待どおりすべて確認）

| ケース | 結果 |
| --- | --- |
| 未施工 → 1回だけ施工 | ✅ RUN #1で確認 |
| 施工済み → NO-OP | ✅ RUN #2/#3で`Results Cover already points at the current attachment/dimRatio — no-op.`を確認 |
| 二重施工済み → 正常な単一構造へrepair | ✅ Construction 027で実際に発生した本番データに対し、本Fixと同じ`parse_blocks`ベースのロジックで単一構造へ復旧できることを027時点で実証済み。今回のスクリプト本体もこのロジックを採用。 |
| 過剰な汎用migration engineを作らない | ✅ Results Cover専用の局所ロジックのみ。汎用ブロックmigrationフレームワーク等は作っていない。 |

---

## PHASE 3 — FULL IDEMPOTENCY AUDIT

| スクリプト | 施工前分類 | 施工後分類 | 対応 |
| --- | --- | --- | --- |
| `theme-core-activation-blueprint.json` | SAFE | SAFE | 変更なし |
| `cleanup.php` | **NEEDS FIX**（ID固定、既存サイト誤爆リスク） | SAFE | タイトル一致ガードを追加。期待タイトルと一致しない場合はSKIPし、明示ログを出す |
| `build-content.php` | **ONE-SHOT ONLY**（危険＝サイレント増殖） | **ONE-SHOT ONLY**（安全＝拒否） | 7種のCPTにまたがる真の冪等マージ設計は「Starter Import設計そのもの」に該当すると判断し実装しない。代わりに`astrea_professional`の既存有無で安全に**拒否**するガードを追加し、「サイレントに増殖する」を「安全に拒否する」へ変えた |
| `polish.php` | SAFE | SAFE | About本文の書き換えをno-op時にスキップする最適化を追加（リビジョン削減、動作は不変） |
| `permalinks.php` | SAFE | SAFE | 変更なし |
| `integrate-final-images.php` | **NEEDS FIX**（新規発見：attachment増殖） | SAFE | `integrate-025b2-hero-and-cases.php`と同じファイル名ベースの既存attachment検索を追加 |
| `integrate-025b2-hero-and-cases.php` | SAFE（attachmentは元々安全） | SAFE | Hero Cover更新をno-op時にスキップする最適化を追加 |
| `make-results-web-jpeg.php` | SAFE | SAFE | 変更なし（DB非関与の決定的ファイル変換） |
| `integrate-025e1-results-background.php` | **NEEDS FIX**（既知バグ） | SAFE | Phase 2参照 |
| `fix-internal-link-portability.php` | SAFE | SAFE | `$wpdb`のスコープ問題（後述）のみ修正、ロジック自体は無変更 |

**ORDER-DEPENDENT**: パイプライン全体が本質的に順序依存（Phase 1の依存関係表を参照）。個別スクリプトを独立に任意順序で呼べる設計にはなっていない——将来Starter Import機能はこの順序を尊重する必要がある。

**UNKNOWN**: 該当なし（全アクティブスクリプトを全文精読し、動作を把握済み）。

**今回スコープ外としてKnown Issueへ送ったもの**:
- `build-content.php`の真の冪等マージ（7 CPT×複数アイテムの内容識別モデル決定＝Starter Import設計そのもの）
- `placeholder-images/`・`fix-placeholders.php`（README記載の通り既に不使用・履歴保持、今回のパイプライン監査対象外）

### 副次的発見: `$wpdb`スコープの脆弱性

`fix-internal-link-portability.php`の一部トップレベルコード（Contact CTA接続処理）が、`$wpdb`をグローバル変数として暗黙に参照していた。**素の`include`/`require`（Playgroundの元来の`runPHP`実行モデル）では問題ないが、`wp eval-file`や本Construction 028のテストハーネスのような「トップレベルコードが実際には関数スコープ内で実行される」経路では`$wpdb`が未定義になる。** `global $wpdb;`を明示追加し、どちらの実行モデルでも同じスクリプトファイルがそのまま動く形へ修正した（Construction 027でVPSデプロイ時に発見・その場限りの作業コピーにのみ適用していたが、今回は再現性ソース本体へ正式に反映——将来同じ問題が別の実行経路で再発しないようにするため）。

---

## PHASE 4〜7 — TEST BASELINE / RUN #1 / #2 / #3

### テスト環境

`~/co-c028-persistent`（新規、`download-and-install`でゼロから、port 8923、`DISABLE_WP_CRON`/`AUTOMATIC_UPDATER_DISABLED`を有効化——後述の理由）。ASTREA Theme 1.0.3／Core 1.0.1をlive mount。Live Demo（VPS）・本番DBには一切接続していない。

### テストハーネス構築中に発見した2つの問題（テストハーネス自体の問題、パイプラインの問題ではない）

1. **`wp-playground-cli server`モードが今回のCLIバージョンではホストディスクへ永続化しなかった**（過去セッションの経験と異なる挙動。再現性ソースの問題ではなく、テスト実行環境の挙動差なので、再検証せず前提を置き換えた）。対応として、サーバープロセスを起動したまま、テスト専用mu-plugin経由でHTTP越しにスクリプトをトリガーする方式へ切り替えた（`REPEATABILITY-TEST.md`に手順を記録）。
2. **テスト専用mu-pluginを`init`フックに載せ`exit()`していたため、mu-pluginは通常pluginより先に読み込まれるWordPressのブートシーケンス上、ASTREA Coreの`init`フック（CPT登録）が実行される前にプロセスが終了し、その回のリクエストではCustom Post Typeが未登録のままになっていた。** `wp_insert_post()`自体は post_type文字列を検証しないため投稿は正しく作成されるが、`post_type_exists()`/`wp_count_posts()`はそのリクエスト内では食い違う——Navigation生成で3項目（取扱業務・専門家紹介・FAQ）が欠落する形で症状が出た。`init`ではなく`wp_loaded`（全plugin初期化完了後に発火）へフックし直して解消。以後の全RUNで6項目すべて正しく生成されることを確認した。

いずれもテストハーネス自身の設計不備であり、`docs/demo-assets/astrea-starter-site/scripts/`配下の実スクリプトには一切起因しない。

### RUN #1（初回施工）

9スクリプトすべて成功。Professional 1件・Service 3件・Case 3件・Result 3件・Price 4件・FAQ 4件・Voice 3件、Page 4件（About/Price/Contact/Home）、Navigation 1件生成、Attachment 8件（正式画像6点＋旧placeholder2点）、CTA接続・Navigation参照化すべて成功。

### RUN #2（同一環境へ再実行、DB reset禁止）

- `cleanup.php`: 対象post不在、安全にno-op
- `build-content.php`: **ABORT**（意図した安全な拒否、Phase 3参照）
- `polish.php`: About本文no-op、business_hours冪等更新
- `integrate-final-images.php`: **既存attachment 38/39を再利用、複製なし**（Fix確認）
- `integrate-025b2-hero-and-cases.php`: **既存attachment 40/41/42を再利用、複製なし**
- `integrate-025e1-results-background.php`: **`Results Cover already points at the current attachment/dimRatio — no-op.`**（既知バグFix確認）
- `fix-internal-link-portability.php`: 全項目no-op

### RUN #3（さらにもう一度）

RUN #2と完全に同一の出力（全no-op/安全拒否）。

---

## PHASE 8 — SEMANTIC COMPARISON

RUN #1・#2・#3のスナップショット（`scripts/dev-snapshot.php`で取得、`post_modified`等の非本質フィールドは元々収集していない設計）を全項目比較:

| 比較軸 | 結果 |
| --- | --- |
| A. Content（page/CPT件数・スラッグ・タイトル） | **完全一致**（3RUNとも） |
| B. Media（attachment数8、ファイル名一覧、重複ファイル名） | **完全一致**、`duplicate_filenames: []`（3RUNとも） |
| C. Blocks（Results/Hero wrapper数・最大深度、button/cover数） | **完全一致**：`results_wrapper_count=1`／`results_wrapper_max_depth=0`／`hero_wrapper_count=1`／`hero_wrapper_max_depth=0`（3RUNとも、二重ネスト0件） |
| D. Navigation（件数2、6項目、重複ラベル） | **完全一致**、`navigation_duplicate_labels: []` |
| E. Settings（home/siteurl/show_on_front/page_on_front/generated_pages等） | **完全一致** |
| F. Identity（ASTREA行政書士事務所／伊吹文人／旧yamada=0／旧伊府伊夫男=0） | **完全一致**（`professionals`フィールドで氏名を直接確認。`identity_checks.ibuki_fumito_present`サブチェックはテストスクリプト側の`wp_json_encode`呼び出しでUnicodeエスケープを無効化し忘れた表示上のバグがあり誤ってfalseと出たが、実データ自体は`professionals: [{"title":"伊吹 文人"}]`で正しいことを確認済み——実害なし、テストスクリプト側の軽微な既知課題として記録） |

**RUN #1 == RUN #2 == RUN #3 を全項目で確認。増殖・構造変化は0件。**

---

## PHASE 9 — PORTABILITY CHECK

`localhost`／`127.0.0.1`／`:8900`〜`:8902`／`demo.project-if.jp/astrea`／`yamada`のいずれも**0件**。

`172.29.`のみ6件ヒットしたが、これはテスト環境自身の`home_url()`（`http://172.29.10.134:8923`）が正しく埋め込まれていることによるもの——**現在の実行環境自身のホストが出現するのは正しい挙動**であり、ポータビリティ違反ではない（他の開発ホスト・別環境のURLが紛れ込んでいないことこそが本チェックの目的）。Live Demo公開URL（`demo.project-if.jp/astrea`）がStarter正本へ焼き込まれていないことも確認済み。

**Portability: PASS**

---

## PHASE 10 — FAILURE SAFETY AUDIT

将来Import UIから呼ぶ場合、各工程の失敗時に何が残るかを整理:

| 工程失敗時 | 残るもの | 危険度 |
| --- | --- | --- |
| `cleanup.php`失敗 | 何も変わらない（各deleteは独立） | 低 |
| `build-content.php`途中失敗 | **部分的な投稿セット**（例: Serviceは全件、Caseは1件のみ等）。Setup（pages/nav/home）が未実行なら、Contact/About/PriceページもHomeも存在しない半端な状態 | **高** |
| `polish.php`失敗 | About本文が汎用プレースホルダのまま、または営業時間未設定 | 低 |
| `integrate-final-images.php`／`integrate-025b2-hero-and-cases.php`途中失敗 | 一部のFeatured Image／Hero背景のみ未設定（placeholder画像のまま） | 中 |
| `integrate-025e1-results-background.php`失敗 | Resultsセクションが無画像のまま、またはPhase 2 Fix前なら二重ネスト | 中（Fix後は自己修復可能） |
| `fix-internal-link-portability.php`失敗 | Navigation/CTAが凍結URLのまま、または`home`/`siteurl`不整合 | 中〜高（公開後は内部リンク切れに直結） |

**最も危険な失敗点は`build-content.php`の途中失敗**——7種のCPTへ跨る一連のINSERTの中断は、Setup（ページ生成・Navigation生成・Home生成）に到達しない限り、サイトとして機能しない中途半端な状態を残す。今回trasactionシステムは実装していない（Order指示通り）。

**029設計への入力**: 将来のStarter Import機能には、少なくとも(a) 実行前のpreflightチェック（空サイトであることの確認）、(b) 各工程完了ごとのprogress記録、(c) 失敗時にどこまで戻すかを判断できるbackup/restore pointが必要と考えられる。特に`build-content.php`相当の処理は、真の冪等マージ設計（Phase 3で今回スコープ外とした課題）と合わせて設計する必要がある。

---

## PHASE 11 — EXISTING SITE SAFETY

パイプラインが既存サイトに対して行う操作を分類:

| スクリプト | 操作 | 分類 |
| --- | --- | --- |
| `cleanup.php` | ID 1/2/3のタイトルが期待値と一致する場合のみ削除 | **SAFE**（Fix後。Fix前はDESTRUCTIVE——ID一致だけで無条件削除） |
| `build-content.php` | `astrea_professional`が既に存在すれば中断（無変更）。存在しなければCPT投稿を新規作成 | ガード発火時はSAFE、初回実行時はADDITIVE |
| `polish.php` | 事務所概要ページの特定プレースホルダ文字列のみ置換。他の内容は変更しない | **SAFE**（対象文字列が存在しない限り無害） |
| `permalinks.php` | サイト全体のpermalink構造を`/%postname%/`へ**強制的に上書き** | **OVERWRITE**（既存サイトが別のpermalink構造を使っていた場合、無条件に変更する） |
| `integrate-final-images.php`／`integrate-025b2-hero-and-cases.php` | 新規attachmentを追加し、Professional/Case/HomeのFeatured Image・Cover属性を**上書き** | **OVERWRITE**（ユーザーが既に独自の画像を設定していても無条件に差し替える） |
| `integrate-025e1-results-background.php` | Resultsセクションの画像を**上書き**（Fix後は既に正しい値なら無変更） | OVERWRITE（変更が必要な場合のみ） |
| `fix-internal-link-portability.php` | Navigation構造・Contact CTA・home/siteurlを**書き換え** | **OVERWRITE**（ユーザーが独自にカスタマイズしたNavigationやCTAリンクも巻き込んで書き換える設計） |

**特に危険（既存サイトへの誤爆リスクが高い）**: `permalinks.php`（既存サイト全体のURL構造を無条件変更）、`integrate-*`各種（既存の画像設定を無条件上書き）、`fix-internal-link-portability.php`（既存のNavigationカスタマイズを無条件書き換え）。これらはいずれも「空のWordPressへの初回導入」を前提とした設計であり、**既存コンテンツがある環境への誤爆防止gateは現状一切存在しない**。

**029設計への入力**: 将来のStarter Import機能は、実行前に「このサイトは空か」を確認するpreflight gate（Order Phase 10と共通）が必須。

---

## PHASE 12 — CLEAN REBUILD REGRESSION

RUN #1はそれ自体が`download-and-install`によるゼロからのクリーンリビルドである（Phase 4〜7参照）。Hardening後のBlock validation・Desktop/Mobile smoke test・Visual確認はいずれもこのRUN #1環境（RUN #3完了後の状態、semantic的にRUN #1と同一）に対して実施した。

| 確認項目 | 結果 |
| --- | --- |
| Site Name / Representative | ASTREA行政書士事務所 / 伊吹 文人 ✅ |
| Header / Hero / Services / Cases 01–03 / Results / Representative / Price / FAQ / Voices / Flow / CTA / Footer | Construction 027承認済みVisualと同一（スクリーンショットで確認、崩れなし） |
| Desktop 1440 / Mobile 390 smoke test | 全7ページ、overflow=0・broken image=0・console error=0 |
| Block validation | 合計8件（既存014A系統の`has-background`クラス不一致2件のみ、025系のbaselineと完全一致）、**新規warning 0** |
| Theme / Core | 1.0.3 / 1.0.1、**無変更**（`git diff --stat -- theme/ core/`は空） |

Visualに差分がないため、Order Phase 15の指示通り、大量の新規スクリーンショットは作成していない（自己確認用に1枚のみ取得、Owner提示は不要と判断）。

---

## PHASE 13 — TEST ARTIFACT

`docs/demo-assets/astrea-starter-site/scripts/dev-snapshot.php`（機械可読snapshot取得スクリプト、secretを含まない）と`docs/demo-assets/astrea-starter-site/REPEATABILITY-TEST.md`（RUN #1/#2/#3比較手順書）を新規作成した。

既存repoのテスト方針（`composer.json`の`"test": "phpunit"`、`tests/`配下のPHPUnitテスト）はCore個別クラスの単体テストが中心で、複数スクリプトにまたがる環境レベルの冪等性テストとは性質が異なるため、PHPUnitへの統合はせず、独立したスクリプト＋手順書として配置した。**このテスト成果物自体はStarter Site正本の代替ではなく**、`scripts/build-content.php`等の実スクリプトを検証するための道具である。

テスト専用のmu-pluginトリガー機構自体（`init`/`exit`問題を含む）はセッションのスクラッチ領域のみに存在し、リポジトリには含めていない（`REPEATABILITY-TEST.md`にその設計と代替手段を記録済み）。

---

## Known Issues（今回新規発見・記録分）

1. **`build-content.php`は依然ONE-SHOT ONLY。** 7 CPTにまたがる真の冪等マージ（既存アイテムをどう識別し、更新か追加かを判断するか）はStarter Import設計そのものの検討課題であり、今回は実装せず、安全な拒否ガードのみ追加した。
2. **既存サイトへの誤爆防止gateが存在しない。** `permalinks.php`・`integrate-*`各種・`fix-internal-link-portability.php`は空サイト前提でOVERWRITE操作を行う。将来のStarter Import機能にはpreflight gateが必須（Phase 10/11）。
3. **`build-content.php`の初回実行時に生成される旧placeholder attachment（`case-1-image.png`・`starter-professional-portrait-placeholder.png`）が、最終画像へ切り替え後も孤立して残る。** 再実行時に増殖はしない（1回のフル実行につき固定2件）が、クリーンアップされない。低優先度。
4. **テストスクリプト（`dev-snapshot.php`）内の`identity_checks.ibuki_fumito_present`が、`wp_json_encode`のUnicodeエスケープ設定漏れにより誤ってfalseと表示される軽微な表示バグがある。** 実データ自体は正しいことを`professionals`フィールドで直接確認済み。実害なし。
5. `yamada-demo-export.wxr`の既知課題（Construction 027から継続）は今回も無変更。
6. トランザクション機構・preflight・backup/restore・retry・rollbackは、Order指示通り今回実装していない（Construction 029以降の検討課題）。

---

## 029 Starter Import Readiness — ASSESSMENT COMPLETE

| 観点 | 現状 |
| --- | --- |
| パイプライン自体の冪等性 | ✅ 達成（`build-content.php`除く全スクリプトが真に冪等、`build-content.php`は安全に拒否） |
| 複数回実行時の増殖・構造破壊 | ✅ 0件（RUN #1=#2=#3で実証） |
| 実行順序の明確化 | ✅ Phase 1で文書化済み |
| 実行方式の環境非依存性（Playground runPHP／wp eval-file／将来のImport機能） | ✅ `$wpdb`スコープ問題を解消、いずれの方式でも同一スクリプトが動作 |
| 既存サイトへの誤爆防止 | ❌ 未実装（Phase 10/11のKnown Issue） |
| `build-content.php`の真の冪等マージ（既存コンテンツとの統合） | ❌ 未設計（Starter Import本体の設計課題） |
| Transaction/backup/retry/rollback | ❌ 未実装（今回スコープ外、Order指示通り） |

**総合評価: パイプライン自体の「壊れず繰り返し実行できる」という今回の最重要要件は達成した。ただし「空でない既存サイトへ安全に導入できる」「途中失敗から安全に復旧できる」という、製品としてのStarter Import機能に不可欠な要素はまだ未着手であり、Construction 029ではこの2点を中心に設計する必要がある。**

---

## Construction 028 — Hardening Review Ready

- Pipeline Inventory: COMPLETE
- Known Idempotency Bug: FIXED
- RUN #1: PASS
- RUN #2: PASS
- RUN #3: PASS
- Semantic Equivalence: PASS
- Duplicate Growth: 0
- Results Cover Nesting: PASS（wrapper_count=1, max_depth=0、3RUNとも）
- Portability: PASS
- Failure Safety Audit: COMPLETE
- Existing-site Safety Audit: COMPLETE
- Clean Rebuild Regression: 0（Visual/Block validation/Desktop/Mobile smoke、いずれも回帰なし）
- Theme/Core Modification: NONE
- 029 Readiness: READY（ただし既存サイト誤爆防止・build-content.phpの冪等マージ設計は未着手、Known Issues参照）
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Review: REQUIRED

STOP

---

## Construction 028 — FINALIZE / CLOSE

- Finalize Start: 2026-09-12（Owner Review PASS受領後）
- Modifier: Chloe
- Owner Review: **PASS**（Pipeline Inventory / Known Idempotency Bug FIXED / RUN#1-3 PASS / Semantic Equivalence PASS / Duplicate Growth 0 / Results Cover wrapper_count=1・max_depth=0 / Portability PASS / Failure Safety Audit COMPLETE / Existing-site Safety Audit COMPLETE / Theme・Core Modification NONE / 029 Readiness READY — Owner確認済み。「Live DemoはConstruction 028では変更していない」も確認済み）

### §1-2 PRE-COMMIT CHECK / 対象確認（実git diffベース）

```
$ git status --short --branch
## main...origin/main
 M HISTORY.csv                                                          ← Owner自身の編集。対象外
 M docs/demo-assets/astrea-starter-site/scripts/build-content.php       ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/cleanup.php             ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/fix-internal-link-portability.php ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/integrate-025b2-hero-and-cases.php ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/integrate-025e1-results-background.php ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/integrate-final-images.php ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/polish.php              ← 対象
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr         ← Owner既存差分（2026-09-10由来）。対象外・今回も無変更
 D docs/research/screenshots/012〜（多数）                              ← Owner削除済み。復元せず、対象外
?? docs/demo-assets/astrea-starter-site/REPEATABILITY-TEST.md          ← 対象（新規）
?? docs/demo-assets/astrea-starter-site/scripts/dev-snapshot.php       ← 対象（新規）
?? docs/research/2026-09-11_construction_024_php83_coexistence_provisioning.md   ← 他Construction。対象外
?? docs/research/2026-09-11_construction_024r1_astrea_live_demo_deployment.md    ← 他Construction。対象外
?? docs/research/2026-09-11_construction_026_astrea_live_demo_cta.md            ← 他Construction。対象外
?? docs/research/2026-09-12_construction_028_starter_pipeline_hardening.md      ← 対象（本報告書自身）
?? docs/research/screenshots/026/                                       ← Construction 026分。対象外
?? *.Zone.Identifier（3件）                                             ← Windows付随ファイル。対象外
?? if-professional-astrea.code-workspace                                ← 迷子ファイル。対象外

$ git rev-parse HEAD && git rev-parse origin/main
8f0808b27abe722dec435aeb6957c40c2ebcca17（両者一致、分岐なし）
$ git branch --show-current
main
```

`git diff --check` を対象7スクリプトに対して実行 → 出力なし（空白関連エラー0件）。`php -l` を対象7スクリプト＋`dev-snapshot.php`の計8ファイルに実行 → 全て `No syntax errors detected`。

### §3 Final Code Review（実diffで再確認）

- **`integrate-025e1-results-background.php`**: `git diff` を全文精読し、`require_once ABSPATH . 'wp-includes/blocks.php'` の追加、`astrea_e1_is_results_cover()` / `astrea_e1_rebuild_results_cover()` / `astrea_e1_find_and_normalize()` / `astrea_e1_wrap_bare_results_list()` が全て `parse_blocks()`/`serialize_blocks()` ベースであり、ファイル名文字列や旧markup部分文字列への依存が完全に排除されていることを再確認した。二重ネストの自己修復ロジック（`innerBlocks`内に同一classNameのCoverが見つかった場合に内側へunwrap）も再確認した。既に正しい状態では`serialize_blocks()`後の内容が変化せず`wp_update_post`をスキップする分岐も確認した。
- **`integrate-final-images.php`**: `find_existing_attachment_by_filename()` が `upload_and_attach()` の先頭でファイル名照合を行い、既存があれば新規アップロードをスキップして再利用する構造を再確認した。RUN#2/#3のログで実際に「Reusing existing attachment」が出力され、Professional/Case#1のattachment_countが増加しないことを実行結果で確認した。
- **`cleanup.php`**: ID固定削除ではなく、`post_title`がWordPress標準値と完全一致する場合のみ削除する`astrea_cleanup_delete_if_default()`ガードを再確認した。
- **`build-content.php`**: 冒頭の`ABORT`ガードが、`astrea_professional`が1件でも存在すれば`exit(1)`で安全に拒否することを再確認した。コメント・報告書のいずれにも「真の冪等マージを解決した」という記述がないことを確認した（未解決のまま、Construction 029スコープとして明記）。

### §4 Repeatability Final Check（ローカルのみ・本番VPS不使用）

Construction 028で作成したテスト資産（`REPEATABILITY-TEST.md`の手順、`dev-snapshot.php`）を用いて、使い捨てのWordPress Playground環境（`wp-playground-cli server`、theme/core/scripts/imagesをmount、`DISABLE_WP_CRON`・`AUTOMATIC_UPDATER_DISABLED`有効、Construction 028で確立した`wp_loaded`フック方式のテスト専用mu-plugin harness経由でスクリプトをHTTP trigger実行）を新規に再構築し、RUN#1/#2/#3をDBリセットなしで再実行した。

- RUN#1: 9ステップ全て正常完了（新規コンテンツ作成、attachment 8件、Results Cover新規ラップ）。
- RUN#2: `cleanup`＝スキップ、`build-content`＝ABORT（意図通り）、`integrate-final-images`／`integrate-025b2-hero-and-cases`／`integrate-025e1-results-background`＝全て既存attachment再利用・no-op、`fix-internal-link-portability`＝Navigation/CTA/URLとも「already portable — no change」。
- RUN#3: RUN#2と完全に同一の挙動・ログ。

`dev-snapshot.php`の出力（`post_type_counts`・`attachment_count`・`attachment_filenames`・`duplicate_filenames`・`results_wrapper_count`/`max_depth`・`hero_wrapper_count`/`max_depth`・`navigation_count`/`navigation_items`/`navigation_duplicate_labels`・`identity_checks`・`stale_url_scan`等）をRUN#1/#2/#3それぞれで取得し`diff`で比較した結果、**3回とも完全に同一（byte-for-byte）**であることを確認した：

```
$ diff run1.json run2.json   → 差分なし（exit 0）
$ diff run2.json run3.json   → 差分なし（exit 0）
```

- `duplicate_filenames`: `[]`（3RUNとも）
- `results_wrapper_count` = 1 / `results_wrapper_max_depth` = 0（3RUNとも）
- `hero_wrapper_count` = 1 / `hero_wrapper_max_depth` = 0（3RUNとも）
- `navigation_count` = 1 / `navigation_duplicate_labels` = `[]`（3RUNとも）
- `identity_checks.old_yamada_count` = 0 / `old_ibu_ifuo_count` = 0（3RUNとも）
- `stale_url_scan`: テスト環境自身の`127.0.0.1`のみ（想定通り、リーク無し）

テスト環境は確認後に停止し、破棄した（本番VPS・Live Demoには一切接続していない）。

**§4 判定: PASS**（当初のConstruction 028実施時の結果と完全に一致する挙動を再現）

### §5 Static / Portability Check（Construction 028差分スコープ限定）

- `php -l`: 対象8ファイル全て構文エラー0件。
- `git diff --check`: 対象7スクリプトで空白関連エラー0件。
- 追加行に対して `localhost` / `127.0.0.1` / `172.29.` / `:8900-8902` / `yamada` / `山田` / `やまだ` / `伊府` / `伊夫男` をgrepし、ヒットは`integrate-025e1-results-background.php`のコメント内1件（「Construction 027's yamada->starter rename」という**過去の名称変更を説明する歴史的言及**）のみで、実データ・実ロジックへの旧識別情報の再混入は0件と確認した。
- 新規のローカルファイルシステム依存（絶対パスのハードコードなど）は追加diff内に存在しないことを確認した。
- 現行の識別情報（ASTREA行政書士事務所／伊吹文人）は§4のRUN結果・`identity_checks`で維持を確認済み。

**§5 判定: PASS**

### §6 Theme/Core Protection

```
$ git diff --stat -- theme/ core/
（出力なし）
$ git status --short -- theme/ core/
（出力なし）
```

Theme/Core への意図しない差分は0件。Theme = 1.0.3、Core = 1.0.1、Theme/Core Modification = NONE を再確認した。

**§6 判定: PASS（想定通り差分なし）**

### §7 コミット対象・除外対象の最終確定

**コミット対象（Construction 028由来と`git diff`/`git status`で確認できたもののみ）:**
- `docs/demo-assets/astrea-starter-site/scripts/build-content.php`
- `docs/demo-assets/astrea-starter-site/scripts/cleanup.php`
- `docs/demo-assets/astrea-starter-site/scripts/fix-internal-link-portability.php`
- `docs/demo-assets/astrea-starter-site/scripts/integrate-025b2-hero-and-cases.php`
- `docs/demo-assets/astrea-starter-site/scripts/integrate-025e1-results-background.php`
- `docs/demo-assets/astrea-starter-site/scripts/integrate-final-images.php`
- `docs/demo-assets/astrea-starter-site/scripts/polish.php`
- `docs/demo-assets/astrea-starter-site/REPEATABILITY-TEST.md`（新規）
- `docs/demo-assets/astrea-starter-site/scripts/dev-snapshot.php`（新規）
- `docs/research/2026-09-12_construction_028_starter_pipeline_hardening.md`（本報告書、新規）

**除外（Owner自身の作業・他Construction・迷子ファイル）:**
- `HISTORY.csv`（Owner編集中）
- `docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr`（Owner既存差分、2026-09-10由来）
- `docs/research/screenshots/012〜`配下の削除群（Owner削除済み、復元せず）
- `docs/research/2026-09-11_construction_024_php83_coexistence_provisioning.md`
- `docs/research/2026-09-11_construction_024r1_astrea_live_demo_deployment.md`
- `docs/research/2026-09-11_construction_026_astrea_live_demo_cta.md`
- `docs/research/screenshots/026/`
- `*.Zone.Identifier`（3件）
- `if-professional-astrea.code-workspace`

**Repeatability Status:** RUN#1/#2/#3 PASS（§4）、再実施でも当初結果と完全一致。

**Known Issues（今回もConstruction 029スコープとして未解決のまま維持）:**
1. `build-content.php`は依然ONE-SHOT ONLYであり、7 CPTにまたがる真の冪等マージは未設計・未実装（Starter Import設計そのものの課題）。
2. 既存サイトへの誤爆防止gate（preflight／new-empty-site detection／overwrite・additive・existing-content・re-import・partial-failure・retryポリシー）は存在しない。
3. rollback / restore point / backup / transaction機構は未実装。
4. media import policy（メディアの重複判定・再利用ポリシーの一般化）は`find_existing_attachment_by_filename()`のファイル名一致に留まり、既存サイトの任意メディアとの衝突判定は未設計。
5. navigation generation・generated-page trackingは新規サイト前提の実装のみで、既存メニュー・既存ページとの統合ロジックは未設計。
6. Starter version tracking（このStarter Siteのどのバージョンが導入されたかの記録）は存在しない。
7. `case-1-image.png`・`starter-professional-portrait-placeholder.png`の孤立placeholder attachmentは今回も無変更（低優先度、再実行での増殖なしを確認済み）。
8. `yamada-demo-export.wxr`の既知課題は今回も無変更。

これらはConstruction 028では一切実装せず、Construction 029の設計課題として明記するに留めた。

**029 Readiness:** READY（パイプライン自体の冪等性・繰り返し安全性は今回確立。ただし上記Known Issuesは029で設計が必要）。

**Deploy Decision: NOT REQUIRED。** Construction 028はStarter Siteパイプライン（ローカルスクリプト群・テスト資産・報告書）のハードニングであり、既に公開済みのLive Demo（`demo.project-if.jp`）の可視状態・Theme・Core・コンテンツを一切変更しない。したがって、本Constructionのテストは意図的にLive Demoの実DBに対しては一切実行しておらず（§4は使い捨てローカル環境のみ）、VPSへのデプロイやLive Demoの再構築は本Constructionの範囲外・不要と判断した。

### §9 COMMIT

`git add` を上記コミット対象10ファイルに限定して実行し、`git diff --cached --stat` で除外対象が一切含まれていないことを確認した上でコミットした。

```
git commit -m "Harden ASTREA starter pipeline"
```

- Commit hash: `<COMMIT_HASH_PENDING_FOLLOWUP>`（このコミット自身のハッシュは、Construction 027の前例に倣い、完了後のフォローアップ記録コミットで本報告書に追記する）

### §10 POST-COMMIT CHECK

コミット後、`git status --short --branch` で Owner自身の未commit差分（`HISTORY.csv`・`yamada-demo-export.wxr`）が削除・stash・commitされずそのまま残っていることを確認した。`git log -1` でコミット内容がConstruction 028スコープの10ファイルのみであることを確認した。

### §11 PUSH

```
git push origin main
```

push後、`git rev-parse HEAD` と `git rev-parse origin/main` が一致することを確認した。force pushは使用していない。

### §12 DEPLOY

**実施していない。** VPSへのSSH接続・nginx/PHP設定変更・robots.txt修正・Live Demo（本番）のWordPress DB変更・パーマリンク再構築等、Live Demoに影響する操作は一切行っていない。

---

## Construction 028 — CLOSED

- Owner Review: PASS
- Known Results Idempotency Bug: FIXED
- Attachment Idempotency Bug: FIXED
- cleanup.php Safety Guard: PASS
- build-content.php Re-run Guard: PASS
- RUN#1: PASS
- RUN#2: PASS
- RUN#3: PASS
- Semantic Equivalence: PASS
- Duplicate Growth: 0
- Results Cover wrapper_count=1 / max_depth=0
- Portability: PASS
- Failure Safety Audit: COMPLETE
- Existing-site Safety Audit: COMPLETE
- Theme/Core Modification: NONE
- Theme: 1.0.3
- Core: 1.0.1
- Commit: `<COMMIT_HASH_PENDING_FOLLOWUP>`
- Push: PASS
- Deploy: NOT REQUIRED / NOT RUN
- Live Demo: UNCHANGED
- 029 Readiness: READY
- Known Remaining Design Issue: `build-content.php`の真の冪等マージ、および既存サイトへのStarter Import導入ポリシー（preflight/anti-overwrite/rollback等）はConstruction 029の設計課題。

STOP
