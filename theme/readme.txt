=== ASTREA ===

Contributors: projectif
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0-rc1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: block-theme, full-site-editing, translation-ready

If Professional ASTREA — 日本の士業・専門家向けのBlock Theme。Theme単体で安全に動作し、ASTREA Core（任意Plugin）を組み合わせることで事務所情報・専門家紹介・料金・お問い合わせ等の表示機能が使えるようになります。

== Description ==

**If Professional ASTREA**（本Theme名：ASTREA）は、行政書士・社会保険労務士・税理士等、日本の士業・専門家事務所のWebサイト向けに設計されたWordPress Block Themeです。

主な特徴（FREE v1で実際に提供される機能のみを記載しています）：

* WordPress Block Theme / Full Site Editing（Site Editor）に対応
* 3種類のStyle Variations（Trust / Natural / Modern）
* 事務所紹介・取扱業務・料金・ご相談の流れ等をまとめたHOME Pattern一式
* 事務所概要・料金・お問い合わせ等の基本Pageテンプレート
* 日本語表示を前提としたレイアウト・文言
* 任意Plugin「ASTREA Core」と組み合わせることで、事務所情報・専門家プロフィール・取扱業務・料金・FAQ・対応事例・実績・お客様の声・お問い合わせフォーム等の管理・表示機能が利用可能

= ThemeとASTREA Coreの関係 =

ASTREA Coreは必須ではありません。ASTREA Theme単体でも、通常のWordPress Block Themeとして安全に動作します（Fatal Errorや白い画面を発生させません）。

ASTREA Coreを有効化すると、以下の機能が利用可能になります。

* 事務所情報（Office Profile）の入力・表示
* 専門家プロフィール（Professional Profile）
* 取扱業務（Service）・料金（Price）
* よくある質問（FAQ）
* 対応事例（CASE）・実績（RESULTS）・お客様の声（VOICE）
* お問い合わせフォームと問い合わせデータの一時保存
* SEO補助（meta description、OGP、構造化データ、Search Console連携）
* Setup補助（初期ページ・メニューの一括作成）

「Coreを入れないとThemeが壊れる」ことはありません。Coreは公式に推奨されますが、必須ではありません。

== Installation ==

1. WordPress管理画面の「外観 > テーマ > 新規追加 > テーマのアップロード」から、ASTREA Theme ZIPをアップロードしてインストールします。
2. インストールしたASTREAを有効化します。
3. 必要に応じて、ASTREA Core Plugin ZIPを「プラグイン > 新規追加 > プラグインのアップロード」からインストールします。
4. ASTREA Coreを有効化します。
5. 管理メニューの「ASTREA」からASTREA Setup画面を開きます。
6. Office Profile（事務所情報）等、必要な項目を入力します。
7. Setup画面から、ホームページ・基本ページ（事務所概要／料金／お問い合わせ）・メニュー（Navigation）を作成します。
8. 「外観 > エディター（Site Editor）」から、配色やStyle Variation（Trust / Natural / Modern）等のデザインを必要に応じて調整します。

== Frequently Asked Questions ==

= ASTREAとは何ですか？ =

日本の士業・専門家事務所向けに設計されたWordPress Block Themeです。ASTREA Theme（本体）と、任意のPlugin「ASTREA Core」の2つで構成されます。

= ThemeとASTREA Coreの違いは何ですか？ =

ASTREA Themeはデザイン・レイアウト・Block Pattern等を担当します。ASTREA Coreは事務所情報・専門家プロフィール・取扱業務・料金・FAQ・お問い合わせ等、テーマを変更しても保持すべきデータと機能を担当します。

= ASTREA CoreなしでThemeだけ使えますか？ =

はい。ASTREA Theme単体でも、通常のWordPress Block Themeとして安全に動作します。Coreが無効化されている状態でPHP Fatal Errorが発生することはありません。ただし、事務所情報の入力画面や動的な一覧表示（取扱業務一覧、FAQ一覧等）はCoreを有効化した場合にのみ利用できます。

= ASTREA Coreを入れると何ができるようになりますか？ =

事務所情報（Office Profile）、専門家プロフィール、取扱業務、料金、FAQ、対応事例（CASE）、実績（RESULTS）、お客様の声（VOICE）の管理・表示、お問い合わせフォームと問い合わせデータの一時保存、SEO補助（meta description・OGP・構造化データ）、Search Console連携、初期セットアップ支援（Setup）が利用可能になります。

= セットアップはどこから始めますか？ =

ASTREA Coreを有効化すると、管理メニューに「ASTREA」が追加されます。ここから事務所情報の入力、ホームページ・基本ページ・メニューの作成といったSetup作業を進められます。

= 専門家（Professionals）の情報はどこで登録しますか？ =

ASTREA Core有効化後、管理メニューの「ASTREA > 専門家プロフィール」から登録します。写真はWordPressのメディアライブラリを利用します。

= 取扱業務（Services）・料金（Prices）・FAQはどこで登録しますか？ =

いずれもASTREA Core有効化後、管理メニューの「ASTREA」配下の各画面から登録します。登録した内容は、HOME PatternやDynamic Block（取扱業務一覧、料金一覧、FAQ一覧等）を通じて自動的に一覧表示されます。

= 対応事例（CASE）・実績（RESULTS）・お客様の声（VOICE）とは何ですか？ =

士業・専門家事務所が実績や利用者の声を紹介するための投稿タイプです。ASTREA Core有効化後、管理メニューの各画面から登録できます。

= お問い合わせ機能について教えてください =

ASTREA Coreはお問い合わせフォームと、送信された問い合わせ内容の一時保存機能を提供します。保存期間は10日・30日・60日・90日から選択でき、初期設定は30日です。保存期間を過ぎた問い合わせは自動的に削除されます。

= ホームページ（HOME）はどのように作られますか？ =

ASTREA Setup画面から「ホームページを作成する」を実行すると、Hero・取扱業務・専門家紹介・料金・FAQ・ご相談の流れ・CTAを組み合わせたホームページが作成され、固定フロントページとして設定されます。既にホームページ（固定フロントページ）が設定されている場合、新しく作成されることはありません。

= メニュー（Navigation）はどのように作られますか？ =

ASTREA Setup画面から「基本メニューを作成する」を実行すると、取扱業務・専門家紹介・FAQ・作成済みの基本ページへのリンクを含むメニューが1件作成されます。ヘッダー・フッターが未編集であれば自動的に反映されますが、既にヘッダー・フッターをカスタマイズしている場合は自動反映されません（お客様が編集した内容を勝手に上書きすることはありません）。既にメニューが存在する場合、新しく作成されることはありません。

= サイトのタイトルはどこで設定しますか？ =

WordPress標準の「設定 > 一般」の「サイトのタイトル」から設定します。ASTREAの事務所情報（Office Profile）とは独立しており、自動的に同期されることはありません。

= Style Variation（Trust / Natural / Modern）とは何ですか？ =

ASTREAには3種類の配色・デザインバリエーションが用意されています。「外観 > エディター（Site Editor）> スタイル」から切り替えできます。

= Site Editorでデザインを変更できますか？ =

はい。ASTREAは標準的なWordPress Block Theme／Full Site Editingに対応しているため、Site Editorから配色・レイアウト・Template Part（ヘッダー・フッター等）を通常のWordPressの操作で編集できます。

= ASTREA Coreを無効化するとデータは消えますか？ =

いいえ。ASTREA Coreを無効化（停止）しても、これまでに入力したデータは保持されます。再度有効化すれば、そのまま表示・編集が再開できます。プラグイン一覧から「削除」しても、Core自体のデータは自動的には削除されません。

= 登録したデータを完全に削除したい場合はどうすればよいですか？ =

管理メニューの「ASTREA > データ削除」から、明示的な確認操作（同意チェック・確認文言の入力）を行うことで、Core管理下のデータ（事務所情報、専門家プロフィール、取扱業務、料金、FAQ、CASE、RESULTS、VOICE、問い合わせデータ、各種設定）を完全に削除できます。

この操作でも、Setupで作成した固定ページ（事務所概要・料金・お問い合わせ等）、作成したメニュー（Navigation）、メディアライブラリの画像（専門家の写真、OGP画像等）は削除されません。これらは通常のWordPressコンテンツとして残ります。「全部完全に消える」わけではありませんので、あらかじめご了承ください。

= アクセス解析（GA4）に対応していますか？ =

ASTREA Coreの設定画面からGoogle Analytics 4（GA4）の測定IDを登録できます。登録しない限り、外部への計測タグは出力されません。

= 他のSEO Pluginと併用できますか？ =

Yoast SEO、All in One SEO、Rank Math、SEOPressのいずれかが有効な場合、ASTREA独自のmeta description・OGP・構造化データの出力は自動的に停止し、重複しないようになっています。Search Console確認用のmetaタグは、これらのPluginとは独立した項目のため、併用しても問題ありません。

= 日本語以外の言語で表示されることはありますか？ =

ASTREAはWordPressのSite Language設定を強制的に変更しません。WordPressのSite Languageが日本語以外に設定されている場合、WordPress Core自体の管理画面文言等が英語になることがあります。日本語で利用する場合は、WordPress本体の日本語Language Packがインストール・設定されていることをご確認ください（ASTREAは日本語Language Packを自動的にインストールしません）。

= 既知の問題はありますか？ =

WordPress 7.1環境において、WordPress標準の一部Block（core/group、core/cover）で、編集画面に「Block contains unexpected or invalid content」という警告が表示される場合があることを確認しています。これはWordPress Core自体の挙動によるものであり、ASTREA固有の不具合ではありません。実際にデータが失われることはないことを確認済みですが、警告画面に表示される「Attempt recovery（回復を試みる）」は、内容に心当たりがない場合は実行しないことをおすすめします。

== Changelog ==

= 1.0.0-rc1 =
* Release Candidate 1。機能開発を終了し、配布・Documentation・Packagingの最終検査を実施した版。

= 0.9.0 =
* Construction Order 014時点での開発版。正式Release Tagは別途発行されます。

== Known Issues ==

* WordPress 7.1環境で、WordPress標準のcore/group・core/cover Blockに「Block contains unexpected or invalid content」という編集画面の警告が表示される場合があります（ASTREA固有の不具合ではなく、WordPress Core側の挙動です。実データの損失は確認されていません）。
