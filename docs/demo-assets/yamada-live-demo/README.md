# やまだ行政書士事務所 — LIVE DEMO Local Rebuild — Reproducibility Package

Construction 023（2026-09-08、ローカル構築）／023-A（画像仕様確定）／023-B（2026-09-10、正式画像組み込み）の成果物。Construction 019の教訓（環境を使い捨てて何も残らなかった）を繰り返さないための再現可能パッケージ。

**現在の状態: 正式画像組み込み済み（placeholderは使用していない）。**

## 内容物

- `yamada-demo-export.wxr` — WordPress標準の`export_wp()`によるフルコンテンツエクスポート（32 items: 全ページ・全CPT投稿・**正式画像2点を含む添付ファイル**のメタデータ）。Construction 023-Bで正式画像組み込み後の状態に更新済み。秘密情報は一切含まれていない。
- `images/` — **正式デモ画像（Owner供給、最終版）**
  - `astrea-demo-yamada-professional-portrait.png`（1616×973、生成原本）
  - `astrea-demo-yamada-case-01-construction-permit.png`（1774×887、生成原本）
  - `images/web/` — WordPress投入用に最適化したWeb配信版（センタークロップ＋JPEG q85変換、原本は無変更）
    - `astrea-demo-yamada-professional-portrait.jpg`（1.7:1へクロップ、約148KB）
    - `astrea-demo-yamada-case-01-construction-permit.jpg`（2:1へクロップ、約176KB）
  - `astrea-demo-yamada-case-01-construction-permit (2).png:Zone.Identifier` — OS側の副産物（対応する実データファイルは存在しない、無害・末使用。詳細はConstruction 023-B報告書§を参照）
- `placeholder-images/` — Construction 023時点で使用していた**旧プレースホルダー画像**（現在は不使用、履歴として保持）。
  - `yamada-professional-portrait.png`（900×1200、3:4——実際の表示コンテナ比率1.7:1とは不一致だったことが023-Aで判明）
  - `case-1-image.png`（1200×800）
- `scripts/` — 構築に使用したPHPスクリプト一式（WordPress自身のpost/postmeta/option APIおよびASTREA Core自身のSetup関数のみ使用。生SQL不使用）。
  - `theme-core-activation-blueprint.json` — Theme/Core有効化用のWordPress Playground Blueprint
  - `build-content.php` — Office Profile・Professional・Service・Case・Result・Price・FAQ・Voice・Setup（pages/navigation/home）の投入
  - `polish.php` — 事務所概要ページの紹介文・営業時間の追加投入
  - `fix-placeholders.php` — （履歴）プレースホルダー画像のラベル修正
  - `cleanup.php` — WordPress既定のサンプルコンテンツ削除
  - `permalinks.php` — パーマリンク構造設定
  - `integrate-final-images.php` — **正式画像2点をWeb最適化JPEGへ変換し、WordPress添付として組み込み、Featured Imageを切り替えるスクリプト（Construction 023-B）**

## 再現手順（将来のVPS移植時の想定）

1. 新規WordPress環境（VPS側は`demo-product-subpath-bootstrap.sh astrea`で用意する想定、別Order）にASTREA Theme v1.0.2 / Core v1.0.1を正規artifactから導入する。
2. `wp-admin`の「ツール > インポート > WordPress」から`yamada-demo-export.wxr`をインポートする（WordPress標準のインポーター。**手動SQL流し込みは行わない**）。
3. `images/web/`の2点のJPEGを、対応する投稿（代表者プロフィール／対応事例#1）にアイキャッチ画像として再アップロード・再設定する（`integrate-final-images.php`と同じロジックで自動化も可能）。
4. ASTREA Core管理画面の「事務所情報」から、事務所名・住所・電話番号・営業時間を`build-content.php`/`polish.php`の値と同じ内容で再入力する（Office Profileはpostmeta/optionの一部でありWXR単体エクスポートには含まれないため、この手順のみ手動再設定が必要）。
5. パーマリンク設定を保存し直してリライトルールを再生成する。
6. サイトのURLをVPS上の実際のURL（`https://demo.project-if.jp/astrea/`）に合わせてWordPress標準の設定（一般設定のサイトURL）を更新する。**独自のSQL文字列置換は行わない**（シリアライズされたデータを壊すリスクがあるため）。URL変更が必要な場合はWP-CLIの`search-replace`（シリアライズを理解する安全なコマンド）を使用する。

## 除外したもの（意図的）

- ローカル環境のadmin/DBパスワード、salt、認証トークン — 一切保存していない。
- Docker/Playgroundのボリューム・コンテナ自体 — 保存していない（本パッケージだけで再現可能な設計）。
