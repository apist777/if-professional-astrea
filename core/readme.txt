=== ASTREA Core ===

Contributors: projectif
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0-rc1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: business, custom-post-type, forms, seo, translation-ready

If Professional ASTREA — ASTREA Theme の公式推奨Plugin（任意）。事務所情報・専門家プロフィール・取扱業務・料金・FAQ・対応事例・実績・お客様の声・お問い合わせ・SEO補助・Setup補助を提供します。

== Description ==

**ASTREA Core**は、WordPress Theme「ASTREA」（If Professional ASTREA）の公式推奨Pluginです。テーマを変更しても保持すべき事務所の情報や、サイト共通の機能を担当します。

ASTREA Coreは任意のPluginです。ASTREA Themeは、Coreがインストールされていない、または無効化されている状態でも、安全に動作します（Fatal Errorを発生させません）。「Coreは推奨する。しかしThemeを人質にしない。」という方針で設計されています。

= 主な機能（FREE v1で実際に提供される機能のみを記載） =

* 事務所情報（Office Profile）の入力・表示
* 専門家プロフィール（Professional Profile）の管理・表示
* 取扱業務（Service）・料金（Price）の管理・表示
* よくある質問（FAQ）の管理・表示
* 対応事例（CASE）・実績（RESULTS）・お客様の声（VOICE）の管理・表示
* お問い合わせフォームと問い合わせデータの一時保存（保存期間：10 / 30 / 60 / 90日、初期設定30日）
* SEO補助（meta description、OGP、Organization / Person構造化データ、BreadcrumbList構造化データ、Search Console確認用メタタグ）
* 既知のSEO Plugin（Yoast SEO、All in One SEO、Rank Math、SEOPress）との重複回避
* Google Analytics 4（GA4）測定IDの登録（登録しない限り外部への計測タグは出力されません）
* ASTREA Setup（初期ページ・メニューの一括作成支援）
* 管理画面からの明示的な確認操作による、Core管理データの完全削除機能

== Installation ==

1. ASTREA Theme（本体）を先にインストール・有効化してください。
2. 「プラグイン > 新規追加 > プラグインのアップロード」から、ASTREA Core ZIPをアップロードしてインストールします。
3. インストールしたASTREA Coreを有効化します。
4. 管理メニューに追加される「ASTREA」から、事務所情報等の入力・Setup作業を行います。

= Coreを無効化・アンインストールすると何が起こりますか？ =

「Installation」欄ではなく「FAQ」欄をご確認ください。

== Frequently Asked Questions ==

= ASTREA Themeなしで使えますか？ =

いいえ。ASTREA Coreは、ASTREA Theme（If Professional ASTREA）と組み合わせて使うことを前提に設計されています。他のThemeでの動作は保証されません。

= Coreを無効化するとデータは消えますか？ =

いいえ。ASTREA Coreを無効化（停止）しても、これまでに入力したデータは保持されます。再度有効化すれば、そのまま表示・編集が再開できます。

= プラグイン一覧から「削除」するとデータは消えますか？ =

いいえ。通常の「削除」操作では、事務所情報・専門家プロフィール・取扱業務・料金・FAQ・CASE・RESULTS・VOICE・問い合わせデータ・各種設定は削除されません。安全な既定動作として、これらのデータは保持されます。

= データを完全に削除したい場合はどうすればよいですか？ =

管理メニューの「ASTREA > データ削除」から、明示的な確認操作（同意チェックボックスへのチェックと、確認文言の入力）を行うことで、Core管理下のデータを完全に削除できます。この操作は元に戻せません。

なお、この操作でも以下は削除されません（通常のWordPressコンテンツとして残ります）。

* Setupで作成した固定ページ（事務所概要・料金・お問い合わせ等）
* 作成したメニュー（Navigation）
* メディアライブラリの画像（専門家の写真、OGP画像等）

= お問い合わせデータの保存期間はどのくらいですか？ =

10日・30日・60日・90日から選択でき、初期設定は30日です。保存期間を過ぎた問い合わせは自動的に削除されます。これは上記の「データ削除」機能（ユーザー操作による完全削除）とは別の、時間経過に基づく仕組みです。

= 他のSEO Pluginと併用できますか？ =

Yoast SEO、All in One SEO、Rank Math、SEOPressのいずれかが有効な場合、ASTREA独自のmeta description・OGP・構造化データの出力は自動的に停止します。重複した出力は行われません。Search Console確認用のmetaタグは、これらのPluginの機能とは独立しているため、併用しても問題ありません。

= Google Analyticsに対応していますか？ =

「ASTREA > SEO」の設定画面からGoogle Analytics 4（GA4）の測定IDを登録できます。未登録の状態では、外部への計測タグは一切出力されません。

== Changelog ==

= 1.0.0-rc1 =
* Release Candidate 1。機能開発を終了し、配布・Documentation・Packagingの最終検査を実施した版。

= 0.12.0 =
* Construction Order 014時点での開発版。正式Release Tagは別途発行されます。

== Known Issues ==

WordPress 7.1環境における core/group・core/cover Block Editor警告について — ASTREA Theme readme.txt の「Known Issues」を参照してください（Core固有の問題ではありません）。
