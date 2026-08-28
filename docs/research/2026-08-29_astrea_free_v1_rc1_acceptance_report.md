# ASTREA FREE v1 — RC1 Release Acceptance Report

Construction 014 COMPLETE承認後の最終検査。「現在完成しているFREE v1を安全にReleaseできるか」のみを判定する。新機能開発・Architecture変更・Design変更・Post v1 Backlog着手は行っていない。

## 0. 正本Decisionの確認

Decision 020（FIXED）を正本として再確認した。ASTREA FREE v1のPHP最低要件は**8.3**、推奨は**8.4以上**である。Construction 014発注文にあった「PHP 7.4 minimum」という記載は、WordPress本体自体の絶対的な最低要件（7.4）とASTREA独自の最低ライン（8.3）の混同であり、採用しない。Decision 020の改定は行っていない。

## 1. RC1基本原則

新機能追加・Architecture変更・Design変更・Post v1 Backlog着手・無関係なRefactor・Documentationの大幅拡張、いずれも行っていない。Version Metadata、Changelog追記、License行末統一、Tags修正（WordPress.org正式Tag外の1件のみ）に限定した。

## 2. Pre-flight Repository Check

- Branch: `main`
- `git status`: クリーン（作業開始時点でUncommitted変更・Untracked fileともに無し）
- origin/mainと完全同期（divergenceなし）

停止条件（main以外／Uncommitted product changes／意図不明なUntracked file）はいずれも該当しなかった。

## 3. HISTORY確認

Construction 014のHISTORY.csv行のStart/Durationは「-」のまま維持し、推測での補完は行っていない。

本RC1の開始時刻は着手直後に実測した：**2026-08-29 08:14 JST**。

## 4-6. RC1 Version Policy / Inventory / 変更

`0.9.0`（Theme）・`0.12.0`（Core）のRepository全体検索を実施し、現在のRelease Metadata（`theme/style.css`、`core/astrea-core.php`のVersion Header、`ASTREA_CORE_VERSION`定数、両readme.txtのStable tag）のみを`1.0.0-rc1`へ更新した。過去のConstruction Report（`docs/research/2026-08-28_construction_order_014_release_preparation_report.md`）内の旧Version記録は履歴として一切書き換えていない。

`1.0.0-rc1`という表記自体の互換性を事前確認した：

- Theme/Plugin Headerの`Version:`：WordPressは`version_compare()`（PHP標準関数）でVersion比較を行い、`-rc1`はPre-release識別子として正しく認識される（`1.0.0-rc1` < `1.0.0`）。技術的な問題は無い。
- readme.txtの`Stable tag`：WordPress.org SVN配布では通常、実際のStable Releaseを指すべきフィールドだが、本Repositoryは現時点でWordPress.orgへSubmissionしていないため、実害は無い。**将来正式にWordPress.orgへSubmissionする際は、Stable tagを実際の安定版（`1.0.0`等）へ確実に更新すること**をRelease Procedureへの申し送り事項とする。

両方とも「問題があれば別Versionへ変更せず報告」の対象には該当しなかったため、`1.0.0-rc1`を採用した。

## 7. Changelog

Theme/Core両readme.txtに `= 1.0.0-rc1 =` Entryを追加した（事実のみ、誇張無し）。既存の`= 0.9.0 =`／`= 0.12.0 =` Entryは変更していない。

## 8. Release Freeze Diff

Version変更のみのCommit（`git diff --stat`で4ファイル、Version/Stable tag/Changelog/POT再生成のみ）であることを確認した。PHP/JS Functional Logicの変更は無い。

## 9. Full Automated Regression（RC1 Version状態）

| 項目 | 結果 |
|------|------|
| PHP Syntax | 全件OK |
| PHPCS | 62/62、0 Errors |
| PHPUnit | 359 tests, 560 assertions, OK |
| smoke-test.sh（全13 Part） | 203 OK / 0 FAIL、Exit Code 0 |

いずれも基準値（PHPUnit 359 tests以上、PHPCS 0 Errors、smoke 203 OK以上・0 FAIL）を満たした。件数の減少は無かった。

## 10. CI

Version変更Commit（`2982152`）Push後、GitHub Actions 3 Job全てGreenを確認した。

## 11. 公式Theme Check

WordPress.org公式Theme Check Plugin（`wp plugin install theme-check --activate`）を実際にInstallし、`wp theme-check run astrea`で実検査した（Construction 014時点の手動監査から格上げ）。

初回検査結果：

- **REQUIRED**：`license.txt`にDOS/UNIX混在のLine Endingがあり、SVN Repositoryで問題を起こす（要修正）。
- **INFO**：`block-theme`タグが不正（style.css Headerから削除すること）。
- **INFO**：Text Domainが単一（`astrea`）であることの確認メッセージ（問題ではない）。

対応：

1. `theme/license.txt`・`core/license.txt`・repository root `LICENSE`のLine Endingを全てCRLFへ統一した（WordPress本体の`license.txt`が採用する形式に合わせた）。
2. `theme/style.css`・`theme/readme.txt`の`Tags`行から`block-theme`を削除した。Theme Check Plugin自身のソース（`class-style-tags-check.php`の`get_allowed_tags()`）を直接確認し、`block-theme`が実際にWordPress.org正式Feature Filter外であることを検証した（古いチェッカーによる誤検知ではないことを確認済み）。`full-site-editing`は既に含まれており、意味的に重複しない。

再検査結果：INFO 1件（Text Domain確認、問題なし）のみ。REQUIRED/WARNINGはゼロ。

検査後、Theme Check PluginはDev環境から削除した（ASTREA自体の依存物ではない）。

**結果：`NOT VERIFIED BY OFFICIAL PLUGIN`ではなく、実際に公式Pluginで検査・合格した。**

## 12-16. Package RC1 / ZIP Structure / Artifact Content Audit / Secret Scan / SHA-256

`tools/release/package.sh`を使用し、旧Construction 014 Artifact（`astrea-theme-0.9.0.zip`・`astrea-core-0.12.0.zip`）を削除した上で、RC1 Versionの新規ZIPを生成した。

```
dist/astrea-theme-1.0.0-rc1.zip
dist/astrea-core-1.0.0-rc1.zip
dist/SHA256SUMS.txt
```

ZIP Root構造（Theme: `astrea/`、Core: `astrea-core/`）を再確認し、禁止項目（`.git`・`.github`・`docs`・`tests`・`tools`・`node_modules`・wp-env・ローカル設定・ログ・Research Screenshot・IDE File・機密File）が一切含まれないことを確認した。

生成されたZIPを実際に展開し、展開後の実ファイルに対してSecret Scan（API Key・Password・Token・秘密鍵・実GA4 ID・テストEmail・ローカルPath・開発用Hostname）を実施した。ヒットは全て正当なアプリケーションコード（Contact/Email確認用Tokenロジック）のみで、実際の秘匿情報の混入は無かった。

### 新規SHA-256 Checksum（Construction 014のChecksumとは別の新規値）

```
cd803a9c9ae1df070ca9692569f4f93a9cab62f33ea12869406ae1c445862202  astrea-theme-1.0.0-rc1.zip
f2a18f3eee2d2da138fda21d1c53340d0e30437b762b35cb777b03625a9ebfd7  astrea-core-1.0.0-rc1.zip
```

## 17-18. RC1 Clean Install（28項目）

Construction 014と同様、既存のwp-env（ASTREA用・他Project用いずれも）には一切触れず、新規のDocker Network（`astrea-rc1-install-test`）・Volume・Containerを作成し、テスト完了後に完全に削除した。Source Bind Mountは行わず、生成したRC1 ZIPそのものをInstallした。

| # | 項目 | 結果 |
|---|------|------|
| 1 | Theme ZIP install | PASS |
| 2 | Theme activate | PASS |
| 3 | Frontend HTTP 200 | PASS |
| 4 | Core無しでFatal無し | PASS |
| 5 | Core ZIP install | PASS |
| 6 | Core activate | PASS |
| 7 | Setup表示 | PASS（認証済みAdminで「セットアップ状況」表示確認） |
| 8 | Office Profile入力 | PASS |
| 9 | HOME生成 | PASS |
| 10 | Basic Pages生成 | PASS（事務所概要／料金／お問い合わせ） |
| 11 | Basic Navigation生成 | PASS（Header/Footer共にconnected） |
| 12 | Header実Navigation Link | PASS |
| 13 | Footer実Navigation Link | PASS |
| 14 | Page-List fallbackではない | PASS（実際の`<ul class="...wp-block-page-list...">`レンダリング無しを確認、CSS定義のみとの誤検知を排除） |
| 15 | Professional表示 | PASS |
| 16 | Service表示 | PASS |
| 17 | Price表示 | PASS |
| 18 | FAQ表示 | PASS |
| 19 | CASE表示 | PASS |
| 20 | RESULTS表示 | PASS |
| 21 | VOICE表示 | PASS |
| 22 | Contact Form表示 | PASS |
| 23 | Site Title Checklist表示 | PASS |
| 24 | Style Variation切替 | PASS（`WP_Theme_JSON_Resolver::get_style_variations()`でTrust/Natural/Modern 3件を確認。過去Construction 012/013で経験した、UI座標クリックに依存する脆いPlaywright Automationは今回採用せず、より確実なPHP層での確認に切り替えた） |
| 25 | Core OFF | PASS |
| 26 | Frontend Fatal無し | PASS |
| 27 | Core ON | PASS |
| 28 | Data復元 | PASS（Office Profile Option無傷） |

## 19. Editor Acceptance

HOMEをEditorで開き、ASTREA Dynamic Block（7件）のUnsupported Block警告が0件であることを確認した。

Save Round Trip検証は、HOME上のHero/Flow/CTA/Trust全4セクションがWordPress 7.1 core/group既知警告の対象であり、Gutenbergの標準挙動として警告中のBlock内Rich Textは直接編集できない（クリックしても編集モードへ入らない）ことが判明した。これはASTREA固有の制限ではなくGutenberg自身の保護動作であり、Construction 014Aの「別の無関係なParagraphを編集しても対象Blockの内容は無傷」という結果と整合する。

そのため、実際のSave Round Trip実機検証は「事務所概要」ページ（警告の無いClean状態、Construction 013で確認済み）で実施した。

1. Editorで実在するParagraph Blockをプログラム的に特定（`.has-warning`・ASTREA Placeholderを除外するロジックで自動選定）
2. 文末に "RC1EDITTEST" を追記
3. Save（Update）ボタンが有効化されたことを確認しClick
4. `post_content`を直接確認し、`astrea/office-hours`・`astrea/office-sns` Dynamic Blockが無傷で維持されていることを確認
5. Frontendで編集内容（RC1EDITTEST）が反映され、Fatal Errorが無いことを確認

いずれもPASS。

## 20. WordPress 7.1 Known Exception

RC1環境でも同一のcore/group・core/cover警告が再現した（HOME上、3ブロックで確認）。Construction 014Aの結論（WordPress Core側問題、ASTREA Theme/Core非依存、Content Loss無し、ASTREA側修正不能）を維持し、RC1 FAIL条件とはしない。ASTREA Dynamic BlockのUnsupported Warning（0件、Item 19）とは明確に別種として扱った。

## 21. Responsive Spot Check

RC1 ZIP Install環境、STRESS Fixture（極端に長い事務所名）で320/375/768/1440pxを実機確認した。

- 4 breakpointいずれもHorizontal Overflow無し（`document.documentElement.scrollWidth`による自動判定）
- Header：正式名称全文表示（省略・truncateなし）を320px/1440px双方で視覚確認
- Hero・Navigation・CTAいずれも崩れ無し

Construction 013のFinding 1修正が、RC1 ZIP環境でも有効であることを確認した。

## 22. Accessibility Spot Check

- H1: 1個のみ（事務所名見出し）
- 見出し階層：H1→H2→H3の順で、階層飛ばし無し
- Main/Nav/Footerランドマーク：いずれも存在
- Skip Link：ページ最初のTab StopがSkip Linkであることを確認。リンク先`#wp--skip-link--target`が実際に`<main id="wp--skip-link--target">`要素に付与されていることをHTML直接確認（WordPress Core標準機構）
- キーボードFocus可視性：Tab移動した要素全てで`outline: auto`（ブラウザ既定のFocus Ring、抑制されていない）を確認
- Form Label：HOMEにはForm無し（Contact Formは別ページ、Construction 005/012で確認済み）

## 23. SEO Spot Check

| 項目 | GA4未設定 | GA4設定時 |
|------|-----------|-----------|
| title | ✅ | ✅ |
| canonical | ✅ | ✅ |
| robots | ✅（`max-image-preview:large`、WordPress標準） | ✅ |
| sitemap | ✅（`/wp-sitemap.xml`、301→200でWordPress標準Canonical化） | ✅ |
| meta description | ✅ | ✅ |
| OGP（title/description/type/url） | ✅ | ✅ |
| Organization/Person JSON-LD | ✅ | ✅ |
| Search Console verification | ✅（設定値がそのままmetaに反映） | ✅ |
| GA4 gtag出力 | 無し（正しい） | ✅（設定した測定IDのみ出力） |
| FAQPage/Offer/ProfessionalService自動生成 | 無し（Decision維持） | 無し（Decision維持） |

og:imageは未設定のため出力無し（OGP画像未設定時の正しい挙動、不具合ではない）。

## 24-25. Core OFF Acceptance / Complete Deletion

Core OFF：HOME/Archive/Single/Search/404の5パターン全てでFatal無し、404は正しくHTTP 404を返すことを確認（Item 26と重複確認）。Core ONへ戻し、データ復元を再確認した。

Complete Deletion：`delete_all_core_data()`を実行し、以下を確認した。

- 削除：Professional/Service/Price/FAQ/CASE/RESULTS/VOICE（7 CPT、各1件）、Office Profile Option
- 保持：Setup生成Page（6件）、Navigation（2件）

これはreadme.txt・data-deletion.phpのDocumentation記載と完全に一致する。実データは使用せず、全てFictional Fixtureのみで検証した。

## 26. Documentation Acceptance

Theme/Core readme.txtを初見ユーザーとして読み直し、Install/Core optional/Setup/Office Profile/Professional/Service/Price/FAQ/CASE/RESULTS/VOICE/Contact/HOME/Navigation/Site Title/Style Variation/Site Editor/Core OFF/Deletion/GA4/SEO coexistence/Language/Known Issueの全項目が、本RC1で実機確認した実際の画面名・挙動と矛盾しないことを確認した。RC1 Version反映（Stable tag、Changelog）も整合している。

## 27-28. License Acceptance / POT Acceptance

RC1 ZIP内に`license.txt`（Theme/Core双方）が存在し、GPL表記が一致することを確認した（Line Ending問題は本Construction内で修正済み）。第三者Unknown Assetはゼロ（Construction 014で確認済み、変更無し）。

Theme/Core双方のPOTファイルがRC1 ZIP内に存在し、Text Domain（`astrea`/`astrea-core`）がHeaderと一致することを確認した。Version変更に伴い再生成し、Project-Id-Versionを`1.0.0-rc1`へ更新した（msgid件数は無変更：Theme 58件、Core 273件）。

## 29. WordPress.org Readiness Classification

| 発見事項 | 分類 |
|---|---|
| license.txtのLine Ending混在 | C（WordPress.org submission only）— 修正済み |
| `block-theme`タグが非公式 | C（WordPress.org submission only）— 修正済み |
| Contributors欄のPlaceholder | C（WordPress.org submission only）— 実際のSubmission時に対応 |
| WordPress 7.1 core/group・core/cover警告 | E（Known external issue）— 対応不要 |

WordPress.org正式Submissionは今回も行っていない。

## 30. Post v1 Backlog

Finding 6（Professional Archive空Excerpt）、Finding 7（Search Breadcrumbラベル）、Finding 8（Price Group表示）は今回も修正していない。RC1 Acceptanceを口実にScopeを広げていない。

## 31-32. BLOCKER定義 / Minor Finding対応

Fatal・Data Loss・Security HIGH以上・Clean Install破損・Packaging破損・Setup破損・Navigation非表示・Contact破損・Theme単体Fatal・Secret混入・License不備・Major Responsive Breakage・ASTREA Dynamic Block Recovery Riskのいずれも発見されなかった。

発見した2件（License Line Ending、非公式Tag）はいずれもMetadata/Documentation Fixとして安全に本RC1内で対応した（Part 32の許可範囲内）。

## 33. RC1 Acceptance Checklist

`docs/release/RC1_ACCEPTANCE_CHECKLIST.md`を実際に使用し、19項目全てにPASSを記入した（未検証のPASSは無い）。詳細は当該ファイルを参照。

## 34-40. Report / HISTORY / Commit / TAG禁止 / Artifact保持 / Cleanup / 最終判定

本Report自体が34の成果物。HISTORY.csv・Commit・CI Green確認は本Report末尾の完了報告のとおり実施する。RC Tag・GitHub Release・Project-if配布・WordPress.org Submissionはいずれも実行していない。

RC1 Artifact（`dist/astrea-theme-1.0.0-rc1.zip`・`dist/astrea-core-1.0.0-rc1.zip`・`dist/SHA256SUMS.txt`）はGit管理外のまま保持し、削除していない。Clean Install用のDocker Network/Volume/Container、および検証用Fixture（Office Profile、CPT各1件、HOME/基本Page、Navigation）は全て削除済み。既存開発環境（wp-env-if-professional-astrea-*）・他Project環境（wp-env-if-thema-mybase-*）はいずれも無傷。

### 最終判定

**RC1 ACCEPTED WITH KNOWN EXCEPTIONS**

Known Exceptions：

1. WordPress 7.1環境のcore/group・core/cover Block Validation Warning（WordPress Core側の問題、ASTREA非依存、Content Loss無し、readme.txt記載済み）
