# CONSTRUCTION ORDER 004 — Service / Price / FAQ 着工前調査

- 実施日: 2026-08-26（JST）
- 担当: クロエ（Claude）
- 対象: Service / Price / FAQ の意味データ基盤設計（Core保存 → 公開境界 → Theme最小表示）
- 基準文書: `05_astrea_free_v1_construction_baseline.md`、Decision 001〜025、`02_astrea_free_v1_specification.md` §7・§10・§11

---

## 1. 正式仕様からの要件抽出

### 1.1 SERVICE（02仕様書§7）

- ASTREA FREEは全士業共通版であり、職種固有のサービス内容を持たない。ユーザー自身が登録する。
- 再利用文脈：**HOME、サービス一覧、個別Service、関連FAQ、CASE、記事等**。
- → 「サービス一覧」「個別Service」の両方が明文で要求されている。**アーカイブページと個別ページの両方が必要**。
- 項目：仕様上明記されているのは実質「名称」「説明」のみ（依頼者向けの分かりやすい表現は編集ガイド上の注意点であり、データ項目ではない）。
- カテゴリ／グループ化：明記なし。追加しない。
- 職種固有Service生成はPROの担当（FREEでは行わない）。

### 1.2 PRICE（02仕様書§10）

- 一元管理。固定額、○円〜、月額、時間制、無料、個別見積、自由表記等、**異種混在の自由記述**に対応する必要がある。
- 任意項目として「料金グループ」「実費・追加費用等の注記」。
- 再利用文脈：**HOME、Priceページ、Service等**。「サービス一覧」のような複数形の一覧語（〜一覧）は使われておらず、Serviceの時と異なり「個別Price」という語も存在しない。→ **個別URLの明文根拠なし。Priceページは単一の集約ページ**（Query Loopで埋め込む対象）と解釈するのが妥当。
- 04文書「残る確認事項3」：「PRICE（自由記述）と構造化データ（schema.org Offer等）の整合方法。Core側のデータモデル設計時に決定する。」→ **今回のデータモデル設計で対応する事項**。ただし構造化データの出力自体（JSON-LD等）は本工程の対象外（後述4.3）。
- 税込・税別の扱い：00〜05のいずれにも記載なし。Construction Order 004指示文自身が確認候補として挙げているに過ぎず、正式仕様上の根拠は発見できなかった。**新規フィールドとして追加しない**（自由記述の金額・補足欄にユーザー自身が記載できるため、既存の「自由表記」方針の範囲内で吸収可能）。
- Service関連（FK）：§10本文に「Service等から同じ料金情報を参照できる構造」とあるが、これは「同じ情報を複数画面から参照できる（表示の再利用）」という意味であり、個々のPrice項目がどのServiceに属すかという正式なリレーションを要求する記述ではない。Construction Order 004指示文も明示的に「PriceがServiceと関連する必要がある場合は、既存仕様に根拠があるか確認し、勝手にFKやRelationを追加しないこと」と釘を刺している。→ **Price→Serviceの形式的リレーションは実装しない**。

### 1.3 FAQ（02仕様書§11）

- 主要項目が明記されている：**質問、回答、カテゴリ、関連Service、表示順、公開状態、重要FAQ**。
- 再利用文脈：**HOME、FAQ一覧、Serviceページ等**。→ 「FAQ一覧」が明文で要求される。個別FAQページの明文要求はないが、後述の通りTaxonomy Archiveを使うと副次的に単一Permalinkが生じる（許容範囲と判断）。
- 件数を強制しない。少数はMinimal表示、多数はカテゴリ表示。
- FAQをSEO目的の量産装置にしない（§11、および§31「FAQ・地域ページ等のSEO目的大量生成→行わない」）。
- 構造化データ（FAQPage等）についてConstruction Order 004指示文は「FAQがあるから自動的にFAQPage schemaを出すとは判断しないこと」と明示。理由：
  - Googleは2023年8月以降、FAQ Rich Result の表示対象を実質的に「著名で権威のある政府・保健機関等のサイト」に限定するガイドライン変更を行っており、一般の士業サイトがFAQPage構造化データを実装しても検索結果での視覚的効果はほぼ得られない状況にある。
  - ASTREAの構造化データ責任はDecision 010（Breadcrumb）で先例化されており、SEO関連の構造化データ判断は個別機能ごとに場当たり的に決めるのではなく、SEO Foundation工程（02仕様書§16、Decision 008/009/010/018）でまとめて設計すべき性質の判断である。
  - → **本工程ではFAQの意味データのみを実装し、FAQPage JSON-LD等の構造化データ出力は実装しない**。要確認事項として後工程（SEO Foundation関連のConstruction Order）へ引き継ぐ。

---

## 2. 保存方式の比較

| 観点 | CPT + Post Meta | Options API | Taxonomy | 独自DB Table |
|---|---|---|---|---|
| 0..N件の個別レコード（作成/編集/削除/個別ID） | ◎ WordPress標準のUIがそのまま使える | △ 配列を1レコードとしてまとめて保存する必要があり、個別編集・削除のUIを自作する必要がある | ✕ レコードではなく分類語 | ◎ だが独自UIを丸ごと自作する必要があり、Decision 002/019のUninstall境界・WordPress標準性からも避けるべき |
| 表示順 | ◎ `menu_order`（Page Attributes標準UI） | △ 配列の並び順を自前で管理するUIが必要 | - | △ 独自カラム管理が必要 |
| カテゴリ／グループ化 | Taxonomyと組み合わせれば◎ | ✕ | ◎ 分類語自体の標準機構 | △ 独自実装 |
| 個別URL（Single/Archive） | ◎ WordPress標準のTemplate階層がそのまま使える | ✕ 個別URLの概念がない | ◎（Term Archive） | ✕ 独自ルーティングが必要 |
| Query Loop / Block Bindings対応 | ◎ ネイティブ対応 | ✕ 対応なし（Office Profileは独自Block Bindings Sourceで対応済み） | ◎ ネイティブ対応 | ✕ |

Professional ProfileがCPTだったという理由だけで機械的にCPTを採用せず、各データの性質を個別に確認した。

- **Service**：0..N件、個別ページ（個別Service）とアーカイブ（サービス一覧）の両方が明文要求される「独立したコンテンツ実体」。→ **CPT（`astrea_service`）+ Post Meta（実質メタなし、標準投稿フィールドのみ）**。CASE同様、将来Serviceとの関連付けを持つ他機能（CASE等）から参照されるため、独立した投稿IDを持つ実体である必要がある。
- **Price**：0..N件だが、個別URLの明文要求がなく、自由記述中心の短い構造化フィールド（表示名・金額・補足・グループ）の集合。CPT自体は個別レコード管理の標準UI（作成・編集・削除・並び替え）が必要な点でOptions APIより明らかに適しており、「個別URLが要らない」ことはCPTを`public=false`寄りに設定すれば両立できる（Office Profileのような単一レコードではないため、Options APIで配列を管理する独自UIを自作するのは車輪の再発明）。→ **CPT（`astrea_price`）+ Post Meta**（`public`は個別URLを作らない設定にする。詳細は3.2）。
- **FAQ**：質問・回答という個別レコード性に加え、**カテゴリという正式なグループ化要件が明記されている**唯一のケース。→ **CPT（`astrea_faq`）+ Post Meta + Taxonomy（`astrea_faq_category`）**。カテゴリをTaxonomyにすることで、「多数ならカテゴリ表示」という要件をWordPress標準のTerm Archiveテンプレート階層だけで実現でき、独自の絞り込みロジックを書く必要がない。

独自DB Tableが必要と判断した項目はない。

---

## 3. データモデル

### 3.1 Service（`astrea_service`）

| WordPress標準フィールド | 用途 |
|---|---|
| `post_title` | 名称 |
| `post_content` | 説明（標準Block Editor、`wp_kses_post`） |
| `menu_order`（page-attributes） | 表示順 |
| `post_status` | 公開状態（WordPress標準） |

追加のPost Metaなし。`public => true`、`has_archive => 'services'`、`rewrite => ['slug' => 'services']`（サービス一覧・個別Serviceの両方に対応）。

### 3.2 Price（`astrea_price`）

| Post Meta | 用途 | サニタイズ |
|---|---|---|
| `astrea_price_amount` | 金額（自由記述：固定額／○円〜／月額／時間制／無料／個別見積等をすべて自由記述で表現） | `sanitize_textarea_field`（改行を許可しHTMLは除去） |
| `astrea_price_notes` | 補足（実費・追加費用等の注記） | `sanitize_textarea_field` |
| `astrea_price_group` | 料金グループ | `sanitize_text_field` |

`post_title` = 表示名。`menu_order` = 表示順。`public => false`, `publicly_queryable => false`（個別URLを作らない。§10に個別Price URLの根拠なし）、`show_ui => true`, `show_in_rest => true`（Query Loop Blockからの参照・管理画面メタボックスに必要）。

**Price→Serviceの形式的リレーションは実装しない**（1.2参照）。

**構造化データとの整合（04文書残る確認事項3への回答）**：自由記述の金額欄はschema.org `Offer.price`（数値）へ機械的に変換できない（例：「月額5,000円〜」は単一の数値ではない）。データモデルとしては自由記述を維持する（§10の要求そのものであるため）。structured data（Offer等）の出力は本工程の対象外とし、将来SEO Foundation関連のConstruction Orderで、可能な範囲のベストエフォート・マッピング（またはマッピング不能と判断し出力しない）を改めて設計する。**本工程はこの整合方法を完全解決しない**（要確認事項として明記）。

### 3.3 FAQ（`astrea_faq`）+ Taxonomy（`astrea_faq_category`）

| フィールド | 実装 |
|---|---|
| 質問 | `post_title` |
| 回答 | `post_content`（標準Block Editor） |
| カテゴリ | Taxonomy `astrea_faq_category`（非階層型。WP標準`post_tag`相当の構造。カテゴリ数は少数想定でparent/child階層の要求は仕様上見当たらないため非階層を採用） |
| 関連Service | Post Meta `astrea_faq_related_services`（`astrea_service`の投稿ID配列。公開済みServiceのIDのみ許可し、不正・未公開IDはサニタイズ時に除外） |
| 表示順 | `menu_order`（page-attributes） |
| 公開状態 | `post_status`（WordPress標準） |
| 重要FAQ | Post Meta `astrea_faq_is_important`（boolean） |

`public => true`、`has_archive => 'faq'`（FAQ一覧要件）、`rewrite => ['slug' => 'faq']`、Taxonomyも`show_in_rest => true`でTerm Archiveテンプレート階層を有効化。

---

## 4. Theme接続方式

Decision 013「Coreが覚える、Blockがつなぐ、Themeが見せる、Patternが並べる」に基づき、データ特性ごとに最も自然な標準機構を選択した。独自Blockは1つも追加していない。

### 4.1 Service — Query Loop（アーカイブ）＋ WordPress標準Single Template Fallback

- `archive-astrea_service.html`：`core/query-title` + `core/query`(postType=astrea_service, inherit) + `post-template` 内で `core/post-title`（H2） + `core/post-excerpt`（Professional Profileと同型、メタ値がないためBlock Bindingsは不要）。
- `single-astrea_service.html`：`core/post-title`(H1) + `core/post-content`。個別Serviceページを明文要求（§7）に応えるため専用テンプレートを用意した（index.htmlへのフォールバックに頼らず明示）。

### 4.2 Price — Dynamic Block（`astrea/price-list`）＋ Theme Pattern

- 当初はProfessional Profileと同じくQuery Loop + ネイティブ`core/post-meta` Bindingを想定したが、実機wp-envで検証した結果、**WordPressコア自身の`build_query_vars_from_query_block()`（`wp-includes/blocks.php`）が`is_post_type_viewable()`で`postType`属性を検証し、非viewableな投稿タイプの場合は黙って標準の`post`型へフォールバックする**ことが判明した（Query Loopが空を返すのではなく、無関係な`post`投稿を表示してしまう）。Priceは3.2の設計判断により意図的に`public => false` / `publicly_queryable => false`としているため、Query Loopでは正しく動作しない。
- この制約は理論だけで判断せず、実際にPageへQuery Loop Blockを埋め込みwp-env上で描画確認して発見した（推測での実装を避けるという方針に基づく）。
- Decision 013は「構造・処理を伴うもの（一覧・条件分岐・件数可変等）はDynamic Block等を用途に応じて利用する」とすでに書き分けており、「個別URLを持たせたくない可変件数の一覧」はまさにDynamic Blockが想定するケースである。そこで設計を訂正し、Core自身がサーバーサイドレンダリングする**Dynamic Block（`astrea/price-list`、`core/includes/price-list-block.php`）**を新設した。`Astrea\Core\Price\get_prices()`を直接呼び出し、`esc_html()`でエスケープしたセマンティックHTMLを返す。WP_Query/Query Loopの`is_post_type_viewable()`ゲートを経由しないため、Priceの「個別URLを持たない」という設計意図を完全に保ったまま一覧表示を実現できる。
- 0件時は空文字列を返す（見出し・空コンテナを一切出力しない。§8 Empty State要件を自然に満たす）。
- Theme Pattern（`theme/patterns/price-list.php`）はこのDynamic Blockを1つ配置するだけの薄いラッパーとし、ユーザーがHOME・料金ページ等へ任意に挿入できるようにする（Decision 013「Patternが並べる」）。Decision 016（初回セットアップ）に基づき、テーマ有効化時に自動挿入することはしない。
- Block namespaceはDecision 012の「Block namespace: `astrea/*`」を文字どおり適用し、レンダリング実装がCore側にあることとは独立に`astrea/price-list`とした（Block Bindings Sourceの識別子`astrea-core/office-profile`とは別の命名規則を持つWordPressの別レジストリのため、両立に矛盾はない）。

### 4.3 FAQ — Query Loop（アーカイブ＋Term Archive）＋ Plain Semantic HTML

- `archive-astrea_faq.html` / `taxonomy-astrea_faq_category.html`：Query Loop + `post-template`。各FAQ項目は`core/post-title`（質問、H2）+ `core/post-content`（回答）という素のセマンティックな見出し+本文構造で表示する。
- 当初はWordPressコアの`core/details`ブロック（WP 6.5以降のネイティブ`<details>/<summary>`）をAccordion表示に使う案を検討した。`core/details`はブラウザネイティブ要素をそのまま描画するため独自JS・ARIA実装が一切不要という利点がある一方、Query Loopの`post-template`内でsummary領域に`core/post-title`（ループ中の現在の投稿を指す文脈依存Block）を正しくネストできるかは未検証で、誤った場合に「見た目だけの開閉UIとして機能しない」壊れた表示になるリスクがあった。実機で確証を得られない挙動に賭けるより、Construction Order 004指示文§10が明示的に許容する「Accordion不要なら単純なSemantic HTMLを優先」を採用し、確実に正しく動作する構成に倒した。
- Accordion化自体は本工程の必須要件ではなく（§11に開閉UIの要求はない）、将来のPattern/Design System工程で改めて実装可否を判断する（05文書セクション17項目5と同種の、Pattern設計時決定事項として扱う）。
- FAQPage構造化データ（JSON-LD）は実装しない（1.3参照）。

### 4.4 共通化した部分・しなかった部分

- **共通化した**：`pre_get_posts`による確定表示順（`menu_order`→`title`→`ID`）と、「公開済み投稿を安全に1件/複数件取得する」ガード処理。Professional Profile・Service・Price・FAQの4箇所で完全に同一のロジックが重複することが判明したため、`core/includes/shared.php`（`Astrea\Core\Shared`）へ抽出した。Professional Profile側もこの共通関数を呼ぶようにリファクタリングした（公開APIの戻り値・既存テストの挙動は変更していない）。
- **共通化しなかった**：Service/Price/FAQ個別のフィールド定義・サニタイズ規則・管理画面メタボックスは、意味が異なるため個別ファイルに保持した（巨大な汎用Content Frameworkにしないため）。Taxonomy対応はFAQのみで、Service/Priceには追加していない（仕様上の根拠がないカテゴリを機械的に量産しないため）。

---

## 5. Core公開境界（API）

Theme / 将来PROはPost Meta名・CPT名・内部Class構造へ直接依存しない。

- `Astrea\Core\Service\get_service(int $id): ?array` / `get_services(): array`
- `Astrea\Core\Price\get_price(int $id): ?array` / `get_prices(): array`
- `Astrea\Core\Faq\get_faq(int $id): ?array` / `get_faqs(): array` / `get_important_faqs(): array`（Professional Profileの`get_representatives()`と同型の先例パターン） / `get_faqs_for_service(int $service_id): array`（§11「同じFAQを…Serviceページ等で再利用する」の再利用要件に対応する読み取りAPI。Theme側の実装は本工程の最小表示範囲外のため未接続、将来のPattern/Design System工程で利用する）

---

## 6. 残存する要確認事項（新規Decisionではない・独自判断で確定していない）

1. **Price自由記述とschema.org Offer構造化データの完全な整合方法**（3.2参照）。自由記述を維持する限り機械的な数値マッピングができないケースが残る。SEO Foundation関連の将来Construction Orderで判断が必要。
2. **FAQPage構造化データを実装するかどうか**（1.3参照）。Googleの現行ガイドライン（2023年8月以降の実質的な適用制限）を踏まえ、本工程では意味データのみとし、構造化データ出力の要否はSEO Foundation工程へ引き継ぐ。
3. Service/Price/FAQが0件の場合の空状態Pattern統一（05文書セクション17項目5、既存の未決定事項）は、本工程でも解消していない（Pattern/Design System設計時の課題として維持）。
