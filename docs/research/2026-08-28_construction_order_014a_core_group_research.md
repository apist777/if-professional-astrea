# CONSTRUCTION ORDER 014A — core/group Editor Validation Warning — 緊急原因調査報告

**Status:** RESEARCH COMPLETE（製品コード変更なし。`docs/research/`・`HISTORY.csv`のみ更新）
**関連:** Construction 013施工報告（新規発見事項として記録）

---

## 結論（先出し）

**ASTREA Pattern側の不具合ではない。** 本調査の過程で当初「ASTREA Patternの`style.spacing.padding`に対応する`style="padding-top:...;"`インラインStyleが欠落している」という仮説を立てたが、**実機検証によりこの仮説は完全に否定された**——ASTREA Themeを完全に無効化し、WordPress標準の「Twenty Twenty-Five」テーマ＋ASTREA Coreも無効化した状態で、**属性を一切持たない最も単純な`<!-- wp:group --><div class="wp-block-group">...`でも同じ警告が再現した。** これはASTREA非依存の、WordPress 7.1環境における`core/group`（および`core/cover`）Block自体のValidation Logicの問題であり、**Category C（WordPress Core / Gutenberg Bug、この環境のWP 7.1固有）** に分類する。

Save Round Tripも実機で確認し、**Content Loss / Layout破壊は一切発生しない**ことを確認した——警告が出ている状態のページで無関係な他Blockを編集・保存しても、`core/group`Block自体の保存済みMarkupはバイト単位で無変更のまま維持される。

---

## 1. 対象Inventory

`theme/`全体を`tagName:"section"`で検索した結果、**4件**（Construction 013報告の記載と一致）：

| Pattern | Padding値 | backgroundColor |
|---|---|---|
| `theme/patterns/home-hero.php` | x-large | surface |
| `theme/patterns/home-flow.php` | large | surface |
| `theme/patterns/home-cta.php` | x-large | contrast/base |
| `theme/patterns/home-trust.php` | large | なし |

参考として、`tagName:"main"`（15箇所、全Template共通の`<main>`ラッパー）・`tagName:"article"`（8箇所、各Archive Templateの記事ラッパー）も確認したが、いずれも`style.spacing.padding`を設定していない。

## 2. 警告再現・警告文

`docs/research/screenshots/014a/warning-before-home-hero.png`（Construction 013で取得済み、再利用）で再現を確認済み。実際の警告文（英語UI、本環境の言語設定による）：

> **Block contains unexpected or invalid content.**
> [Attempt recovery] [⋮]

これはGutenbergの**Block Validation Error**（"Invalid Content"の一種）であり、Construction 013で扱った「このサイトは『X』ブロックに対応していません」という**Unsupported Block**警告（未登録Blockに対するもの）とは**明確に別種**である。Recovery Prompt（「復旧を試みる」ボタン）を伴う点は共通するが、原因は全く異なる。

## 3. Pattern Source Markup分析（当初仮説と、その反証）

### 3-1. 当初の仮説：`style.spacing.padding`のインラインStyle欠落

WordPress 7.1の実行中Editorで、`window.wp.blocks.parse()`/`window.wp.blocks.getSaveContent()`を直接実行し、ASTREA Pattern Sourceの`originalContent`と、現在登録されている`core/group`Block Typeの`save()`が期待する`expectedSaveContent`を比較した：

```
originalContent:      <section class="wp-block-group has-surface-background-color has-background"><p>x</p></section>
expectedSaveContent:  <section class="wp-block-group has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large)"></section>
```

ASTREA Pattern SourceのHTML側に`style="padding-top:...;padding-bottom:...;"`という**インラインStyle属性が存在しない**ことを確認し、これが原因ではないかという仮説を立てた。Hero/Flow/CTA/Trustの4件すべてで同じ欠落パターンを確認した。

### 3-2. 反証：この仮説通りに修正しても警告は消えない

上記の`expectedSaveContent`を**そのまま**HTML側へ手動で反映した完全一致Markup（`class`・`style`とも`getSaveContent()`の出力と完全一致）をテスト用ページへ投入し、実Editorで開いたところ、**「修正前」「修正後（完全一致）」の両方が同じ警告を示した**（`docs/research/screenshots/014a/editor-generated-reference-still-warns.png`）。つまり、**インラインStyleの有無は今回のWarningの原因ではない**。

### 3-3. さらなる切り分け：属性を一切持たない最も単純なGroup Blockでも再現

```html
<!-- wp:group -->
<div class="wp-block-group"><p>Bare group, no attrs at all</p></div>
<!-- /wp:group -->
```

この、`tagName`も`backgroundColor`も`style`も一切持たない最小限のGroup Blockでも、**同じ警告が再現した**。この時点で「ASTREA Patternの記述内容に起因する」という仮説（原因分類A）は完全に排除される。

## 4. Theme / Plugin非依存性の確認（最重要の切り分け）

以下の組み合わせで同じ最小Group Block Testページを開き、いずれも**同一の警告が再現する**ことを確認した：

| Theme | ASTREA Core | 結果 |
|---|---|---|
| ASTREA | 有効 | WARNING |
| Twenty Twenty-Five（WordPress標準同梱） | 有効 | WARNING |
| Twenty Twenty-Five（WordPress標準同梱） | **無効** | WARNING |

**ASTREA Theme・ASTREA Coreのいずれも無効化した状態でも警告が再現する**ことを実機で確認した——これはASTREA非依存、WordPress Core自体（このWP 7.1環境）の問題であることの決定的な証拠である。

## 5. parse / serialize Round Trip（PHP側）

PHP標準の`parse_blocks()`/`serialize_blocks()`によるRound Tripでは、ASTREA Pattern Sourceは**完全に一致**する（差分なし）——Construction 013でも同様の確認を行っており、今回も同じ結果だった。これは、**PHP側のBlock Parser/SerializerとJS（Editor）側のValidation Logicが別の処理系である**ことを裏付けている。PHP側は単なるコメント構文のParse/Serializeであり、JS側（Editorのみ）が行う「このBlock TypeのSave関数を再実行した結果と、実際に保存されているHTMLを比較する」という**Client側限定のValidation**とは全く別の処理である。

## 6. Gutenberg Client Validationの詳細確認

`window.wp.blocks.parse()`を直接呼び出す独立したテストでは、`isValid: false`が**あらゆるケース**（空のGroup、正しいクラス+Style付きGroup、`tagName:"main"`のGroup等）で一律に返る、という不可解な結果が得られた。この独立呼び出し方式の`isValid`判定は、実際のEditor初期化コンテキスト外では信頼できないシグナルであると判断し（調査手法上の教訓として記録）、**実際のEditor UI上で警告バナー（`has-warning`クラス・「Attempt recovery」ボタン）が表示されるかどうか**という、より直接的で実害に即した確認方法へ切り替えた。この判断が、§3-2/§3-3/§4の反証につながった。

## 7. Editor Recovery

「Attempt recovery」ボタンの存在を確認した。既存製品データへの影響を避けるため、テスト用ページ（本調査で作成し、調査完了後に削除済み）でのみRecovery操作の観察を行った——具体的なRecovery後の差分までは、時間の制約により全パターンを網羅的に記録していないが、Recoveryは一般に「Editor自身がその場でBlockを再シリアライズし直す」操作であり、次項のSave Round Trip確認（Recoveryを**行わずに**Saveした場合の結果）の方が実運用上重要と判断し、そちらを優先して確認した。

## 8. Save Round Trip（最重要）

テスト用ページで以下を実施した：

1. Group Block（警告あり）＋Paragraph Blockを含むページを作成。
2. Editorで開く（Group Blockに警告が表示されることを確認）。
3. **Recoveryは行わず**、無関係なParagraph Blockのテキストのみを編集。
4. Save（保存ボタンが正しく有効化され、クリックで保存成功）。
5. 保存後の`post_content`をWP-CLIで直接確認。

**結果：Group Blockの保存済みMarkupは、編集前と一字一句完全に同一のまま維持された。** Paragraph側の編集のみが反映され、Content Loss・attributesの消失・classの変化・innerBlocksの破壊は一切発生しなかった。

これはGutenbergの標準的な安全設計（無効化されたBlockはeditableとして扱わず、その`originalContent`をそのまま保持したままシリアライズする）が、本環境でも正しく機能していることを示している。**通常の編集フローにおけるContent Loss Riskは無い**と判断する。

## 9. tagName比較実験

当初はtagName:"section" vs "main" vs Editor自身が生成する正規Markupの3パターン比較を計画したが、§3-3の反証（tagNameを一切持たないGroup Blockでも再現）により、**tagNameの値自体は今回の警告の原因ではない**ことが判明したため、この軸での追加比較は不要と判断した。

## 10. 属性差分

同様の理由により、属性差分（layout/className/style/tagNameの組み合わせ）の追跡調査は、根本原因の特定という目的に対しては意味を持たないと判断した——**あらゆる属性の組み合わせ（無属性を含む）で再現する**ため、特定の属性の有無に原因を帰属させることはできない。

## 11. WordPress 7.1への影響確認

実行中のWordPress 7.1環境（`wp core verify-checksums`でコア ファイル改ざんなしを確認済み）において、`core/group`および`core/cover`の2 Block Typeで警告が再現し、`core/columns`・`core/column`・`core/paragraph`では再現しないことを確認した（§12参照）。`core/group`・`core/cover`はいずれも比較的新しいBlock Supports機能（`background`：backgroundImage/gradient、`shadow`、`dimensions`、`position.sticky`等）を`block.json`で宣言している共通点があり、これらの新しいSupports機能に関連するClient側Validation Logicに、このWordPress 7.1ビルド固有の不具合がある可能性が高いと推測する（確証ではなく推測として記録する——Gutenberg内部実装のさらなる詳細調査は本調査のScope外）。

Web検索は実施していない——命令書の指示どおり、まず実行中のWordPress 7.1自体を正本として調査し、十分な証拠（Theme/Plugin非依存の再現、Save安全性の実証）が得られたため、外部情報での補強を必要としないと判断した。

## 12. 他Blockへの影響範囲

同一のテストページで`core/columns`・`core/cover`を追加し、以下の結果を得た（`docs/research/screenshots/014a/other-core-blocks-affected-cover-and-group.png`）：

| Block | 警告 |
|---|---|
| `core/group` | **あり** |
| `core/cover` | **あり** |
| `core/columns` | なし |
| `core/column` | なし |
| `core/paragraph` | なし |

**この問題はASTREAが使用する`core/group`だけでなく、`core/cover`にも及ぶ、WordPress 7.1環境における広範な既知の（あるいは未報告の）Gutenberg側の問題である可能性が高い。** ASTREA自体は`core/cover`を現在使用していないため、この論点はConstruction 014Aの直接的なScopeではないが、記録として残す。

## 13. ASTREA Pattern生成経路

Setup経由でHOMEを組み立てるパス（`assemble_home_content()`によるPattern Registry読み出し＋連結）と、Site Editor / Post Editorから手動でPatternを挿入するパスの両方で、同一のPattern Source文字列がそのままBlock Editorへ渡されることを確認済み（Construction 009のArchitectureドキュメントで既に確認済みの事実——`assemble_home_content()`は単純な文字列連結のみで、Pattern内容への加工を一切行わない）。したがって、**Setup生成HOMEのみ／手動挿入のみという切り分けは意味を持たない**——警告は経路に関わらず、Pattern Source自体（あるいはさらに言えば任意のGroup/Cover Block）がEditorへ読み込まれた瞬間に発生する。

## 14. 原因分類

**C. WordPress Core / Gutenberg Bug**（このWP 7.1環境固有の`core/group`・`core/cover`のClient側Validation Logicにおける問題）と判定する。

判定根拠：
- ASTREA Theme無効化＋WordPress標準テーマでも再現（§4）。
- ASTREA Core Plugin無効化でも再現（§4）。
- ASTREA Patternの記述内容（tagName・style・className等）を一切問わず、属性なしの最小Group Blockでも再現（§3-3）。
- PHP側`parse_blocks()`/`serialize_blocks()`のRound Tripは完全一致——問題はJS Client側のValidation Logicに限局される（§5）。
- 同種のSupports機能を持つ`core/cover`にも同じ現象が及ぶ（§12）——ASTREA固有のMarkup問題では説明できない広がり方をしている。

## 15. Severity判定

**MEDIUM**と判定する。

- **HIGH（Content Loss/Layout破壊のリスク）には該当しない**——§8のSave Round Trip実証により、通常の編集・保存フローでContent Lossは発生しないことを確認済み。
- 一方で、**MEDIUM（ユーザーへの警告表示・Recovery操作の誘発によるユーザー混乱）には該当する**——ASTREA FREEの想定利用者（非技術者の士業事務所運営者）がHOME等のPatternを含むページをEditorで開くたびに、原因不明の「無効なコンテンツ」警告と「Attempt recovery」ボタンを目にすることになり、不安や誤操作（Recoveryクリックによる意図しないBlockの再構成、最悪の場合は「削除」の選択）を誘発しうる。

## 16. Release Blocking判定

**No（Release Blockingとしない）。**

理由：
- ASTREA側のPattern修正では解決できない（§3-2の反証により、Pattern SourceをどうGutenberg自身の`getSaveContent()`出力に一致させても警告は消えない）。
- 修正が既存Architectureを壊さない、という条件を満たす「ASTREA側修正案」自体が存在しない——原因がASTREAの外側（WordPress Core自体）にあるため。
- Content Loss Riskが無いことを実証済み。

命令書§16の「Release Blocking判定基準」（通常Editorで警告再現／**ASTREA Pattern側で修正可能**／修正が既存Architectureを壊さない／Content Loss Risk／初心者がRecovery/Deleteを選び得る）のうち、「ASTREA Pattern側で修正可能」という条件を満たさないため、他の条件を満たしていても採用しない。

## 17. 推奨対応方針

ASTREA側でのPattern修正は推奨しない（§16の理由のとおり、根本原因に対して無効なため）。推奨する対応は以下のみ：

1. **WordPress Core側への報告**：WordPress.org Trac／Gutenberg GitHub Issuesへ、`core/group`・`core/cover`のClient Validationに関する不具合として報告することを検討する（ASTREA Project側の対応というより、WordPress Coreコミュニティへの一般的な貢献としての位置づけ）。
2. **将来のWordPressマイナーアップデートでの自然解消を待つ**：Core側の不具合であるため、WordPress自身の将来のリリースで修正される可能性が高い。
3. **ドキュメント上の注記**：ASTREA運用者向けドキュメント（Post v1のUser Documentationタスクの一部として）に「Editorで一部セクションに『無効なコンテンツ』という警告が表示されることがありますが、データが失われることはありません。『復旧を試みる』は使用せず、そのまま保存して問題ありません」という一文を添えることを検討候補とする（014本体スコープでの必須事項ではない）。

命令書が避けるべきとした「独自Block化」「JSで警告抑制」「Core Block monkey patch」「Gutenberg内部API依存」「大規模Pattern再設計」のいずれも、そもそも今回はASTREA側の問題ではないため、検討の対象にすらならない。

## 18. 既存生成済みHOME対応

ASTREA側の修正が存在しないため、既存生成済みHOMEへの対応（自動書換え／案内のみ／自動修正）という論点自体が発生しない。既存HOMEは今回のCore/Gutenberg問題の影響を等しく受けるが、§8で確認したとおりContent Loss Riskはなく、実害は「Editorで開いたときに警告が出る」という点に限られる。

## 19. Theme Variations影響

Trust/Natural/Modern各Style Variationは、`theme/styles/*.json`でcolor/typography/button-radiusトークンのみが異なり、Pattern自体のMarkup構造は完全に共通（Construction 008以降一貫して維持されている制約）。したがって、**Style Variation固有の問題ではなく、3 Variationすべてで同一の現象が発生する**と推定される（§4の「ASTREA Theme無効化でも再現」という結果から、Theme側の要因が原因ではないことは既に確定しており、個別に3 Variationを切り替えての再現実験は本質的な情報を追加しないため実施していない）。

## 20. Core OFF影響

§4で確認済み：ASTREA Core Plugin無効化状態でも警告は同様に再現する。Pattern自体（HTMLコメントとしてのBlock Markup）はCore Pluginの状態と無関係にTheme側の静的ファイルとして存在し、Editorの検証はCoreのPHPコードとは独立してClient側で完結するため、この結果は予想どおりである。**責任範囲はASTREA Core・ASTREA Themeいずれにも属さない。**

## 21. Security

修正候補自体が存在しない（§17）ため、Raw HTML／Unsafe Attribute／Custom JS等を要求するSecurity Riskのある対応も検討していない。今回の調査で新しいSecurity上の懸念は発見されなかった。

## 22. 014本体への影響

**B（014 Release Prep内で確認すればよい）** と判定する——014本体開始前の修正は不要（ASTREA側で行う修正が存在しないため、そもそも「修正」という工程自体が発生しない）。ただし、014のUser-facing Documentation作成時に§17-3の注記を含めるかどうかは、014本体着手時にユーザー判断を仰ぐ事項として記録する。

## 23. 014本体Scope再確認

今回の調査結果により、Construction 014 Release Prepの予定Scope（WordPress日本語環境案内／readme.txt／LICENSE／screenshot.png／POT・i18n確認／Theme Check／Packaging・ZIP／Build・Release手順／Minimum WP/PHP確認／CI Matrix検討／User-facing setup/documentation／RC1準備）に**変更は不要**と判断する。強いて言えば、User-facing Documentation項目の中に§17-3の注記候補が追加され得る程度であり、Scopeの構造自体を変える必要はない。

---

## Test Strategy（該当なし）

ASTREA側の修正施工が不要と判定したため、修正を前提としたTest Planは提示しない。将来、WordPress Core側でこの問題が修正された場合、あるいはASTREA側で何らかの対応を行うことになった場合は、その時点で改めてTest Strategyを設計する。

## 新規Decision要否

**不要。** 今回の事象はWordPress Core側の問題であり、ASTREA自身の恒久的な設計判断を要するものではない。

## ユーザー判断が必要な事項

1. §17-3のDocumentation注記を014本体のUser-facing Documentationに含めるか否か。
2. WordPress Core（Trac/GitHub Issues）への不具合報告を、Project側として正式に行うか否か（行う場合、対外的なコミュニケーションを伴うため、実施の要否・実施主体をユーザー側で判断されたい）。

---

**本調査で製品コード（`theme/`・`core/`・`tests/`・`tools/`）への変更は一切行っていない。** 調査に使用したテスト用ページ・投稿・一時的に有効化した別Theme/Pluginの無効化状態は、調査完了後すべて元の状態へ復元したことを確認済み。調査中に誤って自動インストールされた無関係な2件のPlugin（"Back to top"、"Wavy Divider" — Block Inserterの「Available to install」欄への誤クリックが原因と推定）についても、発見後直ちに削除・復元した。
