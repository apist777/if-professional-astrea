# ASTREA FREE v1 着工時正式仕様 Baseline

- 文書種別：Construction Baseline（着工時点の正式仕様統合）
- 確定日：2026-08-26（JST）
- 状態：**FROZEN** — 2026-08-26 最終着工前監査 GO判定時点の正式仕様を凍結保存したもの
- 基準とした文書：
  - `00_astrea_development_constitution.md`
  - `01_astrea_product_plan_v0.1.md`
  - `02_astrea_free_v1_specification.md`
  - `03_astrea_free_v1_user_journey.md`
  - `04_astrea_free_v1_preconstruction_decisions.md`（Decision 001〜021）
  - `docs/research/2026-08-25_astrea_free_v1_pre_construction_audit.md`（クロエ）
  - `docs/research/2026-08-25_astrea_technical_foundation_research.md`（コデミ）
  - `docs/research/2026-08-26_astrea_free_v1_final_pre_construction_audit.md`（最終監査・GO判定）

本書は00〜04の単なる転記・コピーではなく、実装担当（クロエ・コデミ）が本書を読めば着工基準を把握できるよう、正式仕様とDecisionを統合したものである。本書に新規の仕様判断は含まれていない。すべて既存の00〜04およびDecision 001〜021に基づく。

---

## 0. 本書の使い方

- 個別機能の詳細な文言（原文）は00〜04を参照する。本書はそれらの**要点統合と責任境界の明確化**を目的とする。
- 00〜04と本書の間に食い違いが生じた場合、**成立の新しい順（Decision 021 > 04 > 02/01 > 00）**を優先するが、着工後に本書と原典の間で解釈の相違に気づいた場合は、独自判断で処理せず報告する。
- 本書はFREEZE時点（2026-08-26）のBaselineである。着工後に仕様変更が発生した場合、本書を無記録で書き換えない。変更方針はセクション19を参照。

---

## 1. 製品思想（最上位原則）

出典：`00_astrea_development_constitution.md`

- ASTREA FREEはPROの体験版ではない。**FREE単体で本番運用可能な専門家サイトを完成できる**ことが最重要原則。
- 基本思想：「**FREEは、作れる。PROは、作ってくれる。**」
- PROの価値はFREEの制限解除ではなく、専門職知識に基づく作業の自動化。
- ユーザーの本業と関係ない作業（WordPress学習）を増やさない。一度入力した情報は再利用する。
- 「60点公開思想」：設定は必須 / 推奨 / 詳細の3段階。ただし**製品品質そのものを60点でよいとする意味ではない**。
- デザイン品質は無料であることを理由に下げない：「無料だから選ばれるテーマではなく、無料でなくても選ばれるテーマ」。
- ロックインを作らない：テーマ変更後も意味を持つデータをThemeへ閉じ込めない。Project-if側の障害で既存の公開サイトを止めない。

判断に迷ったときは、常にこの原則へ立ち返る（00 §24）。

---

## 2. FREE / PRO / 別Plugin の境界

出典：`00`, `01`, `02` §3・§31, Decision 015・016

| 区分 | 担当 |
|---|---|
| FREE共通基盤（全士業対象） | Theme + Core |
| 職種固有ページ生成・サイト構成判断・Navigation自動構築・大規模変更前Backup/Restore | Profession PRO（FREE v1では実装しない） |
| 予約管理 | 別Plugin（FREE v1では実装しない） |
| 高度なフォームビルダー | ASTREAの責任外 |
| 問い合わせの高度な検索・顧客管理・案件管理（CRM化） | FREE v1では行わない（一時保存・一覧・CSV Exportは行う。セクション6参照） |
| SEOスコアゲーム、FAQ/地域ページ等のSEO目的大量生成 | 行わない |
| Marketing素材の継続配信 | 将来のSubscription（FREE v1に先行実装しない） |

サポート方針（Decision 015）：FREE / PROともASTREA自身のBug・Security・標準機能不具合・必要なCompatibility問題は製品保守として修正する。個別環境調査・設定代行・サイト制作・カスタマイズ・第三者Plugin調整・SEOコンサル・個別操作指導等は標準では商品価格に含めない。不具合報告窓口は用意するが、それは個別サポート契約を意味しない。

---

## 3. Theme / Core / WordPress 責任境界

出典：`AGENTS.md` §6, Decision 002・013・021

| 領域 | 担当 |
|---|---|
| Templates / Template Parts / Patterns / Style Variations / Design System / Responsive・Accessibility表現 | ASTREA Theme |
| Office Profile、Service、Price、FAQ、CASE / RESULTS / VOICE、Contact処理、SEO / OGP / GA4設定と出力制御 | ASTREA Core |
| 通常の投稿・固定ページ・メディア等の標準コンテンツ | WordPress自身が正本（ASTREA独自の正本を重ねない） |

**Patternは業務データの正本にしない。** Pattern挿入後のBlock markupはページごとに複製されるため、Core側の値変更を自動反映する経路にはならない（Decision 002）。

---

## 4. ASTREA Core の位置付け（任意・公式推奨）

出典：Decision 021（2026-08-26 FINAL FIX）

**ASTREA Coreは任意Pluginとする。ただし、ASTREAの主要機能を利用するための公式推奨Pluginとして位置付ける。**

- ASTREA ThemeはCoreのインストール・有効化を強制しない。
- Coreが存在しない、または無効化されている場合でも、**Theme単体でWordPress Block Themeとして安全に動作し、PHP Fatal等を発生させない**こと。
- Office Profile、Service、Price、FAQ、Contact、SEO / OGP、Search Console支援、Coreデータ再利用、セットアップ支援等、ASTREA Coreが責任を持つ機能は、Coreを有効化した場合に利用可能となる。

基本原則：**「Coreは推奨する。しかしThemeを人質にしない。」**

実装時の必須テスト観点：

- Theme側のテンプレート・Pattern・Block登録処理は、Core非活性時にPHP Fatal・White Screen・編集画面のBlock登録エラー等を起こさないこと。
- Core非活性時にTheme単体でCoreデータを表示する箇所（Header / Footer等）は、空欄・プレースホルダー等への安全なフォールバックを行うこと。
- 初回有効化時、Core未導入であれば「Coreを推奨」する案内を提示してよいが、案内をスキップしてもTheme自体は機能停止しないこと。

---

## 5. 主要機能仕様サマリー

出典：`02_astrea_free_v1_specification.md` §6-14, §19-20

- **HOME**：固定テンプレートではなくPatternの組み合わせ。Hero / Trust / Services / Profile / Flow / Price / FAQ / CASE / Access / CTA等から必要なものだけ使用。0件・少数構成でも破綻しない。
- **SERVICES / PROFILE / FLOW / PRICE / FAQ / CASE・RESULTS・VOICE**：いずれもCoreを正本として一元管理し、複数画面から再利用する。件数（0件〜多数）を問わず成立するデザインとする。PRICEは自由記述含む複数の料金形態に対応（構造化データとの整合方法は実装フェーズで設計。セクション17参照）。PROFILEは「Professional Profile」としてOffice Profileと責任分離し、0〜複数人対応とする（2026-08-26 Decision 022、セクション21参照）。
- **ACCESS**：Coreに登録した住所・営業時間・臨時休業等を再利用し、Accessページで再入力させない。地図表示は「ページ内表示 / Google Mapsで開く / 地図なし」から選択可能。
- **Header / Navigation / Footer**：少数精鋭のPattern。スマートフォンHeaderは優先順位の再設計を行う（縮小コピーにしない）。事務所情報等はCoreから取得。
- **BLOG / NEWS / ARCHIVE / SEARCH / 404**：WordPress標準投稿機能を最大限利用。0件でも公開可能。404も含めDesign Systemの一部として設計する。

---

## 6. Contact

出典：`02` §15、Decision 003〜007

- 標準フォーム項目：名前・メール・電話・件名・問い合わせ内容・Privacy Policy同意。フォームビルダー化しない。入力→確認→送信→完了の一般導線。
- **メール送信**：WordPress標準の`wp_mail()`等を利用。Project-if独自メール配送サーバーは必須としない。
- **通知先アドレス確認**：登録・変更時に確認メール＋一回限りTokenの確認URLで受信可能性を確認する。未確認アドレスへは本番通知を送らない。
- **保存**：問い合わせは送信時点でCoreへ即時保存する（メール配送の成否に関わらず保存）。保存期間は10 / 30 / 60 / 90日から選択、初期値30日。保存期間経過後は自動削除。
- **管理画面**：問い合わせ一覧、未確認状態、未確認件数を表示。通知タイミングは即時 / 指定時刻まとめ通知（初期値：即時）。
- **CRM化の禁止**：高度な検索・顧客管理・案件管理は作らない。ただし、権限を持つユーザーはCSVで一括Exportできる。
- **Spam / Bot対策**：外部CAPTCHAは必須にせず、Nonce・Honeypot・Validation / Sanitization・連続送信抑制等の低負担な基本対策を標準搭載する。曖昧なSpam判定のみで正規問い合わせを無断削除しない。基本思想：「相談者には簡単、Botには面倒」。
- 添付ファイル機能はv1では扱わない（責任範囲外）。

---

## 7. SEO Foundation

出典：`02` §16-18、Decision 008・009・010・018

- 他SEO Pluginを原則必須とせず、WordPress標準機能（XML Sitemap含む）を最大限利用する。
- title / meta description / canonical / robots / OGP / Breadcrumb / 基本構造化データを対象候補とする。
- SEO設定は100点を追わせず、短時間で必要十分な状態にして集客活動へ進ませる。SEOスコアゲームは行わない。
- **OGP**：推奨サイズを画像アップロード時に案内。ページ個別画像のFallback順序を定義。ユーザーを採点するUIにはしない。
- **Search Console**：HTMLタグ方式による所有権確認の支援に限定する。**Search Console API / OAuth連携 / 検索順位分析は作らない。** GA4による所有権確認は案内可だが前提にしない。
- **Breadcrumb**：視覚的なBreadcrumb UI（Theme側表示）と、検索エンジン向けBreadcrumbList構造化データ（Core側JSON-LD出力）の両方を標準対応する。専用の独自データ正本は作らない。
- 他SEO Pluginおよび主要な第三者Pluginとの競合が予想される場合は検出・案内可能にする（セクション10参照）。

---

## 8. 初期セットアップ / ASTREA Dashboard

出典：`02` §21-22、Decision 016

- Theme / Core有効化だけでページ等を勝手に大量生成しない。
- ユーザーの明示操作で、ホーム・事務所概要・取扱業務・料金・FAQ・問い合わせ等の基本ページをまとめて生成可能にする（海外デモデータImportの思想ではなく「ユーザー自身のサイトを作り始められる状態」の生成）。
- 再実行しても重複生成・既存ページの無断上書きをしない。
- FREEは全士業共通基盤。士業固有の専門ページ・専門知識・高度な自動生成はProfession PROの価値領域。
- Privacy Policyは法的完成文章を保証せず、WordPress標準Privacy機能を活用した設定・編集を案内する。
- セットアップは何度でも再度開ける。途中終了可能、不足分だけ後から追加可能。全項目完了を公開条件にしない。
- 設定リセットとユーザーデータ削除は分離する。
- Dashboardは設定状況を点数化せず、完了 / 推奨 / 任意のチェックリストで案内する。

---

## 9. Accessibility / Responsive / Performance

出典：`02` §23-25、Decision 017

- **Accessibility**は追加オプションではなく標準品質として扱う。Keyboard操作、Focus表示、Semantic HTML、見出し階層、Form Label、alt、Contrast、Skip Link、適切なARIA、`prefers-reduced-motion`等を品質要件とする。**完全準拠の無条件保証はしない**が、これを品質を下げる口実にはしない。
- **Responsive**はPCデザインの縮小ではなく、画面サイズごとに情報優先順位・レイアウトを再設計する。端末固有CSSの増殖を避ける。
- **Performance**は高速化Pluginを前提にせず、Theme + Coreの標準構成自体を軽量にする。未使用の外部サービス処理を読み込まない。Core Web Vitals（現時点でLCP / INP / CLS）等を確認するが100点取得ゲームにはしない。

---

## 10. 第三者Plugin共存方針

出典：Decision 018

- Plugin Ecosystemとの共存を前提とし、ユーザーを囲い込まない。
- 主要な既知Pluginとの実際の競合が予想される場合は検出・案内可能にする。必要に応じてASTREA側とPlugin側のどちらの重複機能を使うか選択できる設計を検討する。
- **未知のPluginを推測で制御しない。** 競合事例が判明したものはUpdateで既知リストへ追加する運用とする。
- 第三者Pluginすべての組み合わせについての動作保証はしない。

---

## 11. データ保持・Uninstall

出典：`02` §27-28、Decision 004・019

- Core無効化ではデータを削除しない。再有効化で再利用可能。
- Plugin削除時も、初期状態ではCore所有データ（Office Profile、Service、Price、FAQ、問い合わせ等）を保持する。
- **完全削除はユーザーが明示的に選択した場合のみ**実行可能とし、不可逆性と削除対象を明示したうえで確認を要求する。
- ASTREAが生成したものであっても、通常の固定ページ・投稿・Media等のユーザーコンテンツはCore Uninstallに巻き込んで自動削除しない。
- 問い合わせデータの「保存期間経過後の自動削除」（時間ベース、Decision 004）と、Uninstall時の「完全削除」（ユーザー操作ベース、Decision 019）は**別の削除経路**として区別する。
- ロックイン防止：ASTREA固有Shortcode等を不必要に乱造せず、WordPress Core Blocks・標準HTML・標準投稿データを利用する。

---

## 12. Compatibility（PHP / WordPress）

出典：`02` §29、Decision 020

- **PHP**：8.3以上を1.0の初期最低ラインとする。新PHP対応の追加と旧PHP対応の終了は別々に判断し、新対応追加のみを理由に旧対応を即座に切らない。ASTREA自身のPHP互換性は製品保守対象、第三者Plugin固有のPHP互換性はサポート対象外。
- **WordPress**：7.0以上を初期Target、7.1を主開発・Test基準とする。標準API利用上7.0対応が明確な不利益となる場合は1.0公開前に7.1以上へ引き上げ可能。
- CoreのDB構造変更にはSchema Version等によるMigration管理を導入する（具体的機構は実装フェーズで設計。セクション17参照）。
- 非推奨機能は突然削除せず、移行期間・代替手段を設ける。

---

## 13. Release / Update

出典：`02` §26、Decision 001・012・014・020

- **配布**：WordPress.org公式ディレクトリ掲載を積極的に目指す。掲載要件のためにFREEの製品思想を曲げない。原則単一コードベース。Profession PROはWordPress.org掲載を目的とせず、Project-if独自配布とする。
- **技術識別子**：Theme slug/text domain = `astrea`、Core = `astrea-core`、PHP namespace = `Astrea`、prefix = `astrea_`、Block namespace = `astrea/*`。ThemeとCoreは独立Version管理、Semantic Versioning、正式初回Releaseは1.0.0。
- **Update方針**：通常の設定・Coreデータを不必要に破壊しない。Templateへユーザーデータを直接固定しすぎず、Styles / Core / Binding / WordPress標準設定へ適切に分離する。Site Editor等でのユーザーカスタマイズはWordPress標準挙動（DB保存版がThemeファイルより優先）を尊重し、強制上書きしない。
- ASTREA自身のBug・Security・必要なCompatibility問題は修正し最新版として提供する。
- WordPress.org掲載版はWordPress標準Update経路を基本とし、FREE v1で巨大な独自Update基盤を作らない。
- HISTORY.md＝開発チーム向け履歴、CHANGELOG＝ユーザー向けの意味のある追加・改善・修正、という役割分担とする。
- 基本思想：「普通のWordPress Themeとして普通に更新できる」。

---

## 14. Core → Theme 表示Architecture（実装時に必ず守るArchitecture）

出典：Decision 013・021

基本方針：**「Coreが覚える、Blockがつなぐ、Themeが見せる、Patternが並べる」**

- 単純な再利用値（電話番号・事務所名等の単一値）：Block BindingsなどWordPress標準機構を優先。
- 構造・処理を伴うもの（一覧・条件分岐・件数可変等）：Dynamic Block等を用途に応じて利用。
- 具体的な使い分けの詳細は実装設計時に最適解を選択する（P0外、セクション17）。
- **ThemeからCore内部実装へ密結合しない。独自Frameworkを不必要に作らない。**
- Core無効化時、Block Bindingsが接続されたBlockはPHP Fatalを起こさず、既定値・空欄等へ安全にフォールバックすること（セクション4と同一要件）。
- Core側の保存API（Sanitization / Validation含む）は単一の入口に統一し、手入力・将来のPRO自動生成の双方から同じ経路を通す（将来PRO阻害リスクの回避）。

---

## 15. 技術識別子

出典：Decision 012（セクション13にも記載、参照用に再掲）

| 項目 | 値 |
|---|---|
| ブランド | ASTREA |
| Theme slug / text domain | `astrea` |
| Core Plugin slug / text domain | `astrea-core` |
| PHP namespace | `Astrea` |
| 独自prefix | `astrea_` |
| Block namespace | `astrea/*` |
| Versioning | ThemeとCore独立管理、Semantic Versioning、正式初回Release 1.0.0 |

---

## 16. 開発・CI環境

出典：Decision 020

- 標準ローカル環境：WSL2 + Docker + `@wordpress/env`。
- 再現可能な構築手順をRepositoryへ保持する。
- 必要十分な自動検査とGitHub Actions等によるCIを導入する（テスト自体を目的化しない）。
- Release前には自動Testだけでなく実動作確認も行う。
- Release Checklistを用意する。

**着工直後に実行すべき事項**（Decisionではなく実行待ちの事実。最終監査セクション6参照）：

- Gitリポジトリの初期化（現時点で`.git`が存在せず、バージョン管理が機能していない）。
- `package.json` / `composer.json` / `phpcs.xml` / `.wp-env.json` / CI workflow の作成。

---

## 17. 実装フェーズへ委ねる技術詳細（P0外・非確定事項）

以下は着工を妨げない事項として、正式仕様レベルでは方針のみ確定し、各機能の設計着手時に決定する。無理にP0へ格上げしない（最終監査 §4.3、04文書「残る確認事項」2-6）。

1. Schema Version / Migrationの具体的な保存場所・実行タイミング（方針＝Versioned Migration方式、Decision 020）。
2. Block Bindings / Dynamic Blockの個別Block単位での使い分け（方針＝Decision 013）。
3. PRICE（自由記述）と構造化データ（schema.org Offer等）の整合方法。
4. Pattern と Style Variation（Trust / Natural / Modern）の共有方式。
5. Service / FAQ / CASE等が0件のときの空状態UIパターンの統一。
6. 営業時間・臨時休業データモデルの詳細（将来の予約Pluginとの共有を見据えた設計）。

---

## 18. 着工前監査の結論（引用）

`docs/research/2026-08-26_astrea_free_v1_final_pre_construction_audit.md` より：

> 最終判定：**GO**
> Decision 001〜020により、クロエ・コデミ双方の過去監査でP0扱いだった事項はすべてCLOSEDとなった。

その後、2026-08-26のFINAL FIX（Decision 021：ASTREA Coreの位置付け）により、当時セクション5で「非ブロッキングだが正式な一文が必要」としていた唯一の残存事項もCLOSEDとなった。

**2026-08-26時点で、ASTREA FREE v1の着工を阻害する未決定事項は存在しない。**

---

## 19. 変更管理方針

本書は2026-08-26時点のConstruction Baselineとして凍結する。

着工後に本書の内容へ変更が必要となった場合：

- 本書を無記録で書き換えない。
- 変更内容・理由・影響範囲を`HISTORY.md`へ記録し、追跡可能な状態を維持する。
- 変更が既存のDecision（001〜021）の意味を変えるものである場合、新しいDecision番号（022以降）として`04`へ追記し、本書へ反映する。
- 過去の着工時仕様（本版の内容）が分からなくなるような処理は行わない。

---

## 20. 未実装の確認

本書作成時点で、`theme/`・`core/`・`tools/`ディレクトリへの実装は行われていない（`.gitkeep`のみ）。本書はTheme / Core実装の着工可否判定に用いる基準文書であり、本書の作成自体は実装開始を意味しない。実装着手は別途の正式な着工命令による。

---

## 21. 追記（2026-08-26, Decision 022 — 士業法人・複数専門家対応）

本書は2026-08-26時点のBaselineとして凍結済み（§19参照）であるため、本追記は既存セクションの本文を書き換えず、末尾への追加という形で反映する。

CONSTRUCTION ORDER 002完了後、同日中に以下がDecision 022として正式FIXされた（`04_astrea_free_v1_preconstruction_decisions.md`参照）。

- ASTREA FREEは個人の士業事務所に加え、士業法人・複数の専門家が所属する事務所も正式な対象とする。
- Coreのデータ責任を **Office（Office Profile）** と **Professional Profile** に明確分離する。Office Profile＝事務所・法人そのものの情報（Construction Order 002で実装済み、変更なし）。Professional Profile＝所属する専門家個人の情報（資格・肩書・経歴・写真等）で、0〜複数人（Office 1 : Professional Profile 0..N）を扱える構造として将来実装する。
- CTA・相談方法、およびACCESSページ固有情報（最寄駅・徒歩時間・駐車場等）は、Office Profile / Professional Profileのいずれにも含めない別責任とする。所在地そのものはOffice Profileを正本として再利用する。
- Construction Order 002報告書で報告されていた「Office Profile項目範囲の要確認事項」は、本Decisionにより**CLOSED**となった。

この追記により、本書セクション5（PROFILE）・セクション2（FREE/PRO境界の前提となる想定顧客像）の実質的な内容は、Decision 022の内容へ更新されたものとして扱う。将来Professional Profileを実装するConstruction Orderでは、本セクションおよび`04_astrea_free_v1_preconstruction_decisions.md`のDecision 022を基準文書とする。

---

## 22. 追記（2026-08-26, Decision 023・024 — 代表者情報の正本 / Core無効時URLの保証範囲）

CONSTRUCTION ORDER 003完了後、同日中にCONSTRUCTION ORDER 003Aとして以下が正式FIXされた（`04_astrea_free_v1_preconstruction_decisions.md`のDecision 023・024参照）。既存セクションの本文は書き換えず、末尾への追加として反映する。

**Decision 023（代表者情報の正本）**

- 代表者情報の正本を**Professional Profile**とする。Office Profileの`representative_name`は廃止した。
- Professional Profileに代表者識別フラグ（`is_representative`）を追加した（Construction Order 003Aで実装済み）。具体的な肩書テキストは既存の「資格・肩書」項目を使う。
- 複数の専門家を代表者として同時に指定できるかどうかは一意制約を設けていない（要確認事項として残存。`04`文書参照）。
- 既存のOffice Profile `representative_name`データは、Schema v1→v2 Migrationにより`legacy_representative_name`として保全し、自動的な人物生成・自動flagづけは行わない。管理画面に案内を表示し、人間の判断を仰ぐ。
- Construction Order 002・003報告書の「Office Profile項目範囲」に関する要確認事項は、本Decisionにより**CLOSED**となった。

**Decision 024（Core無効時のCore所有URLの保証範囲）**

- 本書セクション4・14（Core無効時の要件）が求める保証は、「Theme正常動作」「Fatal/Warning/Notice無し」「壊れたMarkup無し」「Core所有データの残留表示無し」「再有効化後の正常復帰」の5点に限定される。**Core所有URLが必ずHTTP 404を返すことは保証対象に含まない。**
- Construction Order 003で確認された「Core無効時に`/professionals/`がHTTP 200のFallbackになる」挙動は、上記5点をすべて満たすためBlocking Bugとして扱わない。
- このHTTP Status是正のためだけに、ThemeへCore所有CPT・URL構造の知識を持たせる実装は行わない（Decision 021の原則を優先）。
- Construction Order 003報告書の要確認事項2は、本Decisionにより**CLOSED**となった。

将来Professional Profile・代表者関連機能を拡張するConstruction Orderでは、本セクションおよび`04_astrea_free_v1_preconstruction_decisions.md`のDecision 023・024を基準文書とする。

---

## 23. 追記（2026-08-26, Decision 025 — Professional Profile代表者の人数制約）

CONSTRUCTION ORDER 004着工にあたり、Decision 023で残されていた要確認事項が以下のとおり正式FIXされた（`04_astrea_free_v1_preconstruction_decisions.md`のDecision 025参照）。既存セクションの本文は書き換えず、末尾への追加として反映する。

- Professional Profileの「代表者」指定（`is_representative`）は、**0〜複数人**を正式に許可する。単一代表者への一意制約は設けない。
- Construction Order 003A時点の実装（一意制約なし）をそのまま正式仕様として承認する。製品コード変更は不要。
- `docs/research/2026-08-26_construction_order_003a_report.md`の残存要確認事項、および本書「本書に基づき残る確認事項」旧項目7は、本Decisionにより**CLOSED**となった。

---

## 24. 追記（2026-08-27, Decision 026 — SEO Foundation：構造化データ方針の確定）

CONSTRUCTION ORDER 006着工にあたり、本書セクション17項目3で実装フェーズへ委ねていた事項が以下のとおり正式FIXされた（`04_astrea_free_v1_preconstruction_decisions.md`のDecision 026参照）。既存セクション（セクション5, 7, 17）の本文は書き換えず、末尾への追加として反映する。

- **Price → Offer / PriceSpecification は自動出力しない。** ASTREA Priceの自由記述モデル（固定額／○円〜／月額／時間制／無料／個別見積／自由表記を単一の自由記述フィールドで扱う設計）は、Offer/PriceSpecificationとして意味的に正確かつ一貫した構造化データを安全に自動生成できる性質を持たないため。既存Priceデータモデルへの変更はない。
- **Office Profileの通常週次営業時間（`business_hours.weekly`）は`openingHoursSpecification`への対応を許可する。** ただし`business_hours.exceptions`（臨時休業等）は変換対象外とする。新規入力項目は追加しない。
- **SEO Plugin検出の初期対象**をYoast SEO・All in One SEO・Rank Math・SEOPressの既知シグネチャに限定する（Decision 018の運用方針の適用）。
- **FAQPage JSON-LDはFREE v1で実装しない。** Google検索のFAQ Rich Resultが2026年5月7日付で完全廃止され実益が失われたため。FAQ意味データ・通常HTML表示は維持する。
- **Office Profile → `Organization`、Professional Profile → `Organization.employee`内の`Person`を基本Schema型とする。** 一般用途の`ProfessionalService`（Schema.org側でdeprecated）は採用せず、特定士業への決め打ちも行わない。代表者フラグはJSON-LD上の独自プロパティへ変換しない。
- 本書セクション17項目3、`docs/research/2026-08-26_construction_order_004_research.md`§6の要確認事項、`04_astrea_free_v1_preconstruction_decisions.md`「残る確認事項」項目3は、本Decisionにより**CLOSED**となった。

将来SEO Foundationを拡張するConstruction Orderでは、本セクションおよび`04_astrea_free_v1_preconstruction_decisions.md`のDecision 026を基準文書とする。

---

## 25. 追記（2026-08-27, Decision 027 — Setup Page 一括生成の対象範囲確定）

CONSTRUCTION ORDER 007着工にあたり、Decision 016の一括生成対象ページ例示が以下のとおり具体化された（`04_astrea_free_v1_preconstruction_decisions.md`のDecision 027参照）。既存セクション（セクション19「変更管理方針」に基づく本Decision導入部の記載方針）の本文は書き換えず、末尾への追加として反映する。

- 一括生成の対象を**事務所概要・料金・お問い合わせの3ページ**に限定する。取扱業務・専門家紹介・FAQは、Construction Order 003/004で獲得済みのCPT Archive URL（`/services/`, `/professionals/`, `/faq/`）で既に到達可能なため、重複するページを生成しない。ホームは対象外とし、Design System着手時に改めて扱う。
- Decision 016本体（明示操作のみで生成、重複生成・上書きをしない、生成物は通常のPageとしてCore Uninstall対象外）は変更しない。
- `docs/research/2026-08-27_construction_order_007_research.md`§20-1の要確認事項は、本Decisionにより**CLOSED**となった。

将来Setup / Onboardingを拡張するConstruction Orderでは、本セクションおよび`04_astrea_free_v1_preconstruction_decisions.md`のDecision 027を基準文書とする。
