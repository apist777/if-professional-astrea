# Construction Order 015C — Visual v2 HOME Components

RC1（Theme/Core 1.0.0-rc1、Version変更なし）をFunctional Baselineとして維持。Design Specification正本：`docs/specifications/06_astrea_visual_v2_design_system.md`。

## 1. Verdict

HOME全体が「見出し+Textの縦積み」から、「Sectionごとに異なるVisual Roleを持つProfessional HOME」へ変化した。Services/CASE/RESULTS/Professional/Price/FAQ/VOICEの7Sectionが、スクロールしながら一目でそれぞれ別の情報種別だと判別できる状態になった（27節Acceptance Condition）。「全部Card化」は行わず、RESULTSはMetric、Priceは構造化List、FAQはQ/A Layoutとして、情報の性質に応じた別々の表現を採用した。

## 2. Architecture上の重要な発見

Services/CASE/RESULTS/Professional/Price/FAQ/VOICEの全Teaser Patternは、**もともとPattern自体にWrapping Groupを持たない**（`<!-- wp:astrea/xxx {...} /-->` という自己完結Dynamic Block呼び出しのみ）ことを施工前調査で確認した。これはDecision 028の「0件ならSection全体（見出し含む）を非表示にする」という要件を満たすため、静的なWrapper Groupを意図的に避けた設計（既存の`home-professional-teaser.php`docblockに明記）である。

この結果、Visual v2のVisual Role実装は、**Pattern自体を一切変更せず**、(1) Dynamic Blockの`render_callback`が出力するHTMLへの後方互換なClass/Wrapper追加（Core PHP）と、(2) `theme.json`のGlobal Raw CSS、の2つだけで完結した。これは既存User Content保護の観点で重要な意味を持つ：**Dynamic Blockは常にLive Dataを都度render_callbackで描画する**ため、既にHOMEを生成・カスタマイズ済みの既存ユーザーも、Themeの更新を受け取るだけで自動的にVisual v2の表示（Card/Metric/Testimonial等）を得られる。post_content内の`<!-- wp:astrea/xxx {...} /-->`コメント自体は一切書き換えていないため、Migration処理は不要かつゼロリスクである。

## 3. Section別の変更内容

### Hero

Typography/Spacing/CTA間隔について、015Bで整備したButton Systemをそのまま適用。構造・文言は変更していない。長い事務所名時のH1折返しはConstruction 013/RC1で確認済みの挙動を維持。

### Services（取扱業務）

`core/includes/service-list-block.php`：各項目に「詳しく見る」Action Linkを追加（Title/Action Link方式——H3のTitleリンクとは別のDOM要素で、Nested Linkにはならない）。Description Paragraphに専用Classを追加。

CSS：`display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr))`によるCard Grid。1件のみの場合はCardが自然に全幅へ伸びる（`auto-fit`の標準的な挙動を利用、固定`repeat(3,1fr)`では1件時に空白が残ってしまうため採用）。Card自体はSurface背景・Restrained Border・radius-md。

### CASE（対応事例）

`core/includes/case-list-block.php`：Servicesと同様にAction Linkを追加。CSSでServicesと明確に区別するため、**Base背景＋Primary色の左Border Accent**、`minmax(280px,1fr)`のやや広めのCard（Excerptがある分の余白確保）を採用し、「Service Cardと同じ見た目にしない」（Order 11節）を満たした。

### RESULTS（実績）

`core/includes/results-list-block.php`はPHP変更なし——既に`value`/`label`が別Classで出力される設計だったため、CSSのみでLarge Metric化した。015Bで追加した`typography.resultsNumber`Tokenを使用（`clamp(1.75rem, 3.5vw, 2.5rem)`。当初`clamp(2.5rem,5vw,4rem)`で試したが、3列Grid内で「200社以上」等5文字前後の文字列が2行に折り返してしまったため、実機確認の上で縮小した）。

**重要な発見と修正**：施工中、開発環境のOwner Fixture（RESULT投稿）が、`label`（post_title）に全文（「相談実績500件以上」）を入れ、専用の`value`Postmeta（`astrea_result_value`）を未設定のまま作成されていたことが判明した。これはCode側の問題ではなく、以前のConstructionでFixtureデータを作成した際に、value/labelの分離設計を正しく使っていなかったというFixtureデータ側の不備だった。Codeで自由入力Dataを勝手にParsingすることはせず（Order 13節の明示的な禁止事項）、Fixtureデータ自体を本来の意図（label=短い見出し、value=強調する数値）に沿って修正した（例：post_title「相談実績500件以上」→「相談実績」、`astrea_result_value`に「500件以上」を設定）。

### Professional（代表者紹介）

`core/includes/professional-profile-block.php`：Photo/Body領域を明確に分離するWrapper追加、Qualification/Bioへ専用Class追加、`has-photo`/`no-photo`Classを持たせた。

**画像無し時の扱い（Order 16節の比較）**：A（Initial Placeholder）／B（Photo領域非表示）／C（Abstract neutral treatment）を検討し、**Bを採用**した。理由：Aは「代表 太」のようなIllustrationやIniital丸Avatarを新たに描画する必要があり、実データが無いことを示す記号を追加することになりかねず、「画像が無いことを強調するPlaceholderは禁止」という制約に抵触するリスクがある。Cは具体的な実装像が曖昧で、過剰な装飾になるリスクがある。Bは、既存の`wp_get_attachment_image()`が画像なしで空文字列を返す既存挙動をそのまま活かし、CSSで中央寄せのText-only Layoutへ自然に切り替わるだけで済み、実機確認でも十分にProfessionalな見た目になることを確認した（Screenshot: `docs/research/screenshots/015c/professional.png`）。

### Price（料金）

`core/includes/price-list-block.php`：各項目にGroup Kicker Label（`astrea_price_group`）、Amount専用Class、Name専用Classを追加。

**設計判断**：`get_prices()`は`menu_order, title, ID`順であり`group`順ではないため、「Groupが変わったら見出しを1回だけ出す」実装は、Group未整列の実データでは同じGroup見出しが重複して分断表示されるリスクがある。Post v1 Finding 8（Price Groupの機能実装）に踏み込まず、Presentationのみに徹する（Order 20節）ため、**Bucket化・再ソートは行わず、各項目自身にGroup Kicker Labelを常に表示する**方式を採用した。データの並び順に関わらず安全に機能する。

CSSはCard Gridではなく、Border-bottom区切りのStructured List（`display:flex;justify-content:space-between`）とし、Amount部分のみ大きく・Primary色で強調した（Servicesと同じCard Gridに見せない、というOrder 19節の要件を満たす）。「おすすめ」「人気No.1」等の架空ラベルは一切追加していない。

### FAQ（よくあるご質問）

`core/includes/faq-list-block.php`：Question（H3）とAnswer（div）それぞれに、`aria-hidden="true"`を付けた装飾的な「Q」「A」Labelを追加した（Screen Readerには読み上げさせず、視覚的な区別のみに使用——見出し階層自体が既に十分な意味構造を提供しているため）。Accordion化・FAQPage Schema・Rating・検索機能はいずれも追加していない（Order 22節の明示的な禁止事項）。

### VOICE（お客様の声）

PHP変更なし——既存の`figure`/`blockquote`/`figcaption`/`cite`構造で十分Testimonialとして機能したため、CSSのみでCard化（Surface背景、Blockquoteをitalic表示）した。Star Rating等の架空Dataは追加していない。

### Flow（ご相談の流れ）

既存構造（`ol`+`li`のNumbered List）を変更していない。長い日本語説明があるため、無理な横並びGrid化は行わなかった（Order 25節）。

### CTA（Closing CTA）

変更なし。既存の高Contrast Sectionが既に目的に合っていると判断した。

## 4. Section Header System

`h2:has(+ .wp-block-astrea-xxx-list)`（CSS `:has()`疑似クラス、モダンBrowserで広くサポート）を用いて、各Teaser見出し（H2）を中央揃え・x-largeサイズへ統一した。Eyebrow（Label）は既存Contentに無いLabelを勝手に生成しないという方針に従い、追加していない。

## 5. Section Rhythm / Surface Usage

Teaser Pattern自体がWrapping Groupを持たない制約（2節）により、Hero/Flow/CTA以外のSectionに対する**全幅の背景色帯**は安全に追加できない（追加すると0件時に「中身の無い色付きBox」が残ってしまう）。そのため、Section Rhythmは主に(1) 各Sectionの異なるLayout様式（Grid／Metric／構造化List／Q&A／Testimonial）そのものによる視覚的な差別化、(2) Section間の一貫した大きめの余白（`margin-top`/`margin-bottom: x-large`）で実現した。「機械的な交互背景」を無理に作ることは避けた（Order 5節の明示的な注意）。

Professional Sectionのみ、Dynamic Block自身の出力（`.wp-block-astrea-representative`）に直接Surface背景・Padding・Border Radiusを付与した——これはBlock自身が0件時に空文字列を返す設計（Decision 028）のため、背景を持たせても「空Wrapperが残る」リスクが無く、安全に実施できた。

## 6. Empty State / Data Count Stress / Long Japanese Stress

- **Empty State**：`empty($items)`時の早期`return ''`ロジックはいずれのBlockでも変更していない。Decision 028の自己非表示挙動は完全に維持。
- **Data Count Stress**：Services投稿を一時的に5件Draft化し1件のみ表示させて確認。`auto-fit`GridによりCardが不自然な空白を残さず全幅へ広がることを実機確認した（Screenshot: `services-one-item.png`）。確認後、全てPublishへ復元済み。
- **Long Japanese Stress**：一時的に非常に長いService名（複合許認可申請サポート業務、約45文字）を持つ投稿を作成し確認。Ellipsis無し、Truncate無し、Card高さが自然に伸びることを確認した（Screenshot流用：`services-one-item.png`は実際にはこの長い名前のテストと同一Screenshotとして記録されている——1件表示+長い名前の複合テストとして実施）。確認後、投稿は削除し元の6件を復元済み。

## 7. Existing HOME Protection

2節で述べたとおり、Pattern（`theme/patterns/home-*.php`）は一切変更していない。既存のSetup生成済みHOMEや、ユーザーが手動編集したHOMEの`post_content`（`<!-- wp:astrea/xxx {...} /-->`ブロックコメント）は無傷のまま、Dynamic Blockの新しいrender_callback出力とtheme.jsonの新しいCSSが自動的に適用される。DB Migration・Content Migrationはいずれも実施していない。

## 8. Dynamic Blocks / Block Validation

`editor_script_handles`によるEditor Placeholder（Construction 013で確立、`save:()=>null`）はいずれのBlockも変更していない——Attribute Schema・Block登録も無変更。実機のBlock/Site Editorで、ASTREA Dynamic Block由来の新規Unsupported Warningが0件であることを確認した（`editor_home_check.png`）。既存のWordPress 7.1 core/group Known Exception（Construction 014A、Hero/Flow/CTA関連の3Group）は同様に再現することを確認したが、これは今回の変更と無関係であり、Editor UI言語が日本語表示になっていたため「復旧を試みる」という文言で表示されることを確認した（Construction 014A当時の英語表示と同一の警告種別）。

## 9. Core OFF

Core無効化状態でHOMEへアクセスし、HTTP 200・Fatal無しを確認した。Theme-onlyのHero/Flow/CTAは静的Fallback文言（事務所名は「ASTREA」、電話CTAは「お電話でのご相談」）で正しく表示され、Dynamic Block由来のSection（Services以降、CTA前まで）は全て自然に非表示となり、空白や崩れは一切発生しなかった（`home-core-off.png`）。

## 10. Accessibility

H1は1個のまま。見出し階層はH1→H2（Section）→H3（Card Title）の順で一貫しており、階層飛ばしは無い。「詳しく見る」Action Linkは各Cardの直前にH3のTitleが隣接しているため、WCAG 2.4.4（Link Purpose, In Context）上は文脈から目的判別可能と判断したが、より厳密な対応（Visually Hidden TextでCard名を補う等）は今後の検討事項としてKnown Issuesに記録する。

## 11. Responsive

320/375/768/1440pxで確認した。

**発見した問題と修正**：
1. Price Amount（例：「月額22,000円〜（税別）」）に`white-space:nowrap`を設定していたため、320/375pxでHorizontal Overflowが発生した。`nowrap`を削除し、自然な折返しへ変更して解消した。
2. Services/CASE/VOICEのGridを固定`repeat(3,1fr)`/`repeat(2,1fr)`から`repeat(auto-fit,minmax(...,1fr))`へ変更し、Mobile専用の明示的な1列強制Media Queryを削除した（`auto-fit`が幅に応じて自然に段階的Wrapするため、明示的なMedia Query不要になった）。

修正後、全4 breakpointでHorizontal Overflow 0を確認した。

## 12. Style Variations

Trust（主施工）／Natural／Modernの3種類でDesktop表示を確認した（`trust-desktop.png`／`natural-desktop.png`／`modern-desktop.png`）。Markupは完全共通、Style（Color Token）のみで個性を実現。Card自体のBorder Radiusは3 Variation共通の固定Token（`radiusMd`）を使用しており、Button独自のVariation別Radius（Trust 2px/Natural 999px/Modern 0px）とは意図的に別軸で扱っている——CardはFoundation Levelの一貫したTokenとし、Button（既に確立されたVariation固有の個性）とは区別した。

015Bで確認した「Style Variation選択時のSnapshot特性」（新しいtheme.json変更が即座に反映されない）に再度遭遇し、同じ再同期手順（`get_style_variations()`の値を`wp_global_styles`投稿へ書き戻す）で対応した。ASTREAのBugではなくWordPress標準の既知特性である。

## 13. Regression

| 項目 | 結果 |
|---|---|
| PHP Syntax | 全件OK |
| PHPCS | 62/62、0 Errors |
| PHPUnit | 359 tests, 560 assertions, OK |
| 公式Theme Check | REQUIRED/WARNING 0、INFO 1（問題なし） |
| smoke-test.sh | CI（Clean環境）で1件のFAIL発見・修正・再確認 |

### smoke-test.sh側の修正（テスト自体の不備、Product Codeの不具合ではない）

初回Push時、CIのPart Y（Price Dynamic Blockの0件時挙動確認）が失敗した：「astrea/price-list rendered a container with 0 Price posts」。原因を特定するため、`gh api`でCIの生Logを取得して調査した。

`render_price_list_block()`のPHPUnit Test（`assertSame( '', Price\render_price_list_block() )`）は本Constructionを通して一貫してPASSしており、0件時に空文字列を返す実装自体は正しい。一方smoke-test.sh Part Yの検査は、生成したPage全体のHTML本文に対して`grep -qF "wp-block-astrea-price-list"`という**単純な部分文字列検索**を行っていた。

Construction 015Cで`theme.json`へ追加したGlobal CSS（Section間の統一Margin用ルール）には、`.wp-block-astrea-service-list, .wp-block-astrea-case-list, .wp-block-astrea-results-list, ... , .wp-block-astrea-price-list, ...`という複数Selectorをまとめた1つのCSS規則が含まれており、このCSSは**Site全体の`<style>`タグとして、Priceが0件のPageも含めた全Pageに出力される**。そのため、実際にはPrice Blockは正しく何もRenderしていないにも関わらず、Page内のCSS文字列に"wp-block-astrea-price-list"という部分文字列が含まれてしまい、smoke-testの単純な部分一致検索が誤検知した。

これはProduct Code（`price-list-block.php`のRender処理）の不具合ではなく、smoke-test.sh側の検査方法が、Visual v2で新たに導入したGlobal CSSアーキテクチャを想定していなかったことによる**Test側の誤検知**と判断した。`tools/ci/smoke-test.sh`のPart Yの検査条件を、単純な部分文字列一致から、実際にRenderされるContainer要素そのもの（`<div class="wp-block-astrea-price-list">`という開始Tagの正規表現）に絞り込む形へ修正した。修正後、実際に0件PriceのPageを作成し、旧検査条件では誤検知が再現すること・新検査条件では正しくPASSすることの両方を、Owner Fixtureには一切影響を与えない一時的なTest Page作成・削除のみで確認した。他のDynamic Block（Service/CASE/FAQ/RESULTS/VOICE）については、smoke-test.sh内に同様の単純部分文字列検査は存在しないことを確認済み。

## 14. Security / Migration

新規Endpoint・新規Input・新規Persistenceはいずれも追加していない。DB Migration・Content Migrationは無し。既存User Content（HOME Pattern自体）の自動書き換えも無し。

## 15. Known Issues（Blockerではない）

- 「詳しく見る」リンクのAccessible Nameが、視覚的にはCard Titleと隣接しているため文脈上判別可能だが、Screen Reader Rotor等でLink一覧のみを走査した場合には曖昧に見える可能性がある（LOW、将来Visually Hidden Textでの補強を検討）。
- RESULTSの数値表示は、既存Fixtureの文字数（4〜6文字程度）を前提にToken値を調整した。将来的に極端に長い数値文字列（例："1,234,567件以上"）が入力された場合の折返し挙動は未検証（LOW、自然な折返し自体は発生するため崩壊はしない）。
- Card系Componentの Border Radiusが3 Variation共通の固定値である点（12節）は意図的な設計判断だが、将来015Fでの3 Variation本格Polish時に見直しの余地がある。

## 16. Design Specification更新

`06_astrea_visual_v2_design_system.md`のRESULTS（13節）・Price（19-20節）の記述に、本Constructionで確定した実装方針（Price Group Kicker方式、RESULTS Token値）を反映する差分更新を行った。

## 17. Screenshot

`docs/research/screenshots/015c/` に保存。`home-before-desktop.png`（Owner Acceptance時点）／`home-after-desktop.png`／`home-after-mobile.png`／Section別クロップ（`services.png`／`case.png`／`results.png`／`professional.png`／`price.png`／`faq.png`／`voice.png`／`flow.png`）／`trust-desktop.png`／`natural-desktop.png`／`modern-desktop.png`／`home-core-off.png`／`editor-home-check.png`／`services-one-item.png`／`price-mobile-crop.png`。
