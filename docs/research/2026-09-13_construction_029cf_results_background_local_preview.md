# Construction 029-CF — Results Background Repair / Local Preview Stabilization

- Start: 2026-09-13
- Modifier: Chloe
- Mode: **ローカル調査・修復・検証のみ。commit / push / deployは一切実施していない。** Owner Visual Review前提。

---

## PHASE 0 — PRECHECK結果

```
$ git rev-parse HEAD && git rev-parse origin/main
925ccbd498152a087aaf1bd009556d847ff65c85（両者一致、分岐なし）
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`）は無変更のまま。

**Baseline（Construction 029-C CLOSE時点）:**
- 029-B + 029-C tests: 57/57 PASS
- 既存Core test suite: 456 total / 453 PASS / 3 errors（`SeoMetaTest`×2、`SetupTest`×1、`DIR_TESTDATA`依存の既知環境エラー）
- Theme: 1.0.3 / Core: 1.0.1（いずれもConstruction 029-B/029-Cでは無変更）

**Owner報告のバグ**: `http://localhost:8888/` のHome「実績（Results）」セクションで、紺色のoverlay/背景・見出し・3つの数値・区切り線・アイコンはすべて表示されているが、本来表示されるべき背景写真だけが表示されていない。

---

## PHASE 1-5 — ROOT CAUSE調査

### 調査手順

1. `wp-env`のcontainer構成を確認。1つの`.wp-env.json`から「development」環境（`-wordpress-1`/`-cli-1`、`http://localhost:8888`）と「tests」環境（`-tests-wordpress-1`/`-tests-cli-1`、`http://localhost:8889`、PHPUnit専用）が別々に生成されていることを確認した。Ownerが実際にブラウザで見ているのは**development環境（8888）**であり、テストで使う8889側とは全く別のDB・別のコンテンツを持つ。
2. `wp-env run cli wp post list --post_type=page` 等でdevelopment環境のHomeページ（page ID 1914）の`post_content`を直接確認。
3. 該当ページの実績セクションが以下のような**素のまま**のブロックだったことを確認：
   ```html
   <!-- wp:astrea/results-list {"heading":"実績"} /-->
   ```
   Coverブロックによるラップが一切存在せず、対応する添付ファイルも存在しなかった。
4. Media Libraryのタイトルを調査したところ、「Verification Fixture Portrait（鈴木/田中/佐藤）」「佐藤健一（代表行政書士）」等、Construction 027/028公式Starter Siteの人格（ASTREA行政書士事務所／伊吹文人）とは無関係な、別系統の検証用フィクスチャコンテンツで構築されたサイトだったことが判明した。

### 根本原因（確定）

**development環境（`localhost:8888`）は、Starter Siteパイプラインの一部である`integrate-025e1-results-background.php`（実績セクションにCoverブロック＋背景写真を統合するスクリプト）が一度も実行されていない、別系統の検証用フィクスチャサイトだった。**

このため実績セクションは「素のまま」の`astrea/results-list`ブロックのみで、Coverによる背景画像の仕組みそのものが存在しない状態だった。

**Construction 029-B / 029-Cとの関係**: 無関係であることを確認した。029-B/029-CのState Model・Preflight・Lock・Ownershipコードは、このコンテンツにも、このスクリプトにも一切触れていない。

**紺色overlayだけが見えた症状の説明**: theme.jsonの既存CSSルールにより「実績」の`<h2>`見出し自体が紺色で着色される仕様のため、Coverブロック（と背景写真）が存在しなくても見出し部分は紺色に見える。Ownerが見た「overlayらしきものは残っているが写真がない」という見え方は、この既存の見出し色指定と、写真統合ステップが未実行だった状態の組み合わせで矛盾なく説明できる。

**結論**: これはStarter Siteパイプライン自体のバグではなく、development環境の構築履歴に起因する1回限りの欠落。Starter Site本体・Theme・Coreのソースコードには問題がないことをPHASE 8のClean Rebuildで確認済み（後述）。

---

## PHASE 6-7 — REPAIR

### 実施内容

Construction 028で堅牢化済みの、**未改変の**`docs/demo-assets/astrea-starter-site/scripts/integrate-025e1-results-background.php`を、development環境の実データに対して実行した。

**環境整備（スクリプト自体は無改変）:**
- `cli`コンテナ内に`/wordpress`（`/var/www/html`へのsymlink）と`/images/web/`を作成（スクリプトが期待するPlayground形式のパス）。
- `docs/demo-assets/astrea-starter-site/images/web/astrea-demo-starter-results-background.jpg`を`docker cp`で`cli`コンテナ（`wp-env-if-professional-astrea-57b77809-cli-1`）にコピー。
- `wp eval-file`でスクリプトを実行。

**実行結果:**
- 素のままだった`astrea/results-list`ブロックが、単一の（二重ネストなし）Coverブロックで正しくラップされた。
- 新規添付ファイル（ID 2246、`astrea-demo-starter-results-background.jpg`）がMedia Libraryに作成された。
- Cover blockの背景URLは動的に生成された添付ファイルURLを参照しており、attachment IDやURLのハードコードは無い（スクリプトの既存の`serialize_block()`ヌルプレースホルダ機構をそのまま利用）。

### ポータビリティ確認

- CSS `background-image`のハードコード: **0**
- localhost/production/demo URLのハードコード: **0**
- Attachment IDのハードコード: **0**（`find_existing_attachment_by_filename()`による動的解決）
- Live Demo Disclosure: **0**（このスクリプトはDisclosureに一切関与しない）
- Theme/Core側の変更: **0**（Theme/Coreソースは無改変）

---

## PHASE 8 — 冪等性検証（RUN #1/#2/#3）

同じスクリプトを間隔を空けて3回連続実行し、都度DB状態を確認した。

| RUN | 結果 | Cover count | 添付ファイル増加 |
| --- | --- | --- | --- |
| #1（初回・実修復） | Coverでラップ成功 | 1 | +1（新規作成、ID 2246） |
| #2 | "Reusing attachment 2246" と表示、no-op | 1（変化なし） | 0 |
| #3 | "Reusing attachment 2246" と表示、no-op | 1（変化なし） | 0 |

**Results Cover Count: STABLE（常に1）／Nested Cover Regression: 0／Duplicate Attachment Growth: 0**

---

## PHASE 9 — CLEAN REBUILD（使い捨て環境での再現性検証）

Construction 028で確立済みの使い捨てPlayground CLIハーネス（scratchpadパス`c028-final`）を再利用し、現行のStarter Siteスクリプト一式を同期した上で、フルの9ステップパイプラインを**ゼロからのWordPressインスタンス**に対して最初から最後まで実行した。

**結果:**
```
results_wrapper_count: 1
max_depth: 0（二重ネストなし）
office_profile_office_name: "ASTREA行政書士事務所"（正しい公式Starter Siteの人格）
old_yamada_count: 0（旧フィクスチャ汚染なし）
重複ファイル名: 0
```

これにより、**Starter Siteパイプライン本体・スクリプト自体には一切問題がなかった**ことが確認された。今回の欠落は、development環境という個別のコンテナ内容物の構築履歴に限定された事象である。

---

## PHASE 10 — 視覚検証（Desktop / Mobile）

修復後のdevelopment環境（`http://localhost:8888/`）で、実績セクションおよびHomeページ全体をDesktop（1440px）・Mobile（390px）でスクリーンショット撮影した。

### 実績セクション単体

- **Desktop**（`029cf-results-desktop.png`）: 写真はdimRatio=80（Construction 025-E1で意図的に選択された強めのoverlay）により、非常に控えめで、ほぼ一様な暗さとして見える。一見「写真が無いのでは」と疑われる見え方だが、Construction 016j時点のOwner承認済み過去スクリーンショット（`docs/research/screenshots/016j/08-results-background.png`）と照合したところ、**全く同じ「写真はほぼ見えず、紺色overlayが支配的」という外観**であることを確認した。これはConstruction 025-E1自身が明記した設計意図（「写真は控えめな存在に留め、数字を主役にする」）どおりの、正しい・既承認の意匠であり、バグではない。
- **Mobile**（`029cf-results-mobile.png`）: `object-fit:cover`によるクロップの違いにより、Desktopより写真の存在が明確に視認できる（ビル群のシルエット・デスクとペンの構図がはっきり判別可能）。写真が実際にレンダリングされていることを独立して裏付けている。

### Home全体（回帰確認）

- **Desktop**（`029cf-home-full-desktop.png`）/ **Mobile**（`029cf-home-full-mobile.png`）: Header、Hero、Services（取扱業務）、Cases（対応事例）、Results（実績）、代表者紹介、料金、CTA、よくあるご質問、お客様の声、ご相談の流れ、Footerまで、全セクションが正常にレンダリングされていることを目視で確認した。レイアウト崩れ・ブロック欠落・スタイル異常は検出されなかった。

**Desktop Visual: PASS / Mobile Visual: PASS**

---

## PHASE 11-12 — LOCAL PREVIEW STABILIZATION

### 事実確認

- `npx wp-env start`で起動する「development」環境が、Ownerが実際にブラウザで閲覧するための、既存の・安定した・適切なPreview環境であることを確認した。
- Local Preview URL: `http://localhost:8888/`
- Local Admin URL: `http://localhost:8888/wp-admin/`
- これとは別に「tests」環境（`http://localhost:8889`、`wp-env run tests-cli vendor/bin/phpunit`専用）が存在し、PHPUnitの自動テストにのみ使われる。この2つの環境を混同しないことが重要（`wp-env run cli`がdevelopment環境、`wp-env run tests-cli`がtests環境）。

### Preview Data Policy（設計提案。実装は不要と判断——今回は実装しない）

今回の根本原因は「development環境のコンテンツが、公式Starter Siteパイプラインを経由せずに、別の検証用フィクスチャで構築されていた」ことに起因する。今後同種の混乱・欠落を防ぐため、以下の役割分離を提案する（Construction 029-D着手前の設計メモであり、今回のConstructionでは実装しない）：

- **A. Owner Visual Preview環境**: `http://localhost:8888`のdevelopment環境をOwnerの継続的な閲覧用として維持する。今回のように、Starter Siteパイプラインの変更を検証する際は、既存のPreviewコンテンツを破壊せずに、個別スクリプトの再実行（今回行ったのと同じ手法）で反映することを基本とする。
- **B. Construction 029-DのFresh Import Testing**: 029-D以降で「まっさらな状態からのインポート」を検証する必要がある場合は、Ownerの閲覧用コンテンツを破壊しない、使い捨て環境（今回のPHASE 9で使った`c028-final`方式のPlayground CLIハーネス、または専用の追加wp-env環境）を使うことを推奨する。development環境そのものをFresh Import Testingの実験台にしない。

**Local Preview: READY**

---

## PHASE 13-14 — スクリーンショット（Owner Review用）

`docs/research/screenshots/029-CF/`に格納（Construction 028-G以降の慣習に合わせた配置）:

| ファイル | 内容 |
| --- | --- |
| `029cf-results-desktop.png` | 実績セクション単体・Desktop |
| `029cf-results-mobile.png` | 実績セクション単体・Mobile |
| `029cf-home-full-desktop.png` | Home全体・Desktop（回帰確認用フルページ） |
| `029cf-home-full-mobile.png` | Home全体・Mobile（回帰確認用フルページ） |

---

## PHASE 15-16 — REGRESSION

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter "StarterImportStateTest|StarterImportPreflightTest"
OK (57 tests, ...)

$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 456, Assertions: ..., Errors: 3.
1) SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback
2) SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image
3) SetupTest::test_checklist_seo_og_image_item_reflects_setting
```

Construction 029-C CLOSE時点のbaseline（453 PASS / 3 errors、同一のテスト・同一のエラー内容）と完全に一致。**内容・件数の変化なし。**

Home全体のDesktop/Mobileフルページスクリーンショット（PHASE 10）で、Header/Hero/Services/Cases/Results/代表者紹介/料金/CTA/FAQ/お客様の声/ご相談の流れ/Footer/ナビゲーション/モバイルレイアウトを目視確認し、レイアウト崩れ等の回帰は検出されなかった。

**029-B+029-C Tests: 57/57 PASS**
**Existing Core Tests: 453/456 PASS**
**Existing Test Errors: 3（unchanged）**
**Regression: 0**

---

## PHASE 17 — Theme / Core への影響

```
$ git status --short core/ theme/ docs/demo-assets/
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr   ← Owner既存の未commit差分（無関係・無変更）
```

`core/`・`theme/`に対する変更は一切なし。今回の修復はdevelopment環境コンテナのDB内容と一時的なコンテナファイルシステム（`/wordpress`シンボリックリンク、`/images/web/`）に対してのみ行われたものであり、リポジトリ内のTheme/Coreソースコードには一切触れていない。

**Theme Modification: NONE / Theme Version: 1.0.3（変更なし）**
**Core Modification: NONE / Core Version: 1.0.1（変更なし）**

---

## PHASE 18 — Live Demo / Starter境界確認

- 今回の修復はdevelopment環境という**ローカルのみ**のコンテナに対して行われたものであり、本番（Live Demo）環境・リポジトリ内容には一切影響していない。
- 使用したスクリプト（`integrate-025e1-results-background.php`）自体はConstruction 028で既にLive Demo Disclosure非依存・Starter Site本体パイプライン所属であることが確認済みで、今回も無改変のまま利用した。
- Starter Demo Disclosure: **0**（このスクリプト・この修復のいずれにも一切関与なし）
- Live Demo Dependency: **0**

**Production: UNCHANGED**

---

## PHASE 19 — 変更ファイル一覧

**Gitで追跡されるリポジトリ内の変更:**
- なし（`core/`・`theme/`・`docs/demo-assets/`に対する変更は一切なし）

**今回追加したドキュメント（本レポート・スクリーンショット）:**
- `docs/research/2026-09-13_construction_029cf_results_background_local_preview.md`（本レポート）
- `docs/research/screenshots/029-CF/029cf-results-desktop.png`
- `docs/research/screenshots/029-CF/029cf-results-mobile.png`
- `docs/research/screenshots/029-CF/029cf-home-full-desktop.png`
- `docs/research/screenshots/029-CF/029cf-home-full-mobile.png`

**リポジトリ外（Dockerコンテナ・DB）に対する変更:**
- development環境（`wp-env-if-professional-astrea-57b77809-*`）のWordPress DB上のHomeページ（post ID 1914）のResultsセクションをCoverブロックでラップ。
- development環境のMedia Libraryに添付ファイル（ID 2246）を追加。
- `cli`コンテナ内に`/wordpress`シンボリックリンクと`/images/web/`ディレクトリを作成（コンテナの一時ファイルシステムのみ、リポジトリ・Git管理外）。

これらはすべてローカルのDocker環境内の変更であり、commit/push/deploy対象ではない。

---

## Construction 029-CF — Visual Review Ready

```
Root Cause: IDENTIFIED
Results Background Repair: COMPLETE
CSS Hard-code: 0
Environment-specific URL: 0
Attachment ID Hard-code: 0
WordPress Standard Editability: PASS
Starter Portability: PASS
Results Cover Count: STABLE
Nested Cover Regression: 0
Duplicate Attachment Growth: 0
Repeat RUN #1/#2/#3: PASS
Clean Rebuild: PASS
Local Preview: READY
Local Preview URL: http://localhost:8888/
Local Admin URL: http://localhost:8888/wp-admin/
Desktop Visual: PASS
Mobile Visual: PASS
Starter Demo Disclosure: 0
Live Demo Dependency: 0
029-B+029-C Tests: 57/57 PASS
Existing Core Tests: 453/456 PASS
Existing Test Errors: 3 (unchanged)
Regression: 0
Theme Modification: NONE
Theme Version: 1.0.3
Core Modification: NONE
Core Version: 1.0.1
Commit: NOT DONE
Push: NOT DONE
Deploy: NOT DONE
Production: UNCHANGED
Owner Visual Review: REQUIRED
```

**STOP。Owner Visual Reviewを受けるまでcommit / push / deployは行わない。Construction 029-Dには着手しない。**

---

## FINALIZE / CLOSE（Owner Visual Review: PASS）

- Owner Visual Review: **PASS**
- Construction Status: **CLOSED**

### 再検証結果（FINALIZE時点）

```
$ git status --short core/ theme/ docs/demo-assets/
 M docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr   ← Owner既存差分（無関係・無変更）
```
core/・theme/に対する変更は無し（PHASE 17時点から変化なし）。

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/
200
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/wp-admin/
302（未ログイン時の正常なログイン画面へのリダイレクト。Admin到達可能）
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/wp-content/uploads/2026/09/astrea-demo-starter-results-background.jpg
200
```

Results background: **VISIBLE**（HTTP 200で画像本体を確認）。

```
$ npx wp-env run tests-cli vendor/bin/phpunit --filter "StarterImportStateTest|StarterImportPreflightTest"
OK (57 tests, 146 assertions)

$ npx wp-env run tests-cli vendor/bin/phpunit
Tests: 456, Assertions: 809, Errors: 3.
1) SeoMetaTest::test_ogp_image_prefers_featured_image_over_site_fallback
2) SeoMetaTest::test_ogp_image_falls_back_to_site_wide_image
3) SetupTest::test_checklist_seo_og_image_item_reflects_setting
```
PHASE 15-16で確認したbaselineと完全に一致。件数・内容とも不変。

### 最終判定

- Root Cause: development environment used fixture content and had not run `integrate-025e1-results-background.php`
- Pipeline defect: **NONE**（Starter Siteパイプライン本体・スクリプトは無傷。Clean Rebuildで再確認済み）
- Theme/Core defect: **NONE**（Theme/Coreソースコードは無改変。Version不変）
- Production impact: **NONE**（今回の修復はローカルdevelopment環境のDocker containerのみに影響。本番Live Demo・リポジトリには一切影響なし）

### Construction 029-CF — CLOSED

```
Owner Visual Review: PASS
Report: FINAL
Local Preview: PASS
Results Background: PASS
Theme: 1.0.3
Core: 1.0.1
Production: UNCHANGED
Deploy: NOT RUN
```

次工程: ASTREA Admin UX / 事務所情報
