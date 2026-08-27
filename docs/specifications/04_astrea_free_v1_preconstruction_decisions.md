# ASTREA FREE v1 着工前決定事項

## 目的

本書は、着工前監査（クロエ・コデミ）で検出された未決定事項について、ユーザーおよび設計側（コデちゃん）との仕様会議で正式にFIXした決定（Decision 001〜020）を記録する。

調査資料ではなく、正式仕様の一部として扱う。

00〜03の正式仕様との間で内容が食い違う場合、**本書のDecisionを最新の正式判断として扱う**。該当する既存仕様は本書公開後、必要な範囲で更新する（更新内容は各Decisionの「影響する既存仕様」に記載し、実際の変更履歴は `HISTORY.md` を参照）。

本書は2026-08-25の仕様会議で確定した内容を、クロエが既存仕様・既存監査との関係が分かる形に正式文書化したものである。会話ログの転記ではない。クロエ独自の新規判断は含まない。

参照した監査資料：

- [`docs/research/2026-08-25_astrea_free_v1_pre_construction_audit.md`](../research/2026-08-25_astrea_free_v1_pre_construction_audit.md)（クロエ）
- [`docs/research/2026-08-25_astrea_technical_foundation_research.md`](../research/2026-08-25_astrea_technical_foundation_research.md)（コデミ）

---

## Decision 001 — ASTREA FREE 配布戦略

**Status:** FIXED
**決定日:** 2026-08-25

### 決定内容

ASTREA FREEの製品仕様は、WordPress.org掲載要件によって決定しない。ASTREAの開発コンセプトに基づく「真のFREE」を正本として設計する。

そのうえでWordPress.org公式Theme Directoryへの掲載を積極的に目指し、必要な場合のみ、製品思想・主要機能・品質を損なわない範囲で公式配布向けの調整を行う。

公式掲載のためにFREEを意図的に不便にしたり、PRO購入を強制する設計にはしない。

原則として単一コードベースを維持し、実際に公式要件との衝突が発生した場合にのみ配布Editionの分離を検討する。

ASTREA FREEは単なる無料体験版ではない。FREE単体で本番運用可能なサイトを完成できる「真のFREE」であると同時に、ASTREAおよびProject-ifの価値を実際の製品品質によって伝える「最強の営業マン」と位置付ける。WordPress.org公式掲載は、ASTREA FREEの認知・信頼・普及を強化するための戦略として扱う。

ASTREA FREEは日本の士業ユーザーにとっての使いやすさを最優先する。WordPress.org掲載を目的として、海外向けテーマを日本語化したような設計へ変更してはならない。WordPress標準、国際化、GPL互換等には可能な限り準拠するが、公式掲載要件とASTREAの製品思想・日本向けUX・品質が衝突する場合は、ASTREAの製品思想を優先する。

Profession PROはWordPress.orgへの掲載を目的とせず、Project-ifによる有料販売・配布を基本とする。FREEとPROの関係については「FREEは、作れる。PROは、作ってくれる。」という最上位原則を維持する。

### 理由・設計原則

- 着工前監査（クロエ）§3.1で「配布チャネル未決定」を最重要論点として指摘した事項への回答。
- 00開発コンセプト「FREEは、作れる」「無料だから選ばれるテーマではなく無料でなくても選ばれるテーマ」という最上位原則を、配布判断より優先する。

### 影響する既存仕様

- 01仕様書 §23-24（PROライセンス、アップデート）の「Project-if共通ライセンス基盤」「WordPress標準に近い更新体験」は、FREEについてはWordPress.org標準Update経路（Decision 020）を意味することが明確になった。
- 02仕様書全体（WordPress標準の尊重、独自実装の抑制方針）と整合し、矛盾はない。

### Theme / Core / WordPress の責任境界

- Theme / Core：GPL互換ライセンスの遵守、WordPress.org Theme Review Guidelines相当の制約（同梱アセットのライセンス、電話ホーム不可等）を実装時に確認する。
- Profession PRO：配布・ライセンス管理はProject-if独自基盤（将来のLicense Platform）が担う。FREEとは別チャネル。

### 実装時に守るべき事項

- Core同梱・強制インストールを前提とした実装をしない（WordPress.org方針との衝突を避けるため、推奨導線に留める。関連: Decision 002, 013）。
- 同梱するフォント・アイコン・JS等の第三者アセットのライセンスをGPL互換の観点で個別に確認する。

---

## Decision 002 — Theme / Core 責任分界とデータ正本

**Status:** FIXED

### 決定内容

ASTREA ThemeとASTREA Coreを明確に分離する。

テーマを変更しても保持すべき意味データ（Office Profile、Service、Price、FAQ、CASE / RESULTS / VOICE、Contact等）はASTREA Coreを正本とする。

Themeは表示（Templates、Template Parts、Patterns、Style Variations、Design System、Responsive / Accessibility表現）を担当する。

WordPressの通常投稿・固定ページ・メディア等、WordPress標準の仕組みで管理されるコンテンツはWordPress自身を正本とし、ASTREA独自の正本を重ねて作らない。

Patternは「挿入時の構成・初期表現」であり、業務データ（Service / Price / FAQ等）の正本にしない。Pattern挿入後のBlock markupはページごとに複製されるため、Core側の値変更を自動反映する経路にはならない。

### 理由・設計原則

- AGENTS.md §6-8（Theme / Core責任分界、ロックイン防止）、00開発コンセプト「テーマを変更しても残るべきデータはThemeへ閉じ込めない」の直接適用。
- 02仕様書 §4（Core情報一元管理）が要求する「一度入力した情報を複数箇所で入力させない」は、Patternを正本にした場合実現不可能なため、正本をCoreに一元化する必要がある。

### 影響する既存仕様

- 02仕様書 §3（製品構成）、§4（Core情報一元管理）、§5（Design System、Patternは少数精鋭）
- 01仕様書 §9-11（Themeと機能の分離）
- AGENTS.md §6, §8

### Theme / Core / WordPress の責任境界

- Theme：表示のみ。Core内部実装（DB構造、クラス）へ直接アクセスしない。
- Core：Office Profile等の永続データ、Contact処理、SEO / OGP / GA4設定と出力制御。
- WordPress：通常投稿・固定ページ・メディア・ユーザー等の標準コンテンツ。

### 実装時に守るべき事項

- Core側の保存API（Sanitization / Validation含む）を単一の入口に統一し、手入力・将来のPRO自動生成の双方から同じ経路を通す（Decision 013、将来PRO阻害リスクの回避）。
- Pattern内でCoreデータを直接ハードコードしない。動的な値の扱いはDecision 013の表示Architectureに従う。

---

## Decision 003 — Contact：メール送信基盤と通知先アドレス確認

**Status:** FIXED

### 決定内容

Contact機能はWordPress標準のメール送信機構（`wp_mail()`等）を利用する。Project-if独自のメール配送サーバーを必須としない。

通知先メールアドレスの登録・変更時は、確認メールと一回限り有効なTokenを含む確認URLにより、そのアドレスが実際に受信可能であることを確認する仕組みを設ける。

### 理由・設計原則

- AGENTS.md §7（WordPress標準の尊重）、§8（ロックインを作らない：Project-if側障害でサイトが止まらない）に基づき、メール配送を自社基盤へ依存させない。
- 通知先メールアドレスの入力ミス・受信不可を放置すると、問い合わせが管理者に届かず気づかれないという着工前監査（クロエ）§3.3で指摘したリスクに直結するため、登録時点での到達確認が必要。

### 影響する既存仕様

- 02仕様書 §15（CONTACT）の「メール送信中心とする」という記述は維持しつつ、「原則DB保存せず」の部分はDecision 004により置き換える。

### Theme / Core / WordPress の責任境界

- Core：通知先メールアドレスの保存、確認Token発行・検証、`wp_mail()`呼び出し。
- Theme：関与しない。

### 実装時に守るべき事項

- Tokenは推測困難な形式とし、一回限り・有効期限付きとする。
- 確認未完了のアドレスへは本番の問い合わせ通知を送らない（未確認状態を管理画面上に明示する）。

---

## Decision 004 — Contact：Coreへの一時保存・保存期間・自動削除

**Status:** FIXED

### 決定内容

問い合わせは送信時点でCoreへ即時保存する（メール配送の成否に関わらず保存する）。

保存期間は10 / 30 / 60 / 90日から選択可能とし、初期値は30日とする。

保存期間経過後は自動削除する。

### 理由・設計原則

- 02仕様書旧§15「原則DB保存せず、メール送信中心」はメール不達時に問い合わせが完全に失われるリスクを内包していた（着工前監査（クロエ）§3.3で指摘）。Coreへの一時保存により、メール不達でも問い合わせ内容自体は失われない設計へ変更する。
- 保存期間を無期限にせず自動削除することで、AGENTS.md §20「必要以上のデータを保存しない」の原則を維持する。

### 影響する既存仕様

- **02仕様書 §15（CONTACT）を修正する。**「問い合わせ内容を原則DB保存せず」という記述は本Decisionにより上書きされる。
- 02仕様書 §31「問い合わせCRM → FREE v1では行わない」は維持されるが、一時保存・一覧管理とCRM化は別概念であることを明記する（Decision 006参照）。

### Theme / Core / WordPress の責任境界

- Core：問い合わせデータの保存・保存期間管理・自動削除（WP-Cron等の標準機構を利用）。

### 実装時に守るべき事項

- 保存期間はサイト管理者が設定変更できるようにする。
- 自動削除はCronの遅延・失敗を考慮し、削除対象の再確認込みで設計する。
- 保存するのはフォーム項目相当のデータに限定し、不必要な追加情報（詳細なUser Agent等）を収集しない。

---

## Decision 005 — Contact：管理画面と通知タイミング

**Status:** FIXED

### 決定内容

管理画面に問い合わせ一覧、未確認状態、未確認件数を表示する。

通知タイミングは即時通知と指定時刻のまとめ通知から選択可能とし、初期値は即時通知とする。

### 理由・設計原則

- 02仕様書 §22 ASTREA Dashboardの「完了 / 推奨 / 任意のチェックリストで案内する」思想と整合させ、問い合わせの見落としを防ぐ最低限の運用UIを提供する。

### 影響する既存仕様

- 02仕様書 §15（CONTACT）、§22（ASTREA Dashboard）への追記事項。矛盾はない。

### Theme / Core / WordPress の責任境界

- Core：一覧画面・未確認状態管理・通知タイミング設定と実行。

### 実装時に守るべき事項

- 「未確認」は既読 / 未読に近い軽量な状態管理とし、Decision 006で除外したCRM的な案件ステータス管理へ発展させない。

---

## Decision 006 — Contact：CRM化しない境界とCSV Export

**Status:** FIXED

### 決定内容

FREE v1では問い合わせ機能をCRM化しない。高度な検索、顧客管理、案件管理等は作らない。

適切な管理権限を持つユーザーが、現在保存されている問い合わせをCSVで一括Exportできるようにする。

### 理由・設計原則

- 01仕様書 §26・02仕様書 §31の既存方針「問い合わせCRM→FREE v1では行わない」を維持しつつ、Decision 004で保存することになったデータをユーザーが自分の資産として持ち出せるようにする（AGENTS.md §8ロックイン防止の精神をContactにも適用）。

### 影響する既存仕様

- 02仕様書 §31（FREE v1でやらないこと）の記述は維持するが、一時保存・一覧・CSV Exportは別機能として実施することを明記する必要がある。

### Theme / Core / WordPress の責任境界

- Core：CSV Export機能、Capability Check。

### 実装時に守るべき事項

- Export操作は適切なCapability Checkを伴う。
- CSVには保存期間内のデータのみを含め、削除済みデータは対象外とする。

---

## Decision 007 — Contact：Spam / Bot対策の基本方針

**Status:** FIXED

### 決定内容

外部CAPTCHAを必須にせず、WordPress標準のSecurity機構、Nonce、Validation / Sanitization、Honeypot、連続送信抑制等の低負担な基本対策を標準搭載する。

曖昧なSpam判定のみを理由に正規の問い合わせを無断で削除しない。

基本思想は「相談者には簡単、Botには面倒」。

### 理由・設計原則

- 士業サイトの相談者に外部CAPTCHA等の追加負荷を強いることは、ASTREAの中心思想（AGENTS.md §4：ユーザーの時間を奪わない）と衝突する。
- 着工前監査（クロエ）§6で指摘したHoneypot / Rate Limitのトレードオフを踏まえ、正規利用者を誤って弾かない設計を優先する。

### 影響する既存仕様

- 02仕様書 §15（CONTACT）の「Nonce、Honeypot、入力検証、Sanitization、Rate Limit等のスパム・セキュリティ対策を実装時に検討する」を、標準搭載する確定方針へ格上げする。

### Theme / Core / WordPress の責任境界

- Core：Spam / Bot対策全般の実装。

### 実装時に守るべき事項

- Spam判定でグレーな問い合わせは「削除」ではなく「要確認」等のステータスに留め、管理者の目視判断を残す。
- 外部CAPTCHA導入は将来のオプションとして拒否しないが、v1の必須要件にはしない。

---

## Decision 008 — SEO Foundation：基本方針とOGP案内

**Status:** FIXED

### 決定内容

他SEO Pluginを原則必須とせず、WordPress標準機能（XML Sitemap含む）を最大限利用する。

OGP画像設定時には推奨サイズを明確に案内する。

SEO設定において100点を追わせず、短時間で必要十分な状態にしてユーザーを集客活動へ進ませることを優先する。

### 理由・設計原則

- 02仕様書 §16「SEOを知らないユーザーでも基本的に妥当な状態になる」「SEOスコアゲームを行わない」という既存方針の再確認・強化。

### 影響する既存仕様

- 02仕様書 §16（SEO Foundation）、§17（OGP）と整合。矛盾はない。

### Theme / Core / WordPress の責任境界

- Core：SEO設定UI、OGP画像アップロード時の推奨サイズ案内、XML Sitemap（WordPress標準へ委任）。

### 実装時に守るべき事項

- 他SEO Pluginを検出した場合の二重出力回避策はDecision 018（第三者Plugin）の方針に従う。

---

## Decision 009 — Search Console：実装範囲の限定

**Status:** FIXED

### 決定内容

Search ConsoleについてはHTMLタグ方式による所有権確認の支援をASTREA Coreで提供可能にする。

Search Console API、OAuth連携、検索順位分析等は作らない。

GA4による所有権確認が可能な場合は案内してよいが、前提とはしない。

WordPress標準のXML Sitemap URLと、Search Consoleへの登録手順を案内する。

### 理由・設計原則

- 着工前監査（クロエ）§3.4で指摘した「認証情報」の実装範囲の曖昧性を解消。軽量なメタタグ確認方式に限定することで、OAuth連携が要求するトークン管理・Security責任範囲の拡大（AGENTS.md §20）を回避する。

### 影響する既存仕様

- 01仕様書 §11「Search Consoleは必要な認証情報を管理画面から設定できる方向を検討する」を具体化する。
- **02仕様書 §18（GA4 / Search Console）に、実装範囲の上限を明記する追記が必要。**

### Theme / Core / WordPress の責任境界

- Core：HTMLタグ方式の確認用メタタグ出力、設定UI、Sitemap URLの案内表示。

### 実装時に守るべき事項

- OAuth / APIキー等の重い認証情報を保存する設計にしない。

---

## Decision 010 — Breadcrumb：視覚UIと構造化データの両方対応

**Status:** FIXED

### 決定内容

視覚的なBreadcrumb表示と、検索エンジン向けのBreadcrumbList構造化データの両方を標準対応する。

WordPress標準の投稿階層・分類（Taxonomy）等を利用し、Breadcrumb専用の独自データ正本を作らない。

過剰なユーザー設定を要求しない。

### 理由・設計原則

- 着工前監査（クロエ）§3.5で指摘した「Breadcrumbが視覚UIか構造化データのみかが不明」という曖昧性を解消。専門家サイトの「迷わない導線」にも資する。

### 影響する既存仕様

- **02仕様書 §16（SEO Foundation）の「Breadcrumb」記載を具体化する追記が必要。**
- 02仕様書 §14（Header / Navigation / Footer）に、視覚Breadcrumbの表示責任がTheme側にあることを補足する。

### Theme / Core / WordPress の責任境界

- Theme：視覚的なBreadcrumb UIの表示（Template Part等）。
- Core：BreadcrumbList構造化データ（JSON-LD）の出力。
- WordPress：投稿階層・Taxonomy情報の提供元。

### 実装時に守るべき事項

- 視覚Breadcrumbのラベル・階層はWordPress標準のクエリ・階層APIから導出し、Core側に重複したBreadcrumb専用データを持たせない。

---

## Decision 011 — Child Theme：FREE v1では提供しない

**Status:** FIXED

### 決定内容

公式Child ThemeはFREE v1の標準構成・正式User Journeyには含めない。ASTREA公式Child Themeとしての配布は行わない。

ただしWordPress標準のChild Theme機構自体は意図的に妨げない。

### 理由・設計原則

- 着工前監査（クロエ）§3.6で指摘したスコープの曖昧性（01仕様書のみに登場し02仕様書に記載なし）を解消。

### 影響する既存仕様

- **01仕様書 §22（Child Theme）を修正する。**「公式Child Themeも用意する方向」という記述は、FREE v1では見送りという形へ更新する。将来のPRO / 上級者向け拡張としての検討自体は妨げない。

### Theme / Core / WordPress の責任境界

- Theme：WordPress標準のChild Theme機構（`style.css`の`Template:`ヘッダー等）を阻害する独自実装をしない。

### 実装時に守るべき事項

- Theme自体の実装が、一般的なChild Theme作成手順（テンプレート階層のオーバーライド等）を妨げる特殊な構造にならないよう注意する。

---

## Decision 012 — 技術識別子

**Status:** FIXED

### 決定内容

- ブランド：ASTREA
- Theme slug / text domain：`astrea`
- Core Plugin slug / text domain：`astrea-core`
- PHP namespace：`Astrea`
- 独自prefix：`astrea_`
- Block namespace：`astrea/*`
- ThemeとCoreは独立してVersion管理する。
- Semantic Versioningを基本思想とする。
- 正式初回Releaseは1.0.0とする。

### 理由・設計原則

- コデミによる技術基盤調査（P0-2）で指摘された「Theme / Core公開slug、namespace、text domain、versioning規約」の未決定を解消。命名の一貫性はAGENTS.md §7（独自実装の必要以上の増殖回避）とも整合する。

### 影響する既存仕様

- 新規事項。既存仕様との矛盾はない。

### Theme / Core / WordPress の責任境界

- Theme / Coreそれぞれが独立したVersion番号を持ち、WordPress標準のTheme / Pluginヘッダー規約に従う。

### 実装時に守るべき事項

- 全PHPコード・Block・Hook・Option名・Meta Key等はこの命名規約に統一する。
- 将来のPRO製品も命名規約の一貫性を踏襲する（職種別PROは別namespace / prefixを持つが、ASTREA Coreとの依存関係は明示する）。

---

## Decision 013 — Core → Theme 表示Architecture

**Status:** FIXED

### 決定内容

基本方針は「Coreが覚える、Blockがつなぐ、Themeが見せる、Patternが並べる」。

単純な再利用値（電話番号、事務所名等の単一値）はBlock BindingsなどWordPress標準機構を優先して用いる。

構造・処理を伴うもの（一覧・条件分岐・件数可変等）はDynamic Block等を用途に応じて利用する。

具体的な使い分けの詳細は実装設計時に最適解を選択する。

ThemeからCore内部実装へ密結合しない。独自Frameworkを不必要に作らない。

### 理由・設計原則

- コデミの技術基盤調査（§4.2）が提示した「Pattern / Block Bindings / 動的Blockの使い分け」の考え方を、正式方針として採用する。
- AGENTS.md §7（WordPress標準の尊重）、§8（ロックイン防止：独自Shortcode残骸を残さない）に直接対応する。

### 影響する既存仕様

- 02仕様書 §4, §7-13（Core情報一元管理、Service / Profile / Flow / Price / FAQ / CASE等の再利用要件）を実現する技術方針として位置づく。矛盾なし。

### Theme / Core / WordPress の責任境界

- Core：Block Binding sourceの登録、Dynamic Blockのサーバーサイドレンダリング、データ提供API。
- Theme：Patternとしての構成・レイアウト、Block Bindingsが接続されたCore Blocksの配置。
- WordPress：Block Bindings API等の標準機構本体。

### 実装時に守るべき事項

- Core無効化時、Block Bindingsが接続されたBlockはPHP fatalを起こさず、既定値・空欄等へ安全にフォールバックすること。
- Theme側からCoreのクラス・DBへ直接アクセスするコードを書かない。連携は登録済みのBlock Binding source・公開APIに限定する。

---

## Decision 014 — Theme Update方針

**Status:** FIXED

### 決定内容

通常の設定・Coreデータを不必要に破壊しない設計とする。

Templateへユーザーデータを直接固定しすぎず、Styles / Core / Binding / WordPress標準設定へ適切に分離する。

Site Editor等でユーザーがTemplate / Template Partを独自構造編集した場合は、WordPress標準挙動を尊重し、強制上書きしない。

ASTREA自身のBug・Security・必要なCompatibility問題は修正し、最新版として提供する。

### 理由・設計原則

- 02仕様書 §26が「Theme、Template、Template Part、Style Variation等の更新とユーザーカスタマイズの関係を明確化する」と自ら未決定を認めていた箇所（着工前監査（クロエ）§2.2で指摘）を解消する。
- FSEのSite Editor標準挙動（DB保存されたユーザー版がThemeファイルより優先される）を尊重することが、AGENTS.md §21「既存サイトを壊さないことを最優先」の実現方法となる。

### 影響する既存仕様

- **02仕様書 §26（Update）を更新する。**「明確化する」という未決定の記述を、本Decisionの確定内容へ置き換える。

### Theme / Core / WordPress の責任境界

- WordPress：Site Editor保存データとThemeファイルの優先順位を司る標準機構そのもの。
- Theme：Bug / Security修正の提供、新Pattern等の追加提供（既存ページへの強制適用はしない）。

### 実装時に守るべき事項

- Security上重大な修正がSite Editorでカスタマイズ済みのTemplateへ影響する場合の具体的な案内方法は、本Decisionでは方針のみ確定とし、詳細は実装フェーズで設計する。

---

## Decision 015 — FREE / PRO サポート原則

**Status:** FIXED

### 決定内容

FREE / PROともASTREA自身のBug、Security問題、標準機能不具合、必要なCompatibility問題は製品保守として修正する。

個別環境調査、設定代行、サイト制作、カスタマイズ、第三者Plugin調整、SEOコンサル、個別操作指導等は商品価格に標準では含めない。

不具合報告窓口は用意可能。ただし不具合報告は個別サポート契約を意味しない。

PROの価値は人的サポートではなく、士業別専門性・自動化・機能・制作時間短縮である。

### 理由・設計原則

- 01仕様書 §26（サポート方針）「個別サポートなし」の既存方針を、FREE / PRO両方に適用される形で明確化する。

### 影響する既存仕様

- 01仕様書 §26と整合。矛盾なし、明確化のみ。

### Theme / Core / WordPress の責任境界

- 該当なし（運用方針）。

### 実装時に守るべき事項

- 不具合報告窓口の実装（フォーム等）を設ける場合、Contact機能（Decision 003-007）と混同しない設計とする。

---

## Decision 016 — 初期セットアップ

**Status:** FIXED

### 決定内容

Theme / Core有効化だけでページ等を勝手に大量生成しない。

ユーザーの明示操作により、ホーム・事務所概要・取扱業務・料金・FAQ・問い合わせ等の基本ページをまとめて生成可能にする。

海外デモデータのImportという思想ではなく、「ユーザー自身のサイトを作り始められる状態」を生成する。

FREEは全士業共通基盤であり、士業固有の専門ページ・専門知識・高度な自動生成はProfession PROの価値領域とする。

再実行しても重複生成・既存ページの無断上書きをしない。

Privacy Policyは法的完成文章を保証するのではなく、WordPress標準Privacy機能を活用した設定・編集を案内する。

セットアップは何度でも再度開ける。途中終了可能。不足分だけ後から追加可能。全項目完了を公開条件にしない。

設定リセットとユーザーデータ削除を分離する。

### 理由・設計原則

- 02仕様書 §21（初回セットアップ）「設定地獄へ放り込まない」「60点公開思想」の直接的な具体化。
- AGENTS.md §5（60点公開思想）、§3（PROはFREEの制限解除ではなく自動化）との整合。

### 影響する既存仕様

- 02仕様書 §21（初回セットアップ）、§22（ASTREA Dashboard）と整合。矛盾なし、具体化のみ。
- 03仕様書（ユーザージャーニー）の「必要なページを作る」ステップの実現方法として位置づく。

### Theme / Core / WordPress の責任境界

- Core：基本ページの一括生成機能、再実行時の重複防止ロジック、設定リセット機能。
- WordPress：生成された固定ページ自体（WordPress標準の投稿データとして保存され、Core専用データにしない）。

### 実装時に守るべき事項

- 生成したページは通常のWordPress固定ページとして保存し、ユーザーが通常のエディタで自由に編集・削除できるようにする（Decision 019のCore Uninstallとの整合：生成物はユーザーコンテンツとして扱う）。
- 再実行時の重複防止は、生成済みページを安全に判定できる仕組みで行う。

---

## Decision 017 — Accessibility

**Status:** FIXED

### 決定内容

Accessibilityは追加オプションではなく、ASTREAの標準品質として扱う。

ユーザーに専門設定を要求せず、Template / Pattern / Block / Form / Navigation / Style等を普通に使うだけで基本的Accessibilityが確保される設計を目指す。

完全準拠（例：WCAG特定レベルの無条件保証）等は保証しない。

### 理由・設計原則

- 02仕様書 §24、AGENTS.md §11の既存方針の再確認・明確化。「保証」の範囲について過剰な約束をしないことを明記する。

### 影響する既存仕様

- 02仕様書 §24（Accessibility）と整合。矛盾はない。

### Theme / Core / WordPress の責任境界

- Theme：表示・操作に関わるAccessibility（Keyboard、Focus、Contrast、Semantic HTML等）。
- Core：Contact等のForm・管理画面のAccessibility。

### 実装時に守るべき事項

- 「準拠を保証しない」を品質を下げる口実にしない。AGENTS.md §5（60点公開思想は品質妥協の根拠にしない）を遵守する。

---

## Decision 018 — 第三者Plugin共存方針

**Status:** FIXED

### 決定内容

Plugin Ecosystemとの共存を前提とし、ユーザーを囲い込まない。

主要な既知Pluginとの実際の競合が予想される場合、検出・案内可能にする。

必要に応じて、ASTREA側とPlugin側のどちらの重複機能を利用するか選択できる設計を検討する。

未知のPluginを推測で制御しない。

競合事例が判明したものは、Updateで既知リストへ追加可能とする。

第三者Pluginすべての組み合わせについての動作保証はしない。

### 理由・設計原則

- 02仕様書 §16「他SEO Pluginを利用したいユーザーを妨害しない」を、SEO Plugin以外の一般的な第三者Pluginにも拡張した方針。
- 着工前監査（クロエ）§7で指摘したSEO Plugin競合検出の設計課題（既知Plugin名のハードコードは保守困難）に対し、「既知リストへの追加式」という運用方針で応える。

### 影響する既存仕様

- **02仕様書 §16（SEO Foundation）の対象範囲をSEO Plugin全般へ一般化する追記が必要。**

### Theme / Core / WordPress の責任境界

- Core：既知Plugin検出ロジックと競合回避（機能の自動無効化・切替UI）。

### 実装時に守るべき事項

- 検出リストはコードとして保守し、正式仕様の追加変更なしにUpdateで追加できる設計とする。
- 未知Pluginとの競合が判明した場合の報告経路をDecision 015の不具合報告窓口と接続する。

---

## Decision 019 — Core Uninstall

**Status:** FIXED

### 決定内容

Core無効化ではデータを削除しない。再有効化で再利用可能とする。

Plugin削除時も、初期状態ではCore所有データ（Office Profile、Service、Price、FAQ、問い合わせ等）を保持する。

完全削除はユーザーが明示的に選択した場合のみ実行可能とし、不可逆性と削除対象を明示したうえで確認を要求する。

ASTREAが生成したものであっても、通常の固定ページ・投稿・Media等のユーザーコンテンツはCore Uninstallに巻き込んで自動削除しない。

### 理由・設計原則

- AGENTS.md §8、02仕様書 §27の既存方針を、WordPress標準の削除機構（`register_uninstall_hook()` / `uninstall.php`、`WP_UNINSTALL_PLUGIN`定数チェック）に沿って明確化する。無効化（Deactivate）と削除（Uninstall / Delete）を区別するWordPress標準ベストプラクティスと一致する。

### 影響する既存仕様

- 02仕様書 §27（データ保持・アンインストール）と整合。矛盾なし、実装機構レベルでの明確化。

### Theme / Core / WordPress の責任境界

- Core：`uninstall.php`等による削除制御、削除確認UI。
- WordPress：無効化 / 削除の標準ライフサイクル（フック）。

### 実装時に守るべき事項

- Decision 004の「保存期間経過後の自動削除」（時間ベース）と、本Decisionの「Uninstall時の完全削除」（ユーザー操作ベース）は別の削除経路であることを、実装・ドキュメント双方で区別する。
- Decision 016で生成したページはユーザーコンテンツとして扱い、Core Uninstallの対象に含めない。

---

## Decision 020 — 開発・CI、PHP / WordPress Compatibility、Release / Update

**Status:** FIXED

### 決定内容

**開発・CI**

- 標準ローカル環境はWSL2 + Docker + `@wordpress/env`とする。
- 再現可能な構築手順をRepositoryへ保持する。
- 必要十分な自動検査とGitHub Actions等によるCIを導入する。ただしテスト自体を目的化しない。
- Release前には自動Testだけでなく実動作確認も行う。

**PHP / WordPress**

- ASTREA FREE 1.0はPHP 8.3以上を初期最低ラインとして設計する。
- 新PHP Versionへの対応追加と、旧PHP Versionの対応終了は別々に判断する。新PHP対応追加のみを理由に旧PHPを即座に切らない。
- ASTREAがPHP Version変更を案内する場合、第三者PluginおよびHosting環境の対応状況を事前確認するよう必ず注意喚起する。
- ASTREA自身のPHP互換性は製品保守対象とするが、第三者Plugin固有のPHP互換性はサポート対象外とする。
- WordPressは7.0以上を初期Targetとし、7.1を主開発・Test基準とする。
- 重要な標準API利用等によりWordPress 7.0対応がArchitecture上明確な不利益となる場合は、1.0公開前に7.1以上へ引き上げ可能とする。

**Release / Update**

- 開発版と正式Releaseを区別する。正式初回Releaseは原則1.0.0とする。
- Theme / Coreは独立してVersion管理する。
- WordPress.org掲載版はWordPress標準Update経路を基本とする。FREE v1で巨大な独自Update基盤を作らない。
- Release Checklistを用意する。
- HISTORY.mdは開発チーム向け履歴、CHANGELOGはユーザー向けの意味のある追加・改善・修正を記録する、という役割分担とする。
- 重大Bug / Security問題は必要に応じてPatch Releaseで対応する。
- 基本思想は「普通のWordPress Themeとして普通に更新できる」。

### 理由・設計原則

- コデミの技術基盤調査（§6・§7・§8・§9）が提示した暫定マトリクス・P0項目を、正式決定として確定する。
- Decision 001（WordPress.org掲載を目指す）と整合し、独自Update基盤への先行投資を避けることでFREE v1公開までのスコープを現実的に保つ。

### 影響する既存仕様

- 02仕様書 §29（Compatibility / Migration）の「対応WordPress・PHPバージョンを明示する」という未決定を確定する。
- 02仕様書 §30（品質管理）、AGENTS.md §19（テスト）と整合。
- 01仕様書 §24（アップデート）の「WordPress標準に近い更新体験」を、FREE v1に限り「WordPress標準Update経路そのもの（WP.org配布）」として具体化する。

### Theme / Core / WordPress の責任境界

- WordPress：WP.org経由の標準Update配信機構。
- Theme / Core：`Requires at least` / `Tested up to` / `Requires PHP`ヘッダーの正確な維持。

### 実装時に守るべき事項

- PHP 8.3という初期最低ラインが、想定ユーザー層（独立直後の士業個人が利用する日本国内ホスティング）の実態と乖離しないか、公開前（2027年）に再確認する（着工前監査（クロエ）§11参照）。
- コデミ書のP0-3（Schema Version / Migration契約の詳細設計）は、本Decisionでは対応方針（Versioned Migration）のみ確定とし、具体的な実装機構は実装フェーズで設計する。

---

## Decision 021 — ASTREA Core の位置付け（任意Plugin・公式推奨）

**Status:** FIXED
**決定日:** 2026-08-26

### 決定内容

ASTREA Coreは任意Pluginとする。ただし、ASTREAの主要機能を利用するための公式推奨Pluginとして位置付ける。

ASTREA ThemeはCoreのインストール・有効化を強制しない。Coreが存在しない、または無効化されている場合でも、Theme単体でWordPress Block Themeとして安全に動作し、PHP Fatal等を発生させないこと。

一方、Office Profile、Service、Price、FAQ、Contact、SEO / OGP、Search Console支援、Coreデータ再利用、セットアップ支援等、ASTREA Coreが責任を持つ機能については、Coreを有効化した場合に利用可能となる。

基本原則：「Coreは推奨する。しかしThemeを人質にしない。」

### 理由・設計原則

- 本書「本書に基づき残る確認事項」1（Core必須 / 任意の明文化）、および `2026-08-26_astrea_free_v1_final_pre_construction_audit.md` セクション5で「非ブロッキングだが正式な一文が必要」と指摘していた事項への正式な回答。本Decisionにより当該事項はCLOSEDとする。
- Decision 001（WordPress.org公式ディレクトリ掲載を目指す）が要求する、Core同梱・強制インストール不可という制約と直接整合する。
- Decision 013（ThemeからCore内部実装へ密結合しない）の帰結を、製品ポジショニングとして明文化するもの。
- 既存のDecision 001〜020の意味を変更するものではなく、Decisionの間で論理的に導けていた結論を正式に確定するものである。

### 影響する既存仕様

- **02仕様書 §3（製品構成）を更新する。**「Core任意・公式推奨」の位置付けを明記した。

### Theme / Core / WordPress の責任境界

- Theme：Core非依存でWordPress標準投稿・標準Blockを表示できる状態を維持する。Core検出時のみ、Decision 013の表示Architecture（Block Bindings等）を通じてCoreデータを利用する。
- Core：有効化された場合のみ、Office Profile等の機能一式を提供する。
- WordPress：Plugin有効化状態の標準的な検出機構。

### 実装時に守るべき事項

- Theme側のテンプレート・Pattern・Block登録処理は、Core非活性時にPHP Fatal・White Screen・編集画面のBlock登録エラー等を起こさないことを必須のテスト観点とする。
- Core非活性時にTheme単体でCoreデータを表示する箇所（Header / Footer等）は、空欄・プレースホルダー等への安全なフォールバックを行う。
- 初回有効化時、Core未導入であれば「Coreを推奨」する案内を提示してよいが、案内をスキップしてもTheme自体は機能停止しない。

---

## Decision 022 — ASTREA FREEの対象利用形態、および Office / Professional Profile 責任境界

**Status:** FIXED
**決定日:** 2026-08-26

### 決定内容

ASTREA FREEは、個人で運営する士業事務所だけを前提としない。以下の双方を正式な対象とする。

- 個人の士業事務所
- 士業法人、および複数の専門家が所属する事務所（例：行政書士法人、司法書士法人、税理士法人、社会保険労務士法人等）

Core内のデータ責任を以下の2概念に明確に分離する。

- **Office（Office Profile）**＝事務所・法人・専門家組織そのものの情報。事務所名、所在地、電話番号、営業時間、休業情報、SNSリンク等。
- **Professional Profile**＝そこに所属する専門家個人の情報（資格・肩書、経歴、学歴、所属、登録情報、写真、紹介文等）。**0〜複数人**を将来的に扱えるデータ構造として設計する（Office 1 : Professional Profile 0..N）。

Office Profileの「代表者名」等の項目について、将来のUI・データ設計・表示設計では、個人事務所のみを暗黙の前提とした不自然な設計にしない。ただし、法人番号・資本金・設立年月日等の一般的な法人台帳情報を大量に追加することは今回のFIXの目的ではなく、ASTREAのWebサイト構築に必要な情報だけを扱う。

CTA・相談方法はOffice Profile / Professional Profileのいずれにも含めず、別責任（CTA / Consultation）として扱う。

ACCESSページ固有の最寄駅・徒歩時間・駐車場・地図表示方式は、Office Profileに含めず、ACCESSの責任とする。所在地そのものはOffice Profileを正本として再利用する。

### 理由・設計原則

- AGENTS.md §6が元々「Office Profile」と「専門家情報」をCoreの責任範囲として別項目に列挙していたことと整合させる。
- 士業法人・複数専門家事務所もFREEの正式な対象顧客であるというユーザー指示に基づく。
- Construction Order 002実施報告（`docs/research/2026-08-26_construction_order_002_report.md`）で発見した「Office Profile項目範囲の要確認事項」への正式な回答（当該事項は本Decisionによりクローズする）。

### 影響する既存仕様

- **01仕様書 §3（想定ユーザー）を更新する。** 個人事務所に加え、法人・複数専門家事務所を正式対象として明記する。
- **02仕様書 §4（ASTREA Core ― 情報一元管理）を更新する。**「事務所情報、代表者情報、資格・所属、住所、電話、営業時間、定休日、臨時休業、アクセス、相談方法、CTA、SNS等」という一括列挙を、Office Profile／Professional Profile／CTA・相談方法／ACCESS固有情報へ明確に分離する。
- **02仕様書 §8（PROFILE）を更新する。** 専門家個人紹介の構造をProfessional Profileとして位置づけ、0〜複数人対応を明記する。
- **02仕様書 §13（ACCESS）を更新する。** 所在地はOffice Profileを正本として再利用し、最寄駅・徒歩時間・駐車場等はACCESS固有情報として区別する。
- `docs/research/2026-08-26_construction_order_002_report.md` §1・§8の要確認事項をCLOSEDとする。
- `05_astrea_free_v1_construction_baseline.md`：既存の変更管理方針（同書§19）に従い、凍結済みの本文は書き換えず、末尾に本Decisionへの参照を追記する。

### Theme / Core / WordPress の責任境界

- Core：Office Profile（Construction Order 002で実装済み、変更なし）。Professional Profile（未実装。将来のConstruction Orderで0..N件の複数専門家データとして設計する）。
- Theme：Office Profileの表示（Header / Footer等、実装済み）。Professional Profileの表示（専門家紹介ページ等、将来実装）。

### 実装時に守るべき事項

- Construction Order 002のOffice Profile実装（`astrea_core_office_profile` Option、管理画面、Block Bindings Source `astrea-core/office-profile`）は、本Decisionを理由に作り直さない。
- Professional Profileを実装する際は、単一レコードのOptionではなく、0..N件を安全に扱えるデータ構造（WordPress標準機能— 例えばカスタム投稿タイプ等 — を優先し、独自DB Tableは避ける）を採用する。
- Office Profileへ将来フィールドを追加する場合、個人事業主1名のみを暗黙の前提とした表現・UIにしない。
- 法人台帳情報（法人番号・資本金・設立年月日等）をOffice Profileへ追加することは、本Decisionの範囲外である。追加が必要になった場合は独自判断で拡張せず、改めて仕様判断を仰ぐ。

---

## Decision 023 — 代表者情報の正本は Professional Profile

**Status:** FIXED
**決定日:** 2026-08-26

### 決定内容

代表者は「人」に属する情報であり、「組織」であるOffice Profileの管轄ではない。代表者情報の正本を**Professional Profile**とする。

- Professional Profileに、その人物が代表者であることを識別するBoolean flag（`is_representative`、postmeta `astrea_professional_is_representative`）を追加する。
- 具体的な肩書テキスト（代表社員・所長・代表税理士等）は、既存の「資格・肩書」フィールド（`qualification`）へ入力する。肩書テキスト専用の新しいフィールドは追加しない。
- **複数の専門家を代表者として指定できるかについては、一意制約を設けない。** 士業法人には複数の代表社員が存在しうるため、単一代表者へ制限する明確な根拠が既存仕様・実態のいずれにも見当たらなかった。0人・1人・複数人のいずれの状態も許容する。**この判断（本当に複数代表者を許容すべきか）自体は、要確認事項として残す**（セクション末尾参照）。
- Office Profileの`representative_name`は廃止する。ただし、「一度入力した情報は可能な限り再利用する」という原則に基づき、既存データを乱暴に削除しない。Schema v1→v2 Migrationにより、既存の`representative_name`値を`legacy_representative_name`という内部専用キーへ保存し直し、Professional Profileで代表者が指定されるまで管理画面に案内を表示する。**Migrationは、既存の人物データを推測して自動生成・自動統合することはしない**（Professional Profile 0件時・同名Professional存在時・異なるProfessional存在時のいずれにおいても、自動的な人物生成・自動flagづけは行わない）。

### 理由・設計原則

- 代表者情報の所在をOfficeからPersonへ移すというDecision 022の帰結を、具体的なデータ移行方式まで含めて確定する。
- 自動Migrationでの人物生成・自動flagづけは、誤った代表者情報を生成するリスクがあり、正確性より危険性が上回ると判断した。
- 複数代表者を制限する明確な仕様上・実務上の根拠がないため、クロエの独自判断で一意制約を追加しない。

### 影響する既存仕様

- **02仕様書 §4（Core情報一元管理）を更新する。** Office Profileの一元管理対象から代表者情報を除外する記述へ改める。
- **02仕様書 §8（Professional Profile）を更新する。** 代表者識別（`is_representative`）を追加する。
- `docs/research/2026-08-26_construction_order_002_report.md`・`docs/research/2026-08-26_construction_order_003_report.md`の該当する要確認事項をCLOSEDとする。

### Theme / Core / WordPress の責任境界

- Core：`is_representative` postmetaの登録・保存、Schema v1→v2 Migration Runner（`Astrea\Core\OfficeProfile\maybe_migrate()`）、管理画面通知。
- Theme：変更なし（Theme側は現時点で代表者情報を一切表示していないため、本Decisionによる影響を受けない）。

### 実装時に守るべき事項

- 既存のOffice Profile Schema v1データに対し、Professional Profileの人物データを自動生成・自動統合しない。
- 複数代表者を禁止する一意制約を実装しない（要確認事項として残す）。
- `legacy_representative_name`は内部専用キーとして扱い、`get_office_profile()`の一般的な公開契約（ThemeやPROが読むべき値）には含めない。

---

## Decision 024 — Core無効時のCore所有URLに関する保証範囲

**Status:** FIXED
**決定日:** 2026-08-26

### 決定内容

ASTREA FREE v1では、Core無効時にCore所有機能のURL（例：`/professionals/`）が必ずHTTP 404を返すことを保証対象と**しない**。保証するのは以下の5点のみとする。

1. Theme全体が正常動作する
2. Fatal / Warning / Noticeを発生させない
3. 壊れたMarkupを表示しない
4. Core所有データを残留表示しない
5. Core再有効化後に正常復帰する

Construction Order 003で確認された「Core無効時に`/professionals/`がHTTP 200のFallback（サイトのトップページ相当）になる」という挙動は、上記5点をすべて満たしているため、FREE v1のBlocking Bugとして扱わない。

このHTTP Statusを是正するためだけに、ThemeへCore所有のCPT名・URL構造等の知識を持たせる実装は行わない。

### 理由・設計原則

- Decision 021「Coreは推奨する。しかしThemeを人質にしない。」の原則を、より具体的な保証範囲として明文化する。
- 完璧な404レスポンスを追求するためにTheme/Core間の望ましくない密結合を生むことを避ける。

### 影響する既存仕様

- `docs/research/2026-08-26_construction_order_003_report.md` §13の要確認事項2をCLOSEDとする（対応不要と正式判断）。
- `05_astrea_free_v1_construction_baseline.md`：既存の変更管理方針に従い、Core無効時の要件（セクション4）に本Decisionへの参照を追記する。

### Theme / Core / WordPress の責任境界

- 変更なし。ThemeはCore所有のCPT・URL知識を持たない状態を維持する。

### 実装時に守るべき事項

- 将来、Core所有URLの404挙動改善が必要になった場合も、Theme経由ではなくCore自身（例：`template_redirect`等）で解決する方式を優先する。

---

## Decision 025 — Professional Profile 代表者の人数制約（複数許可）

**Status:** FIXED
**決定日:** 2026-08-26

### 決定内容

Professional Profileの「代表者」指定（`is_representative`）は、**0〜複数人**を正式に許可する。ASTREA FREEは単一代表者を強制しない。

現在実装済みのboolean（`astrea_professional_is_representative`）による各Professional単位での指定方式を、そのまま正式仕様として承認する。一意制約（単一代表者への制限）は設けない。

### 理由・設計原則

- 個人事務所では通常代表者は1人だが、士業法人等では複数の代表者（例：複数の代表社員）が存在しうる。
- 代表者をWebサイト上で明示しない運用（0人）も妨げない。
- ASTREA側が実態以上の制約を設ける必要がない。
- Decision 023で残されていた要確認事項（本書「残る確認事項」旧項目7）への正式な回答。

### 影響する既存仕様

- Decision 023の「複数代表者への一意制約は実装していない」という実装状態を、正式仕様として確定する（Decision 023自体の内容変更ではない）。
- 本書「本書に基づき残る確認事項」旧項目7を本Decisionにより**CLOSED**とする。
- `docs/research/2026-08-26_construction_order_003a_report.md`の残存要確認事項を本Decisionにより**CLOSED**とする。

### Theme / Core / WordPress の責任境界

- 変更なし。Decision 023で確定した実装（`get_representatives()`が一意制約なしで複数件を返す）をそのまま正式仕様とする。

### 実装時に守るべき事項

- 本Decisionによる製品コード変更は不要（現状の実装を追認するのみ）。
- 将来、単一代表者への制限が必要と判明した場合は、独自判断で追加せず改めて仕様判断を仰ぐ。

---

## Decision 026 — SEO Foundation：構造化データ方針の確定

**Status:** FIXED
**決定日:** 2026-08-27

### 決定内容

CONSTRUCTION ORDER 006（SEO Foundation）着工にあたり、Construction Order 004からの引き継ぎ事項および同006着工前調査（`docs/research/2026-08-27_construction_order_006_research.md`）を踏まえ、以下を正式に確定する。

**1. Price → Offer / PriceSpecification は自動出力しない**

ASTREA Priceは、固定額・○円〜・月額・時間制・無料・個別見積・自由表記等を**単一の自由記述モデル**で扱う（Construction Order 004で確定済みのデータモデル、変更なし）。この判断の理由は「schema.org priceが数値必須だから」という技術的制約のみに帰着させない。より正確には、**自由記述モデルという設計そのものが、Offer / PriceSpecificationとして意味的に正確かつ一貫した構造化データを安全に自動生成できる性質を持たないため**、FREE v1では出力しないと判断する。将来、PRO等で構造化された料金モデル（数値専用フィールド等）を採用する場合は、本Decisionとは別の新しいDecisionとして改めて検討する。既存Priceデータモデルへの変更は行わない。

**2. Office Profileの通常週次営業時間について、`openingHoursSpecification`への対応を許可する**

Office Profileの`business_hours.weekly`（通常の曜日別営業時間）は、既存データのみを用いて`schema.org/openingHoursSpecification`へ安全に対応付け可能と判断し、対応を許可する。ただし、`business_hours.exceptions`（臨時休業・年末年始・夏季休業等の期間指定休業）は、通常営業時間とは意味が異なるため、**FREE v1では`openingHoursSpecification`へ変換しない**。通常営業時間と例外休業を意味的に混在させないことを優先する。新規入力項目は追加しない。

**3. SEO Plugin検出の初期対象は限定的な既知シグネチャリストとする**

Decision 018（第三者Plugin共存方針）の「既知Plugin検出・Update時追加式リスト」運用に従い、初期検出対象を以下の主要SEO Pluginに限定する：Yoast SEO、All in One SEO、Rank Math、SEOPress。検出は各Pluginが公開する安全な存在確認手段（例：定数・クラスの存在確認等、Plugin内部の非公開APIに深く依存しない方法）に限定する。巨大な互換表は作らず、未知Pluginは推測で停止しない。本リストは将来Updateで追加可能な構造とする。

**4. FAQPage JSON-LDはFREE v1で実装しない**

FAQ意味データおよび通常HTML表示（Construction Order 004で実装済み）は維持するが、FAQPage構造化データ（JSON-LD）は実装しない。理由：Google検索のFAQ Rich Resultは2026年5月7日付で完全に廃止されており（着工前調査で一次情報確認済み）、唯一の実益が失われているため。将来、検索エンジンやAI検索等で明確な実益が再び確認された場合のみ、その時点の仕様を調査したうえで再検討する。

**5. Office / Professional のSchema.org型対応方針を確定する**

Office Profile → `Organization`。Professional Profile → `Organization.employee`内の`Person`。一般用途の`ProfessionalService`型はSchema.org側で非推奨（deprecated）とされているため採用しない。特定士業へ固定したSchema型への決め打ちは行わない（FREE共通版としての中立性を優先する）。代表者フラグ（`is_representative`）は、対応するSchema.org標準プロパティが存在しないため、JSON-LD上の独自プロパティへ変換しない。複数のProfessional Profile（Decision 022・025）はすべて`employee`配列へ列挙し、並び順は既存の確定表示順（`menu_order`→`title`→`ID`）をそのまま利用する。

### 理由・設計原則

- Construction Order 004の実施時に「Price（自由記述）と構造化データの整合方法」「FAQPage構造化データの実装要否」を、本書「残る確認事項」項目3として意図的に先送りしていた。本Decisionはその正式な回答である。
- 「データがあるから全部JSON-LDにする」という設計を禁止する指示に基づき、各データについてSchema.orgとしての意味・Google Rich Resultとしての実益・誤用リスクを個別に検討した（詳細は着工前調査資料）。
- AGENTS.md §13・02仕様書§16・§31が明示する「SEOスコアゲームをしない」「FAQを量産装置にしない」という思想と、実益の失われた機能への投資を避けるという判断は整合する。

### 影響する既存仕様

- **02仕様書 §16（SEO Foundation）を更新する。** 構造化データの対象範囲（Organization/Person/BreadcrumbList、Offer/FAQPageは対象外）を明記する。
- `05_astrea_free_v1_construction_baseline.md`：既存の変更管理方針に従い、セクション17（実装フェーズへ委ねる技術詳細）項目3への回答として末尾に追記する。
- `docs/research/2026-08-26_construction_order_004_research.md`§6の要確認事項（Price/Offer整合方法、FAQPage実装要否）を、本Decisionにより**CLOSED**とする。
- 本書「本書に基づき残る確認事項」項目3を本Decisionにより**CLOSED**とする。

### Theme / Core / WordPress の責任境界

- Core：Organization/Person/BreadcrumbList JSON-LD生成、SEO Plugin既知シグネチャ検出、meta description/OGP/Search Console verification meta出力。
- Theme：視覚的Breadcrumb表示（Decision 010）。
- WordPress：title-tag、canonical、robots meta、XML Sitemap（`/wp-sitemap.xml`）、Site Icon、Featured Imageの標準提供（ASTREAはこれらを重複実装しない）。

### 実装時に守るべき事項

- Price/FAQPageの非出力はRegression Testで保証する（将来の実装ミスによる意図しない出力を防ぐ）。
- `openingHoursSpecification`は`business_hours.weekly`のみを対象とし、`exceptions`を含めない。
- SEO Plugin検出リストはコードとして保守し、正式仕様の追加変更なしにUpdateで追加できる設計とする（Decision 018と同じ運用）。
- 将来的にPrice構造化データ・FAQPage・特定士業向けSchemaが必要と判明した場合は、独自判断で追加せず改めて仕様判断を仰ぐ。

---

## 本書に基づき残る確認事項（新規Decisionではない）

以下は、Decision 001〜024によっておおむね解消されたが、正式仕様の文言として明示的な一文が未整備、または性質上「実装フェーズの技術詳細」に属するため、本書ではこれ以上の判断を追加しない事項である。クロエ独自の判断でこれらを確定させることはしない。

1. ~~ASTREA CoreがFREE v1において「必須」か「任意」かの明文化。~~ **CLOSED（2026-08-26 Decision 021により確定）。** ASTREA Coreは任意Plugin・公式推奨として位置付けることが正式にFIXされ、02仕様書 §3へ反映済み。
2. **Schema Version / Migrationの具体的な実装機構。** Decision 020で「Versioned Migration方式を採る」という方針は確定したが、バージョン番号の管理場所、Migration実行のタイミング等の詳細設計は実装フェーズで行う。
3. ~~Price（自由記述）と構造化データ（schema.org Offer等）の整合方法。~~ **CLOSED（2026-08-27 Decision 026により確定）。** ASTREA Priceの自由記述モデルはOffer/PriceSpecificationとして意味的に正確な構造化データを安全に自動生成できないため、FREE v1では出力しないことが正式に確定した。
4. **Pattern と Style Variation（Trust / Natural / Modern）の共有方式。** Design System設計時に決定する。
5. **Service / FAQ / CASE等が0件のときの空状態UIパターンの統一。** Pattern設計時に決定する。
6. ~~営業時間・臨時休業データモデルの詳細（将来の予約Pluginとの共有を見据えた設計）。~~ **CLOSED（2026-08-26 Construction Order 002により実装）。** `astrea_core_office_profile`内の`business_hours`（週次の定休日／開始・終了時刻＋臨時休業等の期間リスト）として実装済み。将来の予約Pluginとの共有インターフェースは未設計だが、データモデル自体は確定した。
7. ~~複数のProfessional Profileを「代表者」として同時に指定できることの是非。~~ **CLOSED（2026-08-26 Decision 025により確定）。** 0〜複数人を正式に許可し、一意制約は設けないことが正式仕様として確定した。

これらは最終監査（`docs/research/`配下の最終監査資料を参照）においてP0（着工前必須）ではなく、各設計フェーズでの決定事項として扱う。
