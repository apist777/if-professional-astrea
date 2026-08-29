# Construction Order 015D — Visual v2: Archive / Single / Internal Funnel — 施工報告

- **Status**: IMPLEMENTATION COMPLETE
- **Date**: 2026-08-29
- **担当**: クロエ (Chloe)
- **承認元Order**: Construction Order 015D（015C承認済みを前提）
- **Functional Baseline**: RC1 (1.0.0-rc1) — Version番号は変更していない

## 1. 施工範囲

対象は Service / Professional / CASE の Archive + Single、および VOICE / FAQ の Archive のみ（Order指定通り、VOICE/FAQにSingleは追加していない）。目的は「HOME → Archive → Single → Contact」を1本の導線として成立させること。

変更したファイル:

- `core/includes/archive-title.php`（新規） — `get_the_archive_title` フィルタで「Archives:」/「アーカイブ:」プレフィックスを対象5 CPTのみ除去。
- `core/includes/closing-cta-block.php`（新規） — `astrea/closing-cta` Dynamic Block。
- `core/includes/setup-pages.php`（既存に追記） — `get_contact_page_url()` を追加、後述の理由で実装を1回改修。
- `core/includes/breadcrumb.php`（既存を修正） — `astrea_case`/`astrea_voice` の階層解決を追加（後述、施工中に発見した既存の欠落）。
- `core/includes/service-list-block.php` / `core/includes/case-list-block.php`（既存に追記） — `is_singular()` によるコンテキスト検出で「関連コンテンツ」を同一Dynamic Blockに兼任させ、Accessible Name をscreen-reader-textで補強。
- `core/astrea-core.php` — 上記新規ファイルのrequire追加。
- `core/assets/js/editor-blocks.js` — `astrea/closing-cta` のEditor登録。
- Archive テンプレート5本（`theme/templates/archive-astrea_{service,professional,case,voice,faq}.html`）全面刷新。
- Single テンプレート3本（`theme/templates/single-astrea_{service,professional,case}.html`）新規構成（Breadcrumb→Header→Content→Related/CTA）。
- `theme/theme.json` — `.screen-reader-text`、Breadcrumbの番号無しリスト化、Archive/Single/Related/CTA用CSSを追加（Raw CSSのみ、Pattern変更なし）。

Architecture Freeze（CPT登録・Data Model・Setup全体構造・SEOタイトル生成・Contact送信処理・削除処理・FREE/PRO境界）は遵守。新規Semantic Data/Postmetaは追加していない。`get_contact_page_url()`とbreadcrumb.phpの拡張はいずれも**既存の公開読み取りAPI・既存トラッキングを読むだけの追加**であり、Setupが「何を生成するか」自体は変更していない。

## 2. Archive v2 — Before/After

| CPT | Before (RC1) | After (015D) | Visual Role |
|---|---|---|---|
| Service | 標準投稿リスト、「Archives: 取扱業務」 | 2カラム Card Grid、見出し「取扱業務」 | Card Grid |
| Professional | 標準投稿リスト | 3カラム Card Grid、円形写真枠（写真無しでも欠落なし） | Card Grid（人物寄り） |
| CASE | 標準投稿リスト、Serviceと同一見た目 | 2カラム、Primary色左Border＋白背景でServiceと明確に差別化 | Card Grid variant |
| VOICE | 標準投稿リスト | 3カラム、Surface背景・斜体引用・太字署名、カードでもBlog一覧でもない証言レイアウト | Testimonial |
| FAQ | 標準投稿リスト | 単カラム、左Borderリスト、Q/A表示 | Structured Q/A List |

いずれも `core/query` のネイティブ `layout:{"type":"grid","minimumColumnWidth":...}` を使用（WP 6.3+標準のCSS Grid auto-fill/fit機構）。VOICE/FAQはOrder §12の明示指示通りCard Grid化していない。

「Archives:」除去は `get_the_archive_title` フィルタのみで対応し、`document_title_parts`（`<title>`生成）・Search・Category・Tag・Date Archiveには一切影響しないことを確認済み（§7）。

## 3. Single v2 — 構成

全Single共通の骨格: `Breadcrumb → post-title → (featured-image/photo) → post-content → Related Content → Closing CTA`。「終わり方が空のまま」にならないことを明示的に確認した（Order §21 No Empty Endings）。

- **Service Single**: Related Contentは「自分以外の全Service」（`is_singular(POST_TYPE)` で自己除外）。
- **Professional Single**: 円形写真＋氏名＋qualification（横並びヘッダー）、career/education/affiliation/registration_infoを構造化フィールドとして表示。
- **CASE Single**: featured-image → 本文 → Related Contentは「そのCASEの既存 `related_services` postmetaが指すServiceのみ」（新データではなく、`core/includes/case.php` に元からあった `META_RELATED_SERVICES` を読むだけ）。

## 4. 重要な発見と対応（施工中に見つけた実バグ／実データギャップ）

Visual v2の見た目確認の過程で、以下は「新機能の見た目」ではなく**既存の欠落**だったため、Architecture Freezeの範囲内で修正した。

### 4.1 Breadcrumbに `astrea_case` / `astrea_voice` の階層が無かった

`get_breadcrumb_items()` は `astrea_service` / `astrea_professional` / `astrea_faq` には専用の3階層解決（Home / Archive / Current）を持っていたが、`astrea_case` と `astrea_voice` には分岐が無く、汎用の `is_singular()` 2階層（Home / Current のみ）にフォールバックしていた。結果、CASE SingleのBreadcrumbだけ「対応事例」への戻り導線が欠けていた。

既存の `append_post_type_trail()` ヘルパーに2 CPTを追加するだけの対応（新規関数無し、Data Model無関係）。修正後、BreadcrumbList JSON-LDも自動的に3階層へ改善された（副次的なSEO改善）。

### 4.2 Closing CTAがContactページを見つけられなかった

`get_contact_page_url()` の初期実装は `GENERATED_PAGES_OPTION`（Setup Wizard生成ページの追跡）のみを見ていたが、Owner Fixtureの「お問い合わせ」ページはこのオプションに記録されておらず（Setup Wizard経由で作られたものではない実データ）、電話番号ボタンしか出ないという実害が発生した。

`setup-checklist.php` の `is_contact_reachable()` が既に持っている「公開Pageを `has_block('astrea/contact-form', ...)` でスキャンする」検出ロジックをフォールバックとして再利用し、Setup追跡が無くても実在するContactページを正しく検出できるよう修正（Order §27「既存のSetup/Navigation/Page detectionを使う」により忠実な実装）。

### 4.3 Owner Fixtureのデータ未入力（Professional構造化フィールド／CASEのrelated_services）

Professional 3名は本文にのみプロフィールを書いており、`qualification`/`career`/`education`/`affiliation`/`registration_info` の各postmetaが未設定だった。CASE 3件も `related_services` が未設定だった。いずれも015C同様の「Fixtureデータ入力漏れ」であり、コード側の問題ではない。実在するフィールド・実在する関連付けを検証するため、Fixtureに実データを追記した（office名・電話番号など既存の世界観と整合する内容）。

### 4.4 Archive Empty Stateの `core/home-link` が裸の `<li>` を出力し、ビュレットが見えていた

`core/home-link` は本来 `core/navigation` の内側で使われる前提のブロックで、単体使用時は `<ul>` に包まれない裸の `<li class="wp-block-home-link">` を出す。ブラウザのUAスタイルにより黒丸が表示されていたため、`.astrea-archive-empty .wp-block-home-link{list-style:none;...}` をCSSのみで追加し解消した。

## 5. Related Content / Closing CTA の設計原則の遵守確認

- 新規Block属性は追加していない（静的Patternから動的な「現在の投稿」を渡す手段が無いため）。
- 新規「レコメンドエンジン」は作っていない。既存の投稿タイプ・既存のソート順（`enforce_deterministic_order`）・既存の `related_services` のみを使用。
- Closing CTAはURLをハードコードしていない。Contact URLも電話番号も存在しなければ完全に非表示（`''` を返す）。実際にCore OFF状態で検証し、Fatalなし・空文字列で安全に消えることを確認。

## 6. Accessibility — 「詳しく見る」Accessible Name問題の解消

015Cで残っていたKnown Issue（複数の「詳しく見る」リンクが同じ文言でAccessible Nameが曖昧）を、レンダリング経路ごとに2通りで解消した。

- **Archiveのネイティブ `core/query`**: WordPress core自身の `core/read-more` が `<span class="screen-reader-text">: {タイトル}</span>` を自動付与することを `wp-includes/blocks/read-more.php` のソースで確認、独自コード無しで解決。
- **HOMEのDynamic Block（service-list/case-list）**: 手動で同様のscreen-reader-text spanを追加。

Playwrightで実機のAccessible Name（textContent経由）を検証し、Archive・HOME・Service Single Related Contentのすべてで一意な名称（例:「詳しく見る（会社設立サポートについて）」）になっていることを確認した。

## 7. SEO / Search / 404 回帰確認

- Service Archive `<title>`: 「取扱業務 – ASTREA行政書士事務所」（プレフィックス除去はtitleに影響しない）。
- CASE Archive `<title>`: 「対応事例 – ASTREA行政書士事務所」。
- CASE SingleのBreadcrumbList JSON-LDが3階層（Home/対応事例/記事名）に改善（§4.1参照）。
- Canonical: Archiveページには元々出力されない（`wp_get_canonical_url()` はWP core仕様でsingular専用、015D起因ではない）。Singleは301リダイレクトで正しい正規URLへ到達することを確認。
- Robots meta: `max-image-preview:large`（WP標準デフォルト、noindexなし）。
- 404: 存在しないURLで404を確認。Search: `?s=` クエリで200を確認。いずれも回帰なし。

## 8. Core OFF

`astrea-core` を一時的に無効化し、以下を確認（直後に再有効化・Fatalログなしを確認済み）:

- Service Archive: 200（CPT非登録のため通常の投稿一覧相当にフォールバック、Fatalなし）。
- Service/CASE Single（`?p=`）: 404（Decision 024の通り、綺麗な404はフォールバックとして許容範囲）。
- HOME: 200、Fatalなし。

## 9. Responsive / Stress確認

- 320/375/768/1440pxで `document.documentElement.scrollWidth` による横スクロール自動検査を実施 — 対象8ページ全て、通常状態・長い日本語ストレス状態ともにOverflowなし。
- **Count Stress**（Service Archive、DOM操作で1/2/3/5件を再現、Fixtureデータは無変更）: いずれも不自然な引き伸ばしや空白は無く、ネイティブGridの `auto-fill`/`auto-fit` 相当の挙動が015Cで必要だったCSS修正なしにそのまま機能した。
- **Long Japanese Stress**（使い捨てテスト投稿を作成・検証後に完全削除。Fixtureの27件は無変更のまま最終確認済み）: Service/Professional/CASEの長いタイトルがカード内で折り返され、Breadcrumbも崩れずに折り返すことを確認。
- **Archive Empty State**（FAQ全5件を一時draft化→検証→即publishへ復元、最終状態を`wp post list`で確認済み）: Header/Breadcrumb/H1は表示されたまま、「現在、FAQの情報は準備中です。」＋「ホーム」リンクが表示。HOME Teaserの完全自己非表示（Decision 028）とは異なる挙動であることを実機で確認。

## 10. Style Variation確認

Trust（デフォルト）に加え、Natural・ModernでCASE Single・Service Archive・VOICE Archiveを検証（`WP_Theme_JSON_Resolver::get_style_variations()` 経由でwp_global_stylesへ適用→検証→Trustへ復元、という015B/015C確立済み手順）。Closing CTAのcontrast背景・ボタン配色・カード背景いずれもVariationごとに正しく切り替わり、崩れなし。最終状態はTrustへ復元済み。

## 11. Block Validation / Theme Check / 自動テスト

- Site Editorで7テンプレート全て（Archive×4 + Single×3）を開き、「invalid content」系の警告が出ないことをPlaywrightで確認（0件）。
- 公式 Theme Check プラグイン実行: REQUIRED 0 / WARNING 0（INFO 1件、text-domain統一の情報表示のみ・RC1から既知の許容事項）。実行後プラグインは削除。
- PHPCS: 62/62（0 errors, 0 warnings）。
- PHPUnit: 359 tests / 560 assertions、OK（既存ベースラインと一致、回帰なし）。

## 12. スコープ外・Known Issues

- Professional Single写真: Owner Fixtureは元々写真を持たないため、円形フレームの実写真での見え方は未検証（既知の制約、015Bから継続）。
- Voice/FAQのSingleページは今回追加していない（Order通りArchiveのみが対象）。
- `og:url` がCPT Archiveでもホームページ URLを返す点は015D以前からの挙動で、SEO Freeze範囲のため今回は変更していない。

## 13. Fixtureへの変更（製品コードではない）

Owner Visual Acceptance Fixtureは引き続き保持（削除していない）。今回のテストのために以下のみ追記（いずれも「実在するはずだったが未入力だった」データで、水増しではない）:

- Professional 3名の `qualification`/`career`/`education`/`affiliation`/`registration_info`。
- CASE 3件の `related_services`（各事例の内容に対応する実在Serviceを1件ずつ紐付け）。

Style Variationは最終的にTrustへ復元、Theme Checkプラグインは削除、Application Passwordは削除、ストレス用の使い捨て投稿3件・FAQ一時draft化5件はすべて元の状態（6 Service / 3 Professional / 3 CASE / 5 FAQ、全てpublish）に復元済み。
