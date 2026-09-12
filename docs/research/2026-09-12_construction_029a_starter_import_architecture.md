# Construction 029-A — ASTREA Starter Import Architecture / Product Specification

**Construction Type: DESIGN / ARCHITECTURE / PRODUCT SPECIFICATION ONLY**
**本Constructionでは実装を一切行っていない。** commit / push / deploy / Theme・Core変更 / DB変更 / VPS操作は0件。

- Start: 2026-09-12
- Modifier: Chloe
- Mode: read-only調査 + 設計文書作成のみ

---

## PHASE 0 — PRECHECK結果

```
$ git rev-parse HEAD && git rev-parse origin/main
46e5dba09e664691efccc10a8c8222c4272cc2ad（両者一致、分岐なし）
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群）は無変更のまま。Construction 027（Starter Site Foundation）・028（Pipeline Hardening）・028-F（Starter/Live Demo Boundary Separation）・028-G（Office Information Visual Refinement）の各報告書を参照し、現在確定している境界（**Starter Site: Live Demo Disclosure = 0** / **Live Demo = Starter Site + Live Demo専用layer**）を前提として設計した。

---

## PHASE 1 — CURRENT PIPELINE INVENTORY（完全棚卸し）

`docs/demo-assets/astrea-starter-site/scripts/` 配下、実ファイルを全て精読して分類した。

| ファイル | 目的 | 分類 | 理由 |
| --- | --- | --- | --- |
| `theme-core-activation-blueprint.json` | Theme切替・Core有効化・blogname設定（Playground Blueprint） | B | Playground固有の初期化手順。Import製品コードの直接の参照先ではないが、「有効化すべき手順」の仕様としては参考にする |
| `cleanup.php` | WordPress既定サンプル（Hello world!/Sample Page/Privacy Policy）をtitle一致ガード付きで削除 | **A候補（要再設計）** | ロジック自体はシンプルで再利用可能だが、「Freshサイトの既定コンテンツ削除」はImport本体というよりPreflight/前処理の一部として位置づけるべき |
| `build-content.php` | Office Profile・Professional・Service・Case・Result・Price・FAQ・Voice投入 + Core Setup関数（pages/navigation/home）呼び出し | **A（Import product logicの中核）** | Core自身のAPI（`update_option`/`wp_insert_post`/`\Astrea\Core\Setup\*`）のみを使用し、生SQL・Theme依存なし。ただし現状はONE-SHOT ONLY（Construction 028で安全な拒否ガードのみ追加、真の冪等マージは未実装＝029-Dのスコープ） |
| `polish.php` | About文言差し替え・営業時間設定 | **A** | 冪等（Construction 028で確認済み）。Core APIのみ使用 |
| `permalinks.php` | パーマリンク構造設定・リライトルールflush | **A** | 単純・副作用小・繰り返し安全 |
| `integrate-final-images.php` | Professional/Case#1のWeb画像をアップロードしFeatured Image設定 | **A** | ファイル名照合による冪等アップロード（Construction 028で追加）。ただし現状ローカルファイルパス（`/images/`）依存 — Media Strategy（PHASE 7）で解決が必要 |
| `integrate-025b2-hero-and-cases.php` | Hero Cover画像・Case#2/#3画像の組み込み | **A** | 同上。ファイル名照合で冪等 |
| `make-results-web-jpeg.php` | Results背景PNG→JPEG変換（ローカルファイル操作） | **B（開発ツール）** | GD画像変換はサーバー環境のimagick/GD可用性に依存する開発時最適化ステップ。**製品Importでは変換済みJPEGを直接同梱すべきで、このステップ自体はImport実行時に含めない**（事前ビルド済み成果物を使う） |
| `integrate-025e1-results-background.php` | Results Cover背景画像の設定（`parse_blocks`/`serialize_blocks`による構造的冪等性、Construction 028で恒久修正） | **A** | 既に高品質な冪等実装（自己修復ロジック含む）。Import product logicにそのまま転用可能 |
| `fix-internal-link-portability.php` | Navigation参照化・内部URL・home/siteurlの環境追従 | **A（必須）** | 環境非依存化の要。`wp eval-file`実行コンテキストでの`global $wpdb;`修正済み（Construction 028）。Importの最終ステップとして必須 |
| `fix-placeholders.php` | 旧プレースホルダー画像のラベル修正（履歴） | **D（legacy/archive）** | Construction 023時点の一時対応。現行パイプラインでは不使用、無変更のまま保持 |
| `add-live-demo-disclosure.php` | Live Demo専用disclosure追記（Construction 028-Fで分離） | **C（Live Demo only）** | **Starter Importのcall graphに絶対含めない。** 現在も`build-content.php`等から一切呼び出されていないことを確認済み（`grep`で実際の`include`/`require`が存在しないことを確認 — 唯一のヒットはコメント内の言及のみ） |
| `dev-snapshot.php` | 冪等性テスト用の機械可読snapshot取得 | **B（開発ツール）** | Construction 028のテスト資産。Import製品コードではないが、029-I（Integration/Repeatability/Safety Test）での再利用価値が高い |
| `REPEATABILITY-TEST.md` | RUN#1/#2/#3比較の手順書 | **B** | テスト専用ドキュメント |

**画像アセット** (`docs/demo-assets/astrea-starter-site/images/`):
- 原本PNG 6枚（合計約11MB） — ローカル開発時の中間生成物、**Import製品には不要**
- Web最適化JPEG 6枚（`images/web/`、合計約1MB） — **これが製品Importで実際に使用する画像**
- `placeholder-images/`（旧履歴、2枚、約12KB） — 不使用、無変更

**Core側の既存パターン発見（Ownership Marker設計の参考）:**

`core/includes/setup-pages.php`・`setup-navigation.php`・`setup-home.php`に、Core自身が既に確立している「生成物追跡」の前例パターンが存在する：

```
astrea_core_generated_pages       — page定義キー => Page ID のmap
astrea_core_generated_navigation  — 生成したNavigation post ID
astrea_core_generated_template_parts — 生成したtemplate part IDのmap
```

いずれも「既存IDが記録されていて、かつそのpostがまだ存在する場合は再生成しない（触らない）」という判定に使われている。さらに`core/includes/setup-checklist.php`のdocblockには重要な既存の製品哲学が明記されている：

> 「設定状況は点数化せず、完了/推奨/任意程度のチェックリストで案内する」「全項目完了を公開条件にしない」「二重管理の状態データソースを持たず、常に実データから導出する」

**この既存の設計原則（`astrea_core_generated_*`命名規則・実データからの状態導出・全項目完了を強制しない）は、Starter ImportのOwnership Marker設計（PHASE 5）・Site Classification設計（PHASE 3）に直接転用できる、既に検証済みの設計言語である。**

---

## PHASE 2 — FIRST RELEASE TARGET

**推奨: NEW / EMPTY ASTREA SITE ONLY を初版対象とする。**

「Empty」の定義（`wp_count_posts()`だけに頼らない、3層の判定）：

1. **WordPress初期コンテンツ層**: `post`（Hello world!）・`page`（Sample Page, Privacy Policy）が、タイトル一致かつ未編集（`post_modified === post_date`など）で残っているか、または既に削除済みか。
2. **ASTREA自身が生成する層**: `astrea_core_generated_pages`等の既存option、および`astrea_professional`/`astrea_service`/`astrea_case`/`astrea_result`/`astrea_price`/`astrea_faq`/`astrea_voice`の各CPT件数。
3. **ユーザーコンテンツ層**: 上記1・2のいずれにも属さない`post`/`page`/カスタム投稿。具体的には「Setup系optionに記録されたID以外のpage」「タイトル・スラッグがStarter定義済み値と一致しないpage」「Starter生成CPT以外の投稿（`post`タイプで独自記事を書いている等）」。

**Empty判定ロジック（概念設計）:**
```
is_empty_for_import():
  user_pages = 全publish/draft pageのうち、
    astrea_core_generated_pages に記録されたID以外
    かつ WordPress既定タイトル（Sample Page等）でもないもの
  user_posts = post_type='post' の投稿のうち、Hello world!以外
  user_cpt_content = astrea_professional/service/case/result/price/faq/voiceの件数
  meaningful_media = astrea生成予定のfilenameパターンと一致しないattachment

  IF user_pages == 0 AND user_posts == 0 AND user_cpt_content == 0
     AND meaningful_media == 0
  THEN EMPTY
  ELSE NOT EMPTY (STATE D or E, see PHASE 3)
```

単純な`wp_count_posts('page')の合計 > 0`のような判定は、WordPress標準ページ（未削除のSample Page等）を「ユーザーコンテンツ」と誤判定してしまうため採用しない。

---

## PHASE 3 — SITE CLASSIFICATION

| STATE | 定義 | Import button | Import allowed | 挙動 |
| --- | --- | --- | --- | --- |
| **A: Fresh WordPress** | ASTREA Theme未有効化 or Core未有効化 | 非表示 | - | Theme/Core有効化を促す通常のASTREA UIへ誘導 |
| **B: ASTREA installed, Starter not imported** | Theme/Core有効・PHASE 2のEmpty判定=TRUE・Starter未実行 | **表示（Primary CTA）** | ✅許可 | 通常のImportフロー（PHASE 15） |
| **C: Starter already imported** | Ownership marker（`astrea_starter_import_status = completed`）が存在 | 非表示 or 「完了済み」表示 | ❌BLOCK（full re-import） | 「事務所情報を編集」等の編集導線のみ表示（PHASE 10） |
| **D: Existing user site with meaningful content** | Empty判定=FALSE、かつStarter markerなし | 非表示 or 警告付き | ❌BLOCK（初版） | 「既存コンテンツが検出されました」警告のみ、Importボタン非活性 |
| **E: Partial / failed previous Starter import** | `astrea_starter_import_status = running` または `failed` | **表示（「再試行」ラベル）** | ✅許可（retry、PHASE 9参照） | 前回の生成物IDリストを参照し、続きから／安全に再試行 |
| **F: Unknown / inconsistent state** | marker存在するが値が想定外、またはmarkerが指すpost/CPTが実在しない | 非表示 | ❌BLOCK | 「サポートへご連絡ください」的な診断表示、自動修復は試みない |

**設計原則:** STATE判定はConstruction 028のBoundary Auditで確立した「実データから導出し、二重管理の状態を持たない」という原則を踏襲しつつ、marker（`astrea_starter_import_status`）は「進行中/失敗/完了」という**プロセス状態**の追跡にのみ使い、「何が完了しているか」自体は実データ（PHASE 2のEmpty判定と同じロジック）から都度導出する。

---

## PHASE 4 — PREFLIGHT

| 確認項目 | PASS条件 | 該当しない場合 |
| --- | --- | --- |
| WordPress version | Theme/Core同梱`readme`記載の最小要件以上 | BLOCK |
| PHP version | 8.1以上（既存の`php8.3`優先方針、Construction 024踏襲） | BLOCK |
| ASTREA Theme installed & active | `wp_get_theme()`確認 | BLOCK |
| ASTREA Core installed & active | `is_plugin_active()`相当確認 | BLOCK |
| 必須CPT登録済み | `post_type_exists('astrea_professional')`等7種 | BLOCK（Core有効化直後の`init`未完了は再試行で解消するケースを区別） |
| 必須Dynamic Block登録済み | `WP_Block_Type_Registry`確認 | BLOCK |
| 必須capability | 実行ユーザーが`manage_options`を持つ | BLOCK |
| メディア書き込み | `wp_upload_dir()`の`error`が空、書き込みテスト | BLOCK |
| DB書き込み | 簡易`update_option`/`delete_option`のラウンドトリップ | BLOCK |
| 現在のサイト状態 | PHASE 3のSTATE判定 | STATE次第（表参照） |
| 既存Starterマーカー | `astrea_starter_import_status`読み取り | STATE判定に使用 |
| Starterバージョン整合性 | 同梱Starter package versionとmarker記録versionの比較 | WARNING（PHASE 17/18参照、古いmarkerがあっても即BLOCKにはしない） |
| 前回未完了import marker | `status=running`が一定時間（例: 10分)以上更新されていない場合 | WARNING → STATE Eとして再試行を提案 |

**判定区分:** PASS（続行）／WARNING（ユーザーに表示し、明示的な続行承認を求める）／BLOCK（Import開始不可、理由をUIに明示）。

---

## PHASE 5 — OWNERSHIP / MARKERS

**Option（site単位、Core既存命名規則`astrea_core_*`と揃えるか、Starter専用の`astrea_starter_*`にするかは029-B設計時に最終決定するが、本書では後者を提案）:**

```
astrea_starter_import_version      — 実行したStarter Packageのバージョン文字列
astrea_starter_import_status       — 'running' | 'completed' | 'failed'
astrea_starter_import_started_at   — 開始時刻（UTC, ISO8601）
astrea_starter_import_completed_at — 完了時刻
astrea_starter_import_generated    — 生成物の追跡map（下記構造）
```

`astrea_starter_import_generated`の構造案（Core既存の`GENERATED_PAGES_OPTION`パターンを拡張）:

```php
array(
  'professional' => array( 12 ),           // post IDs
  'services'     => array( 13, 14, 15 ),
  'cases'        => array( 16, 17, 18 ),
  'results'      => array( 19, 20, 21 ),
  'prices'       => array( 22, 23, 24, 25 ),
  'faqs'         => array( 26, 27, 28, 29 ),
  'voices'       => array( 30, 31, 32 ),
  'attachments'  => array( 33, 34, 35, 36, 37, 38 ),
  'pages'        => array( 'about' => 39, 'price' => 40, 'contact' => 41, 'home' => 42 ),
  'navigation'   => 43,
)
```

**目的別の使い道:**
- **retry**: `status=running`のまま中断した場合、`generated`に既に記録されたIDは「作成済み」として飛ばし、未記録の項目だけ実行する（真の意味でのresume、PHASE 9参照）。
- **rollback（限定的）**: PHASE 12で定める「生成物IDだけを対象にした安全cleanup」の対象リストとして使う。
- **re-import判定**: `status=completed`かつ`generated`の各IDが実在すれば「既にStarter導入済み」と判定（PHASE 3 STATE C）。
- **future upgrade**: 将来のStarter package更新時、「どのpostがStarter起源か」を機械的に識別する唯一の正本データになる（title一致等の推測に頼らない）。

個別post側には、必要に応じて`_astrea_starter_generated = 1`のpostmetaを併用する案も検討したが、**option側の一元管理で十分**と判断（Core既存パターンがoption中心であることと整合、meta散在によるクエリ複雑化を避ける）。

---

## PHASE 6 — IMPORT CONTENT MODEL

現行`build-content.php`他から抽出した正確な対象一覧：

| 対象 | 件数 | 生成元 |
| --- | --- | --- |
| Pages | 4（事務所概要／料金／お問い合わせ／ホーム） | `\Astrea\Core\Setup\generate_pages()` / `generate_home_page()` |
| Professional (代表者) | 1 | `build-content.php` |
| Services | 3 | 同上 |
| Cases | 3 | 同上 |
| Results | 3 | 同上 |
| Prices | 4 | 同上 |
| FAQ | 4 | 同上 |
| Voices | 3 | 同上 |
| Navigation | 1（`wp_navigation`） | `\Astrea\Core\Setup\generate_navigation()` |
| Site options | `blogname`・`blog_public`・`astrea_core_office_profile`・`permalink_structure` | `build-content.php` / `polish.php` / `permalinks.php` |
| Front page設定 | `show_on_front` / `page_on_front` | `generate_home_page()`内部 |
| Media（Featured/Cover画像） | 6ファイル（Professional・Case#1〜3・Hero・Results背景） | `integrate-*.php`群 |
| Block content | Home Hero Cover・Results Cover・Office Information group | `integrate-025b2-*` / `integrate-025e1-*` / Core Setup |
| Internal link portability | Navigation参照化・home/siteurl・Contact CTA接続 | `fix-internal-link-portability.php` |

**含まないもの（確定・PHASE 19再確認）:** Live Demo Disclosure（`astrea-demo-disclosure`className）。

---

## PHASE 7 — MEDIA STRATEGY

比較：

| 方式 | 評価 |
| --- | --- |
| A. Theme bundle | Web最適化JPEG計約1MBのみを同梱すれば現実的。WordPress.org Theme sizeガイドライン上も問題ない規模 |
| B. Core plugin bundle | 同上のサイズ感。Themeより「機能」としての性質が強いImportロジックと画像を同じ場所に置ける利点 |
| C. 別Starterパッケージ（zip等） | バージョニングの独立性は高いが、配布・更新の仕組みを別途用意する必要があり初版としては過剰 |
| D. Remote download | **不採用。** project-if.jpサーバー停止時にImportが機能しなくなるリスクを負う。オフライン・低速回線環境での失敗リスクもある |

**推奨: 画像はImportロジックと同じ場所（PHASE 8の推奨箇所）に、Web最適化済みJPEG（合計約1MB、原本PNGは含めない）としてバンドルする。リモート依存は持たない。**

**ライセンス確認:** 2026-09-12のフォローアップConstruction（029-A License Audit）で、実ファイルの暗号署名メタデータ調査により出所を確定した。詳細は次章「Media License Audit」を参照。

---

## Media License Audit（Construction 029-A License Audit、2026-09-12実施）

### PHASE 1 — Starter Media Inventory

| filename (asset family) | source path | web path | dimensions (source) | dimensions (web) | source size | web size | used location |
| --- | --- | --- | --- | --- | --- | --- | --- |
| astrea-demo-starter-hero-office | `images/*.png` | `images/web/*.jpg` | 1672×941 | 1672×941 | 1,786,244 B | 187,769 B | HOME Hero Cover背景（`integrate-025b2-hero-and-cases.php`） |
| astrea-demo-starter-case-01-construction-permit | 同上 | 同上 | 1774×887 | 1774×887 | 1,812,903 B | 176,253 B | Case#1 Featured Image（`integrate-final-images.php`） |
| astrea-demo-starter-case-02-inheritance | 同上 | 同上 | 1536×1024 | 1536×768 | 2,030,685 B | 150,578 B | Case#2 Featured Image（`integrate-025b2-hero-and-cases.php`） |
| astrea-demo-starter-case-03-restaurant-incorporation | 同上 | 同上 | 1536×1024 | 1536×768 | 2,228,949 B | 200,027 B | Case#3 Featured Image（同上） |
| astrea-demo-starter-professional-portrait | 同上 | 同上 | 1616×973 | 1616×951 | 1,725,005 B | 148,046 B | Professional Featured Image（`integrate-final-images.php`） |
| astrea-demo-starter-results-background | 同上 | 同上 | 1774×887 | 1774×887 | 1,619,562 B | 163,521 B | Results Cover背景（`integrate-025e1-results-background.php`） |

全6アセット、source（PNG原本）とweb（JPEG最適化版）が同一asset familyとして1対1対応。web版のJPEGコメントは`CREATOR: gd-jpeg v1.0 (using IJG JPEG v62), quality = 85`（PHP GDライブラリによるローカル変換の痕跡、`make-results-web-jpeg.php`/`integrate-*.php`の処理と一致）。

**現在のリポジトリ状態:** 全てConstruction 023〜025で追加、Construction 027でファイル名を`yamada`→`starter`へ改名。全て`docs/demo-assets/astrea-starter-site/`配下にコミット済み。**現在の公開Live Demo（`demo.project-if.jp/astrea/`）でそのまま使用中。**

`placeholder-images/`配下の2枚（`case-1-image.png`・`yamada-professional-portrait.png`）は無関係の旧履歴資産（Construction 023時点、現行パイプラインでは不使用）のため、本監査の対象外。

### PHASE 2 — Origin Investigation（推測ではなく実証拠ベース）

**証拠1: リポジトリドキュメント**

`docs/demo-assets/astrea-starter-site/README.md` line 16に明記：
> 「`images/` — **正式デモ画像（Owner供給、最終版）**」

`docs/research/2026-09-10_astrea_live_demo_visual_assets_spec.md`（Construction 023-A、画像仕様確定レポート）に明記：
> 「最終画像はProject-if ASTREA LIVE DEMO専用の新規生成アセット。第三者stock photoは使用しない」
> 最終ステータス: 「VISUAL ASSET SPEC READY / AWAITING OWNER IMAGE GENERATION」（Owner自身による画像生成を待つ運用フローが明記されている）
> 生成プロンプト仕様には "Must Avoid: 実在/著名人物への類似、資格バッジ、読み取り可能な資格証明書・看板・社名・ロゴ、透かし、AI生成の乱雑テキスト" 等、第三者権利侵害・実在人物類似を明示的に回避する指示が含まれる。

`docs/research/design-reference/`配下に、Windows環境でのダウンロードを示す`Zone.Identifier`ファイル（`ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier`等）が存在し、状況証拠として整合する。

**証拠2: 画像ファイル自体に埋め込まれた暗号署名メタデータ（最も強い証拠）**

全6枚のPNG原本に、標準PNG仕様にはない`caBX`チャンク（C2PA Content Credentials / JUMBF形式）が埋め込まれていることを発見し、バイナリレベルで解析した。以下を確認した：

```
softwareAgent.name        : gpt-image
softwareAgent.version     : 2.0
digitalSourceType         : http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia
claim_generator_info.name : OpenAI Media Service API
actions                   : c2pa.created → c2pa.converted → c2pa.watermarked.unbound
                             （c2pa.opened/edited等の「既存画像を素材として開いた」痕跡は0件
                              — 第三者画像を下敷きにした編集ではなく、純粋なtext-to-image生成であることを示す）
created (action timestamp): 2026-09-09T00:00:00Z（Professional/Case#1） / 2026-09-10T00:00:00Z（他4点）
署名証明書チェーン         : OpenAI OpCo, LLC「OpenAI Media Service」← SSL.com「SSL.com C2PA ICA R1 2025」
                             ← SSL.com「SSL.com C2PA Root CA 2025」
タイムスタンプ局           : OpenAI OpCo, LLC「OpenAI TSA Leaf」（RFC3161準拠TSA）
```

これは「AI画像っぽい見た目」からの推測ではなく、**OpenAI自身が発行したデジタル署名（第三者認証局SSL.comが発行したOpenAI名義証明書によるC2PA署名）**による、暗号学的に検証可能な証拠である。全6枚で同一パターンが確認され、内部的な生成日は2026-09-09/10と、README/Construction 023-A報告書の記載時期（2026-09-08〜10）と完全に整合する。

**結論: ORIGIN CONFIRMED（推測ではなく実証拠）。** 全6画像は、Project-if（Owner）がOpenAIの画像生成API（gpt-image、いわゆるChatGPT Image機能）を用いて、Construction 023-A仕様書のプロンプト設計に基づき生成した、Project-if独自の一次生成物である。第三者stock photoやcopyrighted素材の編集物である痕跡は確認されなかった。

### PHASE 3 — AI Generated Assets

- Generation source/service: **OpenAI（gpt-image API / ChatGPT Image機能）**
- Generation date: 2026-09-09〜10（C2PA記録と一致）
- Project-ifによる生成か: **YES**（README「Owner供給」記載、023-A運用フロー記載、C2PA証拠の3点で裏付け）
- Third-party source imageを使った編集か: **NO**（C2PA actionsに編集系アクションなし、`c2pa.created`のみで生成された旨が記録されている）
- External copyrighted imageをinputとして使用した証拠: **なし**（同上）
- Redistribution restriction: 下記OpenAI利用規約調査を参照

**OpenAI利用規約の調査結果（WebSearchによる一次情報確認、2026-09-12時点）:**

- Output（画像を含む）の所有権はユーザーに帰属し、OpenAIは自身が持つ権利・タイトル・利益をユーザーへ譲渡する（"you own the Output... We hereby assign to you all our right, title, and interest, if any, in and to Output"、適用法で認められる範囲内）。
- 商用利用は全プラン（Free/Plus/API/Enterprise）で明示的に許可されている。生成物の販売・Webサイトへの掲載・クライアント向け利用が可能と説明されている。
- **重要な留保**: 所有権は非独占的（"non-exclusive"）であり、他のユーザーが同一・類似の出力を生成する可能性を排除できない。また、Enterprise階層以外（Free/Plus/API通常利用）は著作権侵害に関するOpenAI側の補償（indemnification）が明示的に対象外とされている。
- Theme/Plugin等のソフトウェア製品へのバンドル配布を名指しで制限する条項は、今回参照した範囲の解説記事には見当たらなかった（一般の商用利用許諾に包含されると解釈できる）。

**評価:** 上記の留保（非独占性・補償対象外）は、多くの一般的なフリー画像素材・OSSプロジェクトの配布条件（"AS IS"、無保証）と同水準のリスクであり、単独でREDやUNKNOWN判定の根拠にはならないと判断する。加えて、Construction 023-Aのプロンプト設計自体が実在人物・ロゴ・資格証明書・著名商標等への類似を明示的に回避しているため、第三者権利侵害の実務的リスクは低いと評価する。

### PHASE 4 — External / Stock Assets

該当なし。PHASE 2/3の調査により、全6画像はOpenAI API経由の一次生成物であり、外部stock photoサービス（Pixabay/Shutterstock等）由来の素材は使用されていないことを確認した。

### PHASE 5 — WordPress Distribution Requirement

WordPress.org Theme Review Handbook（Make WordPress Themes公式ハンドブック、WebSearchで一次情報確認）の一般原則：

- Theme zip内の全て（コード・データ・画像）はGPLまたはGPL互換ライセンスに準拠する必要がある（WordPress.orgホスティングのblocker要件）。
- バンドルする画像等のリソースには、ライセンス・著作権情報・出典元の明記が必要（**パブリックドメインの場合は著作権情報の記載は不要**）。全リソースの一覧を1ファイルにまとめる必要がある。
- 例外: 非常にシンプルな画像（設定用アイコン・プレースホルダー・矢印等）はTheme自体のライセンスでカバーされるとみなせるが、写真・アイコン・ロゴ・ベクター・イラスト等、独自性のある画像は帰属表示の対象。ASTREA Starterの6画像（実写風の写真的コンテンツ）はこの例外の対象外＝原則的に帰属表示の検討対象になる。

**AI生成物特有の考慮点:** 複数法域（特に米国著作権局の公表ガイダンス）で、人間の実質的な創作的関与のない完全AI生成物には著作権保護が及ばない（＝パブリックドメイン相当）という立場が取られている。これが適用される場合、WordPress.orgの「パブリックドメインなら著作権情報の記載は不要」という例外に該当し、GPL互換性の問題自体が生じない可能性が高い。ただし、これは法域・個別ケース依存の議論であり、本書は法的な確定判断を行うものではない。

**ASTREA naming HOLD問題（ASTREA vs Astra）は本監査の対象外。Theme resubmission・再提出作業は一切行っていない。**

### PHASE 6 — Asset Classification

| Asset | Origin確認 | Redistribution | 分類 |
| --- | --- | --- | --- |
| astrea-demo-starter-hero-office | OpenAI gpt-image、C2PA署名確認済み | OpenAI ToSで商用利用・所有権譲渡あり、非独占性の留保のみ | **GREEN** |
| astrea-demo-starter-case-01-construction-permit | 同上 | 同上 | **GREEN** |
| astrea-demo-starter-case-02-inheritance | 同上 | 同上 | **GREEN** |
| astrea-demo-starter-case-03-restaurant-incorporation | 同上 | 同上 | **GREEN** |
| astrea-demo-starter-professional-portrait | 同上 | 同上 | **GREEN** |
| astrea-demo-starter-results-background | 同上 | 同上 | **GREEN** |

**全6アセットGREEN。** YELLOW/RED/UNKNOWNに該当するアセットは0件。

### PHASE 7 — Attribution

- 帰属すべき第三者クリエイター・stock photo providerは存在しない（Project-if自身の一次生成物のため）。
- **Attribution: NOT REQUIRED**（第三者著作者への帰属義務という意味で）。証拠: PHASE 2/3で確認した通り、外部素材の編集物ではなく、Project-if自身がOpenAI APIを通じて生成した一次生成物であるため、帰属すべき第三者が存在しない。
- **推奨事項（義務ではなく透明性のための任意提案）:** Starter Package同梱時、`README`または`LICENSE`相当のファイルに「同梱画像はAI生成ツール（OpenAI画像生成API）を用いてProject-ifが作成したオリジナル素材です」という一文を記載することを推奨する。これはWordPress.orgの必須要件ではないが、将来のTheme Review・ユーザー説明の透明性向上に資する。

### PHASE 8 — Packaging Decision（最終）

PHASE 6の全GREEN判定を受け、029-A当初のMedia Strategy（PHASE 7既存章）を維持・確定する。

| 候補 | 最終判断 |
| --- | --- |
| A. Core bundled assets | **採用**（Web最適化JPEG、計約1MB、リモート依存なし） |
| B. Separate bundled Starter package | 不採用（初版としては過剰） |
| C. Remote Project-if download | **不採用**（可用性・オフライン動作・project-if非依存を優先する原則を維持） |

原本PNG（計約11MB）は同梱しない（ローカル開発時の中間生成物、製品には不要）。

---

## PHASE 8 — IMPORT EXECUTION MODEL（アーキテクチャの主要決定）

| 候補 | Theme responsibility適合 | Plugin territory適合 | portability | 将来PRO互換性 | 総合評価 |
| --- | --- | --- | --- | --- | --- |
| A. ASTREA Theme内 | ❌ Themeは表示層の責務。コンテンツ生成・CPT操作はTheme Reviewガイドラインが明確に禁止する「plugin territory」に抵触 | - | Theme切替で機能ごと消える | 低 | 不採用 |
| B. ASTREA Core内（新モジュール追加） | - | ✅Coreは既に全CPT・Setup関数を所有しており、自然な拡張先 | Core依存のみで完結、Theme非依存 | Core自身のバージョニングでImportも管理可能 | **有力候補** |
| C. 別ASTREA Starterプラグイン | - | ✅最も厳密にplugin territory原則に沿う | Core本体を軽量に保てる | 独立更新可能、将来的な機能分離がしやすい | **推奨** |

**推奨アーキテクチャ: C（別モジュールとしてのASTREA Starter機能）を、実装上はCore内の名前空間分離されたサブモジュール（例: `Astrea\Core\StarterImport\*`、将来的に独立プラグイン化も可能な設計）として第一版を実装する。**

**理由:**
1. WordPress.org Theme Reviewは「Themeにコンテンツ生成・カスタム投稿タイプ登録等の機能を持たせない」ことを明確に求めており、Theme内実装は将来のTheme審査・再提出（本Constructionの対象外）に悪影響を及ぼすリスクがある。
2. 完全な別プラグインとして最初から切り出すのは、配布・インストール手順が複雑化し（ユーザーがTheme・Core・Starter Importの3つを個別に導入する必要が生じる）、初版の導入障壁を上げる。
3. Core内に名前空間分離したサブモジュールとして実装しておけば、将来「Starter Importだけを独立プラグインへ切り出す」判断が必要になった場合も、コードの移動は比較的容易（Core本体のCPT定義・Setup関数への依存は`use function`で明示されており、切り離しやすい）。
4. Core自身のバージョン管理・有効化/無効化/アンインストールの仕組みにそのまま乗せられる（`data-deletion.php`の既存パターンを流用できる）。

---

## PHASE 9 — TRANSACTION / FAILURE MODEL

WordPressにサイト全体のtransaction rollback機構は存在しない前提で設計する。

```
[Preflight PASS]
   ↓
astrea_starter_import_status = 'running'
astrea_starter_import_started_at = now()
   ↓
Step 1: Office Profile + blogname          → 成功したらgeneratedに記録なし（optionは冪等上書き）
Step 2: Professional作成                    → ID記録 → generated.professional
Step 3: Services作成                        → ID記録 → generated.services
Step 4: Cases作成                           → ID記録 → generated.cases
Step 5: Results作成                         → ID記録 → generated.results
Step 6: Prices作成                          → ID記録 → generated.prices
Step 7: FAQ作成                             → ID記録 → generated.faqs
Step 8: Voices作成                          → ID記録 → generated.voices
Step 9: Pages/Navigation/Home（Core Setup） → ID記録 → generated.pages / navigation
Step 10: 画像組み込み（Featured/Cover）      → ID記録 → generated.attachments
Step 11: internal link portability          → 記録不要（既存postの書き換えのみ）
   ↓ 各Step完了ごとに astrea_starter_import_generated を都度保存（1 step = 1 update_option）
   ↓
全Step成功 → astrea_starter_import_status = 'completed', completed_at = now()
途中失敗   → astrea_starter_import_status = 'failed'（例外catch）
             失敗Step番号・エラーメッセージも記録（新option or generated配下に格納）
```

**設計方針:**
- 「失敗したので全部消します」は実装しない（Order指示通り、ユーザー既存データ削除の危険を避ける）。
- 各Stepの完了直後に`generated`を永続化するため、途中で失敗しても「どこまで作成されたか」が正確に分かる。
- Step単位はPHASE 6の対象一覧に対応する粒度（11 Step）とし、1 Stepが1トランザクション相当の「作ったら即記録」単位になるようにする。
- **operation log**: 各Stepの開始/終了時刻・結果を配列で`astrea_starter_import_log`（別option、サイズ上限を設けてトリム）に追記する案を提示するが、必須かどうかは029-Bで判断（デバッグ用途が主）。

**Retry戦略（PHASE 3 STATE E）:** `generated`に記録済みのStepはスキップし、未完了のStepから再開する。ただしStep 2〜9（コンテンツ作成系）は現状ONE-SHOT設計（重複作成の危険）のため、**029-Dで各Stepに「IDが記録されていればスキップ」という真の意味でのidempotent-resumeロジックを実装することが前提**。本書ではその設計方針のみを確定する。

**Cleanup戦略（PHASE 12参照）:** `status=failed`の場合、ユーザーに「未完了のStarter Importが検出されました」と提示し、「再試行（続きから）」「安全にクリーンアップして最初からやり直す（`generated`記録IDのみ削除）」の2択を提供する。自動クリーンアップはしない。

---

## PHASE 10 — RE-IMPORT POLICY

**初版採用: Completed Starter Site（STATE C）へのfull re-importは原則BLOCK。**

理由（Order記載の通り採用）: ユーザーが事務所名・電話番号・ページ内容・サービス内容・画像等を編集した後にfull re-importを許すと、編集内容が意図せず失われる可能性が高い。WordPressには「どの変更がユーザー編集か」を機械的に判定する信頼できる手段がない（`post_modified`はSaveボタンを一度でも押せば更新されるが、内容が変わったかは判定できない）。

**Reset / Reinstall Starterは本書の対象外（将来の別機能として設計する）。** 実装する場合も、「ASTREAが生成したobjectだけ」（PHASE 5の`generated`マーカーに記録されたIDのみ）を対象にする必要があり、title一致等の推測ベースの削除は禁止する（Order明記の通り）。

---

## PHASE 11 — EXISTING SITE POLICY

**初版採用: Meaningful existing content detected（STATE D）→ BLOCK。**

理由: Existing Site Merge（ユーザーが既に運用中のサイトへStarterコンテンツを部分的に導入する機能）は、コンテンツの衝突判定・マージ方針・ユーザーへの説明UIなど、単独のImporterの範囲を大きく超えるMigration Tool相当の複雑さを持つ。

**分離判断: FIRST RELEASEはFresh/Safe ASTREA Siteのみに限定し、Existing Site Mergeは明確に別の将来Construction（例: 029-J以降、または全く別のConstruction番号）として切り出す。** Importerを本書の範囲でMigration Toolへ肥大化させない。

---

## PHASE 12 — ROLLBACK / RESTORE POINT

比較：

| 方式 | 評価 |
| --- | --- |
| A. Generated IDsだけ追跡して安全cleanup | 実装コストが低く、PHASE 5のマーカー設計とそのまま整合する。ユーザーの既存データには一切触れない安全性が高い | 
| B. WP export相当（xml生成） | Import前のsnapshotを取るには使えるが、「復元（import back）」の実装コストが高く、初版には過剰 |
| C. DBバックアップ | 共有レンタルサーバー環境では権限・容量的に非現実的なケースが多い |
| D. no destructive rollback + retry | 最もシンプルだが、ユーザーが「失敗したサイトを綺麗にしたい」というニーズに応えられない |
| E. Hybrid（A + D） | AのIDベースcleanupを提供しつつ、それ以上の破壊的操作はしない |

**推奨: E（A + D のHybrid）。** PHASE 9のfailure modelで記録した`generated`マーカーのIDのみを対象にした安全cleanup機能を提供し、それ以上のバックアップ・DB全体復元機能はFREE Starter Import単独では実装しない（Order記載の通り、PRO機能の簡易backup/restore構想には依存させない設計としつつ、FREE単独でも最低限の失敗回復は保証する）。

---

## PHASE 13 — SECURITY

| 項目 | 方針 |
| --- | --- |
| Required capability | `manage_options`（Core既存のSetup関数群と同じ基準） |
| Nonce / CSRF | `wp_nonce_field()` + `check_admin_referer()`（`setup-pages.php`の`handle_generate_pages()`と同一パターンを踏襲） |
| Authorization | 上記capability + nonceの二重チェック |
| Direct request防止 | `admin_post_`アクションフック経由のみ受け付け、直接URLアクセス・REST直叩きは想定しない（初版はPHASE 14 Aの同期リクエストモデルのため） |
| Path validation | 画像パスはバンドル内の固定パスのみ参照、ユーザー入力由来のパスは一切使わない |
| Upload validation | `wp_upload_bits()`/`wp_insert_attachment()`等WordPress標準APIのみ使用（現行integrate-*.phpの実装をそのまま踏襲） |
| Remote request policy | 一切なし（PHASE 7でリモート依存を排除する設計のため） |
| Timeout | PHASE 14の実行モデル次第（同期式なら`max_execution_time`の考慮が必須） |
| Memory | 同上、画像処理を伴うStepでのメモリ使用量に注意 |
| 重複実行ロック | `astrea_starter_import_status = 'running'`のtransient/option自体をロックとして機能させる（実行中は再度のImportボタン押下を弾く） |
| 同時実行防止 | 上記ロックに加え、DB row-level lockではなくoption値の楽観的チェックで十分（単一管理者操作が前提のため） |

**「伊吹先生 × 20」問題への対応:** Importボタン押下直後にサーバー側で`status`を`running`にlockし、UIボタンも無効化（PHASE 15）。二重送信・連打によるProfessional重複作成を防ぐ。

---

## PHASE 14 — PERFORMANCE

| 候補 | 評価 |
| --- | --- |
| A. Single synchronous request | 実装最も単純。ただし共有サーバーの`max_execution_time`（デフォルト30秒程度）超過リスクがある。現行スクリプトの実測（Construction 028実測: 9-step合計で数秒程度、画像処理含む）では規模的には収まる可能性が高いが、低速な共有ホスティングでは不安が残る |
| B. AJAX / REST step runner | Step単位（PHASE 9の11 Step）でリクエストを分割し、都度進捗をUIに反映できる。タイムアウトリスクを大幅に低減。実装コストは中程度 |
| C. WP Cron / background process | 確実性が最も低い（Cronの発火保証がホスティング環境依存、遅延が発生しやすい）。進捗表示もユーザー体験として悪い |

**推奨: B（AJAX/REST step runner）を初版の実行モデルとする。** 理由：
- 低メモリ・短`max_execution_time`・低速filesystemの共有サーバー環境（project-if.jp想定ユーザー層を含む）でも安全に完走できる。
- PHASE 15の「進行: 1. ページを作成 → 2. サービスを作成 → …」というAdmin UXのステップ表示と自然に一致する。
- 各Stepの成功/失敗がPHASE 9のfailure modelとそのまま連動する。

---

## PHASE 15 — ADMIN UX（画面仕様、コードなし）

```
ASTREA管理画面
  ↓
「Starter Site」メニュー項目
  ↓
[Preflight結果表示]
  PASS の場合:
    説明文:
    「完成済みの行政書士事務所サイトを作成します。
      セットアップ後、事務所名・電話番号・サービス等を
      ご自身の内容へ変更できます。」
    [ Starter Siteをセットアップ ] ボタン（Primary CTA）

  WARNING の場合:
    警告内容を具体的に表示（例：「Starter Package v1.1がありますが、
    現在v1.0が導入済みです」等）+ 続行チェックボックス

  BLOCK の場合:
    理由を具体的に表示（例：「サイトに既存のコンテンツが検出されました。
    Starter Setupは新規サイトでのみご利用いただけます。」）
    ボタン非活性 or 非表示

  ↓（PASS→ボタン押下後）

[進行状況画面]（AJAX/RESTでStep単位に進行、PHASE 14）
  1. 事務所情報を設定
  2. 専門家プロフィールを作成
  3. サービスを作成
  4. 対応事例を作成
  5. 実績を作成
  6. 料金プランを作成
  7. よくある質問を作成
  8. お客様の声を作成
  9. ページ・ナビゲーション・ホームページを生成
  10. 画像を登録
  11. 内部リンクを現在の環境に合わせる
  ↓
[完了画面]
  「Starter Siteのセットアップが完了しました。」
  CTA:
    [ サイトを表示 ]
    [ 事務所情報を編集 ]
    [ サービスを編集 ]
    [ 専門家プロフィールを編集 ]

  失敗時:
  「セットアップの一部が完了しませんでした（Step 7で失敗）。」
    [ 再試行 ]
    [ 詳細を見る ]
```

**STATE C（完了済み）でのUI:** Importボタンの代わりに「Starter Siteは既に導入済みです」+ 編集導線（上記CTAと同じ）のみを表示。

---

## PHASE 16 — DESTRUCTIVE ACTION UX

**初版では提供しない。**

理由：post title一致だけで削除する設計は明確に禁止されており（Order記載）、安全な削除には「ASTREAが生成したobjectだけ」を正確に識別する仕組み（PHASE 5のマーカー）が前提になる。マーカー運用が実戦投入され安定するまでは、Resetのような破壊的機能は提供リスクが高い。

将来提供する場合は、PHASE 5の`generated`マーカーに記録されたIDのみを削除対象にし、かつ「Developer-only」（`WP_DEBUG`有効時のみ表示等）からの限定公開を検討する。

---

## PHASE 17 — VERSIONING

**3つの独立バージョンを持つ設計を提案:**

```
Starter Package: 1.0.0（新規、Import機能自体とバンドルコンテンツのバージョン）
Theme:           1.0.3（既存、無変更）
Core:            1.0.1（既存、無変更）
```

**目的:** Theme/Coreの機能追加・バグ修正のリリースサイクルと、Starter Siteのコンテンツ内容・画像・文言の改訂サイクルを分離する。Starter Packageのバージョンは、PHASE 5の`astrea_starter_import_version`optionに記録され、「このサイトはどのStarter Package版で構築されたか」を将来にわたって識別可能にする。

**Core内サブモジュールとして実装する場合（PHASE 8の推奨）でも、Starter Package自体のバージョン番号はCore本体のバージョンとは独立してカウントする**（Core 1.0.1のマイナー更新がStarter Packageの内容変更を意味しない、その逆も然り）。

---

## PHASE 18 — UPDATE POLICY

**原則（正式化）:**

> **Starter update available ≠ user site automatic update**
>
> Starterは初期生成時点のテンプレートであり、生成後のユーザーコンテンツ（編集済みサイト）はStarter Packageの将来の更新から完全に独立する。Starter Package更新時、既にImport済みのサイトへ自動的に変更が反映されることは一切ない。

**運用イメージ:** 将来Starter Package 1.1.0がリリースされても、`astrea_starter_import_version = '1.0.0'`と記録された既存サイトは何も変わらない。ユーザーが望む場合のみ、（PHASE 16で将来設計する）明示的なReset/Reinstall機能を通じて新バージョンを適用できる（自動ではない、常にユーザー起点）。

---

## PHASE 19 — LIVE DEMO BOUNDARY（VERIFIED）

実際に`grep`で確認した結果：

```
$ grep -rln "add-live-demo-disclosure" docs/demo-assets/astrea-starter-site/scripts/*.php
build-content.php   ← コメント内の言及のみ（実際のinclude/requireは0件）

$ grep -n "add-live-demo-disclosure" docs/demo-assets/astrea-starter-site/scripts/build-content.php
331:// `add-live-demo-disclosure.php`, run as an explicit additional step only
```

**Starter Importのcall graphに`add-live-demo-disclosure.php`は一切含まれていないことを確認した（Live Demo Disclosure: 0 を製品仕様として確定）。** Live Demoは引き続き「Starter Import（＝Core内Starterモジュール実行）+ Live Demo専用layer（`add-live-demo-disclosure.php`、Starter Importの外側で独立実行）」という別工程を維持する。

**判定: VERIFIED**

---

## PHASE 20 — WORDPRESS.ORG / PRODUCT BOUNDARY

PHASE 8の検討と合わせ、以下を確認した（read-onlyの一般的なWordPress.org公式ガイドライン理解に基づく、本書内での検討）：

- **Theme Review Guidelinesの一般原則:** Themeはpresentation layerに徹し、コンテンツ生成・カスタム投稿タイプ登録・独自データモデルの導入はplugin territoryとして扱われる。Import機能をTheme側に置くことは、将来のTheme審査再提出時（本Construction対象外）のリスク要因になる。
- **Plugin（Core）側の考慮:** Core自体は既にCPT・カスタムブロック・Setup機能を持つ正当なplugin機能であり、Starter Import機能を追加することは既存の責務範囲の自然な拡張として扱える。有効化/無効化/アンインストール時のデータ所有権（PHASE 5のマーカー・生成物）は、既存の`data-deletion.php`パターン（Uninstall時にoption群を削除、生成されたコンテンツ自体はユーザーデータとして残す）を踏襲する。

**ASTREA naming HOLD問題は本Constructionの対象外。Theme upload/resubmissionは行っていない（Order指示通り、一切のTheme提出作業は実施していない）。**

---

## PHASE 21 — ARCHITECTURE DECISION RECORD

| # | 決定事項 | DECISION | RATIONALE | ALTERNATIVES REJECTED | RISKS |
| --- | --- | --- | --- | --- | --- |
| 1 | Importer location | Core内サブモジュール（将来独立プラグイン化可能な名前空間分離） | Theme Review原則との整合、既存Setup関数群との自然な統合 | Theme内実装（plugin territory抵触）／即時別プラグイン化（導入障壁増） | Core肥大化のリスク、将来の分離コスト |
| 2 | First release supported site state | Fresh/Empty ASTREA Siteのみ（STATE B） | Existing Site Mergeの複雑性を排除し安全性を最優先 | 全State即時対応（安全性・実装コスト面で不採用） | 既存サイトユーザーは利用不可、将来別機能が必要 |
| 3 | Existing site policy | Meaningful content検出時はBLOCK | ユーザーデータ保護最優先 | 警告のみで許可（誤操作リスク） | 一部ユーザーの期待に応えられない可能性 |
| 4 | Preflight policy | PASS/WARNING/BLOCKの3段階、BLOCK時は開始不可 | 明確な判定区分でUI設計と連動しやすい | 単純なbool判定（情報量不足） | Preflight項目の網羅漏れリスク |
| 5 | Ownership marker strategy | Option中心（`astrea_starter_import_*`）+ 生成物ID map | Core既存パターン（`astrea_core_generated_*`）との整合、meta散在回避 | postmeta中心（クエリ複雑化） | option肥大化（IDリストが増えた場合のサイズ） |
| 6 | Execution model | AJAX/RESTによるStep runner | 共有サーバーのtimeout/memory制約への耐性 | 単一同期リクエスト（timeoutリスク）／WP Cron（確実性低） | 実装コストは同期式より高い |
| 7 | Media packaging | Web最適化JPEG（約1MB）をCore同梱、リモート依存なし。**全6アセットMedia License Audit GREEN確定済み（2026-09-12）** | 可用性・オフライン動作・project-if非依存を優先。License AuditでOpenAI API生成・Project-if一次生成物・商用配布可能と確証済み | リモートダウンロード（不採用） | 同梱によるプラグインサイズ増（許容範囲）。OpenAI ToSの非独占性・補償対象外は一般的なフリー素材配布と同水準のリスクとして許容 |
| 8 | Failure strategy | Step単位でgenerated IDを都度永続化、失敗時は自動削除しない | ユーザーデータ保護、正確な進捗追跡 | 全ロールバック（危険）／進捗追跡なし（不透明） | operation logの肥大化管理が必要 |
| 9 | Retry strategy | generated記録済みStepをスキップして再開 | 安全な再試行、重複作成防止 | 常に最初からやり直す（重複リスク） | 各Stepの真の冪等化（029-D）が前提 |
| 10 | Rollback strategy | Hybrid（generated IDベースの安全cleanup + それ以上は非破壊） | FREE単独での最低限の回復力を確保 | DBバックアップ依存（環境依存性高）／ロールバックなし（回復不能） | 部分cleanup後の状態一貫性検証が必要 |
| 11 | Re-import policy | Completed siteへのfull re-importは原則BLOCK | ユーザー編集内容の保護 | 常に上書き許可（データ損失リスク） | Reset機能の需要に応えられない（将来課題） |
| 12 | Starter versioning | Starter Package独立バージョン（Theme/Coreと分離） | リリースサイクルの独立性確保 | Core versionに統合（柔軟性低下） | バージョン管理対象が増える |
| 13 | Update policy | Starter更新≠既存サイト自動更新 | ユーザー編集内容の保護（原則11と一貫） | 自動追従（データ破壊リスク） | ユーザーが更新に気づきにくい可能性（将来通知UI課題） |
| 14 | Live Demo boundary | Starter Import call graphに`add-live-demo-disclosure.php`を含めない | Construction 028-Fで確立した境界の維持 | Live Demo文言を条件分岐で混入（境界崩壊リスク） | なし（既に実データで検証済み） |
| 15 | Admin UX | Preflight→Step進行→完了→編集導線CTAの一直線フロー | Core既存Setup UIパターンとの一貫性 | ウィザード形式の複数画面遷移（複雑化） | Step数が増えた場合のUI冗長化 |

---

## PHASE 22 — IMPLEMENTATION BREAKDOWN（029-B以降の提案）

依存関係を分析した結果、以下の分割を提案する（例示ではなく実際の依存分析に基づく提案）：

```
029-B: Starter Import Domain / State Model
  - PHASE 3のSTATE判定ロジック実装（read-onlyの判定関数のみ、UIなし）
  - PHASE 2のEmpty判定ロジック実装
  - 依存: なし（最も基礎的な層）

029-C: Preflight + Ownership Marker
  - PHASE 4のPreflightチェック実装
  - PHASE 5のマーカーoption CRUD実装
  - 依存: 029-B（STATE判定を内部で使用）

029-D: Content Import Engine（コア移植 + 真の冪等化）
  - build-content.php等のロジックをCore内モジュールへ移植
  - 各Stepにgenerated ID記録 + skip-if-already-created の真の冪等性を実装
    （Construction 028のKnown Issue「build-content.phpの真の冪等マージ未実装」をここで解決）
  - 依存: 029-C（マーカーへの書き込み）

029-E: Media Import
  - integrate-*.php群の画像組み込みロジックをCore内へ移植・同梱画像参照化
  - 依存: 029-D（Step順序上、コンテンツ作成後に画像を紐付けるため）

029-F: Navigation / Site Options / Internal Link Portability
  - fix-internal-link-portability.php相当の移植
  - 依存: 029-D, 029-E（生成済みpage/attachmentへの参照更新のため）

029-G: Failure / Retry / Cleanup UI
  - PHASE 9のfailure model・PHASE 12のcleanup機能実装
  - 依存: 029-D〜F全て（各Stepの失敗を検知する基盤が必要）

029-H: Admin UI（AJAX/REST Step Runner + 画面）
  - PHASE 14の実行モデル・PHASE 15の画面実装
  - 依存: 029-B〜G全て（UIは全ての下位層を統合する最終層）

029-I: Integration / Repeatability / Safety Test
  - Construction 028のdev-snapshot.php / REPEATABILITY-TEST.mdの手法を
    Starter Import製品版に対して再適用
  - RUN#1/#2/#3、Existing-site Safety Audit、Failure Safety Auditを実施
  - 依存: 029-H（完成した製品に対するテストのため最終工程）
```

**029-B→C→D→(E,F並行可)→G→H→I の順で進めるのが最も安全な分割と判断する。** 各ConstructionはOwner Reviewゲートを経てから次へ進む前提とし、本書（029-A）ではこれ以上先に進まない。

---

## PHASE 23 — ACCEPTANCE CRITERIA（将来のStarter Import完成条件）

- Fresh ASTREA site → Import → Starter Site complete
- Import result: 現在の公式Starter Siteとsemantically equivalent（Construction 028のdev-snapshot.php比較手法を継承）
- Starter Disclosure: 0
- Second import（同一サイトへの再実行）: duplicate growth 0
- Completed site: user edits protected（re-import BLOCK、PHASE 10）
- Existing meaningful site: no silent overwrite（PHASE 11のBLOCK）
- Partial failure: detectable（PHASE 9のmarker/generated記録）
- Retry: safe（PHASE 9のresume戦略）
- Concurrent import: blocked（PHASE 13のロック）
- Mobile/Desktop: PASS
- Theme standard editability: PASS（Construction 028-Gで確立した基準を継承）
- No remote dependency: preferred（PHASE 7で確定）
- No Project-if production dependency: preferred（同上）

---

## Owner Review用サマリー

### A. Executive Summary

現行のStarter Siteパイプライン（`docs/demo-assets/astrea-starter-site/`）は、9-stepの再現可能スクリプト群として既に確立・冪等性検証済み（Construction 028）。029-Aでは、この資産を「製品としてのASTREA Starter Import機能」へ発展させるための正式設計を行った。**実装は一切行っていない。**

### B. Recommended Architecture

- **設置場所:** ASTREA Core内の名前空間分離サブモジュール（将来独立プラグイン化可能）
- **実行モデル:** AJAX/RESTによるStep単位実行（11 Step）
- **対象範囲（初版）:** Fresh/EmptyなASTREAサイトのみ

### C. First Release Scope

Fresh ASTREA Site → Starter Site生成、のみ。Existing Site Merge・Reset/Reinstallは将来の別機能として明確に切り離した。

### D. Safety Model

- Preflight（PASS/WARNING/BLOCK 3段階）
- Ownership marker（`astrea_starter_import_*` option群、Core既存パターンを踏襲）
- Step単位のfailure記録・generated-IDベースの安全なretry/cleanup
- Re-import・Existing site上書きは原則BLOCK（ユーザーデータ保護最優先）

### E. User Flow

Preflight確認 → 「Starter Siteをセットアップ」ボタン → 11 Stepの進行表示 → 完了 → 「事務所情報を編集」等のCTA。

### F. Rejected Alternatives

Theme内実装（plugin territory抵触）、リモート画像配信（可用性リスク）、即座の全State対応（安全性リスク）、DB全体バックアップ方式（環境依存性）を、いずれも理由付きで不採用とした（PHASE 21参照）。

### G. 029-B以降のConstruction Plan

029-B（State Model）→ 029-C（Preflight/Marker）→ 029-D（Content Engine、真の冪等化）→ 029-E/F（Media/Navigation、並行可）→ 029-G（Failure/Retry UI）→ 029-H（Admin UI）→ 029-I（Integration Test）の順で提案。

### Owner判断が必要な項目（明確化、2026-09-12時点）

1. ~~**画像素材のライセンス（PHASE 7）**~~ → **本License Auditで解決済み（全6アセットGREEN）。**
2. **Importer設置場所（PHASE 8）**: Core内サブモジュール（推奨）→ **Owner ADOPT済み**。
3. **初版スコープ（PHASE 2/11）**: Fresh Siteのみへの限定 → **Owner ADOPT済み**。
4. **Re-import/Reset方針（PHASE 10/16）**: Completed後のfull re-importはBLOCK、Reset/Reinstallは初版に含めない → **Owner ADOPT済み（BLOCK / NOT IN FIRST RELEASE）**。
5. **029-B以降の分割・着手順（PHASE 22）**: 提案した8分割・順序 → **Owner APPROVED**。

---

## Owner Architecture Decisions（2026-09-12受領）

Owner Architecture Reviewの結果、以下が正式に決定された：

| 項目 | Owner Decision |
| --- | --- |
| Importer Location | **ADOPT** → ASTREA Core内の名前空間分離サブモジュール |
| First Release Scope | **ADOPT** → Fresh / Empty ASTREA Site ONLY |
| Existing Site Merge | **NOT IN FIRST RELEASE** |
| Re-import | **BLOCK** after completed import |
| Reset / Reinstall | **NOT IN FIRST RELEASE** |
| Ownership Tracking | **REQUIRED** from first release |
| Execution Model | **ADOPT** → AJAX / REST step execution |
| Construction Plan（029-B〜029-I） | **ADOPT** |

唯一のOPEN ITEMとして指定された「Starter Site画像素材の製品再配布ライセンス」は、本Construction（029-A License Audit）で全6アセットGREEN判定として解決した（上記「Media License Audit」章参照）。

---

## PHASE 10 — ARCHITECTURE FINAL DECISIONS（FIX）

Media License Auditが全GREENで確定したため、029-A Architecture Decisionを正式にFIXする。

1. **Importer Location**: ASTREA Core submodule
2. **First Release**: Fresh / Empty ASTREA Site ONLY
3. **Existing Site**: BLOCK
4. **Completed Re-import**: BLOCK
5. **Reset / Reinstall**: Not included in first release
6. **Ownership**: generated object tracking required
7. **Preflight**: PASS / WARNING / BLOCK
8. **Execution**: AJAX / REST step runner
9. **Failure**: step state + generated IDs + retry
10. **Rollback**: no blind destructive rollback（generated-IDベースの安全cleanupのみ、PHASE 12）
11. **Media**: license-audited bundled assets（**全6アセットGREEN確定、本Auditで完了**）
12. **Starter Version**: independent Starter package/version marker
13. **Starter Updates**: must not overwrite user-edited imported content
14. **Live Demo Boundary**: Starter Import contains NO Demo Disclosure（VERIFIED）
15. **Admin UX**: preflight → setup → progress → completion CTA

---

## PHASE 11 — 029-B+ PLAN FINALIZATION

PHASE 22で提案した分割・依存関係をそのまま正式Planとして確定する（Owner APPROVED）：

```
Construction 029-B — Starter Import State Model / Domain Foundation
Construction 029-C — Preflight / Ownership Marker
Construction 029-D — Content Import Engine / True Idempotency
Construction 029-E — Media Packaging / Import
Construction 029-F — Navigation / Site Options
Construction 029-G — Failure / Retry / Concurrency Safety
Construction 029-H — Admin UI / Progress UX
Construction 029-I — Integration / Repeatability / Safety Test
```

依存関係の再検証: 029-B（基礎）→029-C（029-Bに依存）→029-D（029-Cに依存）→029-E・029-F（029-Dに依存、並行可）→029-G（029-D〜Fに依存）→029-H（029-B〜Gに依存）→029-I（029-Hに依存、最終工程）。矛盾・逆依存は確認されなかった。**本Constructionでは029-Bに一切着手しない。**

---

## Construction 029-A — CLOSED

- Owner Architecture Review: PASS
- Importer Location: ADOPTED
- First Release Scope: Fresh / Empty ONLY
- Existing Site Policy: BLOCK
- Re-import Policy: BLOCK
- Reset / Reinstall: NOT IN FIRST RELEASE
- Preflight Model: ADOPTED
- Ownership Model: ADOPTED
- Execution Model: AJAX / REST STEP RUNNER
- Failure / Retry Model: ADOPTED
- Starter Versioning: ADOPTED
- Live Demo Boundary: VERIFIED
- Media Inventory: COMPLETE
- Media Origin Audit: COMPLETE
- Media License: PASS
- Redistribution: PASS
- Attribution: NOT REQUIRED（第三者著作者への帰属義務なし。AI生成である旨の任意開示のみ推奨、PHASE 7参照）
- Media Packaging: ADOPTED（Core同梱Web最適化JPEG、リモート依存なし）
- Theme Modification: NONE
- Core Modification: NONE
- Starter Modification: NONE
- Media Modification: NONE
- Commit: `406632d77c6aa52c246366f910e3cc7e7702eeda`
- Push: PASS
- Deploy: NOT REQUIRED / NOT RUN
- Next: Construction 029-B

STOP
