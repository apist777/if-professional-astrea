# Construction Order 016G — ASTREA FREE v1 Final Release Candidate / Pre-Release Audit

## 0. Purpose・スコープ

Construction 016G は新しい施工ではなく、既にOwner Visual Acceptanceを通過したASTREA FREE v1（Theme）+ ASTREA Core（Plugin）を、Final Release Candidateとして安全にRelease判定できる状態まで検証する**竣工検査**である。§1のAbsolute Freezeに従い、Visual v3・Internal Pages・各Owner-approvedページの再設計は一切行っていない。変更を加えたのは、本Orderが明示的に許可する7分類（Release Blocker／明確なバグ／Security／Accessibility failure／WordPress.org requirement／Release metadata・packaging／Owner明示承認）に該当すると判断した、以下の限定的な項目のみである。

## 1. Tested Environment

- Tested commit: `4073cc2`（本Order開始時点、016F-R1確認済み）を起点に、本Order自身の変更を加えたワーキングツリー
- Theme version: 1.0.0-rc1（無変更）
- Core version: 1.0.0-rc1（無変更）
- WordPress: 7.1（`Tested up to`と一致）
- PHP: 8.3.33（`Requires PHP: 8.3`, Decision 020と一致）

## 2. Known Issues 再評価結果

| # | 項目 | 再現するか | 判定 | 対応 |
|---|---|---|---|---|
| A | Price List HOME limit | **再現した**（実測：Price 10件でHOME「料金」セクションが3行に伸長し、Service/CASE/VOICE/FAQの既存4Blockと異なりTeaser原則が崩れることを確認） | **Release Blocker** | 修正済み（§3） |
| B | CPT Archive og:url | 再現しない（Service/Professional/CASE/VOICE/FAQ Archiveすべてで自身のURLを正しく返すことを実測確認） | Post-v1（既に解消済み） | 対応なし |
| C | Professional Archive empty excerpt | 空Excerptは`<p>`ごと綺麗に消え、視覚崩れ・空タグ残留なし（実機確認） | Visual/semantic defectなし、Post-v1 | 対応なし |
| D | Search breadcrumb | 「アーカイブ」という汎用ラベルを表示（誤表示ではないが非specific） | 誤表示ではない、Post-v1 | 対応なし |
| E | Price Group | 各Itemの個別ラベルとして正しく表示、並び替え機構への拡張なし | 現状のまま許容、Post-v1 | 対応なし |

### Known Issue A の詳細（唯一のRelease Blocker）

`astrea/price-list` Dynamic Blockには、`astrea/service-list`・`astrea/case-list`・`astrea/voice-list`・`astrea/faq-list`が全て備えている`limit`属性が存在せず、HOME Patternの呼び出し（`home-price.php`）も`limit`を渡していなかった。実測（Price 10件を一時的に作成→HOME確認→削除）で、HOME「料金」セクションが3行（4+4+2）に伸長し、直下の「まずはお気軽にご相談ください」CTA以下が大きく押し下げられることを確認した。これは他の4つのTeaser Blockが一貫して`limit:3`で自身を制御しているのと明確に矛盾する、確認可能な実装漏れである。

## 3. 実施した修正

### 3.1 `astrea/price-list` Dynamic BlockへのLimit属性追加（Release Blocker修正）

- `core/includes/price-list-block.php`: 他4 Sibling Blockと全く同じ規約（`limit`属性、type:number、default:0=無制限、`array_slice()`）で`limit`属性を追加。
- `theme/patterns/home-price.php`: `{"limit":4,"heading":"料金"}`（他Teaserの`limit:3`ではなく、既存のCompact 4-columnグリッドの列数および現行Fixtureの実件数(4)に合わせて`4`を採用——Owner承認済みHOMEの現在のPixelを一切変えないための意図的な選択）。
- `tests/PriceTest.php`: `test_price_list_block_respects_limit()`を新規追加、`service-list`の同名テストと同じパターン。
- **Fixtureへの反映**: `home-price.php`はSetup時にPage 1914（現在のHOME固定ページ）へ一度だけコピーされる仕組みであり、Pattern側の修正だけでは既存インストール（本開発環境含む）のHOMEには反映されない（WordPress標準の挙動）。本開発環境のPage 1914のpost_contentへ同じ`"limit":4`を直接反映し、実際にPrice 10件でも4件に制限されること、既存4件のFixtureでは1行4件のまま無変更であることを実機確認した。
- 専用のPriceページ（`price-list.php` Pattern、`limit`なし）は無変更——引き続き全件表示。

### 3.2 `includes/data-deletion.php`のABSPATH Guard（調査の結果、修正不要と判断）

WordPress.org公式Plugin Checkツールが「Missing direct file access protection」を報告したが、直接確認したところ56行目に`if ( ! defined( 'ABSPATH' ) ) exit;`が正しく存在していた（Namespace宣言+約20行の`use`文の後という、プロジェクトの既存規約どおりの配置）。**Plugin Checkの誤検知（False Positive）**と判断し、コード変更は行っていない。

### 3.3 `core/readme.txt`のShort Description / Description英語化（WordPress.org要件）

Plugin Checkが「readme短い説明文・説明文が非公式言語（英語以外）」を報告（[2025年7月のWordPress.org方針](https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/)、Plugin Directory向け）。該当するShort Description 1行とDescriptionセクション全体を英語へ翻訳した（Installation/FAQ等の他セクションは対象外のため無変更）。**Theme側の`theme/readme.txt`は対象外**——Theme Check（後述）が同一内容を含めて完全にPASSしており、この方針はPlugin Directory向けであり Theme Directoryには適用されないことを確認したため、Theme側は日本語のまま変更していない。

## 4. WordPress.org Gate

### 4.1 Theme Check（公式Plugin、`theme-check` 20260901、検証専用に一時インストール→検証後アンインストール済み）

**"ASTREA passed the tests"** — 5542 tests実行、**REQUIRED 0件、WARNING 0件**。INFO 1件のみ（"Only one text-domain is being used... astrea" — Text Domainがtheme slugと一致しているという正常な情報通知、対応不要）。

### 4.2 Plugin Check（公式Plugin、`plugin-check` 2.1.0、同様に検証専用インストール→アンインストール済み）

| 種別 | 件数 | 内訳 |
|---|---|---|
| ERROR | 3 | readme.txt英語化要件（**修正済み**、§3.3）／`.gitkeep`隠しファイル（**実際のRelease ZIPには含まれないことを確認**、§4.3）／data-deletion.php ABSPATH Guard（**誤検知、コード確認済み**、§3.2） |
| WARNING | 1 | `load_plugin_textdomain()`非推奨（**Deferred判断**、下記） |

`load_plugin_textdomain()`のWARNINGについて：このアドバイスは「WordPress.orgでHostされているPluginの場合」翻訳の自動読み込みが働くために不要になる、という条件付きのものである。ASTREA Coreは現時点でWordPress.orgにHostされておらず、直接ZIP配布を前提としているため、この呼び出しを今削除すると**現在の配布形態でi18nが機能しなくなる**。WordPress.org Hosting開始時に再評価すべき事項として、Deferred（LOW、Post-v1）と判断し、削除しなかった。

### 4.3 隠しファイル・開発ファイルのRelease ZIP混入確認

`.gitkeep`（`core/languages/`, `theme/languages/`, `theme/parts/`）はリポジトリでは意図的に保持しているが、既存の`tools/release/package.sh`が`rsync --exclude .gitkeep --exclude .git* ...`で既にPackaging時に除外する設計になっていることを確認した。実際に生成したRelease ZIP（§9）を展開して`.gitkeep`・`.git*`・`node_modules`・`*.log`・testファイルの混入が無いことを実機確認済み——Plugin Checkの`.gitkeep`指摘は、Plugin Checkが（Packaging前の）Source Directoryをそのまま検査したことによるものであり、実際に配布されるZIPには影響しない。

### 4.4 その他

- Requires PHP: 8.3 / Requires at least: 7.0 / Tested up to: 7.1 はTheme・Core双方のヘッダーで一致（Decision 020準拠、無変更）。
- License: GPLv2 or later、License URIともTheme・Core双方に明記済み、無変更。
- `readme.txt`・`screenshot.png`ともTheme・Core双方に存在確認済み。

## 5. Security Gate

- `includes/data-deletion.php`: メニュー登録・ページ描画・削除ハンドラーの3箇所すべてで`current_user_can('manage_options')`を確認（多層防御）。削除ハンドラーは`check_admin_referer()`によるNonce検証も実施。
- `includes/contact-form-block.php`: Nonce検証（`wp_verify_nonce`）、Honeypotによるスパム対策、全入力フィールドに`sanitize_text_field`/`sanitize_textarea_field`を適用していることをコード確認。
- Release ZIP内のSecret Scan（AWSキーパターン・秘密鍵ブロック・平文パスワード/APIキー疑わしきパターン）を実施、該当なし。SQL/ログ/バックアップファイルの混入もなし（§4.3であわせて確認）。
- PHPCS（WordPress Coding Standards + PHP Compatibility 8.3-）: 本Orderで変更した2つのPHPファイル（`price-list-block.php`、`home-price.php`）を個別に実行し、Warning/Error 0件を確認。

## 6. Accessibility Final Gate（サンプル確認）

- Skip Link: `#wp--skip-link--target`へのリンクと、`<main id="wp--skip-link--target">`という実際のTarget実装の両方の存在を実機確認。
- H1数・見出し階層・パンくずSemantics・Focus可視化・Form Label/Error・画像alt・Contrast・モバイルNavigation・404復帰リンクのAccessible Nameは、Construction 016F/016F-R1にて13ページ横断で既に詳細確認済み（本Orderでは対象ページのVisualを一切変更していないため再確認は不要と判断し、Skip Link/Targetという未検証だった項目のみ本Orderで新規に確認した）。

## 7. SEO Final Gate

- `wp-sitemap.xml`: HTTP 200で応答。
- `robots.txt`: `/wp-admin/`をDisallow、`admin-ajax.php`のみAllowという WordPress標準の内容、Sitemapへの参照あり。
- Organization JSON-LD: `name`/`url`/`address`/`telephone`/`openingHoursSpecification`/`employee`（Person、`name`/`jobTitle`/`description`/`image`）を実データで確認。
- BreadcrumbList JSON-LD: Service Single等で`@type:BreadcrumbList`/`ListItem`の存在を確認。
- **禁止事項の再侵入なし**: FAQPage自動Schema・Offer自動Schema・非推奨ProfessionalService型は、HOME/FAQ/Price/Service Singleいずれにも一切出力されていないことを確認。
- GA4: 未設定時はタグ非出力（`googletagmanager`0件）を確認。測定ID設定時（`G-TEST1234`で一時検証）にタグが正しく出力されることを確認し、直後に設定を削除して元の状態（未設定）へ復元、復元後も0件であることを確認。
- Search Console verification meta: 既存の`astrea_core_seo_settings`経路で出力される実装（Construction 016F以前に確認済み、無変更）。
- SEO Plugin共存（Yoast SEO等検知時の重複回避ロジック）はコード上に既存（無変更、本Orderでは追加検証せず、既存実装の再確認のみ）。

## 8. Full Functional Regression（§3）

### 8.1 自動テスト

- PHPUnit: **398/398**（本Orderで1件テスト追加）、既知の3件のエラー（`SeoMetaTest`×2、`SetupTest`×1、`wp-phpunit`のattachment factory内`Undefined array key "file"`）はConstruction 016F/016F-R1で確認済みのPre-existingな環境問題であり、本Orderでも同一に発生することを確認、本Order由来ではない。
- PHPCS: 本Orderで変更した2 PHPファイルはWarning/Error 0件（§5）。

### 8.2 smoke-test.sh — ローカル実行の限界とCIによる補完

`tools/ci/smoke-test.sh`はCI（`.github/workflows/*.yml`の`theme-core-smoke-test`ジョブ）で常に新規（Professional等のFixtureが一切存在しない）wp-env上で実行されており、本Orderの2 Commit（後述）を含め、このセッション内の全Push で継続してGreenであることを確認済み。

ローカルのwp-env（本プロジェクトの長期利用Fixtureを保持、Professional 3名が実データとして常駐）では、以下を実測した:

- Backup（`astrea_core_office_profile`オプションを事前保存）を取った上で、実在する3名のProfessional写真を一時的に`_thumbnail_id`を削除して除去し、smoke-test.shをノンストップで実行したところ、Step A〜Nまで（Theme/Core独立性、Office Profile、Professional Archive並び順、Featured Image有/無/破損の3パターン）すべてOKとなった。
- Step O/P（`get_profiles()`が「ちょうどN件」であることを期待するテスト）で、本環境に既存する実Professionalデータとの重複によりFAILしたが、これは**新規に作成されたテストデータ数のみを期待し、環境にあらかじめ存在するProfessionalデータの数を考慮しないテストコード側の前提**によるものであり、CI（常に空の環境）では発生しない、ローカル長期利用環境固有の制約であると判断した。テストコードの「サイレント修正」（Order §12で明示的に禁止）は行わず、以降のSection（Service/CASE/FAQ/Price/Contact/retention/GA4/SEO等）はsmoke-test.sh単体では検証せず、代わりに§9のClean Install検証と本Report各所の個別実測で代替した。
- 実行後、Step L/M/Nで作成されたテスト用Professional（Alpha/Bravo/Charlie/Draft Smoke）とAttachmentを削除、3名の実写真の`_thumbnail_id`を復元し実機で写真表示を確認、`astrea_core_office_profile`オプションをBackupから復元しdiff一致・実サイト表示（「ASTREA行政書士事務所」）を確認した。Construction 016Fで発生した復旧漏れ事故を、今回も再発させていない。

### 8.3 Clean Install検証（実際のRelease ZIPから、真にゼロから）

§9で構築したRelease ZIP 2本を使い、本セッション専用の使い捨てDocker環境（WordPress 7.1 + MariaDB、この開発を通して一度も使われていない完全新規DB）へ以下を実施、いずれもFatal/Warning/Notice無しでHTTP 200を確認した:

1. **Theme onlyインストール**: `wp theme install <ZIP> --activate`→HOME正常表示。
2. **Theme + Coreインストール**: `wp plugin install <ZIP> --activate`→HOME正常表示。
3. **ASTREA Setup相当の一連の関数呼び出し**（`generate_pages()`/`generate_home_page()`/`generate_navigation()`）: 事務所概要・料金・お問い合わせの3 PageがDraftとして生成（意図的な設計——Setup直後は公開前提とせず、Owner確認後に公開する仕様であることをコードで確認）、Navigationも生成。3 Pageを公開状態に変更後、HOME/3 Page全てHTTP 200・エラー無しを確認。
4. **Dynamic Blocksの実データ描画**: Service/Professional/FAQをそれぞれ1件新規作成し、Archive（`/services/`, `/professionals/`, `/faq/`）に正しく反映されることを確認。
5. **お問い合わせフォーム**: Contact PageにForm Blockが正しく描画されることを確認。
6. **Core OFF→ON**: `wp plugin deactivate/activate astrea-core`、いずれもHOMEがHTTP 200・エラー無し、再有効化後にService等のデータが復元表示されることを確認。
7. **完全削除**: `delete_all_core_data()`を実行、作成した3 CPT投稿（Service/Professional/FAQ）が削除される一方、Setup生成の3 Page（事務所概要/料金/お問い合わせ）とHome Pageは明記された仕様どおり削除されず保持されることを確認（Decision 019・data-deletion.phpのdocblock記載どおり）。削除後もArchiveページ（0件状態）がHTTP 200でFatalなく表示されることを確認。

検証後、この使い捨て環境（Container・Networkとも）は完全に破棄済み。本プロジェクトの既存開発環境には一切影響していない。

## 9. Packaging Dry Run

`tools/release/package.sh`を実行し、以下2ZIPとチェックサムを生成した（`dist/`はGit管理外の既存方針を維持、本Reportにのみ記録）：

```
dist/astrea-theme-1.0.0-rc1.zip   (root: astrea/, 63 files)
dist/astrea-core-1.0.0-rc1.zip    (root: astrea-core/, 61 files)
dist/SHA256SUMS.txt
  4db3a8671370b16277dc897abd4b70a9ed738f736c828ea90161ca953012f402  astrea-theme-1.0.0-rc1.zip
  19676188162a62ad0463e429fb5f9482bb53dbf1245780bbdaebc2a70156d4cd  astrea-core-1.0.0-rc1.zip
```

- ZIP展開後のRoot Directoryが`astrea/`・`astrea-core/`であることを確認（WordPress標準の期待どおり）。
- `.git*`・`.gitkeep`・`.DS_Store`・`node_modules`・`*.log`・テスト/開発ファイルの混入なし（§4.3）。
- Secret Scanで機密情報の混入なし（§5）。
- §8.3のClean Install検証は、この2ZIPそのものを使用して実施した。

## 10. Version Policy

`1.0.0-rc1`のまま維持した。`1.0.0` Finalへのversion変更・git tag・GitHub Releaseはいずれも実施していない（§15 Prohibited、Owner承認後の別工程）。

## 11. 変更ファイル一覧

- `core/includes/price-list-block.php`（`limit`属性追加、Release Blocker修正）
- `theme/patterns/home-price.php`（`"limit":4`を追加）
- `core/readme.txt`（Short Description / Description英語化）
- `tests/PriceTest.php`（`limit`のRegression Test追加）
- Fixtureのみの変更（Git管理外）: Page 1914（HOME）のpost_contentへ`"limit":4`を反映
- 新規: 本Report、`docs/research/screenshots/016g/`（Price limit stress test、Empty excerpt確認、Theme Checkスクリーンショット等）
- 新規（Git管理外、既存方針どおり）: `dist/astrea-theme-1.0.0-rc1.zip`、`dist/astrea-core-1.0.0-rc1.zip`、`dist/SHA256SUMS.txt`

## 12. Deferred / Post-v1 Findings（正直な記録）

- `load_plugin_textdomain()`のWARNING（§4.2）: WordPress.org Hosting開始時に再評価。
- Known Issue D（Search breadcrumbの「アーカイブ」という汎用ラベル）: 誤表示ではないため現状維持。
- Known Issue E（Price Group）: 現状のまま、並び替え機構等への拡張は本Orderのスコープ外。
- smoke-test.shのStep O/P以降がローカル長期利用環境で完走できない制約（§8.2）: テストコード側の前提（新規環境を仮定）に起因、CI側は継続してGreen。将来、テストコード自体をローカル長期環境でも完走できるよう改善する余地はあるが、本Orderのスコープ（「無関係な製品コードのサイレント修正禁止」）を踏まえ、着手していない。

## 13. Final Recommendation

Release Blocker（Known Issue A）を修正し、WordPress.org要件（readme英語化）を満たし、Security・Accessibility・SEO・Packaging・Clean Installのいずれにも重大な問題を検出しなかった。残存するのはいずれもLOW/Post-v1に分類される軽微な既知事項のみである。

## 14. Completion Classification

## **B — FINAL RC READY WITH DEFERRED LOW**

Release Blocker = 0（Known Issue Aは本Order中に修正済み）。LOW/Post-v1のみ残存（§12）。ASTREA FREE v1.0.0 Finalへの昇格審査へ進める状態にあると判定する。

## 15. Prohibited（本Order完了時点でも未実施）

`1.0.0` Finalへのversion変更／git tag／GitHub Release／Project-if deployment／WordPress.org submission／PRO実装／新機能／Visual再設計——いずれも実施していない。

---

**Status: AWAITING OWNER FINAL RELEASE DECISION**

Construction 016G自体は「B — FINAL RC READY WITH DEFERRED LOW」の判定で完了するが、実際のVersion変更・Tag・Release・Deploy・WordPress.org Submissionは、Owner自身の承認を経る別工程として実施されない。
