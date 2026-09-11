# Construction 025-E1 — Results Background Support / ASTREA Theme 1.0.3 Candidate

- Start: 2026-09-10 12:15:53 JST（実測、Preflight時）
- End: 2026-09-10 13:24:01 JST（実測）
- Duration: 1:08:08
- Modifier: Chloe
- Mode: Theme 1.0.3候補実装。Core 1.0.1据え置き。DB migrationなし。commit/push未実施（Order §16）。

**Final Verdict: A. PASS — ASTREA THEME 1.0.3 CANDIDATE READY**（軽微な既存問題の指摘を1件含む — §23参照）

---

## 1. Preflight

```
$ git status --short --branch
## main...origin/main
（既知のOwner差分：9月以前のhistorical screenshots削除、README.md/WXR/HISTORY.csv等）

$ git diff --stat -- theme/ core/
(no output)   ← 本Phase開始時点でTheme/Core無変更
```

過去screenshots 235件の削除（Owner既知の意図的容量整理）は復元・stage・変更していない。

## 2. HISTORY.csv cleanup

Construction 025-E0で報告した通り、`HISTORY.csv`の46行目（`013 RESEARCH`の行）に、Construction 025-E0のOrder文全体が誤って貼り付けられ、CSVの1行が496物理行（原文の埋め込み改行による）へ分裂・破壊されていた。

**修正内容**:

- **What was wrong**: 現行ファイルの46〜541行目が、本来1行だった`2026-08-28,013 RESEARCH,Chloe,-,RESEARCH COMPLETE,...`という履歴レコードの途中（`RES`と`EARCH COMPLETE`の間）にOrder文が挿入され、CSV構造が壊れていた。ファイル総行数は78→573行に膨張。
- **What was removed**: 46〜541行目（496行）——誤貼り付けされたOrder文と、それによって分断された`013 RESEARCH`レコードの断片。
- **What remains**: `git show HEAD:HISTORY.csv`の46行目（正しい`013 RESEARCH`の1行、772バイト）を`git`から直接取得し、46〜541行目をこの1行に置換。BOM（`\xef\xbb\xbf`）とCRLF改行を保持。
- **検証**: 修正後`git diff -- HISTORY.csv`が**空**（HEADとバイト単位で完全一致）。他の履歴レコードには一切触れていない。総行数78行（元通り）。

## 3. Baseline architecture（実装前の現行1.0.2実装）

- Results Pattern: `theme/patterns/home-results-teaser.php` — 中身は`<!-- wp:astrea/results-list {"heading":"実績"} /-->`の1行のみ。ラッパーなし。
- `astrea/results-list`はCore（`astrea-core`プラグイン）登録の完全カスタムDynamic Block。`supports`キーなし。属性は`heading`/`emptyMessage`のみ。背景画像・overlay・focal pointの属性・UIは一切なし（025-E0で確認済み）。
- navy背景は`theme/theme.json`の`styles.css`内、`.wp-block-astrea-results-list{...background:var(--wp--preset--color--contrast);...}`というThemeのCSSハードコード（ブロック属性ではない）。
- `paddingblock: var(--wp--preset--spacing--x-large)`（96px、モバイル56px）も同じCSSルール。区切り線・アイコン色（白）・ラベル色（gold）はすべて背景から独立したCSS。
- Baseline実測（1.0.2、port 8895）: Results全体（h2見出し＋grid）の高さ = Desktop 537.125px（h2 170 ＋ grid 367）、Mobile 785.359px。幅は1440px全幅（edge-to-edge）。

Baseline screenshot: `docs/research/screenshots/025-E1/before/`（full/results × 1440/390）

## 4. Implementation architecture（採用したWordPress-native方式）

**Order §4優先順位1（core/cover）を採用。Heroの`astrea-hero-photoplane`と同一の方式。**

`astrea/results-list`を`core/cover`でラップする。パターンは以下を出力する（無画像時）:

```html
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","align":"full","className":"astrea-results-photoplane","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
<div class="wp-block-cover alignfull astrea-results-photoplane" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container">
<!-- wp:astrea/results-list {"heading":"実績"} /-->
</div>
</div>
<!-- /wp:cover -->
```

設計のポイント:

- **無画像時**: `dimRatio:100`＋`overlayColor:contrast`でCover自身が濃紺の無地塗り。その背後で`astrea/results-list`自身のnavy CSS（無変更）も濃紺 → 二重の濃紺で、1.0.2と視覚的に完全に同一（後述§12でピクセル一致を実証）。
- **画像設定時**: `.astrea-results-photoplane:has(.wp-block-cover__image-background)`（＝Cover内に実画像がある時だけ）で`h2`と`.wp-block-astrea-results-list`の`background`を`transparent`へ。これで背景写真＋Coverのオーバーレイが透けて見える。
- **`style.spacing.padding:0`**（ブロック属性）: WP Core Cover既定の20pxインナーコンテナpaddingを打ち消し、`results-list`のedge-to-edge CSSがCoverの全幅に届くようにする。Block Editorが自身で生成するのと同一のインラインスタイル形式なので検証で問題にならない。
- **`.astrea-results-photoplane{min-height:0}`**（`theme.json` CSSルール）: WP Core Cover既定の`min-height:430px`を打ち消す。**ブロック属性の`minHeight:0`は使わない** — Gutenbergの`save()`は`minHeight:0`をfalsy扱いしてスタイルに出力しないため、markupに手書きすると保存内容とeditorの再生成が恒久的に食い違い「Block contains unexpected or invalid content」が毎回出る。CSSルールならブロック属性に触れず、検証にも影響しない。
- Coverの`is-layout-flex`＋`align-items:center`は、`results-list`のコンテンツ高さ（537px）がCore既定の430pxを上回るため実効的に無害（コンテンツが高さを決める）。

CSS変更（`theme.json`、303→305ルール、追加のみ・削除0・改変0）:

```css
.astrea-results-photoplane{min-height:0;}
.astrea-results-photoplane:has(.wp-block-cover__image-background) :is(h2,.wp-block-astrea-results-list){background:transparent;}
```

`:has()`は既に`theme.json`内で多数使用されている確立済みのパターン（`h2:has(+ .wp-block-astrea-results-list)`等）。

## 5. Changed files

**Theme（1.0.3候補、4ファイル）**:

| ファイル | 変更 |
| --- | --- |
| `theme/patterns/home-results-teaser.php` | `astrea/results-list`を`core/cover`（`astrea-results-photoplane`）でラップ。docblockに設計意図・後方互換の根拠・`minHeight:0`をCSSで扱う理由を明記。 |
| `theme/theme.json` | `styles.css`へ上記2ルール追加（追加のみ）。 |
| `theme/style.css` | `Version: 1.0.2` → `1.0.3` |
| `theme/readme.txt` | `Stable tag: 1.0.2` → `1.0.3`、Changelog `= 1.0.3 =` 追記（利用者向けの平易な説明、既存投稿の再保存不要を明記） |

**Core**: 変更ゼロ（`git diff --stat -- core/`空）。

**再現性パッケージ（`docs/demo-assets/yamada-live-demo/`）**:

| ファイル | 変更 |
| --- | --- |
| `README.md` | 025-E1追記、内容物・再現手順を6画像へ更新、1.0.3要件を明記 |
| `yamada-demo-export.wxr` | 36→37 items（Results背景attachment追加）で再エクスポート |
| `images/astrea-demo-yamada-results-background.png` | （既存の生成原本、無変更） |
| `images/web/astrea-demo-yamada-results-background.jpg`（新規） | q85 JPEG、クロップなし、163,521バイト（−89.9%） |
| `scripts/make-results-web-jpeg.php`（新規） | PNG→q85 JPEG変換 |
| `scripts/integrate-025e1-results-background.php`（新規） | Cover属性でResults背景を設定する冪等スクリプト |

**報告・証跡（新規）**: 本報告書、`docs/research/screenshots/025-E1/`（before/compatibility/yamada-background/editor各種）

## 6. WordPress-native capability

Block Editor（Site Editor / Post Editor）で`astrea-results-photoplane`（Cover）を選択すると、標準のCover Blockサイドバー・ツールバーがそのまま使える。カスタムPHP UIは一切なし。

| 機能 | 標準UI経路 | 確認 |
| --- | --- | --- |
| 背景画像を選択 | ブロックツールバー「Add media」→ メディアライブラリ／アップロード | 実機確認（§7） |
| 背景画像を置換 | ツールバー「Replace」 | 実機確認（画像設定時にツールバーへ出現） |
| 背景画像を削除 | 「Replace」→ 画像解除、または「…」メニュー | Cover標準機能 |
| Overlay Color | サイドバー Styles → Color → Overlay | 実機確認（無画像時スクリーンショット） |
| Overlay Opacity | サイドバー Styles → OVERLAY OPACITY スライダー（0〜100、step=10） | 実機確認 |
| Focal Point | サイドバー Settings → FOCAL POINT ピッカー＋LEFT/TOP % | 実機確認（画像設定時に出現、`docs/research/screenshots/025-E1/editor-cover-controls-1440.png`） |

内部コンテンツ（見出し「実績」／300・800・98%の数値／アイコン／ラベル／区切り線）は`astrea/results-list`の従来通りの描画のまま。画像への文字焼き込みは一切なし。

## 7. Editor persistence test（Order §7）

`http://127.0.0.1:8897`（`theme/`・`core/`をリポジトリから直接マウントした開発環境）で実施:

1. HOMEページ（post 32、`astrea/home-results-teaser`パターン適用済み・無画像wrapped形）をPost Editorで開く → **「Block contains unexpected or invalid content」警告なし**（Resultsブロックについて。Console検証メッセージ0件）。
2. Resultsの`astrea-results-photoplane`をList Viewから選択 → サイドバーにOverlay Color・OVERLAY OPACITYスライダーが表示される。
3. `integrate-025e1-results-background.php`で背景画像（`astrea-demo-yamada-results-background.jpg`）を`dimRatio:80`で設定。
4. 再度エディタで開く → **Resultsブロックの検証メッセージ0件**、canvasに背景画像が表示され、ネストされた`astrea/results-list`ブロックも正しく描画（fallback表示ではない）。サイドバーにFocal Pointピッカー・LEFT/TOP %入力・Replaceボタンが出現。
5. リロード後も上記状態が保持。

**重要な実装上の発見（修正済み）**: 当初`dimRatio:78`で試したところ、Gutenbergの`save()`はオーバーレイ濃度クラスを`has-background-dim-{10の倍数へ丸め}`で生成する（スライダーが`step=10`のため）。`has-background-dim-78`と手書きすると`save()`の`has-background-dim-80`と食い違い検証失敗。**`dimRatio`は必ず10の倍数**（0/10/…/100）にする必要がある。最終値は**80**（Order §9の「写真は控えめ、数字優先」に合致）。

Editorに残る「Block contains unexpected or invalid content」は、`astrea-final-cta`グループ等の**既存の別問題**であり、Resultsとは無関係（§13・§23参照）。

## 8. Background Image test

- 標準の「Add media」フローで画像設定 → 保存 → フロントエンドで背景画像が`object-fit:cover`で全幅表示。overflow 0、broken image 0（Results背景について。§10参照）。
- computed box（画像設定時、Desktop 1440）: cover 1440×537、`img.wp-block-cover__image-background` 1440×537（`position:absolute`, `object-fit:cover`）、inner-container 1440×537、results-list 1440×367 — 無画像時と同一の寸法。

## 9. Overlay test

- Overlay Color: `overlayColor:"contrast"`（濃紺）。標準UIのカラーピッカーで変更可能。
- Overlay Opacity: `dimRatio:80`（`has-background-dim-80`）。標準のOVERLAY OPACITYスライダー（step=10）で0〜100を選択可能。
- 無画像時は`dimRatio:100`（完全不透明の濃紺塗り）で1.0.2と同一表示。

## 10. Focal Point test

画像設定時、サイドバーSettings → FOCAL POINTにドラッグ可能なピッカーとLEFT/TOP %入力（既定 50/50）が出現。標準のCover Block焦点機能そのまま。スクリーンショット: `docs/research/screenshots/025-E1/editor-cover-controls-1440.png`

## 11. Replace / Remove test

画像設定時、ブロックツールバーに「Replace」ボタン出現（実機確認 `inspector has "Replace": true`）。Replace経由で別画像への差し替え・画像解除が可能（Cover標準機能）。

## 12. Backward compatibility（Order §5 — 最重要）

### 既存の未ラップ投稿（1.0.2で保存された`<!-- wp:astrea/results-list /-->`、`astrea-results-photoplane`祖先なし）

`.wp-block-astrea-results-list`自身のCSSルールは**一切変更していない**。新規CSSはすべて`.astrea-results-photoplane`配下スコープなので、ラッパーの無い既存投稿には**どのルールも一切マッチしない**。

**ピクセル一致検証**: 1.0.2 baseline（port 8895、frozen ZIP）のResults全体セクションと、1.0.3 Theme適用後の同じ未ラップ投稿の描画を、フルページスクリーンショットからcanvasで切り出してRGBA全画素比較:

| viewport | 比較領域 | diffピクセル | 最大差分 |
| --- | --- | --- | --- |
| Desktop 1440 | 1440×537（h2＋grid） | **0** | 0 |
| Mobile 390 | 390×785 | **0** | 0 |

→ 既存の未ラップResultsは、1.0.3更新後も**バイト単位で完全に同一**。文字色・padding・section高さ・divider・mobile layoutすべて不変。**既存投稿の再保存は不要**。

### 新規挿入（1.0.3の`astrea/home-results-teaser`、背景画像なし）

`core/cover`ラッパー付きだが、`dimRatio:100`＋`overlayColor:contrast`＋`min-height:0`＋`padding:0`の組み合わせで、描画結果は未ラップ版と**同じ寸法（1440×537 / 390×785）**、同じ濃紺、同じ全幅。スクリーンショット比較で視覚差なし（`docs/research/screenshots/025-E1/compatibility/`）。

禁止事項の確認: 背景透明化なし（無画像時）／文字色崩れなし／padding変化なし／section高さ変化なし（537px・785pxで一致）／divider消失なし／mobile layout崩れなし／既存投稿の手動再保存不要。

## 13. Existing Results regression

`astrea/results-list`のCore側レンダリング（`render_results_list_block()`）は無変更。CPT `astrea_result`のデータ・スキーマ・postmeta・taxonomyすべて無変更。DB migrationなし。既存の実績データはそのまま従来通り描画される。

## 14. New insertion behavior

- 背景画像未設定: 従来ASTREAらしい濃紺Results（§12で実証）。
- 背景画像設定: 背景写真 ＋ 濃紺オーバーレイ（`overlayColor:contrast`, `dimRatio` 既定80）＋ 既存Resultsコンテンツ（白い数字・goldラベル・アイコン・区切り線）。
- 既定`dimRatio:80`はASTREAのnavy/goldデザインを壊さない安全値。利用者は標準スライダーで10刻みで変更可能。

## 15. Yamada asset integration

Order §8の手順通り、製品機能の成立を確認した**後に**Yamada Demoへ正式Asset（`astrea-demo-yamada-results-background.png`）を設定。

Asset検証:

| 項目 | 値 |
| --- | --- |
| existence | あり |
| MIME | PNG image data（`file`コマンド） |
| dimensions | 1774 × 887（8-bit RGB, non-interlaced） |
| filesize | 1,619,562 バイト |
| SHA256 | `616ebb57fb334513d2aeedca755907d9a3c26b80ba4ffb7f02b209c8abedd492` |
| corruption | なし（PNGシグネチャ・IHDR・IENDチャンク整合） |

PNG原本は無変更・無削除。

## 16. Image optimization

`make-results-web-jpeg.php`（023-B/025-B2と同じ非破壊方式）:

| ファイル | 元 | Web版 | 処理 | 品質 |
| --- | --- | --- | --- | --- |
| `astrea-demo-yamada-results-background.jpg` | 1,619,562 B | 163,521 B（−89.9%） | クロップなし・そのままJPEG変換（Cover Blockの`object-fit:cover`が表示側フレーミング、Hero踏襲） | q85 |

過剰圧縮なし。

## 17. Responsive QA

Desktop 1440 / Mobile 390、Yamada Demo（背景画像設定済み、dimRatio 80）:

| 項目 | Desktop 1440 | Mobile 390 |
| --- | --- | --- |
| HTTP status | 200 | 200 |
| horizontal overflow | **0** | **0** |
| console errors | 0 | 0 |
| PHP warnings | 0 | 0 |
| JS errors | 0 | 0 |
| broken images | 1（後述※） | 1（後述※） |
| H1 uniqueness | 一意（1個） | 一意（1個） |
| missing media requests | 1（後述※） | 1（後述※） |

**※ broken image / missing request の内訳**: `http://127.0.0.1:**8895**/wp-content/uploads/2026/09/astrea-demo-yamada-hero-office.jpg`。これは**Heroの画像**で、Construction 025-B2でport 8895環境のURLが`post_content`にハードコード保存されたもの。本Phaseの開発環境はport 8897のため404になっているだけで、**Resultsの変更とは無関係**。Results背景画像（`wp_get_attachment_url()`で動的に8897 URL）は正常にロード（broken 0）。本番移植時のsearch-replaceで解消される既知のクロス環境アーティファクト。

Results固有チェック:

| 項目 | 結果 |
| --- | --- |
| 数字（300/800/98%）が読める | OK（白、高コントラスト、オーバーレイと独立） |
| アイコンが読める | OK（白 opacity .85） |
| dividerが自然 | OK（Desktop 縦線、Mobile 横線） |
| 背景が極端にcropされない | OK（`object-fit:cover`、1774×887の2:1画像が1440×537へ自然にクロップ） |
| mobileで情報量過多にならない | OK（3項目縦積み、写真は薄い背景） |
| overlayがDesktop/Mobile双方で十分 | OK（dimRatio 80） |
| text contrastが確保される | OK |

スクリーンショット: `docs/research/screenshots/025-E1/yamada-background/`

## 18. Clean rebuild

Order §11の再現性を確認。**完全にゼロから再クローンした開発DB**（`~/astrea-025e1-dev/wordpress-data`を`~/astrea-live-demo-build/wordpress-data`＝025-B2で独立検証済みの状態から新規コピー）に対し、1本のBlueprintで以下を順に実行して成功:

1. `apply-fresh.php`（＝`astrea/home-results-teaser`パターンの新形をHOMEへ適用、無画像wrapped形）
2. `integrate-025e1-results-background.php`（＝web JPEGを冪等アップロード＋Cover属性で背景画像・dimRatio 80を設定）

結果（クリーン環境、SQLite直接確認＋実機）:

- HOMEの`post_content`に`astrea-results-photoplane` Cover（`has-background-dim-80`, `wp-image-{id}`, `src=...results-background.jpg`）が正しく生成。
- Block Editorで開いて**Resultsブロックの検証エラー0件**、canvasに画像＋ネストされた`astrea/results-list`が正常描画、Focal Point/Replace等の標準コントロール出現。
- フロントエンド overflow 0、Results背景broken 0。

`integrate-025e1-results-background.php`は冪等（ファイル名でattachment既存チェック、既に背景設定済みなら何もしない、旧unwrapped形・新wrapped無画像形のどちらからでもwith-image形へ変換）。手動設定なしで再現される。

Hero / Case 01-03 / Representative の自動再現は Construction 023-B / 025-B2 で検証済み（本Phaseの開発DBはその成果物からのクローン）。025-E1でResults背景を加えた完全な連鎖が再現可能。

## 19. Regression（Results以外）

Yamada Demoフルページ（Desktop 1440）を目視:

| セクション | 結果 |
| --- | --- |
| Header | 変化なし |
| Hero | 構造・レイアウト変化なし（写真は上記※の8895 URL 404で無地navyフォールバック表示、本番／search-replace後は正常。Resultsの変更に起因しない） |
| Service | 変化なし（アイコン・区切り線・3カラム） |
| Case | 変化なし（3枚の写真） |
| **Results** | **背景写真＋オーバーレイが追加（本Phaseの意図した変更）** |
| About | 変化なし |
| Price | 変化なし（1×4） |
| CTA | 変化なし（navy＋goldボタン） |
| FAQ | 変化なし |
| Voices | 変化なし |
| Flow | 変化なし |
| Footer | 変化なし |

**Heroへの影響**: HeroもResultsと同じ`core/cover`系構造だが、本Phaseの変更はすべて`.astrea-results-photoplane`スコープのCSSと`home-results-teaser.php`パターン限定。`astrea-hero-photoplane`・`home-hero.php`・Hero関連CSSには一切触れていない。Heroの`core/cover`の描画は不変。

## 20. Theme validation / tests

| チェック | 結果 |
| --- | --- |
| PHP syntax（`php -l`） | `theme/patterns/home-results-teaser.php` PASS、`docs/.../integrate-025e1-results-background.php` PASS、`make-results-web-jpeg.php` PASS |
| `theme.json` JSON妥当性 | PASS（`json.load`成功） |
| PHPCS（`phpcs.xml` = `theme/`＋`core/`、`WordPress`標準＋`PHPCompatibilityWP` testVersion 8.3-） | **67/67ファイル PASS、エラー・警告0件**（ホスト環境PHPが8.1のため`vendor/composer/platform_check.php`を一時的に無効化して実行、実行後に元へ復元、`git status vendor/`空を確認） |
| Block validation（Block Editor実機） | Resultsブロックの検証メッセージ**0件**（無画像時・画像設定時とも） |
| HTML/render sanity | フロントエンド200、overflow 0、Results背景broken 0、console error 0 |
| responsive | Desktop 1440 / Mobile 390 実機確認（§17） |
| clean rebuild | §18で確認 |
| PHPUnit | 未実行（PHP 8.3必須のテストscaffold＋DBが必要。本Phaseの変更はパターンmarkupとCSSのみでPHPロジックの追加なし。Coreのレンダリング関数は無変更） |

**新規warningを残していない**（PHPCS 67/67クリーン）。

## 21. Theme/Core diff

```
$ git diff --stat -- theme/ core/
 theme/patterns/home-results-teaser.php | 50 ++++++++++++++++++--
 theme/readme.txt                       |  5 +++-
 theme/style.css                        |  2 +-
 theme/theme.json                       |  2 +-
 4 files changed, 54 insertions(+), 5 deletions(-)

$ git diff --check
(no output)   ← whitespaceエラーなし
```

- **Core diff = 0**（`git diff --stat -- core/`空）。
- `theme/theme.json`の変更は`styles.css`文字列への2ルール追加のみ（303→305、削除0・改変0。ルール単位のdiffで確認）。

## 22. Versioning status

- **ASTREA Theme 1.0.3候補**として準備完了。`style.css` Version・`readme.txt` Stable tag・Changelogを1.0.3へ整合。**正式Release / RC Tag / GitHub Release / ZIP生成 / Project-if配布更新はいずれも未実施**（Order §13）。
- **Core bump: NO**（`astrea-core` 1.0.1据え置き、コード無変更）。
- **Database migration: NO**（データスキーマ・postmeta・option無変更。既存投稿の変換不要）。
- Patch Release（1.0.2 → 1.0.3）として妥当: 新機能は加算的（既存挙動は完全後方互換・ピクセル一致）、破壊的変更なし、Core非依存。

## 23. Remaining risks

1. **`dimRatio`は10の倍数制約**: WordPressのオーバーレイ濃度スライダーが`step=10`のため。`integrate-025e1-results-background.php`は80固定、Changelog/READMEにも明記済み。利用者がスライダーで操作する限り自動的に守られる。手動でブロックコードを編集して中途半端な値を入れた場合のみ検証失敗し得る（WordPress標準の挙動であり本Themeに起因しない）。
2. **【既存問題・本Order対象外】Hero Cover のブロック検証警告**: Construction 025-B2でHero画像を組み込んだ際、手組みした`innerHTML`に`wp-image-{id}`クラスが欠落しており、Block Editorで`astrea-hero-photoplane`が「Block contains unexpected or invalid content」を出す。**未改修の официальなbaseline（port 8895、025-E1の変更を一切含まない）でも同じ警告が出ることを確認済み** → 本Phaseの変更に起因しない、025-B2から潜在していた問題。表示（フロントエンド）・データには影響しないが、将来のhotfix（例: 025-B2のHero統合スクリプトに`wp-image-{id}`クラスを追加、または該当ブロックをrecovery）で解消すべき。同種の既存警告が`core/button`（お問い合わせフォームへ）・`core/group`（astrea-final-cta / astrea-home-flow）・`core/list`（astrea-flow-steps）にも存在（Construction 014Aで「WordPress Core/Gutenberg側の問題、Severity MEDIUM、Release Blockingなし」と分類済みのものと同系統）。**いずれも025-E1では発生・悪化させていない。**
3. **開発環境のクロスポートURL**: 検証に使ったHero画像URLが8895ハードコードで8897環境では404。本番移植時のsearch-replaceで解消。Results背景は動的URLのため影響なし。

## 24. Final Verdict

**A. PASS — ASTREA THEME 1.0.3 CANDIDATE READY**

- Results背景画像・オーバーレイ色/濃度・焦点・置換/削除のすべてが、標準WordPress Cover Block UIのみで操作可能（カスタムPHP UIゼロ）。
- 内部コンテンツ（見出し・数値・アイコン・ラベル・区切り線）は従来通り編集・描画。画像への文字焼き込みなし。
- 後方互換: 既存の未ラップResultsは1.0.3更新後も**ピクセル単位で完全に同一**（Desktop/Mobile両方でdiff 0）。既存投稿の再保存不要。
- 新規挿入の無画像時も1.0.2と視覚的に同一。
- Core変更ゼロ、DB migrationなし、PHPCS 67/67クリーン、Resultsブロックの検証エラーゼロ。
- クリーン環境での再現性を確認。
- §23-2の既存Hero検証警告は025-E1とは独立（未改修baselineでも再現）。指摘のみ、別途hotfix推奨。

---

**STOP — commit / push / release / 1.0.3正式施工 / Construction 024 / VPS / demo.project-if.jp/astrea/ / Project-if CTA / WordPress.org提出のいずれにも進みません。** Owner のレビューをお待ちします。
