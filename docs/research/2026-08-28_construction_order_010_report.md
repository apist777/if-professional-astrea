# CONSTRUCTION ORDER 010 — CASE / RESULTS / VOICE Semantic Data Layer — 施工報告

**Status:** COMPLETE
**関連:** Decision 029, 02仕様書§12, Construction 010着工前調査（2026-08-28）、Construction 004/008/009

---

## 1. CASE実装

`core/includes/case.php`（新規）。namespace `Astrea\Core\CaseStudy`、POST_TYPE `astrea_case`、ラベル「対応事例」。

- `supports: title, editor, excerpt, thumbnail, page-attributes`。`public: true`、`has_archive: 'cases'`。
- フィールド：タイトル(post_title)／概要(post_excerpt)／本文(post_content)／画像(Featured Image)／表示順(menu_order)／公開状態(post_status)——すべてWordPress標準機構。
- 新規Metaは`astrea_case_related_services`（関連する取扱業務）の1つのみ。金額・成功率・案件番号等の専用Fieldは追加していない。
- `core/includes/case-admin.php`：関連Service選択のチェックボックスMeta Box（FAQと同一パターン）。
- `core/includes/case-list-block.php`：`astrea/case-list` Dynamic Block（HOME Teaser用）。

## 2. RESULTS実装

`core/includes/result.php`（新規）。namespace `Astrea\Core\Result`、POST_TYPE `astrea_result`、ラベル「実績」。

- `supports: title, page-attributes`のみ（editorなし）。`public: false`、`publicly_queryable: false`（Priceと同型、個別URLなし）。
- フィールド：実績ラベル(post_title)／実績値(新規Meta `astrea_result_value`、自由記述文字列、`sanitize_text_field`)。
- 値を数値と仮定せず、「2015年」「全国対応」等でも問題なく成立することをTestで確認済み。構造化データへの変換は一切行わない。
- `core/includes/result-admin.php`：実績値入力Meta Box（Price踏襲）。
- `core/includes/results-list-block.php`：`astrea/results-list` Dynamic Block（HOME Teaser用、RESULTSの唯一の表示経路）。

## 3. VOICE実装

`core/includes/voice.php`（新規）。namespace `Astrea\Core\Voice`、POST_TYPE `astrea_voice`、ラベル「お客様の声」。

- `supports: title, editor, page-attributes`のみ。`thumbnail`は意図的に含めない（実在顧客写真のPrivacyリスク回避、着工前FIX確認済み）。`public: true`、`has_archive: 'voices'`。
- フィールド：表示名/ラベル(post_title)／本文(post_content)のみ。実名専用Field、住所、連絡先、年齢、性別等の個人情報Fieldは一切追加していない。
- タイトル入力欄には`enter_title_here`フィルタ（WordPress標準機構）で「表示名（例：40代・会社経営者様。実名は入力しないでください）」というPlaceholderを表示し、実名入力を防ぐ案内を入力の瞬間に提示する。
- 関連Service・掲載許可確認・専用画像Meta・専用管理画面は一切実装していない（着工前FIXどおり）。
- `core/includes/voice-list-block.php`：`astrea/voice-list` Dynamic Block。`<blockquote>`/`<cite>`のSemantic HTML（意味的に適切な引用のみ使用、装飾目的の乱用ではない）。

## 4. Service関連方式

FAQの既存`sanitize_related_services()`実装を`core/includes/shared.php`の`sanitize_related_service_ids()`へ抽出し、FAQ・CASE双方がこれを呼び出す形に統一した。**FAQの既存挙動・既存Testはすべて無変更で維持**（`FaqTest.php`28件、全PASS確認済み）。VOICEへの関連付けは着工前FIXどおり実装していない。

## 5. Dynamic Block

新規3種（`astrea/case-list`, `astrea/results-list`, `astrea/voice-list`）を追加。すべて`astrea/price-list`/`astrea/faq-list`と同じ`heading`/`emptyMessage`属性規約（Decision 028）に従う。Dynamic BlockはCore所有データを表示するのみで、新しいデータ正本を持たない。Services Teaserの既知課題（Query Loopでの完全自己非表示不可）を含む共通Listing Block Architectureへのリファクタは、着工前調査どおり本Constructionでは実施せず、011以降へ送付する。

## 6. Templates

新設：`archive-astrea_case.html`（Query Loop＋`core/query-no-results`）、`single-astrea_case.html`（既存Single Templateと同型）、`archive-astrea_voice.html`（同様）。RESULTS/VOICEのSingle Templateは指示どおり作成していない。

## 7. HOME統合

新規HOME Teaser Pattern 3種（`home-case-teaser.php`, `home-results-teaser.php`, `home-voice-teaser.php`）を追加し、`core/includes/setup-home.php`の`HOME_PATTERN_SLUGS`へ組み込んだ（配置順：Hero→Services→CASE→RESULTS→Professional→Price→FAQ→VOICE→Flow→CTA）。

**既存の生成済みHOMEへの影響：** `generate_home_page()`の既存冪等性ガード（`astrea_core_generated_pages['home']`が現存すれば即座に何もしない）により、この変更は**新規生成HOMEにのみ**影響し、既存生成済み・ユーザー編集済みのHOMEは一切変更されないことを、Architecture上の性質として確認した（実装変更不要、Construction 009の設計がそのまま保護として機能する）。0件のCASE/RESULTS/VOICEはDynamic Block自身がセクション全体を非表示にするため、新規生成HOMEでも空見出しが公開されることはない（実機確認済み、§11参照）。

## 8. Privacy

新規の個人情報Fieldは一切追加していない。VOICEの表示名は「実名を入力しない」ことを`enter_title_here`フィルタで案内する。掲載許可確認の同意管理機能・専用UIは実装していない（Decision 029どおりPost v1）——公開可否の判断は、Service/FAQの公開判断と全く同じ通常の編集判断（`post_status`）のみに委ねている。過剰なConsent Management機能は実装していない。

## 9. Core完全削除

`core/includes/data-deletion.php`の削除対象post typeへ`CaseStudy\POST_TYPE`・`Result\POST_TYPE`・`Voice\POST_TYPE`の3件を追加。削除対象・非削除対象の説明UIにも3件を追記した。CASE Featured Image（Media Library Attachment）は削除されないことをPHPUnit・実HTTP双方で確認済み。`core/uninstall.php`のコメント履歴にもConstruction 010の記述を追加した。

## 10. Security / Accessibility

**Security：** 全新規機能でCapability（`edit_posts`/`manage_options`相当）・Nonce・Sanitization（`sanitize_text_field`／既存の関連Service検証）・Escaping（`esc_html`徹底）を既存Service/FAQ/Price水準で実装。不正ID・重複ID・非公開Service参照は保存時に除外されることをTest済み。Core非活性時はCPT自体が未登録になり、Query Loop/Dynamic Blockは既存パターンと同じ安全側動作（Fatal無し、空表示）。

**Accessibility：** VOICEの引用は`<blockquote>`/`<cite>`という意味的に適切なSemantic HTMLを使用。CASE Archiveの画像はFeatured Imageの標準Alt機構に委譲。0件時表示はDecision 028の2ルール（Archive＝前向きなメッセージ、HOME Teaser＝完全非表示）を実機で確認済み。

## 11. Test / CI結果

- PHPUnit：**317 tests / 500 assertions**、全PASS（新規：`CaseTest.php`25件、`ResultTest.php`17件、`VoiceTest.php`16件、`SharedTest.php`5件、`DataDeletionTest.php`への追加2件）。
- PHPCS：`core/`・`theme/`全体で0エラー（56ファイル）。
- 実HTTP smoke-test（`tools/ci/smoke-test.sh`）：Part 11（CF-CM）を新規追加。**Part 1-11の完全な連続PASSをローカルで確認した**（EXIT 0、172件のOK）。

実機検証の過程で、以下3件の**smoke-test.sh自体の記述ミス**（製品コードの不具合ではない）を発見・修正した。

1. CASE Single URLの構築時に、実際のパーマリンク構造（`/cases/<slug>/`）に対し`/<slug>/`（`cases/`プレフィックス抜け）を使っていたため301リダイレクトが発生し失敗と検知していた（Service等の既存の正しいパス構築パターンを踏襲して修正）。
2. CASE編集画面の確認に、Cookieを一切送信しない`check_no_fatal()`（公開ページ専用のヘルパー）を認証必須のwp-admin URLへ誤用し、未ログイン扱いによるログイン画面への302を「Fatal」と誤検知していた。認証済みセッションを明示的に使う直接`curl -b`呼び出しへ修正し、2回連続で同一箇所を確定的に再現したことでこれが環境要因の一過性障害ではなく本物のスクリプトの誤りであると特定した。
3. Media Library添付ファイルの作成に`media_sideload_image()`（外部URL取得、Dockerネットワーキング内での自己ループバックが失敗）を使っていたため実行時エラーとなっており、コンテナ内に既に存在するファイルパスを直接使う`wp media import`へ切り替えて解消した。

いずれも製品コード（`core/`・`theme/`）には影響しない。

## 12. Migration

新規CPT登録のみで、Office Profileのようなoption内スキーマ変更を伴わないため、Migration機構は不要と判断した。`schema_version`の更新も行っていない。

## 13. まとめ

CASE/RESULTS/VOICEの3 CPTを、既存のService/Price/FAQのArchitecture（WordPress標準投稿編集画面、`page-attributes`による表示順、Dynamic Block＋`heading`/`emptyMessage`規約、Core完全削除への統合）を完全に踏襲する形で実装した。着工前調査・着工前FIXで示された6件の判断（別CPT化、RESULTSの軽量CPT化、VOICEのService関連なし、VOICE画像除外、管理画面表示名、新規Decision不要）をすべてそのまま反映した。既存Decision・仕様との矛盾、Security問題、Data loss risk、FREE/PRO境界問題は発見されなかった。新規Decisionは追加していない（Decision 029・着工前FIXの範囲内の実装解釈として処理した）。
