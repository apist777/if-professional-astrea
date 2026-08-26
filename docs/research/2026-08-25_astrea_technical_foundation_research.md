# ASTREA FREE v1 技術基盤調査

- 調査日: 2026-08-25（JST）
- 担当: コデミ（Codex）
- 種別: 着工前・技術基盤監査
- 対象: WordPress標準、Block Theme / Site Editor、Theme / Core構成、ローカル開発環境、テスト / CI、Compatibility
- 制約: 実装は行わない

## 1. 結論

ASTREA FREE v1の基本方針である「Block Themeを表示基盤、ASTREA Coreを永続データと共通機能の正本とする」構成は、WordPress標準とロックイン防止の観点から妥当である。

ただし、現在は正式仕様と空の配置先だけが存在する構想段階であり、着工可能な技術契約までは確定していない。少なくとも次の6点を実装開始前にFIXする必要がある。

1. 対応するWordPress / PHP / DB / Browserの最低・推奨・CI対象バージョン
2. ThemeとCoreの依存方向、Core停止時の表示劣化方式
3. Office Profile等の正規データモデル、保存API、Schema Version、削除方針
4. CoreデータをCore Blocksへ表示する方式（Block Bindings、動的Block、Patternの使い分け）
5. Site EditorでDB保存されたTemplate / Template Part / Global StylesとTheme更新の優先順位に基づく更新方針
6. ローカル環境とCIの再現可能なコマンド、テストマトリクス、リリースゲート

現時点の判定は **条件付き着工可** である。上記のうち特に1〜5を未決定のまま実装すると、データ移行、テーマ更新、最低対応版の引き上げ、Core停止時の障害に直結する。

## 2. 調査範囲と正式仕様の読了

以下の正式仕様を全文確認した。

- `docs/specifications/00_astrea_development_constitution.md`
- `docs/specifications/01_astrea_product_plan_v0.1.md`
- `docs/specifications/02_astrea_free_v1_specification.md`
- `docs/specifications/03_astrea_free_v1_user_journey.md`

仕様から技術基盤へ課される主要要件は次のとおりである。

- FREE単体で本番運用可能
- Themeは表示、Coreはテーマ変更後も残るデータと共通機能
- WordPress Core Blocks、標準投稿、標準APIを優先
- FSE / Block Theme、Patterns、Style Variationsを基本とする
- 一度入力した情報を複数箇所で再入力させない
- Theme / Core停止・変更後もコンテンツを人質に取らない
- 問い合わせ、SEO、OGP、GA4等を未使用時には読み込まない
- 更新で既存サイトを勝手に作り替えない
- Accessibility、Responsive、Performance、Security、Migrationを完成条件に含める
- 品質確認を人間の記憶ではなく自動検査・CIへ移す

## 3. 現在地

調査時点のリポジトリは次の状態だった。

- `theme/`、`core/`、`tools/` は `.gitkeep` のみ
- `docs/research/` は本書作成前には `.gitkeep` のみ
- `README.md` と `.gitignore` は空
- `package.json`、`composer.json`、`phpunit.xml`、`phpcs.xml`、`.wp-env.json`、CI workflowは未作成
- `.git/` ディレクトリは存在するが内容がリポジトリとして成立しておらず、`git status` と `git rev-parse` は `not a git repository` で失敗
- クロエによる別の着工前監査報告書は、現在のワークスペース内では確認できなかった

したがって本書は既存実装レビューではなく、正式仕様を実装可能な技術契約へ落とすためのギャップ監査である。

## 4. WordPress標準とBlock Theme / Site Editor

### 4.1 採用判断

Block Theme採用は維持すべきである。WordPressは `/templates/index.html` を持つテーマをBlock Themeとして扱い、Templates、Template Parts、Patterns、Style Variations、`theme.json` を標準構造としている。ASTREAの表示責任をこの構造へ寄せれば、独自ページビルダーやShortcodeを避けられる。

Themeの最小責任は次を基本とする。

- `style.css`（Themeメタデータ）
- `templates/index.html` を含むTemplate階層
- `parts/` のHeader / Footer等
- `patterns/` の挿入用Pattern
- `theme.json` version 3を中心とするDesign Token / Global Styles
- `styles/` のTrust / Natural / Modern等のStyle Variation
- Theme固有で必要最小限のCSS / PHP

`theme.json` はエディターとフロントの設定・スタイルを同じ基盤へ載せられるため、色、Typography、Spacing、Layoutの正本に適する。独自CSS変数と`theme.json` presetを二重管理しないことが重要である。

### 4.2 Patternはデータ正本ではない

Patternは「挿入時の構成・初期表現」として使用し、Office Profile、Price、FAQ等の正本にしてはならない。通常のPatternを挿入した後のBlock markupはページ側へ複製されるため、Coreの情報変更を自動反映できない。

役割を次のように分けるべきである。

| 用途 | 第一候補 | 理由 |
|---|---|---|
| 静的なレイアウト雛形 | Theme Pattern + Core Blocks | WordPress標準の編集体験を維持できる |
| 事務所名・電話等の単一値 | Core登録のBlock Bindings source | 対応するCore Block属性へ標準的に動的データを接続できる |
| Service / FAQ / Price等の一覧 | Coreの動的BlockまたはQuery互換の構造 | 件数可変、空状態、関連、表示順が必要 |
| Patternごとの局所編集 | Pattern Overrides | 全体構造を共有しつつ許可した属性だけ変更できる |
| HTMLメール送信、SEO出力等 | Core Pluginのサーバー処理 | Themeの表示責任ではない |

Block Bindings APIはWordPress 6.5以降で利用でき、現行仕様ではParagraph / Heading / Button / Image等の対応属性へ外部データを接続できる。ただし全Block・全属性へ万能に適用できるわけではない。一覧、条件分岐、営業時間、構造化データ等をBindingsだけで表現しようとせず、動的Blockと使い分ける必要がある。

### 4.3 Site Editorの上書き優先順位

Block ThemeのTemplateは、ユーザーがSite Editorで保存するとDB内のユーザー版がThemeファイルより優先される。このため、Theme更新でTemplateファイルを修正しても、既にカスタマイズされたサイトへ反映されない場合がある。

これは不具合ではなく、ユーザーカスタマイズを守るWordPress標準挙動である。ASTREAは次を明文化すべきである。

- Theme更新が反映される領域と、ユーザー版が優先される領域
- 「変更をクリア」した場合の影響
- Security / Accessibility上の重大修正をDB保存済みTemplateへどう案内するか
- Pattern追加は既存ページを変更しないこと
- Template / Part / Style Variationの非推奨化手順

Themeファイルを上書きして既存サイトの見た目を強制変更する設計は、正式仕様のUpdate原則と衝突する。

### 4.4 Core Plugin不在時のTheme

ThemeはCore Pluginの有効化を前提にPHP fatalを起こしてはならない。推奨する依存方向は次である。

```text
WordPress Core
├─ ASTREA Theme（単独でも標準投稿・標準Blockを表示可能）
└─ ASTREA Core（Theme非依存で永続データと機能を提供）
   └─ Themeは存在する機能だけを表示に利用
```

Core依存Blockが無効になった場合の編集画面表示、フロントのFallback、再有効化後の復旧をテスト契約に含める。Theme側からCoreの内部クラス・DBテーブルへ直接アクセスせず、公開APIまたはBlock経由に限定する。

## 5. Theme / Core責任分界

### 5.1 妥当な責任範囲

| Theme | ASTREA Core |
|---|---|
| Design Token、Global Styles | Office Profile等の正規データ |
| Templates / Template Parts | Service / Price / FAQ / CASE / RESULTS / VOICE |
| Patterns、Style Variations | Contact処理、Nonce、Validation、Rate Limit |
| Header / Footerの構成と表示 | SEO / OGP / Search Console / GA4の設定と出力制御 |
| Blog / Archive / Search / 404の表示 | Block、Block Bindings source、REST API |
| Responsive / Accessibilityの表示責任 | Schema Version、Migration、Export / Delete |

HeaderやFooterそのものはThemeだが、そこへ表示する電話番号等の値はCoreである。この区別を「画面単位」ではなく「データ・処理・表示」の3層で定義する必要がある。

### 5.2 データモデル上の未決定事項

正式仕様は管理対象を定めているが、保存単位は未決定である。着工前に少なくとも次を設計する必要がある。

- 単一サイト設定（Office Profile、CTA、GA4等）をOptions、Settings API、独自Tableのどれで保持するか
- 複数件データ（Service、FAQ、Price、CASE等）を標準投稿タイプ / Taxonomy / Post Metaで持つか
- 関連付け、表示順、公開状態、翻訳、多拠点、Multisiteをv1でどこまで扱うか
- REST schema、権限、Sanitization、default、nullable、将来追加時の互換性
- Schema Versionと冪等なMigration
- Plugin停止、アンインストール、完全削除、Exportの境界

WordPress標準投稿・Meta・Taxonomyで表現できるものを優先し、独自Tableはクエリ量・整合性・拡張要件を根拠に必要性を証明できた場合に限定する。独自Tableを選ぶとMigration、Export、権限、REST、バックアップの保守範囲が増える。

### 5.3 SEOとWordPress標準の共存

XML SitemapはWordPress標準を第一候補とし、ASTREA Coreが別Sitemapを無条件出力しない。title、canonical、robots、OGP、構造化データも、他SEO Pluginの存在を単なるPlugin名リストだけで判定すると保守不能になりやすい。

着工前に次を定義する。

- WordPress標準へ委譲する項目
- ASTREAが補完する項目
- 他Plugin検出時に停止する出力単位
- Filter / Actionによる外部Pluginとの統合点
- 無効化しても投稿本文や表示が壊れないこと

## 6. Compatibility方針

### 6.1 調査時点の外部条件

2026-08-25時点のWordPress公式最新リリースは7.1（2026-08-19）。WordPress 7.0以降のサーバー最低要件はPHP 7.4以上で、公式互換表ではWordPress 7.1がPHP 7.4、8.0〜8.5に対応している。一方、WordPress Hosting Teamは本番環境にPHP 8.4以降を推奨している。

「WordPressが動作可能な最低PHP」と「ASTREAが製品として保証する最低PHP」は別決定である。古いPHP対応を広げるほどSecurity、型、依存ライブラリ、テストコストの負担が増える。

### 6.2 推奨する暫定マトリクス

公開予定が2027年であるため、今ここで永久FIXせず、開発開始時とRelease Candidate時に再決定する前提で次を暫定基準とする。

| 軸 | 暫定案 | CIでの扱い |
|---|---|---|
| WordPress | 最低7.0、推奨・最新7.1 | 最低、最新安定、次期RC/nightly |
| PHP | 最低8.1候補、推奨8.4、最新8.5 | 最低×最低WP、推奨×最新WP、最新PHP×最新WP |
| DB | WordPress公式要件を下限とし、MySQL / MariaDB双方を保証範囲として再検討 | 採用した保証範囲を少なくとも各1系統 |
| Browser | WordPressのBrowser supportとASTREAのAccessibility要件を基準 | Chromium自動 + Firefox/WebKitの主要E2E |
| Multisite | v1保証対象か明示 | 対象なら別ジョブ、対象外でも誤動作しない確認 |

最低WordPress 7.0は暫定案であり、正式決定ではない。Block Bindingsの編集UIや新APIをどこまで前提にするか、想定ユーザーのホスティング分布、2027年公開時点の保守期間を材料に決めるべきである。

### 6.3 互換性契約に含めるもの

- `Requires at least`、`Tested up to`、`Requires PHP` の整合
- Theme / CoreのVersion互換表と古い組み合わせでの管理画面警告
- WordPress major Beta / RC期間の先行検証
- PHP deprecation / warningを含む`WP_DEBUG`、`SCRIPT_DEBUG`での検査
- Locale、Timezone、Permalink、Multisite、REST無効化・Cron遅延等の環境差
- Core無効化、Theme変更、再有効化、Upgrade、Downgrade、Migration再実行
- 主要な外部SEO / Form Pluginとの共存範囲
- Site Editorでカスタマイズ済みのUpgrade回帰

## 7. ローカル開発環境

### 7.1 推奨構成

公式`@wordpress/env`（wp-env）を主たる再現環境にする案が妥当である。ThemeとCoreを同一WordPressへmountでき、WordPress / PHP version、設定、WP-CLI、PHPUnit用環境を構成できる。

ただし「開発者の手元で動く環境」と「互換性マトリクス」は分ける。

- 日常開発: wp-env + Docker、固定した推奨WordPress / PHP
- 自動テスト: CI matrixで最低 / 最新 / 次期版
- 軽量Preview: WordPress Playground / Blueprint
- 実Hosting差: Release Candidateで実サーバー相当のMySQL / MariaDB、メール、HTTPS確認

PlaygroundはPR PreviewやWordPress / PHP差分の軽量検証に有効だが、WebAssembly / SQLite等の実行差があるため、MySQL / MariaDB、メール配送、Cron、ファイル権限、Web server差を含む唯一の合格環境にはしない。

### 7.2 再現性の条件

着工時に次をリポジトリへ固定し、READMEから単一の入口で実行可能にする。

- Node / npmとComposerの対応版（lockfileを含む）
- `.wp-env.json` と必要ならテスト専用config
- Theme / Coreのmountと自動有効化手順
- Seed data、Theme Unit Test Data、メディア、各件数0 / 1 / 多数のfixture
- 開発、lint、unit、integration、e2e、build、packageの各コマンド
- `WP_DEBUG`、メール捕捉、Cron、HTTPS相当の確認方法
- 秘密情報をcommitしない`.env.example`相当の境界

## 8. テスト / CI基盤

### 8.1 必須レイヤー

| レイヤー | 主な対象 | リリースゲート例 |
|---|---|---|
| Static | PHP / JS / CSS / JSON / i18n | WPCS / PHPCS、ESLint、Stylelint、Prettier、JSON schema |
| WordPress適合 | Theme / Plugin配布要件 | Theme Check、Plugin Check、配布ZIP検査 |
| Unit | Sanitization、Validation、Fallback、Migration関数 | PHPUnit、JS unit |
| Integration | WordPress hooks、REST、DB、Contact、SEO競合 | WordPress test suite、実DB |
| E2E | 初回案内、Site Editor、設定、送信、Theme変更 | Playwright系のブラウザテスト |
| Visual / Responsive | Pattern、件数差、Viewport、Style Variation | 基準スクリーンショット + 人間の承認 |
| Accessibility | Keyboard、Focus、Landmark、Form、Contrast | axe等の自動検査 + 手動検査 |
| Performance | 未使用asset、Query、CWVに影響する回帰 | Asset budget、Query / timing基準 |
| Compatibility | WP / PHP / DB / Theme-Core組合せ | Matrix job |
| Migration | 新規、旧Schema、失敗、再実行 | fixture DB + 冪等性検査 |

Theme CheckとPlugin CheckはWordPress.org向け適合性・Best Practiceの検出に有用だが、製品仕様の正しさ、Accessibility全体、視覚品質、Migration、安全なメール送信を保証するものではない。これらだけを「テスト済み」の根拠にしてはならない。

### 8.2 ASTREA固有の重要シナリオ

- Core情報変更がHeader / Footer / CTA / Access等へ一度で反映される
- Core情報が未入力でも空のラベルや壊れた構造を出さない
- Service / FAQ / Price等が0件、1件、10件以上でも成立する
- Theme変更後もCoreデータが残り、ASTREA固有Shortcode残骸を作らない
- Core停止でfatalせず、再有効化でデータが復帰する
- Site EditorでTemplateを変更したサイトをTheme更新しても意図しない上書きをしない
- SEO Plugin併用時にtitle / canonical / OGP / schemaを二重出力しない
- GA4未設定時は関連script・通信を出さない
- ContactはNonce、Honeypot、Validation、Sanitization、Escaping、Rate Limitを確認し、原則DBへ本文を保存しない
- メール送信失敗を成功画面として扱わない
- Keyboardのみ、200% zoom、reduced motion、High Contrast相当で主要導線を完遂できる
- RTL、日本語、長い事務所名・住所・料金、画像なしでも破綻しない

### 8.3 CI運用

- Pull Request: 高速なStatic、Unit、主要Integration、主要E2E
- main: 全Matrix、Accessibility、Visual、Package検査
- 定期実行: WordPress nightly / 次期RC、最新依存、PHP最新への先行検査
- Release: clean checkoutからbuildし、Theme / Core別ZIP、checksum、Version整合、smoke installを検証
- 失敗を`allow-failure`にする場合もIssue化と期限を必要とする

## 9. 着工前の技術判断リスト

### P0（実装前に必要）

1. WordPress / PHP最低対応版と、その選定理由
2. Theme / Core公開slug、namespace、text domain、versioning規約
3. CoreデータモデルとSchema Version / Migration / Delete契約
4. Block Bindings / 動的Block / Patternの表示戦略
5. CoreなしでのTheme、Theme変更後のCoreという依存契約
6. Site Editor保存データとTheme更新の扱い
7. ローカル環境、CI provider、最小テストマトリクス

### P1（最初の縦切り実装前）

1. Settings / REST / Capability / Nonceの共通方針
2. Asset enqueueと未使用機能を読み込まない方針
3. SEO競合停止の契約
4. Accessibility / Visual / Performanceの合格基準
5. Package生成とWordPress.org配布を想定した除外ファイル

### P2（Release Candidate前）

1. 2027年時点のWordPress / PHP / Browser要件再調査
2. 実Hosting、MySQL / MariaDB、メール配送確認
3. Beta / RC Compatibility運用とSecurity response手順
4. Upgrade / Downgrade / Uninstall / Exportの通し試験

## 10. クロエの着工前監査と分けた視点

本書は、画面・機能単位の実装可否や施工順ではなく、全機能が依存する横断的な技術契約を監査対象とした。

- WordPress標準へ委譲できる境界
- FSEのDB上書き優先順位がUpdateへ与える影響
- Patternと動的データを混同しない表示方式
- Theme / Coreの依存方向と停止時の回復性
- 対応版を宣言するためのCompatibility matrix
- 開発者の記憶に依存しない再現環境・CI・Release gate

なお、調査時点ではワークスペース内にクロエの監査報告書自体が存在しなかったため、文書間の逐語的な差分比較は行っていない。報告書追加後に重複・矛盾を比較する余地がある。

## 11. 推奨する次の工程

実装へ入る前に、P0項目だけを扱う「技術基盤仕様」を正式仕様としてFIXする。その後、Office Profileの単一値をCoreへ保存し、ThemeのCore Blockへ表示する最小の縦切りを、Migration・停止・Theme変更・Accessibility・E2Eまで含めて検証するのが安全である。

本調査では仕様書を変更しておらず、Theme / Coreの実装も行っていない。

## 12. 参照した一次資料

- [WordPress 7.1 release](https://wordpress.org/news/)（2026-08-25確認）
- [PHP Compatibility and WordPress Versions](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/)
- [WordPress Compatibility / Server requirements](https://make.wordpress.org/hosting/handbook/compatibility/)
- [Server Environment](https://make.wordpress.org/hosting/handbook/server-environment/)
- [Theme Structure](https://developer.wordpress.org/themes/core-concepts/theme-structure/)
- [Templates and database precedence](https://developer.wordpress.org/themes/templates/templates/)
- [Introduction to theme.json](https://developer.wordpress.org/themes/global-settings-and-styles/introduction-to-theme-json/)
- [Global Settings & Styles / theme.json](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)
- [Style Variations](https://developer.wordpress.org/themes/global-settings-and-styles/style-variations/)
- [Block Bindings API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/)
- [Get started with wp-env](https://developer.wordpress.org/block-editor/getting-started/devenv/get-started-with-wp-env/)
- [WordPress Playground testing](https://developer.wordpress.org/playground/handbook/about/test/)
- [Theme testing](https://developer.wordpress.org/themes/advanced-topics/testing/)
- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [Plugin Check / Helper Plugins](https://developer.wordpress.org/plugins/developer-tools/helper-plugins/)
- [Theme Check](https://github.com/WordPress/theme-check)

