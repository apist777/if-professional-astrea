# Construction Order 016F — INTERNAL PAGES VISUAL v3（AUDIT / RECONSTRUCTION / OWNER ACCEPTANCE）施工報告

## 1. Order概要

Construction 016E-R4でHOME（front-page.html）のVisual v3再構築がOwner Acceptance候補として完了したことを受け、Owner指摘「HOMEだけデモ詐欺」（HOMEから内部ページへ遷移すると別の古いWordPressサイトのように感じられる）を解消するため、内部ページ全種（Office / Professional Archive+Single / Service Archive+Single / CASE Archive+Single / Price / Contact / FAQ Archive / VOICE Archive / Search / 404）をHOME Visual v3のDNA（HOMEのMarkupそのままのコピーではなく、Wide Grid・Kicker+trailing rule・Editorial Card DNAという「設計思想」の水平展開）で再構築する。HOME自体は本Orderの間Freeze対象（重大な共有CSS/Accessibility/Responsive破壊バグ以外は一切触らない）。

## 2. Phase 1 — 監査結果

事前監査の全内容は [`2026-09-03_016f_internal_pages_audit.md`](2026-09-03_016f_internal_pages_audit.md) に記録済み。13ページ全てをVisual Score 35〜65/100（満点非達成）と判定し、個別バグの集合ではなく**単一の系統的な建築的原因**（後述§3）にあることを特定した。

## 3. 根本原因（Root Cause）

### 3.1 `<main>` 自体のCore崩壊（最重要・全ページ共通）

Construction 016E-R2/R3でHOMEのHero Text Plane／Final CTA／Flowの3箇所で発見・修正した**WordPress Core自身の `.is-layout-constrained > *` ルール**（`align:full`/`alignwide`を持たない子要素を、Coreのグローバルスタイルが `!important` 付きで `max-width:720px; margin-inline:auto` に強制収縮させる）が、内部ページでは**Templateの `<main>` 自体**に対して発生していた。全Internal Page Templateの `<main>` が `align:full` を持たず、Themeが独自に用意したWide Grid CSSの有無に関係なく、内部ページ全体が常に古い狭幅WordPressサイトに見える根本原因になっていた。

### 3.2 「入れ子のCore崩壊」（本Orderで新規発見・全13ページ横断で複数回再発）

§3.1の `<main>` 修正だけでは不十分だった。`<main>` の**直接の子要素自身**（`.astrea-professional-single-header`、`.wp-block-post-title`、`.wp-block-post-content`、`.astrea-archive-listing`（Query Loop本体）、`.wp-block-query-title`、`.wp-block-astrea-breadcrumb`、`.wp-block-post-featured-image`、`.wp-block-astrea-closing-cta`）が、それぞれ独立に同じCore崩壊ルールの対象になっており、`align:full` を持たない限り720pxに再収縮していた。これは016E-R2で「CTA/FlowはSection自身も崩壊しうる」と学んだのと**全く同じバグクラス**が、内部ページでは1ページにつき複数箇所・複数階層で同時多発していたことを意味する。発見の都度、実機のcomputed widthをPlaywrightで直接測定して確認し、該当セレクタを `main.alignfull>...{max-width:none!important;margin-inline:0!important;}` のパターンで個別に無効化した（HOMEのSpec 07 §21の教訓どおり、「意図」ではなく実測で確認する方針を踏襲）。

### 3.3 スコープ漏れによる回帰（本Order内で自己検出・即修正）

上記の一括修正の過程で、意図せず新しい回帰を2件作り込み、いずれも実機screenshotで発見し即座に修正した：
- `body.search main.alignfull>.wp-block-query` にスコープすべきだったSearch専用CSSを無スコープの `.wp-block-query` に書いたため、同じクラスを共有するArchive Listing（`.astrea-archive-listing.wp-block-query`）の3列Gridがreading-max幅（640px）に潰れる回帰が発生 → `body.search` プレフィックスで再スコープして解消。
- FAQ Archiveの `.wp-block-post-template` がCoreの `is-layout-grid`（`minimumColumnWidth:16rem`のauto-fill Grid）のまま幅を解放したため、Q&Aリストが3列の不揃いなMasonry状に崩れる回帰が発生 → `display:block` で明示的にGridを無効化し単一カラムのリストへ復元。

## 4. 変更ページ・変更内容

| # | ページ | 主な変更内容 |
|---|---|---|
| 1 | 全13ページ共通 | `<main>` に `align:full` を追加（§3.1）。Internal Page Header System（Kicker+Title+trailing rule）をArchive見出し・Single H1・Search結果見出し・404見出しに適用（Decision 028のゼロ新規データ原則に従い、WordPress Core自身のbody classで駆動） |
| 2 | Office（事務所概要） | Office Summary / 営業時間 / SNS を縦積みFlexから2カラムGrid（Summary左・営業時間右・SNS下段全幅）へ再構成し、Wide Grid幅いっぱいを使用するよう変更。768px以下は1カラムへ復帰 |
| 3 | Price（料金） | 既存だが未使用だったCSSクラスタの重複バグを解消（§5）。アイコン付き2カラムカードGridへ再構成。ページ末尾に `astrea/closing-cta` を新規追加（Fixture Content、Service/CASE/Professional Singleと同じ既存Dynamic Blockの再利用） |
| 4 | Contact（お問い合わせ） | フォーム上部にリード文（返信目安等）を追加（Fixture Content）。フォーム自体・送信ロジック・PHPは完全にLocked、無変更 |
| 5 | Service/CASE/Professional Archive | 汎用「Surfaceカード」から HOME準拠のEditorial DNA（罫線区切り・連番・写真リード）へ全面刷新。3列→2列→1列のレスポンシブGridを新規追加（§7で詳述するモバイル回帰の修正込み） |
| 6 | FAQ Archive | HOME以前の古いグレーカード調から、単一カラムの罫線区切りQ&Aリストへ再設計 |
| 7 | VOICE Archive | 同上、罫線区切りの引用リストへ再設計 |
| 8 | Service Single / CASE Single | Page Header・関連Service一覧・Closing CTAをWide Grid化。CASE SingleのみFeatured Imageをフル幅Editorial Heroとして新規スタイリング |
| 9 | Professional Single | 160pxの丸型サムネイルを4:5比率の大判Editorial写真へ変更。写真+氏名/資格の2カラムヘッダー構成（HOME崩壊バグの再発を修正、§3.2）。氏名見出しにHOME同様のGold trailing rule追加。経歴/学歴/所属/登録情報の4フィールドを、単なる縦積みdlから「ラベル|内容」の2カラムStructured Data Presentationへ再設計（Order §6の明示要求に対応） |
| 10 | Search | 検索結果見出しに「SEARCH」Kickerを追加（既存の汎用selectorに漏れていたバグを修正）。結果リストの左端揃えGrid回帰を解消 |
| 11 | 404 | 「404」Kicker付き見出し＋trailing rule。過度な装飾は追加せず最小限のまま |

## 5. Priceページの既存CSS重複バグ（本Order範囲内で発見・修正）

`.wp-block-astrea-price-item` に対する定義がtheme.json内に**2箇所**存在していた：古い方（flex+space-between+border-bottomの完成度の高いレイアウト）が先に定義され、後から追加された別クラスタ（`padding`のみの最小定義）がCSS Source-Order（Spec 07 §21と同じ原理）により**常に後者が勝つ**ため、より作り込まれた前者が完全にデッドコード化していた。本Orderでこの重複を統合し、アイコン表示・Wide Grid Card化とあわせて1箇所の完全な定義へ整理した。

## 6. CSS `counter()` の不可解な失敗と回避策（正直な記録）

Service/CASE Archiveカードへの連番表示のため `counter-reset`/`counter-increment`/`content:counter()` を試みたが、実ページ上で常に「01」が全アイテムに表示される問題が発生した。以下を確認したが原因を特定できなかった：
- コンパイル済みCSS全文をgrep検索し、競合する重複 `counter-reset` 宣言が無いことを確認
- 同一DOM構造（`ul.wp-block-post-template>li>article`）を用いた最小Playwright再現テストでは正常に01/02/03と表示され、再現しなかった
- 実ページの `<li>` が `display:list-item`（`display:contents`ではない）ことを確認し、疑われたChromiumの既知の相互作用バグを除外した
- `counter-reset`/`counter-increment` 双方に `!important` を付与しても解消しなかった

原因不明のまま時間を要したため、`:nth-child()` による明示的な静的連番（Service/CASE写真バッジ/CASE写真無しフォールバックそれぞれに12件ずつのルールを生成）へ切り替え、実機screenshotで正しく01→06/01→03と表示されることを確認した。CSS Engineの未解明な挙動として、次回同種の実装をする際の注意点としてここに記録する。

## 7. レスポンシブ確認結果

7幅（1920/1440/1366/1024/768/375/320px）× 13ページ = 91パターンで `document.documentElement.scrollWidth - clientWidth` を機械的に測定し、**Horizontal Overflow = 0px を全パターンで確認**（最終確認時点）。

実装の過程で以下のモバイル回帰を発見・修正した（最終確認には反映済み）：
- Service/CASE/Professional Archiveの3列Gridが375px幅でも3列のまま崩れず、タイトルが極端に折り返される回帰 → 1024px以下2列・600px以下1列のbreakpointを追加
- Priceページの金額（例「110,000円〜」）が375px幅で「110,000」/「円〜」の2行に不格好に折り返される回帰 → 600px以下で金額のfont-sizeを縮小
- Professional Singleの経歴/学歴/所属/登録情報グリッド（160px固定ラベル列）が375px幅で内容列が極端に狭く、読みにくい多行折り返しになる問題 → 600px以下でラベル上・内容下の1カラムへ切替

## 8. Style Variation確認結果

Trust（既定）→ Natural → Modern の順にPrice/Professional Singleページで実機切替確認。3件ともMarkup構造は完全に共通のまま、Token（色・フォント・角丸）のみが変化することを確認。ページ崩れ・要素消失は無し。最終的にTrustへ復元し、`wp_global_styles`（post ID 825）の内容が復元前と一致することをdiffで確認済み。

## 9. Accessibility確認結果

- 全13ページでH1が1つのみ存在することを確認（Kicker/breadcrumbはH1をラップしない設計を維持）
- 見出し階層：Archive→H1→カードH2、Single→H1→フィールドH2という一貫した階層を維持
- フォーム（Contact）のlabel/input関連付け・エラー表示は無変更（PHP Locked）
- リンクの accessible name（「詳しく見る」等）は既存のまま変更なし
- 画像alt：Featured Image／Professional写真は既存のCPTデータのalt属性をそのまま使用、新規追加なし

## 10. Core OFF/ON確認結果

`wp plugin deactivate astrea-core` でCore停止後、Home/Price/Contact/Service Archive/Professional Singleの全URLがHTTP 200を維持し、`Fatal error` / `Warning:` / `Notice:` / `Deprecated:` / `Parse error` のいずれの文字列もレスポンスに含まれないことをcurlで確認した（Decision 021準拠）。Core依存のDynamic Block（price-list, contact-form）は該当箇所が単純に非表示になるのみで、壊れたマークアップや空の装飾ボックスは残らない。Theme独自コンテンツ（Page見出し・リード文・パンくず・ヘッダー/フッター）は正常表示を維持。`wp plugin activate astrea-core` で再有効化後、Price/Serviceの200復帰、及び全データの正常表示を確認した。

## 11. 自動テスト結果

### 11.1 PHPUnit
`397 tests, 657 assertions, 3 errors`。この3件のエラー（`SeoMetaTest`×2、`SetupTest`×1、いずれも `wp-phpunit` の attachment factory内 `Undefined array key "file"`）は、`git stash` で本Orderの変更を全て退避したCommit `32ade76`（016E-R4確認済みベースライン）でも**同一に再現**することを確認済みであり、本Orderが作り込んだ回帰ではない、テスト環境側の既存問題である。本Orderで変更したPHPファイルは0件。

### 11.2 PHPCS
本Orderで変更したPHPファイルは0件（theme.json・templates/*.html・Fixture Contentのみ）のため、対象なし。

### 11.3 smoke-test.sh（部分実行・既知の限界を記録）
`tools/ci/smoke-test.sh` のステップA〜K（Theme/Core独立性、Office Profile保存/読出/Core OFF-ON、Professional Archiveの並び順）は全てOK。ステップL/M/N（「Featured Imageは1件のみレンダリングされるはず」というテストの前提）は、**本Orderと無関係な既存Fixtureデータ**（実在の専門家3名のうち2名がプレースホルダー画像添付済み、1名が実写真添付済みで、合計3件の `<img class="wp-post-image">` が既に存在する）とテストコード自身の「アーカイブは完全に空である」という前提が矛盾しているために発生する、**本Order以前から存在するテストの脆さ**であると実測で確認した（Smokeテスト用データを完全に除去した状態でも、既存の3名の専門家データだけで `<img class="wp-post-image">` が3件存在することを確認）。`set -euo pipefail` によりこのステップで即時終了するため、後続のService/CASE/FAQ/Price/Contact/SEOに関するステップ群は本Order中は実行に到達できなかった。この制約は本Orderの範囲外の既存課題として記録し、対応はConstruction Orderのスコープ外とする。

なお、smoke-test.shの実行中に作成される一時テストデータ（Alpha/Bravo/Charlie/Draft Smoke専門家、Smoke Test Photo）およびOffice Profile設定値が、テスト失敗による早期終了で自動クリーンアップされずFixtureに残留する事故が発生したが、実行のたびに手動で検出・削除し、`astrea_core_office_profile` オプションについては実際の表示内容（ヘッダー/フッター/Officeページ）から正しい値を`sanitize()`経由で再構築して復元し、最終的にFixtureが汚染されていないことを `wp db query` で確認した。

## 12. 残存Finding・意図的な対応見送り事項

- Service/CASE Archiveカードへのアイコン表示は、新規Dynamic Block追加が必要になるため本Orderでは見送った（Order Prohibited「新規Block禁止」に抵触するため）。Priceページのアイコンは既存の `astrea/price-list` Dynamic Blockが既にSVGアイコンを出力していたため、CSSスタイリングのみで実現できた（新規Block不要）
- Professional SingleのボディコラムとPhoto行の間に、モバイル幅で意図せずやや広い余白が残るケースを確認したが、機能・可読性を損なうものではなく、軽微な仕上げ調整として次回以降に持ち越す
- CSS `counter()` の原因不明な失敗（§6）は未解明のまま
- smoke-test.shのFeatured Image関連ステップ（L/M/N）とFixtureデータの前提の食い違い（§11.3）は本Order範囲外の既存課題

## 13. Locked/Prohibited遵守確認

- HOME（front-page.html）は無変更（`git diff --stat` で確認）
- Price-list上限（MEDIUM）、CPT Archive og:url、Search breadcrumbセマンティクス（Finding 7）、Price Group（Finding 8）、Professional Archive空Excerpt（Finding 6）のいずれにも本Orderでは着手していない
- 新規Feature/CPT/DBフィールド/Block・Demo専用コード・Schema/Contact機能/Setup変更は一切無し
- Release関連の操作（Tag、GitHub Release、Project-if deploy、WordPress.org submission）は一切未実施

## 14. 使用ツール・手法（既存確立手法の再利用）

Playwright（`mcr.microsoft.com/playwright:v1.62.1-jammy`）+ Docker Networkを用いたスクリーンショット撮影（`localhost:8888`→`wordpress`のURL書き換えパターンを継続使用）、Style Variation Snapshotスワップ手法（`wp_global_styles` post 825の退避/復元）、いずれも既存の確立された手法をそのまま踏襲した。スクリーンショットは [`docs/research/screenshots/016f/`](screenshots/016f/) に格納。

## 15. Spec更新

`docs/specifications/07_astrea_visual_v3_design_direction.md` に「Internal Page Header System」を新規の恒久原則として追記（§22相当、既存の§17/§19/§20/§21と同じ形式）。

## 16. 測定値・Commit

- Start: 2026-09-03 06:50 JST（016E-R4確認Commit `32ade76` 完了直後、Construction Order 016F着手）
- End: 2026-09-03 16:35 JST（本Report作成・Commit直前の実測時刻）
- Duration: 9h45m
- Commit: （本Report Commit自身のID。git commit後にHISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list` / `gh run watch` で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER VISUAL ACCEPTANCE**

Construction 016G、Tag付与、GitHub Release、Project-if Deploy、WordPress.org Submissionのいずれにも進みません。Owner Visual Acceptanceの明示的な確認をお待ちします。
