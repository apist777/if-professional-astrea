# CONSTRUCTION ORDER 012 — INTEGRATED RELEASE QUALITY AUDIT — 統合完成検査報告

**Status:** AUDIT COMPLETE（検査のみ。`theme/`・`core/`への製品コード変更はゼロ）
**関連:** Decision 021/022/028/029、Remaining Work Audit、Construction 008〜011 各報告

---

## 0. 方法論・スコープ

本監査は「PHPUnitが通る／curlでHTMLが返る」ではなく「実際の日本語専門家事務所サイトとして公開して問題ないか」を実機ブラウザで判定することを目的とした。

- **ブラウザ自動化：** ホスト環境にChromium実行に必要な共有ライブラリが無かったため、`mcr.microsoft.com/playwright:v1.62.1-noble`公式Dockerイメージ（`--network host`でwp-envのlocalhost:8888へ接続）上でPlaywright 1.62.1を実行した。npm packageはすべて`/tmp`配下のScratchpad上にDevelopment-onlyでインストールし、リポジトリの`package.json`・`composer.json`は一切変更していない。
- **Viewport：** 320 / 375 / 768 / 1024 / 1440pxの5段階。
- **Fixtureモデル：** 完全に独立した3つのWordPressインストールではなく、同一wp-env上でMINIMAL→STANDARD→STRESSの順に段階的にデータを積み増す方式を採用した（過去のConstruction文書でも0件→1件→N件の段階検証は同一サイト上で行われており、その延長）。理由：3環境構築のコストに対しUI/Markup検証上の追加知見が乏しいため。
- **Lighthouse：** サンドボックス環境固有のChrome起動設定の問題（`CHROME_INTERSTITIAL_ERROR`、HTTPSアップグレード関連と推定）により実行できなかった。代替として、Playwright自身のNavigation Timing API・レスポンスカウント・転送バイト数による軽量計測を実施した（§Performance参照）。**Lighthouseによるスコアリングは今回「未検証」とする。**
- **Accessibility自動検査：** axe-core 4系を用いてWCAG2A/2AAルールで5ページを走査した。ただし本命令自体が明記する通り、自動検査のみでPASS判定はしていない——実スクリーンショットによる目視確認を主とした。
- **日本語ロケール：** 検査中、既定のwp-env環境に日本語言語パック（`ja`）が入っていないことが判明し、これ自体を独立したFindingとして記録した（§Finding 4）。判明後は`ja`を有効化した状態でSTANDARD/STRESS検査を継続した。検査終了後、環境を検査開始前の状態（`en_US`、Style Variation=Default、テストデータ全削除）へ復元した。
- **既存成果物との重複回避：** Construction 011実装報告・smoke-test.sh Part 1〜12で既に実機確認済みの項目（Professional Single/Archive空meta、Office Hours/SNS、Service HOME 0件、HOME H1、VOICE Heading、Contact Button、Price/Result REST、phone Binding安全性、Core ON/OFF基本動作）は本監査でも改めて実データで再確認したが、詳細な再検証プロセスは重複記載せず、本報告では新規に判明した事実により重点を置く。

---

## 1. Theme Display Inventory / 正本確認

00〜05仕様書、Decision 001〜029（Decision 021・022・028・029を優先的に再確認）、Remaining Work Audit、Construction 008〜011報告、HISTORY.csvを確認した。矛盾は発見されなかった。

## 2. Fixture概要

- **MINIMAL：** 事務所名「みらい総合法務事務所」、専門家1名（田中太郎、代表者フラグのみ、他フィールド空）、Service 1件。Price/FAQ/CASE/RESULTS/VOICE 0件。
- **STANDARD：** 専門家3名（田中太郎に全フィールド追加、佐藤花子・鈴木一郎を追加）、Service 5件、Price 4件、FAQ 5件、CASE 4件（1件に画像あり）、RESULTS 4件、VOICE 4件、営業時間・SNS 2件を設定、Contact Formの同意チェックボックス表示のためPrivacy Policyページを公開。
- **STRESS：** 事務所名・住所を長大な複合名称に変更、専門家1名に複数資格・長い経歴等を追加、Service/Price/FAQ/CASE/RESULTS/VOICEそれぞれに極端に長い日本語テキスト（全角半角混在・中黒・長音・括弧・句読点混在）を追加投入。いずれも架空データであり実在の個人・事務所とは無関係。

すべてWordPress標準API（`wp_insert_post`/`update_post_meta`/Core既存sanitize関数）経由で投入した。

---

## 3. Findings（重要度別）

| # | 重要度 | Finding | 対象 | Evidence | 013対象/014送り/Post v1 |
|---|---|---|---|---|---|
| 1 | **HIGH** | 長い事務所名（複数士業合同事務所名を想定した現実的な長さ）で、Header・Heroとも320pxで7〜8行に折り返り、Navigation・本文がその分下に押し下げられる。技術的な横スクロールや要素重なりは発生しないが、Decision 022が正式object象とする「士業法人・複数専門家事務所」にとって明確な体験劣化。 | `theme/parts/header.html`, `theme/patterns/home-hero.php` | `docs/research/screenshots/012/stress_home_header_overflow_320w.png` | **013必須** |
| 2 | **HIGH** | Setup「基本メニューを作成する」が生成する`wp_navigation`投稿は、Header/FooterのNavigationブロックに`ref`で参照されないため、実際のフロントエンドには反映されない。フロントは常にWordPress自身のPage-Listフォールバック（無関係なSample Page/Privacy Policyを含む）を表示し続ける。生成直後に他のNavigationが一切存在しない状態でも再現した（生成順序・キャッシュの問題ではない）。UI文言にも「作成後サイトエディタで割り当てが必要」という案内が無い。既存コードのdocblockには「a Navigation only renders once referenced from a Template/Template Part in the Site Editor」と明記されており、開発時点で認識されていた制約だが、Setup UIには反映されていない。 | `core/includes/setup-navigation.php`, `theme/parts/header.html`, `theme/parts/footer.html` | wp_navigation投稿一覧＋フロントエンドHTML比較（本文中に記録） | **013必須** |
| 3 | MEDIUM | WordPressの「サイトのタイトル」（`blogname`）とOffice Profileの`office_name`が独立しており同期されない。Header/Hero/FooterはOffice Profileを正しく表示する一方、パンくずのHomeラベルと`<title>`タグはWordPress標準の`blogname`（未設定なら技術的なデフォルト値）のままになる。Setupチェックリストにこれを設定する案内が無い。 | 全ページの`<title>`・`astrea/breadcrumb`のHome項目 | `curl`実測（`<title>if-professional-astrea</title>`等） | **014送り** |
| 4 | MEDIUM | 日本語言語パック（`ja`）が未インストールの状態では、`wp:query-title`等WordPress Core自身の一部文字列（"Archives: "等）が英語のまま表示される。ASTREA自身の文字列はすべて翻訳済みで問題なし。Setup/ドキュメントに言語パック確認の案内が無い。 | 全5 CPT Archive | `ja`導入前後の`<h1>`比較（本文中に記録） | **014送り**（Documentation対応でも可） |
| 5 | MEDIUM | 全7種のCore Dynamic Block（price-list/faq-list/representative/case-list/results-list/voice-list/service-list）が、Block Editor / Site Editorのキャンバス上で「このサイトは『X』ブロックに対応していません」という未対応ブロック警告、および「想定されていないか無効なコンテンツが含まれています（復旧を試みる）」という検証エラー表示になる。HOME・事務所概要ページを編集しようとする度に複数箇所で警告が出るため、非技術者のサイト所有者を混乱させ、誤って「削除」を選ぶリスクがある。通常の保存操作ではWordPress Core自身の`core/missing`ブロックの仕組みにより元のMarkupが保持されると考えられる（Gutenberg既知の設計）が、実際にエディタで保存→フロント再確認という一連の操作は、本セッションのブラウザ自動化上の制約により最後まで再現できず「未検証」として明記する。 | 全7 Dynamic Block（Construction 004/008/010/011で追加） | `docs/research/screenshots/012/editor_unsupported_block_warnings.png` | **014送り**（データ損失の実証的リスクが確認できていないため013必須にはしないが、優先度は高め） |
| 6 | LOW | Professional Archiveで紹介文（post_content）が空のProfessional Profileについて、WordPress標準の`wp:post-excerpt`ブロックが空白のみの`<p>`を出力する。Construction 011の`astrea/professional-field`修正はpostmeta由来フィールドのみが対象で、post_content由来のExcerptはカバー範囲外。 | `theme/templates/archive-astrea_professional.html` | `docs/research/screenshots/012/minimal_professional_archive_320w.png` | Post v1 |
| 7 | LOW | 検索結果ページのパンくず表示が「アーカイブ」という汎用ラベルになっており、「検索結果」等の専用ラベルではない。 | `core/includes/breadcrumb.php` | `standard_search_results_320w.png`（Scratchpadのみ保存） | Post v1 |
| 8 | INFO | Price Groupフィールド（`astrea_price_group`）は保存・サニタイズされているが表示経路が無く、複数Priceは常にフラット表示される。STANDARD Fixture（4件）でも視覚的破綻は無い。 | `core/includes/price-list-block.php` | `standard_price_320w.png`相当（HOME内に表示） | Post v1（Price Group最終判断：**Recommended、Release Blockingではない**） |

## 4. Findings以外の確認結果（問題なし、明示的に記録）

- **Responsive構造：** MINIMAL 65件＋STANDARD 85件＋STRESS 60件、計210件の`document.scrollWidth vs clientWidth`自動チェックで、横スクロール（Overflow）は極端に長い事務所名を投入したSTRESS状態でも**0件**。
- **H1個数：** 全ページでHOMEはちょうど1個（事務所名）、他ページも仕様通り。
- **Empty Heading：** 見出し単体のみが空で出力されるケースは0件（Decision 028の2ルールが正しく機能）。
- **Style Variation：** Trust/Natural/Modernの3種すべてでHOMEを実機Screenshot確認。色・フォント・ボタン角丸のみが変化し、Markup構造・セクション構成は完全に同一。Variation固有のTemplateを要求する破綻は無い。
- **Core OFF：** HOME/Professional Archive・Single/Service/FAQ/CASE/VOICE Archive/Price/Contact/Search/404のいずれも、STRESSデータ投入後もFatal・Critical Errorの兆候なし。再有効化後は全データが完全に復元された。
- **External Requests：** GA4未設定時は`googletagmanager`等の外部リクエストが0件。設定時は意図した`googletagmanager.com/gtag/js?id=...`のみが1件発生。それ以外の第三者リソース読み込みは検出されなかった。
- **Accessibility（axe-core, wcag2a/2aa自動検査）：** HOME・Professional Single・Contact・事務所概要・FAQ Archiveの5ページで検出された自動Violationは、Finding #2に由来する共通の1種類（`<ul>`直下への`<ul>`ネスト、WordPress Core自身の`core/navigation`+`core/page-list`markup）のみ。ASTREA独自コードに起因する自動検出Violationsは0件。
- **Security Regression：** STRESS Fixtureで全角半角混在・記号（／・（）・中黒・長音）を大量投入したが、エスケープ崩れ・XSS兆候・不正hrefは観測されなかった。
- **Performance（簡易計測、Lighthouse未実施）：** HOME（STRESSデータ状態）: load 288ms / requests 5 / 転送量 約212KB / FCP 296ms。Professional Archive: load 138ms / requests 4。Professional Single: load 198ms / requests 4。開発環境（本番相当のCDN・HTTP/2・画像最適化なし）での参考値であり、本番相当の点数評価はLighthouse未実施のため行っていない。
- **Skip Link：** Construction 011で確認済みのWordPress 7.1 Core標準機構。本監査でも全ページの`<main id="wp--skip-link--target">`存在を実機確認した。Tab操作による実機キーボード検証は自動化コストの都合で見送り、markup上の要件充足（一意なターゲットID、単一main）の確認に留めた——**未検証**として明記する。

---

## 5. Release Readiness再計算

Remaining Work Audit時点（Construction 009着手前）の推定完成度は約55〜60%だった。Construction 009（HOME/GA4/データ削除）・010（CASE/RESULTS/VOICE）・011（Theme Display Completion + Security Hardening）でFeature・Theme・Security領域がほぼ完了し、012では初めて「実際の専門家サイトとして」の横断検査を実施した結果、HIGH 2件・MEDIUM 3件・LOW 2件・INFO 1件を新規発見した。

| 領域 | 評価 | 根拠 |
|---|---|---|
| Feature（Core Semantic Data Layer） | ほぼ完了 | CASE/RESULTS/VOICE含め全Entity実装済み |
| Theme（表示層） | ほぼ完了、軽微な穴あり | Finding 6/7が残存するが影響小 |
| Security | 完了 | Construction 011で監査・Hardening済み、012のSTRESSデータでも兆候なし |
| Responsive/A11y | **要修正** | Finding 1（HIGH）、axe-core上は概ね良好 |
| Release Quality（実サイト運用可否） | **要修正** | Finding 2（HIGH）はSetupの主要機能が実際には動作しないという深刻な運用ギャップ |
| Documentation | 未着手 | Finding 3/4はドキュメント対応でも緩和可能 |
| Packaging | 未着手 | Decision 029によりv1.0 Release自体には必須ではない |

**総合進捗率：約80%**（Remaining Work Audit時点の55〜60%から前進。ただしFinding 1・2という2件のHIGH Findingが解消されるまでは実運用可能とは言えないため、013を経ないとRelease Candidateへは進めない）。

---

## 6. 総合判定

**PASS WITH FIXES**（Construction 013実施後、Release Prepへ進める）。

理由：Architecture自体の再検討が必要な致命的破綻（FAIL相当）は発見されなかった。一方、HIGH 2件（長い事務所名のHeader/Hero折返し、Setup生成Navigationがフロントに反映されない）は、実際の専門家事務所サイトとして公開した場合に第一印象・基本機能の両面で看過できない実害があるため、無条件PASSにはできない。

---

## 7. Construction 013候補（Release Blocking想定）

1. Finding 1：長い事務所名のHeader/Hero折返し対策（例：Header office-name要素へのfont-size縮小・行数制限、あるいはHero側のレイアウト再検討）。
2. Finding 2：Setup生成NavigationをHeader/FooterのNavigationブロックへ実際に反映させる仕組み（`ref`更新の自動化、またはSetup UIへの明示的な次ステップ案内の追加）。

## 8. Construction 014送り候補

- Finding 3：Site Title同期またはSetupチェックリストへの案内追加。
- Finding 4：日本語言語パック確認の案内追加（コードよりドキュメント対応が主）。
- Finding 5：Dynamic Blockのエディタ内「未対応ブロック」警告の緩和（最小限のクライアント側プレースホルダ登録、または「正常です」という案内）。

## 9. Post v1候補

- Finding 6：Professional Archiveの空Excerpt対策。
- Finding 7：検索結果パンくずラベル。
- Finding 8：Price Groupの表示対応（Release Blockingではないとの最終判断）。

---

## 10. 着工前に必要なユーザー判断

1. Finding 1の具体的な対策方針（フォントサイズを動的に縮小するか、行数制限＋省略表示にするか、あるいはHeader/Heroのレイアウト自体を再設計するか）。
2. Finding 2の対策方針（Setup側でHeader/Footerのref自動更新まで行うか、UIでの案内追加に留めるか）——前者は「既存Template/Patternを勝手に書き換えない」という既存原則との整合を要検討。
3. Finding 5（Editorの未対応ブロック警告）を013へ前倒しするか014のままとするか。
4. Finding 3・4をコード対応にするかDocumentation対応に留めるか。

新規Decisionは不要と判断する——いずれも既存Architecture・既存Decisionの範囲内で解決可能な実装課題であり、新しい恒久的な設計判断を要するものではない。

---

**本監査で発見された不具合は一切修正していない。** 検査に使用したFixtureデータ（MINIMAL/STANDARD/STRESSの全投稿・Office Profile・生成ページ・Navigation）はすべて削除し、Style Variation・言語設定を含め検査開始前の状態に復元したことを確認済み。
