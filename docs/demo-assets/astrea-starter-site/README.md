# ASTREA行政書士事務所 — ASTREA Official Starter Site — Reproducibility Package

Construction 023（2026-09-08、ローカル構築）／023-A（画像仕様確定）／023-B（2026-09-10、正式画像組み込み）／025-B2（2026-09-10、Hero・Case #2・Case #3の正式画像組み込み）／025-E1（2026-09-10、Results背景画像組み込み＋ASTREA Theme 1.0.3候補）／025-E1H（2026-09-10、Hero画像markupのBlock Editor検証Hotfix）の成果物。Construction 019の教訓（環境を使い捨てて何も残らなかった）を繰り返さないための再現可能パッケージ。

**Construction 027（2026-09-11）で、当初の架空デモ「やまだ行政書士事務所」（代表: 山田太郎）から、ASTREA公式Starter Site原型「ASTREA行政書士事務所」（代表: 伊吹 文人）へ、identityを一般化した。** ディレクトリ名も`yamada-live-demo`から`astrea-starter-site`へ改名している。過去のConstruction 023〜025系レポート内の「やまだ行政書士事務所」「山田太郎」表記は、当時の実際の名称として歴史的記録のまま残してあり、書き換えていない。代表者名は当初「伊府 伊夫男」として実装したが、Owner Reviewを経て「伊吹 文人（いぶき ふみと、Fumito Ibuki）」へ最終決定した（同じくConstruction 027内での調整）。

**位置づけ**: 単なる「ASTREAで作れるデモ」ではなく、将来ユーザーがこの完成形をそのまま導入し、事務所名・代表者・電話番号・写真・サービス等を自分用へ置き換えるだけでサイトを完成できる、ASTREA公式のStarter Site原型（基準データ）として設計されている。Starter Site Import機能自体はまだ実装していない（Construction 027時点）。

**現在の状態: Hero／Representative／Case #1〜#3／Results背景、全ての正式画像組み込み済み（placeholderは使用していない）。**

> Construction 025-E1以降、Results背景の設定には **ASTREA Theme 1.0.3** 相当の`astrea/home-results-teaser`パターン（`astrea/results-list`を`core/cover`でラップした構造）が必要。1.0.2以前ではResults背景画像は設定できない。

## 内容物

- `yamada-demo-export.wxr` — **（既知の課題・要更新）** WordPress標準の`export_wp()`によるフルコンテンツエクスポート（37 items）。Construction 025-E1時点（旧やまだ行政書士事務所identity）でのエクスポートのまま、Construction 027のidentity変更・ファイル名変更を反映していない。ファイル名も内容も更新していない — 本パッケージの**主たる、実際にテスト済みの再現経路は`scripts/`配下のスクリプト実行**であり、このWXRはREADME §手順2に記載された代替（wp-admin GUIインポート）経路専用の副次的資産のため、Construction 027の範囲では意図的に手を加えていない。再現に使う場合は、インポート後に`scripts/`側の最新内容（office_name・Professional名・画像ファイル名）に合わせて手動修正するか、いずれ正式に再エクスポートする必要がある。
- `images/` — **正式デモ画像（Owner供給、最終版）**
  - `astrea-demo-starter-professional-portrait.png`（1616×973、生成原本。Construction 027で`astrea-demo-yamada-professional-portrait.png`から改名）
  - `astrea-demo-starter-hero-office.png`（1672×941、生成原本、Construction 025-B2で追加。Construction 027で改名）
  - `astrea-demo-starter-case-01-construction-permit.png`（1774×887、生成原本。Construction 027で改名）
  - `astrea-demo-starter-case-02-inheritance.png`（1536×1024、生成原本、Construction 025-B2で追加。Construction 027で改名）
  - `astrea-demo-starter-case-03-restaurant-incorporation.png`（1536×1024、生成原本、Construction 025-B2で追加。Construction 027で改名）
  - `astrea-demo-starter-results-background.png`（1774×887、生成原本、Construction 025-E1でResults背景として組み込み。Construction 027で改名）
  - `images/web/` — WordPress投入用に最適化したWeb配信版（センタークロップ＋JPEG q85変換、原本は無変更）
    - `astrea-demo-starter-professional-portrait.jpg`（1.7:1へクロップ、約148KB）
    - `astrea-demo-starter-hero-office.jpg`（クロップなし・そのままJPEG変換、約188KB、Cover Blockの流動的なコンテナに合わせるため）
    - `astrea-demo-starter-case-01-construction-permit.jpg`（2:1へクロップ、約176KB）
    - `astrea-demo-starter-case-02-inheritance.jpg`（2:1へクロップ、約151KB）
    - `astrea-demo-starter-case-03-restaurant-incorporation.jpg`（2:1へクロップ、約200KB）
    - `astrea-demo-starter-results-background.jpg`（クロップなし・そのままJPEG変換、約160KB、Cover Blockのobject-fit:coverで表示側フレーミング）
- `placeholder-images/` — Construction 023時点で使用していた**旧プレースホルダー画像**（現在は不使用、履歴として保持。Construction 027でもファイル名・内容とも変更していない — 当時の履歴記録のため）。
  - `yamada-professional-portrait.png`（900×1200、3:4——実際の表示コンテナ比率1.7:1とは不一致だったことが023-Aで判明）
  - `case-1-image.png`（1200×800）
- `scripts/` — 構築に使用したPHPスクリプト一式（WordPress自身のpost/postmeta/option APIおよびASTREA Core自身のSetup関数のみ使用。生SQL不使用）。Construction 027で、office_name・Professional名・画像ファイル名参照を新identity（ASTREA行政書士事務所／伊吹 文人）へ更新済み。
  - `theme-core-activation-blueprint.json` — Theme/Core有効化用のWordPress Playground Blueprint（blogname更新済み）
  - `build-content.php` — Office Profile・Professional・Service・Case・Result・Price・FAQ・Voice・Setup（pages/navigation/home）の投入（Construction 027でoffice_name／Professional名／画像ファイル名を更新）
  - `polish.php` — 事務所概要ページの紹介文・営業時間の追加投入（人物名を含まない汎用文面のため無変更）
  - `fix-placeholders.php` — （履歴）プレースホルダー画像のラベル修正。無変更（履歴のため）。
  - `cleanup.php` — WordPress既定のサンプルコンテンツ削除
  - `permalinks.php` — パーマリンク構造設定
  - `integrate-final-images.php` — 正式画像2点（Representative／Case #1）をWeb最適化JPEGへ変換し、WordPress添付として組み込み、Featured Imageを切り替えるスクリプト（Construction 023-B。Construction 027で画像ファイル名・代表者名を更新）
  - `integrate-025b2-hero-and-cases.php` — **Hero／Case #2／Case #3の正式画像3点を組み込むスクリプト（Construction 025-B2、025-E1Hで修正、027で画像ファイル名を更新）**。Hero画像はASTREA Themeの`astrea-hero-photoplane`（`core/cover`ブロック）の標準の背景画像・オーバーレイ濃度コントロールで設定し、Case #2/#3は標準のFeatured Imageで設定する。ファイル名によるアタッチメント既存チェックを行うため**再実行しても重複アップロードしない**（冪等）。**Construction 025-E1H**: Hero Cover の保存markupをWordPress標準の`core/cover` `save()`出力と一致させる修正を実施 — `<img>`に`wp-block-cover__image-background wp-image-{id}`のみを付与し、オーバーレイの色・濃度クラスは`<span class="wp-block-cover__background ...">`側へ置く（旧版は`<img>`にオーバーレイクラスを付け`<span>`を欠落させていたため、Block Editorで「Block contains unexpected or invalid content」警告が出ていた）。クリーンリビルド後もHeroのBlock Editor検証警告0件を確認済み。
  - `make-results-web-jpeg.php` — Results背景PNG原本をq85 JPEG（クロップなし）へ変換（Construction 025-E1。027で画像ファイル名・パス参照を更新）。
  - `integrate-025e1-results-background.php` — **Results背景画像を組み込むスクリプト（Construction 025-E1、Theme 1.0.3候補が必要。027で画像ファイル名を更新）**。`astrea/home-results-teaser`パターンが生成する`astrea-results-photoplane`（`core/cover`）へ、標準のCover Block属性（`url`／`id`／`dimRatio`＝80、`overlayColor`＝contrast）で背景画像とnavyオーバーレイを設定する。ファイル名によるアタッチメント既存チェックで**冪等**。パターン適用済み（1.0.3の無画像wrapped形）でも、旧unwrapped形（1.0.2）でも、どちらの状態からでもwith-image形へアップグレードできる。`dimRatio`はWordPressのオーバーレイ濃度スライダーの刻み（step=10）に合わせ10の倍数であること（それ以外はブロック検証に恒久的に失敗する）。
  - `fix-internal-link-portability.php` — **内部リンクを現在環境へ追従させるスクリプト（Construction 025-L1）。再現パイプラインの最後に必ず実行する。** ①生成された`wp_navigation`メニュー（Header/Footer共通）を、凍結された絶対URL（`kind:"custom"`）から参照ベース（ページ＝`kind:"post-type"`＋`id`、CPTアーカイブ＝`kind:"post-type-archive"`＋`type`）へ書き換え、`url`値を現在の`get_permalink()`／`get_post_type_archive_link()`で再生成する。②`post_content`／`postmeta`／`options`に残る他の絶対内部URL（loopback／私設LAN／固定port）を、シリアライズを理解する安全な再帰置換で現在の`home_url()`へ書き換える（外部URLは対象外）。③`home`／`siteurl`のDB行を現在の`home_url()`へ揃える。④Construction 025-CLOSEOUTで追加: Hero/Bottom Contact CTAボタンを現在環境のContactページへ接続する。**ハードコードされたhost／port／IPは一切なし**（すべて`home_url()`／`get_option()`から実行時に導出）。冪等（既にportableな環境では何も変更しない）。

1. 新規WordPress環境（VPS側は`demo-product-subpath-bootstrap.sh astrea`で用意する想定、別Order）にASTREA Theme（Results背景を使う場合は **v1.0.3以降**、使わない場合はv1.0.2でも可）／ Core v1.0.1を正規artifactから導入する。
2. `wp-admin`の「ツール > インポート > WordPress」から`yamada-demo-export.wxr`をインポートする（WordPress標準のインポーター。**手動SQL流し込みは行わない**）。**注意**: このWXRは上記「内容物」節の通りConstruction 027のidentity変更を反映していない旧identityのまま。新identityで再構築する場合は、下記手順3以降のスクリプトベースの経路を使うこと。
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

## Construction 027 — VPS向けアダプテーションの分類（024-R1実施時の知見）

Construction 024-R1でVPS本番環境へこのパイプラインを展開した際、WordPress Playground固有の実行方式（`runPHP`、常に`/wordpress`にWordPress本体が存在する前提）と、実VPS上の`wp-cli eval-file`実行方式との差異により、2種類の軽微なアダプテーションが必要だった。将来のStarter Site Import機能設計のため、ここに分類して記録する。

| 分類 | 内容 | 対応 |
| --- | --- | --- |
| **Playground固有** | 各スクリプト冒頭の`require_once '/wordpress/wp-load.php';` | `wp eval-file`はWordPressを実行前に既にブートストラップ済みのため、この行は実行環境側で除去する必要がある（スクリプト本体は無変更のまま、デプロイ時の作業コピーでのみ除去） |
| **Playground固有** | `/images/`・`/images/web/`への絶対パス参照 | 実VPSでは`/images`という絶対パスは存在しないため、デプロイ時に実際の転送先パスへ書き換える必要がある |
| **通常WordPress処理との差異** | `fix-internal-link-portability.php`の一部トップレベルコードが`$wpdb`をグローバル変数として暗黙に参照 | 通常の`include`実行（Playground `runPHP`）ではトップレベルコードはPHPのグローバルスコープそのものだが、`wp eval-file`は内部的に関数スコープ内でコードをevalするため、`global $wpdb;`の明示宣言が必要（該当箇所に1行追加で解消） |
| **共通化可能** | それ以外すべて（Office Profile／Professional／Service／Case／Result／Price／FAQ／Voice投入、画像アップロード・Featured Image設定、Cover Block属性設定、Navigation/Contact CTA接続ロジック）は、WordPress標準API（`wp_insert_post`／`wp_upload_bits`／`set_post_thumbnail`／`parse_blocks`/`serialize_blocks`等）のみに依存しており、実行方式（Playground `runPHP`／`wp eval-file`／将来の管理画面からのStarter Import機能）を問わず共通利用できる設計になっている | 将来のStarter Site Import機能は、この「共通化可能」部分をそのまま流用できる見込み |

（詳細な実施ログはConstruction 024-R1レポート`docs/research/2026-09-11_construction_024r1_astrea_live_demo_deployment.md`§14-5を参照。）
