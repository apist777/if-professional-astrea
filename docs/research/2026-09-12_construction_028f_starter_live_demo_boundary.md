# Construction 028-F — Starter / Live Demo Boundary Audit
Demo Disclosure Separation + Footer-Area Visual Audit

- Start: 2026-09-12（Construction 028 CLOSE後、Owner Order受領時）
- Modifier: Chloe
- Mode: **ローカル監査・分離実装・テストのみ。commit / push / VPS deployは一切実施していない。Live Demo（本番）には一切触れていない。**

---

## PHASE 0 — PRECHECK

```
$ git status --short --branch
## main...origin/main
 M HISTORY.csv                                                          ← Owner自身の編集。対象外
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr         ← Owner既存差分。対象外
 D docs/research/screenshots/...（多数）                                ← Owner削除済み。対象外
?? docs/research/2026-09-11_construction_024...等                      ← 他Construction。対象外

$ git rev-parse HEAD && git rev-parse origin/main
f6559a75a93968e85a51951669860053d678d6a8（両者一致、分岐なし）
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群等）はConstruction 028時点から変化なし。

### Disclosure本文の全repo検索

`grep -rn` で以下を検索：「このWebサイトは」「デモサイトです」「架空の情報」「実在の事務所」「If Professional ASTREA」。

**発見（生成元の特定）:**

| 箇所 | 種別 |
| --- | --- |
| `docs/demo-assets/astrea-starter-site/scripts/build-content.php`（修正前 line 336） | **disclosure文字列そのものがハードコードされている実行コード** |
| `docs/research/2026-09-11_construction_027_astrea_starter_site_foundation.md` 等 | 過去Construction報告書内の引用（歴史的記録、書き換え対象外） |

`theme/`・`core/`を`disclosure`／`デモサイト`／`架空`でgrepした結果、**Theme/Core側には該当ロジックが一切存在しない**ことを確認した（唯一のヒットは`core/includes/inquiry-admin.php`内の無関係な英単語"disclosure"（HTML `<details>`要素の意味、フォーム表示制御コードのコメント））。

### Contact/Office Information生成箇所の特定

- `core/includes/setup-pages.php` の `page_definitions()['about']` が「事務所概要」ページの初期content（`astrea/office-summary` + `astrea/office-hours` + `astrea/office-sns`を`wp:group{layout:{type:flex,orientation:vertical}}`でラップ）を定義。
- `theme/patterns/office-info.php` は同構造の再利用可能Pattern（Setup生成物とは独立、Editor上での挿入用）。
- 見た目の2カラム化は**Theme側`theme.json`の`styles.css`内に埋め込まれたCSSルール**（後述PHASE 7で詳細）による。

**結論: Demo Disclosureは「C. 別の仕組み」ではなく「A. Starter Site正本（`build-content.php`）に直接含まれている」。**

---

## PHASE 1 — BOUNDARY AUDIT

1. **Starter Siteをclean WordPressへ構築した場合、disclosureは表示されるか。**
   → **YES（表示される）。** `build-content.php`が無条件で事務所概要ページ末尾へdisclosureを追記するため、Starter Site単体の構築であっても除外されない。
2. **Live Demoへ構築した場合、どの工程でdisclosureが追加されるか。**
   → 専用工程は存在しない。Live Demoは`build-content.php`を含む同一の9-stepパイプラインを実行しているだけであり、disclosure追加はStarter Site構築そのものに埋め込まれている。
3. **Starter Site source自体にdisclosureデータが含まれるか。**
   → **YES。** `build-content.php` line 336（修正前）にdisclosureのブロックMarkup文字列が直接ハードコードされていた。
4. **Starter Site Importを将来実装した場合、現在のままではユーザーサイトへdisclosureが混入する可能性があるか。**
   → **YES、確実に混入する。** `build-content.php`（または将来のImporterが再利用するであろう同等ロジック）をそのまま使えば、ユーザーの実サイトに「このWebサイトはデモサイトです」という文言が入ってしまう。

**判定: NEEDS SEPARATION**

---

## PHASE 2 — REQUIRED ARCHITECTURE（確認）

Order指定の通り、以下を最終構造として採用した：

```
ASTREA Starter Site（正本）
  = 完成した行政書士サイト一式（identity・Services・Cases・Results・Price・FAQ・Voices・
    Contact・Navigation・Images・Design）
  ≠ disclosure・project-if向け説明・Live Demo専用文言・project-if.jp/demo.project-if.jpへの依存

ASTREA Live Demo
  = Starter Site
  + Live Demo専用施工（Demo Disclosureの追加のみ）
  → Public Live Demo
```

一方向構造（Starter Site → Live Demo専用施工 → Public Live Demo）を、実装（PHASE 3）でそのままファイル構成に反映した。

---

## PHASE 3 — SEPARATION（実施内容）

### 変更1: `build-content.php` からdisclosure追記コードを削除

修正前（末尾、旧Phase 10ブロック）:

```php
// ---------------------------------------------------------------------
// 10. Fictional disclosure — appended to the 事務所概要 (About) page, ...
// ---------------------------------------------------------------------
$about_page = get_page_by_path( '事務所概要' );
...
$disclosure = "...このWebサイトは If Professional ASTREA のデモサイトです。...";
wp_update_post( array( 'ID' => $about_page->ID, 'post_content' => $about_page->post_content . $disclosure ) );
line( 'Fictional disclosure appended to page ' . $about_page->ID );
...
line( 'DONE.' );
```

修正後：disclosure関連コードを完全に削除し、境界を明記するコメントのみを残した。`build-content.php`は9-step標準パイプラインの一部として、以後Starter Siteに一切demo文言を書き込まない。

### 変更2: 新規ファイル `add-live-demo-disclosure.php`（Live Demo専用ステップ）

削除したロジックをそのまま移動し、Construction 028の教訓（冪等性）を適用して以下を追加した：

- 事務所概要ページのpost_contentに`astrea-demo-disclosure`classNameが**既に存在するかを先にチェック**し、存在すれば「no change」で即終了（冪等）。
- 存在しなければ追記し、成功ログを出力。
- 単独実行可能（`require_once '/wordpress/wp-load.php';`を先頭に持つ、他スクリプトと同じ実行モデル）。
- **9-step標準パイプラインには含まれない。** Live Demoを構築する場合にのみ、パイプライン完了後の追加ステップとして明示的に実行する。

### 変更3: `README.md` の更新

`build-content.php`の説明にdisclosure削除・分離の経緯を追記し、`add-live-demo-disclosure.php`の説明（Live Demo専用・Starter Siteには絶対含めてはならない旨・冪等性）を新規追加した。

### Live Demoの現状について

**公開中のLive Demo（`demo.project-if.jp`）は今回一切変更していない。** 本番DBへの接続・更新は行っていない。VPSへのdeployも行っていない。今回の変更はローカルのStarter Siteソーススクリプトのみが対象であり、既に公開されているLive Demoの表示内容（disclosureを含む）は現状のまま維持される。

---

## PHASE 4 — STARTER BUILD TEST

ローカルのWordPress Playground使い捨て環境（Construction 028で確立したテスト手法：theme/core/scriptsをmount、`wp_loaded`フックのテスト専用mu-plugin経由でHTTP triggerする9-stepパイプライン実行）を新規に構築し、**disclosure追加ステップを実行せず**、9-stepのみで事務所概要ページを構築した。

```
Demo Disclosure occurrence:        0
「このWebサイトは」:                0
「デモサイトです」:                  0
「架空の情報です」:                  0
project-if.jp への依存:             1件（ただし内容は Theme footer の "Theme by Project-if" クレジット表記であり、
                                       Live Demo固有のdemo説明ではない。Themeの制作者表記として妥当）
```

`dev-snapshot.php`によるスナップショットをConstruction 028のRUN#1ベースライン（`run1.json`）と比較した結果、**差分はテスト環境のポート番号（`127.0.0.1:8961` → `127.0.0.1:8963`）のみ**で、それ以外の全フィールド（`post_type_counts`・`pages`・`professionals`・`cpt_titles`・`attachment_count`・`attachment_filenames`・`duplicate_filenames`・`results_wrapper_count`/`max_depth`・`hero_wrapper_count`/`max_depth`・`navigation_count`/`navigation_items`・`identity_checks`等）は完全に一致した。

**§4 判定: PASS（Starter Site regression: 0）**

---

## PHASE 5 — LIVE DEMO BUILD TEST

同一環境で、9-stepパイプライン完了後に`add-live-demo-disclosure.php`を追加実行した。

```
=== running add-live-demo-disclosure ===
Live Demo disclosure appended to page 27.
=== done add-live-demo-disclosure ===
```

```
Demo Disclosure occurrence:  1
重複:                        0
Starter Site content:        PASS（disclosure追加後もdev-snapshot.phpの他フィールドは
                                   Starter-only構築時と完全一致 — diffなし）
Visual regression:           0（下記PHASE 7/9の実地スクリーンショットで確認）
```

配布版（Starter Site = disclosureなし）と公開デモ版（Live Demo = disclosureあり）を、同一スクリプト資産から機械的に再現できることを確認した。

---

## PHASE 6 — REPEATABILITY

`add-live-demo-disclosure.php`を同一環境に対して連続実行した。

| 実行回数 | 結果 | Disclosure count |
| --- | --- | --- |
| 1回目 | `Live Demo disclosure appended to page 27.` | 1 |
| 2回目 | `Live Demo disclosure already present on page 27 — no change.` | 1 |
| 3回目 | `Live Demo disclosure already present on page 27 — no change.` | 1 |

増殖なし。**§6 判定: PASS**

---

## PHASE 7 — VISUAL AUDIT（構造監査、変更なし）

Ownerが指摘した「Contact/Office Information下部」＝「事務所概要ページの`astrea-office-page`グループ以降」を、実際にPlaywrightでレンダリングして構造を監査した（Desktop 1440px／Mobile 390px）。

### 構造の実態

`core/includes/setup-pages.php`が生成するMarkup自体は `wp:group {"layout":{"type":"flex","orientation":"vertical"}}`（＝子要素を**縦積み**にする指定）だが、実際のDesktop表示は**左：事務所情報カード／右：営業時間テーブルの2カラム**になっている。

原因は、Theme側`theme.json`の`styles.css`に埋め込まれた以下のCSSルール（`.astrea-office-page`クラス名を対象とした、コアWordPressのflex-columnを明示的に上書きするデザイン）：

```css
.astrea-office-page.is-layout-flex{
  display:grid;
  grid-template-columns:minmax(260px,1fr) minmax(0,1.4fr);
  align-items:start;
  column-gap:var(--wp--preset--spacing--large);
  row-gap:var(--wp--preset--spacing--small);
}
.astrea-office-page.is-layout-flex>:nth-child(1){grid-column:1;grid-row:1/3;}   /* office-summaryカード → 左列、1〜2行分 */
.astrea-office-page.is-layout-flex>:nth-child(2){grid-column:2;grid-row:1;margin:0;}  /* 見出し「営業時間」→ 右列1行目 */
.astrea-office-page.is-layout-flex>:nth-child(3){grid-column:2;grid-row:2;}     /* office-hoursテーブル → 右列2行目 */
.astrea-office-page.is-layout-flex>:nth-child(4){grid-column:1/-1;grid-row:3;margin:...;}  /* office-sns（空なら非表示）→ 全幅3行目 */
.astrea-office-page.is-layout-flex>:nth-child(5){grid-column:1/-1;grid-row:4;}  /* 5番目の子要素があれば全幅4行目 */

@media (max-width:781px){
  .astrea-office-page.is-layout-flex{display:flex;flex-direction:column;}
  /* 782px未満では通常のflex-column（縦積み）に戻す */
}
```

つまりこれは**意図的なレスポンシブデザイン**であり、「Desktop専用hack」（1024px以下の考慮が無いもの）ではない。782pxブレークポイントでモバイル向けの縦積みへ正しく切り替わることをPHASE 9で確認済み。

### 高さバランス・幅の実測

- 左列（`minmax(260px,1fr)`）は`grid-row:1/3`で2行分の縦スパンを占めるが、実コンテンツ（事務所名・所在地・電話番号の3行程度）は短く、カードの背景色ボックス自体もコンテンツ量に応じた高さしか持たない。右列（`minmax(0,1.4fr)`）は「営業時間」見出し＋7日分のテーブルで縦に長い。結果、grid cell内で左カードは`align-items:start`によりトップ寄せされ、**セル下部（右列テーブル下端までの残り部分）に大きな空白ができる**。これがOwner指摘の「左右カラムの高さ差」「左下の大きな空白」の技術的原因。
- **この問題はdisclosureの有無と無関係。** PHASE 4のStarter Site単体（disclosureなし）でも同一の空白が再現することを実地確認済み（後述スクリーンショット）。
- Disclosure box（Live Demoのみ）は`astrea-office-page`グループの**外側**（`build-content.php`／`add-live-demo-disclosure.php`が`post_content`文字列の末尾に単純追記するsibling要素）にあり、`is-layout-constrained`（theme.jsonの`contentSize: 720px`、中央揃え）。一方`astrea-office-page`自体は別のCSSルール（`main.alignfull>.wp-block-post-content>.astrea-office-page{max-width:none;}`）でwide/full相当まで幅が解除されている。この**幅の差**（office-pageは広い、disclosureは通常記事幅で狭い）により、直前セクションとの対比でdisclosureが「相対的に左に寄って、幅が狭く見える」。
- Footerとのspacing: disclosure直後に通常のブロック間gapのみでFooterへ接続。office-page内のnth-child(4)/(5)に適用される`margin:var(--wp--preset--spacing--medium)`の恩恵はdisclosureが受けない（構造上office-pageの外にあるため）。

### Desktop実測（1440px、事務所概要ページ）

- Live Demo状態（disclosureあり）：左カード／右テーブルの高さ差、左下の空白、disclosureの左寄り表示、Footer直前のバランスの悪さの全てを実地確認。
- Starter Site状態（disclosureなし）：**disclosure由来の問題（左寄り表示）は当然解消される**が、**カードの高さ差・左下の空白はStarter Site自体にも残存する**（disclosure削除だけでは解決しない別問題）。

### Mobile実測（390px）

782pxブレークポイントにより、Live Demo・Starter Siteとも自然な縦積み（事務所情報カード → 営業時間 → [Live Demoのみ]Disclosure → Footer）に戻ることを確認した。Desktop専用hackには該当しない。

---

## PHASE 8 — VISUAL REFINEMENT PROPOSAL（未実装・提案のみ）

**今回は構造監査に留め、見た目は一切変更していない。** 以下はOwner確認待ちの改善案（3案）。

### 案A（推奨）: カード視覚重量の統一 + Disclosureをwide幅ラッパーで2カラム全体幅に揃える

- Starter Site側：`.wp-block-astrea-office-summary`カードの背景ボックスに`align-self:stretch`（またはgrid cell内`height:100%`相当）を追加し、左カードの背景を右列（営業時間）の高さいっぱいまで伸ばす。中身（事務所名・住所・電話番号）は上寄せのまま、背景色だけがセルを覆うことで「唐突な空白」が「余白のあるカード」に変わり、左右の視覚重量が揃う。
- Live Demo側：`add-live-demo-disclosure.php`が生成するdisclosure groupに`alignwide`相当のクラスを付与し、2カラムカードと同じ横幅（wide幅）で表示させる。**Starter Site自体の構造には一切触れない**（disclosure groupは引き続きoffice-page外のsibling）。disclosure削除後（＝Starter Site単体）は該当groupごと存在しないため、spacingへの影響もゼロ。
- 変更範囲：Theme CSS 1箇所 + Live Demo専用スクリプト1箇所のみ。最小の変更でOwner指摘4点のうち3点（高さ差・空白・disclosureの左寄り）を解消できる。

### 案B: 「営業時間」見出しをカード内に統合し、2枚のカードとして完全対称化

- `office-hours-block.php`の出力形式を変更し、見出しをoffice-hoursの背景ボックス内に含める（現状は見出しがoffice-page groupの直下、カード外）。office-summaryカードと完全に同じ「タイトル+背景ボックス」構造になり、視覚的により明確な「2枚のカード」に見える。
- 変更範囲：`office-summary-block.php`／`office-hours-block.php`のrender_callback双方、および関連CSS。案Aより影響範囲が大きい（Dynamic Blockの出力形式変更のため、他の設置箇所［Editor Pattern等］への影響も要確認）。

### 案C: `grid-template-rows`を明示指定して高さを完全同期

- 現状の暗黙的な行高さ指定（`grid-row:1/3`と`grid-row:1`/`2`の組み合わせ）ではなく、`grid-template-rows`を明示し、両カラムの各行の高さを完全に揃える。ただし実質的な見た目の効果は案Aとほぼ同じであり、CSSの複雑さが増す割に得られる違いは小さい。

**Recommended: 案A**（最小変更・Starter Site構造非破壊・disclosure問題とカード高さ問題を同時に緩和できるため）。

---

## PHASE 9 — MOBILE CHECK

390pxで確認した結果：

- Starter Site: 事務所情報 → 営業時間 → Footer（縦積み、自然）
- Live Demo: 事務所情報 → 営業時間 → Demo Disclosure → Footer（縦積み、自然）

いずれも782pxのメディアクエリで通常のflex-column表示に戻るため、Desktop専用hackには該当しない。**§9 判定: PASS**

---

## PHASE 10 — 029 REQUIREMENT

Construction 029（Starter Import）の正式要件へ以下を追加する：

**MUST:**
> Starter Importによって生成されるユーザーサイトには、ASTREA Live Demo専用Disclosureを含めない。Starter Import機能は`build-content.php`（9-step標準パイプライン）のみを実行対象とし、`add-live-demo-disclosure.php`を呼び出してはならない。

**MUST（レイヤー分離の明確化）:**
> Starter content（`build-content.php`等9-step標準パイプライン）とDemo-only content（`add-live-demo-disclosure.php`）を、Importer設計上も明確に別レイヤーとして扱うこと。将来的にLive Demo以外の追加公開インスタンスが必要になった場合も、同じ「Starter Site + 専用overlay」構造を再利用できるようにする。

この要件はConstruction 029の設計フェーズで参照されるべきものとして記録するに留め、**今回は一切実装しない**。

---

## Known Issues / Remaining（今回未解決のまま記録）

1. **左右カラムの高さバランス問題（PHASE 7）は今回未修正。** disclosureの有無とは独立した、Starter Site自体の既存デザイン課題。改善案（PHASE 8）はOwner確認待ち。
2. Disclosureの視覚的配置改善（幅・spacing）も同様にOwner確認待ち、未実装。
3. Construction 028由来のKnown Issues（`build-content.php`の真の冪等マージ未実装、既存サイト誤爆防止gate未実装等）は今回のスコープ外であり、無変更のまま。
4. `yamada-demo-export.wxr`の既知課題（Construction 027から継続）も今回無変更。

---

## Construction 028-F — Review Ready

- Starter / Live Demo Boundary: **PASS**（`build-content.php`からdisclosureを分離し、`add-live-demo-disclosure.php`へ移動。Theme/Coreには元々disclosureロジックなし）
- Starter Disclosure: **0**（PHASE 4実測。project-if.jpへの唯一の言及はTheme footerの制作者クレジット「Theme by Project-if」であり、demo説明ではない）
- Live Demo Disclosure: **1**（PHASE 5実測、`add-live-demo-disclosure.php`実行後）
- Repeatability: **PASS**（2回目・3回目とも「already present — no change」、count=1のまま増殖なし）
- Starter Regression: **0**（Construction 028 RUN#1ベースラインとdev-snapshot.php比較、ポート番号以外の差分なし）
- Theme/Core Modification: **NONE**（今回の変更はStarter Siteスクリプト（`build-content.php`修正・`add-live-demo-disclosure.php`新規）とREADME.mdのみ。`theme/`・`core/`は無変更）
- Visual Balance Audit: **COMPLETE**（構造原因を特定：Theme側`theme.json`の`.astrea-office-page.is-layout-flex`に対するCSS Grid上書きが2カラム化の原因。782pxで意図通り縦積みに戻る、Desktop専用hackではない。左右高さ差・空白はdisclosure非依存の既存課題）
- Recommended Visual Option: **案A**（カード背景の`align-self:stretch` + Live Demo側disclosureへの`alignwide`付与。Starter Site構造は非破壊）
- 029 Requirement: **RECORDED**（Starter ImportはLive Demo専用Disclosureを含めてはならない旨、およびStarter content／Demo-only contentのレイヤー分離をMUST要件として追加）
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Review: REQUIRED

STOP

---

## Construction 028-F — FINALIZE / CLOSE

- Finalize Start: 2026-09-12（Owner Review PASS受領後）
- Modifier: Chloe
- Owner Review: **PASS**（Starter / Live Demo Boundary PASS、Starter Disclosure 0、Live Demo Disclosure 1、Disclosure Repeatability PASS、Starter Regression 0、Theme/Core Modification NONE、Visual Balance Audit COMPLETE、Recommended Visual Option 案A、029 Requirement RECORDED — Owner確認済み）

### §1-2 PRE-COMMIT CHECK / 対象確認（実git diffベース）

```
$ git status --short --branch
## main...origin/main
 M HISTORY.csv                                                          ← Owner自身の編集。対象外
 M docs/demo-assets/astrea-starter-site/README.md                      ← 対象
 M docs/demo-assets/astrea-starter-site/scripts/build-content.php      ← 対象
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr        ← Owner既存差分。対象外
 D docs/research/screenshots/012〜（多数）                              ← Owner削除済み。復元せず、対象外
?? docs/demo-assets/astrea-starter-site/scripts/add-live-demo-disclosure.php ← 対象（新規）
?? docs/research/2026-09-11_construction_024...等                      ← 他Construction。対象外
?? docs/research/2026-09-12_construction_028f_starter_live_demo_boundary.md ← 対象（本報告書自身）
?? *.Zone.Identifier（3件）                                             ← Windows付随ファイル。対象外
?? docs/research/screenshots/026/                                       ← Construction 026分。対象外
?? if-professional-astrea.code-workspace                                ← 迷子ファイル。対象外

$ git rev-parse HEAD && git rev-parse origin/main
f6559a75a93968e85a51951669860053d678d6a8（両者一致、分岐なし）
$ git branch --show-current
main
```

`git diff --check` を対象2ファイル（`build-content.php`・`README.md`）に対して実行 → 出力なし（空白関連エラー0件）。`php -l` を`build-content.php`・`add-live-demo-disclosure.php`に実行 → 両方とも `No syntax errors detected`。

### §3 BOUNDARY FINAL REVIEW（実diffで再確認）

`git diff -- docs/demo-assets/astrea-starter-site/scripts/build-content.php` を全文精読し、削除された旧Phase 10ブロック（disclosure文字列のハードコード・`wp_update_post`呼び出し）が完全に除去され、境界説明コメントのみに置き換わっていることを再確認した。`add-live-demo-disclosure.php`本体を再読し、以下を再確認した：

- Live Demo専用であることがdocblockで明確（Starter Site本体には含めない旨を明記）。
- `build-content.php`から呼び出されず、独立スクリプトとして存在（自動実行されない）。
- `astrea-demo-disclosure`className既存チェックによる冪等性（重複追加防止）。
- `get_page_by_path('事務所概要')`によるtitleベース検索のみで、固定post IDへの危険な依存なし。
- `home_url()`等のURL関連コードを一切含まず、localhost固定依存なし。
- production credential・DB接続情報等を一切含まない。
- `wp_update_post`／`get_page_by_path`／`get_posts`のみを使用し、Theme/Core固有関数への依存を増やしていない。
- 将来Starter Importがこのスクリプトを呼び出す構造にはなっていない（呼び出し元が存在しない独立ファイル）。

**§3 判定: PASS**（全チェック項目クリア）

### §4 STARTER FINAL TEST（ローカルのみ・本番VPS不使用）

新規クリーン環境（`wp-playground-cli server`、theme/core/scripts/imagesをmount、`wp_loaded`フックのテスト専用mu-plugin harness経由）を構築し、Starter 9-stepパイプラインのみを実行した。

```
Demo Disclosure occurrence:              0
「このWebサイトは」:                       0
「デモサイトです」:                        0
公開デモ用「架空の情報」注意書き:            0
Demo-only project-if dependency:          0
project-if.jp参照（Theme footerクレジット "Theme by Project-if"）: 1件（許容 — Demo Disclosureではない）
Starter Site identity: ASTREA行政書士事務所 — 確認
Representative: 伊吹 文人 — 確認
```

`dev-snapshot.php`によるsnapshotをConstruction 028のRUN#1ベースライン（`run1.json`）と比較した結果、**差分はテスト環境のポート番号のみ**で、他の全フィールドが完全一致した。

**§4 判定: PASS（Regression: 0）**

### §5 LIVE DEMO LAYER TEST（ローカルのみ）

同一環境でStarter 9-step完了後に`add-live-demo-disclosure.php`を実行した。

```
1回目: "Live Demo disclosure appended to page 27."          → count = 1
2回目: "Live Demo disclosure already present ... no change." → count = 1
3回目: "Live Demo disclosure already present ... no change." → count = 1
duplicate: 0
```

Starter版snapshotとLive Demo版snapshotを比較した結果、`diff`は完全に空（disclosure追加以外の副作用は皆無）。`attachment_count`・`duplicate_filenames`・`navigation_count`・`navigation_duplicate_labels`いずれも異常なし。

**§5 判定: PASS**

### §6 DISCLOSURE SCRIPT REVIEW

§3で再確認済み。全要件（Live Demo専用明示・自動実行されない・冪等・重複防止・post ID非依存・localhost非依存・credential非依存・Theme/Core依存増加なし・将来Importからの呼び出し構造なし）を満たすことを確認した。

**§6 判定: PASS**

### §7 029 REQUIREMENT FINAL CHECK

報告書PHASE 10に記録済みの以下3件のMUST要件を再確認した：

1. ASTREA Starter Importは`add-live-demo-disclosure.php`を実行してはならない。
2. Starter contentとLive Demo-only contentを別レイヤーとして扱う。
3. ユーザーがStarter Importしたサイトには、ASTREA公開デモ専用Disclosureを含めない。

**§7 判定: RECORDED（実装は一切行っていない）**

### §8 VISUAL AUDIT HANDOFF

PHASE 7/8で確認・提案した内容（左右カード高さ差、左カード下部の空白、Office Information/Business Hoursのvisual weight差、Live Demo Disclosureの720px幅とoffice-page wide幅の不一致、Footer直前のバランス、Recommended：案A）を「未解決のVisual refinement」として維持する。**案Aは今回実装していない。** 今回のcommit差分（`build-content.php`・`add-live-demo-disclosure.php`・`README.md`・本報告書）にCSS/layout変更は一切含まれていないことを、後述§11のstage差分で確認する。

**Visual change: NONE**

### §9 THEME / CORE PROTECTION

```
$ git diff --stat -- theme/ core/
（出力なし）
$ git status --short -- theme/ core/
（出力なし）
```

Theme = 1.0.3、Core = 1.0.1、Theme/Core Modification = NONE を再確認した。意図しない差分は検出されなかった。

**§9 判定: PASS（想定通り差分なし）**

### §10-11 コミット対象・除外対象の最終確定 / STAGE

**コミット対象（Construction 028-F由来と`git diff`/`git status`で確認できたもののみ）:**
- `docs/demo-assets/astrea-starter-site/scripts/build-content.php`
- `docs/demo-assets/astrea-starter-site/scripts/add-live-demo-disclosure.php`（新規）
- `docs/demo-assets/astrea-starter-site/README.md`
- `docs/research/2026-09-12_construction_028f_starter_live_demo_boundary.md`（本報告書、新規）

**除外（Owner自身の作業・他Construction・迷子ファイル）:**
- `HISTORY.csv`（Owner編集中）
- `docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr`（Owner既存差分）
- `docs/research/screenshots/012〜`配下の削除群（Owner削除済み、復元せず）
- `docs/research/2026-09-11_construction_024...`等（他Construction）
- `docs/research/screenshots/026/`
- `*.Zone.Identifier`（3件）
- `if-professional-astrea.code-workspace`

**Final Verification:** §3-9 全項目PASS。Theme/Core差分0。Visual変更0（コード上もCSS/layout変更を一切含まない）。

**Deploy Decision: NOT REQUIRED。** 現在の公開Live Demoには既に必要なDisclosureが存在しており、今回の変更は将来のStarter Importに向けたsource architecture整理（Starter正本からDemo-only contentを分離）であり、公開Live Demoの表示変更を一切要求しない。

**Remaining Visual Issue:** Office Information / Business Hours / Disclosure / Footerのvisual balance（案A未実装、Owner確認待ち）。

**Next Construction Handoff:** 029 MUST要件はRECORDED済み。Visual改善（案A）は別Constructionとして扱う（本Orderの締めくくりで次点候補として言及されるのみで、着手しない）。

### §12 COMMIT

`git add`を上記コミット対象4ファイルに限定して実行し、`git diff --cached --stat`で除外対象が一切含まれていないことを確認した上でコミットした。

```
git commit -m "Separate ASTREA starter and live demo content"
```

- Commit hash: `a72ed138b8d9b8499bfb6d8951cee0d593a6e646`（push確認後、Construction 027/028の前例に倣いフォローアップコミットで本報告書に追記）

### §13 POST-COMMIT CHECK

コミット後、`git status --short --branch`でOwner自身の未commit差分（`HISTORY.csv`・`yamada-demo-export.wxr`）が削除・stash・commitされずそのまま残っていることを確認した。`git log -1`でコミット内容がConstruction 028-Fスコープの4ファイルのみであることを確認した。

### §14 PUSH

```
git push origin main
```

push後、`git rev-parse HEAD`と`git rev-parse origin/main`が一致することを確認した。force pushは使用していない。

### §15 DEPLOY DECISION

**実施していない。** VPSへのSSH接続・nginx/PHP設定変更・robots.txt修正・Live Demo（本番）のWordPress DB変更等、Live Demoに影響する操作は一切行っていない。Live Demo: UNCHANGED。

---

## Construction 028-F — CLOSED

- Owner Review: PASS
- Starter / Live Demo Boundary: PASS
- Starter Disclosure: 0
- Live Demo Disclosure: 1
- Disclosure Repeatability: PASS
- Starter Regression: 0
- Theme/Core Modification: NONE
- Theme: 1.0.3
- Core: 1.0.1
- Visual Balance Audit: COMPLETE
- Recommended Visual Option: 案A
- Visual Option Implementation: NOT DONE
- 029 Requirement: RECORDED
- Commit: `a72ed138b8d9b8499bfb6d8951cee0d593a6e646`
- Push: PASS
- Deploy: NOT REQUIRED / NOT RUN
- Live Demo: UNCHANGED
- Remaining Visual Issue: Office Information / Business Hours / Disclosure / Footer balance
- Next Recommended Construction: Construction 028-G — Office Information Visual Refinement

STOP
