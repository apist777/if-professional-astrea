# やまだ行政書士事務所 — LIVE DEMO Local Rebuild — Reproducibility Package

Construction 023 (2026-09-08)の成果物。Construction 019の教訓（環境を使い捨てて何も残らなかった）を繰り返さないための再現可能パッケージ。

## 内容物

- `yamada-demo-export.wxr` — WordPress標準の`export_wp()`によるフルコンテンツエクスポート（30 items: 全ページ・全CPT投稿・添付ファイルのメタデータを含む）。秘密情報（パスワード・ソルト・APIキー等）は一切含まれていない（WXRの仕様上、認証情報は含まれない）。
- `placeholder-images/` — 現在使用中の2点の**プレースホルダー画像**（最終画像ではない。§7 Image Planを参照）。
  - `yamada-professional-portrait.png`（900×1200、3:4、代表者写真プレースホルダー）
  - `case-1-image.png`（1200×800、3:2、対応事例#1画像プレースホルダー）
- `scripts/` — 今回の構築に使用したPHPスクリプト一式（WordPress自身のpost/postmeta/option APIおよびASTREA Core自身のSetup関数のみ使用。生SQL不使用）。
  - `theme-core-activation-blueprint.json` — Theme/Core有効化用のWordPress Playground Blueprint
  - `build-content.php` — Office Profile・Professional・Service・Case・Result・Price・FAQ・Voice・Setup（pages/navigation/home）の投入
  - `polish.php` — 事務所概要ページの紹介文・営業時間の追加投入
  - `fix-placeholders.php` — プレースホルダー画像のラベルをASCII安全な文字列へ修正
  - `cleanup.php` — WordPress既定のサンプルコンテンツ（Hello world!／Sample Page／Privacy Policy）削除
  - `permalinks.php` — パーマリンク構造を`/%postname%/`へ設定

## 再現手順（将来のVPS移植時の想定）

1. 新規WordPress環境（VPS側は`demo-product-subpath-bootstrap.sh astrea`で用意する想定、別Order）にASTREA Theme v1.0.2 / Core v1.0.1を正規artifactから導入する。
2. `wp-admin`の「ツール > インポート > WordPress」から`yamada-demo-export.wxr`をインポートする（WordPress標準のインポーター。**手動SQL流し込みは行わない**）。
3. `wp-admin`のメディアライブラリから、`placeholder-images/`の2点を対応する投稿（代表者プロフィール／対応事例#1）にアイキャッチ画像として再アップロード・再設定する（WXRの`<wp:attachment_url>`が指す旧URLの実ファイルはこのリポジトリに同梱しているものを使う）。
4. ASTREA Core管理画面の「事務所情報」から、事務所名・住所・電話番号・営業時間を`build-content.php`/`polish.php`の値と同じ内容で再入力する（Office Profileはpostmeta/optionの一部でありWXRのpost/postmeta単体エクスポートには含まれないため、この手順のみ手動再設定が必要）。
5. パーマリンク設定を保存し直してリライトルールを再生成する。
6. §7 Image Planに基づき最終画像へ差し替える（Owner承認後）。
7. サイトのURLをVPS上の実際のURL（`https://demo.project-if.jp/astrea/`）に合わせてWordPress標準の設定（一般設定のサイトURL）を更新する。**独自のSQL文字列置換は行わない**（シリアライズされたデータを壊すリスクがあるため）。URL変更が必要な場合はWP-CLIの`search-replace`（シリアライズを理解する安全なコマンド）を使用する。

## 除外したもの（意図的）

- ローカル環境のadmin/DBパスワード、salt、認証トークン — 一切保存していない。
- Docker/Playgroundのボリューム・コンテナ自体 — 保存していない（本パッケージだけで再現可能な設計）。
