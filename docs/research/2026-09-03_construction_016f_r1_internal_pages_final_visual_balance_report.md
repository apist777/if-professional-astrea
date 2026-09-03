# Construction Order 016F-R1 — Internal Pages Final Visual Balance / Owner Acceptance Corrections 施工報告

## 1. Order概要

Construction 016Fは「Internal Pages Visual v3」としてConditionally Acceptedとなり、HOMEを含むInternal Pagesの方向性自体はApprovedとなった。Owner Visual Acceptanceにて、以下4ページに限定したバランス上の指摘を受けた：

1. FAQ Archive — 右側の未使用余白が過大
2. Search — 結果カラムがデスクトップで狭すぎる／左に偏っている
3. Professional Single — 超Wide Desktop幅（1920px）で余白過大
4. 404 — 技術的には問題ないが視覚的に簡素すぎ、復帰導線が弱い

本OrderはこれらAllowed Targets 4件に限定したLimited Correction Orderであり、HOME/Header/Footer/Office/Price/Contact/Service Archive+Single/CASE Archive+Single/Professional Archive/VOICE Archive/Closing CTA/Resultsを含む既承認ページのRedesignは一切行っていない。

## 2. 各ページのOwner指摘・根本原因・実装内容

### 2.1 FAQ Archive

**根本原因**: FAQ Q&Aリストの`max-width`が`--astrea-v3-reading-max`（640px）に固定されており、1440px幅のページで content が左半分弱（約47%）しか占有せず、右側約53%が意図的な余白ではなく「古い狭幅Content制約が生き残ったように見える」状態になっていた。

**実装**:
- 新規CSS変数 `--astrea-v3-editorial-max: min(880px, 68%)` を`:root`へ追加。既存の`--astrea-v3-reading-max`（640px、Professional Field/Post Content等の本文可読幅として引き続き使用、無変更）とは明確に別トークンとして定義し、承認済みページの段落幅には一切影響しない。
- `.astrea-faq-archive-listing .wp-block-astrea-faq-item`の`max-width`を`--astrea-v3-reading-max`から`--astrea-v3-editorial-max`へ変更。
- 「Reposition」の追加施策として、FAQ見出し（H1）のtrailing rule（既存のKicker+罫線機構、016D-R2確立）を`body.post-type-archive-astrea_faq .astrea-archive-header h1::after{flex:1 1 56px;max-width:var(--astrea-v3-editorial-max);}`でFlex-growさせ、Content列の幅まで伸びるよう変更。これにより見出し自体がComposition全体の意図した幅を視覚的に示す「Editorial FAQ page with deliberate readable measure」を実現し、単なる幅拡大だけでなく「この余白は意図的である」という視覚的合図を追加した。
- Accordion JS・FAQPage Schema・2カラムGrid化は一切行っていない。既存Semantics/Dataも無変更。

### 2.2 Search Results

**根本原因**: FAQと同一の構造的原因（`body.search main.alignfull>.wp-block-query .wp-block-post-template`が`--astrea-v3-reading-max`固定）。1920pxでは特に顕著で、結果リストが画面の44%程度に収まり右側56%が完全な空白になっていた。

**実装**:
- FAQと同じ`--astrea-v3-editorial-max`トークンを`.wp-block-post-template`および`.wp-block-query-pagination`のmax-widthへ適用。
- 検索結果見出し（`main.alignfull>h1`、`body.search`スコープ）のtrailing ruleも同様にFlex-growさせ、`--astrea-v3-editorial-max`を上限としてComposition幅を視覚的に提示。
- 検索結果自体を新規カード化・フィルタ/ソートUIの追加は一切行っていない。単一カラムの縦スキャン性・SEARCH Kicker・既存WordPress検索挙動は無変更。

### 2.3 Professional Single — Wide Desktop（1920px）

**根本原因**: `.astrea-professional-single-header>.wp-block-group`（氏名・資格・trailing ruleを含むテキスト列）が`flex:2 1 360px`でGrow係数を持ち、写真列（`max-width:420px`固定）以外の残り全幅（1920pxでは約1300px超）まで無条件に拡大していた。テキスト自体は短い（氏名+資格の2行のみ）ため、Box自体を縮小しても不可視のFlex Item境界には視覚的効果が無く（背景色・枠線が無いため）、016E-R4 Professional Balanceで得た教訓（「短い左寄せテキストは幅拡大だけでは空白を消せない、位置が動くだけ」）を改めて確認する形になった。

**実装（実際に視覚効果があった施策）**:
- テキスト列は`flex:0 1 auto;width:auto;max-width:min(640px,100%)`へ変更し、不要なGrow自体は撤去（実質的な影響は無いが、レイアウト意図としては誠実）。
- **実効的な修正**: 写真の`max-width`を`420px`固定から`clamp(420px,36vw,700px)`へ変更。1920pxでは写真が約610-690px幅まで拡大し、テキストを「絶対的な幅」で扱うのではなく「写真という視覚的に伸縮可能な要素」でComposition全体の重量感を増やす方針へ転換した。これはOrderの禁止事項「テキスト行を無理に伸ばして画面を埋めない」に正面から従いつつ、「Composition全体を意図的に拡大する」という要求を両立させる。
- 1440pxでは`36vw`が約518pxとなり、既存420pxから緩やかに拡大するのみで、既存の品質・構図は実質的に維持されている（Owner要求「1440 = current quality or better」）。
- 375pxではFlex-wrapにより写真列が独立した行になり、`clamp()`のmin値（420px）はコンテナ幅により上書きされるため、モバイル構成には一切影響しない（Owner要求「375 = no regression」を実測で確認）。
- 4:5比率・Photo-left/Identity-right構成・Name/Qualification/Introduction/Career/Education/Affiliation/Registration information/Closing CTAの全項目・モバイルStacking・No-photo挙動は無変更のまま保持。

### 2.4 404 Page

**根本原因**: 見出し・説明文・ホームリンクのみで構成され、視覚的Densityが低く「復帰導線が弱いWordPress標準エラーページ」の域を出ていなかった。

**実装**（すべて既存の安全なASTREA/WordPress機構のみを再利用、Fixture固有ID・新規Core PHPコード・新規Block Bindings Sourceの追加は一切なし）:
- **トップページ**: 既存の`wp:home-link`をそのまま維持。
- **サイト内をさがす**: `wp:navigation`ブロック（`ref`属性なし、`theme/parts/header.html`のHeaderと全く同じ呼び出し方）を404ページ本文に追加。WordPress Core標準機能により、Header内のNavigationと同一のメニュー項目（取扱業務／専門家紹介／FAQ）がFixture ID非依存で解決される。実機確認で、この機構はastrea-core無効時にも正常に機能する（Core Pluginではなく WordPress Core自身のNavigation機能のため）。重複するLandmarkの曖昧さを避けるため、`"ariaLabel":"404ページからのご案内"`を明示指定し、Header側の無名Navigationと判別可能にした（実機確認済み）。
- **お電話でのご相談**: Header内の電話ボタンと全く同じBlock Bindingsパターン（`source:"astrea-core/office-profile"`, `args.key:"phone_tel"`/`"phone"`）を再利用。Core無効時はBindings APIの標準仕様どおりBlockの静的Fallbackテキスト（「お電話でのご相談」、Header側と同じ文言）へ安全に劣化することを実機確認済み（Fatal/Warning/Notice無し、既存のHeader側の挙動と完全に一貫）。
- 「Contact/お問い合わせページ」への直接リンクは、Fixture固有のPage ID（Core Generated Pagesの`astrea_core_generated_pages`オプションで動的採番されるID）に依存せず安全に解決する既存機構が存在しないことを確認したため、Order §5の指示（"If a destination cannot safely be resolved, omit it"）に従い意図的に省略した。既存Header内の「お問い合わせ」ボタン自体も現状`href="#"`のプレースホルダーであり（Locked/Frozen範囲、本Orderでは変更不可・変更していない）、これを404の新規導線としてそのまま複製することは「動作する復帰導線を追加する」というOrderの目的に反すると判断した。
- 3つの導線をKicker同様のGoldラベル（トップページ／サイト内をさがす／お電話でのご相談）付きで整理し、上部に区切り罫線、ページ全体の上下Paddingも拡大し、「WordPress標準エラーメッセージ+Homeリンク」から「ASTREA品質の復帰ページ」への引き上げを図った。
- 新機能・イラスト依存・JavaScript・アニメーション・Demo専用Assetの追加は一切行っていない。

## 3. 承認済みページへの影響確認

HOME・Service Archive・CASE Single・Professional Archive・VOICE Archive・Price・Office・Contactの計8ページを1440pxでスクリーンショット確認し、本Orderで追加した新規CSSトークン（`--astrea-v3-editorial-max`）・変更セレクタ（FAQ/Search限定Scope、Professional Single限定Selector、404限定Selector）のいずれも、これら承認済みページの見た目に一切影響していないことを確認した（`docs/research/screenshots/016f-r1/frozen-*.png`）。

## 4. レスポンシブ確認結果

既存の7幅×13ページ Overflow測定スクリプトを本Order変更後の状態で再実行し、**Horizontal Overflow = 0pxを全91パターンで再確認**（新規追加分を含め回帰無し）。加えて、対象4ページについて以下を個別確認：

- FAQ: 1440/1024/768/375で意図した挙動（Editorial幅→Tablet収縮→Mobile全幅1カラム）を確認。
- Search: 1920/1440/768/375で同様に確認。
- Professional Single: 1920（Wide Desktop、写真拡大確認）/1440（既存品質維持確認）/375（無回帰確認）。
- 404: 1440/375で3導線の折り返し・Overflow無しを確認。

## 5. Style Variation確認結果

Trust（既定）→Natural→Modernの順に対象4ページを実機切替確認。3 Variationとも構造は完全に共通のまま、Token（色・フォント）のみが変化することを確認し、コントラスト・レイアウトの破綻は無かった。最終的にTrustへ復元し、`wp_global_styles`（post ID 825）の内容が復元前と一致することをdiffで確認済み。

## 6. Core OFF/ON確認結果

`wp plugin deactivate astrea-core`後、FAQ/Search/Professional Single/404の全URLがHTTP 200（CPT依存の2ページはCore停止によりCPT自体が未登録となるため301/404を返すが、これはCore依存ページの既存の期待挙動であり、Fatal/Warning/Notice/Deprecated/Parse errorのいずれもレスポンスに含まれないことを確認）。404ページの電話Block Bindings・サイト内ナビゲーションはいずれもCore停止時に安全なFallback（静的文言／既存メニュー、Fatal無し）で動作することを実機確認した。`wp plugin activate astrea-core`で再有効化後、両URLのHTTP 200復帰を確認した。

## 7. Accessibility確認結果

- 対象4ページすべてでH1が1つのみ存在することを確認。
- 404ページに新設した`wp:navigation`（サイト内をさがす）には`"ariaLabel":"404ページからのご案内"`を明示指定し、Header内の無名Navigation・パンくずNavigationとLandmarkが判別可能であることを実機のDOM構造で確認した。
- Accessible Nameが空のリンク（テキストもaria-labelも無し）は対象4ページで0件。
- 404の電話ボタンはHeaderの既存パターンと完全に同一の実装（Accessible Name＝表示されている電話番号テキスト）であり、新たな後退は無い。
- 既存の見出し階層・パンくずSemantics・フォームLabel関連付け・Contrast・キーボード操作性は無変更。

## 8. 自動テスト結果

### 8.1 PHPUnit
`397 tests, 657 assertions, 3 errors`。この3件（`SeoMetaTest`×2、`SetupTest`×1、`wp-phpunit`のattachment factory内`Undefined array key "file"`）は、Construction 016Fの本文Reportで確認済みのとおり、Construction 016F自体を含む現行mainブランチのベースライン上に既に存在する既知のテスト環境問題であり、本OrderではPHPファイルを一切変更していないため、**PRE-EXISTING**（本Order由来ではない）と結論づける。

### 8.2 PHPCS
本Orderで変更したPHPファイルは0件（`theme/theme.json`・`theme/templates/404.html`のみ）のため対象なし。

### 8.3 smoke-test.sh
Step A〜K（Theme/Core独立性、Office Profile保存/読出/Core OFF-ON、Professional Archive並び順）はすべてOK。Step L/M/N（「写真は1件のみレンダリングされるはず」という前提）は、Construction 016F Reportで実測により原因特定済みの**PRE-EXISTING**な既存Fixture（実在の専門家3名の既存写真）とテスト前提の食い違いにより、本Orderでも同一に失敗した。本Orderでは製品コードを一切変更せず、この既知の制約をそのまま記録するに留めた（Order §12の指示に従い、無関係な製品コードの「サイレント修正」は行っていない）。

**Regression Protection（Order §10）遵守記録**: smoke-test.sh実行前に`astrea_core_office_profile`オプションのBackupを取得。実行後、Step L/M/Nの失敗により残留したテスト用専門家データ（Alpha/Bravo/Charlie/Draft Smoke、Smoke Test Photo）を直ちに検出・削除し、Office ProfileオプションをBackupから直ちに復元、`diff`でBackupとの完全一致を確認し、実サイトのHeader/Footer表示（「ASTREA行政書士事務所」）が正しく復元されていることも実機で確認した。Construction 016Fで発生した同種の復旧漏れ（smoke-test.shを複数回連続実行し、2回目以降のOffice Profile汚染を復元し忘れた事故）を、今回は単一実行＋即時検証・復元の徹底により再発させていない。

## 9. 変更ファイル

- `theme/theme.json`（CSSのみ）: `--astrea-v3-editorial-max`トークン新設、FAQ/Search限定Scopeのmax-width変更、FAQ/Search見出しtrailing ruleの限定Scope拡張、Professional Single Header内Photo/Text列の限定Scope調整、404専用Recovery CSS新設。
- `theme/templates/404.html`: Recovery Composition（3導線）のBlock Markup追加。
- 新規: `docs/research/2026-09-03_construction_016f_r1_internal_pages_final_visual_balance_report.md`（本Report）
- 新規: `docs/research/screenshots/016f-r1/`（Before/After・Variation・Core OFF・Frozen Page確認の各スクリーンショット）

## 10. 既知の残存事項

- Professional Singleの写真拡大（`clamp(420px,36vw,700px)`）は1920pxで大幅な改善をもたらすが、依然として氏名・資格テキストの右側に一定の余白が残る。これはOrderが明示的に禁止する「テキストを無理に伸ばす」対応を避けた結果であり、Owner自身が許容した016E-R4 Professional Balanceの前例（「固定50%を目的にすることを避けた結果としての余白は許容範囲」）と同種の判断として、正直に記録する。
- 404ページの「お問い合わせ」直接リンクは、Fixture非依存で安全に解決する既存機構が無いため意図的に省略した。将来、Core側にPage URL解決用のBlock Bindings key（例: `contact_url`）が追加されれば、このOmissionは解消できる可能性がある。
- smoke-test.shのL/M/N前提とFixtureの不一致（Construction 016Fより継続）は本Order範囲外。

## 11. Locked/Prohibited遵守確認

HOME・Header・Footer・Office・Price・Contact・Service Archive/Single・CASE Archive/Single・Professional Archive・VOICE Archive・Closing CTA・Resultsのいずれも本Orderでは変更していない（§3で実機確認済み）。新規Feature・Semantic Data Architectureの変更・HOME Visual v3の再オープンはいずれも行っていない。

## 12. 測定値・Commit

- Start: 2026-09-03 18:18 JST（Order受領直後、環境確認開始）
- End: 2026-09-03 18:42 JST（本Report作成・Commit直前の実測時刻、実際のCommit時刻は git log 参照）
- Duration: 約0h24m（実測ベース、Commit確定後にHISTORY.csvへ正確な値を記録）
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER FINAL VISUAL ACCEPTANCE**

Construction 016G、Final RC、Version変更、Tag付与、GitHub Release、Deploy、WordPress.org Submissionのいずれにも進みません。Owner Visual Acceptanceの明示的な確認をお待ちします。
