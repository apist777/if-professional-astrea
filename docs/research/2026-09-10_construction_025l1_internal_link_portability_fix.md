# Construction 025-L1 — Local/Internal Link Portability Fix

- Start: 2026-09-10 15:43:07 JST（実測、Preflight時）
- End: 2026-09-10 16:19:18 JST（実測）
- Duration: 0:36:11
- Modifier: Chloe
- Mode: 再現性パッケージ側のみ。Theme/Core無変更（025-E1の4点差分は保持、025-E1Hのデモスクリプト修正も保持）。commit/push未実施（Order §22）。

**Final Verdict: A. PASS — INTERNAL LINKS FULLY PORTABLE**

---

## 1. Preflight

```
$ git diff --stat -- theme/ core/
 theme/patterns/home-results-teaser.php | 50 ++++...
 theme/readme.txt                       |  5 +++-
 theme/style.css                        |  2 +-
 theme/theme.json                       |  2 +-
 4 files changed, 54 insertions(+), 5 deletions(-)   ← Construction 025-E1 の4点のみ
```

- 025-E1（Theme 1.0.3候補、4ファイル）・025-E1H（`integrate-025b2-hero-and-cases.php` の修正）とも保持。巻き戻していない。
- `HISTORY.csv` は HEAD とバイト一致（`git status --short HISTORY.csv` 空）。触れていない。
- Owner による過去screenshots削除は復元していない。
- 新規コミットなし（HEAD = `f8f7a99`）。

## 2. Symptoms

Yamada Demo を「構築した port」以外で serve（別 port で再構築、または既存 DB を別 port へclone）すると、Header / Footer のナビゲーションリンクが旧環境の絶対URL（`http://127.0.0.1:8895/…`）を指したまま残り、別ローカル環境ではリンク切れ（別 port へ飛ぶ / 404）になる。原因はナビゲーションメニューが凍結された絶対URLを保持していること、および clone された DB の `home`/`siteurl` オプションが旧値のまま残ること。

実測（`astrea-025e1-dev` を `:8897` で serve、DB は `:8895` で構築されたものの clone）:

- `wp_navigation` #31 の6リンクがすべて `http://127.0.0.1:8895/…`（`kind:"custom"`）
- `wp_options` `home` / `siteurl` = `http://127.0.0.1:8895`
- レンダリング後の Header / Footer ナビ `<a href>` がすべて `:8895` を指す（現在環境は `:8897`）

## 3. Hardcoded URL inventory

| # | 場所 | 内容 | 分類 | 対応 |
| --- | --- | --- | --- | --- |
| 1 | `theme/` ソース全体 | 内部URLのハードコードなし（`$schema` 参照のみ、外部・ツール用） | A | 対応不要 |
| 2 | `core/` ソース全体 | 内部URLはすべて WordPress-native API（`home_url()` / `site_url()` / `admin_url()` / `get_permalink()` / `get_post_type_archive_link()` / `add_query_arg()` / `wp_safe_redirect()`）経由。外部は Google `googletagmanager.com`（GA4 gtag.js）のみ | B / J | 対応不要 |
| 3 | `docs/demo-assets/yamada-live-demo/scripts/*.php` | host/port/IP のリテラルなし。URLを生成する箇所は Core Setup 関数呼び出しに委譲 | D | 下記 §8 で最終ステップを追加 |
| 4 | **`wp_navigation` 投稿（"ASTREA 基本メニュー"、#31 / clean rebuild では #30）** | **`wp:navigation-link` × 6、`kind:"custom"`、凍結された絶対URL** `http://<構築時host:port>/…`（ページ3件＝`/事務所概要/`・`/料金/`・`/お問い合わせ/`、CPTアーカイブ3件＝`/services/`・`/professionals/`・`/faq/`） | E | 参照ベースへ書き換え＋`url`再生成 |
| 5 | `wp_options` `home` / `siteurl` | clone された DB では旧環境値（`http://127.0.0.1:8895`）。Playground `--site-url` は実行時に WP_HOME/WP_SITEURL 定数でマスクするが DB 行は旧値のまま | H | DB行を `home_url()` へ揃える |
| 6 | `wp_posts` `post_content`（ページ本文・リビジョン） | 現行ページ（HOME #32）のメディアURLは 025-E1H が現在 port へ再解決済み。古いリビジョンには旧 port のメディアURLが残存（レンダリングされないため実害なし） | F | シリアライズ安全な再帰置換で掃除 |
| 7 | `wp_postmeta` | 内部絶対URLはごく僅か（`_wp_attached_file` は相対パス。添付URLは実行時に `home_url()` 基準で組み立てられる） | G / I | 同上の掃除対象に含める |
| 8 | HOME の CTA ボタン | 「お電話でのご相談」＝`href="#"` フォールバック＋`astrea-core/office-profile` `phone_tel` バインディング → 実行時に `tel:` へ解決（host非依存・portable）。「お問い合わせはこちら」「お問い合わせフォームへ」＝`href="#"` プレースホルダ（バインディングなし・実リンクなし） | — | **旧URLではないため 025-L1 の対象外**。`#` は §23 で観察事項として記録 |
| 9 | `theme/parts/footer.html` の「Theme by Project-if」 | `https://project-if.jp/` | J（外部） | 対応不要 |

## 4. Root cause

`Astrea\Core\Setup\generate_navigation()`（`core/includes/setup-navigation.php:189`）が、生成する全 `wp:navigation-link` を

```php
'kind'  => 'custom',
'url'   => $link['url'],   // navigation_links() が Setup 実行時に get_permalink() / get_post_type_archive_link() で取得した絶対URL
```

として保存する。WordPress は `kind:"custom"` のURLを再解決しない。さらに **実機検証の結果、WordPress 7.1 の `core/navigation-link` は `kind:"post-type"` の場合でも保存済み `url` 属性をそのまま出力し、`id` から `get_permalink()` を再導出しない**（`render_block()` に `home_url()` が `:8899` の環境で `url` が `:8897` のリンクを食わせると `href="…:8897…"` を出力することを確認）。

したがって、生成されたメニューは「最初に Setup を走らせた環境」に凍結される。別 port で再構築 / clone すると旧リンクが残る。clone DB では `home`/`siteurl` 行も旧値のまま（`--site-url` フラグは実行時マスクのみ）で、これが症状を増幅する。

## 5. URL storage locations（分類）

- **E — WP Navigation（`wp_navigation` post_content）**: 主因。Header・Footer 両方がこの1エンティティを描画する。
- **H — options（`home` / `siteurl`）**: clone DB で旧値。
- **F — post_content / リビジョン**: 旧 port のメディアURL残存（実害なし、掃除対象）。
- **G / I — postmeta / 添付**: 添付URLは実行時組み立てのため元々 portable。稀な絶対URLのみ掃除対象。
- A（Theme）/ B（Core）/ C（Pattern markup）: **ソースにハードコード内部URLなし**。
- J（外部）: Google gtag.js、`project-if.jp` — 一切触れない。

## 6. Navigation audit（実機）

- `theme/parts/header.html:17` … `<!-- wp:navigation {"overlayMenu":"mobile"} /-->`（bare、`ref` 無し）
- `theme/parts/footer.html` … `<!-- wp:navigation {"layout":{"type":"flex","orientation":"vertical"},"textColor":"base"} /-->`（bare、`ref` 無し）
- 両方が同一の生成済み `wp_navigation` エンティティ（"ASTREA 基本メニュー"）を描画。
- もう1つの `wp_navigation` #4（"Navigation"）は `<!-- wp:page-list /-->` のみ（未使用のフォールバック。`core/page-list` は元から描画時に `get_permalink()` で動的解決）。
- `wp_navigation` エンティティの旧URLは WXR 標準インポーターでは再マップされない（`kind:"custom"` の `url` は importer の再マップ対象外）→ subpath 移植時にも問題になる。

## 7. CTA / Button audit（実機、Block comment attrs と HTML anchor 両方）

| ボタン | attrs | href（保存markup） | 実行時の解決 | portable? |
| --- | --- | --- | --- | --- |
| お電話でのご相談（Hero ×1 / 下部CTA ×1） | `bindings.url → astrea-core/office-profile phone_tel` | `#` | `tel:03-…`（Block Bindings） | ✅ host非依存 |
| お問い合わせはこちら（Hero） | `className:"is-style-outline"` のみ | `#` | （リンクなし） | ✅（`#` は port非依存）／機能なし |
| お問い合わせフォームへ（下部CTA） | `backgroundColor:"accent"` のみ | `#` | （リンクなし） | ✅／機能なし |

- 旧絶対URLを持つボタンは **ゼロ**。
- block attrs 側と HTML href 側の不整合（片方だけ現在URL・片方だけ旧URL）は **なし**。
- 修正で block validation warning を増やさないことを §19 で確認。

## 8. Fix architecture

新規スクリプト **`docs/demo-assets/yamada-live-demo/scripts/fix-internal-link-portability.php`** を、**再現パイプラインの最終ステップ**として実行する。Order §7 の優先順位に従う（1. WordPress-native URL生成 → 2. `home_url()` 等からの動的生成 → 3. 安全な置換）。

1. **Navigation の参照ベース化**（優先順位1）:
   生成済み `wp_navigation` を検出（`astrea_core_generated_navigation` option、なければ ASTREA ラベルを持つ最新の `wp_navigation`）し、Core の `navigation_links()` と同じ順序・同じ条件で、各リンクを
   - ページ: `{"label":…,"type":"page","id":<現在のID>,"url":<get_permalink(id)>,"kind":"post-type"}`
   - CPTアーカイブ: `{"label":…,"type":"<cpt>","url":<get_post_type_archive_link(cpt)>,"kind":"post-type-archive"}`
   として再構築する。`url` は現在環境の `get_permalink()` / `get_post_type_archive_link()` から実行時に再生成（= 現在の `home_url()` 基準、subpath込み）。`kind` を参照型にすることで Block Editor が自身でナビリンクを保存する形と一致し、`id`/`type` を保持するので将来の WordPress が再解決するようになれば自動追従する。

2. **シリアライズ安全な内部URL置換**（優先順位2〜3）:
   `post_content` / `post_excerpt` / `postmeta` / 該当 `options` に残る絶対内部URL（loopback `127.0.0.1`・`localhost`、私設LAN `10.x` / `172.16-31.x` / `192.168.x`、port有無問わず）で、現在の `home_url()` と host:port が異なるものを、WP-CLI `search-replace` と同じ方式の**再帰的 unserialize→置換→serialize** で現在の `home_url()` に書き換える。外部URL（`project-if.jp` 等の実ドメイン）は検出パターンに含めないため一切触れない。
   置換元は「固定文字列」ではなく「loopback/私設LANという内部ホストのクラス」を検出する正規表現。置換先は常に実行時の `home_url()`。→ Order §7 の「`8895 → 8898` の固定文字列置換禁止」を満たす。

3. **`home` / `siteurl` の自己修復**:
   DB 行を直接（`$wpdb`）読み、現在の `home_url()` と異なれば書き換える（`update_option()` は WP_HOME 定数でマスクされた値と比較して no-op になるため直接更新）。WXR インポートではこれらの行は取り込まれないので実害は「生DBコピー」ケースのみだが、コストゼロなので実施。

4. `flush_rewrite_rules(false)`。

**ハードコードされた host / port / IP は一切なし**（`grep` 確認済み。すべて `home_url()` / `get_option()` / `wp_parse_url()` から実行時に導出）。**冪等**（既に portable な環境では「no change」）。

## 9. Changed files

| ファイル | 変更 |
| --- | --- |
| `docs/demo-assets/yamada-live-demo/scripts/fix-internal-link-portability.php` | **新規**。上記 §8。 |
| `docs/demo-assets/yamada-live-demo/README.md` | scripts 一覧に追記。再現手順に「最終ステップとして `fix-internal-link-portability.php` を実行」「subpath 構成でも参照ベースなので誤って root へ飛ばない」を追記。手順を 6→7 ステップに。 |
| `docs/demo-assets/yamada-live-demo/yamada-demo-export.wxr` | portability-fix 済み環境から再エクスポート（ナビが参照ベース `kind:"post-type"`/`"post-type-archive"`、旧 port 残存ゼロ）。 |

**Theme / Core: 変更ゼロ**（`git diff --stat -- theme/ core/` = 025-E1 の4点のみ、Core 空）。

## 10. Dynamic base URL handling

- `$current_home = untrailingslashit( home_url() )` を唯一の基準にする。Playground `--site-url` 環境では WP_HOME 定数の値、実インストールでは DB option。
- 旧ホストの**検出**は広いクラス正規表現（`127.0.0.1|localhost|10.\d+…|172.(1[6-9]|2\d|3[01]).\d+…|192.168.\d+…`、port `(:\d+)?`）。特定の from-string は持たない。
- 置換先は常に `$current_home`。→ 8899 でも localhost でも LAN IP でも subpath でも成立。

## 11. Subpath compatibility

- ナビリンクは `kind` 参照型 → 描画時に `get_permalink()` / `get_post_type_archive_link()` が `home_url()`（`/astrea/` を含む）基準でURLを組み立てる。`demo.project-if.jp/astrea/` で serve した場合、`取扱業務` は `https://demo.project-if.jp/astrea/services/` に解決され、**誤ってサイト root（`…/services/`）へ飛ばない**。
- root-relative URL（`/contact/` 等）は一切導入していない（Order §8 の懸念どおり subpath を壊すため回避）。
- §8-2 の search-replace も「絶対内部 base URL → 別の絶対 URL」であり、root-relative を生成しない。

## 12. Existing demo repair

- fix スクリプトを既存 serve 環境（`astrea-025e1-dev`）に対して単独実行 → ナビ再構築・options 修復・残存URL掃除。レンダリング後HTMLで旧URLゼロを確認。
- クリーンリビルドでも同一の fix がパイプライン最終ステップとして再現される（手作業DB編集なし）。§13 / §14 で実証。

## 13. Environment A rebuild

- 完全新規 `download-and-install`、`--site-url=http://127.0.0.1:8901`、10ステップのフルパイプライン（activate → cleanup → build-content → polish → permalinks → integrate-final-images → integrate-025b2-hero-and-cases → make-results-web-jpeg → integrate-025e1-results-background → **fix-internal-link-portability**）。
- 結果:
  - `home` / `siteurl` = `http://127.0.0.1:8901`
  - `wp_navigation` #30 の6リンクすべて `http://127.0.0.1:8901/…`、`kind:"post-type"` / `"post-type-archive"`
  - クロール（Home から辿れる内部リンク全件）: すべて **200**
  - `:8902` = 0、`:8895` = 0、`localhost` = 0、cross-port = 0

## 14. Environment B rebuild

- 完全新規 `download-and-install`、`--site-url=http://127.0.0.1:8902`、同一パイプライン。
- 結果（A と対称）:
  - `home` / `siteurl` = `http://127.0.0.1:8902`
  - `wp_navigation` #30 の6リンクすべて `http://127.0.0.1:8902/…`
  - クロール: すべて **200**
  - `:8901` = 0、`:8895` = 0、`localhost` = 0、cross-port = 0

## 15. Cross-port leakage test

| 環境 | 自 port のリンク | 他環境 port | 旧 port（:8895 等） | localhost | 判定 |
| --- | --- | --- | --- | --- | --- |
| A（:8901） | すべて | 0 | 0 | 0 | ✅ |
| B（:8902） | すべて | 0 | 0 | 0 | ✅ |
| Review（:8900、WSL IP host） | すべて | 0 | 0 | 0 | ✅ |

DB スキャン（post_content / postmeta / options）とレンダリング後HTMLスキャンの両方で確認。

**Host variation（Order §15）**: Review 環境は `--site-url=http://172.29.10.134:8900`（127.0.0.1 ではない LAN IP）で新規構築し、`home`/`siteurl`・ナビ・全内部リンクが `172.29.10.134:8900` に揃うことを確認。fix スクリプトに `127.0.0.1` リテラルが無いこと（`grep` 済み）と併せ、**127.0.0.1 非依存を証明**。

## 16. Automated internal link crawl

A / B / Review の3環境で、Home から辿れる内部リンクをクロール:

| 項目 | A | B | Review |
| --- | --- | --- | --- |
| 全リンクのレスポンス | 200 | 200 | 200 |
| 404 | 0 | 0 | 0 |
| connection refused | 0 | 0 | 0 |
| cross-port link | 0 | 0 | 0 |
| old-host link | 0 | 0 | 0 |

対象: Header ナビ ×6（Footer も同一エンティティ）、CPT アーカイブ（services / professionals / faq）、個別 CPT 投稿、feed / comments feed、wp-json、oembed。主要固定ページ（`/`, `/事務所概要/`, `/services/`, `/professionals/`, `/faq/`, `/料金/`, `/お問い合わせ/`）すべて 200。

## 17. Manual click test

Playwright 実機（A / B / Review）:

- Header ナビ先頭項目（事務所概要）をクリック → `http://<現在host:port>/事務所概要/` に着地。
- 「取扱業務」をクリック → `http://<現在host:port>/services/` に着地。
- クリック後の `hostname` / `port` / base path がすべて現在環境のまま維持されることを確認（A → `127.0.0.1:8901`、B → `127.0.0.1:8902`、Review → `172.29.10.134:8900`）。

## 18. Media regression

A / B / Review:

| 項目 | 結果 |
| --- | --- |
| broken images | 0 |
| missing media | 0 |
| cross-port media URL | 0 |

Hero 画像・Case #1〜#3・Representative・Results 背景 すべて現在 port から読み込み。025-E1H で修正したクロスポート media URL、025-E1 の Results 背景も、フルパイプライン通過後に維持されていることを確認。

## 19. Block validation

再構築環境の HOME を Block Editor で開いた際:

| ブロック | 検証メッセージ |
| --- | --- |
| Hero（`astrea-hero-photoplane`） | **0** |
| Results（`astrea-results-photoplane`） | **0** |
| Navigation / navigation-link | **0** |
| 新規のリンク関連 warning | **0** |
| 合計（failed / Expected） | 8 |

合計8は既存問題（`core/button` ×2 ＝「お問い合わせフォームへ」等のクラス不一致、`core/group` ×2 ＝ astrea-final-cta / astrea-home-flow、`core/list` ×1 ＝ astrea-flow-steps。Construction 014A で「WordPress Core/Gutenberg 側、Severity MEDIUM、Release Blocking なし」と分類済みの系統）。**025-L1 開始前（025-E1H 後）の 8 から増減なし。**リンクポータビリティ修正による新規 warning はゼロ。

## 20. Theme diff

```
$ git diff --stat -- theme/
 theme/patterns/home-results-teaser.php | 50 ++++++++++++++++++++++++++++++++--
 theme/readme.txt                       |  5 +++-
 theme/style.css                        |  2 +-
 theme/theme.json                       |  2 +-
```

**= Construction 025-E1 の4点のみ。025-L1 での Theme 変更ゼロ。** `git diff --check` clean。

## 21. Core diff

```
$ git diff --stat -- core/
(no output)
```

**Core diff = 0。**（Order §20 の理想を達成: リンク問題は Demo reproducibility 側で完結し、Theme/Core に一切触れていない。Theme/Core 修正が必要という判断には至らなかったため STOP は不要。）

## 22. Owner local review URL

新規クリーンリビルドを WSL IP で起動済み（portability-fix 適用済み、フルパイプライン）:

- **サイト表示: http://172.29.10.134:8900/**
- 主要確認ページ:
  - Home: http://172.29.10.134:8900/
  - 事務所概要: http://172.29.10.134:8900/事務所概要/
  - 取扱業務（アーカイブ）: http://172.29.10.134:8900/services/
  - 専門家紹介（アーカイブ）: http://172.29.10.134:8900/professionals/
  - 料金: http://172.29.10.134:8900/料金/
  - よくあるご質問（アーカイブ）: http://172.29.10.134:8900/faq/
  - お問い合わせ: http://172.29.10.134:8900/お問い合わせ/
- 管理画面: http://172.29.10.134:8900/wp-admin/ （`admin` / `password`）
- `127.0.0.1:8900` でアクセスした場合は WSL IP へ 301（localhost 転送が効いていれば表示可）。表示されない場合は上記 IP を直接使用。

## 23. Remaining risks

1. **参照ベース nav-link も per-environment な `url` 属性を持つ**: WP 7.1 の `core/navigation-link` は保存済み `url` をそのまま描画し `id` から再導出しない（§4 で実証）。したがってポータビリティが保証されるのは
   - **再構築**（fix スクリプトがパイプライン最終ステップとして `url` を再生成）
   - **別URLでの再 serve**（fix スクリプトを1本だけ再実行 — README §7 に記載）
   の2ケース。**生DBを別URLへコピーして「再構築も fix スクリプト実行もせず」serve した場合は自動修復しない。** 完全自己修復にするには下記の Core 変更が必要だが、それでも WP 7.1 では per-env の `url` 再生成が必要なのでパイプラインステップは残る。
2. **候補 Core 設計（未実装、Order §20 により報告のみ・別Order向け）**: `Astrea\Core\Setup\generate_navigation()`（`core/includes/setup-navigation.php:189`）の `'kind' => 'custom'` を、ページ = `kind:"post-type"` + `id`、CPTアーカイブ = `kind:"post-type-archive"` + `type` に変更し、凍結 `url` を出力しない。これにより「生成メニュー」が Block Editor 自身の保存形と一致し、将来の WordPress が `post-type` リンクを再解決するようになれば動的追従する。WP 7.1 現状では fix スクリプトの `url` 再生成が引き続き必要。**本 Construction では Core 未変更。**
3. **Playground `--site-url` の挙動差**: `download-and-install`（真の新規構築）はフラグを尊重（二港テストで実証）。`install-from-existing-files` では DB の `home`/`siteurl` を優先することがある。既存環境を別URLで再 serve する運用者は、新規再構築するか、URL を反映させたうえで（`home_url()` が新URLを返す状態で）fix スクリプトを再実行すること。
4. **`href="#"` の Contact CTA**（お問い合わせはこちら / お問い合わせフォームへ）はプレースホルダで実リンクなし。port/host/subpath に依存しないため portable だが機能はしない。**025-L1 の対象外（旧URL欠陥ではない）。**将来のコンテンツ完成 Order 向けに記録。

## 24. Final Verdict

**A. PASS — INTERNAL LINKS FULLY PORTABLE**

- Theme ソース・Core ソースに内部URLのハードコードは元々ゼロ（すべて WordPress-native API）。唯一の凍結箇所は生成済み `wp_navigation` エンティティ（Header/Footer 共通）と、clone DB の `home`/`siteurl` 行。
- 新規 `fix-internal-link-portability.php`（再現パイプライン最終ステップ）で、ナビを参照ベース化＋`url` を現在環境で再生成し、残存する絶対内部URLをシリアライズ安全に現在の `home_url()` へ書き換える。ハードコードされた host/port/IP はゼロ。
- **Two-Port Rebuild Test**: `:8901` と `:8902` の独立クリーンリビルドで、各環境が自 port のURLのみを持ち、相互リーク 0・旧 port 0・localhost 0 を確認。
- **Host variation**: `127.0.0.1` ではない LAN IP（`172.29.10.134`）でのクリーンリビルドでも全リンクが追従。コードに `127.0.0.1` 依存なし。
- **Crawl / Click / Media / Block validation** すべて PASS。Hero（025-E1H）・Results（025-E1）に退行なし。新規リンク関連 warning ゼロ。
- **Theme diff = Construction 025-E1 の4点のみ、Core diff = 0。** リンク問題は Demo reproducibility 側で完結。

残リスク（§23）: 「生DBを別URLへコピーして再構築も fix 実行もせず serve」した場合のみ自動修復しない（fix スクリプト1本の再実行で解消、README 記載）。真の自己修復には Core 変更（候補設計 §23-2）が必要だが本 Construction の対象外。

---

**STOP — commit / push / release / VPS / Construction 024 のいずれにも進みません。** Owner のレビュー（http://172.29.10.134:8900/）をお待ちします。
