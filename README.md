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
- PHP `>=8.3` と Composer（Coding Standardsチェック用。WordPress自体の実行はwp-env内のPHPを使用する）

### セットアップ

```bash
npm install
npm run env:start      # WordPressをローカルに起動（http://localhost:8888）
composer install       # PHPCS / WordPress Coding Standards / PHP Compatibility
composer run lint      # Coding Standardsチェック
```

### Theme / Core の有効化

```bash
npm run env:cli theme activate astrea
npm run env:cli plugin activate astrea-core
```

### Theme / Core 独立性チェック

ASTREA Coreは任意Plugin・公式推奨という位置付け（Decision 021）。「Themeのみ／Theme+Core／Core無効化／Core再有効化」の4状態でFatalが起きないことを自動確認する。

```bash
npm run smoke-test
```

同じ内容はGitHub Actions（[`.github/workflows/ci.yml`](.github/workflows/ci.yml)）でも実行される。

### 環境の停止

```bash
npm run env:stop       # 停止（データは保持）
npm run env:destroy    # 完全削除
```

## コーディング規約

- WordPress Coding Standards（PHPCS、`phpcs.xml`）
- Text Domain：Theme = `astrea` / Core = `astrea-core`
- PHP Namespace：`Astrea\Theme`（Theme） / `Astrea\Core`（Core）
- Prefix：`astrea_` / `Astrea`
- Block Namespace：`astrea/*`

（技術識別子の正本はDecision 012を参照）
