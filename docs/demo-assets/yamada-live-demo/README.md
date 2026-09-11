# やまだ行政書士事務所 — LIVE DEMO Local Rebuild — Reproducibility Package

Construction 023（2026-09-08、ローカル構築）／023-A（画像仕様確定）／023-B（2026-09-10、正式画像組み込み）／025-B2（2026-09-10、Hero・Case #2・Case #3の正式画像組み込み）／025-E1（2026-09-10、Results背景画像組み込み＋ASTREA Theme 1.0.3候補）／025-E1H（2026-09-10、Hero画像markupのBlock Editor検証Hotfix）の成果物。Construction 019の教訓（環境を使い捨てて何も残らなかった）を繰り返さないための再現可能パッケージ。

**現在の状態: Hero／Representative／Case #1〜#3／Results背景、全ての正式画像組み込み済み（placeholderは使用していない）。**

> Construction 025-E1以降、Results背景の設定には **ASTREA Theme 1.0.3** 相当の`astrea/home-results-teaser`パターン（`astrea/results-list`を`core/cover`でラップした構造）が必要。1.0.2以前ではResults背景画像は設定できない。

## 内容物

- `yamada-demo-export.wxr` — WordPress標準の`export_wp()`によるフルコンテンツエクスポート（37 items: 全ページ・全CPT投稿・**正式画像6点（Hero・Representative・Case #1〜#3・Results背景）を含む添付ファイル**のメタデータ）。Construction 025-E1でResults背景組み込み後の状態に更新済み。秘密情報は一切含まれていない。エクスポート元のローカルポートURLが含まれるため、移植時はWP-CLI `search-replace` でサイトURLを置換すること。
- `images/` — **正式デモ画像（Owner供給、最終版）**
  - `astrea-demo-yamada-professional-portrait.png`（1616×973、生成原本）
  - `astrea-demo-yamada-hero-office.png`（1672×941、生成原本、Construction 025-B2で追加）
  - `astrea-demo-yamada-case-01-construction-permit.png`（1774×887、生成原本）
  - `astrea-demo-yamada-case-02-inheritance.png`（1536×1024、生成原本、Construction 025-B2で追加）
  - `astrea-demo-yamada-case-03-restaurant-incorporation.png`（1536×1024、生成原本、Construction 025-B2で追加）
  - `astrea-demo-yamada-results-background.png`（1774×887、生成原本、Construction 025-E1でResults背景として組み込み）
  - `images/web/` — WordPress投入用に最適化したWeb配信版（センタークロップ＋JPEG q85変換、原本は無変更）
    - `astrea-demo-yamada-professional-portrait.jpg`（1.7:1へクロップ、約148KB）
    - `astrea-demo-yamada-hero-office.jpg`（クロップなし・そのままJPEG変換、約188KB、Cover Blockの流動的なコンテナに合わせるため）
    - `astrea-demo-yamada-case-01-construction-permit.jpg`（2:1へクロップ、約176KB）
    - `astrea-demo-yamada-case-02-inheritance.jpg`（2:1へクロップ、約151KB）
    - `astrea-demo-yamada-case-03-restaurant-incorporation.jpg`（2:1へクロップ、約200KB）
    - `astrea-demo-yamada-results-background.jpg`（クロップなし・そのままJPEG変換、約160KB、Cover Blockのobject-fit:coverで表示側フレーミング）
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
  - `integrate-final-images.php` — 正式画像2点（Representative／Case #1）をWeb最適化JPEGへ変換し、WordPress添付として組み込み、Featured Imageを切り替えるスクリプト（Construction 023-B）
  - `integrate-025b2-hero-and-cases.php` — **Hero／Case #2／Case #3の正式画像3点を組み込むスクリプト（Construction 025-B2、025-E1Hで修正）**。Hero画像はASTREA Themeの`astrea-hero-photoplane`（`core/cover`ブロック）の標準の背景画像・オーバーレイ濃度コントロールで設定し、Case #2/#3は標準のFeatured Imageで設定する。ファイル名によるアタッチメント既存チェックを行うため**再実行しても重複アップロードしない**（冪等）。**Construction 025-E1H**: Hero Cover の保存markupをWordPress標準の`core/cover` `save()`出力と一致させる修正を実施 — `<img>`に`wp-block-cover__image-background wp-image-{id}`のみを付与し、オーバーレイの色・濃度クラスは`<span class="wp-block-cover__background ...">`側へ置く（旧版は`<img>`にオーバーレイクラスを付け`<span>`を欠落させていたため、Block Editorで「Block contains unexpected or invalid content」警告が出ていた）。クリーンリビルド後もHeroのBlock Editor検証警告0件を確認済み。
  - `make-results-web-jpeg.php` — Results背景PNG原本をq85 JPEG（クロップなし）へ変換（Construction 025-E1）。
  - `integrate-025e1-results-background.php` — **Results背景画像を組み込むスクリプト（Construction 025-E1、Theme 1.0.3候補が必要）**。`astrea/home-results-teaser`パターンが生成する`astrea-results-photoplane`（`core/cover`）へ、標準のCover Block属性（`url`／`id`／`dimRatio`＝80、`overlayColor`＝contrast）で背景画像とnavyオーバーレイを設定する。ファイル名によるアタッチメント既存チェックで**冪等**。パターン適用済み（1.0.3の無画像wrapped形）でも、旧unwrapped形（1.0.2）でも、どちらの状態からでもwith-image形へアップグレードできる。`dimRatio`はWordPressのオーバーレイ濃度スライダーの刻み（step=10）に合わせ10の倍数であること（それ以外はブロック検証に恒久的に失敗する）。
  - `fix-internal-link-portability.php` — **内部リンクを現在環境へ追従させるスクリプト（Construction 025-L1）。再現パイプラインの最後に必ず実行する。** ①生成された`wp_navigation`メニュー（Header/Footer共通）を、凍結された絶対URL（`kind:"custom"`）から参照ベース（ページ＝`kind:"post-type"`＋`id`、CPTアーカイブ＝`kind:"post-type-archive"`＋`type`）へ書き換え、`url`値を現在の`get_permalink()`／`get_post_type_archive_link()`で再生成する。②`post_content`／`postmeta`／`options`に残る他の絶対内部URL（loopback／私設LAN／固定port）を、シリアライズを理解する安全な再帰置換で現在の`home_url()`へ書き換える（外部URLは対象外）。③`home`／`siteurl`のDB行を現在の`home_url()`へ揃える。**ハードコードされたhost／port／IPは一切なし**（すべて`home_url()`／`get_option()`から実行時に導出）。冪等（既にportableな環境では何も変更しない）。

1. 新規WordPress環境（VPS側は`demo-product-subpath-bootstrap.sh astrea`で用意する想定、別Order）にASTREA Theme（Results背景を使う場合は **v1.0.3以降**、使わない場合はv1.0.2でも可）／ Core v1.0.1を正規artifactから導入する。
2. `wp-admin`の「ツール > インポート > WordPress」から`yamada-demo-export.wxr`をインポートする（WordPress標準のインポーター。**手動SQL流し込みは行わない**）。
3. `images/web/`の6点のJPEGを、対応する投稿・ブロックへ再設定する（各スクリプトと同じロジックで自動化可能。いずれもWordPress標準の添付API・Featured Image API・Cover Block属性のみを使用）。
   - Representative／Case #1 → `integrate-final-images.php`（Featured Image）
   - Hero（HOMEページのCover Block背景画像）／Case #2／Case #3 → `integrate-025b2-hero-and-cases.php`（Cover Block属性＋Featured Image、ファイル名一致による冪等実行）
   - Results背景 → `make-results-web-jpeg.php`（JPEG生成）→ `integrate-025e1-results-background.php`（Cover Block属性）。**Theme 1.0.3以降**が必要。
4. ASTREA Core管理画面の「事務所情報」から、事務所名・住所・電話番号・営業時間を`build-content.php`/`polish.php`の値と同じ内容で再入力する（Office Profileはpostmeta/optionの一部でありWXR単体エクスポートには含まれないため、この手順のみ手動再設定が必要）。
5. パーマリンク設定を保存し直してリライトルールを再生成する。
6. **`fix-internal-link-portability.php`を実行する（Construction 025-L1、再現パイプラインの最終ステップ）。** 現在の`home_url()`を基準に、Navigationメニュー・`post_content`・`postmeta`・`home`/`siteurl`の内部URLをその環境へ揃える。フルクリーンリビルドでもこのステップを最後に必ず含めること（含めれば、どのローカルURL / portで再構築しても内部リンクがその環境へ自動追従する）。
7. サイトのURLをVPS上の実際のURL（`https://demo.project-if.jp/astrea/`）に合わせてWordPress標準の設定（一般設定のサイトURL）を更新した後、再度 `fix-internal-link-portability.php` を実行する。**独自のSQL文字列置換は行わない**（シリアライズされたデータを壊すリスクがあるため）。大規模なURL置換が別途必要な場合はWP-CLIの`search-replace`（シリアライズを理解する安全なコマンド）を併用する。Navigationは参照ベース（`kind:"post-type"`/`"post-type-archive"`）なので、subpath構成（`/astrea/`配下）でも`get_permalink()`が`home_url()`基準で解決するため誤ってサイトrootへ飛ばない。

## 除外したもの（意図的）

- ローカル環境のadmin/DBパスワード、salt、認証トークン — 一切保存していない。
- Docker/Playgroundのボリューム・コンテナ自体 — 保存していない（本パッケージだけで再現可能な設計）。
