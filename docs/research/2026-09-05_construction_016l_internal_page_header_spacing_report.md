# Construction Order 016L — Internal Page Header Spacing Audit & Visual v3 Polish 施工報告

## 0. Owner Finding

HOME Visual v3はOwner Acceptance済み（Construction 016K）。一方、Service SingleをOwner確認したところ、Breadcrumb→Kicker→H1→本文というPage Header領域で、H1下から次コンテンツまでの垂直余白が大きく見え、内部ページだけ旧来の「巨大なPage Header」感が残っているとの指摘を受けた。局所的な`margin-bottom`削減ではなく、内部ページ全体のVertical RhythmをVisual v3 Design Systemとして監査・統一することが求められた。

## 1. Pre-Construction Audit — 全数監査（§2）

Single（Service/Professional/CASE）、Archive（Service/Professional/CASE/VOICE/FAQ）、静的ページ（Office/Price/Contact）を1920px Desktopで横断確認した。

## 2. Measurement（§3）— 実測結果

Chromium `getBoundingClientRect()` / `getComputedStyle()`による実測（1920px、Chrome Playwright）。

### 2.1 Archive（Service/Professional/CASE/VOICE/FAQ、5ページとも同一構造）

| Metric | 実測値 |
|---|---|
| Breadcrumb → Kicker | 32px |
| Kicker → H1（padding-top） | 32px |
| H1 → Listing | 32px |
| Header Total Height（Breadcrumb top → Listing top） | 202.9px |

5 Archiveすべてで完全に同一の値——不合理な差は存在しない。**Archiveは既に健全であり、変更不要と判断した。**

### 2.2 Single（Service / CASE / Professional）— Page Header自体（Order §1項目1〜4）

| Metric | Service Single | CASE Single | Professional Single |
|---|---|---|---|
| Breadcrumb → Kicker | 32px | 32px | 40px（Kicker機構なし、016F確立の別意匠） |
| Kicker → H1（padding-top） | 32px | 32px | 0px（Kickerなし） |
| H1 → First Content（直後の兄弟要素） | 40px（段落） | 40px（Featured Image） | 24px（Professional Field） |
| Header Total Height | 210.9px | 210.9px | — （Professional Singleは写真付きHeader構成が異なるため単純比較不可） |

**この4指標自体はService/CASEとも既に一致しており、いずれも016F/016Iで確立済みの値（32px=medium Token、40px=`--astrea-v3-heading-gap`）で、個別に見て過大ではない。** Professional SingleはKicker機構を使わない別設計（写真+基本情報の横並びHeader）のため、同一構造への強制統一はOrder §2の"無理に同一構造へ変更しない"に従い行わなかった。

### 2.3 Root Cause — 実際の"大きく見える空白"の発生源

Order §1項目1〜4（厳密な意味でのPage Header）を実測した結果、いずれも健全だった。しかし、Owner Findingが指摘する「H1を表示したあと何もない白い領域が続く」という体感は、**Page Headerの直後ではなく、本文（リード文）が終わった直後、次の"関連する取扱業務"見出し（H2）までの間隔**から生じていることを特定した。

Service Single・CASE Singleはどちらも、post_content終了直後に`astrea/service-list`（関連業務の紹介、Order自身にとってのPage Headerの範囲外だが、読者体験としては連続したCompositionの一部）を配置しているが、この見出し（`h2:has(+ .wp-block-astrea-service-list)`）は**HOME自身のSERVICEセクション見出しと全く同じ共有Selector**を使っており、`margin-top:var(--astrea-v3-section-gap)`（96px）という、HOME上の大型セクション同士の切り替え用に設計された値をそのまま継承していた。

HOMEでは前後に十分な視覚的重量（Hero、3カードグリッド等）があるため96pxが適切に機能するが、Single page上ではリード文がわずか2行程度で終わった直後にこの96pxが来るため、「本文の後に何もない領域が続く」という体感を生んでいた。これはOrder §3が挙げる「複数ルールの加算」ではなく、**単一のTokenが2つの異なる意味的役割（HOME大型セクション間の切り替え／Single page内の本文→関連リストの遷移）に対して無差別に共有されていたこと**がRoot Causeである。

## 3. 実施した修正

Order §5「Preferred: shared Page Header class・既存Token・既存Selector構造」に従い、ページ別Patchを避け、単一の共有Selectorに対する`body.single`スコープ限定の上書き1行のみを追加した。

```css
body.single h2:has(+ .wp-block-astrea-service-list){margin-top:var(--wp--preset--spacing--large);}
```

- `body.single`：WordPress自身が全Single CPT（`single-astrea_service`・`single-astrea_case`等）に自動付与する既存のBody Class。新規Fixtureデータ・新規Conditionalは一切追加していない（Decision 028のZero-New-Fixture-Dependency原則の再適用）。
- `var(--wp--preset--spacing--large)`（56px）：新規Token不使用、既存のSpacing Scaleから流用。
- HOME（Body Class `home`）・Archive（Body Class `archive`）はこのSelectorの対象外のため無変更。

Professional Singleは元々このSelector・関連リスト構造を持たないため、本修正の対象外（無変更）。

### 3.1 変更ファイル

`theme/theme.json`（CSSのみ、1行追加）。他のファイルへの変更なし（`git diff --stat`で`theme/theme.json | 2 +-`のみ確認済み）。PHPファイル変更0件。

## 4. Before / After Measurement Table（§14）

Service Single、1920px、実測値のみ（推測値なし）。

| Metric | Before | After | Result |
|---|---|---|---|
| Breadcrumb → Kicker | 32px | 32px | 無変更（既に健全） |
| Kicker → H1 | 32px | 32px | 無変更（016I確立、維持） |
| H1 → First Content（リード文） | 40px | 40px | 無変更（既に健全） |
| Header Total Height | 210.9px | 210.9px | 無変更 |
| **本文終了 → 関連業務見出し（Root Cause）** | **96px** | **56px** | **修正（-40px）** |

CASE Singleも同一Selectorのため同じ結果（96px→56px）。Before/After screenshot: `01-service-single-before-1920.png`（`git stash`不使用、修正前に直接キャプチャ）／`02-service-single-after-1920.png`。

## 5. Visual Target（§4）確認

H1自体の`padding-top`/`margin-bottom`/フォントサイズはいずれも無変更——H1の存在感は完全に維持されている。修正後、リード文から"関連する取扱業務"見出しまでの遷移が、Page Headerと本文が連続したCompositionとして見えるようになったことを`02-service-single-after-1920.png`・`04-case-single-1920.png`で確認した。

## 6. Archive Header（§7）確認

016I/016JのColored Section Kicker Spacing（Background Top→56px→Kicker→32px→Heading）を内部ページへ機械的にコピーすることはしていない。Archiveは元々32px/32pxの単純な構造で健全であり、無変更とした。Single pageの修正（本文→関連リスト見出し、96px→56px）は全く別のArchitecture（Archiveとは無関係のSelector）であり、混同していない。

## 7. Mobile（§8）確認

320px・375px・768pxで確認。

- Service Single通常表示：`13-service-single-375.png`・`14-service-single-320.png` — 本文→関連業務見出しの間隔が圧縮され、Mobileでも自然な連続Compositionになっていることを確認。
- **Long Japanese H1 Stress Test**：既存Service「建設業許可申請」の`post_title`を一時的に「建設業許可申請・更新・変更手続きサポート」（Order例示と同等の16文字）へ変更し（`wp post meta`ではなく`wp post update --post_title`、事前バックアップ取得→検証後に元の値へ即座に復元、`wp post get`で復元値が"建設業許可申請"と一致することを確認済み）、375px（`15-long-title-375.png`）・320px（`16-long-title-320.png`）を確認。H1が3行に自然に折り返り、Kickerとの衝突・文字サイズの強制縮小・不自然な一文字だけの折返しはいずれも発生しないことを確認した。

## 8. Style Variations（§9）確認

Trust（無変更）・Natural（`19-natural-service-single.png`）・Modern（`20-modern-service-single.png`）の3 Variationで、Service Singleの修正後の間隔を確認した。Variation固有のSpacing Systemは作成していない——`body.single`スコープの単一ルールがそのまま3 Variationで機能することを確認した。Trustへ復元後、`wp_global_styles`（Post ID 825）を差し替え前バックアップとPython `json.dumps(sort_keys=True)`でdiff、完全一致を確認済み。

## 9. Accessibility（§10）確認

本OrderはCSSの`margin-top`1箇所のみの変更であり、HTML構造・見出し階層・Breadcrumbのsemantics・Q/Aラベル等は一切変更していない。H1は各ページ1個のまま、Focus visible・Skip link・Contrastもいずれも無変更。

## 10. Core OFF Safety（§11）確認

`wp plugin deactivate astrea-core`後、HOME HTTP 200・Fatal/Warning無しを確認した。Service Single URLはCore OFF時にHTTP 301（Core管理下のCPTのRewrite Ruleが存在しなくなるため、WordPress自身の標準的な代替先へのリダイレクト）を返すが、これはCore OFF時の既存の既知動作であり、Fatalではなく、本Orderが変更したCSSの`margin-top`とは無関係である。Core再有効化後、HOME HTTP 200へ正常復帰を確認した。

## 11. Regression（§6・§13）確認

- **HOME Freeze**: `h2:has(+ .wp-block-astrea-service-list)`のComputed Style（`margin-top`）をHOME上で実測し、修正前後で`96px`のまま完全に無変更であることを確認した（`17-home-regression-1920.png`・`18-home-regression-375.png`で全体Compositionも016K承認時と一致することを視覚確認）。
- Horizontal Overflow: 7幅（1920/1440/1366/1024/768/375/320）× 14ページ（HOME、5 Archive、3 Static Page、Search、404、Service/CASE/Professional Single）= 98パターンを機械的に確認、超過0件。
- 016H/016I/016J：本Orderが変更したSelectorはこれらのOrderが変更したSelector（`h2:has(+ .wp-block-astrea-{case,results,voice}-list)`、内部ページPage Title `.astrea-archive-header h1`等）とは異なるため、直接の影響なし。

## 12. Technical Verification（§15）結果

- **PHPUnit**: `npx wp-env run tests-cli "vendor/bin/phpunit"`で398/398実行。既知のPre-existingエラー3件（`wp-phpunit`のAttachment Factory起因）のみ、無変更。本Orderで変更したPHPファイルは**0件**。
- **PHPCS**: 対象PHPファイル0件のため実行対象なし。
- **Theme Check**（公式Plugin、検証専用に一時インストール→検証後アンインストール済み）: REQUIRED 0 / WARNING 0 / INFO 1（既存・無変更）。
- **Core OFF→ON**: §10の通り。

## 13. Known Findings（§12、範囲外だが記録）

以下は本Orderのスコープ外だが、監査中に気づいた点として記録する（施工範囲を拡大せず、Reportへの記録のみ行う）：

- Professional Singleは他のSingleと異なり、写真+基本情報を横並びにした独自のHeader構成（Kicker機構なし）を持つ。これは016F時点での意図的な設計判断であり、今回の監査で不具合とは判断していない。
- Service Single / CASE Single以外に、`astrea/service-list`・`astrea/case-list`等の"関連コンテンツ"見出しをpost_content直後に配置しているテンプレートは現状存在しない（今回の`body.single`スコープはこの2テンプレートのみに影響）。将来同様のPatternが追加された場合、同じSelector・Tokenで自動的にカバーされる設計になっている。

## 14. Owner Acceptance Criteria（§17）確認

| # | 基準 | 結果 |
|---|---|---|
| 1 | Owner FindingのService Singleの過剰な下余白が解消 | PASS（§4） |
| 2 | H1の存在感は維持 | PASS（H1自体は無変更） |
| 3 | Internal Page Headerに統一感がある | PASS（Archive/Single双方で共有Selectorのみ使用） |
| 4 | Single間で不合理なSpacing差がない | PASS（Service/CASE同一、Professionalは別設計として妥当） |
| 5 | Archive間で不合理なSpacing差がない | PASS（5 Archiveとも完全同一値） |
| 6 | Mobileで巨大空白/衝突/縦書き化なし | PASS（§7） |
| 7 | Long Japanese H1 safe | PASS（§7 Stress Test） |
| 8 | HOME Visual v3 unchanged | PASS（§11、実測値で確認） |
| 9 | all 3 Variations safe | PASS（§8） |
| 10 | Core OFF safe | PASS（§10） |
| 11 | Horizontal Overflow 0 | PASS（98パターン） |
| 12 | PHPUnit PASS | PASS（398/398、既知3件除く） |
| 13 | PHPCS PASS | PASS（対象0件） |
| 14 | Theme Check REQUIRED 0 / WARNING 0 | PASS |
| 15 | CI Green | Push後 `gh run watch` で確認し、HISTORY.csv確認コミットで記録する |

## 15. 測定値・Commit

- Start: 2026-09-05（本Order着手）
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER INTERNAL PAGE HEADER ACCEPTANCE**

Construction 017へ自律進行禁止。Release Ready宣言・Tag・GitHub Release・Project-if Deploy・WordPress.org submissionのいずれも実施していない。Ownerがスクリーンショットを確認しAPPROVEDを出すまで待機する。
