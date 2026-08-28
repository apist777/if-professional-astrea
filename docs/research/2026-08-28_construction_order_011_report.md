# CONSTRUCTION ORDER 011 — THEME DISPLAY COMPLETION + SECURITY HARDENING — 施工報告

**Status:** COMPLETE
**関連:** Decision 021/022/028/029、Construction 011着工前調査（2026-08-28）、Construction 007/008/009/010

---

## 1. Professional Single

`theme/templates/single-astrea_professional.html`（新規）。着工前FIX #1どおりの表示順（写真→氏名→資格/役職→紹介文→経歴→学歴→所属→登録情報）。写真=`wp:post-featured-image`、氏名=`wp:post-title{level:1}`、紹介文=`wp:post-content`——いずれも標準Block。資格/役職・経歴・学歴・所属・登録情報の5フィールドは新規`astrea/professional-field` Dynamic Block（`core/includes/professional-field-block.php`）で表示する。`is_representative`はSingleの表示項目に含めていない。

## 2. Professional空meta問題の解消

`astrea/professional-field`は、値が空文字のときブロック自体を完全に返さない（`''`を返す）——`core/post-meta` Bindingsが値の有無に関わらず`<p></p>`というタグ自体は残してしまう構造的限界を、この最小の補助Blockで解消した。着工前FIX #2どおり、Professional全体をDynamic Block化するような大規模Architecture変更は行っていない。Archive（`archive-astrea_professional.html`）の既存の空`<p></p>`もこのBlockへ置き換えて同時に解消した（Single/Archive共通の1つの修正）。実機確認：値ありProfessional→`<div class="wp-block-astrea-professional-field"><p>行政書士</p></div>`、値なしProfessional→当該divごと非出力。

## 3. Office Hours / Office SNS Dynamic Block

着工前FIX #3どおり、1本に統合せず`astrea/office-hours`（`core/includes/office-hours-block.php`）と`astrea/office-sns`（`core/includes/office-sns-block.php`）の2責任に分割して新設した。いずれもOffice Profile既存データ（`business_hours.weekly`/`business_hours.exceptions`、`sns_links`）のみを正本とし、新しいデータモデルは追加していない。`heading`/`emptyMessage`規約（Decision 028）に準拠し、営業時間が一切未設定（全曜日休業かつ例外なし）またはSNSリンク0件の場合はセクション全体（見出し含む）を非表示にする。ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図）は一切追加していない（Decision 022）。

SNSリンクの出力は保存時のスキーム制限（`esc_url_raw($url, ['http','https'])`、Construction 002）に加え、描画時にも`esc_url($url, ['http','https'])`を独立して適用する多重防御とした——Security Audit項目17の唯一の未検証事項（Block Bindingsの`phone_tel`→Button URL属性の経路）についても、実機で不正値を直接Option注入し、`tel:`URIへ変換されても危険なスキームが一切生成されないことを確認した（§10参照）。

## 4. Office Pattern（事務所情報）

`theme/patterns/office-info.php`（新規）。既存Bindings（`office_name`/`address`/`phone`）と新規`astrea/office-hours`/`astrea/office-sns`を1つのPatternへ組み合わせた、事務所概要ページ等へ挿入可能な再利用Pattern。着工前FIX #3どおり、Professional Profile・ACCESS固有情報の責任は一切混ぜていない。

## 5. Setup「事務所概要」ページの改善

`core/includes/setup-pages.php`の`page_definitions()['about']['content']`へ、既存プレースホルダ段落の直後に`astrea/office-info`と同一のBlock群（office_name/address/phone Bindings + office-hours + office-sns）を追記した。Construction 009 HOMEの先例どおり、`generate_pages()`の既存冪等性ガード（`page_still_exists()`）により、この変更は**新規生成ページにのみ**影響し、既に生成済み・ユーザー編集済みの事務所概要ページは一切書き換えられない（Architecture上の性質、実装変更不要）。ページ自体は既存仕様どおり`draft`のまま生成される。実機確認：新規生成→公開→事務所名・住所・電話・営業時間表・SNSリンクすべて正しく表示。

## 6. Services HOME Teaser 0件問題の解消

新規`astrea/service-list` Dynamic Block（`core/includes/service-list-block.php`）を追加し、`home-services-teaser.php`のQuery Loop実装を置き換えた。既存の`astrea/case-list`等と全く同じ`heading`/`emptyMessage`/`limit`属性規約に準拠し、Core既存の`Service\get_services()`を呼ぶのみで新しいデータモデルは追加していない。着工前FIXどおり、Price/FAQ/CASE/RESULTS/VOICE/Serviceの統合Listing Architecture化は行っていない（案A採用、既存の緑化済み6 Blockには一切手を入れていない）。実機確認：0件時はHOMEから「取扱業務」見出しごと完全に消え、1件・複数件で正しく見出し+一覧が表示される。

汎用的な再利用可能Blockとして実装し（HOMEにハードコードしない）、Filtering/Sorting UI/Pagination UI/Category Filter/AJAX/Searchは着工前指示どおり一切追加していない。

## 7. HOME H1

`theme/patterns/home-hero.php`の事務所名を、Paragraph BlockからHeading Block（`level:1`）へ変更した。視覚サイズは変更前と同一のtheme.jsonトークン（`xx-large`/`heading`フォント）をそのまま維持しており、H1化による見た目の変化は無い（SemanticとVisual Stylingの分離）。実機確認：HOME全体で`<h1>`はちょうど1個（事務所名）のみ。

## 8. VOICE Heading修正

`theme/templates/archive-astrea_voice.html`の項目タイトルを`level:3`→`level:2`へ修正した。他の全Archive（Service/Professional/CASE/FAQ/検索/HOME）と同じ「Archive title = H1、Item title = H2」規約に統一された。

## 9. Skip Link

**着工前調査時点の想定と異なる重要な発見：本サイトが動作するWordPress 7.1には、Block Theme向けのSkip Link機構が既にCore標準機能として実装済みであることが判明した。** `wp-includes/block-template.php`の`_block_template_add_skip_link()`（`@since 7.0.0`、`wp_enqueue_block_template_skip_link()`経由で`wp_footer`/`wp_enqueue_scripts`に自動フック）が、レンダリング後のHTML中から最初の`<main>`要素を自動検出し、IDが無ければ`wp--skip-link--target`を自動付与した上で、`.wp-site-blocks`の直前に`<a class="skip-link screen-reader-text" id="wp-skip-link" href="#wp--skip-link--target">Skip to content</a>`を自動挿入する。実機確認：`curl http://localhost:8888/`のレスポンスに、Theme側のコード変更なしで既にこのSkip Linkが存在していた。

このため、**Theme/Core側に新規コードは一切追加していない**——追加するとWordPress core自身の仕組みと二重になる。全15 Templateが例外なく`<main>`を1つだけ持つ構造（Construction 008で確認済み、再監査でも re-確認）であるため、このCore機構は本サイトの全ページで一貫して機能する。実機確認：Professional Single・事務所概要ページを含む複数のページで`id="wp--skip-link--target"`が`<main>`要素へ正しく付与されていることを確認した。

## 10. Security Hardening（MEDIUM 2件の修正）

`core/includes/price.php`・`core/includes/result.php`の`astrea_price`/`astrea_result`投稿タイプ登録を、`show_in_rest => true`から`show_in_rest => false`へ変更した。REST APIの投稿タイプController登録は`public`ではなく`show_in_rest`のみで判定される、というSecurity Audit発見の設計意図矛盾を解消した。

- 実機確認：`curl http://localhost:8888/wp-json/wp/v2/astrea_price`・`.../astrea_result`とも`404`（ルート自体が存在しない）。
- 影響確認：管理画面編集（Title＋既存の従来型Meta Box）は`show_in_rest`に依存しないため無変更で動作することを実機確認。既存の`register_post_meta(...show_in_rest=>true...)`は投稿タイプ自体のREST Controllerが存在しない以上どのみち無効となるため、あえて変更していない（不要な変更の追加を避けた）。
- Contact/Inquiry等、他CPTのREST設計は指示どおり一切変更していない。

Block Bindingsの`phone_tel`→Button URL経路（Security Audit項目17の唯一の未検証事項）を実機で確認した：Office Profileの`phone`へ`javascript:alert(1)//03-1234`という悪意ある値を（`sanitize()`をバイパスして）直接Option注入した上でHOMEのHero Buttonを取得したところ、`phone_to_tel_uri()`の`[0-9+\-]`のみを残すフィルタにより`href="tel:103-1234"`という無害な値のみが出力され、スキーム注入は成立しなかった。この経路は安全と確定した。

## 11. Contact Form Button

`core/includes/contact-form-block.php`の送信Buttonへ`wp-element-button`classを追加した。Construction 005のNonce/Validation/Inquiry保存/Notification等の業務Logicは一切変更していない。実機確認：3 Style Variationいずれでもtheme.jsonの`elements.button`トークン（色・枠丸み）を正しく継承することを、`<button type="submit" class="wp-element-button">`の出力で確認した。

## 12. 変更していないもの（着工前指示どおり）

Listing Block共通Architecture化、Price Group表示、ACCESS固有Data、CTA専用Data Model、VOICE Consent UI、CASE関連Service表示、FAQ Accordion、Animation、Slider、Carousel、職業固有UI、Packaging、User Documentation、総合Responsive Audit、総合日本語耐久試験——いずれも着工前指示の「やらないこと」リストどおり一切手を入れていない。

## 13. Migration

Office Profileの`schema_version`（現行2）に変更は無く、新規Dynamic Blockは既存データ構造をそのまま読むだけのため、Migration機構は不要と判断した。

## 14. Core非活性時の安全性

新設した4つのDynamic Block（`professional-field`/`office-hours`/`office-sns`/`service-list`）は、既存の全Dynamic Blockと同じく`init`フックでの`register_block_type()`に依存しており、Core非活性時はブロック自体が未登録となり無害に無出力となる（新規ガードコード不要、既存パターンを踏襲）。実機確認：Core無効化状態でHOME・Professional Single・Office Blocks設置ページをいずれもFatal無しで取得できることを確認し、再有効化後にデータが完全に復元されることも確認した。

## 15. Responsive（本011スコープ内のみ）

着工前調査で懸念された「長い事務所名＋Header電話CTA」の320px挙動を含む本格的なブラウザベースのビジュアル検証は実施していない（実機はcurlベースの構造検証のみ）。新設した`astrea/office-hours`（`<dl>`）・`astrea/office-sns`（`<ul>`）・`astrea/professional-field`（`<div><h2><p>`）はいずれも標準的なFlow Layoutのセマンティック要素のみで構成しており新規CSSを一切追加していないため、既存の他のセクションと同様にtheme.jsonの標準的なレスポンシブ挙動に委ねている。フルレスポンシブ監査はConstruction 012の範囲として明示的に先送りする。

## 16. Test / CI結果

- PHPUnit：**348 tests / 545 assertions**、全PASS（新規：`ProfessionalProfileTest.php`+8件、`OfficeProfileTest.php`+17件、`ServiceTest.php`+6件、`PriceTest.php`+1件、`ResultTest.php`+1件）。
- PHPCS：`theme/`・`core/`全体で0エラー（61ファイル）。
- PHP構文チェック（`php -l`）：全変更ファイルでエラーなし。
- 実HTTP smoke-test（`tools/ci/smoke-test.sh`）：Part 12（CN-CU）を新規追加。**Part 1〜12の完全な連続PASSを2回連続でローカル確認した**（1回目でクリーンアップ漏れ3件を発見・修正、2回目で完全にクリーンな状態への復帰まで確認）。

発見した記述ミス（製品コードの不具合ではない）：Part 12初版のクリーンアップが、HOME生成で作られたPageそのもの・VOICEテスト投稿・実機閲覧中にWordPress自身が自動生成したNavigationフォールバック投稿の3種を削除し忘れていた。2回目の連続実行で製品側には一切影響しないことを確認した上で、スクリプト側のクリーンアップ漏れを修正した。

## 17. まとめ

Theme Display Completion側の11項目（Professional Single/Professional空meta/Office Hours/Office SNS/Office Pattern/Setup事務所概要/Service HOME 0件/HOME H1/VOICE Heading/Skip Link/Contact Button）とSecurity Hardening側の2項目（Price/Result REST露出修正、phone Binding実機確認）をすべて完了した。Skip Linkについては、着工前調査時点の想定に反し、実行環境のWordPress 7.1がCore標準機能として既に提供していることが施工中に判明したため、Theme/Core側の新規実装は行わず、その事実を実機で確認・記録するに留めた——これは「新しい恒久判断」ではなく、単に着工前調査が把握していなかったWordPress Core自体の既存機能を発見した、という技術的事実であり、新規Decisionは不要と判断した。既存Decision・仕様との矛盾、新たなSecurity問題、Data loss risk、FREE/PRO境界問題は発見されなかった。
