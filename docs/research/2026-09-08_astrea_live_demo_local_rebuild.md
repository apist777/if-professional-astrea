# Construction 023 — ASTREA LIVE DEMO Local Rebuild

「やまだ行政書士事務所 完成版」ローカル構築 — 完了報告（Owner Acceptance Gate待ち）

- Start: 2026-09-08 17:55:39 JST（実測）
- End: 2026-09-08 18:33:37 JST（実測）
- Duration: 0:37:58
- Modifier: Chloe
- Baseline: ASTREA Theme **v1.0.2** / ASTREA Core **v1.0.1**（既存stable artifactをGitHub Releaseから取得、無改変）
- スコープ: **LOCAL BUILD ONLY**。VPS施工・Project-if本番変更・nginx変更・demo.project-if.jp変更のいずれも未実施。

## 1. Executive Summary

Construction 019の施工報告書・42枚のスクリーンショット・023-PREの復元監査結果を設計資料として、現行stable（Theme 1.0.2 / Core 1.0.1）上に「やまだ行政書士事務所」をゼロから再構築した。ASTREA Core自身のSetup関数（管理画面ボタンと同一コード）とWordPress標準のpost/postmeta/option APIのみを使用し、生SQLは一切使用していない。

ローカルでHOME・事務所概要・料金・お問い合わせ・専門家プロフィール（一覧・個別）・取扱業務・対応事例・FAQ等が正常に成立し、Desktop(1440px)/Mobile(390px)ともoverflow 0px・console error 0件・H1一意を確認した。架空情報である旨の開示、Contact送信の安全な挙動（private投稿・実メール未送信）も確認済み。

**ASTREA Theme/Core本体は一切変更していない。** 発見した挙動はすべて「製品の意図した設計」であることをソースコードで確認済みで、STOPが必要な製品バグは0件だった。

画像については、Construction 019と同じ2箇所（代表者写真・対応事例#1）に、正しいアスペクト比のcontrolled placeholderを設置した。**最終的な実写真・イラスト等の生成はこのOrderでは行っていない**（§8 Image Generation Gate — 本報告の末尾でOwnerへ画像リストを提示し、生成着手前にSTOPしている）。

## 2. Construction 019からの設計抽出（§1）

023-PRE監査で確定済みの内容（施工報告書＋42枚のスクリーンショットの目視）を基に、以下の構成をそのまま踏襲した：

- HOME: Header → Hero → 取扱業務(3) → 対応事例(3) → 実績(3) → 代表者紹介(1) → 料金(4) → CTA帯 → よくあるご質問 → お客様の声(3) → ご相談の流れ(3) → Footer
- 事務所概要・料金・お問い合わせの3固定ページ ＋ HOME
- Navigation: ヘッダー・フッター共通の基本メニュー

019のスクリーンショットに実際に写っていた文言（サービス名・対応事例のタイトルと本文・料金・FAQ・お客様の声）は、019で「Owner受入済み・実在しそうな自然な日本語」としてすでに検証済みの内容だったため、**Lorem ipsum的な作り直しをせず、ほぼそのまま踏襲した**（§5の要求「実在しそうな自然な日本語」は019の実データがすでに満たしていたため）。旧バグ（画像がプレースホルダーだった点）は忠実再現の対象から除外し、今回は「正しいアスペクト比のcontrolled placeholder」に置き換えた（§15の明示的許可）。

## 3. ローカル環境（§9〜§11）

### 3-1. 既存環境との非衝突確認

- 既存ASTREA開発用wp-env（`~/wp-env/wp-env-if-professional-astrea-57b77809`）は起動せず、`stopped`状態のまま一切変更していない。
- 既存の`.fixture-backups/`（Construction 015G由来の別Fixture、`blogname=ASTREA行政書士事務所`）にも一切触れていない。
- 今回のLIVE DEMO構築は`~/astrea-live-demo-build/`という完全に独立した新規ディレクトリ・新規ポート(8895)・新規データディレクトリで実施し、既存環境と衝突しないことを事前に確認した。

### 3-2. 技術的な環境選定（重要な方式変更）

**当初、既存プロジェクトの標準である`wp-env`（Docker + MySQL）で環境を用意しようとしたが、本セッションのWSL環境からはDocker CLIを一切実行できない（Docker Desktop側のWSL統合がこのディストリビューション向けに有効化されていない）ことが判明し、`wp-env start`が失敗することを確認した。** これは023-PREでも報告済みの既知の環境制約である。

このため、代替として **WordPress Playground CLI（`@wp-playground/cli`、WASM版PHP + SQLite、Dockerを使用しない）** を使用した。ASTREA本体のリポジトリに既に`devDependency`として含まれているツールである。

- PHP 8.3、WordPress 7.1（ASTREA Core readme.txtの動作確認済みバージョンと一致）
- DBはMySQLではなくSQLite（WordPress公式のSQLite Database Integration機構）
- この方式変更により生じる差異は、後述§13で製品側の問題ではなく環境側の特性であることを個別に確認済み。

### 3-3. Theme/Core導入（§10）

GitHub公式Release（v1.0.2）から直接取得・SHA256再検証した上で導入：

| Artifact | Size | SHA-256 |
| --- | --- | --- |
| astrea-theme-1.0.2.zip | 162,246 bytes | `72cd1fc412b0d102e33f28d0437fd31fac7f2846b0e17725ea7345655fd5c176` |
| astrea-core-1.0.1.zip | 160,065 bytes | `88a526527ebbfdbe85aacc3c4311b35ef3c622df1e6bb1292a86cc0028839531` |

いずれもリポジトリのソースツリーから独自にビルドしたものではなく、公式Release ZIPそのものを展開して使用（source treeからの独自ビルドは行っていない）。ZIPを展開したファイル一式をそのままWordPressのwp-content/themes・wp-content/pluginsへ配置し、`switch_theme()`/`activate_plugin()`（WordPress標準API）で有効化した。wp-admin UIからのアップロード操作そのものはPlaygroundの制約上模倣していないが、**結果として稼働するファイル・コードは、公式ZIPをwp-adminからアップロードした場合と完全に同一**である。

## 4. コンテンツ構築（§4〜§5、§11）

ASTREA Core自身が提供するAPI・Setup関数のみを使用（生SQL不使用）：

- `Astrea\Core\OfficeProfile\sanitize()` / `update_option()` — 事務所情報（事務所名・住所・電話番号・営業時間）
- `wp_insert_post()` / `update_post_meta()` — 各CPT（実際のpost_type・meta keyはCoreのソースコードから確認: `astrea_professional`, `astrea_service`, `astrea_case`, `astrea_result`, `astrea_price`, `astrea_faq`, `astrea_voice`）
- `Astrea\Core\Setup\generate_pages()` / `generate_navigation()` / `generate_home_page()` — 管理画面の「基本ページを作成する」「基本メニューを作成する」「ホームページを作成する」ボタンと**全く同じ関数**を直接呼び出し

### コンテンツ件数（実績）

| Type | 件数 |
| --- | --- |
| Professional | 1（山田太郎、代表者フラグON） |
| Service | 3（会社設立サポート／建設業許可申請／相続手続きサポート） |
| Case | 3（うち1件に画像） |
| Result | 3（300社以上／800件以上／98%） |
| Price | 4（60,000円〜／120,000円〜／90,000円〜／月額25,000円〜） |
| FAQ | 4（3件が重要マーク、HOME表示は3件） |
| Voice | 3 |
| 固定ページ | 4（HOME／事務所概要／料金／お問い合わせ） |

すべて自然な日本語で作成し、「サービス1」等の開発用文言・Lorem ipsumは一切使用していない。

## 5. Fictional Disclosure（§3）

事務所概要ページに、Hero等を潰さない自然な場所（ページ下部、営業時間の下）として1箇所のみ設置：

> このWebサイトは If Professional ASTREA のデモサイトです。掲載されている事務所・人物・サービス内容・実績・お客様の声等は、デモ用に作成された架空の情報です。実在の事務所・人物とは一切関係ありません。

スクリーンショット: `docs/research/screenshots/023/05-about-page-disclosure.png`

## 6. 画像 — Placeholder運用（§7〜§8、§15）

ASTREA Coreのソースコードを確認した結果、Featured Image（写真）に対応するContent Typeは **Professional（代表者）とCase（対応事例）の2種類のみ**（Service/Price/Result/FAQ/Voiceには画像フィールドが存在しない — VOICEは「実在の顧客写真掲載リスクを避けるため意図的に非対応」と製品コード自身のコメントに明記されている）。

今回、この2箇所に正しいアスペクト比のcontrolled placeholderを設置した（PHPのGD拡張で生成、ASCII安全なラベル付き。日本語ラベルはWordPressのalt属性としては正しく保存されるが、画像に焼き込む文字はGDの標準ビットマップフォントがUTF-8非対応のため英語表記に統一）。

### 6-1. 最終画像プラン（§7 — 未生成・Owner承認待ち）

| # | 用途 | 配置 | 向き | 目標アスペクト比 | 推奨サイズ | 被写体 | 構図 | トーン | crop safety | モバイル挙動 | alt text | 推奨ファイル名 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| A | 代表者ポートレート | HOME代表者紹介／専門家プロフィール個別ページ | 縦 | 3:4 | 900×1200px | 山田太郎（架空人物、行政書士、30代後半〜40代、清潔感のあるビジネスカジュアル） | バストアップ〜ウエストアップ、正面〜やや斜め、自然な微笑み | 信頼感・親しみやすさ・過度に硬すぎない | 顔・上半身が縦横どちらにcropされても欠けない中央配置 | 縦長のまま100%幅で表示、顔が上部1/3以内 | 「代表 山田太郎の写真」 | `yamada-professional-portrait.jpg` |
| B | 対応事例#1 | HOME対応事例カード／対応事例個別ページ | 横 | 3:2 | 1200×800px | 建設業許可に関連する書類・図面・オフィスの様子等（人物なし、または後ろ姿程度） | 具体的すぎない、業務のイメージが伝わる構図 | 清潔感・専門性 | 上下cropに強い中央被写体 | 横長のまま100%幅、上下がcropされても成立 | 「建設業許可申請のイメージ」 | `case-01-construction-permit.jpg` |

**任意（品質向上オプション、必須ではない）**:

| # | 用途 | 備考 |
| --- | --- | --- |
| C | 対応事例#2用画像 | 019・023とも「画像なし」が正解構成だが、完成度を上げるなら追加可能。追加する場合は同じ3:2, 1200×800pxで統一。 |
| D | 対応事例#3用画像 | 同上。 |

**画像を増やしすぎない方針に従い、上記C/Dは推奨ではなく選択肢としてのみ提示する。** Hero・Service・Price・Result・FAQ・Voiceに画像スロットは存在しないため、これ以上の画像候補はない。

### 6-2. Image Generation Gate（§8）

**ここでSTOP。** 上記A・Bの最終画像は生成していない。Owner承認後、Project-if専用デモ素材として新規生成する（実在人物の再現ではない完全な架空人物として）。generic stock photoの取得やprovenance不明素材の使用は行っていない。

## 7. Product Integrity（§17）— 発見事項の分類

| 発見事項 | 分類 | 対応 |
| --- | --- | --- |
| root URL `/`が起動直後の数秒間、302自己リダイレクトを返すことがある | **環境要因（Playground/WASM特有のworker起動直後の一時的挙動）**。デフォルトTheme（Twenty Twenty-Five）でも同一現象を確認、ASTREA固有ではない。数秒〜十数秒待つと解消し、以降は安定して200を返す。 | 対応不要。ASTREA/Docker環境では発生しない、Playground特有の起動直後の現象として記録のみ。 |
| Result（実績）・Price（料金）CPTには公開アーカイブ/個別ページのURLが存在しない（`/results/`等が404） | **A. Demo content/configuration issueですらない — 製品の意図した設計**。両CPTとも`'public' => false`とソースコードで明示的に宣言されている（Result/Price個別ページを検索エンジンやURLで公開する必要がないという設計判断）。 | 対応不要。バグではない。 |
| 事務所概要ページの初期テンプレート文言「ここに事務所の紹介文を入力してください。」がSetup生成直後は残る | **A. Demo content issue**（製品バグではなく、Setup機能が意図的に残す「要編集」プレースホルダー） | 本Orderの範囲内で実際の紹介文に差し替え済み（§4）。 |

**Theme/Core本体の変更が必要な発見は0件。STOPした事項も0件。**

## 8. Visual Acceptance（§16）

Playwright実機検証（1440px・390px）:

| ページ | 1440px | 390px |
| --- | --- | --- |
| HOME | 200 / overflow 0 / H1×1 / error 0 | 200 / overflow 0 / H1×1 / error 0 |
| 事務所概要 | 200 / overflow 0 / H1×1 / error 0 | 200 / overflow 0 / H1×1 / error 0 |
| 料金 | 200 / overflow 0 / H1×1 / error 0 | 200 / overflow 0 / H1×1 / error 0 |
| お問い合わせ | 200 / overflow 0 / H1×1 / error 0 | 200 / overflow 0 / H1×1 / error 0 |
| 専門家プロフィール個別 | 200（H1「山田 太郎」） | — |

追加確認: `/professionals/`（一覧）200、`/services/`（一覧）200、`/cases/`（一覧）200、`/voices/`（一覧）200、`/faq/`（一覧）200、存在しないURL 404（Modern Phase 9で確立した「独自WordPressの404が正しく返る」パターンと同じ健全性）。

Contact実送信テスト（実UI経由）: 空送信→バリデーションエラー表示、正常送信→成功メッセージ表示、DB上`post_status='private'`で保存（一般公開されない）を確認。実メール送信は発生していない（通知先メールアドレス未設定のため）。

Construction 019との比較:

- **inherited（継承）**: 情報設計・コンテンツ構成・文言のほぼ全体、Trust系の配色・レイアウト方向性
- **improved（改善）**: 画像がガベージなプレースホルダーではなく正しいアスペクト比のラベル付きplaceholderに、事務所概要の紹介文・営業時間が実装、Theme由来のフッタークレジット「Theme by Project-if」表示（v1.0.2で追加された機能、019時点のrc2にはなかった）
- **intentionally changed（意図的な変更）**: Theme/Core versionを1.0.0-rc2→1.0.2/1.0.1へ更新、Fictional Disclosureの新規追加（019にはなかった、今回のOrder §3要件）

完全なpixel-matchは要求されておらず、実施していない。

スクリーンショット: `docs/research/screenshots/023/01〜07*.png`

## 9. Reproducibility（§12）— Construction 019の教訓への対応

Construction 019の最大の反省点（環境を使い捨てて何も再利用可能な資産が残らなかったこと）を踏まえ、以下を`docs/demo-assets/yamada-live-demo/`に保存した：

- `yamada-demo-export.wxr`（WordPress標準エクスポート、30 items、XML妥当性確認済み、秘密情報0件を確認済み）
- プレースホルダー画像2点の実ファイル
- 今回使用したPHPスクリプト一式（WordPress自身のAPIのみ使用）
- 再現手順を記載した`README.md`

admin/DBパスワード・salt・トークン等は一切保存していない。Docker/Playgroundのボリューム自体も保存していない（このパッケージだけで再現できる設計）。

## 10. VPS Migration Plan（§18〜§19、PLAN ONLYで未実行）

将来`https://demo.project-if.jp/astrea/`へ移植する際の想定手順（**今回は一切実行していない**）:

1. If-Thema Modern Phase 9で確立したproduct-subpath architectureを再利用し、`demo-product-subpath-bootstrap.sh astrea`をOwnerが実行（別Order）。
2. §9のREADMEに記載した再現手順でWXR＋Office Profile設定を投入。
3. §6の最終画像（Owner承認・生成後）へ差し替え。
4. サイトURLをWP標準の設定機能で本番URLへ変更（**独自のSQL文字列置換は行わない**。シリアライズされたデータを壊す危険があるため、URL変更が必要な場合はWP-CLIの`search-replace`等、シリアライズを理解する安全な方式を使用する計画とする）。
5. `blog_public=0`（WordPress標準のnoindex機構）を設定し、Modern Phase 9で確立した方針と同様に非索引化する。

実行前の再監査事項（Order指示通り、今回は未実施）:
- `scripts/ops/demo-product-subpath-bootstrap.sh`（project-ifリポジトリ側）の現在のSHAと、Modern Phase 9で発見・修正した旧awk挿入バグが引き続き解消されていることの再確認
- ポート・DB名衝突チェック、rollback手順、nginx marker検証、credential handling — bootstrapスクリプト自身の既存の安全設計を再確認

## 11. Project-if製品ページ導線（§20、PLAN ONLYで未実行）

将来、ローカルOwner受入後にProject-if ASTREA JA/EN製品ページへ「LIVE DEMOを見る」/「View Live Demo」導線を追加する予定（リンク先`https://demo.project-if.jp/astrea/`）。**本Orderでは一切実施していない。**

## 12. Git（§24）

保存したもの: 本報告書、`docs/demo-assets/yamada-live-demo/`一式（WXR・placeholder画像2点・スクリプト・README）、`docs/research/screenshots/023/`（7枚）。

保存しなかったもの: ASTREA Theme/Core product source（一切変更していないため差分なし）、Docker/Playgroundのボリューム・コンテナ自体、admin/DBパスワード等の秘密情報。

## 13. STOP Conditions（§25）該当なし

Theme/Core modification不要、current stable artifact整合、既存Docker/wp-env環境は無変更、VPS/root作業なし、provenance不明画像は不使用（未生成のまま）、実在人物/事務所データの混入なし、Contact安全性はTheme改造なしで確認済み、migration不能な構成ではない（WXR export成功）、大規模visual変更のOwner判断が必要な事態は発生せず、Construction 019 evidenceと新仕様の根本的矛盾もなし。

## 14. Owner Acceptance Gate（§21）

以下を提示します。ご確認の上、**「APPROVED FOR LIVE MIGRATION」** のご意思表示をいただくまで、VPS施工（demo.project-if.jp/astrea/の実際の構築）へは進みません。

1. HOME Desktop — `docs/research/screenshots/023/01-home-1440.png`
2. HOME Mobile — `docs/research/screenshots/023/02-home-390.png`
3. 専門家プロフィール個別ページ — `docs/research/screenshots/023/04-professional-single.png`
4. 代表者紹介セクション（拡大） — `docs/research/screenshots/023/03-professional-section.png`
5. 画像インベントリ — 本報告§6（2箇所がplaceholder、最終画像プランはOwner承認待ち）
6. コンテンツインベントリ — 本報告§4
7. Fictional Disclosure — 本報告§5、スクリーンショット`05-about-page-disclosure.png`
8. Contact挙動 — 本報告§8、スクリーンショット`06-contact-validation.png`／`07-contact-success.png`
9. Migration package概要 — 本報告§9、`docs/demo-assets/yamada-live-demo/README.md`
10. Construction 019との差分 — 本報告§8「Construction 019との比較」

**完了後STOP。Owner承認なしで`demo.project-if.jp/astrea/`は作成していません。**
