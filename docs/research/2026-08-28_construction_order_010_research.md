# CONSTRUCTION ORDER 010 — CASE / RESULTS / VOICE Semantic Data Layer — 事前調査

**種別:** PRE-CONSTRUCTION RESEARCH（調査のみ。製品コード変更なし）
**Status:** RESEARCH COMPLETE
**対象HEAD:** `da54ae7`（Construction 009 COMPLETE時点）
**関連:** 02仕様書§12, Decision 002・013・018・019・021・026・028・029, Construction 003・004・008・009

---

## 1. 正本確認で押さえた前提

- **02仕様書§12（原文）：** 「3種類を区別する。CASE＝対応事例、RESULTS＝公開可能な実績・数字、VOICE＝お客様の声。すべて任意。開業初日で0件でもサイトデザインが成立する。CASEはService等との関連付けを可能とする。VOICEについては掲載許可確認を支援するUIを検討する。ASTREA自身が法的な掲載可否を判断するものではなく、守秘義務、個人情報、所属団体等の規則を確認するよう案内する。」
- **Decision 029：** CASE/RESULTS/VOICEをFREE v1 Release Blockingとし、Construction 010で実装。VOICEの掲載許可確認専用UIはPost v1。
- **既存CPT実装の型（Construction 003/004で確立済み）：** Service（`supports: title, editor, page-attributes`、`public/publicly_queryable: true`、Archive/Single両方あり）、Price（`supports: title, page-attributes`のみ＝editorなし、`public/publicly_queryable: false`＝個別URL無し、Dynamic Block経由でのみ表示）、FAQ（`supports: title, editor, page-attributes`、taxonomy `astrea_faq_category`あり、`related_services`という「配列postmeta＋チェックボックスMeta Box＋既存Publish済みServiceへのフィルタ」という関連付けパターン）。
- **Decision 028（0件時表示）：** Archive専用ページ＝見出し維持＋前向きなNo Resultsメッセージ。HOME Teaser＝見出し含め完全非表示。Query LoopではTeaserの完全自己非表示ができないため、Price/FAQは`heading`/`emptyMessage`属性を持つDynamic Blockで両方の挙動を1実装で満たす設計になっている（Construction 008/009で確立）。
- **Construction 009のHOME組み立て：** `setup-home.php`の`HOME_PATTERN_SLUGS`はTheme側Patternのスラッグを生成時に`WP_Block_Patterns_Registry`から読み取って合成するのみで、複製・同期は行わない。既存の生成済みHOMEページは、Core/Theme更新後もこのリストへ新スラッグを追加するだけでは**一切変更されない**（生成はTracking IDが無いサイトでのみ発生するため）。
- **Core完全削除（Construction 009 `data-deletion.php`）：** 現在の削除対象post typeは`PROFESSIONAL_POST_TYPE, SERVICE_POST_TYPE, PRICE_POST_TYPE, FAQ_POST_TYPE, INQUIRY_POST_TYPE`の配列。新規CPTはこの配列へ追加するだけで良い設計になっている。

---

## 2. 用語の責任境界

CASEとRESULTSは名称こそ並記されているが、性質が異なることが確認できた。

- **CASE＝対応事例：** 個別の案件についての語り（何が起きたか、どう対応したか）。タイトル・本文・画像・日付を持つ「記事」的な構造が自然に適合する。
- **RESULTS＝公開可能な実績・数字：** 「累計相談件数1,000件」「顧問先50社」等の**短い統計ハイライト**。本文（長文）を必要としない、ラベル＋値の組が本質。

この違いを踏まえ、比較した4案の結論は以下のとおり。

| 案 | 内容 | 評価 |
|---|---|---|
| A. 同一Entityの異なる表示概念 | 1つのCPTでtaxonomy等により区別 | CASE（本文必須）とRESULTS（本文不要）でフィールド形状が食い違う。管理画面で「実績」なのに本文欄が出るのは士業ユーザーにとって理解しにくい |
| B. 1CPT内taxonomy分類 | Aと同様の問題を抱える | 同上、不採用 |
| C. 完全に別CPT | CASE・RESULTS双方を専用CPTとして登録 | フィールド形状の違いを自然に表現できる。Price（editorなし・非公開URL）という既存の「軽量CPT」precedentがRESULTSにそのまま使える |
| D. CASEのみ意味データ化、RESULTSは表示概念 | RESULTSをCore CPT化しない | シンプルだが、実績数値を更新するたびにBlock Editorでの直接編集を要求することになり、AGENTS.md「ユーザーにWordPressを勉強させすぎない」という原則にやや反する |

**推奨：C（完全に別CPT）。** ただしRESULTSはCASEと同水準の重量級CPTにはせず、**Priceと同型の軽量CPT**（`supports: title, page-attributes`のみ、`editor`・`excerpt`・`thumbnail`は持たない、`public/publicly_queryable: false`）として設計する。これにより「実績数字を追加・更新する」という頻出作業を、通常の投稿編集と同じ「タイトル欄＋数値欄」の入力で完結させつつ、CASEのような重い構造を強制しない。

この判断は仕様から一意に導けるものではないため、**§23の要確認事項として提示する**（D案＝RESULTSを完全に静的Pattern化する代替案も有効な選択肢として残す）。

---

## 3. CASE / RESULTS Data Model（推奨案）

### CASE（`astrea_case`、仮称）

| フィールド | 実現方法 | 理由 |
|---|---|---|
| タイトル | `post_title`（標準） | 既存CPTと同型 |
| 概要 | `post_excerpt`（標準） | Archive/Teaserカード用の要約に、既存WordPress標準機構をそのまま使う。新規Meta不要 |
| 本文 | `post_content`（標準） | 対応の経緯・結果も本文の中で語ればよく、「結果」専用欄を別に設ける必然性は薄いため統合する |
| 画像 | Featured Image（標準） | 事例のイメージ画像。Professional Profileと同じ標準機構 |
| 日付 | `post_date`（標準） | 新規Meta不要 |
| 表示順 | `menu_order`（`page-attributes`） | 既存CPT全てと同型 |
| 公開状態 | `post_status`（標準） | 新規Meta不要 |
| 関連Service | **新規Meta**（配列ID、`related_services`） | 02§12が明示的に要求。FAQの`sanitize_related_services()`と全く同じパターンを再利用する（§4参照） |

新規Metaは`related_services`の1つのみ。金額・成功率・案件番号・顧客名等は追加しない（指示どおり、FREE共通仕様として不要）。

### RESULTS（`astrea_result`、仮称）

| フィールド | 実現方法 | 理由 |
|---|---|---|
| ラベル | `post_title`（標準） | 例：「累計相談件数」 |
| 値 | **新規Meta**（`astrea_result_value`、自由記述文字列） | 例：「1,000件以上」「15都道府県」。数値専用にせず自由記述とする（Priceの`amount`が自由記述である既存判断と一貫） |
| 表示順 | `menu_order`（`page-attributes`） | 既存CPTと同型 |
| 公開状態 | `post_status`（標準） | 新規Meta不要 |

Price同様、`editor`・`excerpt`・`thumbnail`は持たない。個別URLも持たない（`public: false, publicly_queryable: false`）。関連Serviceも持たない（実績数字は特定業務に紐付けない汎用的な性質のものが大半という判断。必要な場合は将来PRO/Post v1で検討）。

---

## 4. Serviceとの関係

FAQの既存`related_services`実装（`core/includes/faq.php`の`sanitize_related_services()`、`core/includes/faq-admin.php`のチェックボックスMeta Box）を**そのまま再利用する設計**を推奨する。

- 保存形式：配列postmeta（`show_in_rest`のスキーマも同型）。
- サニタイズ：現在Publish済みのServiceのIDのみを許可（保存時点でのフィルタ）。
- Service削除時の扱い：FAQの既存実装がすでに「保存時点でのみ検証し、参照先が後から消えても読み取り側は現在存在するServiceからの逆引きでしか使わない」という安全性を持っている（存在しないServiceのIDが死んだデータとしてpostmeta内に残っても、どこからも実際には辿られないため実害がない）。CASEについても同じ性質を引き継ぐだけでよく、追加のクリーンアップ処理は不要。
- 独自中間Tableは不要（指示どおり不採用）。

**推奨する小さなリファクタ：** `sanitize_related_services()`相当のロジックを`core/includes/shared.php`へ**共通ヘルパーとして抽出**し、FAQとCASE（および任意でVOICE）が同じ関数を呼ぶようにする。現在FAQ内にしか存在しないロジックをコピー＆ペーストで複製するより安全で、Construction 004で確立した「共通化のしすぎない」方針にも反しない小さな範囲の共通化と判断する。

VOICEについても同じ関連付けを持たせるかは§5・§23で扱う。

---

## 5. VOICE Data Model（推奨案）

| フィールド | 実現方法 | 理由 |
|---|---|---|
| 表示名（属性） | `post_title` | 例：「40代・法人代表者様」。実名を入力する場所ではないことを、ラベル・Placeholderで明示する |
| 本文（お客様の声） | `post_content` | そのまま声の内容 |
| 表示順 | `menu_order`（`page-attributes`） | 既存CPTと同型 |
| 公開状態 | `post_status`（標準） | 新規Meta不要 |
| 関連Service | 任意（§4と同じ共有ヘルパーを流用可能） | 02§12に明示要求は無いが、既存の安価な仕組みを転用できるため候補とする。**Release Blockingの必須要件ではない**——§23の確認事項とする |
| 画像 | **意図的に含めない** | 実在の顧客の写真は、匿名化されたテキスト属性より遥かにセンシティブな個人情報であり、Privacy上のリスクがテキストのみの場合より大きい。CASE（事例紹介・書類や事務所の写真等を想定）とは性質が異なると判断し、`thumbnail`サポートを付けないことを推奨する |

新規Metaは0件（関連Serviceを含める場合のみ1件、FAQ/CASEと共有のヘルパーで実装）。

### 掲載許可確認との責任境界（Decision 029で明示的にPost v1）

010では、掲載許可に関する**いかなるメタデータ・チェックボックス・ワークフローも実装しない**。VOICE投稿の公開可否は、Service/FAQの公開判断と全く同じ「投稿者（サイト運営者）が`post_status`を`publish`にするかどうか」という通常の編集判断のみに委ねる。「掲載許可を確認済み」というだけの単純なチェックボックスであっても、Decision 029が明示的にPost v1とした「専用UI」の領域に踏み込むリスクがあるため、010では一切追加しない。ASTREA自身は法的な掲載可否を判断しない（02§12原文どおり）。

---

## 6. Privacy

- 提供する公式フィールドは上記のとおり最小限（VOICEは表示名＋本文のみ）。実名・住所・電話番号・メールアドレス等を入力する項目は用意しない。
- 管理画面の入力欄には、実名や連絡先を入力しないよう促す説明文（`description`）を付す（例：「本名の代わりに『40代・会社経営者様』等の表記を推奨します」）。既存のContact機能（非公開データ）とは明確に別の領域であり、VOICE/CASEは**公開を前提とした投稿コンテンツ**であることを管理画面の説明文で明示する。
- 過剰なConsent Management System（同意取得フォーム、同意ログ、通知メール等）は実装しない（指示どおり）。

---

## 7. WordPress標準機構の活用range

| 機構 | CASE | RESULTS | VOICE |
|---|---|---|---|
| Custom Post Type | ✅ | ✅（非公開URL） | ✅ |
| Post Meta | `related_services`のみ | `astrea_result_value`のみ | 0〜1件（関連Service、任意） |
| Taxonomy | 不採用（`related_services`で代替可能なため） | 不採用 | 不採用 |
| Featured Image | ✅ | 不採用 | 不採用（Privacy理由） |
| Excerpt | ✅（Archive/Teaser用） | 不採用 | 不採用（本文が短いため） |
| menu_order | ✅ | ✅ | ✅ |
| Block Editor（本文） | ✅ | 不使用（`editor`非サポート） | ✅ |
| Query Loop | Archive/Taxonomy用 | 不使用（Dynamic Blockのみ） | Archive用 |
| Block Bindings | 不使用（単一値の差し込み対象ではない） | 不使用 | 不使用 |
| Dynamic Block | HOME Teaser用（新規） | HOME Teaser用（新規、Archiveなしのため唯一の表示経路） | HOME Teaser用（新規） |

独自DB Tableは3つとも不要と判断する。

---

## 8. Admin UI

Service/FAQと同じ、WordPress標準投稿編集画面をそのまま利用する。

- 一覧・新規追加・編集・削除：標準投稿一覧/編集画面（CPT登録のみで自動提供）。
- 表示順：`page-attributes`により標準の「並べ替え」UIがそのまま使える。
- Featured Image：CASEのみ、標準機構。
- Excerpt：CASEのみ、標準の抜粋欄。
- Meta入力：`astrea_result_value`（RESULTS）、`related_services`（CASE、任意でVOICE）はFAQ Admin踏襲のClassic Meta Box（独自JS無し）。
- 管理画面ラベルは02仕様書が既に使っている日本語グロスをそのまま採用する：CASE→「対応事例」、RESULTS→「実績」、VOICE→「お客様の声」。専用SPA管理画面は作らない（指示どおり）。

---

## 9. Theme公開境界

Core内部実装への直接依存を避けるため、既存の3パターンをそのまま踏襲する。

- **Archive/Taxonomy（対応事例・お客様の声）：** Query Loop（`postType: astrea_case` / `astrea_voice`, `inherit: true`）。ThemeはCPT名という「公開契約」にのみ依存する（Service/FAQと同じ既存の結合度）。
- **RESULTS：** 個別URLを持たないため、Query Loopの対象にならない。Dynamic Block（`astrea/results-list`）経由でのみ表示する（Priceと同じ理由・同じ仕組み）。
- **HOME Teaser（3種共通）：** 新規Dynamic Block（`astrea/case-list`, `astrea/results-list`, `astrea/voice-list`）。Decision 028のTeaser自己非表示規則を満たすのはDynamic Blockのみ（Query Loopでは不可能——Construction 008/009で確認済みの制約）。
- **Core公開API：** `Astrea\Core\Case_\get_cases()` 等、既存のService/FAQと同じ形の読み取り専用関数を用意する（クラス名的な問題を避けるため、PHP予約語`Case`との衝突を避ける名前空間・関数名は実装時に確定する——例：`Astrea\Core\CaseStudy`等）。

---

## 10. HOME表示への統合方針

**既存の生成済みHOMEを書き換えない、という制約は、Construction 009のArchitecture自体が既に満たしている。** `assemble_home_content()`はPattern内容を**生成時点で1回だけ**読み取り、生成済みページはその後Core/Themeの更新と一切連動しない（複製後は通常の固定ページとして独立する）。したがって：

- 新規HOME Teaser Pattern（`astrea/home-case-teaser`, `astrea/home-results-teaser`, `astrea/home-voice-teaser`）を追加し、**手動挿入用として**常時利用可能にする。
- `setup-home.php`の`HOME_PATTERN_SLUGS`へこれらを追加することも安全に行える——追加は「まだHOMEを生成していないサイト」にのみ影響し、既存の生成済みHOMEには一切影響しない（Trackingされたページが存在する限り`generate_home_page()`は何もしない、という既存の冪等性ロジックがそのまま保護になる）。
- 既存サイトへ新Teaserを事後的に追加したいユーザー向けの「既存HOMEへ追加する」専用Actionは、**010では作らない**（複雑さに見合わないと判断。必要になった場合はユーザーが通常のBlock Editorで手動挿入すればよく、これは既存のPattern挿入UXと完全に一致する）。

---

## 11. Dynamic Block方針

Price（`astrea/price-list`）・FAQ（`astrea/faq-list`）で確立済みの**`heading`/`emptyMessage`属性による2ルール統一パターン**（Decision 028）をそのまま踏襲し、新規に3つのDynamic Blockを追加する。

- `astrea/case-list`：`get_cases()`をラップ。各項目をタイトル（Single記事へのリンク）＋Excerptで表示。
- `astrea/results-list`：`get_results()`をラップ。各項目をラベル＋値のペアで表示。
- `astrea/voice-list`：`get_voices()`をラップ。各項目を`<blockquote>`本文＋`<cite>`表示名のSemantic HTMLで表示。

**Services Teaserの既知課題（Query Loopでは完全自己非表示ができない）については、010で共通化・解決を試みない。** 3つの新規Blockに求められるのは「新規に0件時ルールを満たすこと」であり、既存の`astrea/price-list`・`astrea/faq-list`を巻き込んだ共通Listing Block Architectureへのリファクタは、既にテスト済み・CI Green済みのコードへの変更リスクを伴い、010の本来の目的（CASE/RESULTS/VOICEの追加）を超える。この統一自体は価値があると判断するため、**Construction 011以降の独立した小規模リファクタ候補として申し送る**（Services Teaserの自己非表示問題も同時に解決できる）。

---

## 12. Templates方針

| Template | 要否 | 理由 |
|---|---|---|
| `archive-astrea_case.html` | **必要** | 対応事例の一覧。既存Archiveと同型（Query Loop＋`core/query-no-results`＋breadcrumb＋footer） |
| `single-astrea_case.html` | **必要** | 事例は個別に読まれる価値のある記事的コンテンツ。`single-astrea_service.html`と全く同じ最小形（Header＋Breadcrumb＋post-title＋post-content）で足りる |
| `archive-astrea_voice.html` | **必要** | お客様の声の一覧。既存Archiveと同型 |
| `single-astrea_voice.html` | **不要** | VOICEは短い引用が中心で、FAQ同様、単独ページとして読む価値が薄い。個別URLは技術的には存在するが、`index.html`への標準フォールバックに委ねる（FAQと同じ判断） |
| RESULTS用Archive/Single | **不要** | 非公開URLのCPT（Priceと同型）。専用Templateは存在しない |

**Professional Single Templateの不足（Remaining Work Auditで発見済み）は、指示どおりConstruction 011の責任範囲とし、010では扱わない。**

---

## 13. Structured Data / SEO

CASE・RESULTS・VOICEのいずれについても、**新規の構造化データ（JSON-LD）は追加しないことを推奨する。**

- CASE（対応事例）：schema.orgに自然に対応する型が無い（`Article`/`CreativeWork`は無理に当てはめれば使えなくはないが、Google Rich Resultとしての明確な実益が無い）。
- VOICE（お客様の声）：`Review`/`AggregateRating`系のマークアップはGoogleの品質ガイドライン上、自己申告・自社ホストの体験談に対して非常に厳格な運用がなされており（第三者検証可能な口コミサイトを主対象とする）、誤用リスクの方が大きい。Decision 026の「データがあるから全部JSON-LDにする、という設計を禁止する」という判断とAGENTS.mdの「SEOスコアゲームをしない」という原則に照らし、実装しない。
- RESULTS：構造化データの対象になり得るschema.org型が存在しない。

title/canonical/OGP等は既存のSEO Foundation（Construction 006）へ完全に委譲する（Service/FAQと同じ扱いで、新規CPTを追加するだけで自動的にOGP/meta description等の対象になる——既存コードのCPT非依存設計により追加作業は不要）。

---

## 14. Core完全削除との統合

`core/includes/data-deletion.php`の`delete_all_posts_of_type()`ループへ、`astrea_case`・`astrea_result`・`astrea_voice`の3 POST_TYPEを追加するのみ。

- 新規Taxonomyを採用しない（§7）ため、削除対象への追加は不要。
- 新規Option・Cronは発生しない（設定画面を持たないため）。
- Media Library Attachment（CASEのFeatured Image）は、既存のProfessional Profile写真・OGP画像と全く同じ理由で**削除しない**（Decision 019）。

影響は「配列へ3行追加する」という最小限の変更にとどまる。

---

## 15. Migration

新規CPT登録は、Office Profileのようなoption内スキーマを持たないため、**Migration機構は不要**と判断する（Service/Price/FAQ追加時と同じ扱い）。`schema_version`の更新も不要。

---

## 16. Security（想定する最低要件）

既存のService/FAQと同水準を維持する。

- Capability：`edit_posts`/`manage_options`相当（WordPress標準CPT Capabilityに委譲）。
- Nonce：Meta Box保存時（`related_services`、`astrea_result_value`）はFAQ/Price Admin踏襲のNonce検証。
- Sanitization：`astrea_result_value`は`sanitize_textarea_field`（Priceの`amount`/`notes`と同型）。`related_services`は既存の「Publish済みServiceのIDのみ許可」フィルタ。
- Escaping：出力時は既存Dynamic Block（Price/FAQ）と同じ`esc_html()`徹底。
- 不正Meta保存拒否・権限無し編集拒否：WordPress標準のCapability機構＋既存パターンのNonce検証で担保。
- Core無効化時のFallback：CPT自体が未登録になるため、Query Loop/Dynamic Blockは既存パターンと同じ安全側動作（空表示・Fatal無し）。

---

## 17. Accessibility

- Heading階層：Archive/Single Templateは既存Templateと同じ構造（`query-title`→`post-title`）を踏襲。
- List/Article semantics：Archiveの各項目は既存どおり`<article>`（Group Blockの`tagName`）。
- 画像alt：Featured Image（CASE）はメディアライブラリ標準のAlt Text機構をそのまま利用（新規UI不要）。
- VOICEの引用：`<blockquote>`/`<cite>`のSemantic HTMLを新規Dynamic Blockで使用（Screen Readerが引用として正しく認識できる）。
- Link purpose：CASE一覧の各リンクテキストはタイトルそのもの（既存Service一覧と同じパターン、曖昧な「続きを読む」リンクにしない）。
- 0件時表示：Decision 028の2ルールをそのまま適用（§11参照）。

---

## 18. Responsive（010範囲：新規UIの成立性確認に限定）

CASE/VOICEのHOME Teaser・Archive一覧が、1件・2件・3件・多数・長いタイトル・長文・画像無しの各パターンで**構造的に破綻しないこと**（Grid/Columnsの折り返しがtheme.json標準のLayout機構に委ねられ、独自CSSを持たないこと）を実装時に確認する。詳細な総合Responsive監査はConstruction 012の範囲であり、010では新規Blockの基本的な成立性確認に留める。

---

## 19. Style Variations

新規Template/Pattern/Dynamic Blockは、既存のService/FAQ/Price/HOME Teaserと同じく、配色・Typographyをtheme.json共通パレット（base/contrast/primary/secondary/surface、heading/bodyフォント）へ完全に委譲する。Trust/Natural/Modernそれぞれに専用のTemplate/Patternは一切作らない（Decision 028の既存方針をそのまま継承）。

---

## 20. FREE / PRO境界

CASE/RESULTS/VOICEの汎用データ層（本調査が設計するもの全て）はFREEの責任範囲である。以下はPRO/Post v1の領域として明確に線引きする。

- 士業固有のCASEテンプレート（例：行政書士業務種別ごとの定型フォーマット）→PRO。
- 職種固有の入力項目（例：建設業許可申請特有の項目）→PRO。
- 業務別実績の自動集計・自動生成→PRO。
- 士業固有Schema.orgマッピング→現時点で採用しない（§13）、将来必要になった場合もPROの検討範囲。

010の実装は完全に職種非依存であり、FREEへ職種知識を混入させない。

---

## 21. Test Strategy（010実装フェーズ向け設計）

**PHPUnit：**
- CPT登録（3種）、`show_ui`/`public`/`publicly_queryable`設定の妥当性。
- `related_services`共有サニタイザーの妥当性（既存Publish済みServiceのみ許可、存在しないID拒否）。
- `astrea_result_value`のサニタイズ。
- 0件・1件・複数件のデータ層関数（`get_cases()`, `get_results()`, `get_voices()`）。
- 各Dynamic Blockの`heading`/`emptyMessage`挙動（Price/FAQと同じテストパターンを踏襲）。
- Capability無し・Nonce無しでのMeta保存拒否。
- Core完全削除：3 POST_TYPEが削除されること、Media/生成済みPage/Navigationが生存すること。

**wp-env smoke（実HTTP）：**
- 管理画面への3種CPT登録確認、実投稿作成・編集。
- Archive（CASE/VOICE）の実際の表示、0件時`query-no-results`。
- CASE Singleの表示。
- HOME Teaser 3種の0件/複数件切り替え（実機、Dynamic Block）。
- Core deactivate/reactivate（Fatal無し、データ保持）。
- Theme only（Core無し）でのFatal無し確認。
- Media（Featured Image）保持確認（完全削除後）。

既存Construction 001-009のRegressionは全て維持する。

---

## 22. 010推奨施工範囲 / 011以降への送付

### CONSTRUCTION 010で実装するもの

1. `astrea_case`・`astrea_result`・`astrea_voice`の3 CPT登録（Meta含む）。
2. `related_services`共通サニタイザーの`shared.php`への抽出、CASE（および採用する場合はVOICE）への適用。
3. Dynamic Block 3種（`astrea/case-list`, `astrea/results-list`, `astrea/voice-list`）。
4. Archive Template 2種（CASE, VOICE）、Single Template 1種（CASE）。
5. HOME Teaser Pattern 3種（新規、`HOME_PATTERN_SLUGS`への追加含む）。
6. Core完全削除対象への3 POST_TYPE追加。
7. 上記のTest一式。

### Construction 011以降へ送るもの

- Professional Single Templateの新設（既にConstruction 011の範囲と確定済み）。
- Price/FAQ/Services Teaserを含めた共通Listing Block Architectureへのリファクタ（§11で発見した将来課題）。
- ACCESS固有情報のCore実装（Decision 029でPost v1として既に確定済み）。
- VOICE掲載許可確認専用UI（Decision 029でPost v1として既に確定済み）。
- 総合Responsive/Accessibility/Performance監査（Construction 012の範囲）。

---

## 23. 要確認事項（着工前にユーザー判断が必要）

1. **CASEとRESULTSを別CPTにする方針（§2）への同意。** 「D案＝RESULTSを完全に静的Pattern化しCore CPT化しない」という代替案も有効であり、どちらを採用するか。
2. **RESULTSのデータモデルをPrice型の軽量CPT（editorなし、非公開URL）とする方針（§3）への同意。**
3. **VOICEに`related_services`（関連Service）を持たせるか。** 02仕様書に明示要求は無く、Release Blockingの必須要件ではない任意機能である。
4. **VOICEに画像フィールドを含めない方針（§5）への同意。** Privacy上の理由による意図的な除外だが、事務所側が「お客様の許可を得た写真」を掲載したいケースを完全に閉ざすことになる。
5. **CASE/RESULTS/VOICEの管理画面表示名（対応事例／実績／お客様の声）への同意。** 02仕様書の既存日本語グロスをそのまま採用する案。
6. **新規Decisionの要否：** 本調査で提案した内容（CASE/RESULTS分離、VOICE画像非対応、構造化データ非対応）は、既存Decision（002・013・019・026・028・029）の範囲内の実装解釈と判断しており、**新規Decisionは不要**と考えるが、着工前に確認いただきたい。

上記いずれも、既存Decisionと矛盾する提案ではなく、実装レベルの設計選択である。ただし仕様書から一意に導けないため、独自にFIXせず報告する。

---

## まとめ

- **CASE/RESULTS推奨Model：** 別CPT。CASEはService型（editor+excerpt+thumbnail+関連Service）、RESULTSはPrice型（editorなし、非公開URL、ラベル+値のみ）。
- **VOICE推奨Model：** 表示名（title）+本文（content）のみ。画像・関連Service（既定では持たせない）・掲載許可確認UIは含めない。
- **Serviceとの関連：** FAQの`related_services`パターンをそのまま再利用、`shared.php`へ共通ヘルパー抽出を推奨。
- **Theme公開方式：** Archive/Taxonomy=Query Loop、RESULTS/HOME Teaser=Dynamic Block（Price/FAQ踏襲の`heading`/`emptyMessage`規約）。
- **HOME統合方式：** 新規HOME Teaser Pattern追加＋`HOME_PATTERN_SLUGS`への追加。既存生成済みHOMEは無傷（Construction 009のArchitecture上の性質による）。
- **Dynamic Block方針：** 新規3種のみ追加。Services Teaser問題を含む共通化リファクタは011以降へ送付。
- **Template方針：** CASE Archive/Single、VOICE Archiveのみ新設。RESULTS/VOICE Singleは不要。
- **Privacy/Security：** 新規個人情報フィールドなし、既存水準のCapability/Nonce/Sanitizationを継承。
- **Core完全削除／Migration：** 3 POST_TYPE追加のみ、Migration不要。
- **FREE/PRO境界：** 職種知識混入なし、汎用データ層のみFREE。
- **要確認事項：** 6件（§23）。既存Decisionとの矛盾は無い。
