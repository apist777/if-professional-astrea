# Construction Order 015A — ASTREA Visual v2 Design Research

RESEARCH / DESIGN SPECIFICATION ONLY。Product Codeは変更していない。設計仕様の正本は `docs/specifications/06_astrea_visual_v2_design_system.md`。本Reportは監査結果・参考調査・Regression Risk・実装計画をまとめる。

## 1. 監査方法

現在保持しているOwner Visual Acceptance Fixture（「ASTREA行政書士事務所」、実在しないFictional Fixture、Construction Order 014系で構築・保持済み）を使用し、Desktop（1440px）・Mobile（375px）でHOME／事務所概要／専門家Archive・Single／取扱業務Archive・Single／CASE Archive・Single／お客様の声Archive／料金／FAQ／お問い合わせを実機確認した。専門家Single・取扱業務Single・CASE Single・FAQ Archiveは本Order時点でOwner Visual Acceptanceの対象外だったため、本監査のために追加でScreenshotを取得した（`docs/research/screenshots/visual-v2-audit/`）。Product Codeは一切変更していない。

## 2. Current Visual Verdict

Install直後・Setup直後いずれの状態でも、**機能的には正しく完成しているが、「単純な縦積み」が支配的で、Professional Services Siteとしての情報密度・視覚的説得力が弱い**。RC1の技術品質（Block Validation、Responsive、Accessibility、SEO等）はConstruction 012〜RC1で確認済みであり、今回発見した問題はいずれもVisual Presentation層に限定される。

目標スコアに対する現状の暫定評価：Install直後 約55〜60点、Setup+実データ投入後 約60〜65点（80点/90点という目標に対し、明確なGapがある）。

## 3. 発見事項の分類

| # | 問題 | 分類 | 対象画面 |
|---|---|---|---|
| 1 | Headerの事務所名／Navigation／電話CTAが一体化して見えない | B | Header（全画面共通） |
| 2 | HOME Section間の視覚的リズム・階層差が弱い | B | HOME |
| 3 | Service/Professional/CASE/VOICE等Archiveが単純な縦積み | B | 各Archive |
| 4 | Archive Titleに"Archive:"（Localeにより「アーカイブ:」）が露出する | C | 各Archive |
| 5 | Single Page末尾にNext Action/CTAがない | B | Service/Professional/CASE Single |
| 6 | Professionalの人物性・Featured Image活用が弱い | B | Professional Archive/Single |
| 7 | RESULTSが数値資産として視覚化されていない | B | HOME |
| 8 | PRICEが単純列挙で料金情報として弱い | B | HOME／料金Page |
| 9 | VOICEがTestimonialとしてデザインされていない | B | HOME／VOICE Archive |
| 10 | Office Hoursが営業時間表として見えない | C | 事務所概要 |
| 11 | Contact FormがBrowser Default Formに近い | B | お問い合わせ |
| 12 | Footerに対しMain ContentのVisual Densityが弱い | B | Single系画面全般 |
| 13 | Typography/Spacing/Container/Section/Card等のDesign Systemが弱い | A（根本原因） | 全画面 |

注：#13は個別のVisual Issueというより、#1〜#12全ての根本原因（Design Tokenの粒度不足）である。A分類（Product-breaking）に該当する項目は無い——RC1のFunctional/Security/Data Architectureは健全であり、今回の発見は全てVisual Presentation層に限定される。#4・#10はWordPress標準機能・既存データ表示の調整で対応可能な軽微な項目としてC寄りに分類した。

D（Demo-only Enhancement）・E（PRO Candidate）に該当する新規発見は無かった（既存Post v1 Backlog Finding 6/7/8とは別軸の整理であり、本Orderで新たにD/E項目を追加してはいない）。

## 4. Before / Proposed（主要7画面）

### HOME

- **Current Problem**：Hero以外の全Section（Services/CASE/RESULTS/Professional/Price/VOICE）が、見出し＋縦積みTextという同一パターンで構成されており、情報の種類に関わらず視覚的に区別がつかない。
- **Proposed Direction**：Section種別ごとにVisual Role（Card Grid／Numeric Metric／Testimonial Card等）を割り当てる（設計仕様05節参照）。情報順序は変更しない。
- **Expected Effect**：スクロールしながら「今どの種類の情報を見ているか」が瞬時に分かるようになり、Professional Services Siteとしての完成度が上がる。

### Header

- **Current Problem**：事務所名・Navigation・電話CTAが同一Flex行に並んでいるが、Visual Weightが均一なため「まとまったHeader」に見えず、単なる横並びTextの集合に見える。
- **Proposed Direction**：Office Identity領域とNavigation+CTA領域を視覚的にグループ化する（設計仕様04節）。Markup構造自体は変更しない。
- **Expected Effect**：長い事務所名でも、Header全体としての一体感・信頼感が向上する。

### Archive（Service/Professional/CASE/VOICE共通）

- **Current Problem**：`get_the_archive_title()`由来の"Archive:"接頭辞が露出し、単純な見出し＋縦積みリストという「投稿一覧」的な見た目になっている。
- **Proposed Direction**：Post Type種別ごとのCard/Listing形式（設計仕様06節）。"Archive:"接頭辞はFilter Hookで安全に除去する方法を確認済み。
- **Expected Effect**：各Archiveが「業務一覧」「専門家紹介一覧」等、目的に応じたListing Pageとして機能するようになる。

### Single（Service/Professional/CASE共通）

- **Current Problem**：Title＋短い本文のみで終わり、Footerとの間に意味のない余白が生じている。次の行動導線が無い。
- **Proposed Direction**：Header Area／Content Area／Related・Next Action／Closing CTAの4段構成（設計仕様07節）。新規Semantic Dataは追加しない。
- **Expected Effect**：訪問者が個別の業務・専門家・事例を見た後、迷わずお問い合わせへ進める。

### Professional（Archive/Single共通）

- **Current Problem**：Featured Imageが活用されず、Name＋短い説明のみのText中心の表示で、「誰が対応するか」という安心材料として機能していない。
- **Proposed Direction**：Photo+Profile構造（設計仕様08節）。画像無しでも破綻しないPlaceholder方針を併せて定義。
- **Expected Effect**：担当者の「人物性」が伝わり、専門家サイトとしての信頼感が向上する。

### Price（料金Page／HOME内）

- **Current Problem**：Title＋金額の単純列挙で、料金体系としての構造が伝わらない。
- **Proposed Direction**：既存の`astrea_price_group`を活用したSection Grouping＋Price Card（設計仕様10節）。Offer Schema追加・Price GroupのPost v1 Backlog着手はいずれも行わない。
- **Expected Effect**：料金情報が「比較・検討しやすい表」として機能する。

### Contact

- **Current Problem**：Input・Label・ButtonがBrowser既定に近いStyleで、Formとして専門家サイトの一部という印象が弱い。
- **Proposed Direction**：既存Field構成を維持したまま、Container化・Spacing調整・Focus/Error Styleの明確化（設計仕様13節）。
- **Expected Effect**：問い合わせという「行動の最終ステップ」に相応しい、安心できるForm体験になる。

## 5. Visual Reference Research

2024〜2026年の法律事務所・会計事務所・専門家サービス系Webサイトの一般的傾向について、Webサイトへの個別アクセスは行わず、既存の一般的なDesign知見（Design Trend Report、業界一般に共有されている傾向）に基づき整理した。個別サイトの模倣（丸コピー）は行っていない。

| 傾向 | 分類 |
|---|---|
| 大きく余白を取ったTypography中心のHero | Adopt |
| 実績・対応件数等の数値を大きく見せるMetric Section | Adopt |
| 担当者の顔写真＋簡潔なProfile Card | Adopt |
| Card/Grid Layoutによる業務・強みの整理 | Adopt |
| Testimonial（お客様の声）をCard/Carousel形式で見せる | Adopt（Carousel等JS依存部分はAvoid、静的Cardのみ採用） |
| 過度なParallax/Scroll Animation | Avoid |
| 半透明Glassmorphism・多用されたGradient背景 | Avoid |
| SaaS Product的なDashboard風UI（Sidebar+Card+Chart多用） | Avoid（士業サイトの文脈に合わない） |
| 明確な情報階層・Whitespaceによる整理 | Long-lived Principle（流行に依存しない普遍的原則） |
| Mobile Firstな情報設計 | Long-lived Principle |
| Accessibility（コントラスト・Focus・見出し階層）の確保 | Long-lived Principle |

この整理は設計仕様書2節（Visual Direction）に反映済み。

## 6. Regression Risk（詳細）

| Risk領域 | 内容 | 対応方針 |
|---|---|---|
| Block Validation | Card/Grid化に伴い`core/group`のLayout属性・Nestingが変化する場合、新たなValidation Warningを生む可能性がある | 015B以降の実装時に、Construction 014Aと同様の「Editor自身が生成する現行Canonical Markup」との比較手法で検証する |
| Dynamic Block | `astrea/*` BlockのServer-side Render・Editor Placeholderは変更しない前提だが、周囲のGroup構造変更が`save()`の出力形と整合しているか要確認 | 各Dynamic Block周囲のWrapper変更時、Construction 013で確立したbyte-identical round-trip検証を再実施する |
| Core OFF | Visual v2 StyleはTheme側theme.json/Pattern側で完結させ、Core非活性時に崩れないことを確認する必要がある | 既存のTheme/Core独立性Testを015B以降でも継続実行する |
| Responsive | Card Grid化・Table化（Office Hours）は、Responsive時の折返し・Overflow Riskを新たに生む | 320/375/768/1440pxでのHorizontal Overflow確認を実装Phaseごとに継続する |
| Accessibility | Card化・Grid化で見出し階層（H2/H3）の使い方が変わる可能性、Focus順序の変化 | 既存確認済みのH1唯一性・見出し階層・Skip Link・Focus可視性のRegression Testを維持する |
| Theme Check / WordPress.org Rules | 新規Tokenの追加がtheme.json構造の妥当性を損なわないか | 実装後、RC1で確立した公式Theme Check実行フローを再利用する |
| 既存ユーザーContent | Setup生成済みHOME・既存Patternを持つサイトに対し、Visual v2が強制的に上書きするMigrationは行わない | Construction 009で確立した「既存ユーザーContentを勝手に上書きしない」原則を維持。Pattern自体の更新は新規Install/新規Setup生成分にのみ適用し、既存サイトのContentは自動変更しない設計とする（詳細な移行方針は015B以降で確定） |

## 7. RC1の扱い

現在のRC1（Theme/Core 1.0.0-rc1）は、Functional Baselineとして保存する。RC1 Tagはまだ作成しない。Visual v2完了後、RC2候補として再評価する。

## 8. Demo Strategy（設計確認）

本Orderでは実Demoを作成しない。Visual v2完成後の第一弾Showcase候補として、行政書士・税理士・社会保険労務士・司法書士を想定する。同一ASTREA Theme（Markup共通）を使用し、Color（Style Variation）・Photography・Copy・Service/Results/Price内容・CTA文言のみで業種ごとの世界観を変えられることを、設計仕様書（15〜16節）で確認済み。Demo専用のArchitecture（Demo限定Block/Template）は設計しない。

## 9. Implementation Plan（提案）

発注文の提案フェーズ分割は妥当と判断し、以下のとおり採用を推奨する。

| Phase | 内容 |
|---|---|
| 015B | Foundation / Design Tokens拡張 / Header v2 / Footer v2 |
| 015C | HOME Components（Section別Visual Role実装） |
| 015D | Archive / Single v2 |
| 015E | Office Hours / Price / Contact v2 |
| 015F | 3 Style Variations整合 / Responsive Polish |
| 015G | Visual Regression Test / Owner Acceptance再実施 |

各Phase完了時にPHPUnit/PHPCS/smoke-test/公式Theme Checkのフル回帰を実施し、CI Greenを確認してから次Phaseへ進む運用を推奨する。

## 10. スクリーンショット

`docs/research/screenshots/visual-v2-audit/` に、本監査で新規取得した3画面（取扱業務Single、対応事例Single、FAQ Archive）のDesktop/Mobile Screenshotを保存した。その他の画面は既存のOwner Visual Acceptance Screenshot（`docs/research/screenshots/owner-acceptance/`）を参照した。
