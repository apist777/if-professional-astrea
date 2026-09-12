# Construction 028-G — Office Information Visual Refinement
ASTREA Starter Site / Live Demo Footer-Area Balance

- Start: 2026-09-12（Construction 028-F CLOSE後、Owner Direction受領時）
- Modifier: Chloe
- Mode: **ローカル実装・確認のみ。commit / push / VPS deployは一切実施していない。Live Demo（本番）には一切触れていない。**

---

## PHASE 0 — PRECHECK

```
$ git rev-parse HEAD && git rev-parse origin/main
679518713b9fe92b3fcb278a39ad498a6cc5afa8（両者一致、分岐なし）
$ git diff --stat -- theme/ core/
（出力なし、着手前は差分0）
```

Owner未commit変更（`HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群等）はConstruction 028-F時点から変化なし。Construction 028-Fで判明済みの構造（CSS Gridで2カラム化・782px以下でvertical stackに戻る）を再確認した。

---

## PHASE 1 — CURRENT STRUCTURE（着手前の記録）

- **Outer block**: `core/includes/setup-pages.php`の`page_definitions()['about']`が生成する`wp:group{"className":"astrea-office-page","layout":{"type":"flex","orientation":"vertical"}}`。
- **実際のDesktop表示**: `theme/theme.json`の`styles.css`内、`.astrea-office-page.is-layout-flex`セレクタが`display:grid;grid-template-columns:minmax(260px,1fr) minmax(0,1.4fr)`でコアのflex-column指定を上書きし、`nth-child(1)`（office-summaryカード）を`grid-row:1/3`（2行分の高さ）、`nth-child(2)`（「営業時間」見出し）を`grid-row:1`、`nth-child(3)`（office-hoursカード）を`grid-row:2`に配置。782px以下では`display:flex;flex-direction:column`に戻る（既存の意図的なレスポンシブ設計）。
- **見出しの非対称の原因を新たに特定**: `office-summary-block.php`の`render_summary_block()`はheading属性に対応しているが、`page_definitions()`側で`<!-- wp:astrea/office-summary /-->`にheading属性が渡されていないため、見出し無しで出力される。一方`office-hours`ブロックは`{"heading":"営業時間"}`が明示的に渡され、`<h2>営業時間</h2>`をカード外に出力する。この非対称な子要素構成（office-summary側は1要素、office-hours側はh2+divの2要素）を前提に、既存grid CSSの`nth-child`位置が設計されていることが判明した。
- **カード背景**: `.wp-block-astrea-office-summary`・`.wp-block-astrea-office-hours`とも`background:var(--wp--preset--color--surface);border-radius:...;padding:var(--wp--preset--spacing--medium);`で同一スタイル。
- **Disclosure親**: `astrea-office-page`グループの外側（sibling）。Construction 028-F時点では`layout:{"type":"constrained"}`のみで、`contentSize`(720px)幅・中央揃え。`astrea-office-page`自体は別ルールで`max-width:none`（wide/full相当）のため、幅の差が「左寄り感」の原因だった。
- **Footer spacing**: 通常のblockGapのみ。

---

## PHASE 2 — IMPLEMENT OPTION A

Order指示（「左右の高さを数学的に完全一致させることそのものを目的にしない」「大幅なcontent redesignはしない」）に従い、以下の対応範囲を決定した：

### A-1 Office Information（実装）

`theme/theme.json`の`.astrea-office-page.is-layout-flex>:nth-child(1)`ルールに`align-self:stretch`を追加し、あわせて`.wp-block-astrea-office-summary`自体（左カードの背景ボックス）に`display:flex;flex-direction:column;justify-content:center`を追加した。

```diff
-.astrea-office-page.is-layout-flex>:nth-child(1){grid-column:1;grid-row:1/3;}
+.astrea-office-page.is-layout-flex>:nth-child(1){grid-column:1;grid-row:1/3;align-self:stretch;}
+.astrea-office-page.is-layout-flex>:nth-child(1).wp-block-astrea-office-summary{display:flex;flex-direction:column;justify-content:center;}
```

- `align-self:stretch`により、左カードの背景ボックス自体がgrid cell（右列＝営業時間テーブルの高さ）いっぱいまで伸びる。**固定height・min-heightは一切使用していない**（コンテナの実高さに動的に追従）。
- `justify-content:center`により、カード内の中身（事務所名・住所・電話番号）が伸びた背景の中で垂直方向に自然に中央配置される。**ダミー情報の追加なし、内部コンテンツのサイズ変更なし**（配置位置のみ変更）。
- 782px以下のメディアクエリは変更していない（既存の`display:flex;flex-direction:column`のみが適用され、`align-self:stretch`は横方向のstretchとして扱われるが子要素は既に100%幅のブロック要素のため実質無変化。Mobile実機確認でも異常なし、PHASE 6参照）。

### A-2 Headings（見送り・現状維持）

見出しの完全対称化（office-summaryにも見出しを追加する）は、既存grid CSSの`nth-child`位置指定が「office-summary=1要素、office-hours=2要素（h2+div）」という現在の構成を前提にしているため、単純な属性追加では済まず、grid位置指定全体の再設計が必要になることが判明した。これは「大幅なcontent redesignはしない」というOrderの方針に反するため、**今回は見送り、現状維持とした**。事務所名（`.wp-block-astrea-office-summary-name`）は既に見出し相当のスタイル（`font-family:heading`・`font-size:large`）で表示されており、完全な非対称ではないと判断した。

### A-3 Live Demo Disclosure（実装）

`add-live-demo-disclosure.php`が生成するブロックMarkupに`"align":"wide"`属性と`alignwide`クラスを追加した。

```diff
-<!-- wp:group {"className":"astrea-demo-disclosure",...} -->
-<div class="wp-block-group astrea-demo-disclosure" ...>
+<!-- wp:group {"align":"wide","className":"astrea-demo-disclosure",...} -->
+<div class="wp-block-group alignwide astrea-demo-disclosure" ...>
```

WordPress標準のalign属性（`wide`）を使用しており、Theme側のカスタムCSS追加は不要だった（`theme.json`の`settings.layout.wideSize: 1200px`をコアが自動的に適用）。**Starter Site自体には一切変更を加えていない**（`add-live-demo-disclosure.php`のみの変更）。

### A-4 Footer Spacing（見送り・追加調整不要と判断）

PHASE 5/6の実地確認の結果、A-1/A-3の変更だけでFooter直前のバランスが十分自然になったため、追加のspacing調整（固定px等）は行わなかった。

---

## PHASE 3 — STARTER SITE CHECK

ローカル使い捨て環境（Construction 028/028-Fで確立した`wp_loaded`フックのテスト専用mu-plugin harness）を新規構築し、Starter 9-stepパイプラインのみを実行した。

- Desktop（1440px）: 左カード（ASTREA行政書士事務所）の背景が右列（営業時間）の高さまで自然に伸び、以前あった「左下の大きな空白」は解消された。中身は縦方向中央配置。
- Mobile（390px）: 782px以下で通常の縦積みに戻り、Construction 028-F時点と同じ自然な表示（regression 0）。
- unnecessary empty area: 0（左カードの空白は解消、代わりに背景色が自然に伸びているだけで不自然な余白ではない）
- Disclosure用ghost spacing: 0（Starter SiteにはDisclosure自体が存在しないため、削除後の余白影響なし）

**§3 判定: PASS**

---

## PHASE 4 — LIVE DEMO CHECK

同一環境で9-step完了後に`add-live-demo-disclosure.php`を実行した。

```
1回目: "Live Demo disclosure appended to page 27."          → count = 1
2回目: "Live Demo disclosure already present ... no change." → count = 1
```

- Disclosure count: 1（重複0）
- wide alignment: `alignwide`クラスが正しく付与され、Office Informationの2カラムセクションと同じ横幅で表示されることを確認。
- Office sectionとのwidth relation: 大幅に改善（以前は左カード幅程度、現在は2カラム全体幅）。
- Footerとのspacing: 自然（通常のblockGap）。
- 左寄り感の改善: 解消（幅がOffice Information全体と揃ったことで、視覚的に「途中で切れた」印象がなくなった）。
- disclosure duplicate: 0

**§4 判定: PASS**

---

## PHASE 5 — DESKTOP VISUAL

1440pxでスクリーンショットを取得した（Owner提示用）。

- **Starter Site**: 事務所概要 → Office Information（左カード・右営業時間、高さ揃い）→ Footer。Disclosureなし。
- **Live Demo**: 事務所概要 → Office Information（同上）→ Demo Disclosure（wide幅、2カラムセクションと同じ横幅）→ Footer。

Home page全体（Header/Hero/Services/Cases/Results/代表者紹介/Price/FAQ/Voices/Flow/Footer）も撮影し、regression 0を確認した（今回の変更は`.astrea-office-page`セレクタのみに影響するため、他セクションへの影響なし）。

**§5 判定: PASS**

---

## PHASE 6 — MOBILE VISUAL

390pxでスクリーンショットを取得した。

- Starter Site: 事務所情報 → 営業時間 → Footer（縦積み、自然）
- Live Demo: 事務所情報 → 営業時間 → Demo Disclosure → Footer（縦積み、自然）
- horizontal overflow: 0
- strange stretch: 0（`justify-content:center`はモバイルでは実質無効果 — カードの高さがコンテンツの自然な高さのみのため）
- excessive gap: 0
- heading hierarchy: natural
- disclosure readable: PASS
- Footer transition: natural

**§6 判定: PASS**

---

## PHASE 7 — STANDARD WORDPRESS EDITABILITY

今回の変更内容を確認した結果：

- custom hardcoded HTML依存: なし（`add-live-demo-disclosure.php`は既存のブロックMarkup生成パターンを維持し、標準の`align`属性を追加しただけ）
- JS layout hack: なし（CSS（theme.json styles.css）のみ）
- absolute positioning: なし
- content量依存の固定height: なし（`align-self:stretch`はコンテナの実高さに動的に追従するのみで、固定値や`min-height`は一切使用していない）
- demo-only Theme hack: なし（`theme.json`の変更は`.astrea-office-page`という**Starter Site自体が使うクラス**への変更であり、Live Demo専用ではない。Live Demo専用の変更は`add-live-demo-disclosure.php`側の標準`align:wide`属性のみ）
- WordPress標準UI: `align:wide`はBlock Editorの「幅を変更」ボタンでユーザーが選択できる標準機能そのもの。`align-self:stretch`はTheme側CSSであり、ユーザー操作を妨げない（Site Editor / Block Editorから通常操作できなくなる構造は作っていない）。

**§7 判定: PASS**

---

## PHASE 8 — REGRESSION

| セクション | 結果 |
| --- | --- |
| Header | PASS |
| Hero | PASS |
| Services | PASS |
| Cases | PASS |
| Results | PASS |
| Representative | PASS |
| Price | PASS |
| FAQ | PASS |
| Voices | PASS |
| Flow | PASS |
| CTA | PASS |
| Contact | PASS |
| Footer | PASS |
| Desktop | PASS |
| Mobile | PASS |

- Starter Disclosure: 0
- Live Demo Disclosure: 1
- Theme: 1.0.3（バージョン番号は変更していない — 後述PHASE 9参照）
- Core: 1.0.1（無変更）

`dev-snapshot.php`によるsnapshot比較（Construction 028-F最終状態 vs 今回）でも、テスト環境のポート番号以外の差分は0件だった。

**§8 判定: PASS**

---

## PHASE 9 — SCOPE DECISION

**Starter Site block/layout側の標準設定だけでは解決できなかった箇所と理由:**

1. **A-1（カード高さのstretch）はTheme CSS変更が必要だった。** WordPress Block EditorのUI上には「Grid内で高さをstretchする」という設定項目が存在せず、これはブロック属性（Starter Site側のpost_content markup）だけでは実現できない。CSS Gridの`align-self`はTheme側のスタイルシートでのみ制御可能なプロパティであるため、`theme/theme.json`の`.astrea-office-page`セレクタへの追加が必要だった。
2. **A-3（Disclosureのwide化）はStarter Site側（正確にはLive Demo専用スクリプト）の変更のみで完結し、Theme変更を要さなかった。** WordPress標準の`align:wide`ブロック属性を使うだけで、既存の`theme.json`の`wideSize`設定がそのまま適用されたため。

**Theme変更の内容:** `theme/theme.json`の`styles.css`文字列内、1箇所（`.astrea-office-page.is-layout-flex>:nth-child(1)`ルールへの`align-self:stretch`追加、および同セレクタ+`.wp-block-astrea-office-summary`への`display:flex;flex-direction:column;justify-content:center`追加）。**Theme 1.0.4等へのversion bumpは行っていない。** Owner Review前のcandidate扱いとする。

**Core変更:** 0件（原則通り、一切変更していない）。

---

## PHASE 10 — VISUAL COMPARISON（Owner提示用）

1. **Desktop Starter Site**（`028g-starter-about-desktop.png`）: 事務所概要ページ、Office Information（高さ揃い）→ Footer。Disclosureなし。
2. **Desktop Live Demo**（`028g-livedemo-about-desktop.png`）: 同上 + Demo Disclosure（wide幅）→ Footer。
3. **Mobile Starter Site**（`028g-starter-about-mobile.png`）: 縦積み、Footer自然。
4. **Mobile Live Demo**（`028g-livedemo-about-mobile.png`）: 縦積み + Disclosure、Footer自然。

**何を変更したか:**
- 左Office Informationカードの背景を、右Business Hoursカードと同じ高さまでCSSで自然に伸ばし（`align-self:stretch`）、中身を垂直中央配置。
- Live Demo専用DisclosureにWordPress標準の`align:wide`属性を付与し、2カラムセクションと同じ横幅で表示。

**なぜ改善したか:**
- 左右のvisual weightの差（Owner指摘の「左下の大きな空白」）が解消された。
- Disclosureが「途中で切れたような」左寄り表示から、Office Information全体と揃った自然な幅になった。

**Theme変更の有無:** あり（`theme/theme.json` 1箇所、`.astrea-office-page`専用ルール、Live Demo固有ではなくStarter Site自体に恒久的に適用される標準CSS）。Version bumpなし、Owner Review前はcandidate扱い。

**Starter/Live Demo差分:** Theme変更（A-1）はStarter Site自体にも適用される共通の改善。Disclosure wide化（A-3）はLive Demo専用スクリプト側のみの変更で、Starter Siteの構造には一切影響しない。

**残る違和感:** 見出し（「営業時間」がカード外、事務所名がカード内）の非対称はPHASE 2 A-2の判断により今回は現状維持。大きな違和感ではないと判断したが、Owner確認を仰ぐ。

---

## WordPress標準編集性・Theme/Core影響まとめ

- custom hardcoded HTML依存: なし
- JS layout hack: なし
- absolute positioning: なし
- content量依存の固定height: なし
- demo-only Theme hack: なし
- Theme Modification: **CANDIDATE**（`theme/theme.json` 1箇所、Owner Review待ち、version bumpなし）
- Core Modification: **NONE**

---

## Construction 028-G — Visual Review Ready

- Implementation: COMPLETE
- Starter Desktop: PASS
- Starter Mobile: PASS
- Live Demo Desktop: PASS
- Live Demo Mobile: PASS
- Starter Disclosure: 0
- Live Demo Disclosure: 1
- Responsive Regression: 0
- WordPress Standard Editability: PASS
- Theme Modification: CANDIDATE（`theme/theme.json`の`.astrea-office-page`ルールへの`align-self:stretch`等追加、1箇所。Version bumpなし）
- Core Modification: NONE
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Visual Review: REQUIRED

STOP

---

## Construction 028-G — FINALIZE / CLOSE

- Finalize Start: 2026-09-12（Owner Visual Review PASS受領後）
- Modifier: Chloe
- Owner Visual Review: **PASS**（4枚のスクリーンショットをOwnerが直接確認。Visual Option A: ADOPT、Office Information stretch: PASS、vertical center placement: PASS、Live Demo Disclosure wide: PASS、Starter/Live Demo Desktop/Mobile: 全てPASS、A-2 heading symmetry redesign: NOT REQUIRED、Additional visual adjustment: NOT REQUIRED、Theme candidate change: ACCEPTED、Core modification: NONE — Owner確認済み）

### PHASE 1 FINAL DIFF AUDIT（実git diffベース）

```
$ git rev-parse HEAD && git rev-parse origin/main
679518713b9fe92b3fcb278a39ad498a6cc5afa8（両者一致、分岐なし）
```

実際のgit diffで確認したConstruction 028-G対象差分：

1. `theme/theme.json` — `styles.css`文字列内、`.astrea-office-page.is-layout-flex>:nth-child(1)`ルールへの挿入1箇所のみ（`align-self:stretch;`、および同セレクタ+`.wp-block-astrea-office-summary`への`display:flex;flex-direction:column;justify-content:center;`）。Python差分ツールで前後のcss文字列を比較し、**この1箇所の挿入以外に一切の差分がないこと**を確認した（`styles.css`以外のtheme.jsonキーは完全一致）。
2. `docs/demo-assets/astrea-starter-site/scripts/add-live-demo-disclosure.php` — disclosureブロックMarkupへの`"align":"wide"`属性・`alignwide`クラス追加のみ（2行の差分、他は無変更）。
3. `docs/research/2026-09-12_construction_028g_office_information_visual_refinement.md` — 本報告書自身（新規）。
4. `docs/research/screenshots/028-G/` — Owner確認済みスクリーンショット4枚（新規、前回セッションでscratchpadから複製済み）。

`git diff --check`: 出力なし（空白関連エラー0件）。`git diff --stat -- core/`: 出力なし（Core差分0件）。

### PHASE 2 SCOPE ISOLATION

`git status --short --branch`のフル出力を確認し、以下を除外対象として確定した（`git add .`は使用せず、対象ファイルを個別に明示指定）：

- `HISTORY.csv`（Owner編集中）
- `docs/demo-assets/astrea-starter-site/yamada-demo-export.wxr`（Owner既存差分）
- `docs/research/screenshots/012〜`配下の削除群（Owner削除済み、復元せず）
- `docs/research/2026-09-11_construction_024...`等（他Construction、3件）
- `docs/research/screenshots/026/`（Construction 026分）
- `*.Zone.Identifier`（3件）
- `if-professional-astrea.code-workspace`（迷子ファイル）

### PHASE 3 THEME CHANGE CONFIRMATION

- unrelated theme.json changes: **0**（PHASE 1のPython差分比較で確認済み）
- global layout regression risk: **0**（セレクタが`.astrea-office-page`専用のため、事務所概要ページ以外に影響しない。Home page含む全ページのregression確認はPHASE 5参照）
- fixed height: **0**（`align-self:stretch`のみ、px固定値なし）
- min-height hack: **0**（使用していない）
- absolute positioning: **0**（使用していない）
- JS dependency: **0**（CSSのみ）
- demo-only selector: **0**（`.astrea-office-page`はStarter Site自体が使うクラスであり、Live Demo専用ではない）

**判定: PASS**

### PHASE 4 VERSION POLICY

```
$ grep -m1 "^Version:" theme/style.css
Version: 1.0.3
$ grep -m1 "Version:" core/astrea-core.php
 * Version: 1.0.1
```

Theme 1.0.3・Core 1.0.1とも無変更。Version bumpは行っていない。

### PHASE 5 FINAL LOCAL VERIFICATION（ローカルのみ・本番VPS不使用）

新規クリーン環境を再構築し、以下を最終確認した：

**Starter Site:**
- Disclosure: 0
- Office Information stretch: PASS（実地スクリーンショットで確認）
- Desktop: PASS
- Mobile: PASS
- Snapshot比較（Construction 028-Fベースライン）: ポート番号以外の差分0件

**Live Demo simulation（Starter 9-step + add-live-demo-disclosure.php）:**
- Disclosure: 1（1回目実行で追加）
- repeat run: 1（2回目「already present — no change」、count維持）
- duplicate: 0
- alignwide: PASS（`class="wp-block-group alignwide astrea-demo-disclosure"`を実HTML出力で確認）
- Desktop: PASS
- Mobile: 前回（本Construction内の実装確認セッション）確認済み、規模の変更なし

**Regression:** Header/Hero/Services/Cases/Results/Representative/Price/FAQ/Voices/Flow/CTA/Contact/Footer全てPASS（Home page全体スクリーンショットで再確認）。Snapshot比較（Construction 028-Gベースライン）でもポート番号以外の差分0件。

**Responsive Regression: 0**

### PHASE 6 WORDPRESS EDITABILITY

再確認の結果、全項目クリア：
- WordPress標準Block Editorを妨げない
- `align:wide`は標準block attribute（Block Editorの「幅を変更」ボタンでユーザーが選択できる標準機能）
- fixed heightなし
- JS hackなし
- absolute positioningなし
- content依存hackなし
- Demo-only Theme hackなし

**判定: PASS**

### PHASE 8 COMMIT対象・除外対象の最終確定

**コミット対象（Construction 028-G由来と`git diff`/`git status`で確認できたもののみ）:**
- `theme/theme.json`
- `docs/demo-assets/astrea-starter-site/scripts/add-live-demo-disclosure.php`
- `docs/research/2026-09-12_construction_028g_office_information_visual_refinement.md`（本報告書、新規）
- `docs/research/screenshots/028-G/`（Owner確認済みスクリーンショット4枚、新規）

**除外:** PHASE 2記載の通り（`HISTORY.csv`・`yamada-demo-export.wxr`・削除済みscreenshot群・他Construction報告書3件・Zone.Identifier×3・`screenshots/026/`・迷子ファイル）。

### PHASE 10 DEPLOY POLICY

**Deploy: NOT RUN。** Theme product codeに変更が含まれているため、repository closeとpublic deploymentを分離する。VPS・nginx・PHP・DB・robots.txtへの操作は一切行っていない。Live Demo本番はConstruction 028-G実装前の状態のまま。Production deploymentは別途Owner指示で行う。

---

## Construction 028-G — CLOSED

- Owner Visual Review: PASS
- Visual Option A: ADOPTED
- A-2 Heading Symmetry: NOT REQUIRED / CURRENT STRUCTURE ACCEPTED
- Starter Desktop: PASS
- Starter Mobile: PASS
- Live Demo Local Desktop: PASS
- Live Demo Local Mobile: PASS
- Starter Disclosure: 0
- Live Demo Disclosure: 1
- Disclosure Repeatability: PASS
- Responsive Regression: 0
- WordPress Standard Editability: PASS
- Theme Modification: ACCEPTED（`theme/theme.json`の`.astrea-office-page`ルール1箇所）
- Theme Version: 1.0.3（無変更）
- Core Modification: NONE
- Core Version: 1.0.1（無変更）
- Commit: `2e90b9d7fc5addfab8c504f72618c9653f56d5ed`
- Follow-up Commit: 本行を含む報告書更新のみのフォローアップコミット（push確認後に作成。ハッシュは完了報告および`git log`参照）
- Push: PASS
- Deploy: NOT RUN
- Production Live Demo: UNCHANGED
- Excluded Owner Changes: `HISTORY.csv`, `yamada-demo-export.wxr`（いずれも無変更のまま保持）
- Next Recommended Construction: Construction 029-A — ASTREA Starter Import Architecture / Product Specification

STOP
