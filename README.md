# If Professional ASTREA

士業・専門家向けWordPress製品「ASTREA」の開発リポジトリ。

- 製品思想・仕様：[`docs/specifications/`](docs/specifications/)（最上位実装基準は [`05_astrea_free_v1_construction_baseline.md`](docs/specifications/05_astrea_free_v1_construction_baseline.md)）
- 調査・監査資料：[`docs/research/`](docs/research/)
- 開発履歴：[`HISTORY.md`](HISTORY.md)
- 開発体制・作業規則：[`AGENTS.md`](AGENTS.md)

## ディレクトリ構成

```
theme/   ASTREA Theme（WordPress Block Theme。wp-envではスラッグ astrea としてマウントされる）
core/    ASTREA Core（WordPress Plugin。wp-envではスラッグ astrea-core としてマウントされる）
tools/   開発・CI補助スクリプト
docs/    仕様書・調査資料
```

## 開発環境

標準環境は **WSL2 + Docker + `@wordpress/env`**（Decision 020）。

### 前提

- Node.js `>=18.12.0` / npm `>=8.19.2`
- Docker（Docker Desktop の WSL2 統合を有効化しておくこと）

Composer / PHPUnit / PHPCSは、**ホストのPHPではなく、wp-envの`tests-cli`コンテナが内蔵する実PHP 8.3**で実行する（本プロジェクトの`Requires PHP: 8.3`と一致させるため）。ホストPHPが8.3未満の環境で`--ignore-platform-req`等を使って`composer.lock`を生成すると、実PHP 8.3で解決できないlockになる事故が起きる（2026-08-26に実際に発生・修正済み。詳細は`docs/research/2026-08-26_construction_order_002_report.md`）。

### セットアップ

```bash
npm install
npm run env:start      # WordPressをローカルに起動（http://localhost:8888）
```

Composer依存関係の初回インストール／更新は、wp-envコンテナの中で実行する。

```bash
CONTAINER=$(docker ps --format '{{.Names}}' | grep tests-cli$)
docker cp <composer.pharのパス> "$CONTAINER:/usr/local/bin/composer.phar"
docker exec -w /var/www/html "$CONTAINER" php /usr/local/bin/composer.phar install
```

インストール後の`vendor/`はホスト側にも反映される（`.wp-env.json`の`mappings`によるbind mount）。

```bash
composer run lint      # PHPCS（WordPress Coding Standards / PHP Compatibility）※要 vendor/ 導入済み
npm run test:php       # PHPUnit（wp-envのtests-cliコンテナ内、実DBに対して実行）
```

### Theme / Core の有効化

```bash
npm run env:cli theme activate astrea
npm run env:cli plugin activate astrea-core
```

### Theme / Core 独立性 + Office Profile チェック

ASTREA Coreは任意Plugin・公式推奨という位置付け（Decision 021）。「Themeのみ／Theme+Core／Core無効化／Core再有効化」の4状態でFatalが起きないこと、およびOffice Profileの保存・表示・データ保持を自動確認する。

```bash
npm run smoke-test
```

同じ内容（`smoke-test` + PHPUnit + PHPCS）はGitHub Actions（[`.github/workflows/ci.yml`](.github/workflows/ci.yml)）でも実行される。

### 環境の停止

```bash
npm run env:stop       # 停止（データは保持）
npm run env:destroy    # 完全削除（次回起動時にWordPress本体・Docker imageを再取得する）
```

## コーディング規約

- WordPress Coding Standards（PHPCS、`phpcs.xml`）
- Text Domain：Theme = `astrea` / Core = `astrea-core`
- PHP Namespace：`Astrea\Theme`（Theme） / `Astrea\Core`（Core）
- Prefix：`astrea_` / `Astrea`
- Block Namespace：`astrea/*`

（技術識別子の正本はDecision 012を参照）
