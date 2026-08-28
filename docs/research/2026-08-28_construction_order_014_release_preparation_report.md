# Construction Order 014 — Release Preparation Report

ASTREA FREE v1「出荷準備施工」の実施記録。目的は新機能追加ではなく、「開発環境では動く」から「第三者がZIPを受け取り、新規WordPress環境へ導入できる」状態への移行。

Construction 014Aで確認したWordPress 7.1のcore/group・core/cover Editor Validation Warningは、Release Blockingではない（Category C／Severity MEDIUM）ことを前提に本Constructionを開始した。

## Part A — Release Metadata Audit

`theme/style.css` と `core/astrea-core.php` のHeaderを実機と突き合わせて監査した。

| 項目 | Theme | Core |
|------|-------|------|
| Name | ASTREA | ASTREA Core |
| Version | 0.9.0 | 0.12.0 |
| Requires at least | 7.0 | 7.0 |
| Tested up to | 7.1 | 7.1 |
| Requires PHP | 8.3 | 8.3 |
| License | GPLv2 or later | GPLv2 or later |
| License URI | gnu.org/licenses/gpl-2.0.html | gnu.org/licenses/gpl-2.0.html |
| Text Domain | astrea | astrea-core |

いずれも実装・PHPCS設定（`phpcs.xml` の `PHPCompatibilityWP testVersion=8.3-`）・`composer.json` と一致していた。矛盾なし。

**PHP最低バージョンに関する重要な発見**：Construction 014発注文には「既存Decision：absolute minimum PHP 7.4」という前提が記載されていたが、`docs/specifications/04_astrea_free_v1_preconstruction_decisions.md` のDecision 020（FIXED、確定済み）は「ASTREA FREE 1.0はPHP 8.3以上を初期最低ラインとして設計する」と明記しており、現在の実装（style.css / astrea-core.php / composer.json / phpcs.xml）は一貫して8.3を採用している。7.4はWordPress本体自体の絶対最低要求であり、ASTREA独自の最低ラインとは別物と考えられる。本Reportでは、既に確定しているDecision 020を正本として扱い、Version表記は変更していない（`Requires PHP: 8.3` のまま）。この点は本Reportの結論として明示的にユーザーへ報告する（詳細後述）。

Versionは開発版のまま据え置いた（0.9.0 / 0.12.0）。v1.0.0への昇格・RC Tag作成は本Constructionでは行わない（発注文の明示的な指示どおり）。

## Part B — readme.txt / Description / Installation / Known Issues / Language

`theme/readme.txt`（新規作成）と `core/readme.txt`（新規作成）をWordPress.org標準形式で作成した。

- Description：FREE v1で実際に存在する機能のみを記載（誇大表現なし）。ASTREA Coreの位置付け（任意・推奨、Theme単体でも安全に動作、Decision 021）を明記。
- Installation：Theme ZIP → 有効化 → Core ZIP（任意） → 有効化 → ASTREA Setup → Office Profile入力 → HOME/基本ページ/メニュー作成 → Site Editorでデザイン調整、という実装と一致する手順を記載。
- FAQ：ASTREAとは何か／Theme・Coreの違い／Coreなしで動くか／Setup／Office Profile／専門家・取扱業務・料金・FAQ・CASE・RESULTS・VOICE／お問い合わせ（保存期間10/30/60/90日、初期値30日）／HOME／Navigation／サイトタイトル（Office Profileとは非同期）／Style Variation／Site Editor／Core無効化時のデータ保持／完全削除（削除される範囲・されない範囲を明記し、「全部完全に消える」という誤解を招く表現は使用していない）／GA4／SEO Plugin共存／日本語Language Packの扱い、を一通りQ&A形式で網羅した。
- Known Issues：Construction 014Aの結論を簡潔に記載（WordPress 7.1のcore/group・core/cover警告、ASTREA固有の不具合ではないこと、データ損失は確認されていないこと、「Attempt recovery」を不用意に実行しない案内）。
- Language：ASTREAが日本語Language Packを自動Installしないこと、WordPress Site Languageを強制変更しないことを明記。

## Part C — License Audit

Theme/Core全体を監査した結果、**バンドルされた第三者Asset（画像・フォント・アイコン等）は一切存在しない**ことを確認した（`find theme core -iregex '.*\.\(png\|jpg\|...\)$'` の結果が空、`theme/screenshot.png` を除く）。JSは `core/assets/js/editor-blocks.js` の1ファイルのみで、外部依存やBundlerを持たない、WordPress自身が既に読み込んでいるGlobal（`wp.blocks`／`wp.element`／`wp.i18n`）のみを使用する自作コードである。

WordPress本体が同梱する正規のGPLv2全文（`license.txt`、`wp-includes` と同階層）から法的本文を採取し、以下を新規作成した。

- `LICENSE`（リポジトリルート、GitHub向け）
- `theme/license.txt`
- `core/license.txt`

`style.css` / `astrea-core.php` / `readme.txt` / LICENSE間でLicense表記（GPLv2 or later）に矛盾がないことを確認した。`composer.json` の `license` フィールド（開発Tool用、配布物には含まれない）も同一。

## Part D — i18n Audit / POT

PHPCSの `WordPress.WP.I18n` Sniff（`text_domain: astrea, astrea-core`）は既にPHPCS設定に組み込まれており、今回の監査でも0件のI18n違反だった。

JS側（`core/assets/js/editor-blocks.js`）はConstruction 013の時点で既に `window.wp.i18n.__()` を使用し、`wp_set_script_translations()` がPHP側（`core/includes/editor-blocks.php`）で正しく登録されていることを再確認した。

`wp i18n make-pot` で以下を新規生成した（wp-envのcli container内、実際のWordPress i18n Toolchainを使用）。

- `theme/languages/astrea.pot`（58 msgid、PHP + JSを走査）
- `core/languages/astrea-core.pot`（273 msgid。`assets/js/editor-blocks.js` 由来の文字列がPHP側の同一文言と正しく集約されていることを確認）

Translation Loading（`load_theme_textdomain`等）は、WordPress 4.6以降の自動Loading（Text Domainがstyle.css/Pluginヘッダーと一致していれば、テーマ・プラグイン用の翻訳ファイルは`languages/`から自動的に読み込まれる仕組み）に委ねており、現行実装（`theme/functions.php`、`core/astrea-core.php`）に旧式の手動`load_theme_textdomain()`等は存在しない。追加は不要と判断した。

## Part E — Theme Check相当の静的監査 / WordPress.org readiness

WordPress.org公式Theme Check Pluginのインストールは行わなかった（ネットワーク経由の追加Toolインストールを本Constructionの必須要件とはしない判断。Construction 012/013で経験した`wp-env run cli`のネットワーク起因ETIMEDOUTの再発リスクを避けた）。代わりに、Theme Checkが検証する主要観点を手動で確認した。

| 観点 | 結果 |
|------|------|
| 必須ファイル存在（style.css / theme.json / templates/index.html / readme.txt / screenshot.png） | ✅ 全て存在 |
| Escaping | ✅ PHPCS `WordPress.Security.EscapeOutput` 等 0件（既存CI基準） |
| I18n | ✅ 0件（Part D参照） |
| Deprecated API | ✅ `PHPCompatibilityWP`（testVersion 8.3-）0件 |
| PHP Notice/Warning | ✅ `debug.log` をClearした状態でHOME生成・表示・Setup画面表示を実行し、新規のNotice/Warning/Deprecatedが出力されないことを確認 |
| Licensing | ✅ Part C参照 |
| Unwanted files | ✅ `theme/`・`core/` 直下に開発専用ファイル（テスト・ドキュメント・IDE設定等）が混入していないことを確認（後述Part F） |

WordPress.org正式Submissionは本Constructionの対象外。将来Submissionする際に大改造が必要になるような問題は発見されなかった（readme.txt形式・screenshot.png・LICENSE・i18n体制は全てWordPress.org標準形式で用意済み）。

## Part F — Distribution File Audit

`theme/` ディレクトリの内容を監査した結果、**開発専用ファイルは元々一切含まれていなかった**ことを確認した（`tests/`・`tools/`・`docs/`はいずれもリポジトリルート直下にあり、`theme/`・`core/`の内部には存在しない）。

Theme ZIP Root構造：

```
astrea/
  style.css / theme.json / functions.php / readme.txt / license.txt / screenshot.png
  languages/  parts/  patterns/  styles/  templates/
```

Core ZIP Root構造：

```
astrea-core/
  astrea-core.php / readme.txt / license.txt / uninstall.php
  assets/  includes/  languages/
```

除外Listは念のための多重防御として `.gitkeep` / `.DS_Store` / `Thumbs.db` / `*.log` / `.git*` を `tools/release/package.sh` に明示したが、実際にこれらが混入するリスクは無かった（対象ディレクトリに元々存在しないため）。

### Secret Scan

`theme/`・`core/` 全体をAPI Key／GA4 IDの実値／Email／Password／Token／SSH鍵／秘匿URL／ローカルパス／開発用Hostnameのパターンで走査した。ヒットしたのはいずれも正当なアプリケーションコード（Contact/Email確認用の一時Token生成ロジック、GA4測定IDのPlaceholder例`G-XXXXXXXXXX`）のみで、実際の秘匿情報の混入は無かった。

## Part G — Reproducible Packaging

`tools/release/package.sh`（新規）を作成した。`theme/`・`core/`をそれぞれ正しいRoot Directory名（`astrea/`・`astrea-core/`）へコピーし、`dist/astrea-theme-<version>.zip`・`dist/astrea-core-<version>.zip`・`dist/SHA256SUMS.txt`を生成する。Versionはstyle.css / astrea-core.phpのHeaderから自動取得する。ソース（`theme/`・`core/`自体）を汚さない。`dist/`は既存の`.gitignore`で除外済みのため、生成物はGitへcommitしない。

```
$ bash tools/release/package.sh
Theme version: 0.9.0
Core version:  0.12.0
Built: dist/astrea-theme-0.9.0.zip
Built: dist/astrea-core-0.12.0.zip
```

### SHA-256 Checksums

```
7ec6030d0d7fd70019caef22d5b1e1f56eac95b5957f3064a111aceb7d8e5f39  astrea-theme-0.9.0.zip
4515a1cb1c978dfc333549ca060d54bbc2ca3cd560a32e9cef11691551d2359b  astrea-core-0.12.0.zip
```

## Part H — ZIP Install Test（重要）

wp-envのSource直接mount環境ではなく、**生成したZIPそのもの**を使った独立したClean Install Testを実施した。既存のwp-env（ASTREA用・他Project用いずれも）には一切触れず、新規のDocker Network（`astrea-clean-install-test`）・Volume・Containerを作成し、テスト完了後に完全に削除した。

使用Imageは、既にローカルにキャッシュ済みのwp-env用WordPress/MariaDB Imageを流用した（新規Pull不要）。ただしSource Bind Mountは一切行わず、WordPress本体・wp-content双方ともコンテナ起動時にImage内蔵の初期状態から生成させた。

| # | 確認項目 | 結果 |
|---|----------|------|
| 1 | ASTREA Theme ZIP install（`wp theme install <zip>`） | ✅ 成功 |
| 2 | Activate | ✅ 成功（`Switched to 'ASTREA' theme`） |
| 3 | Frontend表示 | ✅ HTTP 200 |
| 4 | Core無しでFatal無し | ✅ Fatal Errorマーカー無し |
| 5 | ASTREA Core ZIP install（`wp plugin install <zip>`） | ✅ 成功 |
| 6 | Activate | ✅ 成功 |
| 7 | Setup画面表示 | ✅ HTTP 200（認証Login経由、「セットアップ状況」表示確認） |
| 8 | Office Profile入力 | ✅ `sanitize()` → `update_option()` 成功 |
| 9 | HOME生成 | ✅ `generate_home_page()` 成功 |
| 10 | Basic Navigation生成 | ✅ `generate_navigation()` + `connect_navigation_to_header_footer()` 成功（header/footer共に`connected`） |
| 11 | Frontend Header/Footer Navigation確認 | ✅ 実リンク（事務所概要・お問い合わせ等）を確認、page-list Fallbackへの意図しないFallback無し |
| 12 | Dynamic Data表示 | ✅ 入力した事務所名がHOMEに表示 |
| 13 | Contact表示 | ✅ `<form>`・`astrea-contact-form`クラスを確認 |
| 14 | Core OFF | ✅ `wp plugin deactivate astrea-core` 成功 |
| 15 | Fatal無し | ✅ HTTP 200、Fatal Errorマーカー無し |
| 16 | Core ON | ✅ `wp plugin activate astrea-core` 成功 |
| 17 | Data復元 | ✅ Office Profile Optionが無傷で残存していることを確認 |

テスト後、Network・Volume・Containerを完全に削除し、ホスト環境・他Projectへの影響が無いことを確認した。

## Part I — Compatibility Matrix

| 項目 | 対応範囲 | 実機確認 |
|------|----------|----------|
| WordPress | 7.0以上（Requires at least）、7.1（Tested up to） | ✅ 7.1で実機確認済み（本Construction含む全Construction Orderを通じて）。7.0固有の実機確認は未実施 |
| PHP | 8.3以上（Requires PHP） | ✅ 8.3で実機確認済み（wp-env tests-cliコンテナの実PHP 8.3、PHPCS `PHPCompatibilityWP testVersion=8.3-`） |
| PHP（Recommended） | 8.4以上 | Decision 020策定時の技術調査（2026-08-25）により、WordPress Hosting Teamが本番環境へPHP 8.4以降を推奨していることを確認済み。Minimum（8.3）とRecommended（8.4+）は明確に区別してDocumentation化した |

PHP 7.4での実機動作確認は行っていない。Part Aで述べたとおり、発注文の前提（absolute minimum PHP 7.4）は現在のDecision 020（8.3が初期最低ライン）と整合しないため、7.4対応のSyntax Compatibility監査・実機確認のいずれも実施していない。「実機確認済み」と偽ることを避けるため、readme.txt・本Reportいずれにも7.4の実機確認済みという記載はしていない。

## Part J — User-facing Documentation

`theme/readme.txt`・`core/readme.txt`のInstallation/FAQ節に、初心者が迷わずInstall〜Setup〜Customize〜Uninstallまで到達できる情報を整理した（Part B参照）。開発者向け資料（`docs/`）とユーザー向け資料（`readme.txt`）を明確に分離し、ユーザーが`docs/research/`を読む必要がある構造にはしていない。

## Part K — Release Procedure

`docs/release/RELEASE_PROCEDURE.md`（新規）を作成した。mainクリーン確認からGitHub Release・Project-if配布連携までの14手順を文書化した。RC Tag作成・GitHub Release公開・Project-if本番配布は、いずれも「正式命令があった場合のみ」実行する手順として明記し、Construction 014内では一切実行していない。

## Part L — RC1 Acceptance Checklist

`docs/release/RC1_ACCEPTANCE_CHECKLIST.md`（新規）を作成した。Functional／Responsive／Accessibility／Security／SEO／Editor／Setup／Core OFF／Uninstall／Packaging／Clean Install／Compatibility／Documentation／License／Artifact／CIの16項目のチェック表と、Known Exceptions（WordPress 7.1 core/group警告）、Post v1 Backlog（Finding 6/7/8）を明記した。

## Quality Gate（実施結果）

| 項目 | 結果 |
|------|------|
| PHP Syntax（`php -l`、CIと同一条件） | ✅ 全件OK |
| PHPCS（`composer run lint`、CIと同一条件） | ✅ 62/62 OK、0 Errors |
| PHPUnit | ✅ 359 tests, 560 assertions, OK |
| smoke-test.sh（全13 Part、203 OK） | ✅ Exit Code 0 |

smoke-test実行中に、既知の`wp-env run cli`ETIMEDOUTが複数回発生した。過去のConstructionと同様、製品コードの回帰と決めつけず、直前の中断が残したFixture（テスト用Page・Service・Case投稿、Navigation関連Option）を特定・削除した上で再実行し、最終的にクリーンな全件成功を確認した。

## 開発環境Cleanup

- ZIP Install Test用に作成した独立Docker Network・Volume・Containerを完全削除した。
- Screenshot撮影用に作成したOffice Profile・Service 3件・HOME Page・Navigation・Template Part（Header/Footer）を全て削除し、Optionもリセットした。
- 過去セッションの中断実行が残していたStray Fixture（Case/Service/Page投稿、重複したHOME/基本ページ）も本Constructionの過程で発見し、併せて削除した。
- `git status` はクリーン（意図した新規ファイルのみ）。

## 製品コード変更について

本Constructionは「新機能追加ではない」という原則どおり、`theme/`・`core/`・`tests/`配下の実装コード（PHP/JS）に**変更を加えていない**。今回追加したファイルは以下のとおりで、いずれもRelease Metadata／Documentation／License／Packagingに関するものである。

- `theme/readme.txt`（新規）
- `theme/license.txt`（新規）
- `theme/screenshot.png`（新規）
- `theme/languages/astrea.pot`（新規）
- `core/readme.txt`（新規）
- `core/license.txt`（新規）
- `core/languages/astrea-core.pot`（新規）
- `LICENSE`（リポジトリルート、新規）
- `tools/release/package.sh`（新規）
- `docs/release/RELEASE_PROCEDURE.md`（新規）
- `docs/release/RC1_ACCEPTANCE_CHECKLIST.md`（新規）
- `docs/research/2026-08-28_construction_order_014_release_preparation_report.md`（本Report）
- `docs/research/screenshots/014/`（Screenshot証跡）

`theme/`・`core/`配下のPHP/JS実装（`.php`・`.js`ファイル）には一切変更を加えていない（`readme.txt`・`license.txt`・`screenshot.png`・`languages/*.pot`はいずれもDocumentation/Metadata Artifactであり、実行コードではない）。
