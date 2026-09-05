# Construction Order 018 — ASTREA FREE v1 RC2 Release Preparation / Candidate Sealing 施工報告

## 0. Mission

Construction 017で受入検査を通過した検査済みソースを、正式なRC2 Release Candidate Artifactへ昇格させる。新機能開発・Visual改善・リファクタリングは一切行っていない。「ついでに直す」「せっかくだから整理する」は行っていない。

## 1. Pre-Release Freeze（着工時記録）

- `git status --short`: 事前確認の時点で、着工前から存在する無関係な未追跡ファイル（`docs/research/references/ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier`）のみ。Construction 018の対象外として一貫して触れていない。
- `git branch --show-current`: `main`
- `git tag --list`: **既存Tagなし**（衝突リスクなし）。RC1は過去に検査・準備はされたが、実際にTag/GitHub Release/配布されたことは一度もなかったことを確認した。
- `git rev-list --left-right --count main...origin/main`: `0 0`（着工前・main/origin完全同期）。
- HEAD commit（着工時）: `de58312`（Construction 017 HISTORY確認コミット）。
- Theme Version（着工時）: `1.0.0-rc1`。Core Version（着工時）: `1.0.0-rc1`。
- Theme/Core Stable tag（着工時）: 両方とも`1.0.0-rc1`。
- Requires at least: `7.0`。Tested up to: `7.1`。Requires PHP: `8.3`（Decision 020、無変更）。

## 2. Version Source of Truth — 全数調査結果

以下を確認し、実際にVersion文字列を保持する箇所を特定した。

| 種別 | ファイル | 変更 |
|---|---|---|
| Theme Header | `theme/style.css`（`Version:`） | rc1 → rc2 |
| Theme readme | `theme/readme.txt`（`Stable tag:`） | rc1 → rc2 |
| Theme POT | `theme/languages/astrea.pot`（`Project-Id-Version:`） | 再生成、rc2反映 |
| Core Plugin Header | `core/astrea-core.php`（`Version:`コメント） | rc1 → rc2 |
| Core Version定数 | `core/astrea-core.php`（`ASTREA_CORE_VERSION`） | rc1 → rc2 |
| Core readme | `core/readme.txt`（`Stable tag:`） | rc1 → rc2 |
| Core POT | `core/languages/astrea-core.pot`（`Project-Id-Version:`） | 再生成、rc2反映 |

**意図的に変更しなかった箇所**（Version文字列を含むが、Theme/Coreの実際のVersion source of truthではないと判断）：

- `theme/functions.php`の`const VERSION = '0.1.0';` — "Construction-phase skeleton only"という自己コメント付きの未使用定数（`grep`で使用箇所0件を確認）。RC1の時点から既に`1.0.0-rc1`と不一致のまま存在しており、今回のOrderで新たに生じた不整合ではない。機能に一切影響しないメタデータであり、「ついでに直す」を避けるため、Reportへの明記のみで意図的に無変更とした。
- `package.json`の`"version": "0.1.0"` — リポジトリ自体の開発Tooling manifest（`"private": true`、配布Package（`package.sh`が生成するZIP）には一切含まれない）。Theme/Coreの製品Versionとは無関係。
- `docs/release/RELEASE_PROCEDURE.md`内の`v1.0.0-rc1`例示コマンド — 再利用可能な手順書内の記法例であり、特定Releaseの実績記録ではないため無変更。
- `docs/specifications/07_astrea_visual_v3_design_direction.md`内の`RC1 (1.0.0-rc1)`— 016H着工時点のBaseline記録という歴史的記述であり、無変更。
- `docs/release/RC1_ACCEPTANCE_CHECKLIST.md` — RC1自身の歴史的記録であり、無変更。

Version表記の不整合は、実際にWordPress/Theme Check/Plugin Checkが検証する箇所（Header/Stable tag/POT）において残していない（§6で確認）。

## 3. Changelog / Release Notes（§4）

`theme/readme.txt`・`core/readme.txt`双方の`== Changelog ==`へ、RC1以降の内容をユーザー向けに要約した`= 1.0.0-rc2 =`エントリを追加した（既存の`= 1.0.0-rc1 =`エントリは履歴として無変更のまま維持）。内部Construction番号は羅列せず、実際の変更内容（HOME/内部ページのVisual改善、検索結果パンくずラベル改善、Release Metadata更新等）に対応する記載とした。

GitHub Release用のRelease Notesを新規作成：`docs/release/RC2_RELEASE_NOTES.md`。RC2の位置付け・主な改善点・要件・Theme/Coreの関係・インストール概要・Known Issues（017でRelease Blockerではないと判定した項目のみ、WordPress 7.1 core/group警告はASTREA固有不具合として記載せず）・実施した検査・Artifact名・RC2がpre-releaseであることを含む。

## 4. POT / Translation Sync（§5）

`wp i18n make-pot`（wp-envのcli container内、公式i18n Toolchain）で両POTを再生成した。

- `theme/languages/astrea.pot`: msgid数 58 → 60（Construction 014以降、初めての再生成。翻訳対象文字列の欠落なし、純増のみ）。
- `core/languages/astrea-core.pot`: msgid数 273 → 294（同上）。
- Text Domain（`astrea`/`astrea-core`）はいずれも正しいまま。
- `wp i18n make-pot`実行時、`core/includes/case-list-block.php`他2ファイルで同一文言（`"（%sについて）"`）に異なる`translators:`コメントが付与されている旨のWARNINGが出力された——これは既存のコード構造に起因する軽微な情報であり、文字列の欠落や誤生成ではない（3箇所とも正しく`.pot`へ反映されている）。Version Bump/POT同期のスコープを超える修正（コード側のtranslatorsコメント整理）は本Orderでは行っていない。

## 5. Quality Gate — Source（§6）

Construction 017 baselineとの比較：

| 項目 | 017 Baseline | 018（Version Bump後） | 結果 |
|---|---|---|---|
| PHPUnit | 399/399（既知3件） | **399/399（既知3件、同一）** | 悪化なし |
| PHPCS | 67 files, 0 violations | **67 files, 0 violations** | 悪化なし |
| Theme Check | REQUIRED 0/WARNING 0/INFO 1 | **REQUIRED 0/WARNING 0/INFO 1** | 悪化なし |
| Plugin Check | `.gitkeep`(既知)・`data-deletion.php`誤検知(既知)・`load_plugin_textdomain`(既知Deferred) | **同一3件のみ**（`stable_tag_mismatch`は`core/readme.txt`のStable tag未更新に気づき即修正、再検査でクリア） | 悪化なし |

`git diff --stat`で、Release Preparationの許可範囲（Version/Stable tag/Changelog/POT/Release documentation）外の変更が無いことを確認した（§8参照）。

## 6. Package（§7）

`tools/release/package.sh`（既存の正式script、手作業ZIP無し）を実行。

```
Theme version: 1.0.0-rc2
Core version:  1.0.0-rc2
Built: dist/astrea-theme-1.0.0-rc2.zip
Built: dist/astrea-core-1.0.0-rc2.zip
Checksums written: dist/SHA256SUMS.txt
```

- ZIP Root構造: `astrea/`・`astrea-core/`（正しい）。
- ファイル数: Theme 63 files、Core 61 files。
- Version表記: ZIP内`style.css`・`astrea-core.php`・両`readme.txt`いずれも`1.0.0-rc2`で一致。
- 混入チェック: `.git*`・`.env*`・`node_modules`・`.fixture-backups`・`docs/`・`tests/`・secret・`.gitkeep`・ローカルwp-env artifactいずれも0件（`unzip -l`で全件確認）。

## 7. SHA256（§8）

```
astrea-theme-1.0.0-rc2.zip: 60519afd9cfa8221cc68be0094399bc372eae9ec5552987ed678acab03bb2afa
astrea-core-1.0.0-rc2.zip:  b3eaed92d65c479bd636db24b5fe5ce6972eda0e3405636b9b999b8b5811c10e
```

`sha256sum -c SHA256SUMS.txt` で実ファイルとの再照合を実施、両方`OK`。

## 8. Final Clean Install from Exact RC2 ZIP（§9）

Construction 017のClean Install結果を流用せず、**今回生成したRC2 ZIPそのもの**を使用し、新規の使い捨てDocker環境（MySQL 8.0 + `wordpress:php8.3-apache`、過去に一切使用歴のない完全新規DB）で再実施した。

| # | 項目 | 結果 |
|---|---|---|
| A | Theme ZIPのみ Install/Activate | PASS — Fatal無し、Core無しでも安全、`wp theme get astrea --field=version` = `1.0.0-rc2`、HOME HTTP 200 |
| B | Theme + Core | PASS — Core Install/Activate、`wp plugin get astrea-core --field=version` = `1.0.0-rc2`、HTTP 200、debug.log空 |
| C | Core OFF | PASS — Fatal無し、HTTP 200 |
| D | Core ON | PASS — HTTP 200、debug.log空 |
| E | Setup | PASS — `generate_pages()`で3ページ（事務所概要/料金/お問い合わせ）をDraftとして生成、`generate_navigation()`を2回呼び出し同一ID(8)を返す（冪等性確認）、`generate_home_page()`を2回呼び出したところ2回目は`WP_Error`（コード`astrea_home_exists`、「ホームページは既に作成済みです。」）を返す設計であることを確認——重複作成やWizard State破損は発生せず、意図的なGuardとして正しく機能している |
| F | Contact | PASS — 生成した実フォームの実Nonceで正常送信（`astrea_contact_success=1`、Inquiry `post_status=private`で保存）、不正Nonceは`astrea_contact_error=1`で拒否されInquiry未保存、`astrea_inquiry`投稿タイプの`public`/`publicly_queryable`いずれも`false`を確認 |
| G | Responsive | PASS — 320/375/768/1440/1920pxでHOME・料金ページ、Horizontal Overflow 0（Screenshot: `docs/research/screenshots/018/rc2-clean-{home,price}-{375,1920}.png`）。017で確立したVisual Geometryと同一であることを視覚確認 |

検証後、使い捨て環境は完全に破棄し、既存開発環境への影響はない。

## 9. Release Commit（§10）

Release Commit: **`5a1f6b4`**（"Prepare ASTREA 1.0.0-rc2 release candidate"）。

Push後、CI run [33933536109](https://github.com/apist777/if-professional-astrea/actions/runs/33933536109) — PHP syntax + Coding Standards / PHPUnit (ASTREA Core) / Theme・Core independence smoke test、3 Job全てGreenを確認。`git status --short`はCommit直後にclean（着工前から存在する無関係な未追跡ファイルのみ）。

## 10. Tag Safety Gate（§11）

CI Green確認後、`5a1f6b4`上に注釈付きTag `v1.0.0-rc2` を作成。

```
$ git rev-parse v1.0.0-rc2^{commit}
5a1f6b40426c5ce4c8a16132ea8f6f979cc8c126
$ git rev-parse HEAD
5a1f6b40426c5ce4c8a16132ea8f6f979cc8c126
```

一致を確認（`git show v1.0.0-rc2 --no-patch`でTag自身がRelease Commitそのものを指していることも確認済み）。`git push origin v1.0.0-rc2`でoriginへPush。Tag作成後、対象Commitへの書き換えは一切行っていない。

## 11. GitHub Release（§12）

`gh release create v1.0.0-rc2` で作成。

- Title: `ASTREA 1.0.0-rc2`
- Tag: `v1.0.0-rc2`（既存Tagから、新規target_commitish指定は行わず——`gh`の`--target`はTagが既にRemoteに存在する場合は不要かつVALIDATION ERRORになることを確認したため、Tag自身を単一のSource of Truthとした）
- Pre-release: **YES**（`prerelease: true`を`gh release view`で確認）
- 添付Asset: `astrea-theme-1.0.0-rc2.zip`・`astrea-core-1.0.0-rc2.zip`・`SHA256SUMS.txt`（3件、`gh release view`で確認）
- Release Notes: `docs/release/RC2_RELEASE_NOTES.md`の内容をそのまま使用
- URL: https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.0-rc2

GitHubが自動生成するSource Code archive（zip/tar.gz）とは別に、上記3ファイルのみを配布Artifactとして明示的に添付した。

## 12. Download Verification（§13）

`gh release download v1.0.0-rc2`で、公開直後のGitHub ReleaseからTheme/Core ZIP・SHA256SUMS.txtを再取得し、ローカルの`dist/`にある検査済みArtifactとSHA256を再照合した。

```
Theme (downloaded): 60519afd9cfa8221cc68be0094399bc372eae9ec5552987ed678acab03bb2afa
Theme (local dist): 60519afd9cfa8221cc68be0094399bc372eae9ec5552987ed678acab03bb2afa  → MATCH
Core  (downloaded): b3eaed92d65c479bd636db24b5fe5ce6972eda0e3405636b9b999b8b5811c10e
Core  (local dist): b3eaed92d65c479bd636db24b5fe5ce6972eda0e3405636b9b999b8b5811c10e  → MATCH
```

**GitHub上のRelease Assetと、本Orderで検査したArtifactは、1 byte単位で同一であることを確認した。**

## 13. 変更ファイル（§21）

`theme/style.css`・`theme/readme.txt`・`theme/languages/astrea.pot`・`core/astrea-core.php`・`core/readme.txt`・`core/languages/astrea-core.pot`（いずれもVersion/Stable tag/Changelog/POTのみ）、新規`docs/release/RC2_RELEASE_NOTES.md`、新規Report・Screenshots。Functional PHP/JS/CSS/template/block behaviorへの変更は0件。

## 14. Remaining Known Issues

Construction 017で確認された非Blocker事項（Professional Archive空excerpt、Price Group表示、WordPress 7.1 core/group警告、`load_plugin_textdomain()` Deferred、Contributors placeholder）は本Orderのスコープ外であり、いずれも変更していない。`RC2_RELEASE_NOTES.md`のKnown Issuesに正確に反映済み。

## 15. Deviations / Findings

- Version Bump作業中、`core/readme.txt`のStable tag行の更新を一度失念し、Plugin Checkの`stable_tag_mismatch`ERRORで検出、即座に修正・再検査でクリアを確認した（本Report内で正直に記録する自己発見の手順ミス。Release ZIP生成前に修正済みであり、実際のArtifactには影響していない）。
- `wp i18n make-pot`実行時の`translators:`コメント重複WARNING（§4）は、Version Bump/POT同期の範囲を超えるためコード修正は行わなかった。

## 16. 最終Git状態（§24）

```
$ git status --short
?? "docs/research/references/ChatGPT Image 2026年8月29日 21_14_16.png:Zone.Identifier"   ← 着工前から存在する無関係ファイル、Construction 018の対象外
$ git log -1 --oneline
5a1f6b4 Prepare ASTREA 1.0.0-rc2 release candidate
$ git rev-list --left-right --count main...origin/main
0	0
```

working tree clean（許可範囲外のTracked変更なし）、main/origin完全同期。

**重要な注記**：本Report自身とHISTORY.csvの確定値は、Tag `v1.0.0-rc2` の作成・Push・GitHub Release公開・Download Verificationが全て完了した**後**に、別Commitとして追加する。したがって、その別Commit（HISTORY確認Commit）は **Tag `v1.0.0-rc2` の対象ではない**——Tagが指すのは、実際にVersion/Metadata/POTを変更した唯一のCommit `5a1f6b4` のみである。この後続CommitでProduct Code・Release Metadata・Artifactへの変更は一切行っていない（Report本文の追記とHISTORY.csvの更新のみ）。

## 17. 測定値・Commit

- Start: 2026-09-05 06:56 JST（Construction 017完了直後）
- End: 2026-09-05 09:46 JST（Download Verification完了時点の実測）
- Duration: 2h50m
- Release Commit: `5a1f6b4`（Tag `v1.0.0-rc2` が指すCommit）
- HISTORY確認Commit: 本Reportへの追記完了後に別途記録（Tag対象外）

## 18. Acceptance Criteria チェック

- [x] Theme = 1.0.0-rc2
- [x] Core = 1.0.0-rc2
- [x] metadata synchronized
- [x] Changelog updated
- [x] POT synchronized
- [x] Source quality gates PASS（017 Baseline同一、悪化なし）
- [x] final package.sh PASS
- [x] final RC2 ZIP clean
- [x] SHA256 generated + verified
- [x] exact final ZIP clean-install PASS
- [x] Release Commit CI Green
- [x] v1.0.0-rc2 annotated tag created
- [x] Tag points exact Release Commit
- [x] GitHub Release created as Pre-release
- [x] Theme/Core/SHA assets uploaded
- [x] Downloaded assets SHA match
- [x] HISTORY.csv updated（本Commit）
- [x] Report complete
- [x] working tree clean
- [x] main/origin synchronized
- [x] no Project-if deploy
- [x] no WordPress.org submission
- [x] no final 1.0.0

## 19. Final Verdict

**A. RC2 RELEASED — OWNER FINAL ACCEPTANCE REQUIRED**

RC2は https://github.com/apist777/if-professional-astrea/releases/tag/v1.0.0-rc2 としてPre-release公開済み。GitHub上のAssetと検査したArtifactは1 byte単位で同一であることを確認した。Construction 019、final 1.0.0への自律進行は行っていない。Ownerが実際のRC2 Release（Tag/Asset/Release Notes）を確認するまで待機する。

---

**Status: AWAITING OWNER RC2 FINAL ACCEPTANCE**

Construction 019・final 1.0.0への自律進行は行っていない。
