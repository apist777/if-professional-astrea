# CONSTRUCTION ORDER 011 — 着工前調査 — THEME DISPLAY COMPLETION + SECURITY HOLE AUDIT

**Status:** RESEARCH COMPLETE（製品コード変更なし。`docs/research/`・`HISTORY.csv`のみ更新）
**関連:** 00〜05仕様書、Decision 001〜029（特に021・022・028・029）、Remaining Work Audit（`docs/research/2026-08-27_remaining_work_audit.md`）、Construction 007/008/009/010 各報告

本ドキュメントは、CONSTRUCTION ORDER 011の着工前調査として発令された2件の調査命令——(A) THEME DISPLAY COMPLETION、(B) SECURITY HOLE AUDIT——を1本の報告書に統合したものである。両調査とも実装は一切行っていない。`theme/`・`core/`・`tests/`・`tools/`への変更はゼロである。

調査は2つの独立したサブエージェントに委任し（Theme Display調査／Security調査）、その結果を筆者（クロエ）が検証・統合し、判断が必要な箇所には筆者自身の推奨と、ユーザー判断が必要な事項を明確に分離して記載した。

---

# PART A — THEME DISPLAY COMPLETION

## 1. Theme Display Inventory（表示能力マトリクス）

| データ種別 | Admin登録可能 | Theme表示可能 | HOME表示可能 | Archive | Single | 再利用可能Pattern | 0件安全 |
|---|---|---|---|---|---|---|---|
| Office Profile | Yes（`office-profile.php`、Settings API） | **Partial** — Bindingsで露出するのは`office_name`/`address`/`phone`/`phone_tel`の4スカラーのみ（`block-bindings.php`）。`business_hours`（週次+例外）・`sns_links`（配列）は表示経路が存在しない | Yes（Header/Footer/Hero/CTAで部分利用） | N/A（singleton） | N/A（singleton） | 専用Patternなし（Header/Footer/Hero/CTAへ直接埋め込み） | N/A |
| Professional Profile | Yes（CPT、`professional-profile.php`） | **Partial** — Archiveは氏名/資格/抜粋のみ。**Single Templateが存在しない**（`index.html`にフォールバック、既知の欠落） | Yes（`astrea/representative`、代表者1名のみ） | Yes（`archive-astrea_professional.html`） | **No**（既知の欠落） | No | Yes |
| Service | Yes | Yes（Archive+Single完備） | Yes、ただし**0件で自己非表示できない**（Query Loop方式の限界） | Yes | Yes | 専用Patternなし（`home-services-teaser.php`に直書き） | **No**（唯一の非適合） |
| Price | Yes（非公開CPT） | Yes（Dynamic Block） | Yes（`astrea/price-list`） | No（意図的に非公開） | No | Yes（`price-list.php`） | Yes |
| FAQ | Yes | Yes（Archive+Taxonomy Archive） | Yes（`astrea/faq-list`、重要FAQのみ） | Yes | No（一覧内展開のみ、Single無し） | 専用Patternなし | Yes |
| CASE | Yes | Yes（Archive+Single完備、Construction 010） | Yes（`astrea/case-list`） | Yes | Yes | No | Yes |
| RESULTS | Yes（非公開CPT） | Yes（Dynamic Blockのみ） | Yes（`astrea/results-list`） | No（Priceと同型、意図的） | No | No | Yes |
| VOICE | Yes | Yes（Archive、Construction 010） | Yes（`astrea/voice-list`） | Yes | No（意図的） | No | Yes |
| Contact | Yes（Settings API） | Yes（Contact Form Pattern） | 間接（CTAリンク経由） | N/A | N/A | Yes（`contact-form.php`） | N/A |

**最大のギャップ2件：** ① Professional SingleがそもそもTemplateを持たない、② Services HOME TeaserだけがDecision 028の「HOME Teaserは0件で見出しごと完全非表示」ルールに違反している（Query Loopの構造的限界のため）。

## 2. Professional Single 推奨方式

`to_array()`が公開する実フィールド：`id, name, bio, photo_id, qualification, career, education, affiliation, registration_info, is_representative`。すべて「任意項目」（02仕様書§8）であり、いずれも空値のときにラベル付きの空欄を表示してはならない。`is_representative`はHOME代表者選出用の内部フラグであり、Single上のユーザー向け表示項目としては扱わない。

Single Templateは1投稿＝1URLというWordPress標準のルーティングで解決されるため、「0/1/N人」という懸念はURLの有無そのものに吸収され、Template内部で条件分岐する必要はない——1人目・2人目・N人目で挙動が変わることはない。

**推奨：Block Bindings（`core/post-meta`）＋標準Block。新規Dynamic Blockは不要。**
理由：Construction 008が代表者セクションにDynamic Blockを採用した決定的理由は「0件時にセクション全体を見出しごと1単位として非表示にする」必要があったため（`professional-representative-block.php`のdocblockに明記）。Single Templateにはその「0件を隠す」要件が存在しない（投稿が存在しなければURL自体が存在しない）。`qualification`等はすべてスカラーpostmeta文字列であり、既に`archive-astrea_professional.html`で`core/post-meta` Bindingsの実績があるため、これを踏襲するのがDecision 013の「Blockがつなぐ」層に最も忠実である。

**既知の副次課題：** 既存の`archive-astrea_professional.html`のBindings済み段落は、値が空文字のとき`<p></p>`という空タグを出力する（Bindingsは空文字と未設定を区別しない）。新設するSingle Templateでも同じ性質を引き継ぐことになる。これは新規に生む欠陥ではなく、既存Archiveに既に存在する潜在的な仕様上の割り切りであり、011のSingle新設と同時に対応するか切り離すかはユーザー判断事項とする（§14参照）。

## 3. Office Profile 表示方式

現行データ構造（`office-profile.php`）：
```php
array(
  'schema_version' => 2,
  'office_name'    => '', 'address' => '', 'phone' => '',
  'business_hours' => array(
    'weekly'     => [ 'mon'=>['closed'=>true,'open'=>'','close'=>''], ... 'sun'=>... ],
    'exceptions' => array(),  // 各要素: ['label','start_date','end_date']
  ),
  'sns_links' => array(),      // 各要素: ['label','url']
)
```
Block Bindingsは仕様上スカラー属性しか扱えず（`block-bindings.php`自身のdocblockが明言）、週次営業時間（曜日ごとの休業判定＋例外日レンジ）も、SNSリンク（可変長配列）も、構造的にBindings1本では表現できない。

**推奨：新規Dynamic Block 1〜2個**（例：`astrea/office-hours`、`astrea/office-sns`）。既存の`astrea/price-list`・`astrea/results-list`が採用した「一覧・条件分岐・件数可変を伴うものはDynamic Block」という確立済み判断基準（Decision 013）にそのまま合致する。Pattern＋Bindingsハイブリッドは、ハイブリッドが機能するのは「構造部分が独立スカラーに還元できる場合」（既存4フィールドがまさにそれ）に限られ、営業時間・SNSはその条件を満たさないため不採用。

Decision 022の境界（Office/Professional/ACCESS/CTAの分離）は厳守——本項の対象はOffice Profileが**既に保持している**住所・営業時間・SNSのみであり、ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図方式）やCTA固有データモデルには一切踏み込まない。

## 4. Setup「事務所概要」ページとの統合方針

現行の生成コンテンツ（`setup-pages.php`）は固定プレースホルダ1段落（「ここに事務所の紹介文を入力してください。」）のみで、`draft`状態・既存ページ保護ガード（`page_still_exists()`）により一度生成されたページは二度と上書きされない。

**推奨方針：** Construction 009のHOME組み立て支援と同じ原則（＝既存の生成済み・ユーザー編集済みページには一切触れない）を踏襲する。
- 上記③のOffice表示Dynamic Blockが実装された場合、それを**新規生成時のコンテンツにのみ**追記する（既存の生成済みページは無変更）。
- 加えて、新Blockは独立した挿入可能Pattern（`price-list.php`と同型）としても提供し、既にSetupを実行済みのサイトでもBlockエディタから手動挿入できる退避経路を用意する——これは新規コード側に「既存コンテンツを書き換える」分岐を一切追加しない、最も低リスクな選択。

## 5. Services HOME Teaser 0件問題の解決方針

`home-services-teaser.php`は見出し（`<h2>取扱業務</h2>`）がQuery Loopの**外側**にあるため、0件時でも見出し＋「準備中」メッセージが残ってしまう（自ファイルのdocblockが自認する既知の限界）。他の全HOME Teaser（Price/FAQ/代表者/CASE/RESULTS/VOICE）はDynamic Blockで見出しごと完全非表示を達成済みであり、Serviceだけが取り残されている。

3案の比較：
- **(A) `astrea/service-list` Dynamic Blockのみ新設** — 既存6 Blockと同一の`heading`/`emptyMessage`/`limit`属性規約に完全準拠。新規ファイル1本＋`home-services-teaser.php`の差し替えのみで完結し、既存の緑化済みコードには一切触れない。
- **(B) Price/FAQ/CASE/RESULTS/VOICE/Serviceを1つの汎用Listing Blockへ統合** — 既に緑化・テスト済みの6つのrender関数を作り直す。各Blockの項目マークアップは実際には大きく異なる（Price=名称+金額+注記、FAQ=質問+リッチ本文、VOICE=blockquote/cite、RESULTS=値+ラベル、CASE=リンク付きタイトル+抜粋）ため、「汎用化」しても内部でタイプ別分岐が必要になり、統合の動機自体が薄い。
- **(C) 現状維持・Post v1へ先送り** — Decision 028のTeaser完全非表示ルールに違反した状態が、最も多くのサイトが最初に埋めるであろうService欄で継続する。

**推奨：(A)。** 命令書自身が「統合それ自体を目的化しない」「大量の緑化済みコードを書き直すほどの価値がなければ最小追加を優先する」と明記しており、ここではその条件に正確に合致する——(B)はユーザー向けの便益を何も追加せず、6ブロック分の回帰リスクだけを負う。

## 6. Price / FAQ / Contact の表示品質判定

- **Price：** 構造自体は健全（名称・金額・注記・複数件・改行対応）。ただし`price.php`の`group`（グループ分け用meta）が保存・サニタイズされているのに`price-list-block.php`側で一切読まれておらず、グループ数の多い事務所（相談料/着手金/報酬等）は常にフラット表示になる。新規CSSは不要（既存の見出しスタイルで`<h3>`グループラベルを追加するだけの構造変更）。
- **FAQ：** 構造は健全。Accordion等の新規インタラクティブUIは命令書により明示的に禁止されており、現状のフラット展開表示（アクセシビリティ上むしろ有利）に変更の必要なし。
- **Contact：** 全体的に高品質（`label for`／`aria-required`／`aria-invalid`+`aria-describedby`／エラー`role="alert"`／成功`role="status"`／honeypotのアクセシブルな隠し方、いずれも適切）。ただし送信ボタン・入力欄に`wp-element-button`等のtheme.json連携クラスが付与されておらず、3 Style Variationの`elements.button`トークン（枠丸み・色）を自動継承できていない——他のCTAボタン（`home-cta.php`は`wp-element-button`使用）と見た目が食い違うリスクがある。修正はクラス追加のみで新規CSS不要。

いずれもConstruction 005のビジネスロジック・Securityには一切踏み込んでいない（提示品質のみの評価）。

## 7. CASE / RESULTS / VOICE 表示完成度

Construction 010のArchive/Single/HOME Teaserいずれも構造的な欠陥は見つからなかった。CASE Singleに関連Service一覧を表示する余地（`get_cases_for_service()`は既に実装済みだがTheme未配線）はFAQの`get_faqs_for_service()`と同じ「将来のPattern作業」として意図的に未接続のままであり、欠陥ではない。VOICE Archiveの見出しレベルのみ§9で別途指摘する。データモデル・Security判断（Construction 010で確定済み）は再論しない。

## 8. Skip Link 推奨実装

現状、Skip Linkは`theme/parts/header.html`・`footer.html`・Core側のいずれにも存在しない。WordPress Block Theme標準の実装（`wp_body_open`フック経由、JS不要）：
```html
<a class="skip-link screen-reader-text" href="#wp--skip-link--target">メインコンテンツへスキップ</a>
```
全15 Templateが例外なく`<!-- wp:group {"tagName":"main",...} -->`という同一パターンで`<main>`を1つだけ持つことを確認済みであり、この単一のGroup-as-mainブロックに`id="wp--skip-link--target"`を付与すれば、Template個別編集なしに全ページで一貫したターゲットになる。WordPress coreはBlock Themeがこの仕組みを使うことを既に前提として設計しており（`wp_body_open`経由のSkip Link注入は core標準の挙動）、独自JSは一切不要。

## 9. Landmark再監査結果

Construction 008で発見・修正した「二重Landmark」バグ（`templateParts`のarea宣言が既に`<header>`/`<footer>`でラップしているところへ、内側GroupへtagName上書きを追加して二重化する）の**再発は確認されなかった**。`header.html`・`footer.html`とも内側Groupに`tagName`上書きは一切なく、`theme.json`の`templateParts`宣言のみに委ねている。全15 Templateが`<main>`を1つずつのみ保持。`<nav>`は`astrea/breadcrumb`（`aria-label="パンくずリスト"`付与済み）とcore `wp:navigation`のみで重複なし。

## 10. Heading階層監査結果

- **重大な欠落：front-page.html（HOME）にH1が一切存在しない。** `home-hero.php`は事務所名を`<p>`（大フォントサイズClassのみ）として出力しており、Heading Blockではない。ページ全体で最初に現れる見出しは`home-services-teaser.php`の`<h2>取扱業務</h2>`（またはTrust/Flow次第）——H1が皆無のまま複数のH2が並ぶ状態。
- **`archive-astrea_voice.html`のH1→H3飛び：** query-titleのH1直下で`post-title`が`level:3`（他の全Archiveはlevel 2で統一：FAQ/CASE/Service/Professional/HOME/検索いずれもlevel 2）。VOICEだけの例外。
- その他（404.html、Single系、HOME以下のPattern群）はH2→H3の階層が内部的に一貫している。

## 11. HOME全体構成の評価（推奨のみ・変更提案）

現行順序：Hero→Services→CASE→RESULTS→Professional→Price→FAQ→VOICE→Flow→CTA。

- **Day-one空サイト：** Hero（常時表示）→ Services（現状バグにより「準備中」の空見出しが残存、§5修正後は完全非表示）→（CASE/RESULTS/Professional/Price/FAQ/VOICEはすべて自己非表示）→ Flow→CTA。§5修正後は短いが破綻しないページになる。
- **データ充実サイト：** 何をしているか（Service）→ 実績の証拠（CASE）→ 定量的な実績（RESULTS）→ 誰が対応するか（Professional）→ 費用（Price）→ 懸念解消（FAQ）→ 社会的証明（VOICE）→ 進め方（Flow）→ 行動喚起（CTA）という、一般的な信頼構築の流れとして不自然ではない。
- **1人 vs N人の専門家：** `astrea/representative`は代表者フラグが立った1名のみを表示する仕様（意図的、既存ドキュメント通り）。N人事務所でもHOME側の挙動は変わらず、全員を見るには`archive-astrea_professional.html`への導線が必要——欠陥ではなく既定のスコープ。
- **参考程度の提案（決定ではない）：** RESULTS（定量実績）をCASE（個別事例）の前後どちらに置くか、あるいはTrust（現状HOME自動組み立てから意図的に除外）をHeroの直後に置くかは content-strategy上の好みの範疇であり、技術的な問題ではない。変更が必要と判断する場合は正式な提案として別途上程する。

## 12. Style Variation適合性

`trust.json`/`natural.json`/`modern.json`はいずれも`settings.color.palette`・`settings.typography.fontFamilies`・`styles.elements.button.border.radius`のみを変更し、Template/Patternのマークアップには触れていない——既存ルールに適合。本報告で提案する新規表示（Professional Single、Office表示Block）も、既存Dynamic Block群と同様「セマンティックHTML＋theme.jsonトークンclass、ハードコード色/フォントなし」で実装すれば自動的に適合する。唯一のリスクはContact Formの未クラス化ボタン（§6）——Variation固有の分岐ではなく、単にトークンを拾えていないだけ。

## 13. モバイル/320px時点メモ（本011スコープ内のみ）

フルレスポンシブ監査はConstruction 012の範囲。本調査で見つかった限定的な懸念：Header内の事務所名（fluid font-size）と電話番号ボタンが同一flex行にあり、320px幅・長い事務所名の組み合わせで折返しが窮屈になる可能性（実機未検証、要ブラウザ確認）。Trustの3カラムはcore標準のレスポンシブ挙動に委ねており低リスク。

## 14. Core非活性時の安全性

新設提案するDynamic Block（Office表示）は、既存6 Dynamic Blockと同じくCore側`register_block_type()`が実行されないだけで、未登録Blockコメントとして無害に無出力となる（明示的なガードコード不要、既存の暗黙的挙動をそのまま踏襲）。Professional Single（Bindings方式）は、Core非活性時は`block-bindings.php`自身のガード（`function_exists`チェック）によりBindingsソース自体が未登録となり、各Blockは静的フォールバック内容を表示する——ただし新設Single Templateには意味のある静的フォールバック文言がないため、Core非活性時は複数の空欄が並ぶことになる。これは既存の`archive-astrea_professional.html`の空`<p></p>`と同種の性質であり新しいリスク種別ではないが、フィールド数が多い分影響が目立つため、ユーザー判断事項として明記する（§14相当、後述の§18参照）。

## 15. WordPress.org Theme Review関連フラグ（本011が触れる範囲のみ）

Escaping・Translation・Plugin Territoryはいずれも問題なし（`esc_html`/`esc_url`/`esc_attr`/`wp_kses_post`の一貫使用、`__()`系関数の一貫使用、Theme/Core責任境界の明文化）。Accessibility上の実質的な指摘は§10のHOME H1欠落とVOICE見出しレベルの2点、およびSkip Link不在（§8）——いずれもTheme Review水準でも指摘され得る一般的な項目。

## 16. 011施工範囲分類の提案

| 項目 | 分類 | 理由 |
|---|---|---|
| Professional Single | **Release Blocking** | 既知の欠落（`index.html`フォールバック）。ArchiveはあるのにSingleがないCPTはv1として不完全 |
| Services HOME 0件問題 | **Release Blocking** | Decision 028への明白な違反。day-oneサイトで最も目立つ箇所 |
| HOME H1欠落 | **Release Blocking** | 公開マーケティングサイトのTOPページにH1が皆無はSEO/Accessibility上の一次的欠陥 |
| Office Profile表示（営業時間/SNS） | **Recommended** | データは既に収集・サニタイズ済みだが表示経路ゼロ。実害はあるがBlocking水準ではない |
| Setup事務所概要統合 | **Recommended**（Office表示と対） | 上記が実装されて初めて意味を持つ、追加コストは小さい |
| Contactボタンのクラス付与 | **Recommended** | 1行修正でStyle Variation間の一貫性が大きく改善 |
| VOICE見出しレベル修正 | **Recommended** | 小さいが容易な修正、H1欠落修正と合わせて実施が効率的 |
| Skip Link | **Recommended** | アクセシビリティの基本要件、JS不要・低コスト |
| Price グループ表示 | **Construction 012へ先送り** | 未使用meta（`group`）はあるが実害は小さい、化粧直し水準 |
| FAQ / CASE・RESULTS・VOICE 表示ポリッシュ | **Post v1** | 現状で仕様§11水準を満たしており欠陥なし |
| Landmark修正 | **対応不要**（Post v1相当） | 再監査の結果、再発なし |

## 17. Test Strategy提案（未実装、提案のみ）

本プロジェクトの既存慣行（`tools/ci/smoke-test.sh`、実wp-env `http://localhost:8888`）どおり、実HTTP/ブラウザ検証を主、PHPUnitを補助とする：
1. 新設・変更する各画面（Professional Single、事務所概要ページ、HOME）を実際にcurl/ブラウザで取得し、期待フィールド値の存在と空タグ（`<p></p>`等）の不在を確認。
2. 0件状態（Core データ空／Core非活性）でHOME/該当ページを再取得し、Services Teaser修正後の完全非表示をバイト単位で確認（既存Price/FAQ/CASE/RESULTS/VOICEの検証手法をそのまま流用）。
3. Core非活性化状態での新規Professional Single・Office表示ページのFatal/Warning不在確認。
4. 3 Style Variationを切り替えてContact Formボタン・新設Office Block・新設Professional Singleの見た目一貫性を確認。
5. レンダリング後の実HTMLに対しH1が1つだけ存在し、見出しレベルが飛んでいないことを確認する軽量チェック。
6. 日本語実データの耐久テスト（長い氏名・全角半角混在・長い経歴文の折返し等）はConstruction 012へ明示的に先送り。

## 18. 未解決の要確認事項（ユーザー判断が必要、勝手にFIXしない）

1. Professional Singleの項目**表示順**（写真→氏名→資格→経歴→学歴→所属→登録情報→紹介文、または紹介文を先頭にする等）——仕様上の指定なし。
2. 既存の空meta時`<p></p>`問題（`archive-astrea_professional.html`）を011で一緒に直すか、切り離して別途扱うか。
3. Office表示Blockを1本（`astrea/office-info`等、営業時間+SNS統合）にするか、2本（`astrea/office-hours`＋`astrea/office-sns`）に分けるか——事務所概要ページのPattern構成に影響する。
4. 既にSetupを実行済み（プレースホルダのまま）のサイトへの移行パスを用意するか、それとも「新規生成のみ改善・既存は現状維持のまま」で良しとするか。
5. `astrea/service-list`（§5案A）を単なるHOME Teaser用Blockとして作るか、Priceのような独立ページPattern用途まで広げるか。
6. HOME H1欠落の修正方法として、Heroの事務所名Paragraph（Bindings接続済み）をHeading Blockに変えることの可否——現状はタグライン段落と近い視覚ウェイトで共存しているため、H1化した際の見た目調整が必要になる可能性。
7. §11のHOME順序再検討を、正式な提案として別途上程すべきか、記録のみに留めるか。

---

# PART B — SECURITY HOLE AUDIT

**前提：** ASTREA FREE v1を「第三者が攻撃する」という前提で、Construction 001〜010全体を横断的に再監査した。既存Security Testの再実行ではなく、Input→Save→Read→Output の実データフローを個別に追跡し、可能な範囲でローカルwp-env（`http://localhost:8888`）に対して安全な攻撃シミュレーションを実施した（本番相当環境・外部環境には一切接続していない）。実施した検証内容と結果のみを記録し、検証していない事項は「未検証」として明示する。

## 19. 監査結果一覧（Attack Surface別）

| # | Attack Surface | Finding | Severity | 対象 | 前提条件 | 影響 | 検証内容（Evidence） | 推奨対応（未適用） | Release-Blocking |
|---|---|---|---|---|---|---|---|---|---|
| 1 | REST API — Priceの`show_in_rest`矛盾 | `astrea_price`は`public=false`だが`show_in_rest=true`。WordPress REST APIは`public`ではなく`show_in_rest`のみでコントローラ登録を判定するため、未認証で`/wp-json/wp/v2/astrea_price`から公開済みPrice投稿のtitle/id/slugが取得できる。metaは別要因（下記#6）により漏洩しない。 | **MEDIUM** | `core/includes/price.php:81,86`（実測値確認済み） | 未認証 | 「非公開のはず」というコード自身の設計意図と矛盾。ただしPriceのtitleは元々`astrea/price-list` Blockで公開表示される情報であり、機微情報漏洩ではない | 実機：公開Price投稿を作成→`curl /wp-json/wp/v2/astrea_price`が200でtitle/id/slug/linkを返却。draft状態は401で正しく除外 | `show_in_rest => false`に変更する（管理画面の従来メタボックスはREST非依存で動作するため副作用なし） | No（機微性低・設計意図との矛盾のみ） |
| 2 | REST API — Resultの同型矛盾 | `astrea_result`も同じく`public=false`だが`show_in_rest=true`。 | **MEDIUM** | `core/includes/result.php:55,60`（実測値確認済み） | 未認証 | #1と同様（label/valueの実績統計値） | 実機：公開Result投稿を作成→REST経由で取得成功を確認 | 同上 | No |
| 3 | REST API — Inquiry（機微データ）の非露出確認 | `astrea_inquiry`は`show_in_rest=false`で正しく非公開。 | **NONE-FOUND** | `core/includes/inquiry.php:85`（実測値確認済み） | — | — | 実機：`curl /wp-json/wp/v2/astrea_inquiry` → `404 rest_no_route`（ルート自体が未登録） | — | No |
| 4 | 保存型XSS — CASE/FAQ/VOICE/Professionalのtitle/content | 当初`wp post create`（WP-CLI）でスクリプトタグがエスケープされずに残ることを観測したが、これはWP-CLIが管理者コンテキストなしで`wp_insert_post()`のkses経路を実質バイパスする既知の挙動によるものと判明（本物のHTTPリクエスト経路の反映ではない）。実際のCapabilityコンテキスト（Contributor＝`unfiltered_html`権限なし）で同一ペイロードを`wp_insert_post()`へ直接投入し再現したところ、WordPress標準の`wp_filter_post_kses()`が正しく`<script>`タグを除去することを確認。全Dynamic Block（`price-list-block.php`, `faq-list-block.php`, `case-list-block.php`, `results-list-block.php`, `voice-list-block.php`, `professional-profile-block.php`, `breadcrumb.php`）のrender関数を全文読み込み、`esc_html()`/`esc_url()`/`wp_kses_post()`なしの生echoがゼロであることを確認。 | **NONE-FOUND**（当初の誤検知を訂正済み） | 上記ファイル群 | Administrator/Editor（WordPress標準で`unfiltered_html`を持つ最上位2ロール）のみ生スクリプトを保存できるが、これはASTREA固有の弱化ではなくWordPress自体の既定の信頼モデル | 該当ロールは既にサイト全体を掌握できる権限を持つため、追加の実害なし | 実機二重検証（誤検知の原因特定含む）＋全Dynamic Block全文精読 | — | No |
| 5 | JSON-LD／構造化データへのスクリプト注入再検証 | `wp_json_encode($data, JSON_UNESCAPED_UNICODE \| JSON_HEX_TAG)`を使用、文字列結合は一切なし。 | **NONE-FOUND** | `core/includes/seo-structured-data.php:237-239` | — | — | 実機：Office Profile名を`Evil</script><script>alert(1)</script>"Office\Name`に設定→`/`のJSON-LD内で`<\/script>`等に正しくエスケープされ、リテラルな`</script>`・生の引用符・バックスラッシュは一切出現しないことを確認 | — | No |
| 6 | REST経由の機微postmeta露出 | 全CPTの`register_post_meta()`は`show_in_rest=true`だが、`WP_REST_Posts_Controller`は投稿タイプが`supports`に`'custom-fields'`を宣言していない限り`meta`フィールドをスキーマに追加しない——**ASTREAのどのCPTも`custom-fields`をsupportsに含めていない**ため、登録済みmeta（Price金額/注記、Professional資格/経歴、related_services等）はREST応答に一切出現しない。 | **NONE-FOUND**（推測でなく実測） | 全`core/includes/*.php`のCPT登録箇所 | — | — | 実機：Professional資格metaに`Bar-Secret-License-12345`を設定→当該投稿の完全なREST JSONに`meta`キー自体が存在しないことを確認。`/wp-json/wp/v2/types/astrea_price`等のスキーマにも`meta`プロパティ不在を確認 | （将来Gutenbergメタパネル対応等で`custom-fields`supportを追加する場合は、その時点で`auth_callback`を再監査すること） | No |
| 7 | CSRF — Contact Form送信 | Nonceは`$_POST`のみから検証（`$_REQUEST`は使わない＝GET経由のNonce混入不可）。 | **NONE-FOUND** | `core/includes/contact-form-block.php`（送信ハンドラ） | — | — | 実機：(a) GETで送信action URLを叩く→302リダイレクト、`astrea_inquiry`投稿は作成されず。(b) Nonceなしの全項目POST→同様に作成されず | — | No |
| 8 | CSRF — 既読化/データ削除/Setup生成系アクション | 読み込んだ全ハンドラ（`handle_mark_read`, `handle_delete`, `handle_generate_pages`, `handle_generate_navigation`, `handle_generate_home_page`, `handle_request_email_confirmation`, `handle_export`）が`check_admin_referer()`＋`current_user_can()`を備える。`_nopriv_`版は登録されておらず`admin-post.php`自体が未ログインを弾く。 | **NONE-FOUND** | `inquiry-admin.php`, `data-deletion.php`, `setup-pages.php`, `setup-navigation.php`, `setup-home.php` | — | — | 実機・未認証：既読化action・データ削除actionへのGETがいずれも**HTTP 400**（ハンドラ到達前に拒否） | — | No |
| 9 | 権限昇格 | 全admin-postハンドラ・全設定画面が`manage_options`（サイト全体設定：Office/Contact/SEO/データ削除/Setup）または`edit_post`（投稿単位メタ：Professional/Price/FAQ/CASE/RESULTS）を先頭で判定——過不足なく使い分けられている。 | **NONE-FOUND** | 全`-admin.php` | — | — | 全`add_action('admin_post_...')`/`add_action('save_post_...')`を精読、Capability判定が常に最初のステートメントであることを確認 | — | No |
| 10 | メールヘッダインジェクション | `wp_mail()`呼び出し3箇所。Subject等は`sanitize_text_field()`（CR/LFを空白に潰す）、通知先メールは`sanitize_email()`+`is_email()`のトークン確認フロー経由のみで、公開フォームから直接渡ることはない。 | **NONE-FOUND** | `inquiry-notifications.php:44-53,103-111`, `inquiry-email-confirmation.php:45-83` | — | — | 実機：`victim@example.com%0ABcc:...`、生の`\r\n`、`\nContent-Type:...`の3種のCRLF/パーセントエンコード注入ペイロードを`sanitize_email()`へ投入→いずれもCR/LF/コロン/パーセント/スラッシュが除去され、追加ヘッダ・追加宛先の注入経路なし | — | No |
| 11 | SQLインジェクション | `core/`・`theme/`全体で`$wpdb`使用ゼロ。全クエリは`WP_Query`/`get_posts()`（`absint()`済みID・固定meta key/valueのみ）。`meta_query`の使用は1箇所のみでユーザー入力なし。 | **NONE-FOUND** | 全コードベース | — | — | `grep -rn '\$wpdb'` → 0件。`meta_query\|tax_query`使用箇所を精読 | — | No |
| 12 | オープンリダイレクト | 全リダイレクトは`wp_safe_redirect()`（ホスト許可リスト方式）または固定`admin_url()`/`add_query_arg()`。危険な`wp_redirect()`の使用はゼロ。 | **NONE-FOUND** | `contact-form-block.php`, `data-deletion.php`, `setup-*.php`, `theme/functions.php` | — | — | `grep -rn 'wp_redirect('` → 0件、全リダイレクト箇所を精読 | — | No |
| 13 | URLスキーム — Office Profile SNSリンク | `sanitize_sns_links()`が`esc_url_raw($url, ['http','https'])`でプロトコル明示許可リスト方式を採用、`javascript:`/`data:`を拒否。 | **NONE-FOUND** | `core/includes/office-profile.php:456-495` | — | — | 実機：`javascript:alert(1)`・`data:text/html,<script>...`を投入→両方とも保存時に破棄され、正規の`https://`URLのみ残存 | — | No |
| 14 | SSRF | `wp_remote_get/post`・`media_sideload_image`・`curl_exec`・URL付き`file_get_contents`のいずれもCore内に存在しない。GA4の`googletagmanager.com`はブラウザ側の読み込みでありサーバー側リクエストではない（意図通り）。 | **NONE-FOUND** | 全コードベース | — | — | `grep -rn "wp_remote_\|media_sideload_image\|curl_exec\|file_get_contents("` → 0件 | — | No |
| 15 | ファイル/メディアアップロード | `$_FILES`・`move_uploaded_file`・`wp_handle_upload`のいずれも不使用、全画像処理はWordPress Media Library標準機構（`wp_get_attachment_image`等）に完全委譲。 | **NONE-FOUND** | 全コードベース | — | — | `grep -rn "move_uploaded_file\|\$_FILES\|wp_handle_upload"` → 0件 | — | No |
| 16 | Dynamic Block属性のエスケープ | `heading`/`emptyMessage`は全Blockで`esc_html()`、`limit`は常に`(int)`キャスト後`array_slice()`へ。全6ファイルを全文精読し例外なし。 | **NONE-FOUND** | `{price,faq,case,results,voice}-list-block.php`, `professional-profile-block.php` | — | — | 全文精読 | — | No |
| 17 | Block Bindings出力コンテキスト安全性 | `get_bound_value()`は`key`引数を4項目の許可リストに限定し、想定外キー・空値には`null`（WordPress標準の静的コンテンツへの安全なフォールバック）を返す。`phone_to_tel_uri()`は数字/`+`/`-`以外を除去。Core非活性時はBindingsソース自体が未登録となり静的フォールバックへ委ねられる（Decision 021に合致）。 | **NONE-FOUND**（1点未検証あり） | `core/includes/block-bindings.php` | — | — | 全文精読。ただし「Bindings APIが属性タイプごとに適切なエスケープを自動適用する」というWordPress core自体の契約は、電話番号のような値をButtonのURL属性に束縛した際の実レンダリング結果としては今回未実機検証（静的解析＋文書化済み仕様に基づく結論） | 追加検証推奨：`phone_to_tel_uri()`通過後も残る値をButtonのurl属性へ束縛し、実際の`href`出力を確認する | No |
| 18 | データ削除 — 確認ゲートの回避可能性 | `handle_delete()`はCapability→Nonce（失敗時die）→チェックボックス→完全一致フレーズ（`!==`厳密比較、大小文字/部分一致を許さない）の4段ゲート。削除対象は固定配列でリクエストからの拡張余地なし。 | **NONE-FOUND** | `core/includes/data-deletion.php:174-215,228-255` | — | — | 全文精読。未認証GETは実機でHTTP 400（アプリロジック到達前に拒否）を確認。認証済みでのゲート突破は共有開発環境のデータ破壊を避けるため未実施だが、コード上バイパス可能なパラメータは存在しない | — | No |
| 19 | データ削除 — 影響範囲の封じ込め | 生成済みPage・Navigation・Media Library添付ファイルは削除対象から明示的に除外（ファイル冒頭コメント・実装とも一致）。 | **NONE-FOUND** | `core/includes/data-deletion.php:17-30` | — | — | ソースコードとドキュメントの一致を確認、`wp_navigation`/`page`/`attachment`に触れるコードパスなし | — | No |
| 20 | Setup生成系（Page/Navigation/HOME）— CSRF・重複・上書き | 全ハンドラがCapability+Nonceでゲート。全生成関数が追跡済みID Option（`GENERATED_PAGES_OPTION`）を事前チェックし、再実行は安全な無処理となる。Navigationのリンクラベル/URLはCore自身の既公開コンテンツ（`get_permalink()`等）由来のみで、リクエスト由来ではない。 | **NONE-FOUND** | `setup-pages.php`, `setup-navigation.php`, `setup-home.php` | — | — | 全文精読、冪等性ヘルパー（`page_still_exists()`）含め確認 | — | No |
| 21 | Cron（保持期間クリーンアップ・ダイジェスト） | 両コールバックとも引数ゼロで登録され、リクエストデータを一切読まない。WP-Cronの外部HTTPトリガー可能性（`wp-cron.php`）自体は攻撃者に追加パラメータを注入する経路にはならない。 | **NONE-FOUND**（過剰実行による軽微な負荷のみ、脆弱性ではない） | `inquiry.php:359-405`, `inquiry-notifications.php:20,75-114` | — | `wp-cron.php`への連打で余分なDBクエリが発生し得るが、これはWordPress全体に共通する一般的特性でありASTREA固有ではない | 全文精読、`$_REQUEST`/`$_GET`アクセスなしを確認 | — | No |
| 22 | Cron — deactivate時の解除確認 | `register_deactivation_hook`のコールバック（`core/astrea-core.php`の`deactivate()`）が`Inquiry\clear_cleanup_cron()`・`Inquiry\clear_digest_cron()`を呼び出すことを確認**（初回サブエージェント報告では未検証としていたが、筆者が追加確認しNONE-FOUNDへ更新）**。 | **NONE-FOUND**（確認完了） | `core/astrea-core.php:125-147` | — | — | `core/astrea-core.php`の`deactivate()`関数を直接精読し確認 | — | No |
| 23 | 情報漏洩 | `WP_DEBUG_DISPLAY=false`（開発環境設定として確認、ASTREAコードの管轄外）。URL反射のある唯一の値（`astrea_setup_home_error`）は出力時に`esc_html()`済みで、値の発生源も自ハンドラ内の固定`WP_Error`メッセージ3種のみ（攻撃者制御不可）。スタックトレース・生パス・Nonce値・管理者メールアドレスの漏洩箇所は発見されず。 | **NONE-FOUND** | `setup-admin.php:76-84`、wp-env設定 | — | — | 全文精読＋実機で`WP_DEBUG_DISPLAY`が`false`であることを確認 | — | No |
| 24 | 問い合わせデータの到達可能性 | `astrea_inquiry`は`show_ui=false`、`show_in_rest=false`、`exclude_from_search=true`、`create_posts=>'do_not_allow'`。公開経路（Shortcode/Block/Template）は存在せず、Capabilityゲート済み管理画面のみが読み取り経路。 | **NONE-FOUND**（防御多層性の注記1件あり） | `core/includes/inquiry.php:75-94` | — | — | 実機：REST 404確認済み。静的：他ファイルからの公開表示参照なし | **注記（脆弱性ではない）：** 保持期間クリーンアップCronと`admin_init`発火のキャッチアップ安全網が両方とも長期間動作しない場合（管理者が全くログインしないサイト）、保持期間超過後も問い合わせデータが残り続ける——WP-Cronのアクセス駆動特性に起因する一般的な性質で、コード自体が既にこのトレードオフを文書化済み | No |
| 25 | Core非活性時のフェイルセーフ | `tools/ci/smoke-test.sh`（1699行）がOffice Profile・Professional Archive・Service/Price/FAQ・Migration・代表者フラグ・0件状態を含む広範囲でCore有効化/無効化サイクルを繰り返し、`check_no_fatal`/`fetch_no_fatal_any_status`でFatal不在を検証済み。各`*Test.php`のdeactivate系テストのdocblockが「実機検証はsmoke-test.shが担当」と明記——意図的な役割分担。 | **NONE-FOUND**（テストカバレッジの静的確認、実行はしていない） | `tools/ci/smoke-test.sh`, `tests/*Test.php` | — | — | スクリプト精読によりカバレッジ構造を確認。共有開発DB/Plugin有効化状態を変更するリスクを考慮し、監査中の実行は見送った | 通常のRelease Gatingの一環として`tools/ci/smoke-test.sh`を独立して1回実行し、最新のPASS/FAILシグナルを得ることを推奨 | No |
| 26 | 依存関係／サプライチェーン | `core/composer.json`は存在せず（PHPランタイム依存なし）。ルート`package.json`の`devDependencies`は`@wordpress/env`のみ（配布物に含まれないローカルツール）。外部スクリプト参照はGA4の`googletagmanager.com/gtag/js`のみ（意図通り、GA4測定ID設定時のみ・プライバシー通知文言あり）。 | **NONE-FOUND** | `core/`, `package.json` | — | — | `composer.json`不在確認、`package.json`全文確認、`grep -rn "https\?://"`で既知安全ドメイン（schema.org/wordpress.org/w3.org/gnu.org）以外の残存なしを確認 | — | No |

## 20. WordPress Security API使用箇所の文脈確認（サンプル抜粋）

| 箇所 | 使用関数 | 文脈 | 適切か |
|---|---|---|---|
| `office-profile.php` `sanitize_sns_links()` | `esc_url_raw($url, ['http','https'])` | 保存時のURL検証（出力エスケープではない） | Yes — スキーム制限付き保存時サニタイズとして正しい選択 |
| `seo-structured-data.php` `print_json_ld()` | `wp_json_encode(..., JSON_HEX_TAG)` | `<script type="application/ld+json">`内部 | Yes — JSON文脈であり`esc_html()`は不適切（有効なJSONを破壊する） |
| `seo-meta.php` `output_ogp()` | `esc_attr()` | `<meta content="...">`属性値 | Yes — 属性文脈 |
| `case-list-block.php` | title/excerptに`esc_html()`、`get_permalink()`に`esc_url()` | 同一行内でHTML本文とhref属性が混在 | Yes — 値ごとの文脈に応じた使い分けができている |
| `data-deletion.php` | `current_user_can('manage_options')` | サイト全体に影響する破壊的操作 | Yes — 複数投稿タイプ・グローバル設定を削除する操作として適切な広さ |
| `price-admin.php` `save_meta()` | `current_user_can('edit_post', $post_id)` | 投稿単位メタ保存 | Yes — 過剰な`manage_options`ではなくWP標準のメタボックス慣行に一致 |
| `contact-form-block.php` `handle_submit()` | `wp_verify_nonce()`（`$_POST`のみ参照） | 未認証・公開フォームエンドポイント | Yes — `$_REQUEST`を意図的に避け、GET経由のNonce混入を閉じている |

`esc_html()`を`esc_attr()`/`esc_url()`が必要な箇所で誤用している例は発見されなかった（逆方向も同様）。

## 21. 誤検知防止の徹底（実施内容）

1. **保存型XSSの誤検知と訂正：** `wp post create`での初回観測が誤りだったことを、低権限ユーザーコンテキストでの再実験により明確に否定・記録した（§19 #4）。
2. **「WordPress coreが処理するはず」という前提を全て実機/一次情報で検証：**
   - `meta`フィールドがRESTスキーマに載るのは`custom-fields`サポート時のみ、という契約 → 実機確認済み（#6）。
   - `wp_safe_redirect()`のホスト許可リスト機構 → WordPress core自体の安定した既定契約として引用（他ドメインへの到達性検証まではローカル監査のスコープ外として明示）。
   - Administrator/Editorの`unfiltered_html`既定権限 → `populate_roles()`のcore既定挙動として引用し、Contributorとの比較実験で整合性を確認。
   - Block Bindings APIの属性種別ごとの自動エスケープ → **未実機検証と明記**（#17）、断定していない。

## 22. Security Audit Summary（重篤度別集計）

- **CRITICAL：** 0件
- **HIGH：** 0件
- **MEDIUM：** 2件（Price CPT・Result CPTのREST露出、いずれも設計意図との矛盾であり機微データ漏洩ではない）
- **LOW：** 0件
- **INFO：** 1件（問い合わせデータの保持期限超過に関する多層防御上の注記。Cron解除の未検証だった1件は筆者が追加確認しNONE-FOUNDへ更新済み）
- **NONE-FOUND（明示的に検証し問題なし）：** 約21項目（XSS、CSRF、権限昇格、SQLi、SSRF、オープンリダイレクト、メールインジェクション、ファイルアップロード、JSON-LD注入、Block属性エスケープ、Bindings安全性、データ削除の封じ込め、Setup生成の安全性、Cron引数安全性、依存関係、Deactivation時フェイルセーフ）

## 23. Release-Blocking Security修正の有無

**なし。** MEDIUM 2件（Price/Result CPTのREST露出）は実装意図との矛盾として修正を推奨するが、露出する情報はいずれも本来Dynamic Blockで公開表示される内容（タイトルのみ）であり、機微postmeta・下書き・問い合わせ情報の漏洩経路は一切確認されなかった。真に機微な問い合わせ経路（Inquiry）は、REST・検索・公開Template・CSRF・メールヘッダインジェクションのいずれの角度からも安全側の実装であることを実機で確認済み。MEDIUM 2件の修正自体は`show_in_rest => false`への1行変更2箇所で完結する低リスク・低コストな作業であり、Construction 011の実装スコープへ含めるかはユーザー判断とするが、含めても実装コストはごく小さい。

---

# 総括レポート（要求フォーマット準拠）

1. **Theme Display Inventory結果：** Part A §1参照。最大のギャップはProfessional Single欠如とServices HOME Teaserの0件非対応。
2. **Professional Single推奨方式：** Block Bindings＋標準Block（Dynamic Block不要）。Part A §2。
3. **Office Profile表示推奨方式：** 新規Dynamic Block 1〜2本（営業時間・SNS）。Part A §3。
4. **Setup事務所概要統合方針：** 新規生成時のみ改善、既存ページは無変更＋挿入可能Patternとしても提供。Part A §4。
5. **Service HOME 0件問題の解決案：** `astrea/service-list`単独新設（案A）を推奨。Part A §5。
6. **Listing統合判断：** 不採用（案B）。既存6 Blockの再設計コストに見合う便益なし。Part A §5。
7. **Price/FAQ/Contact ポリッシュ判断：** Priceはグループ表示未実装（先送り可）、FAQは変更不要、Contactはボタンクラス付与を推奨。Part A §6。
8. **CASE/RESULTS/VOICE ポリッシュ判断：** 構造的欠陥なし。VOICE見出しレベルのみ修正推奨。Part A §7・§10。
9. **Skip Link／Landmark／Heading結果：** Skip Link不在（新設推奨）、Landmark再発なし、HOME H1欠落（Blocking）とVOICE見出しレベル飛び（Recommended）を発見。Part A §8〜10。
10. **HOME全体評価：** 現行順序は妥当。RESULTS/CASE順序・Trust追加は参考程度の提案に留め、正式変更はしない。Part A §11。
11. **011 Release Blocking施工範囲：** Professional Single新設、Services HOME 0件修正、HOME H1修正。Part A §16。
12. **012へ先送りする項目：** Priceグループ表示、フルレスポンシブ監査、日本語実データ耐久テスト。
13. **Post v1項目：** FAQ/CASE/RESULTS/VOICEの追加ポリッシュ、CASE Single関連Service表示配線。
14. **着工前に必要なユーザー判断：** Part A §18（7件）参照——Professional Single項目順、既存空meta問題の扱い、Office Block分割方針、既存事務所概要ページの移行有無、service-list Blockのスコープ、HOME H1化に伴うHero見た目調整、HOME順序変更の要否。
15. **Who：** クロエ（Chloe）
16. **Start：** 2026-08-28 09:25 JST
17. **End：**（本報告末尾のHISTORY.csv記録時に実測記録）
18. **Duration：**（Start/Endの実測差分）
19. **research-doc path：** `docs/research/2026-08-28_construction_order_011_research.md`（本ファイル）
20. **Commit/CI：**（コミット後に追記）

## Security Audit 追加項目

21. **Security Audit Summary：** Part B §22参照。CRITICAL/HIGH該当なし、MEDIUM 2件（REST露出の設計意図矛盾）、INFO 1件（保持期限超過の多層防御注記）。
22. **CRITICAL/HIGH件数：** 0件
23. **MEDIUM件数：** 2件
24. **LOW/INFO件数：** LOW 0件、INFO 1件
25. **Release-Blocking Security修正の有無：** なし（Part B §23）。ただし低コストな修正のため、011実装スコープへの任意組み込みは可能。

---

**本調査で発見された不具合・脆弱性は一切修正していない。** 本施工（Construction 011実装）は、上記§14の未解決ユーザー判断事項がFIXされ、正式な着工命令が発令されるまで開始しない。
