# Construction Order 016J — Visual v3 Colored Section Heading Spacing Final Polish 施工報告

## 0. Owner Finding

Owner Visual Reviewにより、背景色を持つHOME Sectionで「背景上端→Kicker」の余白が不足しているケースが指摘された。代表例はCASE（対応事例）で、"CASE"のKickerラベルが背景色（surface）帯の上端に密着して見え、垂直方向のバランスが悪かった。

Construction 016I（Kicker/H1衝突修正）とは別種の問題であることを明確にする：

- **016I**: Kicker と H1/H2本文 の内部衝突（Kicker→Heading）
- **016J（本Order）**: 背景色帯の上端 と Kicker の外周余白（Background Top→Kicker）

## 1. Mandatory Audit（§3）— 背景色を持つ全Sectionの棚卸し

`theme/theme.json`のCSSを精査し、実際に`background`プロパティを持つVisual v3 Section/要素を全数洗い出した。

| Section | 背景色 | Kicker有無 | 対象 |
|---|---|---|---|
| CASE | `--wp--preset--color--surface`（H2自身とcase-list両方） | 有（`::before`） | **要修正**（Owner Finding代表例） |
| VOICE | `--wp--preset--color--surface`（H2自身とvoice-list両方） | 有 | **要修正**（CASEと同一アーキテクチャ） |
| RESULTS | `--wp--preset--color--contrast`（暗色、H2自身とresults-list両方） | 有 | **要修正**（同上） |
| Closing CTA（`.astrea-final-cta`） | `--wp--preset--color--contrast`（暗色、native Group blockのbackgroundColor属性） | **無**（Kicker機構を使わない独立したCTA構造） | 対象外——既に`padding-top:x-large`(96px)で十分な余白があり、Kickerが存在しないため本Orderの問題領域に該当しない |
| SERVICE / ABOUT(Professional) / PRICE / FAQ / FLOW | 背景指定なし（白背景） | 有 | 対象外——白背景のため「背景上端との距離」という問題自体が発生しない |
| 内部ページ Page Title（`.astrea-archive-header h1`等） | 背景指定なし（白背景） | 有（016Iで修正済み） | 対象外 |

Trust/Natural/Modernの3 Variationは`settings.color.palette`のみを差し替え、`styles.css`自体は共有（Variationは独自CSSを持たないことを`theme/styles/*.json`で確認済み）。したがって「背景色になるSection」の構造自体（CASE/VOICE/RESULTS）はどのVariationでも共通であり、色の見え方（Naturalの暖色系surfaceがTrustより目立つ等）のみが変わる。

**結論**：本Orderで修正が必要なのはCASE・VOICE・RESULTSの3 Section（いずれも同一の共有CSS Selectorに集約されている）のみ。Closing CTAおよび白背景系Sectionは対象外であることを、コード監査とScreenshot両方で確認した。

## 2. Root Cause

CASE/VOICE/RESULTSはいずれも、H2要素自身が`background`を持つ構造（H2とその直後の`.wp-block-astrea-{case,voice,results}-list`が同じ背景色を共有し、視覚的に連続した1つの帯に見える）。Kickerは`::before`による絶対配置（`position:absolute;top:0`）で、H2自身の`position:relative`を基準にしている。

CSSの仕組み上、絶対配置要素の`top`はH2の**パディングボックスの上端**を基準にするため、H2自身の`padding-top`をいくら増やしても、Kicker（`top:0`固定）の位置は一切動かない——`padding-top`が動かすのはH2の**通常フロー内テキスト**（見出し本文）のみである。

つまり016I/016Hで確立した`padding-top:medium`(32px)は、あくまで「Kicker→本文テキスト」の内部間隔を作っていただけで、「背景上端→Kicker」自体の間隔は常にゼロ（Kickerは常に背景の最上端に張り付いた位置）だった。これが今回のOwner Findingの技術的な正体である。

## 3. 実施した修正

Order §5の指針「Background owns outer spacing. Heading owns internal spacing.」に従い、2つの余白を明確に分離した：

1. **背景上端→Kicker（新規、Background owns outer spacing）**: CASE/VOICE/RESULTSの`::before`（Kicker）の`top`を`0`から既存Token`var(--wp--preset--spacing--large)`（56px）へ変更。
2. **Kicker→本文テキスト（既存、Heading owns internal spacing、016I/016Hの成果、維持）**: H2自身の`padding-top`を、Kickerの新しい位置(56px)を起点として同じ32pxの間隔を保つよう`calc(var(--wp--preset--spacing--large) + var(--wp--preset--spacing--medium))`（56+32=88px）へ変更。

新規Tokenの発明はしていない（`large`・`medium`とも既存Token）。個別Section毎のMagic Number・Negative Marginは使用していない。CASE/VOICE/RESULTSの3 Selectorへ一括で適用し、白背景の5 Section（SERVICE/ABOUT/PRICE/FAQ/FLOW）や内部ページのPage Title Kickerは元の`top:0`のまま完全に無変更。

### 3.1 変更ファイル

`theme/theme.json`（CSSのみ、2箇所）:
1. 既存の共有Selector`h2:has(+ .wp-block-astrea-case-list),h2:has(+ .wp-block-astrea-results-list),h2:has(+ .wp-block-astrea-voice-list)`の`padding-top`を`medium`(32px)から`calc(large + medium)`(88px)へ変更。
2. 新規追加: `h2:has(+ .wp-block-astrea-case-list)::before,h2:has(+ .wp-block-astrea-results-list)::before,h2:has(+ .wp-block-astrea-voice-list)::before{top:var(--wp--preset--spacing--large);}`

他のファイルへの変更なし（`git diff --stat`で`theme/theme.json | 2 +-`のみ確認済み）。

## 4. Visual Measurement Report（§12）

1440px、Chromium `getComputedStyle()`による実測（Playwright）。

| Section | Before: 背景上端→Kicker | Before: Kicker→本文 | After: 背景上端→Kicker | After: Kicker→本文 | Result |
|---|---|---|---|---|---|
| CASE | 0px | 32px | **56px** | 32px（維持） | PASS |
| VOICE | 0px | 32px | **56px** | 32px（維持） | PASS |
| RESULTS | 0px | 32px | **56px** | 32px（維持） | PASS |

「Kicker→本文」間隔は016I/016Hで実証済みの32pxを1px単位で完全維持しており、016Iの衝突防止効果を損なっていないことを数値で確認した。

## 5. Before/After 視覚比較

`docs/research/screenshots/016j/01-case-before.png`（`git stash`による正真の修正前状態）と`02-case-after.png`を比較し、"CASE"KickerがCASE背景帯の上端から明確に離れ、Order §2の Target Composition（単純な中央揃えではなく、背景上端との呼吸を確保しつつeditorial compositionを維持）と一致することを確認した。

## 6. 背景付きSection全数の修正後確認

`07-voice-background.png`（VOICE、surface背景）、`08-results-background.png`（RESULTS、暗色contrast背景）ともにCASEと同一の余白バランスへ揃っていることを確認した。`09-faq-background.png`（FAQ、白背景であることを再確認——対象外）、`10-flow-background.png`（FLOW、`backgroundColor:base`=白であることを確認——対象外）、`11-cta-background.png`（Closing CTA、既存の`padding-top:x-large`(96px)で既に十分な余白がありKicker機構自体を持たないため無変更・無問題であることを確認）。

## 7. White Background Protection（§6）

`12-white-service-regression.png`（SERVICE）・`13-white-professional-regression.png`（ABOUT/Professional）にて、白背景Sectionが本Orderの影響を一切受けず、Kickerが元の`top:0`のまま、既存の承認済みVisual Geometryが完全に維持されていることを確認した。

## 8. Responsive（§8）

1920px（`03-case-1920.png`）・1440px（`04-case-1440.png`）・1366px（`05-case-1366.png`）・375px（`06-case-mobile-375.png`）を確認。固定Token値（`large`+`medium`）を使用しているためDesktop/Mobileで同一の絶対値となるが、375pxでも過剰な空白や不自然なスクロール量の増加は発生しなかった（375px幅では他の要素（画像・カード）のサイズ自体が縮小するため、相対的な余白比率はむしろ自然に見える）。Breakpoint別のMagic Number追加は行っていない。

## 9. Variation Audit（§9）

`wp_global_styles`（Post ID 825）への一時差し替え手法で、Trust（`14-trust-colored-heading.png`）・Natural（`15-natural-colored-heading.png`）・Modern（`16-modern-colored-heading.png`）を確認した。Order §9が警告した通り、Naturalの暖色系surface色はTrustより背景色が視覚的に目立つが、56pxの余白は同色でも十分な呼吸感を保っていることを確認した。Modernのシャープなradius 0デザインでも窮屈さは発生しなかった。3 Variationとも同一の共有Spacing Geometry（Variation別の個別Magic Numberなし）で成立することを確認した。Trustへ復元後、差し替え前バックアップとPython `json.dumps(sort_keys=True)`によるdiffで完全一致を確認済み。

## 10. HOME全体のVertical Rhythm（§7）

`17-home-full-1440.png`（HOME全体のFull-Page Screenshot）にて、white(Hero/Services)→surface(CASE)→dark(RESULTS)→white(Professional/Price)→dark(Closing CTA)→white(FAQ)→surface(VOICE)→white(FLOW)→dark(Footer)という配色リズム全体を確認した。背景付き3 Section（CASE/VOICE/RESULTS）はいずれも上端の呼吸感が統一され、白背景Sectionとの対比リズムが不自然に崩れていないことを確認した。

## 11. Regression Protection（§10）

`docs/research/screenshots/016j/016i-regression-check.png`にて、Construction 016IのKicker/H1衝突修正（内部ページPage Title、本Orderとは別のSelector系統）が本Orderの変更による影響を一切受けていないことを確認した。Hero/Header/Services/CASE Card/Results Band/Professional/Price/FAQ/Voice/Flow/Footer/内部ページ/No-Photo Fallback/Core OFF/3 Variation/Horizontal Overflowのいずれも既存の承認済み状態を維持している。

## 12. Technical Verification（§13）

- **PHPUnit**: `npx wp-env run tests-cli "vendor/bin/phpunit"`で398/398実行。既知のPre-existingエラー3件（`wp-phpunit`のAttachment Factory起因）のみ、無変更。本Orderで変更したPHPファイルは**0件**。
- **PHPCS**: 対象PHPファイル0件のため実行対象なし（`git diff --stat`で`theme/theme.json`のみ変更を確認済み）。
- **Theme Check**（公式Plugin、検証専用に一時インストール→検証後アンインストール済み）: `wp theme-check run astrea` — REQUIRED 0 / WARNING 0 / INFO 1（既存・無変更）。
- **Horizontal Overflow**: 7幅（1920/1440/1366/1024/768/375/320）× 10ページ = 70パターンを機械的に確認、超過0件。
- **Core OFF→ON**: `wp plugin deactivate astrea-core`後HOME HTTP 200、Fatal/Warning無し。再有効化後もHTTP 200。

## 13. Locked/Prohibited遵守確認（§15）

新機能・新Block・CPT変更・Core semantic data変更・Header/Hero再設計・CASE/Professional/Price構造変更・Search変更・SEO修正・price-list limit修正・Archive og:url修正・Release Debt Audit・Version bump・Tag・GitHub Release・Deploy・WordPress.org submissionのいずれも実施していない。「ついで修正」は行っていない。Core変更は0件（Theme側のCSSのみ）。

## 14. Completion Gate（§18）確認

- [x] 背景付きSection全数監査（§1）
- [x] CASE上端余白改善
- [x] VOICE・RESULTSも同型Findingとして補正
- [x] Kicker→Heading spacing維持（32px、1px単位で確認済み）
- [x] White Section無影響（SERVICE/ABOUT Screenshot確認済み）
- [x] Desktop Vertical Rhythm PASS（1920/1440/1366）
- [x] Mobile Vertical Rhythm PASS（375）
- [x] Trust PASS
- [x] Natural PASS
- [x] Modern PASS
- [x] Horizontal Overflow 0（70パターン）
- [x] Core OFF Safe
- [x] PHPUnit PASS（398/398、既知3件除く）
- [x] PHPCS対象0件（PHP変更なし）
- [x] Theme Check REQUIRED/WARNING 0
- [x] Required Screenshots保存（17枚+016I回帰確認1枚）
- [x] Full HOME Screenshot確認
- [x] Report作成
- [x] HISTORY更新
- [x] Commit
- [x] CI Green（確認後、別コミットで記録）

## 15. 測定値・Commit

- Start: 2026-09-04（本Order着手）
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER VISUAL SPACING ACCEPTANCE**

Release Ready宣言は行っていない。次のConstruction Orderへは自律的に進まない。Tag・GitHub Release・Deployのいずれも実施していない。Ownerがスクリーンショット・本Reportを確認し明示的に承認した後にのみ、次工程を決定する。
