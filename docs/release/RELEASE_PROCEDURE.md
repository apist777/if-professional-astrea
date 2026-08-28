# ASTREA FREE v1 — Release Procedure

Construction Order 014で確定した、正式Release（v1.0.0およびそれ以降のPatch/Minor Release）に向けた標準手順。

この文書自体はConstruction 014で「手順を確定する」ためのものであり、実際のRC1 Tag作成・GitHub Release公開は、別途の正式命令があるまで実行しない。

## 前提

- 標準開発環境：WSL2 + Docker + `@wordpress/env`（Decision 020）
- 対応WordPress：7.0以上（7.1で主に検証）
- 対応PHP：8.3以上（Decision 020。詳細は本書末尾の「PHP最低バージョンに関する注記」を参照）

## 手順

### 1. mainブランチをクリーンな状態にする

```bash
git status   # untracked/uncommittedが無いことを確認
git log --oneline -5
```

### 2. 自動Testを実行する

```bash
npm run test:php     # PHPUnit
npm run smoke-test   # Theme/Core独立性 + 機能End-to-Endチェック
```

いずれも `wp-env run cli` はネットワーク起因のETIMEDOUTで散発的に失敗することがある（本プロジェクトで既知の現象）。その場合は製品コードの回帰と決めつけず、直前の実行が残したFixtureをクリーンアップした上で再実行し、原因を切り分けること。

### 3. PHPCS（Coding Standards + PHP Compatibility）を実行する

CI（`.github/workflows/ci.yml`）と同じ条件（PHP 8.3、リポジトリルートを作業ディレクトリとする）で実行する。wp-envのcli/tests-cliコンテナは `/var/www/html` がWordPress本体のルートであり、リポジトリルート（`theme/`・`core/`が直下にある構造）とは異なるため、`phpcs.xml` の相対パスがそのままでは解決できない。以下のように、同じPHP 8.3イメージをリポジトリルートに直接bind mountして実行する。

```bash
docker run --rm -v "$(pwd)":/app -w /app \
  wp-env-if-professional-astrea-<hash>-tests-cli \
  composer run lint
```

（`<hash>` はwp-envが実行時に生成するProject Hash。`docker ps` で実際のコンテナ名を確認する。）

### 4. Theme Check相当の静的監査を実施する

Construction 014時点では、WordPress.org公式Theme Check Pluginのインストールは行っていない（ネットワーク経由の追加Toolインストールを本手順の必須要件にはしない）。代わりに、以下を手動で確認する。

- 必須ファイルの存在：`style.css`、`theme.json`、`templates/index.html`、`readme.txt`、`screenshot.png`（Theme） / `astrea-core.php`、`readme.txt`（Core）
- `style.css` / `astrea-core.php` / `readme.txt` の間でVersion・Requires at least・Tested up to・Requires PHP・Licenseの記載が一致していること
- `debug.log` に、通常の利用フロー（Setup一式の実行、Core ON/OFF切り替え）でPHP Notice/Warning/Deprecatedが出力されないこと

### 5. Packagingを実行する

```bash
bash tools/release/package.sh
```

`dist/astrea-theme-<version>.zip`、`dist/astrea-core-<version>.zip`、`dist/SHA256SUMS.txt` が生成される（`dist/` は`.gitignore`済みで、Gitへcommitしない）。

### 6. Checksumを記録する

```bash
cat dist/SHA256SUMS.txt
```

Release Reportへ転記する。

### 7. ZIP Install Testを実施する

ソースを直接mountしたwp-env環境ではなく、生成したZIPそのものを、ASTREA関連の既存bind mountを一切持たない、独立したWordPress環境へInstallして確認する。手順の要点：

1. 独立したDocker Network / Volumeを新規作成する（既存のwp-env環境や、他Projectのコンテナには一切触れない）。
2. 既にローカルにキャッシュされているWordPress公式相当Image（wp-envが内部で使用しているものを流用可）でMySQL/WordPressコンテナを起動する。source側のbind mountは行わない。
3. `wp core install` でWordPressを初期化する。
4. `wp theme install <path-to-astrea-theme-zip>.zip --activate` を実行する。
5. Core無しでFrontendがHTTP 200・Fatal無しであることを確認する。
6. `wp plugin install <path-to-astrea-core-zip>.zip --activate` を実行する。
7. Setup画面表示、Office Profile入力、HOME/基本ページ/Navigation生成、Frontend表示、Contact表示を確認する。
8. Core OFF → Fatal無し、Core ON → データ復元を確認する。
9. 確認後、Network / Volume / Containerを完全に削除する。

### 8. Screenshotを確認する

`theme/screenshot.png` が最新のASTREAの見た目を反映していること、規定のサイズ（1200×900px推奨）であることを確認する。

### 9. Documentationを確認する

`theme/readme.txt`、`core/readme.txt` の内容が、実際の機能・画面名・設定項目と一致していることを確認する（画面名・ボタン文言が変わった場合は追随して更新する）。

### 10. Versionを確認する

`theme/style.css`、`core/astrea-core.php`、`core/astrea-core.php` 内の `ASTREA_CORE_VERSION` 定数、`theme/readme.txt`・`core/readme.txt` の `Stable tag` が全て一致していることを確認する。

### 11. CI Greenを確認する

```bash
git push origin main
gh run watch <run-id> --exit-status
```

3つのJob（PHP syntax + Coding Standards / Theme・Core independence smoke test / PHPUnit）全てが成功していることを確認する。

### 12. RC Tagを作成する（正式命令があった場合のみ）

Construction 014ではこの手順を「確定」するのみで、実行はしない。実行する場合の想定コマンド例：

```bash
git tag -a v1.0.0-rc1 -m "ASTREA FREE v1.0.0-rc1"
git push origin v1.0.0-rc1
```

### 13. GitHub Releaseを作成する（正式命令があった場合のみ）

Release Notes Templateは本手順に付随して用意してよいが、実際の公開はConstruction 014の対象外。

### 14. Project-ifへの配布連携（正式命令があった場合のみ）

Project-if本番サイトへの配布・Deployは、Construction 014では一切行わない。

## PHP最低バージョンに関する注記

Construction 014の発注文には「既存Decision：absolute minimum PHP 7.4」という前提が記載されていたが、`docs/specifications/04_astrea_free_v1_preconstruction_decisions.md` のDecision 020（FIXED）は「ASTREA FREE 1.0はPHP 8.3以上を初期最低ラインとして設計する」と明記しており、実際に `theme/style.css`・`core/astrea-core.php`・`composer.json`・`phpcs.xml`（`PHPCompatibilityWP` の `testVersion` も `8.3-`）は一貫して8.3を最低ラインとして実装・検証されている。WordPress本体自体の絶対的な要求ライン（7.4）とASTREA独自の最低ラインの混同と考えられる。

本Release Procedureでは、既存Decision 020を正本として8.3を最低ラインとして扱う。PHP 7.4環境での実機動作確認は行っていない（実施しておらず、実施の予定もない）。もし将来的にPHP 7.4対応が必要と判断された場合は、新しいDecisionとして正式に決定すること。
